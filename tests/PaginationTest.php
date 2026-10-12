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

use Adl\Data\Pagination;

$failed = 0;
$passed = 0;

function expect(bool $ok, string $label): void
{
    global $failed, $passed;
    if ($ok) {
        $passed++;
        return;
    }
    $failed++;
    echo "KO  $label\n";
}

$roman = Pagination::compute(Pagination::fromQuery('', '', '', '', '', '', '', '', '', ''));
expect($roman['signs_per_line'] === 58, 'roman signes/ligne ' . $roman['signs_per_line']);
expect($roman['lines'] === 35, 'roman lignes ' . $roman['lines']);
expect($roman['signs_per_page'] === 2030, 'roman signes/page ' . $roman['signs_per_page']);
expect($roman['text_pages'] === 257, 'roman pages texte ' . $roman['text_pages']);
expect($roman['book_pages'] === 265, 'roman livre ' . $roman['book_pages']);
expect($roman['sig16'] === 272, 'roman cahier 16 ' . $roman['sig16']);
expect($roman['sig8'] === 272, 'roman cahier 8 ' . $roman['sig8']);
expect($roman['sig4'] === 268, 'roman cahier 4 ' . $roman['sig4']);
expect($roman['warnings'] === [], 'roman sans alerte');
expect($roman['spine'] === '/outils/dos?p=272', 'lien dos ' . $roman['spine']);

$poche = Pagination::compute(Pagination::fromQuery('', '', 'poche', '', '', 'poche', '', '', '', ''));
expect($poche['signs_per_line'] === 60, 'poche signes/ligne ' . $poche['signs_per_line']);
expect($poche['lines'] === 39, 'poche lignes ' . $poche['lines']);
expect($poche['text_pages'] === 223, 'poche pages ' . $poche['text_pages']);
expect($poche['sig16'] === 240, 'poche cahier 16 ' . $poche['sig16']);

$chapters = Pagination::compute(Pagination::fromQuery('520000', 'signes', 'roman', '', '', 'roman', '', '', '8', '20'));
expect($chapters['text_pages'] === 267, 'chapitres pages ' . $chapters['text_pages']);
expect($chapters['book_pages'] === 275, 'chapitres livre ' . $chapters['book_pages']);
expect($chapters['sig16'] === 288, 'chapitres cahier ' . $chapters['sig16']);

$a4 = Pagination::compute(Pagination::fromQuery('520000', 'signes', 'a4', '', '', 'roman', '', '', '0', '0'));
expect($a4['signs_per_line'] > 70, 'a4 ligne longue ' . $a4['signs_per_line']);
expect($a4['warnings'] !== [], 'a4 alerte');

$zero = Pagination::compute(Pagination::fromQuery('0', 'signes', '', '', '', '', '', '', '', ''));
expect($zero['has'] === false, 'volume nul');
expect($zero['text_pages'] === 0, 'volume nul pages');
expect($zero['signs_per_line'] === 58, 'géométrie sans volume');

$words = Pagination::compute(Pagination::fromQuery('80000', 'mots', '', '', '', '', '', '', '0', '0'));
expect($words['sec'] === 480000, 'mots → signes ' . $words['sec']);

echo "$passed ok, $failed ko\n";
exit($failed === 0 ? 0 : 1);
