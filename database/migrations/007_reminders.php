<?php

declare(strict_types=1);

use Adl\Models\EmailTemplate;

return static function (\PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE reminder_sends (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            kind VARCHAR(60) NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            subject_type VARCHAR(40) NULL,
            subject_id INT UNSIGNED NULL,
            sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_lookup (kind, user_id, subject_type, subject_id, sent_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'ALTER TABLE notifications
            ADD COLUMN kind VARCHAR(60) NULL AFTER user_id,
            ADD COLUMN subject_type VARCHAR(40) NULL AFTER kind,
            ADD COLUMN subject_id INT UNSIGNED NULL AFTER subject_type,
            ADD INDEX idx_user_read (user_id, read_at)'
    );

    EmailTemplate::ensure(
        'relance-profil',
        'Relance — vitrine incomplète',
        'Votre vitrine n’est complétée qu’à {{ completion }} %',
        '<p>Bonjour {{ prenom }},</p><p>Votre vitrine est complétée à <strong>{{ completion }}&nbsp;%</strong>. Les profils précis reçoivent nettement plus de demandes.</p><p>Il manque encore&nbsp;: {{ manques }}.</p><p><a href="{{ lien }}">Compléter ma vitrine</a></p>',
        'prenom, completion, manques, lien'
    );
    EmailTemplate::ensure(
        'relance-mission',
        'Relance — première mission',
        'Publiez votre première mission sur Acteurs du Livre',
        '<p>Bonjour {{ prenom }},</p><p>Vous cherchez des prestataires, mais aucune mission n’est encore en ligne. Décrivez le besoin et le budget&nbsp;: les professionnels du métier choisi pourront y répondre gratuitement.</p><p><a href="{{ lien }}">Rédiger l’annonce</a></p>',
        'prenom, lien'
    );
    EmailTemplate::ensure(
        'relance-demande',
        'Relance — demande sans réponse',
        'Une demande attend votre réponse',
        '<p>Bonjour {{ prenom }},</p><p>{{ detail }}</p><p>Sans réponse, le projet risque de s’enliser — et l’autre partie d’aller voir ailleurs.</p><p><a href="{{ lien }}">Ouvrir la demande</a></p>',
        'prenom, detail, lien'
    );
    EmailTemplate::ensure(
        'relance-message',
        'Relance — message sans réponse',
        'Un message attend votre réponse',
        '<p>Bonjour {{ prenom }},</p><p>Vous avez un message sans réponse concernant <strong>{{ sujet }}</strong>, envoyé {{ delai }}.</p><p><a href="{{ lien }}">Ouvrir la conversation</a></p>',
        'prenom, sujet, delai, lien'
    );
    EmailTemplate::ensure(
        'relance-projet',
        'Relance — projet en cours',
        'Votre projet « {{ titre }} » est toujours en cours',
        '<p>Bonjour {{ prenom }},</p><p>{{ detail }}</p><p><a href="{{ lien }}">Voir le suivi</a></p>',
        'prenom, titre, detail, lien'
    );
};
