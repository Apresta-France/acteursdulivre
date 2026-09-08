<?php

declare(strict_types=1);

namespace Adl\Controllers;

use Adl\Core\Auth;
use Adl\Core\Request;
use Adl\Core\View;
use Adl\Data\Seo;
use Adl\Data\Share;
use Adl\Models\Analytics;
use Adl\Models\Publisher;
use Adl\Models\PublisherClaim;

/**
 * Annuaire public des maisons d'édition + gestion de la fiche revendiquée dans l'espace membre.
 */
final class PublisherController
{
    private const BASE = '/maisons-edition';

    // ------------------------------------------------------------------
    // Listes : annuaire, pays, ville
    // ------------------------------------------------------------------

    public function index(Request $request): void
    {
        $filters = $this->filters($request);
        $seo = Seo::catalog()['maisons-edition'];
        $total = $this->safeCount();
        $this->renderListing($request, $filters, [
            'heading' => 'Maisons d\'édition en France et en Europe',
            'heading_count' => $total,
            'lead' => 'Plus de ' . format_int(max(0, (int) floor($total / 50) * 50)) . ' éditeurs recensés dans ' . count(Publisher::countries()) . ' pays : grands groupes, indépendants, micro-structures, généralistes et spécialisés. Cherchez par nom, pays, ville ou genre éditorial ; connectez-vous pour consulter les coordonnées et prendre contact.',
            'title' => $seo['title'],
            'description' => $seo['description'],
            'path' => self::BASE,
            'crumbs' => [
                ['name' => 'Communauté', 'url' => '/communaute'],
                ['name' => 'Maisons d\'édition', 'url' => self::BASE],
            ],
            'scope' => 'all',
        ]);
    }

    public function countries(Request $request): void
    {
        $countries = Publisher::countries();
        $cities = [];
        foreach ($countries as $country) {
            $cities[$country['slug']] = array_slice(Publisher::citiesForCountry($country['slug'], 8), 0, 8);
        }
        $title = 'Maisons d\'édition par pays';
        $description = 'Les éditeurs européens classés par pays et par ville : France, Allemagne, Royaume-Uni, Espagne, Italie, Belgique, Suisse et ' . max(0, count($countries) - 7) . ' autres pays.';
        View::page('maisons-edition-pays', [
            'title' => $title,
            'countries' => $countries,
            'citiesByCountry' => $cities,
            'meta' => Seo::build($title, $description, Share::absolute(self::BASE . '/pays'), 'website', null, [
                'json_ld' => [
                    Seo::organization(),
                    Seo::website(),
                    Seo::webPage($title, $description, self::BASE . '/pays', 'CollectionPage'),
                    Seo::breadcrumb([
                        ['name' => Seo::BRAND, 'url' => '/'],
                        ['name' => 'Communauté', 'url' => '/communaute'],
                        ['name' => 'Maisons d\'édition', 'url' => self::BASE],
                        ['name' => 'Par pays', 'url' => self::BASE . '/pays'],
                    ]),
                ],
            ]),
        ]);
    }

    public function country(Request $request, string $countrySlug): void
    {
        $country = Publisher::country($countrySlug);
        if (!$country) {
            not_found('Aucune maison d\'édition recensée pour ce pays.');
        }
        $filters = $this->filters($request);
        $filters['country'] = $country['slug'];
        $label = self::countryPhrase($country['name']);
        $this->renderListing($request, $filters, [
            'heading' => 'Maisons d\'édition ' . $label,
            'heading_count' => $country['n'],
            'lead' => format_int($country['n']) . ' ' . ($country['n'] > 1 ? 'éditeurs recensés' : 'éditeur recensé') . ' ' . $label . ' : littérature, jeunesse, BD, essais, poésie, universitaire… Filtrez par ville, genre ou taille de structure, puis connectez-vous pour joindre la maison.',
            'title' => 'Maisons d\'édition ' . $label . ' : annuaire des éditeurs',
            'description' => 'Annuaire des ' . format_int($country['n']) . ' maisons d\'édition ' . $label . ' recensées sur acteursdulivre.fr : ligne éditoriale, genres, taille, ville. Coordonnées réservées aux membres.',
            'path' => $country['href'],
            'crumbs' => [
                ['name' => 'Communauté', 'url' => '/communaute'],
                ['name' => 'Maisons d\'édition', 'url' => self::BASE],
                ['name' => $country['name'], 'url' => $country['href']],
            ],
            'scope' => 'country',
            'country' => $country,
            'cities' => Publisher::citiesForCountry($country['slug'], 30),
        ]);
    }

