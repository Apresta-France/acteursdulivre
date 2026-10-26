<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;
use Adl\Data\Geography;
use Adl\Data\SalonHubs;

final class Salon
{
    public const PER_PAGE = 20;

    /** Slugs réservés sous /salons/ qui ne peuvent pas désigner une fiche. */
    private const RESERVED_SLUGS = ['ajouter', 'pays', 'zone', 'visibilite'];

    public const FALLBACK_CATEGORIES = [
        'Général',
        'Jeunesse',
        'Festival littéraire',
        'Polar',
        'BD',
        'Poésie',
        'Sciences',
        'Professionnel',
        'Régional',
        'Autre',
    ];

    private const MONTHS = [
        1 => 'janv.', 2 => 'févr.', 3 => 'mars', 4 => 'avr.',
        5 => 'mai', 6 => 'juin', 7 => 'juil.', 8 => 'août',
        9 => 'sept.', 10 => 'oct.', 11 => 'nov.', 12 => 'déc.',
    ];

    private const MONTHS_LONG = [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
        5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
        9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
    ];

    public static function tableExists(): bool
    {
        try {
            Database::fetch('SELECT 1 FROM salons LIMIT 1');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function countAll(): int
    {
        return (int) (Database::fetch('SELECT COUNT(*) AS n FROM salons')['n'] ?? 0);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function upcoming(int $limit = 8, string $exceptSlug = ''): array
    {
        $sql = 'SELECT * FROM salons
             WHERE starts_on IS NOT NULL AND starts_on >= CURDATE()';
        $params = [];
        if ($exceptSlug !== '') {
            $sql .= ' AND slug != ?';
            $params[] = $exceptSlug;
        }
        $sql .= ' ORDER BY ' . self::FEATURED_SQL . ' DESC, starts_on ASC, name ASC LIMIT ' . max(1, $limit);
        $rows = Database::fetchAll($sql, $params);
        return array_map([self::class, 'hydrate'], $rows);
    }

    private const FEATURED_SQL = '(featured_until IS NOT NULL AND featured_until >= CURDATE())';

    /**
     * Salons partenaires en cours de mise en avant.
     *
     * @param array<string, string> $filters mêmes filtres que search() (pays, région, zone, catégorie…)
     * @return list<array<string, mixed>>
     */
    public static function featured(int $limit = 4, array $filters = []): array
    {
        [$where, $params] = self::whereFor($filters);
        $rows = Database::fetchAll(
            'SELECT * FROM salons WHERE ' . self::FEATURED_SQL . ' AND ' . $where . '
             ORDER BY (starts_on IS NULL) ASC, starts_on ASC, name ASC
             LIMIT ' . max(1, $limit),
            $params
        );
        return array_map([self::class, 'hydrate'], $rows);
    }

    public static function isFeatured(array $salon): bool
    {
        $until = trim((string) ($salon['featured_until'] ?? ''));
        return $until !== '' && $until >= date('Y-m-d');
    }

    public static function featuredLabel(array $salon): string
    {
        $until = trim((string) ($salon['featured_until'] ?? ''));
        if ($until === '') {
            return '';
        }
        return admin_date($until . ' 00:00:00', $until);
    }

    /**
     * Liens de toutes les manifestations à venir, pour la page agenda indexable.
     *
     * @return list<array{name: string, href: string, city: string}>
     */
    public static function indexLinks(): array
    {
        $rows = Database::fetchAll(
            'SELECT name, slug, city, starts_on FROM salons
             WHERE slug != ""
               AND (starts_on IS NULL OR starts_on >= CURDATE() OR (ends_on IS NOT NULL AND ends_on >= CURDATE()))
             ORDER BY (starts_on IS NULL) ASC, starts_on ASC, name ASC'
        );
        $out = [];
        foreach ($rows as $row) {
            $slug = trim((string) ($row['slug'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));
            if ($slug === '' || $name === '' || in_array($slug, self::RESERVED_SLUGS, true)) {
                continue;
            }
            $year = substr((string) ($row['starts_on'] ?? ''), 0, 4);
            if ($year !== '' && !str_contains($name, $year)) {
                $name .= ' (' . $year . ')';
            }
            $out[] = [
                'name' => $name,
                'href' => '/salons/' . $slug,
                'city' => trim((string) ($row['city'] ?? '')),
            ];
        }

        return $out;
    }

    /** @return list<string> */
    public static function categoryLabels(): array
    {
        $labels = [];
        try {
            foreach (self::facetRows('category') as $row) {
                $labels[] = $row['l'];
            }
        } catch (\Throwable) {
        }
        if ($labels === []) {
            $labels = self::FALLBACK_CATEGORIES;
        } elseif (!in_array('Autre', $labels, true)) {
            $labels[] = 'Autre';
        }
        return $labels;
    }

    /** @return list<string> */
    /** @return array{name: string, slug: string, n: int, href: string}|null */
    public static function country(string $slug): ?array
    {
        $slug = slugify($slug);
        if ($slug === '') {
            return null;
        }
        foreach (self::facets()['countries'] as $row) {
            if ($row['v'] === $slug) {
                return [
                    'name' => $row['l'],
                    'slug' => $row['v'],
                    'n' => $row['n'],
                    'href' => '/salons/pays/' . $row['v'],
                ];
            }
        }
        return null;
    }

    /** @return array{name: string, slug: string, n: int, href: string}|null */
    public static function zone(string $slug): ?array
    {
        $zone = Geography::zone($slug);
        if ($zone === null) {
            return null;
        }
        foreach (self::facets()['zones'] as $row) {
            if ($row['v'] === $zone['slug']) {
                return [
                    'name' => $zone['name'],
                    'slug' => $zone['slug'],
                    'n' => $row['n'],
                    'href' => '/salons/zone/' . $zone['slug'],
                ];
            }
        }
        return null;
    }

    public static function countryLabels(): array
    {
        $labels = [];
        try {
            foreach (self::facetRows('country') as $row) {
                $labels[] = $row['l'];
            }
        } catch (\Throwable) {
        }
        if ($labels === []) {
            $labels = ['France'];
        }
        if (!in_array('France', $labels, true)) {
            array_unshift($labels, 'France');
        }
        return $labels;
    }

    /**
     * Salons au nom proche, pour éviter un doublon à la proposition ou à la validation.
     *
     * @return list<array<string, mixed>>
     */
    public static function similar(string $name, string $city = '', int $limit = 4): array
    {
        $q = self::nameKey($name);
        if (mb_strlen($q) < 3) {
            return [];
        }
        $stop = ['salon', 'festival', 'foire', 'livre', 'livres', 'du', 'de', 'des', 'le', 'la', 'les', 'un', 'une', 'et', 'au', 'aux', 'en', 'sur'];
        $words = array_values(array_filter(
            explode(' ', $q),
            static fn (string $w): bool => mb_strlen($w) >= 3 && !in_array($w, $stop, true)
        ));
        if ($words === []) {
            $words = array_values(array_filter(explode(' ', $q), static fn (string $w): bool => mb_strlen($w) >= 4));
        }
        usort($words, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));
        $needle = $words[0] ?? $q;
        $like = '%' . addcslashes($needle, '%_\\') . '%';
        $rows = Database::fetchAll(
            'SELECT * FROM salons
             WHERE name LIKE ? OR search_text LIKE ?
             ORDER BY (starts_on IS NULL) ASC, starts_on ASC, name ASC
             LIMIT 80',
            [$like, $like]
        );
        $cityKey = self::nameKey($city);
        $scored = [];
        foreach ($rows as $row) {
            $other = self::nameKey((string) ($row['name'] ?? ''));
            if ($other === '') {
                continue;
            }
            $score = 0;
            if ($other === $q) {
                $score = 100;
            } elseif (mb_strlen($q) >= 10 && (str_contains($other, $q) || str_contains($q, $other))) {
                $score = 80;
            } elseif (mb_strlen($other) >= 10 && (str_contains($other, $q) || str_contains($q, $other))) {
                $score = 80;
            } elseif (levenshtein(substr($q, 0, 60), substr($other, 0, 60)) <= 2) {
                $score = 70;
            } elseif ($words !== [] && count(array_intersect($words, explode(' ', $other))) >= max(1, (int) ceil(count($words) * 0.6))) {
                $score = 50;
            }
            if ($score === 0) {
                continue;
            }
            if ($cityKey !== '' && self::nameKey((string) ($row['city'] ?? '')) === $cityKey) {
                $score += 15;
            }
            $scored[] = [$score, self::hydrate($row)];
        }
        usort($scored, static fn (array $a, array $b): int => $b[0] <=> $a[0]);
        return array_map(static fn (array $s): array => $s[1], array_slice($scored, 0, $limit));
    }

    /**
     * Même manifestation déjà publiée : même nom, même ville, même année.
     *
     * @return array<string, mixed>|null
     */
    public static function sameListing(string $name, string $city, ?string $startsOn): ?array
    {
        $nameKey = self::nameKey($name);
        $cityKey = self::nameKey($city);
        if ($nameKey === '' || $cityKey === '') {
            return null;
        }
        $year = is_string($startsOn) && preg_match('/^(\d{4})/', $startsOn, $m) === 1 ? $m[1] : '';
        $like = '%' . addcslashes($nameKey, '%_\\') . '%';
        $rows = Database::fetchAll('SELECT * FROM salons WHERE search_text LIKE ? LIMIT 80', [$like]);
        foreach ($rows as $row) {
            if (self::nameKey((string) ($row['name'] ?? '')) !== $nameKey) {
                continue;
            }
            if (self::nameKey((string) ($row['city'] ?? '')) !== $cityKey) {
                continue;
            }
            $rowYear = substr((string) ($row['starts_on'] ?? ''), 0, 4);
            if ($year !== '' && $rowYear !== '' && $rowYear !== $year) {
                continue;
            }
            return self::hydrate($row);
        }
        return null;
    }

    public static function nameKey(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', search_norm($text)));
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function createFromProposal(array $data): array
    {
        $name = mb_substr(trim((string) ($data['name'] ?? '')), 0, 255);
        if (mb_strlen($name) < 2) {
            throw new \RuntimeException('Indiquez le nom du salon.');
        }
        $city = trim((string) ($data['city'] ?? ''));
        if (mb_strlen($city) < 2) {
            throw new \RuntimeException('Indiquez la ville.');
        }
        $country = trim((string) ($data['country'] ?? 'France')) ?: 'France';
        $region = trim((string) ($data['region'] ?? ''));
        $category = mb_substr(trim((string) ($data['category'] ?? '')), 0, 120);
        $start = self::dateOrNull($data['starts_on'] ?? null);
        $end = self::dateOrNull($data['ends_on'] ?? null);
        if ($start === null) {
            throw new \RuntimeException('Indiquez une date de début.');
        }
        if ($end !== null && $end < $start) {
            $end = $start;
        }
        $slug = unique_slug(trim($name . ' ' . $city), static function (string $candidate): bool {
            if (in_array($candidate, self::RESERVED_SLUGS, true) || SalonHubs::isReserved($candidate)) {
                return true;
            }
            return Database::fetch('SELECT id FROM salons WHERE slug = ?', [$candidate]) !== null;
        });
        $description = trim((string) ($data['description'] ?? ''));
        $organizer = trim((string) ($data['organizer'] ?? ''));
        $search = search_norm(implode(' ', array_filter([
            $name, $category, $city, $region, $country, $description, $organizer,
        ])));
        Database::query(
            'INSERT INTO salons (
                slug, name, category, category_slug, type_label, is_direct,
                dates_raw, starts_on, ends_on, dates_confirmed,
                city, department, region, region_slug, country, country_slug,
                venue, website, attendance, exhibitors, ticket, organizer, contact, socials,
                description, audience, notes, search_text
            ) VALUES (
                ?, ?, ?, ?, ?, 1,
                ?, ?, ?, ?,
                ?, "", ?, ?, ?, ?,
                ?, ?, "", "", ?, ?, "", "",
                ?, ?, ?, ?
            )',
            [
                $slug,
                $name,
                $category,
                slugify($category),
                $category,
                self::rawDates($start, $end),
                $start,
                $end,
                !empty($data['dates_confirmed']) ? 1 : 0,
                $city,
                $region,
                slugify($region),
                $country,
                slugify($country),
                mb_substr(trim((string) ($data['venue'] ?? '')), 0, 255),
                SalonProposal::normalizeWebsite((string) ($data['website'] ?? ''), false),
                mb_substr(trim((string) ($data['ticket'] ?? '')), 0, 255),
                mb_substr($organizer, 0, 255),
                $description !== '' ? mb_substr($description, 0, 4000) : null,
                mb_substr(trim((string) ($data['audience'] ?? '')), 0, 190),
                null,
                $search,
            ]
        );
        $row = Database::fetch('SELECT * FROM salons WHERE id = ?', [(int) Database::lastId()]);
        return $row ? self::hydrate($row) : throw new \RuntimeException('Le salon n\'a pas pu être créé.');
    }

    public static function findById(int $id): ?array
    {
        $row = Database::fetch('SELECT * FROM salons WHERE id = ?', [$id]);
        return $row ? self::hydrate($row) : null;
    }

    private static function dateOrNull(mixed $value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '' || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m)) {
            return null;
        }
        if (!checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return null;
        }
        return $raw;
    }

    private static function rawDates(?string $start, ?string $end): string
    {
        if ($start === null) {
            return '';
        }
        if ($end === null || $end === $start) {
            return $start;
        }
        return $start . ' – ' . $end;
    }

    /** @param array<string, string> $filters */
    public static function countWhere(array $filters): int
    {
        [$where, $params] = self::whereFor($filters);

        return (int) (Database::fetch('SELECT COUNT(*) AS n FROM salons WHERE ' . $where, $params)['n'] ?? 0);
    }

    /**
     * @return list<array{ym: string, n: int}>
     */
    public static function monthCounts(): array
    {
        return Database::fetchAll(
            'SELECT DATE_FORMAT(starts_on, "%Y-%m") AS ym, COUNT(*) AS n
             FROM salons
             WHERE starts_on IS NOT NULL
             GROUP BY ym
             ORDER BY ym ASC'
        );
    }

    /**
     * Toutes les fiches d'un filtre, pour les liens internes d'une page indexable.
     *
     * @param array<string, string> $filters
     * @return list<array{name: string, href: string, city: string}>
     */
    public static function linkIndex(array $filters, int $limit = 500): array
    {
        [$where, $params] = self::whereFor($filters);
        $rows = Database::fetchAll(
            'SELECT name, slug, city, starts_on FROM salons WHERE ' . $where . '
             ORDER BY (starts_on IS NULL) ASC, starts_on ASC, name ASC
             LIMIT ' . max(1, $limit),
            $params
        );
        $out = [];
        foreach ($rows as $row) {
            $slug = trim((string) ($row['slug'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));
            if ($slug === '' || $name === '' || in_array($slug, self::RESERVED_SLUGS, true)) {
                continue;
            }
            $out[] = [
                'name' => $name,
                'href' => '/salons/' . $slug,
                'city' => trim((string) ($row['city'] ?? '')),
            ];
        }

        return $out;
    }

    /**
     * Éditions déjà terminées, liées depuis la page agenda indexable.
     *
     * @return list<array{name: string, href: string, city: string}>
     */
    public static function pastLinks(int $limit = 500): array
    {
        $rows = Database::fetchAll(
            'SELECT name, slug, city, starts_on FROM salons
             WHERE slug != ""
               AND starts_on IS NOT NULL
               AND starts_on < CURDATE()
               AND (ends_on IS NULL OR ends_on < CURDATE())
             ORDER BY starts_on DESC, name ASC
             LIMIT ' . max(1, $limit)
        );
        $out = [];
        foreach ($rows as $row) {
            $slug = trim((string) ($row['slug'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));
            if ($slug === '' || $name === '') {
                continue;
            }
            $year = substr((string) ($row['starts_on'] ?? ''), 0, 4);
            if ($year !== '' && !str_contains($name, $year)) {
                $name .= ', édition ' . $year;
            }
            $out[] = [
                'name' => $name,
                'href' => '/salons/' . $slug,
                'city' => trim((string) ($row['city'] ?? '')),
            ];
        }

        return $out;
    }

    public static function find(string $slug): ?array
    {
        $row = Database::fetch('SELECT * FROM salons WHERE slug = ?', [$slug]);
        return $row ? self::hydrate($row) : null;
    }

    public static function findForOwner(int $userId): ?array
    {
        $row = Database::fetch('SELECT * FROM salons WHERE owner_user_id = ? ORDER BY id ASC LIMIT 1', [$userId]);
        return $row ? self::hydrate($row) : null;
    }

    public static function assignOwner(int $id, ?int $userId): void
    {
        if ($userId === null) {
            Database::query('UPDATE salons SET owner_user_id = NULL, claimed_at = NULL WHERE id = ?', [$id]);
            return;
        }
        Database::query('UPDATE salons SET owner_user_id = ?, claimed_at = NOW() WHERE id = ?', [$userId, $id]);
    }

    public static function setFeatured(int $id, ?string $until): void
    {
        $until = trim((string) $until);
        if ($until !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $until)) {
            throw new \RuntimeException('Indiquez une date de fin de mise en avant valide.');
        }
        Database::query('UPDATE salons SET featured_until = ? WHERE id = ?', [$until !== '' ? $until : null, $id]);
    }

    /**
     * Fiches publiées pour l’administration : gestion du partenariat et de l’attribution.
     *
     * @return list<array<string, mixed>>
     */
    public static function adminList(string $q = '', int $limit = 80): array
    {
        $where = 's.slug != ""';
        $params = [];
        $q = search_norm(trim($q));
        if ($q !== '') {
            $where .= ' AND s.search_text LIKE ?';
            $params[] = '%' . $q . '%';
        }
        $rows = Database::fetchAll(
            'SELECT s.*, u.email AS owner_email, u.first_name AS owner_first_name, u.last_name AS owner_last_name
             FROM salons s
             LEFT JOIN users u ON u.id = s.owner_user_id
             WHERE ' . $where . '
             ORDER BY ' . str_replace('featured_until', 's.featured_until', self::FEATURED_SQL) . ' DESC,
                      (s.starts_on IS NULL) ASC, s.starts_on ASC, s.name ASC
             LIMIT ' . max(1, $limit),
            $params
        );
        $out = [];
        foreach ($rows as $row) {
            $item = self::hydrate($row);
            $item['owner_email'] = (string) ($row['owner_email'] ?? '');
            $item['owner_name'] = trim((string) (($row['owner_first_name'] ?? '') . ' ' . ($row['owner_last_name'] ?? '')));
            $out[] = $item;
        }
        return $out;
    }

    /**
     * Mise à jour de la fiche par l’organisateur qui en est propriétaire.
     * Le slug reste identique pour ne pas casser les liens.
     *
     * @param array<string, mixed> $data
     */
    public static function updateFromOwner(int $id, array $data): void
    {
        $name = mb_substr(trim((string) ($data['name'] ?? '')), 0, 255);
        if (mb_strlen($name) < 2) {
            throw new \RuntimeException('Indiquez le nom du salon.');
        }
        $city = trim((string) ($data['city'] ?? ''));
        if (mb_strlen($city) < 2) {
            throw new \RuntimeException('Indiquez la ville.');
        }
        $country = trim((string) ($data['country'] ?? 'France')) ?: 'France';
        $region = trim((string) ($data['region'] ?? ''));
        $category = mb_substr(trim((string) ($data['category'] ?? '')), 0, 120);
        $start = self::dateOrNull($data['starts_on'] ?? null);
        $end = self::dateOrNull($data['ends_on'] ?? null);
        if ($end !== null && $start !== null && $end < $start) {
            $end = $start;
        }
        $confirmed = !empty($data['dates_confirmed']);
        if ($start === null) {
            $confirmed = false;
        }
        $description = trim((string) ($data['description'] ?? ''));
        $organizer = trim((string) ($data['organizer'] ?? ''));
        $search = search_norm(implode(' ', array_filter([
            $name, $category, $city, $region, $country, $description, $organizer,
        ])));

        $fields = [
            'name' => $name,
            'category' => $category,
            'category_slug' => slugify($category),
            'type_label' => $category,
            'dates_raw' => self::rawDates($start, $end),
            'starts_on' => $start,
            'ends_on' => $end,
            'dates_confirmed' => $confirmed ? 1 : 0,
            'confirmed_by_organizer' => $confirmed ? 1 : 0,
            'city' => $city,
            'region' => $region,
            'region_slug' => slugify($region),
            'country' => $country,
            'country_slug' => slugify($country),
            'venue' => mb_substr(trim((string) ($data['venue'] ?? '')), 0, 255),
            'website' => SalonProposal::normalizeWebsite((string) ($data['website'] ?? ''), false),
            'ticket' => mb_substr(trim((string) ($data['ticket'] ?? '')), 0, 255),
            'organizer' => mb_substr($organizer, 0, 255),
            'contact' => mb_substr(trim((string) ($data['contact'] ?? '')), 0, 255),
            'socials' => mb_substr(trim((string) ($data['socials'] ?? '')), 0, 255),
            'description' => $description !== '' ? mb_substr($description, 0, 4000) : null,
            'program' => trim((string) ($data['program'] ?? '')) !== '' ? mb_substr(trim((string) $data['program']), 0, 6000) : null,
            'exhibitors_list' => trim((string) ($data['exhibitors_list'] ?? '')) !== '' ? mb_substr(trim((string) $data['exhibitors_list']), 0, 4000) : null,
            'audience' => mb_substr(trim((string) ($data['audience'] ?? '')), 0, 190),
            'search_text' => $search,
        ];
        if (array_key_exists('logo_path', $data)) {
            $fields['logo_path'] = $data['logo_path'] !== null ? (string) $data['logo_path'] : null;
        }

        $set = implode(', ', array_map(static fn (string $k): string => $k . ' = ?', array_keys($fields)));
        Database::query('UPDATE salons SET ' . $set . ' WHERE id = ?', [...array_values($fields), $id]);
    }

    /**
     * @param array{q?: string, category?: string, region?: string, country?: string} $filters
     * @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int}
     */
    public static function search(array $filters, int $page = 1): array
    {
        [$where, $params] = self::whereFor($filters);
        $total = (int) (Database::fetch('SELECT COUNT(*) AS n FROM salons WHERE ' . $where, $params)['n'] ?? 0);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * self::PER_PAGE;
        $rows = Database::fetchAll(
            'SELECT * FROM salons WHERE ' . $where . '
             ORDER BY (starts_on IS NOT NULL AND starts_on < CURDATE() AND (ends_on IS NULL OR ends_on < CURDATE())) ASC,
                      (starts_on IS NULL) ASC,
                      DATE_FORMAT(starts_on, "%Y-%m") ASC,
                      ' . self::FEATURED_SQL . ' DESC,
                      starts_on ASC,
                      name ASC
             LIMIT ' . self::PER_PAGE . ' OFFSET ' . $offset,
            $params
        );

        return [
            'items' => array_map([self::class, 'hydrate'], $rows),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
        ];
    }

    /**
     * @return array{categories: list<array{v: string, l: string, n: int}>, regions: list<array{v: string, l: string, n: int}>, countries: list<array{v: string, l: string, n: int}>, zones: list<array{v: string, l: string, n: int}>}
     */
    public static function facets(): array
    {
        $countries = self::facetRows('country');
        $zoneCounts = [];
        foreach ($countries as $row) {
            $zone = Geography::zoneSlugForCountry($row['v']);
            if ($zone === '') {
                continue;
            }
            $zoneCounts[$zone] = ($zoneCounts[$zone] ?? 0) + $row['n'];
        }
        $zones = [];
        foreach (Geography::ZONES as $slug => $meta) {
            $n = $zoneCounts[$slug] ?? 0;
            if ($n === 0) {
                continue;
            }
            $zones[] = ['v' => $slug, 'l' => $meta['name'], 'n' => $n];
        }
        return [
            'categories' => self::facetRows('category'),
            'regions' => self::facetRows('region', 'country = "France" AND region != "" AND region != "—"'),
            'countries' => $countries,
            'zones' => $zones,
        ];
    }

    /**
     * @return list<array{v: string, l: string, n: int}>
     */
    private static function facetRows(string $column, string $extra = '1=1'): array
    {
        $rows = Database::fetchAll(
            "SELECT {$column} AS l, COUNT(*) AS n FROM salons
             WHERE {$column} != '' AND {$extra}
             GROUP BY {$column} ORDER BY n DESC, l ASC"
        );
        $out = [];
        foreach ($rows as $row) {
            $label = (string) $row['l'];
            $out[] = ['v' => slugify($label), 'l' => $label, 'n' => (int) $row['n']];
        }
        return $out;
    }

    /**
     * @param array<string, string> $filters
     * @return array{0: string, 1: list<mixed>}
     */
    private static function whereFor(array $filters): array
    {
        $where = ['1=1'];
        $params = [];

        $q = search_norm(trim((string) ($filters['q'] ?? '')));
        if ($q !== '') {
            $where[] = 'search_text LIKE ?';
            $params[] = '%' . $q . '%';
        }
        $category = trim((string) ($filters['category'] ?? ''));
        if ($category !== '') {
            $where[] = 'category_slug = ?';
            $params[] = $category;
        }
        $region = trim((string) ($filters['region'] ?? ''));
        if ($region !== '') {
            $where[] = 'region_slug = ?';
            $params[] = $region;
        }
        $country = trim((string) ($filters['country'] ?? ''));
        if ($country !== '') {
            $where[] = 'country_slug = ?';
            $params[] = $country;
        } else {
            $zone = slugify((string) ($filters['zone'] ?? ''));
            if ($zone !== '' && Geography::isZone($zone)) {
                $slugs = Geography::countrySlugsForZone($zone);
                if ($slugs === []) {
                    $where[] = '0=1';
                } else {
                    $where[] = 'country_slug IN (' . implode(',', array_fill(0, count($slugs), '?')) . ')';
                    $params = array_merge($params, $slugs);
                }
            }
        }
        $from = trim((string) ($filters['from'] ?? ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) === 1) {
            $where[] = 'starts_on IS NOT NULL AND starts_on >= ?';
            $params[] = $from;
        }
        $until = trim((string) ($filters['until'] ?? ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $until) === 1) {
            $where[] = 'starts_on IS NOT NULL AND starts_on <= ?';
            $params[] = $until;
        }

        return [implode(' AND ', $where), $params];
    }

    public static function isUnverifiedText(string $raw): bool
    {
        $n = search_norm(trim($raw));
        return $n === '' || $n === '—' || $n === '-'
            || str_contains($n, 'non trouve')
            || str_contains($n, 'non verifie')
            || str_contains($n, 'non precise')
            || $n === 'n/a';
    }

    public static function cleanDateText(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '' || self::isUnverifiedText($raw)) {
            return '';
        }
        return $raw;
    }

    /**
     * Retire l’annotation de sourcing « hors de la fenêtre actuelle du site ».
     */
    public static function cleanNotes(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '' || self::isUnverifiedText($raw)) {
            return '';
        }
        $raw = (string) preg_replace(
            '/\s*[;,]?\s*hors de la fenêtre actuelle du site\s*(?:\([^)]*\))?/iu',
            '',
            $raw
        );
        $raw = trim($raw, " \t\n\r\0\x0B;,·-");
        return $raw;
    }

