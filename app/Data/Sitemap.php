<?php

declare(strict_types=1);

namespace Adl\Data;

use Adl\Core\Database;
use Adl\Models\Profile;

final class Sitemap
{
    /**
     * @return list<array{loc: string, lastmod?: string, priority?: string}>
     */
    public static function urls(): array
    {
        $urls = [];
        $seen = [];

        foreach (self::staticPages() as $page) {
            self::push($urls, $seen, $page['path'], $page['lastmod'] ?? null, $page['priority'] ?? null);
        }

        foreach (self::tradePaths() as $path) {
            self::push($urls, $seen, $path, null, '0.8');
        }

        foreach (self::tradeCityPaths() as $path) {
            self::push($urls, $seen, $path, null, '0.7');
        }

        foreach (self::rows(
            'SELECT s.slug, s.created_at AS lastmod
             FROM services s
             JOIN users u ON u.id = s.user_id
             WHERE s.status = "published"
               AND u.status = "active"
               AND u.offers_services = 1
               AND s.slug IS NOT NULL AND s.slug != ""
               AND NOT EXISTS (
                    SELECT 1 FROM invoices i
                    WHERE i.seller_id = s.user_id
                      AND i.status IN ("issued", "overdue")
                      AND i.due_at < NOW()
               )'
        ) as $row) {
            self::push($urls, $seen, '/prestations/' . $row['slug'], $row['lastmod'] ?? null, '0.7');
        }

        foreach (self::rows(
            'SELECT p.slug, p.updated_at AS lastmod
             FROM profiles p
             JOIN users u ON u.id = p.user_id
             WHERE u.offers_services = 1
               AND u.status = "active"
               AND p.slug IS NOT NULL AND p.slug != ""
               AND (
                    (p.title IS NOT NULL AND p.title != "")
                 OR (p.presentation IS NOT NULL AND p.presentation != "")
                 OR (p.trades_json IS NOT NULL AND p.trades_json != "" AND p.trades_json != "[]")
               )
               AND NOT EXISTS (
                    SELECT 1 FROM invoices i
                    WHERE i.seller_id = u.id
                      AND i.status IN ("issued", "overdue")
                      AND i.due_at < NOW()
               )'
        ) as $row) {
            self::push($urls, $seen, '/prestataires/' . $row['slug'], $row['lastmod'] ?? null, '0.7');
        }

        foreach (self::rows(
            'SELECT m.slug, m.created_at AS lastmod
             FROM missions m
             JOIN users u ON u.id = m.user_id
             WHERE m.status = "open"
               AND u.status = "active"
               AND m.slug IS NOT NULL AND m.slug != ""'
        ) as $row) {
            self::push($urls, $seen, '/missions/' . $row['slug'], $row['lastmod'] ?? null, '0.6');
        }

        foreach (self::rows(
            'SELECT slug, published_at AS lastmod
             FROM articles
             WHERE published_at IS NOT NULL AND published_at <= NOW()
               AND slug IS NOT NULL AND slug != ""'
        ) as $row) {
            self::push($urls, $seen, '/journal/' . $row['slug'], $row['lastmod'] ?? null, '0.6');
        }

        return $urls;
    }

    public static function xml(): string
    {
        $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $out .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach (self::urls() as $url) {
            $out .= "  <url>\n";
            $out .= '    <loc>' . self::escape($url['loc']) . "</loc>\n";
            if (!empty($url['lastmod'])) {
                $out .= '    <lastmod>' . self::escape($url['lastmod']) . "</lastmod>\n";
            }
            if (!empty($url['priority'])) {
                $out .= '    <priority>' . self::escape($url['priority']) . "</priority>\n";
            }
            $out .= "  </url>\n";
        }
        $out .= '</urlset>' . "\n";
        return $out;
    }

    public static function robots(): string
    {
        return Seo::robotsTxt();
    }

    /**
     * @return list<array{path: string, lastmod?: string, priority: string}>
     */
    private static function staticPages(): array
    {
        $legalMod = '2026-08-31';
        $pages = [
            ['path' => '/', 'priority' => '1.0'],
            ['path' => '/recherche', 'priority' => '0.8'],
            ['path' => '/prestations', 'priority' => '0.8'],
            ['path' => '/prestataires', 'priority' => '0.8'],
            ['path' => '/missions', 'priority' => '0.8'],
            ['path' => '/journal', 'priority' => '0.8'],
            ['path' => '/comment-ca-marche', 'priority' => '0.7'],
            ['path' => '/tarifs', 'priority' => '0.7'],
            ['path' => '/confiance', 'priority' => '0.6'],
            ['path' => '/a-propos', 'priority' => '0.6'],
            ['path' => '/inscription', 'priority' => '0.6'],
            ['path' => '/aide', 'priority' => '0.5'],
            ['path' => '/questions', 'priority' => '0.6'],
            ['path' => '/contact', 'priority' => '0.5'],
        ];
        foreach (LegalPages::slugs() as $item) {
            $pages[] = [
                'path' => $item['href'],
                'lastmod' => $legalMod,
                'priority' => '0.3',
            ];
        }
        return $pages;
    }

    /** @return list<string> */
    private static function tradePaths(): array
    {
        try {
            $trades = Catalog::trades();
        } catch (\Throwable) {
            $trades = Profile::TRADES;
        }
        $paths = [];
        foreach ($trades as $trade) {
            $slug = slugify($trade);
            if ($slug !== '') {
                $paths[] = '/metiers/' . $slug;
            }
        }
        return $paths;
    }

    /** @return list<string> */
    private static function tradeCityPaths(): array
    {
        try {
            $pairs = Catalog::tradeCityPairs();
        } catch (\Throwable) {
            return [];
        }
        $paths = [];
        foreach ($pairs as $trade => $cities) {
            foreach ($cities as $row) {
                $path = Catalog::tradeCityPath((string) $trade, (string) ($row['slug'] ?? ''));
                if ($path !== '' && substr_count($path, '/') >= 2) {
                    $paths[] = $path;
                }
            }
        }
        return $paths;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function rows(string $sql): array
    {
        try {
            return Database::fetchAll($sql);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param list<array{loc: string, lastmod?: string, priority?: string}> $urls
     * @param array<string, true> $seen
     */
    private static function push(array &$urls, array &$seen, string $path, ?string $lastmod, ?string $priority): void
    {
        $loc = Share::absolute($path);
        if ($loc === '' || isset($seen[$loc])) {
            return;
        }
        $seen[$loc] = true;
        $entry = ['loc' => $loc];
        $date = self::lastmod($lastmod);
        if ($date !== null) {
            $entry['lastmod'] = $date;
        }
        if ($priority !== null && $priority !== '') {
            $entry['priority'] = $priority;
        }
        $urls[] = $entry;
    }

    private static function lastmod(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $ts = strtotime($value);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
