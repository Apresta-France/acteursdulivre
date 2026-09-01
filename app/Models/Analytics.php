<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;
use Adl\Core\Env;
use Adl\Core\Request;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

final class Analytics
{
    public const LIVE_WINDOW = 5;
    public const RATE_MAX = 36;
    public const RETENTION_MONTHS = 24;

    private const BOT_UA = 'bot|crawl|spider|slurp|preview|wget|curl|python-requests|httpie|monitoring|uptime|headless|lighthouse|pagespeed|facebookexternalhit|pingdom|gtmetrix|semrush|ahrefs|mj12|dotbot|petalbot|bytespider|gptbot|chatgpt|claudebot|anthropic|perplexity|applebot|bingbot|yandex|duckduckbot|ia_archiver';

    private const SKIP_PREFIXES = [
        '/admin', '/cron', '/install', '/api', '/auth', '/sitemap.xml',
        '/robots.txt', '/llms.txt', '/public/',
    ];

    private const PAGE_LABELS = [
        'accueil' => 'Accueil',
        'recherche' => 'Recherche',
        'prestations' => 'Catalogue prestations',
        'prestataires' => 'Catalogue prestataires',
        'missions' => 'Appels d’offres',
        'journal' => 'Journal',
        'article' => 'Article du journal',
        'profil' => 'Profil prestataire',
        'prestation' => 'Fiche prestation',
        'mission' => 'Fiche mission',
        'metier' => 'Page métier',
        'metier_ville' => 'Métier × ville',
        'comment' => 'Comment ça marche',
        'tarifs' => 'Tarifs',
        'confiance' => 'Confiance',
        'apropos' => 'À propos',
        'aide' => 'Aide',
        'questions' => 'Questions',
        'contact' => 'Contact',
        'legal' => 'Page légale',
        'connexion' => 'Connexion',
        'inscription' => 'Inscription',
        'mdp' => 'Mot de passe',
        'espace' => 'Espace membre',
        'newsletter' => 'Newsletter',
        'erreur' => 'Page d’erreur',
        'autre' => 'Autre page',
    ];

    private const ACTION_LABELS = [
        'inscription' => 'Inscription',
        'connexion' => 'Connexion',
        'newsletter' => 'Inscription newsletter',
        'candidature' => 'Candidature envoyée',
        'favori' => 'Favori',
        'contact' => 'Message de contact',
        'message' => 'Message envoyé',
        'commande' => 'Commande ouverte',
        'filtre' => 'Filtre de recherche',
        'partage' => 'Partage',
        'signalement' => 'Signalement',
    ];

    private const PERIODS = [
        'jour' => 'Aujourd’hui',
        'hier' => 'Hier',
        '7j' => '7 jours',
        '14j' => '14 jours',
        '30j' => '30 jours',
        'semaine' => 'Cette semaine',
        'mois' => 'Ce mois',
        '90j' => '3 mois',
        '12m' => '12 mois',
        'xj' => 'X jours',
        'perso' => 'Personnalisé',
    ];

    /** @var array<string, int> */
    private static array $dailyBuf = [];

    /** @var array<string, int> */
    private static array $minuteBuf = [];

    public static function hit(): void
    {
        try {
            if (!self::shouldCollect()) {
                return;
            }
            $path = self::requestPath();
            $classified = self::classify($path);
            if ($classified === null) {
                return;
            }
            $visitor = self::visitorId();
            if (!self::touchLive($visitor, $classified['path'])) {
                return;
            }
            $unique = self::markUnique($visitor);
            self::bump('pv', '', '', true);
            self::bump('visit', 'pageview');
            if ($unique) {
                self::bump('visit', 'unique');
                self::bump('entry', $classified['path']);
            }
            self::bump('page', $classified['page'], '', true);
            self::bump('path', $classified['path'], '', true);
            if ($classified['entity'] !== null) {
                self::bump($classified['entity'][0], $classified['entity'][1], '', true);
            }
            self::bump('hour', self::now()->format('H'));
            self::bump('device', self::device());
            $ref = self::referrerHost();
            if ($ref !== '') {
                self::bump('ref', $ref);
            } elseif ($unique) {
                self::bump('ref', 'direct');
            }
            self::flush();
        } catch (Throwable) {
        }
    }

    public static function search(string $query, string $type, int $results, string $city = ''): void
    {
        try {
            if (!self::shouldCollect()) {
                return;
            }
            $q = self::normalizeQuery($query);
            $type = self::normalizeSearchType($type);
            $bucket = self::resultBucket($results);
            if ($q !== '') {
                self::bump('search', $q, $type . '|' . $bucket, true);
                self::bump('search_type', $type);
                if ($results === 0) {
                    self::bump('search_empty', $q, $type, true);
                }
            } else {
                self::bump('action', 'filtre', '', true);
            }
            if ($city !== '') {
                self::bump('search_city', mb_substr($city, 0, 80));
            }
            self::flush();
        } catch (Throwable) {
        }
    }

    public static function action(string $name): void
    {
        try {
            if (!self::shouldCollect()) {
                return;
            }
            $name = self::normalizeAction($name);
            if ($name === '') {
                return;
            }
            self::bump('action', $name, '', true);
            self::flush();
        } catch (Throwable) {
        }
    }

    public static function collectBeacon(Request $request): void
    {
        try {
            if (!self::shouldCollect() || !self::sameOrigin()) {
                return;
            }
            $action = $request->string('a');
            if ($action === '') {
                $raw = (string) file_get_contents('php://input');
                if ($raw !== '') {
                    $json = json_decode($raw, true);
                    if (is_array($json)) {
                        $action = trim((string) ($json['a'] ?? $json['action'] ?? ''));
                    }
                }
            }
            $action = self::normalizeAction($action);
            if ($action === '') {
                return;
            }
            $visitor = self::visitorId();
            if (!self::touchLive($visitor, self::requestPath())) {
                return;
            }
            self::bump('action', $action, '', true);
            self::flush();
        } catch (Throwable) {
        }
    }

