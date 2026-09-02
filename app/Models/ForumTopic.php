<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;
use Adl\Core\ProfanityFilter;
use RuntimeException;

final class ForumTopic
{
    public const PER_PAGE = 20;
    public const MIN_BODY = 80;

    public static function find(int $id): ?array
    {
        $row = self::baseFetch('t.id = ?', [$id]);
        return $row ? self::present($row) : null;
    }

    public static function findBySlug(int $categoryId, string $slug): ?array
    {
        $row = self::baseFetch('t.category_id = ? AND t.slug = ?', [$categoryId, $slug]);
        return $row ? self::present($row) : null;
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int, per_page: int}
     */
    public static function list(array $opts = []): array
    {
        $page = max(1, (int) ($opts['page'] ?? 1));
        $perPage = max(1, min(50, (int) ($opts['per_page'] ?? self::PER_PAGE)));
        $filter = (string) ($opts['filter'] ?? 'recent');
        $categoryId = (int) ($opts['category_id'] ?? 0);
        $userId = (int) ($opts['user_id'] ?? 0);
        $q = trim((string) ($opts['q'] ?? ''));

        $where = ['t.status = "visible"'];
        $params = [];

        if ($categoryId > 0) {
            $where[] = 't.category_id = ?';
            $params[] = $categoryId;
        }
        if ($q !== '') {
            $where[] = '(t.title LIKE ? OR p_op.body LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
        }
        if ($filter === 'unanswered') {
            $where[] = 't.reply_count = 0';
        } elseif ($filter === 'solved') {
            $where[] = 't.is_solved = 1';
        } elseif ($filter === 'mine' && $userId > 0) {
            $where[] = 't.user_id = ?';
            $params[] = $userId;
        } elseif ($filter === 'followed' && $userId > 0) {
            $where[] = 'EXISTS (SELECT 1 FROM forum_topic_follows f WHERE f.topic_id = t.id AND f.user_id = ?)';
            $params[] = $userId;
        }

        $whereSql = implode(' AND ', $where);
        $joinOp = $q !== ''
            ? 'LEFT JOIN forum_posts p_op ON p_op.topic_id = t.id AND p_op.is_op = 1'
            : '';

        $countRow = Database::fetch(
            "SELECT COUNT(DISTINCT t.id) AS n FROM forum_topics t $joinOp WHERE $whereSql",
            $params
        );
        $total = (int) ($countRow['n'] ?? 0);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;

        $order = match ($filter) {
            'followed', 'popular' => 't.follow_count DESC, t.last_post_at DESC',
            'useful' => 't.reply_count DESC, t.last_post_at DESC',
            default => 't.is_pinned DESC, t.last_post_at DESC',
        };

        $rows = Database::fetchAll(
            "SELECT t.*,
                    c.name AS category_name, c.slug AS category_slug,
                    u.first_name, u.last_name, u.avatar_url, u.platform_cofounder, u.created_at AS user_created_at,
                    p.title AS profile_title, p.city AS profile_city, p.verification_status,
                    p.trades_json, p.slug AS profile_slug,
                    lu.first_name AS last_first_name, lu.last_name AS last_last_name
             FROM forum_topics t
             INNER JOIN forum_categories c ON c.id = t.category_id
             INNER JOIN users u ON u.id = t.user_id
             LEFT JOIN profiles p ON p.user_id = u.id
             LEFT JOIN users lu ON lu.id = t.last_post_user_id
             $joinOp
             WHERE $whereSql
             ORDER BY $order
             LIMIT $perPage OFFSET $offset",
            $params
        );

        return [
            'items' => array_map([self::class, 'present'], $rows),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
        ];
    }

    public static function recent(int $limit = 3): array
    {
        $found = self::list(['filter' => 'recent', 'per_page' => $limit, 'page' => 1]);
        return $found['items'];
    }

