<?php

declare(strict_types=1);

namespace Adl\Core;

use Adl\Models\Setting;
use RuntimeException;

final class OAuth
{
    public const PROVIDERS = ['google', 'facebook'];

    public static function enabled(string $provider): bool
    {
        $cfg = self::config($provider);
        return $cfg !== null && $cfg['client_id'] !== '' && $cfg['client_secret'] !== '';
    }

    /** @return list<string> */
    public static function enabledProviders(): array
    {
        $out = [];
        foreach (self::PROVIDERS as $provider) {
            if (self::enabled($provider)) {
                $out[] = $provider;
            }
        }
        return $out;
    }

    public static function label(string $provider): string
    {
        return match ($provider) {
            'google' => 'Google',
            'facebook' => 'Facebook',
            default => $provider,
        };
    }

    /** @return array<string, string>|null */
    public static function config(string $provider): ?array
    {
        return match ($provider) {
            'google' => [
                'label' => 'Google',
                'client_id' => self::cred('google_client_id', 'GOOGLE_CLIENT_ID'),
                'client_secret' => self::cred('google_client_secret', 'GOOGLE_CLIENT_SECRET'),
                'auth_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
                'token_url' => 'https://oauth2.googleapis.com/token',
                'scopes' => 'openid email profile',
            ],
            'facebook' => [
                'label' => 'Facebook',
                'client_id' => self::cred('facebook_app_id', 'FACEBOOK_APP_ID'),
                'client_secret' => self::cred('facebook_app_secret', 'FACEBOOK_APP_SECRET'),
                'auth_url' => 'https://www.facebook.com/v21.0/dialog/oauth',
                'token_url' => 'https://graph.facebook.com/v21.0/oauth/access_token',
                'scopes' => 'email,public_profile',
            ],
            default => null,
        };
    }

    public static function redirectUri(string $provider): string
    {
        $uri = url('/auth/' . $provider . '/callback');
        if (!str_starts_with($uri, 'http://') && !str_starts_with($uri, 'https://')) {
            $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
            $uri = ($https ? 'https' : 'http') . '://' . $host . $uri;
        }
        return $uri;
    }

    public static function authorizationUrl(string $provider): string
    {
        $cfg = self::config($provider);
        if ($cfg === null) {
            throw new RuntimeException('Fournisseur OAuth inconnu.');
        }
        $state = bin2hex(random_bytes(16));
        $_SESSION['_oauth_state'] = $state;
        $_SESSION['_oauth_provider'] = $provider;
        $_SESSION['_oauth_started'] = time();

        $query = [
            'client_id' => $cfg['client_id'],
            'redirect_uri' => self::redirectUri($provider),
            'response_type' => 'code',
            'scope' => $cfg['scopes'],
            'state' => $state,
        ];
        if ($provider === 'google') {
            $query['access_type'] = 'online';
            $query['prompt'] = 'select_account';
        }

        return $cfg['auth_url'] . '?' . http_build_query($query);
    }

    public static function consumeState(string $provider, string $state): bool
    {
        $ok = $state !== ''
            && hash_equals((string) ($_SESSION['_oauth_state'] ?? ''), $state)
            && ($_SESSION['_oauth_provider'] ?? '') === $provider
            && (time() - (int) ($_SESSION['_oauth_started'] ?? 0)) < 600;
        unset($_SESSION['_oauth_state'], $_SESSION['_oauth_provider'], $_SESSION['_oauth_started']);
        return $ok;
    }

