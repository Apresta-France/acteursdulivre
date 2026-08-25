<?php

declare(strict_types=1);

use Adl\Controllers\AccountController;
use Adl\Controllers\AdminController;
use Adl\Controllers\AuthController;
use Adl\Controllers\InstallController;
use Adl\Controllers\PageController;
use Adl\Core\Router;

return static function (Router $router): void {
    $router->get('/install', [InstallController::class, 'index']);
    $router->post('/install', [InstallController::class, 'store']);

    $router->get('/', [PageController::class, 'home']);
    $router->get('/recherche', [PageController::class, 'search']);
    $router->get('/api/recherche', [PageController::class, 'searchApi']);
    $router->get('/metiers/{slug}', [PageController::class, 'metier']);
    $router->get('/prestations/{slug}', [PageController::class, 'fiche']);
    $router->get('/prestataires/{slug}', [PageController::class, 'profil']);
    $router->get('/missions', [PageController::class, 'missions']);
    $router->get('/missions/{slug}', [PageController::class, 'mission']);
    $router->get('/comment-ca-marche', [PageController::class, 'comment']);
    $router->get('/tarifs', [PageController::class, 'tarifs']);
    $router->get('/confiance', [PageController::class, 'confiance']);
    $router->get('/a-propos', [PageController::class, 'apropos']);
    $router->get('/journal', [PageController::class, 'journal']);
    $router->get('/journal/{slug}', [PageController::class, 'article']);
    $router->get('/aide', [PageController::class, 'aide']);
    $router->get('/mentions-legales', [PageController::class, 'legal']);
    $router->get('/cgu', [PageController::class, 'legal']);
    $router->get('/cgv', [PageController::class, 'legal']);
    $router->get('/confidentialite', [PageController::class, 'legal']);
    $router->get('/cookies', [PageController::class, 'legal']);
    $router->get('/contact', [PageController::class, 'contact']);
    $router->post('/contact', [PageController::class, 'contactSubmit']);

    $router->get('/connexion', [AuthController::class, 'loginForm']);
    $router->post('/connexion', [AuthController::class, 'login']);
    $router->get('/inscription', [AuthController::class, 'registerForm']);
    $router->post('/inscription', [AuthController::class, 'register']);
    $router->post('/deconnexion', [AuthController::class, 'logout']);

    $router->get('/espace', [AccountController::class, 'dashboard']);
    $router->get('/espace/publier', [AccountController::class, 'publier']);
    $router->post('/espace/publier', [AccountController::class, 'publierSave']);
    $router->get('/espace/commande', [AccountController::class, 'commande']);
    $router->get('/espace/suivi', [AccountController::class, 'suivi']);
    $router->get('/espace/commandes', [AccountController::class, 'commandes']);
    $router->get('/espace/missions', [AccountController::class, 'missions']);
    $router->get('/espace/candidatures', [AccountController::class, 'candidatures']);
    $router->get('/espace/prestations', [AccountController::class, 'prestations']);
    $router->get('/espace/prestations/creer', [AccountController::class, 'creer']);
    $router->get('/espace/messages', [AccountController::class, 'messages']);
    $router->get('/espace/notifications', [AccountController::class, 'notifications']);
    $router->get('/espace/favoris', [AccountController::class, 'favoris']);
    $router->get('/espace/avis', [AccountController::class, 'avis']);
    $router->get('/espace/vitrine', [AccountController::class, 'vitrine']);
    $router->post('/espace/vitrine', [AccountController::class, 'vitrineSave']);
    $router->get('/espace/parametres', [AccountController::class, 'parametres']);
    $router->post('/espace/parametres', [AccountController::class, 'parametresSave']);
    $router->get('/espace/facturation', [AccountController::class, 'facturation']);

    $router->get('/admin', [AdminController::class, 'dashboard']);
    $router->get('/admin/verifications', [AdminController::class, 'verifications']);
    $router->get('/admin/moderation', [AdminController::class, 'moderation']);
    $router->get('/admin/litiges', [AdminController::class, 'litiges']);
    $router->get('/admin/avis', [AdminController::class, 'avis']);
    $router->get('/admin/utilisateurs', [AdminController::class, 'utilisateurs']);
    $router->get('/admin/prestations', [AdminController::class, 'prestations']);
    $router->get('/admin/missions', [AdminController::class, 'missions']);
    $router->get('/admin/finances', [AdminController::class, 'finances']);
    $router->get('/admin/pre-ouverture', [AdminController::class, 'preOuverture']);
    $router->get('/admin/journal', [AdminController::class, 'journal']);
    $router->get('/admin/reglages', [AdminController::class, 'reglages']);
    $router->get('/admin/smtp', [AdminController::class, 'smtp']);
    $router->post('/admin/smtp', [AdminController::class, 'smtpSave']);
    $router->post('/admin/smtp/test', [AdminController::class, 'smtpTest']);
    $router->get('/admin/emails', [AdminController::class, 'emails']);
    $router->get('/admin/emails/{id}', [AdminController::class, 'emailEdit']);
    $router->post('/admin/emails/{id}', [AdminController::class, 'emailSave']);
};
