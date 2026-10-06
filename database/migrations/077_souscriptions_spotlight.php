<?php

declare(strict_types=1);

use Adl\Models\EmailTemplate;

/**
 * Mise en avant d’un livre : couverture, proposition publique,
 * et annonce sponsorisée signalée comme telle.
 */
return static function (PDO $pdo): void {
    $cols = $pdo->query('SHOW COLUMNS FROM souscriptions')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('cover_image', $cols, true)) {
        $pdo->exec(
            'ALTER TABLE souscriptions
             ADD COLUMN cover_image VARCHAR(255) NOT NULL DEFAULT "" AFTER cover_rule'
        );
    }
    if (!in_array('proposer_name', $cols, true)) {
        $pdo->exec(
            'ALTER TABLE souscriptions
             ADD COLUMN proposer_name VARCHAR(190) NOT NULL DEFAULT "" AFTER outcome'
        );
    }
    if (!in_array('proposer_email', $cols, true)) {
        $pdo->exec(
            'ALTER TABLE souscriptions
             ADD COLUMN proposer_email VARCHAR(190) NOT NULL DEFAULT "" AFTER proposer_name'
        );
    }
    if (!in_array('proposer_note', $cols, true)) {
        $pdo->exec(
            'ALTER TABLE souscriptions
             ADD COLUMN proposer_note TEXT NULL AFTER proposer_email'
        );
    }

    EmailTemplate::ensure(
        'souscription-proposition-admin',
        'Souscription : nouvelle proposition — administrateurs',
        'Livre proposé : {{ livre }}',
        '<p>Bonjour {{ prenom }},</p>'
        . '<p><strong>{{ demandeur }}</strong> ({{ email }}) propose d’annoncer « <strong>{{ livre }}</strong> » ({{ type }}, sur {{ plateforme }}).</p>'
        . '<p>Message :</p><blockquote>{{ message }}</blockquote>'
        . '<p><a href="{{ lien_admin }}">Examiner la proposition</a></p>',
        'prenom, demandeur, email, livre, type, plateforme, message, lien_admin'
    );
    EmailTemplate::ensure(
        'souscription-proposition-recue',
        'Souscription : accusé de réception de la proposition',
        'Votre livre {{ livre }} est en cours de vérification',
        '<p>Bonjour {{ prenom }},</p>'
        . '<p>Nous avons bien reçu votre proposition d’annoncer « <strong>{{ livre }}</strong> » sur acteursdulivre.fr.</p>'
        . '<p>L’équipe vérifie chaque fiche à la main. Le livre n’est pas encore visible. Vous pouvez répondre à cet e-mail si un complément est utile.</p>'
        . '<p><a href="{{ lien }}">Voir les livres annoncés</a></p>',
        'prenom, livre, lien'
    );
};
