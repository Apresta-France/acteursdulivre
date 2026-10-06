<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS http_errors (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            status SMALLINT UNSIGNED NOT NULL,
            method VARCHAR(8) NOT NULL DEFAULT "GET",
            path VARCHAR(500) NOT NULL,
            path_hash CHAR(40) NOT NULL,
            query_string VARCHAR(500) NULL,
            referrer VARCHAR(500) NULL,
            user_agent VARCHAR(255) NULL,
            user_id INT UNSIGNED NULL,
            ip_hash CHAR(64) NULL,
            message VARCHAR(255) NULL,
            hits INT UNSIGNED NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY http_errors_burst (status, path_hash, ip_hash, updated_at),
            KEY http_errors_group (status, path_hash, updated_at),
            KEY http_errors_updated (updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS http_error_acks (
            status SMALLINT UNSIGNED NOT NULL,
            path_hash CHAR(40) NOT NULL,
            path VARCHAR(500) NOT NULL,
            user_id INT UNSIGNED NULL,
            acked_at DATETIME NOT NULL,
            PRIMARY KEY (status, path_hash)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
};
