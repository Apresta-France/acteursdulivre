<?php

declare(strict_types=1);

return static function (\PDO $pdo): void {
    $pdo->exec(
        'ALTER TABLE users
            ADD COLUMN seeks_services TINYINT(1) NOT NULL DEFAULT 0 AFTER role,
            ADD COLUMN offers_services TINYINT(1) NOT NULL DEFAULT 0 AFTER seeks_services'
    );

    $pdo->exec('UPDATE users SET seeks_services = 1 WHERE role IN ("client", "admin")');
    $pdo->exec('UPDATE users SET offers_services = 1 WHERE role IN ("prestataire", "admin")');

    $stmt = $pdo->prepare(
        'UPDATE email_templates
         SET body_html = ?, updated_at = NOW()
         WHERE slug = ?'
    );
    $stmt->execute([
        '<p>Bonjour {{ prenom }},</p><p>Votre compte est prêt. Selon votre choix, vous pouvez chercher des prestataires, proposer vos services, ou les deux.</p><p><a href="{{ lien_espace }}">Ouvrir mon espace</a></p>',
        'bienvenue',
    ]);
};
