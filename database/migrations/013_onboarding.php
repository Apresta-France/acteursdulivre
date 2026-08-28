<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $cols = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('onboarding_done_at', $cols, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN onboarding_done_at DATETIME NULL AFTER avatar_url');
        $pdo->exec('UPDATE users SET onboarding_done_at = created_at WHERE onboarding_done_at IS NULL');
    }
};
