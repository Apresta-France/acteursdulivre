<?php

declare(strict_types=1);

namespace Adl\Core;

final class BotGuard
{
    public const OK = 'ok';
    public const HONEYPOT = 'honeypot';
    public const RETRY = 'retry';

    public const HONEYPOT_NAME = 'company_website';
    public const TOKEN_NAME = '_gate';
    public const RETRY_MESSAGE = 'La session du formulaire a expiré. Merci de renvoyer votre message.';
    public const TOO_MANY_MESSAGE = 'Trop de demandes. Réessayez plus tard.';

    public const TTL = 7200;
    public const FAST_SECONDS = 2;
    private const MAX_NONCES = 40;

    public static function issue(string $formId, ?int $issuedAt = null): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE || !self::validFormId($formId)) {
            return '';
        }
        $issuedAt ??= time();
        $nonce = bin2hex(random_bytes(16));
        self::remember($formId, $nonce, $issuedAt);

        return $formId . '|' . $issuedAt . '|' . $nonce . '|' . self::sign($formId, $issuedAt, $nonce);
    }

    public static function fields(string $formId): string
    {
        $token = self::issue($formId);
        $uid = 'ft-' . bin2hex(random_bytes(4));

        return '<div class="form-trap" aria-hidden="true">'
            . '<label for="' . e($uid) . '">Site web</label>'
            . '<input type="text" name="' . e(self::HONEYPOT_NAME) . '" id="' . e($uid)
            . '" value="" tabindex="-1" autocomplete="off">'
            . '</div>'
            . '<input type="hidden" name="' . e(self::TOKEN_NAME) . '" value="' . e($token) . '">';
    }

    /**
     * @return array{status: string, fast: bool}
     */
    public static function inspect(string $formId, ?string $token, ?string $honeypot, bool $consume = true): array
    {
        $parsed = self::parse($token);
        if ($parsed === null || $parsed['form'] !== $formId) {
            return ['status' => self::RETRY, 'fast' => false];
        }
        if (!hash_equals(self::sign($parsed['form'], $parsed['t'], $parsed['nonce']), $parsed['sig'])) {
            return ['status' => self::RETRY, 'fast' => false];
        }
        $age = time() - $parsed['t'];
        if ($age > self::TTL || $age < -30) {
            return ['status' => self::RETRY, 'fast' => false];
        }
        if ($consume && !self::takeNonce($formId, $parsed['nonce'])) {
            return ['status' => self::RETRY, 'fast' => false];
        }
        if (!$consume && !self::hasNonce($formId, $parsed['nonce'])) {
            return ['status' => self::RETRY, 'fast' => false];
        }
        $fast = $age < self::FAST_SECONDS;
        if (trim((string) $honeypot) !== '') {
            return ['status' => self::HONEYPOT, 'fast' => $fast];
        }

        return ['status' => self::OK, 'fast' => $fast];
    }

    public static function consume(string $formId, Request $request): string
    {
        return self::inspect(
            $formId,
            $request->string(self::TOKEN_NAME),
            $request->string(self::HONEYPOT_NAME),
            true
        )['status'];
    }

    public static function validFormId(string $formId): bool
    {
        return preg_match('/^[a-z0-9-]{1,40}$/', $formId) === 1;
    }

    private static function sign(string $formId, int $issuedAt, string $nonce): string
    {
        $payload = session_id() . '|' . $formId . '|' . $issuedAt . '|' . $nonce;

        return hash_hmac('sha256', $payload, self::key());
    }

    private static function key(): string
    {
        $key = (string) (Env::get('APP_KEY', '') ?: '');

        return $key !== '' ? $key : 'adl-form-gate';
    }

    /** @return array{form: string, t: int, nonce: string, sig: string}|null */
    private static function parse(?string $token): ?array
    {
        if (!is_string($token) || $token === '') {
            return null;
        }
        $parts = explode('|', $token);
        if (count($parts) !== 4) {
            return null;
        }
        [$formId, $issuedRaw, $nonce, $sig] = $parts;
        if (!self::validFormId($formId) || !ctype_digit($issuedRaw) || !ctype_xdigit($nonce) || !ctype_xdigit($sig)) {
            return null;
        }

        return [
            'form' => $formId,
            't' => (int) $issuedRaw,
            'nonce' => $nonce,
            'sig' => $sig,
        ];
    }

    private static function remember(string $formId, string $nonce, int $issuedAt): void
    {
        if (!isset($_SESSION['_bot_nonces']) || !is_array($_SESSION['_bot_nonces'])) {
            $_SESSION['_bot_nonces'] = [];
        }
        if (!isset($_SESSION['_bot_nonces'][$formId]) || !is_array($_SESSION['_bot_nonces'][$formId])) {
            $_SESSION['_bot_nonces'][$formId] = [];
        }
        $_SESSION['_bot_nonces'][$formId][$nonce] = $issuedAt;
        if (count($_SESSION['_bot_nonces'][$formId]) > self::MAX_NONCES) {
            asort($_SESSION['_bot_nonces'][$formId]);
            $_SESSION['_bot_nonces'][$formId] = array_slice($_SESSION['_bot_nonces'][$formId], -self::MAX_NONCES, null, true);
        }
    }

    private static function hasNonce(string $formId, string $nonce): bool
    {
        return isset($_SESSION['_bot_nonces'][$formId])
            && is_array($_SESSION['_bot_nonces'][$formId])
            && array_key_exists($nonce, $_SESSION['_bot_nonces'][$formId]);
    }

    private static function takeNonce(string $formId, string $nonce): bool
    {
        if (!self::hasNonce($formId, $nonce)) {
            return false;
        }
        unset($_SESSION['_bot_nonces'][$formId][$nonce]);

        return true;
    }
}
