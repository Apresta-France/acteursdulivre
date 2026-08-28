<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;
use RuntimeException;

final class Conversation
{
    public static function find(int $id): ?array
    {
        $row = Database::fetch('SELECT * FROM conversations WHERE id = ?', [$id]);
        return $row ? self::present($row) : null;
    }

    public static function findForUser(int $id, int $userId): ?array
    {
        $row = Database::fetch(
            'SELECT c.*
             FROM conversations c
             JOIN conversation_participants p ON p.conversation_id = c.id
             WHERE c.id = ? AND p.user_id = ?',
            [$id, $userId]
        );
        return $row ? self::hydrateForUser($row, $userId) : null;
    }

    public static function unreadCount(int $userId): int
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS n
             FROM conversation_participants p
             JOIN conversations c ON c.id = p.conversation_id
             JOIN messages last ON last.id = (
                SELECT m.id FROM messages m
                WHERE m.conversation_id = c.id
                ORDER BY m.id DESC LIMIT 1
             )
             WHERE p.user_id = ?
               AND last.user_id != ?
               AND (p.last_read_at IS NULL OR last.created_at > p.last_read_at)',
            [$userId, $userId]
        );
        return (int) ($row['n'] ?? 0);
    }

    /** @return list<array<string, mixed>> */
    public static function forUser(int $userId): array
    {
        $rows = Database::fetchAll(
            'SELECT c.*
             FROM conversations c
             JOIN conversation_participants p ON p.conversation_id = c.id
             WHERE p.user_id = ?
             ORDER BY COALESCE(c.updated_at, c.created_at) DESC, c.id DESC',
            [$userId]
        );
        return array_map(static fn (array $row): array => self::hydrateForUser($row, $userId), $rows);
    }

    /**
     * @param array{order_id?: ?int, mission_id?: ?int, service_id?: ?int, subject?: string} $context
     * @return array<string, mixed>
     */
    public static function open(int $fromId, int $toId, array $context = []): array
    {
        if ($fromId === $toId) {
            throw new RuntimeException('Vous ne pouvez pas vous écrire à vous-même.');
        }
        if (!User::find($toId)) {
            throw new RuntimeException('Ce destinataire est introuvable.');
        }

        $existing = self::findPair($fromId, $toId, $context);
        if ($existing) {
            return self::hydrateForUser($existing, $fromId);
        }

        $subject = trim((string) ($context['subject'] ?? ''));
        Database::query(
            'INSERT INTO conversations (subject, order_id, mission_id, service_id, created_at, updated_at)
             VALUES (?, ?, ?, ?, NOW(), NOW())',
            [
                $subject !== '' ? $subject : null,
                $context['order_id'] ?? null,
                $context['mission_id'] ?? null,
                $context['service_id'] ?? null,
            ]
        );
        $id = (int) Database::lastId();
        Database::query(
            'INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?), (?, ?)',
            [$id, $fromId, $id, $toId]
        );

        $row = Database::fetch('SELECT * FROM conversations WHERE id = ?', [$id]);
        return self::hydrateForUser($row ?? ['id' => $id], $fromId);
    }

    public static function send(int $conversationId, int $userId, string $body): array
    {
        $thread = self::findForUser($conversationId, $userId);
        if (!$thread) {
            throw new RuntimeException('Cette conversation est introuvable.');
        }
        $body = trim($body);
        if ($body === '') {
            throw new RuntimeException('Écrivez un message avant d\'envoyer.');
        }
        if (mb_strlen($body) > 8000) {
            throw new RuntimeException('Le message est trop long.');
        }

        Database::query(
            'INSERT INTO messages (conversation_id, user_id, body, created_at) VALUES (?, ?, ?, NOW())',
            [$conversationId, $userId, $body]
        );
        $messageId = (int) Database::lastId();
        Database::query('UPDATE conversations SET updated_at = NOW() WHERE id = ?', [$conversationId]);
        Database::query(
            'UPDATE conversation_participants SET last_read_at = NOW() WHERE conversation_id = ? AND user_id = ?',
            [$conversationId, $userId]
        );

        $otherId = (int) ($thread['other']['id'] ?? 0);
        if ($otherId > 0) {
            Notification::create(
                $otherId,
                'Nouveau message de ' . User::displayName(User::find($userId) ?? []),
                mb_strlen($body) > 140 ? mb_substr($body, 0, 137) . '…' : $body,
                '/espace/messages/' . $conversationId,
                'message',
                'conversation',
                $conversationId
            );
        }

        return Database::fetch('SELECT * FROM messages WHERE id = ?', [$messageId]) ?? [];
    }

    public static function markRead(int $conversationId, int $userId): void
    {
        Database::query(
            'UPDATE conversation_participants SET last_read_at = NOW()
             WHERE conversation_id = ? AND user_id = ?',
            [$conversationId, $userId]
        );
    }

    /** @return list<array<string, mixed>> */
    public static function messages(int $conversationId): array
    {
        $rows = Database::fetchAll(
            'SELECT m.*, u.first_name, u.last_name, u.avatar_url
             FROM messages m
             JOIN users u ON u.id = m.user_id
             WHERE m.conversation_id = ?
             ORDER BY m.id ASC',
            [$conversationId]
        );
        return array_map(static function (array $row): array {
            $row['who'] = User::displayName($row);
            $row['initials'] = User::initials($row);
            $row['when'] = time_ago($row['created_at'] ?? null);
            return $row;
        }, $rows);
    }

    /** @param array<string, mixed> $context */
    private static function findPair(int $fromId, int $toId, array $context): ?array
    {
        $orderId = isset($context['order_id']) ? (int) $context['order_id'] : 0;
        $missionId = isset($context['mission_id']) ? (int) $context['mission_id'] : 0;
        $serviceId = isset($context['service_id']) ? (int) $context['service_id'] : 0;

        $sql = 'SELECT c.*
                FROM conversations c
                JOIN conversation_participants a ON a.conversation_id = c.id AND a.user_id = ?
                JOIN conversation_participants b ON b.conversation_id = c.id AND b.user_id = ?
                WHERE 1=1';
        $params = [$fromId, $toId];
        if ($orderId > 0) {
            $sql .= ' AND c.order_id = ?';
            $params[] = $orderId;
        } elseif ($missionId > 0) {
            $sql .= ' AND c.mission_id = ?';
            $params[] = $missionId;
        } elseif ($serviceId > 0) {
            $sql .= ' AND c.service_id = ?';
            $params[] = $serviceId;
        }
        $sql .= ' ORDER BY c.id DESC LIMIT 1';

        return Database::fetch($sql, $params);
    }

    /** @param array<string, mixed> $row */
    private static function hydrateForUser(array $row, int $userId): array
    {
        $presented = self::present($row);
        $other = Database::fetch(
            'SELECT u.id, u.first_name, u.last_name, u.avatar_url
             FROM conversation_participants p
             JOIN users u ON u.id = p.user_id
             WHERE p.conversation_id = ? AND p.user_id != ?
             LIMIT 1',
            [(int) $row['id'], $userId]
        );
        $last = Database::fetch(
            'SELECT body, user_id, created_at FROM messages
             WHERE conversation_id = ? ORDER BY id DESC LIMIT 1',
            [(int) $row['id']]
        );
        $mine = Database::fetch(
            'SELECT last_read_at FROM conversation_participants
             WHERE conversation_id = ? AND user_id = ?',
            [(int) $row['id'], $userId]
        );
        $unread = false;
        if ($last && (int) ($last['user_id'] ?? 0) !== $userId) {
            $readAt = $mine['last_read_at'] ?? null;
            $unread = $readAt === null || strtotime((string) $last['created_at']) > strtotime((string) $readAt);
        }

        $presented['other'] = $other ? [
            'id' => (int) $other['id'],
            'name' => User::displayName($other),
            'initials' => User::initials($other),
            'avatar_url' => $other['avatar_url'] ?? '',
        ] : ['id' => 0, 'name' => 'Conversation', 'initials' => 'AD', 'avatar_url' => ''];
        $presented['preview'] = $last ? (string) $last['body'] : 'Aucun message pour le moment.';
        $presented['when'] = time_ago($last['created_at'] ?? $row['updated_at'] ?? $row['created_at'] ?? null);
        $presented['unread'] = $unread;
        $presented['href'] = '/espace/messages/' . (int) $row['id'];
        return $presented;
    }

    /** @param array<string, mixed> $row */
    private static function present(array $row): array
    {
        $subject = trim((string) ($row['subject'] ?? ''));
        $row['subject'] = $subject !== '' ? $subject : 'Conversation';
        $row['href'] = '/espace/messages/' . (int) ($row['id'] ?? 0);
        return $row;
    }
}
