<?php

declare(strict_types=1);

define('ADL_ROOT', dirname(__DIR__));
require ADL_ROOT . '/app/bootstrap.php';

use Adl\Core\Migrator;

$applied = Migrator::migrate();
if ($applied === []) {
    echo "Aucune nouvelle migration.\n";
    exit(0);
}
echo "Migrations appliquées :\n- " . implode("\n- ", $applied) . "\n";
