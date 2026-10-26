<?php

declare(strict_types=1);

namespace Adl\Data;

use Adl\Models\Contest;

/** Pages indexables de l'annuaire des prix et concours : genre, mois. */
final class ContestHubs
{
    private const MIN_COUNTRY = 2;
    private const MIN_MONTH = 3;
    private const MIN_GENRE = 3;

    /** @var array<string, array{category: string, label: string, intro: string}> */
    private const GENRES = [
        'premier-roman' => [
            'category' => 'premier-roman',
            'label' => 'Premier roman',
            'intro' => 'Prix et concours ouverts aux auteurs de premier roman : manuscrits inédits ou premiers ouvrages parus, en France, en Europe et dans la francophonie. Les dates et conditions se reconfirment dans le règlement de chaque récompense. Un prix manque ? Signalez-le.',
        ],
        'poesie' => [
            'category' => 'poesie',
            'label' => 'Poésie',
            'intro' => 'Prix de poésie et concours de poèmes : recueils publiés et textes inédits, en France, en Europe et dans la francophonie. Les dates limites et les dotations se confirment dans le règlement officiel. Un prix manque ? Signalez-le.',
        ],
        'polar' => [
            'category' => 'polar-thriller',
            'label' => 'Polar & thriller',
            'intro' => 'Prix et concours de roman policier, noir et thriller : manuscrits inédits et ouvrages parus, en France, en Europe et dans la francophonie. Un prix manque ? Signalez-le.',
        ],
        'jeunesse' => [
            'category' => 'jeunesse',
            'label' => 'Jeunesse',
            'intro' => 'Prix et concours de littérature jeunesse : albums, romans et textes pour la jeunesse, en France, en Europe et dans la francophonie. Un prix manque ? Signalez-le.',
        ],
        'bd' => [
            'category' => 'bande-dessinee',
            'label' => 'Bande dessinée',
            'intro' => 'Prix et concours de bande dessinée et de roman graphique : projets et albums, en France, en Europe et dans la francophonie. Un prix manque ? Signalez-le.',
        ],
        'nouvelles' => [
            'category' => 'nouvelle',
            'label' => 'Nouvelle',
            'intro' => 'Concours de nouvelles et appels à textes courts : thèmes, dotations et dates limites, en France, en Europe et dans la francophonie. Un concours manque ? Signalez-le.',
        ],
        'traduction' => [
            'category' => 'traduction',
            'label' => 'Traduction',
            'intro' => 'Prix et distinctions pour traducteurs et œuvres traduites, en France, en Europe et dans la francophonie. Un prix manque ? Signalez-le.',
        ],
        'imaginaire' => [
            'category' => 'imaginaire',
            'label' => 'Imaginaire',
            'intro' => 'Prix et concours de science-fiction, fantasy et fantastique : manuscrits et œuvres parues, en France, en Europe et dans la francophonie. Un prix manque ? Signalez-le.',
        ],
    ];

    /** @var array<string, string> */
    private const GENRE_REDIRECTS = [
        'bande-dessinee' => '/prix-et-concours/bd',
        'bande-dessinee-bd' => '/prix-et-concours/bd',
        'polar-thriller' => '/prix-et-concours/polar',
        'nouvelle' => '/prix-et-concours/nouvelles',
        'premiers-romans' => '/prix-et-concours/premier-roman',
    ];

    /** @var array<int, string> */
    private const MONTHS = [
        1 => 'janvier', 2 => 'fevrier', 3 => 'mars', 4 => 'avril',
        5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'aout',
        9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'decembre',
    ];

    /** @return array<string, mixed>|null */
    public static function resolve(string $slug): ?array
    {
        $slug = slugify($slug);
        if ($slug === '' || $slug === 'ajouter') {
            return null;
        }
        if (isset(self::GENRE_REDIRECTS[$slug])) {
            return ['redirect' => self::GENRE_REDIRECTS[$slug]];
        }
        if (isset(self::GENRES[$slug])) {
            return self::genre($slug, self::GENRES[$slug]);
        }

        return self::genreFromFacet($slug);
    }

    public static function isReserved(string $slug): bool
    {
        $slug = slugify($slug);
        if ($slug === '') {
            return false;
        }
        if ($slug === 'pays' || $slug === 'zone' || isset(self::GENRES[$slug]) || isset(self::GENRE_REDIRECTS[$slug])) {
            return true;
        }

        return self::genreFromFacet($slug) !== null;
    }

    /** @return array<string, mixed>|null */
    public static function month(int $year, string $monthSlug): ?array
    {
        if ($year < 2024 || $year > 2035) {
            return null;
        }
        $monthSlug = slugify($monthSlug);
        $number = array_search($monthSlug, self::MONTHS, true);
        if ($number === false) {
            return null;
        }
        $from = sprintf('%04d-%02d-01', $year, $number);
        $until = date('Y-m-t', strtotime($from) ?: time());
        $count = self::count(['from' => $from, 'until' => $until]);
        if ($count < self::MIN_MONTH) {
            return null;
        }
        $label = self::monthLabel($number) . ' ' . $year;
        $path = '/prix-et-concours/' . $year . '/' . $monthSlug;

        return [
            'kind' => 'month',
            'label' => $label,
            'path' => $path,
            'h1' => 'Prix et concours : clôture en ' . $label,
            'title' => 'Prix et concours littéraires : date limite en ' . $label,
            'description' => 'Prix, concours et appels à textes dont la date limite de candidature tombe en ' . $label . ' : dotations, conditions et liens officiels.',
            'intro' => 'Prix, concours et appels à textes dont la candidature clôture en ' . $label . ', en France, en Europe et dans la francophonie. Les dates se confirment dans le règlement de chaque récompense. Un prix manque ? Signalez-le.',
            'filters' => ['from' => $from, 'until' => $until],
            'facet_category' => '',
        ];
    }

