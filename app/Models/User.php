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
        Database::query(
            'INSERT INTO users (email, password, first_name, last_name, role, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())',
            [
                strtolower($data['email']),
                password_hash($data['password'], PASSWORD_DEFAULT),
                $data['first_name'],
                $data['last_name'],
                $data['role'] ?? 'client',
                $data['status'] ?? 'active',
            ]
        );
        return (int) Database::lastId();
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
        return Database::fetchAll('SELECT id, email, first_name, last_name, role, status, created_at FROM users ORDER BY id DESC');
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
}
