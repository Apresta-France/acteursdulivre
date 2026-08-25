<?php

declare(strict_types=1);

namespace Adl\Controllers;

use Adl\Core\Auth;
use Adl\Core\Mailer;
use Adl\Core\Request;
use Adl\Core\View;
use Adl\Models\User;

final class AuthController
{
    public function loginForm(Request $request): void
    {
        if (Auth::check()) {
            redirect('/espace');
        }
        View::page('connexion', [
            'title' => 'Connexion',
            'error' => flash('error'),
        ]);
    }

    public function login(Request $request): void
    {
        $email = $request->string('email');
        $password = $request->string('password');

        if (!Auth::attempt($email, $password)) {
            flash('error', 'E-mail ou mot de passe incorrect.');
            $_SESSION['_old'] = ['email' => $email];
            redirect('/connexion');
        }

        $intended = $_SESSION['_intended'] ?? '/espace';
        unset($_SESSION['_intended'], $_SESSION['_old']);
        redirect($intended);
    }

    public function registerForm(Request $request): void
    {
        if (Auth::check()) {
            redirect('/espace');
        }
        View::page('inscription', [
            'title' => 'Inscription',
            'error' => flash('error'),
        ]);
    }

    public function register(Request $request): void
    {
        $email = strtolower($request->string('email'));
        $password = $request->string('password');
        $first = $request->string('first_name');
        $last = $request->string('last_name');
        $role = $request->string('role', 'prestataire');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8 || $first === '' || $last === '') {
            flash('error', 'Merci de renseigner un e-mail valide, un mot de passe de 8 caractères, un prénom et un nom.');
            redirect('/inscription');
        }

        if (User::findByEmail($email)) {
            flash('error', 'Un compte existe déjà avec cet e-mail.');
            redirect('/inscription');
        }

        if (!in_array($role, ['client', 'prestataire'], true)) {
            $role = 'prestataire';
        }

        $id = User::create([
            'email' => $email,
            'password' => $password,
            'first_name' => $first,
            'last_name' => $last,
            'role' => $role,
        ]);

        $user = User::find($id);
        Auth::login($user);

        try {
            Mailer::sendTemplate('bienvenue', $email, [
                'prenom' => $first,
                'lien_espace' => url('/espace'),
            ]);
        } catch (\Throwable) {
            // l'inscription reste valide si l'e-mail échoue
        }

        redirect('/espace');
    }

    public function logout(Request $request): void
    {
        Auth::logout();
        redirect('/');
    }
}
