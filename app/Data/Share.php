<?php

declare(strict_types=1);

namespace Adl\Data;

use Adl\Core\Env;

final class Share
{
    public static function absolute(string $path = '/'): string
    {
        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        $base = rtrim(Env::get('APP_URL', ''), '/');
        if ($base === '') {
            $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
                || (($_SERVER['SERVER_PORT'] ?? '') === '443');
            $host = (string) ($_SERVER['HTTP_HOST'] ?? 'www.acteursdulivre.fr');
            $base = ($https ? 'https' : 'http') . '://' . $host;
        }

        if ($path === '' || $path === '/') {
            return $base . '/';
        }

        return $base . '/' . ltrim($path, '/');
    }

    public static function current(): string
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $base = rtrim(Env::get('APP_URL', ''), '/');
        if ($base !== '') {
            return $base . $uri;
        }

        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $query = parse_url($uri, PHP_URL_QUERY);
        return self::absolute((string) $path) . ($query ? '?' . $query : '');
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    public static function meta(string $title, string $description, ?string $url = null, string $type = 'website', ?string $image = null, array $extra = []): array
    {
        return Seo::build($title, $description, $url, $type, $image, $extra);
    }

    /**
     * @return list<array{id: string, label: string, href: string, copy?: bool, native?: bool}>
     */
    public static function networks(string $url, string $title, string $text = ''): array
    {
        $encUrl = rawurlencode($url);
        $encTitle = rawurlencode($title);
        $message = $title . ($text !== '' ? "\n" . $text : '') . "\n" . $url;

        return [
            [
                'id' => 'facebook',
                'label' => 'Facebook',
                'href' => 'https://www.facebook.com/sharer/sharer.php?u=' . $encUrl,
            ],
            [
                'id' => 'instagram',
                'label' => 'Instagram',
                'href' => '#',
                'copy' => true,
            ],
            [
                'id' => 'linkedin',
                'label' => 'LinkedIn',
                'href' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $encUrl,
            ],
            [
                'id' => 'x',
                'label' => 'X',
                'href' => 'https://twitter.com/intent/tweet?url=' . $encUrl . '&text=' . $encTitle,
            ],
            [
                'id' => 'whatsapp',
                'label' => 'WhatsApp',
                'href' => 'https://api.whatsapp.com/send?text=' . rawurlencode($message),
            ],
            [
                'id' => 'copy',
                'label' => 'Copier le lien',
                'href' => '#',
                'copy' => true,
            ],
        ];
    }
}
