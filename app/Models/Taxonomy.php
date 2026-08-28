<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;
use RuntimeException;
use Throwable;

final class Taxonomy
{
    public const KIND_TRADE = 'trade';
    public const KIND_SPECIALTY = 'specialty';
    public const GLOBAL_NAME = 'Global';

    /** @var array<string, list<array<string, mixed>>> */
    private static array $cache = [];

    /** @return list<string> */
    public static function defaults(string $kind): array
    {
        if ($kind === self::KIND_SPECIALTY) {
            return array_values(array_unique(array_merge([self::GLOBAL_NAME], Profile::GENRES)));
        }
        return Profile::TRADES;
    }

    /** @return list<array<string, mixed>> */
    public static function list(string $kind, bool $enabledOnly = false): array
    {
        $key = $kind . ':' . ($enabledOnly ? 'on' : 'all');
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $sql = 'SELECT * FROM taxonomy_terms WHERE kind = ?';
        $params = [$kind];
        if ($enabledOnly) {
            $sql .= ' AND enabled = 1';
        }
        $sql .= ' ORDER BY position ASC, id ASC';

        $rows = array_map([self::class, 'hydrate'], Database::fetchAll($sql, $params));
        self::$cache[$key] = $rows;
        return $rows;
    }

    /** @return list<string> */
    public static function names(string $kind, bool $enabledOnly = true): array
    {
        try {
            $rows = self::list($kind, false);
            if ($rows !== []) {
                if ($enabledOnly) {
                    $rows = array_values(array_filter($rows, static fn (array $row): bool => !empty($row['enabled'])));
                }
                return array_column($rows, 'name');
            }
        } catch (Throwable) {
        }
        return self::defaults($kind);
    }

    public static function find(int $id): ?array
    {
        $row = Database::fetch('SELECT * FROM taxonomy_terms WHERE id = ?', [$id]);
        return $row ? self::hydrate($row) : null;
    }

    public static function create(string $kind, string $name, bool $isGlobal = false): array
    {
        $kind = self::normalizeKind($kind);
        $name = self::normalizeName($name);
        if ($name === '') {
            throw new RuntimeException('Indiquez un libellé.');
        }
        if (self::exists($kind, $name)) {
            throw new RuntimeException('Ce terme existe déjà dans la liste.');
        }

        $position = (int) (Database::fetch(
            'SELECT COALESCE(MAX(position), -1) + 1 AS n FROM taxonomy_terms WHERE kind = ?',
            [$kind]
        )['n'] ?? 0);

        if ($isGlobal) {
            Database::query('UPDATE taxonomy_terms SET is_global = 0 WHERE kind = ?', [$kind]);
        }

        Database::query(
            'INSERT INTO taxonomy_terms (kind, name, slug, position, enabled, is_global) VALUES (?, ?, ?, ?, 1, ?)',
            [$kind, $name, self::uniqueSlug($kind, $name), $position, $isGlobal ? 1 : 0]
        );

        self::flush();
        return self::find((int) Database::lastId()) ?? [];
    }

    public static function update(int $id, string $name, bool $enabled, bool $isGlobal = false): void
    {
        $term = self::find($id);
        if (!$term) {
            throw new RuntimeException('Terme introuvable.');
        }

        $name = self::normalizeName($name);
        if ($name === '') {
            throw new RuntimeException('Indiquez un libellé.');
        }
        if (self::exists((string) $term['kind'], $name, $id)) {
            throw new RuntimeException('Ce terme existe déjà dans la liste.');
        }

        $oldName = (string) $term['name'];
        if ($isGlobal) {
            Database::query(
                'UPDATE taxonomy_terms SET is_global = 0 WHERE kind = ? AND id != ?',
                [$term['kind'], $id]
            );
        }

        Database::query(
            'UPDATE taxonomy_terms SET name = ?, slug = ?, enabled = ?, is_global = ? WHERE id = ?',
            [$name, self::uniqueSlug((string) $term['kind'], $name, $id), $enabled ? 1 : 0, $isGlobal ? 1 : 0, $id]
        );

        if ($oldName !== $name) {
            self::renameUsages((string) $term['kind'], $oldName, $name);
        }

        self::flush();
    }

    public static function delete(int $id): void
    {
        $term = self::find($id);
        if (!$term) {
            throw new RuntimeException('Terme introuvable.');
        }
        if (self::usageCount($term) > 0) {
            throw new RuntimeException('Ce terme est encore utilisé. Masquez-le plutôt que de le supprimer.');
        }

        Database::query('DELETE FROM taxonomy_terms WHERE id = ?', [$id]);
        self::flush();
        self::reindex((string) $term['kind']);
        self::flush();
    }

