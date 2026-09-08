<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;

final class Salon
{
    public const PER_PAGE = 20;

    /** Slugs réservés sous /salons/ qui ne peuvent pas désigner une fiche. */
    private const RESERVED_SLUGS = ['ajouter'];

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
        $sql .= ' ORDER BY starts_on ASC, name ASC LIMIT ' . max(1, $limit);
        $rows = Database::fetchAll($sql, $params);
        return array_map([self::class, 'hydrate'], $rows);
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
            if (in_array($candidate, self::RESERVED_SLUGS, true)) {
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

    public static function find(string $slug): ?array
    {
        $row = Database::fetch('SELECT * FROM salons WHERE slug = ?', [$slug]);
        return $row ? self::hydrate($row) : null;
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
             ORDER BY (starts_on IS NULL) ASC, starts_on ASC, name ASC
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
     * @return array{categories: list<array{v: string, l: string, n: int}>, regions: list<array{v: string, l: string, n: int}>, countries: list<array{v: string, l: string, n: int}>}
     */
    public static function facets(): array
    {
        return [
            'categories' => self::facetRows('category'),
            'regions' => self::facetRows('region', 'country = "France" AND region != ""'),
            'countries' => self::facetRows('country'),
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
        }

        return [implode(' AND ', $where), $params];
    }

    /** @param array<string, mixed> $row */
    public static function hydrate(array $row): array
    {
        $start = (string) ($row['starts_on'] ?? '');
        $end = (string) ($row['ends_on'] ?? '');
        $city = (string) ($row['city'] ?? '');
        $region = (string) ($row['region'] ?? '');
        $country = (string) ($row['country'] ?? '');
        $place = $city;
        if ($country !== '' && $country !== 'France') {
            $place = trim($city . ($city !== '' ? ' · ' : '') . $country);
        } elseif ($region !== '') {
            $place = trim($city . ($city !== '' ? ' · ' : '') . $region);
        }

        $row['href'] = '/salons/' . $row['slug'];
        $row['kind'] = (string) ($row['category'] ?? '');
        $row['place'] = $place;
        $row['iso'] = $start;
        $row['day'] = $start !== '' ? (string) (int) substr($start, 8, 2) : '—';
        $row['month'] = $start !== '' ? (self::MONTHS[(int) substr($start, 5, 2)] ?? '') : '';
        $row['when'] = self::formatWhen($start, $end, (string) ($row['dates_raw'] ?? ''), !empty($row['dates_confirmed']));
        return $row;
    }

    private static function formatWhen(string $start, string $end, string $raw, bool $confirmed): string
    {
        if ($start === '') {
            return $raw !== '' ? $raw : 'Date à confirmer';
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
