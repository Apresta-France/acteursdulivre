<?php

declare(strict_types=1);

use Adl\Core\Auth;
use Adl\Core\Csrf;
use Adl\Core\Env;

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = '/'): string
{
    $base = rtrim(Env::get('APP_URL', ''), '/');
    $path = '/' . ltrim($path, '/');
    if ($path === '//') {
        $path = '/';
    }
    return ($base !== '' ? $base : '') . ($path === '/' ? '/' : rtrim($path, '/'));
}

function asset(string $path): string
{
    return url('public/assets/' . ltrim($path, '/'));
}

function old(string $key, mixed $default = ''): mixed
{
    $flashed = $_SESSION['_old'][$key] ?? null;
    return $flashed ?? ($_POST[$key] ?? $default);
}

function flash(string $key, mixed $value = null): mixed
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return $value;
    }
    $out = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $out;
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

function auth_user(): ?array
{
    return Auth::user();
}

function avatar_style(string $initials, int $size = 34): string
{
    $bg = (ord($initials[0] ?? 'A') * 7) % 2 === 0 ? '#022746' : '#D85D3F';
    $font = $size >= 38 ? 13 : ($size >= 28 ? 11 : 10);
    return "width:{$size}px;height:{$size}px;min-width:{$size}px;border-radius:50%;background:{$bg};color:#FFF;display:flex;align-items:center;justify-content:center;font-family:'Space Grotesk',monospace;font-size:{$font}px;";
}

function photo(int $index = 0): string
{
    $photos = [
        'Old books (Unsplash).jpg',
        'Watercolor Flowers (Unsplash).jpg',
        'Leather bound books (Unsplash).jpg',
        'Mixing console (Unsplash).jpg',
        'Books, pencils, laptop, and iphone on a desk (Unsplash).jpg',
        'Library Books Bookshelves (Unsplash).jpg',
    ];
    $name = $photos[$index] ?? $photos[0];
    return 'https://commons.wikimedia.org/wiki/Special:FilePath/' . rawurlencode($name) . '?width=1200';
}

function redirect(string $path, int $code = 302): never
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)), true, $code);
    exit;
}

function slugify(string $text): string
{
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
    $text = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $text));
    return trim($text, '-');
}
