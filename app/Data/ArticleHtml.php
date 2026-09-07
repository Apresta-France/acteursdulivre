<?php

declare(strict_types=1);

namespace Adl\Data;

use DOMDocument;
use DOMElement;

final class ArticleHtml
{
    /**
     * @return array{
     *     html: string,
     *     toc: list<array{id: string, label: string, level: int}>,
     *     faqs: list<array{q: string, a: string}>,
     *     howto: array{name: string, steps: list<array{name: string, text: string}>}|null
     * }
     */
    public static function enhance(string $html): array
    {
        $empty = ['html' => $html, 'toc' => [], 'faqs' => [], 'howto' => null];
        $html = trim($html);
        if ($html === '') {
            return ['html' => '', 'toc' => [], 'faqs' => [], 'howto' => null];
        }

        $dom = new DOMDocument();
        $dom->encoding = 'UTF-8';
        libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="adl-art">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        if (!$loaded) {
            return $empty;
        }

        $root = $dom->getElementById('adl-art');
        if (!$root instanceof DOMElement) {
            return $empty;
        }

        $used = [];
        $toc = [];
        $faqStart = null;
        $howtoStart = null;

        foreach (iterator_to_array($root->getElementsByTagName('*')) as $el) {
            if (!$el instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($el->tagName);
            if ($tag !== 'h2' && $tag !== 'h3') {
                continue;
            }
            $label = trim(preg_replace('/\s+/u', ' ', $el->textContent) ?? '');
            if ($label === '') {
                continue;
            }
            $id = trim($el->getAttribute('id'));
            if ($id === '' || isset($used[$id])) {
                $base = slugify($label) ?: 'section';
                $id = $base;
                $n = 2;
                while (isset($used[$id])) {
                    $id = $base . '-' . $n++;
                }
                $el->setAttribute('id', $id);
            }
            $used[$id] = true;
            if ($tag === 'h2') {
                $toc[] = ['id' => $id, 'label' => $label, 'level' => 2];
                if ($faqStart === null && preg_match('/questions? fr[eé]quentes|\bfaq\b/iu', $label) === 1) {
                    $faqStart = $el;
                }
                if ($howtoStart === null && preg_match('/[eé]tapes pour s.?auto|[eé]tapes d.une auto/iu', $label) === 1) {
                    $howtoStart = $el;
                }
            }
        }

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child) ?: '';
        }

        return [
            'html' => trim($out),
            'toc' => $toc,
            'faqs' => $faqStart instanceof DOMElement ? self::faqsFrom($faqStart) : [],
            'howto' => $howtoStart instanceof DOMElement ? self::howtoFrom($howtoStart) : null,
        ];
    }

    /** @return list<array{q: string, a: string}> */
    private static function faqsFrom(DOMElement $h2): array
    {
        $faqs = [];
        $node = $h2->nextSibling;
        $currentQ = null;
        $parts = [];
        while ($node) {
            if ($node instanceof DOMElement) {
                $tag = strtolower($node->tagName);
                if ($tag === 'h2') {
                    break;
                }
                if ($tag === 'h3') {
                    if ($currentQ !== null && $parts !== []) {
                        $faqs[] = ['q' => $currentQ, 'a' => trim(implode(' ', $parts))];
                    }
                    $currentQ = trim(preg_replace('/\s+/u', ' ', $node->textContent) ?? '');
                    $parts = [];
                } elseif (in_array($tag, ['p', 'ul', 'ol'], true) && $currentQ !== null) {
                    $parts[] = trim(preg_replace('/\s+/u', ' ', $node->textContent) ?? '');
                }
            }
            $node = $node->nextSibling;
        }
        if ($currentQ !== null && $parts !== []) {
            $faqs[] = ['q' => $currentQ, 'a' => trim(implode(' ', $parts))];
        }

        return $faqs;
    }

    /**
     * @return array{name: string, steps: list<array{name: string, text: string}>}|null
     */
    private static function howtoFrom(DOMElement $h2): ?array
    {
        $name = trim(preg_replace('/\s+/u', ' ', $h2->textContent) ?? '');
        $node = $h2->nextSibling;
        while ($node) {
            if ($node instanceof DOMElement) {
                $tag = strtolower($node->tagName);
                if ($tag === 'h2') {
                    break;
                }
                if ($tag === 'ol') {
                    $steps = [];
                    foreach ($node->getElementsByTagName('li') as $li) {
                        if (!$li instanceof DOMElement || $li->parentNode !== $node) {
                            continue;
                        }
                        $text = trim(preg_replace('/\s+/u', ' ', $li->textContent) ?? '');
                        if ($text === '') {
                            continue;
                        }
                        $label = '';
                        foreach ($li->childNodes as $child) {
                            if ($child instanceof DOMElement && strtolower($child->tagName) === 'strong') {
                                $label = trim(preg_replace('/\s+/u', ' ', $child->textContent) ?? '');
                                break;
                            }
                        }
                        $steps[] = [
                            'name' => $label !== '' ? $label : $text,
                            'text' => $text,
                        ];
                    }
                    if ($steps === []) {
                        return null;
                    }
                    return ['name' => $name !== '' ? $name : 'Étapes', 'steps' => $steps];
                }
            }
            $node = $node->nextSibling;
        }

        return null;
    }
}
