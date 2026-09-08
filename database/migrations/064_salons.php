<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS salons (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(190) NOT NULL,
            name VARCHAR(255) NOT NULL,
            category VARCHAR(120) NOT NULL DEFAULT "",
            category_slug VARCHAR(120) NOT NULL DEFAULT "",
            type_label VARCHAR(190) NOT NULL DEFAULT "",
            is_direct TINYINT(1) NOT NULL DEFAULT 1,
            dates_raw VARCHAR(255) NOT NULL DEFAULT "",
            starts_on DATE NULL,
            ends_on DATE NULL,
            dates_confirmed TINYINT(1) NOT NULL DEFAULT 0,
            city VARCHAR(120) NOT NULL DEFAULT "",
            department VARCHAR(120) NOT NULL DEFAULT "",
            region VARCHAR(120) NOT NULL DEFAULT "",
            region_slug VARCHAR(120) NOT NULL DEFAULT "",
            country VARCHAR(120) NOT NULL DEFAULT "",
            country_slug VARCHAR(120) NOT NULL DEFAULT "",
            venue VARCHAR(255) NOT NULL DEFAULT "",
            website VARCHAR(255) NOT NULL DEFAULT "",
            attendance VARCHAR(255) NOT NULL DEFAULT "",
            exhibitors VARCHAR(255) NOT NULL DEFAULT "",
            ticket VARCHAR(255) NOT NULL DEFAULT "",
            organizer VARCHAR(255) NOT NULL DEFAULT "",
            contact VARCHAR(255) NOT NULL DEFAULT "",
            socials VARCHAR(255) NOT NULL DEFAULT "",
            description TEXT NULL,
            audience VARCHAR(190) NOT NULL DEFAULT "",
            notes TEXT NULL,
            search_text TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY salons_slug (slug),
            KEY salons_dates (starts_on, name),
            KEY salons_cat (category_slug, starts_on),
            KEY salons_geo (country_slug, region_slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $path = ADL_ROOT . '/database/seeds/salons.json';
    if (!is_file($path)) {
        return;
    }
    $items = json_decode((string) file_get_contents($path), true);
    if (!is_array($items)) {
        return;
    }

    $exists = (int) ($pdo->query('SELECT COUNT(*) FROM salons')->fetchColumn() ?: 0);
    if ($exists > 0) {
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO salons (
            slug, name, category, category_slug, type_label, is_direct,
            dates_raw, starts_on, ends_on, dates_confirmed,
            city, department, region, region_slug, country, country_slug,
            venue, website, attendance, exhibitors, ticket, organizer, contact, socials,
            description, audience, notes, search_text
        ) VALUES (
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?
        )'
    );

    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $name = trim((string) ($item['name'] ?? ''));
        $slug = trim((string) ($item['slug'] ?? ''));
        if ($name === '' || $slug === '') {
            continue;
        }
        $category = trim((string) ($item['category'] ?? ''));
        $region = trim((string) ($item['region'] ?? ''));
        $country = trim((string) ($item['country'] ?? 'France'));
        $search = search_norm(implode(' ', array_filter([
            $name,
            $category,
            (string) ($item['type_label'] ?? ''),
            (string) ($item['city'] ?? ''),
            $region,
            $country,
            (string) ($item['description'] ?? ''),
            (string) ($item['organizer'] ?? ''),
        ])));
        $stmt->execute([
            $slug,
            $name,
            $category,
            slugify($category),
            trim((string) ($item['type_label'] ?? '')),
            !empty($item['direct']) ? 1 : 0,
            trim((string) ($item['dates_raw'] ?? '')),
            $item['starts_on'] ?: null,
            $item['ends_on'] ?: null,
            !empty($item['dates_confirmed']) ? 1 : 0,
            trim((string) ($item['city'] ?? '')),
            trim((string) ($item['department'] ?? '')),
            $region,
            slugify($region),
            $country,
            slugify($country),
            trim((string) ($item['venue'] ?? '')),
            trim((string) ($item['website'] ?? '')),
            trim((string) ($item['attendance'] ?? '')),
            trim((string) ($item['exhibitors'] ?? '')),
            trim((string) ($item['ticket'] ?? '')),
            trim((string) ($item['organizer'] ?? '')),
            trim((string) ($item['contact'] ?? '')),
            trim((string) ($item['socials'] ?? '')),
            trim((string) ($item['description'] ?? '')),
            trim((string) ($item['audience'] ?? '')),
            trim((string) ($item['notes'] ?? '')),
            $search,
        ]);
    }
};
