<?php

declare(strict_types=1);

namespace Adl\Controllers;

use Adl\Core\Auth;
use Adl\Core\Mailer;
use Adl\Core\Request;
use Adl\Core\View;
use Adl\Data\Catalog;
use Adl\Data\LegalPages;
use Adl\Data\Share;
use Adl\Models\Article;
use Adl\Models\Profile;
use Adl\Models\Service;
use Adl\Models\User;

final class PageController
{
    public function home(Request $request): void
    {
        View::page('accueil', ['title' => 'La place de marché des métiers du livre']);
    }

    public function search(Request $request): void
    {
        $query = $request->string('q');
        $type = $request->string('type', 'all');
        $cat = $request->string('cat');
        $availableOnly = $request->bool('dispo');
        $found = Catalog::search($query, $type, $cat, 48, $availableOnly);
        $heading = $query !== '' ? $query : ($cat !== '' ? $cat : 'Tous les métiers du livre');
        $qs = http_build_query(array_filter([
            'q' => $query !== '' ? $query : null,
            'type' => $type !== '' && $type !== 'all' ? $type : null,
            'cat' => $cat !== '' ? $cat : null,
            'dispo' => $availableOnly ? '1' : null,
        ]));

        View::page('resultats', [
            'title' => $query !== '' ? 'Recherche : ' . $query : 'Recherche de prestataires',
            'query' => $query,
            'searchType' => $found['type'],
            'searchCat' => $found['cat'],
            'searchCount' => $found['count'],
            'searchResults' => $found['results'],
            'searchState' => $found,
            'availableOnly' => $availableOnly,
            'trades' => Catalog::trades(),
            'searchTypes' => Catalog::TYPES,
            'meta' => Share::meta(
                'Recherche : ' . $heading,
                $cat !== ''
                    ? 'Prestataires « ' . $cat . ' » sur acteursdulivre.fr — profils, prestations et missions.'
                    : 'Recherche de prestataires des métiers du livre sur acteursdulivre.fr.',
                Share::absolute('/recherche' . ($qs !== '' ? '?' . $qs : ''))
            ),
        ]);
    }

    public function searchApi(Request $request): void
    {
        $found = Catalog::search(
            $request->string('q'),
            $request->string('type', 'all'),
            $request->string('cat'),
            max(1, min(48, $request->int('limit', 24) ?? 24)),
            $request->bool('dispo')
        );
        foreach (['results', 'suggestions'] as $key) {
            foreach ($found[$key] as $i => $item) {
                $found[$key][$i]['href'] = url((string) ($item['href'] ?? '/recherche'));
            }
        }
        json_response($found);
    }

