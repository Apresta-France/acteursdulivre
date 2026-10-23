<?php

declare(strict_types=1);

namespace Adl\Data;

use Adl\Models\Salon;

/** Pages indexables de l'agenda : région, genre, mois. */
final class SalonHubs
{
    private const MIN_REGION = 4;
    private const MIN_MONTH = 3;
    private const MIN_GENRE = 8;

    /** @var array<string, array{category: string, label: string, intro: string}> */
    private const GENRES = [
        'jeunesse' => [
            'category' => 'jeunesse',
            'label' => 'Jeunesse',
            'intro' => 'Salons, festivals et fêtes du livre jeunesse : albums, romans, BD et rencontres avec les auteurs, en France, en Europe et dans la francophonie. Les dates, villes et lieux sont ceux de l’agenda, à reconfirmer sur le site de l’organisateur. Une date manque ? Signalez-la.',
        ],
        'polar' => [
            'category' => 'polar-roman-noir',
            'label' => 'Polar',
            'intro' => 'Salons et festivals polar et roman noir : dédicaces, tables rondes et rencontres, en France, en Europe et dans la francophonie. Cette page suit les dates de l’agenda. Une manifestation manque ? Signalez-la.',
        ],
        'bd' => [
            'category' => 'bande-dessinee-bd',
            'label' => 'Bande dessinée',
            'intro' => 'Festivals et salons de bande dessinée : dédicaces, expositions et rencontres, en France, en Europe et dans la francophonie. Les dates se confirment auprès de chaque organisateur. Une date manque ? Signalez-la.',
        ],
        'romance' => [
            'category' => '',
            'label' => 'Romance',
            'intro' => 'Salons et festivals où la romance est au programme, dont les manifestations new romance. La liste vient de l’agenda (titre, genre ou description). Les dates se confirment sur le site de l’organisateur. Une date manque ? Signalez-la.',
        ],
    ];

    /** @var array<string, string> */
    private const GENRE_REDIRECTS = [
        'polar-roman-noir' => '/salons/polar',
        'bande-dessinee' => '/salons/bd',
        'bande-dessinee-bd' => '/salons/bd',
    ];

    /** @var array<int, string> */
    private const MONTHS = [
        1 => 'janvier', 2 => 'fevrier', 3 => 'mars', 4 => 'avril',
        5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'aout',
        9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'decembre',
    ];

