<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Auth;
use Adl\Core\Database;

final class HttpError
{
    public const PER_PAGE = 40;

    /** @var array<string, string> */
    public const FILTERS = [
        'suivi' => 'À suivre',
        '404' => '404',
        '403' => '403',
        '419' => '419',
        '429' => '429',
        '500' => '500',
        'autres' => 'Autres',
        'tous' => 'Toutes',
        'vus' => 'Déjà vues',
    ];

    private static bool $saved = false;

    private static string $note = '';

    public static function note(string $message): void
    {
        $message = trim($message);
        if ($message !== '') {
            self::$note = $message;
        }
    }

    public static function capture(): void
    {
        if (self::$saved || PHP_SAPI === 'cli') {
            return;
        }

        $code = (int) http_response_code();
        $fatal = error_get_last();
        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if (is_array($fatal) && in_array((int) ($fatal['type'] ?? 0), $fatalTypes, true)) {
            $code = 500;
            self::note((string) ($fatal['message'] ?? ''));
        }
        if ($code < 400 || $code > 599) {
            return;
        }

        self::record($code);
    }

    public static function record(int $status): void
    {
        if (self::$saved || $status < 400 || $status > 599) {
            return;
        }
        self::$saved = true;

        try {
            $path = self::clipPath(self::requestPath());
            $hash = sha1($path);
            $method = self::method();
            $query = self::cleanQuery((string) ($_SERVER['QUERY_STRING'] ?? ''));
            $referrer = self::cleanReferrer((string) ($_SERVER['HTTP_REFERER'] ?? ''));
            $agent = self::clip((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 255);
            $message = self::clip(self::$note, 255);
            $ip = self::clip(client_ip_hash(), 64);
            $userId = self::userId();

            $updated = Database::query(
                'UPDATE http_errors
                 SET hits = hits + 1,
                     updated_at = NOW(),
                     method = ?,
                     query_string = ?,
                     referrer = ?,
                     user_agent = ?,
                     user_id = ?,
                     message = IF(? = "", message, ?)
                 WHERE status = ? AND path_hash = ? AND ip_hash = ?
                   AND updated_at >= (NOW() - INTERVAL 60 SECOND)
                 ORDER BY id DESC
                 LIMIT 1',
                [
                    $method,
                    $query,
                    $referrer,
                    $agent !== '' ? $agent : null,
                    $userId,
                    $message,
                    $message !== '' ? $message : null,
                    $status,
                    $hash,
                    $ip,
                ]
            );
            if ($updated->rowCount() > 0) {
                self::maybePrune();
                return;
            }

            Database::query(
                'INSERT INTO http_errors
                    (status, method, path, path_hash, query_string, referrer, user_agent, user_id, ip_hash, message, hits, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())',
                [
                    $status,
                    $method,
                    $path,
                    $hash,
                    $query,
                    $referrer,
                    $agent !== '' ? $agent : null,
                    $userId,
                    $ip !== '' ? $ip : null,
                    $message !== '' ? $message : null,
                ]
            );
            self::maybePrune();
        } catch (\Throwable) {
        }
    }

    public static function ready(): bool
    {
        try {
            Database::fetch('SELECT 1 FROM http_errors LIMIT 1');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function countOpen(): int
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS n FROM (
                SELECT e.status, e.path_hash
                FROM http_errors e
                LEFT JOIN http_error_acks a ON a.status = e.status AND a.path_hash = e.path_hash
                GROUP BY e.status, e.path_hash
                HAVING MAX(a.acked_at) IS NULL OR MAX(e.updated_at) > MAX(a.acked_at)
            ) open_errors'
        );

        return (int) ($row['n'] ?? 0);
    }

    /** @return array{open: int, hits404: int, hitsOther: int} */
    public static function stats(): array
    {
        $row = Database::fetch(
            'SELECT
                COALESCE(SUM(CASE WHEN status = 404 THEN hits ELSE 0 END), 0) AS hits404,
                COALESCE(SUM(CASE WHEN status <> 404 THEN hits ELSE 0 END), 0) AS hits_other
             FROM http_errors
             WHERE updated_at >= (NOW() - INTERVAL 7 DAY)'
        );

        return [
            'open' => self::countOpen(),
            'hits404' => (int) ($row['hits404'] ?? 0),
            'hitsOther' => (int) ($row['hits_other'] ?? 0),
        ];
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int, per_page: int}
     */
    public static function search(string $q, string $filtre = 'suivi', int $page = 1, int $perPage = self::PER_PAGE): array
    {
        $perPage = max(1, min(100, $perPage));
        [$where, $having, $params] = self::filters($q, $filtre);

        $count = Database::fetch(
            'SELECT COUNT(*) AS n FROM (
                SELECT e.status, e.path_hash
                FROM http_errors e
                LEFT JOIN http_error_acks a ON a.status = e.status AND a.path_hash = e.path_hash
                ' . $where . '
                GROUP BY e.status, e.path_hash
                ' . $having . '
            ) grouped',
            $params
        );
        $total = (int) ($count['n'] ?? 0);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;

        $rows = $total === 0
            ? []
            : Database::fetchAll(
                'SELECT e.status,
                        e.path_hash,
                        MAX(e.path) AS path,
                        SUM(e.hits) AS hits,
                        MIN(e.created_at) AS first_seen,
                        MAX(e.updated_at) AS last_seen,
                        MAX(a.acked_at) AS acked_at
                 FROM http_errors e
                 LEFT JOIN http_error_acks a ON a.status = e.status AND a.path_hash = e.path_hash
                 ' . $where . '
                 GROUP BY e.status, e.path_hash
                 ' . $having . '
                 ORDER BY last_seen DESC
                 LIMIT ' . $perPage . ' OFFSET ' . $offset,
                $params
            );

        return [
            'items' => self::withLatest($rows),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
        ];
    }

