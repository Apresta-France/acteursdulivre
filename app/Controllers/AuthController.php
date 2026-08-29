<?php

declare(strict_types=1);

namespace Adl\Controllers;

use Adl\Core\Auth;
use Adl\Core\Mailer;
use Adl\Core\OAuth;
use Adl\Core\Request;
use Adl\Core\View;
use Adl\Data\Onboarding;
use Adl\Models\LegalAcceptance;
use Adl\Models\User;
use Throwable;

final class AuthController
{
    public function loginForm(Request $request): void
    {
        if (Auth::check()) {
            redirect(Onboarding::homePath(Auth::user() ?? []));
        }
        View::page('connexion', [
            'title' => 'Connexion',
            'error' => flash('error'),
            'meta' => \Adl\Data\Seo::forScreen('connexion'),
        ]);
    }

    public function login(Request $request): void
    {
        $email = $request->string('email');
        $password = $request->string('password');

        $existing = User::findByEmail($email);
        if ($existing && User::isOauthOnly($existing)) {
            flash('error', 'Ce compte se connecte avec Google ou Facebook.');
            $_SESSION['_old'] = ['email' => $email];
            redirect('/connexion');
        }

        if (!Auth::attempt($email, $password, $request->bool('remember'))) {
            flash('error', 'E-mail ou mot de passe incorrect.');
            $_SESSION['_old'] = ['email' => $email];
            redirect('/connexion');
        }

        $intended = $_SESSION['_intended'] ?? '/espace';
        unset($_SESSION['_intended'], $_SESSION['_old']);
        $user = Auth::user();
        if ($user && Onboarding::isPending($user) && in_array($intended, ['/espace', '/', ''], true)) {
            $intended = '/espace/bienvenue';
        }
        redirect($intended);
    }

    public function registerForm(Request $request): void
    {
        if (Auth::check()) {
            redirect(Onboarding::homePath(Auth::user() ?? []));
        }
        View::page('inscription', [
            'title' => 'Créer un compte professionnel',
            'error' => flash('error'),
            'meta' => \Adl\Data\Seo::forScreen('inscription'),
        ]);
    }

    public function register(Request $request): void
    {
        $email = strtolower($request->string('email'));
        $password = $request->string('password');
        $first = $request->string('first_name');
        $last = $request->string('last_name');
        $seeks = $request->bool('seeks_services');
        $offers = $request->bool('offers_services');

        $remember = static function () use ($email, $first, $last, $seeks, $offers): void {
            $_SESSION['_old'] = [
                'email' => $email,
                'first_name' => $first,
                'last_name' => $last,
                'seeks_services' => $seeks ? '1' : '',
                'offers_services' => $offers ? '1' : '',
            ];
        };

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8 || $first === '' || $last === '') {
            flash('error', 'Merci de renseigner un e-mail valide, un mot de passe de 8 caractères, un prénom et un nom.');
            $remember();
            redirect('/inscription');
        }

        if (!$seeks && !$offers) {
            flash('error', 'Choisissez au moins un usage : chercher des prestataires, proposer vos services, ou les deux.');
            $remember();
            redirect('/inscription');
        }

        if (!$request->bool('charte')) {
            flash('error', 'L\'acceptation des CGU, des CGV et de la politique de confidentialité est obligatoire.');
            $remember();
            redirect('/inscription');
        }

        if ($offers && !$request->bool('charte_ia')) {
            flash('error', 'Pour proposer vos services, l\'engagement sans IA générative est obligatoire.');
            $remember();
            redirect('/inscription');
        }

        if (User::findByEmail($email)) {
            flash('error', 'Un compte existe déjà avec cet e-mail.');
            $remember();
            redirect('/inscription');
        }

        $id = User::create([
            'email' => $email,
            'password' => $password,
            'first_name' => $first,
            'last_name' => $last,
            'seeks_services' => $seeks,
            'offers_services' => $offers,
            'role' => User::roleFromIntents($seeks, $offers),
        ]);

        unset($_SESSION['_old']);
        $user = User::find($id) ?? ['id' => $id, 'onboarding_done_at' => null];
        Auth::login($user);
        self::persistSignupAcceptances($id, $offers);

        try {
            Mailer::sendTemplate('bienvenue', $email, [
                'prenom' => $first,
                'lien_espace' => url('/espace/bienvenue'),
            ]);
        } catch (\Throwable) {
            // l'inscription reste valide si l'e-mail échoue
        }

