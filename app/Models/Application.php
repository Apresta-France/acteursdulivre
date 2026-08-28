<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;

final class Application
{
    public const STATUSES = [
        'sent' => 'Envoyée',
        'viewed' => 'Vue',
        'accepted' => 'Acceptée',
        'rejected' => 'Non retenue',
    ];

    public static function countForMission(int $missionId): int
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS n FROM applications WHERE mission_id = ?',
            [$missionId]
        );
        return (int) ($row['n'] ?? 0);
    }

    /** @return list<array<string, mixed>> */
    public static function forUser(int $userId): array
    {
        $rows = Database::fetchAll(
            'SELECT a.*, m.title, m.slug, m.category_name, m.status AS mission_status,
                    u.first_name, u.last_name
             FROM applications a
             JOIN missions m ON m.id = a.mission_id
             JOIN users u ON u.id = m.user_id
             WHERE a.user_id = ?
             ORDER BY a.created_at DESC',
            [$userId]
        );

        return array_map(static function (array $row): array {
            $status = (string) ($row['status'] ?? 'sent');
            $row['by'] = User::displayName($row);
            $row['price'] = isset($row['price']) ? format_euros((int) $row['price']) : '—';
            $row['when'] = time_ago($row['created_at'] ?? null);
            $row['status_label'] = self::STATUSES[$status] ?? 'Envoyée';
            $row['status_tone'] = match ($status) {
                'accepted' => 'green',
                'rejected' => 'grey',
                'viewed' => 'navy',
                default => 'orange',
            };
            $row['href'] = '/missions/' . ($row['slug'] ?? '');
            return $row;
        }, $rows);
    }
}
