<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;

final class Profile
{
    public const TRADES = [
        'Correction', 'Illustration', 'Traduction', 'Maquette',
        'Édition', 'Impression', 'Presse & com', 'Librairie', 'Audio',
    ];

    public const GENRES = [
        'Roman', 'Polar', 'Essai', 'Jeunesse', 'BD & graphique',
        'Poésie', 'Théâtre', 'Sciences humaines', 'Pratique', 'Livre audio',
    ];

    public const SKILL_LEVELS = ['Initiée', 'Confirmée', 'Experte'];

    public const LANG_LEVELS = ['Langue de travail', 'Courant', 'Lu', 'Natif'];

    public const PORTFOLIO_KINDS = [
        'creation' => 'Création réalisée',
        'example' => 'Exemple / extrait',
        'book' => 'Ouvrage publié',
    ];

    public static function findByUser(int $userId): ?array
    {
        $row = Database::fetch('SELECT * FROM profiles WHERE user_id = ?', [$userId]);
        return $row ? self::hydrate($row) : null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $row = Database::fetch(
            'SELECT p.*, u.first_name, u.last_name, u.offers_services
             FROM profiles p
             JOIN users u ON u.id = p.user_id
             WHERE p.slug = ?',
            [$slug]
        );
        return $row ? self::hydrate($row) : null;
    }

    public static function ensure(int $userId): array
    {
        User::ensureProfile($userId);
        $profile = self::findByUser($userId);
        if ($profile) {
            return $profile;
        }
        throw new \RuntimeException('Profil introuvable.');
    }

    public static function save(int $userId, array $data): array
    {
        $profile = self::ensure($userId);
        $slug = (string) ($profile['slug'] ?? '');
        if ($slug === '') {
            $slug = unique_slug(
                trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')) ?: 'profil',
                static fn (string $candidate): bool => Database::fetch(
                    'SELECT id FROM profiles WHERE slug = ? AND user_id != ?',
                    [$candidate, $userId]
                ) !== null
            );
        }

        Database::query(
            'UPDATE profiles SET
                slug = ?, title = ?, presentation = ?, city = ?, availability = ?,
                languages = ?, hourly_rate = ?, rate_note = ?, website = ?,
                trades_json = ?, skills_json = ?, tools_json = ?, genres_json = ?,
                languages_json = ?, experiences_json = ?, education_json = ?,
                updated_at = NOW()
             WHERE user_id = ?',
            [
                $slug,
                $data['title'] ?? null,
                $data['presentation'] ?? null,
                $data['city'] ?? null,
                $data['availability'] ?? null,
                $data['languages'] ?? null,
                $data['hourly_rate'] ?? null,
                $data['rate_note'] ?? null,
                $data['website'] ?? null,
                self::encode($data['trades'] ?? []),
                self::encode($data['skills'] ?? []),
                self::encode($data['tools'] ?? []),
                self::encode($data['genres'] ?? []),
                self::encode($data['languages_list'] ?? []),
                self::encode($data['experiences'] ?? []),
                self::encode($data['education'] ?? []),
                $userId,
            ]
        );

        return self::findByUser($userId) ?? $profile;
    }

    /** @return list<array<string, mixed>> */
    public static function searchPublished(): array
    {
        $rows = Database::fetchAll(
            'SELECT p.*, u.first_name, u.last_name
             FROM profiles p
             JOIN users u ON u.id = p.user_id
             WHERE u.offers_services = 1
               AND u.status = "active"
               AND (
                    (p.title IS NOT NULL AND p.title != "")
                 OR (p.presentation IS NOT NULL AND p.presentation != "")
                 OR (p.trades_json IS NOT NULL AND p.trades_json != "" AND p.trades_json != "[]")
               )
             ORDER BY p.updated_at DESC, p.id DESC'
        );

        return array_map([self::class, 'hydrate'], $rows);
    }

    public static function completeness(array $profile): int
    {
        $checks = [
            trim((string) ($profile['title'] ?? '')) !== '',
            mb_strlen(trim((string) ($profile['presentation'] ?? ''))) >= 80,
            trim((string) ($profile['city'] ?? '')) !== '',
            !empty($profile['trades']),
            !empty($profile['skills']),
            !empty($profile['genres']),
            !empty($profile['languages_list']) || trim((string) ($profile['languages'] ?? '')) !== '',
            !empty($profile['experiences']) || !empty($profile['education']),
            !empty($profile['portfolio']),
            trim((string) ($profile['hourly_rate'] ?? '')) !== '',
        ];
        $done = count(array_filter($checks));
        return (int) round($done / max(count($checks), 1) * 100);
    }

    public static function initials(array $profile): string
    {
        if (!empty($profile['first_name']) || !empty($profile['last_name'])) {
            return User::initials($profile);
        }
        return 'AD';
    }

    public static function displayName(array $profile): string
    {
        $name = trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
        return $name !== '' ? $name : 'Profil';
    }

    public static function publicHref(array $profile): string
    {
        $slug = (string) ($profile['slug'] ?? '');
        return $slug !== '' ? '/prestataires/' . $slug : '/recherche';
    }

    /** @param array<string, mixed> $row */
    private static function hydrate(array $row): array
    {
        $row['trades'] = self::decode($row['trades_json'] ?? null);
        $row['skills'] = self::decode($row['skills_json'] ?? null);
        $row['tools'] = self::decode($row['tools_json'] ?? null);
        $row['genres'] = self::decode($row['genres_json'] ?? null);
        $row['languages_list'] = self::decode($row['languages_json'] ?? null);
        $row['experiences'] = self::decode($row['experiences_json'] ?? null);
        $row['education'] = self::decode($row['education_json'] ?? null);
        $row['portfolio'] = PortfolioItem::forProfile((int) $row['id']);
        $row['completion'] = self::completeness($row);
        return $row;
    }

    /** @param mixed $value */
    private static function encode(mixed $value): string
    {
        return json_encode(is_array($value) ? array_values($value) : [], JSON_UNESCAPED_UNICODE) ?: '[]';
    }

    /** @return list<mixed> */
    private static function decode(mixed $json): array
    {
        if (!is_string($json) || $json === '') {
            return [];
        }
        $data = json_decode($json, true);
        return is_array($data) ? array_values($data) : [];
    }
}