    public static function findGroup(int $status, string $path): ?array
    {
        $path = self::clipPath($path);
        $hash = sha1($path);
        $row = Database::fetch(
            'SELECT e.status,
                    e.path_hash,
                    MAX(e.path) AS path,
                    SUM(e.hits) AS hits,
                    MIN(e.created_at) AS first_seen,
                    MAX(e.updated_at) AS last_seen,
                    MAX(a.acked_at) AS acked_at
             FROM http_errors e
             LEFT JOIN http_error_acks a ON a.status = e.status AND a.path_hash = e.path_hash
             WHERE e.status = ? AND e.path_hash = ?
             GROUP BY e.status, e.path_hash',
            [$status, $hash]
        );
        if (!$row) {
            return null;
        }

        $items = self::withLatest([$row]);

        return $items[0] ?? null;
    }

    /** @return list<array<string, mixed>> */
    public static function occurrences(int $status, string $path, int $limit = 80): array
    {
        $hash = sha1(self::clipPath($path));
        $limit = max(1, min(200, $limit));

        return Database::fetchAll(
            'SELECT e.*, TRIM(CONCAT(u.first_name, " ", u.last_name)) AS user_name
             FROM http_errors e
             LEFT JOIN users u ON u.id = e.user_id
             WHERE e.status = ? AND e.path_hash = ?
             ORDER BY e.updated_at DESC, e.id DESC
             LIMIT ' . $limit,
            [$status, $hash]
        );
    }

    public static function acknowledge(int $status, string $path, ?int $userId): void
    {
        $path = self::clipPath($path);
        Database::query(
            'INSERT INTO http_error_acks (status, path_hash, path, user_id, acked_at)
             VALUES (?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), path = VALUES(path), acked_at = NOW()',
            [$status, sha1($path), $path, $userId]
        );
    }

