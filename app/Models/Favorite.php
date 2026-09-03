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
            if (
                $service
                && ($service['status'] ?? '') === 'published'
                && User::isPublicOfferer((int) ($service['user_id'] ?? 0))
            ) {
                $out[] = $service;
            }
        }
        return $out;
    }

    public static function has(int $userId, int $serviceId): bool
    {
        $row = Database::fetch(
            'SELECT service_id FROM favorites WHERE user_id = ? AND service_id = ?',
            [$userId, $serviceId]
        );
        return $row !== null;
    }

    public static function toggle(int $userId, int $serviceId): bool
    {
        $service = Service::find($serviceId);
        if (!$service) {
            throw new \RuntimeException('Cette prestation est introuvable.');
        }
        if (self::has($userId, $serviceId)) {
            Database::query(
                'DELETE FROM favorites WHERE user_id = ? AND service_id = ?',
                [$userId, $serviceId]
            );
            return false;
        }
        if (
            ($service['status'] ?? '') !== 'published'
            || !User::isPublicOfferer((int) ($service['user_id'] ?? 0))
        ) {
            throw new \RuntimeException('Cette prestation n\'est plus disponible.');
        }
        try {
            Database::query(
                'INSERT INTO favorites (user_id, service_id, created_at) VALUES (?, ?, NOW())',
                [$userId, $serviceId]
            );
        } catch (\PDOException $e) {
            if (!str_contains($e->getMessage(), 'Duplicate')) {
                throw $e;
            }
        }
        return true;
    }
}
