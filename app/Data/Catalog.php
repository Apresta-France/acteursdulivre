<?php

declare(strict_types=1);

namespace Adl\Data;

use Adl\Models\Article;
use Adl\Models\Mission;
use Adl\Models\PortfolioItem;
use Adl\Models\Profile;
use Adl\Models\Service;
use Adl\Models\Setting;
use Adl\Models\Taxonomy;
use Adl\Models\User;

final class Catalog
{
    public const TYPES = [
        'all' => 'Tout',
        'prestations' => 'Prestations',
        'prestataires' => 'Prestataires',
        'missions' => 'Recherches',
    ];

    /** Volume utile pour les métiers quantifiables ; absent = champ masqué (le brief suffit). */
    public const VOLUME_HINTS = [
        'Correction' => ['label' => 'Volume', 'placeholder' => '420 000 signes'],
        'Bêta-lecture' => ['label' => 'Volume', 'placeholder' => '240 pages'],
        'Traduction' => ['label' => 'Volume', 'placeholder' => '80 000 mots'],
        'Maquette' => ['label' => 'Volume', 'placeholder' => '256 pages'],
        'Impression' => ['label' => 'Tirage', 'placeholder' => '500 exemplaires'],
        'Audio' => ['label' => 'Durée', 'placeholder' => '6 heures'],
    ];

    public const BRIEF_HINTS = [
        'Écriture' => 'Genre, volume, accompagnement souhaité (prête-plume, réécriture, coaching), calendrier…',
        'Correction' => 'Genre, état du texte, attentes, contraintes de calendrier…',
        'Bêta-lecture' => 'Genre, public visé, ce que vous attendez de la lecture, calendrier…',
        'Illustration' => 'Couverture ou intérieur, style souhaité, format, références…',
        'Traduction' => 'Langues, genre, public, contraintes éditoriales, calendrier…',
        'Maquette' => 'Format, nombre de pages, contraintes graphiques, calendrier…',
        'Édition' => 'Projet, accompagnement souhaité, calendrier…',
        'Impression' => 'Format, papier, façonnage, quantité, calendrier…',
        'Presse & com' => 'Ouvrage, cibles, actions souhaitées, calendrier…',
        'Librairie' => 'Ouvrage, diffusion souhaitée, zone, calendrier…',
        'Audio' => 'Durée estimée, ton, public, contraintes techniques…',
        'Agent littéraire' => 'Projet, genre, ce que vous attendez d’un accompagnement, calendrier…',
        'Salons' => 'Type d’événement, dates, lieu, public, prestations souhaitées…',
    ];

    public const TRADE_LABELS = [
        'Écriture' => 'Auteurs',
        'Correction' => 'Correcteurs',
        'Bêta-lecture' => 'Bêta-lecteurs',
        'Illustration' => 'Illustrateurs',
        'Traduction' => 'Traducteurs',
        'Maquette' => 'Maquettistes',
        'Édition' => 'Éditeurs',
        'Impression' => 'Imprimeurs',
        'Presse & com' => 'Presse & com',
        'Librairie' => 'Libraires',
        'Audio' => 'Narrateurs audio',
        'Agent littéraire' => 'Agents littéraires',
        'Salons' => 'Salons & événements',
    ];

    public const TRADE_TITLES = [
        'Écriture' => 'Écriture & prête-plume',
        'Correction' => 'Correction & relecture',
        'Bêta-lecture' => 'Bêta-lecture',
        'Illustration' => 'Illustration',
        'Traduction' => 'Traduction',
        'Maquette' => 'Maquette',
        'Édition' => 'Édition',
        'Impression' => 'Impression',
        'Presse & com' => 'Presse & communication',
        'Librairie' => 'Librairie',
        'Audio' => 'Narration audio',
        'Agent littéraire' => 'Agent littéraire',
        'Salons' => 'Salons & événements',
    ];

    public static function tradeTitle(string $trade): string
    {
        return self::TRADE_TITLES[$trade] ?? $trade;
    }

    /** @return list<string> */
    public static function trades(): array
    {
        return Taxonomy::names(Taxonomy::KIND_TRADE);
    }

    /** @return list<string> */
    public static function footerLabels(): array
    {
        $out = [];
        foreach (self::trades() as $trade) {
            $out[] = self::TRADE_LABELS[$trade] ?? $trade;
        }
        return $out;
    }

    /** @return list<string> */
    public static function specialties(): array
    {
        return Taxonomy::names(Taxonomy::KIND_SPECIALTY);
    }