    public function city(Request $request, string $countrySlug, string $citySlug): void
    {
        $country = Publisher::country($countrySlug);
        $city = $country ? Publisher::city($countrySlug, $citySlug) : null;
        if (!$country || !$city) {
            not_found('Aucune maison d\'édition recensée pour cette ville.');
        }
        $filters = $this->filters($request);
        $filters['country'] = $country['slug'];
        $filters['city'] = $city['slug'];
        $this->renderListing($request, $filters, [
            'heading' => 'Maisons d\'édition à ' . $city['name'],
            'heading_count' => $city['n'],
            'lead' => format_int($city['n']) . ' ' . ($city['n'] > 1 ? 'éditeurs installés' : 'éditeur installé') . ' à ' . $city['name'] . ' (' . $country['name'] . '). Ligne éditoriale, genres publiés et taille de chaque maison ; les coordonnées sont réservées aux membres connectés.',
            'title' => 'Maisons d\'édition à ' . $city['name'] . ' (' . $country['name'] . ')',
            'description' => 'Les ' . format_int($city['n']) . ' maisons d\'édition de ' . $city['name'] . ', ' . $country['name'] . ' : présentation, genres, taille et groupe. Prenez contact avec un compte acteursdulivre.fr.',
            'path' => $city['href'],
            'crumbs' => [
                ['name' => 'Communauté', 'url' => '/communaute'],
                ['name' => 'Maisons d\'édition', 'url' => self::BASE],
                ['name' => $country['name'], 'url' => $country['href']],
                ['name' => $city['name'], 'url' => $city['href']],
            ],
            'scope' => 'city',
            'country' => $country,
            'city' => $city,
            'cities' => Publisher::citiesForCountry($country['slug'], 30),
        ]);
    }

    // ------------------------------------------------------------------
    // Fiche
    // ------------------------------------------------------------------

    public function show(Request $request, string $slug): void
    {
        $viewer = Auth::user();
        $publisher = $this->safeFind($slug);
        $admin = $viewer && ($viewer['role'] ?? '') === 'admin';
        $isOwner = $publisher && $viewer && (int) ($publisher['owner_user_id'] ?? 0) === (int) $viewer['id'];
        if (!$publisher || (!Publisher::isPublic($publisher) && !$isOwner && !$admin)) {
            not_found('Cette maison d\'édition n\'est pas dans l\'annuaire.');
        }

        // Anti-aspiration : les visiteurs non connectés sont plafonnés en nombre de fiches par IP.
        if (!$viewer && rate_limited('pub-view', Publisher::ipHash(), Publisher::VISITOR_VIEW_LIMIT, Publisher::VISITOR_VIEW_WINDOW)) {
            $this->tooMany();
        }

        $revealed = $viewer && (Publisher::isRevealed((int) $publisher['id']) || $isOwner || $admin);
        $pendingClaim = $viewer && !$isOwner ? $this->safePendingClaim((int) $publisher['id'], (int) $viewer['id']) : null;
        $related = [];
        try {
            $related = Publisher::related($publisher, 6);
        } catch (\Throwable) {
        }

        $description = Publisher::metaDescription($publisher);
        $title = $publisher['name'] . ' — maison d\'édition' . ($publisher['location_label'] !== '' ? ' à ' . $publisher['location_label'] : '');
        $crumbs = [
            ['name' => Seo::BRAND, 'url' => '/'],
            ['name' => 'Communauté', 'url' => '/communaute'],
            ['name' => 'Maisons d\'édition', 'url' => self::BASE],
        ];
        if ($publisher['country_slug'] !== '') {
            $crumbs[] = ['name' => $publisher['country'], 'url' => $publisher['country_href']];
        }
        $crumbs[] = ['name' => $publisher['name'], 'url' => $publisher['href']];

        View::page('maison-edition', [
            'title' => $publisher['name'],
            'publisher' => $publisher,
            'related' => $related,
            'isOwner' => $isOwner,
            'isAdmin' => $admin,
            'revealed' => $revealed,
            'pendingClaim' => $pendingClaim,
            'contactLeft' => $viewer ? max(0, Publisher::CONTACT_DAILY_LIMIT - Publisher::contactViewsToday((int) $viewer['id'])) : 0,
            'meta' => Seo::build(
                $title,
                $description,
                Share::absolute($publisher['href']),
                'website',
                $publisher['logo_src'] !== '' ? $publisher['logo_src'] : null,
                [
                    'image_alt' => $publisher['name'],
                    'image_width' => $publisher['logo_src'] !== '' ? 400 : Seo::OG_W,
                    'image_height' => $publisher['logo_src'] !== '' ? 400 : Seo::OG_H,
                    'twitter_card' => $publisher['logo_src'] !== '' ? 'summary' : 'summary_large_image',
                    'robots' => Publisher::isPublic($publisher) ? Seo::ROBOTS_INDEX : Seo::ROBOTS_NONE,
                    'json_ld' => [
                        Seo::organization(),
                        Seo::website(),
                        Seo::webPage($title, $description, $publisher['href'], 'ItemPage'),
                        Seo::breadcrumb($crumbs),
                        self::organizationLd($publisher, $description),
                    ],
                ]
            ),
        ]);
    }

