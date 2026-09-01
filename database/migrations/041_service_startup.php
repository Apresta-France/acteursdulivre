<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $serviceCols = $pdo->query('SHOW COLUMNS FROM services')->fetchAll(PDO::FETCH_COLUMN);
    if (is_array($serviceCols)) {
        if (!in_array('startup_enabled', $serviceCols, true)) {
            $pdo->exec('ALTER TABLE services ADD COLUMN startup_enabled TINYINT(1) NOT NULL DEFAULT 0');
        }
        if (!in_array('startup_kind', $serviceCols, true)) {
            $pdo->exec('ALTER TABLE services ADD COLUMN startup_kind VARCHAR(16) NULL');
        }
        if (!in_array('startup_value', $serviceCols, true)) {
            $pdo->exec('ALTER TABLE services ADD COLUMN startup_value INT UNSIGNED NULL');
        }
    }

    $orderCols = $pdo->query('SHOW COLUMNS FROM orders')->fetchAll(PDO::FETCH_COLUMN);
    if (is_array($orderCols)) {
        if (!in_array('startup_enabled', $orderCols, true)) {
            $pdo->exec('ALTER TABLE orders ADD COLUMN startup_enabled TINYINT(1) NOT NULL DEFAULT 0');
        }
        if (!in_array('startup_kind', $orderCols, true)) {
            $pdo->exec('ALTER TABLE orders ADD COLUMN startup_kind VARCHAR(16) NULL');
        }
        if (!in_array('startup_value', $orderCols, true)) {
            $pdo->exec('ALTER TABLE orders ADD COLUMN startup_value INT UNSIGNED NULL');
        }
    }
};
