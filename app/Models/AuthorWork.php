<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;

final class AuthorWork
{
    public const MAX_IMAGES = 3;
    public const IMAGE_EXT = ['jpg', 'jpeg', 'png', 'webp'];
    public const IMAGE_MAX_BYTES = 5 * 1024 * 1024;
    public const UPLOAD_DIR = 'oeuvres';

    public const KINDS = [
        'roman' => 'Roman',
        'nouvelles' => 'Recueil de nouvelles',
        'poesie' => 'Poésie',
        'theatre' => 'Théâtre',
        'essai' => 'Essai',
        'biographie' => 'Biographie / mémoires',
        'recit' => 'Récit / témoignage',
        'jeunesse' => 'Livre jeunesse',
        'album' => 'Album illustré',
        'bd' => 'Bande dessinée / roman graphique',
        'beau-livre' => 'Beau livre',
        'pratique' => 'Livre pratique',
        'scolaire' => 'Scolaire / universitaire',
        'anthologie' => 'Anthologie / collectif',
        'audio' => 'Livre audio',
        'autre' => 'Autre',
    ];

    public const ROLES = [
        'auteur' => 'Auteur / autrice',
        'co-auteur' => 'Co-auteur / co-autrice',
        'illustrateur' => 'Illustration',
        'traducteur' => 'Traduction',
        'scenariste' => 'Scénario',
        'directeur' => 'Direction d\'ouvrage',
        'prefacier' => 'Préface',
        'photographe' => 'Photographies',
        'narrateur' => 'Narration (audio)',
        'autre' => 'Autre contribution',
    ];

    public const STATUSES = [
        'published' => 'Disponible',
        'upcoming' => 'À paraître',
        'out_of_print' => 'Épuisé',
    ];

    public const FORMATS = [
        'broche' => 'Broché',
        'poche' => 'Poche',
        'relie' => 'Relié',
        'numerique' => 'Numérique',
        'audio' => 'Audio',
    ];

    /** @return list<array<string, mixed>> */
    public static function forPage(int $pageId): array
    {
        $rows = Database::fetchAll(
            'SELECT * FROM author_works
             WHERE author_page_id = ?
             ORDER BY featured DESC, sort_order ASC, year DESC, id DESC',
            [$pageId]
        );
        return array_map([self::class, 'hydrate'], $rows);
    }

    public static function find(int $id, int $pageId): ?array
    {
        $row = Database::fetch(
            'SELECT * FROM author_works WHERE id = ? AND author_page_id = ?',
            [$id, $pageId]
        );
        return $row ? self::hydrate($row) : null;
    }

    public static function countForPage(int $pageId): int
    {
        return (int) (Database::fetch(
            'SELECT COUNT(*) AS n FROM author_works WHERE author_page_id = ?',
            [$pageId]
        )['n'] ?? 0);
    }

