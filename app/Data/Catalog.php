<?php

declare(strict_types=1);

namespace Adl\Data;

use Adl\Models\Mission;
use Adl\Models\PortfolioItem;
use Adl\Models\Profile;

final class Catalog
{
    public const TYPES = [
        'all' => 'Tout',
        'prestations' => 'Prestations',
        'prestataires' => 'Prestataires',
        'missions' => 'Missions',
    ];

    /** @return list<string> */
    public static function trades(): array
    {
        return Profile::TRADES;
    }

    /**
     * @return array{
     *   query: string,
     *   type: string,
     *   cat: string,
     *   count: int,
     *   results: list<array<string, mixed>>,
     *   groups: array<string, list<array<string, mixed>>>,
     *   suggestions: list<array<string, mixed>>
     * }
     */
    public static function search(string $q, string $type = 'all', string $cat = '', int $limit = 48): array
    {
        $type = array_key_exists($type, self::TYPES) ? $type : 'all';
        $items = [];

        if ($type === 'all' || $type === 'prestations') {
            $items = array_merge($items, self::services());
        }
        if ($type === 'all' || $type === 'prestataires') {
            $items = array_merge($items, self::providers());
        }
        if ($type === 'all' || $type === 'missions') {
            $items = array_merge($items, self::missions());
        }

        $scored = [];
        foreach ($items as $item) {
            if ($cat !== '' && search_norm((string) ($item['cat'] ?? '')) !== search_norm($cat)) {
                continue;
            }
            $score = self::score($q, $item);
            if ($q !== '' && $score <= 0) {
                continue;
            }
            $item['score'] = $score;
            $scored[] = $item;
        }

        usort($scored, static function (array $a, array $b): int {
            return ($b['score'] <=> $a['score']) ?: strcmp((string) $a['title'], (string) $b['title']);
        });

        $results = array_slice($scored, 0, $limit);
        $groups = ['prestations' => [], 'prestataires' => [], 'missions' => []];
        foreach ($results as $item) {
            $groups[$item['kind']][] = $item;
        }

        $suggestions = array_slice($results, 0, 8);

        return [
            'query' => $q,
            'type' => $type,
            'cat' => $cat,
            'count' => count($scored),
            'results' => $results,
            'groups' => $groups,
            'suggestions' => $suggestions,
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function services(): array
    {
        $rows = [
            ['Correction', 'Correction complète d\'un roman jusqu\'à 90 000 signes', 'Marion Vasseur', 'MV', '420 €', '4,9', 87, '8 jours', 'Confirmée', 'Choix de la rédaction'],
            ['Correction', 'Relecture typographique sur épreuves PDF', 'Atelier Virgule', 'AV', '260 €', '4,8', 52, '4 jours', 'Experte', ''],
            ['Correction', 'Correction d\'un essai ou document, jusqu\'à 300 pages', 'Paul Ferrand', 'PF', '640 €', '4,9', 38, '12 jours', 'Confirmé', ''],
            ['Correction', 'Réécriture et lissage stylistique, forfait 50 pages', 'Nadia Chaumet', 'NC', '580 €', '5,0', 24, '10 jours', 'Nouvelle', 'Nouveau'],
            ['Correction', 'Préparation de copie avant maquette', 'Studio Grain', 'SG', '340 €', '4,7', 61, '5 jours', 'Confirmé', ''],
            ['Correction', 'Relecture jeunesse, album et premières lectures', 'Claire Ozanne', 'CO', '190 €', '4,9', 45, '3 jours', 'Experte', 'Répond en 1 h'],
            ['Illustration', 'Couverture illustrée, roman ou essai, 1 piste + retouches', 'Atelier Kess', 'AK', '480 €', '4,9', 41, '10 jours', 'Confirmée', ''],
            ['Illustration', 'Album jeunesse 24 pages, aquarelle originale', 'Atelier Kess', 'AK', '2 200 €', '5,0', 18, '6 semaines', 'Experte', 'Choix de la rédaction'],
            ['Illustration', 'Ornements et lettrines pour recueil de poésie', 'Studio Grain', 'SG', '360 €', '4,6', 22, '12 jours', 'Confirmé', ''],
            ['Traduction', 'Traduction littéraire EN→FR au signe', 'Sofia Renard', 'SR', '18 € / page', '4,9', 33, '3 semaines', 'Experte', ''],
            ['Traduction', 'Traduction ES→FR, nouvelles et roman', 'Sofia Renard', 'SR', '16 € / page', '4,8', 19, '4 semaines', 'Confirmée', ''],
            ['Maquette', 'Maquette intérieure, collection Grands Formats', 'Studio Grain', 'SG', '650 €', '4,8', 47, '3 semaines', 'Confirmé', ''],
            ['Maquette', 'Couverture + quatrième, collection poche', 'Studio Grain', 'SG', '290 €', '4,7', 28, '8 jours', 'Confirmé', ''],
            ['Impression', '300 ex. broché, papier bouffant, dos carré collé', 'Imprimerie Baudry', 'IB', '1 190 €', '4,8', 54, '12 jours', 'Experte', ''],
            ['Presse & com', 'Attachée de presse, premier roman, 6 semaines', 'Hélène Aubry', 'HA', '2 400 €', '4,9', 16, '6 semaines', 'Confirmée', ''],
            ['Audio', 'Narration livre audio, studio fourni', 'Studio Bel Écho', 'BE', '220 € / h', '4,8', 21, 'sur devis', 'Confirmé', ''],
            ['Édition', 'Direction éditoriale d\'un premier roman', 'Éditions La Ligne', 'EL', '1 800 €', '4,7', 11, '2 mois', 'Experte', ''],
            ['Librairie', 'Dépôt et animation en librairie indépendante', 'Librairie Haut-Le-Cœur', 'LH', 'sur devis', '4,6', 9, 'à convenir', 'Confirmée', ''],
        ];

        $out = [];
        foreach ($rows as $i => [$cat, $title, $by, $initials, $price, $rating, $reviews, $delay, $level, $tag]) {
            $out[] = [
                'kind' => 'prestations',
                'kind_label' => 'Prestation',
                'title' => $title,
                'subtitle' => $by,
                'href' => '/prestations/' . slugify($title),
                'cat' => $cat,
                'meta' => $delay . ' · ★ ' . $rating . ' (' . $reviews . ')',
                'price' => $price,
                'thumb' => photo($i % 6),
                'initials' => $initials,
                'level' => $level,
                'tag' => $tag,
                'delay' => $delay,
                'rating' => $rating,
                'reviews' => $reviews,
                'search' => $cat . ' ' . $title . ' ' . $by . ' ' . $tag,
            ];
        }
        return $out;
    }

    /** @return list<array<string, mixed>> */
    public static function providers(): array
    {
        $demo = [
            ['marion-vasseur', 'Marion Vasseur', 'MV', 'Correctrice-relectrice', 'Correction', 'Nantes', 'Romans, essais, polar. Douze ans en maison d\'édition.', ['Roman', 'Polar', 'Essai']],
            ['atelier-virgule', 'Atelier Virgule', 'AV', 'Relecture sur épreuves', 'Correction', 'Paris', 'Dernière passe typographique sur PDF maquetté.', ['Essai', 'Pratique']],
            ['paul-ferrand', 'Paul Ferrand', 'PF', 'Correcteur d\'essais', 'Correction', 'Lille', 'Appareil de notes, normes bibliographiques, documents longs.', ['Essai', 'Sciences humaines']],
            ['nadia-chaumet', 'Nadia Chaumet', 'NC', 'Réécriture et lissage', 'Correction', 'Lyon', 'Style, rythme, resserrement, voix de l\'auteur préservée.', ['Roman', 'Jeunesse']],
            ['atelier-kess', 'Atelier Kess', 'AK', 'Illustration et couverture', 'Illustration', 'Marseille', 'Aquarelle, gouache, visuels originaux sans IA.', ['Jeunesse', 'Poésie']],
            ['sofia-renard', 'Sofia Renard', 'SR', 'Traductrice littéraire', 'Traduction', 'Toulouse', 'EN/ES vers FR, nouvelles et romans contemporains.', ['Roman', 'Essai']],
            ['studio-grain', 'Studio Grain', 'SG', 'Maquette et direction artistique', 'Maquette', 'Bordeaux', 'Intérieurs, collections, ornements, InDesign.', ['Essai', 'Poésie']],
            ['helene-aubry', 'Hélène Aubry', 'HA', 'Attachée de presse', 'Presse & com', 'Paris', 'Lancements, libraires, podcasts, salons.', ['Roman', 'Essai']],
            ['imprimerie-baudry', 'Imprimerie Baudry', 'IB', 'Impression offset et numérique', 'Impression', 'Lyon', 'Broché, papier recyclé, petits et moyens tirages.', ['Pratique']],
            ['studio-bel-echo', 'Studio Bel Écho', 'BE', 'Narration livre audio', 'Audio', 'Nantes', 'Voix, direction, studio, livrable mastering.', ['Livre audio']],
        ];

        $out = [];
        foreach ($demo as [$slug, $name, $initials, $title, $cat, $city, $excerpt, $genres]) {
            $out[] = [
                'kind' => 'prestataires',
                'kind_label' => 'Prestataire',
                'title' => $name,
                'subtitle' => $title . ' · ' . $city,
                'href' => '/prestataires/' . $slug,
                'cat' => $cat,
                'meta' => implode(' · ', $genres),
                'price' => '',
                'thumb' => '',
                'initials' => $initials,
                'excerpt' => $excerpt,
                'city' => $city,
                'genres' => $genres,
                'demo' => true,
                'search' => $name . ' ' . $title . ' ' . $cat . ' ' . $city . ' ' . $excerpt . ' ' . implode(' ', $genres),
            ];
        }

        try {
            foreach (Profile::searchPublished() as $profile) {
                $name = Profile::displayName($profile);
                $trades = $profile['trades'] ?: [];
                $cat = (string) ($trades[0] ?? 'Prestataire');
                $out[] = [
                    'kind' => 'prestataires',
                    'kind_label' => 'Prestataire',
                    'title' => $name,
                    'subtitle' => trim((string) ($profile['title'] ?? 'Prestataire')) . ($profile['city'] ? ' · ' . $profile['city'] : ''),
                    'href' => Profile::publicHref($profile),
                    'cat' => $cat,
                    'meta' => implode(' · ', array_slice($profile['genres'] ?: $trades, 0, 4)),
                    'price' => $profile['hourly_rate'] ? 'à partir de ' . $profile['hourly_rate'] : '',
                    'thumb' => '',
                    'initials' => Profile::initials($profile),
                    'excerpt' => (string) ($profile['presentation'] ?? ''),
                    'city' => (string) ($profile['city'] ?? ''),
                    'genres' => $profile['genres'] ?? [],
                    'live' => true,
                    'search' => $name . ' ' . ($profile['title'] ?? '') . ' ' . implode(' ', $trades) . ' ' . ($profile['city'] ?? '') . ' ' . ($profile['presentation'] ?? '') . ' ' . implode(' ', $profile['genres'] ?? []) . ' ' . implode(' ', $profile['tools'] ?? []),
                ];
            }
        } catch (\Throwable) {
            // Catalogue encore consultable sans base.
        }

        return $out;
    }

    /** @return list<array<string, mixed>> */
    public static function missions(): array
    {
        $demo = [
            ['Recherche correcteur pour essai historique, 240 pages', 'Éditions du Fleuve Noirci', 'Correction', '600 – 900 €', '12 sept.', 'Essai historique, 420 000 signes, notes de bas de page.', ['Essai', 'Notes', 'Norme maison']],
            ['Illustrateur album jeunesse 3-6 ans, 24 pages', 'Camille D., autrice', 'Illustration', '1 800 – 2 500 €', '18 sept.', 'Album couleur, cession 5 ans, 24 planches.', ['Album', 'Couleur', 'Jeunesse']],
            ['Traduction ES→FR d\'un recueil de nouvelles', 'Éditions Pampa', 'Traduction', '3 200 €', '30 sept.', '180 000 signes, littérature contemporaine.', ['Littérature', '180 000 signes']],
            ['Impression 500 ex. broché, papier recyclé', 'Collectif Encre Vive', 'Impression', '1 400 – 2 000 €', '5 oct.', 'Offset, dos carré collé, éco-labels.', ['Offset', 'Éco-labels']],
            ['Attaché de presse, premier roman, sortie janvier', 'Éditions La Ligne', 'Presse & com', '2 000 – 3 000 €', '22 sept.', 'Presse écrite, podcasts, salons.', ['Presse', 'Podcasts']],
            ['Narrateur pour livre audio, 7 h de texte', 'Studio Bel Écho', 'Audio', '2 400 €', '12 oct.', 'Voix féminine, studio fourni.', ['Voix', 'Audio']],
        ];

        $out = [];
        foreach ($demo as [$title, $by, $cat, $budget, $deadline, $excerpt, $tags]) {
            $out[] = [
                'kind' => 'missions',
                'kind_label' => 'Mission',
                'title' => $title,
                'subtitle' => $by . ' · ' . $cat,
                'href' => '/missions/' . slugify($title),
                'cat' => $cat,
                'meta' => $budget . ' · avant le ' . $deadline,
                'price' => $budget,
                'thumb' => '',
                'initials' => '',
                'excerpt' => $excerpt,
                'tags' => $tags,
                'deadline' => $deadline,
                'by' => $by,
                'demo' => true,
                'search' => $title . ' ' . $by . ' ' . $cat . ' ' . $excerpt . ' ' . implode(' ', $tags),
            ];
        }

        try {
            foreach (Mission::open() as $mission) {
                $out[] = [
                    'kind' => 'missions',
                    'kind_label' => 'Mission',
                    'title' => $mission['title'],
                    'subtitle' => $mission['by'] . ' · ' . ($mission['category_name'] ?: 'Mission'),
                    'href' => $mission['href'],
                    'cat' => (string) ($mission['category_name'] ?? ''),
                    'meta' => $mission['budget'] . ' · avant le ' . $mission['deadline_label'],
                    'price' => $mission['budget'],
                    'thumb' => '',
                    'initials' => $mission['initials'],
                    'excerpt' => (string) ($mission['brief'] ?? ''),
                    'tags' => array_filter([$mission['volume'] ?? null, $mission['category_name'] ?? null]),
                    'deadline' => $mission['deadline_label'],
                    'by' => $mission['by'],
                    'live' => true,
                    'search' => $mission['title'] . ' ' . $mission['by'] . ' ' . ($mission['category_name'] ?? '') . ' ' . ($mission['brief'] ?? '') . ' ' . ($mission['volume'] ?? ''),
                ];
            }
        } catch (\Throwable) {
            // Catalogue encore consultable sans base.
        }

        return $out;
    }

    public static function provider(string $slug): ?array
    {
        foreach (self::providers() as $provider) {
            if (str_ends_with((string) $provider['href'], '/' . $slug)) {
                return $provider;
            }
        }
        return null;
    }

    public static function mission(string $slug): ?array
    {
        try {
            $live = Mission::findBySlug($slug);
            if ($live) {
                return $live;
            }
        } catch (\Throwable) {
        }

        foreach (self::missions() as $mission) {
            if (str_ends_with((string) $mission['href'], '/' . $slug)) {
                return $mission;
            }
        }
        return null;
    }

    /** @return list<array<string, mixed>> */
    public static function suggestionsForTrade(string $trade, int $limit = 3): array
    {
        $out = [];
        foreach (self::providers() as $provider) {
            if ($trade !== '' && search_norm((string) $provider['cat']) !== search_norm($trade)) {
                continue;
            }
            $out[] = $provider;
            if (count($out) >= $limit) {
                break;
            }
        }
        return $out;
    }

    /** @param array<string, mixed> $profile */
    public static function profileToPublic(array $profile): array
    {
        $name = Profile::displayName($profile);
        $trades = $profile['trades'] ?: [];
        $tags = array_values(array_unique(array_merge(
            $trades,
            $profile['genres'] ?: [],
            $profile['tools'] ?: []
        )));

        $portfolio = [];
        foreach ($profile['portfolio'] ?? [] as $item) {
            $portfolio[] = [
                'title' => $item['title'],
                'caption' => trim(($item['year'] ?? '') . ($item['year'] ? ' · ' : '') . ($item['kind_label'] ?? '')),
                'description' => $item['description'] ?? '',
                'img' => PortfolioItem::image($item),
                'kind' => $item['kind'] ?? 'creation',
                'kind_label' => $item['kind_label'] ?? 'Création',
            ];
        }

        return [
            'live' => true,
            'name' => $name,
            'initials' => Profile::initials($profile),
            'title' => (string) ($profile['title'] ?? 'Prestataire'),
            'city' => (string) ($profile['city'] ?? ''),
            'level' => (string) ($profile['level'] ?? 'Nouveau'),
            'presentation' => (string) ($profile['presentation'] ?? ''),
            'availability' => (string) ($profile['availability'] ?? ''),
            'hourly_rate' => (string) ($profile['hourly_rate'] ?? ''),
            'rate_note' => (string) ($profile['rate_note'] ?? ''),
            'languages' => (string) ($profile['languages'] ?? ''),
            'languages_list' => $profile['languages_list'] ?? [],
            'trades' => $trades,
            'skills' => $profile['skills'] ?? [],
            'tools' => $profile['tools'] ?? [],
            'genres' => $profile['genres'] ?? [],
            'experiences' => $profile['experiences'] ?? [],
            'education' => $profile['education'] ?? [],
            'portfolio' => $portfolio,
            'tags' => $tags,
            'website' => (string) ($profile['website'] ?? ''),
            'href' => Profile::publicHref($profile),
        ];
    }

    /** @param array<string, mixed> $item */
    private static function score(string $q, array $item): int
    {
        if ($q === '') {
            return 1;
        }
        $needle = search_norm($q);
        $words = preg_split('/\s+/', $needle) ?: [];
        $hay = [
            8 => search_norm((string) ($item['title'] ?? '')),
            5 => search_norm((string) ($item['cat'] ?? '')),
            4 => search_norm((string) ($item['subtitle'] ?? '')),
            2 => search_norm((string) ($item['search'] ?? '')),
        ];

        $score = 0;
        foreach ($hay as $weight => $text) {
            if ($text === $needle) {
                $score += $weight * 6;
                continue;
            }
            if (str_contains($text, $needle)) {
                $score += $weight * 3;
            }
            foreach ($words as $word) {
                if (mb_strlen($word) >= 2 && str_contains($text, $word)) {
                    $score += $weight;
                }
            }
        }
        return $score;
    }
}
