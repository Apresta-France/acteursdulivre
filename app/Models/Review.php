<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;

final class Review
{
    /** @return array{avg: string, count: int} */
    public static function statsForUser(int $userId): array
    {
        $row = Database::fetch(
            'SELECT ROUND(AVG(rating), 1) AS avg_rating, COUNT(*) AS n
             FROM reviews WHERE target_id = ?',
            [$userId]
        );
        $count = (int) ($row['n'] ?? 0);
        $avg = $count > 0 ? str_replace('.', ',', (string) $row['avg_rating']) : '';

        return ['avg' => $avg, 'count' => $count];
    }

    /** @return list<array<string, mixed>> */
    public static function forTarget(int $userId, int $limit = 20): array
    {
        $rows = Database::fetchAll(
            'SELECT r.*, u.first_name, u.last_name
             FROM reviews r
             JOIN users u ON u.id = r.author_id
             WHERE r.target_id = ?
             ORDER BY r.created_at DESC
             LIMIT ' . max(1, $limit),
            [$userId]
        );

        return array_map(static function (array $row): array {
            $row['who'] = User::displayName($row);
            $row['initials'] = User::initials($row);
            $row['avatar'] = avatar_style($row['initials'], 28);
            $row['note'] = str_replace('.', ',', (string) $row['rating']);
            $row['txt'] = (string) ($row['body'] ?? '');
            $row['when'] = time_ago($row['created_at'] ?? null);
            return $row;
        }, $rows);
    }
}