    /**
     * @param list<int> $pageIds
     * @return array<int, int>
     */
    public static function countForPages(array $pageIds): array
    {
        $pageIds = array_values(array_filter(array_map('intval', $pageIds), static fn (int $id): bool => $id > 0));
        if ($pageIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($pageIds), '?'));
        $rows = Database::fetchAll(
            'SELECT author_page_id, COUNT(*) AS n FROM author_works
             WHERE author_page_id IN (' . $placeholders . ')
             GROUP BY author_page_id',
            $pageIds
        );
        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['author_page_id']] = (int) $row['n'];
        }
        return $out;
    }

    /** @param array<string, mixed> $data */
    public static function create(int $pageId, array $data): int
    {
        $payload = self::payload($data);
        $order = (int) (Database::fetch(
            'SELECT COALESCE(MAX(sort_order), 0) + 1 AS n FROM author_works WHERE author_page_id = ?',
            [$pageId]
        )['n'] ?? 1);
        Database::query(
            'INSERT INTO author_works (author_page_id, title, subtitle, kind, role, status, publisher, collection,
                year, isbn, pages, language, formats_json, price, summary, excerpt, buy_url, more_url, images_json,
                featured, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$pageId, ...array_values($payload), $order]
        );
        self::touchPage($pageId);
        return (int) Database::lastId();
    }

    /** @param array<string, mixed> $data */
    public static function update(int $id, int $pageId, array $data): void
    {
        $payload = self::payload($data);
        $sets = implode(', ', array_map(static fn (string $col): string => $col . ' = ?', array_keys($payload)));
        Database::query(
            'UPDATE author_works SET ' . $sets . ', updated_at = NOW() WHERE id = ? AND author_page_id = ?',
            [...array_values($payload), $id, $pageId]
        );
        self::touchPage($pageId);
    }

    public static function delete(int $id, int $pageId): void
    {
        $work = self::find($id, $pageId);
        if (!$work) {
            return;
        }
        Database::query('DELETE FROM author_works WHERE id = ? AND author_page_id = ?', [$id, $pageId]);
        foreach ($work['image_paths'] as $path) {
            if (!preg_match('#^https?://#i', $path)) {
                delete_upload($path);
            }
        }
        self::touchPage($pageId);
    }

    /** Échange la position avec l'œuvre voisine (ordre manuel, les mises en avant restent en tête). */
    public static function move(int $id, int $pageId, string $direction): void
    {
        $works = self::forPage($pageId);
        $index = null;
        foreach ($works as $i => $work) {
            if ((int) $work['id'] === $id) {
                $index = $i;
                break;
            }
        }
        if ($index === null) {
            return;
        }
        $target = $direction === 'up' ? $index - 1 : $index + 1;
        if (!isset($works[$target]) || !empty($works[$target]['featured']) !== !empty($works[$index]['featured'])) {
            return;
        }
        [$works[$index], $works[$target]] = [$works[$target], $works[$index]];
        Database::transaction(static function () use ($works, $pageId): void {
            foreach ($works as $i => $work) {
                Database::query(
                    'UPDATE author_works SET sort_order = ? WHERE id = ? AND author_page_id = ?',
                    [$i + 1, (int) $work['id'], $pageId]
                );
            }
        });
        self::touchPage($pageId);
    }

    public static function normalizeIsbn(string $isbn): string
    {
        $clean = strtoupper(preg_replace('/[^0-9Xx]/', '', $isbn) ?? '');
        if ($clean === '') {
            return '';
        }
        if (strlen($clean) === 13) {
            return substr($clean, 0, 3) . '-' . substr($clean, 3, 1) . '-' . substr($clean, 4, 5) . '-' . substr($clean, 9, 3) . '-' . substr($clean, 12);
        }
        return $clean;
    }

    /** @param array<string, mixed> $work */
    public static function coverUrl(array $work): string
    {
        $images = $work['images'] ?? [];
        return $images !== [] ? (string) $images[0] : '';
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function payload(array $data): array
    {
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new \RuntimeException('Le titre de l\'œuvre est obligatoire.');
        }
        $kind = (string) ($data['kind'] ?? 'roman');
        $role = (string) ($data['role'] ?? 'auteur');
        $status = (string) ($data['status'] ?? 'published');
        $formats = [];
        foreach ((array) ($data['formats'] ?? []) as $format) {
            $format = (string) $format;
            if (array_key_exists($format, self::FORMATS) && !in_array($format, $formats, true)) {
                $formats[] = $format;
            }
        }
        $year = trim((string) ($data['year'] ?? ''));
        if ($year !== '' && !preg_match('/^\d{4}(-\d{2})?$/', $year)) {
            throw new \RuntimeException('L\'année de parution doit ressembler à 2024 ou 2024-09.');
        }
        $pages = (int) ($data['pages'] ?? 0);
        $images = [];
        foreach ((array) ($data['images'] ?? []) as $image) {
            $image = trim((string) $image);
            if ($image !== '' && !in_array($image, $images, true)) {
                $images[] = $image;
            }
        }
        $images = array_slice($images, 0, self::MAX_IMAGES);

        $summary = trim((string) ($data['summary'] ?? ''));
        $excerpt = trim((string) ($data['excerpt'] ?? ''));

        return [
            'title' => mb_substr($title, 0, 190),
            'subtitle' => self::nullable((string) ($data['subtitle'] ?? ''), 190),
            'kind' => array_key_exists($kind, self::KINDS) ? $kind : 'roman',
            'role' => array_key_exists($role, self::ROLES) ? $role : 'auteur',
            'status' => array_key_exists($status, self::STATUSES) ? $status : 'published',
            'publisher' => self::nullable((string) ($data['publisher'] ?? ''), 190),
            'collection' => self::nullable((string) ($data['collection'] ?? ''), 190),
            'year' => $year !== '' ? $year : null,
            'isbn' => self::nullable(self::normalizeIsbn((string) ($data['isbn'] ?? '')), 32),
            'pages' => $pages > 0 && $pages < 65000 ? $pages : null,
            'language' => self::nullable((string) ($data['language'] ?? ''), 60),
            'formats_json' => $formats !== [] ? json_encode($formats, JSON_UNESCAPED_UNICODE) : null,
            'price' => self::nullable((string) ($data['price'] ?? ''), 40),
            'summary' => $summary !== '' ? mb_substr($summary, 0, 8000) : null,
            'excerpt' => $excerpt !== '' ? mb_substr($excerpt, 0, 6000) : null,
            'buy_url' => self::nullable(AuthorPage::cleanUrl((string) ($data['buy_url'] ?? '')), 500),
            'more_url' => self::nullable(AuthorPage::cleanUrl((string) ($data['more_url'] ?? '')), 500),
            'images_json' => $images !== [] ? json_encode($images, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'featured' => !empty($data['featured']) ? 1 : 0,
        ];
    }

    private static function nullable(string $value, int $max): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        return mb_strlen($value) > $max ? mb_substr($value, 0, $max) : $value;
    }

    private static function touchPage(int $pageId): void
    {
        Database::query('UPDATE author_pages SET updated_at = NOW() WHERE id = ?', [$pageId]);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function hydrate(array $row): array
    {
        $paths = [];
        $rawImages = json_decode((string) ($row['images_json'] ?? ''), true);
        if (is_array($rawImages)) {
            foreach ($rawImages as $path) {
                $path = trim((string) $path);
                if ($path !== '') {
                    $paths[] = $path;
                }
            }
        }
        $row['image_paths'] = $paths;
        $row['images'] = array_map(
            static fn (string $path): string => preg_match('#^https?://#i', $path) ? $path : uploaded($path),
            $paths
        );
        $row['cover'] = $row['images'][0] ?? '';
        $formats = json_decode((string) ($row['formats_json'] ?? ''), true);
        $row['formats'] = is_array($formats) ? array_values(array_filter(array_map('strval', $formats), static fn (string $f): bool => isset(self::FORMATS[$f]))) : [];
        $row['formats_labels'] = array_map(static fn (string $f): string => self::FORMATS[$f], $row['formats']);
        $row['kind_label'] = self::KINDS[$row['kind'] ?? 'roman'] ?? self::KINDS['roman'];
        $row['role_label'] = self::ROLES[$row['role'] ?? 'auteur'] ?? self::ROLES['auteur'];
        $row['status_label'] = self::STATUSES[$row['status'] ?? 'published'] ?? self::STATUSES['published'];
        $meta = array_filter([
            (string) ($row['publisher'] ?? ''),
            (string) ($row['collection'] ?? ''),
            (string) ($row['year'] ?? ''),
        ], static fn (string $v): bool => $v !== '');
        $row['meta_label'] = implode(' · ', $meta);
        return $row;
    }
}
