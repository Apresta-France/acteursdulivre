<?php

declare(strict_types=1);

namespace Adl\Core;

final class NewsletterBuilder
{
    public const MAX_BLOCKS = 40;
    public const MAX_CARDS = 12;

    /** @var list<string> */
    public const TYPES = ['heading', 'text', 'image', 'button', 'divider', 'spacer', 'quote', 'cards'];

    /** @return list<array<string, mixed>> */
    public static function defaultBlocks(): array
    {
        return [
            self::block('heading', ['text' => 'Bonjour,']),
            self::block('text', [
                'html' => '<p>Voici les nouvelles d’Acteurs du Livre.</p>',
            ]),
        ];
    }

    /**
     * @return array{subject: string, preheader: string, blocks: list<array<string, mixed>>}
     */
    public static function fromWeekly(): array
    {
        $composed = NewsletterComposer::compose();
        $blocks = [
            self::block('heading', ['text' => 'Bonjour,']),
        ];
        $week = (string) ($composed['week'] ?? '');
        $intro = $week !== ''
            ? '<p>Voici le point de la semaine (à partir du ' . e($week) . ') : les recherches ouvertes, les nouveaux profils, et une lecture utile.</p>'
            : '<p>Voici le point de la semaine : les recherches ouvertes, les nouveaux profils, et une lecture utile.</p>';
        $blocks[] = self::block('text', ['html' => $intro]);

        if (($composed['missions'] ?? []) !== []) {
            $blocks[] = self::block('cards', [
                'title' => 'Dernières recherches',
                'items' => $composed['missions'],
            ]);
        }
        if (($composed['people'] ?? []) !== []) {
            $blocks[] = self::block('cards', [
                'title' => 'Nouveaux profils',
                'items' => $composed['people'],
            ]);
        }
        if (($composed['url_items'] ?? []) !== []) {
            $blocks[] = self::block('cards', [
                'title' => 'À lire',
                'items' => $composed['url_items'],
            ]);
        }
        if (!empty($composed['empty'])) {
            $blocks[] = self::block('text', [
                'html' => '<p>Peu de nouveautés cette semaine. Le journal et l’annuaire restent ouverts.</p>',
            ]);
            $blocks[] = self::block('button', [
                'label' => 'Lire le journal',
                'href' => url('/journal'),
                'align' => 'left',
            ]);
        }
        $blocks[] = self::block('text', [
            'html' => '<p>À la semaine prochaine,<br>L’équipe d’Acteurs du Livre</p>',
        ]);

        return [
            'subject' => (string) ($composed['subject'] ?? 'Lettre d’Acteurs du Livre'),
            'preheader' => $week !== '' ? 'Le point de la semaine du ' . $week : '',
            'blocks' => self::normalize($blocks),
        ];
    }

    /**
     * @return array{
     *     missions: list<array<string, string>>,
     *     people: list<array<string, string>>,
     *     articles: list<array<string, string>>
     * }
     */
    public static function catalog(): array
    {
        return NewsletterComposer::catalog();
    }

    /** @return list<array<string, mixed>> */
    public static function decode(string $json): array
    {
        if (trim($json) === '') {
            return [];
        }
        $data = json_decode($json, true);
        return self::normalize(is_array($data) ? $data : []);
    }

