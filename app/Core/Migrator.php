<?php

declare(strict_types=1);

namespace Adl\Core;

use PDO;

final class Migrator
{
    public static function migrate(?PDO $pdo = null): array
    {
        $pdo ??= Database::pdo();
        self::ensureTable($pdo);

        $done = $pdo->query('SELECT name FROM migrations')->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $applied = [];
        foreach (self::files() as $file) {
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

    /**
     * @return array{
     *   items: list<array{name: string, status: string, applied_at: ?string}>,
     *   total: int,
     *   applied: int,
     *   pending: int,
     *   missing: int,
     *   up_to_date: bool
     * }
     */
    public static function status(?PDO $pdo = null): array
    {
        $pdo ??= Database::pdo();
        self::ensureTable($pdo);

        $rows = $pdo->query('SELECT name, applied_at FROM migrations ORDER BY name')->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $appliedMap = [];
        foreach ($rows as $row) {
            $appliedMap[(string) $row['name']] = (string) ($row['applied_at'] ?? '');
        }

        $items = [];
        $applied = 0;
        $pending = 0;
        foreach (self::files() as $file) {
            $name = basename($file);
            $at = $appliedMap[$name] ?? null;
            unset($appliedMap[$name]);
            if ($at !== null && $at !== '') {
                $applied++;
                $items[] = ['name' => $name, 'status' => 'applied', 'applied_at' => $at];
            } else {
                $pending++;
                $items[] = ['name' => $name, 'status' => 'pending', 'applied_at' => null];
            }
        }

        $missing = 0;
        foreach ($appliedMap as $name => $at) {
            $missing++;
            $items[] = ['name' => $name, 'status' => 'missing', 'applied_at' => $at !== '' ? $at : null];
        }

        return [
            'items' => $items,
            'total' => $applied + $pending,
            'applied' => $applied,
            'pending' => $pending,
            'missing' => $missing,
            'up_to_date' => $pending === 0,
        ];
    }

    public static function pendingCount(?PDO $pdo = null): int
    {
        return self::status($pdo)['pending'];
    }

    private static function ensureTable(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(191) NOT NULL UNIQUE,
                applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    /** @return list<string> */
    private static function files(): array
    {
        $files = glob(ADL_ROOT . '/database/migrations/*.php') ?: [];
        sort($files);

        return $files;
    }
}
