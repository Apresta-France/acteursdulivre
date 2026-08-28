<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $cols = $pdo->query('SHOW COLUMNS FROM services')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('image_path', $cols, true)) {
        $pdo->exec('ALTER TABLE services ADD COLUMN image_path VARCHAR(255) NULL AFTER excerpt');
    }
};