    /**
     * @param list<mixed>|array<int|string, mixed> $raw
     * @return list<array<string, mixed>>
     */
    public static function normalize(array $raw): array
    {
        $out = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $block = self::normalizeBlock($row);
            if ($block !== null) {
                $out[] = $block;
            }
            if (count($out) >= self::MAX_BLOCKS) {
                break;
            }
        }
        return $out;
    }

    /** @param array<string, mixed> $row */
    private static function normalizeBlock(array $row): ?array
    {
        $type = (string) ($row['type'] ?? '');
        if (!in_array($type, self::TYPES, true)) {
            return null;
        }
        $id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($row['id'] ?? '')) ?: self::freshId();
        $base = ['id' => $id, 'type' => $type];

        return match ($type) {
            'heading' => $base + [
                'text' => self::plain((string) ($row['text'] ?? ''), 180),
                'level' => in_array(($row['level'] ?? 'h2'), ['h2', 'h3'], true) ? (string) ($row['level'] ?? 'h2') : 'h2',
            ],
            'text' => $base + [
                'html' => sanitize_user_html((string) ($row['html'] ?? '')),
            ],
            'image' => $base + [
                'src' => self::safeImageSrc((string) ($row['src'] ?? '')),
                'alt' => self::plain((string) ($row['alt'] ?? ''), 160),
                'href' => self::safeUrl((string) ($row['href'] ?? '')),
            ],
            'button' => $base + [
                'label' => self::plain((string) ($row['label'] ?? ''), 80) ?: 'En savoir plus',
                'href' => self::safeUrl((string) ($row['href'] ?? '')),
                'align' => ($row['align'] ?? '') === 'center' ? 'center' : 'left',
            ],
            'divider' => $base,
            'spacer' => $base + [
                'height' => max(8, min(80, (int) ($row['height'] ?? 24))),
            ],
            'quote' => $base + [
                'text' => self::plain((string) ($row['text'] ?? ''), 500),
                'cite' => self::plain((string) ($row['cite'] ?? ''), 120),
            ],
            'cards' => $base + [
                'title' => self::plain((string) ($row['title'] ?? ''), 80),
                'items' => self::normalizeCards(is_array($row['items'] ?? null) ? $row['items'] : []),
            ],
            default => null,
        };
    }

    /**
     * @param list<mixed>|array<int|string, mixed> $raw
     * @return list<array<string, string>>
     */
    private static function normalizeCards(array $raw): array
    {
        $out = [];
        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }
            $title = self::plain((string) ($item['title'] ?? ''), 160);
            $href = self::safeUrl((string) ($item['href'] ?? ''));
            if ($title === '' && $href === '') {
                continue;
            }
            $out[] = [
                'title' => $title !== '' ? $title : 'À voir',
                'meta' => self::plain((string) ($item['meta'] ?? ''), 160),
                'excerpt' => self::plain((string) ($item['excerpt'] ?? ''), 280),
                'href' => $href,
            ];
            if (count($out) >= self::MAX_CARDS) {
                break;
            }
        }
        return $out;
    }

    /** @param list<array<string, mixed>> $blocks */
    public static function render(array $blocks, string $preheader = ''): string
    {
        $blocks = self::normalize($blocks);
        $html = '';
        $preheader = self::plain($preheader, 180);
        if ($preheader !== '') {
            $html .= '<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">'
                . e($preheader) . '</div>';
        }
        foreach ($blocks as $block) {
            $html .= self::renderBlock($block);
        }
        return $html;
    }

    /** @param array<string, mixed> $block */
    private static function renderBlock(array $block): string
    {
        return match ((string) ($block['type'] ?? '')) {
            'heading' => self::renderHeading($block),
            'text' => self::renderText($block),
            'image' => self::renderImage($block),
            'button' => self::renderButton($block),
            'divider' => '<hr style="border:0;border-top:1px solid #E8ECF1;margin:22px 0;">',
            'spacer' => '<div style="height:' . (int) ($block['height'] ?? 24) . 'px;line-height:' . (int) ($block['height'] ?? 24) . 'px;font-size:1px;">&nbsp;</div>',
            'quote' => self::renderQuote($block),
            'cards' => self::renderCards($block),
            default => '',
        };
    }

    /** @param array<string, mixed> $block */
    private static function renderHeading(array $block): string
    {
        $text = (string) ($block['text'] ?? '');
        if ($text === '') {
            return '';
        }
        $tag = ($block['level'] ?? 'h2') === 'h3' ? 'h3' : 'h2';
        $size = $tag === 'h3' ? '16px' : '20px';
        return '<' . $tag . ' style="font-size:' . $size . ';margin:22px 0 10px;color:#022746;">' . e($text) . '</' . $tag . '>';
    }

    /** @param array<string, mixed> $block */
    private static function renderText(array $block): string
    {
        $inner = (string) ($block['html'] ?? '');
        if (trim(strip_tags($inner)) === '') {
            return '';
        }
        return '<div style="margin:0 0 14px;">' . $inner . '</div>';
    }

    /** @param array<string, mixed> $block */
    private static function renderImage(array $block): string
    {
        $src = self::publicImageUrl((string) ($block['src'] ?? ''));
        if ($src === '') {
            return '';
        }
        $img = '<img src="' . e($src) . '" alt="' . e((string) ($block['alt'] ?? '')) . '" width="544" style="display:block;width:100%;max-width:544px;height:auto;border:0;border-radius:10px;">';
        $href = (string) ($block['href'] ?? '');
        if ($href !== '') {
            $img = '<a href="' . e($href) . '">' . $img . '</a>';
        }
        return '<p style="margin:0 0 16px;">' . $img . '</p>';
    }

    /** @param array<string, mixed> $block */
    private static function renderButton(array $block): string
    {
        $href = (string) ($block['href'] ?? '');
        $label = (string) ($block['label'] ?? 'En savoir plus');
        if ($href === '') {
            return '';
        }
        $align = ($block['align'] ?? 'left') === 'center' ? 'center' : 'left';
        return '<p style="margin:18px 0;text-align:' . $align . ';">'
            . '<a href="' . e($href) . '" style="display:inline-block;background:#D85D3F;color:#fff;text-decoration:none;font-weight:600;padding:11px 18px;border-radius:10px;">'
            . e($label) . '</a></p>';
    }

    /** @param array<string, mixed> $block */
    private static function renderQuote(array $block): string
    {
        $text = (string) ($block['text'] ?? '');
        if ($text === '') {
            return '';
        }
        $cite = (string) ($block['cite'] ?? '');
        $html = '<blockquote style="margin:16px 0;padding:0 0 0 14px;border-left:3px solid #D85D3F;color:#4A5A6B;">'
            . '<p style="margin:0;">' . e($text) . '</p>';
        if ($cite !== '') {
            $html .= '<p style="margin:8px 0 0;font-size:13px;color:#8496A8;">— ' . e($cite) . '</p>';
        }
        return $html . '</blockquote>';
    }

    /** @param array<string, mixed> $block */
    private static function renderCards(array $block): string
    {
        $items = is_array($block['items'] ?? null) ? $block['items'] : [];
        if ($items === []) {
            return '';
        }
        $html = '';
        $title = (string) ($block['title'] ?? '');
        if ($title !== '') {
            $html .= '<h2 style="font-size:17px;margin:28px 0 10px;color:#022746;">' . e($title) . '</h2>';
        }
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $href = (string) ($item['href'] ?? '');
            $name = (string) ($item['title'] ?? '');
            $line = $href !== ''
                ? '<a href="' . e($href) . '" style="color:#D85D3F;font-weight:600;">' . e($name) . '</a>'
                : '<strong>' . e($name) . '</strong>';
            $html .= '<p style="margin:0 0 14px;">' . $line;
            if (!empty($item['meta'])) {
                $html .= '<br><span style="color:#66768A;font-size:13px;">' . e((string) $item['meta']) . '</span>';
            }
            if (!empty($item['excerpt'])) {
                $html .= '<br>' . e((string) $item['excerpt']);
            }
            $html .= '</p>';
        }
        return $html;
    }

    public static function safeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || preg_match('#^(javascript|data|vbscript):#i', $url)) {
            return '';
        }
        if (preg_match('#^https://#i', $url)) {
            return $url;
        }
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            $path = safe_internal_path($url);
            return $path !== null ? url($path) : '';
        }
        return '';
    }

    public static function safeImageSrc(string $src): string
    {
        $src = trim($src);
        if ($src === '') {
            return '';
        }
        if (preg_match('#^https://#i', $src)) {
            return $src;
        }
        $path = str_replace(['\\', "\0"], '/', $src);
        if ($path === '' || str_contains($path, '..') || str_contains($path, ':')) {
            return '';
        }
        if (preg_match('#^(newsletter|journal|img)/[a-zA-Z0-9._/-]+$#', $path)) {
            return $path;
        }
        return '';
    }

    public static function publicImageUrl(string $src): string
    {
        $src = self::safeImageSrc($src);
        if ($src === '') {
            return '';
        }
        if (preg_match('#^https://#i', $src)) {
            return $src;
        }
        if (str_starts_with($src, 'img/')) {
            return asset($src);
        }
        return uploaded($src);
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    public static function block(string $type, array $extra = []): array
    {
        return self::normalizeBlock(['id' => self::freshId(), 'type' => $type] + $extra)
            ?? ['id' => self::freshId(), 'type' => 'text', 'html' => ''];
    }

    public static function freshId(): string
    {
        return 'b' . bin2hex(random_bytes(4));
    }

    private static function plain(string $value, int $max): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', RichText::plain($value)) ?? $value);
        if (mb_strlen($value) > $max) {
            $value = rtrim(mb_substr($value, 0, $max));
        }
        return $value;
    }
}
