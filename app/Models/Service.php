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

    public const IMAGE_MAX = 5;

    /**
     * @return array{enabled: int, kind: ?string, value: ?int}
     */
    public static function normalizeStartup(bool $enabled, string $kind, ?int $value): array
    {
        if (!$enabled) {
            return ['enabled' => 0, 'kind' => null, 'value' => null];
        }
        $kind = $kind === 'percent' ? 'percent' : 'amount';
        $value = (int) $value;
        if ($kind === 'percent') {
            if ($value < 1 || $value > 100) {
                throw new \RuntimeException('Indiquez un pourcentage d’accompagnement entre 1 et 100.');
            }
        } elseif ($value < 1) {
            throw new \RuntimeException('Indiquez le montant de l’accompagnement de démarrage.');
        }

        return ['enabled' => 1, 'kind' => $kind, 'value' => $value];
    }

    public static function computeStartupAmount(int $base, string $kind, int $value): int
    {
        if ($kind === 'percent') {
            return max(0, (int) round($base * min(100, max(0, $value)) / 100));
        }

        return min(max(0, $base), max(0, $value));
    }

    /**
     * @param array<string, mixed> $service
     * @return array{startup_enabled: int, startup_kind: ?string, startup_value: ?int, deposit_amount: int}
     */
    public static function startupSnapshot(array $service, int $amount): array
    {
        if (empty($service['startup_enabled'])) {
            return [
                'startup_enabled' => 0,
                'startup_kind' => null,
                'startup_value' => null,
                'deposit_amount' => 0,
            ];
        }
        $kind = (string) ($service['startup_kind'] ?? '') === 'percent' ? 'percent' : 'amount';
        $value = (int) ($service['startup_value'] ?? 0);

        return [
            'startup_enabled' => 1,
            'startup_kind' => $kind,
            'startup_value' => $value,
            'deposit_amount' => self::computeStartupAmount($amount, $kind, $value),
        ];
    }

    /** @param array<string, mixed> $row */
    public static function startupLabel(array $row): string
    {
        if (empty($row['startup_enabled'])) {
            return '';
        }
        $value = (int) ($row['startup_value'] ?? 0);
        if ((string) ($row['startup_kind'] ?? '') === 'percent') {
            return $value . ' % du montant';
        }

        return format_euros_ttc($value);
    }

    public static function find(int $id): ?array
    {
        $row = Database::fetch(
            'SELECT ' . self::sellerSelect() . '
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
            'SELECT ' . self::sellerSelect() . '
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
            'SELECT ' . self::sellerSelect() . '
             FROM services s
             JOIN users u ON u.id = s.user_id
             LEFT JOIN profiles p ON p.user_id = u.id
             WHERE s.status = "published"
               AND u.status = "active"
               AND u.offers_services = 1
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
            'SELECT ' . self::sellerSelect() . '
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
            'SELECT ' . self::sellerSelect() . '
             FROM services s
             JOIN users u ON u.id = s.user_id
             LEFT JOIN profiles p ON p.user_id = u.id
             WHERE s.user_id = ?
             ORDER BY s.created_at DESC',
            [$userId]
        );
        return array_map([self::class, 'present'], $rows);
    }

    private static function sellerSelect(): string
    {
        static $sql = null;
        if ($sql !== null) {
            return $sql;
        }
        $sql = 's.*, u.first_name, u.last_name, p.slug AS profile_slug, p.city AS seller_city';
        try {
            $col = Database::fetch("SHOW COLUMNS FROM profiles LIKE 'city_slug'");
            if ($col !== null) {
                $sql .= ', p.city_slug AS seller_city_slug, p.city_area_slug AS seller_city_area_slug';
            }
        } catch (\Throwable) {
        }
        return $sql;
    }

    public static function setStatus(int $id, string $status): void
    {
        if (!isset(self::STATUSES[$status])) {
            throw new \InvalidArgumentException('Statut de prestation invalide.');
        }
        $service = self::find($id);
        if (!$service) {
            throw new \RuntimeException('Prestation introuvable.');
        }
        if ($status === 'published') {
            Invoice::assertCanOffer((int) $service['user_id']);
        }
        Database::query('UPDATE services SET status = ? WHERE id = ?', [$status, $id]);
    }

    public static function countPublished(): int
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS n FROM services s
             WHERE s.status = "published"
               AND EXISTS (
                    SELECT 1 FROM users u
                    WHERE u.id = s.user_id
                      AND u.status = "active"
                      AND u.offers_services = 1
               )
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
     * @param list<array<string, mixed>> $options
     */
    public static function create(int $userId, array $data, array $packages = [], array $options = []): array
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

        $priceFrom = self::resolvePriceFrom(
            isset($data['price_from']) ? (int) $data['price_from'] : null,
            $packages
        );

        $imagePaths = self::normalizeImagePaths($data);
        $portfolioUrl = self::normalizePortfolioUrl($data['portfolio_url'] ?? null);

        $startup = self::startupFields($data);
        $id = (int) Database::transaction(static function () use ($userId, $data, $title, $slug, $imagePaths, $portfolioUrl, $priceFrom, $packages, $options, $startup): int {
            Database::query(
                'INSERT INTO services (user_id, category_name, specialty, title, slug, excerpt, image_path, portfolio_url, status, price_from, delay, startup_enabled, startup_kind, startup_value)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $userId,
                    $data['category_name'] ?? null,
                    $data['specialty'] ?? null,
                    $title,
                    $slug,
                    $data['excerpt'] ?? null,
                    $imagePaths[0] ?? null,
                    $portfolioUrl,
                    $data['status'] ?? 'published',
                    $priceFrom,
                    $data['delay'] ?? null,
                    $startup['enabled'],
                    $startup['kind'],
                    $startup['value'],
                ]
            );

            $newId = (int) Database::lastId();
            self::replacePackages($newId, $packages);
            self::replaceOptions($newId, $options);
            self::replaceExtraImages($newId, $imagePaths);
            return $newId;
        });

        try {
            return self::find($id) ?? ['id' => $id, 'slug' => $slug];
        } catch (\Throwable) {
            return ['id' => $id, 'slug' => $slug];
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param list<array<string, mixed>> $packages
     * @param list<array<string, mixed>> $options
     */
    public static function update(int $id, int $userId, array $data, array $packages = [], array $options = []): array
    {
        $service = self::find($id);
        if (!$service || (int) ($service['user_id'] ?? 0) !== $userId) {
            throw new \RuntimeException('Cette prestation est introuvable.');
        }

        $status = (string) ($data['status'] ?? $service['status'] ?? 'published');
        if ($status === 'published') {
            Invoice::assertCanOffer($userId);
        }

        $title = trim((string) ($data['title'] ?? $service['title'] ?? ''));
        $priceFrom = self::resolvePriceFrom(
            isset($data['price_from']) ? (int) $data['price_from'] : null,
            $packages
        );

        $previousPaths = self::imagePaths($service);
        $imagePaths = array_key_exists('images', $data) || array_key_exists('image_path', $data)
            ? self::normalizeImagePaths($data)
            : $previousPaths;
        $portfolioUrl = array_key_exists('portfolio_url', $data)
            ? self::normalizePortfolioUrl($data['portfolio_url'] ?? null)
            : self::normalizePortfolioUrl($service['portfolio_url'] ?? null);

        $startup = array_key_exists('startup_enabled', $data)
            ? self::startupFields($data)
            : [
                'enabled' => !empty($service['startup_enabled']) ? 1 : 0,
                'kind' => $service['startup_kind'] ?? null,
                'value' => isset($service['startup_value']) ? (int) $service['startup_value'] : null,
            ];
        Database::transaction(static function () use ($id, $userId, $data, $service, $title, $imagePaths, $portfolioUrl, $status, $priceFrom, $packages, $options, $startup): void {
            Database::query(
                'UPDATE services
                 SET category_name = ?, specialty = ?, title = ?, excerpt = ?, image_path = ?, portfolio_url = ?, status = ?, price_from = ?, delay = ?, startup_enabled = ?, startup_kind = ?, startup_value = ?
                 WHERE id = ? AND user_id = ?',
                [
                    $data['category_name'] ?? $service['category_name'] ?? null,
                    $data['specialty'] ?? $service['specialty'] ?? null,
                    $title,
                    $data['excerpt'] ?? $service['excerpt'] ?? null,
                    $imagePaths[0] ?? null,
                    $portfolioUrl,
                    $status,
                    $priceFrom,
                    $data['delay'] ?? $service['delay'] ?? null,
                    $startup['enabled'],
                    $startup['kind'],
                    $startup['value'],
                    $id,
                    $userId,
                ]
            );
            self::replacePackages($id, $packages);
            self::replaceOptions($id, $options);
            self::replaceExtraImages($id, $imagePaths);
        });
        self::deleteObsoleteImages($imagePaths, $previousPaths);

        try {
            return self::find($id) ?? $service;
        } catch (\Throwable) {
            $service['slug'] = $service['slug'] ?? '';
            $service['id'] = $id;
            return $service;
        }
    }

    public static function deleteForUser(int $id, int $userId): void
    {
        $service = self::find($id);
        if (!$service || (int) ($service['user_id'] ?? 0) !== $userId) {
            throw new \RuntimeException('Cette prestation est introuvable.');
        }

        $open = Database::fetch(
            'SELECT id FROM orders
             WHERE service_id = ?
               AND status IN ("pending", "in_progress", "delivered", "dispute")
             LIMIT 1',
            [$id]
        );
        if ($open) {
            throw new \RuntimeException(
                'Cette prestation a une commande en cours. Terminez le suivi avant de la supprimer, ou enregistrez-la en brouillon pour la retirer de l\'annuaire.'
            );
        }

        Database::transaction(static function () use ($id, $userId): void {
            Database::query('DELETE FROM favorites WHERE service_id = ?', [$id]);
            Database::query('UPDATE orders SET service_id = NULL WHERE service_id = ?', [$id]);
            try {
                Database::query('UPDATE conversations SET service_id = NULL WHERE service_id = ?', [$id]);
            } catch (\Throwable) {
            }
            Database::query('DELETE FROM services WHERE id = ? AND user_id = ?', [$id, $userId]);
        });

        foreach (self::imagePaths($service) as $path) {
            self::deleteImageFile($path);
        }
    }

    /**
     * @param array<string, mixed> $service
     * @return list<string>
     */
    public static function imagePaths(array $service): array
    {
        if (isset($service['image_paths']) && is_array($service['image_paths'])) {
            $out = [];
            foreach ($service['image_paths'] as $path) {
                $path = trim((string) $path);
                if ($path !== '' && !in_array($path, $out, true)) {
                    $out[] = $path;
                }
            }
            return $out;
        }
        $one = trim((string) ($service['image_path'] ?? ''));
        return $one !== '' ? [$one] : [];
    }

    /**
     * @param array<string, mixed> $data
     * @return list<string>
     */
    private static function normalizeImagePaths(array $data): array
    {
        $raw = $data['images'] ?? [];
        if (!is_array($raw) || $raw === []) {
            $one = trim((string) ($data['image_path'] ?? ''));
            $raw = $one !== '' ? [$one] : [];
        }
        $out = [];
        foreach ($raw as $path) {
            $path = trim((string) $path);
            if ($path === '' || str_contains($path, '..') || str_contains($path, "\0")) {
                continue;
            }
            if (!in_array($path, $out, true)) {
                $out[] = $path;
            }
            if (count($out) >= self::IMAGE_MAX) {
                break;
            }
        }
        return $out;
    }

    private static function normalizePortfolioUrl(mixed $value): ?string
    {
        $url = trim((string) $value);
        if ($url === '') {
            return null;
        }
        return mb_strlen($url) > 500 ? null : $url;
    }

    /** @param list<string> $paths */
    public static function discardImageFiles(array $paths): void
    {
        foreach ($paths as $path) {
            self::deleteImageFile((string) $path);
        }
    }

    /** @param list<string> $paths */
    private static function replaceExtraImages(int $serviceId, array $paths): void
    {
        Database::query('DELETE FROM service_images WHERE service_id = ?', [$serviceId]);
        foreach (array_slice($paths, 1) as $i => $path) {
            Database::query(
                'INSERT INTO service_images (service_id, image_path, sort_order) VALUES (?, ?, ?)',
                [$serviceId, $path, $i]
            );
        }
    }

    /**
     * @param list<string> $paths
     * @param list<string> $previousPaths
     */
    private static function deleteObsoleteImages(array $paths, array $previousPaths): void
    {
        foreach ($previousPaths as $old) {
            if (!in_array($old, $paths, true)) {
                self::deleteImageFile($old);
            }
        }
    }

    private static function deleteImageFile(string $path): void
    {
        $path = trim(str_replace(['\\', "\0"], '/', $path));
        if ($path === '' || str_contains($path, '..')) {
            return;
        }
        $full = ADL_ROOT . '/public/uploads/' . ltrim($path, '/');
        $root = realpath(ADL_ROOT . '/public/uploads');
        $real = realpath($full);
        if ($root === false || $real === false || !is_file($real)) {
            return;
        }
        $prefix = rtrim($root, '/\\') . DIRECTORY_SEPARATOR;
        if ($real !== $root && !str_starts_with($real, $prefix)) {
            return;
        }
        @unlink($real);
    }

    /**
     * Prix affiché / commandable de départ : la formule la moins chère l’emporte.
     *
     * @param list<array<string, mixed>> $packages
     */
    public static function resolvePriceFrom(?int $priceFrom, array $packages): ?int
    {
        $fromPackages = self::lowestPackagePrice($packages);
        if ($fromPackages !== null) {
            return $fromPackages;
        }

        return ($priceFrom !== null && $priceFrom > 0) ? $priceFrom : null;
    }

    /**
     * @param list<array<string, mixed>> $packages
     */
    public static function lowestPackagePrice(array $packages): ?int
    {
        $prices = [];
        foreach ($packages as $package) {
            if (!is_array($package)) {
                continue;
            }
            $price = (int) ($package['price'] ?? 0);
            if ($price > 0) {
                $prices[] = $price;
            }
        }

        return $prices !== [] ? min($prices) : null;
    }

    /** @param list<array<string, mixed>> $packages */
    public static function replacePackages(int $serviceId, array $packages): void
    {
        Database::transaction(static function () use ($serviceId, $packages): void {
            $existing = [];
            foreach (Database::fetchAll('SELECT id FROM service_packages WHERE service_id = ?', [$serviceId]) as $row) {
                $existing[(int) $row['id']] = true;
            }
            $kept = [];
            foreach ($packages as $package) {
                $name = trim((string) ($package['name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $description = trim((string) ($package['description'] ?? '')) ?: null;
                $price = (int) ($package['price'] ?? 0);
                $delay = trim((string) ($package['delay'] ?? '')) ?: null;
                $id = (int) ($package['id'] ?? 0);
                if ($id > 0 && isset($existing[$id]) && !isset($kept[$id])) {
                    Database::query(
                        'UPDATE service_packages SET name = ?, description = ?, price = ?, delay = ? WHERE id = ? AND service_id = ?',
                        [$name, $description, $price, $delay, $id, $serviceId]
                    );
                    $kept[$id] = true;
                    continue;
                }
                Database::query(
                    'INSERT INTO service_packages (service_id, name, description, price, delay) VALUES (?, ?, ?, ?, ?)',
                    [$serviceId, $name, $description, $price, $delay]
                );
            }
            $obsolete = array_diff(array_keys($existing), array_keys($kept));
            if ($obsolete === []) {
                return;
            }
            $in = implode(',', array_fill(0, count($obsolete), '?'));
            Database::query(
                'DELETE FROM service_packages WHERE service_id = ? AND id IN (' . $in . ')',
                [$serviceId, ...$obsolete]
            );
        });
    }

    /** @param list<array<string, mixed>> $options */
    public static function replaceOptions(int $serviceId, array $options): void
    {
        Database::transaction(static function () use ($serviceId, $options): void {
            $existing = [];
            foreach (Database::fetchAll('SELECT id FROM service_options WHERE service_id = ?', [$serviceId]) as $row) {
                $existing[(int) $row['id']] = true;
            }
            $kept = [];
            foreach ($options as $option) {
                $name = trim((string) ($option['name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $price = (int) ($option['price'] ?? 0);
                $id = (int) ($option['id'] ?? 0);
                if ($id > 0 && isset($existing[$id]) && !isset($kept[$id])) {
                    Database::query(
                        'UPDATE service_options SET name = ?, price = ? WHERE id = ? AND service_id = ?',
                        [$name, $price, $id, $serviceId]
                    );
                    $kept[$id] = true;
                    continue;
                }
                Database::query(
                    'INSERT INTO service_options (service_id, name, price) VALUES (?, ?, ?)',
                    [$serviceId, $name, $price]
                );
            }
            $obsolete = array_diff(array_keys($existing), array_keys($kept));
            if ($obsolete === []) {
                return;
            }
            $in = implode(',', array_fill(0, count($obsolete), '?'));
            Database::query(
                'DELETE FROM service_options WHERE service_id = ? AND id IN (' . $in . ')',
                [$serviceId, ...$obsolete]
            );
        });
    }

    /**
     * @param array<string, mixed> $service
     * @param list<mixed> $ids
     * @return list<array{id: int, name: string, price: int}>
     */
    public static function pickOptions(array $service, array $ids): array
    {
        $wanted = [];
        foreach ($ids as $id) {
            if (is_array($id)) {
                continue;
            }
            $id = (int) $id;
            if ($id > 0) {
                $wanted[$id] = true;
            }
        }
        $picked = [];
        foreach ($service['options'] ?? [] as $option) {
            if (!is_array($option)) {
                continue;
            }
            $id = (int) ($option['id'] ?? 0);
            if ($id < 1 || !isset($wanted[$id])) {
                continue;
            }
            $picked[] = [
                'id' => $id,
                'name' => (string) ($option['name'] ?? ''),
                'price' => (int) ($option['price'] ?? 0),
            ];
        }
        return $picked;
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
            $package['price_label'] = format_euros_ttc((int) ($package['price'] ?? 0));
        }
        unset($package);

        // Affichage « à partir de » = formule la moins chère (pas un tarif unitaire saisi à part).
        $listedFromPackages = self::lowestPackagePrice($packages);
        if ($listedFromPackages !== null) {
            $row['price_from'] = $listedFromPackages;
        }

        $options = [];
        try {
            $options = Database::fetchAll(
                'SELECT * FROM service_options WHERE service_id = ? ORDER BY id ASC',
                [(int) $row['id']]
            );
        } catch (\Throwable) {
        }
        foreach ($options as &$option) {
            $option['price_label'] = format_euros_ttc((int) ($option['price'] ?? 0));
        }
        unset($option);

        $row['by'] = User::displayName($row);
        $row['initials'] = User::initials($row);
        $row['avatar'] = avatar_style($row['initials'], 26);
        $row['price'] = isset($row['price_from']) ? format_euros_ttc((int) $row['price_from']) : 'sur devis';
        $row['rating'] = $reviews['avg'];
        $row['reviews'] = $reviews['count'];
        $row['status_label'] = self::STATUSES[$row['status'] ?? 'draft'] ?? 'Brouillon';
        $row['href'] = '/prestations/' . $row['slug'];
        $row['profile_href'] = !empty($row['profile_slug']) ? '/prestataires/' . $row['profile_slug'] : '';
        $row['packages'] = $packages;
        $row['options'] = $options;
        $imagePaths = [];
        $imagePath = trim((string) ($row['image_path'] ?? ''));
        if ($imagePath !== '') {
            $imagePaths[] = $imagePath;
        }
        try {
            foreach (Database::fetchAll(
                'SELECT image_path FROM service_images WHERE service_id = ? ORDER BY sort_order ASC, id ASC',
                [(int) $row['id']]
            ) as $extra) {
                $path = trim((string) ($extra['image_path'] ?? ''));
                if ($path !== '' && !in_array($path, $imagePaths, true)) {
                    $imagePaths[] = $path;
                }
            }
        } catch (\Throwable) {
        }
        $row['image_paths'] = $imagePaths;
        $row['images'] = array_map(static fn (string $path): string => uploaded($path), $imagePaths);
        $row['has_image'] = $imagePaths !== [];
        $row['img'] = $imagePaths !== [] ? $row['images'][0] : service_brand_cover_url((string) ($row['category_name'] ?? ''));
        $row['portfolio_url'] = trim((string) ($row['portfolio_url'] ?? ''));
        $row['live'] = true;
        $row['kind'] = 'prestations';
        $row['kind_label'] = 'Prestation';
        $row['cat'] = (string) ($row['category_name'] ?? '');
        $row['specialty'] = (string) ($row['specialty'] ?? '');
        $city = trim((string) ($row['seller_city'] ?? $row['city'] ?? ''));
        $citySlug = trim((string) ($row['seller_city_slug'] ?? $row['city_slug'] ?? ''));
        $cityArea = trim((string) ($row['seller_city_area_slug'] ?? $row['city_area_slug'] ?? ''));
        if ($city !== '' && ($citySlug === '' || $cityArea === '')) {
            $norm = \Adl\Data\Cities::fromFreeText($city);
            $citySlug = $citySlug !== '' ? $citySlug : $norm['slug'];
            $cityArea = $cityArea !== '' ? $cityArea : $norm['area_slug'];
        }
        $row['city'] = $city;
        $row['city_slug'] = $citySlug;
        $row['city_area_slug'] = $cityArea;
        $row['subtitle'] = $row['by'] . ($city !== '' ? ' · ' . $city : '');
        $row['meta'] = trim(($row['delay'] ?? '') . ($reviews['avg'] !== '' ? ' · ★ ' . $reviews['avg'] : ''));
        $row['thumb'] = $row['has_image'] ? $row['img'] : '';
        $row['cover'] = $row['has_image'] ? '' : $row['img'];
        $row['search'] = $row['cat'] . ' ' . $row['specialty'] . ' ' . $row['title'] . ' ' . $row['by'] . ' ' . $city . ' ' . plain_text((string) ($row['excerpt'] ?? ''));
        $row['startup_enabled'] = !empty($row['startup_enabled']);
        $row['startup_kind'] = $row['startup_enabled']
            ? ((string) ($row['startup_kind'] ?? '') === 'percent' ? 'percent' : 'amount')
            : '';
        $row['startup_value'] = $row['startup_enabled'] ? (int) ($row['startup_value'] ?? 0) : 0;
        $row['startup_label'] = self::startupLabel($row);
        return $row;
    }

    /**
     * @param array<string, mixed> $data
     * @return array{enabled: int, kind: ?string, value: ?int}
     */
    private static function startupFields(array $data): array
    {
        return self::normalizeStartup(
            !empty($data['startup_enabled']),
            (string) ($data['startup_kind'] ?? 'amount'),
            isset($data['startup_value']) ? (int) $data['startup_value'] : null
        );
    }
}
