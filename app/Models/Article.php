<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;
use Adl\Data\ArticleHtml;

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
        $imagePath = array_key_exists('image_path', $data)
            ? (trim((string) ($data['image_path'] ?? '')) ?: null)
            : ($current['image_path'] ?? null);
        $imageAlt = trim((string) ($data['image_alt'] ?? '')) ?: null;

        if ($id && $current) {
            if (
                $imagePath !== ($current['image_path'] ?? null)
                && !str_starts_with((string) ($current['image_path'] ?? ''), 'img/')
            ) {
                self::deleteImageFile((string) ($current['image_path'] ?? ''));
            }
            Database::query(
                'UPDATE articles SET title = ?, slug = ?, category = ?, excerpt = ?, image_path = ?, image_alt = ?, body = ?, published_at = ? WHERE id = ?',
                [$title, $slug, $category, $excerpt, $imagePath, $imageAlt, $body, $publishedAt, $id]
            );
            return $id;
        }

        Database::query(
            'INSERT INTO articles (title, slug, category, excerpt, image_path, image_alt, body, published_at, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())',
            [$title, $slug, $category, $excerpt, $imagePath, $imageAlt, $body, $publishedAt]
        );
        return (int) Database::lastId();
    }

    public static function delete(int $id): void
    {
        $current = self::find($id);
        Database::query('DELETE FROM articles WHERE id = ?', [$id]);
        if ($current && !str_starts_with((string) ($current['image_path'] ?? ''), 'img/')) {
            self::deleteImageFile((string) ($current['image_path'] ?? ''));
        }
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
        $prepared = ArticleHtml::enhance(sanitize_rich_html((string) ($row['body'] ?? '')));
        $body = $prepared['html'];
        $words = max(1, str_word_count(strip_tags($body)));
        $imagePath = trim((string) ($row['image_path'] ?? ''));
        $row['body'] = $body;
        $row['body_html'] = $body;
        $row['toc'] = $prepared['toc'];
        $row['faqs'] = $prepared['faqs'];
        $row['cat'] = (string) ($row['category'] ?? 'Journal');
        $row['chapo'] = (string) ($row['excerpt'] ?? '');
        $row['read'] = max(1, (int) ceil($words / 200)) . ' min';
        $row['word_count'] = $words;
        $row['href'] = '/journal/' . $row['slug'];
        $row['img'] = $imagePath !== '' ? article_image_url($imagePath) : photo(((int) ($row['id'] ?? 0)) % 6);
        $row['has_cover'] = $imagePath !== '';
        $row['image_alt'] = trim((string) ($row['image_alt'] ?? '')) ?: (string) ($row['title'] ?? '');
        $row['slotId'] = 'jr-' . ($row['id'] ?? '0');
        $row['published'] = !empty($row['published_at']);
        $row['status'] = $row['published'] ? 'Publié' : 'Brouillon';
        $row['when'] = $row['published_at'] ? format_deadline(substr((string) $row['published_at'], 0, 10)) : '';
        $row['live'] = true;
        return $row;
    }

    private static function deleteImageFile(string $path): void
    {
        $path = trim(str_replace(['\\', "\0"], '/', $path));
        if ($path === '' || str_contains($path, '..') || str_starts_with($path, 'img/')) {
            return;
        }
        $full = ADL_ROOT . '/public/uploads/' . ltrim($path, '/');
        $root = realpath(ADL_ROOT . '/public/uploads');
        $real = realpath($full);
        if ($root === false || $real === false || !str_starts_with($real, $root) || !is_file($real)) {
            return;
        }
        @unlink($real);
    }
}
