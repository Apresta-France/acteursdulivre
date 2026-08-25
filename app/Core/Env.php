<?php

declare(strict_types=1);

namespace Adl\Core;

final class Env
{
    private static array $values = [];
    private static bool $loaded = false;

    public static function load(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"'))
                || (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }
            self::$values[$key] = $value;
            $_ENV[$key] = $value;
        }
        self::$loaded = true;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, self::$values)) {
            return self::$values[$key];
        }
        $fromEnv = $_ENV[$key] ?? getenv($key);
        if ($fromEnv !== false && $fromEnv !== null) {
            return (string) $fromEnv;
        }
        return $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = strtolower((string) self::get($key, $default ? '1' : '0'));
        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    public static function loaded(): bool
    {
        return self::$loaded;
    }

    public static function write(string $path, array $values): void
    {
        $lines = [
            '# Acteurs du Livre — généré le ' . date('Y-m-d H:i:s'),
            '',
        ];
        foreach ($values as $key => $value) {
            $value = (string) $value;
            if ($value !== '' && preg_match('/[\s#"\']/', $value)) {
                $value = '"' . str_replace(['\\', '"'], ['\\\\', '\"'], $value) . '"';
            }
            $lines[] = $key . '=' . $value;
        }
        $tmp = $path . '.tmp';
        file_put_contents($tmp, implode("\n", $lines) . "\n");
        rename($tmp, $path);
        self::load($path);
    }
}
