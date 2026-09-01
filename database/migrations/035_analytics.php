<?php

declare(strict_types=1);

return static function (\PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS stats_daily (
            day DATE NOT NULL,
            kind VARCHAR(20) NOT NULL,
            dim VARCHAR(160) NOT NULL DEFAULT \'\',
            extra VARCHAR(80) NOT NULL DEFAULT \'\',
            hits INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (day, kind, dim, extra)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS stats_minute (
            bucket DATETIME NOT NULL,
            kind VARCHAR(20) NOT NULL,
            dim VARCHAR(160) NOT NULL DEFAULT \'\',
            hits INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (bucket, kind, dim)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS stats_uniques (
            day DATE NOT NULL,
            visitor CHAR(16) NOT NULL,
            PRIMARY KEY (day, visitor)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS stats_live (
            visitor CHAR(16) NOT NULL,
            seen_at DATETIME NOT NULL,
            page VARCHAR(160) NOT NULL DEFAULT \'\',
            minute DATETIME NOT NULL,
            minute_hits TINYINT UNSIGNED NOT NULL DEFAULT 1,
            PRIMARY KEY (visitor),
            KEY idx_stats_live_seen (seen_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
};
