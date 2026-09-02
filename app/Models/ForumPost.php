<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;
use Adl\Core\ProfanityFilter;
use RuntimeException;

final class ForumPost
{
    public const PER_PAGE = 20;
    public const MIN_BODY = 40;

    public static function find(int $id): ?array
    {
        $row = Database::fetch(
            'SELECT p.*,
                    u.first_name, u.last_name, u.avatar_url, u.platform_cofounder, u.created_at AS user_created_at,
                    pr.title AS profile_title, pr.city AS profile_city, pr.verification_status,
                    pr.trades_json, pr.slug AS profile_slug,
                    parent_u.first_name AS parent_first_name, parent_u.last_name AS parent_last_name
             FROM forum_posts p
             INNER JOIN users u ON u.id = p.user_id
             LEFT JOIN profiles pr ON pr.user_id = u.id
             LEFT JOIN forum_posts parent ON parent.id = p.parent_id
             LEFT JOIN users parent_u ON parent_u.id = parent.user_id
             WHERE p.id = ?
             LIMIT 1',
            [$id]
        );
        return $row ? self::present($row) : null;
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int}
     */
    public static function forTopic(int $topicId, string $sort = 'chrono', int $page = 1, ?int $viewerId = null): array
    {
        $page = max(1, $page);
        $perPage = self::PER_PAGE;

        $countRow = Database::fetch(
            'SELECT COUNT(*) AS n FROM forum_posts WHERE topic_id = ? AND status = "visible" AND is_op = 0',
            [$topicId]
        );
        $total = (int) ($countRow['n'] ?? 0);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;

        $order = $sort === 'useful'
            ? 'p.is_solution DESC, p.useful_count DESC, p.position ASC'
            : 'p.position ASC';

        $rows = Database::fetchAll(
            "SELECT p.*,
                    u.first_name, u.last_name, u.avatar_url, u.platform_cofounder, u.created_at AS user_created_at,
                    pr.title AS profile_title, pr.city AS profile_city, pr.verification_status,
                    pr.trades_json, pr.slug AS profile_slug,
                    parent_u.first_name AS parent_first_name, parent_u.last_name AS parent_last_name
             FROM forum_posts p
             INNER JOIN users u ON u.id = p.user_id
             LEFT JOIN profiles pr ON pr.user_id = u.id
             LEFT JOIN forum_posts parent ON parent.id = p.parent_id
             LEFT JOIN users parent_u ON parent_u.id = parent.user_id
             WHERE p.topic_id = ? AND p.status = \"visible\" AND p.is_op = 0
             ORDER BY $order
             LIMIT $perPage OFFSET $offset",
            [$topicId]
        );

        $items = array_map([self::class, 'present'], $rows);
        if ($viewerId) {
            $voted = self::votedIds($topicId, $viewerId);
            foreach ($items as $i => $item) {
                $items[$i]['liked'] = isset($voted[$item['id']]);
            }
        }

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
        ];
    }

    public static function opening(int $topicId): ?array
    {
        $row = Database::fetch(
            'SELECT p.*,
                    u.first_name, u.last_name, u.avatar_url, u.platform_cofounder, u.created_at AS user_created_at,
                    pr.title AS profile_title, pr.city AS profile_city, pr.verification_status,
                    pr.trades_json, pr.slug AS profile_slug
             FROM forum_posts p
             INNER JOIN users u ON u.id = p.user_id
             LEFT JOIN profiles pr ON pr.user_id = u.id
             WHERE p.topic_id = ? AND p.is_op = 1 AND p.status = "visible"
             ORDER BY p.id ASC
             LIMIT 1',
            [$topicId]
        );
        return $row ? self::present($row) : null;
    }

    public static function participants(int $topicId, int $limit = 8): array
    {
        $rows = Database::fetchAll(
            'SELECT u.id, u.first_name, u.last_name, u.avatar_url,
                    MIN(p.position) AS first_pos
             FROM forum_posts p
             INNER JOIN users u ON u.id = p.user_id
             WHERE p.topic_id = ? AND p.status = "visible"
             GROUP BY u.id
             ORDER BY first_pos ASC
             LIMIT ' . max(1, min(20, $limit)),
            [$topicId]
        );
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id' => (int) $row['id'],
                'name' => User::displayName($row),
                'initials' => User::initials($row),
                'avatar_url' => $row['avatar_url'] ?? null,
            ];
        }
        return $out;
    }

    public static function participantCount(int $topicId): int
    {
        $row = Database::fetch(
            'SELECT COUNT(DISTINCT user_id) AS n FROM forum_posts
             WHERE topic_id = ? AND status = "visible"',
            [$topicId]
        );
        return (int) ($row['n'] ?? 0);
    }

    public static function authorPostCount(int $userId): int
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS n FROM forum_posts WHERE user_id = ? AND status = "visible"',
            [$userId]
        );
        return (int) ($row['n'] ?? 0);
    }

    /** @param array<string, mixed> $data */
    public static function create(int $topicId, int $userId, array $data): array
    {
        $topic = ForumTopic::find($topicId);
        if (!$topic) {
            throw new RuntimeException('Discussion introuvable.');
        }
        if (!empty($topic['is_locked'])) {
            throw new RuntimeException('Cette discussion est verrouillée.');
        }

        $body = trim((string) ($data['body'] ?? ''));
        if (mb_strlen(plain_text($body) ?: $body) < self::MIN_BODY) {
            throw new RuntimeException('La réponse doit faire au moins ' . self::MIN_BODY . ' caractères.');
        }
        if (empty($data['no_ai'])) {
            throw new RuntimeException('Confirmez que votre réponse n\'a pas été générée par IA.');
        }

        ProfanityFilter::assertClean($body);

        $parentId = (int) ($data['parent_id'] ?? 0);
        if ($parentId > 0) {
            $parent = self::find($parentId);
            if (!$parent || (int) $parent['topic_id'] !== $topicId) {
                throw new RuntimeException('Citation invalide.');
            }
        } else {
            $parentId = null;
        }

        $safeBody = sanitize_user_html($body);

        return Database::transaction(static function () use ($topicId, $userId, $safeBody, $parentId): array {
            $max = Database::fetch(
                'SELECT MAX(position) AS n FROM forum_posts WHERE topic_id = ?',
                [$topicId]
            );
            $position = ((int) ($max['n'] ?? 0)) + 1;
            Database::query(
                'INSERT INTO forum_posts
                    (topic_id, user_id, parent_id, body, position, is_op, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, 0, "visible", NOW(), NOW())',
                [$topicId, $userId, $parentId, $safeBody, $position]
            );
            $postId = (int) Database::lastId();
            Database::query(
                'UPDATE forum_topics
                 SET reply_count = reply_count + 1,
                     last_post_at = NOW(),
                     last_post_user_id = ?,
                     updated_at = NOW()
                 WHERE id = ?',
                [$userId, $topicId]
            );
            Database::query(
                'INSERT INTO forum_topic_follows (topic_id, user_id, created_at, last_read_at, last_read_post_id)
                 VALUES (?, ?, NOW(), NOW(), ?)
                 ON DUPLICATE KEY UPDATE last_read_at = NOW(), last_read_post_id = ?',
                [$topicId, $userId, $postId, $postId]
            );
            Database::query(
                'UPDATE forum_topics t
                 SET follow_count = (SELECT COUNT(*) FROM forum_topic_follows f WHERE f.topic_id = t.id)
                 WHERE t.id = ?',
                [$topicId]
            );
            $post = self::find($postId);
            if (!$post) {
                throw new RuntimeException('Réponse introuvable après création.');
            }
            return $post;
        });
    }

    public static function toggleUseful(int $postId, int $userId): array
    {
        $post = self::find($postId);
        if (!$post || !empty($post['is_op'])) {
            throw new RuntimeException('Message invalide.');
        }
        if ((int) $post['user_id'] === $userId) {
            throw new RuntimeException('Vous ne pouvez pas voter pour votre propre message.');
        }

        $exists = Database::fetch(
            'SELECT post_id FROM forum_post_votes WHERE post_id = ? AND user_id = ?',
            [$postId, $userId]
        );
        if ($exists) {
            Database::query(
                'DELETE FROM forum_post_votes WHERE post_id = ? AND user_id = ?',
                [$postId, $userId]
            );
            Database::query(
                'UPDATE forum_posts SET useful_count = GREATEST(useful_count - 1, 0) WHERE id = ?',
                [$postId]
            );
            $liked = false;
        } else {
            Database::query(
                'INSERT INTO forum_post_votes (post_id, user_id, created_at) VALUES (?, ?, NOW())',
                [$postId, $userId]
            );
            Database::query(
                'UPDATE forum_posts SET useful_count = useful_count + 1 WHERE id = ?',
                [$postId]
            );
            $liked = true;
        }
        $fresh = self::find($postId);
        return [
            'liked' => $liked,
            'useful_count' => (int) ($fresh['useful_count'] ?? 0),
        ];
    }

    public static function hide(int $postId, int $topicId, bool $isAdmin): void
    {
        if (!$isAdmin) {
            throw new RuntimeException('Seul un administrateur peut supprimer une réponse.');
        }

        $post = self::find($postId);
        if (
            !$post
            || (int) ($post['topic_id'] ?? 0) !== $topicId
            || ($post['status'] ?? '') !== 'visible'
            || !empty($post['is_op'])
        ) {
            throw new RuntimeException('Réponse invalide.');
        }

        $wasSolution = !empty($post['is_solution']);

        Database::transaction(static function () use ($postId, $topicId, $wasSolution): void {
            Database::query(
                'UPDATE forum_posts SET status = "hidden", is_solution = 0, updated_at = NOW() WHERE id = ?',
                [$postId]
            );

            $last = Database::fetch(
                'SELECT user_id, created_at FROM forum_posts
                 WHERE topic_id = ? AND status = "visible"
                 ORDER BY created_at DESC, id DESC
                 LIMIT 1',
                [$topicId]
            );
            $count = Database::fetch(
                'SELECT COUNT(*) AS n FROM forum_posts
                 WHERE topic_id = ? AND status = "visible" AND is_op = 0',
                [$topicId]
            );

            $sql = 'UPDATE forum_topics
                    SET reply_count = ?,
                        last_post_at = ?,
                        last_post_user_id = ?,
                        updated_at = NOW()';
            $params = [
                (int) ($count['n'] ?? 0),
                $last['created_at'] ?? date('Y-m-d H:i:s'),
                $last ? (int) $last['user_id'] : null,
            ];
            if ($wasSolution) {
                $sql .= ', is_solved = 0, solved_post_id = NULL';
            }
            $sql .= ' WHERE id = ?';
            $params[] = $topicId;
            Database::query($sql, $params);
        });
    }

    /** @return array<int, true> */
    private static function votedIds(int $topicId, int $userId): array
    {
        $rows = Database::fetchAll(
            'SELECT v.post_id FROM forum_post_votes v
             INNER JOIN forum_posts p ON p.id = v.post_id
             WHERE p.topic_id = ? AND v.user_id = ?',
            [$topicId, $userId]
        );
        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['post_id']] = true;
        }
        return $out;
    }

    /** @param array<string, mixed> $row */
    public static function presentAuthor(array $row): array
    {
        $trades = [];
        if (!empty($row['trades_json'])) {
            $decoded = json_decode((string) $row['trades_json'], true);
            if (is_array($decoded)) {
                $trades = $decoded;
            }
        }
        $trade = '';
        foreach ($trades as $t) {
            if (is_string($t) && $t !== '') {
                $trade = $t;
                break;
            }
            if (is_array($t) && !empty($t['label'])) {
                $trade = (string) $t['label'];
                break;
            }
        }
        $title = trim((string) ($row['profile_title'] ?? ''));
        $role = $title !== '' ? $title : ($trade !== '' ? $trade : 'Membre');
        $city = trim((string) ($row['profile_city'] ?? ''));
        $meta = $role;
        if ($city !== '') {
            $meta .= ' · ' . $city;
        }
        $name = User::displayName([
            'first_name' => $row['first_name'] ?? '',
            'last_name' => $row['last_name'] ?? '',
        ]);
        $verified = ($row['verification_status'] ?? '') === Profile::VERIFY_VERIFIED;
        $profileSlug = trim((string) ($row['profile_slug'] ?? ''));

        return [
            'id' => (int) ($row['user_id'] ?? $row['id'] ?? 0),
            'name' => $name,
            'initials' => User::initials([
                'first_name' => $row['first_name'] ?? '',
                'last_name' => $row['last_name'] ?? '',
            ]),
            'avatar_url' => $row['avatar_url'] ?? null,
            'role' => $role,
            'city' => $city,
            'meta' => $meta,
            'verified' => $verified,
            'is_platform_cofounder' => (int) ($row['platform_cofounder'] ?? 0) === 1,
            'profile_href' => $profileSlug !== '' ? '/prestataires/' . $profileSlug : null,
            'member_since' => self::memberSinceLabel($row['user_created_at'] ?? null),
        ];
    }

    private static function memberSinceLabel(mixed $datetime): string
    {
        if (!is_string($datetime) || $datetime === '') {
            return '';
        }
        $ts = strtotime($datetime);
        if ($ts === false) {
            return '';
        }
        $months = [
            1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
            5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
            9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre',
        ];
        $m = (int) date('n', $ts);
        return ($months[$m] ?? '') . ' ' . date('Y', $ts);
    }

    /** @param array<string, mixed> $row */
    public static function present(array $row): array
    {
        $author = self::presentAuthor($row);
        $parentName = trim(
            ((string) ($row['parent_first_name'] ?? '')) . ' ' . ((string) ($row['parent_last_name'] ?? ''))
        );
        return [
            'id' => (int) ($row['id'] ?? 0),
            'topic_id' => (int) ($row['topic_id'] ?? 0),
            'user_id' => (int) ($row['user_id'] ?? 0),
            'parent_id' => $row['parent_id'] !== null ? (int) $row['parent_id'] : null,
            'body' => (string) ($row['body'] ?? ''),
            'body_html' => user_html((string) ($row['body'] ?? '')),
            'position' => (int) ($row['position'] ?? 0),
            'num' => '#' . (int) ($row['position'] ?? 0),
            'is_op' => !empty($row['is_op']),
            'is_solution' => !empty($row['is_solution']),
            'useful_count' => (int) ($row['useful_count'] ?? 0),
            'status' => (string) ($row['status'] ?? 'visible'),
            'created_at' => $row['created_at'] ?? null,
            'when' => time_ago($row['created_at'] ?? null),
            'author' => $author,
            'citation' => $parentName,
            'liked' => false,
        ];
    }
}
