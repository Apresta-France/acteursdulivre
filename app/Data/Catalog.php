<?php

declare(strict_types=1);

namespace Adl\Data;

use Adl\Models\Article;
use Adl\Models\Mission;
use Adl\Models\PortfolioItem;
use Adl\Models\Profile;
use Adl\Models\Review;
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
        'Lecture éditoriale' => ['label' => 'Volume', 'placeholder' => '240 pages'],
        'Iconographie' => ['label' => 'Volume', 'placeholder' => '40 visuels'],
        'Reliure' => ['label' => 'Tirage', 'placeholder' => '30 exemplaires'],
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
        'Iconographie' => 'Ouvrage, nombre de visuels, droits souhaités, sources, calendrier…',
        'Lecture éditoriale' => 'Genre, public, ce que vous attendez du rapport, calendrier…',
        'Photographie' => 'Usage (portrait, ouvrage, reportage), format, droits, calendrier…',
        'Reliure' => 'Type de reliure, quantité, matériaux, calendrier…',
        'Juridique' => 'Type d’acte (contrat, cession, litige), ouvrage, calendrier…',
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
        'Iconographie' => 'Iconographes',
        'Lecture éditoriale' => 'Lecteurs éditoriaux',
        'Photographie' => 'Photographes',
        'Reliure' => 'Relieurs',
        'Juridique' => 'Juristes',
    ];

    /** @var array<string, string> métier canonique => fichier de vignette par défaut */
    public const COVER_STEMS = [
        'Écriture' => 'auteurs',
        'Correction' => 'correcteurs',
        'Bêta-lecture' => 'beta-lecteurs',
        'Illustration' => 'illustrateurs',
        'Traduction' => 'traducteurs',
        'Maquette' => 'maquettistes',
        'Édition' => 'editeurs',
        'Impression' => 'imprimeurs',
        'Presse & com' => 'presse-communication',
        'Librairie' => 'libraires',
        'Audio' => 'narrateurs-audio',
        'Agent littéraire' => 'agents-litteraires',
        'Salons' => 'salons-evenements',
        'Iconographie' => 'iconographes',
        'Lecture éditoriale' => 'lecteurs-editoriaux',
        'Photographie' => 'photographes',
        'Reliure' => 'relieurs',
        'Juridique' => 'juristes',
    ];

    public const DELAYS = [
        'week' => 'Moins d\'une semaine',
        'mid' => '1 à 3 semaines',
        'month' => 'Plus d\'un mois',
    ];

    public const LEVELS = [
        'expert' => 'Experte / Expert',
        'confirme' => 'Confirmé',
        'nouveau' => 'Nouveau',
    ];

    public const TRUST = [
        'verified' => 'Profil vérifié',
        'rated' => 'Avis 4,5 et plus',
        'available' => 'Disponibles uniquement',
    ];

    public const BUDGET_MIN = 200;
    public const BUDGET_MAX = 4000;

    /** @var list<string> */
    public const HOME_QUICK_PREFERRED = [
        'Correction de roman',
        'Bêta-lecture',
        'Couverture illustrée',
        'Impression 300 ex.',
        'Traduction EN→FR',
    ];

    /** @var array<string, true> */
    private const SEARCH_STOP = [
        'de' => true, 'du' => true, 'des' => true, 'le' => true, 'la' => true, 'les' => true,
        'un' => true, 'une' => true, 'et' => true, 'ou' => true, 'pour' => true, 'd' => true,
        'l' => true, 'au' => true, 'aux' => true, 'a' => true, 'en' => true, 'ex' => true,
        'the' => true, 'of' => true,
    ];

    /** @var array<string, list<string>> */
    private const SEARCH_SYNONYMS = [
        'couverture' => ['illustration'],
        'illustre' => ['illustration'],
        'illustree' => ['illustration'],
        'illustrateur' => ['illustration'],
        'illustratrice' => ['illustration'],
        'correcteur' => ['correction'],
        'correctrice' => ['correction'],
        'relecture' => ['correction'],
        'orthotypo' => ['correction'],
        'imprimeur' => ['impression'],
        'imprimerie' => ['impression'],
        'tirage' => ['impression'],
        'exemplaire' => ['impression'],
        'exemplaires' => ['impression'],
        'offset' => ['impression'],
        'traducteur' => ['traduction'],
        'traductrice' => ['traduction'],
        'anglais' => ['traduction'],
        'francais' => ['traduction'],
        'beta' => ['beta lecture'],
        'maquettiste' => ['maquette'],
        'auteur' => ['ecriture'],
        'ecrivain' => ['ecriture'],
    ];

    /**
     * Slug d’intention de recherche (souvent au féminin) pour les pages ville.
     * Ex. /correctrice/paris
     *
     * @var array<string, array{slug: string, one: string, many: string, article: string}>
     */
    public const TRADE_GEO = [
        'Écriture' => ['slug' => 'auteure', 'one' => 'Auteure', 'many' => 'Auteures', 'article' => 'une'],
        'Correction' => ['slug' => 'correctrice', 'one' => 'Correctrice', 'many' => 'Correctrices', 'article' => 'une'],
        'Bêta-lecture' => ['slug' => 'beta-lectrice', 'one' => 'Bêta-lectrice', 'many' => 'Bêta-lectrices', 'article' => 'une'],
        'Illustration' => ['slug' => 'illustratrice', 'one' => 'Illustratrice', 'many' => 'Illustratrices', 'article' => 'une'],
        'Traduction' => ['slug' => 'traductrice', 'one' => 'Traductrice', 'many' => 'Traductrices', 'article' => 'une'],
        'Maquette' => ['slug' => 'maquettiste', 'one' => 'Maquettiste', 'many' => 'Maquettistes', 'article' => 'un'],
        'Édition' => ['slug' => 'editrice', 'one' => 'Éditrice', 'many' => 'Éditrices', 'article' => 'une'],
        'Impression' => ['slug' => 'imprimeur', 'one' => 'Imprimeur', 'many' => 'Imprimeurs', 'article' => 'un'],
        'Presse & com' => ['slug' => 'attachee-de-presse', 'one' => 'Attachée de presse', 'many' => 'Attachées de presse', 'article' => 'une'],
        'Librairie' => ['slug' => 'libraire', 'one' => 'Libraire', 'many' => 'Libraires', 'article' => 'un'],
        'Audio' => ['slug' => 'narratrice-audio', 'one' => 'Narratrice audio', 'many' => 'Narratrices audio', 'article' => 'une'],
        'Agent littéraire' => ['slug' => 'agente-litteraire', 'one' => 'Agente littéraire', 'many' => 'Agentes littéraires', 'article' => 'une'],
        'Salons' => ['slug' => 'salon-du-livre', 'one' => 'Salon du livre', 'many' => 'Salons du livre', 'article' => 'un'],
        'Iconographie' => ['slug' => 'iconographe', 'one' => 'Iconographe', 'many' => 'Iconographes', 'article' => 'un'],
        'Lecture éditoriale' => ['slug' => 'lectrice-editoriale', 'one' => 'Lectrice éditoriale', 'many' => 'Lectrices éditoriales', 'article' => 'une'],
        'Photographie' => ['slug' => 'photographe', 'one' => 'Photographe', 'many' => 'Photographes', 'article' => 'un'],
        'Reliure' => ['slug' => 'relieuse', 'one' => 'Relieuse', 'many' => 'Relieuses', 'article' => 'une'],
        'Juridique' => ['slug' => 'juriste', 'one' => 'Juriste', 'many' => 'Juristes', 'article' => 'un'],
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
        'Iconographie' => 'Iconographie',
        'Lecture éditoriale' => 'Lecture éditoriale',
        'Photographie' => 'Photographie',
        'Reliure' => 'Reliure',
        'Juridique' => 'Juridique & droits d\'auteur',
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

    /** @return list<array{label: string, href: string}> */
    public static function footerMetiers(): array
    {
        $out = [];
        foreach (self::trades() as $trade) {
            $out[] = [
                'label' => self::TRADE_LABELS[$trade] ?? $trade,
                'href' => self::tradePath($trade),
            ];
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

    public static function tradePath(string $trade): string
    {
        return '/metiers/' . slugify($trade);
    }

    public static function tradeGeoSlug(string $trade): string
    {
        return self::TRADE_GEO[$trade]['slug'] ?? slugify($trade);
    }

    public static function tradeGeoLabel(string $trade): string
    {
        return self::TRADE_GEO[$trade]['one'] ?? (self::TRADE_LABELS[$trade] ?? $trade);
    }

    public static function tradeGeoLabelPlural(string $trade): string
    {
        return self::TRADE_GEO[$trade]['many'] ?? (self::TRADE_LABELS[$trade] ?? $trade);
    }

    public static function tradeGeoArticle(string $trade): string
    {
        return self::TRADE_GEO[$trade]['article'] ?? 'un';
    }

    public static function tradeCityPath(string $trade, string $citySlug): string
    {
        $citySlug = Cities::normalizeSlug($citySlug);
        if ($citySlug === '') {
            return self::tradePath($trade);
        }
        return '/' . self::tradeGeoSlug($trade) . '/' . $citySlug;
    }

    public static function typePath(string $type): string
    {
        return match ($type) {
            'prestations' => '/prestations',
            'prestataires' => '/prestataires',
            'missions' => '/missions',
            default => '/recherche',
        };
    }

    /** @return array<string, string> alias lisible => métier canonique */
    public static function tradeAliases(): array
    {
        return [
            'Auteur' => 'Écriture',
            'Auteure' => 'Écriture',
            'Auteurs' => 'Écriture',
            'Auteures' => 'Écriture',
            'Auteurs & prête-plume' => 'Écriture',
            'Prête-plume' => 'Écriture',
            'Réécriture' => 'Écriture',
            'Correcteur' => 'Correction',
            'Correctrice' => 'Correction',
            'Correcteurs' => 'Correction',
            'Correctrices' => 'Correction',
            'Correction orthotypo' => 'Correction',
            'Préparation de copie' => 'Correction',
            'Bêta-lecteur' => 'Bêta-lecture',
            'Bêta-lectrice' => 'Bêta-lecture',
            'Bêta-lecteurs' => 'Bêta-lecture',
            'Bêta-lectrices' => 'Bêta-lecture',
            'Illustrateur' => 'Illustration',
            'Illustratrice' => 'Illustration',
            'Illustrateurs' => 'Illustration',
            'Illustratrices' => 'Illustration',
            'Illustration & couverture' => 'Illustration',
            'Couverture' => 'Illustration',
            'Traducteur' => 'Traduction',
            'Traductrice' => 'Traduction',
            'Traducteurs' => 'Traduction',
            'Traductrices' => 'Traduction',
            'Traduction littéraire' => 'Traduction',
            'Maquettiste' => 'Maquette',
            'Maquettistes' => 'Maquette',
            'Maquette intérieure' => 'Maquette',
            'Direction artistique' => 'Maquette',
            'Éditeur' => 'Édition',
            'Éditrice' => 'Édition',
            'Éditeurs' => 'Édition',
            'Éditrices' => 'Édition',
            'Édition & direction de collection' => 'Édition',
            'Dépôt légal & ISBN' => 'Édition',
            'Imprimeur' => 'Impression',
            'Imprimeurs' => 'Impression',
            'Impression offset' => 'Impression',
            'Impression numérique' => 'Impression',
            'Tirage' => 'Impression',
            'Exemplaire' => 'Impression',
            'Exemplaires' => 'Impression',
            'Iconographe' => 'Iconographie',
            'Iconographes' => 'Iconographie',
            'Recherche iconographique' => 'Iconographie',
            'Lecteur éditorial' => 'Lecture éditoriale',
            'Lectrice éditoriale' => 'Lecture éditoriale',
            'Lecteurs éditoriaux' => 'Lecture éditoriale',
            'Lectrices éditoriales' => 'Lecture éditoriale',
            'Comité de lecture' => 'Lecture éditoriale',
            'Évaluation de manuscrit' => 'Lecture éditoriale',
            'Photographe' => 'Photographie',
            'Photographes' => 'Photographie',
            'Photo d\'auteur' => 'Photographie',
            'Relieur' => 'Reliure',
            'Relieuse' => 'Reliure',
            'Relieurs' => 'Reliure',
            'Relieuses' => 'Reliure',
            'Reliure & finitions' => 'Reliure',
            'Reliure d\'art' => 'Reliure',
            'Juriste' => 'Juridique',
            'Juristes' => 'Juridique',
            'Droits d\'auteur' => 'Juridique',
            'Contrats & droits d\'auteur' => 'Juridique',
            'Contrats éditoriaux' => 'Juridique',
            'Presse' => 'Presse & com',
            'Attaché de presse' => 'Presse & com',
            'Attachée de presse' => 'Presse & com',
            'Attachés de presse' => 'Presse & com',
            'Attachées de presse' => 'Presse & com',
            'Réseaux sociaux & communauté' => 'Presse & com',
            'Libraire' => 'Librairie',
            'Libraires' => 'Librairie',
            'Diffusion en librairie' => 'Librairie',
            'Vente en ligne' => 'Librairie',
            'Narrateur' => 'Audio',
            'Narratrice' => 'Audio',
            'Narrateurs' => 'Audio',
            'Narratrices' => 'Audio',
            'Narrateurs audio' => 'Audio',
            'Narratrice audio' => 'Audio',
            'Narratrices audio' => 'Audio',
            'Livre audio' => 'Audio',
            'Livre audio & narration' => 'Audio',
            'Agent littéraire' => 'Agent littéraire',
            'Agente littéraire' => 'Agent littéraire',
            'Agents littéraires' => 'Agent littéraire',
            'Agentes littéraires' => 'Agent littéraire',
            'Salon' => 'Salons',
            'Salon du livre' => 'Salons',
            'Salons & événements' => 'Salons',
            'Salons & rencontres' => 'Salons',
            'Ateliers & médiation' => 'Salons',
        ];
    }

    /** @return list<array{group: string, items: list<array{label: string, href: string}>}> */
    public static function megaGroups(): array
    {
        $groups = [
            'Écrire & corriger' => [
                'Auteurs & prête-plume' => 'Écriture',
                'Correction orthotypo' => 'Correction',
                'Bêta-lecture' => 'Bêta-lecture',
                'Lecture éditoriale' => 'Lecture éditoriale',
                'Préparation de copie' => 'Correction',
                'Réécriture' => 'Écriture',
                'Traduction littéraire' => 'Traduction',
            ],
            'Fabriquer' => [
                'Illustration & couverture' => 'Illustration',
                'Iconographie' => 'Iconographie',
                'Photographie' => 'Photographie',
                'Maquette intérieure' => 'Maquette',
                'Impression offset' => 'Impression',
                'Reliure & finitions' => 'Reliure',
            ],
            'Éditer & diffuser' => [
                'Édition & direction de collection' => 'Édition',
                'Agents littéraires' => 'Agent littéraire',
                'Contrats & droits d\'auteur' => 'Juridique',
                'Dépôt légal & ISBN' => 'Édition',
                'Diffusion en librairie' => 'Librairie',
                'Vente en ligne' => 'Librairie',
            ],
            'Faire vivre le livre' => [
                'Attachés de presse' => 'Presse & com',
                'Réseaux sociaux & communauté' => 'Presse & com',
                'Livre audio & narration' => 'Audio',
                'Salons & rencontres' => 'Salons',
                'Ateliers & médiation' => 'Salons',
            ],
        ];
        $out = [];
        foreach ($groups as $group => $items) {
            $links = [];
            foreach ($items as $label => $trade) {
                $links[] = ['label' => $label, 'href' => self::tradePath($trade)];
            }
            $out[] = ['group' => $group, 'items' => $links];
        }
        return $out;
    }

    public static function tradeFromSlug(string $slug): ?string
    {
        $slug = slugify($slug);
        if ($slug === '') {
            return null;
        }
        try {
            $trades = array_values(array_unique(array_merge(
                self::trades(),
                Taxonomy::names(Taxonomy::KIND_TRADE, false)
            )));
        } catch (\Throwable) {
            $trades = Profile::TRADES;
        }
        $want = str_replace('-', '', $slug);
        foreach ($trades as $trade) {
            $candidates = array_unique([
                slugify($trade),
                slugify(self::TRADE_LABELS[$trade] ?? $trade),
                slugify(self::TRADE_TITLES[$trade] ?? $trade),
                self::tradeGeoSlug($trade),
            ]);
            foreach ($candidates as $candidate) {
                if ($candidate === $slug || str_replace('-', '', $candidate) === $want) {
                    return $trade;
                }
            }
        }
        foreach (self::tradeAliases() as $alias => $trade) {
            $candidate = slugify($alias);
            if ($candidate === $slug || str_replace('-', '', $candidate) === $want) {
                return $trade;
            }
        }
        return null;
    }

    public static function resolveTrade(string $text): ?string
    {
        return self::tradeFromSlug(slugify(trim($text)));
    }

    public static function coverStem(string $category): string
    {
        $trade = self::resolveTrade($category);
        if ($trade === null) {
            return '';
        }
        return self::COVER_STEMS[$trade] ?? '';
    }

    /**
     * URL publique propre, ou null si la requête (texte libre, dispo…) doit rester une recherche.
     */
    public static function redirectPath(string $currentPath, string $q, string $type, string $cat, bool $availableOnly, array $filters = []): ?string
    {
        if ($availableOnly || self::hasFacetFilters($filters)) {
            return null;
        }
        $type = ($type === '' || !array_key_exists($type, self::TYPES)) ? 'all' : $type;
        $city = Cities::canonicalArea((string) ($filters['city'] ?? ''));
        $trade = self::resolveTrade($cat);
        if ($trade === null && $q !== '') {
            $trade = self::tradeFromQuery($q);
        }
        if ($city === '' && $q !== '') {
            $city = self::cityFromQuery($q, $trade);
        }
        if ($type === 'all' && $trade !== null && $city !== '' && self::tradeCityHasResults($trade, $city)) {
            $target = self::tradeCityPath($trade, $city);
            return $target !== $currentPath ? $target : null;
        }
        if ($type === 'all' && $trade !== null && $city === '' && ($q === '' || self::resolveTrade($q) === $trade)) {
            $target = self::tradePath($trade);
            return $target !== $currentPath ? $target : null;
        }
        if ($q !== '' || $cat !== '' || $city !== '') {
            return null;
        }
        $hub = self::typePath($type);
        return $hub !== $currentPath ? $hub : null;
    }

    public static function tradeFromQuery(string $q): ?string
    {
        $direct = self::resolveTrade($q);
        if ($direct !== null) {
            return $direct;
        }
        $found = self::queryTrades($q);
        return count($found) === 1 ? $found[0] : null;
    }

    public static function cityFromQuery(string $q, ?string $knownTrade = null): string
    {
        $q = trim($q);
        if ($q === '') {
            return '';
        }
        $rest = $q;
        $trades = $knownTrade !== null ? [$knownTrade] : self::queryTrades($q);
        foreach ($trades as $trade) {
            $labels = [
                $trade,
                self::TRADE_LABELS[$trade] ?? '',
                self::TRADE_TITLES[$trade] ?? '',
                self::tradeGeoLabel($trade),
                self::tradeGeoLabelPlural($trade),
                self::tradeGeoSlug($trade),
            ];
            foreach (self::tradeAliases() as $alias => $canonical) {
                if ($canonical === $trade) {
                    $labels[] = $alias;
                }
            }
            foreach (self::SEARCH_SYNONYMS as $word => $targets) {
                foreach ($targets as $target) {
                    if (self::resolveTrade((string) $target) === $trade) {
                        $labels[] = $word;
                    }
                }
            }
            foreach (array_unique(array_filter($labels)) as $label) {
                $pattern = '/' . preg_quote((string) $label, '/') . '/iu';
                $rest = trim((string) preg_replace($pattern, ' ', $rest));
            }
        }
        $rest = trim((string) preg_replace('/\b(a|à|de|du|des|en|sur|au|aux|le|la|les)\b/iu', ' ', $rest));
        $kept = [];
        foreach (preg_split('/\s+/', $rest) ?: [] as $token) {
            if ($token !== '' && !self::isNonCityToken($token)) {
                $kept[] = $token;
            }
        }
        $rest = implode(' ', $kept);
        if ($rest === '') {
            return '';
        }
        return Cities::guessFromQuery($rest);
    }

    private static function isNonCityToken(string $text): bool
    {
        if (self::resolveTrade($text) !== null) {
            return true;
        }
        $slug = slugify($text);
        $norm = search_norm($text);
        if ($slug === '' || isset(self::SEARCH_STOP[$slug])) {
            return true;
        }
        foreach (array_merge(Profile::GENRES, self::specialties()) as $label) {
            $label = (string) $label;
            if ($label !== '' && (slugify($label) === $slug || search_norm($label) === $norm)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{
     *   query: string,
     *   type: string,
     *   cat: string,
     *   count: int,
     *   results: list<array<string, mixed>>,
     *   groups: array<string, list<array<string, mixed>>>,
     *   suggestions: list<array<string, mixed>>,
     *   available_only: bool,
     *   filters: array<string, mixed>,
     *   facets: array<string, list<array{v: string, l: string, n: int}>>,
     *   city: string
     * }
     */
    public static function search(string $q, string $type = 'all', string $cat = '', int $limit = 48, bool $availableOnly = false, array $filters = []): array
    {
        $type = array_key_exists($type, self::TYPES) ? $type : 'all';
        $filters = self::normalizeFilters($filters, $availableOnly);
        if ($type === 'missions') {
            $filters['city'] = '';
        }

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

        $pool = [];
        foreach ($items as $item) {
            $item = self::decorate($item);
            if ($cat !== '' && !self::itemHasTrade($item, [$cat])) {
                continue;
            }
            $score = self::score($q, $item);
            if ($q !== '' && $score <= 0) {
                continue;
            }
            $item['score'] = $score;
            $pool[] = $item;
        }

        $city = (string) ($filters['city'] ?? '');
        if ($city !== '') {
            $pool = array_values(array_filter($pool, static function (array $item) use ($city): bool {
                if (($item['kind'] ?? '') === 'missions') {
                    return false;
                }
                return Cities::itemMatches($item, $city);
            }));
        }

        $facets = self::facetOptions($pool, $type);
        $scored = [];
        foreach ($pool as $item) {
            if (!self::matchesFacets($item, $filters)) {
                continue;
            }
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
            'available_only' => $availableOnly || in_array('available', $filters['trust'], true),
            'filters' => $filters,
            'facets' => $facets,
            'city' => (string) ($filters['city'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $raw
     * @return array{kinds: list<string>, metiers: list<string>, specs: list<string>, delays: list<string>, levels: list<string>, trust: list<string>, bmin: ?int, bmax: ?int, city: string}
     */
    public static function normalizeFilters(array $raw, bool $availableOnly = false): array
    {
        $pick = static function (array $values, array $allowed): array {
            $out = [];
            foreach ($values as $value) {
                $value = (string) $value;
                if (isset($allowed[$value]) || in_array($value, $allowed, true)) {
                    $out[] = $value;
                }
            }
            return array_values(array_unique($out));
        };

        $trust = $pick($raw['trust'] ?? [], array_keys(self::TRUST));
        if ($availableOnly && !in_array('available', $trust, true)) {
            $trust[] = 'available';
        }

        $bmin = isset($raw['bmin']) && $raw['bmin'] !== '' && $raw['bmin'] !== null ? (int) $raw['bmin'] : null;
        $bmax = isset($raw['bmax']) && $raw['bmax'] !== '' && $raw['bmax'] !== null ? (int) $raw['bmax'] : null;

        return [
            'kinds' => $pick($raw['kinds'] ?? [], ['prestations', 'prestataires', 'missions']),
            'metiers' => array_values(array_filter(array_map('strval', $raw['metiers'] ?? []))),
            'specs' => array_values(array_filter(array_map('strval', $raw['specs'] ?? []))),
            'delays' => $pick($raw['delays'] ?? [], array_keys(self::DELAYS)),
            'levels' => $pick($raw['levels'] ?? [], array_keys(self::LEVELS)),
            'trust' => $trust,
            'bmin' => $bmin,
            'bmax' => $bmax,
            'city' => Cities::canonicalArea((string) ($raw['city'] ?? '')),
        ];
    }

    public static function hasFacetFilters(array $filters): bool
    {
        $filters = self::normalizeFilters($filters);
        return $filters['kinds'] !== []
            || $filters['metiers'] !== []
            || $filters['specs'] !== []
            || $filters['delays'] !== []
            || $filters['levels'] !== []
            || $filters['trust'] !== []
            || ($filters['bmin'] !== null && $filters['bmin'] !== self::BUDGET_MIN)
            || ($filters['bmax'] !== null && $filters['bmax'] !== self::BUDGET_MAX);
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
                $reviews = Review::statsForUser((int) $profile['user_id']);
                $location = $profile['location_label'] ?? Profile::locationLabel($profile);
                $out[] = [
                    'kind' => 'prestataires',
                    'kind_label' => 'Prestataire',
                    'title' => $name,
                    'subtitle' => trim((string) ($profile['title'] ?? 'Prestataire') . ($location !== '' ? ' · ' . $location : '')),
                    'href' => Profile::publicHref($profile),
                    'cat' => $cat,
                    'trades' => $trades,
                    'meta' => implode(' · ', array_slice($profile['genres'] ?: $trades, 0, 4)),
                    'price' => Profile::formatRateSearch($profile),
                    'hourly_rate' => (string) ($profile['hourly_rate'] ?? ''),
                    'thumb' => '',
                    'initials' => Profile::initials($profile),
                    'excerpt' => (string) ($profile['presentation'] ?? ''),
                    'city' => (string) ($profile['city'] ?? ''),
                    'city_slug' => (string) ($profile['city_slug'] ?? ''),
                    'city_area_slug' => (string) ($profile['city_area_slug'] ?? ''),
                    'genres' => $profile['genres'] ?? [],
                    'level' => (string) ($profile['level'] ?? 'Nouveau'),
                    'verified' => ($profile['verification_status'] ?? '') === Profile::VERIFY_VERIFIED,
                    'rating' => $reviews['avg'] ?? '',
                    'rating_count' => (int) ($reviews['count'] ?? 0),
                    'live' => true,
                    'availability_status' => $profile['availability_status'] ?? Profile::STATUS_AVAILABLE,
                    'availability_label' => $profile['availability_label'] ?? Profile::statusLabel($profile),
                    'is_busy' => !empty($profile['is_busy']),
                    'search' => $name . ' ' . ($profile['title'] ?? '') . ' ' . implode(' ', $trades) . ' ' . $location . ' ' . ($profile['presentation'] ?? '') . ' ' . ($profile['does'] ?? '') . ' ' . ($profile['does_not'] ?? '') . ' ' . implode(' ', $profile['genres'] ?? []) . ' ' . implode(' ', $profile['tools'] ?? []) . ' ' . ($profile['work_mode_label'] ?? '') . ' ' . ($profile['response_time_label'] ?? ''),
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
                    'budget' => $mission['budget'],
                    'meta' => !empty($mission['deadline'])
                        ? 'avant le ' . $mission['deadline_label']
                        : 'échéance à convenir',
                    'price' => $mission['budget'],
                    'thumb' => '',
                    'initials' => $mission['initials'],
                    'excerpt' => (string) ($mission['brief'] ?? ''),
                    'tags' => array_values(array_filter([(string) ($mission['volume'] ?? '')])),
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
            if ($trade !== '' && !self::itemHasTrade($provider, [$trade])) {
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
        $unique = [];
        $seenUsers = [];
        foreach (self::services() as $service) {
            $userId = (int) ($service['user_id'] ?? 0);
            if ($userId > 0) {
                if (isset($seenUsers[$userId])) {
                    continue;
                }
                $seenUsers[$userId] = true;
            }
            $unique[] = $service;
        }

        $out = [];
        foreach (array_slice($unique, $offset, $limit) as $i => $service) {
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
                'href' => self::tradePath($trade),
            ];
        }
        return $out;
    }

    public static function tradeCityHasResults(string $trade, string $citySlug): bool
    {
        $citySlug = Cities::normalizeSlug($citySlug);
        if ($trade === '' || $citySlug === '') {
            return false;
        }
        $pairs = self::tradeCityPairs($trade);
        return isset($pairs[$trade][$citySlug]);
    }

    /**
     * Couples métier × ville (aire) qui ont au moins un prestataire ou une prestation.
     *
     * @return array<string, array<string, array{slug: string, label: string, n: int}>>
     */
    public static function tradeCityPairs(?string $onlyTrade = null): array
    {
        static $all = null;
        if ($all === null) {
            $all = self::buildTradeCityPairs();
        }
        if ($onlyTrade === null) {
            return $all;
        }
        return isset($all[$onlyTrade]) ? [$onlyTrade => $all[$onlyTrade]] : [];
    }

    /**
     * @return array<string, array<string, array{slug: string, label: string, n: int}>>
     */
    private static function buildTradeCityPairs(): array
    {
        $out = [];
        $add = static function (string $trade, array $item) use (&$out): void {
            $area = Cities::itemAreaSlug($item);
            if ($trade === '' || $area === '') {
                return;
            }
            $label = Cities::labelForSlug($area, false);
            if (!isset($out[$trade][$area])) {
                $out[$trade][$area] = ['slug' => $area, 'label' => $label, 'n' => 0];
            }
            $out[$trade][$area]['n']++;
        };

        foreach (self::providers() as $provider) {
            foreach ($provider['trades'] ?? [] as $trade) {
                $resolved = self::resolveTrade((string) $trade);
                if ($resolved === null) {
                    continue;
                }
                $add($resolved, $provider);
            }
        }
        foreach (self::services() as $service) {
            $trade = self::resolveTrade((string) ($service['cat'] ?? ''));
            if ($trade === null) {
                continue;
            }
            $add($trade, $service);
        }

        foreach ($out as $trade => $cities) {
            uasort($out[$trade], static function (array $a, array $b): int {
                return ($b['n'] <=> $a['n']) ?: strcasecmp($a['label'], $b['label']);
            });
        }

        return $out;
    }

    /**
     * @return list<array{slug: string, label: string, n: int, href: string}>
     */
    public static function citiesForTrade(string $trade): array
    {
        $out = [];
        foreach (self::tradeCityPairs($trade)[$trade] ?? [] as $row) {
            $out[] = [
                'slug' => $row['slug'],
                'label' => $row['label'],
                'n' => $row['n'],
                'href' => self::tradeCityPath($trade, $row['slug']),
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

    /**
     * Suggestions d'accueil : formulations réellement cherchées.
     * On garde les exemples du hero, et on complète avec des requêtes du catalogue.
     *
     * @return list<string>
     */
    public static function homeQuick(int $limit = 5): array
    {
        return array_slice(self::HOME_QUICK_PREFERRED, 0, $limit);
    }

    /** @return list<array{v: string, k: string}> */
    public static function missionsBandStats(): array
    {
        try {
            $commission = Setting::get('commission_percent', '8') ?: '8';
        } catch (\Throwable) {
            $commission = '8';
        }

        return [
            ['v' => '0 €', 'k' => 'pour candidater : aucune commission sur la candidature'],
            ['v' => '1ʳᵉ', 'k' => 'mission offerte au prestataire, sans commission'],
            ['v' => $commission . ' %', 'k' => 'dès la 2ᵉ mission réalisée, sur le hors-taxe, sans abonnement'],
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function homeReviews(int $limit = 3): array
    {
        try {
            return Review::recentPublic($limit);
        } catch (\Throwable) {
            return [];
        }
    }

    /** @return list<array{name: string, role: string, initials: string}> */
    public static function equipe(): array
    {
        return [
            ['name' => 'Julien LARZILLIÈRE', 'role' => 'Président — TERCIUM, EDITIONS TESSERACT', 'initials' => 'JL'],
            ['name' => 'Guillaume REYNAERT', 'role' => 'Directeur général — GPR PROJECTS', 'initials' => 'GR'],
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
            $profile['genres'] ?: []
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
            'city_slug' => (string) ($profile['city_slug'] ?? ''),
            'city_area_slug' => (string) ($profile['city_area_slug'] ?? ''),
            'location_label' => (string) ($profile['location_label'] ?? Profile::locationLabel($profile)),
            'work_mode' => (string) ($profile['work_mode'] ?? ''),
            'work_mode_label' => (string) ($profile['work_mode_label'] ?? Profile::workModeLabel($profile)),
            'response_time' => (string) ($profile['response_time'] ?? ''),
            'response_time_label' => (string) ($profile['response_time_label'] ?? Profile::responseTimeLabel($profile)),
            'member_since' => (string) ($profile['member_since'] ?? ''),
            'member_since_label' => (string) ($profile['member_since_label'] ?? Profile::memberSinceLabel($profile['member_since'] ?? null)),
            'level' => (string) ($profile['level'] ?? 'Nouveau'),
            'presentation' => (string) ($profile['presentation'] ?? ''),
            'does' => (string) ($profile['does'] ?? ''),
            'does_not' => (string) ($profile['does_not'] ?? ''),
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
            'socials' => $profile['socials'] ?? [],
            'href' => Profile::publicHref($profile),
            'is_founder' => !empty($profile['is_founder']),
            'user_id' => (int) ($profile['user_id'] ?? 0),
            'is_verified' => !empty($profile['is_verified']),
            'reviews' => Review::forTarget((int) ($profile['user_id'] ?? 0), 8),
            'review_stats' => Review::statsForUser((int) ($profile['user_id'] ?? 0)),
            'services' => array_values(array_filter(
                Service::forUser((int) ($profile['user_id'] ?? 0)),
                static fn (array $service): bool => ($service['status'] ?? '') === 'published'
            )),
        ];
    }

    /** @param array<string, mixed> $item */
    private static function decorate(array $item): array
    {
        $item['delay_bucket'] = self::delayBucket((string) ($item['delay'] ?? $item['deadline'] ?? ''));
        $item['price_num'] = self::priceAmount($item);
        $item['rating_num'] = self::ratingAmount($item['rating'] ?? 0);
        $item['verified'] = !empty($item['verified']);
        $item['level_key'] = self::levelKey((string) ($item['level'] ?? ''));
        $specs = [];
        foreach (array_merge(
            [(string) ($item['specialty'] ?? '')],
            is_array($item['genres'] ?? null) ? $item['genres'] : []
        ) as $spec) {
            $spec = trim((string) $spec);
            if ($spec !== '') {
                $specs[] = $spec;
            }
        }
        $item['specialties'] = $specs !== [] ? array_values(array_unique($specs)) : [Taxonomy::GLOBAL_NAME];
        if (($item['kind'] ?? '') === 'prestations' && empty($item['thumb']) && empty($item['cover'])) {
            $item['cover'] = service_brand_cover_url((string) ($item['cat'] ?? ''));
        }
        return $item;
    }

    /** @param array<string, mixed> $item */
    private static function matchesFacets(array $item, array $filters): bool
    {
        $city = (string) ($filters['city'] ?? '');
        if ($city !== '') {
            if ((string) ($item['kind'] ?? '') === 'missions') {
                return false;
            }
            if (!Cities::itemMatches($item, $city)) {
                return false;
            }
        }
        if ($filters['kinds'] !== [] && !in_array((string) ($item['kind'] ?? ''), $filters['kinds'], true)) {
            return false;
        }
        if ($filters['metiers'] !== [] && !self::itemHasTrade($item, $filters['metiers'])) {
            return false;
        }
        if ($filters['specs'] !== [] && !self::itemHasSpec($item, $filters['specs'])) {
            return false;
        }
        if ($filters['delays'] !== []) {
            $bucket = (string) ($item['delay_bucket'] ?? '');
            $kind = (string) ($item['kind'] ?? '');
            $applyDelay = $bucket !== '' || $kind === 'prestations' || $kind === 'service';
            if ($applyDelay && !in_array($bucket, $filters['delays'], true)) {
                return false;
            }
        }
        if ($filters['levels'] !== [] && !in_array((string) ($item['level_key'] ?? ''), $filters['levels'], true)) {
            return false;
        }
        if (in_array('verified', $filters['trust'], true) && empty($item['verified'])) {
            return false;
        }
        if (in_array('rated', $filters['trust'], true) && ($item['rating_num'] ?? 0) < 4.5) {
            return false;
        }
        if (in_array('available', $filters['trust'], true) && ($item['kind'] ?? '') === 'prestataires' && !empty($item['is_busy'])) {
            return false;
        }
        $bmin = $filters['bmin'];
        $bmax = $filters['bmax'];
        $budgetOn = ($bmin !== null && $bmin !== self::BUDGET_MIN) || ($bmax !== null && $bmax !== self::BUDGET_MAX);
        if ($budgetOn) {
            $price = $item['price_num'] ?? null;
            if ($price !== null) {
                if ($bmin !== null && $price < $bmin) {
                    return false;
                }
                if ($bmax !== null && $price > $bmax) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @param list<array<string, mixed>> $pool
     * @return array<string, list<array{v: string, l: string, n: int}>>
     */
    private static function facetOptions(array $pool, string $type): array
    {
        $kinds = [];
        foreach (self::TYPES as $value => $label) {
            if ($value === 'all') {
                continue;
            }
            $kinds[] = ['v' => $value, 'l' => $label, 'n' => self::countWhere($pool, static fn (array $i): bool => ($i['kind'] ?? '') === $value)];
        }

        $metiers = [];
        foreach (self::trades() as $trade) {
            $metiers[] = ['v' => $trade, 'l' => $trade, 'n' => self::countWhere($pool, static fn (array $i): bool => self::itemHasTrade($i, [$trade]))];
        }

        $specs = [];
        foreach (self::specialties() as $spec) {
            $specs[] = ['v' => $spec, 'l' => $spec, 'n' => self::countWhere($pool, static fn (array $i): bool => self::itemHasSpec($i, [$spec]))];
        }

        $delays = [];
        foreach (self::DELAYS as $value => $label) {
            $delays[] = ['v' => $value, 'l' => $label, 'n' => self::countWhere($pool, static fn (array $i): bool => ($i['delay_bucket'] ?? '') === $value)];
        }

        $levels = [];
        foreach (self::LEVELS as $value => $label) {
            $levels[] = ['v' => $value, 'l' => $label, 'n' => self::countWhere($pool, static fn (array $i): bool => ($i['level_key'] ?? '') === $value)];
        }

        $trust = [
            ['v' => 'verified', 'l' => self::TRUST['verified'], 'n' => self::countWhere($pool, static fn (array $i): bool => !empty($i['verified']))],
            ['v' => 'rated', 'l' => self::TRUST['rated'], 'n' => self::countWhere($pool, static fn (array $i): bool => ($i['rating_num'] ?? 0) >= 4.5)],
            ['v' => 'available', 'l' => self::TRUST['available'], 'n' => self::countWhere($pool, static fn (array $i): bool => ($i['kind'] ?? '') !== 'prestataires' || empty($i['is_busy']))],
        ];

        $out = [
            'kind' => $kinds,
            'metier' => $metiers,
            'spec' => $specs,
            'delay' => $delays,
            'level' => $levels,
            'trust' => $trust,
        ];
        if ($type !== 'all') {
            unset($out['kind']);
        }
        return $out;
    }

    /**
     * @param list<array<string, mixed>> $items
     * @param callable(array<string, mixed>): bool $fn
     */
    private static function countWhere(array $items, callable $fn): int
    {
        $n = 0;
        foreach ($items as $item) {
            if ($fn($item)) {
                $n++;
            }
        }
        return $n;
    }

    /** @param array<string, mixed> $item */
    private static function itemHasTrade(array $item, array $trades): bool
    {
        $hay = array_merge([(string) ($item['cat'] ?? '')], is_array($item['trades'] ?? null) ? $item['trades'] : []);
        $want = [];
        foreach ($trades as $trade) {
            $trade = (string) $trade;
            $want[search_norm($trade)] = true;
            $resolved = self::resolveTrade($trade);
            if ($resolved !== null) {
                $want[search_norm($resolved)] = true;
            }
        }
        foreach ($hay as $value) {
            $value = (string) $value;
            if ($value === '') {
                continue;
            }
            if (isset($want[search_norm($value)])) {
                return true;
            }
            $resolved = self::resolveTrade($value);
            if ($resolved !== null && isset($want[search_norm($resolved)])) {
                return true;
            }
        }
        return false;
    }

    /** @param array<string, mixed> $item */
    private static function itemHasSpec(array $item, array $specs): bool
    {
        $hay = $item['specialties'] ?? [];
        foreach ($specs as $spec) {
            $want = search_norm((string) $spec);
            foreach ($hay as $value) {
                if (search_norm((string) $value) === $want) {
                    return true;
                }
            }
        }
        return false;
    }

    private static function delayBucket(string $delay): string
    {
        $delay = search_norm($delay);
        if ($delay === '') {
            return '';
        }
        if (preg_match('/(\d+)\s*jour/', $delay, $m)) {
            $n = (int) $m[1];
            return $n <= 7 ? 'week' : ($n <= 21 ? 'mid' : 'month');
        }
        if (preg_match('/(\d+)\s*semaine/', $delay, $m)) {
            $n = (int) $m[1];
            return $n <= 1 ? 'week' : ($n <= 3 ? 'mid' : 'month');
        }
        if (str_contains($delay, 'mois')) {
            return 'month';
        }
        return '';
    }

    /** @param array<string, mixed> $item */
    private static function priceAmount(array $item): ?int
    {
        if (isset($item['price_from']) && $item['price_from'] !== '' && $item['price_from'] !== null) {
            return (int) $item['price_from'];
        }
        if (isset($item['budget_min']) && $item['budget_min'] !== '' && $item['budget_min'] !== null) {
            return (int) $item['budget_min'];
        }
        foreach ([$item['hourly_rate'] ?? '', $item['price'] ?? ''] as $raw) {
            $raw = (string) $raw;
            if ($raw === '' || str_contains($raw, '%')) {
                continue;
            }
            if (preg_match('/(\d[\d\s]*)/', $raw, $m)) {
                return (int) str_replace([' ', "\u{00a0}"], '', $m[1]);
            }
        }
        return null;
    }

    private static function ratingAmount(mixed $rating): float
    {
        if ($rating === '' || $rating === null) {
            return 0.0;
        }
        return (float) str_replace(',', '.', (string) $rating);
    }

    private static function levelKey(string $level): string
    {
        $level = search_norm($level);
        if ($level === '') {
            return '';
        }
        if (str_contains($level, 'expert')) {
            return 'expert';
        }
        if (str_contains($level, 'confirm')) {
            return 'confirme';
        }
        if (str_contains($level, 'nouveau') || str_contains($level, 'nouvelle') || str_contains($level, 'initie')) {
            return 'nouveau';
        }
        return 'nouveau';
    }

    /**
     * @return array{needle: string, needle_flat: string, words: list<string>, trades: list<string>}
     */
    private static function searchIntent(string $q): array
    {
        static $cache = [];
        if (!isset($cache[$q])) {
            $needle = search_norm($q);
            $cache[$q] = [
                'needle' => $needle,
                'needle_flat' => trim((string) preg_replace('/[^a-z0-9]+/', ' ', $needle)),
                'words' => self::searchTokens($q),
                'trades' => self::queryTrades($q),
            ];
        }
        return $cache[$q];
    }

    /** @return list<string> */
    private static function searchTokens(string $q): array
    {
        $norm = trim((string) preg_replace('/[^a-z0-9]+/', ' ', search_norm($q)));
        $tokens = [];
        foreach (preg_split('/\s+/', $norm) ?: [] as $part) {
            if ($part === '' || isset(self::SEARCH_STOP[$part])) {
                continue;
            }
            $tokens[$part] = true;
            foreach (self::SEARCH_SYNONYMS[$part] ?? [] as $extra) {
                $bits = trim((string) preg_replace('/[^a-z0-9]+/', ' ', search_norm($extra)));
                foreach (preg_split('/\s+/', $bits) ?: [] as $bit) {
                    if ($bit !== '' && !isset(self::SEARCH_STOP[$bit])) {
                        $tokens[$bit] = true;
                    }
                }
            }
        }
        return array_map('strval', array_keys($tokens));
    }

    /** @return list<string> */
    private static function queryTrades(string $q): array
    {
        $q = trim($q);
        if ($q === '') {
            return [];
        }
        $found = [];
        $direct = self::resolveTrade($q);
        if ($direct !== null) {
            $found[$direct] = true;
        }
        $qNorm = search_norm($q);
        $labels = self::tradeAliases();
        foreach (self::TRADE_TITLES as $trade => $title) {
            $labels[$trade] = $trade;
            $labels[$title] = $trade;
            $labels[self::TRADE_LABELS[$trade] ?? $trade] = $trade;
        }
        foreach ($labels as $label => $trade) {
            $norm = search_norm((string) $label);
            if ($norm === '' || mb_strlen($norm) < 4) {
                continue;
            }
            $quoted = preg_quote($norm, '/');
            if (preg_match('/(?<![a-z0-9])' . $quoted . '(?![a-z0-9])/u', $qNorm) === 1) {
                $found[$trade] = true;
            }
        }
        foreach (self::searchTokens($q) as $token) {
            $trade = self::resolveTrade((string) $token);
            if ($trade !== null) {
                $found[$trade] = true;
            }
        }
        return array_keys($found);
    }

    private static function tokenInText(string $text, string $word): bool
    {
        if ($word === '') {
            return false;
        }
        if (mb_strlen($word) <= 2) {
            return (bool) preg_match('/\b' . preg_quote($word, '/') . '\b/', $text);
        }
        return str_contains($text, $word);
    }

    /** @param array<string, mixed> $item */
    private static function score(string $q, array $item): int
    {
        if ($q === '') {
            return 1;
        }
        $intent = self::searchIntent($q);
        $hay = [
            8 => search_norm((string) ($item['title'] ?? '')),
            5 => search_norm((string) ($item['cat'] ?? '')),
            4 => search_norm((string) ($item['subtitle'] ?? '')),
            2 => search_norm((string) ($item['search'] ?? '')),
        ];

        $score = 0;
        foreach ($hay as $weight => $text) {
            $textFlat = trim((string) preg_replace('/[^a-z0-9]+/', ' ', $text));
            if ($textFlat === $intent['needle_flat'] || $text === $intent['needle']) {
                $score += $weight * 6;
                continue;
            }
            if ($intent['needle_flat'] !== '' && (str_contains($textFlat, $intent['needle_flat']) || str_contains($text, $intent['needle']))) {
                $score += $weight * 3;
            }
            foreach ($intent['words'] as $word) {
                if (self::tokenInText($textFlat !== '' ? $textFlat : $text, $word) || self::tokenInText($text, $word)) {
                    $score += $weight;
                }
            }
        }
        if ($intent['trades'] !== [] && self::itemHasTrade($item, $intent['trades'])) {
            $score += 24;
        }
        return $score;
    }
}
