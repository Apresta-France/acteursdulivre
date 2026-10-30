<?php

declare(strict_types=1);

use Adl\Models\EmailTemplate;

return static function (PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS visio_proposals (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            conversation_id INT UNSIGNED NOT NULL,
            provider_id INT UNSIGNED NOT NULL,
            client_id INT UNSIGNED NOT NULL,
            token_hash CHAR(64) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT "pending",
            slot1_at DATETIME NOT NULL,
            slot2_at DATETIME NOT NULL,
            slot3_at DATETIME NOT NULL,
            chosen_slot TINYINT UNSIGNED NULL,
            meet_url VARCHAR(191) NULL,
            refusal_reason TEXT NULL,
            responded_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            UNIQUE KEY visio_proposals_token (token_hash),
            UNIQUE KEY visio_proposals_meet (meet_url),
            KEY visio_proposals_conversation (conversation_id, id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    EmailTemplate::ensure(
        'visio-proposition',
        'Proposition de visio',
        '{{ prestataire }} vous propose une visio',
        '<p>Bonjour {{ prenom }},</p><p>{{ prestataire }} vous propose une visio sur kMeet. Choisissez l’un de ces trois créneaux&nbsp;:</p>{{ creneaux_html }}<p>Vous pouvez aussi <a href="{{ lien }}">ouvrir la proposition</a>.</p>',
        'prenom, prestataire, creneaux_html, lien'
    );
    EmailTemplate::ensure(
        'visio-acceptee',
        'Visio confirmée',
        'Visio confirmée le {{ quand }}',
        '<p>Bonjour {{ prenom }},</p><p>{{ client }} a retenu le créneau du {{ quand }}.</p><p>Lien de visio kMeet&nbsp;: <a href="{{ lien_visio }}">{{ lien_visio }}</a></p><p><a href="{{ lien }}">Ouvrir la conversation</a></p>',
        'prenom, client, quand, lien_visio, lien'
    );
    EmailTemplate::ensure(
        'visio-lien',
        'Lien de visio',
        'Votre visio du {{ quand }}',
        '<p>Bonjour {{ prenom }},</p><p>Votre visio avec {{ prestataire }} est prévue le {{ quand }}.</p><p><a href="{{ lien_visio }}">Rejoindre la visio</a></p><p>{{ lien_visio }}</p><p><a href="{{ lien }}">Ouvrir la conversation</a></p>',
        'prenom, prestataire, quand, lien_visio, lien'
    );
    EmailTemplate::ensure(
        'visio-aucun-creneau',
        'Aucun créneau de visio',
        'Aucun créneau de visio ne convient',
        '<p>Bonjour {{ prenom }},</p><p>{{ client }} indique qu’aucun des trois créneaux ne lui convient. Vous pouvez en proposer d’autres.</p><p><a href="{{ lien }}">Proposer d’autres créneaux</a></p>',
        'prenom, client, lien'
    );
    EmailTemplate::ensure(
        'visio-refusee',
        'Visio refusée',
        '{{ client }} a refusé la visio',
        '<p>Bonjour {{ prenom }},</p><p>{{ client }} a refusé la visio.</p><p>{{ message }}</p><p><a href="{{ lien }}">Ouvrir la conversation</a></p>',
        'prenom, client, message, lien'
    );
};
