<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $cols = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN) ?: [];
    if (!in_array('platform_cofounder', $cols, true)) {
        $pdo->exec(
            'ALTER TABLE users
             ADD COLUMN platform_cofounder TINYINT(1) NOT NULL DEFAULT 0 AFTER founder'
        );
    }
};
