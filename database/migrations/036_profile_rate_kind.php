<?php

declare(strict_types=1);

return static function (\PDO $pdo): void {
    $cols = $pdo->query('SHOW COLUMNS FROM profiles')->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('rate_kind', $cols, true)) {
        return;
    }
    $pdo->exec('ALTER TABLE profiles ADD COLUMN rate_kind VARCHAR(20) NULL AFTER hourly_rate');
};
