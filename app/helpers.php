<?php

declare(strict_types=1);

use Adl\Core\Auth;
use Adl\Core\Csrf;
use Adl\Core\Env;

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function sanitize_rich_html(?string $html): string
{
    return \Adl\Core\RichText::sanitize((string) $html);
}

function rich_html(?string $html, string $fallback = ''): string
{
    $clean = sanitize_rich_html($html);

    return $clean !== '' ? $clean : $fallback;
}

function plain_text(?string $html): string
{
    return \Adl\Core\RichText::plain((string) $html);
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

function journal_listing_url(string $q = '', string $category = '', int $page = 1): string
{
    $query = [];
    if ($q !== '') {
        $query['q'] = $q;
    }
    if ($category !== '') {
        $query['cat'] = $category;
    }
    if ($page > 1) {
        $query['page'] = $page;
    }
    $href = url('/journal');
    if ($query !== []) {
        $href .= '?' . http_build_query($query);
    }
    return $href;
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

function user_error_message(\Throwable $e, string $fallback = 'Une erreur est survenue. Réessayez dans un instant.'): string
{
    if ($e instanceof \PDOException) {
        return $fallback;
    }
    if ($e instanceof \RuntimeException) {
        $message = trim($e->getMessage());
        return $message !== '' ? $message : $fallback;
    }
    return $fallback;
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
    $initials = trim((string) ($person['initials'] ?? ''));
    if ($initials === '') {
        $initials = \Adl\Models\User::initials($person ?? []);
    }
    if ($src !== '') {
        return '<img class="' . e(trim($class . ' avatar-photo')) . '" src="' . e($src) . '" alt="" width="' . $size . '" height="' . $size . '">';
    }
    return '<span class="' . e($class) . '" style="' . e(avatar_style($initials, $size)) . '">' . e($initials) . '</span>';
}

function photo_asset(string $stem): string
{
    $dir = ADL_ROOT . '/public/assets/img/photos/';
    if (is_file($dir . $stem . '.webp')) {
        return asset('img/photos/' . $stem . '.webp');
    }
    if (is_file($dir . $stem . '.jpg')) {
        return asset('img/photos/' . $stem . '.jpg');
    }
    return asset('img/photos/' . $stem . '.jpg');
}

function photo(int $index = 0): string
{
    $photos = ['books', 'flowers', 'leather', 'console', 'desk', 'library'];
    $stem = $photos[$index] ?? $photos[0];
    $dir = ADL_ROOT . '/public/assets/img/photos/';
    if (is_file($dir . $stem . '.webp') || is_file($dir . $stem . '.jpg')) {
        return photo_asset($stem);
    }
    $remote = [
        'Old books (Unsplash).jpg',
        'Watercolor Flowers (Unsplash).jpg',
        'Leather bound books (Unsplash).jpg',
        'Mixing console (Unsplash).jpg',
        'Books, pencils, laptop, and iphone on a desk (Unsplash).jpg',
        'Library Books Bookshelves (Unsplash).jpg',
    ];
    $file = $remote[$index] ?? $remote[0];
    return 'https://commons.wikimedia.org/wiki/Special:FilePath/' . rawurlencode($file) . '?width=800';
}

/**
 * Photos du héros d'accueil — CC0 (Unsplash avant juin 2017, Wikimedia Commons) :
 * hero-write : « Writing the Moment » — rawpixel.com
 * hero-paint : « A notebook with paint brushes » — Tim Arterbury
 * hero-shop  : librairie parisienne aux ampoules — Unsplash / Commons
 *
 * @return list<string>
 */
function home_hero_photos(): array
{
    $urls = [
        photo_asset('hero-write') . '?v=2',
        photo_asset('hero-paint') . '?v=2',
        photo_asset('hero-shop') . '?v=2',
    ];
    shuffle($urls);
    return array_values($urls);
}

function service_cover_label(string $category): string
{
    return $category !== '' ? $category : 'Prestation';
}

function service_cover_image_url(string $category = ''): string
{
    $stem = $category !== '' ? \Adl\Data\Catalog::coverStem($category) : '';
    if ($stem !== '') {
        $dir = ADL_ROOT . '/public/assets/img/covers/';
        if (is_file($dir . $stem . '.webp')) {
            return asset('img/covers/' . $stem . '.webp') . '?v=5';
        }
        if (is_file($dir . $stem . '.jpg')) {
            return asset('img/covers/' . $stem . '.jpg') . '?v=5';
        }
    }
    $webp = ADL_ROOT . '/public/assets/img/service-cover-default.webp';
    if (is_file($webp)) {
        return asset('img/service-cover-default.webp');
    }
    return asset('img/service-cover-default.jpg');
}

function service_cover_html(string $category, string $extraClass = ''): string
{
    $label = service_cover_label($category);
    $class = trim('service-cover ' . $extraClass);
    $url = service_cover_image_url($category);
    return '<div class="' . e($class) . '" style="--service-cover-photo:url(\'' . e($url) . '\')" role="img" aria-label="' . e('Visuel ' . $label) . '">'
        . '<span class="service-cover-photo" aria-hidden="true"></span>'
        . '<span class="service-cover-kicker">acteursdulivre.fr</span>'
        . '<span class="service-cover-type">' . e($label) . '</span>'
        . '</div>';
}

function service_brand_cover_url(string $category = ''): string
{
    return service_cover_image_url($category);
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
    return '';
}

/** @param array<string, mixed> $item */
function search_card_html(array $item, bool $showNetwork = false): string
{
    $kind = (string) ($item['kind'] ?? '');
    $isPerson = $kind === 'prestataires';
    $isBusy = !empty($item['is_busy']);
    $media = search_card_media($item);
    $class = 'search-card';
    if ($isBusy) {
        $class .= ' is-busy';
    }
    if ($isPerson) {
        $class .= ' search-card-person';
    }

    $kicker = '<div class="search-card-kicker"><span>' . e((string) ($item['kind_label'] ?? '')) . '</span>';
    if (!empty($item['cat'])) {
        $kicker .= '<span>' . e((string) $item['cat']) . '</span>';
    }
    if ($showNetwork && !empty($item['live'])) {
        $kicker .= '<span class="search-live">Votre réseau</span>';
    }
    if ($isPerson && !empty($item['availability_label'])) {
        $kicker .= '<span class="status-pill' . ($isBusy ? ' is-busy' : ' is-available') . '">'
            . e((string) $item['availability_label']) . '</span>';
    }
    $kicker .= '</div>';

    $title = '<div class="search-card-title">' . e((string) ($item['title'] ?? '')) . '</div>';
    $sub = '<div class="search-card-sub">' . e((string) ($item['subtitle'] ?? '')) . '</div>';
    $heading = $isPerson
        ? '<div class="search-card-heading">' . avatar_html($item, 40, 'avatar search-card-avatar')
            . '<div class="search-card-who">' . $title . $sub . '</div></div>'
        : $title . $sub;

    $metaBits = array_filter([
        (string) ($item['meta'] ?? ''),
        !empty($item['rating']) ? '★ ' . $item['rating'] : '',
    ]);
    $meta = '<div class="search-card-meta"><span>' . e(implode(' · ', $metaBits)) . '</span>';
    if (!empty($item['price'])) {
        $meta .= '<strong>' . e((string) $item['price']) . '</strong>';
    }
    $meta .= '</div>';

    return '<a class="' . e($class) . '" href="' . e(url((string) ($item['href'] ?? '/'))) . '">'
        . $media
        . '<div class="search-card-body">'
        . $kicker
        . $heading
        . $meta
        . '</div></a>';
}

function redirect(string $path, int $code = 302): never
{
    header('Location: ' . url(safe_internal_path($path) ?? '/espace'), true, $code);
    exit;
}

function safe_internal_path(?string $path): ?string
{
    $path = str_replace(["\r", "\n", '\\'], '', trim((string) $path));
    if ($path === '' || $path[0] !== '/' || str_starts_with($path, '//')) {
        return null;
    }
    return $path;
}

function ascii_fold(string $text): string
{
    static $map = [
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE',
        'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Œ' => 'OE',
        'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Ÿ' => 'Y',
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae',
        'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'œ' => 'oe',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'ÿ' => 'y',
    ];
    $text = strtr($text, $map);
    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    return $ascii !== false ? $ascii : $text;
}

function slugify(string $text): string
{
    $text = strtolower(ascii_fold($text));
    $text = (string) preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

function search_norm(string $text): string
{
    return strtolower(ascii_fold(mb_strtolower($text)));
}

/**
 * @param list<array{v: string, l: string, n: int|string}> $options
 * @param list<string> $selected
 */
function search_filter_group(string $name, string $label, array $options, array $selected = [], bool $keepEmpty = false): string
{
    $html = '<div class="sf-group"><div class="sf-group-label">' . e($label) . '</div><div class="sf-opts">';
    foreach ($options as $opt) {
        $value = (string) ($opt['v'] ?? '');
        $on = in_array($value, $selected, true);
        if (!$keepEmpty && !$on && (int) ($opt['n'] ?? 0) === 0 && in_array($name, ['metier', 'spec'], true)) {
            continue;
        }
        $html .= '<label class="sf-opt">'
            . '<input type="checkbox" name="' . e($name) . '[]" value="' . e($value) . '"' . ($on ? ' checked' : '') . '>'
            . '<span class="sf-box" aria-hidden="true"></span>'
            . '<span class="sf-txt">' . e((string) ($opt['l'] ?? $value)) . '</span>'
            . '<span class="sf-n">' . e((string) ($opt['n'] ?? 0)) . '</span>'
            . '</label>';
    }
    return $html . '</div></div>';
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

function article_image_url(string $path): string
{
    $path = trim(str_replace(['\\', "\0"], '/', $path));
    if ($path === '' || str_contains($path, '..')) {
        return '';
    }
    if (str_starts_with($path, 'img/')) {
        return asset($path);
    }

    return uploaded($path);
}

function json_response(array $payload, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
        $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
    }
    $json = json_encode($payload, $flags);
    echo $json !== false ? $json : '{}';
    exit;
}

function not_found(string $message = ''): never
{
    http_response_code(404);
    \Adl\Core\View::render('errors/404', [
        'title' => 'Page introuvable',
        'message' => $message !== '' ? $message : 'Le lien est peut-être ancien, ou la page a été retirée.',
        'meta' => [
            'title' => 'Page introuvable — acteursdulivre.fr',
            'description' => 'Cette page n\'existe pas ou a été retirée.',
            'robots' => \Adl\Data\Seo::ROBOTS_NONE,
        ],
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

function format_euros_ttc(?int $amount): string
{
    $label = format_euros($amount);
    return $label === 'sur devis' ? $label : $label . ' TTC';
}

function format_bytes(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' o';
    }
    if ($bytes < 1024 * 1024) {
        return rtrim(rtrim(number_format($bytes / 1024, 1, ',', ' '), '0'), ',') . ' Ko';
    }
    return rtrim(rtrim(number_format($bytes / (1024 * 1024), 1, ',', ' '), '0'), ',') . ' Mo';
}

function upload_safe_name(string $name): string
{
    $base = basename(str_replace('\\', '/', $name));
    $base = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $base) ?? '';
    $base = trim($base, '.-');
    if (mb_strlen($base) > 160) {
        $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));
        $stem = pathinfo($base, PATHINFO_FILENAME);
        $base = mb_substr($stem, 0, 140) . ($ext !== '' ? '.' . $ext : '');
    }
    return $base;
}

/** @return array<string, list<string>> */
function upload_mime_map(): array
{
    return [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
        'gif' => ['image/gif'],
        'txt' => ['text/plain'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'odt' => ['application/vnd.oasis.opendocument.text'],
    ];
}

function assert_upload(array $file, array $allowedExt, int $maxBytes): string
{
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
        throw new RuntimeException('Le fichier n\'a pas pu être transmis.');
    }
    if ((int) ($file['size'] ?? 0) > $maxBytes) {
        throw new RuntimeException('Le fichier dépasse la taille maximale autorisée.');
    }

    $name = (string) ($file['name'] ?? '');
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if ($ext === '' || !in_array($ext, $allowedExt, true)) {
        throw new RuntimeException('Format de fichier non accepté.');
    }
    if (preg_match('/\.(php|phtml|phar|exe|js|html|htm|svg|sh|bat|cmd)\./i', $name)) {
        throw new RuntimeException('Format de fichier non accepté.');
    }

    $tmp = (string) $file['tmp_name'];
    $map = upload_mime_map();
    if (isset($map[$ext]) && class_exists(\finfo::class)) {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
        if (!in_array($mime, $map[$ext], true)) {
            throw new RuntimeException('Le type du fichier ne correspond pas à son extension.');
        }
    }

    return $ext;
}

function store_upload(array $file, string $subdir, array $allowedExt, int $maxBytes): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $ext = assert_upload($file, $allowedExt, $maxBytes);

    $dir = ADL_ROOT . '/public/uploads/' . trim($subdir, '/');
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Impossible de créer le dossier d\'upload.');
    }

    $safe = upload_safe_name((string) ($file['name'] ?? ''));
    $filename = bin2hex(random_bytes(8)) . '-' . ($safe !== '' ? $safe : ('fichier.' . $ext));
    if (!move_uploaded_file((string) $file['tmp_name'], $dir . '/' . $filename)) {
        throw new RuntimeException('Le fichier n\'a pas pu être enregistré.');
    }

    return trim($subdir, '/') . '/' . $filename;
}

