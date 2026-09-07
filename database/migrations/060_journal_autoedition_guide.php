<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $articles = require dirname(__DIR__) . '/seeds/journal/_index.php';
    $bySlug = [];
    foreach ($articles as $seed) {
        $slug = (string) ($seed['slug'] ?? '');
        if ($slug !== '') {
            $bySlug[$slug] = $seed;
        }
    }

    $guideSlug = 'autoedition-guide-complet';
    $guide = $bySlug[$guideSlug] ?? null;
    if ($guide !== null) {
        $find = $pdo->prepare('SELECT id FROM articles WHERE slug = ? LIMIT 1');
        $find->execute([$guideSlug]);
        if (!$find->fetchColumn()) {
            $when = (string) ($guide['published_at'] ?? date('Y-m-d H:i:s'));
            $insert = $pdo->prepare(
                'INSERT INTO articles (title, slug, category, excerpt, image_path, image_alt, body, published_at, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $insert->execute([
                $guide['title'],
                $guideSlug,
                $guide['category'] ?? 'Édition',
                $guide['excerpt'] ?? null,
                $guide['image_path'] ?? null,
                $guide['image_alt'] ?? null,
                $guide['body'] ?? '',
                $when,
                $when,
            ]);
        }
    }

    $backlinks = [
        'cout-fabrication-roman-autoedition',
        'autoedition-droits-auteur',
        'isbn-depot-legal-afnil-france',
        'livre-autoedite-en-librairie',
        'impression-pod-numerique-offset',
        'cout-reel-ebook',
    ];
    $update = $pdo->prepare('UPDATE articles SET body = ? WHERE slug = ?');
    foreach ($backlinks as $slug) {
        $seed = $bySlug[$slug] ?? null;
        if ($seed === null || empty($seed['body'])) {
            continue;
        }
        $update->execute([(string) $seed['body'], $slug]);
    }
};
