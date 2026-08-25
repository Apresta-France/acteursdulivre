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

function icon(string $name, int $size = 20): string
{
    $paths = [
        'home' => '<path d="M12 3.2 2.8 11.1h2.4V21h5.3v-6.1h3V21h5.3v-9.9h2.4L12 3.2z"/>',
        'search' => '<path fill-rule="evenodd" d="M10.6 3a7.6 7.6 0 1 0 4.7 13.6l4.1 4.1 1.5-1.5-4.1-4.1A7.6 7.6 0 0 0 10.6 3zm0 2.2a5.4 5.4 0 1 1 0 10.8 5.4 5.4 0 0 1 0-10.8z"/>',
        'plus' => '<path d="M11 4h2v7h7v2h-7v7h-2v-7H4v-2h7V4z"/>',
        'file-plus' => '<path fill-rule="evenodd" d="M6 2h8l6 6v14H4V2h2zm7 1.8V8h4.2L13 3.8zM11 11h2v3h3v2h-3v3h-2v-3H8v-2h3v-3z"/>',
        'clipboard' => '<path fill-rule="evenodd" d="M9 2h6c.6 0 1.1.3 1.4.8H19a1 1 0 0 1 1 1V21a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V3.8a1 1 0 0 1 1-1h2.6C8 2.3 8.4 2 9 2zm.4 2H14.6V5H9.4V4zM8 10h8v1.8H8V10zm0 4h8v1.8H8V14z"/>',
        'bag' => '<path fill-rule="evenodd" d="M8 7V6a4 4 0 1 1 8 0v1h4v14H4V7h4zm2 0h4V6a2 2 0 1 0-4 0v1z"/>',
        'heart' => '<path d="M12 20.4 10.6 19C5.4 14.4 2 11.3 2 7.5 2 4.4 4.4 2 7.5 2c1.7 0 3.4.8 4.5 2.1C13.1 2.8 14.8 2 16.5 2 19.6 2 22 4.4 22 7.5c0 3.8-3.4 6.9-8.6 11.5L12 20.4z"/>',
        'id' => '<path d="M12 12a4.2 4.2 0 1 0 0-8.4 4.2 4.2 0 0 0 0 8.4zM4 20.8C4 17.2 7.6 15 12 15s8 2.2 8 5.8V22H4v-1.2z"/>',
        'plus-box' => '<path fill-rule="evenodd" d="M4 3h16a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1zm7 4h2v4h4v2h-4v4h-2v-4H7v-2h4V7z"/>',
        'grid' => '<path d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z"/>',
        'megaphone' => '<path d="M14 4v16l-6.2-3.4H5a2 2 0 0 1-2-2v-5.2a2 2 0 0 1 2-2h2.8L14 4zm2 3.4 4.4-1.6v12.4L16 16.6V7.4zM7.8 18.2 7 21h3.4l.6-2.2-3.2-.6z"/>',
        'send' => '<path d="M3 11.2 21 3l-4.6 18-4.8-6.3L3 11.2zm8.7 2.3 2.5 3.3 2.3-9-6.7 4.4 1.9 1.3z"/>',
        'invoice' => '<path fill-rule="evenodd" d="M7 2h10v20l-2.5-1.3L12 22l-2.5-1.3L7 22V2zm3 5h4v2h-4V7zm0 4h7v2h-7v-2zm0 4h7v2h-7v-2z"/>',
        'mail' => '<path fill-rule="evenodd" d="M3 5h18v14H3V5zm2 2.2 7 5 7-5H5zm0 2.4V17h14V9.6l-7 5-7-5z"/>',
        'bell' => '<path d="M12 2a6 6 0 0 1 6 6v4.2l1.6 2.4V16H4.4v-1.4L6 12.2V8a6 6 0 0 1 6-6zm-2.4 16h4.8A2.4 2.4 0 0 1 12 20.4 2.4 2.4 0 0 1 9.6 18z"/>',
        'gear' => '<path fill-rule="evenodd" d="M10.2 2h3.6l.4 2.2c.7.2 1.3.5 1.9.9l2.1-.9 1.8 3.1-1.7 1.5c.2.6.3 1.3.3 2s-.1 1.4-.3 2l1.7 1.5-1.8 3.1-2.1-.9c-.6.4-1.2.7-1.9.9L13.8 22h-3.6l-.4-2.2a7 7 0 0 1-1.9-.9l-2.1.9-1.8-3.1 1.7-1.5A7 7 0 0 1 5.4 12c0-.7.1-1.4.3-2L4 8.5l1.8-3.1 2.1.9c.6-.4 1.2-.7 1.9-.9L10.2 2zM12 8.4A3.6 3.6 0 1 0 12 15.6 3.6 3.6 0 0 0 12 8.4z"/>',
        'arrow' => '<path d="M10 5.6 16.4 12 10 18.4 8.6 17l4.9-5-4.9-5L10 5.6z"/>',
        'sliders' => '<path d="M4 5h10v2H4V5zm12 0h4v2h-4V5zM4 11h4v2H4v-2zm6 0h10v2H10v-2zM4 17h8v2H4v-2zm10 0h6v2h-6v-2zM13 3h2v6h-2V3zm-6 6h2v6H7V9zm8 6h2v6h-2v-6z"/>',
        'store' => '<path d="M4 3h16l1.4 6.2c.1.6-.3 1.2-1 1.3H19v9H5v-9H3.6c-.7-.1-1.1-.7-1-1.3L4 3zm3 8v7h4v-4h2v4h4v-7H7z"/>',
        'book' => '<path d="M5 3h9.2A3.8 3.8 0 0 1 18 6.8V21H7.2A2.2 2.2 0 0 0 5 18.8V3zm2 2v13.8c0 .1.1.2.2.2H16V6.8A1.8 1.8 0 0 0 14.2 5H7zm10 0h2v16h-2V5z"/>',
        'dot' => '<circle cx="12" cy="12" r="3.4"/>',
    ];

    $inner = $paths[$name] ?? $paths['dot'];
    return '<svg class="ico" xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">' . $inner . '</svg>';
}
