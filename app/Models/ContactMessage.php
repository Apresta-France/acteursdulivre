<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Auth;
use Adl\Core\Database;
use Adl\Core\Mailer;
use RuntimeException;

final class ContactMessage
{
    public const STATUS_OPEN = 'open';
    public const STATUS_REPLIED = 'replied';
    public const STATUS_HANDLED = 'handled';

    public const PER_PAGE = 40;

    /** @var array<string, string> */
    public const STATUSES = [
        self::STATUS_OPEN => 'À traiter',
        self::STATUS_REPLIED => 'Répondu',
        self::STATUS_HANDLED => 'Traité',
    ];

    public static function tableExists(): bool
    {
        try {
            Database::fetch('SELECT 1 FROM contact_messages LIMIT 1');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function find(int $id): ?array
    {
        $row = Database::fetch(
            'SELECT m.*,
                    u.first_name, u.last_name, u.email AS user_email, u.avatar_url, u.role AS user_role,
                    h.first_name AS handler_first_name, h.last_name AS handler_last_name
             FROM contact_messages m
             LEFT JOIN users u ON u.id = m.user_id
             LEFT JOIN users h ON h.id = m.handled_by
             WHERE m.id = ?',
            [$id]
        );
        return $row ? self::hydrate($row) : null;
    }

    public static function countOpen(): int
    {
        if (!self::tableExists()) {
            return 0;
        }
        return (int) (Database::fetch(
            'SELECT COUNT(*) AS n FROM contact_messages WHERE status = ?',
            [self::STATUS_OPEN]
        )['n'] ?? 0);
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int, per_page: int}
     */
    public static function search(string $q, string $status = self::STATUS_OPEN, int $page = 1, int $perPage = self::PER_PAGE): array
    {
        $perPage = max(1, min(100, $perPage));
        $sql = 'FROM contact_messages m
                LEFT JOIN users u ON u.id = m.user_id
                LEFT JOIN users h ON h.id = m.handled_by
                WHERE 1=1';
        $params = [];

        if ($status !== '' && $status !== 'tous' && isset(self::STATUSES[$status])) {
            $sql .= ' AND m.status = ?';
            $params[] = $status;
        }

        $q = trim($q);
        if ($q !== '') {
            $sql .= ' AND (m.name LIKE ? OR m.email LIKE ? OR m.body LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $count = Database::fetch('SELECT COUNT(*) AS n ' . $sql, $params);
        $total = (int) ($count['n'] ?? 0);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;

        $rows = $total === 0
            ? []
            : Database::fetchAll(
                'SELECT m.*,
                        u.first_name, u.last_name, u.email AS user_email, u.avatar_url, u.role AS user_role,
                        h.first_name AS handler_first_name, h.last_name AS handler_last_name
                 ' . $sql . ' ORDER BY (m.status = "open") DESC, m.created_at DESC, m.id DESC
                 LIMIT ' . $perPage . ' OFFSET ' . $offset,
                $params
            );

        return [
            'items' => array_map([self::class, 'hydrate'], $rows),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
        ];
    }

    /**
     * @param array{name?: string, email?: string, body?: string, email_log_id?: int|null} $data
     */
    public static function create(array $data): int
    {
        $name = mb_substr(trim((string) ($data['name'] ?? '')), 0, 190);
        $email = mb_substr(strtolower(trim((string) ($data['email'] ?? ''))), 0, 190);
        $body = trim((string) ($data['body'] ?? ''));
        if ($email === '' || $body === '') {
            throw new RuntimeException('Merci d\'indiquer un e-mail valide et votre message.');
        }

        $userId = self::resolveUserId($email);
        $logId = isset($data['email_log_id']) ? (int) $data['email_log_id'] : 0;

        Database::query(
            'INSERT INTO contact_messages
                (name, email, user_id, body, status, email_log_id, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())',
            [
                $name !== '' ? $name : 'Visiteur',
                $email,
                $userId,
                $body,
                self::STATUS_OPEN,
                $logId > 0 ? $logId : null,
            ]
        );

        return (int) Database::lastId();
    }

    public static function setEmailLogId(int $id, int $logId): void
    {
        if ($id < 1 || $logId < 1) {
            return;
        }
        Database::query('UPDATE contact_messages SET email_log_id = ? WHERE id = ?', [$logId, $id]);
    }

    public static function markHandled(int $id, int $adminId, string $note = ''): array
    {
        return self::setStatus($id, self::STATUS_HANDLED, $adminId, $note);
    }

    public static function reopen(int $id, int $adminId, string $note = ''): array
    {
        $row = self::require($id);
        $note = trim($note);
        Database::query(
            'UPDATE contact_messages
             SET status = ?, admin_note = ?, handled_by = ?, handled_at = NULL
             WHERE id = ?',
            [
                self::STATUS_OPEN,
                $note !== '' ? $note : ($row['admin_note'] ?? null),
                $adminId > 0 ? $adminId : null,
                $id,
            ]
        );
        return self::require($id);
    }

    public static function saveNote(int $id, string $note): array
    {
        self::require($id);
        $note = trim($note);
        Database::query(
            'UPDATE contact_messages SET admin_note = ? WHERE id = ?',
            [$note !== '' ? $note : null, $id]
        );
        return self::require($id);
    }

    public static function reply(int $id, int $adminId, string $reply, string $note = ''): array
    {
        $row = self::require($id);
        $reply = trim($reply);
        if ($reply === '') {
            throw new RuntimeException('Indiquez le texte de la réponse.');
        }
        $email = (string) ($row['email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Impossible de répondre : e-mail du destinataire manquant.');
        }

        Mailer::sendTemplate('contact-reponse', $email, [
            'nom' => (string) ($row['name'] ?? 'bonjour'),
            'message' => $reply,
        ]);

        $note = trim($note);
        Database::query(
            'UPDATE contact_messages
             SET status = ?, reply_body = ?, admin_note = ?, handled_by = ?, handled_at = NOW()
             WHERE id = ?',
            [
                self::STATUS_REPLIED,
                $reply,
                $note !== '' ? $note : ($row['admin_note'] ?? null),
                $adminId,
                $id,
            ]
        );

        return self::require($id);
    }

    public static function importFromEmailLog(): int
    {
        if (!self::tableExists()) {
            return 0;
        }
        try {
            $rows = Database::fetchAll(
                'SELECT id, subject, body_html, created_at
                 FROM email_log
                 WHERE template_slug = ?
                 ORDER BY id ASC',
                ['contact-interne']
            );
        } catch (\Throwable) {
            return 0;
        }

        $imported = 0;
        foreach ($rows as $row) {
            $logId = (int) ($row['id'] ?? 0);
            if ($logId < 1 || self::existsForEmailLog($logId)) {
                continue;
            }
            $parsed = self::parseFromEmailHtml((string) ($row['body_html'] ?? ''));
            $name = (string) ($parsed['name'] ?? '');
            if ($name === '' && preg_match('/Nouveau message de\s+(.+)$/u', (string) ($row['subject'] ?? ''), $m)) {
                $name = trim($m[1]);
            }
            $email = strtolower((string) ($parsed['email'] ?? ''));
            $body = (string) ($parsed['body'] ?? '');
            if ($body === '') {
                $body = EmailLog::excerpt((string) ($row['body_html'] ?? ''), 4000);
            }
            if ($email === '' && $body === '') {
                continue;
            }
            Database::query(
                'INSERT INTO contact_messages
                    (name, email, user_id, body, status, email_log_id, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    mb_substr($name !== '' ? $name : 'Visiteur', 0, 190),
                    mb_substr($email, 0, 190),
                    $email !== '' ? self::resolveUserId($email) : null,
                    $body,
                    self::STATUS_OPEN,
                    $logId,
                    (string) ($row['created_at'] ?? date('Y-m-d H:i:s')),
                ]
            );
            $imported++;
        }

        return $imported;
    }

    /**
     * @return array{name?: string, email?: string, body?: string}|null
     */
    public static function parseFromEmailHtml(string $html): ?array
    {
        if ($html === '') {
            return null;
        }
        if (!preg_match(
            '/<p>\s*<strong>(.*?)<\/strong>\s*\(([^)<]*?@[^)]+)\)\s*<\/p>\s*<p>(.*?)<\/p>/is',
            $html,
            $m
        )) {
            return null;
        }

        $name = self::decodeHtml($m[1]);
        $email = strtolower(trim(self::decodeHtml($m[2])));
        $bodyHtml = preg_replace('/<br\s*\/?>/i', "\n", $m[3]) ?? $m[3];
        $body = self::decodeHtml($bodyHtml, true);

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = '';
        }

        return [
            'name' => $name,
            'email' => $email,
            'body' => $body,
        ];
    }

    public static function listUrl(string $q = '', string $status = self::STATUS_OPEN, int $page = 1): string
    {
        $query = [];
        if ($q !== '') {
            $query['q'] = $q;
        }
        if ($status !== '' && $status !== self::STATUS_OPEN) {
            $query['filtre'] = $status;
        }
        if ($page > 1) {
            $query['page'] = $page;
        }

        return '/admin/contact' . ($query === [] ? '' : '?' . http_build_query($query));
    }

    public static function statusLabel(string $status): string
    {
        return self::STATUSES[$status] ?? 'À traiter';
    }

    public static function statusTone(string $status): string
    {
        return match ($status) {
            self::STATUS_REPLIED => 'green',
            self::STATUS_HANDLED => 'grey',
            default => 'orange',
        };
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function hydrate(array $row): array
    {
        $status = (string) ($row['status'] ?? self::STATUS_OPEN);
        $name = trim((string) ($row['name'] ?? ''));
        $handler = trim(trim((string) ($row['handler_first_name'] ?? '') . ' ' . (string) ($row['handler_last_name'] ?? '')));
        $userId = (int) ($row['user_id'] ?? 0);
        $row['status_label'] = self::statusLabel($status);
        $row['status_tone'] = self::statusTone($status);
        $row['is_open'] = $status === self::STATUS_OPEN;
        $row['excerpt'] = EmailLog::excerpt((string) ($row['body'] ?? ''), 180);
        $row['handler_name'] = $handler;
        $row['has_account'] = $userId > 0;
        $row['account_name'] = $userId > 0
            ? User::displayName([
                'first_name' => (string) ($row['first_name'] ?? ''),
                'last_name' => (string) ($row['last_name'] ?? ''),
                'email' => (string) ($row['user_email'] ?? $row['email'] ?? ''),
            ])
            : '';
        $row['who'] = $name !== '' ? $name : 'Visiteur';
        return $row;
    }

    /** @return array<string, mixed> */
    private static function require(int $id): array
    {
        $row = self::find($id);
        if (!$row) {
            throw new RuntimeException('Message introuvable.');
        }
        return $row;
    }

    private static function existsForEmailLog(int $logId): bool
    {
        $row = Database::fetch('SELECT id FROM contact_messages WHERE email_log_id = ?', [$logId]);
        return $row !== null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function setStatus(int $id, string $status, int $adminId, string $note): array
    {
        $row = self::require($id);
        if (!isset(self::STATUSES[$status])) {
            throw new RuntimeException('Statut inconnu.');
        }
        $note = trim($note);
        Database::query(
            'UPDATE contact_messages
             SET status = ?, admin_note = ?, handled_by = ?, handled_at = NOW()
             WHERE id = ?',
            [
                $status,
                $note !== '' ? $note : ($row['admin_note'] ?? null),
                $adminId > 0 ? $adminId : null,
                $id,
            ]
        );
        return self::require($id);
    }

    private static function resolveUserId(string $email): ?int
    {
        $session = Auth::user();
        if ($session && strtolower((string) ($session['email'] ?? '')) === strtolower($email)) {
            return (int) $session['id'];
        }
        if ($email === '') {
            return null;
        }
        try {
            $user = User::findByEmail($email);
        } catch (\Throwable) {
            return null;
        }
        return $user ? (int) $user['id'] : null;
    }

    private static function decodeHtml(string $value, bool $keepNewlines = false): string
    {
        $text = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($keepNewlines) {
            $text = str_replace(["\r\n", "\r"], "\n", $text);
            $text = preg_replace("/[ \t]+/u", ' ', $text) ?? $text;
            $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
            return trim($text);
        }
        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }
}
