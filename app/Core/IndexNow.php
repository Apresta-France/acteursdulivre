<?php

declare(strict_types=1);

namespace Adl\Core;

use Adl\Data\Share;

/** Signale des URL nouvelles ou mises à jour à IndexNow (Bing, et les moteurs qui le relaient). */
final class IndexNow
{
    public const KEY = 'f4c8e2a19b6d47c0a3e5d8f1b27490ac';

    public static function keyLocation(): string
    {
        return '/' . self::KEY . '.txt';
    }

    /** @param list<string> $urls chemins ou URL absolues */
    public static function submit(array $urls): array
    {
        $list = [];
        foreach ($urls as $url) {
            $url = trim($url);
            if ($url === '') {
                continue;
            }
            if (!str_starts_with($url, 'http')) {
                $url = Share::absolute($url);
            }
            if ($url !== '') {
                $list[$url] = true;
            }
        }
        $list = array_slice(array_keys($list), 0, 1000);
        if ($list === []) {
            return ['ok' => true, 'submitted' => 0];
        }

        $host = (string) parse_url(Share::absolute('/'), PHP_URL_HOST);
        $payload = json_encode([
            'host' => $host,
            'key' => self::KEY,
            'keyLocation' => Share::absolute(self::keyLocation()),
            'urlList' => $list,
        ], JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            return ['ok' => false, 'submitted' => 0];
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'timeout' => 5,
                'ignore_errors' => true,
                'header' => "Content-Type: application/json; charset=utf-8\r\n",
                'content' => $payload,
            ],
        ]);
        $body = @file_get_contents('https://api.indexnow.org/indexnow', false, $context);

        return ['ok' => $body !== false, 'submitted' => count($list)];
    }
}