    /** Dévoile les coordonnées : compte obligatoire, plafond quotidien, journalisé. */
    public function contact(Request $request, string $slug): void
    {
        $viewer = Auth::user();
        $publisher = $this->safeFind($slug);
        if (!$publisher || !Publisher::isPublic($publisher)) {
            not_found();
        }
        if (!$viewer) {
            $_SESSION['_intended'] = $publisher['href'];
            flash('error', 'Créez un compte ou connectez-vous pour consulter les coordonnées de cette maison d\'édition.');
            redirect('/connexion');
        }
        try {
            Publisher::revealContact((int) $publisher['id'], (int) $viewer['id']);
            Analytics::action('maison_contact');
        } catch (\Throwable $e) {
            flash('error', user_error_message($e));
        }
        redirect($publisher['href'] . '#contact');
    }

    // ------------------------------------------------------------------
    // Revendication
    // ------------------------------------------------------------------

    public function claimForm(Request $request, string $slug): void
    {
        $user = Auth::requireUser();
        $publisher = $this->safeFind($slug);
        if (!$publisher || !Publisher::isPublic($publisher)) {
            not_found();
        }
        View::page('maison-edition-revendiquer', [
            'title' => 'Revendiquer la fiche ' . $publisher['name'],
            'publisher' => $publisher,
            'pendingClaim' => $this->safePendingClaim((int) $publisher['id'], (int) $user['id']),
            'alreadyOwned' => !empty($publisher['owner_user_id']),
            'isOwner' => (int) ($publisher['owner_user_id'] ?? 0) === (int) $user['id'],
            'suggestedEmail' => str_contains((string) $user['email'], '@') ? (string) $user['email'] : '',
            'error' => flash('error'),
            'meta' => Seo::build(
                'Revendiquer la fiche ' . $publisher['name'],
                'Prenez la main sur la fiche de votre maison d\'édition.',
                Share::absolute($publisher['href'] . '/revendiquer'),
                'website',
                null,
                ['robots' => Seo::ROBOTS_NONE]
            ),
        ]);
        unset($_SESSION['_old']);
    }

    public function claim(Request $request, string $slug): void
    {
        $user = Auth::requireUser();
        $publisher = $this->safeFind($slug);
        if (!$publisher || !Publisher::isPublic($publisher)) {
            not_found();
        }
        if (!$request->bool('attest')) {
            flash('error', 'Merci de confirmer que vous êtes habilité à représenter cette maison.');
            $_SESSION['_old'] = $request->all();
            redirect($publisher['href'] . '/revendiquer');
        }
        try {
            PublisherClaim::create($publisher, $user, [
                'role_title' => $request->string('role_title'),
                'company_email' => $request->string('company_email'),
                'phone' => $request->string('phone'),
                'message' => $request->string('message'),
            ]);
            Analytics::action('maison_revendication');
            unset($_SESSION['_old']);
            flash('saved', 'Demande envoyée. L\'équipe vérifie chaque demande à la main et vous prévient par e-mail dès qu\'elle est traitée.');
            redirect($publisher['href']);
        } catch (\Throwable $e) {
            flash('error', user_error_message($e));
            $_SESSION['_old'] = $request->all();
            redirect($publisher['href'] . '/revendiquer');
        }
    }

