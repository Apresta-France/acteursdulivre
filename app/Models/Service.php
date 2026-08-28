<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;

final class Service
{
    public const STATUSES = [
        'draft' => 'Brouillon',
        'published' => 'En ligne',
    ];

    public static function find(int $id): ?array
    {
        $row = Database::fetch(
            'SELECT s.*, u.first_name, u.last_name, p.slug AS profile_slug
             FROM services s
             JOIN users u ON u.id = s.user_id
             LEFT JOIN profiles p ON p.user_id = u.id
             WHERE s.id = ?',
            [$id]
        );
        return $row ? self::present($row) : null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $row = Database::fetch(
            'SELECT s.*, u.first_name, u.last_name, p.slug AS profile_slug
             FROM services s
             JOIN users u ON u.id = s.user_id
             LEFT JOIN profiles p ON p.user_id = u.id
             WHERE s.slug = ?',
            [$slug]
        );
        return $row ? self::present($row) : null;
    }

    /** @return list<array<string, mixed>> */
    public static function published(): array
    {
        $rows = Database::fetchAll(
            'SELECT s.*, u.first_name, u.last_name, p.slug AS profile_slug
             FROM services s
             JOIN users u ON u.id = s.user_id
             LEFT JOIN profiles p ON p.user_id = u.id
             WHERE s.status = "published"
               AND NOT EXISTS (
                    SELECT 1 FROM invoices i
                    WHERE i.seller_id = s.user_id
                      AND i.status IN ("issued", "overdue")
                      AND i.due_at < NOW()
               )
             ORDER BY s.created_at DESC'
        );
        return array_map([self::class, 'present'], $rows);
    }

    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        $rows = Database::fetchAll(
            'SELECT s.*, u.first_name, u.last_name, p.slug AS profile_slug
             FROM services s
             JOIN users u ON u.id = s.user_id
             LEFT JOIN profiles p ON p.user_id = u.id
             ORDER BY s.created_at DESC'
        );
        return array_map([self::class, 'present'], $rows);
    }

    /** @return list<array<string, mixed>> */
    public static function forUser(int $userId): array
    {
        $rows = Database::fetchAll(
            'SELECT s.*, u.first_name, u.last_name, p.slug AS profile_slug
             FROM services s
             JOIN users u ON u.id = s.user_id
             LEFT JOIN profiles p ON p.user_id = u.id
             WHERE s.user_id = ?
             ORDER BY s.created_at DESC',
            [$userId]
        );
        return array_map([self::class, 'present'], $rows);
    }

    public static function countPublished(): int
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS n FROM services s
             WHERE s.status = "published"
               AND NOT EXISTS (
                    SELECT 1 FROM invoices i
                    WHERE i.seller_id = s.user_id
                      AND i.status IN ("issued", "overdue")
                      AND i.due_at < NOW()
               )'
        );
        return (int) ($row['n'] ?? 0);
    }

    /**
     * @param array<string, mixed> $data
     * @param list<array<string, mixed>> $packages
     */
    public static function create(int $userId, array $data, array $packages = []): array
    {
        $status = (string) ($data['status'] ?? 'published');
        if ($status === 'published') {
            Invoice::assertCanOffer($userId);
        }

        $title = trim((string) ($data['title'] ?? ''));
        $slug = unique_slug(
            $title,
            static fn (string $candidate): bool => Database::fetch('SELECT id FROM services WHERE slug = ?', [$candidate]) !== null
        );

        $priceFrom = isset($data['price_from']) ? (int) $data['price_from'] : null;
        if ($priceFrom === null) {
            foreach ($packages as $package) {
                if (isset($package['price']) && (int) $package['price'] > 0) {
                    $priceFrom = (int) $package['price'];
                    break;
                }
            }
        }

        Database::query(
            'INSERT INTO services (user_id, category_name, specialty, title, slug, excerpt, image_path, status, price_from, delay)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $userId,
                $data['category_name'] ?? null,
                $data['specialty'] ?? null,
                $title,
                $slug,
                $data['excerpt'] ?? null,
                $data['image_path'] ?? null,
                $data['status'] ?? 'published',
                $priceFrom,
                $data['delay'] ?? null,
            ]
        );

        $id = (int) Database::lastId();
        self::replacePackages($id, $packages);

        return self::find($id) ?? ['slug' => $slug];
    }

    /** @param list<array<string, mixed>> $packages */
    public static function replacePackages(int $serviceId, array $packages): void
    {
        Database::query('DELETE FROM service_packages WHERE service_id = ?', [$serviceId]);
        foreach ($packages as $package) {
            $name = trim((string) ($package['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            Database::query(
                'INSERT INTO service_packages (service_id, name, description, price, delay) VALUES (?, ?, ?, ?, ?)',
                [
                    $serviceId,
                    $name,
                    trim((string) ($package['description'] ?? '')) ?: null,
                    (int) ($package['price'] ?? 0),
                    trim((string) ($package['delay'] ?? '')) ?: null,
                ]
            );
        }
    }

    /** @param array<string, mixed> $row */
    private static function present(array $row): array
    {
        $reviews = Review::statsForUser((int) $row['user_id']);
        $packages = Database::fetchAll(
            'SELECT * FROM service_packages WHERE service_id = ? ORDER BY id ASC',
            [(int) $row['id']]
        );
        foreach ($packages as &$package) {
            $package['price_label'] = format_euros((int) ($package['price'] ?? 0));
        }
        unset($package);

        $row['by'] = User::displayName($row);
        $row['initials'] = User::initials($row);
        $row['avatar'] = avatar_style($row['initials'], 26);
        $row['price'] = isset($row['price_from']) ? format_euros((int) $row['price_from']) : 'sur devis';
        $row['rating'] = $reviews['avg'];
        $row['reviews'] = $reviews['count'];
        $row['status_label'] = self::STATUSES[$row['status'] ?? 'draft'] ?? 'Brouillon';
        $row['href'] = '/prestations/' . $row['slug'];
        $row['profile_href'] = !empty($row['profile_slug']) ? '/prestataires/' . $row['profile_slug'] : '';
        $row['packages'] = $packages;
        $imagePath = trim((string) ($row['image_path'] ?? ''));
        $row['has_image'] = $imagePath !== '';
        $row['img'] = $imagePath !== '' ? uploaded($imagePath) : service_brand_cover_url((string) ($row['category_name'] ?? ''));
        $row['live'] = true;
        $row['kind'] = 'prestations';
        $row['kind_label'] = 'Prestation';
        $row['cat'] = (string) ($row['category_name'] ?? '');
        $row['specialty'] = (string) ($row['specialty'] ?? '');
        $row['subtitle'] = $row['by'];
        $row['meta'] = trim(($row['delay'] ?? '') . ($reviews['avg'] !== '' ? ' · ★ ' . $reviews['avg'] : ''));
        $row['thumb'] = $row['has_image'] ? $row['img'] : '';
        $row['search'] = $row['cat'] . ' ' . $row['specialty'] . ' ' . $row['title'] . ' ' . $row['by'] . ' ' . ($row['excerpt'] ?? '');
        return $row;
    }
}
