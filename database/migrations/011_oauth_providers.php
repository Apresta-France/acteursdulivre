<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $cols = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('google_id', $cols, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN google_id VARCHAR(64) NULL UNIQUE AFTER last_login_at');
    }
    if (!in_array('facebook_id', $cols, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN facebook_id VARCHAR(64) NULL UNIQUE AFTER google_id');
    }
    if (!in_array('avatar_url', $cols, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN avatar_url VARCHAR(1024) NULL AFTER facebook_id');
    }

    $password = $pdo->query("SHOW COLUMNS FROM users LIKE 'password'")->fetch(PDO::FETCH_ASSOC);
    if (is_array($password) && strtoupper((string) ($password['Null'] ?? '')) !== 'YES') {
        $pdo->exec('ALTER TABLE users MODIFY password VARCHAR(255) NULL');
    }
};
