<?php

declare(strict_types=1);

define('ADL_ROOT', dirname(__DIR__));

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

use Adl\Core\VerifiedBot;

$failures = 0;
$check = static function (bool $ok, string $label) use (&$failures): void {
    if ($ok) {
        echo "ok  $label\n";
        return;
    }
    $failures++;
    echo "ECHEC  $label\n";
};

$check(VerifiedBot::ipInCidr('66.249.66.1', '66.249.64.0/19'), 'Googlebot dans le préfixe');
$check(!VerifiedBot::ipInCidr('1.2.3.4', '66.249.64.0/19'), 'IP hors préfixe');
$check(VerifiedBot::ipInCidr('2001:4860:4801:1::1', '2001:4860:4801::/48'), 'IPv6 dans le préfixe');
$check(!VerifiedBot::ipInCidr('192.168.0.1', '2001:4860:4801::/48'), 'familles différentes');

$check(public_http_url('https://user:secret@editeur.fr/page') === '', 'userinfo rejeté');
$check(public_http_url('https://@editeur.fr') === '', 'arobase d\'autorité rejetée');
$check(public_http_url('https://editeur.fr/catalogue') === 'https://editeur.fr/catalogue', 'site public conservé');
$check(public_http_url('https://www.youtube.com/@chaine') === 'https://www.youtube.com/@chaine', 'arobase de chemin conservée');
$check(public_http_url('coline.mln') === 'https://coline.mln', 'hôte syntaxiquement valable');

exit($failures === 0 ? 0 : 1);
