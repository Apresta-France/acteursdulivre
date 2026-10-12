<?php

declare(strict_types=1);

use Adl\Models\EmailTemplate;

return static function (\PDO $pdo): void {
    EmailTemplate::ensure(
        'alerte-echange',
        'Alerte interne — échange commencé',
        'Nouvel échange entre {{ client }} et {{ prestataire }}',
        '<p>Bonjour,</p><p><strong>{{ client }}</strong> ({{ client_email }}) et <strong>{{ prestataire }}</strong> ({{ prestataire_email }}) viennent de commencer un échange.</p><p>Sujet : {{ sujet }}</p><p>{{ message }}</p><p><a href="{{ lien }}">Ouvrir la conversation</a></p>',
        'client, client_email, prestataire, prestataire_email, sujet, message, lien'
    );
    EmailTemplate::ensure(
        'alerte-demande-devis',
        'Alerte interne — demande de devis',
        '{{ evenement }} — {{ titre }}',
        '<p>Bonjour,</p><p><strong>{{ client }}</strong> ({{ client_email }}) vient d’ouvrir une {{ nature }} auprès de <strong>{{ prestataire }}</strong> ({{ prestataire_email }}).</p><p>Dossier <strong>{{ numero }}</strong> — {{ titre }}.</p><p>Montant indiqué : {{ montant }}.</p>{{ message_html }}<p><a href="{{ lien }}">Ouvrir le dossier</a></p>',
        'evenement, nature, client, client_email, prestataire, prestataire_email, numero, titre, montant, message_html, lien'
    );
    EmailTemplate::ensure(
        'alerte-devis-accepte',
        'Alerte interne — devis accepté',
        'Devis accepté — {{ numero }}',
        '<p>Bonjour,</p><p><strong>{{ client }}</strong> ({{ client_email }}) a accepté le devis de <strong>{{ prestataire }}</strong> ({{ prestataire_email }}).</p><p>Dossier <strong>{{ numero }}</strong> — {{ titre }}.</p><p>Montant : {{ montant }}. Acompte : {{ acompte }}.</p><p><a href="{{ lien }}">Ouvrir le dossier</a></p>',
        'client, client_email, prestataire, prestataire_email, numero, titre, montant, acompte, lien'
    );
};
