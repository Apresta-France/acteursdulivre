<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS forum_categories (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            slug VARCHAR(160) NOT NULL UNIQUE,
            description VARCHAR(400) NOT NULL DEFAULT "",
            position SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS forum_topics (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            category_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(191) NOT NULL,
            tags_json TEXT NULL,
            is_pinned TINYINT(1) NOT NULL DEFAULT 0,
            is_locked TINYINT(1) NOT NULL DEFAULT 0,
            is_solved TINYINT(1) NOT NULL DEFAULT 0,
            solved_post_id INT UNSIGNED NULL,
            reply_count INT UNSIGNED NOT NULL DEFAULT 0,
            view_count INT UNSIGNED NOT NULL DEFAULT 0,
            follow_count INT UNSIGNED NOT NULL DEFAULT 0,
            last_post_at DATETIME NOT NULL,
            last_post_user_id INT UNSIGNED NULL,
            status ENUM("visible", "hidden", "moderated") NOT NULL DEFAULT "visible",
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_forum_topic_cat_slug (category_id, slug),
            KEY idx_forum_topics_activity (status, last_post_at),
            KEY idx_forum_topics_category (category_id, status, last_post_at),
            KEY idx_forum_topics_user (user_id),
            CONSTRAINT fk_forum_topics_category FOREIGN KEY (category_id) REFERENCES forum_categories(id) ON DELETE CASCADE,
            CONSTRAINT fk_forum_topics_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS forum_posts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            topic_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            parent_id INT UNSIGNED NULL,
            body MEDIUMTEXT NOT NULL,
            position INT UNSIGNED NOT NULL DEFAULT 1,
            is_op TINYINT(1) NOT NULL DEFAULT 0,
            is_solution TINYINT(1) NOT NULL DEFAULT 0,
            useful_count INT UNSIGNED NOT NULL DEFAULT 0,
            status ENUM("visible", "hidden") NOT NULL DEFAULT "visible",
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_forum_posts_topic (topic_id, status, position),
            KEY idx_forum_posts_user (user_id),
            CONSTRAINT fk_forum_posts_topic FOREIGN KEY (topic_id) REFERENCES forum_topics(id) ON DELETE CASCADE,
            CONSTRAINT fk_forum_posts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_forum_posts_parent FOREIGN KEY (parent_id) REFERENCES forum_posts(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS forum_post_votes (
            post_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (post_id, user_id),
            CONSTRAINT fk_forum_votes_post FOREIGN KEY (post_id) REFERENCES forum_posts(id) ON DELETE CASCADE,
            CONSTRAINT fk_forum_votes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS forum_topic_follows (
            topic_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (topic_id, user_id),
            CONSTRAINT fk_forum_follows_topic FOREIGN KEY (topic_id) REFERENCES forum_topics(id) ON DELETE CASCADE,
            CONSTRAINT fk_forum_follows_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $categories = [
        ['Tarifs et devis', 'tarifs-et-devis', 'Ce que coûte chaque métier, et pourquoi les écarts existent.', 1],
        ['Contrats et droits', 'contrats-et-droits', 'Cession, exclusivité, à-valoir, clauses à ne pas signer.', 2],
        ['Fabrication', 'fabrication', 'Papier, façonnage, maquette, fichiers prêts à imprimer.', 3],
        ['Écriture et relecture', 'ecriture-et-relecture', 'Structure, préparation de copie, bêta-lecture.', 4],
        ['Diffusion et librairies', 'diffusion-et-librairies', 'Dépôt-vente, salons, offices, remises.', 5],
        ['Vie de prestataire', 'vie-de-prestataire', 'Statut, factures, clients difficiles, charge de travail.', 6],
        ['Charte et IA', 'charte-et-ia', 'Application de l\'interdiction, cas litigieux, signalements.', 7],
        ['La plateforme', 'la-plateforme', 'Nouveautés, retours, pré-ouverture, demandes de fonctions.', 8],
        ['Édition', 'edition', 'Maisons, collections, manuscrits, relation auteur-éditeur.', 9],
        ['Divers', 'divers', 'Sujets transverses, questions hors cadre, discussions ouvertes.', 10],
    ];

    $find = $pdo->prepare('SELECT id FROM forum_categories WHERE slug = ? LIMIT 1');
    $insert = $pdo->prepare(
        'INSERT INTO forum_categories (name, slug, description, position, created_at)
         VALUES (?, ?, ?, ?, NOW())'
    );
    foreach ($categories as [$name, $slug, $desc, $pos]) {
        $find->execute([$slug]);
        if ($find->fetchColumn()) {
            continue;
        }
        $insert->execute([$name, $slug, $desc, $pos]);
    }
};
