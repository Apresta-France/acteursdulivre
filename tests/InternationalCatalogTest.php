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

use Adl\Controllers\PublisherController;
use Adl\Data\Geography;
use Adl\Models\Publisher;
use Adl\Models\Salon;

$failures = 0;
$check = static function (bool $ok, string $label) use (&$failures): void {
    if ($ok) {
        echo "ok  $label\n";
        return;
    }
    $failures++;
    echo "ECHEC  $label\n";
};

$phrases = [
    'Maroc' => 'au Maroc',
    'Sénégal' => 'au Sénégal',
    'Liban' => 'au Liban',
    'Cameroun' => 'au Cameroun',
    'Bénin' => 'au Bénin',
    'Gabon' => 'au Gabon',
    'Mali' => 'au Mali',
    'Togo' => 'au Togo',
    'Burkina Faso' => 'au Burkina Faso',
    'Côte d\'Ivoire' => 'en Côte d\'Ivoire',
    'RD Congo' => 'en RD Congo',
    'États-Unis' => 'aux États-Unis',
    'Émirats arabes unis' => 'aux Émirats arabes unis',
    'Monaco' => 'à Monaco',
    'Luxembourg' => 'au Luxembourg',
    'Canada' => 'au Canada',
    'Belgique' => 'en Belgique',
    'Suisse' => 'en Suisse',
    'Haïti' => 'à Haïti',
    'Tunisie' => 'en Tunisie',
    'France' => 'en France',
];
foreach ($phrases as $country => $want) {
    $check(Geography::countryPhrase($country) === $want, 'préposition ' . $country);
    $check(PublisherController::countryPhrase($country) === $want, 'wrapper ' . $country);
}

$empty = Publisher::normalizeCountry('');
$check($empty['country'] === '', 'pays vide non remplacé par Europe');
$check(Geography::locationFallback('', '') === 'International', 'libellé neutre International');

$quebec = Publisher::normalizeCountry('Canada (Québec)');
$check($quebec['country'] === 'Canada' && $quebec['region'] === 'Québec', 'Canada (Québec) normalisé');

$check(Publisher::sizeKey('') === '', 'taille vide conservée');
$check(Publisher::typologyKey('') === '', 'typologie vide conservée');

$check(Geography::zoneSlugForCountry('Canada') === 'amerique-du-nord', 'zone Canada');
$check(Geography::zoneSlugForCountry('Sénégal') === 'afrique', 'zone Sénégal');
$check(Geography::zoneSlugForCountry('Maroc') === 'maghreb-moyen-orient', 'zone Maroc');
$check(Geography::zoneSlugForCountry('Haïti') === 'caraibes', 'zone Haïti');
$check(in_array('canada', Geography::countrySlugsForZone('amerique-du-nord'), true), 'filtre Amérique du Nord');

$placeholder = Salon::hydrate([
    'slug' => 'test-placeholder',
    'name' => 'Salon test',
    'starts_on' => '2027-01-01',
    'ends_on' => null,
    'dates_confirmed' => 0,
    'dates_raw' => 'non trouvé (2027)',
    'city' => 'Zagreb',
    'region' => '—',
    'country' => 'Croatie',
    'venue' => 'non trouvé pour 2027',
    'attendance' => 'non trouvé (historiquement 100 000)',
    'category' => 'Salon généraliste',
]);
$check($placeholder['when'] === 'Dates à confirmer', 'date fictive 1er janvier masquée');
$check($placeholder['when_dates'] === 'Dates à confirmer', 'when_dates sans 1er janvier');
$check($placeholder['month_heading'] === 'Dates à confirmer', 'groupe Dates à confirmer');
$check($placeholder['venue'] === '', 'lieu « non trouvé » masqué');
$check($placeholder['attendance'] === '', 'fréquentation « non trouvé » masquée');
$check($placeholder['iso'] === '', 'iso vide pour date fictive');

$dated = Salon::hydrate([
    'slug' => 'test-date',
    'name' => 'Salon daté',
    'starts_on' => '2027-04-07',
    'ends_on' => '2027-04-11',
    'dates_confirmed' => 0,
    'dates_raw' => '7–11 avr. 2027',
    'city' => 'Québec',
    'region' => 'Québec',
    'country' => 'Canada',
    'category' => 'Salon généraliste',
]);
$check(str_contains($dated['when'], 'à confirmer'), 'dates réelles à confirmer conservées');
$check($dated['iso'] === '2027-04-07', 'date réelle conservée');

$check(Salon::isPlaceholderDate('2026-01-01', false), '1er janvier non confirmé = placeholder');
$check(!Salon::isPlaceholderDate('2026-01-01', true), '1er janvier confirmé conservé');
$check(!Salon::isPlaceholderDate('2026-11-21', false), 'vraie date non confirmée conservée');

exit($failures === 0 ? 0 : 1);
