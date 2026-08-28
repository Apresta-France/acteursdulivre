<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS taxonomy_terms (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            kind ENUM("trade", "specialty") NOT NULL,
            name VARCHAR(120) NOT NULL,
            slug VARCHAR(160) NOT NULL,
            position INT NOT NULL DEFAULT 0,
            enabled TINYINT(1) NOT NULL DEFAULT 1,
            is_global TINYINT(1) NOT NULL DEFAULT 0,
            UNIQUE KEY uniq_kind_slug (kind, slug),
            UNIQUE KEY uniq_kind_name (kind, name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $count = (int) $pdo->query('SELECT COUNT(*) FROM taxonomy_terms')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $slugify = static function (string $text): string {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        $slug = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $ascii));
        return trim($slug, '-') ?: 'terme';
    };

    $stmt = $pdo->prepare(
        'INSERT INTO taxonomy_terms (kind, name, slug, position, enabled, is_global)
         VALUES (?, ?, ?, ?, 1, ?)'
    );

    $trades = [
        'Écriture', 'Correction', 'Bêta-lecture', 'Illustration', 'Traduction', 'Maquette',
        'Édition', 'Impression', 'Presse & com', 'Librairie', 'Audio',
        'Agent littéraire', 'Salons',
    ];
    foreach ($trades as $i => $name) {
        $stmt->execute(['trade', $name, $slugify($name), $i, 0]);
    }

    $specialties = [
        ['Global', 1],
        ['Roman', 0],
        ['Polar', 0],
        ['Essai', 0],
        ['Jeunesse', 0],
        ['BD & graphique', 0],
        ['Poésie', 0],
        ['Théâtre', 0],
        ['Sciences humaines', 0],
        ['Pratique', 0],
        ['Livre audio', 0],
    ];
    foreach ($specialties as $i => [$name, $global]) {
        $stmt->execute(['specialty', $name, $slugify($name), $i, $global]);
    }
};
