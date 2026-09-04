<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $stmt = $pdo->prepare(
        'UPDATE articles
         SET image_path = ?, image_alt = ?
         WHERE slug = ?'
    );
    $stmt->execute([
        'img/journal/tribune-membres-journal.jpg',
        'Une personne rédige un article dans un carnet, entourée de pages relues et d’un ordinateur affichant une mise en page.',
        'tribune-membres-journal',
    ]);
};