    // ------------------------------------------------------------------
    // Proposer une maison absente de l'annuaire
    // ------------------------------------------------------------------

    public function addForm(Request $request): void
    {
        if (!Auth::user()) {
            $_SESSION['_intended'] = self::BASE . '/ajouter';
            flash('error', 'Créez un compte ou connectez-vous pour ajouter votre maison d\'édition à l\'annuaire.');
            redirect('/connexion');
        }
        $user = Auth::requireUser();
        $owned = null;
        $pendingCreation = null;
        try {
            $owned = Publisher::findForOwner((int) $user['id']);
            foreach (PublisherClaim::forUser((int) $user['id']) as $c) {
                if ($c['is_creation'] && $c['status'] === PublisherClaim::STATUS_PENDING) {
                    $pendingCreation = $c;
                    break;
                }
            }
        } catch (\Throwable) {
        }

        // Après un premier envoi, on remontre les homonymes pour éviter un doublon.
        $similar = [];
        $oldName = (string) old('name');
        if ($oldName !== '' && !empty($_SESSION['_old'])) {
            $similar = $this->safe(static fn (): array => Publisher::similar($oldName, (string) old('country'), 0, 5));
        }

        View::page('maison-edition-ajouter', [
            'title' => 'Ajouter ma maison d\'édition',
            'owned' => $owned,
            'pendingCreation' => $pendingCreation,
            'similar' => $similar,
            'sizes' => Publisher::SIZES,
            'typologies' => Publisher::TYPOLOGIES,
            'countries' => $this->safe(static fn (): array => Publisher::countries()),
            'suggestedEmail' => str_contains((string) $user['email'], '@') ? (string) $user['email'] : '',
            'error' => flash('error'),
            'meta' => Seo::build(
                'Ajouter ma maison d\'édition',
                'Proposez votre maison d\'édition dans l\'annuaire et gérez sa fiche.',
                Share::absolute(self::BASE . '/ajouter'),
                'website',
                null,
                ['robots' => Seo::ROBOTS_NONE]
            ),
        ]);
        unset($_SESSION['_old']);
    }

    public function add(Request $request): void
    {
        $user = Auth::requireUser();
        $back = self::BASE . '/ajouter';
        $keep = static function () use ($request): void {
            $all = $request->all();
            unset($all['_token'], $all['logo']);
            $_SESSION['_old'] = $all;
        };

        if (!$request->bool('attest')) {
            flash('error', 'Merci de confirmer que vous êtes habilité à représenter cette maison.');
            $keep();
            redirect($back);
        }

        $name = $request->string('name');
        $country = $request->string('country');
        if (!$request->bool('not_duplicate')) {
            $similar = $this->safe(static fn (): array => Publisher::similar($name, $country, 0, 5));
            if ($similar !== []) {
                flash('error', 'Des maisons au nom proche existent déjà dans l\'annuaire. Vérifiez qu\'il ne s\'agit pas de la vôtre : dans ce cas, revendiquez sa fiche plutôt que d\'en créer une nouvelle.');
                $keep();
                redirect($back . '#doublons');
            }
        }

        try {
            $data = [
                'name' => $name,
                'country' => $country,
                'city' => $request->string('city'),
                'founded' => $request->string('founded'),
                'parent_group' => $request->string('parent_group'),
                'size_key' => $request->string('size_key'),
                'typology_key' => $request->string('typology_key'),
                'genres' => $request->string('genres'),
                'description' => $request->string('description'),
                'website' => $request->string('website'),
                'contact_email' => $request->string('contact_email'),
                'contact_address' => $request->string('contact_address'),
                'contact_phone' => $request->string('contact_phone'),
                'submissions_note' => $request->string('submissions_note'),
            ];
            if (mb_strlen(trim((string) $data['description'])) < 40) {
                throw new \RuntimeException('Présentez la maison en quelques phrases (40 caractères minimum) : c\'est ce que verront les visiteurs.');
            }
            $logo = store_upload($request->file('logo'), 'publishers', ['jpg', 'jpeg', 'png', 'webp'], 2 * 1024 * 1024);
            if ($logo !== null) {
                $data['logo_path'] = $logo;
            }
            PublisherClaim::createForNew($user, $data, [
                'role_title' => $request->string('role_title'),
                'company_email' => $request->string('company_email'),
                'phone' => $request->string('phone'),
                'message' => $request->string('message'),
            ]);
            Analytics::action('maison_creation');
            unset($_SESSION['_old']);
            flash('saved', 'Merci ! Votre maison est enregistrée et sera publiée dès validation par l\'équipe, en général sous deux jours ouvrés. Vous pouvez déjà compléter sa fiche.');
            redirect('/espace/maison-edition');
        } catch (\Throwable $e) {
            flash('error', user_error_message($e));
            $keep();
            redirect($back);
        }
    }

