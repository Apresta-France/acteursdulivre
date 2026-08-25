<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;

final class Setting
{
    private static array $cache = [];

    public static function get(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }
        $row = Database::fetch('SELECT setting_value FROM settings WHERE setting_key = ?', [$key]);
        self::$cache[$key] = $row['setting_value'] ?? $default;
        return self::$cache[$key];
    }

    public static function set(string $key, string $value): void
    {
        Database::query(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
            [$key, $value]
        );
        self::$cache[$key] = $value;
    }

    public static function all(): array
    {
        $rows = Database::fetchAll('SELECT setting_key, setting_value FROM settings');
        $out = [];
        foreach ($rows as $row) {
            $out[$row['setting_key']] = $row['setting_value'];
        }
        return $out;
    }
}
