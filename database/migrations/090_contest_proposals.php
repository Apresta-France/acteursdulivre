<?php

declare(strict_types=1);

use Adl\Models\EmailTemplate;

/**
 * Propositions de prix et concours : un visiteur ou un membre soumet une
 * récompense ou un appel à textes, l'équipe le publie (ou le refuse)
 * depuis /admin/prix.
 */
return static function (PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS contest_proposals (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NULL,
            contact_name VARCHAR(190) NOT NULL DEFAULT "",
            contact_email VARCHAR(190) NOT NULL,
            name VARCHAR(255) NOT NULL,
            kind VARCHAR(60) NOT NULL DEFAULT "prix",
            category VARCHAR(120) NOT NULL DEFAULT "",
            opens_on DATE NULL,
            deadline_on DATE NULL,
            awarded_on DATE NULL,
            prize VARCHAR(255) NOT NULL DEFAULT "",
            entry_fee VARCHAR(255) NOT NULL DEFAULT "",
            eligibility VARCHAR(255) NOT NULL DEFAULT "",
            city VARCHAR(120) NOT NULL DEFAULT "",
            region VARCHAR(120) NOT NULL DEFAULT "",
            country VARCHAR(120) NOT NULL DEFAULT "France",
            venue VARCHAR(255) NOT NULL DEFAULT "",
            website VARCHAR(255) NOT NULL DEFAULT "",
            organizer VARCHAR(255) NOT NULL DEFAULT "",
            description TEXT NULL,
            notes TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT "pending",
            admin_note TEXT NULL,
            contest_id INT UNSIGNED NULL,
            decided_by INT UNSIGNED NULL,
            decided_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY contest_proposals_status (status, created_at),
            KEY contest_proposals_email (contact_email, created_at),
            KEY contest_proposals_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    EmailTemplate::ensure(
        'prix-proposition-admin',
        'Prix : nouvelle proposition — administrateurs',
        'Nouveau prix ou concours proposé : {{ prix }}',
        '<p>Bonjour {{ prenom }},</p>'
        . '<p><strong>{{ demandeur }}</strong> ({{ email }}) propose d’ajouter « <strong>{{ prix }}</strong> » à l’annuaire des prix et concours ({{ lieu }}, {{ dates }}).</p>'
        . '<p>{{ doublons }}</p>'
        . '<p>Message :</p><blockquote>{{ message }}</blockquote>'
        . '<p><a href="{{ lien_admin }}">Examiner la proposition</a></p>',
        'prenom, demandeur, email, prix, lieu, dates, doublons, message, lien_admin'
    );
    EmailTemplate::ensure(
        'prix-proposition-recue',
        'Prix : accusé de réception de la proposition',
        'Votre proposition {{ prix }} est en cours de validation',
        '<p>Bonjour {{ prenom }},</p>'
        . '<p>Nous avons bien reçu votre proposition d’ajouter « <strong>{{ prix }}</strong> » à l’annuaire des prix et concours d’acteursdulivre.fr.</p>'
        . '<p>L’équipe vérifie chaque récompense à la main, en général sous deux jours ouvrés. Vous recevrez un e-mail dès qu’elle sera publiée, ou si un complément est nécessaire.</p>'
        . '<p><a href="{{ lien_agenda }}">Parcourir les prix et concours</a></p>',
        'prenom, prix, lien_agenda'
    );
    EmailTemplate::ensure(
        'prix-proposition-validee',
        'Prix : fiche publiée',
        '{{ prix }} est dans l’annuaire',
        '<p>Bonjour {{ prenom }},</p>'
        . '<p>« <strong>{{ prix }}</strong> » figure désormais dans l’annuaire des prix et concours. Merci de l’avoir signalé.</p>'
        . '<p><a href="{{ lien_fiche }}">Voir la fiche</a> · <a href="{{ lien_agenda }}">Ouvrir l’annuaire</a></p>',
        'prenom, prix, lien_fiche, lien_agenda'
    );
    EmailTemplate::ensure(
        'prix-proposition-refusee',
        'Prix : proposition non retenue',
        'Votre proposition pour {{ prix }} n’a pas été retenue',
        '<p>Bonjour {{ prenom }},</p>'
        . '<p>Nous n’avons pas pu publier « <strong>{{ prix }}</strong> » dans l’annuaire des prix et concours.</p>'
        . '<p>Motif : {{ motif }}</p>'
        . '<p>Si la récompense y figure déjà, vous la trouverez dans l’annuaire. Pour tout complément, écrivez-nous.</p>'
        . '<p><a href="{{ lien_agenda }}">Ouvrir l’annuaire</a></p>',
        'prenom, prix, motif, lien_agenda'
    );
};