    /** @return array{label: string, placeholder: string}|null */
    public static function volumeHint(string $trade): ?array
    {
        return self::VOLUME_HINTS[$trade] ?? null;
    }

    public static function briefHint(string $trade): string
    {
        return self::BRIEF_HINTS[$trade] ?? 'Attentes, contraintes, calendrier…';
    }

    public static function tradeFromSlug(string $slug): ?string
    {
        $trades = array_values(array_unique(array_merge(
            self::trades(),
            Taxonomy::names(Taxonomy::KIND_TRADE, false)
        )));
        $want = str_replace('-', '', $slug);
        foreach ($trades as $trade) {
            $candidates = array_unique([slugify($trade), slugify(self::TRADE_LABELS[$trade] ?? $trade)]);
            foreach ($candidates as $candidate) {
                if ($candidate === $slug || str_replace('-', '', $candidate) === $want) {
                    return $trade;
                }
            }
        }
        return null;
    }

    /**
     * @return array{
     *   query: string,
     *   type: string,
     *   cat: string,
     *   count: int,
     *   results: list<array<string, mixed>>,
     *   groups: array<string, list<array<string, mixed>>>,
     *   suggestions: list<array<string, mixed>>,
     *   available_only: bool
     * }
     */
    public static function search(string $q, string $type = 'all', string $cat = '', int $limit = 48, bool $availableOnly = false): array
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
            if ($availableOnly && ($item['kind'] ?? '') === 'prestataires' && !empty($item['is_busy'])) {
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

        return [
            'query' => $q,
            'type' => $type,
            'cat' => $cat,
            'count' => count($scored),
            'results' => $results,
            'groups' => $groups,
            'suggestions' => array_slice($results, 0, 8),
            'available_only' => $availableOnly,
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function services(): array
    {
        try {
            return Service::published();
        } catch (\Throwable) {
            return [];
        }
    }

    /** @return list<array<string, mixed>> */
    public static function providers(): array
    {
        $out = [];
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
                    'price' => Profile::formatRateSearch($profile),
                    'thumb' => '',
                    'initials' => Profile::initials($profile),
                    'excerpt' => (string) ($profile['presentation'] ?? ''),
                    'city' => (string) ($profile['city'] ?? ''),
                    'genres' => $profile['genres'] ?? [],
                    'live' => true,
                    'availability_status' => $profile['availability_status'] ?? Profile::STATUS_AVAILABLE,
                    'availability_label' => $profile['availability_label'] ?? Profile::statusLabel($profile),
                    'is_busy' => !empty($profile['is_busy']),
                    'search' => $name . ' ' . ($profile['title'] ?? '') . ' ' . implode(' ', $trades) . ' ' . ($profile['city'] ?? '') . ' ' . ($profile['presentation'] ?? '') . ' ' . implode(' ', $profile['genres'] ?? []) . ' ' . implode(' ', $profile['tools'] ?? []),
                ];
            }
        } catch (\Throwable) {
        }

