<?php

declare(strict_types=1);

use Adl\Models\EmailTemplate;

return static function (PDO $pdo): void {
    EmailTemplate::ensure(
        'recherche-retiree',
        'Recherche retirée',
        'La recherche « {{ titre }} » a été retirée',
        '<p>Bonjour {{ prenom }},</p><p>La recherche « {{ titre }} » a été retirée par le porteur de projet. Votre candidature n’est plus en attente.</p><p><a href="{{ lien }}">Voir mes candidatures</a></p>',
        'prenom, titre, lien'
    );
};
