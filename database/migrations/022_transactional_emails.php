<?php

declare(strict_types=1);

use Adl\Models\EmailTemplate;

return static function (\PDO $pdo): void {
    EmailTemplate::ensure(
        'nouvelle-commande',
        'Nouvelle commande',
        'Nouvelle commande {{ numero }} — {{ titre }}',
        '<p>Bonjour {{ prenom }},</p><p>Un porteur de projet a ouvert la commande <strong>{{ numero }}</strong> : « {{ titre }} ».</p><p>Envoyez le devis pour lancer les jalons. Le règlement se fait entre vous, hors de la plateforme.</p><p><a href="{{ lien }}">Ouvrir le suivi</a></p>',
        'prenom, numero, titre, lien'
    );
    EmailTemplate::ensure(
        'commande-acceptee',
        'Commande acceptée',
        'Commande {{ numero }} acceptée',
        '<p>Bonjour {{ prenom }},</p><p>Le prestataire a accepté « {{ titre }} » ({{ numero }}) et démarre le travail.</p><p><a href="{{ lien }}">Voir le suivi</a></p>',
        'prenom, numero, titre, lien'
    );
    EmailTemplate::ensure(
        'commande-livree',
        'Livraison à valider',
        'Livraison à valider — {{ numero }}',
        '<p>Bonjour {{ prenom }},</p><p>« {{ titre }} » ({{ numero }}) est livrée. Validez et notez la mission pour clôturer.</p><p><a href="{{ lien }}">Valider la livraison</a></p>',
        'prenom, numero, titre, lien'
    );
    EmailTemplate::ensure(
        'commande-litige',
        'Litige ouvert',
        'Litige ouvert sur {{ numero }}',
        '<p>Bonjour {{ prenom }},</p><p>Un litige a été signalé sur « {{ titre }} » ({{ numero }}).</p><p>{{ motif }}</p><p><a href="{{ lien }}">Voir le suivi</a></p>',
        'prenom, numero, titre, motif, lien'
    );
    EmailTemplate::ensure(
        'nouvelle-candidature',
        'Nouvelle candidature',
        'Nouvelle candidature sur « {{ titre }} »',
        '<p>Bonjour {{ prenom }},</p><p>{{ qui }} propose ses services sur « {{ titre }} ».</p><p><a href="{{ lien }}">Voir les candidatures</a></p>',
        'prenom, qui, titre, lien'
    );
    EmailTemplate::ensure(
        'candidature-refusee',
        'Candidature non retenue',
        'Votre candidature n’a pas été retenue',
        '<p>Bonjour {{ prenom }},</p><p>Votre proposition sur « {{ titre }} » n’a pas été retenue.</p><p><a href="{{ lien }}">Voir mes candidatures</a></p>',
        'prenom, titre, lien'
    );
    EmailTemplate::ensure(
        'facture-commission',
        'Facture de commission',
        'Facture {{ numero }} — {{ montant }}',
        '<p>Bonjour {{ prenom }},</p><p>Le client a validé la mission. Votre facture de commission {{ numero }} ({{ montant }}) est à régler avant le {{ echeance }}.</p><p><a href="{{ lien }}">Ouvrir la facturation</a></p>',
        'prenom, numero, montant, echeance, lien'
    );
    EmailTemplate::ensure(
        'facture-echue',
        'Facture échue',
        'Facture {{ numero }} échue — prestations suspendues',
        '<p>Bonjour {{ prenom }},</p><p>La facture {{ numero }} n’a pas été réglée. Vos fiches ne sont plus proposées tant que le paiement n’est pas reçu.</p><p><a href="{{ lien }}">Régulariser</a></p>',
        'prenom, numero, lien'
    );
};
