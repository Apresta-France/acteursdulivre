<?php

declare(strict_types=1);

/**
 * Un membre peut proposer une maison absente de l'annuaire : la fiche est créée en statut
 * « pending » et une demande de type « creation » rejoint la file des revendications.
 */
return static function (PDO $pdo): void {
    $col = $pdo->query('SHOW COLUMNS FROM publisher_claims LIKE "kind"')->fetch();
    if (!$col) {
        $pdo->exec('ALTER TABLE publisher_claims ADD COLUMN kind VARCHAR(20) NOT NULL DEFAULT "claim" AFTER user_id');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO email_templates (slug, name, subject, body_html, variables)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE slug = VALUES(slug)'
    );
    $templates = [
        [
            'maison-creation-admin',
            'Maison d’édition : nouvelle fiche proposée — administrateurs',
            'Nouvelle maison d’édition proposée : {{ maison }}',
            '<p>Bonjour {{ prenom }},</p>'
            . '<p><strong>{{ demandeur }}</strong> (compte {{ email_compte }}) propose d’ajouter la maison « <strong>{{ maison }}</strong> » ({{ lieu }}) à l’annuaire et souhaite en gérer la fiche.</p>'
            . '<p>Fonction déclarée : {{ fonction }}<br>E-mail professionnel : {{ email_pro }}<br>{{ domaine }}</p>'
            . '<p>{{ doublons }}</p>'
            . '<p>Message :</p><blockquote>{{ message }}</blockquote>'
            . '<p><a href="{{ lien_admin }}">Examiner la proposition dans l’administration</a> · <a href="{{ lien_fiche }}">Prévisualiser la fiche</a></p>',
            'prenom, demandeur, email_compte, email_pro, fonction, domaine, doublons, message, maison, lieu, lien_admin, lien_fiche',
        ],
        [
            'maison-creation-recue',
            'Maison d’édition : accusé de réception de la proposition',
            'Votre maison {{ maison }} est en cours de validation',
            '<p>Bonjour {{ prenom }},</p>'
            . '<p>Nous avons bien reçu votre proposition d’ajouter « <strong>{{ maison }}</strong> » à l’annuaire des maisons d’édition d’acteursdulivre.fr.</p>'
            . '<p>La fiche n’est pas encore visible publiquement : l’équipe vérifie chaque proposition à la main, en général sous deux jours ouvrés. Vous pouvez déjà la compléter depuis votre espace, et vous recevrez un e-mail dès qu’elle sera publiée.</p>'
            . '<p><a href="{{ lien_espace }}">Compléter ma fiche</a></p>',
            'prenom, maison, lien_espace',
        ],
        [
            'maison-creation-validee',
            'Maison d’édition : fiche publiée',
            'La fiche {{ maison }} est en ligne',
            '<p>Bonjour {{ prenom }},</p>'
            . '<p>Votre maison « <strong>{{ maison }}</strong> » fait désormais partie de l’annuaire des maisons d’édition. Elle affiche le badge « Fiche gérée par la maison » et vous la modifiez à tout moment depuis votre espace.</p>'
            . '<p>Les membres connectés peuvent consulter vos coordonnées et vous écrire directement.</p>'
            . '<p><a href="{{ lien_fiche }}">Voir la fiche publique</a> · <a href="{{ lien_espace }}">Modifier ma fiche</a></p>',
            'prenom, maison, lien_espace, lien_fiche',
        ],
        [
            'maison-creation-refusee',
            'Maison d’édition : proposition non retenue',
            'Votre proposition pour {{ maison }} n’a pas été retenue',
            '<p>Bonjour {{ prenom }},</p>'
            . '<p>Nous n’avons pas pu publier la fiche « <strong>{{ maison }}</strong> » dans l’annuaire.</p>'
            . '<p>Motif : {{ motif }}</p>'
            . '<p>Si la maison existe déjà dans l’annuaire, vous pouvez revendiquer sa fiche. Pour tout complément, écrivez-nous.</p>'
            . '<p><a href="{{ lien_annuaire }}">Ouvrir l’annuaire</a></p>',
            'prenom, maison, motif, lien_annuaire',
        ],
    ];
    foreach ($templates as $template) {
        $stmt->execute($template);
    }
};