        return $out;
    }

    /** @return list<array<string, mixed>> */
    public static function missions(): array
    {
        $out = [];
        try {
            foreach (Mission::open() as $mission) {
                $out[] = [
                    'kind' => 'missions',
                    'kind_label' => 'Recherche',
                    'title' => $mission['title'],
                    'subtitle' => $mission['by'] . ' · ' . ($mission['category_name'] ?: 'Recherche'),
                    'href' => $mission['href'],
                    'cat' => (string) ($mission['category_name'] ?? ''),
                    'meta' => $mission['budget'] . (!empty($mission['deadline']) ? ' · avant le ' . $mission['deadline_label'] : ' · échéance à convenir'),
                    'price' => $mission['budget'],
                    'thumb' => '',
                    'initials' => $mission['initials'],
                    'excerpt' => (string) ($mission['brief'] ?? ''),
                    'tags' => array_values(array_filter([$mission['volume'] ?? null, $mission['category_name'] ?? null])),
                    'deadline' => $mission['deadline_label'],
                    'when' => $mission['when'],
                    'applicants' => (int) ($mission['applicants'] ?? 0),
                    'volume' => (string) ($mission['volume'] ?? ''),
                    'by' => $mission['by'],
                    'live' => true,
                    'search' => $mission['title'] . ' ' . $mission['by'] . ' ' . ($mission['category_name'] ?? '') . ' ' . ($mission['brief'] ?? '') . ' ' . ($mission['volume'] ?? ''),
                ];
            }
        } catch (\Throwable) {
        }

        return $out;
    }

    public static function provider(string $slug): ?array
    {
        try {
            $profile = Profile::findBySlug($slug);
            if ($profile && !empty($profile['offers_services'])) {
                return self::profileToPublic($profile);
            }
        } catch (\Throwable) {
        }
        return null;
    }

    public static function mission(string $slug): ?array
    {
        try {
            return Mission::findBySlug($slug);
        } catch (\Throwable) {
            return null;
        }
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

    /** @return list<array<string, mixed>> */
    public static function featuredServices(int $limit = 3, int $offset = 0): array
    {
        $out = [];
        foreach (array_slice(self::services(), $offset, $limit) as $i => $service) {
            $service['homeSlotId'] = 'home-feat-' . ($offset + $i);
            $service['go'] = true;
            $out[] = $service;
        }
        return $out;
    }

    /** @return list<array<string, mixed>> */
    public static function homeMissions(int $limit = 5): array
    {
        $out = [];
        foreach (array_slice(self::missions(), 0, $limit) as $mission) {
            $mission['urgence'] = '';
            $mission['urgenceStyle'] = 'display: none;';
            $mission['avatars'] = [];
            $mission['go'] = true;
            $out[] = $mission;
        }
        return $out;
    }

    /** @return list<array<string, mixed>> */
    public static function tradeCards(): array
    {
        $counts = self::tradeCounts();
        $out = [];
        foreach (self::trades() as $i => $trade) {
            $n = $counts[$trade] ?? 0;
            $out[] = [
                'num' => str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'name' => self::TRADE_LABELS[$trade] ?? $trade,
                'trade' => $trade,
                'count' => format_int($n),
                'countLabel' => $n === 0 ? 'Aucun profil' : ($n . ' profil' . ($n > 1 ? 's' : '')),
                'href' => '/metiers/' . slugify($trade),
            ];
        }
        return $out;
    }

    /** @return array<string, int> */
    public static function tradeCounts(): array
    {
        $counts = array_fill_keys(self::trades(), 0);
        try {
            foreach (Profile::searchPublished() as $profile) {
                foreach ($profile['trades'] ?? [] as $trade) {
                    if (isset($counts[$trade])) {
                        $counts[$trade]++;
                    }
                }
            }
        } catch (\Throwable) {
        }
        return $counts;
    }

    /** @return list<array{v: string, k: string}> */
    public static function homeStats(): array
    {
        try {
            $pros = User::countOfferers();
            $services = Service::countPublished();
            $missions = Mission::countOpen();
            $commission = Setting::get('commission_percent', '8') ?: '8';
        } catch (\Throwable) {
            $pros = $services = $missions = 0;
            $commission = '8';
        }

        return [
            ['v' => format_int($pros), 'k' => $pros > 1 ? 'professionnels du livre inscrits' : 'professionnel du livre inscrit'],
            ['v' => format_int($services), 'k' => $services > 1 ? 'prestations à prix affiché' : 'prestation à prix affiché'],
            ['v' => format_int($missions), 'k' => $missions > 1 ? 'missions ouvertes' : 'mission ouverte'],
            ['v' => $commission . ' %', 'k' => 'dès la 2ᵉ mission, sans abonnement'],
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function journalPreview(int $limit = 3): array
    {
        try {
            $items = Article::preview($limit);
            foreach ($items as &$item) {
                $item['go'] = true;
            }
            return $items;
        } catch (\Throwable) {
            return [];
        }
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
            'avatar_src' => user_avatar_src($profile),
            'title' => (string) ($profile['title'] ?? 'Prestataire'),
            'city' => (string) ($profile['city'] ?? ''),
            'level' => (string) ($profile['level'] ?? 'Nouveau'),
            'presentation' => (string) ($profile['presentation'] ?? ''),
            'availability' => (string) ($profile['availability'] ?? ''),
            'availability_status' => $profile['availability_status'] ?? Profile::STATUS_AVAILABLE,
            'availability_label' => $profile['availability_label'] ?? Profile::statusLabel($profile),
            'availability_summary' => $profile['availability_summary'] ?? Profile::availabilitySummary($profile),
            'is_busy' => !empty($profile['is_busy']),
            'hourly_rate' => (string) ($profile['hourly_rate'] ?? ''),
            'rate_note' => (string) ($profile['rate_note'] ?? ''),
            'rate_kicker' => Profile::rateKicker($profile),
            'rate_is_percent' => Profile::isPercentRate($profile),
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
            'is_founder' => !empty($profile['is_founder']),
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
