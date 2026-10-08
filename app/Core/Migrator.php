<?php

declare(strict_types=1);

namespace Adl\Core;

use PDO;

final class Migrator
{
    public static function migrate(?PDO $pdo = null): array
    {
        $token = self::migrationsToken();
        $stamp = ADL_ROOT . '/storage/cache/migrations.stamp';
        if (is_file($stamp) && trim((string) file_get_contents($stamp)) === $token) {
            return [];
        }

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

        self::forgetRenamed($pdo);

        $dir = dirname($stamp);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return $applied;
        }
        @file_put_contents($stamp, $token, LOCK_EX);

        return $applied;
    }

    /** Empreinte des fichiers de migration : évite une lecture SQL à chaque page. */
    private static function migrationsToken(): string
    {
        $parts = [];
        foreach (self::files() as $file) {
            $parts[] = basename($file) . ':' . (string) filemtime($file);
        }

        return sha1(implode('|', $parts));
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
        $currentSlugs = [];
        foreach (self::files() as $file) {
            $name = basename($file);
            $currentSlugs[self::slug($name)] = $name;
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
            if (isset($currentSlugs[self::slug($name)])) {
                continue;
            }
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

    /** Nom logique après le préfixe numérique : 039_coach_litteraire.php → coach_litteraire */
    private static function slug(string $name): string
    {
        $base = preg_replace('/\.php$/', '', $name) ?? $name;
        if (preg_match('/^\d+_(.+)$/', $base, $parts) === 1) {
            return $parts[1];
        }

        return $base;
    }

    /** Supprime les anciennes lignes après un renommage (039_foo → 040_foo). */
    private static function forgetRenamed(PDO $pdo): void
    {
        $current = [];
        foreach (self::files() as $file) {
            $name = basename($file);
            $current[$name] = true;
        }
        if ($current === []) {
            return;
        }

        $slugs = [];
        foreach (array_keys($current) as $name) {
            $slugs[self::slug($name)] = true;
        }

        $done = $pdo->query('SELECT name FROM migrations')->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $delete = $pdo->prepare('DELETE FROM migrations WHERE name = ?');
        foreach ($done as $name) {
            $name = (string) $name;
            if (isset($current[$name]) || !isset($slugs[self::slug($name)])) {
                continue;
            }
            $delete->execute([$name]);
        }
    }
}
