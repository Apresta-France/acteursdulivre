<?php

declare(strict_types=1);

use Adl\Core\Env;

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'Adl\\')) {
        return;
    }
    $path = ADL_ROOT . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

require ADL_ROOT . '/app/helpers.php';

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$isInstall = str_starts_with($uri, '/install');
$isCron = $uri === '/cron' || str_starts_with($uri, '/cron/');
$isSeo = $uri === '/sitemap.xml' || $uri === '/robots.txt' || $uri === '/llms.txt';
$envFile = ADL_ROOT . '/.env';

if (!is_file($envFile) && !$isInstall) {
    header('Location: /install');
    exit;
}

if (is_file($envFile)) {
    Env::load($envFile);
}

date_default_timezone_set(Env::get('APP_TIMEZONE', 'Europe/Paris'));

if (session_status() !== PHP_SESSION_ACTIVE && PHP_SAPI !== 'cli' && !$isCron && !$isSeo) {
    session_name(Env::get('SESSION_NAME', 'adl_session'));
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
