<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS souscriptions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(190) NOT NULL,
            title VARCHAR(190) NOT NULL,
            genre VARCHAR(120) NOT NULL DEFAULT "",
            kind VARCHAR(20) NOT NULL DEFAULT "souscription",
            status VARCHAR(20) NOT NULL DEFAULT "draft",
            featured TINYINT(1) NOT NULL DEFAULT 0,
            bearer VARCHAR(190) NOT NULL DEFAULT "",
            bearer_role VARCHAR(120) NOT NULL DEFAULT "",
            pitch VARCHAR(600) NOT NULL DEFAULT "",
            body MEDIUMTEXT NULL,
            trades_json TEXT NULL,
            facts_json TEXT NULL,
            cover_ink VARCHAR(7) NOT NULL DEFAULT "#15212f",
            cover_paper VARCHAR(7) NOT NULL DEFAULT "#f4efe6",
            cover_rule VARCHAR(7) NOT NULL DEFAULT "#eb963b",
            closes_on DATE NULL,
            host VARCHAR(190) NOT NULL DEFAULT "",
            external_url VARCHAR(500) NOT NULL DEFAULT "",
            cta VARCHAR(120) NOT NULL DEFAULT "",
            outcome VARCHAR(190) NOT NULL DEFAULT "",
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL,
            UNIQUE KEY souscriptions_slug (slug),
            KEY souscriptions_public (status, featured, closes_on)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
};
