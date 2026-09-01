<?php

declare(strict_types=1);

namespace Adl\Data;

final class Cities
{
    /** @var array<string, string> slug => nom officiel */
    private const MAJOR = [
        'aix-en-provence' => 'Aix-en-Provence',
        'ajaccio' => 'Ajaccio',
        'amiens' => 'Amiens',
        'angers' => 'Angers',
        'annecy' => 'Annecy',
        'antibes' => 'Antibes',
        'argenteuil' => 'Argenteuil',
        'aubervilliers' => 'Aubervilliers',
        'avignon' => 'Avignon',
        'besancon' => 'Besançon',
        'beziers' => 'Béziers',
        'bordeaux' => 'Bordeaux',
        'boulogne-billancourt' => 'Boulogne-Billancourt',
        'brest' => 'Brest',
        'caen' => 'Caen',
        'cannes' => 'Cannes',
        'cayenne' => 'Cayenne',
        'clermont-ferrand' => 'Clermont-Ferrand',
        'colmar' => 'Colmar',
        'dijon' => 'Dijon',
        'dunkerque' => 'Dunkerque',
        'fort-de-france' => 'Fort-de-France',
        'grenoble' => 'Grenoble',
        'le-havre' => 'Le Havre',
        'le-mans' => 'Le Mans',
        'lille' => 'Lille',
        'limoges' => 'Limoges',
        'lorient' => 'Lorient',
        'lyon' => 'Lyon',
        'marseille' => 'Marseille',
        'metz' => 'Metz',
        'montpellier' => 'Montpellier',
        'montreuil' => 'Montreuil',
        'mulhouse' => 'Mulhouse',
        'nancy' => 'Nancy',
        'nantes' => 'Nantes',
        'nice' => 'Nice',
        'nimes' => 'Nîmes',
        'niort' => 'Niort',
        'orleans' => 'Orléans',
        'paris' => 'Paris',
        'pau' => 'Pau',
        'perigueux' => 'Périgueux',
        'perpignan' => 'Perpignan',
        'pointe-a-pitre' => 'Pointe-à-Pitre',
        'poitiers' => 'Poitiers',
        'reims' => 'Reims',
        'rennes' => 'Rennes',
        'rouen' => 'Rouen',
        'saint-denis' => 'Saint-Denis',
        'saint-etienne' => 'Saint-Étienne',
        'saint-nazaire' => 'Saint-Nazaire',
        'strasbourg' => 'Strasbourg',
        'toulon' => 'Toulon',
        'toulouse' => 'Toulouse',
        'tours' => 'Tours',
        'troyes' => 'Troyes',
        'valence' => 'Valence',
        'villeurbanne' => 'Villeurbanne',
    ];

    /**
     * @return list<array{name: string, slug: string, area_slug: string, insee: string, postcode: string, dept: string, hint: string, label: string}>
     */
    public static function suggest(string $q, int $limit = 8): array
    {
        $q = trim($q);
        if (mb_strlen($q) < 2) {
            return [];
        }

        $limit = max(1, min(12, $limit));
        $remote = self::apiSearch($q, $limit);
        if ($remote !== []) {
            return $remote;
        }

        $needle = search_norm($q);
        $out = [];
        foreach (self::MAJOR as $slug => $name) {
            if (!str_contains(search_norm($name), $needle) && !str_contains($slug, slugify($q))) {
                continue;
            }
            $out[] = self::present([
                'name' => $name,
                'slug' => $slug,
                'area_slug' => $slug,
                'insee' => '',
                'postcode' => '',
                'dept' => '',
                'hint' => '',
            ]);
            if (count($out) >= $limit) {
                break;
            }
        }
        return $out;
    }

    /**
     * Normalise une saisie profil (libellé + slug/insee éventuels).
     *
     * @return array{name: string, slug: string, area_slug: string, insee: string, postcode: string, dept: string}
     */
    public static function resolveInput(string $label, string $slug = '', string $insee = ''): array
    {
        $label = self::cleanLabel($label);
        $slug = self::normalizeSlug($slug);
        $insee = preg_replace('/\D+/', '', $insee) ?? '';

        if ($insee !== '') {
            $hit = self::apiByInsee($insee);
            if ($hit !== null) {
                return $hit;
            }
        }
        if ($slug !== '') {
            $hit = self::resolveSlug($slug);
            if ($hit !== null) {
                if ($label !== '') {
                    $hit['name'] = $label;
                }
                return $hit;
            }
        }
        if ($label !== '') {
            $local = self::fromFreeText($label);
            $hits = self::apiSearch($label, 1);
            if ($hits !== [] && self::samePlace($hits[0], $local)) {
                return $hits[0];
            }
            return $local;
        }

        return self::empty();
    }

