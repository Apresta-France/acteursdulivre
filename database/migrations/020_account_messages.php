<?php

declare(strict_types=1);

return static function (\PDO $pdo): void {
    $userCols = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
    foreach ([
        'notify_messages' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'notify_jalons' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'notify_missions' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'notify_newsletter' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'company_name' => 'VARCHAR(191) NULL',
        'siret' => 'VARCHAR(32) NULL',
        'vat_number' => 'VARCHAR(32) NULL',
        'billing_address' => 'TEXT NULL',
        'iban' => 'VARCHAR(42) NULL',
        'deleted_at' => 'DATETIME NULL',
    ] as $col => $def) {
        if (!in_array($col, $userCols, true)) {
            $pdo->exec('ALTER TABLE users ADD COLUMN ' . $col . ' ' . $def);
        }
    }

    $msgCols = $pdo->query('SHOW COLUMNS FROM messages')->fetchAll(PDO::FETCH_COLUMN);
    foreach ([
        'attachment_path' => 'VARCHAR(255) NULL',
        'attachment_name' => 'VARCHAR(191) NULL',
        'attachment_size' => 'INT UNSIGNED NULL',
    ] as $col => $def) {
        if (!in_array($col, $msgCols, true)) {
            $pdo->exec('ALTER TABLE messages ADD COLUMN ' . $col . ' ' . $def);
        }
    }

    $pdo->exec('ALTER TABLE messages MODIFY body TEXT NULL');

    $orderCols = $pdo->query('SHOW COLUMNS FROM orders')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('dispute_admin_note', $orderCols, true)) {
        $pdo->exec('ALTER TABLE orders ADD COLUMN dispute_admin_note TEXT NULL');
    }
};