/** @return array{path: string, name: string, size: int}|null */
function store_private_upload(array $file, string $subdir, array $allowedExt, int $maxBytes): ?array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $ext = assert_upload($file, $allowedExt, $maxBytes);
    $original = upload_safe_name((string) ($file['name'] ?? '')) ?: ('fichier.' . $ext);
    $size = (int) ($file['size'] ?? 0);

    $dir = ADL_ROOT . '/storage/uploads/' . trim($subdir, '/');
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Impossible de créer le dossier d\'upload.');
    }

    $stored = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file((string) $file['tmp_name'], $dir . '/' . $stored)) {
        throw new RuntimeException('Le fichier n\'a pas pu être enregistré.');
    }

    return [
        'path' => trim($subdir, '/') . '/' . $stored,
        'name' => $original,
        'size' => $size,
    ];
}

/** @return array{path: string, name: string, size: int} */
function copy_public_upload_to_private(string $publicRelative, string $subdir, string $originalName): array
{
    $publicRelative = str_replace(['\\', "\0"], '/', $publicRelative);
    if ($publicRelative === '' || str_contains($publicRelative, '..')) {
        throw new RuntimeException('Le fichier n\'a pas pu être enregistré.');
    }

    $src = ADL_ROOT . '/public/uploads/' . ltrim($publicRelative, '/');
    $publicRoot = realpath(ADL_ROOT . '/public/uploads');
    $real = realpath($src);
    if ($publicRoot === false || $real === false || !str_starts_with($real, $publicRoot) || !is_file($real)) {
        throw new RuntimeException('Le fichier n\'a pas pu être enregistré.');
    }

    $ext = strtolower(pathinfo($real, PATHINFO_EXTENSION));
    $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'txt', 'doc', 'docx', 'odt'];
    if ($ext === '' || !in_array($ext, $allowed, true)) {
        throw new RuntimeException('Format de fichier non accepté.');
    }

    $dir = ADL_ROOT . '/storage/uploads/' . trim($subdir, '/');
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Impossible de créer le dossier d\'upload.');
    }

    $name = upload_safe_name($originalName) ?: ('fichier.' . $ext);
    $stored = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!copy($real, $dir . '/' . $stored)) {
        throw new RuntimeException('Le fichier n\'a pas pu être enregistré.');
    }

    return [
        'path' => trim($subdir, '/') . '/' . $stored,
        'name' => $name,
        'size' => (int) filesize($dir . '/' . $stored),
    ];
}