    // ------------------------------------------------------------------
    // Espace membre : gérer sa fiche
    // ------------------------------------------------------------------

    public function espace(Request $request): void
    {
        $user = Auth::requireUser();
        $publisher = null;
        $claims = [];
        try {
            $publisher = Publisher::findForOwner((int) $user['id']);
            $claims = PublisherClaim::forUser((int) $user['id']);
        } catch (\Throwable) {
        }
        View::page('espace-maison', [
            'title' => 'Ma maison d\'édition',
            'publisher' => $publisher,
            'claims' => $claims,
            'sizes' => Publisher::SIZES,
            'typologies' => Publisher::TYPOLOGIES,
            'countries' => Publisher::countries(),
            'error' => flash('error'),
            'saved' => flash('saved'),
        ]);
    }

    public function espaceSave(Request $request): void
    {
        $user = Auth::requireUser();
        $publisher = Publisher::findForOwner((int) $user['id']);
        if (!$publisher) {
            flash('error', 'Aucune fiche ne vous est attribuée pour le moment.');
            redirect('/espace/maison-edition');
        }
        try {
            $data = [
                'name' => $request->string('name'),
                'country' => $request->string('country'),
                'city' => $request->string('city'),
                'founded' => $request->string('founded'),
                'parent_group' => $request->string('parent_group'),
                'size_key' => $request->string('size_key'),
                'typology_key' => $request->string('typology_key'),
                'genres' => $request->string('genres'),
                'description' => $request->string('description'),
                'website' => $request->string('website'),
                'contact_email' => $request->string('contact_email'),
                'contact_address' => $request->string('contact_address'),
                'contact_phone' => $request->string('contact_phone'),
                'submissions_note' => $request->string('submissions_note'),
            ];
            $logo = store_upload($request->file('logo'), 'publishers', ['jpg', 'jpeg', 'png', 'webp'], 2 * 1024 * 1024);
            if ($logo !== null) {
                if (!empty($publisher['logo_path'])) {
                    delete_upload((string) $publisher['logo_path']);
                }
                $data['logo_path'] = $logo;
            } elseif ($request->bool('remove_logo') && !empty($publisher['logo_path'])) {
                delete_upload((string) $publisher['logo_path']);
                $data['logo_path'] = null;
            }
            Publisher::save((int) $publisher['id'], $data, false);
            flash('saved', 'Fiche mise à jour.');
        } catch (\Throwable $e) {
            flash('error', user_error_message($e));
        }
        redirect('/espace/maison-edition');
    }

    // ------------------------------------------------------------------
    // Interne
    // ------------------------------------------------------------------

