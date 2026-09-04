<?php

declare(strict_types=1);

namespace Adl\Core;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

final class RichText
{
    public const PROFILE_FULL = 'full';
    public const PROFILE_BASIC = 'basic';
    public const PROFILE_RECOMMENDATION = 'recommendation';

    /** @var array<string, array<string, list<string>>> */
    private const PROFILES = [
        self::PROFILE_FULL => [
            'p' => [],
            'br' => [],
            'strong' => [],
            'b' => [],
            'em' => [],
            'i' => [],
            'u' => [],
            'ul' => [],
            'ol' => [],
            'li' => [],
            'h2' => ['id'],
            'h3' => ['id'],
            'h4' => ['id'],
            'blockquote' => [],
            'figure' => [],
            'figcaption' => [],
            'table' => [],
            'caption' => [],
            'thead' => [],
            'tbody' => [],
            'tr' => [],
            'th' => ['scope'],
            'td' => [],
            'a' => ['href', 'title', 'rel', 'target'],
        ],
        self::PROFILE_BASIC => [
            'p' => [],
            'br' => [],
            'strong' => [],
            'b' => [],
            'em' => [],
            'i' => [],
            'u' => [],
            'ul' => [],
            'ol' => [],
            'li' => [],
            'blockquote' => [],
            'a' => ['href', 'title', 'rel', 'target'],
        ],
        self::PROFILE_RECOMMENDATION => [
            'p' => [],
            'br' => [],
            'strong' => [],
            'b' => [],
            'em' => [],
            'i' => [],
            'u' => [],
        ],
    ];

    public static function sanitize(string $html, string $profile = self::PROFILE_FULL): string
    {
        $allowed = self::PROFILES[$profile] ?? self::PROFILES[self::PROFILE_FULL];
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        if (strip_tags($html) === $html) {
            return self::plainToHtml($html);
        }

        $dom = new DOMDocument();
        $dom->encoding = 'UTF-8';
        libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="adl-rt">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        if (!$loaded) {
            return self::plainToHtml(self::plain($html));
        }

        $root = $dom->getElementById('adl-rt');
        if (!$root instanceof DOMElement) {
            return '';
        }

        self::promoteDivs($root, $dom);
        self::cleanNode($root, $dom, $allowed);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child) ?: '';
        }
        $out = trim($out);

        return self::plain($out) === '' ? '' : $out;
    }

    public static function plain(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xc2\xa0", ' ', $text);

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    private static function plainToHtml(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $blocks = preg_split("/\n{2,}/", $text) ?: [$text];
        $html = '';
        foreach ($blocks as $block) {
            $block = trim($block);
            if ($block === '') {
                continue;
            }
            $html .= '<p>' . nl2br(htmlspecialchars($block, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false) . '</p>';
        }

        return $html;
    }

    private static function promoteDivs(DOMElement $root, DOMDocument $dom): void
    {
        foreach (iterator_to_array($root->childNodes) as $child) {
            if (!$child instanceof DOMElement || strtolower($child->tagName) !== 'div') {
                continue;
            }
            $p = $dom->createElement('p');
            while ($child->firstChild) {
                $p->appendChild($child->firstChild);
            }
            $root->replaceChild($p, $child);
        }
    }

    /** @var list<string> */
    private const DROP = [
        'script', 'style', 'iframe', 'object', 'embed', 'svg', 'math',
        'video', 'audio', 'canvas', 'template', 'noscript', 'link', 'meta', 'base',
        'input', 'button', 'select', 'option', 'optgroup', 'textarea',
        'label', 'fieldset', 'legend',
    ];

    /** @param array<string, list<string>> $allowed */
    private static function cleanNode(DOMNode $node, DOMDocument $dom, array $allowed): void
    {
        $toRemove = [];
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMText) {
                continue;
            }
            if (!$child instanceof DOMElement) {
                $toRemove[] = $child;
                continue;
            }

            $tag = strtolower($child->tagName);
            if (in_array($tag, self::DROP, true)) {
                $toRemove[] = $child;
                continue;
            }

            if (isset($allowed[$tag])) {
                self::cleanAttributes($child, $tag, $allowed);
            }

            self::cleanNode($child, $dom, $allowed);

            $unwrap = !isset($allowed[$tag])
                || ($tag === 'a' && trim($child->getAttribute('href')) === '');
            if ($unwrap) {
                $parent = $child->parentNode;
                while ($child->firstChild) {
                    $parent?->insertBefore($child->firstChild, $child);
                }
                $toRemove[] = $child;
            }
        }

        foreach ($toRemove as $dead) {
            $dead->parentNode?->removeChild($dead);
        }
    }

    /** @param array<string, list<string>> $allowed */
    private static function cleanAttributes(DOMElement $el, string $tag, array $allowed): void
    {
        $keep = $allowed[$tag];
        $names = [];
        if ($el->hasAttributes()) {
            foreach ($el->attributes as $attr) {
                $names[] = $attr->name;
            }
        }

        foreach ($names as $name) {
            $lname = strtolower($name);
            if (!in_array($lname, $keep, true)) {
                $el->removeAttribute($name);
                continue;
            }
            if ($lname === 'href') {
                $href = trim($el->getAttribute('href'));
                if (!self::safeHref($href)) {
                    $el->removeAttribute('href');
                    $el->removeAttribute('rel');
                    $el->removeAttribute('target');
                } elseif (preg_match('#^https?://#i', $href) === 1) {
                    $el->setAttribute('href', $href);
                    $el->setAttribute('rel', 'noopener noreferrer');
                    $el->setAttribute('target', '_blank');
                } else {
                    $el->setAttribute('href', $href);
                    $el->removeAttribute('rel');
                    $el->removeAttribute('target');
                }
            }
            if ($lname === 'title') {
                $el->setAttribute('title', strip_tags($el->getAttribute('title')));
            }
            if ($lname === 'id') {
                $id = trim($el->getAttribute('id'));
                if (preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,79}$/', $id) !== 1) {
                    $el->removeAttribute('id');
                }
            }
            if ($lname === 'scope') {
                $scope = strtolower(trim($el->getAttribute('scope')));
                if (!in_array($scope, ['col', 'row', 'colgroup', 'rowgroup'], true)) {
                    $el->removeAttribute('scope');
                } else {
                    $el->setAttribute('scope', $scope);
                }
            }
        }
    }

    private static function safeHref(string $href): bool
    {
        if ($href === '' || str_starts_with($href, '#')) {
            return $href !== '';
        }
        if (preg_match('#^(javascript|data|vbscript):#i', $href) === 1) {
            return false;
        }
        if (preg_match('#^mailto:[^\s]+$#i', $href) === 1) {
            return true;
        }
        if (function_exists('safe_internal_path') && safe_internal_path($href) !== null) {
            return true;
        }

        return preg_match('#^https?://#i', $href) === 1;
    }
}
