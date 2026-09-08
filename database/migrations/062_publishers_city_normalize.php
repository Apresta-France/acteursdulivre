<?php

declare(strict_types=1);

use Adl\Models\Publisher;

/**
 * Unifie les villes importées (« Barcelone (Catalogne) », « Bari / Rome », « Antwerpen »…)
 * pour que chaque ville n'ait qu'une seule URL GEO. Le champ région est complété quand
 * la parenthèse portait une région et que rien n'était renseigné.
 */
return static function (PDO $pdo): void {
    $rows = $pdo->query('SELECT id, city, region, search_text FROM publishers WHERE city != ""')->fetchAll(PDO::FETCH_ASSOC);
    $update = $pdo->prepare('UPDATE publishers SET city = ?, city_slug = ?, region = ?, search_text = ? WHERE id = ?');
    foreach ($rows as $row) {
        $raw = (string) $row['city'];
        $city = Publisher::normalizeCity($raw);
        $region = (string) $row['region'];
        if ($region === '' && preg_match('/\(([^)]+)\)/u', $raw, $m)) {
            $region = mb_substr(trim($m[1]), 0, 120);
        }
        if ($city === $raw && $region === (string) $row['region']) {
            continue;
        }
        $search = (string) $row['search_text'];
        $extra = search_norm($raw . ' ' . $city . ' ' . $region);
        if ($extra !== '' && !str_contains($search, $extra)) {
            $search = trim($search . ' ' . $extra);
        }
        $update->execute([$city, $city !== '' ? slugify($city) : '', $region, $search, (int) $row['id']]);
    }
};