function send_private_file(string $relative, string $downloadName, string $mime = 'application/octet-stream'): never
{
    send_stored_upload($relative, $downloadName, $mime, true);
}

/** @return array{path: string, name: string, size: int} */
function copy_any_upload_to_private(string $relative, string $subdir, string $originalName): array
{
    $src = resolve_upload_path($relative, false);
    if ($src === null) {
        throw new RuntimeException('Le fichier n\'a pas pu être enregistré.');
    }

    $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
    $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'txt', 'doc', 'docx', 'odt'];
    if ($ext === '' || !in_array($ext, $allowed, true)) {
        throw new RuntimeException('Format de fichier non accepté.');
    }

    $dir = ADL_ROOT . '/storage/uploads/' . trim($subdir, '/');
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Impossible de créer le dossier d\'upload.');
    }

    $name = upload_safe_name($originalName) ?: ('fichier.' . $ext);
    $stored = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!copy($src, $dir . '/' . $stored)) {
        throw new RuntimeException('Le fichier n\'a pas pu être enregistré.');
    }

    return [
        'path' => trim($subdir, '/') . '/' . $stored,
        'name' => $name,
        'size' => (int) filesize($dir . '/' . $stored),
    ];
}

function send_any_upload(string $relative, string $downloadName, string $mime = 'application/octet-stream', bool $inline = false): never
{
    send_stored_upload($relative, $downloadName, $mime, false, $inline);
}

