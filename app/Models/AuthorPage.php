<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;

final class AuthorPage
{
    public const GENRES = [
        'Roman', 'Nouvelle', 'Poésie', 'Théâtre', 'Essai', 'Biographie / mémoires', 'Récit / témoignage',
        'Jeunesse', 'Young adult', 'Album illustré', 'Bande dessinée', 'Roman graphique', 'Polar / thriller',
        'Science-fiction', 'Fantasy / fantastique', 'Romance', 'Historique', 'Humour', 'Pratique / bien-être',
        'Beau livre', 'Scolaire / universitaire', 'Régionalisme', 'Spiritualité', 'Autre',
    ];

    public const OPEN_TO = [
        'dedicaces' => 'Dédicaces et salons du livre',
        'rencontres' => 'Rencontres en librairie ou bibliothèque',
        'scolaire' => 'Interventions scolaires',
        'ateliers' => 'Ateliers d\'écriture',
        'lectures' => 'Lectures publiques',
        'interviews' => 'Interviews et presse',
        'residences' => 'Résidences d\'écriture',
        'collectifs' => 'Projets collectifs et anthologies',
    ];

    public const LINK_KINDS = [
        'site' => 'Site ou blog',
        'editeur' => 'Page chez l\'éditeur',
        'babelio' => 'Babelio',
        'goodreads' => 'Goodreads',
        'librairie' => 'Librairie en ligne',
        'podcast' => 'Podcast ou vidéo',
        'newsletter' => 'Lettre d\'information',
        'reseau' => 'Réseau social',
        'autre' => 'Autre lien',
    ];

    public const BIO_MAX = 12000;
    public const SHORT_BIO_MAX = 500;

    private const SELECT = 'SELECT a.*, u.first_name, u.last_name, u.avatar_url, u.status AS user_status,
                u.offers_services, u.founder, u.platform_cofounder, u.created_at AS member_since,
                p.slug AS profile_slug, p.city AS profile_city, p.title AS profile_title
         FROM author_pages a
         JOIN users u ON u.id = a.user_id
         LEFT JOIN profiles p ON p.user_id = a.user_id';

    public static function findByUser(int $userId): ?array
    {
        $row = Database::fetch(self::SELECT . ' WHERE a.user_id = ?', [$userId]);
        return $row ? self::hydrate($row) : null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $row = Database::fetch(self::SELECT . ' WHERE a.slug = ?', [$slug]);
        return $row ? self::hydrate($row) : null;
    }

