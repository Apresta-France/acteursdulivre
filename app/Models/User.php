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

    public static function findByProvider(string $provider, string $providerId): ?array
    {
        $col = $provider === 'facebook' ? 'facebook_id' : 'google_id';
        if ($providerId === '') {
            return null;
        }
        return Database::fetch('SELECT * FROM users WHERE ' . $col . ' = ?', [$providerId]);
    }

    public static function linkProvider(int $id, string $provider, string $providerId, ?string $avatarUrl = null): void
    {
        $col = $provider === 'facebook' ? 'facebook_id' : 'google_id';
        $data = [$col => $providerId];
        if (is_string($avatarUrl) && $avatarUrl !== '') {
            $user = self::find($id);
            if ($user && empty($user['avatar_url'])) {
                $data['avatar_url'] = $avatarUrl;
            }
        }
        self::update($id, $data);
    }

    public static function isOauthOnly(array $user): bool
    {
        $hash = (string) ($user['password'] ?? '');
        return $hash === '' && (
            (string) ($user['google_id'] ?? '') !== ''
            || (string) ($user['facebook_id'] ?? '') !== ''
        );
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

        $password = (string) ($data['password'] ?? '');
        $hash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null;

        Database::query(
            'INSERT INTO users (email, password, first_name, last_name, role, seeks_services, offers_services, status, google_id, facebook_id, avatar_url, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
            [
                strtolower($data['email']),
                $hash,
                $data['first_name'],
                $data['last_name'],
                $role,
                $seeks,
                $offers,
                $data['status'] ?? 'active',
                $data['google_id'] ?? null,
                $data['facebook_id'] ?? null,
                $data['avatar_url'] ?? null,
            ]
        );
        $id = (int) Database::lastId();
        self::maybeGrantFounder($id);
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

    public static function countAll(): int
    {
        $row = Database::fetch('SELECT COUNT(*) AS n FROM users');
        return (int) ($row['n'] ?? 0);
    }

    public static function countOfferers(): int
    {
        $row = Database::fetch('SELECT COUNT(*) AS n FROM users WHERE offers_services = 1 AND status = "active"');
        return (int) ($row['n'] ?? 0);
    }

    public static function countSeekers(): int
    {
        $row = Database::fetch('SELECT COUNT(*) AS n FROM users WHERE seeks_services = 1 AND status = "active"');
        return (int) ($row['n'] ?? 0);
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

    public static function isFounder(int|array|null $user): bool
    {
        if (is_array($user)) {
            if (array_key_exists('founder', $user)) {
                return (int) $user['founder'] === 1;
            }
            $user = (int) ($user['id'] ?? 0);
        }
        $id = (int) ($user ?? 0);
        if ($id < 1) {
            return false;
        }
        try {
            $row = Database::fetch('SELECT founder FROM users WHERE id = ?', [$id]);
        } catch (\Throwable) {
            return false;
        }
        return (int) ($row['founder'] ?? 0) === 1;
    }

    private static function maybeGrantFounder(int $id): void
    {
        try {
            $limit = Commission::founderLimit();
            Database::query(
                'UPDATE users SET founder = 1
                 WHERE id = ?
                   AND (SELECT n FROM (SELECT COUNT(*) AS n FROM users WHERE founder = 1) AS taken) < ?',
                [$id, $limit]
            );
        } catch (\Throwable) {
        }
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

    public static function onboardingPending(array $user): bool
    {
        return empty($user['onboarding_done_at']);
    }

    public static function markOnboardingDone(int $id): void
    {
        self::update($id, ['onboarding_done_at' => date('Y-m-d H:i:s')]);
    }

    /** @param array<string, mixed> $file */
    public static function storeAvatar(int $userId, array $file): ?string
    {
        $stored = store_upload($file, 'avatars', ['jpg', 'jpeg', 'png', 'webp'], 2 * 1024 * 1024);
        if ($stored === null) {
            return null;
        }
        self::update($userId, ['avatar_url' => $stored]);
        return $stored;
    }

    public static function ensureProfile(int $userId): void
    {
        if ($userId < 1) {
            throw new \RuntimeException('Impossible de créer une vitrine : identifiant utilisateur manquant.');
        }
        $exists = Database::fetch('SELECT id FROM profiles WHERE user_id = ?', [$userId]);
        if ($exists) {
            return;
        }
        $user = self::find($userId);
        if (!$user) {
            throw new \RuntimeException('Impossible de créer une vitrine : ce compte n\'existe plus. Reconnectez-vous.');
        }
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