    /**
     * Discussions à l'activité la plus récente, avec extrait du dernier message.
     *
     * @return list<array<string, mixed>>
     */
    public static function recentForHome(int $limit = 3): array
    {
        $limit = max(1, min(10, $limit));
        $rows = Database::fetchAll(
            'SELECT t.*,
                    c.name AS category_name, c.slug AS category_slug,
                    u.first_name, u.last_name, u.avatar_url, u.platform_cofounder, u.created_at AS user_created_at,
                    p.title AS profile_title, p.city AS profile_city, p.verification_status,
                    p.trades_json, p.slug AS profile_slug,
                    lu.first_name AS last_first_name, lu.last_name AS last_last_name,
                    lu.avatar_url AS last_avatar_url,
                    lp.body AS last_body, lp.is_op AS last_is_op
             FROM forum_topics t
             INNER JOIN forum_categories c ON c.id = t.category_id
             INNER JOIN users u ON u.id = t.user_id
             LEFT JOIN profiles p ON p.user_id = u.id
             LEFT JOIN users lu ON lu.id = t.last_post_user_id
             LEFT JOIN forum_posts lp ON lp.topic_id = t.id AND lp.status = "visible"
               AND lp.id = (SELECT MAX(id) FROM forum_posts WHERE topic_id = t.id AND status = "visible")
             WHERE t.status = "visible"
             ORDER BY t.last_post_at DESC
             LIMIT ' . $limit
        );

        $out = [];
        foreach ($rows as $row) {
            $topic = self::present($row);
            $excerpt = trim(plain_text((string) ($row['last_body'] ?? '')));
            if (mb_strlen($excerpt) > 160) {
                $excerpt = rtrim(mb_substr($excerpt, 0, 157)) . '…';
            }
            $lastName = trim(((string) ($row['last_first_name'] ?? '')) . ' ' . ((string) ($row['last_last_name'] ?? '')));
            $lastAuthor = [
                'name' => $lastName,
                'initials' => User::initials([
                    'first_name' => $row['last_first_name'] ?? '',
                    'last_name' => $row['last_last_name'] ?? '',
                ]),
                'avatar_url' => $row['last_avatar_url'] ?? null,
            ];
            if ($lastName === '') {
                $lastAuthor = $topic['author'] ?? $lastAuthor;
            }
            $topic['excerpt'] = $excerpt;
            $topic['last_author'] = $lastAuthor;
            $topic['last_is_op'] = !empty($row['last_is_op']);
            $out[] = $topic;
        }
        return $out;
    }

    public static function unanswered(int $limit = 4): array
    {
        $found = self::list(['filter' => 'unanswered', 'per_page' => $limit, 'page' => 1]);
        return $found['items'];
    }

    public static function related(int $categoryId, int $excludeId, int $limit = 4): array
    {
        $rows = Database::fetchAll(
            'SELECT t.*,
                    c.name AS category_name, c.slug AS category_slug,
                    u.first_name, u.last_name, u.avatar_url, u.platform_cofounder, u.created_at AS user_created_at,
                    p.title AS profile_title, p.city AS profile_city, p.verification_status,
                    p.trades_json, p.slug AS profile_slug,
                    lu.first_name AS last_first_name, lu.last_name AS last_last_name
             FROM forum_topics t
             INNER JOIN forum_categories c ON c.id = t.category_id
             INNER JOIN users u ON u.id = t.user_id
             LEFT JOIN profiles p ON p.user_id = u.id
             LEFT JOIN users lu ON lu.id = t.last_post_user_id
             WHERE t.status = "visible" AND t.category_id = ? AND t.id != ?
             ORDER BY t.last_post_at DESC
             LIMIT ' . max(1, min(10, $limit)),
            [$categoryId, $excludeId]
        );
        return array_map([self::class, 'present'], $rows);
    }

