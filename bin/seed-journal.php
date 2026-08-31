<?php

declare(strict_types=1);

define('ADL_ROOT', dirname(__DIR__));
require ADL_ROOT . '/app/bootstrap.php';

use Adl\Core\Database;

$articles = require ADL_ROOT . '/database/seeds/journal/_index.php';
$inserted = 0;
$skipped = 0;
foreach ($articles as $seed) {
    $slug = (string) ($seed['slug'] ?? '');
    if ($slug === '') {
        continue;
    }
    $exists = Database::fetch('SELECT id FROM articles WHERE slug = ?', [$slug]);
    if ($exists) {
        $skipped++;
        continue;
    }
    $when = (string) ($seed['published_at'] ?? date('Y-m-d H:i:s'));
    Database::query(
        'INSERT INTO articles (title, slug, category, excerpt, image_path, image_alt, body, published_at, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $seed['title'],
            $slug,
            $seed['category'] ?? 'Journal',
            $seed['excerpt'] ?? null,
            $seed['image_path'] ?? null,
            $seed['image_alt'] ?? null,
            $seed['body'] ?? '',
            $when,
            $when,
        ]
    );
    $inserted++;
}
echo "inserted=$inserted skipped=$skipped total=" . count($articles) . PHP_EOL;
