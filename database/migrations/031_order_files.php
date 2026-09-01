<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS order_files (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            file_name VARCHAR(191) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            file_size INT UNSIGNED NOT NULL DEFAULT 0,
            mime VARCHAR(80) NULL,
            note VARCHAR(400) NULL,
            view_count INT UNSIGNED NOT NULL DEFAULT 0,
            download_count INT UNSIGNED NOT NULL DEFAULT 0,
            last_viewed_at DATETIME NULL,
            last_downloaded_at DATETIME NULL,
            withdrawn_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            KEY order_files_order (order_id, created_at),
            KEY order_files_user (user_id),
            CONSTRAINT order_files_order_fk FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS order_file_clicks (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            file_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            action VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL,
            KEY order_file_clicks_file (file_id, created_at),
            KEY order_file_clicks_lookup (file_id, user_id, action, created_at),
            CONSTRAINT order_file_clicks_file_fk FOREIGN KEY (file_id) REFERENCES order_files(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
};
