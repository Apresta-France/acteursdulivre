<?php

declare(strict_types=1);

return static function (\PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS newsletter_letters (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            subject VARCHAR(255) NOT NULL,
            preheader VARCHAR(255) NOT NULL DEFAULT "",
            blocks_json MEDIUMTEXT NOT NULL,
            body_html MEDIUMTEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT "draft",
            campaign_id INT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            sent_at DATETIME NULL,
            KEY newsletter_letters_status (status),
            KEY newsletter_letters_updated (updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
};
