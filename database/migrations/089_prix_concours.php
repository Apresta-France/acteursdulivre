<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS contests (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(190) NOT NULL,
            name VARCHAR(255) NOT NULL,
            kind VARCHAR(60) NOT NULL DEFAULT "prix",
            kind_label VARCHAR(120) NOT NULL DEFAULT "",
            category VARCHAR(120) NOT NULL DEFAULT "",
            category_slug VARCHAR(120) NOT NULL DEFAULT "",
            opens_on DATE NULL,
            deadline_on DATE NULL,
            awarded_on DATE NULL,
            dates_raw VARCHAR(255) NOT NULL DEFAULT "",
            dates_confirmed TINYINT(1) NOT NULL DEFAULT 0,
            prize VARCHAR(255) NOT NULL DEFAULT "",
            entry_fee VARCHAR(255) NOT NULL DEFAULT "",
            eligibility VARCHAR(255) NOT NULL DEFAULT "",
            city VARCHAR(120) NOT NULL DEFAULT "",
            department VARCHAR(120) NOT NULL DEFAULT "",
            region VARCHAR(120) NOT NULL DEFAULT "",
            region_slug VARCHAR(120) NOT NULL DEFAULT "",
            country VARCHAR(120) NOT NULL DEFAULT "",
            country_slug VARCHAR(120) NOT NULL DEFAULT "",
            venue VARCHAR(255) NOT NULL DEFAULT "",
            website VARCHAR(255) NOT NULL DEFAULT "",
            organizer VARCHAR(255) NOT NULL DEFAULT "",
            contact VARCHAR(255) NOT NULL DEFAULT "",
            description TEXT NULL,
            audience VARCHAR(190) NOT NULL DEFAULT "",
            notes TEXT NULL,
            search_text TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY contests_slug (slug),
            KEY contests_deadline (deadline_on, name),
            KEY contests_cat (category_slug, deadline_on),
            KEY contests_geo (country_slug, region_slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $path = ADL_ROOT . '/database/seeds/prix.json';
    if (!is_file($path)) {
        return;
    }
    $items = json_decode((string) file_get_contents($path), true);
    if (!is_array($items)) {
        return;
    }

    $exists = (int) ($pdo->query('SELECT COUNT(*) FROM contests')->fetchColumn() ?: 0);
    if ($exists > 0) {
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO contests (
            slug, name, kind, kind_label, category, category_slug,
            opens_on, deadline_on, awarded_on, dates_raw, dates_confirmed,
            prize, entry_fee, eligibility,
            city, department, region, region_slug, country, country_slug,
            venue, website, organizer, contact,
            description, audience, notes, search_text
        ) VALUES (
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
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
        $kind = trim((string) ($item['kind'] ?? 'prix'));
        $search = search_norm(implode(' ', array_filter([
            $name,
            $category,
            $kind,
            (string) ($item['city'] ?? ''),
            $region,
            $country,
            (string) ($item['prize'] ?? ''),
            (string) ($item['eligibility'] ?? ''),
            (string) ($item['description'] ?? ''),
            (string) ($item['organizer'] ?? ''),
        ])));
        $stmt->execute([
            $slug,
            $name,
            $kind !== '' ? $kind : 'prix',
            trim((string) ($item['kind_label'] ?? '')) ?: $kind,
            $category,
            slugify($category),
            $item['opens_on'] ?: null,
            $item['deadline_on'] ?: null,
            $item['awarded_on'] ?: null,
            trim((string) ($item['dates_raw'] ?? '')),
            !empty($item['dates_confirmed']) ? 1 : 0,
            trim((string) ($item['prize'] ?? '')),
            trim((string) ($item['entry_fee'] ?? '')),
            trim((string) ($item['eligibility'] ?? '')),
            trim((string) ($item['city'] ?? '')),
            trim((string) ($item['department'] ?? '')),
            $region,
            slugify($region),
            $country,
            slugify($country),
            trim((string) ($item['venue'] ?? '')),
            trim((string) ($item['website'] ?? '')),
            trim((string) ($item['organizer'] ?? '')),
            trim((string) ($item['contact'] ?? '')),
            trim((string) ($item['description'] ?? '')),
            trim((string) ($item['audience'] ?? '')),
            trim((string) ($item['notes'] ?? '')),
            $search,
        ]);
    }
};