    /**
     * @param array<string, string> $filters
     * @param array<string, mixed> $ctx
     */
    private function renderListing(Request $request, array $filters, array $ctx): void
    {
        $pageNum = max(1, $request->int('page', 1) ?? 1);
        try {
            $found = Publisher::search($filters, $pageNum);
        } catch (\Throwable) {
            $found = ['items' => [], 'total' => 0, 'pages' => 1, 'page' => 1];
        }
        if ($pageNum > $found['pages']) {
            redirect(catalog_listing_url((string) $ctx['path'], $found['pages'], $this->queryFor($filters, (string) $ctx['scope'])));
        }

        $countrySlug = (string) ($filters['country'] ?? '');
        $citySlug = ($ctx['scope'] ?? '') === 'city' ? (string) ($filters['city'] ?? '') : '';
        $facets = [
            'genres' => $this->safe(static fn (): array => Publisher::genreFacets(18, $countrySlug, $citySlug)),
            'sizes' => $this->safe(static fn (): array => Publisher::sizeFacets($countrySlug, $citySlug)),
            'countries' => $this->safe(static fn (): array => Publisher::countries()),
        ];

        $userQuery = $this->queryFor($filters, (string) $ctx['scope']);
        $isFiltered = $userQuery !== [] || $pageNum > 1;
        $path = (string) $ctx['path'];
        $canonical = $path;
        $crumbs = array_merge([['name' => Seo::BRAND, 'url' => '/']], $ctx['crumbs']);

        $itemList = [];
        foreach ($found['items'] as $i => $item) {
            $itemList[] = [
                '@type' => 'ListItem',
                'position' => ($found['page'] - 1) * Publisher::PER_PAGE + $i + 1,
                'url' => Share::absolute((string) $item['href']),
                'name' => (string) $item['name'],
            ];
        }
        $jsonLd = [
            Seo::organization(),
            Seo::website(),
            Seo::webPage((string) $ctx['title'], (string) $ctx['description'], $path, 'CollectionPage'),
            Seo::breadcrumb($crumbs),
        ];
        if ($itemList !== [] && !$isFiltered) {
            $jsonLd[] = [
                '@type' => 'ItemList',
                'name' => (string) $ctx['heading'],
                'numberOfItems' => $found['total'],
                'itemListOrder' => 'https://schema.org/ItemListOrderAscending',
                'itemListElement' => $itemList,
            ];
        }

        $meta = Seo::build(
            (string) $ctx['title'] . ($pageNum > 1 ? ' — page ' . $pageNum : ''),
            (string) $ctx['description'],
            Share::absolute($canonical),
            'website',
            null,
            [
                'robots' => $isFiltered ? 'noindex, follow' : Seo::ROBOTS_INDEX,
                'json_ld' => $isFiltered ? [] : $jsonLd,
            ]
        );

        $genreLabel = ($filters['genre'] ?? '') !== '' ? Publisher::genreLabel((string) $filters['genre']) : '';

        View::page('maisons-edition', array_merge($ctx, [
            'title' => (string) $ctx['title'],
            'publishers' => $found['items'],
            'pager' => ['page' => $found['page'], 'pages' => $found['pages'], 'total' => $found['total']],
            'pagerPath' => $path,
            'filters' => $filters,
            'genreLabel' => $genreLabel,
            'facets' => $facets,
            'isFiltered' => $userQuery !== [],
            'sizes' => Publisher::SIZES,
            'typologies' => Publisher::TYPOLOGIES,
            'meta' => $meta,
            'query' => (string) ($filters['q'] ?? ''),
            'breadcrumbs' => $ctx['crumbs'],
        ]));
    }

    /** @return array<string, string> */
    private function filters(Request $request): array
    {
        $out = [
            'q' => mb_substr($request->string('q'), 0, 80),
            'genre' => mb_substr($request->string('genre'), 0, 60),
            'size' => $request->string('taille'),
            'typology' => $request->string('typologie'),
            'independent' => $request->bool('independant') ? '1' : '',
        ];
        $country = $request->string('pays');
        if ($country !== '') {
            $out['country'] = mb_substr($country, 0, 60);
        }
        $city = $request->string('ville');
        if ($city !== '') {
            $out['city'] = mb_substr($city, 0, 60);
        }
        return $out;
    }

