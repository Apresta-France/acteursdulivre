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
            $base = 'https://acteursdulivre.fr';
        }

        if ($path === '' || $path === '/') {
            return $base . '/';
        }

        $path = '/' . ltrim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return $base . $path;
    }

    public static function current(): string
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $base = rtrim(Env::get('APP_URL', ''), '/');
        if ($base !== '') {
            $basePath = rtrim((string) (parse_url($base, PHP_URL_PATH) ?: ''), '/');
            if ($basePath !== '' && ($path === $basePath || str_starts_with($path, $basePath . '/'))) {
                $path = substr($path, strlen($basePath)) ?: '/';
            }
        }

        return self::absolute((string) $path);
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
                'href' => $url !== '' ? $url : '#',
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
                'href' => $url !== '' ? $url : '#',
                'copy' => true,
            ],
        ];
    }
}
