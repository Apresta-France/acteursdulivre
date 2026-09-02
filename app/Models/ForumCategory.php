<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;

final class ForumCategory
{
    public static function all(): array
    {
        $rows = Database::fetchAll(
            'SELECT c.*,
                    (SELECT COUNT(*) FROM forum_topics t
                     WHERE t.category_id = c.id AND t.status = "visible") AS topic_count,
                    (SELECT MAX(t.last_post_at) FROM forum_topics t
                     WHERE t.category_id = c.id AND t.status = "visible") AS last_activity_at
             FROM forum_categories c
             ORDER BY c.position ASC, c.id ASC'
        );
        return array_map([self::class, 'present'], $rows);
    }

    public static function findBySlug(string $slug): ?array
    {
        $row = Database::fetch('SELECT * FROM forum_categories WHERE slug = ?', [$slug]);
        return $row ? self::present($row) : null;
    }

    public static function find(int $id): ?array
    {
        $row = Database::fetch('SELECT * FROM forum_categories WHERE id = ?', [$id]);
        return $row ? self::present($row) : null;
    }

    public static function countTopics(int $categoryId, string $filter = 'all'): int
    {
        $sql = 'SELECT COUNT(*) AS n FROM forum_topics WHERE category_id = ? AND status = "visible"';
        $params = [$categoryId];
        if ($filter === 'unanswered') {
            $sql .= ' AND reply_count = 0';
        } elseif ($filter === 'solved') {
            $sql .= ' AND is_solved = 1';
        }
        $row = Database::fetch($sql, $params);
        return (int) ($row['n'] ?? 0);
    }

    public static function countPosts(int $categoryId): int
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS n FROM forum_posts p
             INNER JOIN forum_topics t ON t.id = p.topic_id
             WHERE t.category_id = ? AND t.status = "visible" AND p.status = "visible"',
            [$categoryId]
        );
        return (int) ($row['n'] ?? 0);
    }

    /** @param array<string, mixed> $row */
    public static function present(array $row): array
    {
        $position = (int) ($row['position'] ?? 0);
        $slug = (string) ($row['slug'] ?? '');
        return [
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) ($row['name'] ?? ''),
            'slug' => $slug,
            'description' => (string) ($row['description'] ?? ''),
            'position' => $position,
            'n' => str_pad((string) $position, 2, '0', STR_PAD_LEFT),
            'topic_count' => (int) ($row['topic_count'] ?? 0),
            'last_activity_at' => $row['last_activity_at'] ?? null,
            'last_activity' => time_ago($row['last_activity_at'] ?? null),
            'href' => '/forum/' . $slug,
        ];
    }
}
