<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $stmt = $pdo->prepare(
        'INSERT INTO email_templates (slug, name, subject, body_html, variables)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE slug = VALUES(slug)'
    );
    $stmt->execute([
        'tribune-a-moderer',
        'Tribune à modérer — administrateurs',
        'Une nouvelle tribune attend votre validation',
        '<p>Bonjour {{ prenom }},</p><p><strong>{{ auteur }}</strong> vient de soumettre la tribune « <strong>{{ titre }}</strong> » au journal.</p><p>Elle n’est pas encore publique. Vous pouvez la relire, la valider ou indiquer un motif de refus depuis l’administration.</p><p><a href="{{ lien_moderation }}">Ouvrir la file de modération</a></p>',
        'prenom, auteur, titre, lien_moderation',
    ]);
};
