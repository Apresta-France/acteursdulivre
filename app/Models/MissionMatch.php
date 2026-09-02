<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;

final class MissionMatch
{
    public const MAX_RECIPIENTS = 30;
    public const USER_COOLDOWN_HOURS = 24;
    public const CANDIDATE_POOL = 120;

    /** @return list<array<string, mixed>> */
    public static function pendingMissions(int $limit = 6): array
    {
        $limit = max(1, $limit);
        $rows = Database::fetchAll(
            "SELECT m.*
             FROM missions m
             JOIN users owner ON owner.id = m.user_id
             WHERE m.status = 'open'
               AND m.match_alerted_at IS NULL
               AND TRIM(IFNULL(m.category_name, '')) != ''
               AND owner.status = 'active'
             ORDER BY m.created_at ASC, m.id ASC
             LIMIT {$limit}"
        );
        return $rows;
    }

    public static function markAlerted(int $missionId): void
    {
        Database::query(
            'UPDATE missions SET match_alerted_at = NOW() WHERE id = ? AND match_alerted_at IS NULL',
            [$missionId]
        );
    }

    public static function alreadyNotified(int $missionId, int $userId): bool
    {
        $row = Database::fetch(
            'SELECT id FROM mission_match_alerts WHERE mission_id = ? AND user_id = ? LIMIT 1',
            [$missionId, $userId]
        );
        return $row !== null;
    }

    public static function record(int $missionId, int $userId): void
    {
        Database::query(
            'INSERT IGNORE INTO mission_match_alerts (mission_id, user_id, sent_at) VALUES (?, ?, NOW())',
            [$missionId, $userId]
        );
    }

    /**
     * Prestataires disponibles, métier correspondant, sans mission gagnée.
     * Si le vivier dépasse 30, on garde les vitrines les plus remplies.
     *
     * @param array<string, mixed> $mission
     * @return list<array<string, mixed>>
     */
    public static function recipients(array $mission): array
    {
        $missionId = (int) ($mission['id'] ?? 0);
        $ownerId = (int) ($mission['user_id'] ?? 0);
        $trade = trim((string) ($mission['category_name'] ?? ''));
        if ($missionId < 1 || $trade === '') {
            return [];
        }

        $candidates = self::candidates($missionId, $ownerId, $trade);
        $ranked = [];
        foreach ($candidates as $row) {
            $userId = (int) ($row['id'] ?? 0);
            if ($userId < 1 || !self::rowHasTrade($row, $trade)) {
                continue;
            }
            $row['completion'] = self::completionFromRow($row);
            $row['is_verified'] = (($row['verification_status'] ?? '') === Profile::VERIFY_VERIFIED) ? 1 : 0;
            $ranked[] = $row;
        }

        usort($ranked, static function (array $a, array $b): int {
            $cmp = ((int) $b['completion']) <=> ((int) $a['completion']);
            if ($cmp !== 0) {
                return $cmp;
            }
            $cmp = ((int) $b['is_verified']) <=> ((int) $a['is_verified']);
            if ($cmp !== 0) {
                return $cmp;
            }
            $aLogin = strtotime((string) ($a['last_login_at'] ?? '')) ?: 0;
            $bLogin = strtotime((string) ($b['last_login_at'] ?? '')) ?: 0;
            return $bLogin <=> $aLogin;
        });

        return array_slice($ranked, 0, self::MAX_RECIPIENTS);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function candidates(int $missionId, int $ownerId, string $trade): array
    {
        $hours = self::USER_COOLDOWN_HOURS;
        $pool = self::CANDIDATE_POOL;

        return Database::fetchAll(
            "SELECT u.id, u.email, u.first_name, u.last_name, u.avatar_url, u.last_login_at, u.notify_missions,
                    p.id AS profile_id, p.title, p.presentation, p.city, p.hourly_rate, p.languages,
                    p.trades_json, p.skills_json, p.genres_json, p.languages_json,
                    p.experiences_json, p.education_json, p.verification_status,
                    (SELECT COUNT(*) FROM portfolio_items pi WHERE pi.profile_id = p.id) AS portfolio_count
             FROM users u
             JOIN profiles p ON p.user_id = u.id
             WHERE u.offers_services = 1
               AND u.status = 'active'
               AND u.id != ?
               AND IFNULL(u.notify_missions, 1) = 1
               AND (p.availability_status IS NULL OR p.availability_status = '' OR p.availability_status = ?)
               AND JSON_CONTAINS(p.trades_json, JSON_QUOTE(?), '$')
               AND NOT EXISTS (
                    SELECT 1 FROM applications won
                    WHERE won.user_id = u.id AND won.status = 'accepted'
               )
               AND NOT EXISTS (
                    SELECT 1 FROM orders o
                    WHERE o.seller_id = u.id AND o.status != 'cancelled'
               )
               AND NOT EXISTS (
                    SELECT 1 FROM invoices i
                    WHERE i.seller_id = u.id
                      AND i.status IN ('issued', 'overdue')
                      AND i.due_at < NOW()
               )
               AND NOT EXISTS (
                    SELECT 1 FROM applications applied
                    WHERE applied.mission_id = ? AND applied.user_id = u.id
               )
               AND NOT EXISTS (
                    SELECT 1 FROM mission_match_alerts sent
                    WHERE sent.mission_id = ? AND sent.user_id = u.id
               )
               AND NOT EXISTS (
                    SELECT 1 FROM mission_match_alerts recent
                    WHERE recent.user_id = u.id
                      AND recent.sent_at >= DATE_SUB(NOW(), INTERVAL {$hours} HOUR)
               )
             ORDER BY
                (p.verification_status = 'verified') DESC,
                CHAR_LENGTH(IFNULL(p.presentation, '')) DESC,
                u.last_login_at DESC
             LIMIT {$pool}",
            [$ownerId, Profile::STATUS_AVAILABLE, $trade, $missionId, $missionId]
        );
    }

    /** @param array<string, mixed> $row */
    private static function rowHasTrade(array $row, string $trade): bool
    {
        $trades = self::decodeJson($row['trades_json'] ?? null);
        return in_array($trade, $trades, true);
    }

    /** @param array<string, mixed> $row */
    private static function completionFromRow(array $row): int
    {
        return Profile::completeness([
            'avatar_url' => $row['avatar_url'] ?? '',
            'title' => $row['title'] ?? '',
            'presentation' => $row['presentation'] ?? '',
            'city' => $row['city'] ?? '',
            'trades' => self::decodeJson($row['trades_json'] ?? null),
            'skills' => self::decodeJson($row['skills_json'] ?? null),
            'genres' => self::decodeJson($row['genres_json'] ?? null),
            'languages_list' => self::decodeJson($row['languages_json'] ?? null),
            'languages' => $row['languages'] ?? '',
            'experiences' => self::decodeJson($row['experiences_json'] ?? null),
            'education' => self::decodeJson($row['education_json'] ?? null),
            'portfolio' => ((int) ($row['portfolio_count'] ?? 0)) > 0 ? [1] : [],
            'hourly_rate' => $row['hourly_rate'] ?? '',
        ]);
    }

    /** @return list<mixed> */
    private static function decodeJson(mixed $json): array
    {
        if (!is_string($json) || $json === '') {
            return [];
        }
        $data = json_decode($json, true);
        return is_array($data) ? array_values($data) : [];
    }
}
