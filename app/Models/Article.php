<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;

final class Article
{
    public static function find(int $id): ?array
    {
        $row = Database::fetch('SELECT * FROM articles WHERE id = ?', [$id]);
        return $row ? self::present($row) : null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $row = Database::fetch('SELECT * FROM articles WHERE slug = ?', [$slug]);
        return $row ? self::present($row) : null;
    }

    /** @param array<string, mixed> $data */
    public static function save(?int $id, array $data): int
    {
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new \InvalidArgumentException('Le titre est obligatoire.');
        }
        $category = trim((string) ($data['category'] ?? '')) ?: 'Journal';
        $excerpt = trim((string) ($data['excerpt'] ?? '')) ?: null;
        $body = sanitize_rich_html((string) ($data['body'] ?? ''));
        $published = !empty($data['published']);
        $current = $id ? self::find($id) : null;
        $slugSource = trim((string) ($data['slug'] ?? '')) ?: $title;
        $slug = unique_slug(
            $slugSource,
            static fn (string $candidate): bool => Database::fetch(
                'SELECT id FROM articles WHERE slug = ? AND id != ?',
                [$candidate, $id ?? 0]
            ) !== null
        );
        $publishedAt = $published
            ? (($current['published_at'] ?? null) ?: date('Y-m-d H:i:s'))
            : null;

        if ($id && $current) {
            Database::query(
                'UPDATE articles SET title = ?, slug = ?, category = ?, excerpt = ?, body = ?, published_at = ? WHERE id = ?',
                [$title, $slug, $category, $excerpt, $body, $publishedAt, $id]
            );
            return $id;
        }

        Database::query(
            'INSERT INTO articles (title, slug, category, excerpt, body, published_at, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())',
            [$title, $slug, $category, $excerpt, $body, $publishedAt]
        );
        return (int) Database::lastId();
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM articles WHERE id = ?', [$id]);
    }

    /** @return list<array<string, mixed>> */
    public static function published(): array
    {
        $rows = Database::fetchAll(
            'SELECT * FROM articles
             WHERE published_at IS NOT NULL AND published_at <= NOW()
             ORDER BY published_at DESC'
        );
        return array_map([self::class, 'present'], $rows);
    }

    /** @return list<array<string, mixed>> */
    public static function preview(int $limit = 3): array
    {
        return array_slice(self::published(), 0, $limit);
    }

    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        $rows = Database::fetchAll('SELECT * FROM articles ORDER BY created_at DESC');
        return array_map([self::class, 'present'], $rows);
    }

    /** @param array<string, mixed> $row */
    private static function present(array $row): array
    {
        $body = (string) ($row['body'] ?? '');
        $words = max(1, str_word_count(strip_tags($body)));
        $row['cat'] = (string) ($row['category'] ?? 'Journal');
        $row['chapo'] = (string) ($row['excerpt'] ?? '');
        $row['read'] = max(1, (int) ceil($words / 200)) . ' min';
        $row['href'] = '/journal/' . $row['slug'];
        $row['img'] = photo(((int) ($row['id'] ?? 0)) % 6);
        $row['slotId'] = 'jr-' . ($row['id'] ?? '0');
        $row['published'] = !empty($row['published_at']);
        $row['status'] = $row['published'] ? 'Publié' : 'Brouillon';
        $row['when'] = $row['published_at'] ? format_deadline(substr((string) $row['published_at'], 0, 10)) : '';
        $row['live'] = true;
        return $row;
    }
}