    /** @var array<string, string> */
    private const REGION_INTRO = [
        'hauts-de-france' => 'Du Festival New Romance de Lille au salon du livre de Mers-les-Bains, les Hauts-de-France accueillent chaque année des dizaines de salons et de fêtes du livre. Cette page recense ceux du Nord, du Pas-de-Calais, de la Somme, de l’Oise et de l’Aisne, avec leurs dates, leur lieu et leur genre (jeunesse, polar, BD, romance). Elle est mise à jour chaque semaine ; une date manque ? Signalez-la.',
        'ile-de-france' => 'Paris et l’Île-de-France concentrent des salons généralistes, des festivals de genre et des fêtes de quartier. Cette page liste ceux de l’agenda, de Paris aux départements de grande couronne, avec leurs dates et leur ville. Elle est mise à jour chaque semaine ; une date manque ? Signalez-la.',
        'auvergne-rhone-alpes' => 'De Lyon aux Alpes et au Massif central, l’Auvergne-Rhône-Alpes a un des agendas les plus fournis. Cette page recense les salons et festivals du livre de la région, avec leurs dates, leur ville et leur genre. Elle est mise à jour chaque semaine ; une date manque ? Signalez-la.',
        'provence-alpes-cote-d-azur' => 'Du littoral méditerranéen aux Alpes du Sud, la Provence-Alpes-Côte d’Azur accueille de grands festivals et des salons de ville. Cette page suit ceux de l’agenda, avec leurs dates et leur lieu. Elle est mise à jour chaque semaine ; une date manque ? Signalez-la.',
        'occitanie' => 'De Toulouse à Montpellier et aux villes moyennes, l’Occitanie a un agenda dense de salons et de festivals. Cette page en donne les dates, les villes et les genres. Elle est mise à jour chaque semaine ; une date manque ? Signalez-la.',
        'nouvelle-aquitaine' => 'De Bordeaux à Limoges et à la côte basque, la Nouvelle-Aquitaine mêle salons généralistes et festivals de genre. Cette page recense ceux de l’agenda. Elle est mise à jour chaque semaine ; une date manque ? Signalez-la.',
        'bretagne' => 'La Bretagne a un maillage serré de salons du livre, de festivals jeunesse et de fêtes littéraires. Cette page en liste les dates et les villes. Elle est mise à jour chaque semaine ; une date manque ? Signalez-la.',
        'normandie' => 'La Normandie accueille des salons de ville et des festivals, du Calvados à la Seine-Maritime. Cette page recense ceux de l’agenda, avec leurs dates et leur lieu. Elle est mise à jour chaque semaine ; une date manque ? Signalez-la.',
        'grand-est' => 'De Strasbourg à Reims et à Nancy, le Grand Est a des salons généralistes et des festivals spécialisés. Cette page en donne les dates et les villes. Elle est mise à jour chaque semaine ; une date manque ? Signalez-la.',
        'pays-de-la-loire' => 'Les Pays de la Loire accueillent des salons et des fêtes du livre, de Nantes à la côte et au Maine. Cette page suit ceux de l’agenda. Elle est mise à jour chaque semaine ; une date manque ? Signalez-la.',
        'bourgogne-franche-comte' => 'La Bourgogne-Franche-Comté a des salons de ville et des festivals, de Dijon à Besançon. Cette page recense ceux de l’agenda, avec leurs dates et leur ville. Elle est mise à jour chaque semaine ; une date manque ? Signalez-la.',
        'centre-val-de-loire' => 'Le Centre-Val de Loire accueille des salons et des fêtes du livre le long de la Loire et dans les préfectures. Cette page en liste les dates et les villes. Elle est mise à jour chaque semaine ; une date manque ? Signalez-la.',
        'corse' => 'La Corse a peu de salons recensés, et chacun compte pour les lecteurs et les auteurs de l’île. Cette page suit ceux de l’agenda. Elle est mise à jour chaque semaine ; une date manque ? Signalez-la.',
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
        $region = self::region($slug);
        if ($region !== null) {
            return $region;
        }

        return self::genreFromFacet($slug);
    }

    public static function isReserved(string $slug): bool
    {
        $slug = slugify($slug);
        if ($slug === '' || $slug === 'pays' || $slug === 'zone' || isset(self::GENRES[$slug]) || isset(self::GENRE_REDIRECTS[$slug])) {
            return $slug !== '';
        }

        return self::region($slug) !== null;
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
        $path = '/salons/' . $year . '/' . $monthSlug;

        return [
            'kind' => 'month',
            'label' => $label,
            'path' => $path,
            'h1' => 'Salons du livre en ' . $label,
            'title' => 'Salons du livre en ' . $label,
            'description' => 'Agenda des salons et festivals du livre en ' . $label . ' : dates, villes et genres, en France, en Europe et dans la francophonie.',
            'intro' => 'Salons et festivals du livre datés en ' . $label . ', en France, en Europe et dans la francophonie. Les dates se confirment auprès de l’organisateur. Une manifestation manque ? Signalez-la.',
            'filters' => ['from' => $from, 'until' => $until],
            'facet_region' => '',
            'facet_category' => '',
        ];
    }

    /**
     * Liens de navigation (régions, genres, mois) pour l’agenda et le sitemap.
     *
     * @return list<array{href: string, label: string, kind: string}>
     */
    public static function nav(): array
    {
        $out = [];
        foreach (self::regions() as $hub) {
            $out[] = ['href' => (string) $hub['path'], 'label' => (string) $hub['label'], 'kind' => 'region'];
        }
        try {
            foreach (Salon::facets()['countries'] as $row) {
                if ((int) ($row['n'] ?? 0) < self::MIN_REGION) {
                    continue;
                }
                $out[] = [
                    'href' => '/salons/pays/' . $row['v'],
                    'label' => $row['l'],
                    'kind' => 'country',
                ];
            }
            foreach (Salon::facets()['zones'] as $row) {
                if ((int) ($row['n'] ?? 0) < self::MIN_REGION || $row['v'] === 'france') {
                    continue;
                }
                $out[] = [
                    'href' => '/salons/zone/' . $row['v'],
                    'label' => $row['l'],
                    'kind' => 'zone',
                ];
            }
        } catch (\Throwable) {
        }
        foreach (self::GENRES as $slug => $genre) {
            if ($slug === 'romance' && self::count(['q' => 'romance']) < 1) {
                continue;
            }
            if ($slug !== 'romance' && self::count(['category' => $genre['category']]) < 1) {
                continue;
            }
            $out[] = ['href' => '/salons/' . $slug, 'label' => $genre['label'], 'kind' => 'genre'];
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
                'priority' => $item['kind'] === 'region' ? '0.7' : '0.6',
            ];
        }

