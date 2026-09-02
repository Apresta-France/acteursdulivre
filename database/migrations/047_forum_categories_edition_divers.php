<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $categories = [
        ['Édition', 'edition', 'Maisons, collections, manuscrits, relation auteur-éditeur.', 9],
        ['Divers', 'divers', 'Sujets transverses, questions hors cadre, discussions ouvertes.', 10],
    ];

    $find = $pdo->prepare('SELECT id FROM forum_categories WHERE slug = ? LIMIT 1');
    $insert = $pdo->prepare(
        'INSERT INTO forum_categories (name, slug, description, position, created_at)
         VALUES (?, ?, ?, ?, NOW())'
    );
    foreach ($categories as [$name, $slug, $desc, $pos]) {
        $find->execute([$slug]);
        if ($find->fetchColumn()) {
            continue;
        }
        $insert->execute([$name, $slug, $desc, $pos]);
    }
};
