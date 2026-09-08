<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;
use PDO;
use RuntimeException;

final class Publisher
{
    public const PER_PAGE = 24;
    public const ADMIN_PER_PAGE = 40;

    /** Coordonnées consultables par compte et par 24 h (anti-aspiration). */
    public const CONTACT_DAILY_LIMIT = 25;
    /** Fiches consultables par visiteur non connecté (par IP) sur la fenêtre ci-dessous. */
    public const VISITOR_VIEW_LIMIT = 120;
    public const VISITOR_VIEW_WINDOW = 600;
    /** Durée pendant laquelle une coordonnée dévoilée reste affichée dans la session. */
    public const REVEAL_TTL = 3600;

    public const SIZES = [
        'grand-groupe' => 'Grand groupe',
        'eti' => 'ETI',
        'pme' => 'PME',
        'micro' => 'Micro-structure',
    ];

    public const TYPOLOGIES = [
        'generaliste' => 'Généraliste',
        'specialise' => 'Spécialisé',
        'mixte' => 'Généraliste avec spécialités',
    ];

    /** Slugs réservés sous /maisons-edition/ qui ne peuvent pas désigner une maison. */
    private const RESERVED_SLUGS = ['pays', 'genre', 'taille', 'recherche', 'nouvelle', 'contact', 'revendiquer', 'ajouter'];

    public const STATUS_PUBLISHED = 'published';
    public const STATUS_HIDDEN = 'hidden';
    /** Fiche proposée par un membre, en attente de validation par l'équipe. */
    public const STATUS_PENDING = 'pending';

    // ------------------------------------------------------------------
    // Normalisation
    // ------------------------------------------------------------------

    /** @return array{country: string, region: string} */
    public static function normalizeCountry(string $raw): array
    {
        $raw = trim($raw);
        $region = '';
        if (preg_match('/^(.*?)\s*\((.*?)\)\s*$/u', $raw, $m) === 1) {
            $raw = trim($m[1]);
            $region = trim($m[2]);
            if (preg_match('/^diffusion/iu', $region) === 1) {
                $region = '';
            }
        }
        $parts = preg_split('#\s*/\s*#u', $raw) ?: [$raw];
        $country = trim((string) ($parts[0] ?? $raw));
        if (count($parts) > 1 && $region === '') {
            $region = 'aussi ' . trim(implode(' / ', array_slice($parts, 1)));
        }
        return ['country' => $country !== '' ? $country : 'Europe', 'region' => $region];
    }

    public static function sizeKey(string $raw): string
    {
        $n = search_norm($raw);
        if (str_contains($n, 'grand groupe') || str_contains($n, 'groupe familial')) {
            return 'grand-groupe';
        }
        if (str_starts_with($n, 'eti') || str_contains($n, 'grand independant')) {
            return 'eti';
        }
        if (str_starts_with($n, 'pme')) {
            return 'pme';
        }
        if (str_contains($n, 'micro')) {
            return 'micro';
        }
        return isset(self::SIZES[$raw]) ? $raw : 'pme';
    }

    public static function typologyKey(string $raw): string
    {
        $n = search_norm($raw);
        $g = str_contains($n, 'generaliste');
        $s = str_contains($n, 'specialis');
        if ($g && $s) {
            return 'mixte';
        }
        if ($s) {
            return 'specialise';
        }
        if ($g) {
            return 'generaliste';
        }
        return isset(self::TYPOLOGIES[$raw]) ? $raw : 'specialise';
    }

    /** @return list<string> */
    public static function splitGenres(string $raw): array
    {
        $parts = preg_split('/\s*[,;·\/]\s*|\s+et\s+(?=[a-zà-ÿ])/u', $raw) ?: [];
        $out = [];
        $seen = [];
        foreach ($parts as $part) {
            $part = trim($part, " \t\n\r\0\x0B.");
            $part = preg_replace('/\s*\(.*?\)\s*/u', ' ', $part) ?? $part;
            $part = trim(preg_replace('/\s+/u', ' ', $part) ?? $part);
            if ($part === '' || mb_strlen($part) < 2) {
                continue;
            }
            $label = mb_strtoupper(mb_substr($part, 0, 1)) . mb_substr($part, 1);
            $key = slugify($label);
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $label;
        }
        return $out;
    }

    public static function foundedYear(string $raw): ?int
    {
        if (preg_match('/\b(1[5-9]\d{2}|20\d{2})\b/', $raw, $m) !== 1) {
            return null;
        }
        $year = (int) $m[1];
        return $year <= (int) date('Y') ? $year : null;
    }

    public static function isUnverifiedText(string $raw): bool
    {
        $n = search_norm(trim($raw));
        return $n === '' || str_starts_with($n, 'non verifie') || str_starts_with($n, 'non precise') || str_starts_with($n, 'non trouve') || $n === 'n/a' || $n === '-';
    }

