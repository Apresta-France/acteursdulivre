<?php

declare(strict_types=1);

namespace Adl\Core;

use Adl\Models\Mission;
use Adl\Models\MissionMatch;
use Adl\Models\Notification;
use Adl\Models\User;
use Throwable;

final class MissionMatchCron
{
    /**
     * @return array{
     *     ok: bool,
     *     skipped: bool,
     *     reason?: string,
     *     ran_at?: string,
     *     stats?: array<string, int>,
     *     errors?: list<string>
     * }
     */
    public static function run(bool $force = false): array
    {
        unset($force);
        $lockPath = ADL_ROOT . '/storage/cron-mission-match.lock';
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
                'missions' => 0,
                'recipients' => 0,
                'emails' => 0,
                'skipped_empty' => 0,
            ];
            $errors = [];

            foreach (MissionMatch::pendingMissions() as $row) {
                $stats['missions']++;
                try {
                    self::alertMission($row, $stats, $errors);
                } catch (Throwable $e) {
                    $errors[] = 'mission #' . (int) ($row['id'] ?? 0) . ' : ' . $e->getMessage();
                }
            }

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

    /**
     * @param array<string, mixed> $row
     * @param array<string, int> $stats
     * @param list<string> $errors
     */
    private static function alertMission(array $row, array &$stats, array &$errors): void
    {
        $missionId = (int) ($row['id'] ?? 0);
        $mission = Mission::find($missionId);
        if (!$mission || ($mission['status'] ?? '') !== 'open') {
            if ($missionId > 0) {
                MissionMatch::markAlerted($missionId);
            }
            return;
        }

        $recipients = MissionMatch::recipients($mission);
        if ($recipients === []) {
            MissionMatch::markAlerted($missionId);
            $stats['skipped_empty']++;
            return;
        }

        $title = (string) ($mission['title'] ?? 'Nouvelle recherche');
        $trade = (string) ($mission['category_name'] ?? '');
        $budget = (string) ($mission['budget'] ?? Mission::budgetLabel(
            isset($mission['budget_min']) ? (int) $mission['budget_min'] : null,
            isset($mission['budget_max']) ? (int) $mission['budget_max'] : null
        ));
        $path = (string) ($mission['href'] ?? ('/missions/' . ($mission['slug'] ?? '')));
        $link = url($path);

        foreach ($recipients as $user) {
            $userId = (int) ($user['id'] ?? 0);
            if ($userId < 1 || MissionMatch::alreadyNotified($missionId, $userId)) {
                continue;
            }

            $full = User::find($userId);
            if ($full) {
                $user = array_merge($user, $full);
            }

            try {
                Notification::upsertUnread(
                    $userId,
                    'Nouvelle recherche en ' . $trade,
                    '« ' . $title . ' » correspond à l’un de vos métiers.',
                    $path,
                    'mission_match',
                    'mission',
                    $missionId
                );
            } catch (Throwable $e) {
                $errors[] = 'notification #' . $userId . ' : ' . $e->getMessage();
            }

            $email = trim((string) ($user['email'] ?? ''));
            if ($email !== '' && User::wantsEmail($user, 'missions')) {
                try {
                    Mailer::sendTemplate('nouvelle-mission-metier', $email, [
                        'prenom' => (string) ($user['first_name'] ?? ''),
                        'metier' => $trade,
                        'titre' => $title,
                        'budget' => $budget,
                        'lien' => $link,
                    ]);
                    $stats['emails']++;
                } catch (Throwable $e) {
                    $errors[] = 'e-mail ' . $email . ' : ' . $e->getMessage();
                }
            }

            MissionMatch::record($missionId, $userId);
            $stats['recipients']++;
        }

        MissionMatch::markAlerted($missionId);
    }
}
