<?php

declare(strict_types=1);

use Adl\Models\EmailTemplate;

return static function (PDO $pdo): void {
    EmailTemplate::replaceContent(
        'demande-recommandation',
        '{{ prestataire }} vous demande une recommandation',
        '<p>Bonjour {{ prenom }},</p><p>{{ prestataire }} vous demande une recommandation pour sa page sur Acteurs du Livre{{ contexte }}.</p><p>Le texte vient de vous : ce n’est pas un avis lié à une mission suivie ici.</p><p><a href="{{ lien }}">Écrire la recommandation</a></p><p>Ce lien reste valable jusqu’au {{ expiration }}.</p>'
    );
    EmailTemplate::replaceContent(
        'relance-profil',
        'Votre vitrine n’est complétée qu’à {{ completion }} %',
        '<p>Bonjour {{ prenom }},</p><p>Votre vitrine est complétée à <strong>{{ completion }}&nbsp;%</strong>.</p><p>Il manque encore&nbsp;: {{ manques }}.</p><p><a href="{{ lien }}">Compléter ma vitrine</a></p>'
    );
    EmailTemplate::replaceContent(
        'relance-mission',
        'Aucune recherche n’est encore en ligne',
        '<p>Bonjour {{ prenom }},</p><p>Vous n’avez pas encore publié de recherche. Décrivez le besoin et le budget : les professionnels du métier choisi pourront y répondre.</p><p><a href="{{ lien }}">Rédiger l’annonce</a></p>'
    );
    EmailTemplate::replaceContent(
        'relance-demande',
        'Une demande attend votre réponse',
        '<p>Bonjour {{ prenom }},</p><p>{{ detail }}</p><p>Une réponse de votre part permet de faire avancer le projet.</p><p><a href="{{ lien }}">Ouvrir la demande</a></p>'
    );
    EmailTemplate::replaceContent(
        'nouvelle-mission-metier',
        'Nouvelle recherche {{ metier }} : {{ titre }}',
        '<p>Bonjour {{ prenom }},</p><p>Une recherche vient d’être publiée dans l’un de vos métiers&nbsp;: <strong>{{ titre }}</strong> ({{ metier }}).</p><p>Budget&nbsp;: {{ budget }}.</p><p><a href="{{ lien }}">Voir la recherche</a></p>'
    );
};
