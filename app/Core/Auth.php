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

    public static function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);
        $hash = (string) ($user['password'] ?? '');
        if (!$user || $hash === '' || !password_verify($password, $hash)) {
            return false;
        }
        if (($user['status'] ?? 'active') !== 'active') {
            return false;
        }
        self::login($user);
        return true;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['_user_cache'] = $user;
        User::touchLastLogin((int) $user['id']);
    }

    public static function logout(): void
    {
        unset($_SESSION['user_id'], $_SESSION['_user_cache']);
        session_regenerate_id(true);
    }

    public static function requireUser(): array
    {
        $user = self::user();
        if (!$user) {
            $_SESSION['_intended'] = $_SERVER['REQUEST_URI'] ?? '/espace';
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
}
