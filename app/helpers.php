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
    $bg = (ord($initials[0] ?? 'A') * 7) % 2 === 0 ? '#15212f' : '#D85D3F';
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

function search_norm(string $text): string
{
    $text = mb_strtolower($text);
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    return strtolower($ascii !== false ? $ascii : $text);
}

function unique_slug(string $base, callable $taken): string
{
    $slug = slugify($base) ?: 'item';
    $candidate = $slug;
    $i = 2;
    while ($taken($candidate)) {
        $candidate = $slug . '-' . $i++;
    }
    return $candidate;
}

function uploaded(string $path): string
{
    return url('public/uploads/' . ltrim($path, '/'));
}

function json_response(array $payload, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function store_upload(array $file, string $subdir, array $allowedExt, int $maxBytes): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
        throw new RuntimeException('Le fichier n\'a pas pu être transmis.');
    }
    if ((int) ($file['size'] ?? 0) > $maxBytes) {
        throw new RuntimeException('Le fichier dépasse la taille maximale autorisée.');
    }

    $name = (string) ($file['name'] ?? '');
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        throw new RuntimeException('Format de fichier non accepté.');
    }

    $dir = ADL_ROOT . '/public/uploads/' . trim($subdir, '/');
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Impossible de créer le dossier d\'upload.');
    }

    $filename = bin2hex(random_bytes(8)) . '-' . preg_replace('/[^a-z0-9._-]+/i', '-', $name);
    $filename = trim($filename, '-') ?: (bin2hex(random_bytes(8)) . '.' . $ext);
    if (!move_uploaded_file((string) $file['tmp_name'], $dir . '/' . $filename)) {
        throw new RuntimeException('Le fichier n\'a pas pu être enregistré.');
    }

    return trim($subdir, '/') . '/' . $filename;
}

function format_deadline(?string $date): string
{
    if ($date === null || $date === '') {
        return 'à convenir';
    }
    $ts = strtotime($date);
    if ($ts === false) {
        return $date;
    }
    $months = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
    return date('j', $ts) . ' ' . $months[(int) date('n', $ts) - 1];
}

function time_ago(?string $datetime): string
{
    if ($datetime === null || $datetime === '') {
        return '';
    }
    $ts = strtotime($datetime);
    if ($ts === false) {
        return '';
    }
    $diff = time() - $ts;
    if ($diff < 3600) {
        return 'à l\'instant';
    }
    if ($diff < 86400) {
        $h = (int) floor($diff / 3600);
        return 'il y a ' . $h . ' h';
    }
    if ($diff < 86400 * 7) {
        $d = (int) floor($diff / 86400);
        return 'il y a ' . $d . ' j';
    }
    return format_deadline(date('Y-m-d', $ts));
}
