<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $articles = require dirname(__DIR__) . '/seeds/journal/_index.php';
    $slugs = [
        'forum-metiers-du-livre',
        'section-auteur-fiche-publique',
    ];
    $bySlug = [];
    foreach ($articles as $seed) {
        $slug = (string) ($seed['slug'] ?? '');
        if ($slug !== '' && in_array($slug, $slugs, true)) {
            $bySlug[$slug] = $seed;
        }
    }

    $find = $pdo->prepare('SELECT id FROM articles WHERE slug = ? LIMIT 1');
    $insert = $pdo->prepare(
        'INSERT INTO articles (title, slug, category, excerpt, image_path, image_alt, body, published_at, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    foreach ($slugs as $slug) {
        $seed = $bySlug[$slug] ?? null;
        if ($seed === null) {
            continue;
        }
        $find->execute([$slug]);
        if ($find->fetchColumn()) {
            continue;
        }
        $when = (string) ($seed['published_at'] ?? date('Y-m-d H:i:s'));
        $insert->execute([
            $seed['title'],
            $slug,
            $seed['category'] ?? 'Plateforme',
            $seed['excerpt'] ?? null,
            $seed['image_path'] ?? null,
            $seed['image_alt'] ?? null,
            $seed['body'] ?? '',
            $when,
            $when,
        ]);
    }
};
