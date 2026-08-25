<?php

declare(strict_types=1);

namespace Adl\Controllers;

use Adl\Core\Auth;
use Adl\Core\Request;
use Adl\Core\View;
use Adl\Models\User;

final class AccountController
{
    public function dashboard(Request $request): void
    {
        Auth::requireUser();
        View::page('dashboard', [
            'title' => 'Tableau de bord',
            'error' => flash('error'),
        ]);
    }

    public function publier(Request $request): void
    {
        Auth::requireSeeker();
        View::page('publier', ['title' => 'Publier une mission']);
    }

    public function commande(Request $request): void
    {
        Auth::requireSeeker();
        View::page('commande', ['title' => 'Commande & paiement']);
    }

    public function suivi(Request $request): void
    {
        Auth::requireUser();
        View::page('suivi', ['title' => 'Suivi de commande']);
    }

    public function commandes(Request $request): void
    {
        Auth::requireSeeker();
        View::page('commandes', ['title' => 'Mes commandes']);
    }

    public function missions(Request $request): void
    {
        Auth::requireSeeker();
        View::page('mesmissions', ['title' => 'Mes missions']);
    }

    public function candidatures(Request $request): void
    {
        Auth::requireOfferer();
        View::page('candidatures', ['title' => 'Mes candidatures']);
    }

    public function prestations(Request $request): void
    {
        Auth::requireOfferer();
        View::page('mesprestations', ['title' => 'Mes prestations']);
    }

    public function creer(Request $request): void
    {
        Auth::requireOfferer();
        View::page('creer', ['title' => 'Créer une prestation']);
    }

    public function messages(Request $request): void
    {
        Auth::requireUser();
        View::page('messagerie', ['title' => 'Messagerie']);
    }

    public function notifications(Request $request): void
    {
        Auth::requireUser();
        View::page('notifications', ['title' => 'Notifications']);
    }

    public function favoris(Request $request): void
    {
        Auth::requireSeeker();
        View::page('favoris', ['title' => 'Favoris']);
    }

    public function avis(Request $request): void
    {
        Auth::requireUser();
        View::page('avis', ['title' => 'Laisser un avis']);
    }

    public function vitrine(Request $request): void
    {
        Auth::requireOfferer();
        View::page('vitrine', ['title' => 'Ma vitrine']);
    }

    public function parametres(Request $request): void
    {
        $user = Auth::requireUser();
        View::page('parametres', [
            'title' => 'Paramètres',
            'prenom' => $user['first_name'],
            'nom' => $user['last_name'],
            'seeksChecked' => User::seeksServices($user),
            'offersChecked' => User::offersServices($user),
            'saved' => flash('saved') ? true : false,
            'error' => flash('error'),
        ]);
    }

    public function parametresSave(Request $request): void
    {
        $user = Auth::requireUser();
        $seeks = $request->bool('seeks_services');
        $offers = $request->bool('offers_services');

        if (!$seeks && !$offers) {
            flash('error', 'Conservez au moins un usage : chercher des prestataires ou proposer vos services.');
            redirect('/espace/parametres');
        }

        if ($offers) {
            User::ensureProfile((int) $user['id']);
        }

        User::update((int) $user['id'], [
            'first_name' => $request->string('first_name', $user['first_name']),
            'last_name' => $request->string('last_name', $user['last_name']),
            'seeks_services' => $seeks ? 1 : 0,
            'offers_services' => $offers ? 1 : 0,
            'role' => ($user['role'] ?? '') === 'admin'
                ? 'admin'
                : User::roleFromIntents($seeks, $offers),
        ]);
        flash('saved', true);
        redirect('/espace/parametres');
    }

    public function facturation(Request $request): void
    {
        Auth::requireOfferer();
        View::page('facturation', ['title' => 'Facturation']);
    }
}
