<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;
use Adl\Core\Mailer;
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
     * @return array<string, mixed>|null
     */
    public static function findBetween(int $fromId, int $toId, array $context = []): ?array
    {
        $existing = self::findPair($fromId, $toId, $context);
        return $existing ? self::hydrateForUser($existing, $fromId) : null;
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
        $to = User::find($toId);
        if (!$to || ($to['status'] ?? '') !== 'active' || !empty($to['deleted_at'])) {
            throw new RuntimeException('Ce destinataire n\'est plus joignable.');
        }

        return Database::transaction(static function () use ($fromId, $toId, $context): array {
            $existing = self::findPair($fromId, $toId, $context);
            if ($existing) {
                return self::hydrateForUser($existing, $fromId);
            }

            $subject = trim((string) ($context['subject'] ?? ''));
            try {
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
            } catch (\PDOException $e) {
                $again = self::findPair($fromId, $toId, $context);
                if ($again) {
                    return self::hydrateForUser($again, $fromId);
                }
                throw $e;
            }
            $id = (int) Database::lastId();
            Database::query(
                'INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?), (?, ?)',
                [$id, $fromId, $id, $toId]
            );

            $row = Database::fetch('SELECT * FROM conversations WHERE id = ?', [$id]);
            return self::hydrateForUser($row ?? ['id' => $id], $fromId);
        });
    }

    /**
     * @param array<string, mixed>|null $file
     * @param array{path?: ?string, name?: ?string}|null $publicUpload
     */
    public static function send(
        int $conversationId,
        int $userId,
        string $body,
        ?array $file = null,
        ?array $publicUpload = null,
        bool $notify = true
    ): array {
        $thread = self::findForUser($conversationId, $userId);
        if (!$thread) {
            throw new RuntimeException('Cette conversation est introuvable.');
        }
        $body = trim($body);
        if (mb_strlen($body) > 8000) {
            throw new RuntimeException('Le message est trop long.');
        }

        $attachment = null;
        if (is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $attachment = store_private_upload(
                $file,
                'messages/' . $conversationId,
                ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'txt', 'doc', 'docx', 'odt'],
                8 * 1024 * 1024
            );
        } elseif (is_array($publicUpload) && trim((string) ($publicUpload['path'] ?? '')) !== '') {
            try {
                $attachment = copy_any_upload_to_private(
                    (string) $publicUpload['path'],
                    'messages/' . $conversationId,
                    (string) ($publicUpload['name'] ?? 'fichier')
                );
            } catch (\Throwable) {
                $attachment = null;
            }
        }
        if ($body === '' && $attachment === null) {
            throw new RuntimeException('Écrivez un message ou joignez un fichier.');
        }

        $messageId = (int) Database::transaction(static function () use ($conversationId, $userId, $body, $attachment): int {
            Database::query(
                'INSERT INTO messages (conversation_id, user_id, body, attachment_path, attachment_name, attachment_size, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())',
                [
                    $conversationId,
                    $userId,
                    $body !== '' ? $body : null,
                    $attachment['path'] ?? null,
                    $attachment['name'] ?? null,
                    $attachment['size'] ?? null,
                ]
            );
            $id = (int) Database::lastId();
            Database::query('UPDATE conversations SET updated_at = NOW() WHERE id = ?', [$conversationId]);
            Database::query(
                'UPDATE conversation_participants SET last_read_at = NOW() WHERE conversation_id = ? AND user_id = ?',
                [$conversationId, $userId]
            );

            return $id;
        });

        $preview = $body !== ''
            ? $body
            : 'Pièce jointe : ' . (string) ($attachment['name'] ?? 'fichier');
        $otherId = (int) ($thread['other']['id'] ?? 0);
        if ($notify && $otherId > 0) {
            try {
                $senderName = User::displayName(User::find($userId) ?? []);
                $unread = self::unreadFromSender($conversationId, $otherId, $userId);
                $title = $unread > 1
                    ? $unread . ' nouveaux messages de ' . $senderName
                    : 'Nouveau message de ' . $senderName;
                $hadUnread = Notification::hasUnread($otherId, 'message', 'conversation', $conversationId);
                Notification::upsertUnread(
                    $otherId,
                    $title,
                    mb_strlen($preview) > 140 ? mb_substr($preview, 0, 137) . '…' : $preview,
                    '/espace/messages/' . $conversationId,
                    'message',
                    'conversation',
                    $conversationId
                );
                if (!$hadUnread) {
                    Mailer::notify(User::find($otherId), 'messages', 'nouveau-message', [
                        'sujet' => (string) ($thread['subject'] ?? 'votre conversation'),
                        'lien' => url('/espace/messages/' . $conversationId),
                    ]);
                }
            } catch (\Throwable) {
            }
        }

        return Database::fetch('SELECT * FROM messages WHERE id = ?', [$messageId]) ?? [];
    }

    public static function findByOrder(int $orderId): ?array
    {
        $row = Database::fetch(
            'SELECT * FROM conversations WHERE order_id = ? ORDER BY id DESC LIMIT 1',
            [$orderId]
        );
        return $row ? self::present($row) : null;
    }

    /** @return array{path: string, name: string, mime: string} */
    public static function attachmentForUser(int $conversationId, int $messageId, int $userId): array
    {
        if (!self::findForUser($conversationId, $userId)) {
            throw new RuntimeException('Cette conversation est introuvable.');
        }
        $row = Database::fetch(
            'SELECT attachment_path, attachment_name FROM messages
             WHERE id = ? AND conversation_id = ?',
            [$messageId, $conversationId]
        );
        $path = trim((string) ($row['attachment_path'] ?? ''));
        if ($path === '') {
            throw new RuntimeException('Aucune pièce jointe.');
        }
        $name = trim((string) ($row['attachment_name'] ?? '')) ?: 'piece-jointe';
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimes = upload_mime_map();
        return [
            'path' => $path,
            'name' => $name,
            'mime' => $mimes[$ext][0] ?? 'application/octet-stream',
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function exportForUser(int $userId): array
    {
        $rows = Database::fetchAll(
            'SELECT c.id, c.subject, m.body, m.attachment_name, m.created_at
             FROM messages m
             JOIN conversations c ON c.id = m.conversation_id
             JOIN conversation_participants p ON p.conversation_id = c.id AND p.user_id = ?
             WHERE m.user_id = ?
             ORDER BY m.id ASC',
            [$userId, $userId]
        );
        return array_map(static function (array $row): array {
            return [
                'conversation_id' => (int) $row['id'],
                'subject' => $row['subject'],
                'body' => $row['body'],
                'attachment' => $row['attachment_name'],
                'created_at' => $row['created_at'],
            ];
        }, $rows);
    }

    public static function markRead(int $conversationId, int $userId): void
    {
        Database::query(
            'UPDATE conversation_participants SET last_read_at = NOW()
             WHERE conversation_id = ? AND user_id = ?',
            [$conversationId, $userId]
        );
        Notification::markSubjectRead($userId, 'message', 'conversation', $conversationId);
    }

    public static function hasOpenReport(int $conversationId, int $reporterId): bool
    {
        return Report::hasOpen($reporterId, 'conversation', $conversationId);
    }

    public static function report(int $conversationId, int $reporterId, string $reason, string $body): int
    {
        if (!self::findForUser($conversationId, $reporterId)) {
            throw new RuntimeException('Cette conversation est introuvable.');
        }
        if (self::hasOpenReport($conversationId, $reporterId)) {
            throw new RuntimeException('Vous avez déjà signalé cette conversation. L\'équipe la traite.');
        }

        return Report::create($reporterId, 'conversation', $conversationId, $reason, $body);
    }

    /** @return array<string, mixed>|null */
    public static function findForAdmin(int $id): ?array
    {
        $row = Database::fetch('SELECT * FROM conversations WHERE id = ?', [$id]);
        if (!$row) {
            return null;
        }

        $presented = self::present($row);
        $participants = Database::fetchAll(
            'SELECT u.id, u.first_name, u.last_name, u.email, u.avatar_url
             FROM conversation_participants p
             JOIN users u ON u.id = p.user_id
             WHERE p.conversation_id = ?
             ORDER BY u.id',
            [$id]
        );
        $presented['participants'] = array_map(static function (array $user): array {
            return [
                'id' => (int) $user['id'],
                'name' => User::displayName($user),
                'email' => (string) ($user['email'] ?? ''),
                'initials' => User::initials($user),
                'avatar_url' => $user['avatar_url'] ?? '',
                'href' => '/admin/utilisateurs/' . (int) $user['id'],
            ];
        }, $participants);

        $messages = self::messages($id);
        foreach ($messages as &$message) {
            if (!empty($message['has_file'])) {
                $message['file_href'] = '/admin/conversations/' . $id . '/fichier/' . (int) $message['id'];
            }
        }
        unset($message);
        $presented['messages'] = $messages;
        $presented['context'] = self::adminContext($row);
        $presented['reports'] = Report::forTarget('conversation', $id);

        return $presented;
    }

    /** @return array{path: string, name: string, mime: string} */
    public static function attachmentForAdmin(int $conversationId, int $messageId): array
    {
        if (!self::find($conversationId)) {
            throw new RuntimeException('Cette conversation est introuvable.');
        }
        $row = Database::fetch(
            'SELECT attachment_path, attachment_name FROM messages
             WHERE id = ? AND conversation_id = ?',
            [$messageId, $conversationId]
        );
        $path = trim((string) ($row['attachment_path'] ?? ''));
        if ($path === '') {
            throw new RuntimeException('Aucune pièce jointe.');
        }
        $name = trim((string) ($row['attachment_name'] ?? '')) ?: 'piece-jointe';
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimes = upload_mime_map();

        return [
            'path' => $path,
            'name' => $name,
            'mime' => $mimes[$ext][0] ?? 'application/octet-stream',
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return list<array{label: string, href: string}>
     */
    private static function adminContext(array $row): array
    {
        $links = [];
        $orderId = (int) ($row['order_id'] ?? 0);
        if ($orderId > 0) {
            try {
                $order = Order::find($orderId);
            } catch (\Throwable) {
                $order = null;
            }
            $links[] = [
                'label' => 'Commande ' . (string) ($order['num'] ?? $orderId),
                'href' => '/espace/suivi/' . $orderId,
            ];
        }
        $missionId = (int) ($row['mission_id'] ?? 0);
        if ($missionId > 0) {
            try {
                $mission = Mission::find($missionId);
            } catch (\Throwable) {
                $mission = null;
            }
            if ($mission) {
                $links[] = [
                    'label' => (string) ($mission['title'] ?? 'Mission'),
                    'href' => (string) ($mission['href'] ?? '/admin/missions'),
                ];
            }
        }
        $serviceId = (int) ($row['service_id'] ?? 0);
        if ($serviceId > 0) {
            try {
                $service = Service::find($serviceId);
            } catch (\Throwable) {
                $service = null;
            }
            if ($service) {
                $links[] = [
                    'label' => (string) ($service['title'] ?? 'Prestation'),
                    'href' => (string) ($service['href'] ?? '/admin/prestations'),
                ];
            }
        }

        return $links;
    }

    private static function unreadFromSender(int $conversationId, int $recipientId, int $senderId): int
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS n
             FROM messages m
             JOIN conversation_participants p
               ON p.conversation_id = m.conversation_id AND p.user_id = ?
             WHERE m.conversation_id = ?
               AND m.user_id = ?
               AND (p.last_read_at IS NULL OR m.created_at > p.last_read_at)',
            [$recipientId, $conversationId, $senderId]
        );
        return max(1, (int) ($row['n'] ?? 1));
    }

    /** @return array<string, string> */
    public static function jalonPings(): array
    {
        return [
            'quote' => 'Devis envoyé dans le suivi de commande.',
            'quote_accept' => 'Devis accepté. Nous continuons les jalons dans le suivi.',
            'quote_refused' => 'Devis refusé. Vous pouvez en proposer un nouveau dans le suivi.',
            'order_cancelled' => 'Commande annulée.',
            'deposit_invoice' => 'Facture d’acompte déposée dans le suivi de commande.',
            'deposit_paid' => 'J’ai réglé l’acompte hors plateforme : je le confirme dans le suivi.',
            'deposit_ack' => 'Acompte bien reçu, je démarre la mission.',
            'deliver' => 'Prestation livrée : voir le suivi de commande.',
            'final_invoice' => 'Facture de solde déposée dans le suivi de commande.',
            'final_paid' => 'J’ai réglé le solde hors plateforme : je le confirme dans le suivi.',
            'vault' => 'Nouveau fichier dans l’espace de dépôt.',
        ];
    }

    public static function jalonPingHref(string $body, int $orderId): string
    {
        if ($orderId < 1) {
            return '';
        }
        $body = trim($body);
        if ($body === '') {
            return '';
        }
        $pings = self::jalonPings();
        if (in_array($body, $pings, true)) {
            return '/espace/suivi/' . $orderId;
        }
        $vault = (string) ($pings['vault'] ?? '');
        if ($vault !== '' && str_starts_with($body, $vault)) {
            return '/espace/suivi/' . $orderId . '/depot';
        }

        return '';
    }

    /** @return list<array<string, mixed>> */
    public static function messages(int $conversationId, int $afterId = 0): array
    {
        $conversation = Database::fetch(
            'SELECT order_id FROM conversations WHERE id = ?',
            [$conversationId]
        );
        $orderId = (int) ($conversation['order_id'] ?? 0);
        $sql = 'SELECT m.*, u.first_name, u.last_name, u.avatar_url
             FROM messages m
             JOIN users u ON u.id = m.user_id
             WHERE m.conversation_id = ?';
        $params = [$conversationId];
        if ($afterId > 0) {
            $sql .= ' AND m.id > ?';
            $params[] = $afterId;
        }
        $sql .= ' ORDER BY m.id ASC';
        $rows = Database::fetchAll($sql, $params);
        return array_map(static function (array $row) use ($orderId): array {
            $row['who'] = User::displayName($row);
            $row['initials'] = User::initials($row);
            $row['when'] = format_message_when($row['created_at'] ?? null);
            $row['created_iso'] = datetime_iso($row['created_at'] ?? null);
            $row['has_file'] = trim((string) ($row['attachment_path'] ?? '')) !== '';
            $row['file_href'] = $row['has_file']
                ? '/espace/messages/' . (int) $row['conversation_id'] . '/fichier/' . (int) $row['id']
                : '';
            $row['file_label'] = (string) ($row['attachment_name'] ?? '');
            $size = (int) ($row['attachment_size'] ?? 0);
            $row['file_size'] = $size > 0 ? format_bytes($size) : '';
            $row['href'] = self::jalonPingHref((string) ($row['body'] ?? ''), $orderId);
            return $row;
        }, $rows);
    }

    /** @param array<string, mixed> $context */
    private static function findPair(int $fromId, int $toId, array $context): ?array
    {
        if ($fromId === $toId) {
            return null;
        }

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
        } else {
            $sql .= ' AND c.order_id IS NULL AND c.mission_id IS NULL AND c.service_id IS NULL';
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
            'SELECT body, attachment_name, user_id, created_at FROM messages
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
        $preview = trim((string) ($last['body'] ?? ''));
        if ($preview === '' && !empty($last['attachment_name'])) {
            $preview = 'Pièce jointe : ' . $last['attachment_name'];
        }
        $presented['preview'] = $preview !== '' ? $preview : 'Aucun message pour le moment.';
        $stamp = $last['created_at'] ?? $row['updated_at'] ?? $row['created_at'] ?? null;
        $presented['when'] = time_ago($stamp);
        $presented['created_iso'] = datetime_iso($stamp);
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
