<?php

declare(strict_types=1);

return static function (\PDO $pdo): void {
    $cols = $pdo->query('SHOW COLUMNS FROM profiles')->fetchAll(PDO::FETCH_COLUMN);
    $add = static function (string $sql) use ($pdo): void {
        $pdo->exec($sql);
    };

    if (!in_array('city_slug', $cols, true)) {
        $add('ALTER TABLE profiles ADD COLUMN city_slug VARCHAR(160) NULL AFTER city');
    }
    if (!in_array('city_area_slug', $cols, true)) {
        $add('ALTER TABLE profiles ADD COLUMN city_area_slug VARCHAR(160) NULL AFTER city_slug');
    }
    if (!in_array('city_insee', $cols, true)) {
        $add('ALTER TABLE profiles ADD COLUMN city_insee VARCHAR(12) NULL AFTER city_area_slug');
    }
    if (!in_array('city_postcode', $cols, true)) {
        $add('ALTER TABLE profiles ADD COLUMN city_postcode VARCHAR(10) NULL AFTER city_insee');
    }
    if (!in_array('city_dept', $cols, true)) {
        $add('ALTER TABLE profiles ADD COLUMN city_dept VARCHAR(80) NULL AFTER city_postcode');
    }

    $indexes = $pdo->query('SHOW INDEX FROM profiles')->fetchAll(PDO::FETCH_ASSOC);
    $indexNames = [];
    foreach ($indexes as $index) {
        $indexNames[] = (string) ($index['Key_name'] ?? '');
    }
    if (!in_array('idx_profiles_city_slug', $indexNames, true)) {
        $add('ALTER TABLE profiles ADD INDEX idx_profiles_city_slug (city_slug)');
    }
    if (!in_array('idx_profiles_city_area', $indexNames, true)) {
        $add('ALTER TABLE profiles ADD INDEX idx_profiles_city_area (city_area_slug)');
    }

    $rows = $pdo->query(
        'SELECT user_id, city FROM profiles
         WHERE city IS NOT NULL AND city != ""
           AND (city_slug IS NULL OR city_slug = "")'
    )->fetchAll(PDO::FETCH_ASSOC);

    $update = $pdo->prepare(
        'UPDATE profiles SET city_slug = ?, city_area_slug = ? WHERE user_id = ?'
    );
    foreach ($rows as $row) {
        $norm = \Adl\Data\Cities::fromFreeText((string) ($row['city'] ?? ''));
        if ($norm['slug'] === '') {
            continue;
        }
        $update->execute([$norm['slug'], $norm['area_slug'], (int) $row['user_id']]);
    }
};
