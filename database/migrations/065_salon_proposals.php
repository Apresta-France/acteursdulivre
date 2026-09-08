<?php

declare(strict_types=1);

use Adl\Models\EmailTemplate;

/**
 * Propositions de salons : un visiteur ou un membre soumet une manifestation,
 * l’équipe la publie (ou la refuse) depuis /admin/salons.
 */
return static function (PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS salon_proposals (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NULL,
            contact_name VARCHAR(190) NOT NULL DEFAULT "",
            contact_email VARCHAR(190) NOT NULL,
            name VARCHAR(255) NOT NULL,
            category VARCHAR(120) NOT NULL DEFAULT "",
            starts_on DATE NULL,
            ends_on DATE NULL,
            city VARCHAR(120) NOT NULL DEFAULT "",
            region VARCHAR(120) NOT NULL DEFAULT "",
            country VARCHAR(120) NOT NULL DEFAULT "France",
            venue VARCHAR(255) NOT NULL DEFAULT "",
            website VARCHAR(255) NOT NULL DEFAULT "",
            organizer VARCHAR(255) NOT NULL DEFAULT "",
            ticket VARCHAR(255) NOT NULL DEFAULT "",
            audience VARCHAR(190) NOT NULL DEFAULT "",
            description TEXT NULL,
            notes TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT "pending",
            admin_note TEXT NULL,
            salon_id INT UNSIGNED NULL,
            decided_by INT UNSIGNED NULL,
            decided_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY salon_proposals_status (status, created_at),
            KEY salon_proposals_email (contact_email, created_at),
            KEY salon_proposals_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    EmailTemplate::ensure(
        'salon-proposition-admin',
        'Salon : nouvelle proposition — administrateurs',
        'Nouveau salon proposé : {{ salon }}',
        '<p>Bonjour {{ prenom }},</p>'
        . '<p><strong>{{ demandeur }}</strong> ({{ email }}) propose d’ajouter « <strong>{{ salon }}</strong> » à l’agenda ({{ lieu }}, {{ dates }}).</p>'
        . '<p>{{ doublons }}</p>'
        . '<p>Message :</p><blockquote>{{ message }}</blockquote>'
        . '<p><a href="{{ lien_admin }}">Examiner la proposition</a></p>',
        'prenom, demandeur, email, salon, lieu, dates, doublons, message, lien_admin'
    );
    EmailTemplate::ensure(
        'salon-proposition-recue',
        'Salon : accusé de réception de la proposition',
        'Votre salon {{ salon }} est en cours de validation',
        '<p>Bonjour {{ prenom }},</p>'
        . '<p>Nous avons bien reçu votre proposition d’ajouter « <strong>{{ salon }}</strong> » à l’agenda des salons d’acteursdulivre.fr.</p>'
        . '<p>L’équipe vérifie chaque manifestation à la main, en général sous deux jours ouvrés. Vous recevrez un e-mail dès qu’elle sera publiée, ou si un complément est nécessaire.</p>'
        . '<p><a href="{{ lien_agenda }}">Parcourir l’agenda</a></p>',
        'prenom, salon, lien_agenda'
    );
    EmailTemplate::ensure(
        'salon-proposition-validee',
        'Salon : fiche publiée',
        '{{ salon }} est dans l’agenda',
        '<p>Bonjour {{ prenom }},</p>'
        . '<p>« <strong>{{ salon }}</strong> » figure désormais dans l’agenda des salons du livre. Merci de l’avoir signalé.</p>'
        . '<p><a href="{{ lien_fiche }}">Voir la fiche</a> · <a href="{{ lien_agenda }}">Ouvrir l’agenda</a></p>',
        'prenom, salon, lien_fiche, lien_agenda'
    );
    EmailTemplate::ensure(
        'salon-proposition-refusee',
        'Salon : proposition non retenue',
        'Votre proposition pour {{ salon }} n’a pas été retenue',
        '<p>Bonjour {{ prenom }},</p>'
        . '<p>Nous n’avons pas pu publier « <strong>{{ salon }}</strong> » dans l’agenda.</p>'
        . '<p>Motif : {{ motif }}</p>'
        . '<p>Si le salon y figure déjà, vous le trouverez dans l’agenda. Pour tout complément, écrivez-nous.</p>'
        . '<p><a href="{{ lien_agenda }}">Ouvrir l’agenda</a></p>',
        'prenom, salon, motif, lien_agenda'
    );
};
