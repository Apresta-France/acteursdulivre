<?php

declare(strict_types=1);

namespace Adl\Core;

/** Variantes WebP redimensionnées, servies ensuite comme fichiers statiques. */
final class ImageFit
{
    /** @var list<int> */
    private const WIDTHS = [96, 256, 480, 800, 1280];

    public static function wants(string $uri): bool
    {
        return (bool) preg_match('#^/public/cache/img/w(?:' . implode('|', self::WIDTHS) . ')/#', $uri);
    }

    public static function respond(string $uri): never
    {
        $parsed = self::parseRequest($uri);
        if ($parsed === null) {
            self::fail(404);
        }
        [$width, $sourceRel] = $parsed;
        $source = self::sourceFile($sourceRel);
        if ($source === null) {
            self::fail(404);
        }

        $dest = self::destFile($width, $sourceRel);
        if (!is_file($dest) || (int) filemtime($dest) < (int) filemtime($source)) {
            if (!self::write($source, $dest, $width)) {
                self::redirectOriginal($sourceRel);
            }
        }

        $size = filesize($dest);
        header('Content-Type: image/webp');
        header('Cache-Control: public, max-age=2592000, immutable');
        header('X-Content-Type-Options: nosniff');
        if ($size !== false) {
            header('Content-Length: ' . $size);
        }
        readfile($dest);
        exit;
    }

    public static function url(string $url, int $cssWidth): string
    {
        $url = trim($url);
        if ($url === '' || str_starts_with($url, 'data:') || str_contains($url, '/public/cache/img/')) {
            return $url;
        }

        $relative = self::localRelative($url);
        if ($relative === null) {
            return $url;
        }
        $source = self::sourceFile($relative);
        if ($source === null) {
            return $url;
        }

        $bucket = self::bucket($cssWidth);
        $bytes = filesize($source);
        $info = @getimagesize($source);
        $pixelWidth = is_array($info) ? (int) $info[0] : 0;
        $webp = is_array($info) && ($info[2] ?? 0) === IMAGETYPE_WEBP;
        if ($bytes !== false && $pixelWidth > 0 && $pixelWidth <= $bucket && ($webp && $bytes < 120000 || $bytes < 40000)) {
            return $url;
        }

        $segments = explode('/', $relative . '.webp');

        return url('public/cache/img/w' . $bucket . '/' . implode('/', array_map('rawurlencode', $segments)));
    }

    public static function bucket(int $cssWidth): int
    {
        $target = min(1280, max(96, $cssWidth * 2));
        foreach (self::WIDTHS as $width) {
            if ($target <= $width) {
                return $width;
            }
        }

        return 1280;
    }

    /** @return array{0: int, 1: string}|null */
    private static function parseRequest(string $uri): ?array
    {
        $path = rawurldecode(parse_url($uri, PHP_URL_PATH) ?: $uri);
        if (preg_match('#^/public/cache/img/w(\d+)/(.+)$#', $path, $m) !== 1) {
            return null;
        }
        $width = (int) $m[1];
        $rest = str_replace('\\', '/', $m[2]);
        if (!in_array($width, self::WIDTHS, true) || !str_ends_with(strtolower($rest), '.webp')) {
            return null;
        }
        if (str_contains($rest, '..') || preg_match('/[\x00-\x1F\x7F]/', $rest) === 1) {
            return null;
        }
        $sourceRel = substr($rest, 0, -5);
        if (!self::allowedRelative($sourceRel)) {
            return null;
        }

        return [$width, $sourceRel];
    }