    public static function isPlaceholderDate(string $start, bool $confirmed): bool
    {
        if ($confirmed || $start === '') {
            return false;
        }
        return (bool) preg_match('/^\d{4}-01-01$/', substr($start, 0, 10));
    }

    /** @param array<string, mixed> $row */
    public static function hydrate(array $row): array
    {
        $confirmed = !empty($row['dates_confirmed']);
        $start = (string) ($row['starts_on'] ?? '');
        $end = (string) ($row['ends_on'] ?? '');
        if (self::isPlaceholderDate($start, $confirmed)) {
            $start = '';
            $end = '';
            $confirmed = false;
            $row['starts_on'] = '';
            $row['ends_on'] = '';
            $row['dates_confirmed'] = 0;
        }
        $city = (string) ($row['city'] ?? '');
        $region = self::cleanDateText((string) ($row['region'] ?? ''));
        $country = (string) ($row['country'] ?? '');
        $place = $city;
        if ($country !== '' && $country !== 'France') {
            $place = trim($city . ($city !== '' ? ' · ' : '') . $country);
        } elseif ($region !== '') {
            $place = trim($city . ($city !== '' ? ' · ' : '') . $region);
        }

        $row['region'] = $region;
        $row['department'] = self::cleanDateText((string) ($row['department'] ?? ''));
        $row['venue'] = self::cleanDateText((string) ($row['venue'] ?? ''));
        $row['attendance'] = self::cleanDateText((string) ($row['attendance'] ?? ''));
        $row['exhibitors'] = self::cleanDateText((string) ($row['exhibitors'] ?? ''));
        $row['ticket'] = self::cleanDateText((string) ($row['ticket'] ?? ''));
        $row['socials'] = self::cleanDateText((string) ($row['socials'] ?? ''));
        $row['notes'] = self::cleanNotes((string) ($row['notes'] ?? ''));
        $row['is_featured'] = self::isFeatured($row);
        $row['featured_label'] = self::featuredLabel($row);
        $row['confirmed_by_organizer'] = !empty($row['confirmed_by_organizer']);
        $row['logo_src'] = !empty($row['logo_path']) ? uploaded((string) $row['logo_path']) : '';
        $row['program'] = trim((string) ($row['program'] ?? ''));
        $row['exhibitors_list'] = trim((string) ($row['exhibitors_list'] ?? ''));
        $row['claimed'] = !empty($row['owner_user_id']);
        $row['href'] = '/salons/' . $row['slug'];
        $row['kind'] = (string) ($row['category'] ?? '');
        $row['place'] = $place;
        $row['iso'] = $start;
        $row['day'] = $start !== '' ? (string) (int) substr($start, 8, 2) : '—';
        $row['month'] = $start !== '' ? (self::MONTHS[(int) substr($start, 5, 2)] ?? '') : '';
        $row['confirmed'] = $confirmed;
        $until = $end !== '' ? substr($end, 0, 10) : ($start !== '' ? substr($start, 0, 10) : '');
        $row['is_past'] = $until !== '' && $until < date('Y-m-d');
        $raw = self::cleanDateText((string) ($row['dates_raw'] ?? ''));
        $row['when_dates'] = self::formatWhen($start, $end, $raw, true);
        $row['when'] = self::formatWhen($start, $end, $raw, $confirmed);
        if ($start !== '') {
            $monthNum = (int) substr($start, 5, 2);
            $year = (int) substr($start, 0, 4);
            $row['month_key'] = substr($start, 0, 7);
            $row['month_heading'] = trim((self::MONTHS_LONG[$monthNum] ?? '') . ' ' . $year);
        } else {
            $row['month_key'] = 'sans-date';
            $row['month_heading'] = 'Dates à confirmer';
        }
        return $row;
    }

