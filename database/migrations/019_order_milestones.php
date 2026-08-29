<?php

declare(strict_types=1);

return static function (\PDO $pdo): void {
    $orderCols = $pdo->query('SHOW COLUMNS FROM orders')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('deposit_amount', $orderCols, true)) {
        $pdo->exec('ALTER TABLE orders ADD COLUMN deposit_amount INT NOT NULL DEFAULT 0');
    }
    if (!in_array('quote_delay', $orderCols, true)) {
        $pdo->exec('ALTER TABLE orders ADD COLUMN quote_delay VARCHAR(80) NULL');
    }
    if (!in_array('quote_note', $orderCols, true)) {
        $pdo->exec('ALTER TABLE orders ADD COLUMN quote_note TEXT NULL');
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS order_milestones (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id INT UNSIGNED NOT NULL,
            code VARCHAR(40) NOT NULL,
            position SMALLINT UNSIGNED NOT NULL,
            actor VARCHAR(20) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT "pending",
            amount INT NULL,
            delay VARCHAR(80) NULL,
            note TEXT NULL,
            file_name VARCHAR(255) NULL,
            file_path VARCHAR(255) NULL,
            completed_at DATETIME NULL,
            completed_by INT UNSIGNED NULL,
            UNIQUE KEY order_milestones_unique (order_id, code),
            KEY order_milestones_status (status),
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
};
