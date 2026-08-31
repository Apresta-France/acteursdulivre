<?php

declare(strict_types=1);

use Adl\Models\EmailTemplate;

return static function (\PDO $pdo): void {
    EmailTemplate::ensure(
        'devis-refuse',
        'Devis refusé',
        'Devis refusé — {{ numero }}',
        '<p>Bonjour {{ prenom }},</p><p>Le porteur de projet a refusé le devis de la commande <strong>{{ numero }}</strong> : « {{ titre }} ».</p><p>La commande reste ouverte : vous pouvez proposer un nouveau devis.</p>{{ message_html }}<p><a href="{{ lien }}">Ouvrir le suivi</a></p>',
        'prenom, numero, titre, message_html, lien'
    );
    EmailTemplate::ensure(
        'commande-annulee',
        'Commande annulée',
        'Commande annulée — {{ numero }}',
        '<p>Bonjour {{ prenom }},</p><p>Le porteur de projet a annulé la commande <strong>{{ numero }}</strong> : « {{ titre }} ».</p><p>Cette commande est clôturée.</p><p><a href="{{ lien }}">Voir le suivi</a></p>',
        'prenom, numero, titre, lien'
    );
};
