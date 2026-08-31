<?php

declare(strict_types=1);

use Adl\Models\EmailTemplate;

return static function (\PDO $pdo): void {
    $cols = $pdo->query('SHOW COLUMNS FROM newsletter_subscribers')->fetchAll(PDO::FETCH_COLUMN);
    $add = static function (string $sql) use ($pdo): void {
        $pdo->exec($sql);
    };
    foreach ([
        'status' => 'VARCHAR(20) NOT NULL DEFAULT "confirmed"',
        'confirm_token' => 'VARCHAR(64) NULL',
        'unsub_token' => 'VARCHAR(64) NULL',
        'user_id' => 'INT UNSIGNED NULL',
        'source' => 'VARCHAR(40) NULL',
        'confirmed_at' => 'DATETIME NULL',
        'unsubscribed_at' => 'DATETIME NULL',
        'confirm_sent_at' => 'DATETIME NULL',
    ] as $col => $def) {
        if (!in_array($col, $cols, true)) {
            $add('ALTER TABLE newsletter_subscribers ADD COLUMN ' . $col . ' ' . $def);
        }
    }

    $idx = $pdo->query('SHOW INDEX FROM newsletter_subscribers')->fetchAll(PDO::FETCH_ASSOC);
    $indexNames = array_values(array_unique(array_map(static fn (array $row): string => (string) $row['Key_name'], $idx)));
    if (!in_array('newsletter_confirm_token', $indexNames, true)) {
        $add('ALTER TABLE newsletter_subscribers ADD UNIQUE KEY newsletter_confirm_token (confirm_token)');
    }
    if (!in_array('newsletter_unsub_token', $indexNames, true)) {
        $add('ALTER TABLE newsletter_subscribers ADD UNIQUE KEY newsletter_unsub_token (unsub_token)');
    }

    $existing = $pdo->query('SELECT id FROM newsletter_subscribers WHERE unsub_token IS NULL OR unsub_token = ""')->fetchAll(PDO::FETCH_COLUMN);
    $upd = $pdo->prepare('UPDATE newsletter_subscribers SET status = "confirmed", confirmed_at = COALESCE(confirmed_at, created_at), unsub_token = ? WHERE id = ?');
    foreach ($existing as $id) {
        $upd->execute([bin2hex(random_bytes(16)), (int) $id]);
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS newsletter_campaigns (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            subject VARCHAR(255) NOT NULL,
            body_html MEDIUMTEXT NOT NULL,
            source VARCHAR(20) NOT NULL DEFAULT "manual",
            status VARCHAR(20) NOT NULL DEFAULT "queued",
            sent_count INT UNSIGNED NOT NULL DEFAULT 0,
            fail_count INT UNSIGNED NOT NULL DEFAULT 0,
            skip_count INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            started_at DATETIME NULL,
            finished_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS newsletter_deliveries (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            campaign_id INT UNSIGNED NOT NULL,
            subscriber_id INT UNSIGNED NOT NULL,
            email VARCHAR(191) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT "pending",
            error VARCHAR(255) NULL,
            sent_at DATETIME NULL,
            KEY newsletter_deliveries_campaign (campaign_id, status),
            KEY newsletter_deliveries_subscriber (subscriber_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $ins = $pdo->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_key = setting_key'
    );
    foreach ([
        'newsletter_enabled' => '0',
        'newsletter_weekday' => '3',
        'newsletter_hour' => '8',
        'newsletter_include_missions' => '1',
        'newsletter_include_people' => '1',
        'newsletter_include_url' => '1',
        'newsletter_source_url' => '',
        'newsletter_batch_size' => '25',
        'newsletter_smtp_host' => '',
        'newsletter_smtp_port' => '587',
        'newsletter_smtp_username' => '',
        'newsletter_smtp_password' => '',
        'newsletter_smtp_encryption' => 'tls',
        'newsletter_smtp_from_address' => '',
        'newsletter_smtp_from_name' => 'Acteurs du Livre',
        'newsletter_last_weekly_at' => '',
    ] as $key => $value) {
        $ins->execute([$key, $value]);
    }

    EmailTemplate::ensure(
        'newsletter-confirm',
        'Newsletter — confirmation',
        'Confirmez votre inscription à la lettre d’Acteurs du Livre',
        '<p>Bonjour,</p><p>Une inscription à la lettre d’information a été demandée avec cette adresse. Cliquez pour confirmer :</p><p><a href="{{ lien_confirmation }}">Confirmer mon inscription</a></p><p>Si vous n’êtes pas à l’origine de cette demande, ignorez ce message.</p>',
        'lien_confirmation'
    );
};
