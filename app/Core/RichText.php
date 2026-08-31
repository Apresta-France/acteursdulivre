<?php

declare(strict_types=1);

namespace Adl\Core;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

final class RichText
{
    /** @var array<string, list<string>> */
    private const ALLOWED = [
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
        'h3' => [],
        'h4' => [],
        'a' => ['href', 'title', 'rel', 'target'],
    ];

    public static function sanitize(string $html): string
    {
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
        self::cleanNode($root, $dom);

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

    private static function cleanNode(DOMNode $node, DOMDocument $dom): void
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
            if ($tag === 'script' || $tag === 'style') {
                $toRemove[] = $child;
                continue;
            }

            if ($tag === 'div') {
                $parent = $child->parentNode;
                while ($child->firstChild) {
                    $parent?->insertBefore($child->firstChild, $child);
                }
                $toRemove[] = $child;
                continue;
            }

            if (!isset(self::ALLOWED[$tag])) {
                $parent = $child->parentNode;
                while ($child->firstChild) {
                    $parent?->insertBefore($child->firstChild, $child);
                }
                $toRemove[] = $child;
                continue;
            }

            self::cleanAttributes($child, $tag);
            if ($tag === 'a' && trim($child->getAttribute('href')) === '') {
                $parent = $child->parentNode;
                while ($child->firstChild) {
                    $parent?->insertBefore($child->firstChild, $child);
                }
                $toRemove[] = $child;
                continue;
            }
            self::cleanNode($child, $dom);
        }

        foreach ($toRemove as $dead) {
            $dead->parentNode?->removeChild($dead);
        }
    }

    private static function cleanAttributes(DOMElement $el, string $tag): void
    {
        $allowed = self::ALLOWED[$tag];
        $names = [];
        if ($el->hasAttributes()) {
            foreach ($el->attributes as $attr) {
                $names[] = $attr->name;
            }
        }

        foreach ($names as $name) {
            $lname = strtolower($name);
            if (!in_array($lname, $allowed, true)) {
                $el->removeAttribute($name);
                continue;
            }
            if ($lname === 'href') {
                $href = trim($el->getAttribute('href'));
                if (!self::safeHref($href)) {
                    $el->removeAttribute('href');
                    $el->removeAttribute('rel');
                    $el->removeAttribute('target');
                } else {
                    $el->setAttribute('href', $href);
                    $el->setAttribute('rel', 'noopener noreferrer');
                    $el->setAttribute('target', '_blank');
                }
            }
            if ($lname === 'title') {
                $el->setAttribute('title', strip_tags($el->getAttribute('title')));
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

        return preg_match('#^https?://#i', $href) === 1;
    }
}