    /** @return array{email: string, address: string} */
    public static function splitContact(string $raw): array
    {
        $raw = trim($raw);
        if (self::isUnverifiedText($raw)) {
            return ['email' => '', 'address' => ''];
        }
        $email = '';
        if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $raw, $m) === 1) {
            $email = strtolower($m[0]);
        }
        $address = $raw;
        if ($email !== '') {
            $address = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', '', $address) ?? $address;
        }
        $address = trim((string) preg_replace('/^[\s—–\-·|,;\/]+|[\s—–\-·|,;\/]+$/u', '', $address));
        $address = trim((string) preg_replace('/\s+/u', ' ', $address));
        if (mb_strlen($address) < 6) {
            $address = '';
        }
        return ['email' => $email, 'address' => $address];
    }

    public static function normalizeWebsite(string $raw): string
    {
        $raw = trim($raw);
        if (self::isUnverifiedText($raw)) {
            return '';
        }
        $raw = preg_replace('/\s.*$/u', '', $raw) ?? $raw;
        if (!preg_match('#^https?://#i', $raw)) {
            $raw = 'https://' . ltrim($raw, '/');
        }
        $host = parse_url($raw, PHP_URL_HOST);
        if (!is_string($host) || !str_contains($host, '.')) {
            return '';
        }
        return rtrim($raw, '/');
    }

    public static function websiteHost(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host)) {
            return '';
        }
        return (string) preg_replace('/^www\./i', '', $host);
    }

    private static function isIndependent(string $group): bool
    {
        return str_starts_with(search_norm(trim($group)), 'independant');
    }

    /**
     * « Barcelone (Catalogne) » → Barcelone ; « Bari / Rome » → Bari ; exonymes usuels unifiés.
     */
    public static function normalizeCity(string $raw): string
    {
        $city = trim($raw);
        if ($city === '' || self::isUnverifiedText($city)) {
            return '';
        }
        $city = trim((string) preg_replace('/\s*\([^)]*\)/u', '', $city));
        $city = trim((string) preg_split('~\s*[/;,]\s*|\s+(?:et|und|and|&)\s+~u', $city, 2)[0]);
        $aliases = [
            'antwerpen' => 'Anvers', 'kiev' => 'Kyiv', 'zurich' => 'Zürich', 'munchen' => 'Munich',
            'koln' => 'Cologne', 'frankfurt' => 'Francfort-sur-le-Main', 'frankfurt am main' => 'Francfort-sur-le-Main',
            'francfort' => 'Francfort-sur-le-Main', 'wien' => 'Vienne', 'lisboa' => 'Lisbonne', 'milano' => 'Milan',
            'roma' => 'Rome', 'torino' => 'Turin', 'firenze' => 'Florence', 'genf' => 'Genève', 'geneve' => 'Genève',
            'brussel' => 'Bruxelles', 'brussels' => 'Bruxelles', 'london' => 'Londres', 'edinburgh' => 'Édimbourg',
            'warszawa' => 'Varsovie', 'krakow' => 'Cracovie', 'praha' => 'Prague', 'kobenhavn' => 'Copenhague',
            'luxembourg-ville' => 'Luxembourg', 'salzburg' => 'Salzbourg', 'hamburg' => 'Hambourg',
        ];
        $key = strtolower(ascii_fold($city));
        return mb_substr($aliases[$key] ?? $city, 0, 120);
    }

    /**
     * Prépare les colonnes dérivées (slugs, clés, texte de recherche) à partir des champs saisis.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function derive(array $row): array
    {
        $country = self::normalizeCountry((string) ($row['country'] ?? ''));
        $city = self::normalizeCity((string) ($row['city'] ?? ''));
        $genres = is_array($row['genres'] ?? null) ? $row['genres'] : self::splitGenres((string) ($row['genres'] ?? ''));
        $size = (string) ($row['size'] ?? '');
        $typology = (string) ($row['typology'] ?? '');
        $group = trim((string) ($row['parent_group'] ?? $row['group'] ?? ''));
        $founded = trim((string) ($row['founded'] ?? ''));
        $description = trim((string) ($row['description'] ?? ''));
        $genreSlugs = array_map('slugify', $genres);

        $searchText = search_norm(implode(' ', array_filter([
            (string) ($row['name'] ?? ''),
            $country['country'],
            $country['region'],
            $city,
            $group,
            implode(' ', $genres),
            $description,
            $size,
            $typology,
        ])));

        return [
            'country' => $country['country'],
            'country_slug' => slugify($country['country']),
            'region' => $country['region'],
            'city' => $city,
            'city_slug' => $city !== '' ? slugify($city) : '',
            'founded' => self::isUnverifiedText($founded) ? '' : $founded,
            'founded_year' => self::foundedYear($founded),
            'parent_group' => $group,
            'independent' => self::isIndependent($group) ? 1 : 0,
            'size' => trim($size),
            'size_key' => self::sizeKey($size),
            'typology' => trim($typology),
            'typology_key' => self::typologyKey($typology),
            'genres_json' => json_encode(array_values($genres), JSON_UNESCAPED_UNICODE) ?: '[]',
            'genres_search' => $genreSlugs === [] ? '' : '|' . implode('|', $genreSlugs) . '|',
            'description' => $description,
            'name_search' => search_norm((string) ($row['name'] ?? '')),
            'search_text' => $searchText,
        ];
    }

    // ------------------------------------------------------------------
    // Import (migration + CLI)
    // ------------------------------------------------------------------

    /**
     * @param list<array<string, string>> $rows
     * @return array{inserted: int, skipped: int}
     */
    public static function importRows(PDO $pdo, array $rows): array
    {
        $existing = $pdo->query('SELECT slug FROM publishers')->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $taken = array_fill_keys(array_map('strval', $existing), true);
        foreach (self::RESERVED_SLUGS as $reserved) {
            $taken[$reserved] = true;
        }
        $byName = $pdo->query('SELECT name_search, country_slug FROM publishers')->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $known = [];
        foreach ($byName as $r) {
            $known[$r['name_search'] . '|' . $r['country_slug']] = true;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO publishers (slug, name, name_search, country, country_slug, region, city, city_slug, founded, founded_year,
                parent_group, independent, size, size_key, typology, typology_key, genres_json, genres_search, description,
                website, contact_email, contact_address, segments, search_text, status, source, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "published", "import", NOW())'
        );

        $inserted = 0;
        $skipped = 0;
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                $skipped++;
                continue;
            }
            $d = self::derive($row);
            $key = $d['name_search'] . '|' . $d['country_slug'];
            if (isset($known[$key])) {
                $skipped++;
                continue;
            }
            $known[$key] = true;

            $base = slugify($name) ?: 'maison';
            $slug = $base;
            $i = 2;
            while (isset($taken[$slug])) {
                $slug = $base . '-' . ($i === 2 && $d['city_slug'] !== '' ? $d['city_slug'] : (string) $i);
                $i++;
            }
            $taken[$slug] = true;

            $contact = self::splitContact((string) ($row['contact'] ?? ''));
            $stmt->execute([
                $slug,
                $name,
                $d['name_search'],
                $d['country'],
                $d['country_slug'],
                $d['region'],
                $d['city'],
                $d['city_slug'],
                $d['founded'],
                $d['founded_year'],
                $d['parent_group'],
                $d['independent'],
                $d['size'],
                $d['size_key'],
                $d['typology'],
                $d['typology_key'],
                $d['genres_json'],
                $d['genres_search'],
                $d['description'],
                self::normalizeWebsite((string) ($row['website'] ?? '')),
                $contact['email'],
                $contact['address'],
                trim((string) ($row['segments'] ?? '')),
                $d['search_text'],
            ]);
            $inserted++;
        }

        return ['inserted' => $inserted, 'skipped' => $skipped];
    }

    // ------------------------------------------------------------------
    // Lecture
    // ------------------------------------------------------------------

    public static function find(int $id): ?array
    {
        $row = Database::fetch('SELECT * FROM publishers WHERE id = ?', [$id]);
        return $row ? self::hydrate($row) : null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $row = Database::fetch('SELECT * FROM publishers WHERE slug = ?', [$slug]);
        return $row ? self::hydrate($row) : null;
    }

    public static function findForOwner(int $userId): ?array
    {
        $row = Database::fetch('SELECT * FROM publishers WHERE owner_user_id = ? ORDER BY id ASC LIMIT 1', [$userId]);
        return $row ? self::hydrate($row) : null;
    }

    public static function isPublic(array $publisher): bool
    {
        return ($publisher['status'] ?? '') === 'published';
    }

    public static function countPublished(): int
    {
        return (int) (Database::fetch('SELECT COUNT(*) AS n FROM publishers WHERE status = "published"')['n'] ?? 0);
    }

    public static function countClaimed(): int
    {
        return (int) (Database::fetch('SELECT COUNT(*) AS n FROM publishers WHERE owner_user_id IS NOT NULL AND claimed_at IS NOT NULL')['n'] ?? 0);
    }

    public static function countAll(): int
    {
        return (int) (Database::fetch('SELECT COUNT(*) AS n FROM publishers')['n'] ?? 0);
    }

    /**
     * @param array{q?: string, country?: string, city?: string, genre?: string, size?: string, typology?: string, independent?: string} $filters
     * @return array{items: list<array<string, mixed>>, total: int, pages: int, page: int}
     */
    public static function search(array $filters, int $page = 1, int $perPage = self::PER_PAGE): array
    {
        [$where, $params] = self::whereFor($filters);
        $total = (int) (Database::fetch('SELECT COUNT(*) AS n FROM publishers WHERE ' . $where, $params)['n'] ?? 0);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));

        $q = search_norm(trim((string) ($filters['q'] ?? '')));
        $order = 'name ASC';
        $orderParams = [];
        if ($q !== '') {
            $order = 'CASE WHEN name_search = ? THEN 0 WHEN name_search LIKE ? THEN 1 WHEN name_search LIKE ? THEN 2 ELSE 3 END, name ASC';
            $orderParams = [$q, $q . '%', '%' . $q . '%'];
        }

        $rows = Database::fetchAll(
            'SELECT * FROM publishers WHERE ' . $where . ' ORDER BY ' . $order . ' LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage),
            array_merge($params, $orderParams)
        );

        return [
            'items' => array_map([self::class, 'hydrate'], $rows),
            'total' => $total,
            'pages' => $pages,
            'page' => $page,
        ];
    }

    /**
     * @param array<string, string> $filters
     * @return array{0: string, 1: list<mixed>}
     */
    private static function whereFor(array $filters): array
    {
        $where = ['status = "published"'];
        $params = [];

        $q = search_norm(trim((string) ($filters['q'] ?? '')));
        if ($q !== '') {
            $tokens = array_slice(array_filter(preg_split('/\s+/', $q) ?: [], static fn (string $t): bool => mb_strlen($t) >= 2), 0, 6);
            if ($tokens === []) {
                $tokens = [$q];
            }
            foreach ($tokens as $token) {
                $where[] = 'search_text LIKE ?';
                $params[] = '%' . self::escapeLike($token) . '%';
            }
        }
        $country = slugify((string) ($filters['country'] ?? ''));
        if ($country !== '') {
            $where[] = 'country_slug = ?';
            $params[] = $country;
        }
        $city = slugify((string) ($filters['city'] ?? ''));
        if ($city !== '') {
            $where[] = 'city_slug = ?';
            $params[] = $city;
        }
        $genre = slugify((string) ($filters['genre'] ?? ''));
        if ($genre !== '') {
            $where[] = 'genres_search LIKE ?';
            $params[] = '%|' . self::escapeLike($genre) . '|%';
        }
        $size = (string) ($filters['size'] ?? '');
        if (isset(self::SIZES[$size])) {
            $where[] = 'size_key = ?';
            $params[] = $size;
        }
        $typology = (string) ($filters['typology'] ?? '');
        if (isset(self::TYPOLOGIES[$typology])) {
            $where[] = 'typology_key = ?';
            $params[] = $typology;
        }
        if ((string) ($filters['independent'] ?? '') === '1') {
            $where[] = 'independent = 1';
        }

        return [implode(' AND ', $where), $params];
    }

    private static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /** @return list<array{name: string, slug: string, n: int, href: string}> */
    public static function countries(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $rows = Database::fetchAll(
            'SELECT country AS name, country_slug AS slug, COUNT(*) AS n
             FROM publishers WHERE status = "published" AND country_slug != ""
             GROUP BY country_slug, country ORDER BY n DESC, country ASC'
        );
        $cache = [];
        foreach ($rows as $row) {
            $cache[] = [
                'name' => (string) $row['name'],
                'slug' => (string) $row['slug'],
                'n' => (int) $row['n'],
                'href' => '/maisons-edition/pays/' . $row['slug'],
            ];
        }
        return $cache;
    }

    /** @return array{name: string, slug: string, n: int, href: string}|null */
    public static function country(string $slug): ?array
    {
        foreach (self::countries() as $country) {
            if ($country['slug'] === $slug) {
                return $country;
            }
        }
        return null;
    }

    /** @return list<array{name: string, slug: string, n: int, href: string}> */
    public static function citiesForCountry(string $countrySlug, int $limit = 60): array
    {
        $rows = Database::fetchAll(
            'SELECT city AS name, city_slug AS slug, COUNT(*) AS n
             FROM publishers WHERE status = "published" AND country_slug = ? AND city_slug != ""
             GROUP BY city_slug, city ORDER BY n DESC, city ASC LIMIT ' . $limit,
            [$countrySlug]
        );
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'name' => (string) $row['name'],
                'slug' => (string) $row['slug'],
                'n' => (int) $row['n'],
                'href' => '/maisons-edition/pays/' . $countrySlug . '/' . $row['slug'],
            ];
        }
        return $out;
    }

    /** @return array{name: string, slug: string, n: int, href: string}|null */
    public static function city(string $countrySlug, string $citySlug): ?array
    {
        foreach (self::citiesForCountry($countrySlug, 500) as $city) {
            if ($city['slug'] === $citySlug) {
                return $city;
            }
        }
        return null;
    }

    /** @return list<array{name: string, slug: string, n: int, href: string, country_slug: string}> */
    public static function allCities(): array
    {
        $rows = Database::fetchAll(
            'SELECT city, city_slug, country_slug, COUNT(*) AS n
             FROM publishers WHERE status = "published" AND city_slug != "" AND country_slug != ""
             GROUP BY country_slug, city_slug, city ORDER BY country_slug, city'
        );
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'name' => (string) $row['city'],
                'slug' => (string) $row['city_slug'],
                'country_slug' => (string) $row['country_slug'],
                'n' => (int) $row['n'],
                'href' => '/maisons-edition/pays/' . $row['country_slug'] . '/' . $row['city_slug'],
            ];
        }
        return $out;
    }

    /**
     * Genres les plus fréquents (agrégés en PHP : 600 lignes, pas de FULLTEXT nécessaire).
     *
     * @return list<array{v: string, l: string, n: int}>
     */
    public static function genreFacets(int $limit = 18, string $countrySlug = '', string $citySlug = ''): array
    {
        $sql = 'SELECT genres_json FROM publishers WHERE status = "published"';
        $params = [];
        if ($countrySlug !== '') {
            $sql .= ' AND country_slug = ?';
            $params[] = $countrySlug;
            if ($citySlug !== '') {
                $sql .= ' AND city_slug = ?';
                $params[] = $citySlug;
            }
        }
        $counts = [];
        $labels = [];
        foreach (Database::fetchAll($sql, $params) as $row) {
            $genres = json_decode((string) ($row['genres_json'] ?? '[]'), true);
            if (!is_array($genres)) {
                continue;
            }
            foreach ($genres as $genre) {
                $key = slugify((string) $genre);
                if ($key === '') {
                    continue;
                }
                $counts[$key] = ($counts[$key] ?? 0) + 1;
                $labels[$key] ??= (string) $genre;
            }
        }
        arsort($counts);
        $out = [];
        foreach (array_slice($counts, 0, $limit, true) as $key => $n) {
            $out[] = ['v' => (string) $key, 'l' => $labels[$key], 'n' => (int) $n];
        }
        return $out;
    }

    public static function genreLabel(string $slug): string
    {
        $slug = slugify($slug);
        foreach (self::genreFacets(400) as $facet) {
            if ($facet['v'] === $slug) {
                return $facet['l'];
            }
        }
        return ucfirst(str_replace('-', ' ', $slug));
    }

    /** @return list<array{v: string, l: string, n: int}> */
    public static function sizeFacets(string $countrySlug = '', string $citySlug = ''): array
    {
        $sql = 'SELECT size_key, COUNT(*) AS n FROM publishers WHERE status = "published"';
        $params = [];
        if ($countrySlug !== '') {
            $sql .= ' AND country_slug = ?';
            $params[] = $countrySlug;
            if ($citySlug !== '') {
                $sql .= ' AND city_slug = ?';
                $params[] = $citySlug;
            }
        }
        $rows = Database::fetchAll($sql . ' GROUP BY size_key', $params);
        $counts = [];
        foreach ($rows as $row) {
            $counts[(string) $row['size_key']] = (int) $row['n'];
        }
        $out = [];
        foreach (self::SIZES as $key => $label) {
            $out[] = ['v' => $key, 'l' => $label, 'n' => $counts[$key] ?? 0];
        }
        return $out;
    }

    /** @return list<array<string, mixed>> */
    public static function related(array $publisher, int $limit = 6): array
    {
        $params = [(int) $publisher['id'], (string) $publisher['country_slug']];
        $sql = 'SELECT * FROM publishers WHERE status = "published" AND id != ? AND country_slug = ?';
        $genres = $publisher['genres'] ?? [];
        $first = isset($genres[0]) ? slugify((string) $genres[0]) : '';
        $order = 'RAND()';
        if ($first !== '') {
            $order = 'CASE WHEN genres_search LIKE ? THEN 0 ELSE 1 END, RAND()';
            $params[] = '%|' . self::escapeLike($first) . '|%';
        }
        $rows = Database::fetchAll($sql . ' ORDER BY ' . $order . ' LIMIT ' . $limit, $params);
        return array_map([self::class, 'hydrate'], $rows);
    }

    /** @return list<array<string, mixed>> */
    public static function latestClaimed(int $limit = 6): array
    {
        $rows = Database::fetchAll(
            'SELECT * FROM publishers WHERE status = "published" AND owner_user_id IS NOT NULL ORDER BY claimed_at DESC LIMIT ' . $limit
        );
        return array_map([self::class, 'hydrate'], $rows);
    }

    /**
     * Liste admin (toutes les fiches, y compris masquées).
     *
     * @return array{items: list<array<string, mixed>>, total: int, pages: int, page: int}
     */
    public static function adminList(string $q, string $filter, int $page): array
    {
        $where = ['1=1'];
        $params = [];
        $q = search_norm(trim($q));
        if ($q !== '') {
            $where[] = '(search_text LIKE ? OR slug LIKE ? OR contact_email LIKE ?)';
            $like = '%' . self::escapeLike($q) . '%';
            array_push($params, $like, $like, $like);
        }
        if ($filter === 'claimed') {
            $where[] = 'owner_user_id IS NOT NULL';
        } elseif ($filter === 'hidden') {
            $where[] = 'status != "published"';
        } elseif ($filter === 'nocontact') {
            $where[] = 'contact_email = "" AND contact_address = ""';
        }
        $sql = implode(' AND ', $where);
        $total = (int) (Database::fetch('SELECT COUNT(*) AS n FROM publishers WHERE ' . $sql, $params)['n'] ?? 0);
        $pages = max(1, (int) ceil($total / self::ADMIN_PER_PAGE));
        $page = max(1, min($page, $pages));
        $rows = Database::fetchAll(
            'SELECT p.*, u.email AS owner_email, u.first_name AS owner_first_name, u.last_name AS owner_last_name
             FROM publishers p LEFT JOIN users u ON u.id = p.owner_user_id
             WHERE ' . $sql . ' ORDER BY p.name ASC LIMIT ' . self::ADMIN_PER_PAGE . ' OFFSET ' . (($page - 1) * self::ADMIN_PER_PAGE),
            $params
        );
        return [
            'items' => array_map([self::class, 'hydrate'], $rows),
            'total' => $total,
            'pages' => $pages,
            'page' => $page,
        ];
    }

    // ------------------------------------------------------------------
    // Écriture (propriétaire / admin)
    // ------------------------------------------------------------------

    /**
     * @param array<string, mixed> $data
     */
    public static function save(int $id, array $data, bool $admin = false): int
    {
        $name = trim((string) ($data['name'] ?? ''));
        if (mb_strlen($name) < 2) {
            throw new RuntimeException('Le nom de la maison est obligatoire.');
        }
        if (mb_strlen($name) > 190) {
            throw new RuntimeException('Le nom est trop long (190 caractères maximum).');
        }
        $description = trim((string) ($data['description'] ?? ''));
        if (mb_strlen($description) > 1200) {
            throw new RuntimeException('La présentation est limitée à 1 200 caractères.');
        }
        $email = strtolower(trim((string) ($data['contact_email'] ?? '')));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('L\'e-mail de contact n\'est pas valide.');
        }
        $website = self::normalizeWebsite((string) ($data['website'] ?? ''));
        if (trim((string) ($data['website'] ?? '')) !== '' && $website === '') {
            throw new RuntimeException('L\'adresse du site web n\'est pas valide.');
        }
        $sizeKey = (string) ($data['size_key'] ?? 'pme');
        if (!isset(self::SIZES[$sizeKey])) {
            $sizeKey = 'pme';
        }
        $typoKey = (string) ($data['typology_key'] ?? 'specialise');
        if (!isset(self::TYPOLOGIES[$typoKey])) {
            $typoKey = 'specialise';
        }
        $genresRaw = $data['genres'] ?? '';
        $genres = is_array($genresRaw) ? array_values(array_filter(array_map('strval', $genresRaw))) : self::splitGenres((string) $genresRaw);
        $genres = array_slice($genres, 0, 20);

        $d = self::derive([
            'name' => $name,
            'country' => (string) ($data['country'] ?? ''),
            'city' => (string) ($data['city'] ?? ''),
            'founded' => (string) ($data['founded'] ?? ''),
            'parent_group' => (string) ($data['parent_group'] ?? ''),
            'size' => self::SIZES[$sizeKey],
            'typology' => self::TYPOLOGIES[$typoKey],
            'genres' => $genres,
            'description' => $description,
        ]);
        $d['size_key'] = $sizeKey;
        $d['typology_key'] = $typoKey;

        $fields = [
            'name' => $name,
            'name_search' => $d['name_search'],
            'country' => $d['country'],
            'country_slug' => $d['country_slug'],
            'region' => mb_substr((string) ($data['region'] ?? $d['region']), 0, 120),
            'city' => mb_substr($d['city'], 0, 120),
            'city_slug' => $d['city_slug'],
            'founded' => mb_substr($d['founded'], 0, 120),
            'founded_year' => $d['founded_year'],
            'parent_group' => mb_substr($d['parent_group'], 0, 190),
            'independent' => $d['independent'],
            'size' => $d['size'],
            'size_key' => $sizeKey,
            'typology' => $d['typology'],
            'typology_key' => $typoKey,
            'genres_json' => $d['genres_json'],
            'genres_search' => $d['genres_search'],
            'description' => $description,
            'website' => $website,
            'contact_email' => $email,
            'contact_address' => mb_substr(trim((string) ($data['contact_address'] ?? '')), 0, 255),
            'contact_phone' => mb_substr(trim((string) ($data['contact_phone'] ?? '')), 0, 40),
            'submissions_note' => mb_substr(trim((string) ($data['submissions_note'] ?? '')), 0, 600),
            'search_text' => $d['search_text'],
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if (array_key_exists('logo_path', $data)) {
            $fields['logo_path'] = $data['logo_path'] !== null ? (string) $data['logo_path'] : null;
        }
        if ($admin) {
            $status = (string) ($data['status'] ?? 'published');
            $fields['status'] = in_array($status, [self::STATUS_PUBLISHED, self::STATUS_HIDDEN, self::STATUS_PENDING], true) ? $status : 'published';
            if (array_key_exists('segments', $data)) {
                $fields['segments'] = mb_substr(trim((string) $data['segments']), 0, 255);
            }
        }

        if ($id > 0) {
            $set = implode(', ', array_map(static fn (string $k): string => $k . ' = ?', array_keys($fields)));
            Database::query('UPDATE publishers SET ' . $set . ' WHERE id = ?', array_merge(array_values($fields), [$id]));
            return $id;
        }

        $slug = unique_slug($name, static function (string $candidate): bool {
            if (in_array($candidate, self::RESERVED_SLUGS, true)) {
                return true;
            }
            return Database::fetch('SELECT id FROM publishers WHERE slug = ?', [$candidate]) !== null;
        });
        $fields['slug'] = $slug;
        $fields['source'] = 'admin';
        $fields['status'] ??= 'published';
        $fields['created_at'] = date('Y-m-d H:i:s');
        $cols = implode(', ', array_keys($fields));
        $marks = implode(', ', array_fill(0, count($fields), '?'));
        Database::query('INSERT INTO publishers (' . $cols . ') VALUES (' . $marks . ')', array_values($fields));
        return (int) Database::lastId();
    }

    public static function setStatus(int $id, string $status): void
    {
        if (!in_array($status, [self::STATUS_PUBLISHED, self::STATUS_HIDDEN, self::STATUS_PENDING], true)) {
            throw new RuntimeException('Statut inconnu.');
        }
        Database::query('UPDATE publishers SET status = ?, updated_at = NOW() WHERE id = ?', [$status, $id]);
    }

    /**
     * Fiche proposée par un membre : créée masquée (« pending »), rattachée au compte pour qu'il
     * puisse la compléter, publiée seulement après validation par l'équipe.
     *
     * @param array<string, mixed> $data
     */
    public static function createPending(array $data, int $userId): int
    {
        if (trim((string) ($data['country'] ?? '')) === '') {
            throw new RuntimeException('Indiquez le pays du siège de la maison.');
        }
        unset($data['segments']);
        $id = self::save(0, array_merge($data, ['status' => self::STATUS_PENDING]), true);
        Database::query(
            'UPDATE publishers SET source = "member", owner_user_id = ?, claimed_at = NULL WHERE id = ?',
            [$userId, $id]
        );
        return $id;
    }

    /**
     * Maisons déjà recensées dont le nom ressemble à celui proposé (détection de doublons).
     *
     * @return list<array<string, mixed>>
     */
    public static function similar(string $name, string $country = '', int $excludeId = 0, int $limit = 5): array
    {
        $key = self::nameKey($name);
        if ($key === '') {
            return [];
        }
        $words = array_values(array_filter(explode(' ', $key), static fn (string $w): bool => mb_strlen($w) >= 3));
        $needle = $words[0] ?? $key;
        $rows = Database::fetchAll(
            'SELECT * FROM publishers WHERE status != "hidden" AND id != ? AND name_search LIKE ? ORDER BY (status = "published") DESC, name ASC LIMIT 60',
            [$excludeId, '%' . $needle . '%']
        );
        $countrySlug = $country !== '' ? slugify(self::normalizeCountry($country)['country']) : '';
        $scored = [];
        foreach ($rows as $row) {
            $otherKey = self::nameKey((string) $row['name']);
            if ($otherKey === '') {
                continue;
            }
            $score = 0;
            if ($otherKey === $key) {
                $score = 100;
            } elseif (str_contains($otherKey, $key) || str_contains($key, $otherKey)) {
                $score = 80;
            } elseif (levenshtein(substr($key, 0, 60), substr($otherKey, 0, 60)) <= 2) {
                $score = 70;
            } elseif ($words !== [] && count(array_intersect($words, explode(' ', $otherKey))) >= max(1, (int) ceil(count($words) * 0.6))) {
                $score = 50;
            }
            if ($score === 0) {
                continue;
            }
            if ($countrySlug !== '' && (string) $row['country_slug'] === $countrySlug) {
                $score += 10;
            }
            $scored[] = [$score, self::hydrate($row)];
        }
        usort($scored, static fn (array $a, array $b): int => $b[0] <=> $a[0]);
        return array_map(static fn (array $s): array => $s[1], array_slice($scored, 0, $limit));
    }

    /** Nom normalisé sans les mots génériques (« éditions », « les », « publishing »…). */
    private static function nameKey(string $name): string
    {
        $key = (string) preg_replace('/[^a-z0-9]+/', ' ', search_norm($name));
        $key = (string) preg_replace('/\b(les|le|la|l|editions?|edition|ed|editeurs?|editeur|publishing|publishers?|verlag|editorial|editora|editore|edizioni|ediciones|uitgeverij|press|presses|books|maison|d|de|du|des|et|and|the|of)\b/u', ' ', $key);
        return trim((string) preg_replace('/\s+/', ' ', $key));
    }

    public static function assignOwner(int $id, ?int $userId): void
    {
        if ($userId === null) {
            Database::query('UPDATE publishers SET owner_user_id = NULL, claimed_at = NULL, updated_at = NOW() WHERE id = ?', [$id]);
            return;
        }
        Database::query(
            'UPDATE publishers SET owner_user_id = ?, claimed_at = NOW(), updated_at = NOW() WHERE id = ?',
            [$userId, $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM publisher_claims WHERE publisher_id = ?', [$id]);
        Database::query('DELETE FROM publisher_contact_views WHERE publisher_id = ?', [$id]);
        Database::query('DELETE FROM publishers WHERE id = ?', [$id]);
    }

    // ------------------------------------------------------------------
    // Coordonnées : accès réservé aux comptes, plafonné
    // ------------------------------------------------------------------

    public static function contactViewsToday(int $userId): int
    {
        return (int) (Database::fetch(
            'SELECT COUNT(DISTINCT publisher_id) AS n FROM publisher_contact_views
             WHERE user_id = ? AND created_at > (NOW() - INTERVAL 1 DAY)',
            [$userId]
        )['n'] ?? 0);
    }

    public static function hasViewedContact(int $publisherId, int $userId): bool
    {
        return Database::fetch(
            'SELECT id FROM publisher_contact_views WHERE publisher_id = ? AND user_id = ? AND created_at > (NOW() - INTERVAL 1 DAY) LIMIT 1',
            [$publisherId, $userId]
        ) !== null;
    }

    /**
     * Enregistre la consultation ; lève une exception si le plafond quotidien est atteint.
     */
    public static function revealContact(int $publisherId, int $userId): void
    {
        if (!self::hasViewedContact($publisherId, $userId) && self::contactViewsToday($userId) >= self::CONTACT_DAILY_LIMIT) {
            throw new RuntimeException(
                'Vous avez consulté ' . self::CONTACT_DAILY_LIMIT . ' fiches de contact aujourd\'hui. Revenez demain pour en découvrir d\'autres.'
            );
        }
        Database::query(
            'INSERT INTO publisher_contact_views (publisher_id, user_id, ip_hash, created_at) VALUES (?, ?, ?, NOW())',
            [$publisherId, $userId, self::ipHash()]
        );
        $_SESSION['_pub_reveal'][$publisherId] = time();
    }

    public static function isRevealed(int $publisherId): bool
    {
        $at = $_SESSION['_pub_reveal'][$publisherId] ?? null;
        return is_int($at) && $at > time() - self::REVEAL_TTL;
    }

    public static function hasContact(array $publisher): bool
    {
        return trim((string) ($publisher['contact_email'] ?? '')) !== ''
            || trim((string) ($publisher['contact_address'] ?? '')) !== ''
            || trim((string) ($publisher['website'] ?? '')) !== ''
            || trim((string) ($publisher['contact_phone'] ?? '')) !== '';
    }

    public static function ipHash(): string
    {
        $ip = (string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '');
        return substr(hash('sha256', $ip . '|adl-pub'), 0, 40);
    }

    public static function contactStats(): array
    {
        try {
            $row = Database::fetch(
                'SELECT COUNT(*) AS total, COUNT(DISTINCT user_id) AS users,
                        SUM(created_at > (NOW() - INTERVAL 7 DAY)) AS week
                 FROM publisher_contact_views'
            ) ?? [];
        } catch (\Throwable) {
            $row = [];
        }
        return [
            'total' => (int) ($row['total'] ?? 0),
            'users' => (int) ($row['users'] ?? 0),
            'week' => (int) ($row['week'] ?? 0),
        ];
    }

    // ------------------------------------------------------------------
    // Présentation
    // ------------------------------------------------------------------

    /** @param array<string, mixed> $row */
    public static function hydrate(array $row): array
    {
        $genres = json_decode((string) ($row['genres_json'] ?? '[]'), true);
        $row['genres'] = is_array($genres) ? array_values(array_map('strval', $genres)) : [];
        $row['genre_links'] = [];
        foreach ($row['genres'] as $genre) {
            $row['genre_links'][] = ['label' => $genre, 'href' => '/maisons-edition?genre=' . rawurlencode(slugify($genre))];
        }
        $row['href'] = '/maisons-edition/' . $row['slug'];
        $row['country_href'] = $row['country_slug'] !== '' ? '/maisons-edition/pays/' . $row['country_slug'] : '/maisons-edition';
        $row['city_href'] = ($row['city_slug'] ?? '') !== '' && $row['country_slug'] !== ''
            ? '/maisons-edition/pays/' . $row['country_slug'] . '/' . $row['city_slug']
            : $row['country_href'];
        $row['size_label'] = self::SIZES[$row['size_key'] ?? ''] ?? (string) ($row['size'] ?? '');
        $row['typology_label'] = self::TYPOLOGIES[$row['typology_key'] ?? ''] ?? (string) ($row['typology'] ?? '');
        $row['is_claimed'] = !empty($row['owner_user_id']);
        $row['is_independent'] = (int) ($row['independent'] ?? 0) === 1;
        $row['logo_src'] = !empty($row['logo_path']) ? uploaded((string) $row['logo_path']) : '';
        $row['initials'] = self::initials((string) $row['name']);
        $row['website_host'] = self::websiteHost((string) ($row['website'] ?? ''));
        $row['founded_label'] = self::foundedLabel($row);
        $row['location_label'] = implode(', ', array_filter([(string) ($row['city'] ?? ''), (string) ($row['country'] ?? '')]));
        $row['has_contact'] = self::hasContact($row);
        $row['group_label'] = $row['is_independent']
            ? 'Indépendant'
            : (trim((string) ($row['parent_group'] ?? '')) !== '' ? (string) $row['parent_group'] : '');
        $row['owner_name'] = trim((string) (($row['owner_first_name'] ?? '') . ' ' . ($row['owner_last_name'] ?? '')));
        return $row;
    }

    public static function initials(string $name): string
    {
        $words = preg_split('/[\s\-]+/u', trim($name)) ?: [];
        $words = array_values(array_filter($words, static fn (string $w): bool => $w !== '' && !in_array(mb_strtolower($w), ['les', 'la', 'le', 'de', 'des', 'du', 'd\'', 'editions', 'éditions', 'the', 'and', '&'], true)));
        if ($words === []) {
            $words = [$name];
        }
        $out = '';
        foreach (array_slice($words, 0, 2) as $word) {
            $out .= mb_strtoupper(mb_substr($word, 0, 1));
        }
        return $out !== '' ? $out : 'ME';
    }

    private static function foundedLabel(array $row): string
    {
        $year = (int) ($row['founded_year'] ?? 0);
        $raw = trim((string) ($row['founded'] ?? ''));
        if ($year > 0 && ($raw === '' || $raw === (string) $year)) {
            return (string) $year;
        }
        if ($year > 0) {
            return $raw;
        }
        return $raw !== '' ? $raw : '';
    }

    /**
     * Texte SEO de la fiche (meta description) sans coordonnées.
     */
    public static function metaDescription(array $publisher): string
    {
        $bits = [];
        $bits[] = $publisher['name'] . ' : maison d\'édition ' . ($publisher['typology_key'] === 'generaliste' ? 'généraliste' : 'spécialisée');
        if (($publisher['location_label'] ?? '') !== '') {
            $bits[0] .= ' à ' . $publisher['location_label'];
        }
        if (($publisher['founded_year'] ?? null) !== null) {
            $bits[0] .= ', fondée en ' . $publisher['founded_year'];
        }
        $bits[0] .= '.';
        if ($publisher['genres'] !== []) {
            $bits[] = 'Genres : ' . implode(', ', array_slice($publisher['genres'], 0, 5)) . '.';
        }
        if (trim((string) $publisher['description']) !== '') {
            $bits[] = (string) $publisher['description'];
        }
        return \Adl\Data\Seo::clip(implode(' ', $bits), 160);
    }
}
