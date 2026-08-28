<?php

declare(strict_types=1);

return static function (\PDO $pdo): void {
    $profileCols = $pdo->query('SHOW COLUMNS FROM profiles')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('verification_status', $profileCols, true)) {
        $pdo->exec(
            'ALTER TABLE profiles
             ADD COLUMN verification_status VARCHAR(20) NULL DEFAULT NULL AFTER level'
        );
    }

    $reviewCols = $pdo->query('SHOW COLUMNS FROM reviews')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('hidden_at', $reviewCols, true)) {
        $pdo->exec('ALTER TABLE reviews ADD COLUMN hidden_at DATETIME NULL AFTER body');
    }
};