    private static function formatWhen(string $start, string $end, string $raw, bool $confirmed): string
    {
        $raw = self::cleanDateText($raw);
        if (self::isPlaceholderDate($start, $confirmed)) {
            $start = '';
            $end = '';
        }
        if ($start === '') {
            return $raw !== '' ? $raw : 'Dates à confirmer';
        }
        $sy = (int) substr($start, 0, 4);
        $sm = (int) substr($start, 5, 2);
        $sd = (int) substr($start, 8, 2);
        $label = $sd . ' ' . (self::MONTHS[$sm] ?? '') . ' ' . $sy;
        if ($end !== '' && $end !== $start) {
            $ey = (int) substr($end, 0, 4);
            $em = (int) substr($end, 5, 2);
            $ed = (int) substr($end, 8, 2);
            if ($sy === $ey && $sm === $em) {
                $label = $sd . ' – ' . $ed . ' ' . (self::MONTHS[$sm] ?? '') . ' ' . $sy;
            } else {
                $label = $sd . ' ' . (self::MONTHS[$sm] ?? '') . ' – ' . $ed . ' ' . (self::MONTHS[$em] ?? '') . ' ' . $ey;
            }
        }
        if (!$confirmed) {
            $label .= ' · à confirmer';
        }
        return $label;
    }
}
