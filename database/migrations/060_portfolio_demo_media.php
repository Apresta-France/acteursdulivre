<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $portfolioCols = $pdo->query('SHOW COLUMNS FROM portfolio_items')->fetchAll(PDO::FETCH_COLUMN) ?: [];
    if (!in_array('media_type', $portfolioCols, true)) {
        $pdo->exec(
            'ALTER TABLE portfolio_items
                ADD COLUMN media_type VARCHAR(20) NOT NULL DEFAULT "image" AFTER kind'
        );
    }
    if (!in_array('text_excerpt', $portfolioCols, true)) {
        $pdo->exec(
            'ALTER TABLE portfolio_items
                ADD COLUMN text_excerpt TEXT NULL AFTER description'
        );
    }

    $workCols = $pdo->query('SHOW COLUMNS FROM author_works')->fetchAll(PDO::FETCH_COLUMN) ?: [];
    if (!in_array('excerpt_pdf_path', $workCols, true)) {
        $pdo->exec(
            'ALTER TABLE author_works
                ADD COLUMN excerpt_pdf_path VARCHAR(255) NULL AFTER excerpt'
        );
    }
    if (!in_array('excerpt_audio_path', $workCols, true)) {
        $pdo->exec(
            'ALTER TABLE author_works
                ADD COLUMN excerpt_audio_path VARCHAR(255) NULL AFTER excerpt_pdf_path'
        );
    }
};