    /** @return array{pruned_minute: int, pruned_uniques: int, pruned_live: int, pruned_daily: int} */
    public static function prune(): array
    {
        $now = self::now();
        $minuteCut = $now->modify('-3 hours')->format('Y-m-d H:i:s');
        $uniqueCut = $now->modify('-2 days')->format('Y-m-d');
        $liveCut = $now->modify('-30 minutes')->format('Y-m-d H:i:s');
        $dailyCut = $now->modify('-' . self::RETENTION_MONTHS . ' months')->format('Y-m-d');

        $count = static function (string $sql, array $params): int {
            try {
                return Database::query($sql, $params)->rowCount();
            } catch (Throwable) {
                return 0;
            }
        };

        return [
            'pruned_minute' => $count('DELETE FROM stats_minute WHERE bucket < ?', [$minuteCut]),
            'pruned_uniques' => $count('DELETE FROM stats_uniques WHERE day < ?', [$uniqueCut]),
            'pruned_live' => $count('DELETE FROM stats_live WHERE seen_at < ?', [$liveCut]),
            'pruned_daily' => $count('DELETE FROM stats_daily WHERE day < ?', [$dailyCut]),
        ];
    }

    /** @return array<string, mixed> */
    public static function dashboard(Request $request): array
    {
        $period = self::resolvePeriod($request);
        $compare = $request->string('compare', '1') !== '0';
        $current = self::aggregateRange($period['from'], $period['to']);
        $previous = $compare
            ? self::aggregateRange($period['prev_from'], $period['prev_to'])
            : self::emptyAggregate();
        $series = self::series($period, $compare);

        return [
            'period' => $period,
            'compare' => $compare,
            'periods' => self::PERIODS,
            'kpis' => self::kpis($current, $previous, $compare),
            'series' => $series,
            'pages' => self::ranked($current['page'], $previous['page'], $compare, [self::class, 'pageLabel']),
            'paths' => self::rankedPaths($current['path'], $previous['path'], $compare),
            'profiles' => self::rankedEntities('profile', $current['profile'], $previous['profile'], $compare),
            'services' => self::rankedEntities('service', $current['service'], $previous['service'], $compare),
            'missions' => self::rankedEntities('mission', $current['mission'], $previous['mission'], $compare),
            'articles' => self::rankedEntities('article', $current['article'], $previous['article'], $compare),
            'metiers' => self::rankedEntities('metier', $current['metier'] ?? [], $previous['metier'] ?? [], $compare),
            'searches' => self::rankedSearches($current['search'], $previous['search'], $compare),
            'search_empty' => self::ranked($current['search_empty'], $previous['search_empty'], $compare),
            'search_types' => self::ranked($current['search_type'], $previous['search_type'], $compare, [self::class, 'searchTypeLabel']),
            'search_cities' => self::ranked($current['search_city'], $previous['search_city'], $compare),
            'actions' => self::ranked($current['action'], $previous['action'], $compare, [self::class, 'actionLabel']),
            'referrers' => self::ranked($current['ref'], $previous['ref'], $compare, [self::class, 'refLabel']),
            'devices' => self::ranked($current['device'], $previous['device'], $compare),
            'entries' => self::rankedPaths($current['entry'], $previous['entry'], $compare),
            'hours' => self::hours($period['from'] === $period['to'] ? $period['from'] : null, $compare ? $period['prev_from'] : null),
            'live' => self::liveSnapshot(),
        ];
    }

    /** @return array<string, mixed> */
    public static function liveSnapshot(): array
    {
        $now = self::now();
        $from5 = $now->modify('-' . self::LIVE_WINDOW . ' minutes')->format('Y-m-d H:i:s');
        $from15 = $now->modify('-15 minutes')->format('Y-m-d H:i:s');
        $from60 = $now->modify('-60 minutes')->format('Y-m-d H:i:00');

        $nowCount = 0;
        $pages = [];
        try {
            $nowCount = (int) (Database::fetch(
                'SELECT COUNT(*) AS n FROM stats_live WHERE seen_at >= ?',
                [$from5]
            )['n'] ?? 0);
            $pageRows = Database::fetchAll(
                'SELECT page, COUNT(*) AS n FROM stats_live WHERE seen_at >= ? AND page != \'\' GROUP BY page ORDER BY n DESC LIMIT 8',
                [$from5]
            );
            $pages = self::labelPathRows($pageRows);
        } catch (Throwable) {
        }

        $views15 = self::sumMinute('pv', $from15);
        $views60 = self::sumMinute('pv', $from60);
        $minutes = self::minuteSeries($from60, $now);
        $profiles = self::topMinute('profile', $from15, 6);
        $searches = self::topMinute('search', $from15, 8);
        $actions = self::topMinute('action', $from15, 8);

        return [
            'now' => $nowCount,
            'views_15' => $views15,
            'views_60' => $views60,
            'minutes' => $minutes,
            'pages' => $pages,
            'profiles' => self::labelEntityRows('profile', $profiles),
            'searches' => array_map(static function (array $row): array {
                return [
                    'label' => (string) $row['dim'],
                    'n' => (int) $row['n'],
                    'href' => '/recherche?q=' . rawurlencode((string) $row['dim']),
                ];
            }, $searches),
            'actions' => array_map(static function (array $row): array {
                return [
                    'label' => self::actionLabel((string) $row['dim']),
                    'n' => (int) $row['n'],
                ];
            }, $actions),
            'updated' => $now->format('H:i:s'),
        ];
    }

