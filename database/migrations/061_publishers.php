<?php

declare(strict_types=1);

use Adl\Models\Publisher;

return static function (PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS publishers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(190) NOT NULL,
            name VARCHAR(190) NOT NULL,
            name_search VARCHAR(190) NOT NULL DEFAULT "",
            country VARCHAR(120) NOT NULL DEFAULT "",
            country_slug VARCHAR(120) NOT NULL DEFAULT "",
            region VARCHAR(120) NOT NULL DEFAULT "",
            city VARCHAR(120) NOT NULL DEFAULT "",
            city_slug VARCHAR(120) NOT NULL DEFAULT "",
            founded VARCHAR(120) NOT NULL DEFAULT "",
            founded_year SMALLINT UNSIGNED NULL,
            parent_group VARCHAR(190) NOT NULL DEFAULT "",
            independent TINYINT(1) NOT NULL DEFAULT 0,
            size VARCHAR(80) NOT NULL DEFAULT "",
            size_key VARCHAR(20) NOT NULL DEFAULT "pme",
            typology VARCHAR(80) NOT NULL DEFAULT "",
            typology_key VARCHAR(20) NOT NULL DEFAULT "specialise",
            genres_json TEXT NULL,
            genres_search TEXT NULL,
            description TEXT NULL,
            website VARCHAR(255) NOT NULL DEFAULT "",
            contact_email VARCHAR(190) NOT NULL DEFAULT "",
            contact_address VARCHAR(255) NOT NULL DEFAULT "",
            contact_phone VARCHAR(40) NOT NULL DEFAULT "",
            submissions_note VARCHAR(600) NOT NULL DEFAULT "",
            segments VARCHAR(255) NOT NULL DEFAULT "",
            logo_path VARCHAR(255) NULL,
            search_text TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT "published",
            source VARCHAR(20) NOT NULL DEFAULT "import",
            owner_user_id INT UNSIGNED NULL,
            claimed_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL,
            UNIQUE KEY publishers_slug (slug),
            KEY publishers_country (status, country_slug, city_slug),
            KEY publishers_size (status, size_key),
            KEY publishers_owner (owner_user_id),
            KEY publishers_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS publisher_claims (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            publisher_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            role_title VARCHAR(120) NOT NULL DEFAULT "",
            company_email VARCHAR(190) NOT NULL DEFAULT "",
            phone VARCHAR(40) NOT NULL DEFAULT "",
            message TEXT NULL,
            domain_match TINYINT(1) NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT "pending",
            admin_note TEXT NULL,
            decided_by INT UNSIGNED NULL,
            decided_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY publisher_claims_status (status, created_at),
            KEY publisher_claims_publisher (publisher_id, status),
            KEY publisher_claims_user (user_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS publisher_contact_views (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            publisher_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            ip_hash VARCHAR(40) NOT NULL DEFAULT "",
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY publisher_contact_views_user (user_id, created_at),
            KEY publisher_contact_views_publisher (publisher_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $seed = ADL_ROOT . '/database/seeds/maisons-edition.json';
    if (is_file($seed)) {
        $rows = json_decode((string) file_get_contents($seed), true);
        if (is_array($rows)) {
            Publisher::importRows($pdo, $rows);
        }
    }

    $stmt = $pdo->prepare(
        'INSERT INTO email_templates (slug, name, subject, body_html, variables)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE slug = VALUES(slug)'
    );
    $templates = [
        [
            'maison-revendication-admin',
            'Maison d’édition : demande de prise en main — administrateurs',
            'Une maison d’édition souhaite gérer sa fiche : {{ maison }}',
            '<p>Bonjour {{ prenom }},</p>'
            . '<p><strong>{{ demandeur }}</strong> (compte {{ email_compte }}) demande à prendre la main sur la fiche « <strong>{{ maison }}</strong> » de l’annuaire des maisons d’édition.</p>'
            . '<p>Fonction déclarée : {{ fonction }}<br>E-mail professionnel : {{ email_pro }}<br>{{ domaine }}</p>'
            . '<p>Message :</p><blockquote>{{ message }}</blockquote>'
            . '<p><a href="{{ lien_admin }}">Examiner la demande dans l’administration</a> · <a href="{{ lien_fiche }}">Voir la fiche publique</a></p>',
            'prenom, demandeur, email_compte, email_pro, fonction, domaine, message, maison, lien_admin, lien_fiche',
        ],
        [
            'maison-revendication-recue',
            'Maison d’édition : accusé de réception de la demande',
            'Votre demande pour la fiche {{ maison }} est bien reçue',
            '<p>Bonjour {{ prenom }},</p>'
            . '<p>Nous avons bien reçu votre demande pour gérer la fiche « <strong>{{ maison }}</strong> » sur acteursdulivre.fr.</p>'
            . '<p>L’équipe vérifie chaque demande à la main, en général sous deux jours ouvrés. Vous recevrez un e-mail dès qu’une décision est prise.</p>'
            . '<p><a href="{{ lien_fiche }}">Revoir la fiche</a></p>',
            'prenom, maison, lien_fiche',
        ],
        [
            'maison-revendication-validee',
            'Maison d’édition : fiche attribuée',
            'La fiche {{ maison }} est désormais la vôtre',
            '<p>Bonjour {{ prenom }},</p>'
            . '<p>Votre demande a été validée : vous gérez désormais la fiche « <strong>{{ maison }}</strong> » dans l’annuaire des maisons d’édition.</p>'
            . '<p>Vous pouvez compléter la présentation, les genres publiés, les coordonnées et ajouter un logo depuis votre espace. Les membres connectés pourront vous écrire directement.</p>'
            . '<p><a href="{{ lien_espace }}">Compléter ma fiche</a> · <a href="{{ lien_fiche }}">Voir la fiche publique</a></p>',
            'prenom, maison, lien_espace, lien_fiche',
        ],
        [
            'maison-revendication-refusee',
            'Maison d’édition : demande refusée',
            'Votre demande pour la fiche {{ maison }} n’a pas été retenue',
            '<p>Bonjour {{ prenom }},</p>'
            . '<p>Nous n’avons pas pu valider votre demande pour la fiche « <strong>{{ maison }}</strong> ».</p>'
            . '<p>Motif : {{ motif }}</p>'
            . '<p>Si vous disposez d’éléments complémentaires (adresse e-mail sur le domaine de la maison, justificatif), vous pouvez renouveler la demande ou nous écrire.</p>'
            . '<p><a href="{{ lien_fiche }}">Revoir la fiche</a></p>',
            'prenom, maison, motif, lien_fiche',
        ],
    ];
    foreach ($templates as $template) {
        $stmt->execute($template);
    }
};
