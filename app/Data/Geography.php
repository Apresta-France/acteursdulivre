<?php

declare(strict_types=1);

namespace Adl\Data;

/**
 * Pays, prépositions (« au Maroc », « en Côte d'Ivoire ») et zones éditoriales.
 */
final class Geography
{
    public const UNKNOWN_LABEL = 'International';

    /** @var array<string, array{name: string, hint: string}> */
    public const ZONES = [
        'france' => ['name' => 'France', 'hint' => 'Métropole et outre-mer'],
        'europe' => ['name' => 'Europe', 'hint' => 'Continent européen'],
        'amerique-du-nord' => ['name' => 'Amérique du Nord', 'hint' => 'Canada, États-Unis'],
        'afrique' => ['name' => 'Afrique', 'hint' => 'Afrique francophone et continent'],
        'maghreb-moyen-orient' => ['name' => 'Maghreb et Moyen-Orient', 'hint' => 'Maghreb, Proche-Orient et monde arabe'],
        'caraibes' => ['name' => 'Caraïbes', 'hint' => 'Caraïbes francophones'],
        'asie' => ['name' => 'Asie', 'hint' => 'Asie et océan Indien éditorial'],
        'amerique-latine' => ['name' => 'Amérique latine', 'hint' => 'Mexique, Amérique centrale et du Sud'],
    ];

    /** @var list<string> */
    public const MAJOR_CITIES = [
        'montreal', 'quebec', 'toronto', 'ottawa', 'gatineau', 'laval', 'sherbrooke',
        'saguenay', 'trois-rivieres', 'longueuil', 'vancouver', 'calgary',
        'bruxelles', 'anvers', 'liege', 'gand', 'charleroi', 'namur',
        'geneve', 'lausanne', 'zurich', 'berne', 'bale', 'fribourg', 'sion', 'neuchatel',
        'dakar', 'abidjan', 'alger', 'tunis', 'casablanca', 'rabat', 'marrakech',
        'beyrouth', 'le-caire', 'tunis', 'kinshasa', 'yaounde', 'douala', 'bamako',
        'cotonou', 'libreville', 'lome', 'niamey', 'ouagadougou', 'conakry',
        'antananarivo', 'port-au-prince', 'port-louis',
        'new-york', 'mexico', 'buenos-aires', 'bogota', 'singapour',
    ];

    /** @var array<string, string> slug de zone */
    private const COUNTRY_ZONES = [
        'france' => 'france',
        'allemagne' => 'europe',
        'belgique' => 'europe',
        'suisse' => 'europe',
        'espagne' => 'europe',
        'italie' => 'europe',
        'autriche' => 'europe',
        'portugal' => 'europe',
        'royaume-uni' => 'europe',
        'pays-bas' => 'europe',
        'irlande' => 'europe',
        'suede' => 'europe',
        'pologne' => 'europe',
        'ukraine' => 'europe',
        'norvege' => 'europe',
        'finlande' => 'europe',
        'republique-tcheque' => 'europe',
        'rep-tcheque' => 'europe',
        'serbie' => 'europe',
        'roumanie' => 'europe',
        'grece' => 'europe',
        'lituanie' => 'europe',
        'estonie' => 'europe',
        'islande' => 'europe',
        'hongrie' => 'europe',
        'croatie' => 'europe',
        'slovenie' => 'europe',
        'slovaquie' => 'europe',
        'lettonie' => 'europe',
        'bosnie-herzegovine' => 'europe',
        'albanie' => 'europe',
        'macedoine-du-nord' => 'europe',
        'moldavie' => 'europe',
        'bulgarie' => 'europe',
        'danemark' => 'europe',
        'luxembourg' => 'europe',
        'monaco' => 'europe',
        'montenegro' => 'europe',
        'andorre' => 'europe',
        'malte' => 'europe',
        'chypre' => 'europe',
        'russie' => 'europe',
        'canada' => 'amerique-du-nord',
        'etats-unis' => 'amerique-du-nord',
        'senegal' => 'afrique',
        'cote-d-ivoire' => 'afrique',
        'cameroun' => 'afrique',
        'mali' => 'afrique',
        'rd-congo' => 'afrique',
        'republique-democratique-du-congo' => 'afrique',
        'gabon' => 'afrique',
        'togo' => 'afrique',
        'benin' => 'afrique',
        'niger' => 'afrique',
        'burkina-faso' => 'afrique',
        'guinee' => 'afrique',
        'madagascar' => 'afrique',
        'maurice' => 'afrique',
        'maroc' => 'maghreb-moyen-orient',
        'tunisie' => 'maghreb-moyen-orient',
        'algerie' => 'maghreb-moyen-orient',
        'egypte' => 'maghreb-moyen-orient',
        'liban' => 'maghreb-moyen-orient',
        'syrie' => 'maghreb-moyen-orient',
        'emirats-arabes-unis' => 'maghreb-moyen-orient',
        'arabie-saoudite' => 'maghreb-moyen-orient',
        'qatar' => 'maghreb-moyen-orient',
        'turquie' => 'maghreb-moyen-orient',
        'israel' => 'maghreb-moyen-orient',
        'haiti' => 'caraibes',
        'inde' => 'asie',
        'cambodge' => 'asie',
        'chine' => 'asie',
        'taiwan' => 'asie',
        'singapour' => 'asie',
        'japon' => 'asie',
        'mexique' => 'amerique-latine',
        'argentine' => 'amerique-latine',
        'colombie' => 'amerique-latine',
        'bresil' => 'amerique-latine',
        'chili' => 'amerique-latine',
        'perou' => 'amerique-latine',
    ];