    public function metier(Request $request, string $slug): void
    {
        $trade = Catalog::tradeFromSlug($slug);
        if ($trade === null) {
            not_found('Ce métier n\'est pas répertorié.');
        }

        $providers = Catalog::search('', 'prestataires', $trade);
        $services = Catalog::search('', 'prestations', $trade);
        $missions = Catalog::search('', 'missions', $trade);
        $label = Catalog::TRADE_LABELS[$trade] ?? $trade;

        View::page('metier', [
            'title' => $trade . ' — métier du livre',
            'slug' => $slug,
            'trade' => $trade,
            'tradeLabel' => $label,
            'providers' => $providers['results'],
            'services' => $services['results'],
            'missions' => $missions['results'],
            'meta' => Share::meta(
                $trade . ' — acteursdulivre.fr',
                'Prestataires, prestations et missions pour le métier « ' . $trade . ' ».',
                Share::absolute('/metiers/' . $slug)
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
        if (
            !$service
            || ($service['status'] ?? '') !== 'published'
            || !User::isPublicOfferer((int) ($service['user_id'] ?? 0))
        ) {
            not_found('Cette prestation n\'est plus disponible.');
        }

        View::page('fiche', [
            'title' => $service['title'],
            'slug' => $slug,
            'service' => $service,
            'meta' => Share::meta(
                $service['title'],
                trim((string) ($service['excerpt'] ?: $service['by'] . ' · ' . $service['price'])),
                Share::absolute($service['href']),
                'website',
                !empty($service['has_image']) ? $service['img'] : null
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
        View::page('profil', [
            'title' => $public['name'],
            'slug' => $slug,
            'liveProfile' => $public,
            'meta' => Share::meta(
                $public['name'] . ' — ' . ($public['title'] ?: 'Prestataire'),
                $excerpt !== ''
                    ? mb_substr($excerpt, 0, 180)
                    : trim($public['title'] . ($public['city'] ? ' · ' . $public['city'] : '') . ' · prestataire sur acteursdulivre.fr'),
                Share::absolute($public['href']),
                'profile'
            ),
        ]);
    }

    public function missions(Request $request): void
    {
        $cat = $request->string('cat');
        $found = Catalog::search('', 'missions', $cat);
        View::page('missions', [
            'title' => 'Appels d\'offres',
            'searchCat' => $cat,
            'liveMissions' => $found['results'],
            'trades' => Catalog::trades(),
        ]);
    }

    public function mission(Request $request, string $slug): void
    {
        $mission = Catalog::mission($slug);
        if (!$mission) {
            not_found('Cette mission n\'est plus disponible.');
        }
        if (($mission['status'] ?? '') === 'draft') {
            $viewer = Auth::user();
            $owner = $viewer && (int) ($viewer['id'] ?? 0) === (int) ($mission['user_id'] ?? 0);
            $admin = $viewer && ($viewer['role'] ?? '') === 'admin';
            if (!$owner && !$admin) {
                not_found('Cette mission n\'est plus disponible.');
            }
        }

        View::page('mission', [
            'title' => $mission['title'],
            'slug' => $slug,
            'liveMission' => $mission,
            'suggestions' => Catalog::suggestionsForTrade((string) ($mission['category_name'] ?? '')),
        ]);
    }

    public function comment(Request $request): void
    {
        View::page('comment', ['title' => 'Comment ça marche']);
    }

    public function tarifs(Request $request): void
    {
        View::page('tarifs', ['title' => 'Tarifs']);
    }

    public function confiance(Request $request): void
    {
        View::page('confiance', ['title' => 'Confiance & sécurité']);
    }

    public function apropos(Request $request): void
    {
        View::page('apropos', ['title' => 'À propos']);
    }

    public function journal(Request $request): void
    {
        try {
            $articles = Article::published();
        } catch (\Throwable) {
            $articles = [];
        }
        View::page('journal', [
            'title' => 'Le journal',
            'articles' => $articles,
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

        View::page('article', [
            'title' => $article['title'],
            'slug' => $slug,
            'article' => $article,
            'meta' => Share::meta(
                $article['title'],
                (string) ($article['excerpt'] ?: $article['chapo']),
                Share::absolute($article['href']),
                'article'
            ),
        ]);
    }

    public function aide(Request $request): void
    {
        View::page('aide', ['title' => 'Centre d\'aide']);
    }

    public function legal(Request $request): void
    {
        $doc = LegalPages::get(LegalPages::slugFromPath($request->path()));
        View::page('legal', [
            'title' => $doc['title'],
            'legalDoc' => $doc,
        ]);
    }

    public function contact(Request $request): void
    {
        View::page('contact', [
            'title' => 'Contact',
            'sent' => flash('contact_sent') ? true : false,
        ]);
    }

    public function contactSubmit(Request $request): void
    {
        $email = $request->string('email');
        $name = $request->string('name');
        $message = $request->string('message');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '') {
            flash('error', 'Merci d\'indiquer un e-mail valide et votre message.');
            redirect('/contact');
        }

        try {
            Mailer::sendTemplate('contact-interne', \Adl\Core\Env::get('MAIL_FROM_ADDRESS', 'bonjour@acteursdulivre.fr'), [
                'nom' => $name !== '' ? $name : 'Visiteur',
                'email' => $email,
                'message' => nl2br(e($message)),
            ]);
            Mailer::sendTemplate('contact-accuse', $email, [
                'nom' => $name !== '' ? $name : 'bonjour',
            ]);
        } catch (\Throwable $e) {
            try {
                Mailer::send(
                    \Adl\Core\Env::get('MAIL_FROM_ADDRESS', 'bonjour@acteursdulivre.fr'),
                    'Message de contact',
                    '<p><strong>' . e($name) . '</strong> (' . e($email) . ')</p><p>' . nl2br(e($message)) . '</p>'
                );
            } catch (\Throwable) {
                flash('error', 'Le message n\'a pas pu être envoyé. Réessayez dans un instant.');
                redirect('/contact');
            }
        }

        flash('contact_sent', true);
        redirect('/contact');
    }
}