    public static function acknowledgeOpen(?int $userId): void
    {
        Database::query(
            'INSERT INTO http_error_acks (status, path_hash, path, user_id, acked_at)
             SELECT e.status, e.path_hash, MAX(e.path), ?, NOW()
             FROM http_errors e
             LEFT JOIN http_error_acks a ON a.status = e.status AND a.path_hash = e.path_hash
             GROUP BY e.status, e.path_hash
             HAVING MAX(a.acked_at) IS NULL OR MAX(e.updated_at) > MAX(a.acked_at)
             ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), path = VALUES(path), acked_at = NOW()',
            [$userId]
        );
    }

    public static function forget(int $status, string $path): void
    {
        $hash = sha1(self::clipPath($path));
        Database::query('DELETE FROM http_errors WHERE status = ? AND path_hash = ?', [$status, $hash]);
        Database::query('DELETE FROM http_error_acks WHERE status = ? AND path_hash = ?', [$status, $hash]);
    }

    public static function prune(): void
    {
        try {
            Database::query('DELETE FROM http_errors WHERE updated_at < (NOW() - INTERVAL 90 DAY)');
            $cut = Database::fetch('SELECT id FROM http_errors ORDER BY id DESC LIMIT 1 OFFSET 20000');
            if ($cut) {
                Database::query('DELETE FROM http_errors WHERE id <= ?', [(int) $cut['id']]);
            }
            Database::query(
                'DELETE a FROM http_error_acks a
                 LEFT JOIN http_errors e ON e.status = a.status AND e.path_hash = a.path_hash
                 WHERE e.id IS NULL'
            );
        } catch (\Throwable) {
        }
    }

    public static function label(int $status): string
    {
        return match ($status) {
            403 => 'Accès refusé',
            404 => 'Page introuvable',
            419 => 'Session expirée',
            429 => 'Trop de requêtes',
            500 => 'Erreur serveur',
            default => 'Erreur ' . $status,
        };
    }

    public static function tone(int $status): string
    {
        return match (true) {
            $status >= 500 => 'orange',
            $status === 404 => 'orange',
            $status === 403 => 'navy',
            default => 'grey',
        };
    }

    public static function listUrl(string $q = '', string $filtre = 'suivi', int $page = 1): string
    {
        $query = [];
        if ($q !== '') {
            $query['q'] = $q;
        }
        if ($filtre !== '' && $filtre !== 'suivi') {
            $query['filtre'] = $filtre;
        }
        if ($page > 1) {
            $query['page'] = $page;
        }

        return '/admin/erreurs' . ($query === [] ? '' : '?' . http_build_query($query));
    }

    public static function detailUrl(int $status, string $path, string $back = ''): string
    {
        $query = [
            'code' => $status,
            'chemin' => $path,
        ];
        if ($back !== '' && $back !== '/admin/erreurs') {
            $query['retour'] = $back;
        }

        return '/admin/erreurs/detail?' . http_build_query($query);
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private static function withLatest(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $conds = [];
        $params = [];
        foreach ($rows as $row) {
            $conds[] = '(status = ? AND path_hash = ?)';
            $params[] = (int) $row['status'];
            $params[] = (string) $row['path_hash'];
        }
        $latest = Database::fetchAll(
            'SELECT h.status, h.path_hash, h.method, h.query_string, h.referrer, h.message
             FROM http_errors h
             INNER JOIN (
                SELECT status, path_hash, MAX(updated_at) AS updated_at
                FROM http_errors
                WHERE ' . implode(' OR ', $conds) . '
                GROUP BY status, path_hash
             ) last ON last.status = h.status AND last.path_hash = h.path_hash AND last.updated_at = h.updated_at',
            $params
        );
        $by = [];
        foreach ($latest as $hit) {
            $by[(int) $hit['status'] . ':' . $hit['path_hash']] = $hit;
        }

        $out = [];
        foreach ($rows as $row) {
            $key = (int) $row['status'] . ':' . $row['path_hash'];
            $hit = $by[$key] ?? [];
            $acked = (string) ($row['acked_at'] ?? '');
            $last = (string) ($row['last_seen'] ?? '');
            $row['method'] = (string) ($hit['method'] ?? 'GET');
            $row['query_string'] = (string) ($hit['query_string'] ?? '');
            $row['referrer'] = (string) ($hit['referrer'] ?? '');
            $row['message'] = (string) ($hit['message'] ?? '');
            $row['hits'] = (int) ($row['hits'] ?? 0);
            $row['status'] = (int) ($row['status'] ?? 0);
            $row['open'] = $acked === '' || strcmp($last, $acked) > 0;
            $out[] = $row;
        }

        return $out;
    }

    /**
     * @return array{0: string, 1: string, 2: list<mixed>}
     */
    private static function filters(string $q, string $filtre): array
    {
        $where = 'WHERE 1=1';
        $params = [];
        $having = '';

        if (ctype_digit($filtre)) {
            $where .= ' AND e.status = ?';
            $params[] = (int) $filtre;
        } elseif ($filtre === 'autres') {
            $where .= ' AND e.status NOT IN (403, 404, 419, 429, 500)';
        }

        $q = trim($q);
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $where .= ' AND (e.path LIKE ? OR e.message LIKE ? OR e.referrer LIKE ? OR e.query_string LIKE ?)';
            array_push($params, $like, $like, $like, $like);
        }

        if ($filtre === 'suivi') {
            $having = 'HAVING MAX(a.acked_at) IS NULL OR MAX(e.updated_at) > MAX(a.acked_at)';
        } elseif ($filtre === 'vus') {
            $having = 'HAVING MAX(a.acked_at) IS NOT NULL AND MAX(e.updated_at) <= MAX(a.acked_at)';
        }

        return [$where, $having, $params];
    }

    private static function maybePrune(): void
    {
        if (random_int(1, 40) === 1) {
            self::prune();
        }
    }

    private static function requestPath(): string
    {
        $uri = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        $uri = is_string($uri) ? rawurldecode($uri) : '/';
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }

        return $uri === '' ? '/' : $uri;
    }

    private static function method(): string
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        return preg_match('/^[A-Z]{1,8}$/', $method) === 1 ? $method : 'GET';
    }

    private static function userId(): ?int
    {
        try {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                return null;
            }
            $id = Auth::id();

            return $id !== null && $id > 0 ? $id : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private static function clipPath(string $path): string
    {
        $path = self::clip($path, 500);
        if ($path === '' || $path[0] !== '/') {
            $path = '/' . ltrim($path, '/');
        }

        return self::clip($path, 500);
    }

    private static function cleanQuery(string $query): ?string
    {
        $query = ltrim($query, '?');
        if ($query === '') {
            return null;
        }
        parse_str($query, $params);
        if (!is_array($params)) {
            return null;
        }
        foreach (['token', '_token', 'password', 'passwd', 'secret', 'authorization', 'access_token', 'refresh_token'] as $key) {
            unset($params[$key]);
        }
        $built = http_build_query($params);
        if ($built === '') {
            return null;
        }

        return self::clip($built, 500);
    }

    private static function cleanReferrer(string $referrer): ?string
    {
        $referrer = trim($referrer);
        if ($referrer === '') {
            return null;
        }
        $parts = parse_url($referrer);
        if (!is_array($parts)) {
            return self::clip($referrer, 500);
        }
        $clean = '';
        if (!empty($parts['scheme']) && !empty($parts['host'])) {
            $clean = $parts['scheme'] . '://' . $parts['host'];
            if (!empty($parts['port'])) {
                $clean .= ':' . $parts['port'];
            }
        }
        $clean .= (string) ($parts['path'] ?? '');
        if (!empty($parts['query'])) {
            $query = self::cleanQuery((string) $parts['query']);
            if ($query !== null) {
                $clean .= '?' . $query;
            }
        }
        $clean = self::clip($clean, 500);

        return $clean !== '' ? $clean : null;
    }

    private static function clip(string $value, int $max): string
    {
        if (function_exists('iconv')) {
            $clean = iconv('UTF-8', 'UTF-8//IGNORE', $value);
            if (is_string($clean)) {
                $value = $clean;
            }
        }
        $value = str_replace(["\0", "\r", "\n"], ' ', $value);
        $value = trim($value);
        if (mb_strlen($value) > $max) {
            $value = mb_substr($value, 0, $max);
        }

        return $value;
    }
}
