<?php

declare(strict_types=1);

namespace Adl\Core;

final class App
{
    public static function run(): void
    {
        register_shutdown_function(static function (): void {
            \Adl\Models\HttpError::capture();
        });

        $request = new Request();
        $path = $request->path();

        if (Env::loaded() && !self::skipsAutoMigrate($path)) {
            try {
                Migrator::migrate();
            } catch (\Throwable) {
                // La page d'erreur de connexion gère le cas DB
            }
        }
        $csrfExempt = $path === '/install'
            || str_starts_with($path, '/install')
            || str_starts_with($path, '/newsletter/desinscription/')
            || $path === '/api/stats';
        if ($request->isPost() && !$csrfExempt) {
            $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
            if ($contentLength > 0 && $_POST === [] && $_FILES === []) {
                flash('error', 'L\'envoi est trop lourd. Réduisez les fichiers et réessayez.');
                $back = parse_url((string) ($_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_PATH);
                redirect(is_string($back) ? $back : '/');
            }
            if (!Csrf::check($request->string('_token'))) {
                http_response_code(419);
                \Adl\Models\HttpError::note('Session expirée');
                View::render('errors/419', [
                    'title' => 'Session expirée',
                    'meta' => [
                        'title' => 'Session expirée — acteursdulivre.fr',
                        'robots' => \Adl\Data\Seo::ROBOTS_NONE,
                    ],
                ]);
                return;
            }
        }

        $router = new Router();
        $routes = require ADL_ROOT . '/config/routes.php';
        $routes($router);
        try {
            $router->dispatch($request);
        } catch (\Throwable $e) {
            self::fail($e);
        }
    }

    private static function fail(\Throwable $e): void
    {
        try {
            error_log('ADL ' . $e::class . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        } catch (\Throwable) {
        }
        \Adl\Models\HttpError::note($e->getMessage());
        if (!headers_sent()) {
            http_response_code(500);
        }
        try {
            View::render('errors/500', [
                'title' => 'Erreur',
                'meta' => [
                    'title' => 'Erreur — acteursdulivre.fr',
                    'robots' => \Adl\Data\Seo::ROBOTS_NONE,
                ],
            ]);
        } catch (\Throwable) {
            if (!headers_sent()) {
                header('Content-Type: text/html; charset=utf-8');
            }
            echo '<!DOCTYPE html><meta charset="utf-8"><title>Erreur</title><p>Une erreur est survenue. Réessayez dans un instant.</p>';
        }
    }

    private static function skipsAutoMigrate(string $path): bool
    {
        return $path === '/install'
            || str_starts_with($path, '/install/')
            || $path === '/admin'
            || str_starts_with($path, '/admin/');
    }
}
