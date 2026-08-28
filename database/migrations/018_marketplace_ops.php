<?php

declare(strict_types=1);

return static function (\PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS conversation_participants (
            conversation_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            last_read_at DATETIME NULL,
            PRIMARY KEY (conversation_id, user_id),
            FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS remember_tokens (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            selector VARCHAR(24) NOT NULL UNIQUE,
            token_hash VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS reports (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            reporter_id INT UNSIGNED NULL,
            target_type VARCHAR(40) NOT NULL,
            target_id INT UNSIGNED NULL,
            reason VARCHAR(80) NOT NULL,
            body TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT "open",
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS newsletter_subscribers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(191) NOT NULL UNIQUE,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $convCols = $pdo->query('SHOW COLUMNS FROM conversations')->fetchAll(PDO::FETCH_COLUMN);
    foreach ([
        'order_id' => 'INT UNSIGNED NULL',
        'mission_id' => 'INT UNSIGNED NULL',
        'service_id' => 'INT UNSIGNED NULL',
        'updated_at' => 'DATETIME NULL',
    ] as $col => $def) {
        if (!in_array($col, $convCols, true)) {
            $pdo->exec('ALTER TABLE conversations ADD COLUMN ' . $col . ' ' . $def);
        }
    }

    $orderCols = $pdo->query('SHOW COLUMNS FROM orders')->fetchAll(PDO::FETCH_COLUMN);
    foreach ([
        'brief' => 'TEXT NULL',
        'package_name' => 'VARCHAR(80) NULL',
        'accepted_at' => 'DATETIME NULL',
        'delivered_at' => 'DATETIME NULL',
        'dispute_reason' => 'TEXT NULL',
        'dispute_opened_by' => 'INT UNSIGNED NULL',
        'dispute_at' => 'DATETIME NULL',
    ] as $col => $def) {
        if (!in_array($col, $orderCols, true)) {
            $pdo->exec('ALTER TABLE orders ADD COLUMN ' . $col . ' ' . $def);
        }
    }

    $profileCols = $pdo->query('SHOW COLUMNS FROM profiles')->fetchAll(PDO::FETCH_COLUMN);
    foreach ([
        'verification_doc_path' => 'VARCHAR(255) NULL',
        'verification_doc_name' => 'VARCHAR(255) NULL',
        'verification_note' => 'VARCHAR(255) NULL',
    ] as $col => $def) {
        if (!in_array($col, $profileCols, true)) {
            $pdo->exec('ALTER TABLE profiles ADD COLUMN ' . $col . ' ' . $def);
        }
    }

    $indexes = $pdo->query('SHOW INDEX FROM applications')->fetchAll(PDO::FETCH_ASSOC);
    $hasUnique = false;
    foreach ($indexes as $index) {
        if (($index['Key_name'] ?? '') === 'applications_mission_user_unique') {
            $hasUnique = true;
            break;
        }
    }
    if (!$hasUnique) {
        $pdo->exec(
            'ALTER TABLE applications
             ADD UNIQUE KEY applications_mission_user_unique (mission_id, user_id)'
        );
    }
};
