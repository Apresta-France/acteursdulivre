<?php

declare(strict_types=1);

return static function (\PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(191) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(120) NOT NULL,
            last_name VARCHAR(120) NOT NULL,
            role ENUM("client", "prestataire", "admin") NOT NULL DEFAULT "client",
            status ENUM("active", "pending", "suspended") NOT NULL DEFAULT "active",
            last_login_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE profiles (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL UNIQUE,
            title VARCHAR(255) NULL,
            presentation TEXT NULL,
            city VARCHAR(120) NULL,
            availability VARCHAR(120) NULL,
            level VARCHAR(40) NOT NULL DEFAULT "Nouveau",
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE password_resets (
            email VARCHAR(191) NOT NULL,
            token VARCHAR(128) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
};
