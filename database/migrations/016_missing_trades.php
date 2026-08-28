<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $official = [
        'Écriture', 'Correction', 'Bêta-lecture', 'Illustration', 'Traduction', 'Maquette',
        'Édition', 'Impression', 'Presse & com', 'Librairie', 'Audio',
        'Agent littéraire', 'Salons',
    ];

    $slugify = static function (string $text): string {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        $slug = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $ascii));
        return trim($slug, '-') ?: 'terme';
    };

    $existing = $pdo->query(
        "SELECT id, name, slug, position FROM taxonomy_terms WHERE kind = 'trade' ORDER BY position ASC, id ASC"
    )->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $byName = [];
    foreach ($existing as $row) {
        $byName[(string) $row['name']] = $row;
    }

    $insert = $pdo->prepare(
        'INSERT INTO taxonomy_terms (kind, name, slug, position, enabled, is_global)
         VALUES (\'trade\', ?, ?, ?, 1, 0)'
    );
    $usedSlugs = [];
    foreach ($existing as $row) {
        $usedSlugs[(string) $row['slug']] = true;
    }

    foreach ($official as $i => $name) {
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
        $insert->execute([$name, $slug, 1000 + $i]);
    }

    $rows = $pdo->query(
        "SELECT id, name FROM taxonomy_terms WHERE kind = 'trade' ORDER BY position ASC, id ASC"
    )->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $order = array_flip($official);
    usort($rows, static function (array $a, array $b) use ($order): int {
        $ia = $order[$a['name']] ?? 1000;
        $ib = $order[$b['name']] ?? 1000;
        return $ia <=> $ib ?: ((int) $a['id'] <=> (int) $b['id']);
    });

    $update = $pdo->prepare('UPDATE taxonomy_terms SET position = ? WHERE id = ?');
    foreach ($rows as $i => $row) {
        $update->execute([$i, (int) $row['id']]);
    }
};
