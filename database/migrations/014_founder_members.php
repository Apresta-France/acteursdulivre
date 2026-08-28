<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $cols = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('founder', $cols, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN founder TINYINT(1) NOT NULL DEFAULT 0 AFTER offers_services');
    }

    $pdo->exec(
        'UPDATE users SET founder = 1
         WHERE id IN (SELECT id FROM (SELECT id FROM users ORDER BY id ASC LIMIT 100) AS first_accounts)'
    );

    $pdo->exec(
        "INSERT INTO settings (setting_key, setting_value) VALUES
            ('founder_limit', '100'),
            ('founder_commission_percent', '6')
         ON DUPLICATE KEY UPDATE setting_key = setting_key"
    );
};