function delete_upload(string $relative): void
{
    $real = resolve_upload_path($relative, false);
    if ($real !== null && is_file($real)) {
        @unlink($real);
    }
}

function send_stored_upload(string $relative, string $downloadName, string $mime, bool $privateOnly, bool $inline = false): never
{
    $real = resolve_upload_path($relative, $privateOnly);
    if ($real === null) {
        not_found('Fichier introuvable.');
    }

    $name = upload_safe_name($downloadName) ?: 'piece-jointe';
    $disposition = $inline ? 'inline' : 'attachment';
    header('Content-Type: ' . $mime);
    header('Content-Disposition: ' . $disposition . '; filename="' . $name . '"');
    header('Content-Length: ' . (string) filesize($real));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    readfile($real);
    exit;
}

function resolve_upload_path(string $relative, bool $privateOnly = false): ?string
{
    $relative = str_replace(['\\', "\0"], '/', $relative);
    if ($relative === '' || str_contains($relative, '..')) {
        return null;
    }

    $candidates = [
        [ADL_ROOT . '/storage/uploads/' . ltrim($relative, '/'), ADL_ROOT . '/storage/uploads'],
    ];
    if (!$privateOnly) {
        $candidates[] = [ADL_ROOT . '/public/uploads/' . ltrim($relative, '/'), ADL_ROOT . '/public/uploads'];
    }

    foreach ($candidates as [$full, $rootDir]) {
        $root = realpath($rootDir);
        $real = realpath($full);
        if ($root === false || $real === false || !is_file($real)) {
            continue;
        }
        $rootNorm = strtolower(str_replace('\\', '/', $root));
        $realNorm = strtolower(str_replace('\\', '/', $real));
        if (str_starts_with($realNorm, $rootNorm)) {
            return $real;
        }
    }

    return null;
}