    /** @var array<string, string> search_norm => préposition */
    private const PREPOSITIONS = [
        'pays-bas' => 'aux',
        'etats-unis' => 'aux',
        'emirats arabes unis' => 'aux',
        'philippines' => 'aux',
        'monaco' => 'à',
        'haiti' => 'à',
        'madagascar' => 'à',
        'maurice' => 'à',
        'singapour' => 'à',
        'taiwan' => 'à',
        'cuba' => 'à',
        'malte' => 'à',
        'chypre' => 'à',
        'hong kong' => 'à',
        'macao' => 'à',
        'gibraltar' => 'à',
        'luxembourg' => 'au',
        'portugal' => 'au',
        'danemark' => 'au',
        'royaume-uni' => 'au',
        'montenegro' => 'au',
        'canada' => 'au',
        'maroc' => 'au',
        'senegal' => 'au',
        'liban' => 'au',
        'cameroun' => 'au',
        'benin' => 'au',
        'gabon' => 'au',
        'mali' => 'au',
        'togo' => 'au',
        'burkina faso' => 'au',
        'niger' => 'au',
        'qatar' => 'au',
        'mexique' => 'au',
        'cambodge' => 'au',
        'japon' => 'au',
        'bresil' => 'au',
        'perou' => 'au',
        'chili' => 'au',
        'congo' => 'au',
        'ghana' => 'au',
        'kenya' => 'au',
        'nigeria' => 'au',
        'vietnam' => 'au',
        'laos' => 'au',
        'nepal' => 'au',
        'bangladesh' => 'au',
        'pakistan' => 'au',
        'kosovo' => 'au',
        'rwanda' => 'au',
        'tchad' => 'au',
        'soudan' => 'au',
        'yemen' => 'au',
        'koweit' => 'au',
        'france' => 'en',
        'allemagne' => 'en',
        'belgique' => 'en',
        'suisse' => 'en',
        'espagne' => 'en',
        'italie' => 'en',
        'autriche' => 'en',
        'irlande' => 'en',
        'suede' => 'en',
        'pologne' => 'en',
        'ukraine' => 'en',
        'norvege' => 'en',
        'finlande' => 'en',
        'republique tcheque' => 'en',
        'rep. tcheque' => 'en',
        'rep tcheque' => 'en',
        'serbie' => 'en',
        'roumanie' => 'en',
        'grece' => 'en',
        'lituanie' => 'en',
        'estonie' => 'en',
        'islande' => 'en',
        'hongrie' => 'en',
        'croatie' => 'en',
        'slovenie' => 'en',
        'slovaquie' => 'en',
        'lettonie' => 'en',
        'bosnie-herzegovine' => 'en',
        'albanie' => 'en',
        'macedoine du nord' => 'en',
        'moldavie' => 'en',
        'bulgarie' => 'en',
        'europe' => 'en',
        'cote d\'ivoire' => 'en',
        'cote divoire' => 'en',
        'tunisie' => 'en',
        'algerie' => 'en',
        'turquie' => 'en',
        'syrie' => 'en',
        'egypte' => 'en',
        'guinee' => 'en',
        'inde' => 'en',
        'colombie' => 'en',
        'argentine' => 'en',
        'chine' => 'en',
        'russie' => 'en',
        'georgie' => 'en',
        'armenie' => 'en',
        'australie' => 'en',
        'mauritanie' => 'en',
        'ethiopie' => 'en',
        'andorre' => 'en',
        'israel' => 'en',
        'arabie saoudite' => 'en',
        'rd congo' => 'en',
        'rdc' => 'en',
        'republique democratique du congo' => 'en',
        'iran' => 'en',
        'irak' => 'en',
        'oman' => 'en',
        'ouganda' => 'en',
        'equateur' => 'en',
        'angola' => 'en',
        'afrique' => 'en',
        'amerique du nord' => 'en',
        'amerique latine' => 'en',
        'asie' => 'en',
    ];

