<?php

declare(strict_types=1);

use Adl\Data\Catalog;

return static function (PDO $pdo): void {
    $old = 'Salons';
    $new = 'Coach littéraire';
    $newSlug = 'coach-litteraire';

    $findTrade = $pdo->prepare("SELECT id, name, slug FROM taxonomy_terms WHERE kind = 'trade' AND name = ? LIMIT 1");
    $findTrade->execute([$old]);
    $oldRow = $findTrade->fetch(PDO::FETCH_ASSOC) ?: null;
    $findTrade->execute([$new]);
    $newRow = $findTrade->fetch(PDO::FETCH_ASSOC) ?: null;

    $renameUsages = static function (PDO $pdo, string $from, string $to): void {
        $pdo->prepare('UPDATE services SET category_name = ? WHERE category_name = ?')->execute([$to, $from]);
        $pdo->prepare('UPDATE missions SET category_name = ? WHERE category_name = ?')->execute([$to, $from]);
        $rows = $pdo->query('SELECT id, trades_json AS payload FROM profiles')->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $update = $pdo->prepare('UPDATE profiles SET trades_json = ? WHERE id = ?');
        foreach ($rows as $row) {
            $data = json_decode((string) ($row['payload'] ?? ''), true);
            if (!is_array($data)) {
                continue;
            }
            $changed = false;
            foreach ($data as $i => $value) {
                if ($value === $from) {
                    $data[$i] = $to;
                    $changed = true;
                }
            }
            if ($changed) {
                $update->execute([
                    json_encode(array_values(array_unique($data)), JSON_UNESCAPED_UNICODE) ?: '[]',
                    (int) $row['id'],
                ]);
            }
        }
    };

    if ($oldRow && !$newRow) {
        $pdo->prepare('UPDATE taxonomy_terms SET name = ?, slug = ? WHERE id = ?')
            ->execute([$new, $newSlug, (int) $oldRow['id']]);
        $renameUsages($pdo, $old, $new);
    } elseif ($oldRow && $newRow) {
        $renameUsages($pdo, $old, $new);
        $pdo->prepare('DELETE FROM taxonomy_terms WHERE id = ?')->execute([(int) $oldRow['id']]);
    } elseif (!$oldRow && !$newRow) {
        $position = (int) ($pdo->query(
            "SELECT COALESCE(MAX(position), -1) + 1 AS n FROM taxonomy_terms WHERE kind = 'trade'"
        )->fetch(PDO::FETCH_ASSOC)['n'] ?? 0);
        $pdo->prepare(
            "INSERT INTO taxonomy_terms (kind, name, slug, position, enabled, is_global)
             VALUES ('trade', ?, ?, ?, 1, 0)"
        )->execute([$new, $newSlug, $position]);
    }

    $pdo->exec(
        "UPDATE taxonomy_terms
         SET enabled = 0
         WHERE kind = 'specialty' AND name IN ('Stand', 'Organisation', 'Dédicaces')"
    );

    $names = Catalog::mappedSpecialtyNames();
    if ($names !== []) {
        $slugify = static function (string $text): string {
            $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
            $slug = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $ascii));
            return trim($slug, '-') ?: 'terme';
        };

        $existing = $pdo->query(
            "SELECT name, slug, position FROM taxonomy_terms WHERE kind = 'specialty'"
        )->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $byName = [];
        $usedSlugs = [];
        $maxPosition = -1;
        foreach ($existing as $row) {
            $byName[(string) $row['name']] = true;
            $usedSlugs[(string) $row['slug']] = true;
            $maxPosition = max($maxPosition, (int) $row['position']);
        }

        $insert = $pdo->prepare(
            "INSERT INTO taxonomy_terms (kind, name, slug, position, enabled, is_global)
             VALUES ('specialty', ?, ?, ?, 1, 0)"
        );
        foreach ($names as $name) {
            if ($name === '' || isset($byName[$name])) {
                continue;
            }
            $slug = $slugify($name);
            $base = $slug;
            $n = 2;
            while (isset($usedSlugs[$slug])) {
                $slug = $base . '-' . $n++;
            }
            $usedSlugs[$slug] = true;
            $insert->execute([$name, $slug, ++$maxPosition]);
        }
    }

    $slugs = [
        'accompagnement-ecriture-coach-mentorat',
        'lecture-editoriale-comite-de-lecture',
        'a-t-on-besoin-agent-litteraire-2026',
        'cout-couverture-roman-illustree',
        'choisir-papier-bouffant-offset-recycle',
        'cession-droits-illustration',
        'cout-reel-ebook',
        'salon-du-livre-budget-premier-stand',
        'booktok-instagram-newsletter-premier-roman',
        'service-de-presse-exemplaires-delai',
        'attache-de-presse-livre',
        'impression-pod-numerique-offset',
        'beta-lecture-rapport-avant-correction',
        'livre-autoedite-en-librairie',
        'maquette-interieure-livre',
    ];
    $articles = require dirname(__DIR__) . '/seeds/journal/_index.php';
    $bySlug = [];
    foreach ($articles as $article) {
        $slug = (string) ($article['slug'] ?? '');
        if ($slug !== '') {
            $bySlug[$slug] = $article;
        }
    }
    $updateArticle = $pdo->prepare('UPDATE articles SET body = ?, excerpt = ? WHERE slug = ?');
    foreach ($slugs as $slug) {
        if (!isset($bySlug[$slug])) {
            continue;
        }
        $updateArticle->execute([
            (string) ($bySlug[$slug]['body'] ?? ''),
            $bySlug[$slug]['excerpt'] ?? null,
            $slug,
        ]);
    }
};
