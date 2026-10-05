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
    public const LP_MAX_STEPS = 10;

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
        'landing' => 'Landing pub',
        'mdp' => 'Mot de passe',
        'espace' => 'Espace membre',
        'newsletter' => 'Newsletter',
        'outils' => 'Outils',
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
        'salon_proposal' => 'Proposition de salon',
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
            if (self::isOwnEntity($classified['entity'])) {
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
            if ($unique) {
                $utm = [];
                if (session_status() === PHP_SESSION_ACTIVE && is_array($_SESSION['_utm'] ?? null)) {
                    $utm = $_SESSION['_utm'];
                }
                $utmSource = trim((string) ($utm['source'] ?? ''));
                $utmCampaign = trim((string) ($utm['campaign'] ?? ''));
                if ($utmSource !== '') {
                    self::bump('utm_source', $utmSource);
                }
                if ($utmCampaign !== '') {
                    self::bump('utm_campaign', $utmCampaign);
                }
            }
            self::bump('hour', self::now()->format('H'));
            self::bump('device', self::device());
            $ref = self::referrerHost();
            if ($ref !== '') {
                self::bump('ref', $ref);
            } elseif ($unique) {
                self::bump('ref', 'direct');
            }
            self::traceAfterHit($classified);
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
            self::traceStep('action', $name, self::actionLabel($name));
            self::flush();
        } catch (Throwable) {
        }
    }

    public static function landingConversion(string $slug): void
    {
        try {
            $slug = trim(mb_substr($slug, 0, 160));
            if ($slug === '' || !self::shouldCollect()) {
                return;
            }
            self::bump('landing_signup', $slug, '', true);
            self::flush();
        } catch (Throwable) {
        }
    }

    /**
     * @param list<string> $dims
     * @return array<string, int>
     */
    public static function hitsByKind(string $kind, array $dims, string $from, string $to): array
    {
        return self::dimHits($kind, $dims, $from, $to);
    }

    public static function collectBeacon(Request $request): void
    {
        try {
            if (!self::shouldCollect() || !self::sameOrigin()) {
                return;
            }
            if ($request->string('lp') === '1') {
                self::collectLandingClick($request);
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
            'pruned_landing' => $count(
                'DELETE e FROM stats_landing_events e
                 INNER JOIN stats_landing_visits v ON v.id = e.visit
                 WHERE v.started_at < ?',
                [$dailyCut]
            ) + $count('DELETE FROM stats_landing_visits WHERE started_at < ?', [$dailyCut]),
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
        $editorialCurrent = self::articleHitsByOrigin($current['article'], false);
        $editorialPrevious = self::articleHitsByOrigin($previous['article'], false);
        $tribuneCurrent = self::articleHitsByOrigin($current['article'], true);
        $tribunePrevious = self::articleHitsByOrigin($previous['article'], true);
        $tribuneViews = (int) array_sum($tribuneCurrent);
        $previousTribuneViews = (int) array_sum($tribunePrevious);

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
            'articles' => self::rankedEntities('article', $editorialCurrent, $editorialPrevious, $compare),
            'tribune_articles' => self::rankedEntities('article', $tribuneCurrent, $tribunePrevious, $compare),
            'tribune_views' => [
                'n' => $tribuneViews,
                'v' => format_int($tribuneViews),
                'delta' => $compare ? self::delta($tribuneViews, $previousTribuneViews) : null,
            ],
            'metiers' => self::rankedEntities('metier', $current['metier'] ?? [], $previous['metier'] ?? [], $compare),
            'landings' => self::rankedEntities('landing', $current['landing'] ?? [], $previous['landing'] ?? [], $compare),
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

    /**
     * @return array{
     *     period: array<string, mixed>,
     *     compare: bool,
     *     periods: array<string, string>,
     *     kpis: list<array<string, mixed>>,
     *     series: list<array<string, mixed>>,
     *     profile: array<string, mixed>,
     *     services: list<array<string, mixed>>,
     *     tips: list<array<string, string>>,
     *     empty: bool
     * }
     */
    public static function providerDashboard(int $userId, Request $request): array
    {
        $period = self::resolvePeriod($request, '30j');
        $compare = $request->string('compare', '1') !== '0';
        $owned = self::providerEntities($userId);
        $profileSlug = $owned['profile_slug'];
        $services = $owned['services'];
        $serviceSlugs = array_values(array_filter(array_map(
            static fn (array $row): string => (string) ($row['slug'] ?? ''),
            $services
        )));

        $currentProfile = $profileSlug !== ''
            ? self::dimHits('profile', [$profileSlug], $period['from'], $period['to'])
            : [];
        $previousProfile = $compare && $profileSlug !== ''
            ? self::dimHits('profile', [$profileSlug], $period['prev_from'], $period['prev_to'])
            : [];
        $currentServices = $serviceSlugs !== []
            ? self::dimHits('service', $serviceSlugs, $period['from'], $period['to'])
            : [];
        $previousServices = $compare && $serviceSlugs !== []
            ? self::dimHits('service', $serviceSlugs, $period['prev_from'], $period['prev_to'])
            : [];

        $profileN = (int) ($currentProfile[$profileSlug] ?? 0);
        $profileP = (int) ($previousProfile[$profileSlug] ?? 0);
        $serviceN = (int) array_sum($currentServices);
        $serviceP = (int) array_sum($previousServices);
        $seenN = count(array_filter($currentServices, static fn (int $n): bool => $n > 0));
        $seenP = count(array_filter($previousServices, static fn (int $n): bool => $n > 0));

        $published = 0;
        foreach ($services as $service) {
            if (($service['status'] ?? '') === 'published') {
                $published++;
            }
        }

        $kpis = [];
        foreach ([
            ['k' => 'Vitrine', 'n' => $profileN, 'p' => $profileP, 'unit' => 'vue'],
            ['k' => 'Prestations', 'n' => $serviceN, 'p' => $serviceP, 'unit' => 'vue'],
            ['k' => 'Total', 'n' => $profileN + $serviceN, 'p' => $profileP + $serviceP, 'unit' => 'vue'],
            ['k' => 'Fiches vues', 'n' => $seenN, 'p' => $seenP, 'unit' => 'fiche'],
        ] as $item) {
            $kpis[] = [
                'k' => $item['k'],
                'v' => format_int($item['n']),
                'n' => $item['n'],
                'unit' => $item['unit'],
                'delta' => $compare ? self::delta($item['n'], $item['p']) : null,
            ];
        }

        $maxService = max(1, ...array_values($currentServices + [0]));
        $serviceRows = [];
        foreach ($services as $service) {
            $slug = (string) ($service['slug'] ?? '');
            if ($slug === '') {
                continue;
            }
            $hits = (int) ($currentServices[$slug] ?? 0);
            $prev = (int) ($previousServices[$slug] ?? 0);
            $publishedRow = ($service['status'] ?? '') === 'published';
            $serviceRows[] = [
                'id' => (int) ($service['id'] ?? 0),
                'key' => $slug,
                'label' => (string) ($service['title'] ?? $slug),
                'n' => $hits,
                'v' => format_int($hits),
                'pct' => (int) round(100 * $hits / $maxService),
                'delta' => $compare ? self::delta($hits, $prev) : null,
                'href' => $publishedRow ? '/prestations/' . $slug : '/espace/prestations/' . (int) ($service['id'] ?? 0) . '/modifier',
                'edit_href' => '/espace/prestations/' . (int) ($service['id'] ?? 0) . '/modifier',
                'status' => (string) ($service['status'] ?? 'draft'),
                'status_label' => Service::STATUSES[$service['status'] ?? 'draft'] ?? 'Brouillon',
                'published' => $publishedRow,
            ];
        }
        usort($serviceRows, static function (array $a, array $b): int {
            return $b['n'] <=> $a['n'] ?: strcasecmp((string) $a['label'], (string) $b['label']);
        });

        $profileHref = $profileSlug !== '' ? '/prestataires/' . $profileSlug : '';

        return [
            'period' => $period,
            'compare' => $compare,
            'periods' => self::PERIODS,
            'kpis' => $kpis,
            'series' => self::ownedSeries($period, $compare, $profileSlug, $serviceSlugs),
            'profile' => [
                'slug' => $profileSlug,
                'n' => $profileN,
                'v' => format_int($profileN),
                'delta' => $compare ? self::delta($profileN, $profileP) : null,
                'href' => $profileHref,
                'completion' => (int) ($owned['profile']['completion'] ?? 0),
            ],
            'services' => $serviceRows,
            'published_count' => $published,
            'tips' => self::providerTips($owned['profile'], $services, $profileN, $serviceRows),
            'empty' => $profileSlug === '' && $serviceSlugs === [],
        ];
    }

    /**
     * @return array{
     *     days: int,
     *     profile_views: int,
     *     service_views: int,
     *     total: int,
     *     by_service: array<string, int>,
     *     top_service: ?array{title: string, n: int, href: string}
     * }
     */
    public static function providerSummary(int $userId, int $days = 7): array
    {
        $days = max(1, min(366, $days));
        $to = self::now()->setTime(0, 0);
        $from = $to->modify('-' . ($days - 1) . ' days');
        $fromS = $from->format('Y-m-d');
        $toS = $to->format('Y-m-d');
        $owned = self::providerEntities($userId);
        $profileSlug = $owned['profile_slug'];
        $serviceSlugs = [];
        $titles = [];
        foreach ($owned['services'] as $service) {
            $slug = (string) ($service['slug'] ?? '');
            if ($slug === '') {
                continue;
            }
            $serviceSlugs[] = $slug;
            $titles[$slug] = (string) ($service['title'] ?? $slug);
        }
        $profileViews = $profileSlug !== ''
            ? (int) (self::dimHits('profile', [$profileSlug], $fromS, $toS)[$profileSlug] ?? 0)
            : 0;
        $byService = $serviceSlugs !== []
            ? self::dimHits('service', $serviceSlugs, $fromS, $toS)
            : [];
        $top = null;
        $topN = 0;
        foreach ($byService as $slug => $hits) {
            if ($hits > $topN) {
                $topN = $hits;
                $top = [
                    'title' => $titles[$slug] ?? $slug,
                    'n' => $hits,
                    'href' => '/prestations/' . $slug,
                ];
            }
        }

        return [
            'days' => $days,
            'profile_views' => $profileViews,
            'service_views' => (int) array_sum($byService),
            'total' => $profileViews + (int) array_sum($byService),
            'by_service' => $byService,
            'top_service' => $top,
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

    public static function periodQuery(array $keep, array $override = [], string $base = '/admin/statistiques'): string
    {
        $params = array_merge($keep, $override);
        foreach ($params as $key => $value) {
            if ($value === null || $value === '' || $value === false) {
                unset($params[$key]);
            }
        }
        return $params === [] ? $base : $base . '?' . http_build_query($params);
    }

    /** @param ?array{0: string, 1: string} $entity */
    private static function isOwnEntity(?array $entity): bool
    {
        if ($entity === null) {
            return false;
        }
        $uid = (int) ($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) {
            $cached = $_SESSION['_user_cache'] ?? null;
            if (is_array($cached)) {
                $uid = (int) ($cached['id'] ?? 0);
            }
        }
        if ($uid <= 0) {
            return false;
        }
        [$kind, $slug] = $entity;
        if ($slug === '') {
            return false;
        }
        try {
            if ($kind === 'profile') {
                return Database::fetch(
                    'SELECT 1 FROM profiles WHERE user_id = ? AND slug = ?',
                    [$uid, $slug]
                ) !== null;
            }
            if ($kind === 'service') {
                return Database::fetch(
                    'SELECT 1 FROM services WHERE user_id = ? AND slug = ?',
                    [$uid, $slug]
                ) !== null;
            }
        } catch (Throwable) {
            return false;
        }
        return false;
    }

    /**
     * @return array{profile_slug: string, profile: ?array<string, mixed>, services: list<array<string, mixed>>}
     */
    private static function providerEntities(int $userId): array
    {
        $profile = null;
        $profileSlug = '';
        try {
            $profile = Profile::findByUser($userId);
            $profileSlug = trim((string) ($profile['slug'] ?? ''));
        } catch (Throwable) {
        }
        $services = [];
        try {
            $services = Database::fetchAll(
                'SELECT id, slug, title, status FROM services WHERE user_id = ? ORDER BY created_at DESC',
                [$userId]
            );
        } catch (Throwable) {
        }

        return [
            'profile_slug' => $profileSlug,
            'profile' => $profile,
            'services' => $services,
        ];
    }

    /**
     * @param list<string> $dims
     * @return array<string, int>
     */
    private static function dimHits(string $kind, array $dims, string $from, string $to): array
    {
        $dims = array_values(array_unique(array_filter($dims, static fn (string $d): bool => $d !== '')));
        if ($dims === []) {
            return [];
        }
        $out = array_fill_keys($dims, 0);
        try {
            $placeholders = implode(',', array_fill(0, count($dims), '?'));
            $rows = Database::fetchAll(
                "SELECT dim, SUM(hits) AS hits
                 FROM stats_daily
                 WHERE day BETWEEN ? AND ? AND kind = ? AND dim IN ({$placeholders})
                 GROUP BY dim",
                array_merge([$from, $to, $kind], $dims)
            );
            foreach ($rows as $row) {
                $out[(string) $row['dim']] = (int) $row['hits'];
            }
        } catch (Throwable) {
        }
        return $out;
    }

    /**
     * @param list<string> $serviceSlugs
     * @return array<string, int>
     */
    private static function ownedHitsByDay(string $from, string $to, string $profileSlug, array $serviceSlugs): array
    {
        [$sql, $params] = self::ownedEntitySql($profileSlug, $serviceSlugs);
        if ($sql === '') {
            return [];
        }
        $out = [];
        try {
            $rows = Database::fetchAll(
                "SELECT day, SUM(hits) AS hits FROM stats_daily
                 WHERE day BETWEEN ? AND ? AND {$sql}
                 GROUP BY day",
                array_merge([$from, $to], $params)
            );
            foreach ($rows as $row) {
                $out[(string) $row['day']] = (int) $row['hits'];
            }
        } catch (Throwable) {
        }
        return $out;
    }

    /**
     * @param list<string> $serviceSlugs
     * @return array{0: string, 1: list<string>}
     */
    private static function ownedEntitySql(string $profileSlug, array $serviceSlugs): array
    {
        $parts = [];
        $params = [];
        if ($profileSlug !== '') {
            $parts[] = '(kind = ? AND dim = ?)';
            $params[] = 'profile';
            $params[] = $profileSlug;
        }
        $serviceSlugs = array_values(array_unique(array_filter($serviceSlugs, static fn (string $s): bool => $s !== '')));
        if ($serviceSlugs !== []) {
            $placeholders = implode(',', array_fill(0, count($serviceSlugs), '?'));
            $parts[] = '(kind = ? AND dim IN (' . $placeholders . '))';
            $params[] = 'service';
            array_push($params, ...$serviceSlugs);
        }
        if ($parts === []) {
            return ['', []];
        }
        return ['(' . implode(' OR ', $parts) . ')', $params];
    }

    /**
     * @param array<string, mixed> $period
     * @param list<string> $serviceSlugs
     * @return list<array{label: string, current: int, previous: int, uniques: int, day?: string}>
     */
    private static function ownedSeries(array $period, bool $compare, string $profileSlug, array $serviceSlugs): array
    {
        $from = (string) $period['from'];
        $to = (string) $period['to'];
        $days = (int) $period['days'];
        $weekly = $days > 90;
        $current = self::ownedHitsByDay($from, $to, $profileSlug, $serviceSlugs);
        $previous = $compare
            ? self::ownedHitsByDay((string) $period['prev_from'], (string) $period['prev_to'], $profileSlug, $serviceSlugs)
            : [];

        $start = self::parseDay($from) ?? self::now();
        $end = self::parseDay($to) ?? $start;
        $cursor = $start;
        $out = [];
        $i = 0;
        while ($cursor <= $end) {
            $key = $cursor->format('Y-m-d');
            $prevKey = (self::parseDay((string) $period['prev_from']) ?? $cursor)
                ->modify('+' . $i . ' days')
                ->format('Y-m-d');
            $out[] = [
                'label' => $weekly ? $cursor->format('W') : ((int) $cursor->format('j') . '/' . $cursor->format('n')),
                'current' => (int) ($current[$key] ?? 0),
                'previous' => (int) ($previous[$prevKey] ?? 0),
                'uniques' => 0,
                'day' => $key,
            ];
            $cursor = $cursor->modify('+1 day');
            $i++;
        }

        if ($weekly) {
            $grouped = [];
            foreach ($out as $row) {
                $week = 'S' . (self::parseDay((string) $row['day'])?->format('W') ?? '');
                if (!isset($grouped[$week])) {
                    $grouped[$week] = [
                        'label' => $week,
                        'current' => 0,
                        'previous' => 0,
                        'uniques' => 0,
                    ];
                }
                $grouped[$week]['current'] += $row['current'];
                $grouped[$week]['previous'] += $row['previous'];
            }
            return array_values($grouped);
        }

        return $out;
    }

    /**
     * @param ?array<string, mixed> $profile
     * @param list<array<string, mixed>> $services
     * @param list<array<string, mixed>> $serviceRows
     * @return list<array{title: string, body: string, href: string, cta: string}>
     */
    private static function providerTips(?array $profile, array $services, int $profileViews, array $serviceRows): array
    {
        $tips = [];
        $completion = (int) ($profile['completion'] ?? 0);
        $published = array_values(array_filter(
            $services,
            static fn (array $s): bool => ($s['status'] ?? '') === 'published'
        ));
        $publishedHits = array_values(array_filter(
            $serviceRows,
            static fn (array $row): bool => !empty($row['published'])
        ));
        $serviceViews = 0;
        foreach ($publishedHits as $row) {
            $serviceViews += (int) ($row['n'] ?? 0);
        }

        if ($completion < 80) {
            $tips[] = [
                'title' => 'Compléter la vitrine',
                'body' => 'Profil à ' . $completion . ' %. Titre, présentation, métiers et tarif aident l’annuaire à vous trouver.',
                'href' => '/espace/vitrine',
                'cta' => 'Éditer la vitrine',
            ];
        }
        if ($published === []) {
            $tips[] = [
                'title' => 'Publier une prestation',
                'body' => 'Une offre à prix et délai affichés apparaît dans le catalogue, pas seulement sur votre fiche.',
                'href' => '/espace/prestations/creer',
                'cta' => 'Composer une offre',
            ];
        } elseif ($serviceViews === 0) {
            $tips[] = [
                'title' => 'Rendre les fiches plus claires',
                'body' => 'Un titre concret, un métier exact et un prix visible se trouvent plus facilement dans l’annuaire.',
                'href' => '/espace/prestations',
                'cta' => 'Revoir les prestations',
            ];
        }
        if ($profileViews > 0 && $serviceViews === 0 && $published !== []) {
            $tips[] = [
                'title' => 'Les visiteurs s’arrêtent à la vitrine',
                'body' => 'Ils ouvrent votre fiche sans cliquer une prestation. Un extrait net et un prix affiché aident à passer le cap.',
                'href' => '/espace/prestations',
                'cta' => 'Ajuster les offres',
            ];
        }
        $best = $publishedHits[0] ?? null;
        $second = $publishedHits[1] ?? null;
        if (is_array($best) && (int) ($best['n'] ?? 0) > 0 && is_array($second)
            && (int) ($best['n'] ?? 0) >= 3 * max(1, (int) ($second['n'] ?? 0))) {
            $tips[] = [
                'title' => 'Une prestation attire nettement plus',
                'body' => '« ' . (string) $best['label'] . ' » concentre les vues. Alignez les autres titres sur ce que les visiteurs cherchent déjà.',
                'href' => (string) ($best['edit_href'] ?? '/espace/prestations'),
                'cta' => 'Ouvrir cette fiche',
            ];
        }
        $slug = trim((string) ($profile['slug'] ?? ''));
        if ($slug !== '' && count($tips) < 3) {
            $tips[] = [
                'title' => 'Partager le lien public',
                'body' => 'Lien, visuels, QR code, signature et badge sont prêts dans votre vitrine. Les vues apparaissent ici.',
                'href' => '/espace/vitrine?onglet=partage',
                'cta' => 'Ouvrir le kit',
            ];
        }

        return array_slice($tips, 0, 4);
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
        if (\Adl\Core\Auth::isImpersonating()) {
            return false;
        }
        return true;
    }

    private static function sameOrigin(): bool
    {
        $appHost = parse_url((string) Env::get('APP_URL', ''), PHP_URL_HOST);
        if (!is_string($appHost) || $appHost === '') {
            return false;
        }
        foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $header) {
            $raw = (string) ($_SERVER[$header] ?? '');
            if ($raw === '') {
                continue;
            }
            $host = parse_url($raw, PHP_URL_HOST);
            return is_string($host) && strcasecmp($host, $appHost) === 0;
        }
        return false;
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
        if ($head === 'besoin' && $slug === '') {
            return ['page' => 'landing', 'path' => '/besoin', 'entity' => null];
        }
        if ($head === 'besoin' && $slug !== '' && $tail === '') {
            return ['page' => 'landing', 'path' => '/besoin/' . $slug, 'entity' => ['landing', $slug]];
        }
        if ($head === 'outils') {
            return [
                'page' => 'outils',
                'path' => $slug === '' ? '/outils' : '/outils/' . $slug,
                'entity' => null,
            ];
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
    private static function resolvePeriod(Request $request, string $default = '7j'): array
    {
        $tz = self::tz();
        $today = new DateTimeImmutable('today', $tz);
        $id = $request->string('periode', $default);
        if (!isset(self::PERIODS[$id])) {
            $id = isset(self::PERIODS[$default]) ? $default : '7j';
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
            'landing' => [],
            'landing_signup' => [],
            'utm_source' => [],
            'utm_campaign' => [],
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
            'landing' => '/besoin/',
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
     * Sépare les articles éditoriaux des tribunes membres.
     *
     * @param array<string, int> $hits
     * @return array<string, int>
     */
    private static function articleHitsByOrigin(array $hits, bool $tribunes): array
    {
        $slugs = array_keys($hits);
        if ($slugs === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($slugs), '?'));
        try {
            $rows = Database::fetchAll(
                "SELECT slug FROM articles
                 WHERE slug IN ({$placeholders})
                   AND author_id IS " . ($tribunes ? 'NOT NULL' : 'NULL'),
                $slugs
            );
        } catch (Throwable) {
            return $tribunes ? [] : $hits;
        }
        $allowed = array_fill_keys(array_column($rows, 'slug'), true);
        return array_intersect_key($hits, $allowed);
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
            } elseif ($kind === 'landing') {
                foreach ($slugs as $slug) {
                    $landing = \Adl\Data\Landings::find((string) $slug);
                    $out[(string) $slug] = $landing
                        ? (string) ($landing['need'] ?? $landing['h1'] ?? $slug)
                        : (string) $slug;
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

    /** @return array<string, mixed> */
    public static function landingJourneyReport(string $slug, Request $request): array
    {
        $slug = trim($slug);
        $period = self::resolveLandingPeriod($request);
        $compare = $request->string('compare', '1') !== '0';
        $tranche = self::landingTranche($request, $period);
        $page = max(1, (int) ($request->int('p', 1) ?? 1));
        $base = '/admin/landings/' . $slug . '/statistiques';
        $hourSlice = $tranche !== null || ($period['grain'] ?? '') === 'minute';
        [$fromAt, $toAt] = self::landingBounds($period, $tranche, false);
        [$prevFromAt, $prevToAt] = self::landingBounds($period, $tranche, true);

        $ready = true;
        $current = ['arrivals' => 0, 'interacted' => 0, 'steps' => 0];
        $previous = $current;
        $series = [];
        $interactions = [];
        $nextPages = [];
        $nextActions = [];
        $firstSteps = [];
        $paths = [];
        $devices = [];
        $sources = [];
        $journeyPack = ['rows' => [], 'total' => 0, 'pages' => 1, 'page' => 1, 'per_page' => 20];
        try {
            $current = self::landingVisitStats($slug, $fromAt, $toAt);
            $previous = $compare ? self::landingVisitStats($slug, $prevFromAt, $prevToAt) : $previous;
            $series = self::landingSeries($slug, $period, $compare, $tranche, $base);
        } catch (Throwable $e) {
            if (self::landingTableMissing($e)) {
                $ready = false;
            }
        }
        if ($ready) {
            $arrivalsNow = (int) $current['arrivals'];
            try {
                $interactions = self::landingBars(self::landingEventRank($slug, $fromAt, $toAt, 'click', 8), $arrivalsNow);
                $nextPages = self::landingBars(self::landingEventRank($slug, $fromAt, $toAt, 'page', 8), $arrivalsNow);
                $nextActions = self::landingBars(self::landingEventRank($slug, $fromAt, $toAt, 'action', 8), $arrivalsNow);
                $firstSteps = self::landingBars(self::landingEventRank($slug, $fromAt, $toAt, '', 8, true), $arrivalsNow);
                $paths = self::landingBars(self::landingPaths($slug, $fromAt, $toAt), $arrivalsNow);
                $devices = self::landingBars(self::landingFacet($slug, $fromAt, $toAt, 'device'), $arrivalsNow);
                $sources = self::landingBars(self::landingFacet($slug, $fromAt, $toAt, 'source'), $arrivalsNow);
            } catch (Throwable $e) {
                if (self::landingTableMissing($e)) {
                    $ready = false;
                }
            }
            try {
                $journeyPack = self::landingJourneys($slug, $fromAt, $toAt, $page);
            } catch (Throwable $e) {
                if (self::landingTableMissing($e)) {
                    $ready = false;
                }
            }
        }

        $views = 0;
        $viewsPrev = 0;
        $signups = 0;
        $signupsPrev = 0;
        if (!$hourSlice) {
            try {
                $views = (int) (self::hitsByKind('landing', [$slug], (string) $period['from'], (string) $period['to'])[$slug] ?? 0);
                $viewsPrev = $compare
                    ? (int) (self::hitsByKind('landing', [$slug], (string) $period['prev_from'], (string) $period['prev_to'])[$slug] ?? 0)
                    : 0;
                $signups = (int) (self::hitsByKind('landing_signup', [$slug], (string) $period['from'], (string) $period['to'])[$slug] ?? 0);
                $signupsPrev = $compare
                    ? (int) (self::hitsByKind('landing_signup', [$slug], (string) $period['prev_from'], (string) $period['prev_to'])[$slug] ?? 0)
                    : 0;
            } catch (Throwable) {
            }
        } elseif ($ready) {
            try {
                $signups = self::landingSignupEvents($slug, $fromAt, $toAt);
                $signupsPrev = $compare ? self::landingSignupEvents($slug, $prevFromAt, $prevToAt) : 0;
            } catch (Throwable) {
            }
        }

        $arrivals = (int) $current['arrivals'];
        $interacted = (int) $current['interacted'];
        $arrivalsPrev = (int) $previous['arrivals'];
        $interactedPrev = (int) $previous['interacted'];
        $rate = $arrivals > 0 ? (int) round(100 * $interacted / $arrivals) : null;
        $kpis = [];
        if (!$hourSlice) {
            $kpis[] = [
                'k' => 'Pages vues',
                'v' => format_int($views),
                'n' => $views,
                'note' => 'Affichages, rechargements compris.',
                'delta' => $compare ? self::delta($views, $viewsPrev) : null,
            ];
        } else {
            $idle = max(0, $arrivals - $interacted);
            $idlePrev = max(0, $arrivalsPrev - $interactedPrev);
            $idleDelta = $compare ? self::delta($idle, $idlePrev) : null;
            if (is_array($idleDelta) && $idleDelta['tone'] === 'up') {
                $idleDelta['tone'] = 'down';
            } elseif (is_array($idleDelta) && $idleDelta['tone'] === 'down') {
                $idleDelta['tone'] = 'up';
            }
            $kpis[] = [
                'k' => 'Sans suite',
                'v' => format_int($idle),
                'n' => $idle,
                'note' => 'Arrivées sans clic, page ni action.',
                'delta' => $idleDelta,
            ];
        }
        $kpis[] = [
            'k' => 'Arrivées',
            'v' => format_int($arrivals),
            'n' => $arrivals,
            'note' => 'Une visite suivie. Un rechargement ne compte pas deux fois.',
            'delta' => $compare ? self::delta($arrivals, $arrivalsPrev) : null,
        ];
        $kpis[] = [
            'k' => 'Avec interaction',
            'v' => format_int($interacted),
            'n' => $interacted,
            'note' => $rate === null ? 'Aucune arrivée sur cette période.' : ($rate . ' % des arrivées'),
            'delta' => $compare ? self::delta($interacted, $interactedPrev) : null,
        ];
        $kpis[] = [
            'k' => 'Inscriptions',
            'v' => format_int($signups),
            'n' => $signups,
            'note' => $hourSlice ? 'Inscriptions datées dans cette tranche.' : 'Comptes créés après cette landing.',
            'delta' => $compare ? self::delta($signups, $signupsPrev) : null,
        ];

        $pageNow = (int) $journeyPack['page'];
        $pages = (int) $journeyPack['pages'];
        $total = (int) $journeyPack['total'];
        $fromRow = $total === 0 ? 0 : (($pageNow - 1) * (int) $journeyPack['per_page']) + 1;
        $toRow = $total === 0 ? 0 : $fromRow + count($journeyPack['rows']) - 1;

        return [
            'ready' => $ready,
            'period' => $period,
            'compare' => $compare,
            'periods' => self::landingPeriodChips($period, $compare, $base),
            'tranche' => $tranche,
            'base' => $base,
            'day_href' => self::landingPageHref($period, $compare, null, 1, $base),
            'grain' => (string) ($period['grain'] ?? 'day'),
            'grain_label' => self::landingGrainLabel((string) ($period['grain'] ?? 'day')),
            'zoom_hint' => self::landingZoomHint((string) ($period['grain'] ?? 'day')),
            'filter_label' => $tranche === null ? '' : ($tranche . ' h – ' . ($tranche + 1) . ' h'),
            'kpis' => $kpis,
            'series' => $series,
            'interactions' => $interactions,
            'pages_next' => $nextPages,
            'actions_next' => $nextActions,
            'first_steps' => $firstSteps,
            'paths' => $paths,
            'devices' => $devices,
            'sources' => $sources,
            'journeys' => $journeyPack['rows'],
            'pager' => [
                'page' => $pageNow,
                'pages' => $pages,
                'total' => $total,
                'from' => $fromRow,
                'to' => $toRow,
                'prev' => $pageNow > 1 ? self::landingPageHref($period, $compare, $tranche, $pageNow - 1, $base) : '',
                'next' => $pageNow < $pages ? self::landingPageHref($period, $compare, $tranche, $pageNow + 1, $base) : '',
            ],
            'views_n' => $views,
            'arrivals_n' => $arrivals,
        ];
    }

    private static function collectLandingClick(Request $request): void
    {
        $trace = self::landingTrace();
        if ($trace === null) {
            return;
        }
        $page = '/besoin/' . (string) $trace['slug'];
        $here = self::sanitizeClickTarget($request->string('p'));
        if ($here !== '' && $here !== $page) {
            return;
        }
        if (!self::touchLive(self::visitorId(), $page)) {
            return;
        }
        self::traceClick($request->string('t'), $request->string('l'));
    }

    /** @param array{page: string, path: string, entity: ?array{0: string, 1: string}} $classified */
    private static function traceAfterHit(array $classified): void
    {
        try {
            $entity = $classified['entity'] ?? null;
            if (is_array($entity) && ($entity[0] ?? '') === 'landing') {
                $slug = (string) ($entity[1] ?? '');
                if ($slug !== '' && \Adl\Data\Landings::find($slug) !== null) {
                    self::openLandingVisit($slug);
                    return;
                }
            }
            if (self::landingTrace() === null) {
                return;
            }
            if (($classified['page'] ?? '') === 'espace') {
                $path = self::anonymousSpacePath(self::requestPath());
                self::traceStep('page', $path, self::spaceLabel($path));
                return;
            }
            $path = (string) ($classified['path'] ?? '');
            if ($path === '' || str_starts_with($path, '/erreur/')) {
                return;
            }
            $trace = self::landingTrace();
            if ($trace !== null && $path === '/besoin/' . (string) $trace['slug']) {
                return;
            }
            self::traceStep('page', $path, self::pathLabel($path));
        } catch (Throwable) {
        }
    }

    private static function openLandingVisit(string $slug): void
    {
        try {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                return;
            }
            $slug = mb_substr(trim($slug), 0, 80);
            if ($slug === '') {
                return;
            }
            $current = self::landingTrace();
            if ($current !== null && (string) $current['slug'] === $slug) {
                return;
            }
            if ($current !== null) {
                self::traceStep('page', '/besoin/' . $slug, self::pathLabel('/besoin/' . $slug));
            }
            $now = self::now()->format('Y-m-d H:i:s');
            for ($try = 0; $try < 2; $try++) {
                $id = bin2hex(random_bytes(8));
                try {
                    Database::query(
                        'INSERT INTO stats_landing_visits (id, landing, started_at, device, source, steps, interacted)
                         VALUES (?, ?, ?, ?, ?, 0, 0)',
                        [$id, $slug, $now, self::device(), self::landingSource()]
                    );
                    $_SESSION['_lp_trace'] = [
                        'id' => $id,
                        'slug' => $slug,
                        'n' => 0,
                        'until' => time() + 43200,
                        'last' => '',
                    ];
                    return;
                } catch (Throwable) {
                }
            }
        } catch (Throwable) {
        }
    }

    private static function traceClick(string $target, string $label): void
    {
        try {
            $target = self::sanitizeClickTarget($target);
            $label = self::scrubLabel($label);
            if ($target === '#video' && $label === '') {
                $label = 'Vidéo de présentation';
            }
            if ($label === '' && str_starts_with($target, '/')) {
                $label = self::scrubLabel(self::pathLabel($target));
            }
            if ($label === '' && str_starts_with($target, '#') && $target !== '#externe') {
                $label = 'Section ' . substr($target, 1);
            }
            if ($label === '') {
                return;
            }
            self::traceStep('click', $target !== '' ? $target : '#page', $label);
        } catch (Throwable) {
        }
    }

    private static function traceStep(string $kind, string $target, string $label): void
    {
        try {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                return;
            }
            $trace = self::landingTrace();
            if ($trace === null) {
                return;
            }
            $n = (int) ($trace['n'] ?? 0);
            if ($n >= self::LP_MAX_STEPS) {
                return;
            }
            if (!in_array($kind, ['page', 'action', 'click'], true)) {
                return;
            }
            $target = mb_substr(trim($target), 0, 160);
            $label = self::scrubLabel($label);
            if ($label === '') {
                return;
            }
            $last = (string) ($trace['last'] ?? '');
            if (self::landingSameNavigation($kind, $target, $last)) {
                return;
            }
            $sig = $kind . '|' . $target . '|' . $label;
            if ($last === $sig) {
                return;
            }
            $seq = $n + 1;
            $id = (string) $trace['id'];
            Database::query(
                'INSERT INTO stats_landing_events (visit, seq, at, kind, label, target) VALUES (?, ?, ?, ?, ?, ?)',
                [$id, $seq, self::now()->format('Y-m-d H:i:s'), $kind, $label, $target]
            );
            $updated = Database::query(
                'UPDATE stats_landing_visits SET steps = ?, interacted = 1 WHERE id = ?',
                [$seq, $id]
            );
            if ($updated->rowCount() === 0) {
                Database::query('DELETE FROM stats_landing_events WHERE visit = ? AND seq = ?', [$id, $seq]);
                unset($_SESSION['_lp_trace']);
                return;
            }
            $_SESSION['_lp_trace']['n'] = $seq;
            $_SESSION['_lp_trace']['last'] = $sig;
        } catch (Throwable) {
        }
    }

    private static function landingSameNavigation(string $kind, string $target, string $last): bool
    {
        if (!str_starts_with($target, '/')) {
            return false;
        }
        $click = 'click|' . $target . '|';
        $page = 'page|' . $target . '|';
        if ($kind === 'page' && str_starts_with($last, $click)) {
            return true;
        }
        return $kind === 'click' && str_starts_with($last, $page);
    }

    private static function landingTableMissing(Throwable $e): bool
    {
        if ($e->getCode() === '42S02') {
            return true;
        }
        $message = $e->getMessage();
        return str_contains($message, 'stats_landing_') && str_contains($message, 'exist');
    }

    /** @return array{id: string, slug: string, n: int, until: int, last: string}|null */
    private static function landingTrace(): ?array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }
        $row = $_SESSION['_lp_trace'] ?? null;
        if (!is_array($row)) {
            return null;
        }
        $id = (string) ($row['id'] ?? '');
        $slug = (string) ($row['slug'] ?? '');
        $until = (int) ($row['until'] ?? 0);
        if (!preg_match('/^[a-f0-9]{16}$/', $id) || $slug === '' || $until < time()) {
            unset($_SESSION['_lp_trace']);
            return null;
        }
        return [
            'id' => $id,
            'slug' => $slug,
            'n' => (int) ($row['n'] ?? 0),
            'until' => $until,
            'last' => (string) ($row['last'] ?? ''),
        ];
    }

    private static function landingSource(): string
    {
        $source = '';
        if (session_status() === PHP_SESSION_ACTIVE && is_array($_SESSION['_utm'] ?? null)) {
            $source = strtolower(trim((string) ($_SESSION['_utm']['source'] ?? '')));
        }
        $source = preg_replace('/[^a-z0-9._-]/', '', $source) ?? '';
        if ($source !== '') {
            return mb_substr($source, 0, 40);
        }
        $ref = self::referrerHost();
        if ($ref !== '') {
            $ref = preg_replace('/^www\./', '', $ref) ?? $ref;
            return mb_substr($ref, 0, 40);
        }
        return 'direct';
    }

    private static function sanitizeClickTarget(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '#video' || $raw === '#externe') {
            return $raw;
        }
        if (str_starts_with($raw, '#')) {
            $anchor = substr($raw, 1);
            if (preg_match('/^[a-z0-9_-]{1,40}$/i', $anchor)) {
                return '#' . strtolower($anchor);
            }
            return '';
        }
        if (preg_match('#^https?://#i', $raw)) {
            return '#externe';
        }
        if (!str_starts_with($raw, '/')) {
            return '';
        }
        $path = parse_url($raw, PHP_URL_PATH);
        if (!is_string($path) || $path === '' || str_contains($path, '..')) {
            return '';
        }
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }
        return mb_substr($path, 0, 160);
    }

    private static function scrubLabel(string $label): string
    {
        $label = trim(strip_tags($label));
        $label = preg_replace('/\s+/u', ' ', $label) ?? '';
        $label = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu', '', $label) ?? '';
        $label = preg_replace('/\b(?:\+?\d[\d .\-]{7,}\d)\b/u', '', $label) ?? '';
        $label = trim(preg_replace('/\s+/u', ' ', $label) ?? '');
        if ($label === '' || str_contains($label, '@') || preg_match('/https?:\/\//i', $label)) {
            return '';
        }
        return mb_substr($label, 0, 120);
    }

    private static function anonymousSpacePath(string $path): string
    {
        $parts = explode('/', trim($path, '/'));
        $section = strtolower((string) ($parts[1] ?? ''));
        $action = strtolower((string) ($parts[2] ?? ''));
        if (!preg_match('/^[a-z0-9-]{1,40}$/', $section)) {
            return '/espace';
        }
        $kept = '/espace/' . $section;
        if (in_array($action, ['creer', 'nouvelle', 'nouveau', 'modifier', 'apercu'], true)) {
            $kept .= '/' . $action;
        }
        return $kept;
    }

    private static function spaceLabel(string $path): string
    {
        $parts = explode('/', trim($path, '/'));
        $section = (string) ($parts[1] ?? '');
        $action = (string) ($parts[2] ?? '');
        $map = [
            'bienvenue' => 'Bienvenue',
            'tribune' => 'Tribune',
            'publier' => 'Publier une recherche',
            'commande' => 'Commande',
            'suivi' => 'Suivi de mission',
            'commandes' => 'Commandes',
            'missions' => 'Recherches publiées',
            'candidatures' => 'Candidatures',
            'statistiques' => 'Statistiques du compte',
            'prestations' => 'Prestations',
            'messages' => 'Messagerie',
            'notifications' => 'Notifications',
            'favoris' => 'Favoris',
            'forum' => 'Forum',
            'avis' => 'Avis',
            'vitrine' => 'Vitrine',
            'auteur' => 'Page auteur',
            'maison-edition' => 'Maison d’édition',
            'parametres' => 'Paramètres',
            'facturation' => 'Facturation',
        ];
        if ($section === '') {
            return 'Espace membre';
        }
        $base = $map[$section] ?? 'Espace membre';
        $actions = [
            'creer' => 'création',
            'nouvelle' => 'création',
            'nouveau' => 'création',
            'modifier' => 'modification',
            'apercu' => 'aperçu',
        ];
        if (isset($actions[$action])) {
            return $base . ' · ' . $actions[$action];
        }
        return $base;
    }

    /** @return array{0: string, 1: string} */
    private static function landingBounds(array $period, ?int $tranche, bool $previous): array
    {
        if ($tranche !== null && ($period['grain'] ?? '') === 'hour') {
            $day = $previous ? (string) $period['prev_from'] : (string) $period['from'];
            $h = sprintf('%02d', $tranche);
            return [$day . ' ' . $h . ':00:00', $day . ' ' . $h . ':59:59'];
        }
        if ($previous) {
            return [(string) $period['prev_from_at'], (string) $period['prev_to_at']];
        }
        return [(string) $period['from_at'], (string) $period['to_at']];
    }

    /** @return array<string, int> */
    private static function landingVisitStats(string $slug, string $from, string $to): array
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS arrivals,
                    COALESCE(SUM(interacted), 0) AS interacted,
                    COALESCE(SUM(steps), 0) AS steps
             FROM stats_landing_visits
             WHERE landing = ? AND started_at >= ? AND started_at <= ?',
            [$slug, $from, $to]
        ) ?? [];
        return [
            'arrivals' => (int) ($row['arrivals'] ?? 0),
            'interacted' => (int) ($row['interacted'] ?? 0),
            'steps' => (int) ($row['steps'] ?? 0),
        ];
    }

    private static function landingSignupEvents(string $slug, string $from, string $to): int
    {
        return (int) (Database::fetch(
            'SELECT COUNT(DISTINCT e.visit) AS n
             FROM stats_landing_events e
             INNER JOIN stats_landing_visits v ON v.id = e.visit
             WHERE v.landing = ? AND e.at >= ? AND e.at <= ?
               AND e.kind = \'action\' AND e.target = \'inscription\'',
            [$slug, $from, $to]
        )['n'] ?? 0);
    }

    /** @return list<array<string, mixed>> */
    private static function landingSeries(string $slug, array $period, bool $compare, ?int $tranche, string $base): array
    {
        $grain = (string) ($period['grain'] ?? 'day');
        if ($grain === 'minute') {
            return self::landingMinuteSeries($slug, $period, $compare);
        }
        if ($grain === 'hour') {
            return self::landingHourSeries($slug, $period, $compare, $tranche, $base);
        }
        return self::landingCalendarSeries($slug, $period, $compare, $base);
    }

    /** @return list<array<string, mixed>> */
    private static function landingMinuteSeries(string $slug, array $period, bool $compare): array
    {
        $current = self::landingMinuteBuckets($slug, (string) $period['from_at'], (string) $period['to_at']);
        $previous = $compare
            ? self::landingMinuteBuckets($slug, (string) $period['prev_from_at'], (string) $period['prev_to_at'])
            : [];
        $out = [];
        foreach ($current as $i => $bucket) {
            $out[] = [
                'label' => (string) $bucket['label'],
                'current' => (int) $bucket['n'],
                'previous' => (int) ($previous[$i]['n'] ?? 0),
                'href' => '',
                'on' => false,
            ];
        }
        return $out;
    }

    /** @return list<array{label: string, n: int}> */
    private static function landingMinuteBuckets(string $slug, string $fromAt, string $toAt): array
    {
        $tz = self::tz();
        $from = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $fromAt, $tz) ?: self::now()->modify('-59 minutes');
        $to = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $toAt, $tz) ?: self::now();
        $minute = (int) $from->format('i');
        $bucketStart = $from->setTime((int) $from->format('H'), $minute - ($minute % 5), 0);
        $rows = Database::fetchAll(
            'SELECT DATE_FORMAT(started_at, \'%Y-%m-%d %H:%i\') AS t, COUNT(*) AS n
             FROM stats_landing_visits
             WHERE landing = ? AND started_at >= ? AND started_at <= ?
             GROUP BY DATE_FORMAT(started_at, \'%Y-%m-%d %H:%i\')',
            [$slug, $fromAt, $toAt]
        );
        $map = [];
        foreach ($rows as $row) {
            $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i', (string) $row['t'], $tz);
            if (!$dt instanceof DateTimeImmutable) {
                continue;
            }
            $m = (int) $dt->format('i');
            $bucket = $dt->setTime((int) $dt->format('H'), $m - ($m % 5), 0)->format('Y-m-d H:i');
            $map[$bucket] = ($map[$bucket] ?? 0) + (int) $row['n'];
        }
        $out = [];
        $cursor = $bucketStart;
        while ($cursor <= $to) {
            if ($cursor->modify('+5 minutes') <= $from) {
                $cursor = $cursor->modify('+5 minutes');
                continue;
            }
            $out[] = [
                'label' => $cursor->format('H:i'),
                'n' => (int) ($map[$cursor->format('Y-m-d H:i')] ?? 0),
            ];
            $cursor = $cursor->modify('+5 minutes');
        }
        return $out;
    }

    /** @return list<array<string, mixed>> */
    private static function landingHourSeries(string $slug, array $period, bool $compare, ?int $tranche, string $base): array
    {
        $current = self::landingCountsByHour($slug, (string) $period['from_at'], (string) $period['to_at']);
        $previous = $compare
            ? self::landingCountsByHour($slug, (string) $period['prev_from_at'], (string) $period['prev_to_at'])
            : [];
        $out = [];
        for ($h = 0; $h < 24; $h++) {
            $out[] = [
                'label' => $h . ' h',
                'current' => (int) ($current[$h] ?? 0),
                'previous' => (int) ($previous[$h] ?? 0),
                'href' => self::periodQuery([
                    'periode' => (string) $period['id'],
                    'compare' => $compare ? '1' : '0',
                    'jours' => (int) ($period['jours'] ?? 21),
                    'du' => (string) ($period['du'] ?? ''),
                    'au' => (string) ($period['au'] ?? ''),
                    'tranche' => (string) $h,
                ], [], $base),
                'on' => $tranche === $h,
            ];
        }
        return $out;
    }

    /** @return array<int, int> */
    private static function landingCountsByHour(string $slug, string $from, string $to): array
    {
        $rows = Database::fetchAll(
            'SELECT HOUR(started_at) AS h, COUNT(*) AS n
             FROM stats_landing_visits
             WHERE landing = ? AND started_at >= ? AND started_at <= ?
             GROUP BY HOUR(started_at)',
            [$slug, $from, $to]
        );
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['h']] = (int) $row['n'];
        }
        return $map;
    }

    /** @return list<array<string, mixed>> */
    private static function landingCalendarSeries(string $slug, array $period, bool $compare, string $base): array
    {
        $current = self::landingCountsByDay($slug, (string) $period['from_at'], (string) $period['to_at']);
        $previous = $compare
            ? self::landingCountsByDay($slug, (string) $period['prev_from_at'], (string) $period['prev_to_at'])
            : [];
        $start = self::parseDay((string) $period['from']) ?? self::now();
        $end = self::parseDay((string) $period['to']) ?? $start;
        $prevStart = self::parseDay((string) $period['prev_from']) ?? $start;
        $points = [];
        $cursor = $start;
        $i = 0;
        while ($cursor <= $end) {
            $prevKey = $prevStart->modify('+' . $i . ' days')->format('Y-m-d');
            $points[] = [
                'day' => $cursor,
                'current' => (int) ($current[$cursor->format('Y-m-d')] ?? 0),
                'previous' => (int) ($previous[$prevKey] ?? 0),
            ];
            $cursor = $cursor->modify('+1 day');
            $i++;
        }
        $grain = (string) ($period['grain'] ?? 'day');
        if ($grain === 'day') {
            $out = [];
            foreach ($points as $point) {
                /** @var DateTimeImmutable $day */
                $day = $point['day'];
                $iso = $day->format('Y-m-d');
                $out[] = [
                    'label' => (int) $day->format('j') . '/' . $day->format('n'),
                    'current' => $point['current'],
                    'previous' => $point['previous'],
                    'href' => self::periodQuery([
                        'periode' => 'perso',
                        'du' => $iso,
                        'au' => $iso,
                        'compare' => $compare ? '1' : '0',
                    ], [], $base),
                    'on' => false,
                ];
            }
            return $out;
        }

        $groups = [];
        foreach ($points as $point) {
            /** @var DateTimeImmutable $day */
            $day = $point['day'];
            if ($grain === 'month') {
                $key = $day->format('Y-m');
                $label = self::monthAbbr((int) $day->format('n')) . ' ' . $day->format('Y');
            } else {
                $key = $day->format('o') . '-W' . $day->format('W');
                $label = 'S' . $day->format('W');
            }
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'label' => $label,
                    'current' => 0,
                    'previous' => 0,
                    'from' => $day,
                    'to' => $day,
                ];
            }
            $groups[$key]['current'] += $point['current'];
            $groups[$key]['previous'] += $point['previous'];
            $groups[$key]['to'] = $day;
        }
        $out = [];
        foreach ($groups as $group) {
            /** @var DateTimeImmutable $from */
            $from = $group['from'];
            /** @var DateTimeImmutable $to */
            $to = $group['to'];
            $out[] = [
                'label' => (string) $group['label'],
                'current' => (int) $group['current'],
                'previous' => (int) $group['previous'],
                'href' => self::periodQuery([
                    'periode' => 'perso',
                    'du' => $from->format('Y-m-d'),
                    'au' => $to->format('Y-m-d'),
                    'compare' => $compare ? '1' : '0',
                ], [], $base),
                'on' => false,
            ];
        }
        return $out;
    }

    /** @return array<string, int> */
    private static function landingCountsByDay(string $slug, string $from, string $to): array
    {
        $rows = Database::fetchAll(
            'SELECT DATE(started_at) AS d, COUNT(*) AS n
             FROM stats_landing_visits
             WHERE landing = ? AND started_at >= ? AND started_at <= ?
             GROUP BY DATE(started_at)',
            [$slug, $from, $to]
        );
        $map = [];
        foreach ($rows as $row) {
            $map[substr((string) $row['d'], 0, 10)] = (int) $row['n'];
        }
        return $map;
    }

    /** @return list<array<string, mixed>> */
    private static function landingEventRank(string $slug, string $from, string $to, string $kind, int $limit, bool $firstOnly = false): array
    {
        $sql = 'SELECT e.kind, e.label, e.target, COUNT(DISTINCT e.visit) AS n
                FROM stats_landing_visits v
                INNER JOIN stats_landing_events e ON e.visit = v.id
                WHERE v.landing = ? AND v.started_at >= ? AND v.started_at <= ?';
        $params = [$slug, $from, $to];
        if ($kind === 'page') {
            $sql .= " AND (e.kind = 'page' OR (e.kind = 'click' AND e.target LIKE '/%'))";
        } elseif ($kind !== '') {
            $sql .= ' AND e.kind = ?';
            $params[] = $kind;
        }
        if ($firstOnly) {
            $sql .= ' AND e.seq = 1';
        }
        $sql .= ' GROUP BY e.kind, e.label, e.target ORDER BY n DESC LIMIT ' . max(1, min(20, $limit));
        $rows = Database::fetchAll($sql, $params);
        $out = [];
        foreach ($rows as $row) {
            $target = (string) $row['target'];
            $href = (str_starts_with($target, '/') && !str_starts_with($target, '/espace') && !str_starts_with($target, '/admin'))
                ? $target
                : '';
            $label = (string) $row['label'];
            if ($kind === '') {
                $label = self::landingKindLabel((string) $row['kind']) . ' · ' . $label;
            }
            $out[] = [
                'label' => $label,
                'n' => (int) $row['n'],
                'href' => $href,
                'sub' => $href,
            ];
        }
        return $out;
    }

    /** @return list<array<string, mixed>> */
    private static function landingPaths(string $slug, string $from, string $to): array
    {
        try {
            Database::pdo()->exec('SET SESSION group_concat_max_len = 8192');
        } catch (Throwable) {
        }
        $rows = Database::fetchAll(
            'SELECT path, COUNT(*) AS n FROM (
                SELECT GROUP_CONCAT(
                    CONCAT(
                        CASE e.kind WHEN \'click\' THEN \'Clic\' WHEN \'action\' THEN \'Action\' ELSE \'Page\' END,
                        \' · \',
                        LEFT(e.label, 72)
                    )
                    ORDER BY e.seq SEPARATOR \' → \'
                ) AS path
                FROM stats_landing_visits v
                INNER JOIN stats_landing_events e ON e.visit = v.id
                WHERE v.landing = ? AND v.started_at >= ? AND v.started_at <= ?
                GROUP BY e.visit
            ) t
            WHERE path IS NOT NULL AND path != \'\'
            GROUP BY path
            ORDER BY n DESC
            LIMIT 8',
            [$slug, $from, $to]
        );
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'label' => (string) $row['path'],
                'n' => (int) $row['n'],
                'href' => '',
                'sub' => '',
            ];
        }
        return $out;
    }

    /** @return list<array<string, mixed>> */
    private static function landingFacet(string $slug, string $from, string $to, string $column): array
    {
        if (!in_array($column, ['device', 'source'], true)) {
            return [];
        }
        $rows = Database::fetchAll(
            "SELECT {$column} AS dim, COUNT(*) AS n
             FROM stats_landing_visits
             WHERE landing = ? AND started_at >= ? AND started_at <= ?
             GROUP BY {$column}
             ORDER BY n DESC",
            [$slug, $from, $to]
        );
        $merged = [];
        foreach ($rows as $row) {
            $dim = (string) $row['dim'];
            $label = $column === 'device' ? self::landingDeviceLabel($dim) : self::landingSourceLabel($dim);
            $merged[$label] = ($merged[$label] ?? 0) + (int) $row['n'];
        }
        arsort($merged);
        $merged = array_slice($merged, 0, 6, true);
        $out = [];
        foreach ($merged as $label => $n) {
            $out[] = [
                'label' => (string) $label,
                'n' => $n,
                'href' => '',
                'sub' => '',
            ];
        }
        return $out;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private static function landingBars(array $rows, int $arrivals): array
    {
        $max = 1;
        foreach ($rows as $row) {
            $max = max($max, (int) ($row['n'] ?? 0));
        }
        $out = [];
        foreach ($rows as $row) {
            $n = (int) ($row['n'] ?? 0);
            $out[] = [
                'label' => (string) ($row['label'] ?? ''),
                'n' => $n,
                'v' => format_int($n),
                'pct' => (int) round(100 * $n / $max),
                'share' => $arrivals > 0 ? (int) round(100 * $n / $arrivals) : 0,
                'href' => (string) ($row['href'] ?? ''),
                'sub' => (string) ($row['sub'] ?? ''),
            ];
        }
        return $out;
    }

    /** @return array{rows: list<array<string, mixed>>, total: int, pages: int, page: int, per_page: int} */
    private static function landingJourneys(string $slug, string $from, string $to, int $page): array
    {
        $perPage = 20;
        $total = (int) (Database::fetch(
            'SELECT COUNT(*) AS n FROM stats_landing_visits
             WHERE landing = ? AND started_at >= ? AND started_at <= ?',
            [$slug, $from, $to]
        )['n'] ?? 0);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;
        $rows = $total === 0 ? [] : Database::fetchAll(
            'SELECT id, started_at, device, source, steps
             FROM stats_landing_visits
             WHERE landing = ? AND started_at >= ? AND started_at <= ?
             ORDER BY started_at DESC, id DESC
             LIMIT ' . $perPage . ' OFFSET ' . $offset,
            [$slug, $from, $to]
        );
        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (string) $row['id'];
        }
        $events = [];
        if ($ids !== []) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $eventRows = Database::fetchAll(
                "SELECT visit, seq, at, kind, label, target
                 FROM stats_landing_events
                 WHERE visit IN ({$placeholders})
                 ORDER BY seq ASC",
                $ids
            );
            foreach ($eventRows as $event) {
                $events[(string) $event['visit']][] = $event;
            }
        }
        $journeys = [];
        foreach ($rows as $row) {
            $id = (string) $row['id'];
            $steps = [];
            foreach ($events[$id] ?? [] as $event) {
                $target = (string) $event['target'];
                $href = (str_starts_with($target, '/') && !str_starts_with($target, '/espace') && !str_starts_with($target, '/admin'))
                    ? $target
                    : '';
                $at = (string) $event['at'];
                $steps[] = [
                    'kind' => self::landingKindLabel((string) $event['kind']),
                    'label' => (string) $event['label'],
                    'time' => strlen($at) >= 16 ? substr($at, 11, 5) : '',
                    'href' => $href,
                ];
            }
            $n = (int) $row['steps'];
            $journeys[] = [
                'when' => self::landingWhen((string) $row['started_at']),
                'device' => self::landingDeviceLabel((string) $row['device']),
                'source' => self::landingSourceLabel((string) $row['source']),
                'steps_n' => $n,
                'capped' => $n >= self::LP_MAX_STEPS,
                'idle' => $steps === [],
                'events' => $steps,
            ];
        }
        return [
            'rows' => $journeys,
            'total' => $total,
            'pages' => $pages,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    /** @return list<array{id: string, label: string, href: string, on: bool}> */
    private static function landingPeriodChips(array $period, bool $compare, string $base): array
    {
        $labels = [
            'heure' => 'Cette heure',
            'jour' => 'Aujourd’hui',
            'hier' => 'Hier',
            '7j' => '7 jours',
            'semaine' => 'Cette semaine',
            'mois' => 'Ce mois',
            '90j' => '3 mois',
            '12m' => '12 mois',
        ];
        $keep = [
            'compare' => $compare ? '1' : '0',
            'jours' => (int) ($period['jours'] ?? 21),
        ];
        $out = [];
        foreach ($labels as $id => $label) {
            $out[] = [
                'id' => $id,
                'label' => $label,
                'on' => ($period['id'] ?? '') === $id,
                'href' => self::periodQuery($keep, ['periode' => $id], $base),
            ];
        }
        return $out;
    }

    private static function landingPageHref(array $period, bool $compare, ?int $tranche, int $page, string $base): string
    {
        return self::periodQuery([
            'periode' => (string) $period['id'],
            'compare' => $compare ? '1' : '0',
            'jours' => (int) ($period['jours'] ?? 21),
            'du' => (string) ($period['du'] ?? ''),
            'au' => (string) ($period['au'] ?? ''),
            'tranche' => $tranche === null ? null : (string) $tranche,
            'p' => $page > 1 ? (string) $page : null,
        ], [], $base);
    }

    private static function landingTranche(Request $request, array $period): ?int
    {
        if (($period['grain'] ?? '') !== 'hour') {
            return null;
        }
        $raw = $request->string('tranche');
        if ($raw === '' || !preg_match('/^\d{1,2}$/', $raw)) {
            return null;
        }
        $hour = (int) $raw;
        return ($hour >= 0 && $hour <= 23) ? $hour : null;
    }

    /** @return array<string, mixed> */
    private static function resolveLandingPeriod(Request $request): array
    {
        $jours = max(1, min(366, (int) ($request->int('jours', 21) ?? 21)));
        if ($request->string('periode') === 'heure') {
            $now = self::now();
            $from = $now->modify('-59 minutes');
            $prevTo = $from->modify('-1 minute');
            $prevFrom = $prevTo->modify('-59 minutes');
            return [
                'id' => 'heure',
                'label' => 'Cette heure',
                'from' => $from->format('Y-m-d'),
                'to' => $now->format('Y-m-d'),
                'from_at' => $from->format('Y-m-d H:i:00'),
                'to_at' => $now->format('Y-m-d H:i:59'),
                'prev_from' => $prevFrom->format('Y-m-d'),
                'prev_to' => $prevTo->format('Y-m-d'),
                'prev_from_at' => $prevFrom->format('Y-m-d H:i:00'),
                'prev_to_at' => $prevTo->format('Y-m-d H:i:59'),
                'days' => 1,
                'jours' => $jours,
                'du' => '',
                'au' => '',
                'hourly' => true,
                'grain' => 'minute',
                'range_label' => $from->format('H:i') . ' → ' . $now->format('H:i'),
                'prev_label' => $prevFrom->format('H:i') . ' → ' . $prevTo->format('H:i'),
            ];
        }
        $period = self::resolvePeriod($request, '7j');
        $days = (int) $period['days'];
        $period['from_at'] = $period['from'] . ' 00:00:00';
        $period['to_at'] = $period['to'] . ' 23:59:59';
        $period['prev_from_at'] = $period['prev_from'] . ' 00:00:00';
        $period['prev_to_at'] = $period['prev_to'] . ' 23:59:59';
        $period['grain'] = !empty($period['hourly']) ? 'hour' : ($days > 180 ? 'month' : ($days > 62 ? 'week' : 'day'));
        return $period;
    }

    private static function landingGrainLabel(string $grain): string
    {
        return match ($grain) {
            'minute' => 'Cette heure, par tranches de 5 minutes',
            'hour' => 'Heure par heure',
            'week' => 'Semaine par semaine',
            'month' => 'Mois par mois',
            default => 'Jour par jour',
        };
    }

    private static function landingZoomHint(string $grain): string
    {
        return match ($grain) {
            'hour' => 'Cliquez une heure pour n’afficher que les parcours de cette tranche.',
            'day' => 'Cliquez un jour pour afficher ses 24 heures.',
            'week' => 'Cliquez une semaine pour l’ouvrir au jour le jour.',
            'month' => 'Cliquez un mois pour l’ouvrir au jour le jour.',
            default => '',
        };
    }

    private static function landingKindLabel(string $kind): string
    {
        return match ($kind) {
            'click' => 'Clic',
            'action' => 'Action',
            default => 'Page',
        };
    }

    private static function landingDeviceLabel(string $device): string
    {
        return match ($device) {
            'mobile' => 'Mobile',
            'tablette' => 'Tablette',
            default => 'Ordinateur',
        };
    }

    private static function landingSourceLabel(string $source): string
    {
        return match ($source) {
            '', 'direct' => 'Accès direct',
            'google' => 'Google',
            'meta', 'facebook', 'instagram' => 'Meta',
            'bing' => 'Bing',
            'linkedin' => 'LinkedIn',
            default => $source,
        };
    }

    private static function landingWhen(string $at): string
    {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $at, self::tz());
        if (!$dt instanceof DateTimeImmutable) {
            return $at;
        }
        return (int) $dt->format('j') . ' ' . self::monthAbbr((int) $dt->format('n')) . ' · ' . $dt->format('H:i');
    }

    private static function monthAbbr(int $month): string
    {
        $months = [1 => 'janv.', 2 => 'févr.', 3 => 'mars', 4 => 'avr.', 5 => 'mai', 6 => 'juin', 7 => 'juil.', 8 => 'août', 9 => 'sept.', 10 => 'oct.', 11 => 'nov.', 12 => 'déc.'];
        return $months[$month] ?? '';
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
