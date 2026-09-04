<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $article = require dirname(__DIR__) . '/seeds/journal/tribune-membres-journal.php';
    $slug = (string) ($article['slug'] ?? '');
    if ($slug === '') {
        return;
    }

    $find = $pdo->prepare('SELECT id FROM articles WHERE slug = ? LIMIT 1');
    $find->execute([$slug]);
    if ($find->fetchColumn()) {
        return;
    }

    $publishedAt = (string) ($article['published_at'] ?? date('Y-m-d H:i:s'));
    $insert = $pdo->prepare(
        'INSERT INTO articles
            (title, slug, category, excerpt, image_path, image_alt, body, published_at, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $insert->execute([
        $article['title'],
        $slug,
        $article['category'] ?? 'Plateforme',
        $article['excerpt'] ?? null,
        $article['image_path'] ?? null,
        $article['image_alt'] ?? null,
        $article['body'] ?? '',
        $publishedAt,
        $publishedAt,
    ]);
};
