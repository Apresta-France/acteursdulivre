<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $cols = $pdo->query('SHOW COLUMNS FROM services')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('specialty', $cols, true)) {
        $pdo->exec('ALTER TABLE services ADD COLUMN specialty VARCHAR(120) NULL AFTER category_name');
    }
};
