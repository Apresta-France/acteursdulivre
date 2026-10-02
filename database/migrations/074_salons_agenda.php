<?php

declare(strict_types=1);

/**
 * Reprend l'agenda des salons : met à jour les fiches déjà en base
 * et crée celles qui manquent. Les salons ajoutés hors de ce fichier
 * (propositions validées, par exemple) restent en place.
 */
return static function (PDO $pdo): void {
    $path = ADL_ROOT . '/database/seeds/salons.json';
    if (!is_file($path)) {
        return;
    }
    $items = json_decode((string) file_get_contents($path), true);
    if (!is_array($items)) {
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
        )
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            category = VALUES(category),
            category_slug = VALUES(category_slug),
            type_label = VALUES(type_label),
            is_direct = VALUES(is_direct),
            dates_raw = VALUES(dates_raw),
            starts_on = VALUES(starts_on),
            ends_on = VALUES(ends_on),
            dates_confirmed = VALUES(dates_confirmed),
            city = VALUES(city),
            department = VALUES(department),
            region = VALUES(region),
            region_slug = VALUES(region_slug),
            country = VALUES(country),
            country_slug = VALUES(country_slug),
            venue = VALUES(venue),
            website = VALUES(website),
            attendance = VALUES(attendance),
            exhibitors = VALUES(exhibitors),
            ticket = VALUES(ticket),
            organizer = VALUES(organizer),
            contact = VALUES(contact),
            socials = VALUES(socials),
            description = VALUES(description),
            audience = VALUES(audience),
            notes = VALUES(notes),
            search_text = VALUES(search_text)'
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