    /**
     * Suggestions de discussions proches d'un titre en cours de saisie.
     *
     * @return list<array{href: string, title: string, subtitle: string, kind_label: string, meta: string}>
     */
    public static function suggestSimilar(string $q, int $categoryId = 0, int $limit = 6): array
    {
        $limit = max(1, min(10, $limit));
        $tokens = self::titleTokens($q);
        if ($tokens === []) {
            return [];
        }

        $ors = [];
        $params = [];
        foreach ($tokens as $token) {
            $like = '%' . addcslashes($token, '%_\\') . '%';
            $ors[] = '(t.title LIKE ? OR t.tags_json LIKE ?)';
            $params[] = $like;
            $params[] = $like;
        }
        $where = 't.status = "visible" AND (' . implode(' OR ', $ors) . ')';
        $rows = Database::fetchAll(
            "SELECT t.*,
                    c.name AS category_name, c.slug AS category_slug,
                    u.first_name, u.last_name, u.avatar_url, u.platform_cofounder, u.created_at AS user_created_at,
                    p.title AS profile_title, p.city AS profile_city, p.verification_status,
                    p.trades_json, p.slug AS profile_slug,
                    lu.first_name AS last_first_name, lu.last_name AS last_last_name
             FROM forum_topics t
             INNER JOIN forum_categories c ON c.id = t.category_id
             INNER JOIN users u ON u.id = t.user_id
             LEFT JOIN profiles p ON p.user_id = u.id
             LEFT JOIN users lu ON lu.id = t.last_post_user_id
             WHERE $where
             ORDER BY t.last_post_at DESC
             LIMIT 48",
            $params
        );

        $scored = [];
        foreach ($rows as $row) {
            $topic = self::present($row);
            $hayTitle = search_norm((string) ($topic['title'] ?? ''));
            $hayTags = search_norm(implode(' ', $topic['tags'] ?? []));
            $score = 0;
            $matched = 0;
            foreach ($tokens as $token) {
                $inTitle = str_contains($hayTitle, $token);
                $inTags = str_contains($hayTags, $token);
                if ($inTitle) {
                    $score += 3;
                    $matched++;
                } elseif ($inTags) {
                    $score += 1;
                    $matched++;
                }
            }
            if ($matched === 0) {
                continue;
            }
            if ($categoryId > 0 && (int) ($topic['category_id'] ?? 0) === $categoryId) {
                $score += 2;
            }
            $score += min(2, (int) ($topic['reply_count'] ?? 0) > 0 ? 1 : 0);
            $scored[] = ['score' => $score, 'matched' => $matched, 'topic' => $topic];
        }

        usort($scored, static function (array $a, array $b): int {
            if ($a['score'] !== $b['score']) {
                return $b['score'] <=> $a['score'];
            }
            if ($a['matched'] !== $b['matched']) {
                return $b['matched'] <=> $a['matched'];
            }
            return ((int) ($b['topic']['id'] ?? 0)) <=> ((int) ($a['topic']['id'] ?? 0));
        });

        $out = [];
        foreach (array_slice($scored, 0, $limit) as $item) {
            $topic = $item['topic'];
            $replies = (int) ($topic['reply_count'] ?? 0);
            $out[] = [
                'href' => (string) ($topic['href'] ?? '/forum'),
                'title' => (string) ($topic['title'] ?? ''),
                'subtitle' => (string) ($topic['category_short'] ?? $topic['category_name'] ?? ''),
                'kind_label' => (string) ($topic['category_short'] ?? 'Forum'),
                'meta' => $replies === 0
                    ? 'sans réponse · ' . (string) ($topic['when'] ?? '')
                    : format_int($replies) . ' réponse' . ($replies > 1 ? 's' : '') . ' · ' . (string) ($topic['last_when'] ?? $topic['when'] ?? ''),
            ];
        }
        return $out;
    }

