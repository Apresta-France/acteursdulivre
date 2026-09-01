<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $cols = $pdo->query('SHOW COLUMNS FROM services')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('portfolio_url', $cols, true)) {
        $pdo->exec('ALTER TABLE services ADD COLUMN portfolio_url VARCHAR(500) NULL AFTER image_path');
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS service_images (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            service_id INT UNSIGNED NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
            FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    try {
        $imageCols = $pdo->query('SHOW COLUMNS FROM service_images')->fetchAll(PDO::FETCH_COLUMN);
        if (is_array($imageCols) && !in_array('sort_order', $imageCols, true)) {
            $pdo->exec('ALTER TABLE service_images ADD COLUMN sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0');
        }
    } catch (\Throwable) {
    }
};