    /** @var array<string, string> search_norm => slug canonique */
    private const ALIASES = [
        'etats unis' => 'etats-unis',
        'usa' => 'etats-unis',
        'united states' => 'etats-unis',
        'emirats' => 'emirats-arabes-unis',
        'uae' => 'emirats-arabes-unis',
        'cote divoire' => 'cote-d-ivoire',
        'cote d ivoire' => 'cote-d-ivoire',
        'rdc' => 'rd-congo',
        'republique democratique du congo' => 'rd-congo',
        'congo rdc' => 'rd-congo',
        'rep. tcheque' => 'republique-tcheque',
        'rep tcheque' => 'republique-tcheque',
        'republique tcheque' => 'republique-tcheque',
        'czech republic' => 'republique-tcheque',
        'uk' => 'royaume-uni',
        'grande-bretagne' => 'royaume-uni',
        'united kingdom' => 'royaume-uni',
    ];

    public static function isZone(string $slug): bool
    {
        return isset(self::ZONES[slugify($slug)]);
    }

    /** @return array{name: string, slug: string, hint: string, href: string}|null */
    public static function zone(string $slug): ?array
    {
        $slug = slugify($slug);
        if (!isset(self::ZONES[$slug])) {
            return null;
        }
        return [
            'name' => self::ZONES[$slug]['name'],
            'slug' => $slug,
            'hint' => self::ZONES[$slug]['hint'],
            'href' => '/maisons-edition/zone/' . $slug,
        ];
    }

    /** @return list<array{name: string, slug: string, hint: string, href: string}> */
    public static function zones(): array
    {
        $out = [];
        foreach (self::ZONES as $slug => $meta) {
            $out[] = [
                'name' => $meta['name'],
                'slug' => $slug,
                'hint' => $meta['hint'],
                'href' => '/maisons-edition/zone/' . $slug,
            ];
        }
        return $out;
    }

    public static function zoneSlugForCountry(string $country): string
    {
        $slug = self::countrySlug($country);
        if ($slug === '') {
            return '';
        }
        return self::COUNTRY_ZONES[$slug] ?? '';
    }

    /** @return list<string> */
    public static function countrySlugsForZone(string $zoneSlug): array
    {
        $zoneSlug = slugify($zoneSlug);
        if ($zoneSlug === '' || !isset(self::ZONES[$zoneSlug])) {
            return [];
        }
        $out = [];
        foreach (self::COUNTRY_ZONES as $country => $zone) {
            if ($zone === $zoneSlug || ($zoneSlug === 'europe' && $country === 'france')) {
                $out[] = $country;
            }
        }
        if ($zoneSlug === 'europe' && !in_array('france', $out, true)) {
            $out[] = 'france';
        }
        return array_values(array_unique($out));
    }

