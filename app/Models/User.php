<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;

final class User
{
    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::fetch('SELECT * FROM users WHERE email = ?', [strtolower($email)]);
    }

    public static function create(array $data): int
    {
        $role = $data['role'] ?? 'client';
        $seeks = self::normalizeFlag($data['seeks_services'] ?? null, $role === 'client' || $role === 'admin');
        $offers = self::normalizeFlag($data['offers_services'] ?? null, $role === 'prestataire' || $role === 'admin');
        if ($role === 'admin') {
            $seeks = 1;
            $offers = 1;
        }
        if ($role !== 'admin') {
            $role = $offers ? 'prestataire' : 'client';
        }

        Database::query(
            'INSERT INTO users (email, password, first_name, last_name, role, seeks_services, offers_services, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())',
            [
                strtolower($data['email']),
                password_hash($data['password'], PASSWORD_DEFAULT),
                $data['first_name'],
                $data['last_name'],
                $role,
                $seeks,
                $offers,
                $data['status'] ?? 'active',
            ]
        );
        $id = (int) Database::lastId();
        if ($offers) {
            self::ensureProfile($id);
        }
        return $id;
    }

    public static function update(int $id, array $data): void
    {
        $fields = [];
        $params = [];
        foreach ($data as $key => $value) {
            $fields[] = $key . ' = ?';
            $params[] = $value;
        }
        $params[] = $id;
        Database::query('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?', $params);
        unset($_SESSION['_user_cache']);
    }

    public static function touchLastLogin(int $id): void
    {
        Database::query('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$id]);
    }

    public static function all(): array
    {
        return Database::fetchAll('SELECT id, email, first_name, last_name, role, seeks_services, offers_services, status, created_at FROM users ORDER BY id DESC');
    }

    public static function initials(array $user): string
    {
        $a = mb_strtoupper(mb_substr((string) ($user['first_name'] ?? ''), 0, 1));
        $b = mb_strtoupper(mb_substr((string) ($user['last_name'] ?? ''), 0, 1));
        return $a . $b ?: 'AD';
    }

    public static function displayName(array $user): string
    {
        return trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
    }

    public static function seeksServices(?array $user): bool
    {
        if (!$user) {
            return false;
        }
        if (array_key_exists('seeks_services', $user)) {
            return (int) $user['seeks_services'] === 1;
        }
        return in_array($user['role'] ?? '', ['client', 'admin'], true);
    }

    public static function offersServices(?array $user): bool
    {
        if (!$user) {
            return false;
        }
        if (array_key_exists('offers_services', $user)) {
            return (int) $user['offers_services'] === 1;
        }
        return in_array($user['role'] ?? '', ['prestataire', 'admin'], true);
    }

    public static function roleFromIntents(bool $seeks, bool $offers): string
    {
        return $offers ? 'prestataire' : 'client';
    }

    public static function usageLabel(array $user): string
    {
        $seeks = self::seeksServices($user);
        $offers = self::offersServices($user);
        if ($seeks && $offers) {
            return 'Cherche et propose';
        }
        if ($offers) {
            return 'Propose des services';
        }
        if ($seeks) {
            return 'Cherche des prestataires';
        }
        return 'Compte';
    }

    public static function ensureProfile(int $userId): void
    {
        $exists = Database::fetch('SELECT id FROM profiles WHERE user_id = ?', [$userId]);
        if ($exists) {
            return;
        }
        $user = self::find($userId);
        $slug = unique_slug(
            trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'profil',
            static fn (string $candidate): bool => Database::fetch('SELECT id FROM profiles WHERE slug = ?', [$candidate]) !== null
        );
        Database::query('INSERT INTO profiles (user_id, slug) VALUES (?, ?)', [$userId, $slug]);
    }

    private static function normalizeFlag(mixed $value, bool $fallback): int
    {
        if ($value === null) {
            return $fallback ? 1 : 0;
        }
        return ($value === true || $value === 1 || $value === '1' || $value === 'on') ? 1 : 0;
    }
}
