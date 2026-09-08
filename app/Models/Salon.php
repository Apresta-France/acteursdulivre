<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;

final class Salon
{
    public const PER_PAGE = 20;

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
    public static function upcoming(int $limit = 8): array
    {
        $rows = Database::fetchAll(
            'SELECT * FROM salons
             WHERE starts_on IS NOT NULL AND starts_on >= CURDATE()
             ORDER BY starts_on ASC, name ASC
             LIMIT ' . max(1, $limit)
        );
        return array_map([self::class, 'hydrate'], $rows);
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
