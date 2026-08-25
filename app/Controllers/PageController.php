<?php

declare(strict_types=1);

namespace Adl\Controllers;

use Adl\Core\Mailer;
use Adl\Core\Request;
use Adl\Core\View;
use Adl\Data\Catalog;
use Adl\Data\LegalPages;
use Adl\Models\Profile;

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
        $found = Catalog::search($query, $type, $cat);

        View::page('resultats', [
            'title' => $query !== '' ? 'Recherche : ' . $query : 'Recherche',
            'query' => $query,
            'searchType' => $found['type'],
            'searchCat' => $found['cat'],
            'searchCount' => $found['count'],
            'searchResults' => $found['results'],
            'searchState' => $found,
            'trades' => Catalog::trades(),
            'searchTypes' => Catalog::TYPES,
        ]);
    }

    public function searchApi(Request $request): void
    {
        $found = Catalog::search(
            $request->string('q'),
            $request->string('type', 'all'),
            $request->string('cat'),
            max(1, min(48, $request->int('limit', 24) ?? 24))
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
        View::page('metier', ['title' => 'Correction — métier du livre', 'slug' => $slug]);
    }

    public function fiche(Request $request, string $slug): void
    {
        View::page('fiche', ['title' => 'Correction complète d\'un roman', 'slug' => $slug]);
    }

    public function profil(Request $request, string $slug): void
    {
        try {
            $profile = Profile::findBySlug($slug);
        } catch (\Throwable) {
            $profile = null;
        }

        if ($profile && !empty($profile['offers_services'])) {
            $public = Catalog::profileToPublic($profile);
            View::page('profil', [
                'title' => $public['name'],
                'slug' => $slug,
                'liveProfile' => $public,
            ]);
            return;
        }

        $provider = Catalog::provider($slug);
        if ($provider) {
            View::page('profil', [
                'title' => $provider['title'],
                'slug' => $slug,
                'catalogProfile' => $provider,
            ]);
            return;
        }

        View::page('profil', ['title' => 'Marion Vasseur', 'slug' => $slug]);
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
        if ($mission && !empty($mission['live'])) {
            View::page('mission', [
                'title' => $mission['title'],
                'slug' => $slug,
                'liveMission' => $mission,
                'suggestions' => Catalog::suggestionsForTrade((string) ($mission['category_name'] ?? '')),
            ]);
            return;
        }

        View::page('mission', [
            'title' => $mission['title'] ?? 'Détail de la mission',
            'slug' => $slug,
            'catalogMission' => $mission,
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
        View::page('journal', ['title' => 'Le journal']);
    }

    public function article(Request $request, string $slug): void
    {
        View::page('article', ['title' => 'Combien coûte vraiment la fabrication d\'un roman', 'slug' => $slug]);
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

        if ($email === '' || $message === '') {
            flash('error', 'Merci d\'indiquer votre e-mail et votre message.');
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
            Mailer::send(
                \Adl\Core\Env::get('MAIL_FROM_ADDRESS', 'bonjour@acteursdulivre.fr'),
                'Message de contact',
                '<p><strong>' . e($name) . '</strong> (' . e($email) . ')</p><p>' . nl2br(e($message)) . '</p>'
            );
        }

        flash('contact_sent', true);
        redirect('/contact');
    }
}
