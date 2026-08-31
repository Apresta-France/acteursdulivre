<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $dates = require dirname(__DIR__) . '/seeds/journal_dates.php';
    $stmt = $pdo->prepare(
        'UPDATE articles SET published_at = ?, created_at = ? WHERE slug = ?'
    );
    foreach ($dates as $slug => $when) {
        $stmt->execute([$when, $when, $slug]);
    }
};
