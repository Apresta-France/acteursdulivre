<?php

declare(strict_types=1);

namespace Adl\Core;

use Adl\Models\User;

final class Auth
{
    public static function user(): ?array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }
        $id = $_SESSION['user_id'] ?? null;
        if (!$id) {
            $id = self::resumeRemember();
        }
        if (!$id) {
            return null;
        }
        $user = User::find((int) $id);
        if (!$user || ($user['status'] ?? 'active') !== 'active') {
            if (self::isImpersonating() && self::stopImpersonating()) {
                return self::user();
            }
            self::logout();
            return null;
        }
        if (self::isImpersonating() && self::impersonator() === null) {
            self::logout();
            return null;
        }
        if (!self::credentialsMatch($user)) {
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
        $fresh = User::find((int) $user['id']);
        if ($fresh) {
            $user = $fresh;
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['_user_cache'] = $user;
        self::stampCredentials($user);
        unset($_SESSION['_oauth_pending'], $_SESSION['_oauth_return'], $_SESSION['_impersonator_id']);
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
        unset($_SESSION['user_id'], $_SESSION['_user_cache'], $_SESSION['_impersonator_id'], $_SESSION['_auth_pw']);
        session_regenerate_id(true);
    }

    public static function impersonate(array $target, array $admin): void
    {
        $adminId = (int) ($admin['id'] ?? 0);
        $targetId = (int) ($target['id'] ?? 0);
        if ($adminId < 1 || $targetId < 1) {
            throw new \RuntimeException('Impersonnation impossible.');
        }
        if (($admin['role'] ?? '') !== 'admin') {
            throw new \RuntimeException('Seuls les administrateurs peuvent ouvrir une session à la place d’un autre compte.');
        }
        if ($adminId === $targetId) {
            throw new \RuntimeException('Vous ne pouvez pas vous connecter à la place de votre propre compte.');
        }
        if (self::isImpersonating()) {
            throw new \RuntimeException('Terminez d’abord l’impersonnation en cours.');
        }
        if (($target['status'] ?? '') !== 'active' || User::isClosed($target)) {
            throw new \RuntimeException('Ce compte n’est pas accessible (inactif, suspendu ou clôturé).');
        }

        session_regenerate_id(true);
        $_SESSION['_impersonator_id'] = $adminId;
        $_SESSION['user_id'] = $targetId;
        $_SESSION['_user_cache'] = $target;
        self::stampCredentials($target);
    }

    public static function isImpersonating(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        return (int) ($_SESSION['_impersonator_id'] ?? 0) > 0;
    }

    public static function impersonator(): ?array
    {
        $id = (int) ($_SESSION['_impersonator_id'] ?? 0);
        if ($id < 1) {
            return null;
        }
        $admin = User::find($id);
        if (!$admin || ($admin['role'] ?? '') !== 'admin' || ($admin['status'] ?? '') !== 'active' || User::isClosed($admin)) {
            return null;
        }
        return $admin;
    }

    public static function stopImpersonating(): bool
    {
        $adminId = (int) ($_SESSION['_impersonator_id'] ?? 0);
        if ($adminId < 1) {
            return false;
        }
        $admin = User::find($adminId);
        unset($_SESSION['_impersonator_id'], $_SESSION['_user_cache']);
        if (!$admin || ($admin['role'] ?? '') !== 'admin' || ($admin['status'] ?? '') !== 'active' || User::isClosed($admin)) {
            unset($_SESSION['user_id']);
            session_regenerate_id(true);
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = $adminId;
        $_SESSION['_user_cache'] = $admin;
        self::stampCredentials($admin);
        return true;
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

    public static function refreshStamp(?array $user = null): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }
        if ($user === null) {
            $id = (int) ($_SESSION['user_id'] ?? 0);
            $user = $id > 0 ? User::find($id) : null;
        }
        if ($user) {
            self::stampCredentials($user);
        }
    }

    /** @param array<string, mixed> $user */
    private static function stampCredentials(array $user): void
    {
        $_SESSION['_auth_pw'] = (string) ($user['password'] ?? '');
    }

    /** @param array<string, mixed> $user */
    private static function credentialsMatch(array $user): bool
    {
        if (!array_key_exists('_auth_pw', $_SESSION)) {
            self::stampCredentials($user);
            return true;
        }
        return hash_equals((string) $_SESSION['_auth_pw'], (string) ($user['password'] ?? ''));
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
        $user = User::find($userId);
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        unset($_SESSION['_oauth_pending'], $_SESSION['_oauth_return']);
        if ($user) {
            self::stampCredentials($user);
        }
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
            'secure' => request_is_https(),
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
            'secure' => request_is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE['adl_remember']);
    }
}
