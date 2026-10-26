<?php

use Adl\Models\EmailTemplate;

/**
 * Salons partenaires : mise en avant payante (featured_until), fiche enrichie
 * (logo, programme, exposants) et revendication par l’organisateur
 * (owner_user_id + salon_claims), sur le modèle des maisons d’édition.
 */
return static function (PDO $pdo): void {
    $columns = [];
    foreach ($pdo->query('SHOW COLUMNS FROM salons')->fetchAll(PDO::FETCH_COLUMN) as $col) {
        $columns[(string) $col] = true;
    }
    $add = static function (string $sql) use ($pdo): void {
        $pdo->exec($sql);
    };
    if (!isset($columns['featured_until'])) {
        $add('ALTER TABLE salons ADD COLUMN featured_until DATE NULL AFTER dates_confirmed');
    }
    if (!isset($columns['owner_user_id'])) {
        $add('ALTER TABLE salons ADD COLUMN owner_user_id INT UNSIGNED NULL AFTER is_direct');
    }
    if (!isset($columns['claimed_at'])) {
        $add('ALTER TABLE salons ADD COLUMN claimed_at DATETIME NULL AFTER owner_user_id');
    }
    if (!isset($columns['confirmed_by_organizer'])) {
        $add('ALTER TABLE salons ADD COLUMN confirmed_by_organizer TINYINT(1) NOT NULL DEFAULT 0 AFTER dates_confirmed');
    }
    if (!isset($columns['logo_path'])) {
        $add('ALTER TABLE salons ADD COLUMN logo_path VARCHAR(255) NULL AFTER socials');
    }
    if (!isset($columns['program'])) {
        $add('ALTER TABLE salons ADD COLUMN program TEXT NULL AFTER description');
    }
    if (!isset($columns['exhibitors_list'])) {
        $add('ALTER TABLE salons ADD COLUMN exhibitors_list TEXT NULL AFTER program');
    }

    $keys = [];
    foreach ($pdo->query('SHOW INDEX FROM salons')->fetchAll(PDO::FETCH_ASSOC) as $idx) {
        $keys[(string) $idx['Key_name']] = true;
    }
    if (!isset($keys['salons_featured'])) {
        $add('ALTER TABLE salons ADD KEY salons_featured (featured_until)');
    }
    if (!isset($keys['salons_owner'])) {
        $add('ALTER TABLE salons ADD KEY salons_owner (owner_user_id)');
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS salon_claims (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            salon_id INT UNSIGNED NOT NULL,
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
            KEY salon_claims_status (status, created_at),
            KEY salon_claims_salon (salon_id, status),
            KEY salon_claims_user (user_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    EmailTemplate::ensure(
        'salon-revendication-admin',
        'Salon : demande de gestion de fiche — administrateurs',
        'Revendication de fiche salon : {{ salon }}',
        '<p>Bonjour {{ prenom }},</p>'
        . '<p><strong>{{ demandeur }}</strong> (compte {{ email_compte }}, e-mail pro {{ email_pro }}, fonction « {{ fonction }} ») demande à gérer la fiche « <strong>{{ salon }}</strong> ».</p>'
        . '<p>{{ domaine }}</p>'
        . '<p>Message :</p><blockquote>{{ message }}</blockquote>'
        . '<p><a href="{{ lien_admin }}">Examiner la demande</a> · <a href="{{ lien_fiche }}">Voir la fiche</a></p>',
        'prenom, salon, demandeur, email_compte, email_pro, fonction, domaine, message, lien_admin, lien_fiche'
    );
    EmailTemplate::ensure(
        'salon-revendication-recue',
        'Salon : accusé de réception de la revendication',
        'Votre demande pour la fiche {{ salon }} est bien reçue',
        '<p>Bonjour {{ prenom }},</p>'
        . '<p>Nous avons bien reçu votre demande de prise en main de la fiche « <strong>{{ salon }}</strong> ».</p>'
        . '<p>L’équipe vérifie chaque demande à la main — en général sous deux jours ouvrés — et vous prévient par e-mail dès qu’elle est traitée.</p>'
        . '<p><a href="{{ lien_fiche }}">Voir la fiche</a></p>',
        'prenom, salon, lien_fiche'
    );
    EmailTemplate::ensure(
        'salon-revendication-validee',
        'Salon : fiche attribuée à l’organisateur',
        'La fiche {{ salon }} est à vous',
        '<p>Bonjour {{ prenom }},</p>'
        . '<p>Vous gérez désormais la fiche « <strong>{{ salon }}</strong> » : dates confirmées par l’organisateur, présentation, programme, exposants et logo se modifient depuis votre espace.</p>'
        . '<p><a href="{{ lien_espace }}">Ouvrir mon espace salon</a> · <a href="{{ lien_fiche }}">Voir la fiche</a></p>',
        'prenom, salon, lien_espace, lien_fiche'
    );
    EmailTemplate::ensure(
        'salon-revendication-refusee',
        'Salon : revendication non retenue',
        'Votre demande pour la fiche {{ salon }} n’a pas été retenue',
        '<p>Bonjour {{ prenom }},</p>'
        . '<p>La demande de prise en main de la fiche « <strong>{{ salon }}</strong> » a été refusée.</p>'
        . '<p>Motif : {{ motif }}</p>'
        . '<p>Si vous pensez qu’il s’agit d’une erreur, répondez à cet e-mail ou écrivez-nous via la page contact.</p>'
        . '<p><a href="{{ lien_fiche }}">Voir la fiche</a></p>',
        'prenom, salon, motif, lien_fiche'
    );
};
