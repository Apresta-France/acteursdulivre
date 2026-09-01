<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;

final class EmailLog
{
    public const PER_PAGE = 40;

    /** @var array<string, string> */
    public const SOURCES = [
        'tous' => 'Tous',
        'transactional' => 'Transactionnels',
        'newsletter' => 'Newsletter',
        'test' => 'Tests',
    ];

    /**
     * @param array{
     *   recipient?: string,
     *   subject?: string,
     *   body_html?: string,
     *   body_text?: string|null,
     *   template_slug?: string|null,
     *   source?: string,
     *   status?: string,
     *   error?: string|null
     * } $data
     */
    public static function record(array $data): void
    {
        $recipient = mb_substr(trim((string) ($data['recipient'] ?? '')), 0, 191);
        $subject = mb_substr(trim((string) ($data['subject'] ?? '')), 0, 255);
        if ($recipient === '' && $subject === '') {
            return;
        }

        $slug = trim((string) ($data['template_slug'] ?? ''));
        $source = (string) ($data['source'] ?? 'transactional');
        if (!isset(self::SOURCES[$source]) || $source === 'tous') {
            $source = 'transactional';
        }
        $status = (string) ($data['status'] ?? 'sent');
        if (!in_array($status, ['sent', 'failed', 'file'], true)) {
            $status = 'sent';
        }
        $error = trim((string) ($data['error'] ?? ''));
        $text = trim((string) ($data['body_text'] ?? ''));

        try {
            Database::query(
                'INSERT INTO email_log
                    (recipient, subject, body_html, body_text, template_slug, source, status, error, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())',
                [
                    $recipient !== '' ? $recipient : '—',
                    $subject !== '' ? $subject : '(sans sujet)',
                    (string) ($data['body_html'] ?? ''),
                    $text !== '' ? $text : null,
                    $slug !== '' ? mb_substr($slug, 0, 80) : null,
                    $source,
                    $status,
                    $error !== '' ? mb_substr($error, 0, 255) : null,
                ]
            );
        } catch (\Throwable) {
            // La table peut manquer pendant une migration ; l'envoi ne doit pas échouer.
        }
    }

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM email_log WHERE id = ?', [$id]);
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int, per_page: int}
     */
    public static function search(string $q, string $source = 'tous', int $page = 1, int $perPage = self::PER_PAGE): array
    {
        $perPage = max(1, min(100, $perPage));
        $sql = 'FROM email_log WHERE 1=1';
        $params = [];

        $q = trim($q);
        if ($q !== '') {
            $sql .= ' AND (recipient LIKE ? OR subject LIKE ? OR template_slug LIKE ?)';
            $like = '%' . $q . '%';
            $params = [$like, $like, $like];
        }
        if ($source !== '' && $source !== 'tous' && isset(self::SOURCES[$source])) {
            $sql .= ' AND source = ?';
            $params[] = $source;
        }

        $count = Database::fetch('SELECT COUNT(*) AS n ' . $sql, $params);
        $total = (int) ($count['n'] ?? 0);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;

        $rows = $total === 0
            ? []
            : Database::fetchAll(
                'SELECT * ' . $sql . ' ORDER BY created_at DESC, id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset,
                $params
            );

        return [
            'items' => $rows,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
        ];
    }

    public static function excerpt(string $html, int $len = 160): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', plain_text($html)) ?? '');
        if ($text === '') {
            return '';
        }
        if (mb_strlen($text) <= $len) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $len - 1)) . '…';
    }

    public static function sourceLabel(string $source): string
    {
        return match ($source) {
            'newsletter' => 'Newsletter',
            'test' => 'Test',
            default => 'Transactionnel',
        };
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'failed' => 'Échec',
            'file' => 'Fichier local',
            default => 'Envoyé',
        };
    }

    public static function statusTone(string $status): string
    {
        return match ($status) {
            'failed' => 'orange',
            'file' => 'grey',
            default => 'green',
        };
    }

    public static function listUrl(string $q = '', string $source = 'tous', int $page = 1): string
    {
        $query = [];
        if ($q !== '') {
            $query['q'] = $q;
        }
        if ($source !== '' && $source !== 'tous') {
            $query['filtre'] = $source;
        }
        if ($page > 1) {
            $query['page'] = $page;
        }

        return '/admin/envois' . ($query === [] ? '' : '?' . http_build_query($query));
    }
};
