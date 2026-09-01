<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS email_log (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            recipient VARCHAR(191) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            body_html MEDIUMTEXT NOT NULL,
            body_text TEXT NULL,
            template_slug VARCHAR(80) NULL,
            source VARCHAR(40) NOT NULL DEFAULT "transactional",
            status ENUM("sent", "failed", "file") NOT NULL DEFAULT "sent",
            error VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email_log_created (created_at),
            INDEX idx_email_log_recipient (recipient),
            INDEX idx_email_log_source (source),
            INDEX idx_email_log_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
};