        return $out;
    }

    /** @param array<string, string> $filters */
    private static function count(array $filters): int
    {
        try {
            return Salon::countWhere($filters);
        } catch (\Throwable) {
            return 0;
        }
    }

    /** @return list<array<string, mixed>> */
    private static function regions(): array
    {
        $out = [];
        try {
            $facets = Salon::facets()['regions'] ?? [];
        } catch (\Throwable) {
            return [];
        }
        foreach ($facets as $row) {
            $slug = (string) ($row['v'] ?? '');
            if ($slug === '' || $slug === 'paca' || (int) ($row['n'] ?? 0) < self::MIN_REGION) {
                continue;
            }
            $hub = self::region($slug);
            if ($hub !== null) {
                $out[] = $hub;
            }
        }

        return $out;
    }

    /** @return array<string, mixed>|null */
    private static function region(string $slug): ?array
    {
        try {
            $facets = Salon::facets()['regions'] ?? [];
        } catch (\Throwable) {
            return null;
        }
        foreach ($facets as $row) {
            if ((string) ($row['v'] ?? '') !== $slug) {
                continue;
            }
            if ((int) ($row['n'] ?? 0) < self::MIN_REGION || $slug === 'paca') {
                return null;
            }
            $label = (string) $row['l'];
            $intro = self::REGION_INTRO[$slug]
                ?? ($label . ' : cette page recense les salons et festivals du livre de la région, avec leurs dates, leur ville et leur genre. Elle est mise à jour chaque semaine ; une date manque ? Signalez-la.');

            return [
                'kind' => 'region',
                'label' => $label,
                'path' => '/salons/' . $slug,
                'h1' => 'Salons du livre en ' . $label,
                'title' => 'Salons du livre en ' . $label . ' 2026-2027',
                'description' => 'Les salons et festivals du livre en ' . $label . ' : dates, villes et genres, de 2026 à 2027. Agenda mis à jour chaque semaine.',
                'intro' => $intro,
                'filters' => ['region' => $slug],
                'facet_region' => $slug,
                'facet_category' => '',
            ];
        }

        return null;
    }

    /** @param array{category: string, label: string, intro: string} $genre
     *  @return array<string, mixed>
     */
    private static function genre(string $slug, array $genre): array
    {
        $filters = $genre['category'] !== ''
            ? ['category' => $genre['category']]
            : ['q' => 'romance'];

        return [
            'kind' => 'genre',
            'label' => $genre['label'],
            'path' => '/salons/' . $slug,
            'h1' => 'Salons du livre ' . mb_strtolower($genre['label']),
            'title' => 'Salons du livre ' . mb_strtolower($genre['label']) . ' 2026-2027',
            'description' => 'Agenda des salons et festivals ' . mb_strtolower($genre['label']) . ' en France, en Europe et dans la francophonie : dates, villes et lieux.',
            'intro' => $genre['intro'],
            'filters' => $filters,
            'facet_region' => '',
            'facet_category' => $genre['category'],
        ];
    }

    /** @return array<string, mixed>|null */
    private static function genreFromFacet(string $slug): ?array
    {
        try {
            $facets = Salon::facets()['categories'] ?? [];
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
                'path' => '/salons/' . $slug,
                'h1' => 'Salons du livre : ' . $label,
                'title' => $label . ' — salons du livre 2026-2027',
                'description' => 'Agenda des salons et festivals classés « ' . $label . ' » : dates, villes et lieux, en France, en Europe et dans la francophonie.',
                'intro' => 'Salons et festivals classés « ' . $label . ' » dans l’agenda : dates, villes et lieux, en France, en Europe et dans la francophonie. Une date manque ? Signalez-la.',
                'filters' => ['category' => $slug],
                'facet_region' => '',
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
            $rows = Salon::monthCounts();
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
