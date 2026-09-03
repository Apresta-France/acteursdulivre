<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS author_pages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            slug VARCHAR(190) NOT NULL,
            enabled TINYINT(1) NOT NULL DEFAULT 0,
            pen_name VARCHAR(190) NULL,
            tagline VARCHAR(190) NULL,
            bio TEXT NULL,
            short_bio VARCHAR(500) NULL,
            genres_json TEXT NULL,
            website VARCHAR(255) NULL,
            wikipedia_url VARCHAR(255) NULL,
            press_json TEXT NULL,
            links_json TEXT NULL,
            awards_json TEXT NULL,
            events_json TEXT NULL,
            open_to_json TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL,
            UNIQUE KEY author_pages_user (user_id),
            UNIQUE KEY author_pages_slug (slug),
            KEY author_pages_enabled (enabled, updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS author_works (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            author_page_id INT UNSIGNED NOT NULL,
            title VARCHAR(190) NOT NULL,
            subtitle VARCHAR(190) NULL,
            kind VARCHAR(40) NOT NULL DEFAULT "roman",
            role VARCHAR(40) NOT NULL DEFAULT "auteur",
            status VARCHAR(20) NOT NULL DEFAULT "published",
            publisher VARCHAR(190) NULL,
            collection VARCHAR(190) NULL,
            year VARCHAR(10) NULL,
            isbn VARCHAR(32) NULL,
            pages SMALLINT UNSIGNED NULL,
            language VARCHAR(60) NULL,
            formats_json TEXT NULL,
            price VARCHAR(40) NULL,
            summary TEXT NULL,
            excerpt TEXT NULL,
            buy_url VARCHAR(500) NULL,
            more_url VARCHAR(500) NULL,
            images_json TEXT NULL,
            featured TINYINT(1) NOT NULL DEFAULT 0,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL,
            KEY author_works_page (author_page_id, featured, sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
};
