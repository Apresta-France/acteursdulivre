<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;
use Adl\Data\Geography;
use Adl\Data\ContestHubs;

final class Contest
{
    public const PER_PAGE = 20;

    /** Slugs réservés sous /prix-et-concours/ qui ne peuvent pas désigner une fiche. */
    private const RESERVED_SLUGS = ['ajouter', 'pays', 'zone'];

    public const KINDS = [
        'prix' => 'Prix littéraire',
        'concours' => 'Concours',
        'appel' => 'Appel à textes',
        'bourse' => 'Bourse / résidence',
    ];

    public const FALLBACK_CATEGORIES = [
        'Roman',
        'Premier roman',
        'Poésie',
        'Jeunesse',
        'Bande dessinée',
        'Polar & thriller',
        'Essai & document',
        'Nouvelle',
        'Imaginaire',
        'Traduction',
        'Autre',
    ];

    public const SORTS = [
        '' => 'Date limite',
        'ouverture' => 'Ouverture',
        'remise' => 'Proclamation',
        'nom' => 'Nom',
        'pays' => 'Pays',
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
            Database::fetch('SELECT 1 FROM contests LIMIT 1');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function countAll(): int
    {
        return (int) (Database::fetch('SELECT COUNT(*) AS n FROM contests')['n'] ?? 0);
    }

    public static function kindLabel(string $kind): string
    {
        return self::KINDS[$kind] ?? ($kind !== '' ? ucfirst($kind) : 'Prix');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function upcoming(int $limit = 8, string $exceptSlug = ''): array
    {
        $sql = 'SELECT * FROM contests
             WHERE (deadline_on IS NOT NULL AND deadline_on >= CURDATE())
                OR (awarded_on IS NOT NULL AND awarded_on >= CURDATE())';
        $params = [];
        if ($exceptSlug !== '') {
            $sql .= ' AND slug != ?';
            $params[] = $exceptSlug;
        }
        $sql .= ' ORDER BY COALESCE(deadline_on, awarded_on) ASC, name ASC LIMIT ' . max(1, $limit);
        $rows = Database::fetchAll($sql, $params);
        return array_map([self::class, 'hydrate'], $rows);
    }

    /**
     * Liens de toutes les récompenses à venir, pour la page indexable.
     *
     * @return list<array{name: string, href: string, city: string}>
     */
    public static function indexLinks(): array
    {
        $rows = Database::fetchAll(
            'SELECT name, slug, city, deadline_on, awarded_on FROM contests
             WHERE slug != ""
               AND (deadline_on IS NULL OR deadline_on >= CURDATE() OR (awarded_on IS NOT NULL AND awarded_on >= CURDATE()))
             ORDER BY (deadline_on IS NULL) ASC, deadline_on ASC, name ASC'
        );
        $out = [];
        foreach ($rows as $row) {
            $slug = trim((string) ($row['slug'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));
            if ($slug === '' || $name === '' || in_array($slug, self::RESERVED_SLUGS, true)) {
                continue;
            }
            $year = substr((string) ($row['awarded_on'] ?? $row['deadline_on'] ?? ''), 0, 4);
            if ($year !== '' && !str_contains($name, $year)) {
                $name .= ' (' . $year . ')';
            }
            $out[] = [
                'name' => $name,
                'href' => '/prix-et-concours/' . $slug,
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
                    'href' => '/prix-et-concours/pays/' . $row['v'],
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
                    'href' => '/prix-et-concours/zone/' . $zone['slug'],
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
     * Récompenses au nom proche, pour éviter un doublon à la proposition ou à la validation.
     *
     * @return list<array<string, mixed>>
     */
    public static function similar(string $name, string $city = '', int $limit = 4): array
    {
        $q = self::nameKey($name);
        if (mb_strlen($q) < 3) {
            return [];
        }
        $stop = ['prix', 'concours', 'festival', 'litteraire', 'litteraires', 'de', 'des', 'du', 'le', 'la', 'les', 'un', 'une', 'et', 'au', 'aux', 'en', 'sur', 'pour', 'd'];
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
            'SELECT * FROM contests
             WHERE name LIKE ? OR search_text LIKE ?
             ORDER BY (deadline_on IS NULL) ASC, deadline_on ASC, name ASC
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
     * Même récompense déjà publiée : même nom, même ville, même année.
     *
     * @return array<string, mixed>|null
     */
    public static function sameListing(string $name, string $city, ?string $deadline): ?array
    {
        $nameKey = self::nameKey($name);
        $cityKey = self::nameKey($city);
        if ($nameKey === '') {
            return null;
        }
        $year = is_string($deadline) && preg_match('/^(\d{4})/', $deadline, $m) === 1 ? $m[1] : '';
        $like = '%' . addcslashes($nameKey, '%_\\') . '%';
        $rows = Database::fetchAll('SELECT * FROM contests WHERE search_text LIKE ? LIMIT 80', [$like]);
        foreach ($rows as $row) {
            if (self::nameKey((string) ($row['name'] ?? '')) !== $nameKey) {
                continue;
            }
            if ($cityKey !== '' && self::nameKey((string) ($row['city'] ?? '')) !== $cityKey) {
                continue;
            }
            $rowYear = substr((string) ($row['deadline_on'] ?? $row['awarded_on'] ?? ''), 0, 4);
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
            throw new \RuntimeException('Indiquez le nom du prix ou du concours.');
        }
        $country = trim((string) ($data['country'] ?? 'France')) ?: 'France';
        $region = trim((string) ($data['region'] ?? ''));
        $city = mb_substr(trim((string) ($data['city'] ?? '')), 0, 120);
        $category = mb_substr(trim((string) ($data['category'] ?? '')), 0, 120);
        $kind = trim((string) ($data['kind'] ?? 'prix'));
        if (!isset(self::KINDS[$kind])) {
            $kind = 'prix';
        }
        $opens = self::dateOrNull($data['opens_on'] ?? null);
        $deadline = self::dateOrNull($data['deadline_on'] ?? null);
        $awarded = self::dateOrNull($data['awarded_on'] ?? null);
        if ($deadline === null && $awarded === null) {
            throw new \RuntimeException('Indiquez au moins la date limite ou la date de proclamation.');
        }
        if ($opens !== null && $deadline !== null && $deadline < $opens) {
            $deadline = $opens;
        }
        $slug = unique_slug(trim($name . ' ' . $city), static function (string $candidate): bool {
            if (in_array($candidate, self::RESERVED_SLUGS, true) || ContestHubs::isReserved($candidate)) {
                return true;
            }
            return Database::fetch('SELECT id FROM contests WHERE slug = ?', [$candidate]) !== null;
        });
        $description = trim((string) ($data['description'] ?? ''));
        $organizer = trim((string) ($data['organizer'] ?? ''));
        $search = search_norm(implode(' ', array_filter([
            $name, $kind, $category, $city, $region, $country,
            (string) ($data['prize'] ?? ''), (string) ($data['eligibility'] ?? ''),
            $description, $organizer,
        ])));
        Database::query(
            'INSERT INTO contests (
                slug, name, kind, kind_label, category, category_slug,
                opens_on, deadline_on, awarded_on, dates_raw, dates_confirmed,
                prize, entry_fee, eligibility,
                city, department, region, region_slug, country, country_slug,
                venue, website, organizer, contact,
                description, audience, notes, search_text
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?,
                ?, "", ?, ?, ?, ?,
                ?, ?, ?, "",
                ?, ?, ?, ?
            )',
            [
                $slug,
                $name,
                $kind,
                self::kindLabel($kind),
                $category,
                slugify($category),
                $opens,
                $deadline,
                $awarded,
                self::rawDates($opens, $deadline, $awarded),
                !empty($data['dates_confirmed']) ? 1 : 0,
                mb_substr(trim((string) ($data['prize'] ?? '')), 0, 255),
                mb_substr(trim((string) ($data['entry_fee'] ?? '')), 0, 255),
                mb_substr(trim((string) ($data['eligibility'] ?? '')), 0, 255),
                $city,
                $region,
                slugify($region),
                $country,
                slugify($country),
                mb_substr(trim((string) ($data['venue'] ?? '')), 0, 255),
                ContestProposal::normalizeWebsite((string) ($data['website'] ?? ''), false),
                mb_substr($organizer, 0, 255),
                $description !== '' ? mb_substr($description, 0, 4000) : null,
                null,
                null,
                $search,
            ]
        );
        $row = Database::fetch('SELECT * FROM contests WHERE id = ?', [(int) Database::lastId()]);
        return $row ? self::hydrate($row) : throw new \RuntimeException('La fiche n\'a pas pu être créée.');
    }

    /**
     * Importe des fiches déjà normalisées (mêmes clés que database/seeds/prix.json).
     * Les récompenses déjà présentes (même nom + même ville + même année) sont ignorées.
     *
     * @param list<array<string, mixed>> $items
     * @return array{inserted: int, skipped: int}
     */
    public static function importItems(array $items): array
    {
        $inserted = 0;
        $skipped = 0;
        foreach ($items as $item) {
            if (!is_array($item)) {
                $skipped++;
                continue;
            }
            $name = mb_substr(trim((string) ($item['name'] ?? '')), 0, 255);
            if (mb_strlen($name) < 2) {
                $skipped++;
                continue;
            }
            $city = mb_substr(trim((string) ($item['city'] ?? '')), 0, 120);
            $deadline = self::dateOrNull($item['deadline_on'] ?? null);
            $awarded = self::dateOrNull($item['awarded_on'] ?? null);
            if (self::sameListing($name, $city, $deadline ?? $awarded)) {
                $skipped++;
                continue;
            }
            $opens = self::dateOrNull($item['opens_on'] ?? null);
            $kind = trim((string) ($item['kind'] ?? 'prix'));
            if (!isset(self::KINDS[$kind])) {
                $kind = 'prix';
            }
            $category = mb_substr(trim((string) ($item['category'] ?? '')), 0, 120);
            $region = mb_substr(trim((string) ($item['region'] ?? '')), 0, 120);
            $country = mb_substr(trim((string) ($item['country'] ?? 'France')) ?: 'France', 0, 120);
            $slug = unique_slug(trim($name . ' ' . $city), static function (string $candidate): bool {
                if (in_array($candidate, self::RESERVED_SLUGS, true) || ContestHubs::isReserved($candidate)) {
                    return true;
                }
                return Database::fetch('SELECT id FROM contests WHERE slug = ?', [$candidate]) !== null;
            });
            $datesRaw = trim((string) ($item['dates_raw'] ?? ''));
            if ($datesRaw === '') {
                $datesRaw = self::rawDates($opens, $deadline, $awarded);
            }
            $description = trim((string) ($item['description'] ?? ''));
            $organizer = trim((string) ($item['organizer'] ?? ''));
            $prize = trim((string) ($item['prize'] ?? ''));
            $fee = trim((string) ($item['entry_fee'] ?? ''));
            $eligibility = trim((string) ($item['eligibility'] ?? ''));
            $search = search_norm(implode(' ', array_filter([
                $name, $kind, $category, $city, $region, $country,
                $prize, $eligibility, $description, $organizer,
            ])));
            try {
                Database::query(
                    'INSERT INTO contests (
                        slug, name, kind, kind_label, category, category_slug,
                        opens_on, deadline_on, awarded_on, dates_raw, dates_confirmed,
                        prize, entry_fee, eligibility,
                        city, department, region, region_slug, country, country_slug,
                        venue, website, organizer, contact,
                        description, audience, notes, search_text
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?,
                        ?, "", ?, ?, ?, ?,
                        ?, ?, ?, "",
                        ?, ?, ?, ?
                    )',
                    [
                        $slug,
                        $name,
                        $kind,
                        self::kindLabel($kind),
                        $category,
                        slugify($category),
                        $opens,
                        $deadline,
                        $awarded,
                        $datesRaw,
                        !empty($item['dates_confirmed']) ? 1 : 0,
                        mb_substr($prize, 0, 255),
                        mb_substr($fee, 0, 255),
                        mb_substr($eligibility, 0, 255),
                        $city,
                        $region,
                        slugify($region),
                        $country,
                        slugify($country),
                        mb_substr(trim((string) ($item['venue'] ?? '')), 0, 255),
                        ContestProposal::normalizeWebsite((string) ($item['website'] ?? ''), false),
                        mb_substr($organizer, 0, 255),
                        $description !== '' ? mb_substr($description, 0, 4000) : null,
                        mb_substr(trim((string) ($item['audience'] ?? '')), 0, 190),
                        trim((string) ($item['notes'] ?? '')) !== '' ? (string) $item['notes'] : null,
                        $search,
                    ]
                );
                $inserted++;
            } catch (\Throwable) {
                $skipped++;
            }
        }

        return ['inserted' => $inserted, 'skipped' => $skipped];
    }

    public static function findById(int $id): ?array
    {
        $row = Database::fetch('SELECT * FROM contests WHERE id = ?', [$id]);
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

    private static function rawDates(?string $opens, ?string $deadline, ?string $awarded): string
    {
        $parts = [];
        if ($deadline !== null) {
            $parts[] = 'limite ' . $deadline;
        }
        if ($awarded !== null && $awarded !== $deadline) {
            $parts[] = 'remise ' . $awarded;
        }
        return implode(' – ', $parts);
    }

    /** @param array<string, string> $filters */
    public static function countWhere(array $filters): int
    {
        [$where, $params] = self::whereFor($filters);

        return (int) (Database::fetch('SELECT COUNT(*) AS n FROM contests WHERE ' . $where, $params)['n'] ?? 0);
    }

    /**
     * @return list<array{ym: string, n: int}>
     */
    public static function monthCounts(): array
    {
        return Database::fetchAll(
            'SELECT DATE_FORMAT(deadline_on, "%Y-%m") AS ym, COUNT(*) AS n
             FROM contests
             WHERE deadline_on IS NOT NULL
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
            'SELECT name, slug, city, deadline_on FROM contests WHERE ' . $where . '
             ORDER BY (deadline_on IS NULL) ASC, deadline_on ASC, name ASC
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
                'href' => '/prix-et-concours/' . $slug,
                'city' => trim((string) ($row['city'] ?? '')),
            ];
        }

        return $out;
    }

    /**
     * Éditions déjà terminées (limite passée et proclamation passée).
     *
     * @return list<array{name: string, href: string, city: string}>
     */
    public static function pastLinks(int $limit = 500): array
    {
        $rows = Database::fetchAll(
            'SELECT name, slug, city, deadline_on, awarded_on FROM contests
             WHERE slug != ""
               AND deadline_on IS NOT NULL
               AND deadline_on < CURDATE()
               AND (awarded_on IS NULL OR awarded_on < CURDATE())
             ORDER BY deadline_on DESC, name ASC
             LIMIT ' . max(1, $limit)
        );
        $out = [];
        foreach ($rows as $row) {
            $slug = trim((string) ($row['slug'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));
            if ($slug === '' || $name === '') {
                continue;
            }
            $year = substr((string) ($row['deadline_on'] ?? ''), 0, 4);
            if ($year !== '' && !str_contains($name, $year)) {
                $name .= ', édition ' . $year;
            }
            $out[] = [
                'name' => $name,
                'href' => '/prix-et-concours/' . $slug,
                'city' => trim((string) ($row['city'] ?? '')),
            ];
        }

        return $out;
    }

    public static function find(string $slug): ?array
    {
        $row = Database::fetch('SELECT * FROM contests WHERE slug = ?', [$slug]);
        return $row ? self::hydrate($row) : null;
    }

    /**
     * @param array{q?: string, category?: string, country?: string, zone?: string, kind?: string, fee?: string, tri?: string} $filters
     * @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int}
     */
    public static function search(array $filters, int $page = 1): array
    {
        [$where, $params] = self::whereFor($filters);
        $total = (int) (Database::fetch('SELECT COUNT(*) AS n FROM contests WHERE ' . $where, $params)['n'] ?? 0);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * self::PER_PAGE;
        $rows = Database::fetchAll(
            'SELECT * FROM contests WHERE ' . $where . ' ' . self::orderClause((string) ($filters['tri'] ?? '')) . '
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

    private static function orderClause(string $tri): string
    {
        return match ($tri) {
            'ouverture' => 'ORDER BY (opens_on IS NULL) ASC, opens_on ASC, name ASC',
            'remise' => 'ORDER BY (awarded_on IS NULL) ASC, awarded_on ASC, name ASC',
            'nom' => 'ORDER BY name ASC',
            'pays' => 'ORDER BY country ASC, (deadline_on IS NULL) ASC, deadline_on ASC, name ASC',
            default => 'ORDER BY (deadline_on IS NOT NULL AND deadline_on < CURDATE() AND (awarded_on IS NULL OR awarded_on < CURDATE())) ASC,
                      (deadline_on IS NULL) ASC,
                      deadline_on ASC,
                      name ASC',
        };
    }

    /**
     * @return array{categories: list<array{v: string, l: string, n: int}>, countries: list<array{v: string, l: string, n: int}>, zones: list<array{v: string, l: string, n: int}>, kinds: list<array{v: string, l: string, n: int}>}
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
        $kinds = [];
        foreach (self::facetRows('kind') as $row) {
            $kinds[] = ['v' => $row['v'], 'l' => self::kindLabel($row['l']), 'n' => $row['n']];
        }
        return [
            'categories' => self::facetRows('category'),
            'countries' => $countries,
            'zones' => $zones,
            'kinds' => $kinds,
        ];
    }

    /**
     * @return list<array{v: string, l: string, n: int}>
     */
    private static function facetRows(string $column, string $extra = '1=1'): array
    {
        $rows = Database::fetchAll(
            "SELECT {$column} AS l, COUNT(*) AS n FROM contests
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
        $kind = slugify((string) ($filters['kind'] ?? ''));
        if ($kind !== '' && isset(self::KINDS[$kind])) {
            $where[] = 'kind = ?';
            $params[] = $kind;
        }
        $fee = slugify((string) ($filters['fee'] ?? ''));
        if ($fee === 'gratuit') {
            $where[] = '(entry_fee = "" OR LOWER(entry_fee) LIKE "%gratuit%" OR entry_fee = "0" OR entry_fee = "0 €")';
        } elseif ($fee === 'payant') {
            $where[] = 'entry_fee != "" AND LOWER(entry_fee) NOT LIKE "%gratuit%"';
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
            $where[] = 'deadline_on IS NOT NULL AND deadline_on >= ?';
            $params[] = $from;
        }
        $until = trim((string) ($filters['until'] ?? ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $until) === 1) {
            $where[] = 'deadline_on IS NOT NULL AND deadline_on <= ?';
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

    public static function cleanNotes(string $raw): string
    {
        $raw = trim($raw);
        if (self::isUnverifiedText($raw)) {
            return '';
        }
        $raw = (string) preg_replace(
            '/\s*[;,]?\s*hors de la fenêtre actuelle du site\s*(?:\([^)]*\))?/iu',
            '',
            $raw
        );
        return trim($raw, " \t\n\r\0\x0B;,·-");
    }

    /** @param array<string, mixed> $row */
    public static function hydrate(array $row): array
    {
        $confirmed = !empty($row['dates_confirmed']);
        $deadline = (string) ($row['deadline_on'] ?? '');
        $opens = (string) ($row['opens_on'] ?? '');
        $awarded = (string) ($row['awarded_on'] ?? '');
        $city = (string) ($row['city'] ?? '');
        $region = self::cleanDateText((string) ($row['region'] ?? ''));
        $country = (string) ($row['country'] ?? '');
        $place = $city;
        if ($country !== '' && $country !== 'France') {
            $place = trim($city . ($city !== '' ? ' · ' : '') . $country);
        } elseif ($region !== '') {
            $place = trim($city . ($city !== '' ? ' · ' : '') . $region);
        }
        if ($place === '') {
            $place = $country !== '' ? $country : 'En ligne';
        }

        $row['region'] = $region;
        $row['venue'] = self::cleanDateText((string) ($row['venue'] ?? ''));
        $row['prize'] = self::cleanDateText((string) ($row['prize'] ?? ''));
        $row['entry_fee'] = self::cleanDateText((string) ($row['entry_fee'] ?? ''));
        $row['eligibility'] = self::cleanDateText((string) ($row['eligibility'] ?? ''));
        $row['notes'] = self::cleanNotes((string) ($row['notes'] ?? ''));
        $row['href'] = '/prix-et-concours/' . $row['slug'];
        $row['kind_label'] = self::kindLabel((string) ($row['kind'] ?? ''));
        $row['kind'] = (string) ($row['category'] ?? '') !== ''
            ? $row['kind_label'] . ' · ' . $row['category']
            : $row['kind_label'];
        $row['place'] = $place;
        $sortDate = $deadline !== '' ? $deadline : $awarded;
        $row['iso'] = $sortDate;
        $row['day'] = $sortDate !== '' ? (string) (int) substr($sortDate, 8, 2) : '—';
        $row['month'] = $sortDate !== '' ? (self::MONTHS[(int) substr($sortDate, 5, 2)] ?? '') : '';
        $row['confirmed'] = $confirmed;
        $today = date('Y-m-d');
        $isPast = ($deadline !== '' && $deadline < $today && ($awarded === '' || $awarded < $today))
            || ($deadline === '' && $awarded !== '' && $awarded < $today);
        $row['is_past'] = $isPast;
        $row['is_closed'] = !$isPast && $deadline !== '' && $deadline < $today;
        $row['is_free'] = self::isFree((string) ($row['entry_fee'] ?? ''));

        $row['when_dates'] = self::formatDeadline($deadline, $awarded, true);
        $row['when'] = self::formatDeadline($deadline, $awarded, $confirmed);
        $row['deadline_label'] = self::deadlineLabel($deadline);
        $row['awarded_label'] = self::dateLabel($awarded);
        $row['opens_label'] = self::dateLabel($opens);

        $groupDate = $deadline !== '' ? $deadline : $awarded;
        if ($groupDate !== '') {
            $monthNum = (int) substr($groupDate, 5, 2);
            $year = (int) substr($groupDate, 0, 4);
            $row['month_key'] = substr($groupDate, 0, 7);
            $row['month_heading'] = trim((self::MONTHS_LONG[$monthNum] ?? '') . ' ' . $year);
        } else {
            $row['month_key'] = 'sans-date';
            $row['month_heading'] = 'Dates à confirmer';
        }
        return $row;
    }

    private static function isFree(string $fee): bool
    {
        $n = search_norm($fee);
        return $n === '' || str_contains($n, 'gratuit') || $n === '0' || str_contains($n, 'libre');
    }

    private static function dateLabel(string $date): string
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m) !== 1) {
            return '';
        }
        return (int) $m[3] . ' ' . (self::MONTHS[(int) $m[2]] ?? '') . ' ' . $m[1];
    }

    private static function deadlineLabel(string $deadline): string
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $deadline) !== 1) {
            return '';
        }
        $days = (int) ((strtotime($deadline) - strtotime(date('Y-m-d'))) / 86400);
        if ($days < 0) {
            return 'Clôturé le ' . self::dateLabel($deadline);
        }
        if ($days === 0) {
            return 'Clôture aujourd’hui';
        }
        if ($days === 1) {
            return 'Clôture demain';
        }
        return 'J-' . $days . ' · ' . self::dateLabel($deadline);
    }

    private static function formatDeadline(string $deadline, string $awarded, bool $confirmed): string
    {
        $parts = [];
        if ($deadline !== '') {
            $parts[] = 'Limite : ' . self::dateLabel($deadline);
        }
        if ($awarded !== '' && $awarded !== $deadline) {
            $parts[] = 'Proclamation : ' . self::dateLabel($awarded);
        }
        if ($parts === []) {
            return 'Dates à confirmer';
        }
        $label = implode(' · ', $parts);
        if (!$confirmed) {
            $label .= ' · à confirmer';
        }
        return $label;
    }
}