    /** @return array<string, mixed> */
    public static function ensure(int $userId): array
    {
        $existing = self::findByUser($userId);
        if ($existing) {
            return $existing;
        }
        $user = User::find($userId);
        if (!$user) {
            throw new \RuntimeException('Compte introuvable.');
        }
        $slug = self::freeSlug(User::displayName($user) ?: 'auteur', $userId);
        Database::query(
            'INSERT INTO author_pages (user_id, slug, enabled) VALUES (?, ?, 0)',
            [$userId, $slug]
        );
        $page = self::findByUser($userId);
        if (!$page) {
            throw new \RuntimeException('La fiche auteur n\'a pas pu être créée.');
        }
        return $page;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function save(int $userId, array $data): array
    {
        $page = self::ensure($userId);

        $penName = self::clip((string) ($data['pen_name'] ?? ''), 190);
        $tagline = self::clip((string) ($data['tagline'] ?? ''), 190);
        $bio = trim((string) ($data['bio'] ?? ''));
        if (mb_strlen($bio) > self::BIO_MAX) {
            $bio = mb_substr($bio, 0, self::BIO_MAX);
        }
        $shortBio = self::clip((string) ($data['short_bio'] ?? ''), self::SHORT_BIO_MAX);

        $genres = [];
        foreach ((array) ($data['genres'] ?? []) as $genre) {
            $genre = trim((string) $genre);
            if ($genre !== '' && in_array($genre, self::GENRES, true) && !in_array($genre, $genres, true)) {
                $genres[] = $genre;
            }
        }
        $genres = array_slice($genres, 0, 8);

        $openTo = [];
        foreach ((array) ($data['open_to'] ?? []) as $key) {
            $key = trim((string) $key);
            if (array_key_exists($key, self::OPEN_TO) && !in_array($key, $openTo, true)) {
                $openTo[] = $key;
            }
        }

        $press = self::cleanRows($data['press'] ?? [], ['title', 'source', 'date', 'url'], 30, true);
        $links = [];
        foreach (self::cleanRows($data['links'] ?? [], ['kind', 'label', 'url'], 20, true) as $row) {
            $row['kind'] = array_key_exists($row['kind'], self::LINK_KINDS) ? $row['kind'] : 'autre';
            $links[] = $row;
        }
        $awards = self::cleanRows($data['awards'] ?? [], ['year', 'label', 'work'], 30, false);
        $events = self::cleanRows($data['events'] ?? [], ['date', 'label', 'place', 'url'], 30, false);

        $website = self::cleanUrl((string) ($data['website'] ?? ''));
        $wikipedia = self::cleanUrl((string) ($data['wikipedia_url'] ?? ''));
        if ($wikipedia !== '' && !preg_match('#^https?://([a-z\-]+\.)?(m\.)?wikipedia\.org/#i', $wikipedia)) {
            throw new \RuntimeException('Le lien Wikipédia doit pointer vers wikipedia.org.');
        }

        // L'adresse publique suit le nom de plume tant que la fiche n'est pas publiée ; ensuite elle ne bouge plus.
        $slug = (string) ($page['slug'] ?? '');
        if (empty($page['enabled'])) {
            $base = $penName !== '' ? $penName : trim((string) ($page['first_name'] ?? '') . ' ' . (string) ($page['last_name'] ?? ''));
            if (slugify($base) !== '') {
                $slug = self::freeSlug($base, $userId);
            }
        }

        Database::query(
            'UPDATE author_pages
             SET slug = ?, pen_name = ?, tagline = ?, bio = ?, short_bio = ?, genres_json = ?, website = ?,
                 wikipedia_url = ?, press_json = ?, links_json = ?, awards_json = ?, events_json = ?,
                 open_to_json = ?, updated_at = NOW()
             WHERE user_id = ?',
            [
                $slug,
                $penName !== '' ? $penName : null,
                $tagline !== '' ? $tagline : null,
                $bio !== '' ? $bio : null,
                $shortBio !== '' ? $shortBio : null,
                self::encode($genres),
                $website !== '' ? $website : null,
                $wikipedia !== '' ? $wikipedia : null,
                self::encode($press),
                self::encode($links),
                self::encode($awards),
                self::encode($events),
                self::encode($openTo),
                $userId,
            ]
        );

        $saved = self::findByUser($userId);
        if (!$saved) {
            throw new \RuntimeException('Fiche auteur introuvable.');
        }
        return $saved;
    }

    public static function setEnabled(int $userId, bool $enabled): void
    {
        self::ensure($userId);
        Database::query(
            'UPDATE author_pages SET enabled = ?, updated_at = NOW() WHERE user_id = ?',
            [$enabled ? 1 : 0, $userId]
        );
    }

    /** @param array<string, mixed> $page */
    public static function isPublic(array $page): bool
    {
        return !empty($page['enabled']) && (($page['user_status'] ?? 'active') === 'active');
    }

    /** @param array<string, mixed> $page */
    public static function publicHref(array $page): string
    {
        $slug = (string) ($page['slug'] ?? '');
        return $slug !== '' ? '/auteurs/' . $slug : '/auteurs';
    }

    /** @param array<string, mixed> $page */
    public static function displayName(array $page): string
    {
        $pen = trim((string) ($page['pen_name'] ?? ''));
        if ($pen !== '') {
            return $pen;
        }
        $name = trim((string) ($page['first_name'] ?? '') . ' ' . (string) ($page['last_name'] ?? ''));
        return $name !== '' ? $name : 'Auteur';
    }

    /** @param array<string, mixed> $page */
    public static function completion(array $page, int $worksCount): int
    {
        $checks = [
            trim((string) ($page['tagline'] ?? '')) !== '',
            mb_strlen(trim((string) ($page['bio'] ?? ''))) >= 200,
            ($page['genres'] ?? []) !== [],
            $worksCount > 0,
            $worksCount >= 3,
            ($page['press'] ?? []) !== [] || trim((string) ($page['wikipedia_url'] ?? '')) !== '',
            ($page['links'] ?? []) !== [] || trim((string) ($page['website'] ?? '')) !== '',
            trim((string) ($page['avatar_url'] ?? '')) !== '',
        ];
        $done = count(array_filter($checks));
        return (int) round($done / count($checks) * 100);
    }

    /**
     * @return array{
     *   accounts_with_works: int,
     *   works: int,
     *   pages: int,
     *   pages_published: int,
     *   pages_draft: int,
     *   pages_empty: int,
     *   featured: int,
     *   kinds: list<array{label: string, n: int}>
     * }
     */
    public static function stats(): array
    {
        $withWorks = Database::fetch(
            'SELECT COUNT(DISTINCT ap.user_id) AS n
             FROM author_pages ap
             INNER JOIN author_works aw ON aw.author_page_id = ap.id
             INNER JOIN users u ON u.id = ap.user_id
             WHERE u.deleted_at IS NULL'
        );
        $works = Database::fetch('SELECT COUNT(*) AS n FROM author_works');
        $pages = Database::fetch(
            'SELECT COUNT(*) AS n FROM author_pages a
             JOIN users u ON u.id = a.user_id
             WHERE u.deleted_at IS NULL'
        );
        $published = Database::fetch(
            'SELECT COUNT(*) AS n FROM author_pages a
             JOIN users u ON u.id = a.user_id
             WHERE a.enabled = 1 AND u.status = "active" AND u.deleted_at IS NULL'
        );
        $drafts = Database::fetch(
            'SELECT COUNT(*) AS n FROM author_pages a
             JOIN users u ON u.id = a.user_id
             WHERE a.enabled = 0 AND u.deleted_at IS NULL'
        );
        $empty = Database::fetch(
            'SELECT COUNT(*) AS n FROM author_pages ap
             JOIN users u ON u.id = ap.user_id
             WHERE u.deleted_at IS NULL
               AND NOT EXISTS (SELECT 1 FROM author_works aw WHERE aw.author_page_id = ap.id)'
        );
        $featured = Database::fetch('SELECT COUNT(*) AS n FROM author_works WHERE featured = 1');
        $kindRows = Database::fetchAll(
            'SELECT kind, COUNT(*) AS n FROM author_works GROUP BY kind ORDER BY n DESC'
        );
        $kinds = [];
        foreach ($kindRows as $row) {
            $kind = (string) ($row['kind'] ?? '');
            $kinds[] = [
                'label' => AuthorWork::KINDS[$kind] ?? ($kind !== '' ? $kind : 'Autre'),
                'n' => (int) ($row['n'] ?? 0),
            ];
        }

        return [
            'accounts_with_works' => (int) ($withWorks['n'] ?? 0),
            'works' => (int) ($works['n'] ?? 0),
            'pages' => (int) ($pages['n'] ?? 0),
            'pages_published' => (int) ($published['n'] ?? 0),
            'pages_draft' => (int) ($drafts['n'] ?? 0),
            'pages_empty' => (int) ($empty['n'] ?? 0),
            'featured' => (int) ($featured['n'] ?? 0),
            'kinds' => $kinds,
        ];
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public static function listPublic(int $limit = 24, int $page = 1): array
    {
        $limit = max(1, min(60, $limit));
        $offset = max(0, ($page - 1) * $limit);
        $where = 'WHERE a.enabled = 1 AND u.status = "active"';
        $total = (int) (Database::fetch(
            'SELECT COUNT(*) AS n FROM author_pages a JOIN users u ON u.id = a.user_id ' . $where
        )['n'] ?? 0);
        $rows = Database::fetchAll(
            self::SELECT . ' ' . $where . ' ORDER BY a.updated_at DESC, a.id DESC LIMIT ' . $limit . ' OFFSET ' . $offset
        );
        $items = [];
        $ids = array_map(static fn (array $r): int => (int) $r['id'], $rows);
        $counts = AuthorWork::countForPages($ids);
        foreach ($rows as $row) {
            $item = self::hydrate($row);
            $item['works_count'] = $counts[(int) $row['id']] ?? 0;
            $items[] = $item;
        }
        return ['items' => $items, 'total' => $total];
    }

    /** @return list<array{slug: string, lastmod: ?string}> */
    public static function sitemapRows(): array
    {
        return Database::fetchAll(
            'SELECT a.slug, COALESCE(a.updated_at, a.created_at) AS lastmod
             FROM author_pages a
             JOIN users u ON u.id = a.user_id
             WHERE a.enabled = 1 AND u.status = "active" AND a.slug IS NOT NULL AND a.slug != ""'
        );
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function hydrate(array $row): array
    {
        $row['genres'] = self::decodeList($row['genres_json'] ?? null);
        $row['press'] = self::decodeRows($row['press_json'] ?? null);
        $row['links'] = self::decodeRows($row['links_json'] ?? null);
        $row['awards'] = self::decodeRows($row['awards_json'] ?? null);
        $row['events'] = self::decodeRows($row['events_json'] ?? null);
        $row['open_to'] = self::decodeList($row['open_to_json'] ?? null);
        $row['open_to_labels'] = array_values(array_filter(array_map(
            static fn (string $key): string => self::OPEN_TO[$key] ?? '',
            $row['open_to']
        )));
        foreach ($row['links'] as $i => $link) {
            $row['links'][$i]['kind_label'] = self::LINK_KINDS[$link['kind'] ?? 'autre'] ?? self::LINK_KINDS['autre'];
        }
        $row['name'] = self::displayName($row);
        $row['initials'] = User::initials($row);
        $row['avatar_src'] = user_avatar_src($row);
        $row['href'] = self::publicHref($row);
        $row['is_founder'] = !empty($row['founder']);
        $row['is_platform_cofounder'] = !empty($row['platform_cofounder']);
        $row['is_public'] = self::isPublic($row);
        $row['profile_href'] = '';
        if (!empty($row['profile_slug']) && (int) ($row['offers_services'] ?? 0) === 1 && ($row['user_status'] ?? '') === 'active') {
            $row['profile_href'] = '/prestataires/' . $row['profile_slug'];
        }
        $since = (string) ($row['member_since'] ?? '');
        $row['member_since_label'] = '';
        if ($since !== '' && ($ts = strtotime($since)) !== false) {
            $row['member_since_label'] = 'Membre depuis ' . date('Y', $ts);
        }
        return $row;
    }

    private static function freeSlug(string $base, int $userId): string
    {
        return unique_slug(
            $base,
            static fn (string $candidate): bool => Database::fetch(
                'SELECT id FROM author_pages WHERE slug = ? AND user_id != ?',
                [$candidate, $userId]
            ) !== null
        );
    }

    /**
     * @param mixed $rows
     * @param list<string> $keys
     * @return list<array<string, string>>
     */
    private static function cleanRows(mixed $rows, array $keys, int $max, bool $requireUrl): array
    {
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $item = [];
            foreach ($keys as $key) {
                $value = self::clip((string) ($row[$key] ?? ''), $key === 'url' ? 500 : 190);
                if ($key === 'url') {
                    $value = self::cleanUrl($value);
                }
                $item[$key] = $value;
            }
            if (implode('', $item) === '') {
                continue;
            }
            if ($requireUrl && ($item['url'] ?? '') === '') {
                continue;
            }
            $out[] = $item;
            if (count($out) >= $max) {
                break;
            }
        }
        return $out;
    }

    public static function cleanUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (!preg_match('#^https?://#i', $url)) {
            if (preg_match('#^[a-z0-9.-]+\.[a-z]{2,}(/|$)#i', $url)) {
                $url = 'https://' . $url;
            } else {
                return '';
            }
        }
        if (mb_strlen($url) > 500 || preg_match('/[\s<>"\']/', $url)) {
            return '';
        }
        return $url;
    }

    private static function clip(string $value, int $max): string
    {
        $value = trim($value);
        return mb_strlen($value) > $max ? mb_substr($value, 0, $max) : $value;
    }

    /** @param list<mixed> $list */
    private static function encode(array $list): ?string
    {
        if ($list === []) {
            return null;
        }
        $json = json_encode(array_values($list), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $json === false ? null : $json;
    }

    /** @return list<string> */
    private static function decodeList(?string $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return [];
        }
        return array_values(array_filter(array_map(
            static fn ($v): string => is_scalar($v) ? trim((string) $v) : '',
            $data
        ), static fn (string $v): bool => $v !== ''));
    }

    /** @return list<array<string, string>> */
    private static function decodeRows(?string $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return [];
        }
        $out = [];
        foreach ($data as $row) {
            if (!is_array($row)) {
                continue;
            }
            $clean = [];
            foreach ($row as $key => $value) {
                $clean[(string) $key] = is_scalar($value) ? (string) $value : '';
            }
            $out[] = $clean;
        }
        return $out;
    }
}
