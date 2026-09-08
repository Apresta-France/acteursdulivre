<?php

declare(strict_types=1);

/*
 * Importe (ou complète) l'annuaire des maisons d'édition depuis un fichier JSON.
 *
 *   php bin/import-maisons-edition.php [chemin/vers/fichier.json]
 *
 * Par défaut : database/seeds/maisons-edition.json. Les maisons déjà présentes
 * (même nom + même pays) sont ignorées : les fiches revendiquées ou retouchées
 * ne sont jamais écrasées.
 */

define('ADL_ROOT', dirname(__DIR__));
require ADL_ROOT . '/app/bootstrap.php';

use Adl\Core\Database;
use Adl\Models\Publisher;

$file = $argv[1] ?? ADL_ROOT . '/database/seeds/maisons-edition.json';
if (!is_file($file)) {
    fwrite(STDERR, "Fichier introuvable : {$file}\n");
    exit(1);
}
$rows = json_decode((string) file_get_contents($file), true);
if (!is_array($rows)) {
    fwrite(STDERR, "JSON invalide.\n");
    exit(1);
}

$result = Publisher::importRows(Database::pdo(), $rows);
echo 'inserted=' . $result['inserted'] . ' skipped=' . $result['skipped'] . ' total=' . count($rows) . PHP_EOL;
