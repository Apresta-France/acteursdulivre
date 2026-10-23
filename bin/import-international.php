<?php

declare(strict_types=1);

/*
 * Importe les CSV internationaux et applique les corrections de fiches existantes.
 *
 *   php bin/import-international.php
 */

define('ADL_ROOT', dirname(__DIR__));
require ADL_ROOT . '/app/bootstrap.php';

use Adl\Core\Database;
use Adl\Data\InternationalImport;

$result = InternationalImport::apply(Database::pdo());
echo 'publishers inserted=' . $result['publishers']['inserted']
    . ' skipped=' . $result['publishers']['skipped']
    . ' rejected=' . count($result['publishers']['rejected']) . PHP_EOL;
echo 'salons inserted=' . $result['salons']['inserted']
    . ' updated=' . $result['salons']['updated']
    . ' rejected=' . count($result['salons']['rejected']) . PHP_EOL;
echo 'enrichments=' . $result['enrichments'] . PHP_EOL;
foreach (array_merge($result['publishers']['rejected'], $result['salons']['rejected']) as $row) {
    echo 'exclu: ' . ($row['name'] !== '' ? $row['name'] : '(sans nom)') . ' — ' . $row['reason'] . PHP_EOL;
}