    /**
     * Liens de navigation (pays, zones, genres, mois) pour l'annuaire et le sitemap.
     *
     * @return list<array{href: string, label: string, kind: string}>
     */
    public static function nav(): array
    {
        $out = [];
        try {
            foreach (Contest::facets()['countries'] as $row) {
                if ((int) ($row['n'] ?? 0) < self::MIN_COUNTRY) {
                    continue;
                }
                $out[] = [
                    'href' => '/prix-et-concours/pays/' . $row['v'],
                    'label' => $row['l'],
                    'kind' => 'country',
                ];
            }
            foreach (Contest::facets()['zones'] as $row) {
                if ((int) ($row['n'] ?? 0) < self::MIN_COUNTRY || $row['v'] === 'france') {
                    continue;
                }
                $out[] = [
                    'href' => '/prix-et-concours/zone/' . $row['v'],
                    'label' => $row['l'],
                    'kind' => 'zone',
                ];
            }
        } catch (\Throwable) {
        }
        foreach (self::GENRES as $slug => $genre) {
            if (self::count(['category' => $genre['category']]) < 1) {
                continue;
            }
            $out[] = ['href' => '/prix-et-concours/' . $slug, 'label' => $genre['label'], 'kind' => 'genre'];
        }
        foreach (self::months() as $hub) {
            $out[] = ['href' => (string) $hub['path'], 'label' => (string) $hub['label'], 'kind' => 'month'];
        }

        return $out;
    }

    /** @return list<array{path: string, priority: string}> */
    public static function sitemapPaths(): array
    {
        $out = [];
        foreach (self::nav() as $item) {
            $out[] = [
                'path' => (string) $item['href'],
                'priority' => $item['kind'] === 'country' ? '0.7' : '0.6',
            ];
        }

        return $out;
    }

    /** @param array<string, string> $filters */
    private static function count(array $filters): int
    {
        try {
            return Contest::countWhere($filters);
        } catch (\Throwable) {
            return 0;
        }
    }

    /** @param array{category: string, label: string, intro: string} $genre
     *  @return array<string, mixed>
     */
    private static function genre(string $slug, array $genre): array
    {
        return [
            'kind' => 'genre',
            'label' => $genre['label'],
            'path' => '/prix-et-concours/' . $slug,
            'h1' => 'Prix et concours : ' . mb_strtolower($genre['label']),
            'title' => 'Prix et concours ' . mb_strtolower($genre['label']) . ' 2026-2027',
            'description' => 'Prix, concours et appels à textes ' . mb_strtolower($genre['label']) . ' en France, en Europe et dans la francophonie : dates limites, dotations et conditions.',
            'intro' => $genre['intro'],
            'filters' => ['category' => $genre['category']],
            'facet_category' => $genre['category'],
        ];
    }

    /** @return array<string, mixed>|null */
    private static function genreFromFacet(string $slug): ?array
    {
        try {
            $facets = Contest::facets()['categories'] ?? [];
        } catch (\Throwable) {
            return null;
        }
        foreach ($facets as $row) {
            if ((string) ($row['v'] ?? '') !== $slug || (int) ($row['n'] ?? 0) < self::MIN_GENRE) {
                continue;
            }
            $label = (string) $row['l'];

            return [
                'kind' => 'genre',
                'label' => $label,
                'path' => '/prix-et-concours/' . $slug,
                'h1' => 'Prix et concours : ' . $label,
                'title' => $label . ' — prix et concours littéraires 2026-2027',
                'description' => 'Prix, concours et appels à textes classés « ' . $label . ' » : dates limites, dotations et conditions, en France, en Europe et dans la francophonie.',
                'intro' => 'Prix et concours classés « ' . $label . ' » dans l\'annuaire : dates limites, dotations et conditions, en France, en Europe et dans la francophonie. Un prix manque ? Signalez-le.',
                'filters' => ['category' => $slug],
                'facet_category' => $slug,
            ];
        }

        return null;
    }

    /** @return list<array<string, mixed>> */
    private static function months(): array
    {
        $out = [];
        try {
            $rows = Contest::monthCounts();
        } catch (\Throwable) {
            return [];
        }
        foreach ($rows as $row) {
            $key = (string) ($row['ym'] ?? '');
            if (preg_match('/^(\d{4})-(\d{2})$/', $key, $m) !== 1) {
                continue;
            }
            if ((int) ($row['n'] ?? 0) < self::MIN_MONTH) {
                continue;
            }
            $hub = self::month((int) $m[1], self::MONTHS[(int) $m[2]] ?? '');
            if ($hub !== null) {
                $out[] = $hub;
            }
        }

        return $out;
    }

    private static function monthLabel(int $number): string
    {
        return match ($number) {
            1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
            5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
            9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre',
            default => '',
        };
    }
}
