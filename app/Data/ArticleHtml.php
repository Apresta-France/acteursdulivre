<?php

declare(strict_types=1);

namespace Adl\Data;

use DOMDocument;
use DOMElement;

final class ArticleHtml
{
    /**
     * @return array{html: string, toc: list<array{id: string, label: string, level: int}>, faqs: list<array{q: string, a: string}>}
     */
    public static function enhance(string $html): array
    {
        $html = trim($html);
        if ($html === '') {
            return ['html' => '', 'toc' => [], 'faqs' => []];
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
            return ['html' => $html, 'toc' => [], 'faqs' => []];
        }

        $root = $dom->getElementById('adl-art');
        if (!$root instanceof DOMElement) {
            return ['html' => $html, 'toc' => [], 'faqs' => []];
        }

        $used = [];
        $toc = [];
        $faqStart = null;

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
}
