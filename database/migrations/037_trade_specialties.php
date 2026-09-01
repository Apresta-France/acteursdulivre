<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $names = \Adl\Data\Catalog::mappedSpecialtyNames();
    if ($names === []) {
        return;
    }

    $slugify = static function (string $text): string {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        $slug = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $ascii));
        return trim($slug, '-') ?: 'terme';
    };

    $existing = $pdo->query(
        "SELECT name, slug, position FROM taxonomy_terms WHERE kind = 'specialty'"
    )->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $byName = [];
    $usedSlugs = [];
    $maxPosition = -1;
    foreach ($existing as $row) {
        $byName[(string) $row['name']] = true;
        $usedSlugs[(string) $row['slug']] = true;
        $maxPosition = max($maxPosition, (int) $row['position']);
    }

    $insert = $pdo->prepare(
        'INSERT INTO taxonomy_terms (kind, name, slug, position, enabled, is_global)
         VALUES (\'specialty\', ?, ?, ?, 1, 0)'
    );

    foreach ($names as $name) {
        if (isset($byName[$name])) {
            continue;
        }
        $slug = $slugify($name);
        $base = $slug;
        $n = 2;
        while (isset($usedSlugs[$slug])) {
            $slug = $base . '-' . $n++;
        }
        $usedSlugs[$slug] = true;
        $insert->execute([$name, $slug, ++$maxPosition]);
    }
};
