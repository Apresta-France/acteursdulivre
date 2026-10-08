<?php

declare(strict_types=1);

namespace Adl\Core;

/** Cache fichier court, pour des données publiques identiques d'une visite à l'autre. */
final class FileCache
{
    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        $path = self::path($key);
        if (is_file($path)) {
            $raw = @unserialize((string) file_get_contents($path));
            if (is_array($raw) && (int) ($raw['exp'] ?? 0) >= time() && array_key_exists('v', $raw)) {
                return $raw['v'];
            }
        }

        $value = $callback();
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return $value;
        }
        @file_put_contents($path, serialize(['exp' => time() + max(1, $ttl), 'v' => $value]), LOCK_EX);

        return $value;
    }

    private static function path(string $key): string
    {
        $safe = preg_replace('/[^a-z0-9._-]+/i', '-', $key) ?? 'cache';

        return ADL_ROOT . '/storage/cache/' . $safe . '.cache';
    }
}