    /** @return list<string> */
    public static function titleTokens(string $q): array
    {
        $q = trim($q);
        if (mb_strlen($q) < 3) {
            return [];
        }
        $norm = search_norm($q);
        $parts = preg_split('/[^a-z0-9]+/', $norm) ?: [];
        $stop = [
            'le', 'la', 'les', 'un', 'une', 'des', 'de', 'du', 'et', 'ou', 'au', 'aux',
            'pour', 'dans', 'sur', 'avec', 'sans', 'que', 'qui', 'quoi', 'quel', 'quelle',
            'quels', 'quelles', 'ce', 'cet', 'cette', 'ces', 'en', 'est', 'sont', 'ont',
            'mon', 'ma', 'mes', 'ton', 'ta', 'tes', 'son', 'sa', 'ses', 'notre', 'nos',
            'votre', 'vos', 'leur', 'leurs', 'je', 'tu', 'il', 'elle', 'nous', 'vous',
            'ils', 'elles', 'ne', 'pas', 'plus', 'moins', 'tres', 'aussi', 'comme',
            'dont', 'ou', 'si', 'se', 'me', 'te', 'y', 'd', 'l', 'n', 's', 'c',
            'par', 'vers', 'chez', 'entre', 'sous', 'donc', 'car', 'mais', 'alors',
            'comment', 'quand', 'combien', 'pourquoi', 'quelqu', 'toute', 'tous',
            'tout', 'toutes', 'faire', 'fait', 'etre', 'avoir', 'peut', 'peuton',
            'faut', 'ilfaut', 'bonne', 'bon', 'bien', 'mal', 'ca', 'cela',
        ];
        $out = [];
        foreach ($parts as $part) {
            if ($part === '' || mb_strlen($part) < 3 || in_array($part, $stop, true)) {
                continue;
            }
            $out[$part] = $part;
            if (count($out) >= 8) {
                break;
            }
        }
        if ($out === [] && mb_strlen($norm) >= 3) {
            $compact = preg_replace('/[^a-z0-9]+/', '', $norm) ?? '';
            if (mb_strlen($compact) >= 3) {
                $out[$compact] = $compact;
            }
        }
        return array_values($out);
    }

    public static function countVisible(): int
    {
        $row = Database::fetch('SELECT COUNT(*) AS n FROM forum_topics WHERE status = "visible"');
        return (int) ($row['n'] ?? 0);
    }

