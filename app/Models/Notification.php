<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;

final class Notification
{
    /** @return list<array<string, mixed>> */
    public static function forUser(int $userId, int $limit = 80): array
    {
        $rows = Database::fetchAll(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT ' . max(1, $limit),
            [$userId]
        );
        return array_map([self::class, 'present'], $rows);
    }

    public static function findForUser(int $id, int $userId): ?array
    {
        $row = Database::fetch(
            'SELECT * FROM notifications WHERE id = ? AND user_id = ?',
            [$id, $userId]
        );
        return $row ? self::present($row) : null;
    }

    public static function unreadCount(int $userId): int
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS n FROM notifications WHERE user_id = ? AND read_at IS NULL',
            [$userId]
        );
        return (int) ($row['n'] ?? 0);
    }

    public static function hasUnread(int $userId, string $kind, ?string $subjectType = null, ?int $subjectId = null): bool
    {
        $row = Database::fetch(
            'SELECT id FROM notifications
             WHERE user_id = ? AND kind = ? AND read_at IS NULL
               AND (subject_type <=> ?) AND (subject_id <=> ?)
             LIMIT 1',
            [$userId, $kind, $subjectType, $subjectId]
        );
        return $row !== null;
    }

    public static function create(
        int $userId,
        string $title,
        string $body,
        string $link,
        string $kind,
        ?string $subjectType = null,
        ?int $subjectId = null
    ): int {
        Database::query(
            'INSERT INTO notifications (user_id, kind, subject_type, subject_id, title, body, link, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
            [$userId, $kind, $subjectType, $subjectId, $title, $body, $link]
        );
        return (int) Database::lastId();
    }

    public static function markRead(int $id, int $userId): ?array
    {
        $row = self::findForUser($id, $userId);
        if (!$row) {
            return null;
        }
        if (empty($row['read_at'])) {
            Database::query(
                'UPDATE notifications SET read_at = NOW() WHERE id = ? AND user_id = ? AND read_at IS NULL',
                [$id, $userId]
            );
        }
        return $row;
    }

    public static function markAllRead(int $userId): void
    {
        Database::query(
            'UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL',
            [$userId]
        );
    }

    /** @param array<string, mixed> $row */
    public static function present(array $row): array
    {
        $row['unread'] = empty($row['read_at']);
        $row['when'] = time_ago($row['created_at'] ?? null);
        $row['href'] = (string) ($row['link'] ?: '/espace/notifications');
        return $row;
    }
}
