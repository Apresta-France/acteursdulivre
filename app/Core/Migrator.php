<?php

declare(strict_types=1);

namespace Adl\Core;

use PDO;

final class Migrator
{
    public static function migrate(?PDO $pdo = null): array
    {
        $pdo ??= Database::pdo();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(191) NOT NULL UNIQUE,
                applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $done = $pdo->query('SELECT name FROM migrations')->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $dir = ADL_ROOT . '/database/migrations';
        $files = glob($dir . '/*.php') ?: [];
        sort($files);

        $applied = [];
        foreach ($files as $file) {
            $name = basename($file);
            if (in_array($name, $done, true)) {
                continue;
            }
            $migration = require $file;
            if (is_callable($migration)) {
                $migration($pdo);
            } elseif (is_array($migration) && isset($migration['up'])) {
                $migration['up']($pdo);
            }
            $stmt = $pdo->prepare('INSERT INTO migrations (name) VALUES (?)');
            $stmt->execute([$name]);
            $applied[] = $name;
        }

        return $applied;
    }
}
