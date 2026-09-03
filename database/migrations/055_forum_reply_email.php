<?php

declare(strict_types=1);

use Adl\Models\EmailTemplate;

return static function (PDO $pdo): void {
    EmailTemplate::ensure(
        'forum-nouvelle-reponse',
        'Forum — nouvelle réponse',
        'Nouvelle réponse sur « {{ titre }} »',
        '<p>Bonjour {{ prenom }},</p><p>{{ qui }} a répondu à la discussion <strong>{{ titre }}</strong>.</p><p><a href="{{ lien }}">Lire la réponse</a></p>',
        'prenom, qui, titre, lien'
    );
};
