<?php

declare(strict_types=1);

use Adl\Models\ContactMessage;
use Adl\Models\EmailTemplate;

/**
 * Messages du formulaire /contact : file admin, import des e-mails contact-interne déjà envoyés.
 */
return static function (PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS contact_messages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(190) NOT NULL DEFAULT "",
            email VARCHAR(190) NOT NULL,
            user_id INT UNSIGNED NULL,
            body TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT "open",
            admin_note TEXT NULL,
            reply_body TEXT NULL,
            handled_by INT UNSIGNED NULL,
            handled_at DATETIME NULL,
            email_log_id INT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY contact_messages_email_log (email_log_id),
            KEY contact_messages_status (status, created_at),
            KEY contact_messages_email (email, created_at),
            KEY contact_messages_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    EmailTemplate::ensure(
        'contact-reponse',
        'Contact — réponse de l\'équipe',
        'Réponse à votre message',
        '<p>Bonjour {{ nom }},</p><p>{{ message }}</p><p>L\'équipe Acteurs du Livre</p>',
        'nom, message'
    );

    ContactMessage::importFromEmailLog();
};
