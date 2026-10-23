<?php

declare(strict_types=1);

define('ADL_ROOT', dirname(__DIR__));
require ADL_ROOT . '/app/helpers.php';

$failures = 0;
$check = static function (bool $ok, string $label) use (&$failures): void {
    if ($ok) {
        echo "ok  $label\n";
        return;
    }
    $failures++;
    echo "ECHEC  $label\n";
};

$check(count_label(1, 'salon', 'salons') === '1 salon', '1 salon');
$check(count_label(0, 'salon', 'salons') === '0 salon', '0 salon');
$check(count_label(2, 'salon', 'salons') === '2 salons', '2 salons');
$check(count_label(1, 'maison', 'maisons') === '1 maison', '1 maison');
$check(count_label(1, 'éditeur', 'éditeurs') === '1 éditeur', '1 éditeur');
$check(count_label(1124, 'éditeur', 'éditeurs') === '1 124 éditeurs', 'milliers avec espace');

$check(count_des(1, 'salon et festival', 'salons et festivals') === 'de 1 salon et festival', 'agenda de 1');
$check(count_des(12, 'salon et festival', 'salons et festivals') === 'des 12 salons et festivals', 'agenda des n');
$check(count_des(1, 'maison d\'édition', 'maisons d\'édition') === 'de 1 maison d\'édition', 'annuaire de 1');
$check(count_des(241, 'maison d\'édition', 'maisons d\'édition') === 'des 241 maisons d\'édition', 'annuaire des n');

$usa = 'Agenda ' . count_des(1, 'salon et festival', 'salons et festivals') . ' du livre aux États-Unis';
$check($usa === 'Agenda de 1 salon et festival du livre aux États-Unis', 'méta États-Unis');
$check(!str_contains($usa, '1 salons'), 'pas de 1 salons');

$niger = 'Annuaire ' . count_des(1, 'maison d\'édition', 'maisons d\'édition') . ' au Niger recensée';
$check($niger === 'Annuaire de 1 maison d\'édition au Niger recensée', 'méta Niger');
$check(!str_contains($niger, '1 maisons'), 'pas de 1 maisons');

exit($failures === 0 ? 0 : 1);
