<?php

declare(strict_types=1);

namespace Adl\Core;

use Adl\Models\User;

final class Auth
{
    public static function user(): ?array
    {
        $id = $_SESSION['user_id'] ?? null;
        if (!$id) {
            $id = self::resumeRemember();
        }
        if (!$id) {
            return null;
        }
        $user = User::find((int) $id);
        if (!$user || ($user['status'] ?? 'active') !== 'active') {
            self::logout();
            return null;
        }
        $_SESSION['_user_cache'] = $user;
        return $user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user ? (int) $user['id'] : null;
    }

    public static function attempt(string $email, string $password, bool $remember = false): bool
    {
        $user = User::findByEmail($email);
        $hash = (string) ($user['password'] ?? '');
        if (!$user || $hash === '' || !password_verify($password, $hash)) {
            return false;
        }
        if (($user['status'] ?? 'active') !== 'active') {
            return false;
        }
        self::login($user, $remember);
        return true;
    }

    public static function login(array $user, bool $remember = false): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['_user_cache'] = $user;
        unset($_SESSION['_oauth_pending']);
        User::touchLastLogin((int) $user['id']);
        self::forgetRemember();
        if ($remember) {
            self::issueRemember((int) $user['id']);
        }
        if (($user['role'] ?? '') !== 'admin') {
            try {
                \Adl\Models\Analytics::action('connexion');
            } catch (\Throwable) {
            }
        }
    }

    public static function logout(): void
    {
        self::forgetRemember();
        unset($_SESSION['user_id'], $_SESSION['_user_cache']);
        session_regenerate_id(true);
    }

    public static function requireUser(): array
    {
        $user = self::user();
        if (!$user) {
            $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/espace');
            if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) === 'POST') {
                $path = parse_url($uri, PHP_URL_PATH) ?: '/espace';
                if (preg_match('#^(/missions/[^/]+)/candidater$#', $path, $m)) {
                    $uri = $m[1];
                } else {
                    $uri = $path;
                }
            }
            $_SESSION['_intended'] = $uri;
            $avec = (int) ($_POST['avec'] ?? 0);
            if ($avec > 0) {
                $_SESSION['_pending_message'] = [
                    'avec' => $avec,
                    'sujet' => trim((string) ($_POST['sujet'] ?? '')),
                    'prestation' => (int) ($_POST['prestation'] ?? 0),
                    'mission' => (int) ($_POST['mission'] ?? 0),
                ];
            }
            redirect('/connexion');
        }
        return $user;
    }

    public static function requireAdmin(): array
    {
        $user = self::requireUser();
        if (($user['role'] ?? '') !== 'admin') {
            http_response_code(403);
            View::render('errors/403', ['title' => 'Accès refusé']);
            exit;
        }
        return $user;
    }

    public static function requireSeeker(): array
    {
        $user = self::requireUser();
        if (!User::seeksServices($user)) {
            flash('error', 'Cette action est disponible si vous cherchez des prestataires. Vous pouvez l\'activer dans vos paramètres.');
            redirect('/espace');
        }
        return $user;
    }

    public static function requireOfferer(): array
    {
        $user = self::requireUser();
        if (!User::offersServices($user)) {
            flash('error', 'Cette action est disponible si vous proposez vos services. Vous pouvez l\'activer dans vos paramètres.');
            redirect('/espace');
        }
        return $user;
    }

    private static function issueRemember(int $userId): void
    {
        try {
            $selector = bin2hex(random_bytes(9));
            $token = bin2hex(random_bytes(32));
            Database::query(
                'INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at, created_at)
                 VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY), NOW())',
                [$userId, $selector, hash('sha256', $token)]
            );
            self::setRememberCookie($selector . ':' . $token);
        } catch (\Throwable) {
        }
    }

    private static function resumeRemember(): ?int
    {
        $raw = (string) ($_COOKIE['adl_remember'] ?? '');
        if (!str_contains($raw, ':')) {
            return null;
        }
        [$selector, $token] = explode(':', $raw, 2);
        if ($selector === '' || $token === '') {
            return null;
        }
        try {
            $row = Database::fetch(
                'SELECT user_id, token_hash, expires_at FROM remember_tokens WHERE selector = ?',
                [$selector]
            );
        } catch (\Throwable) {
            return null;
        }
        if (!$row || strtotime((string) $row['expires_at']) < time()) {
            self::clearRememberCookie();
            return null;
        }
        if (!hash_equals((string) $row['token_hash'], hash('sha256', $token))) {
            self::clearRememberCookie();
            return null;
        }
        $userId = (int) $row['user_id'];
        try {
            Database::query('DELETE FROM remember_tokens WHERE selector = ?', [$selector]);
        } catch (\Throwable) {
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        self::issueRemember($userId);
        return $userId;
    }

    private static function forgetRemember(): void
    {
        $raw = (string) ($_COOKIE['adl_remember'] ?? '');
        if (str_contains($raw, ':')) {
            $selector = explode(':', $raw, 2)[0];
            try {
                Database::query('DELETE FROM remember_tokens WHERE selector = ?', [$selector]);
            } catch (\Throwable) {
            }
        }
        self::clearRememberCookie();
    }

    private static function setRememberCookie(string $value): void
    {
        setcookie('adl_remember', $value, [
            'expires' => time() + 60 * 60 * 24 * 30,
            'path' => '/',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_COOKIE['adl_remember'] = $value;
    }

    private static function clearRememberCookie(): void
    {
        setcookie('adl_remember', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE['adl_remember']);
    }
}