    public static function periodQuery(array $keep, array $override = []): string
    {
        $params = array_merge($keep, $override);
        foreach ($params as $key => $value) {
            if ($value === null || $value === '' || $value === false) {
                unset($params[$key]);
            }
        }
        return $params === [] ? '/admin/statistiques' : '/admin/statistiques?' . http_build_query($params);
    }

    private static function shouldCollect(): bool
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if ($method === 'HEAD') {
            return false;
        }
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        if ($ua === '' || preg_match('/' . self::BOT_UA . '/i', $ua)) {
            return false;
        }
        $cached = $_SESSION['_user_cache'] ?? null;
        if (is_array($cached) && ($cached['role'] ?? '') === 'admin') {
            return false;
        }
        return true;
    }

    private static function sameOrigin(): bool
    {
        $appHost = parse_url((string) Env::get('APP_URL', ''), PHP_URL_HOST);
        if (!is_string($appHost) || $appHost === '') {
            return true;
        }
        foreach (['HTTP_ORIGIN' => true, 'HTTP_REFERER' => true] as $header => $_) {
            $raw = (string) ($_SERVER[$header] ?? '');
            if ($raw === '') {
                continue;
            }
            $host = parse_url($raw, PHP_URL_HOST);
            return is_string($host) && strcasecmp($host, $appHost) === 0;
        }
        return true;
    }

    private static function visitorId(): string
    {
        $day = self::today();
        $ip = self::ipClass();
        $ua = substr(hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 16);
        $key = (string) (Env::get('APP_KEY', '') ?: 'adl-stats');
        return substr(hash_hmac('sha256', $day . '|' . $ip . '|' . $ua, $key), 0, 16);
    }

    private static function ipClass(): string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        if (str_contains($ip, ':')) {
            $parts = explode(':', $ip);
            return implode(':', array_slice($parts, 0, 4));
        }
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            return $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.0';
        }
        return '0.0.0.0';
    }

    private static function touchLive(string $visitor, string $page): bool
    {
        $now = self::now();
        $seen = $now->format('Y-m-d H:i:s');
        $minute = $now->format('Y-m-d H:i:00');
        $page = mb_substr($page, 0, 160);
        $row = Database::fetch('SELECT minute, minute_hits FROM stats_live WHERE visitor = ?', [$visitor]);
        if ($row === null) {
            Database::query(
                'INSERT INTO stats_live (visitor, seen_at, page, minute, minute_hits) VALUES (?, ?, ?, ?, 1)',
                [$visitor, $seen, $page, $minute]
            );
            return true;
        }
        if ((string) $row['minute'] === $minute) {
            $hits = (int) $row['minute_hits'];
            if ($hits >= self::RATE_MAX) {
                return false;
            }
            Database::query(
                'UPDATE stats_live SET seen_at = ?, page = ?, minute_hits = minute_hits + 1 WHERE visitor = ?',
                [$seen, $page, $visitor]
            );
            return true;
        }
        Database::query(
            'UPDATE stats_live SET seen_at = ?, page = ?, minute = ?, minute_hits = 1 WHERE visitor = ?',
            [$seen, $page, $minute, $visitor]
        );
        return true;
    }

    private static function markUnique(string $visitor): bool
    {
        $stmt = Database::query(
            'INSERT IGNORE INTO stats_uniques (day, visitor) VALUES (?, ?)',
            [self::today(), $visitor]
        );
        return $stmt->rowCount() > 0;
    }

    private static function bump(string $kind, string $dim = '', string $extra = '', bool $minute = false): void
    {
        $dim = mb_substr($dim, 0, 160);
        $extra = mb_substr($extra, 0, 80);
        $key = $kind . "\0" . $dim . "\0" . $extra;
        self::$dailyBuf[$key] = (self::$dailyBuf[$key] ?? 0) + 1;
        if ($minute) {
            $mkey = $kind . "\0" . $dim;
            self::$minuteBuf[$mkey] = (self::$minuteBuf[$mkey] ?? 0) + 1;
        }
    }

    private static function flush(): void
    {
        if (self::$dailyBuf === [] && self::$minuteBuf === []) {
            return;
        }
        $day = self::today();
        if (self::$dailyBuf !== []) {
            $values = [];
            $params = [];
            foreach (self::$dailyBuf as $key => $hits) {
                [$kind, $dim, $extra] = explode("\0", $key, 3);
                $values[] = '(?, ?, ?, ?, ?)';
                array_push($params, $day, $kind, $dim, $extra, $hits);
            }
            Database::query(
                'INSERT INTO stats_daily (day, kind, dim, extra, hits) VALUES ' . implode(', ', $values)
                . ' ON DUPLICATE KEY UPDATE hits = hits + VALUES(hits)',
                $params
            );
            self::$dailyBuf = [];
        }
        if (self::$minuteBuf !== []) {
            $bucket = self::now()->format('Y-m-d H:i:00');
            $values = [];
            $params = [];
            foreach (self::$minuteBuf as $key => $hits) {
                [$kind, $dim] = explode("\0", $key, 2);
                $values[] = '(?, ?, ?, ?)';
                array_push($params, $bucket, $kind, $dim, $hits);
            }
            Database::query(
                'INSERT INTO stats_minute (bucket, kind, dim, hits) VALUES ' . implode(', ', $values)
                . ' ON DUPLICATE KEY UPDATE hits = hits + VALUES(hits)',
                $params
            );
            self::$minuteBuf = [];
        }
    }

    /** @return array{page: string, path: string, entity: ?array{0: string, 1: string}}|null */
    private static function classify(string $path): ?array
    {
        foreach (self::SKIP_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, rtrim($prefix, '/') . '/')) {
                return null;
            }
        }
        $code = http_response_code();
        if ($code >= 400) {
            return ['page' => 'erreur', 'path' => '/erreur/' . $code, 'entity' => null];
        }

        $parts = $path === '/' ? [] : explode('/', trim($path, '/'));
        $head = $parts[0] ?? '';
        $slug = $parts[1] ?? '';
        $tail = $parts[2] ?? '';

        if ($head === '') {
            return ['page' => 'accueil', 'path' => '/', 'entity' => null];
        }

        $static = [
            'recherche' => 'recherche',
            'comment-ca-marche' => 'comment',
            'tarifs' => 'tarifs',
            'confiance' => 'confiance',
            'a-propos' => 'apropos',
            'aide' => 'aide',
            'questions' => 'questions',
            'contact' => 'contact',
            'connexion' => 'connexion',
            'inscription' => 'inscription',
            'mot-de-passe-oublie' => 'mdp',
            'mentions-legales' => 'legal',
            'cgu' => 'legal',
            'cgv' => 'legal',
            'confidentialite' => 'legal',
            'cookies' => 'legal',
            'regles-ia' => 'legal',
        ];

        if ($head === 'espace') {
            return ['page' => 'espace', 'path' => '/espace', 'entity' => null];
        }
        if ($head === 'newsletter') {
            return ['page' => 'newsletter', 'path' => '/newsletter', 'entity' => null];
        }
        if ($head === 'mot-de-passe') {
            return ['page' => 'mdp', 'path' => '/mot-de-passe', 'entity' => null];
        }
        if (isset($static[$head]) && $slug === '') {
            return ['page' => $static[$head], 'path' => '/' . $head, 'entity' => null];
        }
        if ($head === 'prestataires' && $slug === '') {
            return ['page' => 'prestataires', 'path' => '/prestataires', 'entity' => null];
        }
        if ($head === 'prestataires' && $slug !== '' && $tail === '') {
            return ['page' => 'profil', 'path' => '/prestataires/' . $slug, 'entity' => ['profile', $slug]];
        }
        if ($head === 'prestations' && $slug === '') {
            return ['page' => 'prestations', 'path' => '/prestations', 'entity' => null];
        }
        if ($head === 'prestations' && $slug !== '' && $tail === '') {
            return ['page' => 'prestation', 'path' => '/prestations/' . $slug, 'entity' => ['service', $slug]];
        }
        if ($head === 'missions' && $slug === '') {
            return ['page' => 'missions', 'path' => '/missions', 'entity' => null];
        }
        if ($head === 'missions' && $slug !== '' && $tail === '') {
            return ['page' => 'mission', 'path' => '/missions/' . $slug, 'entity' => ['mission', $slug]];
        }
        if ($head === 'journal' && $slug === '') {
            return ['page' => 'journal', 'path' => '/journal', 'entity' => null];
        }
        if ($head === 'journal' && $slug !== '' && $tail === '') {
            return ['page' => 'article', 'path' => '/journal/' . $slug, 'entity' => ['article', $slug]];
        }
        if ($head === 'metiers' && $slug !== '' && $tail === '') {
            return ['page' => 'metier', 'path' => '/metiers/' . $slug, 'entity' => ['metier', $slug]];
        }
        if ($head === 'metiers' && $slug !== '' && $tail !== '') {
            return ['page' => 'metier_ville', 'path' => '/metiers/' . $slug . '/' . $tail, 'entity' => ['metier', $slug]];
        }

        return ['page' => 'autre', 'path' => '/' . $head, 'entity' => null];
    }

    private static function requestPath(): string
    {
        $uri = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
        $uri = rawurldecode($uri);
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }
        return $uri === '' ? '/' : $uri;
    }

    private static function normalizeQuery(string $query): string
    {
        $q = mb_strtolower(trim($query));
        $q = preg_replace('/\s+/u', ' ', $q) ?? $q;
        $q = mb_substr($q, 0, 80);
        if ($q === '' || str_contains($q, '@') || preg_match('/https?:\/\//', $q)) {
            return '';
        }
        return $q;
    }

    private static function normalizeSearchType(string $type): string
    {
        return in_array($type, ['prestations', 'prestataires', 'missions'], true) ? $type : 'all';
    }

    private static function resultBucket(int $results): string
    {
        if ($results <= 0) {
            return '0';
        }
        if ($results <= 3) {
            return '1-3';
        }
        if ($results <= 12) {
            return '4-12';
        }
        return '13+';
    }

    private static function normalizeAction(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/[^a-z0-9_-]/', '', $name) ?? '';
        return mb_substr($name, 0, 40);
    }

    private static function device(): string
    {
        $ua = strtolower((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if (preg_match('/tablet|ipad|playbook|silk/', $ua)) {
            return 'tablette';
        }
        if (preg_match('/mobile|android|iphone|ipod|phone/', $ua)) {
            return 'mobile';
        }
        return 'ordinateur';
    }

    private static function referrerHost(): string
    {
        $ref = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        if ($ref === '') {
            return '';
        }
        $host = parse_url($ref, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return '';
        }
        $own = parse_url((string) Env::get('APP_URL', ''), PHP_URL_HOST);
        if (is_string($own) && $own !== '' && strcasecmp($host, $own) === 0) {
            return '';
        }
        return mb_strtolower($host);
    }

    /**
     * @return array{
     *     id: string,
     *     label: string,
     *     from: string,
     *     to: string,
     *     prev_from: string,
     *     prev_to: string,
     *     days: int,
     *     jours: int,
     *     du: string,
     *     au: string,
     *     hourly: bool,
     *     range_label: string,
     *     prev_label: string
     * }
     */
    private static function resolvePeriod(Request $request): array
    {
        $tz = self::tz();
        $today = new DateTimeImmutable('today', $tz);
        $id = $request->string('periode', '7j');
        if (!isset(self::PERIODS[$id])) {
            $id = '7j';
        }
        $jours = max(1, min(366, $request->int('jours', 21) ?? 21));
        $du = $request->string('du');
        $au = $request->string('au');

        $from = $today;
        $to = $today;

        switch ($id) {
            case 'jour':
                break;
            case 'hier':
                $from = $today->modify('-1 day');
                $to = $from;
                break;
            case '7j':
                $from = $today->modify('-6 days');
                break;
            case '14j':
                $from = $today->modify('-13 days');
                break;
            case '30j':
                $from = $today->modify('-29 days');
                break;
            case 'semaine':
                $from = $today->modify('monday this week');
                break;
            case 'mois':
                $from = $today->modify('first day of this month');
                break;
            case '90j':
                $from = $today->modify('-89 days');
                break;
            case '12m':
                $from = $today->modify('-11 months')->modify('first day of this month');
                break;
            case 'xj':
                $from = $today->modify('-' . ($jours - 1) . ' days');
                break;
            case 'perso':
                $fromParsed = self::parseDay($du) ?? $today->modify('-6 days');
                $toParsed = self::parseDay($au) ?? $today;
                if ($fromParsed > $toParsed) {
                    [$fromParsed, $toParsed] = [$toParsed, $fromParsed];
                }
                $max = $fromParsed->modify('+365 days');
                if ($toParsed > $max) {
                    $toParsed = $max;
                }
                $from = $fromParsed;
                $to = $toParsed;
                $du = $from->format('Y-m-d');
                $au = $to->format('Y-m-d');
                break;
        }

        $days = (int) $from->diff($to)->format('%a') + 1;
        $prevTo = $from->modify('-1 day');
        $prevFrom = $prevTo->modify('-' . ($days - 1) . ' days');

        return [
            'id' => $id,
            'label' => self::PERIODS[$id],
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'prev_from' => $prevFrom->format('Y-m-d'),
            'prev_to' => $prevTo->format('Y-m-d'),
            'days' => $days,
            'jours' => $jours,
            'du' => $du,
            'au' => $au,
            'hourly' => $days === 1,
            'range_label' => self::rangeLabel($from, $to),
            'prev_label' => self::rangeLabel($prevFrom, $prevTo),
        ];
    }

    private static function parseDay(string $value): ?DateTimeImmutable
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value, self::tz());
        return $date instanceof DateTimeImmutable ? $date : null;
    }

    private static function rangeLabel(DateTimeImmutable $from, DateTimeImmutable $to): string
    {
        $months = [1 => 'janv.', 2 => 'févr.', 3 => 'mars', 4 => 'avr.', 5 => 'mai', 6 => 'juin', 7 => 'juil.', 8 => 'août', 9 => 'sept.', 10 => 'oct.', 11 => 'nov.', 12 => 'déc.'];
        $fmt = static function (DateTimeImmutable $d) use ($months): string {
            return (int) $d->format('j') . ' ' . $months[(int) $d->format('n')] . ' ' . $d->format('Y');
        };
        if ($from->format('Y-m-d') === $to->format('Y-m-d')) {
            return $fmt($from);
        }
        return $fmt($from) . ' → ' . $fmt($to);
    }

    /**
     * @return array{
     *     pv: int,
     *     unique: int,
     *     search: array<string, int>,
     *     search_empty: array<string, int>,
     *     search_type: array<string, int>,
     *     search_city: array<string, int>,
     *     page: array<string, int>,
     *     path: array<string, int>,
     *     profile: array<string, int>,
     *     service: array<string, int>,
     *     mission: array<string, int>,
     *     article: array<string, int>,
     *     action: array<string, int>,
     *     ref: array<string, int>,
     *     device: array<string, int>,
     *     entry: array<string, int>
     * }
     */
    private static function aggregateRange(string $from, string $to): array
    {
        $out = self::emptyAggregate();
        try {
            $rows = Database::fetchAll(
                'SELECT kind, dim, extra, SUM(hits) AS hits
                 FROM stats_daily
                 WHERE day BETWEEN ? AND ?
                 GROUP BY kind, dim, extra',
                [$from, $to]
            );
        } catch (Throwable) {
            return $out;
        }

        foreach ($rows as $row) {
            $kind = (string) $row['kind'];
            $dim = (string) $row['dim'];
            $hits = (int) $row['hits'];
            if ($kind === 'pv') {
                $out['pv'] += $hits;
                continue;
            }
            if ($kind === 'visit' && $dim === 'unique') {
                $out['unique'] += $hits;
                continue;
            }
            if (isset($out[$kind]) && is_array($out[$kind]) && $dim !== '') {
                $key = $kind === 'search' ? $dim : $dim;
                $out[$kind][$key] = ($out[$kind][$key] ?? 0) + $hits;
            }
        }

        return $out;
    }

    /** @return array<string, mixed> */
    private static function emptyAggregate(): array
    {
        return [
            'pv' => 0,
            'unique' => 0,
            'search' => [],
            'search_empty' => [],
            'search_type' => [],
            'search_city' => [],
            'page' => [],
            'path' => [],
            'profile' => [],
            'service' => [],
            'mission' => [],
            'article' => [],
            'metier' => [],
            'action' => [],
            'ref' => [],
            'device' => [],
            'entry' => [],
        ];
    }

    /**
     * @param array<string, mixed> $current
     * @param array<string, mixed> $previous
     * @return list<array<string, mixed>>
     */
    private static function kpis(array $current, array $previous, bool $compare): array
    {
        $searchN = array_sum($current['search']);
        $actionN = array_sum($current['action']);
        $profileN = array_sum($current['profile']);
        $items = [
            ['k' => 'Pages vues', 'n' => (int) $current['pv'], 'p' => (int) $previous['pv']],
            ['k' => 'Visiteurs', 'n' => (int) $current['unique'], 'p' => (int) $previous['unique']],
            ['k' => 'Recherches', 'n' => (int) $searchN, 'p' => (int) array_sum($previous['search'])],
            ['k' => 'Prestataires vus', 'n' => (int) $profileN, 'p' => (int) array_sum($previous['profile'])],
            ['k' => 'Actions', 'n' => (int) $actionN, 'p' => (int) array_sum($previous['action'])],
        ];
        $out = [];
        foreach ($items as $item) {
            $out[] = [
                'k' => $item['k'],
                'v' => format_int($item['n']),
                'n' => $item['n'],
                'delta' => $compare ? self::delta($item['n'], $item['p']) : null,
            ];
        }
        return $out;
    }

    /** @return array{text: string, tone: string, pct: ?int} */
    private static function delta(int $current, int $previous): array
    {
        if ($previous === 0 && $current === 0) {
            return ['text' => 'identique', 'tone' => 'flat', 'pct' => 0];
        }
        if ($previous === 0) {
            return ['text' => 'nouveau', 'tone' => 'up', 'pct' => null];
        }
        $pct = (int) round(100 * ($current - $previous) / $previous);
        if ($pct === 0) {
            return ['text' => '0 %', 'tone' => 'flat', 'pct' => 0];
        }
        $sign = $pct > 0 ? '+' : '';
        return [
            'text' => $sign . $pct . ' %',
            'tone' => $pct > 0 ? 'up' : 'down',
            'pct' => $pct,
        ];
    }

    /**
     * @param array<string, mixed> $period
     * @return list<array{label: string, current: int, previous: int, uniques: int}>
     */
    private static function series(array $period, bool $compare): array
    {
        if (!empty($period['hourly'])) {
            return self::hourSeries($period['from'], $compare ? $period['prev_from'] : null);
        }

        $from = $period['from'];
        $to = $period['to'];
        $days = (int) $period['days'];
        $weekly = $days > 90;
        $pv = self::byDay($from, $to, 'pv');
        $uniques = self::byDay($from, $to, 'visit', 'unique');
        $prevPv = $compare ? self::byDay($period['prev_from'], $period['prev_to'], 'pv') : [];

        $start = self::parseDay($from) ?? self::now();
        $end = self::parseDay($to) ?? $start;
        $cursor = $start;
        $out = [];
        $i = 0;
        while ($cursor <= $end) {
            $key = $cursor->format('Y-m-d');
            $prevKey = (self::parseDay($period['prev_from']) ?? $cursor)
                ->modify('+' . $i . ' days')
                ->format('Y-m-d');
            $out[] = [
                'label' => $weekly ? $cursor->format('W') : ((int) $cursor->format('j') . '/' . $cursor->format('n')),
                'current' => (int) ($pv[$key] ?? 0),
                'previous' => (int) ($prevPv[$prevKey] ?? 0),
                'uniques' => (int) ($uniques[$key] ?? 0),
                'day' => $key,
            ];
            $cursor = $cursor->modify('+1 day');
            $i++;
        }

        if ($weekly) {
            $grouped = [];
            foreach ($out as $row) {
                $week = substr($row['day'], 0, 8) . 'W' . (self::parseDay($row['day'])?->format('W') ?? '');
                if (!isset($grouped[$week])) {
                    $grouped[$week] = [
                        'label' => 'S' . (self::parseDay($row['day'])?->format('W') ?? ''),
                        'current' => 0,
                        'previous' => 0,
                        'uniques' => 0,
                    ];
                }
                $grouped[$week]['current'] += $row['current'];
                $grouped[$week]['previous'] += $row['previous'];
                $grouped[$week]['uniques'] += $row['uniques'];
            }
            return array_values($grouped);
        }

        return $out;
    }

    /** @return list<array{label: string, current: int, previous: int, uniques: int}> */
    private static function hourSeries(string $day, ?string $prevDay): array
    {
        $cur = self::hoursMap($day);
        $prev = $prevDay !== null ? self::hoursMap($prevDay) : [];
        $out = [];
        for ($h = 0; $h < 24; $h++) {
            $key = str_pad((string) $h, 2, '0', STR_PAD_LEFT);
            $out[] = [
                'label' => $key . 'h',
                'current' => (int) ($cur[$key] ?? 0),
                'previous' => (int) ($prev[$key] ?? 0),
                'uniques' => 0,
            ];
        }
        return $out;
    }

    /** @return array<string, int> */
    private static function hoursMap(string $day): array
    {
        $out = [];
        try {
            $rows = Database::fetchAll(
                'SELECT dim, SUM(hits) AS hits FROM stats_daily WHERE day = ? AND kind = \'hour\' GROUP BY dim',
                [$day]
            );
            foreach ($rows as $row) {
                $out[(string) $row['dim']] = (int) $row['hits'];
            }
        } catch (Throwable) {
        }
        return $out;
    }

    /** @return array<string, int> */
    private static function byDay(string $from, string $to, string $kind, string $dim = ''): array
    {
        $out = [];
        try {
            if ($dim === '') {
                $rows = Database::fetchAll(
                    'SELECT day, SUM(hits) AS hits FROM stats_daily WHERE day BETWEEN ? AND ? AND kind = ? GROUP BY day',
                    [$from, $to, $kind]
                );
            } else {
                $rows = Database::fetchAll(
                    'SELECT day, SUM(hits) AS hits FROM stats_daily WHERE day BETWEEN ? AND ? AND kind = ? AND dim = ? GROUP BY day',
                    [$from, $to, $kind, $dim]
                );
            }
            foreach ($rows as $row) {
                $out[(string) $row['day']] = (int) $row['hits'];
            }
        } catch (Throwable) {
        }
        return $out;
    }

    /**
     * @param array<string, int> $current
     * @param array<string, int> $previous
     * @return list<array<string, mixed>>
     */
    private static function ranked(array $current, array $previous, bool $compare, ?callable $label = null, int $limit = 12): array
    {
        arsort($current, SORT_NUMERIC);
        $max = max(1, ...array_values($current + [0]));
        $out = [];
        $i = 0;
        foreach ($current as $dim => $hits) {
            if ($dim === '' || $i >= $limit) {
                break;
            }
            $prev = (int) ($previous[$dim] ?? 0);
            $out[] = [
                'key' => $dim,
                'label' => $label ? (string) $label($dim) : $dim,
                'n' => (int) $hits,
                'v' => format_int((int) $hits),
                'pct' => (int) round(100 * $hits / $max),
                'delta' => $compare ? self::delta((int) $hits, $prev) : null,
                'href' => null,
            ];
            $i++;
        }
        return $out;
    }

    /**
     * @param array<string, int> $current
     * @param array<string, int> $previous
     * @return list<array<string, mixed>>
     */
    private static function rankedPaths(array $current, array $previous, bool $compare): array
    {
        $rows = self::ranked($current, $previous, $compare, null, 15);
        $byKind = [];
        foreach ($rows as $row) {
            $classified = self::classify((string) $row['key']);
            if ($classified !== null && $classified['entity'] !== null) {
                $byKind[$classified['entity'][0]][] = $classified['entity'][1];
            }
        }
        $resolved = [];
        foreach ($byKind as $kind => $slugs) {
            $resolved[$kind] = self::entityLabels($kind, $slugs);
        }
        foreach ($rows as $i => $row) {
            $href = (string) $row['key'];
            $rows[$i]['label'] = self::pathLabelResolved($href, $resolved);
            $rows[$i]['href'] = str_starts_with($href, '/') ? $href : null;
        }
        return $rows;
    }

    /** @param array<string, array<string, string>> $resolved */
    private static function pathLabelResolved(string $path, array $resolved): string
    {
        $classified = $path === '/' || str_starts_with($path, '/erreur/')
            ? null
            : self::classify($path);
        if ($classified !== null && $classified['entity'] !== null) {
            $kind = $classified['entity'][0];
            $slug = $classified['entity'][1];
            if (isset($resolved[$kind][$slug])) {
                return $resolved[$kind][$slug];
            }
        }
        return self::pathLabel($path);
    }

    /**
     * @param array<string, int> $current
     * @param array<string, int> $previous
     * @return list<array<string, mixed>>
     */
    private static function rankedEntities(string $kind, array $current, array $previous, bool $compare): array
    {
        $rows = self::ranked($current, $previous, $compare, null, 12);
        $labels = self::entityLabels($kind, array_column($rows, 'key'));
        $prefix = match ($kind) {
            'profile' => '/prestataires/',
            'service' => '/prestations/',
            'mission' => '/missions/',
            'article' => '/journal/',
            'metier' => '/metiers/',
            default => '/',
        };
        foreach ($rows as $i => $row) {
            $slug = (string) $row['key'];
            $rows[$i]['label'] = $labels[$slug] ?? $slug;
            $rows[$i]['href'] = $prefix . $slug;
        }
        return $rows;
    }

    /**
     * @param array<string, int> $current
     * @param array<string, int> $previous
     * @return list<array<string, mixed>>
     */
    private static function rankedSearches(array $current, array $previous, bool $compare): array
    {
        $rows = self::ranked($current, $previous, $compare, null, 20);
        foreach ($rows as $i => $row) {
            $q = (string) $row['key'];
            $rows[$i]['href'] = '/recherche?q=' . rawurlencode($q);
        }
        return $rows;
    }

    /**
     * @return list<array{label: string, current: int, previous: int}>
     */
    private static function hours(?string $day, ?string $prevDay): array
    {
        if ($day === null) {
            return [];
        }
        return self::hourSeries($day, $prevDay);
    }

    /** @param list<array{page?: string, dim?: string, n: int|string}> $rows */
    private static function labelPathRows(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $path = (string) ($row['page'] ?? $row['dim'] ?? '');
            $out[] = [
                'label' => self::pathLabel($path),
                'n' => (int) $row['n'],
                'href' => str_starts_with($path, '/') ? $path : null,
            ];
        }
        return $out;
    }

    /** @param list<array{dim: string, n: int|string}> $rows */
    private static function labelEntityRows(string $kind, array $rows): array
    {
        $labels = self::entityLabels($kind, array_column($rows, 'dim'));
        $prefix = $kind === 'profile' ? '/prestataires/' : '/';
        $out = [];
        foreach ($rows as $row) {
            $slug = (string) $row['dim'];
            $out[] = [
                'label' => $labels[$slug] ?? $slug,
                'n' => (int) $row['n'],
                'href' => $prefix . $slug,
            ];
        }
        return $out;
    }

    /** @param list<string> $slugs @return array<string, string> */
    private static function entityLabels(string $kind, array $slugs): array
    {
        $slugs = array_values(array_filter($slugs, static fn (string $s): bool => $s !== ''));
        if ($slugs === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($slugs), '?'));
        $out = [];
        try {
            if ($kind === 'profile') {
                $rows = Database::fetchAll(
                    "SELECT p.slug, p.name_mode, p.public_name, u.first_name, u.last_name
                     FROM profiles p
                     JOIN users u ON u.id = p.user_id
                     WHERE p.slug IN ({$placeholders})",
                    $slugs
                );
                foreach ($rows as $row) {
                    $out[(string) $row['slug']] = Profile::displayName($row);
                }
            } elseif ($kind === 'service') {
                $rows = Database::fetchAll("SELECT slug, title FROM services WHERE slug IN ({$placeholders})", $slugs);
                foreach ($rows as $row) {
                    $out[(string) $row['slug']] = (string) $row['title'];
                }
            } elseif ($kind === 'mission') {
                $rows = Database::fetchAll("SELECT slug, title FROM missions WHERE slug IN ({$placeholders})", $slugs);
                foreach ($rows as $row) {
                    $out[(string) $row['slug']] = (string) $row['title'];
                }
            } elseif ($kind === 'article') {
                $rows = Database::fetchAll("SELECT slug, title FROM articles WHERE slug IN ({$placeholders})", $slugs);
                foreach ($rows as $row) {
                    $out[(string) $row['slug']] = (string) $row['title'];
                }
            } elseif ($kind === 'metier') {
                foreach ($slugs as $slug) {
                    $trade = \Adl\Data\Catalog::tradeFromSlug((string) $slug);
                    $out[(string) $slug] = $trade ?? (string) $slug;
                }
            }
        } catch (Throwable) {
        }
        return $out;
    }

    private static function pageLabel(string $key): string
    {
        return self::PAGE_LABELS[$key] ?? $key;
    }

    private static function actionLabel(string $key): string
    {
        if ($key === 'recherche') {
            return 'Recherche lancée';
        }
        return self::ACTION_LABELS[$key] ?? $key;
    }

    private static function searchTypeLabel(string $key): string
    {
        return match ($key) {
            'prestations' => 'Prestations',
            'prestataires' => 'Prestataires',
            'missions' => 'Missions',
            default => 'Tous types',
        };
    }

    private static function refLabel(string $key): string
    {
        return $key === 'direct' ? 'Accès direct' : $key;
    }

    private static function pathLabel(string $path): string
    {
        if ($path === '/') {
            return 'Accueil';
        }
        if (str_starts_with($path, '/erreur/')) {
            return 'Erreur ' . substr($path, 8);
        }
        $classified = self::classify($path);
        if ($classified === null) {
            return $path;
        }
        if ($classified['entity'] !== null) {
            $kind = $classified['entity'][0];
            $slug = $classified['entity'][1];
            $labels = self::entityLabels($kind, [$slug]);
            if (isset($labels[$slug])) {
                return $labels[$slug];
            }
        }
        if ($classified['page'] === 'legal') {
            return match ($path) {
                '/mentions-legales' => 'Mentions légales',
                '/cgu' => 'CGU',
                '/cgv' => 'CGV',
                '/confidentialite' => 'Confidentialité',
                '/cookies' => 'Cookies',
                '/regles-ia' => 'Règles IA',
                default => 'Page légale',
            };
        }
        return self::pageLabel($classified['page']);
    }

    private static function sumMinute(string $kind, string $from): int
    {
        try {
            return (int) (Database::fetch(
                'SELECT COALESCE(SUM(hits), 0) AS n FROM stats_minute WHERE kind = ? AND bucket >= ?',
                [$kind, $from]
            )['n'] ?? 0);
        } catch (Throwable) {
            return 0;
        }
    }

    /** @return list<array{t: string, n: int}> */
    private static function minuteSeries(string $from, DateTimeImmutable $now): array
    {
        $map = [];
        try {
            $rows = Database::fetchAll(
                'SELECT bucket, SUM(hits) AS n FROM stats_minute WHERE kind = \'pv\' AND bucket >= ? GROUP BY bucket',
                [$from]
            );
            foreach ($rows as $row) {
                $map[substr((string) $row['bucket'], 0, 16)] = (int) $row['n'];
            }
        } catch (Throwable) {
        }
        $out = [];
        $cursor = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $from, self::tz()) ?: $now->modify('-60 minutes');
        $end = $now->setTime((int) $now->format('H'), (int) $now->format('i'), 0);
        while ($cursor <= $end) {
            $key = $cursor->format('Y-m-d H:i');
            $out[] = [
                't' => $cursor->format('H:i'),
                'n' => $map[$key] ?? 0,
            ];
            $cursor = $cursor->modify('+1 minute');
        }
        return $out;
    }

    /** @return list<array{dim: string, n: int|string}> */
    private static function topMinute(string $kind, string $from, int $limit): array
    {
        try {
            return Database::fetchAll(
                'SELECT dim, SUM(hits) AS n FROM stats_minute
                 WHERE kind = ? AND bucket >= ? AND dim != \'\'
                 GROUP BY dim ORDER BY n DESC LIMIT ' . $limit,
                [$kind, $from]
            );
        } catch (Throwable) {
            return [];
        }
    }

    private static function today(): string
    {
        return self::now()->format('Y-m-d');
    }

    private static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', self::tz());
    }

    private static function tz(): DateTimeZone
    {
        return new DateTimeZone(Env::get('APP_TIMEZONE', 'Europe/Paris') ?: 'Europe/Paris');
    }
}