    public static function countrySlug(string $country): string
    {
        $country = trim($country);
        if ($country === '') {
            return '';
        }
        $norm = search_norm($country);
        $norm = (string) preg_replace('/\s+/', ' ', $norm);
        if (isset(self::ALIASES[$norm])) {
            return self::ALIASES[$norm];
        }
        $slug = slugify($country);
        return self::ALIASES[str_replace('-', ' ', $slug)] ?? $slug;
    }

    /**
     * « au Maroc », « en Côte d'Ivoire », « aux États-Unis ».
     */
    public static function countryPhrase(string $country): string
    {
        $country = trim($country);
        if ($country === '') {
            return 'à l\'international';
        }
        $prep = self::preposition($country);
        return $prep . ' ' . $country;
    }

    public static function preposition(string $country): string
    {
        $n = search_norm(trim($country));
        $n = (string) preg_replace('/\s+/', ' ', $n);
        if ($n === '') {
            return 'à';
        }
        if (isset(self::PREPOSITIONS[$n])) {
            return self::PREPOSITIONS[$n];
        }
        $slugKey = str_replace('-', ' ', slugify($country));
        if (isset(self::PREPOSITIONS[$slugKey])) {
            return self::PREPOSITIONS[$slugKey];
        }
        if (preg_match('/^[aeiouy]/', $n) === 1) {
            return 'en';
        }
        return 'au';
    }

    public static function locationFallback(string $city, string $country): string
    {
        $label = implode(', ', array_filter([$city, $country], static fn (string $v): bool => trim($v) !== ''));
        return $label !== '' ? $label : self::UNKNOWN_LABEL;
    }

    public static function cityLabel(string $slug): string
    {
        $map = [
            'montreal' => 'Montréal',
            'quebec' => 'Québec',
            'toronto' => 'Toronto',
            'ottawa' => 'Ottawa',
            'gatineau' => 'Gatineau',
            'laval' => 'Laval',
            'sherbrooke' => 'Sherbrooke',
            'saguenay' => 'Saguenay',
            'trois-rivieres' => 'Trois-Rivières',
            'longueuil' => 'Longueuil',
            'vancouver' => 'Vancouver',
            'calgary' => 'Calgary',
            'bruxelles' => 'Bruxelles',
            'anvers' => 'Anvers',
            'liege' => 'Liège',
            'gand' => 'Gand',
            'charleroi' => 'Charleroi',
            'namur' => 'Namur',
            'geneve' => 'Genève',
            'lausanne' => 'Lausanne',
            'zurich' => 'Zürich',
            'berne' => 'Berne',
            'bale' => 'Bâle',
            'fribourg' => 'Fribourg',
            'sion' => 'Sion',
            'neuchatel' => 'Neuchâtel',
            'dakar' => 'Dakar',
            'abidjan' => 'Abidjan',
            'alger' => 'Alger',
            'tunis' => 'Tunis',
            'casablanca' => 'Casablanca',
            'rabat' => 'Rabat',
            'marrakech' => 'Marrakech',
            'beyrouth' => 'Beyrouth',
            'le-caire' => 'Le Caire',
            'kinshasa' => 'Kinshasa',
            'yaounde' => 'Yaoundé',
            'douala' => 'Douala',
            'bamako' => 'Bamako',
            'cotonou' => 'Cotonou',
            'libreville' => 'Libreville',
            'lome' => 'Lomé',
            'niamey' => 'Niamey',
            'ouagadougou' => 'Ouagadougou',
            'conakry' => 'Conakry',
            'antananarivo' => 'Antananarivo',
            'port-au-prince' => 'Port-au-Prince',
            'port-louis' => 'Port-Louis',
            'new-york' => 'New York',
            'mexico' => 'Mexico',
            'buenos-aires' => 'Buenos Aires',
            'bogota' => 'Bogotá',
            'singapour' => 'Singapour',
        ];
        return $map[slugify($slug)] ?? '';
    }
}