    /**
     * @return array{name: string, slug: string, area_slug: string, insee: string, postcode: string, dept: string}|null
     */
    public static function resolveSlug(string $slug): ?array
    {
        $slug = self::normalizeSlug($slug);
        if ($slug === '') {
            return null;
        }
        if (isset(self::MAJOR[$slug])) {
            return [
                'name' => self::MAJOR[$slug],
                'slug' => $slug,
                'area_slug' => $slug,
                'insee' => '',
                'postcode' => '',
                'dept' => '',
            ];
        }
        $hits = self::apiSearch(str_replace('-', ' ', $slug), 8);
        foreach ($hits as $hit) {
            if ($hit['slug'] === $slug || $hit['area_slug'] === $slug) {
                return $hit;
            }
        }
        return null;
    }

    /**
     * @return array{name: string, slug: string, area_slug: string, insee: string, postcode: string, dept: string}
     */
    public static function fromFreeText(string $raw): array
    {
        $label = self::cleanLabel($raw);
        if ($label === '') {
            return self::empty();
        }
        $slug = slugify($label);
        if ($slug === '') {
            return self::empty();
        }
        $area = self::areaSlug($label, $slug);
        $name = self::MAJOR[$area] ?? self::MAJOR[$slug] ?? $label;

        return [
            'name' => $name,
            'slug' => $slug,
            'area_slug' => $area,
            'insee' => '',
            'postcode' => '',
            'dept' => '',
        ];
    }

    public static function normalizeSlug(string $slug): string
    {
        return slugify($slug);
    }

    public static function labelForSlug(string $slug, bool $remote = true): string
    {
        $slug = self::normalizeSlug($slug);
        if ($slug === '') {
            return '';
        }
        if (isset(self::MAJOR[$slug])) {
            return self::MAJOR[$slug];
        }
        if ($remote) {
            $resolved = self::resolveSlug($slug);
            if ($resolved !== null) {
                return $resolved['name'];
            }
        }
        return self::titleFromSlug($slug);
    }

    /** @param array<string, mixed> $item */
    public static function itemAreaSlug(array $item): string
    {
        $area = self::normalizeSlug((string) ($item['city_area_slug'] ?? ''));
        if ($area !== '') {
            return $area;
        }
        $slug = self::normalizeSlug((string) ($item['city_slug'] ?? ''));
        $city = trim((string) ($item['city'] ?? ''));
        if ($slug === '' && $city !== '') {
            return self::fromFreeText($city)['area_slug'];
        }
        if ($slug !== '') {
            return self::areaSlug($city, $slug);
        }
        return '';
    }

    /** @param array<string, mixed> $item */
    public static function itemCitySlug(array $item): string
    {
        $slug = self::normalizeSlug((string) ($item['city_slug'] ?? ''));
        if ($slug !== '') {
            return $slug;
        }
        $city = trim((string) ($item['city'] ?? ''));
        return $city !== '' ? self::fromFreeText($city)['slug'] : '';
    }

    /** @param array<string, mixed> $item */
    public static function itemMatches(array $item, string $want): bool
    {
        $want = self::normalizeSlug($want);
        if ($want === '') {
            return true;
        }
        $slug = self::itemCitySlug($item);
        $area = self::itemAreaSlug($item);
        return $slug === $want || $area === $want;
    }

    public static function areaSlug(string $name, string $slug): string
    {
        $slug = self::normalizeSlug($slug);
        foreach (['paris', 'lyon', 'marseille'] as $metro) {
            if ($slug === $metro || str_starts_with($slug, $metro . '-')) {
                if ($slug === $metro || preg_match('/^' . $metro . '-\d/', $slug) === 1) {
                    return $metro;
                }
            }
            if (preg_match('/^' . $metro . '\s+\d/i', $name) === 1) {
                return $metro;
            }
        }
        return $slug;
    }

    public static function guessFromQuery(string $rest): string
    {
        $rest = trim((string) preg_replace('/\s+/', ' ', $rest));
        if ($rest === '') {
            return '';
        }
        $slug = self::normalizeSlug($rest);
        if (isset(self::MAJOR[$slug])) {
            return $slug;
        }
        $tokens = preg_split('/\s+/', $rest) ?: [];
        if (count($tokens) === 1 && mb_strlen($slug) >= 3) {
            if (isset(self::MAJOR[$slug]) || self::resolveSlug($slug) !== null) {
                return $slug;
            }
            return $slug;
        }
        if (isset(self::MAJOR[$slug])) {
            return $slug;
        }
        $hit = self::resolveSlug($slug);
        return $hit['area_slug'] ?? '';
    }

