<?php

declare(strict_types=1);

namespace Adl\Core;

final class App
{
    public static function run(): void
    {
        $request = new Request();

        if (Env::loaded() && Env::bool('APP_DEBUG', false) && $request->path() !== '/install' && !str_starts_with($request->path(), '/install/')) {
            try {
                Migrator::migrate();
            } catch (\Throwable) {
                // La page d'erreur de connexion gère le cas DB
            }
        }

        if ($request->isPost() && $request->path() !== '/install' && !str_starts_with($request->path(), '/install')) {
            if (!Csrf::check($request->string('_token'))) {
                http_response_code(419);
                View::render('errors/419', ['title' => 'Session expirée']);
                return;
            }
        }

        $router = new Router();
        $routes = require ADL_ROOT . '/config/routes.php';
        $routes($router);
        $router->dispatch($request);
    }
}
