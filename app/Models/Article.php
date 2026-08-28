<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;

final class Article
{
    public static function findBySlug(string $slug): ?array
    {
        $row = Database::fetch('SELECT * FROM articles WHERE slug = ?', [$slug]);
        return $row ? self::present($row) : null;
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
