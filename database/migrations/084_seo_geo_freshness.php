<?php

declare(strict_types=1);

use Adl\Data\FreshnessCopy;

/**
 * Textes de pré-ouverture devenus faux, fiches Leya « indépendant » et « grand groupe »,
 * trois sites de salons en 404.
 */
return static function (PDO $pdo): void {
    $articles = $pdo->query('SELECT id, body FROM articles')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $updateArticle = $pdo->prepare('UPDATE articles SET body = ? WHERE id = ?');
    foreach ($articles as $article) {
        $body = FreshnessCopy::replace((string) ($article['body'] ?? ''));
        if ($body !== (string) ($article['body'] ?? '')) {
            $updateArticle->execute([$body, (int) $article['id']]);
        }
    }

    $categories = $pdo->query('SELECT id, description FROM forum_categories')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $updateCategory = $pdo->prepare('UPDATE forum_categories SET description = ? WHERE id = ?');
    foreach ($categories as $category) {
        $description = FreshnessCopy::replace((string) ($category['description'] ?? ''));
        if ($description !== (string) ($category['description'] ?? '')) {
            $updateCategory->execute([$description, (int) $category['id']]);
        }
    }

    $groups = $pdo->query(
        'SELECT id, parent_group FROM publishers WHERE size_key = "grand-groupe" AND independent = 1'
    )->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $updateGroup = $pdo->prepare('UPDATE publishers SET independent = 0, parent_group = ? WHERE id = ?');
    foreach ($groups as $row) {
        $group = trim((string) ($row['parent_group'] ?? ''));
        if (preg_match('/\(([^)]+)\)/u', $group, $match) === 1) {
            $group = trim($match[1]);
        }
        $updateGroup->execute([$group, (int) $row['id']]);
    }

    $broken = [
        'https://festidlirejeunes.wixsite.com',
        'https://una-volta.com/evenements',
        'https://www.angouleme.fr/actualites/organisation-dun-nouveau-festival-de-la-bande-dessinee-a-angouleme/',
    ];
    $clearSite = $pdo->prepare('UPDATE salons SET website = "" WHERE website = ?');
    foreach ($broken as $url) {
        $clearSite->execute([$url]);
    }

    $avatarDir = ADL_ROOT . '/public/uploads/avatars';
    if (is_dir($avatarDir)) {
        $avatars = [];
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            $avatars = array_merge($avatars, glob($avatarDir . '/*.' . $ext) ?: []);
        }
        foreach ($avatars as $avatar) {
            if (is_file($avatar)) {
                \Adl\Core\ImageFit::compressInPlace($avatar);
            }
        }
    }
};
