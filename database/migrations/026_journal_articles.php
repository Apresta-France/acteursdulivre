<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $articles = require dirname(__DIR__) . '/seeds/journal/_index.php';
    $find = $pdo->prepare('SELECT id FROM articles WHERE slug = ? LIMIT 1');
    $insert = $pdo->prepare(
        'INSERT INTO articles (title, slug, category, excerpt, image_path, image_alt, body, published_at, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
    );

    foreach ($articles as $seed) {
        $slug = (string) ($seed['slug'] ?? '');
        if ($slug === '') {
            continue;
        }
        $find->execute([$slug]);
        if ($find->fetchColumn()) {
            continue;
        }
        $insert->execute([
            $seed['title'],
            $slug,
            $seed['category'] ?? 'Journal',
            $seed['excerpt'] ?? null,
            $seed['image_path'] ?? null,
            $seed['image_alt'] ?? null,
            $seed['body'] ?? '',
        ]);
    }
};
