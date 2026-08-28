<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $cols = $pdo->query('SHOW COLUMNS FROM services')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('category_name', $cols, true)) {
        $pdo->exec('ALTER TABLE services ADD COLUMN category_name VARCHAR(120) NULL AFTER category_id');
    }
    if (!in_array('delay', $cols, true)) {
        $pdo->exec('ALTER TABLE services ADD COLUMN delay VARCHAR(80) NULL AFTER price_from');
    }
};
