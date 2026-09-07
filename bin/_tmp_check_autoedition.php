<?php

declare(strict_types=1);

define('ADL_ROOT', dirname(__DIR__));
require ADL_ROOT . '/app/bootstrap.php';

use Adl\Data\ArticleHtml;
use Adl\Models\Article;

$article = Article::findBySlug('autoedition-guide-complet');
if (!$article) {
    fwrite(STDERR, "article missing\n");
    exit(1);
}

$body = (string) ($article['body'] ?? '');
echo 'title=' . $article['title'] . PHP_EOL;
echo 'cat=' . $article['cat'] . PHP_EOL;
echo 'words=' . $article['word_count'] . PHP_EOL;
echo 'read=' . $article['read'] . PHP_EOL;
echo 'toc=' . count($article['toc'] ?? []) . PHP_EOL;
echo 'faqs=' . count($article['faqs'] ?? []) . PHP_EOL;
echo 'howto_steps=' . count($article['howto']['steps'] ?? []) . PHP_EOL;
echo 'howto_name=' . ($article['howto']['name'] ?? '') . PHP_EOL;
echo 'has_dl=' . (str_contains($body, '<dl>') ? 'yes' : 'no') . PHP_EOL;
echo 'has_afnil=' . (str_contains($body, 'afnil.org') ? 'yes' : 'no') . PHP_EOL;
echo 'has_bnf=' . (str_contains($body, 'bnf.fr') ? 'yes' : 'no') . PHP_EOL;
echo 'internal_journal=' . preg_match_all('#href="/journal/[^"]+#', $body) . PHP_EOL;
echo 'internal_metiers=' . preg_match_all('#href="/metiers/[^"]+#', $body) . PHP_EOL;
echo 'external_https=' . preg_match_all('#href="https://#', $body) . PHP_EOL;

$linked = Adl\Core\Database::fetchAll(
    'SELECT slug FROM articles WHERE body LIKE ? ORDER BY slug',
    ['%autoedition-guide-complet%']
);
echo 'backlinks=' . implode(',', array_column($linked, 'slug')) . PHP_EOL;

$prepared = ArticleHtml::enhance(sanitize_rich_html($body));
echo 'sanitized_dl=' . (str_contains($prepared['html'], '<dl>') ? 'yes' : 'no') . PHP_EOL;
echo 'rel_noopener=' . (str_contains($prepared['html'], 'noopener') ? 'yes' : 'no') . PHP_EOL;
