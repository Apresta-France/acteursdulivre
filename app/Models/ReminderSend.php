<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;

final class ReminderSend
{
    public static function count(string $kind, int $userId, ?string $subjectType = null, ?int $subjectId = null): int
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS n FROM reminder_sends
             WHERE kind = ? AND user_id = ?
               AND (subject_type <=> ?) AND (subject_id <=> ?)',
            [$kind, $userId, $subjectType, $subjectId]
        );
        return (int) ($row['n'] ?? 0);
    }

    public static function lastAt(string $kind, int $userId, ?string $subjectType = null, ?int $subjectId = null): ?string
    {
        $row = Database::fetch(
            'SELECT sent_at FROM reminder_sends
             WHERE kind = ? AND user_id = ?
               AND (subject_type <=> ?) AND (subject_id <=> ?)
             ORDER BY sent_at DESC, id DESC
             LIMIT 1',
            [$kind, $userId, $subjectType, $subjectId]
        );
        return isset($row['sent_at']) ? (string) $row['sent_at'] : null;
    }

    public static function isDue(string $kind, int $userId, int $cooldownHours, int $maxSends, ?string $subjectType = null, ?int $subjectId = null): bool
    {
        if (self::count($kind, $userId, $subjectType, $subjectId) >= $maxSends) {
            return false;
        }
        $last = self::lastAt($kind, $userId, $subjectType, $subjectId);
        if ($last === null) {
            return true;
        }
        $ts = strtotime($last);
        if ($ts === false) {
            return true;
        }
        return (time() - $ts) >= ($cooldownHours * 3600);
    }

    public static function record(string $kind, int $userId, ?string $subjectType = null, ?int $subjectId = null): void
    {
        Database::query(
            'INSERT INTO reminder_sends (kind, user_id, subject_type, subject_id, sent_at)
             VALUES (?, ?, ?, ?, NOW())',
            [$kind, $userId, $subjectType, $subjectId]
        );
    }
}
