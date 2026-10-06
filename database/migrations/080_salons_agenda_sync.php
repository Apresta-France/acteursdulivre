<?php

declare(strict_types=1);

/**
 * Les fiches annoncées dans l'article d'agenda sont dans salons.json,
 * mais la synchronisation précédente a déjà été jouée. On la rejoue
 * pour créer les adresses /salons/… qui répondent encore 404.
 */
return require __DIR__ . '/074_salons_agenda.php';