    /**
     * @return array{name: string, slug: string, area_slug: string, insee: string, postcode: string, dept: string}
     */
    public static function empty(): array
    {
        return [
            'name' => '',
            'slug' => '',
            'area_slug' => '',
            'insee' => '',
            'postcode' => '',
            'dept' => '',
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array{name: string, slug: string, area_slug: string, insee: string, postcode: string, dept: string, hint: string, label: string}
     */
    private static function present(array $row): array
    {
        $name = (string) ($row['name'] ?? '');
        $slug = self::normalizeSlug((string) ($row['slug'] ?? $name));
        $area = self::normalizeSlug((string) ($row['area_slug'] ?? '')) ?: self::areaSlug($name, $slug);
        $postcode = (string) ($row['postcode'] ?? '');
        $dept = (string) ($row['dept'] ?? '');
        $hint = trim((string) ($row['hint'] ?? ''));
        if ($hint === '') {
            $hint = trim($postcode . ($dept !== '' ? ($postcode !== '' ? ' · ' : '') . $dept : ''));
        }
        return [
            'name' => $name,
            'slug' => $slug,
            'area_slug' => $area,
            'insee' => (string) ($row['insee'] ?? ''),
            'postcode' => $postcode,
            'dept' => $dept,
            'hint' => $hint,
            'label' => $name,
        ];
    }

    /**
     * @return list<array{name: string, slug: string, area_slug: string, insee: string, postcode: string, dept: string, hint: string, label: string}>
     */
    private static function apiSearch(string $q, int $limit): array
    {
        $url = 'https://geo.api.gouv.fr/communes?' . http_build_query([
            'nom' => $q,
            'boost' => 'population',
            'limit' => $limit,
            'fields' => 'nom,code,codesPostaux,departement,region,population',
        ]);
        $rows = self::httpJson($url, 300);
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $mapped = self::fromApiRow($row);
            if ($mapped['slug'] !== '') {
                $out[] = self::present($mapped);
            }
        }
        return $out;
    }

    /**
     * @return array{name: string, slug: string, area_slug: string, insee: string, postcode: string, dept: string}|null
     */
    private static function apiByInsee(string $insee): ?array
    {
        $url = 'https://geo.api.gouv.fr/communes/' . rawurlencode($insee) . '?fields=nom,code,codesPostaux,departement,region';
        $row = self::httpJson($url, 86400);
        if (!is_array($row) || empty($row['nom'])) {
            return null;
        }
        return self::fromApiRow($row);
    }

    /**
     * @param array<string, mixed> $row
     * @return array{name: string, slug: string, area_slug: string, insee: string, postcode: string, dept: string}
     */
    private static function fromApiRow(array $row): array
    {
        $name = trim((string) ($row['nom'] ?? ''));
        $slug = slugify($name);
        $codes = $row['codesPostaux'] ?? [];
        $postcode = is_array($codes) ? (string) ($codes[0] ?? '') : '';
        $dept = '';
        if (is_array($row['departement'] ?? null)) {
            $dept = trim((string) (($row['departement']['nom'] ?? '') ?: ($row['departement']['code'] ?? '')));
        }
        return [
            'name' => $name,
            'slug' => $slug,
            'area_slug' => self::areaSlug($name, $slug),
            'insee' => (string) ($row['code'] ?? ''),
            'postcode' => $postcode,
            'dept' => $dept,
        ];
    }

    /**
     * @param array<string, mixed> $a
     * @param array<string, mixed> $b
     */
    private static function samePlace(array $a, array $b): bool
    {
        return ($a['slug'] ?? '') === ($b['slug'] ?? '')
            || ($a['area_slug'] ?? '') === ($b['slug'] ?? '')
            || ($a['slug'] ?? '') === ($b['area_slug'] ?? '');
    }

    public static function cleanLabel(string $raw): string
    {
        $raw = trim(preg_replace('/\s+/', ' ', $raw) ?? $raw);
        $raw = (string) preg_replace('/\s*\(\d{2,5}\)\s*$/', '', $raw);
        return trim($raw);
    }

    public static function titleFromSlug(string $slug): string
    {
        $slug = str_replace('-', ' ', self::normalizeSlug($slug));
        if ($slug === '') {
            return '';
        }
        return mb_convert_case($slug, MB_CASE_TITLE, 'UTF-8');
    }

    private static function httpJson(string $url, int $ttl): mixed
    {
        static $memory = [];
        if (isset($memory[$url])) {
            return $memory[$url];
        }
        $cacheDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'adl-geo';
        $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . sha1($url) . '.json';
        if (is_file($cacheFile) && (time() - (int) filemtime($cacheFile)) < $ttl) {
            $cached = json_decode((string) file_get_contents($cacheFile), true);
            if ($cached !== null) {
                $memory[$url] = $cached;
                return $cached;
            }
        }

        $raw = self::httpGet($url);
        if ($raw === null) {
            return null;
        }
        $data = json_decode($raw, true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        $memory[$url] = $data;
        try {
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0775, true);
            }
            file_put_contents($cacheFile, $raw);
        } catch (\Throwable) {
        }
        return $data;
    }

    private static function httpGet(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return null;
            }
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 3,
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTPHEADER => ['Accept: application/json', 'User-Agent: acteursdulivre.fr'],
            ]);
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if (!is_string($body) || $code >= 400) {
                return null;
            }
            return $body;
        }

        $ctx = stream_context_create([
            'http' => [
                'timeout' => 3,
                'header' => "Accept: application/json\r\nUser-Agent: acteursdulivre.fr\r\n",
                'ignore_errors' => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $ctx);
        return is_string($body) ? $body : null;
    }
}
