<?php

declare(strict_types=1);

namespace Adl\Core;

/**
 * Exempte du limiteur d'annuaire les robots dont l'adresse est prouvée
 * (reverse DNS pour Google, Bing et Apple ; plages publiées pour OpenAI et Perplexity).
 * Le user-agent seul ne suffit jamais.
 */
final class VerifiedBot
{
    private const SUCCESS_TTL = 43200;
    private const FAILURE_TTL = 3600;
    private const RANGE_TTL = 604800;

    public static function clientIp(): string
    {
        $remote = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        $forwarded = trim((string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''));
        if ($forwarded !== '' && self::isIp($forwarded) && $remote !== '' && self::isCloudflare($remote)) {
            return $forwarded;
        }

        return self::isIp($remote) ? $remote : '';
    }

    public static function isExempt(): bool
    {
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $ip = self::clientIp();
        $kind = self::kind($ua);
        if ($ip === '' || $kind === null) {
            return false;
        }

        $cached = self::cached($ip, $kind);
        if ($cached !== null) {
            return $cached;
        }

        $ok = match ($kind) {
            'google' => self::reverseDns($ip, ['.googlebot.com', '.google.com']),
            'bing' => self::reverseDns($ip, ['.search.msn.com']),
            'apple' => self::reverseDns($ip, ['.applebot.apple.com']),
            'openai' => self::inVendorRanges($ip, ['openai-gptbot.json', 'openai-searchbot.json', 'openai-chatgpt-user.json']),
            'perplexity' => self::inVendorRanges($ip, ['perplexity-bot.json', 'perplexity-user.json']),
            default => false,
        };
        self::remember($ip, $kind, $ok);

        return $ok;
    }

    public static function refreshRanges(bool $force = false): array
    {
        $sources = [
            'openai-gptbot.json' => 'https://openai.com/gptbot.json',
            'openai-searchbot.json' => 'https://openai.com/searchbot.json',
            'openai-chatgpt-user.json' => 'https://openai.com/chatgpt-user.json',
            'perplexity-bot.json' => 'https://www.perplexity.ai/perplexitybot.json',
            'perplexity-user.json' => 'https://www.perplexity.ai/perplexity-user.json',
            'cloudflare-v4.txt' => 'https://www.cloudflare.com/ips-v4',
            'cloudflare-v6.txt' => 'https://www.cloudflare.com/ips-v6',
        ];
        $dir = self::cacheDir();
        $updated = 0;
        foreach ($sources as $name => $url) {
            $body = self::httpGet($url);
            if ($body === null || strlen($body) < 8) {
                continue;
            }
            if (str_ends_with($name, '.json')) {
                $decoded = json_decode($body, true);
                if (!is_array($decoded) || !isset($decoded['prefixes']) || !is_array($decoded['prefixes'])) {
                    continue;
                }
            }
            if (@file_put_contents($dir . '/' . $name, $body, LOCK_EX) !== false) {
                $updated++;
            }
        }

        return ['ok' => true, 'updated' => $updated];
    }

    public static function ipInCidr(string $ip, string $cidr): bool
    {
        $cidr = trim($cidr);
        if ($cidr === '') {
            return false;
        }
        if (!str_contains($cidr, '/')) {
            return $ip === $cidr;
        }
        [$subnet, $bits] = explode('/', $cidr, 2);
        $ipBin = @inet_pton($ip);
        $subBin = @inet_pton($subnet);
        if (!is_string($ipBin) || !is_string($subBin) || strlen($ipBin) !== strlen($subBin)) {
            return false;
        }
        $bits = (int) $bits;
        $max = strlen($ipBin) * 8;
        if ($bits < 0 || $bits > $max) {
            return false;
        }
        $bytes = intdiv($bits, 8);
        $rem = $bits % 8;
        if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($subBin, 0, $bytes)) {
            return false;
        }
        if ($rem === 0) {
            return true;
        }
        $mask = (0xFF << (8 - $rem)) & 0xFF;

