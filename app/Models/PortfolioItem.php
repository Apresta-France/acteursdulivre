<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;

final class PortfolioItem
{
    public const MEDIA_IMAGE = 'image';
    public const MEDIA_TEXT = 'text';
    public const MEDIA_PDF = 'pdf';
    public const MEDIA_AUDIO = 'audio';

    public const IMAGE_EXT = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    public const PDF_EXT = ['pdf'];
    public const AUDIO_EXT = ['mp3', 'wav', 'm4a', 'ogg', 'aac'];
    public const MAX_BYTES = Profile::PORTFOLIO_MAX_BYTES;

    /** @return list<string> */
    public static function extensionsFor(string $media): array
    {
        return match (self::normalizeMedia($media)) {
            self::MEDIA_PDF => self::PDF_EXT,
            self::MEDIA_AUDIO => self::AUDIO_EXT,
            default => self::IMAGE_EXT,
        };
    }

    public static function mediaLabel(string $media): string
    {
        return Profile::PORTFOLIO_MEDIA_TYPES[self::normalizeMedia($media)]
            ?? Profile::PORTFOLIO_MEDIA_TYPES[self::MEDIA_IMAGE];
    }

    public static function normalizeMedia(string $media): string
    {
        return array_key_exists($media, Profile::PORTFOLIO_MEDIA_TYPES) ? $media : self::MEDIA_IMAGE;
    }

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
            $media = self::normalizeMedia((string) ($item['media_type'] ?? self::MEDIA_IMAGE));
            $excerpt = trim((string) ($item['text_excerpt'] ?? ''));
            $excerpt = $excerpt !== '' ? mb_substr($excerpt, 0, 6000) : null;
            $payload = [
                $title,
                trim((string) ($item['description'] ?? '')) ?: null,
                $excerpt,
                trim((string) ($item['year'] ?? '')) ?: null,
                self::normalizeKind((string) ($item['kind'] ?? 'creation')),
                $media,
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
                         SET title = ?, description = ?, text_excerpt = ?, year = ?, kind = ?, media_type = ?,
                             image_path = ?, image_url = ?, sort_order = ?
                         WHERE id = ? AND profile_id = ?',
                        [...$payload, $id, $profileId]
                    );
                    $keep[] = $id;
                    continue;
                }
            }
            Database::query(
                'INSERT INTO portfolio_items
                    (profile_id, title, description, text_excerpt, year, kind, media_type, image_path, image_url, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
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
        if (self::normalizeMedia((string) ($item['media_type'] ?? self::MEDIA_IMAGE)) !== self::MEDIA_IMAGE) {
            return '';
        }
        $path = trim(str_replace(['\\', "\0"], '/', (string) ($item['image_path'] ?? '')));
        if ($path !== '' && !str_contains($path, '..')) {
            return uploaded($path);
        }
        $url = trim((string) ($item['image_url'] ?? ''));
        if ($url !== '' && preg_match('#^https?://#i', $url) === 1) {
            return $url;
        }
        return photo((int) ($item['id'] ?? 0) % 6);
    }

    /** @param array<string, mixed> $item */
    public static function fileUrl(array $item): string
    {
        $path = trim(str_replace(['\\', "\0"], '/', (string) ($item['image_path'] ?? '')));
        if ($path === '' || str_contains($path, '..')) {
            return '';
        }
        return uploaded($path);
    }

    public static function displayFileName(string $path): string
    {
        $name = basename(str_replace(['\\', "\0"], '/', $path));
        if (preg_match('/^[a-f0-9]{16}-(.+)$/i', $name, $m)) {
            return $m[1];
        }
        return $name;
    }

    public static function untitledLabel(string $media, int $n): string
    {
        return match (self::normalizeMedia($media)) {
            self::MEDIA_TEXT => 'Extrait ' . $n,
            self::MEDIA_PDF => 'Extrait PDF ' . $n,
            self::MEDIA_AUDIO => 'Échantillon ' . $n,
            default => 'Exemple ' . $n,
        };
    }

    public static function kindLabel(string $kind): string
    {
        return Profile::PORTFOLIO_KINDS[$kind] ?? Profile::PORTFOLIO_KINDS['creation'];
    }

    /** @param array<string, mixed> $row */
    private static function present(array $row): array
    {
        $media = self::normalizeMedia((string) ($row['media_type'] ?? self::MEDIA_IMAGE));
        $row['media_type'] = $media;
        $row['media_label'] = self::mediaLabel($media);
        $row['text_excerpt'] = (string) ($row['text_excerpt'] ?? '');
        $row['kind_label'] = self::kindLabel((string) ($row['kind'] ?? 'creation'));
        $row['file'] = '';
        $row['file_name'] = '';
        if ($media === self::MEDIA_IMAGE) {
            $row['image'] = self::image($row);
            return $row;
        }
        $row['image'] = '';
        $path = trim((string) ($row['image_path'] ?? ''));
        if ($path !== '') {
            $row['file'] = self::fileUrl($row);
            $row['file_name'] = self::displayFileName($path);
        }
        return $row;
    }

    private static function normalizeKind(string $kind): string
    {
        return array_key_exists($kind, Profile::PORTFOLIO_KINDS) ? $kind : 'creation';
    }
}
