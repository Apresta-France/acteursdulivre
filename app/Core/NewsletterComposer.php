<?php

declare(strict_types=1);

namespace Adl\Core;

use Adl\Models\Article;
use Adl\Models\Mission;
use Adl\Models\Newsletter;
use Adl\Models\Profile;

final class NewsletterComposer
{
    /**
     * @return array{
     *     subject: string,
     *     html: string,
     *     text: string,
     *     missions: list<array<string, string>>,
     *     people: list<array<string, string>>,
     *     url_items: list<array<string, string>>,
     *     empty: bool
     * }
     */
    public static function compose(?string $sourceUrl = null): array
    {
        $missions = Newsletter::includeMissions() ? self::missions() : [];
        $people = Newsletter::includePeople() ? self::people() : [];
        $urlItems = [];
        if (Newsletter::includeUrl()) {
            $url = $sourceUrl !== null ? trim($sourceUrl) : Newsletter::sourceUrl();
            $urlItems = self::fromUrl($url);
        }

        $empty = $missions === [] && $people === [] && $urlItems === [];
        $week = (new \DateTimeImmutable('now', new \DateTimeZone(Env::get('APP_TIMEZONE', 'Europe/Paris'))))
            ->format('d/m/Y');
        $subject = 'Le point sur les métiers du livre — semaine du ' . $week;

        $html = View::fetch('emails/newsletter', [
            'missions' => $missions,
            'people' => $people,
            'urlItems' => $urlItems,
            'empty' => $empty,
            'week' => $week,
        ]);
        $text = trim(html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8'));

        return [
            'subject' => $subject,
            'html' => $html,
            'text' => $text,
            'missions' => $missions,
            'people' => $people,
            'url_items' => $urlItems,
            'empty' => $empty,
        ];
    }

    /** @return list<array<string, string>> */
    private static function missions(): array
    {
        $open = Mission::open();
        $recent = [];
        $weekAgo = time() - 7 * 86400;
        foreach ($open as $row) {
            $ts = strtotime((string) ($row['created_at'] ?? '')) ?: 0;
            if ($ts >= $weekAgo) {
                $recent[] = $row;
            }
        }
        $pick = $recent !== [] ? $recent : $open;
        $out = [];
        foreach (array_slice($pick, 0, 5) as $row) {
            $brief = RichText::plain((string) ($row['brief'] ?? ''));
            if (mb_strlen($brief) > 180) {
                $brief = rtrim(mb_substr($brief, 0, 177)) . '…';
            }
            $out[] = [
                'title' => (string) ($row['title'] ?? 'Mission'),
                'meta' => trim((string) ($row['category_name'] ?? '') . ' · ' . (string) ($row['budget'] ?? '')),
                'excerpt' => $brief,
                'href' => url((string) ($row['href'] ?? '/missions')),
            ];
        }
        return $out;
    }

    /** @return list<array<string, string>> */
    private static function people(): array
    {
        $profiles = Profile::searchPublished();
        $weekAgo = time() - 7 * 86400;
        $recent = [];
        foreach ($profiles as $row) {
            $ts = strtotime((string) ($row['created_at'] ?? ($row['updated_at'] ?? ''))) ?: 0;
            if ($ts >= $weekAgo) {
                $recent[] = $row;
            }
        }
        $pick = $recent !== [] ? $recent : $profiles;
        $out = [];
        foreach (array_slice($pick, 0, 5) as $row) {
            $trades = $row['trades'] ?? [];
            $trade = is_array($trades) && $trades !== [] ? (string) $trades[0] : '';
            $city = trim((string) ($row['city'] ?? ''));
            $meta = trim($trade . ($city !== '' ? ' · ' . $city : ''), ' ·');
            $out[] = [
                'title' => Profile::displayName($row),
                'meta' => $meta !== '' ? $meta : (string) ($row['title'] ?? ''),
                'excerpt' => trim((string) ($row['title'] ?? '')),
                'href' => url(Profile::publicHref($row)),
            ];
        }
        unset($row);
        return $out;
    }

    /** @return list<array<string, string>> */
    public static function fromUrl(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return self::latestArticles();
        }

        $internal = self::internalPath($raw);
        if ($internal !== null) {
            if ($internal === '/' || $internal === '/journal') {
                return self::latestArticles();
            }
            if (preg_match('#^/journal/([a-z0-9-]+)$#', $internal, $m)) {
                $article = Article::findBySlug($m[1]);
                return $article && !empty($article['published']) ? [self::articleItem($article)] : [];
            }
        }

        $fetched = self::fetchRemote($raw);
        return $fetched;
    }

    /** @return list<array<string, string>> */
    private static function latestArticles(): array
    {
        $out = [];
        foreach (Article::preview(3) as $article) {
            $out[] = self::articleItem($article);
        }
        return $out;
    }

    /** @param array<string, mixed> $article */
    private static function articleItem(array $article): array
    {
        $excerpt = trim((string) ($article['chapo'] ?? $article['excerpt'] ?? ''));
        if ($excerpt === '') {
            $excerpt = RichText::plain((string) ($article['body'] ?? ''));
            if (mb_strlen($excerpt) > 220) {
                $excerpt = rtrim(mb_substr($excerpt, 0, 217)) . '…';
            }
        }
        return [
            'title' => (string) ($article['title'] ?? 'Article'),
            'meta' => trim((string) ($article['cat'] ?? '') . ' · ' . (string) ($article['read'] ?? '')),
            'excerpt' => $excerpt,
            'href' => url((string) ($article['href'] ?? '/journal')),
        ];
    }

