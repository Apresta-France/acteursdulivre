<?php

declare(strict_types=1);

namespace Adl\Data;

final class Souscriptions
{
    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        $items = [];
        foreach (self::raw() as $item) {
            $items[] = self::present($item);
        }

        return $items;
    }

    /** @return array{featured: ?array<string, mixed>, items: list<array<string, mixed>>, counts: array<string, int>} */
    public static function listing(string $type = ''): array
    {
        $all = self::all();
        $open = array_values(array_filter($all, static fn (array $item): bool => !empty($item['open'])));
        $closed = array_values(array_filter($all, static fn (array $item): bool => empty($item['open'])));

        $pool = match ($type) {
            'souscription', 'prevente', 'vente' => array_values(array_filter(
                $open,
                static fn (array $item): bool => ($item['kind'] ?? '') === $type
            )),
            'terminees' => $closed,
            default => $open,
        };

        $featured = null;
        $items = $pool;
        if ($type !== 'terminees') {
            foreach ($pool as $i => $item) {
                if (!empty($item['featured'])) {
                    $featured = $item;
                    unset($items[$i]);
                    $items = array_values($items);
                    break;
                }
            }
        }

        return [
            'featured' => $featured,
            'items' => $items,
            'counts' => [
                'open' => count($open),
                'souscription' => count(array_filter($open, static fn (array $item): bool => $item['kind'] === 'souscription')),
                'prevente' => count(array_filter($open, static fn (array $item): bool => $item['kind'] === 'prevente')),
                'vente' => count(array_filter($open, static fn (array $item): bool => $item['kind'] === 'vente')),
                'closed' => count($closed),
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function preview(int $limit = 3): array
    {
        $open = array_values(array_filter(self::all(), static fn (array $item): bool => !empty($item['open'])));
        usort($open, static function (array $a, array $b): int {
            $feat = ((int) !empty($b['featured'])) <=> ((int) !empty($a['featured']));
            if ($feat !== 0) {
                return $feat;
            }

            return strcmp((string) $a['closes'], (string) $b['closes']);
        });

        return array_slice($open, 0, $limit);
    }

    /** @return array<string, mixed>|null */
    public static function find(string $slug): ?array
    {
        foreach (self::all() as $item) {
            if ($item['slug'] === $slug) {
                return $item;
            }
        }

        return null;
    }

    /** @return list<array<string, mixed>> */
    public static function others(string $slug, int $limit = 3): array
    {
        $items = [];
        foreach (self::all() as $item) {
            if ($item['slug'] === $slug || empty($item['open'])) {
                continue;
            }
            $items[] = $item;
            if (count($items) >= $limit) {
                break;
            }
        }

        return $items;
    }

    /** @param array<string, mixed> $item */
    private static function present(array $item): array
    {
        $today = new \DateTimeImmutable('today');
        $end = new \DateTimeImmutable((string) $item['closes']);
        $diff = $today->diff($end);
        $daysLeft = $diff->invert ? -$diff->days : $diff->days;
        $open = ($item['status'] ?? 'open') === 'open' && $daysLeft >= 0;

        $item['open'] = $open;
        $item['days_left'] = $daysLeft;
        $item['when'] = self::whenLabel($open, $daysLeft);
        $item['closes_label'] = self::frenchDate($end);
        $item['href'] = '/souscriptions/' . $item['slug'];
        $host = trim((string) ($item['host'] ?? ''));
        $closesLabel = self::frenchDate($end);
        $item['where_line'] = $host === ''
            ? ($open ? 'Jusqu’au ' . $closesLabel : 'Close le ' . $closesLabel)
            : ($open ? 'Sur ' . $host . ' · jusqu’au ' . $closesLabel : 'Était sur ' . $host);

        return $item;
    }

    private static function whenLabel(bool $open, int $daysLeft): string
    {
        if (!$open) {
            return 'Clôturée';
        }
        if ($daysLeft === 0) {
            return 'Dernier jour';
        }
        if ($daysLeft === 1) {
            return 'Plus qu’un jour';
        }

        return 'Plus que ' . $daysLeft . ' jours';
    }

    private static function frenchDate(\DateTimeImmutable $date): string
    {
        $months = [
            1 => 'janv.', 2 => 'févr.', 3 => 'mars', 4 => 'avr.', 5 => 'mai', 6 => 'juin',
            7 => 'juil.', 8 => 'août', 9 => 'sept.', 10 => 'oct.', 11 => 'nov.', 12 => 'déc.',
        ];

        return (int) $date->format('j') . ' ' . $months[(int) $date->format('n')] . ' ' . $date->format('Y');
    }

    /** @return list<array<string, mixed>> */
    private static function raw(): array
    {
        return [
            [
                'slug' => 'la-derniere-marge',
                'title' => 'La dernière marge',
                'genre' => 'Essai illustré',
                'kind' => 'souscription',
                'kind_label' => 'Souscription',
                'status' => 'open',
                'featured' => true,
                'bearer' => 'Atelier du Plat',
                'bearer_role' => 'Relieur',
                'pitch' => 'Un essai cousu main, porté par un relieur. La souscription du tirage est ouverte sur Ulule.',
                'body' => [
                    'Le livre tient en 96 pages, format 17 × 24. Le texte suit une marge qui se rétrécit chapitre après chapitre, jusqu’à disparaître. Les planches sont des relevés de cahiers réels, redessinés.',
                    'L’atelier imprime en risographie, puis coud les cahiers. Il n’y a pas de distributeur : les exemplaires partent de l’atelier, et une cinquantaine sont réservés à des librairies qui ont déjà dit oui.',
                    'La souscription du tirage — papier, plaques, couture — est ouverte sur Ulule.',
                ],
                'trades' => [
                    ['role' => 'Reliure et impression', 'name' => 'Atelier du Plat'],
                ],
                'uses' => ['Papier et plaques', 'Impression risographie', 'Couture de 400 exemplaires'],
                'cover' => ['ink' => '#15212f', 'paper' => '#f4efe6', 'rule' => '#eb963b'],
                'price_label' => 'dès 32 €',
                'goal' => 8400,
                'raised' => 5720,
                'backers' => 96,
                'closes' => '2026-10-24',
                'host' => 'Ulule',
                'cta' => 'Soutenir la souscription',
                'rewards' => [
                    ['name' => 'L’exemplaire', 'price' => '32 €', 'detail' => 'Le livre cousu, expédié depuis l’atelier.'],
                    ['name' => 'Livre et cahier', 'price' => '54 €', 'detail' => 'L’exemplaire, plus un cahier de couture non rogné.'],
                    ['name' => 'Exemplaire nominatif', 'price' => '90 €', 'detail' => 'Votre nom dans le colophon, et le livre.'],
                ],
                'facts' => [
                    ['Format', '17 × 24 cm'],
                    ['Pages', '96'],
                    ['Tirage visé', '400 exemplaires'],
                    ['Impression', 'Risographie, puis couture'],
                ],
            ],
            [
                'slug' => 'ce-que-le-papier-retient',
                'title' => 'Ce que le papier retient',
                'genre' => 'Roman',
                'kind' => 'prevente',
                'kind_label' => 'Prévente',
                'status' => 'open',
                'featured' => false,
                'bearer' => 'Claire Vasseur',
                'bearer_role' => 'Autrice',
                'pitch' => 'Roman. Le livre est bouclé, l’imprimeur a le bon à tirer. La prévente ouvre les premiers envois avant la mise en place de janvier.',
                'body' => [
                    'Une correctrice d’épreuves retrouve, dans les marges d’un roman qu’elle ne doit pas toucher, une autre version du même livre. Elle décide de la suivre.',
                    'La prévente est sur la boutique de l’autrice. Le tirage est déjà à l’imprimeur. Les premiers exemplaires partent avant les librairies.',
                ],
                'trades' => [
                    ['role' => 'Autrice', 'name' => 'Claire Vasseur'],
                    ['role' => 'Impression', 'name' => 'Tirage déjà lancé'],
                ],
                'uses' => [],
                'cover' => ['ink' => '#2c241c', 'paper' => '#f6f1e8', 'rule' => '#c4a574'],
                'price_label' => '22 €',
                'goal' => 0,
                'raised' => 0,
                'backers' => 0,
                'closes' => '2026-11-12',
                'host' => 'la boutique de l’autrice',
                'cta' => 'Précommander',
                'rewards' => [],
                'facts' => [
                    ['Format', '13 × 21 cm'],
                    ['Pages', '288'],
                    ['Parution', 'Janvier 2027'],
                    ['Prévente jusqu’au', '12 nov. 2026'],
                ],
            ],
            [
                'slug' => 'inventaire-des-tables',
                'title' => 'Inventaire des tables',
                'genre' => 'Poésie',
                'kind' => 'vente',
                'kind_label' => 'Vente',
                'status' => 'open',
                'featured' => false,
                'bearer' => 'Maison du Carré',
                'bearer_role' => 'Maison d’édition',
                'pitch' => 'Le tirage est là : 300 exemplaires déjà imprimés. La vente se fait sur le site de la maison, le temps qu’il en reste.',
                'body' => [
                    'Trente-deux poèmes pris sur des tables de librairie, de salon et de cuisine. Le livre est un format carré, cousu, avec une couverture souple non pelliculée.',
                    'La vente se fait sur le site de la maison, tant qu’il reste des exemplaires.',
                ],
                'trades' => [
                    ['role' => 'Édition', 'name' => 'Maison du Carré'],
                ],
                'uses' => [],
                'cover' => ['ink' => '#3c2a28', 'paper' => '#f7f0ea', 'rule' => '#eb963b'],
                'price_label' => '18 €',
                'goal' => 0,
                'raised' => 0,
                'backers' => 0,
                'closes' => '2026-11-30',
                'host' => 'le site de la maison',
                'cta' => 'Acheter le livre',
                'rewards' => [],
                'facts' => [
                    ['Format', '16 × 16 cm'],
                    ['Pages', '72'],
                    ['Tirage', '300 exemplaires'],
                    ['Disponible', 'Tant qu’il en reste'],
                ],
            ],
            [
                'slug' => 'le-chevalet-des-teinturiers',
                'title' => 'Le chevalet des Teinturiers',
                'genre' => 'Album',
                'kind' => 'souscription',
                'kind_label' => 'Souscription',
                'status' => 'open',
                'featured' => false,
                'bearer' => 'Les Deux Feuilles',
                'bearer_role' => 'Maison d’édition',
                'pitch' => 'Album jeunesse. La souscription de l’impression est sur KissKissBankBank.',
                'body' => [
                    'Un enfant installe son chevalet dans une rue trop étroite pour une voiture, et peint ce que les passants ne voient plus. Les images sont à la gouache, reproduites en quadrichromie.',
                    'La maison a le texte, les images et le devis d’imprimeur. La souscription qui paie le papier est sur KissKissBankBank.',
                ],
                'trades' => [
                    ['role' => 'Édition', 'name' => 'Les Deux Feuilles'],
                    ['role' => 'Illustration', 'name' => 'Gouache, reproduite en quadrichromie'],
                ],
                'uses' => ['Papier 170 g', 'Impression quadrichromie en France', 'Façonnage cartonné'],
                'cover' => ['ink' => '#1e3344', 'paper' => '#eef3f6', 'rule' => '#d4783a'],
                'price_label' => 'dès 24 €',
                'goal' => 15000,
                'raised' => 6150,
                'backers' => 148,
                'closes' => '2026-12-01',
                'host' => 'KissKissBankBank',
                'cta' => 'Soutenir la souscription',
                'rewards' => [
                    ['name' => 'L’album', 'price' => '24 €', 'detail' => 'Cartonné, expédié à la parution.'],
                    ['name' => 'Album et tirage', 'price' => '48 €', 'detail' => 'L’album et une planche numérotée.'],
                ],
                'facts' => [
                    ['Format', '24 × 28 cm'],
                    ['Pages', '32'],
                    ['Public', 'À partir de 5 ans'],
                    ['Impression', 'France'],
                ],
            ],
            [
                'slug' => 'la-tournee-des-libraires',
                'title' => 'La tournée des libraires',
                'genre' => 'Récit',
                'kind' => 'prevente',
                'kind_label' => 'Prévente',
                'status' => 'open',
                'featured' => false,
                'bearer' => 'Marc Ellen',
                'bearer_role' => 'Auteur',
                'pitch' => 'Récit d’une tournée de dédicaces dans vingt-deux librairies. Prévente jusqu’au salon où le livre sera en main.',
                'body' => [
                    'Ce n’est pas un guide. C’est le compte des kilomètres, des tables trop petites et des libraires qui ont gardé trois exemplaires quand même.',
                    'Le livre sort pour le salon de Montreuil. La prévente, sur la page de l’auteur, permet de le réserver avant.',
                ],
                'trades' => [
                    ['role' => 'Auteur', 'name' => 'Marc Ellen'],
                ],
                'uses' => [],
                'cover' => ['ink' => '#243028', 'paper' => '#f3f6ef', 'rule' => '#7d8f62'],
                'price_label' => '19 €',
                'goal' => 0,
                'raised' => 0,
                'backers' => 0,
                'closes' => '2026-11-06',
                'host' => 'la page de prévente',
                'cta' => 'Précommander',
                'rewards' => [],
                'facts' => [
                    ['Format', '12 × 18 cm'],
                    ['Pages', '160'],
                    ['Sortie', 'Salon de Montreuil'],
                    ['Retrait', 'Sur place, sans frais de port'],
                ],
            ],
            [
                'slug' => 'les-cahiers-du-quai',
                'title' => 'Les cahiers du quai',
                'genre' => 'Cahier',
                'kind' => 'souscription',
                'kind_label' => 'Souscription',
                'status' => 'closed',
                'featured' => false,
                'bearer' => 'Collectif Quai 4',
                'bearer_role' => 'Collectif',
                'pitch' => 'Souscription bouclée en septembre sur Ulule. Le tirage est parti à l’impression. L’annonce reste visible, elle ne prend plus de lien actif.',
                'body' => [
                    'Quatre auteurs, un imprimeur, un relieur. Le cahier rassemble des textes écrits pendant une résidence au bord d’un canal, et les notes de fabrication en regard.',
                    'L’objectif, sur Ulule, a été dépassé. Les exemplaires nominatifs sont partis en premier. Le reste du tirage sera en librairie en novembre.',
                ],
                'trades' => [
                    ['role' => 'Textes', 'name' => 'Collectif Quai 4'],
                    ['role' => 'Impression et reliure', 'name' => 'Atelier associé au collectif'],
                ],
                'uses' => [],
                'cover' => ['ink' => '#4a5560', 'paper' => '#eceae6', 'rule' => '#9aa3ad'],
                'price_label' => '28 €',
                'goal' => 4500,
                'raised' => 5040,
                'backers' => 121,
                'closes' => '2026-09-02',
                'host' => 'Ulule',
                'cta' => 'Souscription terminée',
                'outcome' => 'Objectif dépassé',
                'rewards' => [],
                'facts' => [
                    ['Format', '15 × 21 cm'],
                    ['Pages', '128'],
                    ['Tirage', '500 exemplaires'],
                    ['En librairie', 'Novembre 2026'],
                ],
            ],
        ];
    }
}
