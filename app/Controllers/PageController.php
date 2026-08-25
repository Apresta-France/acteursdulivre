<?php

declare(strict_types=1);

namespace Adl\Controllers;

use Adl\Core\Mailer;
use Adl\Core\Request;
use Adl\Core\View;

final class PageController
{
    public function home(Request $request): void
    {
        View::page('accueil', ['title' => 'La place de marché des métiers du livre']);
    }

    public function search(Request $request): void
    {
        View::page('resultats', [
            'title' => 'Recherche',
            'query' => $request->string('q', 'correction roman'),
        ]);
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
        View::page('profil', ['title' => 'Marion Vasseur', 'slug' => $slug]);
    }

    public function missions(Request $request): void
    {
        View::page('missions', ['title' => 'Appels d\'offres']);
    }

    public function mission(Request $request, string $slug): void
    {
        View::page('mission', ['title' => 'Détail de la mission', 'slug' => $slug]);
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
        View::page('legal', ['title' => 'Mentions légales']);
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
