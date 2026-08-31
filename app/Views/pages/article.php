<?php
$article = $article ?? null;
if (!$article) {
    not_found('Cet article n\'est plus en ligne.');
}
$shareUrl = $meta['url'] ?? \Adl\Data\Share::current();
$shareTitle = $meta['title'] ?? (string) $article['title'];
$shareText = $meta['description'] ?? (string) ($article['excerpt'] ?? '');
$shareLabel = 'Partager l\'article';
?>
<div class="article-page">
  <div class="search-crumb">Le journal · <?= e((string) $article['cat']) ?></div>
  <div class="article-share-wrap">
    <?php require ADL_ROOT . '/app/Views/partials/share.php'; ?>
  </div>
  <p class="journal-kicker"><?= e((string) $article['cat']) ?> · <?= e((string) $article['read']) ?> de lecture<?= $article['when'] !== '' ? ' · ' . e((string) $article['when']) : '' ?></p>
  <h1><?= e((string) $article['title']) ?></h1>
  <?php if (!empty($article['chapo'])): ?>
    <p class="article-chapo"><?= e((string) $article['chapo']) ?></p>
  <?php endif; ?>
  <div class="article-body">
    <?= rich_html((string) ($article['body'] ?? ''), '<p>Le texte de cet article n\'est pas encore renseigné.</p>') ?>
  </div>
  <p><a class="btn-ghost" href="<?= e(url('/journal')) ?>">Retour au journal</a></p>
</div>
