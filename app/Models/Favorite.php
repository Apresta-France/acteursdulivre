<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;

final class Favorite
{
    /** @return list<array<string, mixed>> */
    public static function forUser(int $userId): array
    {
        $rows = Database::fetchAll(
            'SELECT s.id
             FROM favorites f
             JOIN services s ON s.id = f.service_id
             WHERE f.user_id = ?
             ORDER BY f.created_at DESC',
            [$userId]
        );

        $out = [];
        foreach ($rows as $row) {
            $service = Service::find((int) $row['id']);
            if ($service) {
                $out[] = $service;
            }
        }
        return $out;
    }
}
