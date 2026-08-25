<?php

declare(strict_types=1);

return static function (\PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE articles (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(191) NOT NULL UNIQUE,
            category VARCHAR(80) NULL,
            excerpt TEXT NULL,
            body MEDIUMTEXT NULL,
            published_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE settings (
            setting_key VARCHAR(120) PRIMARY KEY,
            setting_value TEXT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE email_templates (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(80) NOT NULL UNIQUE,
            name VARCHAR(160) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            body_html MEDIUMTEXT NOT NULL,
            variables VARCHAR(255) NULL,
            updated_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $templates = [
        [
            'bienvenue',
            'Bienvenue',
            'Bienvenue sur Acteurs du Livre, {{ prenom }}',
            '<p>Bonjour {{ prenom }},</p><p>Votre compte est prêt. Vous pouvez compléter votre vitrine et proposer vos premières prestations.</p><p><a href="{{ lien_espace }}">Ouvrir mon espace</a></p>',
            'prenom, lien_espace',
        ],
        [
            'contact-interne',
            'Contact — notification interne',
            'Nouveau message de {{ nom }}',
            '<p><strong>{{ nom }}</strong> ({{ email }})</p><p>{{ message }}</p>',
            'nom, email, message',
        ],
        [
            'contact-accuse',
            'Contact — accusé de réception',
            'Nous avons bien reçu votre message',
            '<p>Bonjour {{ nom }},</p><p>Votre message est arrivé. Une personne de l\'équipe vous répond sous 4 heures ouvrées.</p>',
            'nom',
        ],
        [
            'nouveau-message',
            'Nouveau message',
            'Nouveau message sur Acteurs du Livre',
            '<p>Vous avez reçu un nouveau message concernant <strong>{{ sujet }}</strong>.</p><p><a href="{{ lien }}">Ouvrir la conversation</a></p>',
            'sujet, lien',
        ],
        [
            'reset-password',
            'Réinitialisation du mot de passe',
            'Réinitialiser votre mot de passe',
            '<p>Bonjour {{ prenom }},</p><p>Une demande de réinitialisation a été faite. Ce lien expire dans une heure.</p><p><a href="{{ lien }}">Choisir un nouveau mot de passe</a></p>',
            'prenom, lien',
        ],
    ];

    $stmt = $pdo->prepare('INSERT INTO email_templates (slug, name, subject, body_html, variables) VALUES (?, ?, ?, ?, ?)');
    foreach ($templates as $row) {
        $stmt->execute($row);
    }

    $pdo->exec("INSERT INTO settings (setting_key, setting_value) VALUES
        ('site_tagline', 'La place de marché des métiers du livre'),
        ('commission_percent', '8')
    ");
};