    private static function internalPath(string $raw): ?string
    {
        if (str_starts_with($raw, '/')) {
            return safe_internal_path($raw);
        }
        $parts = parse_url($raw);
        if (!is_array($parts) || empty($parts['host'])) {
            return null;
        }
        $appHost = parse_url((string) Env::get('APP_URL', ''), PHP_URL_HOST);
        $host = strtolower((string) $parts['host']);
        $ok = ['acteursdulivre.fr', 'www.acteursdulivre.fr', 'acteursdulivre.test'];
        if (is_string($appHost) && $appHost !== '') {
            $ok[] = strtolower($appHost);
        }
        if (!in_array($host, $ok, true)) {
            return null;
        }
        $path = (string) ($parts['path'] ?? '/');
        return safe_internal_path($path === '' ? '/' : $path);
    }

    /** @return list<array<string, string>> */
    private static function fetchRemote(string $url): array
    {
        if (!preg_match('#^https://#i', $url)) {
            throw new \RuntimeException('L\'URL source doit commencer par https://.');
        }
        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            throw new \RuntimeException('URL source invalide.');
        }
        $host = strtolower($host);
        if (self::isPrivateHost($host)) {
            throw new \RuntimeException('Cette URL n\'est pas autorisée comme source.');
        }

        $ctx = stream_context_create([
            'http' => [
                'timeout' => 8,
                'follow_location' => 1,
                'max_redirects' => 3,
                'header' => "User-Agent: ActeursDuLivre-Newsletter/1.0\r\nAccept: text/html,application/rss+xml,application/xml;q=0.9,*/*;q=0.8\r\n",
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);
        $raw = @file_get_contents($url, false, $ctx, 0, 400000);
        if (!is_string($raw) || $raw === '') {
            throw new \RuntimeException('Impossible de lire cette URL pour composer la lettre.');
        }

        if (preg_match('/<(rss|feed|rdf:RDF)\b/i', $raw)) {
            return self::parseFeed($raw);
        }

        $item = self::parseHtmlPage($raw, $url);
        return $item !== null ? [$item] : [];
    }

    private static function isPrivateHost(string $host): bool
    {
        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            return true;
        }
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }
        return false;
    }

    /** @return list<array<string, string>> */
    private static function parseFeed(string $xml): array
    {
        libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml);
        libxml_clear_errors();
        if ($doc === false) {
            return [];
        }
        $items = [];
        if (isset($doc->channel->item)) {
            foreach ($doc->channel->item as $item) {
                $items[] = [
                    'title' => trim((string) $item->title) ?: 'Article',
                    'meta' => '',
                    'excerpt' => RichText::plain((string) ($item->description ?? $item->title)),
                    'href' => trim((string) $item->link),
                ];
                if (count($items) >= 3) {
                    break;
                }
            }
        } elseif (isset($doc->entry)) {
            foreach ($doc->entry as $entry) {
                $href = '';
                foreach ($entry->link ?? [] as $link) {
                    $rel = (string) ($link['rel'] ?? 'alternate');
                    if ($rel === 'alternate' || $href === '') {
                        $href = (string) ($link['href'] ?? '');
                    }
                }
                $items[] = [
                    'title' => trim((string) $entry->title) ?: 'Article',
                    'meta' => '',
                    'excerpt' => RichText::plain((string) ($entry->summary ?? $entry->title)),
                    'href' => $href,
                ];
                if (count($items) >= 3) {
                    break;
                }
            }
        }
        return $items;
    }

    /** @return array<string, string>|null */
    private static function parseHtmlPage(string $html, string $url): ?array
    {
        $title = '';
        if (preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)/i', $html, $m)
            || preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:title["\']/i', $html, $m)) {
            $title = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        } elseif (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) {
            $title = html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        $title = trim(preg_replace('/\s+/u', ' ', $title) ?? $title);

        $desc = '';
        if (preg_match('/<meta[^>]+property=["\']og:description["\'][^>]+content=["\']([^"\']+)/i', $html, $m)
            || preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)/i', $html, $m)
            || preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']description["\']/i', $html, $m)) {
            $desc = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        if ($desc === '' && preg_match_all('/<p\b[^>]*>(.*?)<\/p>/is', $html, $blocks)) {
            foreach ($blocks[1] as $block) {
                $plain = RichText::plain($block);
                if (mb_strlen($plain) >= 80) {
                    $desc = $plain;
                    break;
                }
            }
        }
        $desc = trim(preg_replace('/\s+/u', ' ', $desc) ?? $desc);
        if (mb_strlen($desc) > 280) {
            $desc = rtrim(mb_substr($desc, 0, 277)) . '…';
        }
        if ($title === '' && $desc === '') {
            return null;
        }
        return [
            'title' => $title !== '' ? $title : 'À lire',
            'meta' => '',
            'excerpt' => $desc,
            'href' => $url,
        ];
    }
}