    private static function localRelative(string $url): ?string
    {
        if (preg_match('#^https?://#i', $url) === 1) {
            $host = strtolower((string) parse_url($url, PHP_URL_HOST));
            $appHost = strtolower((string) parse_url((string) Env::get('APP_URL', ''), PHP_URL_HOST));
            if ($host === '' || $appHost === '' || $host !== $appHost) {
                return null;
            }
            $path = (string) parse_url($url, PHP_URL_PATH);
        } elseif (str_starts_with($url, '/')) {
            $path = $url;
        } else {
            return null;
        }

        $path = rawurldecode(str_replace('\\', '/', $path));
        if (!str_starts_with($path, '/public/')) {
            return null;
        }
        $relative = substr($path, strlen('/public/'));
        if (!self::allowedRelative($relative)) {
            return null;
        }

        return $relative;
    }

    private static function allowedRelative(string $relative): bool
    {
        if ($relative === '' || str_contains($relative, '..') || str_contains($relative, "\0")) {
            return false;
        }
        $ext = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return false;
        }

        return str_starts_with($relative, 'uploads/') || str_starts_with($relative, 'assets/img/');
    }

    private static function sourceFile(string $relative): ?string
    {
        $path = ADL_ROOT . '/public/' . $relative;
        $root = realpath(ADL_ROOT . '/public');
        $real = realpath($path);
        if ($root === false || $real === false || !is_file($real)) {
            return null;
        }
        $rootNorm = rtrim(strtolower(str_replace('\\', '/', $root)), '/');
        $realNorm = strtolower(str_replace('\\', '/', $real));
        $uploads = $rootNorm . '/uploads/';
        $assets = $rootNorm . '/assets/img/';
        if (!str_starts_with($realNorm, $uploads) && !str_starts_with($realNorm, $assets)) {
            return null;
        }

        return $real;
    }

    private static function destFile(int $width, string $sourceRel): string
    {
        return ADL_ROOT . '/public/cache/img/w' . $width . '/' . $sourceRel . '.webp';
    }

    private static function write(string $source, string $dest, int $width): bool
    {
        if (!function_exists('imagewebp') || !function_exists('imagecreatetruecolor')) {
            return false;
        }
        $info = @getimagesize($source);
        if (!is_array($info)) {
            return false;
        }
        $srcW = (int) $info[0];
        $srcH = (int) $info[1];
        if ($srcW < 1 || $srcH < 1 || $srcW * $srcH > 24000000) {
            return false;
        }

        $im = match ((int) ($info[2] ?? 0)) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
            IMAGETYPE_PNG => @imagecreatefrompng($source),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : false,
            default => false,
        };
        if ($im === false) {
            return false;
        }
        if (function_exists('imagepalettetotruecolor')) {
            @imagepalettetotruecolor($im);
        }

        $dstW = min($width, $srcW);
        $dstH = max(1, (int) round($srcH * ($dstW / $srcW)));
        $out = imagecreatetruecolor($dstW, $dstH);
        if ($out === false) {
            imagedestroy($im);
            return false;
        }
        imagealphablending($out, false);
        imagesavealpha($out, true);
        $clear = imagecolorallocatealpha($out, 0, 0, 0, 127);
        if ($clear !== false) {
            imagefilledrectangle($out, 0, 0, $dstW, $dstH, $clear);
        }
        imagecopyresampled($out, $im, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);
        imagedestroy($im);

        $dir = dirname($dest);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            imagedestroy($out);
            return false;
        }
        $tmp = $dest . '.tmp';
        $ok = imagewebp($out, $tmp, 76);
        imagedestroy($out);
        if (!$ok || !is_file($tmp)) {
            @unlink($tmp);
            return false;
        }
        if (is_file($dest)) {
            @unlink($dest);
        }
        if (!@rename($tmp, $dest)) {
            @unlink($tmp);
            return false;
        }

        return true;
    }

    private static function redirectOriginal(string $sourceRel): never
    {
        header('Location: ' . url('public/' . implode('/', array_map('rawurlencode', explode('/', $sourceRel)))), true, 302);
        exit;
    }

    private static function fail(int $code): never
    {
        http_response_code($code);
        header('Content-Type: text/plain; charset=UTF-8');
        echo $code === 404 ? 'Introuvable' : 'Erreur';
        exit;
    }
}
