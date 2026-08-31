<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;
use RuntimeException;

final class NewsletterCampaign
{
    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';
    public const STATUS_EMPTY = 'empty';

    /**
     * @param array{subject: string, html: string} $composed
     */
    public static function queue(array $composed, string $source = 'manual'): int
    {
        $subject = trim((string) ($composed['subject'] ?? ''));
        $html = (string) ($composed['html'] ?? '');
        if ($subject === '' || $html === '') {
            throw new RuntimeException('La lettre n\'a pas de contenu à envoyer.');
        }

        $subscribers = Newsletter::confirmed();
        Database::query(
            'INSERT INTO newsletter_campaigns (subject, body_html, source, status, created_at)
             VALUES (?, ?, ?, ?, NOW())',
            [$subject, $html, $source, $subscribers === [] ? self::STATUS_EMPTY : self::STATUS_QUEUED]
        );
        $id = (int) Database::lastId();
        if ($subscribers === []) {
            Database::query(
                'UPDATE newsletter_campaigns SET finished_at = NOW() WHERE id = ?',
                [$id]
            );
            throw new RuntimeException('Aucun abonné confirmé : rien à envoyer.');
        }

        $stmt = Database::pdo()->prepare(
            'INSERT INTO newsletter_deliveries (campaign_id, subscriber_id, email, status)
             VALUES (?, ?, ?, "pending")'
        );
        foreach ($subscribers as $row) {
            $stmt->execute([$id, (int) $row['id'], (string) $row['email']]);
        }

        return $id;
    }

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM newsletter_campaigns WHERE id = ?', [$id]);
    }

    /** @return list<array<string, mixed>> */
    public static function recent(int $limit = 12): array
    {
        return Database::fetchAll(
            'SELECT * FROM newsletter_campaigns ORDER BY id DESC LIMIT ' . max(1, $limit)
        );
    }

    /** @return list<array<string, mixed>> */
    public static function pendingBatch(int $limit): array
    {
        $limit = max(1, min(200, $limit));
        return Database::fetchAll(
            'SELECT d.*, c.subject, c.body_html, s.unsub_token
             FROM newsletter_deliveries d
             JOIN newsletter_campaigns c ON c.id = d.campaign_id
             JOIN newsletter_subscribers s ON s.id = d.subscriber_id
             WHERE d.status = "pending"
               AND s.status = "confirmed"
             ORDER BY d.id ASC
             LIMIT ' . $limit
        );
    }

    public static function markSending(int $campaignId): void
    {
        Database::query(
            'UPDATE newsletter_campaigns SET status = ?, started_at = COALESCE(started_at, NOW()) WHERE id = ?',
            [self::STATUS_SENDING, $campaignId]
        );
    }

    public static function markDelivery(int $id, int $campaignId, string $status, string $error = ''): void
    {
        Database::query(
            'UPDATE newsletter_deliveries
             SET status = ?, error = ?, sent_at = IF(? = "sent", NOW(), sent_at)
             WHERE id = ?',
            [$status, $error !== '' ? mb_substr($error, 0, 255) : null, $status, $id]
        );
        $col = $status === 'sent' ? 'sent_count' : ($status === 'failed' ? 'fail_count' : 'skip_count');
        Database::query(
            'UPDATE newsletter_campaigns SET ' . $col . ' = ' . $col . ' + 1 WHERE id = ?',
            [$campaignId]
        );
        self::maybeFinish($campaignId);
    }

    public static function skipUnsubscribedPending(): int
    {
        $rows = Database::fetchAll(
            'SELECT d.id, d.campaign_id
             FROM newsletter_deliveries d
             JOIN newsletter_subscribers s ON s.id = d.subscriber_id
             WHERE d.status = "pending" AND s.status != "confirmed"'
        );
        foreach ($rows as $row) {
            self::markDelivery((int) $row['id'], (int) $row['campaign_id'], 'skipped', 'désinscrit');
        }
        return count($rows);
    }

    public static function pendingCount(): int
    {
        $row = Database::fetch('SELECT COUNT(*) AS n FROM newsletter_deliveries WHERE status = "pending"');
        return (int) ($row['n'] ?? 0);
    }

    private static function maybeFinish(int $campaignId): void
    {
        $left = Database::fetch(
            'SELECT COUNT(*) AS n FROM newsletter_deliveries WHERE campaign_id = ? AND status = "pending"',
            [$campaignId]
        );
        if ((int) ($left['n'] ?? 0) > 0) {
            return;
        }
        Database::query(
            'UPDATE newsletter_campaigns SET status = ?, finished_at = NOW() WHERE id = ?',
            [self::STATUS_SENT, $campaignId]
        );
    }
}
