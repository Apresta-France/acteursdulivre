<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;

final class Profile
{
    public const TRADE_BOOKSTORE = 'Librairie';
    public const TRADE_BETA_READER = 'Bêta-lecture';

    public const TRADES = [
        'Écriture', 'Correction', 'Bêta-lecture', 'Illustration', 'Traduction', 'Maquette',
        'Édition', 'Impression', 'Presse & com', 'Librairie', 'Audio',
        'Agent littéraire', 'Salons',
        'Iconographie', 'Lecture éditoriale', 'Photographie', 'Reliure', 'Juridique',
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

    public const SOCIAL_NETWORKS = [
        'linkedin' => 'LinkedIn',
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'x' => 'X (Twitter)',
        'youtube' => 'YouTube',
        'tiktok' => 'TikTok',
        'bluesky' => 'Bluesky',
        'threads' => 'Threads',
        'mastodon' => 'Mastodon',
    ];

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_BUSY = 'busy';

    public const VERIFY_PENDING = 'pending';
    public const VERIFY_VERIFIED = 'verified';
    public const VERIFY_REFUSED = 'refused';

    /** @return list<array<string, mixed>> */
    public static function forAdmin(string $filter = 'pending'): array
    {
        $sql = 'SELECT p.id, p.user_id, p.slug, p.title, p.city, p.verification_status, p.updated_at,
                       p.verification_doc_path, p.verification_doc_name, p.verification_note,
                       p.trades_json, u.first_name, u.last_name, u.email, u.avatar_url, u.created_at, u.status AS user_status
                FROM profiles p
                JOIN users u ON u.id = p.user_id
                WHERE u.offers_services = 1';
        if ($filter === 'pending') {
            $sql .= ' AND (p.verification_status IS NULL OR p.verification_status = "" OR p.verification_status = "pending")';
        } elseif ($filter === 'verified' || $filter === 'refused') {
            $sql .= ' AND p.verification_status = ?';
        }
        $sql .= ' ORDER BY p.updated_at DESC, p.id DESC';
        $params = in_array($filter, ['verified', 'refused'], true) ? [$filter] : [];
        $rows = Database::fetchAll($sql, $params);
        return array_map(static function (array $row): array {
            $row['trades'] = self::decode($row['trades_json'] ?? null);
            $row['name'] = User::displayName($row);
            $row['doc_href'] = !empty($row['verification_doc_path'])
                ? '/admin/verifications/' . (int) $row['user_id'] . '/justificatif'
                : '';
            $row['status'] = (string) ($row['verification_status'] ?? '') ?: self::VERIFY_PENDING;
            $row['status_label'] = match ($row['status']) {
                self::VERIFY_VERIFIED => 'Vérifié',
                self::VERIFY_REFUSED => 'Refusé',
                default => 'En attente',
            };
            return $row;
        }, $rows);
    }

    public static function countPendingVerification(): int
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS n
             FROM profiles p
             JOIN users u ON u.id = p.user_id
             WHERE u.offers_services = 1
               AND (p.verification_status IS NULL OR p.verification_status = "" OR p.verification_status = "pending")'
        );
        return (int) ($row['n'] ?? 0);
    }

    public static function setVerification(int $userId, string $status): void
    {
        if (!in_array($status, [self::VERIFY_PENDING, self::VERIFY_VERIFIED, self::VERIFY_REFUSED], true)) {
            throw new \InvalidArgumentException('Statut de vérification invalide.');
        }
        $profile = self::findByUser($userId);
        if (!$profile) {
            throw new \RuntimeException('Profil introuvable.');
        }
        Database::query(
            'UPDATE profiles SET verification_status = ?, updated_at = NOW() WHERE user_id = ?',
            [$status, $userId]
        );
    }

    public static function storeVerificationDoc(int $userId, array $file, string $note = ''): void
    {
        $profile = self::ensure($userId);
        $stored = store_private_upload($file, 'verifications', ['pdf', 'jpg', 'jpeg', 'png', 'webp'], 8 * 1024 * 1024);
        if ($stored === null) {
            throw new \RuntimeException('Ajoutez un justificatif (PDF, JPG ou PNG, 8 Mo max).');
        }
        Database::query(
            'UPDATE profiles
             SET verification_doc_path = ?, verification_doc_name = ?, verification_note = ?,
                 verification_status = ?, updated_at = NOW()
             WHERE user_id = ?',
            [
                $stored['path'],
                $stored['name'] !== '' ? $stored['name'] : (string) ($file['name'] ?? 'justificatif'),
                trim($note) !== '' ? trim($note) : null,
                self::VERIFY_PENDING,
                $userId,
            ]
        );
        unset($profile);
    }

    public static function findByUser(int $userId): ?array
    {
        $row = Database::fetch(
            'SELECT p.*, u.first_name, u.last_name, u.avatar_url, u.offers_services, u.founder
             FROM profiles p
             JOIN users u ON u.id = p.user_id
             WHERE p.user_id = ?',
            [$userId]
        );
        return $row ? self::hydrate($row) : null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $row = Database::fetch(
            'SELECT p.*, u.first_name, u.last_name, u.avatar_url, u.offers_services, u.founder
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
                availability_status = ?,
                languages = ?, hourly_rate = ?, rate_note = ?, website = ?, socials_json = ?,
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
                self::normalizeStatus($data['availability_status'] ?? null),
                $data['languages'] ?? null,
                self::normalizeRate((string) ($data['hourly_rate'] ?? ''), (string) ($data['rate_kind'] ?? ''), $data['trades'] ?? []),
                $data['rate_note'] ?? null,
                $data['website'] ?? null,
                self::encode(self::normalizeSocials($data['socials'] ?? [])),
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

    /**
     * Met à jour seulement les champs fournis, sans écraser le reste de la vitrine.
     *
     * @param array<string, mixed> $data
     */
    public static function patch(int $userId, array $data): array
    {
        $current = self::ensure($userId);
        $merged = [
            'first_name' => $data['first_name'] ?? ($current['first_name'] ?? ''),
            'last_name' => $data['last_name'] ?? ($current['last_name'] ?? ''),
            'title' => array_key_exists('title', $data) ? $data['title'] : ($current['title'] ?? ''),
            'presentation' => array_key_exists('presentation', $data) ? $data['presentation'] : ($current['presentation'] ?? ''),
            'city' => array_key_exists('city', $data) ? $data['city'] : ($current['city'] ?? ''),
            'availability' => array_key_exists('availability', $data) ? $data['availability'] : ($current['availability'] ?? ''),
            'availability_status' => array_key_exists('availability_status', $data)
                ? $data['availability_status']
                : ($current['availability_status'] ?? self::STATUS_AVAILABLE),
            'languages' => array_key_exists('languages', $data) ? $data['languages'] : ($current['languages'] ?? ''),
            'hourly_rate' => array_key_exists('hourly_rate', $data) ? $data['hourly_rate'] : ($current['hourly_rate'] ?? ''),
            'rate_kind' => array_key_exists('rate_kind', $data) ? $data['rate_kind'] : ($current['rate_kind'] ?? ''),
            'rate_note' => array_key_exists('rate_note', $data) ? $data['rate_note'] : ($current['rate_note'] ?? ''),
            'website' => array_key_exists('website', $data) ? $data['website'] : ($current['website'] ?? ''),
            'socials' => array_key_exists('socials', $data) ? $data['socials'] : ($current['socials'] ?? []),
            'trades' => array_key_exists('trades', $data) ? $data['trades'] : ($current['trades'] ?? []),
            'skills' => array_key_exists('skills', $data) ? $data['skills'] : ($current['skills'] ?? []),
            'tools' => array_key_exists('tools', $data) ? $data['tools'] : ($current['tools'] ?? []),
            'genres' => array_key_exists('genres', $data) ? $data['genres'] : ($current['genres'] ?? []),
            'languages_list' => array_key_exists('languages_list', $data) ? $data['languages_list'] : ($current['languages_list'] ?? []),
            'experiences' => array_key_exists('experiences', $data) ? $data['experiences'] : ($current['experiences'] ?? []),
            'education' => array_key_exists('education', $data) ? $data['education'] : ($current['education'] ?? []),
        ];

        return self::save($userId, $merged);
    }

    public static function setAvailabilityStatus(int $userId, string $status): array
    {
        $profile = self::ensure($userId);
        Database::query(
            'UPDATE profiles SET availability_status = ?, updated_at = NOW() WHERE user_id = ?',
            [self::normalizeStatus($status), $userId]
        );

        return self::findByUser($userId) ?? $profile;
    }

    public static function normalizeStatus(mixed $value): string
    {
        return $value === self::STATUS_BUSY ? self::STATUS_BUSY : self::STATUS_AVAILABLE;
    }

    public static function isBusy(array $profile): bool
    {
        return self::normalizeStatus($profile['availability_status'] ?? null) === self::STATUS_BUSY;
    }

    public static function statusLabel(array|string $profileOrStatus): string
    {
        $status = is_array($profileOrStatus)
            ? self::normalizeStatus($profileOrStatus['availability_status'] ?? null)
            : self::normalizeStatus($profileOrStatus);

        return $status === self::STATUS_BUSY ? 'Occupé' : 'Disponible';
    }

    public static function availabilitySummary(array $profile): string
    {
        $label = self::statusLabel($profile);
        $note = trim((string) ($profile['availability'] ?? ''));
        return $note !== '' ? $label . ' · ' . $note : $label;
    }

    /** @return list<array<string, mixed>> */
    public static function searchPublished(): array
    {
        $rows = Database::fetchAll(
            'SELECT p.*, u.first_name, u.last_name, u.avatar_url
             FROM profiles p
             JOIN users u ON u.id = p.user_id
             WHERE u.offers_services = 1
               AND u.status = "active"
               AND NOT EXISTS (
                    SELECT 1 FROM invoices i
                    WHERE i.seller_id = u.id
                      AND i.status IN ("issued", "overdue")
                      AND i.due_at < NOW()
               )
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

    /** @return list<string> */
    public static function missingLabels(array $profile): array
    {
        $checks = [
            'un titre' => trim((string) ($profile['title'] ?? '')) !== '',
            'une présentation' => mb_strlen(trim((string) ($profile['presentation'] ?? ''))) >= 80,
            'une ville' => trim((string) ($profile['city'] ?? '')) !== '',
            'au moins un métier' => !empty($profile['trades']),
            'des compétences' => !empty($profile['skills']),
            'une spécialité' => !empty($profile['genres']),
            'des langues' => !empty($profile['languages_list']) || trim((string) ($profile['languages'] ?? '')) !== '',
            'un parcours' => !empty($profile['experiences']) || !empty($profile['education']),
            'une pièce de portfolio' => !empty($profile['portfolio']),
            'un tarif' => trim((string) ($profile['hourly_rate'] ?? '')) !== '',
        ];
        $missing = [];
        foreach ($checks as $label => $ok) {
            if (!$ok) {
                $missing[] = $label;
            }
        }
        return $missing;
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

    /** @param list<mixed> $trades */
    public static function isBookstore(array $profile = [], array $trades = []): bool
    {
        $list = $trades !== [] ? $trades : ($profile['trades'] ?? []);
        return in_array(self::TRADE_BOOKSTORE, $list, true);
    }

    public static function isPercentRate(array $profile): bool
    {
        $rate = trim((string) ($profile['hourly_rate'] ?? ''));
        if (str_contains($rate, '%')) {
            return true;
        }
        if (($profile['rate_kind'] ?? '') === 'percent') {
            return true;
        }
        return self::isBookstore($profile) && $rate !== '' && !str_contains($rate, '€');
    }

    /**
     * @param list<mixed> $trades
     */
    public static function normalizeRate(string $rate, string $kind = '', array $trades = []): ?string
    {
        $rate = trim($rate);
        if ($rate === '') {
            return null;
        }
        if ($kind !== 'percent' && $kind !== 'price') {
            $kind = self::isBookstore([], $trades) ? 'percent' : 'price';
        }
        if ($kind !== 'percent' && !str_contains($rate, '%')) {
            return $rate;
        }

        $raw = trim(str_replace(['%', ' '], '', str_replace(',', '.', $rate)));
        if (is_numeric($raw)) {
            $n = (float) $raw;
            $formatted = fmod($n, 1.0) === 0.0
                ? (string) (int) $n
                : rtrim(rtrim(number_format($n, 2, ',', ' '), '0'), ',');
            return $formatted . ' %';
        }

        return str_contains($rate, '%') ? $rate : $rate . ' %';
    }

    public static function rateKicker(array $profile): string
    {
        return self::isPercentRate($profile) ? 'Commission' : 'À partir de';
    }

    public static function formatRateSearch(array $profile): string
    {
        $rate = trim((string) ($profile['hourly_rate'] ?? ''));
        if ($rate === '') {
            return '';
        }
        return self::isPercentRate($profile) ? 'commission ' . $rate : 'à partir de ' . $rate;
    }

    /**
     * @param mixed $rows
     * @return list<array{network: string, label: string, url: string}>
     */
    public static function normalizeSocials(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $out = [];
        $seen = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $network = strtolower(trim((string) ($row['network'] ?? '')));
            if ($network === '' || !isset(self::SOCIAL_NETWORKS[$network]) || isset($seen[$network])) {
                continue;
            }
            $url = self::normalizeSocialUrl((string) ($row['url'] ?? ''), $network);
            if ($url === '') {
                continue;
            }
            $seen[$network] = true;
            $out[] = [
                'network' => $network,
                'label' => self::SOCIAL_NETWORKS[$network],
                'url' => $url,
            ];
        }

        return $out;
    }

    public static function normalizeSocialUrl(string $raw, string $network): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $raw) !== 1 && !str_contains($raw, '.')) {
            $handle = ltrim($raw, '@/');
            $handle = trim($handle);
            if ($handle === '' || preg_match('#^[A-Za-z0-9._-]+$#', $handle) !== 1) {
                return '';
            }
            $raw = match ($network) {
                'instagram' => 'https://www.instagram.com/' . $handle,
                'facebook' => 'https://www.facebook.com/' . $handle,
                'linkedin' => 'https://www.linkedin.com/in/' . $handle,
                'x' => 'https://x.com/' . $handle,
                'youtube' => 'https://www.youtube.com/@' . $handle,
                'tiktok' => 'https://www.tiktok.com/@' . $handle,
                'bluesky' => 'https://bsky.app/profile/' . $handle,
                'threads' => 'https://www.threads.net/@' . $handle,
                default => $raw,
            };
        }

        if (preg_match('#^https?://#i', $raw) !== 1) {
            $raw = 'https://' . ltrim($raw, '/');
        }

        if (filter_var($raw, FILTER_VALIDATE_URL) === false) {
            return '';
        }

        $parts = parse_url($raw);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true) || $host === '' || !str_contains($host, '.')) {
            return '';
        }

        return $raw;
    }

    /** @param array<string, mixed> $row */
    private static function hydrate(array $row): array
    {
        $row['socials'] = self::normalizeSocials(self::decode($row['socials_json'] ?? null));
        $row['trades'] = self::decode($row['trades_json'] ?? null);
        $row['skills'] = self::decode($row['skills_json'] ?? null);
        $row['tools'] = self::decode($row['tools_json'] ?? null);
        $row['genres'] = self::decode($row['genres_json'] ?? null);
        $row['languages_list'] = self::decode($row['languages_json'] ?? null);
        $row['experiences'] = self::decode($row['experiences_json'] ?? null);
        $row['education'] = self::decode($row['education_json'] ?? null);
        $row['portfolio'] = PortfolioItem::forProfile((int) $row['id']);
        $row['availability_status'] = self::normalizeStatus($row['availability_status'] ?? null);
        $row['is_busy'] = $row['availability_status'] === self::STATUS_BUSY;
        $row['availability_label'] = self::statusLabel($row);
        $row['availability_summary'] = self::availabilitySummary($row);
        $row['completion'] = self::completeness($row);
        $row['avatar_src'] = user_avatar_src($row);
        $row['is_founder'] = (int) ($row['founder'] ?? 0) === 1;
        $row['verification_status'] = (string) ($row['verification_status'] ?? '');
        $row['is_verified'] = $row['verification_status'] === self::VERIFY_VERIFIED;
        $row['verification_doc_href'] = !empty($row['verification_doc_path'])
            ? '/admin/verifications/' . (int) ($row['user_id'] ?? 0) . '/justificatif'
            : '';
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
