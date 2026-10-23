<?php

declare(strict_types=1);

use Adl\Data\InternationalImport;

/**
 * Corrige l’affichage hors Europe et importe les maisons et salons internationaux.
 * Idempotent : les doublons (nom + pays, ou slug salon) sont ignorés / mis à jour.
 */
return static function (PDO $pdo): void {
    InternationalImport::apply($pdo);
};
