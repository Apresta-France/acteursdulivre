<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;
use RuntimeException;

final class Review
{
    public const CRITERIA = [
        'quality' => 'Qualité de la prestation',
        'efficiency' => 'Efficacité',
        'satisfaction' => 'Satisfaction globale',
    ];

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

    public static function forOrderAuthor(int $orderId, int $authorId): ?array
    {
        return Database::fetch(
            'SELECT * FROM reviews WHERE order_id = ? AND author_id = ? LIMIT 1',
            [$orderId, $authorId]
        );
    }

    /**
     * @param array{quality: int, efficiency: int, satisfaction: int, body?: string} $ratings
     * @return array<string, mixed>
     */
    public static function createForOrder(int $orderId, int $authorId, int $targetId, array $ratings): array
    {
        if (self::forOrderAuthor($orderId, $authorId)) {
            throw new RuntimeException('Un avis a déjà été déposé pour cette mission.');
        }

        $quality = self::score($ratings['quality'] ?? 0);
        $efficiency = self::score($ratings['efficiency'] ?? 0);
        $satisfaction = self::score($ratings['satisfaction'] ?? 0);
        $overall = (int) round(($quality + $efficiency + $satisfaction) / 3);
        $body = trim((string) ($ratings['body'] ?? ''));

        Database::query(
            'INSERT INTO reviews (order_id, author_id, target_id, rating, rating_quality, rating_efficiency, rating_satisfaction, body)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$orderId, $authorId, $targetId, $overall, $quality, $efficiency, $satisfaction, $body !== '' ? $body : null]
        );

        return Database::fetch('SELECT * FROM reviews WHERE id = ?', [(int) Database::lastId()]) ?? [];
    }

    public static function score(mixed $value): int
    {
        $n = (int) $value;
        if ($n < 1 || $n > 5) {
            throw new RuntimeException('Chaque critère doit être noté de 1 à 5.');
        }
        return $n;
    }
}
