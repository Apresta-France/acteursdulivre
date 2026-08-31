<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $cols = $pdo->query('SHOW COLUMNS FROM articles')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('image_path', $cols, true)) {
        $pdo->exec('ALTER TABLE articles ADD COLUMN image_path VARCHAR(255) NULL AFTER excerpt');
    }
    if (!in_array('image_alt', $cols, true)) {
        $pdo->exec('ALTER TABLE articles ADD COLUMN image_alt VARCHAR(255) NULL AFTER image_path');
    }

    $exists = $pdo->query(
        "SELECT id FROM articles WHERE slug = 'cout-fabrication-roman-autoedition' LIMIT 1"
    )->fetchColumn();
    if ($exists) {
        return;
    }

    $seed = require dirname(__DIR__) . '/seeds/journal_cout_fabrication.php';
    $stmt = $pdo->prepare(
        'INSERT INTO articles (title, slug, category, excerpt, image_path, image_alt, body, published_at, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
    );
    $stmt->execute([
        $seed['title'],
        $seed['slug'],
        $seed['category'],
        $seed['excerpt'],
        $seed['image_path'],
        $seed['image_alt'],
        $seed['body'],
    ]);
};