        redirect(Onboarding::homePath($user ?? ['onboarding_done_at' => null]));
    }

    public function logout(Request $request): void
    {
        Auth::logout();
        redirect('/');
    }

    public function forgotForm(Request $request): void
    {
        if (Auth::check()) {
            redirect('/espace');
        }
        View::page('mot-de-passe-oublie', [
            'title' => 'Mot de passe oublié',
            'sent' => flash('saved') ? true : false,
            'error' => flash('error'),
            'meta' => \Adl\Data\Seo::forScreen('connexion'),
        ]);
    }

    public function forgot(Request $request): void
    {
        $email = strtolower($request->string('email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Indiquez l\'e-mail de votre compte.');
            redirect('/mot-de-passe-oublie');
        }
        try {
            $token = User::requestPasswordReset($email);
            if ($token) {
                $user = User::findByEmail($email);
                Mailer::sendTemplate('reset-password', $email, [
                    'prenom' => (string) ($user['first_name'] ?? ''),
                    'lien' => url('/mot-de-passe/' . $token),
                ]);
            }
        } catch (Throwable) {
        }
        flash('saved', true);
        redirect('/mot-de-passe-oublie');
    }

    public function resetForm(Request $request, string $token): void
    {
        if (Auth::check()) {
            redirect('/espace');
        }
        $user = User::consumePasswordReset($token);
        if (!$user) {
            flash('error', 'Ce lien a expiré. Demandez un nouveau message.');
            redirect('/mot-de-passe-oublie');
        }
        View::page('mot-de-passe', [
            'title' => 'Nouveau mot de passe',
            'token' => $token,
            'error' => flash('error'),
            'meta' => \Adl\Data\Seo::forScreen('connexion'),
        ]);
    }

    public function reset(Request $request, string $token): void
    {
        $user = User::consumePasswordReset($token);
        if (!$user) {
            flash('error', 'Ce lien a expiré. Demandez un nouveau message.');
            redirect('/mot-de-passe-oublie');
        }
        $password = $request->string('password');
        $confirm = $request->string('password_confirmation');
        if (strlen($password) < 8) {
            flash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
            redirect('/mot-de-passe/' . rawurlencode($token));
        }
        if ($password !== $confirm) {
            flash('error', 'Les deux mot de passe ne correspondent pas.');
            redirect('/mot-de-passe/' . rawurlencode($token));
        }
        User::setPassword((int) $user['id'], $password);
        User::clearPasswordReset((string) $user['email']);
        Auth::login($user);
        flash('saved', 'Votre mot de passe a été mis à jour.');
        redirect('/espace');
    }

    public function oauthStart(Request $request, string $provider): void
    {
        if (!in_array($provider, OAuth::PROVIDERS, true) || !OAuth::enabled($provider)) {
            flash('error', 'Cette connexion n\'est pas disponible pour le moment.');
            redirect(Auth::check() ? '/espace/parametres' : '/connexion');
        }
        if ($request->string('next') === 'parametres' && Auth::check()) {
            $_SESSION['_oauth_return'] = '/espace/parametres';
        }
        header('Location: ' . OAuth::authorizationUrl($provider), true, 302);
        exit;
    }

    public function oauthCallback(Request $request, string $provider): void
    {
        $fail = (string) ($_SESSION['_oauth_return'] ?? '/connexion');
        if (!in_array($provider, OAuth::PROVIDERS, true) || !OAuth::enabled($provider)) {
            flash('error', 'Cette connexion n\'est pas disponible pour le moment.');
            redirect($fail);
        }

        if ($request->string('error') !== '') {
            flash('error', 'Connexion ' . OAuth::label($provider) . ' annulée.');
            unset($_SESSION['_oauth_return']);
            redirect($fail);
        }

        if (!OAuth::consumeState($provider, $request->string('state'))) {
            flash('error', 'Session de connexion expirée. Merci de réessayer.');
            unset($_SESSION['_oauth_return']);
            redirect('/connexion');
        }

        try {
            $profile = OAuth::fetchProfile($provider, $request->string('code'));
        } catch (Throwable) {
            flash('error', 'Impossible de finaliser la connexion ' . OAuth::label($provider) . '. Réessayez dans un instant.');
            unset($_SESSION['_oauth_return']);
            redirect($fail);
        }

        $current = Auth::user();
        if ($current) {
            $this->linkLoggedIn($current, $profile);
            return;
        }

        $byProvider = User::findByProvider($provider, $profile['provider_id']);
        if ($byProvider) {
            $this->loginExisting($byProvider);
            return;
        }

        if ($profile['email'] === '') {
            flash('error', OAuth::label($provider) . ' n\'a pas transmis d\'e-mail. Autorisez le partage de l\'adresse, ou inscrivez-vous avec un mot de passe.');
            redirect('/inscription');
        }

        $byEmail = User::findByEmail($profile['email']);
        if ($byEmail) {
            flash('error', 'Un compte existe déjà avec cet e-mail. Connectez-vous, puis liez ' . OAuth::label($provider) . ' depuis vos paramètres.');
            $_SESSION['_old'] = ['email' => $profile['email']];
            unset($_SESSION['_oauth_return']);
            redirect('/connexion');
        }

        $_SESSION['_oauth_pending'] = $profile + ['expires' => time() + 900];
        redirect('/inscription/sso');
    }

    public function completeSsoForm(Request $request): void
    {
        if (Auth::check()) {
            redirect(Onboarding::homePath(Auth::user() ?? []));
        }
        if (!OAuth::featureEnabled()) {
            unset($_SESSION['_oauth_pending']);
            flash('error', 'La connexion Google ou Facebook n\'est pas disponible pour le moment.');
            redirect('/inscription');
        }
        $pending = $this->pendingProfile();
        if (!$pending) {
            flash('error', 'Reprenez la connexion Google ou Facebook pour créer votre compte.');
            redirect('/inscription');
        }
        View::page('inscription-sso', [
            'title' => 'Finaliser l\'inscription',
            'error' => flash('error'),
            'pending' => $pending,
        ]);
    }

    public function completeSso(Request $request): void
    {
        if (!OAuth::featureEnabled()) {
            unset($_SESSION['_oauth_pending']);
            flash('error', 'La connexion Google ou Facebook n\'est pas disponible pour le moment.');
            redirect('/inscription');
        }
        $pending = $this->pendingProfile();
        if (!$pending) {
            flash('error', 'Reprenez la connexion Google ou Facebook pour créer votre compte.');
            redirect('/inscription');
        }

        $seeks = $request->bool('seeks_services');
        $offers = $request->bool('offers_services');

        if (!$seeks && !$offers) {
            flash('error', 'Choisissez au moins un usage : chercher des prestataires, proposer vos services, ou les deux.');
            redirect('/inscription/sso');
        }
        if ($offers && !$request->bool('charte_ia')) {
            flash('error', 'Pour proposer vos services, l\'engagement sans IA générative est obligatoire.');
            redirect('/inscription/sso');
        }
        if (!$request->bool('charte')) {
            flash('error', 'L\'acceptation des CGU, des CGV et de la politique de confidentialité est obligatoire.');
            redirect('/inscription/sso');
        }
        if (User::findByEmail($pending['email'])) {
            unset($_SESSION['_oauth_pending']);
            flash('error', 'Un compte existe déjà avec cet e-mail. Connectez-vous.');
            redirect('/connexion');
        }

        $provider = (string) $pending['provider'];
        $id = User::create([
            'email' => $pending['email'],
            'first_name' => $pending['first_name'],
            'last_name' => $pending['last_name'],
            'seeks_services' => $seeks,
            'offers_services' => $offers,
            'role' => User::roleFromIntents($seeks, $offers),
            OAuth::column($provider) => $pending['provider_id'],
            'avatar_url' => $pending['avatar_url'] !== '' ? $pending['avatar_url'] : null,
        ]);

        unset($_SESSION['_oauth_pending'], $_SESSION['_old']);
        $user = User::find($id) ?? ['id' => $id, 'onboarding_done_at' => null];
        Auth::login($user);
        self::persistSignupAcceptances($id, $offers);

        try {
            Mailer::sendTemplate('bienvenue', $pending['email'], [
                'prenom' => $pending['first_name'],
                'lien_espace' => url('/espace/bienvenue'),
            ]);
        } catch (Throwable) {
        }

        redirect(Onboarding::homePath($user));
    }

    /** @param array<string, mixed> $user */
    private function loginExisting(array $user): void
    {
        if (($user['status'] ?? 'active') !== 'active') {
            flash('error', 'Ce compte n\'est pas actif. Contactez-nous si besoin.');
            unset($_SESSION['_oauth_return']);
            redirect('/connexion');
        }
        Auth::login($user);
        $intended = $_SESSION['_oauth_return'] ?? $_SESSION['_intended'] ?? '/espace';
        unset($_SESSION['_oauth_return'], $_SESSION['_intended'], $_SESSION['_old']);
        if (Onboarding::isPending($user) && in_array($intended, ['/espace', '/', ''], true)) {
            $intended = '/espace/bienvenue';
        }
        redirect($intended);
    }

    /**
     * @param array<string, mixed> $current
     * @param array{provider: string, provider_id: string, email: string, first_name: string, last_name: string, avatar_url: string} $profile
     */
    private function linkLoggedIn(array $current, array $profile): void
    {
        $other = User::findByProvider($profile['provider'], $profile['provider_id']);
        if ($other && (int) $other['id'] !== (int) $current['id']) {
            flash('error', 'Ce compte ' . OAuth::label($profile['provider']) . ' est déjà lié à un autre utilisateur.');
            unset($_SESSION['_oauth_return']);
            redirect('/espace/parametres');
        }
        User::linkProvider((int) $current['id'], $profile['provider'], $profile['provider_id'], $profile['avatar_url']);
        unset($_SESSION['_oauth_return']);
        flash('saved', true);
        redirect('/espace/parametres');
    }

    private static function persistSignupAcceptances(int $userId, bool $offers): void
    {
        try {
            $docs = ['cgu', 'cgv', 'confidentialite'];
            if ($offers) {
                $docs[] = 'charte_ia';
            }
            LegalAcceptance::recordMany($userId, $docs, 'register');
        } catch (Throwable) {
        }
    }

    /** @return array{provider: string, provider_id: string, email: string, first_name: string, last_name: string, avatar_url: string}|null */
    private function pendingProfile(): ?array
    {
        $pending = $_SESSION['_oauth_pending'] ?? null;
        if (!is_array($pending) || (int) ($pending['expires'] ?? 0) < time()) {
            unset($_SESSION['_oauth_pending']);
            return null;
        }
        if (($pending['email'] ?? '') === '' || ($pending['provider_id'] ?? '') === '') {
            unset($_SESSION['_oauth_pending']);
            return null;
        }
        return $pending;
    }
}
