<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $article = require dirname(__DIR__) . '/seeds/journal/acteurs-du-livre-ouvre-ses-portes.php';
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
    $image = trim((string) ($article['image_path'] ?? ''));
    $insert->execute([
        $article['title'],
        $slug,
        $article['category'] ?? 'Plateforme',
        $article['excerpt'] ?? null,
        $image !== '' ? $image : null,
        trim((string) ($article['image_alt'] ?? '')) ?: null,
        $article['body'] ?? '',
        $publishedAt,
        $publishedAt,
    ]);
};
