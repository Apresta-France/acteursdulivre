<?php

declare(strict_types=1);

/** @return list<array<string, mixed>> */
$articles = [];

$first = require dirname(__DIR__) . '/journal_cout_fabrication.php';
$articles[] = $first;

$files = glob(__DIR__ . '/*.php') ?: [];
sort($files);
foreach ($files as $file) {
    if (basename($file) === '_index.php') {
        continue;
    }
    $row = require $file;
    if (!is_array($row) || empty($row['slug'])) {
        continue;
    }
    $articles[] = $row;
}

$dates = require dirname(__DIR__) . '/journal_dates.php';
foreach ($articles as &$article) {
    $slug = (string) ($article['slug'] ?? '');
    if ($slug !== '' && isset($dates[$slug])) {
        $article['published_at'] = $dates[$slug];
    }
}
unset($article);

return $articles;
