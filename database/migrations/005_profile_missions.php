<?php

declare(strict_types=1);

return static function (\PDO $pdo): void {
    $pdo->exec(
        'ALTER TABLE profiles
            ADD COLUMN slug VARCHAR(191) NULL UNIQUE AFTER user_id,
            ADD COLUMN languages VARCHAR(255) NULL AFTER city,
            ADD COLUMN hourly_rate VARCHAR(80) NULL AFTER languages,
            ADD COLUMN rate_note VARCHAR(160) NULL AFTER hourly_rate,
            ADD COLUMN website VARCHAR(255) NULL AFTER rate_note,
            ADD COLUMN trades_json TEXT NULL AFTER website,
            ADD COLUMN skills_json TEXT NULL AFTER trades_json,
            ADD COLUMN tools_json TEXT NULL AFTER skills_json,
            ADD COLUMN genres_json TEXT NULL AFTER tools_json,
            ADD COLUMN languages_json TEXT NULL AFTER genres_json,
            ADD COLUMN experiences_json TEXT NULL AFTER languages_json,
            ADD COLUMN education_json TEXT NULL AFTER experiences_json,
            ADD COLUMN updated_at DATETIME NULL AFTER education_json'
    );

    $pdo->exec(
        'CREATE TABLE portfolio_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            profile_id INT UNSIGNED NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NULL,
            year VARCHAR(20) NULL,
            kind VARCHAR(40) NOT NULL DEFAULT "creation",
            image_path VARCHAR(255) NULL,
            image_url VARCHAR(500) NULL,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'ALTER TABLE missions
            ADD COLUMN category_name VARCHAR(120) NULL AFTER category_id,
            ADD COLUMN volume VARCHAR(120) NULL AFTER brief,
            ADD COLUMN attachment_name VARCHAR(255) NULL AFTER deadline,
            ADD COLUMN attachment_path VARCHAR(255) NULL AFTER attachment_name'
    );

    $pdo->exec(
        "ALTER TABLE missions
            MODIFY COLUMN status ENUM('draft', 'open', 'assigned', 'closed') NOT NULL DEFAULT 'open'"
    );

    $rows = $pdo->query(
        'SELECT p.id, p.user_id, u.first_name, u.last_name
         FROM profiles p
         JOIN users u ON u.id = p.user_id
         WHERE p.slug IS NULL OR p.slug = \'\''
    )->fetchAll(PDO::FETCH_ASSOC);

    $used = [];
    $update = $pdo->prepare('UPDATE profiles SET slug = ? WHERE id = ?');
    foreach ($rows as $row) {
        $base = slugify(trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))) ?: 'profil';
        $slug = $base;
        $i = 2;
        while (isset($used[$slug])) {
            $slug = $base . '-' . $i++;
        }
        $used[$slug] = true;
        $update->execute([$slug, $row['id']]);
    }
};
