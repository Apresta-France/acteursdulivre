<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;

final class PortfolioItem
{
    /** @return list<array<string, mixed>> */
    public static function forProfile(int $profileId): array
    {
        $rows = Database::fetchAll(
            'SELECT * FROM portfolio_items WHERE profile_id = ? ORDER BY sort_order ASC, id ASC',
            [$profileId]
        );
        return array_map([self::class, 'present'], $rows);
    }

    /**
     * @param list<array<string, mixed>> $items
     */
    public static function replace(int $profileId, array $items): void
    {
        $keep = [];
        foreach ($items as $i => $item) {
            $title = trim((string) ($item['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $payload = [
                $title,
                trim((string) ($item['description'] ?? '')) ?: null,
                trim((string) ($item['year'] ?? '')) ?: null,
                self::normalizeKind((string) ($item['kind'] ?? 'creation')),
                $item['image_path'] ?? null,
                trim((string) ($item['image_url'] ?? '')) ?: null,
                $i,
            ];
            $id = (int) ($item['id'] ?? 0);
            if ($id > 0) {
                $existing = Database::fetch(
                    'SELECT id FROM portfolio_items WHERE id = ? AND profile_id = ?',
                    [$id, $profileId]
                );
                if ($existing) {
                    Database::query(
                        'UPDATE portfolio_items
                         SET title = ?, description = ?, year = ?, kind = ?, image_path = ?, image_url = ?, sort_order = ?
                         WHERE id = ? AND profile_id = ?',
                        [...$payload, $id, $profileId]
                    );
                    $keep[] = $id;
                    continue;
                }
            }
            Database::query(
                'INSERT INTO portfolio_items (profile_id, title, description, year, kind, image_path, image_url, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$profileId, ...$payload]
            );
            $keep[] = (int) Database::lastId();
        }

        if ($keep === []) {
            Database::query('DELETE FROM portfolio_items WHERE profile_id = ?', [$profileId]);
            return;
        }

        $placeholders = implode(',', array_fill(0, count($keep), '?'));
        Database::query(
            'DELETE FROM portfolio_items WHERE profile_id = ? AND id NOT IN (' . $placeholders . ')',
            [$profileId, ...$keep]
        );
    }

    /** @param array<string, mixed> $item */
    public static function image(array $item): string
    {
        $path = trim((string) ($item['image_path'] ?? ''));
        if ($path !== '') {
            return uploaded($path);
        }
        $url = trim((string) ($item['image_url'] ?? ''));
        if ($url !== '') {
            return $url;
        }
        return photo((int) ($item['id'] ?? 0) % 6);
    }

    public static function kindLabel(string $kind): string
    {
        return Profile::PORTFOLIO_KINDS[$kind] ?? Profile::PORTFOLIO_KINDS['creation'];
    }

    /** @param array<string, mixed> $row */
    private static function present(array $row): array
    {
        $row['image'] = self::image($row);
        $row['kind_label'] = self::kindLabel((string) ($row['kind'] ?? 'creation'));
        return $row;
    }

    private static function normalizeKind(string $kind): string
    {
        return array_key_exists($kind, Profile::PORTFOLIO_KINDS) ? $kind : 'creation';
    }
}
