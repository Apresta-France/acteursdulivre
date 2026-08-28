<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS legal_acceptances (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            document VARCHAR(40) NOT NULL,
            version VARCHAR(20) NOT NULL,
            context VARCHAR(80) NOT NULL,
            ip VARCHAR(45) NULL,
            accepted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_legal_acceptance (user_id, document, version, context),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS invoices (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            number VARCHAR(40) NOT NULL UNIQUE,
            order_id INT UNSIGNED NOT NULL,
            seller_id INT UNSIGNED NOT NULL,
            amount INT NOT NULL DEFAULT 0,
            commission_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
            status VARCHAR(40) NOT NULL DEFAULT "issued",
            issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            due_at DATETIME NOT NULL,
            paid_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE RESTRICT,
            FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $orderCols = $pdo->query('SHOW COLUMNS FROM orders')->fetchAll(PDO::FETCH_COLUMN);
    $orderAdds = [
        'commission_percent' => 'ADD COLUMN commission_percent DECIMAL(5,2) NULL AFTER amount',
        'commission_amount' => 'ADD COLUMN commission_amount INT NOT NULL DEFAULT 0 AFTER commission_percent',
        'first_mission_free' => 'ADD COLUMN first_mission_free TINYINT(1) NOT NULL DEFAULT 0 AFTER commission_amount',
        'confirmed_at' => 'ADD COLUMN confirmed_at DATETIME NULL AFTER first_mission_free',
        'buyer_cgv_at' => 'ADD COLUMN buyer_cgv_at DATETIME NULL AFTER confirmed_at',
        'seller_cgv_at' => 'ADD COLUMN seller_cgv_at DATETIME NULL AFTER buyer_cgv_at',
    ];
    foreach ($orderAdds as $col => $sql) {
        if (!in_array($col, $orderCols, true)) {
            $pdo->exec('ALTER TABLE orders ' . $sql);
        }
    }

    $reviewCols = $pdo->query('SHOW COLUMNS FROM reviews')->fetchAll(PDO::FETCH_COLUMN);
    $reviewAdds = [
        'rating_quality' => 'ADD COLUMN rating_quality TINYINT NULL AFTER rating',
        'rating_efficiency' => 'ADD COLUMN rating_efficiency TINYINT NULL AFTER rating_quality',
        'rating_satisfaction' => 'ADD COLUMN rating_satisfaction TINYINT NULL AFTER rating_efficiency',
    ];
    foreach ($reviewAdds as $col => $sql) {
        if (!in_array($col, $reviewCols, true)) {
            $pdo->exec('ALTER TABLE reviews ' . $sql);
        }
    }

    $indexes = $pdo->query('SHOW INDEX FROM reviews')->fetchAll(PDO::FETCH_ASSOC);
    $hasUniq = false;
    foreach ($indexes as $index) {
        if (($index['Key_name'] ?? '') === 'uniq_review_order_author') {
            $hasUniq = true;
            break;
        }
    }
    if (!$hasUniq) {
        $pdo->exec('ALTER TABLE reviews ADD UNIQUE KEY uniq_review_order_author (order_id, author_id)');
    }

    $pdo->exec(
        "INSERT INTO settings (setting_key, setting_value) VALUES
            ('invoice_due_days', '15')
         ON DUPLICATE KEY UPDATE setting_key = setting_key"
    );
};