        return (ord($ipBin[$bytes]) & $mask) === (ord($subBin[$bytes]) & $mask);
    }

    private static function kind(string $ua): ?string
    {
        $ua = strtolower($ua);
        if ($ua === '') {
            return null;
        }
        if (str_contains($ua, 'googlebot') || str_contains($ua, 'google-extended') || str_contains($ua, 'googleother') || str_contains($ua, 'storebot-google')) {
            return 'google';
        }
        if (str_contains($ua, 'bingbot')) {
            return 'bing';
        }
        if (str_contains($ua, 'applebot')) {
            return 'apple';
        }
        if (str_contains($ua, 'gptbot') || str_contains($ua, 'oai-searchbot') || str_contains($ua, 'chatgpt-user')) {
            return 'openai';
        }
        if (str_contains($ua, 'perplexitybot') || str_contains($ua, 'perplexity-user')) {
            return 'perplexity';
        }

        return null;
    }

    /** @param list<string> $suffixes */
    private static function reverseDns(string $ip, array $suffixes): bool
    {
        $host = @gethostbyaddr($ip);
        if (!is_string($host) || $host === '' || $host === $ip) {
            return false;
        }
        $host = strtolower(rtrim($host, '.'));
        $matched = false;
        foreach ($suffixes as $suffix) {
            if (str_ends_with($host, $suffix)) {
                $matched = true;
                break;
            }
        }
        if (!$matched || !preg_match('/^[a-z0-9.-]+$/', $host)) {
            return false;
        }
        $forward = @gethostbynamel($host);
        if (is_array($forward) && in_array($ip, $forward, true)) {
            return true;
        }
        $records = @dns_get_record($host, DNS_AAAA);
        if (!is_array($records)) {
            return false;
        }
        foreach ($records as $record) {
            if (($record['ipv6'] ?? '') === $ip) {
                return true;
            }
        }

        return false;
    }

    /** @param list<string> $files */
    private static function inVendorRanges(string $ip, array $files): bool
    {
        foreach ($files as $file) {
            foreach (self::prefixes($file) as $cidr) {
                if (self::ipInCidr($ip, $cidr)) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function isCloudflare(string $ip): bool
    {
        foreach (['cloudflare-v4.txt', 'cloudflare-v6.txt'] as $file) {
            foreach (self::lines($file) as $cidr) {
                if (self::ipInCidr($ip, $cidr)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @return list<string> */
    private static function prefixes(string $file): array
    {
        $raw = self::readRange($file);
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }
        $out = [];
        foreach ($decoded['prefixes'] ?? [] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $cidr = (string) ($row['ipv4Prefix'] ?? $row['ipv6Prefix'] ?? '');
            if ($cidr !== '') {
                $out[] = $cidr;
            }
        }

        return $out;
    }

    /** @return list<string> */
    private static function lines(string $file): array
    {
        $raw = self::readRange($file);
        if ($raw === '') {
            return [];
        }
        $out = [];
        foreach (preg_split('/\R/', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line !== '' && !str_starts_with($line, '#')) {
                $out[] = $line;
            }
        }

        return $out;
    }

    private static function readRange(string $file): string
    {
        $cached = self::cacheDir() . '/' . $file;
        $bundled = ADL_ROOT . '/app/Data/bot-ranges/' . $file;
        if (is_file($cached) && (time() - (int) filemtime($cached)) < self::RANGE_TTL) {
            $body = file_get_contents($cached);
            if (is_string($body) && $body !== '') {
                return $body;
            }
        }
        $body = is_file($bundled) ? file_get_contents($bundled) : false;

        return is_string($body) ? $body : '';
    }

    private static function cached(string $ip, string $kind): ?bool
    {
        $file = self::cacheDir() . '/hit-' . hash('sha256', $ip . '|' . $kind) . '.json';
        if (!is_file($file)) {
            return null;
        }
        $decoded = json_decode((string) file_get_contents($file), true);
        if (!is_array($decoded) || (int) ($decoded['exp'] ?? 0) < time()) {
            return null;
        }

        return !empty($decoded['ok']);
    }

    private static function remember(string $ip, string $kind, bool $ok): void
    {
        $file = self::cacheDir() . '/hit-' . hash('sha256', $ip . '|' . $kind) . '.json';
        $ttl = $ok ? self::SUCCESS_TTL : self::FAILURE_TTL;
        @file_put_contents($file, json_encode(['ok' => $ok, 'exp' => time() + $ttl]) ?: '{}', LOCK_EX);
    }

    private static function cacheDir(): string
    {
        $dir = ADL_ROOT . '/storage/cache/bot-ranges';
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }

        return $dir;
    }

    private static function isIp(string $ip): bool
    {
        return $ip !== '' && filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    private static function httpGet(string $url): ?string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 6,
                'ignore_errors' => true,
                'header' => "User-Agent: acteursdulivre-cron\r\n",
            ],
        ]);
        $body = @file_get_contents($url, false, $context);

        return is_string($body) && $body !== '' ? $body : null;
    }
}