    public static function move(int $id, string $direction): void
    {
        $term = self::find($id);
        if (!$term) {
            throw new RuntimeException('Terme introuvable.');
        }

        $list = self::list((string) $term['kind'], false);
        $index = null;
        foreach ($list as $i => $row) {
            if ((int) $row['id'] === $id) {
                $index = $i;
                break;
            }
        }
        if ($index === null) {
            return;
        }

        $swap = $direction === 'up' ? $index - 1 : $index + 1;
        if (!isset($list[$swap])) {
            return;
        }

        $a = $list[$index];
        $b = $list[$swap];
        Database::query('UPDATE taxonomy_terms SET position = ? WHERE id = ?', [(int) $b['position'], (int) $a['id']]);
        Database::query('UPDATE taxonomy_terms SET position = ? WHERE id = ?', [(int) $a['position'], (int) $b['id']]);
        self::flush();
    }

    /** @param array<string, mixed> $term */
    public static function usageCount(array $term): int
    {
        $name = (string) ($term['name'] ?? '');
        if ($name === '') {
            return 0;
        }

        try {
            if (($term['kind'] ?? '') === self::KIND_TRADE) {
                $services = (int) (Database::fetch('SELECT COUNT(*) AS n FROM services WHERE category_name = ?', [$name])['n'] ?? 0);
                $missions = (int) (Database::fetch('SELECT COUNT(*) AS n FROM missions WHERE category_name = ?', [$name])['n'] ?? 0);
                $profiles = (int) (Database::fetch(
                    'SELECT COUNT(*) AS n FROM profiles WHERE JSON_CONTAINS(trades_json, JSON_QUOTE(?))',
                    [$name]
                )['n'] ?? 0);
                return $services + $missions + $profiles;
            }

            $services = (int) (Database::fetch('SELECT COUNT(*) AS n FROM services WHERE specialty = ?', [$name])['n'] ?? 0);
            $profiles = (int) (Database::fetch(
                'SELECT COUNT(*) AS n FROM profiles WHERE JSON_CONTAINS(genres_json, JSON_QUOTE(?))',
                [$name]
            )['n'] ?? 0);
            return $services + $profiles;
        } catch (Throwable) {
            return 0;
        }
    }

    private static function renameUsages(string $kind, string $old, string $new): void
    {
        if ($old === $new) {
            return;
        }

        try {
            if ($kind === self::KIND_TRADE) {
                Database::query('UPDATE services SET category_name = ? WHERE category_name = ?', [$new, $old]);
                Database::query('UPDATE missions SET category_name = ? WHERE category_name = ?', [$new, $old]);
                self::renameJson('profiles', 'trades_json', $old, $new);
                return;
            }
            Database::query('UPDATE services SET specialty = ? WHERE specialty = ?', [$new, $old]);
            self::renameJson('profiles', 'genres_json', $old, $new);
        } catch (Throwable) {
        }
    }

    private static function renameJson(string $table, string $column, string $old, string $new): void
    {
        $rows = Database::fetchAll('SELECT id, ' . $column . ' AS payload FROM ' . $table);
        foreach ($rows as $row) {
            $data = json_decode((string) ($row['payload'] ?? ''), true);
            if (!is_array($data)) {
                continue;
            }
            $changed = false;
            foreach ($data as $i => $value) {
                if ($value === $old) {
                    $data[$i] = $new;
                    $changed = true;
                }
            }
            if ($changed) {
                Database::query(
                    'UPDATE ' . $table . ' SET ' . $column . ' = ? WHERE id = ?',
                    [json_encode(array_values($data), JSON_UNESCAPED_UNICODE) ?: '[]', (int) $row['id']]
                );
            }
        }
    }

    private static function exists(string $kind, string $name, ?int $exceptId = null): bool
    {
        $sql = 'SELECT id FROM taxonomy_terms WHERE kind = ? AND name = ?';
        $params = [$kind, $name];
        if ($exceptId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $exceptId;
        }
        return Database::fetch($sql, $params) !== null;
    }

    private static function uniqueSlug(string $kind, string $name, ?int $exceptId = null): string
    {
        $base = slugify($name) ?: 'terme';
        $slug = $base;
        $i = 2;
        while (true) {
            $sql = 'SELECT id FROM taxonomy_terms WHERE kind = ? AND slug = ?';
            $params = [$kind, $slug];
            if ($exceptId !== null) {
                $sql .= ' AND id != ?';
                $params[] = $exceptId;
            }
            if (Database::fetch($sql, $params) === null) {
                return $slug;
            }
            $slug = $base . '-' . $i++;
        }
    }

    private static function reindex(string $kind): void
    {
        foreach (self::list($kind, false) as $i => $row) {
            Database::query('UPDATE taxonomy_terms SET position = ? WHERE id = ?', [$i, (int) $row['id']]);
        }
    }

    private static function normalizeKind(string $kind): string
    {
        return $kind === self::KIND_SPECIALTY ? self::KIND_SPECIALTY : self::KIND_TRADE;
    }

    private static function normalizeName(string $name): string
    {
        return trim(preg_replace('/\s+/', ' ', $name) ?? $name);
    }

    /** @param array<string, mixed> $row */
    private static function hydrate(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['position'] = (int) $row['position'];
        $row['enabled'] = (int) ($row['enabled'] ?? 1) === 1;
        $row['is_global'] = (int) ($row['is_global'] ?? 0) === 1;
        return $row;
    }

    private static function flush(): void
    {
        self::$cache = [];
    }
}