    /** @return array{provider: string, provider_id: string, email: string, first_name: string, last_name: string, avatar_url: string} */
    public static function fetchProfile(string $provider, string $code): array
    {
        $cfg = self::config($provider);
        if ($cfg === null || $code === '') {
            throw new RuntimeException('Échange OAuth impossible.');
        }

        $token = self::http('POST', $cfg['token_url'], [
            'client_id' => $cfg['client_id'],
            'client_secret' => $cfg['client_secret'],
            'redirect_uri' => self::redirectUri($provider),
            'code' => $code,
            'grant_type' => 'authorization_code',
        ]);
        $access = (string) ($token['access_token'] ?? '');
        if ($access === '') {
            throw new RuntimeException('Jeton OAuth manquant.');
        }

        $raw = $provider === 'facebook'
            ? self::http('GET', 'https://graph.facebook.com/v21.0/me?' . http_build_query([
                'fields' => 'id,first_name,last_name,name,email,picture.type(large)',
                'access_token' => $access,
            ]))
            : self::http('GET', 'https://openidconnect.googleapis.com/v1/userinfo', [], [
                'Authorization: Bearer ' . $access,
            ]);

        return self::normalize($provider, $raw);
    }

    public static function column(string $provider): string
    {
        return $provider === 'facebook' ? 'facebook_id' : 'google_id';
    }

    private static function cred(string $settingKey, string $envKey): string
    {
        try {
            $fromDb = Setting::get($settingKey, '');
            if (is_string($fromDb) && $fromDb !== '') {
                return $fromDb;
            }
        } catch (\Throwable) {
        }
        return trim((string) Env::get($envKey, ''));
    }

    /** @param array<string, mixed> $raw */
    private static function normalize(string $provider, array $raw): array
    {
        if ($provider === 'facebook') {
            $id = (string) ($raw['id'] ?? '');
            $email = strtolower(trim((string) ($raw['email'] ?? '')));
            $first = trim((string) ($raw['first_name'] ?? ''));
            $last = trim((string) ($raw['last_name'] ?? ''));
            $name = trim((string) ($raw['name'] ?? ''));
            $picture = $raw['picture']['data']['url'] ?? '';
        } else {
            $id = (string) ($raw['sub'] ?? '');
            $email = strtolower(trim((string) ($raw['email'] ?? '')));
            $first = trim((string) ($raw['given_name'] ?? ''));
            $last = trim((string) ($raw['family_name'] ?? ''));
            $name = trim((string) ($raw['name'] ?? ''));
            $picture = (string) ($raw['picture'] ?? '');
        }

        if ($id === '') {
            throw new RuntimeException('Identifiant prestataire manquant.');
        }
        if ($first === '' && $name !== '') {
            $parts = preg_split('/\s+/', $name) ?: [];
            $first = (string) array_shift($parts);
            if ($last === '') {
                $last = implode(' ', $parts);
            }
        }
        if ($first === '') {
            $first = $email !== '' ? explode('@', $email)[0] : 'Membre';
        }
        if ($last === '') {
            $last = '—';
        }

        $avatar = is_string($picture) ? trim($picture) : '';
        if ($avatar !== '' && !preg_match('#^https://#i', $avatar)) {
            $avatar = '';
        }

        return [
            'provider' => $provider,
            'provider_id' => $id,
            'email' => $email,
            'first_name' => mb_substr($first, 0, 120),
            'last_name' => mb_substr($last, 0, 120),
            'avatar_url' => mb_substr($avatar, 0, 1024),
        ];
    }

    /**
     * @param array<string, string> $fields
     * @param list<string> $headers
     * @return array<string, mixed>
     */
    private static function http(string $method, string $url, array $fields = [], array $headers = []): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('L\'extension PHP cURL est requise pour la connexion sociale.');
        }

        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER => array_merge(['Accept: application/json'], $headers),
        ];
        if ($method === 'POST') {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = http_build_query($fields);
        }
        curl_setopt_array($ch, $opts);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($body === false || $status >= 400) {
            throw new RuntimeException($err !== '' ? $err : 'Réponse OAuth invalide.');
        }
        $json = json_decode((string) $body, true);
        if (!is_array($json)) {
            throw new RuntimeException('Réponse OAuth illisible.');
        }
        return $json;
    }
}
