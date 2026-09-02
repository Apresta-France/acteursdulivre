<?php

declare(strict_types=1);

use Adl\Models\EmailTemplate;

return static function (PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS review_requests (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            seller_id INT UNSIGNED NOT NULL,
            kind VARCHAR(20) NOT NULL,
            order_id INT UNSIGNED NULL,
            recipient_email VARCHAR(190) NOT NULL,
            recipient_name VARCHAR(190) NOT NULL DEFAULT "",
            context VARCHAR(190) NOT NULL DEFAULT "",
            token VARCHAR(64) NULL,
            status VARCHAR(20) NOT NULL DEFAULT "pending",
            last_sent_at DATETIME NOT NULL,
            completed_at DATETIME NULL,
            expires_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY review_requests_token (token),
            KEY review_requests_seller (seller_id, status),
            KEY review_requests_order (order_id),
            KEY review_requests_email (seller_id, recipient_email, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS recommendations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            request_id INT UNSIGNED NOT NULL,
            target_id INT UNSIGNED NOT NULL,
            author_name VARCHAR(190) NOT NULL,
            author_email VARCHAR(190) NOT NULL,
            author_role VARCHAR(190) NOT NULL DEFAULT "",
            context VARCHAR(190) NOT NULL DEFAULT "",
            body TEXT NOT NULL,
            hidden_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY recommendations_request (request_id),
            KEY recommendations_target (target_id, hidden_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    EmailTemplate::ensure(
        'demande-avis',
        'Demande d’avis',
        '{{ prestataire }} vous demande de valider et noter « {{ titre }} »',
        '<p>Bonjour {{ prenom }},</p><p>{{ prestataire }} vous demande de valider la mission « {{ titre }} » ({{ numero }}) et de laisser un avis. C’est ce jalon qui clôture le suivi.</p><p><a href="{{ lien }}">Valider et noter</a></p>',
        'prenom, prestataire, titre, numero, lien'
    );
    EmailTemplate::ensure(
        'demande-recommandation',
        'Demande de recommandation',
        '{{ prestataire }} vous demande une recommandation',
        '<p>Bonjour {{ prenom }},</p><p>{{ prestataire }} vous invite à laisser une recommandation sur sa vitrine Acteurs du Livre{{ contexte }}.</p><p>Ce texte apparaîtra comme une recommandation hors plateforme — distincte des avis liés à une mission réalisée ici.</p><p><a href="{{ lien }}">Écrire une recommandation</a></p><p>Ce lien expire le {{ expiration }}.</p>',
        'prenom, prestataire, contexte, lien, expiration'
    );
};