    public static function stats(): array
    {
        $topics = Database::fetch('SELECT COUNT(*) AS n FROM forum_topics WHERE status = "visible"');
        $posts = Database::fetch('SELECT COUNT(*) AS n FROM forum_posts WHERE status = "visible"');
        $unanswered = Database::fetch(
            'SELECT COUNT(*) AS n FROM forum_topics WHERE status = "visible" AND reply_count = 0'
        );
        $week = Database::fetch(
            'SELECT COUNT(*) AS n FROM forum_topics
             WHERE status = "visible" AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)'
        );
        return [
            'topics' => (int) ($topics['n'] ?? 0),
            'posts' => (int) ($posts['n'] ?? 0),
            'unanswered' => (int) ($unanswered['n'] ?? 0),
            'week' => (int) ($week['n'] ?? 0),
        ];
    }

    /** @return array{mine: int, followed: int, posts: int} */
    public static function userStats(int $userId): array
    {
        $mine = Database::fetch(
            'SELECT COUNT(*) AS n FROM forum_topics WHERE user_id = ? AND status = "visible"',
            [$userId]
        );
        $followed = Database::fetch(
            'SELECT COUNT(*) AS n FROM forum_topic_follows f
             INNER JOIN forum_topics t ON t.id = f.topic_id
             WHERE f.user_id = ? AND t.status = "visible"',
            [$userId]
        );
        $posts = Database::fetch(
            'SELECT COUNT(*) AS n FROM forum_posts WHERE user_id = ? AND status = "visible"',
            [$userId]
        );
        return [
            'mine' => (int) ($mine['n'] ?? 0),
            'followed' => (int) ($followed['n'] ?? 0),
            'posts' => (int) ($posts['n'] ?? 0),
        ];
    }

    public static function unfollow(int $topicId, int $userId): void
    {
        if (!self::isFollowing($topicId, $userId)) {
            return;
        }
        Database::query(
            'DELETE FROM forum_topic_follows WHERE topic_id = ? AND user_id = ?',
            [$topicId, $userId]
        );
        Database::query(
            'UPDATE forum_topics SET follow_count = GREATEST(follow_count - 1, 0) WHERE id = ?',
            [$topicId]
        );
    }

    public static function topContributors(int $limit = 5): array
    {
        $rows = Database::fetchAll(
            'SELECT u.id, u.first_name, u.last_name, u.avatar_url, u.platform_cofounder,
                    p.title AS profile_title, p.city AS profile_city, p.verification_status,
                    p.trades_json, p.slug AS profile_slug,
                    COUNT(fp.id) AS post_count
             FROM forum_posts fp
             INNER JOIN users u ON u.id = fp.user_id
             LEFT JOIN profiles p ON p.user_id = u.id
             WHERE fp.status = "visible"
             GROUP BY u.id
             ORDER BY post_count DESC
             LIMIT ' . max(1, min(20, $limit))
        );
        $out = [];
        foreach ($rows as $row) {
            $out[] = ForumPost::presentAuthor($row) + [
                'post_count' => (int) ($row['post_count'] ?? 0),
            ];
        }
        return $out;
    }

    public static function popularTags(int $limit = 12, ?int $categoryId = null): array
    {
        $sql = 'SELECT tags_json FROM forum_topics WHERE status = "visible" AND tags_json IS NOT NULL';
        $params = [];
        if ($categoryId) {
            $sql .= ' AND category_id = ?';
            $params[] = $categoryId;
        }
        $rows = Database::fetchAll($sql, $params);
        $counts = [];
        foreach ($rows as $row) {
            foreach (self::decodeTags($row['tags_json'] ?? null) as $tag) {
                $counts[$tag] = ($counts[$tag] ?? 0) + 1;
            }
        }
        arsort($counts);
        return array_slice(array_keys($counts), 0, $limit);
    }

    /** @param array<string, mixed> $data */
    public static function create(int $userId, array $data): array
    {
        $categoryId = (int) ($data['category_id'] ?? 0);
        $title = trim((string) ($data['title'] ?? ''));
        $body = trim((string) ($data['body'] ?? ''));
        $tags = self::normalizeTags($data['tags'] ?? []);

        if ($categoryId <= 0 || !ForumCategory::find($categoryId)) {
            throw new RuntimeException('Choisissez une rubrique.');
        }
        if (mb_strlen($title) < 8) {
            throw new RuntimeException('Le titre doit faire au moins 8 caractères.');
        }
        if (mb_strlen($title) > 180) {
            throw new RuntimeException('Le titre est trop long.');
        }
        if (mb_strlen(plain_text($body) ?: $body) < self::MIN_BODY) {
            throw new RuntimeException('Le message doit faire au moins ' . self::MIN_BODY . ' caractères.');
        }
        if (empty($data['no_ai'])) {
            throw new RuntimeException('Confirmez que votre message n\'a pas été généré par IA.');
        }

        ProfanityFilter::assertClean($title, $body, implode(' ', $tags));

        $safeBody = sanitize_user_html($body);
        $slug = unique_slug(
            $title,
            static fn (string $candidate): bool => Database::fetch(
                'SELECT id FROM forum_topics WHERE category_id = ? AND slug = ?',
                [$categoryId, $candidate]
            ) !== null
        );

        return Database::transaction(static function () use ($userId, $categoryId, $title, $slug, $tags, $safeBody): array {
            Database::query(
                'INSERT INTO forum_topics
                    (category_id, user_id, title, slug, tags_json, reply_count, view_count, follow_count,
                     last_post_at, last_post_user_id, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, 0, 0, 0, NOW(), ?, "visible", NOW(), NOW())',
                [$categoryId, $userId, $title, $slug, self::encodeTags($tags), $userId]
            );
            $topicId = (int) Database::lastId();
            Database::query(
                'INSERT INTO forum_posts
                    (topic_id, user_id, body, position, is_op, status, created_at, updated_at)
                 VALUES (?, ?, ?, 1, 1, "visible", NOW(), NOW())',
                [$topicId, $userId, $safeBody]
            );
            $postId = (int) Database::lastId();
            Database::query(
                'INSERT INTO forum_topic_follows (topic_id, user_id, created_at, last_read_at, last_read_post_id)
                 VALUES (?, ?, NOW(), NOW(), ?)
                 ON DUPLICATE KEY UPDATE topic_id = topic_id',
                [$topicId, $userId, $postId]
            );
            Database::query(
                'UPDATE forum_topics SET follow_count = 1 WHERE id = ?',
                [$topicId]
            );
            $topic = self::find($topicId);
            if (!$topic) {
                throw new RuntimeException('Discussion introuvable après création.');
            }
            return $topic;
        });
    }

    public static function bumpViews(int $topicId): void
    {
        Database::query('UPDATE forum_topics SET view_count = view_count + 1 WHERE id = ?', [$topicId]);
    }

    public static function markSolved(int $topicId, int $postId, int $actorId, bool $isAdmin = false): void
    {
        $topic = self::find($topicId);
        if (!$topic) {
            throw new RuntimeException('Discussion introuvable.');
        }
        if (!$isAdmin && (int) $topic['user_id'] !== $actorId) {
            throw new RuntimeException('Seul l\'auteur peut retenir une réponse.');
        }
        $post = ForumPost::find($postId);
        if (!$post || (int) $post['topic_id'] !== $topicId || !empty($post['is_op'])) {
            throw new RuntimeException('Réponse invalide.');
        }
        Database::transaction(static function () use ($topicId, $postId): void {
            Database::query('UPDATE forum_posts SET is_solution = 0 WHERE topic_id = ?', [$topicId]);
            Database::query('UPDATE forum_posts SET is_solution = 1 WHERE id = ?', [$postId]);
            Database::query(
                'UPDATE forum_topics SET is_solved = 1, solved_post_id = ? WHERE id = ?',
                [$postId, $topicId]
            );
        });
    }

    public static function isFollowing(int $topicId, int $userId): bool
    {
        return Database::fetch(
            'SELECT topic_id FROM forum_topic_follows WHERE topic_id = ? AND user_id = ?',
            [$topicId, $userId]
        ) !== null;
    }

    public static function markRead(int $topicId, int $userId): void
    {
        if ($userId <= 0 || $topicId <= 0) {
            return;
        }
        if (!self::isFollowing($topicId, $userId)) {
            return;
        }
        $max = Database::fetch(
            'SELECT MAX(id) AS n FROM forum_posts WHERE topic_id = ? AND status = "visible"',
            [$topicId]
        );
        Database::query(
            'UPDATE forum_topic_follows
             SET last_read_at = NOW(), last_read_post_id = ?
             WHERE topic_id = ? AND user_id = ?',
            [(int) ($max['n'] ?? 0), $topicId, $userId]
        );
    }

    public static function unreadReplyCount(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }
        $row = Database::fetch(
            'SELECT COUNT(*) AS n
             FROM forum_posts p
             INNER JOIN forum_topic_follows f ON f.topic_id = p.topic_id AND f.user_id = ?
             INNER JOIN forum_topics t ON t.id = p.topic_id AND t.status = "visible"
             WHERE p.status = "visible"
               AND p.is_op = 0
               AND p.user_id != ?
               AND p.id > COALESCE(f.last_read_post_id, 0)',
            [$userId, $userId]
        );
        return (int) ($row['n'] ?? 0);
    }

    /**
     * @param list<int> $topicIds
     * @return array<int, int>
     */
    public static function unreadCountsForTopics(int $userId, array $topicIds): array
    {
        $topicIds = array_values(array_unique(array_filter(array_map('intval', $topicIds))));
        if ($userId <= 0 || $topicIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($topicIds), '?'));
        $params = array_merge([$userId, $userId], $topicIds);
        $rows = Database::fetchAll(
            "SELECT p.topic_id, COUNT(*) AS n
             FROM forum_posts p
             INNER JOIN forum_topic_follows f ON f.topic_id = p.topic_id AND f.user_id = ?
             WHERE p.status = \"visible\"
               AND p.is_op = 0
               AND p.user_id != ?
               AND p.topic_id IN ($placeholders)
               AND p.id > COALESCE(f.last_read_post_id, 0)
             GROUP BY p.topic_id",
            $params
        );
        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['topic_id']] = (int) ($row['n'] ?? 0);
        }
        return $out;
    }

    public static function toggleFollow(int $topicId, int $userId): bool
    {
        if (self::isFollowing($topicId, $userId)) {
            Database::query(
                'DELETE FROM forum_topic_follows WHERE topic_id = ? AND user_id = ?',
                [$topicId, $userId]
            );
            Database::query(
                'UPDATE forum_topics SET follow_count = GREATEST(follow_count - 1, 0) WHERE id = ?',
                [$topicId]
            );
            return false;
        }
        $max = Database::fetch(
            'SELECT MAX(id) AS n FROM forum_posts WHERE topic_id = ? AND status = "visible"',
            [$topicId]
        );
        Database::query(
            'INSERT INTO forum_topic_follows (topic_id, user_id, created_at, last_read_at, last_read_post_id)
             VALUES (?, ?, NOW(), NOW(), ?)
             ON DUPLICATE KEY UPDATE last_read_at = COALESCE(last_read_at, NOW()),
                                     last_read_post_id = COALESCE(last_read_post_id, VALUES(last_read_post_id))',
            [$topicId, $userId, (int) ($max['n'] ?? 0)]
        );
        Database::query('UPDATE forum_topics SET follow_count = follow_count + 1 WHERE id = ?', [$topicId]);
        return true;
    }

    /** @return list<string> */
    public static function normalizeTags(mixed $raw): array
    {
        if (is_string($raw)) {
            $parts = preg_split('/[,;]+/', $raw) ?: [];
        } elseif (is_array($raw)) {
            $parts = $raw;
        } else {
            $parts = [];
        }
        $out = [];
        foreach ($parts as $part) {
            $tag = mb_strtolower(trim((string) $part));
            $tag = preg_replace('/\s+/u', ' ', $tag) ?? $tag;
            if ($tag === '' || mb_strlen($tag) > 40) {
                continue;
            }
            $out[$tag] = $tag;
            if (count($out) >= 8) {
                break;
            }
        }
        return array_values($out);
    }

    /** @return list<string> */
    public static function decodeTags(mixed $json): array
    {
        if (!is_string($json) || $json === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }
        return self::normalizeTags($decoded);
    }

    /** @param list<string> $tags */
    public static function encodeTags(array $tags): ?string
    {
        $tags = self::normalizeTags($tags);
        return $tags === [] ? null : json_encode($tags, JSON_UNESCAPED_UNICODE);
    }

    public static function badge(array $topic): string
    {
        if (!empty($topic['is_solved'])) {
            return 'Résolu';
        }
        if ((int) ($topic['reply_count'] ?? 0) === 0) {
            return 'Sans réponse';
        }
        if ((int) ($topic['follow_count'] ?? 0) >= 20 || (int) ($topic['reply_count'] ?? 0) >= 30) {
            return 'Populaire';
        }
        if (($topic['status'] ?? '') === 'moderated') {
            return 'Modéré';
        }
        return '';
    }

    public static function shortCat(string $categoryName): string
    {
        $map = [
            'Tarifs et devis' => 'Tarifs',
            'Contrats et droits' => 'Contrats',
            'Fabrication' => 'Fabrication',
            'Écriture et relecture' => 'Écriture',
            'Diffusion et librairies' => 'Diffusion',
            'Vie de prestataire' => 'Métier',
            'Charte et IA' => 'Charte',
            'La plateforme' => 'Plateforme',
            'Édition' => 'Édition',
            'Divers' => 'Divers',
        ];
        return $map[$categoryName] ?? $categoryName;
    }

    /** @param array<string, mixed> $row */
    public static function present(array $row): array
    {
        $categorySlug = (string) ($row['category_slug'] ?? '');
        $slug = (string) ($row['slug'] ?? '');
        $categoryName = (string) ($row['category_name'] ?? '');
        $author = ForumPost::presentAuthor($row);
        $lastName = trim(((string) ($row['last_first_name'] ?? '')) . ' ' . ((string) ($row['last_last_name'] ?? '')));
        if ($lastName === '') {
            $lastName = (string) ($author['name'] ?? '');
        }
        $tags = self::decodeTags($row['tags_json'] ?? null);
        $replyCount = (int) ($row['reply_count'] ?? 0);
        $topic = [
            'id' => (int) ($row['id'] ?? 0),
            'category_id' => (int) ($row['category_id'] ?? 0),
            'user_id' => (int) ($row['user_id'] ?? 0),
            'title' => (string) ($row['title'] ?? ''),
            'slug' => $slug,
            'tags' => $tags,
            'tags_label' => implode(' · ', $tags),
            'is_pinned' => !empty($row['is_pinned']),
            'is_locked' => !empty($row['is_locked']),
            'is_solved' => !empty($row['is_solved']),
            'solved_post_id' => $row['solved_post_id'] !== null ? (int) $row['solved_post_id'] : null,
            'reply_count' => $replyCount,
            'view_count' => (int) ($row['view_count'] ?? 0),
            'follow_count' => (int) ($row['follow_count'] ?? 0),
            'last_post_at' => $row['last_post_at'] ?? null,
            'last_post_user_id' => $row['last_post_user_id'] !== null ? (int) $row['last_post_user_id'] : null,
            'last_by' => $replyCount === 0 ? 'aucune réponse' : $lastName,
            'last_when' => time_ago($row['last_post_at'] ?? null),
            'status' => (string) ($row['status'] ?? 'visible'),
            'created_at' => $row['created_at'] ?? null,
            'when' => time_ago($row['created_at'] ?? null),
            'category_name' => $categoryName,
            'category_slug' => $categorySlug,
            'category_short' => self::shortCat($categoryName),
            'category_href' => $categorySlug !== '' ? '/forum/' . $categorySlug : '/forum',
            'href' => ($categorySlug !== '' && $slug !== '') ? '/forum/' . $categorySlug . '/' . $slug : '/forum',
            'author' => $author,
            'views_label' => format_int((int) ($row['view_count'] ?? 0)),
        ];
        $topic['badge'] = self::badge($topic);
        $topic['tone'] = in_array($topic['category_short'], ['Tarifs', 'Charte'], true) ? 'orange' : 'navy';
        return $topic;
    }

    /** @return array<string, mixed>|null */
    private static function baseFetch(string $where, array $params): ?array
    {
        return Database::fetch(
            "SELECT t.*,
                    c.name AS category_name, c.slug AS category_slug,
                    u.first_name, u.last_name, u.avatar_url, u.platform_cofounder, u.created_at AS user_created_at,
                    p.title AS profile_title, p.city AS profile_city, p.verification_status,
                    p.trades_json, p.slug AS profile_slug,
                    lu.first_name AS last_first_name, lu.last_name AS last_last_name
             FROM forum_topics t
             INNER JOIN forum_categories c ON c.id = t.category_id
             INNER JOIN users u ON u.id = t.user_id
             LEFT JOIN profiles p ON p.user_id = u.id
             LEFT JOIN users lu ON lu.id = t.last_post_user_id
             WHERE $where
             LIMIT 1",
            $params
        );
    }
}
