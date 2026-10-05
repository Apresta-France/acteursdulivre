<?php

declare(strict_types=1);

return static function (\PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS stats_landing_visits (
            id CHAR(16) NOT NULL,
            landing VARCHAR(80) NOT NULL,
            started_at DATETIME NOT NULL,
            device VARCHAR(16) NOT NULL DEFAULT \'\',
            source VARCHAR(40) NOT NULL DEFAULT \'\',
            steps TINYINT UNSIGNED NOT NULL DEFAULT 0,
            interacted TINYINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_lp_visit_window (landing, started_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS stats_landing_events (
            visit CHAR(16) NOT NULL,
            seq TINYINT UNSIGNED NOT NULL,
            at DATETIME NOT NULL,
            kind VARCHAR(12) NOT NULL,
            label VARCHAR(120) NOT NULL DEFAULT \'\',
            target VARCHAR(160) NOT NULL DEFAULT \'\',
            PRIMARY KEY (visit, seq),
            KEY idx_lp_event_at (at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
};