function admin_date(?string $datetime, string $empty = '—'): string
{
    if ($datetime === null || $datetime === '') {
        return $empty;
    }
    $ts = strtotime($datetime);
    return $ts === false ? $datetime : date('d/m/Y', $ts);
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

function app_datetime(?string $datetime): ?\DateTimeImmutable
{
    if ($datetime === null || trim($datetime) === '') {
        return null;
    }
    try {
        $tz = new \DateTimeZone(date_default_timezone_get() ?: 'Europe/Paris');
        return new \DateTimeImmutable($datetime, $tz);
    } catch (\Throwable) {
        return null;
    }
}

function datetime_iso(?string $datetime): string
{
    $dt = app_datetime($datetime);
    return $dt ? $dt->format(\DateTimeInterface::ATOM) : '';
}

function format_message_when(?string $datetime): string
{
    $dt = app_datetime($datetime);
    if (!$dt) {
        return '';
    }
    $now = new \DateTimeImmutable('now', $dt->getTimezone());
    $time = $dt->format('H:i');
    if ($dt->format('Y-m-d') === $now->format('Y-m-d')) {
        return $time;
    }
    if ($dt->format('Y-m-d') === $now->modify('-1 day')->format('Y-m-d')) {
        return 'hier ' . $time;
    }
    if ($dt->format('Y') === $now->format('Y')) {
        return format_deadline($dt->format('Y-m-d')) . ', ' . $time;
    }
    return $dt->format('d/m/Y') . ' ' . $time;
}

function time_ago(?string $datetime): string
{
    $dt = app_datetime($datetime);
    if (!$dt) {
        return '';
    }
    $diff = time() - $dt->getTimestamp();
    if ($diff < 0) {
        $diff = 0;
    }
    if ($diff < 45) {
        return 'à l\'instant';
    }
    if ($diff < 3600) {
        return 'il y a ' . max(1, (int) floor($diff / 60)) . ' min';
    }
    if ($diff < 86400) {
        return 'il y a ' . (int) floor($diff / 3600) . ' h';
    }
    if ($diff < 86400 * 7) {
        return 'il y a ' . (int) floor($diff / 86400) . ' j';
    }
    return format_deadline($dt->format('Y-m-d'));
}

/** @param array<string, mixed> $msg */
function inbox_message_html(array $msg, int $currentUserId): string
{
    $mine = (int) ($msg['user_id'] ?? 0) === $currentUserId && $currentUserId > 0;
    $iso = (string) ($msg['created_iso'] ?? datetime_iso($msg['created_at'] ?? null));
    $html = '<article class="msg' . ($mine ? ' is-mine' : '') . '" data-msg-id="' . (int) ($msg['id'] ?? 0) . '"';
    if ($iso !== '') {
        $html .= ' data-created="' . e($iso) . '"';
    }
    $html .= '>';
    $html .= '<div class="msg-meta">' . e((string) ($msg['who'] ?? '')) . ' · ';
    $html .= '<time datetime="' . e($iso) . '">' . e((string) ($msg['when'] ?? '')) . '</time></div>';
    $body = trim((string) ($msg['body'] ?? ''));
    $href = trim((string) ($msg['href'] ?? ''));
    if ($body !== '' && $href !== '') {
        $html .= '<a class="msg-bubble is-link" href="' . e(url($href)) . '" title="Ouvrir le suivi de commande">' . nl2br(e($body)) . '</a>';
    } elseif ($body !== '') {
        $html .= '<p>' . nl2br(e($body)) . '</p>';
    }
    if (!empty($msg['has_file'])) {
        $html .= '<a class="msg-file" href="' . e(url((string) ($msg['file_href'] ?? ''))) . '" title="Télécharger">';
        $html .= icon('download', 16) . ' ';
        $html .= e((string) ($msg['file_label'] ?? ''));
        if (!empty($msg['file_size'])) {
            $html .= ' · ' . e((string) $msg['file_size']);
        }
        $html .= '</a>';
    }
    return $html . '</article>';
}

function icon(string $name, int $size = 20): string
{
    $line = [
        'chat' => '<path d="M17.2 3.5A4.2 4.2 0 0 1 21.4 7.7v6.6a4.2 4.2 0 0 1-4.2 4.2h-5.1l-4.8 2.8v-2.8H6.8A4.2 4.2 0 0 1 2.6 14.3V7.7A4.2 4.2 0 0 1 6.8 3.5z"/><circle cx="8.4" cy="11" r="1.1" fill="currentColor" stroke="none"/><circle cx="12" cy="11" r="1.1" fill="currentColor" stroke="none"/><circle cx="15.6" cy="11" r="1.1" fill="currentColor" stroke="none"/>',
        'bell' => '<path d="M6.15 9.6a5.85 5.85 0 0 1 11.7 0c0 4.35 1.55 6.15 2.25 7 .26.32.03.9-.4.9H4.3c-.43 0-.66-.58-.4-.9.7-.85 2.25-2.65 2.25-7z"/><path d="M10 19.2a2 2 0 0 0 4 0"/><path d="M10.35 4.15A1.65 1.65 0 0 1 12 2.5 1.65 1.65 0 0 1 13.65 4.15"/>',
    ];
    if (isset($line[$name])) {
        return '<svg class="ico ico-line" xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $line[$name] . '</svg>';
    }
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
        'share-youtube' => '<path d="M23 12.2s0-3.2-.4-4.6c-.2-.8-.9-1.5-1.7-1.7C19.4 5.5 12 5.5 12 5.5s-7.4 0-8.9.4c-.8.2-1.5.9-1.7 1.7C1 9 1 12.2 1 12.2s0 3.2.4 4.6c.2.8.9 1.5 1.7 1.7 1.5.4 8.9.4 8.9.4s7.4 0 8.9-.4c.8-.2 1.5-.9 1.7-1.7.4-1.4.4-4.6.4-4.6zM9.8 15.6V8.8l6.2 3.4-6.2 3.4z"/>',
        'share-tiktok' => '<path d="M14.4 3h2.6c.2 2.2 1.6 3.8 3.8 4v2.6c-1.3 0-2.6-.4-3.8-1.1v6.8A6.3 6.3 0 1 1 8.4 9.2v2.7a3.7 3.7 0 1 0 2.6 3.5V3h3.4z"/>',
        'share-bluesky' => '<path d="M6.3 5.2c2.1 1.6 4.4 4.8 5.7 7 1.3-2.2 3.6-5.4 5.7-7 1.6-1.2 4.3-2.1 4.3 1.2 0 .7-.4 5.5-.6 6.3-.8 2.8-3.6 3.5-6.2 3.1 4.4.8 5.6 3.3 3.1 5.8-4.7 4.8-6.8-1.2-7.3-2.8-.1-.3-.1-.4-.1-.4s0 .1-.1.4c-.5 1.6-2.6 7.6-7.3 2.8-2.4-2.5-1.3-5 3.1-5.8-2.6.4-5.4-.3-6.2-3.1-.2-.8-.6-5.6-.6-6.3 0-3.3 2.7-2.4 4.3-1.2z"/>',
        'share-threads' => '<path fill-rule="evenodd" d="M16.7 11.3c.1-.5.1-1 .1-1.4C16.7 6 14.3 3.8 11 3.8 7.3 3.8 5 6.4 5 10.4c0 3.7 1.9 6.6 5.4 6.6 1.8 0 3.3-.7 4.3-1.8l-1.3-1.2c-.7.7-1.7 1.1-2.9 1.1-2.2 0-3.5-1.7-3.5-4.7 0-2.8 1.3-4.6 3.5-4.6 1.9 0 3.1 1.2 3.3 3.2-1-.6-2.1-.8-3.1-.6-1.7.3-2.8 1.6-2.8 3.3 0 1.8 1.2 3 2.8 3 .8 0 1.6-.3 2.1-.9.3.6.8 1.1 1.6 1.4-1 .9-2.4 1.4-4 1.4-4.2 0-7-3.3-7-8 0-4.9 2.9-8.4 7.8-8.4 4.6 0 7.5 3.2 7.5 7.8 0 .7 0 1.4-.1 2.1h-2z"/>',
        'share-mastodon' => '<path d="M12 3.2c-3.6 0-6.4 1.1-6.4 4.4v5.3c0 2.3 1.2 3.1 2.2 3.1.9 0 1.6-.6 1.6-1.3v-4.2c0-1.3.6-1.8 1.4-1.8.8 0 1.3.5 1.3 1.8v6.3h2.6V10.5c0-1.3.5-1.8 1.3-1.8.8 0 1.4.5 1.4 1.8v4.2c0 .7.7 1.3 1.6 1.3 1 0 2.2-.8 2.2-3.1V7.6c0-3.3-2.8-4.4-6.4-4.4H12zM8.3 19.2c-2.6-.3-4.9-1.2-4.9-5.2V9.8h2.4v4.1c0 1.8.8 2.8 2.5 3.1v2.2zM15.7 19.2v-2.2c1.7-.3 2.5-1.3 2.5-3.1V9.8h2.4v4.2c0 4-2.3 4.9-4.9 5.2z"/>',
        'share-whatsapp' => '<path d="M12 3.2A8.7 8.7 0 0 0 5.4 17.2L4.2 21l3.9-1.2A8.7 8.7 0 1 0 12 3.2zm0 1.7a7 7 0 0 1 5.9 10.7l-.3.4.2.5.7 2.1-2.2-.7-.5-.1-.4.2A7 7 0 0 1 12 4.9zm-3.4 3.3c.2 0 .4 0 .6.4.2.4.7 1.6.7 1.7s.1.3 0 .5c-.1.2-.2.3-.4.5l-.3.3c-.2.1-.3.3-.1.6.2.3.8 1.3 1.8 2.1 1.2 1 2.2 1.3 2.5 1.4.3.1.5.1.7-.1.2-.2.7-.8.9-1.1.2-.3.3-.2.6-.1.3.1 1.7.8 2 .9.3.1.5.2.6.3.1.1.1.7-.2 1.3-.3.7-1.5 1.3-2.1 1.4-.5.1-1.2.1-2 0A8.6 8.6 0 0 1 8.6 14c-.6-.9-1.1-2-1.2-2.3-.2-.4-.8-1.4-.8-2.4 0-1 .5-1.5.7-1.7.2-.2.4-.3.6-.3z"/>',
        'share-copy' => '<path d="M8 4h9.2A1.8 1.8 0 0 1 19 5.8V16h-1.8V6.6H8V4zm-3 3.4h9.2A1.8 1.8 0 0 1 16 9.2v9A1.8 1.8 0 0 1 14.2 20H5.8A1.8 1.8 0 0 1 4 18.2v-9A1.8 1.8 0 0 1 5.8 7.4zM6 9.2v9h8.2v-9H6z"/>',
        'download' => '<path d="M11 3h2v10.2l3.4-3.4 1.4 1.4L12 17.4 6.2 11.2l1.4-1.4L11 13.2V3zM4 19h16v2H4v-2z"/>',
        'dot' => '<circle cx="12" cy="12" r="3.4"/>',
        'play' => '<path d="M8 5.4v13.2L19.2 12 8 5.4z"/>',
    ];

    $inner = $paths[$name] ?? $paths['dot'];
    return '<svg class="ico" xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">' . $inner . '</svg>';
}
