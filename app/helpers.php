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

function user_avatar_src(?array $user): string
{
    if (!$user) {
        return '';
    }
    $raw = trim((string) ($user['avatar_url'] ?? $user['avatar_src'] ?? ''));
    if ($raw === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $raw) === 1) {
        return $raw;
    }
    return uploaded($raw);
}

function avatar_html(?array $person, int $size = 34, string $class = 'avatar'): string
{
    $src = user_avatar_src($person);
    $initials = \Adl\Models\User::initials($person ?? []);
    if ($src !== '') {
        return '<img class="' . e(trim($class . ' avatar-photo')) . '" src="' . e($src) . '" alt="" width="' . $size . '" height="' . $size . '">';
    }
    return '<span class="' . e($class) . '" style="' . e(avatar_style($initials, $size)) . '">' . e($initials) . '</span>';
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

function service_cover_label(string $category): string
{
    return $category !== '' ? $category : 'Prestation';
}

function service_cover_html(string $category, string $extraClass = ''): string
{
    $label = service_cover_label($category);
    $class = trim('service-cover ' . $extraClass);
    return '<div class="' . e($class) . '" role="img" aria-label="' . e('Visuel ' . $label) . '">'
        . '<span class="service-cover-kicker">acteursdulivre.fr</span>'
        . '<span class="service-cover-type">' . e($label) . '</span>'
        . '</div>';
}

function service_brand_cover_url(string $category): string
{
    $label = service_cover_label($category);
    $safe = htmlspecialchars($label, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 480" width="800" height="480">'
        . '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
        . '<stop offset="0%" stop-color="#1c2b3c"/><stop offset="100%" stop-color="#15212f"/></linearGradient></defs>'
        . '<rect width="800" height="480" fill="url(#g)"/>'
        . '<text x="770" y="220" text-anchor="end" fill="rgba(255,255,255,.08)" font-family="Georgia, serif" font-size="280" font-style="italic">a</text>'
        . '<text x="630" y="100" fill="#eb963b" font-family="Georgia, serif" font-size="90">’</text>'
        . '<text x="48" y="400" fill="#efdfce" font-family="Segoe UI, Helvetica, Arial, sans-serif" font-size="18" letter-spacing="3">ACTEURSDULIVRE.FR</text>'
        . '<text x="48" y="358" fill="#ffffff" font-family="Segoe UI, Helvetica, Arial, sans-serif" font-size="52" font-weight="700">' . $safe . '</text>'
        . '</svg>';
    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
}

/** @param array<string, mixed> $item */
function search_card_media(array $item): string
{
    if (!empty($item['thumb'])) {
        return '<div class="search-card-media" style="background-image:url(\'' . e((string) $item['thumb']) . '\')"></div>';
    }
    if (($item['kind'] ?? '') === 'prestations') {
        return service_cover_html((string) ($item['cat'] ?? ''), 'search-card-media');
    }
    $initials = (string) ($item['initials'] ?? '');
    if ($initials === '') {
        $initials = mb_strtoupper(mb_substr((string) ($item['title'] ?? 'AD'), 0, 2));
    }
    return '<div class="search-card-media search-card-media-plain"><span class="avatar" style="'
        . e(avatar_style($initials, 42)) . '">' . e($initials) . '</span></div>';
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

function not_found(string $message = ''): never
{
    http_response_code(404);
    \Adl\Core\View::render('errors/404', [
        'title' => 'Page introuvable',
        'message' => $message !== '' ? $message : 'Le lien est peut-être ancien, ou la page a été retirée.',
    ]);
    exit;
}

function format_int(int $n): string
{
    return number_format($n, 0, ',', ' ');
}

function format_euros(?int $amount): string
{
    if ($amount === null) {
        return 'sur devis';
    }
    return format_int($amount) . ' €';
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
        'chat' => '<path d="M4 3h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-5.2L8 21.4V17H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/>',
        'bell' => '<path d="M12 2a6 6 0 0 1 6 6v4.2l1.6 2.4V16H4.4v-1.4L6 12.2V8a6 6 0 0 1 6-6zm-2.4 16h4.8A2.4 2.4 0 0 1 12 20.4 2.4 2.4 0 0 1 9.6 18z"/>',
        'gear' => '<path fill-rule="evenodd" d="M10.2 2h3.6l.4 2.2c.7.2 1.3.5 1.9.9l2.1-.9 1.8 3.1-1.7 1.5c.2.6.3 1.3.3 2s-.1 1.4-.3 2l1.7 1.5-1.8 3.1-2.1-.9c-.6.4-1.2.7-1.9.9L13.8 22h-3.6l-.4-2.2a7 7 0 0 1-1.9-.9l-2.1.9-1.8-3.1 1.7-1.5A7 7 0 0 1 5.4 12c0-.7.1-1.4.3-2L4 8.5l1.8-3.1 2.1.9c.6-.4 1.2-.7 1.9-.9L10.2 2zM12 8.4A3.6 3.6 0 1 0 12 15.6 3.6 3.6 0 0 0 12 8.4z"/>',
        'arrow' => '<path d="M10 5.6 16.4 12 10 18.4 8.6 17l4.9-5-4.9-5L10 5.6z"/>',
        'sliders' => '<path d="M4 5h10v2H4V5zm12 0h4v2h-4V5zM4 11h4v2H4v-2zm6 0h10v2H10v-2zM4 17h8v2H4v-2zm10 0h6v2h-6v-2zM13 3h2v6h-2V3zm-6 6h2v6H7V9zm8 6h2v6h-2v-6z"/>',
        'store' => '<path d="M4 3h16l1.4 6.2c.1.6-.3 1.2-1 1.3H19v9H5v-9H3.6c-.7-.1-1.1-.7-1-1.3L4 3zm3 8v7h4v-4h2v4h4v-7H7z"/>',
        'book' => '<path d="M5 3h9.2A3.8 3.8 0 0 1 18 6.8V21H7.2A2.2 2.2 0 0 0 5 18.8V3zm2 2v13.8c0 .1.1.2.2.2H16V6.8A1.8 1.8 0 0 0 14.2 5H7zm10 0h2v16h-2V5z"/>',
        'clock' => '<path fill-rule="evenodd" d="M12 2a10 10 0 1 1 0 20 10 10 0 0 1 0-20zm0 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16zm.8 3.2V12l3.6 2.2-.8 1.3L11 12.5V7.2h1.8z"/>',
        'share' => '<path d="M14.4 8.4 20 3.2v5.4h-1.6V6.2l-5.2 4.7-1.1-1.2 5.1-4.6h-2.2V3.4h5.6v5zM4 6h7.2v1.8H5.8v10.4h10.4V13H18v6.8H4V6z"/>',
        'share-facebook' => '<path d="M14.2 8.4h2.4V5.2h-2.4c-2.7 0-4.4 1.7-4.4 4.3v1.7H7.6v3.2h2.2V22h3.4v-7.6h2.6l.6-3.2h-3.2V9.8c0-.8.4-1.4 1-1.4z"/>',
        'share-instagram' => '<path fill-rule="evenodd" d="M8.2 3h7.6A5.2 5.2 0 0 1 21 8.2v7.6A5.2 5.2 0 0 1 15.8 21H8.2A5.2 5.2 0 0 1 3 15.8V8.2A5.2 5.2 0 0 1 8.2 3zm0 1.8A3.4 3.4 0 0 0 4.8 8.2v7.6a3.4 3.4 0 0 0 3.4 3.4h7.6a3.4 3.4 0 0 0 3.4-3.4V8.2a3.4 3.4 0 0 0-3.4-3.4H8.2zM12 8.1A3.9 3.9 0 1 1 8.1 12 3.9 3.9 0 0 1 12 8.1zm0 1.7A2.2 2.2 0 1 0 14.2 12 2.2 2.2 0 0 0 12 9.8zm4.7-2.9a1 1 0 1 1-1 1 1 1 0 0 1 1-1z"/>',
        'share-linkedin' => '<path d="M6.4 9.2H3.6V20h2.8V9.2zM5 4.2A1.6 1.6 0 1 0 5 7.4 1.6 1.6 0 0 0 5 4.2zM20.4 13.2c0-3.1-1.6-4.6-3.8-4.6-1.8 0-2.6 1-3.1 1.7V9.2H10.8c0 1.1 0 10.8 0 10.8h2.7v-6c0-.3 0-.7.1-1 .3-.7.9-1.5 2-1.5 1.4 0 2 1.1 2 2.6V20h2.8v-6.8z"/>',
        'share-x' => '<path d="M4 4.8h3.4l4.1 5.6L16.2 4.8H20l-6.2 7.2L20.4 19.2h-3.4l-4.5-6.2-4.8 6.2H4l6.7-7.6L4 4.8z"/>',
        'share-whatsapp' => '<path d="M12 3.2A8.7 8.7 0 0 0 5.4 17.2L4.2 21l3.9-1.2A8.7 8.7 0 1 0 12 3.2zm0 1.7a7 7 0 0 1 5.9 10.7l-.3.4.2.5.7 2.1-2.2-.7-.5-.1-.4.2A7 7 0 0 1 12 4.9zm-3.4 3.3c.2 0 .4 0 .6.4.2.4.7 1.6.7 1.7s.1.3 0 .5c-.1.2-.2.3-.4.5l-.3.3c-.2.1-.3.3-.1.6.2.3.8 1.3 1.8 2.1 1.2 1 2.2 1.3 2.5 1.4.3.1.5.1.7-.1.2-.2.7-.8.9-1.1.2-.3.3-.2.6-.1.3.1 1.7.8 2 .9.3.1.5.2.6.3.1.1.1.7-.2 1.3-.3.7-1.5 1.3-2.1 1.4-.5.1-1.2.1-2 0A8.6 8.6 0 0 1 8.6 14c-.6-.9-1.1-2-1.2-2.3-.2-.4-.8-1.4-.8-2.4 0-1 .5-1.5.7-1.7.2-.2.4-.3.6-.3z"/>',
        'share-copy' => '<path d="M8 4h9.2A1.8 1.8 0 0 1 19 5.8V16h-1.8V6.6H8V4zm-3 3.4h9.2A1.8 1.8 0 0 1 16 9.2v9A1.8 1.8 0 0 1 14.2 20H5.8A1.8 1.8 0 0 1 4 18.2v-9A1.8 1.8 0 0 1 5.8 7.4zM6 9.2v9h8.2v-9H6z"/>',
        'dot' => '<circle cx="12" cy="12" r="3.4"/>',
    ];

    $inner = $paths[$name] ?? $paths['dot'];
    return '<svg class="ico" xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">' . $inner . '</svg>';
}
