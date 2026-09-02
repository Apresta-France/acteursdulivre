<?php

declare(strict_types=1);

namespace Adl\Core;

use Adl\Models\Newsletter;
use Adl\Models\NewsletterCampaign;
use Adl\Models\Setting;
use Throwable;

final class NewsletterCron
{
    /**
     * @return array{
     *     ok: bool,
     *     skipped: bool,
     *     reason?: string,
     *     ran_at?: string,
     *     stats?: array<string, int|string>,
     *     errors?: list<string>
     * }
     */
    public static function run(bool $force = false): array
    {
        $lockPath = ADL_ROOT . '/storage/cron-newsletter.lock';
        $dir = dirname($lockPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $handle = fopen($lockPath, 'c+');
        if ($handle === false) {
            return ['ok' => false, 'skipped' => true, 'reason' => 'lock_unavailable'];
        }
        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            return ['ok' => true, 'skipped' => true, 'reason' => 'already_running'];
        }

        try {
            $stats = [
                'queued' => 0,
                'sent' => 0,
                'failed' => 0,
                'skipped' => 0,
                'pending' => 0,
            ];
            $errors = [];

            if (Newsletter::enabled() && ($force || self::weeklyDue())) {
                try {
                    $composed = NewsletterComposer::compose();
                    if (!$composed['empty'] && Newsletter::countByStatus(Newsletter::STATUS_CONFIRMED) > 0) {
                        NewsletterCampaign::queue($composed, 'weekly');
                        $stats['queued'] = 1;
                    }
                    Setting::set('newsletter_last_weekly_at', date('Y-m-d'));
                } catch (Throwable $e) {
                    $errors[] = $e->getMessage();
                }
            }

            $stats['skipped'] = NewsletterCampaign::skipUnsubscribedPending();
            if (Newsletter::enabled()) {
                $batch = NewsletterCampaign::pendingBatch(Newsletter::batchSize());
                foreach ($batch as $row) {
                    $deliveryId = (int) $row['id'];
                    $campaignId = (int) $row['campaign_id'];
                    if (!NewsletterCampaign::claimDelivery($deliveryId)) {
                        continue;
                    }
                    NewsletterCampaign::markSending($campaignId);
                    $unsub = url('/newsletter/desinscription/' . (string) $row['unsub_token']);
                    try {
                        NewsletterMailer::send(
                            (string) $row['email'],
                            (string) $row['subject'],
                            (string) $row['body_html'],
                            $unsub
                        );
                        NewsletterCampaign::markDelivery($deliveryId, $campaignId, 'sent');
                        $stats['sent']++;
                    } catch (Throwable $e) {
                        NewsletterCampaign::markDelivery($deliveryId, $campaignId, 'failed', $e->getMessage());
                        $stats['failed']++;
                        $errors[] = $e->getMessage();
                    }
                }
            }
            $stats['pending'] = NewsletterCampaign::pendingCount();

            return [
                'ok' => $errors === [],
                'skipped' => false,
                'ran_at' => date('c'),
                'stats' => $stats,
                'errors' => $errors,
            ];
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public static function weeklyDue(): bool
    {
        if (!Newsletter::enabled()) {
            return false;
        }
        $tz = new \DateTimeZone(Env::get('APP_TIMEZONE', 'Europe/Paris'));
        $now = new \DateTimeImmutable('now', $tz);
        if ((int) $now->format('N') !== Newsletter::weekday()) {
            return false;
        }
        if ((int) $now->format('G') < Newsletter::hour()) {
            return false;
        }
        $last = Newsletter::setting('newsletter_last_weekly_at', '');
        return $last !== $now->format('Y-m-d');
    }
}
