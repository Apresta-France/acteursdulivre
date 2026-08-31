<?php

declare(strict_types=1);

namespace Adl\Controllers;

use Adl\Core\Auth;
use Adl\Core\Mailer;
use Adl\Core\Request;
use Adl\Core\View;
use Adl\Data\Catalog;
use Adl\Data\LegalPages;
use Adl\Data\Seo;
use Adl\Data\Share;
use Adl\Data\Sitemap;
use Adl\Models\Application;
use Adl\Models\Article;
use Adl\Models\Favorite;
use Adl\Models\Invoice;
use Adl\Models\Mission;
use Adl\Models\Newsletter;
use Adl\Models\Profile;
use Adl\Models\Report;
use Adl\Models\Service;
use Adl\Models\User;

final class PageController
{
    public function sitemap(Request $request): void
    {
        http_response_code(200);
        header('Content-Type: application/xml; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        header('X-Robots-Tag: noindex');
        echo Sitemap::xml();
    }

    public function robots(Request $request): void
    {
        http_response_code(200);
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        echo Sitemap::robots();
    }

    public function llms(Request $request): void
    {
        http_response_code(200);
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        echo Seo::llmsTxt();
    }

    public function home(Request $request): void
    {
        $page = Seo::catalog()['accueil'];
        View::page('accueil', [
            'title' => $page['title'],
            'meta' => Seo::forScreen('accueil', ['title' => $page['title']]),
        ]);
    }

    public function search(Request $request): void
    {
        $this->renderCatalog($request, 'all');
    }

    public function prestationsIndex(Request $request): void
    {
        $this->renderCatalog($request, 'prestations');
    }

    public function prestatairesIndex(Request $request): void
    {
        $this->renderCatalog($request, 'prestataires');
    }

    public function searchApi(Request $request): void
    {
        $filters = self::searchFilters($request);
        $cat = $request->string('cat');
        if ($cat !== '') {
            $cat = Catalog::resolveTrade($cat) ?? $cat;
        }
        $found = Catalog::search(
            $request->string('q'),
            $request->string('type', 'all'),
            $cat,
            max(1, min(48, $request->int('limit', 24) ?? 24)),
            $request->bool('dispo'),
            $filters
        );
        foreach (['results', 'suggestions'] as $key) {
            foreach ($found[$key] as $i => $item) {
                $found[$key][$i]['href'] = url((string) ($item['href'] ?? '/recherche'));
            }
        }
        $found['cat'] = $cat;
        json_response($found);
    }

    public function metier(Request $request, string $slug): void
    {
        $trade = Catalog::tradeFromSlug($slug);
        if ($trade === null) {
            not_found('Ce métier n\'est pas répertorié.');
        }
        $canonicalSlug = slugify($trade);
        if ($slug !== $canonicalSlug) {
            redirect(Catalog::tradePath($trade), 301);
        }

        $preview = 6;
        $providers = Catalog::search('', 'prestataires', $trade, $preview);
        $services = Catalog::search('', 'prestations', $trade, $preview);
        $missions = Catalog::search('', 'missions', $trade, $preview);
        $label = Catalog::TRADE_LABELS[$trade] ?? $trade;
        $geo = Seo::tradeCopy($trade);
        $path = Catalog::tradePath($trade);
        $specs = [];
        foreach ($services['facets']['spec'] ?? [] as $spec) {
            if ((int) ($spec['n'] ?? 0) > 0) {
                $specs[] = $spec;
            }
        }
        $otherTrades = [];
        foreach (Catalog::trades() as $other) {
            if ($other === $trade) {
                continue;
            }
            $otherTrades[] = [
                'label' => Catalog::TRADE_LABELS[$other] ?? $other,
                'href' => Catalog::tradePath($other),
            ];
        }

        View::page('metier', [
            'title' => $geo['h1'],
            'slug' => $slug,
            'trade' => $trade,
            'tradeLabel' => $label,
            'tradeTitle' => Catalog::tradeTitle($trade),
            'tradeGeo' => $geo,
            'providers' => $providers['results'],
            'services' => $services['results'],
            'missions' => $missions['results'],
            'providerCount' => (int) ($providers['count'] ?? 0),
            'serviceCount' => (int) ($services['count'] ?? 0),
            'missionCount' => (int) ($missions['count'] ?? 0),
            'tradeSpecs' => $specs,
            'volumeHint' => Catalog::volumeHint($trade),
            'briefHint' => Catalog::briefHint($trade),
            'otherTrades' => $otherTrades,
            'heroImg' => photo(abs(crc32($canonicalSlug)) % 6),
            'meta' => Seo::build(
                $geo['h1'],
                $geo['description'],
                Share::absolute($path),
                'website',
                null,
                [
                    'json_ld' => [
                        Seo::organization(),
                        Seo::website(),
                        Seo::breadcrumb([
                            ['name' => 'Acteurs du Livre', 'url' => '/'],
                            ['name' => 'Métiers', 'url' => '/recherche'],
                            ['name' => $geo['h1'], 'url' => $path],
                        ]),
                    ],
                ]
            ),
        ]);
    }

    public function fiche(Request $request, string $slug): void
    {
        try {
            $service = Service::findBySlug($slug);
        } catch (\Throwable) {
            $service = null;
        }
        $viewer = Auth::user();
        $isOwner = $service && $viewer && (int) ($viewer['id'] ?? 0) === (int) ($service['user_id'] ?? 0);
        $admin = $viewer && ($viewer['role'] ?? '') === 'admin';
        $public = $service
            && ($service['status'] ?? '') === 'published'
            && User::isPublicOfferer((int) ($service['user_id'] ?? 0));
        if (!$service || (!$public && !$isOwner && !$admin)) {
            not_found('Cette prestation n\'est plus disponible.');
        }

        $canOrder = $public && (!$viewer || (int) ($viewer['id'] ?? 0) !== (int) ($service['user_id'] ?? 0));
        $isFavorite = false;
        if ($viewer) {
            try {
                $isFavorite = Favorite::has((int) $viewer['id'], (int) $service['id']);
            } catch (\Throwable) {
            }
        }

        $excerpt = trim(plain_text((string) ($service['excerpt'] ?? '')));
        if ($excerpt === '') {
            $excerpt = trim((string) ($service['by'] . ' · ' . $service['price']));
        }
        View::page('fiche', [
            'title' => $service['title'],
            'slug' => $slug,
            'service' => $service,
            'canOrder' => $canOrder,
            'isFavorite' => $isFavorite,
            'meta' => Seo::build(
                $service['title'],
                $excerpt !== '' ? $excerpt : (string) $service['title'],
                Share::absolute((string) $service['href']),
                'website',
                !empty($service['has_image']) ? (string) $service['img'] : null,
                [
                    'image_alt' => (string) $service['title'],
                    'json_ld' => [
                        Seo::organization(),
                        Seo::website(),
                        Seo::breadcrumb([
                            ['name' => 'Acteurs du Livre', 'url' => '/'],
                            ['name' => 'Prestations', 'url' => '/prestations'],
                            ['name' => (string) $service['title'], 'url' => (string) $service['href']],
                        ]),
                        Seo::offer($service),
                    ],
                ]
            ),
        ]);
    }

    public function profil(Request $request, string $slug): void
    {
        try {
            $profile = Profile::findBySlug($slug);
        } catch (\Throwable) {
            $profile = null;
        }

        if (!$profile || !User::isPublicOfferer($profile)) {
            not_found('Ce profil n\'est pas publié.');
        }

        $public = Catalog::profileToPublic($profile);
        $excerpt = trim((string) ($public['presentation'] ?? ''));
        $desc = $excerpt !== ''
            ? $excerpt
            : trim($public['title'] . ($public['city'] ? ' · ' . $public['city'] : '') . ' · prestataire sur acteursdulivre.fr');
        View::page('profil', [
            'title' => $public['name'],
            'slug' => $slug,
            'liveProfile' => $public,
            'meta' => Seo::build(
                $public['name'] . ' — ' . ($public['title'] ?: 'Prestataire'),
                $desc,
                Share::absolute((string) $public['href']),
                'website',
                null,
                [
                    'json_ld' => [
                        Seo::organization(),
                        Seo::website(),
                        Seo::breadcrumb([
                            ['name' => 'Acteurs du Livre', 'url' => '/'],
                            ['name' => 'Prestataires', 'url' => '/prestataires'],
                            ['name' => (string) $public['name'], 'url' => (string) $public['href']],
                        ]),
                        Seo::person($public),
                    ],
                ]
            ),
        ]);
    }

    public function missions(Request $request): void
    {
        $cat = $request->string('metier') ?: $request->string('cat');
        if ($cat !== '') {
            $resolved = Catalog::resolveTrade($cat);
            $cat = $resolved ?? $cat;
        }
        $found = Catalog::search('', 'missions', $cat);
        View::page('missions', [
            'title' => 'Appels d\'offres',
            'searchCat' => $cat,
            'liveMissions' => $found['results'],
            'trades' => Catalog::trades(),
            'meta' => Seo::build(
                'Appels d\'offres du livre',
                'Missions ouvertes : correction, illustration, traduction, impression. Candidatez sans commission sur la candidature.',
                Share::absolute('/missions'),
                'website',
                null,
                [
                    'robots' => $cat !== '' ? 'noindex, follow' : Seo::ROBOTS_INDEX,
                    'json_ld' => $cat !== '' ? [] : Seo::webPageGraph(
                        'Appels d\'offres du livre',
                        'Missions ouvertes des métiers du livre.',
                        '/missions',
                        'missions'
                    ),
                ]
            ),
        ]);
    }

    public function mission(Request $request, string $slug): void
    {
        $mission = Catalog::mission($slug);
        if (!$mission) {
            not_found('Cette mission n\'est plus disponible.');
        }
        $viewer = Auth::user();
        $isOwner = $viewer && (int) ($viewer['id'] ?? 0) === (int) ($mission['user_id'] ?? 0);
        $admin = $viewer && ($viewer['role'] ?? '') === 'admin';
        if (($mission['status'] ?? '') === 'draft' && !$isOwner && !$admin) {
            not_found('Cette mission n\'est plus disponible.');
        }
        $ownerAccount = User::find((int) ($mission['user_id'] ?? 0));
        if ((!$ownerAccount || ($ownerAccount['status'] ?? '') !== 'active') && !$admin && !$isOwner) {
            not_found('Cette mission n\'est plus disponible.');
        }

        $myApplication = null;
        $applications = [];
        if ($isOwner) {
            try {
                $applications = Application::forMission((int) $mission['id']);
                foreach ($applications as $app) {
                    if (($app['status'] ?? '') === 'sent') {
                        Application::markViewed((int) $app['id'], (int) $viewer['id']);
                    }
                }
                $applications = Application::forMission((int) $mission['id']);
            } catch (\Throwable) {
            }
        } elseif ($viewer && User::offersServices($viewer)) {
            try {
                $myApplication = Application::findForUserOnMission((int) $mission['id'], (int) $viewer['id']);
            } catch (\Throwable) {
            }
        }
        $brief = trim((string) ($mission['brief'] ?? ''));
        $isDraft = ($mission['status'] ?? '') === 'draft';
        View::page('mission', [
            'title' => $mission['title'],
            'slug' => $slug,
            'liveMission' => $mission,
            'isOwner' => $isOwner,
            'canApply' => $viewer && User::offersServices($viewer) && !$isOwner && !$myApplication && ($mission['status'] ?? '') === 'open' && !Invoice::sellerIsBlocked((int) $viewer['id']),
            'offersServices' => $viewer && User::offersServices($viewer),
            'myApplication' => $myApplication,
            'applications' => $applications,
            'old' => flash('old') ?: [],
            'error' => flash('error'),
            'saved' => flash('saved'),
            'suggestions' => Catalog::suggestionsForTrade((string) ($mission['category_name'] ?? '')),
            'meta' => Seo::build(
                (string) $mission['title'],
                $brief !== '' ? $brief : ('Appel d\'offres ' . ($mission['category_name'] ?? '') . ' sur acteursdulivre.fr.'),
                Share::absolute((string) $mission['href']),
                'website',
                null,
                [
                    'robots' => $isDraft ? Seo::ROBOTS_NONE : Seo::ROBOTS_INDEX,
                    'json_ld' => $isDraft ? [] : [
                        Seo::organization(),
                        Seo::website(),
                        Seo::breadcrumb([
                            ['name' => 'Acteurs du Livre', 'url' => '/'],
                            ['name' => 'Appels d\'offres', 'url' => '/missions'],
                            ['name' => (string) $mission['title'], 'url' => (string) $mission['href']],
                        ]),
                    ],
                ]
            ),
        ]);
    }

    public function missionFile(Request $request, string $slug): void
    {
        $user = Auth::requireUser();
        $mission = Mission::findBySlug($slug);
        if (!$mission || trim((string) ($mission['attachment_path'] ?? '')) === '') {
            not_found('Cette pièce jointe n\'est plus disponible.');
        }
        if (!Mission::canAccessAttachment($mission, $user)) {
            not_found('Cette pièce jointe n\'est plus disponible.');
        }
        $path = (string) $mission['attachment_path'];
        $name = trim((string) ($mission['attachment_name'] ?? '')) ?: 'document';
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimes = upload_mime_map();
        send_any_upload($path, $name, $mimes[$ext][0] ?? 'application/octet-stream');
    }

    public function comment(Request $request): void
    {
        View::page('comment', [
            'title' => 'Comment ça marche',
            'meta' => Seo::forScreen('comment'),
        ]);
    }

    public function tarifs(Request $request): void
    {
        View::page('tarifs', [
            'title' => 'Tarifs et commission 8 %',
            'meta' => Seo::forScreen('tarifs'),
        ]);
    }

    public function confiance(Request $request): void
    {
        View::page('confiance', [
            'title' => 'Confiance et sécurité',
            'meta' => Seo::forScreen('confiance'),
        ]);
    }

    public function apropos(Request $request): void
    {
        View::page('apropos', [
            'title' => 'À propos d\'Acteurs du Livre',
            'meta' => Seo::forScreen('apropos'),
        ]);
    }

    public function journal(Request $request): void
    {
        $q = $request->string('q');
        $catRaw = $request->string('cat');
        $page = max(1, $request->int('page', 1) ?? 1);
        $cat = '';
        $categories = [];
        $found = [
            'items' => [],
            'total' => 0,
            'page' => 1,
            'pages' => 1,
            'per_page' => Article::PER_PAGE,
        ];
        $hasJournal = false;
        try {
            $categories = Article::publishedCategories();
            $cat = Article::resolveCategory($catRaw, array_column($categories, 'label'));
            $found = Article::searchPublished($q, $cat, $page, Article::PER_PAGE);
            $hasJournal = Article::countPublished() > 0;
        } catch (\Throwable) {
        }
        $filtered = $q !== '' || $cat !== '';
        $items = $found['items'];
        $hero = (!$filtered && $found['page'] === 1 && $items !== []) ? $items[0] : null;
        $rest = $hero ? array_slice($items, 1) : $items;

        $metaExtra = [];
        if ($cat !== '' && $q === '') {
            $metaExtra['title'] = 'Le journal — ' . $cat;
        }
        $meta = Seo::forScreen('journal', $metaExtra);
        $canonical = '/journal';
        if ($cat !== '' && $q === '') {
            $canonical .= '?cat=' . rawurlencode($cat);
        }
        $meta['url'] = Share::absolute($canonical);
        if ($q !== '' || $found['page'] > 1) {
            $meta['robots'] = 'noindex, follow';
        }

        View::page('journal', [
            'title' => $metaExtra['title'] ?? 'Le journal des métiers du livre',
            'articles' => $items,
            'hero' => $hero,
            'rest' => $rest,
            'journalQ' => $q,
            'journalCat' => $cat,
            'journalCategories' => $categories,
            'journalHasContent' => $hasJournal,
            'journalFiltered' => $filtered,
            'pager' => [
                'page' => $found['page'],
                'pages' => $found['pages'],
                'total' => $found['total'],
            ],
            'meta' => $meta,
        ]);
    }

    public function journalApi(Request $request): void
    {
        $q = $request->string('q');
        $limit = max(1, min(12, $request->int('limit', 8) ?? 8));
        try {
            $cat = Article::resolveCategory($request->string('cat'));
            $suggestions = Article::suggestPublished($q, $cat, $limit);
        } catch (\Throwable) {
            $suggestions = [];
        }
        foreach ($suggestions as $i => $item) {
            $suggestions[$i]['href'] = url((string) ($item['href'] ?? '/journal'));
        }
        json_response([
            'suggestions' => $suggestions,
            'results' => $suggestions,
        ]);
    }

    public function article(Request $request, string $slug): void
    {
        try {
            $article = Article::findBySlug($slug);
        } catch (\Throwable) {
            $article = null;
        }
        if (!$article || empty($article['published'])) {
            not_found('Cet article n\'est plus en ligne.');
        }

        $published = (string) ($article['published_at'] ?? '');
        $iso = $published !== '' ? date('c', strtotime($published) ?: time()) : null;
        $cover = !empty($article['has_cover']) ? (string) $article['img'] : null;
        $jsonLd = [
            Seo::organization(),
            Seo::website(),
            Seo::breadcrumb([
                ['name' => 'Acteurs du Livre', 'url' => '/'],
                ['name' => 'Le journal', 'url' => '/journal'],
                ['name' => (string) $article['title'], 'url' => (string) $article['href']],
            ]),
            Seo::article($article),
        ];
        if (!empty($article['faqs']) && is_array($article['faqs'])) {
            $jsonLd[] = Seo::faqPage($article['faqs']);
        }
        View::page('article', [
            'title' => $article['title'],
            'slug' => $slug,
            'article' => $article,
            'meta' => Seo::build(
                (string) $article['title'],
                (string) ($article['excerpt'] ?: $article['chapo'] ?: $article['title']),
                Share::absolute((string) $article['href']),
                'article',
                $cover,
                [
                    'published_time' => $iso,
                    'modified_time' => $iso,
                    'section' => (string) ($article['cat'] ?? ''),
                    'image_alt' => (string) ($article['image_alt'] ?? $article['title']),
                    'json_ld' => $jsonLd,
                ]
            ),
        ]);
    }

    public function aide(Request $request): void
    {
        $query = $request->string('q');
        View::page('aide', [
            'title' => 'Centre d\'aide',
            'helpQuery' => $query,
            'reportError' => flash('error'),
            'reportSaved' => flash('saved'),
            'meta' => Seo::forScreen('aide'),
        ]);
    }

    public function newsletter(Request $request): void
    {
        try {
            $email = $request->string('email');
            $user = Auth::user();
            $immediate = $user !== null && strcasecmp((string) ($user['email'] ?? ''), $email) === 0;
            $result = Newsletter::subscribe($email, 'footer', $user ? (int) $user['id'] : null, $immediate);
            flash('saved', match ($result) {
                'already' => 'Cette adresse est déjà inscrite.',
                'confirmed' => 'Inscription enregistrée. Merci.',
                default => 'Vérifiez votre boîte : un e-mail de confirmation vient d’être envoyé.',
            });
        } catch (\Throwable $e) {
            flash('error', user_error_message($e));
        }
        $back = $request->string('back');
        redirect($back !== '' ? $back : '/');
    }

    public function newsletterConfirm(Request $request, string $token): void
    {
        $row = Newsletter::confirm($token);
        View::render('pages/newsletter-confirmer', [
            'title' => 'Inscription confirmée',
            'ok' => $row !== null,
            'meta' => [
                'title' => 'Inscription à la lettre — acteursdulivre.fr',
                'robots' => Seo::ROBOTS_NONE,
            ],
        ]);
    }

    public function newsletterUnsubscribe(Request $request, string $token): void
    {
        $row = Newsletter::unsubscribeByToken($token);
        if ($request->isPost()) {
            header('Content-Type: text/plain; charset=utf-8');
            header('Cache-Control: no-store');
            http_response_code(200);
            echo 'OK';
            return;
        }
        View::render('pages/newsletter-desinscription', [
            'title' => 'Désinscription',
            'ok' => $row !== null,
            'meta' => [
                'title' => 'Désinscription de la lettre — acteursdulivre.fr',
                'robots' => Seo::ROBOTS_NONE,
            ],
        ]);
    }

    public function report(Request $request): void
    {
        $viewer = Auth::user();
        try {
            $type = $request->string('type', 'user');
            if ($type === 'conversation') {
                throw new \RuntimeException('Signalez cette conversation depuis la messagerie.');
            }
            $body = $request->string('body');
            $link = $request->string('url');
            if ($link !== '' && safe_internal_path($link) === null && !preg_match('#^https?://#i', $link)) {
                $link = '';
            }
            if ($link !== '') {
                $body = ($body !== '' ? $body . "\n\n" : '') . 'Lien : ' . $link;
            }
            Report::create(
                $viewer ? (int) $viewer['id'] : null,
                $type,
                $request->int('id'),
                $request->string('reason'),
                $body
            );
            flash('saved', 'Signalement reçu. L\'équipe de modération le traitera.');
        } catch (\Throwable $e) {
            flash('error', user_error_message($e));
        }
        $back = $request->string('back');
        redirect($back !== '' ? $back : '/aide');
    }

    public function legal(Request $request): void
    {
        $doc = LegalPages::get(LegalPages::slugFromPath($request->path()));
        $path = $request->path();
        View::page('legal', [
            'title' => $doc['title'],
            'legalDoc' => $doc,
            'meta' => Seo::build(
                (string) $doc['title'],
                'Document juridique d\'acteursdulivre.fr : ' . $doc['title'] . '. Édité par EDITIONS TESSERACT. Mise à jour ' . $doc['updated'] . '.',
                Share::absolute($path),
                'website',
                null,
                [
                    'json_ld' => Seo::webPageGraph((string) $doc['title'], (string) $doc['title'], $path, 'legal', [
                        'breadcrumbs' => [
                            ['name' => 'Acteurs du Livre', 'url' => '/'],
                            ['name' => (string) $doc['title'], 'url' => $path],
                        ],
                    ]),
                ]
            ),
        ]);
    }

    public function contact(Request $request): void
    {
        View::page('contact', [
            'title' => 'Contact',
            'sent' => flash('contact_sent') ? true : false,
            'meta' => Seo::forScreen('contact'),
        ]);
    }

    private function renderCatalog(Request $request, string $forcedType): void
    {
        $query = $request->string('q');
        $type = $forcedType !== 'all' ? $forcedType : $request->string('type', 'all');
        if (!array_key_exists($type, Catalog::TYPES)) {
            $type = 'all';
        }
        $cat = $request->string('cat');
        if ($cat !== '') {
            $cat = Catalog::resolveTrade($cat) ?? $cat;
        }
        $availableOnly = $request->bool('dispo');
        $filters = self::searchFilters($request);
        $target = Catalog::redirectPath($request->path(), $query, $type, $cat, $availableOnly, $filters);
        if ($target !== null) {
            redirect($target, 301);
        }

        $found = Catalog::search($query, $type, $cat, 48, $availableOnly, $filters);
        $hub = Catalog::typePath($type);
        $indexable = $query === '' && $cat === '' && !$availableOnly && !Catalog::hasFacetFilters($filters);
        $copy = match ($type) {
            'prestations' => [
                'title' => 'Prestations des métiers du livre',
                'heading' => 'Prestations à prix affiché',
                'description' => 'Offres packagées : correction, illustration, traduction, maquette, impression. Prix, délai et périmètre affichés.',
            ],
            'prestataires' => [
                'title' => 'Prestataires des métiers du livre',
                'heading' => 'Prestataires du livre',
                'description' => 'Correcteurs, illustrateurs, traducteurs, maquettistes, imprimeurs : profils publics à parcourir.',
            ],
            default => [
                'title' => 'Annuaire des métiers du livre',
                'heading' => 'Tous les métiers du livre',
                'description' => 'Parcourez les prestataires, prestations et recherches ouvertes des métiers du livre.',
            ],
        };
        $heading = $query !== '' ? $query : ($cat !== '' ? $cat : $copy['heading']);

        View::page('resultats', [
            'title' => $query !== '' ? 'Recherche : ' . $query : $copy['title'],
            'query' => $query,
            'searchType' => $found['type'],
            'searchCat' => $found['cat'],
            'searchCount' => $found['count'],
            'searchResults' => $found['results'],
            'searchState' => $found,
            'availableOnly' => $availableOnly,
            'searchFilters' => $found['filters'] ?? $filters,
            'searchFacets' => $found['facets'] ?? [],
            'trades' => Catalog::trades(),
            'searchTypes' => Catalog::TYPES,
            'catalogHeading' => $heading,
            'meta' => Seo::build(
                $query !== '' ? 'Recherche : ' . $heading : $copy['title'],
                $copy['description'],
                Share::absolute($hub),
                'website',
                null,
                [
                    'robots' => $indexable ? Seo::ROBOTS_INDEX : 'noindex, follow',
                    'json_ld' => $indexable ? Seo::webPageGraph(
                        $copy['title'],
                        $copy['description'],
                        $hub,
                        'resultats'
                    ) : [],
                ]
            ),
        ]);
    }

    public function contactSubmit(Request $request): void
    {
        $email = $request->string('email');
        $name = $request->string('name');
        $message = $request->string('message');
        $old = ['name' => $name, 'email' => $email, 'message' => $message];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '') {
            flash('error', 'Merci d\'indiquer un e-mail valide et votre message.');
            $_SESSION['_old'] = $old;
            redirect('/contact');
        }

        try {
            Mailer::sendTemplate('contact-interne', Mailer::fromAddress(), [
                'nom' => $name !== '' ? $name : 'Visiteur',
                'email' => $email,
                'message' => $message,
            ]);
        } catch (\Throwable $e) {
            try {
                Mailer::send(
                    Mailer::fromAddress(),
                    'Message de contact',
                    '<p><strong>' . e($name) . '</strong> (' . e($email) . ')</p><p>' . nl2br(e($message)) . '</p>'
                );
            } catch (\Throwable) {
                flash('error', 'Le message n\'a pas pu être envoyé. Réessayez dans un instant.');
                $_SESSION['_old'] = $old;
                redirect('/contact');
            }
        }

        try {
            Mailer::sendTemplate('contact-accuse', $email, [
                'nom' => $name !== '' ? $name : 'bonjour',
            ]);
        } catch (\Throwable) {
        }

        unset($_SESSION['_old']);
        flash('contact_sent', true);
        redirect('/contact');
    }

    /** @return array<string, mixed> */
    private static function searchFilters(Request $request): array
    {
        return [
            'kinds' => $request->strings('kind'),
            'metiers' => $request->strings('metier'),
            'specs' => $request->strings('spec'),
            'delays' => $request->strings('delay'),
            'levels' => $request->strings('level'),
            'trust' => $request->strings('trust'),
            'bmin' => $request->int('bmin'),
            'bmax' => $request->int('bmax'),
        ];
    }
}