    /**
     * Paramètres GET à conserver dans la pagination (hors ceux portés par l'URL elle-même).
     *
     * @param array<string, string> $filters
     * @return array<string, string>
     */
    private function queryFor(array $filters, string $scope): array
    {
        $q = [];
        if (($filters['q'] ?? '') !== '') {
            $q['q'] = $filters['q'];
        }
        if (($filters['genre'] ?? '') !== '') {
            $q['genre'] = $filters['genre'];
        }
        if (($filters['size'] ?? '') !== '') {
            $q['taille'] = $filters['size'];
        }
        if (($filters['typology'] ?? '') !== '') {
            $q['typologie'] = $filters['typology'];
        }
        if (($filters['independent'] ?? '') === '1') {
            $q['independant'] = '1';
        }
        if ($scope === 'all') {
            if (($filters['country'] ?? '') !== '') {
                $q['pays'] = $filters['country'];
            }
            if (($filters['city'] ?? '') !== '') {
                $q['ville'] = $filters['city'];
            }
        } elseif ($scope === 'country' && ($filters['city'] ?? '') !== '') {
            $q['ville'] = $filters['city'];
        }
        return $q;
    }

    private function safeFind(string $slug): ?array
    {
        try {
            return Publisher::findBySlug($slug);
        } catch (\Throwable) {
            return null;
        }
    }

    private function safePendingClaim(int $publisherId, int $userId): ?array
    {
        try {
            return PublisherClaim::pendingFor($publisherId, $userId);
        } catch (\Throwable) {
            return null;
        }
    }

    private function safeCount(): int
    {
        try {
            return Publisher::countPublished();
        } catch (\Throwable) {
            return 0;
        }
    }

    /** @return list<mixed> */
    private function safe(callable $fn): array
    {
        try {
            return $fn();
        } catch (\Throwable) {
            return [];
        }
    }

    private function tooMany(): never
    {
        http_response_code(429);
        header('Retry-After: ' . Publisher::VISITOR_VIEW_WINDOW);
        View::render('errors/429', [
            'title' => 'Trop de consultations',
            'meta' => [
                'title' => 'Trop de consultations — acteursdulivre.fr',
                'description' => 'Ralentissez ou connectez-vous pour continuer à parcourir l\'annuaire.',
                'robots' => Seo::ROBOTS_NONE,
            ],
        ]);
        exit;
    }

    public static function countryPhrase(string $country): string
    {
        $n = search_norm($country);
        $feminine = ['france', 'allemagne', 'belgique', 'suisse', 'espagne', 'italie', 'autriche', 'irlande', 'suede', 'pologne', 'ukraine', 'norvege', 'finlande', 'republique tcheque', 'serbie', 'roumanie', 'grece', 'lituanie', 'estonie', 'islande', 'hongrie', 'croatie', 'slovenie', 'slovaquie', 'lettonie', 'bosnie-herzegovine', 'albanie', 'macedoine du nord', 'moldavie', 'bulgarie', 'europe'];
        $plural = ['pays-bas'];
        $vowel = preg_match('/^[aeiouy]/', $n) === 1;
        if (in_array($n, $plural, true)) {
            return 'aux ' . $country;
        }
        if (in_array($n, $feminine, true) || $vowel) {
            return 'en ' . $country;
        }
        if (in_array($n, ['luxembourg', 'portugal', 'danemark', 'royaume-uni', 'montenegro', 'canada'], true)) {
            return 'au ' . $country;
        }
        return 'à ' . $country;
    }

    /** JSON-LD Organization volontairement sans e-mail, téléphone ni URL (anti-aspiration). */
    private static function organizationLd(array $p, string $description): array
    {
        $ld = [
            '@type' => 'Organization',
            '@id' => Share::absolute($p['href']) . '#organization',
            'name' => $p['name'],
            'description' => Seo::clip($description, 300),
            'mainEntityOfPage' => Share::absolute($p['href']),
            'knowsAbout' => array_slice($p['genres'], 0, 10),
        ];
        if ($p['logo_src'] !== '') {
            $ld['logo'] = Share::absolute($p['logo_src']);
        }
        if ($p['founded_year']) {
            $ld['foundingDate'] = (string) $p['founded_year'];
        }
        $address = array_filter([
            '@type' => 'PostalAddress',
            'addressLocality' => $p['city'] !== '' ? $p['city'] : null,
            'addressCountry' => $p['country'] !== '' ? $p['country'] : null,
        ]);
        if (count($address) > 1) {
            $ld['address'] = $address;
        }
        if (!$p['is_independent'] && $p['group_label'] !== '') {
            $ld['parentOrganization'] = ['@type' => 'Organization', 'name' => $p['group_label']];
        }
        return $ld;
    }
}
