# Acteurs du Livre

Place de marché des métiers du livre, éditée par **EDITIONS TESSERACT**. PHP 8.1+, MySQL, MVC maison (`Adl\`), sans framework.

Site local prévu : `https://acteursdulivre.test`.

## Modèle

Aucun abonnement. La première mission réalisée par un prestataire est offerte. Ensuite, une commission (8 %, 6 % pour les 100 premiers inscrits et dès 12 missions réalisées) est facturée **au prestataire** lorsque le client valide et note la mission.

La plateforme **n’encaisse pas** le prix des missions. Client et prestataire se règlent hors site. Les déclarations de règlement dans le suivi valent engagement sur l’honneur.

Pré-ouverture : inscriptions ouvertes ; ouverture clients annoncée pour octobre 2026.

## Prérequis

- PHP 8.1 ou plus, extensions `pdo_mysql`, `mbstring`, `fileinfo`, `json`
- MySQL 8 / MariaDB
- Un vhost qui pointe le **document root** vers `public/` (ou l’URL de base prévue par `APP_URL`)

## Installation

1. Cloner le dépôt et copier `.env.example` vers `.env`.
2. Renseigner `APP_URL`, `APP_KEY` (chaîne aléatoire), et les identifiants `DB_*`.
3. Créer la base MySQL vide.
4. Ouvrir `/install` une première fois, **ou** lancer :

```bash
php bin/migrate.php
```

Les migrations dans `database/migrations/` s’appliquent dans l’ordre. En `APP_DEBUG=true`, elles sont aussi rejouées au chargement de l’application.

5. Créer le premier administrateur depuis l’installateur, puis se connecter.

Dossiers à laisser inscriptibles par PHP : `storage/` (logs, uploads privés, cache) et `public/uploads/`.

## Cron

Relances (profil incomplet, message sans réponse, livraison à valider, facture échue) :

```bash
# toutes les heures, ou via l’URL /cron/{tache}
php -r "require 'vendor/autoload.php';" # si Composer est utilisé pour l’autoload
```

L’entrée HTTP est `GET /cron/{tache}` (sans secret dans l’URL). Le CLI `php bin/cron.php {tache}` reste disponible.

## SSO

Google et Facebook sont optionnels (`OAUTH_ENABLED`, puis Administration → Connexion sociale). Un compte e-mail + mot de passe reste toujours disponible. La configuration OAuth se fait dans l’admin, pas dans ce README.

## Structure

```
app/Controllers    Pages publiques, espace, admin, cron, install
app/Models         Utilisateurs, commandes, jalons, factures, messagerie
app/Views/pages    Vues PHP dynamiques (préférence sur les .html)
app/Views/*.html   Maquettes d’origine — référence de design uniquement
app/Data           Légal, catalogue, SEO, prototype (données partagées)
database/migrations
public/            Front controller, CSS, JS, polices
storage/uploads    Pièces jointes messagerie (servies après contrôle d’accès)
```

`View::fetch` charge d’abord le `.php` s’il existe. Les fichiers `.html` conservent le design fourni au départ ; ils ne sont plus servis dès qu’une vue PHP porte le même nom.

## Pages dynamiques

Accueil, comment ça marche, tarifs, confiance et à propos sont des vues PHP. Les chiffres affichés viennent de la base (profils, prestations, missions, avis réels). Les mentions légales ne portent que les données de EDITIONS TESSERACT.

## Messagerie

Pièces jointes : PDF, images, Word/ODT/texte, 8 Mo maximum. Stockage hors web public, téléchargement réservé aux participants de la conversation. Zone de dépôt (glisser-déposer) ou choix de fichier.

## Compte

Dans `/espace/parametres` : usages, mot de passe, notifications e-mail, mentions de facturation (SIREN, SIRET, TVA, code de routage facultatif, IBAN facultatif), export JSON RGPD, clôture du compte (bloquée s’il reste une commande ou une facture ouverte).

## Licence et contact

EDITIONS TESSERACT — 486 rue Sadi Carnot, 59184 Sainghin-en-Weppes.  
guillaume@editions-tesseract.fr
