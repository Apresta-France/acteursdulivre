<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;

final class Recommendation
{
    /** @return list<array<string, mixed>> */
    public static function forTarget(int $userId, int $limit = 20, bool $includeHidden = false): array
    {
        $sql = 'SELECT * FROM recommendations WHERE target_id = ?';
        if (!$includeHidden) {
            $sql .= ' AND hidden_at IS NULL';
        }
        $sql .= ' ORDER BY created_at DESC LIMIT ' . max(1, $limit);

        try {
            $rows = Database::fetchAll($sql, [$userId]);
        } catch (\Throwable) {
            return [];
        }

        return array_map([self::class, 'present'], $rows);
    }

    public static function countForTarget(int $userId): int
    {
        try {
            $row = Database::fetch(
                'SELECT COUNT(*) AS n FROM recommendations WHERE target_id = ? AND hidden_at IS NULL',
                [$userId]
            );
        } catch (\Throwable) {
            return 0;
        }

        return (int) ($row['n'] ?? 0);
    }

    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        try {
            $rows = Database::fetchAll(
                'SELECT r.*, t.first_name AS target_first, t.last_name AS target_last
                 FROM recommendations r
                 JOIN users t ON t.id = r.target_id
                 ORDER BY r.created_at DESC'
            );
        } catch (\Throwable) {
            return [];
        }

        return array_map(static function (array $row): array {
            $row = self::present($row);
            $row['cible'] = trim(($row['target_first'] ?? '') . ' ' . ($row['target_last'] ?? ''));
            return $row;
        }, $rows);
    }

    public static function hide(int $id, bool $hide = true): void
    {
        Database::query(
            $hide
                ? 'UPDATE recommendations SET hidden_at = NOW() WHERE id = ?'
                : 'UPDATE recommendations SET hidden_at = NULL WHERE id = ?',
            [$id]
        );
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM recommendations WHERE id = ?', [$id]);
    }

    /** @return list<array<string, mixed>> */
    public static function exportForUser(int $userId): array
    {
        return self::forTarget($userId, 200, true);
    }

    /** @param array<string, mixed> $row */
    public static function present(array $row): array
    {
        $row['who'] = trim((string) ($row['author_name'] ?? ''));
        $row['role'] = trim((string) ($row['author_role'] ?? ''));
        $row['context'] = trim((string) ($row['context'] ?? ''));
        $row['txt'] = (string) ($row['body'] ?? '');
        $row['when'] = time_ago($row['created_at'] ?? null);
        $row['hidden'] = !empty($row['hidden_at']);
        $initials = '';
        foreach (preg_split('/\s+/', $row['who']) ?: [] as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
            if (mb_strlen($initials) >= 2) {
                break;
            }
        }
        $row['initials'] = $initials !== '' ? $initials : 'R';
        $row['avatar'] = avatar_style($row['initials'], 28);
        return $row;
    }
}
