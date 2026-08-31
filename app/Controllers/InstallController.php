<?php

declare(strict_types=1);

namespace Adl\Controllers;

use Adl\Core\Database;
use Adl\Core\Env;
use Adl\Core\Mailer;
use Adl\Core\Migrator;
use Adl\Core\Request;
use Adl\Core\View;
use Adl\Models\Setting;
use Adl\Models\User;
use PDO;
use Throwable;

final class InstallController
{
    public function index(Request $request): void
    {
        if (is_file(ADL_ROOT . '/.env')) {
            redirect('/');
        }
        View::render('install/wizard', [
            'title' => 'Installation',
            'error' => flash('error'),
            'values' => $_SESSION['_old'] ?? $this->defaults(),
        ], 'layouts/install');
    }

    public function store(Request $request): void
    {
        if (is_file(ADL_ROOT . '/.env')) {
            redirect('/');
        }

        $values = [
            'APP_NAME' => $request->string('APP_NAME', 'Acteurs du Livre'),
            'APP_URL' => rtrim($request->string('APP_URL', 'https://acteursdulivre.test'), '/'),
            'APP_ENV' => 'local',
            'APP_DEBUG' => 'true',
            'APP_KEY' => bin2hex(random_bytes(16)),
            'APP_TIMEZONE' => 'Europe/Paris',
            'DB_HOST' => $request->string('DB_HOST', '127.0.0.1'),
            'DB_PORT' => $request->string('DB_PORT', '3306'),
            'DB_NAME' => preg_replace('/[^a-zA-Z0-9_]/', '', $request->string('DB_NAME', 'acteursdulivre')) ?: 'acteursdulivre',
            'DB_USER' => $request->string('DB_USER', 'root'),
            'DB_PASS' => $request->string('DB_PASS', ''),
            'DB_CHARSET' => 'utf8mb4',
            'MAIL_HOST' => $request->string('MAIL_HOST', ''),
            'MAIL_PORT' => $request->string('MAIL_PORT', '587'),
            'MAIL_USERNAME' => $request->string('MAIL_USERNAME', ''),
            'MAIL_PASSWORD' => $request->string('MAIL_PASSWORD', ''),
            'MAIL_ENCRYPTION' => $request->string('MAIL_ENCRYPTION', 'tls'),
            'MAIL_FROM_ADDRESS' => $request->string('MAIL_FROM_ADDRESS', 'bonjour@acteursdulivre.fr'),
            'MAIL_FROM_NAME' => $request->string('MAIL_FROM_NAME', 'Acteurs du Livre'),
            'SESSION_NAME' => 'adl_session',
            'OAUTH_ENABLED' => 'false',
            'OAUTH_GOOGLE_ENABLED' => 'true',
            'OAUTH_FACEBOOK_ENABLED' => 'true',
            'GOOGLE_CLIENT_ID' => '',
            'GOOGLE_CLIENT_SECRET' => '',
            'FACEBOOK_APP_ID' => '',
            'FACEBOOK_APP_SECRET' => '',
        ];

        $adminEmail = strtolower($request->string('ADMIN_EMAIL'));
        $adminPassword = $request->string('ADMIN_PASSWORD');
        $adminFirst = $request->string('ADMIN_FIRST', 'Samuel');
        $adminLast = $request->string('ADMIN_LAST', 'Ohayon');

        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL) || strlen($adminPassword) < 8) {
            $_SESSION['_old'] = $values + [
                'ADMIN_EMAIL' => $adminEmail,
                'ADMIN_FIRST' => $adminFirst,
                'ADMIN_LAST' => $adminLast,
            ];
            flash('error', 'L\'administrateur doit avoir un e-mail valide et un mot de passe d\'au moins 8 caractères.');
            redirect('/install');
        }

        try {
            Env::write(ADL_ROOT . '/.env', $values);
            $pdo = Database::connectWithoutDatabase();
            $dbName = $values['DB_NAME'];
            $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $dbName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            Database::reset();
            Env::load(ADL_ROOT . '/.env');
            Migrator::migrate();

            $existingAdmin = User::findByEmail($adminEmail);
            if ($existingAdmin) {
                User::setPassword((int) $existingAdmin['id'], $adminPassword);
                User::update((int) $existingAdmin['id'], [
                    'first_name' => $adminFirst,
                    'last_name' => $adminLast,
                    'role' => 'admin',
                    'status' => 'active',
                ]);
            } else {
                User::create([
                    'email' => $adminEmail,
                    'password' => $adminPassword,
                    'first_name' => $adminFirst,
                    'last_name' => $adminLast,
                    'role' => 'admin',
                ]);
            }

            Setting::set('mail_host', $values['MAIL_HOST']);
            Setting::set('mail_port', $values['MAIL_PORT']);
            Setting::set('mail_username', $values['MAIL_USERNAME']);
            Setting::set('mail_password', $values['MAIL_PASSWORD']);
            Setting::set('mail_encryption', $values['MAIL_ENCRYPTION']);
            Setting::set('mail_from_address', $values['MAIL_FROM_ADDRESS']);
            Setting::set('mail_from_name', $values['MAIL_FROM_NAME']);

            try {
                Mailer::sendTemplate('bienvenue', $adminEmail, [
                    'prenom' => $adminFirst,
                    'lien_espace' => $values['APP_URL'] . '/espace',
                ]);
            } catch (Throwable) {
            }
        } catch (Throwable $e) {
            @unlink(ADL_ROOT . '/.env');
            Database::reset();
            $_SESSION['_old'] = $values + [
                'ADMIN_EMAIL' => $adminEmail,
                'ADMIN_FIRST' => $adminFirst,
                'ADMIN_LAST' => $adminLast,
            ];
            flash('error', 'Installation interrompue : ' . $e->getMessage());
            redirect('/install');
        }

        unset($_SESSION['_old']);
        redirect('/connexion');
    }

    private function defaults(): array
    {
        return [
            'APP_NAME' => 'Acteurs du Livre',
            'APP_URL' => 'https://acteursdulivre.test',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '3306',
            'DB_NAME' => 'acteursdulivre',
            'DB_USER' => 'root',
            'DB_PASS' => '',
            'MAIL_HOST' => '',
            'MAIL_PORT' => '587',
            'MAIL_USERNAME' => '',
            'MAIL_PASSWORD' => '',
            'MAIL_ENCRYPTION' => 'tls',
            'MAIL_FROM_ADDRESS' => 'bonjour@acteursdulivre.fr',
            'MAIL_FROM_NAME' => 'Acteurs du Livre',
            'ADMIN_EMAIL' => 'samuel@acteursdulivre.fr',
            'ADMIN_FIRST' => 'Samuel',
            'ADMIN_LAST' => 'Ohayon',
        ];
    }
}
