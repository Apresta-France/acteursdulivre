<?php
$article = $article ?? null;
if (!$article) {
    not_found('Cet article n\'est plus en ligne.');
}
$shareUrl = $meta['url'] ?? \Adl\Data\Share::current();
$shareTitle = $meta['title'] ?? (string) $article['title'];
$shareText = $meta['description'] ?? (string) ($article['excerpt'] ?? '');
$shareLabel = 'Partager l\'article';
$toc = is_array($article['toc'] ?? null) ? $article['toc'] : [];
$cover = (string) ($article['img'] ?? '');
$coverAlt = (string) ($article['image_alt'] ?? $article['title']);
?>
<article class="article-page" itemscope itemtype="https://schema.org/Article">
  <nav class="search-crumb" aria-label="Fil d'Ariane">
    <a href="<?= e(url('/')) ?>">Accueil</a>
    <span aria-hidden="true"> · </span>
    <a href="<?= e(url('/journal')) ?>">Le journal</a>
    <span aria-hidden="true"> · </span>
    <span><?= e((string) $article['cat']) ?></span>
  </nav>

  <div class="article-layout">
    <?php if ($toc !== []): ?>
      <nav class="article-toc" data-article-toc aria-label="Sommaire de l'article">
        <button class="article-toc-toggle" type="button" data-toc-toggle aria-expanded="false">
          Sommaire
        </button>
        <p class="article-toc-title">Sommaire</p>
        <ol>
          <?php foreach ($toc as $item): ?>
            <li>
              <a href="#<?= e((string) $item['id']) ?>"><?= e((string) $item['label']) ?></a>
            </li>
          <?php endforeach; ?>
        </ol>
      </nav>
    <?php endif; ?>

    <div class="article-main">
      <header class="article-head">
        <p class="journal-kicker">
          <span itemprop="articleSection"><?= e((string) $article['cat']) ?></span>
          · <?= e((string) $article['read']) ?> de lecture
          <?php if ($article['when'] !== ''): ?>
            · <time itemprop="datePublished" datetime="<?= e(substr((string) ($article['published_at'] ?? ''), 0, 10)) ?>"><?= e((string) $article['when']) ?></time>
          <?php endif; ?>
        </p>
        <h1 itemprop="headline"><?= e((string) $article['title']) ?></h1>
        <?php if (!empty($article['chapo'])): ?>
          <p class="article-chapo" itemprop="description"><?= e((string) $article['chapo']) ?></p>
        <?php endif; ?>
        <div class="article-byline">
          <div>
            <strong itemprop="author" itemscope itemtype="https://schema.org/Organization"><span itemprop="name">Rédaction Acteurs du Livre</span></strong>
            <span>EDITIONS TESSERACT · acteursdulivre.fr</span>
          </div>
        </div>
        <div class="article-share-wrap">
          <?php require ADL_ROOT . '/app/Views/partials/share.php'; ?>
        </div>
      </header>

      <?php if ($cover !== ''): ?>
        <figure class="article-cover">
          <img
            src="<?= e($cover) ?>"
            alt="<?= e($coverAlt) ?>"
            width="1200"
            height="675"
            itemprop="image"
            fetchpriority="high"
            decoding="async"
          >
          <figcaption><?= e($coverAlt) ?></figcaption>
        </figure>
      <?php endif; ?>

      <div class="article-body" itemprop="articleBody">
        <?= (string) ($article['body_html'] ?? $article['body'] ?? '') ?: '<p>Le texte de cet article n\'est pas encore renseigné.</p>' ?>
      </div>

      <aside class="article-related" aria-label="Pour aller plus loin">
        <h2>Continuer sur Acteurs du Livre</h2>
        <ul>
          <li><a href="<?= e(url('/metiers/correction')) ?>">Trouver un correcteur</a></li>
          <li><a href="<?= e(url('/metiers/maquette')) ?>">Trouver un maquettiste</a></li>
          <li><a href="<?= e(url('/metiers/illustration')) ?>">Trouver un illustrateur</a></li>
          <li><a href="<?= e(url('/metiers/impression')) ?>">Trouver un imprimeur</a></li>
          <li><a href="<?= e(url('/prestations')) ?>">Voir les prestations à prix affiché</a></li>
          <li><a href="<?= e(url('/comment-ca-marche')) ?>">Comment ça marche</a></li>
        </ul>
      </aside>

      <p><a class="btn-ghost" href="<?= e(url('/journal')) ?>">Retour au journal</a></p>
    </div>
  </div>
</article>
