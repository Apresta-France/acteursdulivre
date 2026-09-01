<?php

declare(strict_types=1);

namespace Adl\Core;

final class App
{
    public static function run(): void
    {
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
                flash('error', 'L\'envoi est trop lourd. Réduisez les fichiers (5 Mo max par visuel) et réessayez.');
                $back = parse_url((string) ($_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_PATH);
                redirect(is_string($back) ? $back : '/');
            }
            if (!Csrf::check($request->string('_token'))) {
                http_response_code(419);
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
        $router->dispatch($request);
    }

    private static function skipsAutoMigrate(string $path): bool
    {
        return $path === '/install'
            || str_starts_with($path, '/install/')
            || $path === '/admin'
            || str_starts_with($path, '/admin/');
    }
}
