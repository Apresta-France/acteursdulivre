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
$discussion = is_array($articleForumTopic ?? null) ? $articleForumTopic : null;
$commentError = trim((string) ($articleCommentError ?? ''));
$commentOld = is_array($articleCommentOld ?? null) ? $articleCommentOld : [];
$articleHref = (string) ($article['href'] ?? '/journal/' . ($slug ?? ''));
$authNext = rawurlencode($articleHref . '#commentaires');
$articleUser = auth_user();
?>
<article class="article-page" itemscope itemtype="https://schema.org/Article">
  <?php if (!empty($privatePreview)): ?>
    <div class="article-preview-banner">
      <div>
        <strong>Aperçu privé</strong>
        <span>Vous seul pouvez voir cette version. Elle n’est pas encore publiée dans le journal.</span>
      </div>
      <a class="btn-navy" href="<?= e(url('/espace/tribune/' . (int) $article['id'])) ?>">Revenir à l’éditeur</a>
    </div>
  <?php endif; ?>

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
            <?php if (!empty($article['author_name'])): ?>
              <strong itemprop="author" itemscope itemtype="https://schema.org/Person"><span itemprop="name"><?= e((string) $article['author_name']) ?></span></strong>
              <span>Tribune d’un membre d’Acteurs du Livre</span>
            <?php else: ?>
              <strong itemprop="author" itemscope itemtype="https://schema.org/Organization"><span itemprop="name">Rédaction Acteurs du Livre</span></strong>
              <span>EDITIONS TESSERACT · acteursdulivre.fr</span>
            <?php endif; ?>
          </div>
        </div>
        <?php if (empty($privatePreview)): ?>
          <div class="article-share-wrap">
            <?php require ADL_ROOT . '/app/Views/partials/share.php'; ?>
          </div>
        <?php endif; ?>
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

      <?php
      $relatedLinks = [
        ['href' => '/metiers/correction', 'icon' => 'trade-correction', 'label' => 'Trouver un correcteur'],
        ['href' => '/metiers/maquette', 'icon' => 'trade-maquette', 'label' => 'Trouver un maquettiste'],
        ['href' => '/metiers/illustration', 'icon' => 'trade-illustration', 'label' => 'Trouver un illustrateur'],
        ['href' => '/metiers/impression', 'icon' => 'trade-impression', 'label' => 'Trouver un imprimeur'],
        ['href' => '/prestations', 'icon' => 'bag', 'label' => 'Voir les prestations à prix affiché'],
        ['href' => '/comment-ca-marche', 'icon' => 'book', 'label' => 'Comment ça marche'],
      ];
      ?>
      <aside class="article-related" aria-label="Pour aller plus loin">
        <h2>Continuer sur Acteurs du Livre</h2>
        <ul>
          <?php foreach ($relatedLinks as $link): ?>
            <li>
              <a href="<?= e(url((string) $link['href'])) ?>">
                <span class="article-related-ico" aria-hidden="true"><?= icon((string) $link['icon'], 16) ?></span>
                <span class="article-related-label"><?= e((string) $link['label']) ?></span>
                <span class="article-related-arrow" aria-hidden="true"><?= icon('arrow', 16) ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </aside>

      <section class="article-comments" id="commentaires" aria-labelledby="article-comments-title">
        <div class="article-comments-head">
          <div>
            <p class="journal-kicker">La discussion continue sur le forum</p>
            <h2 id="article-comments-title">Et vous, qu’en pensez-vous&nbsp;?</h2>
          </div>
          <?php if ($discussion): ?>
            <?php $commentCount = (int) ($discussion['reply_count'] ?? 0) + 1; ?>
            <a class="article-comments-count" href="<?= e(url((string) $discussion['href'])) ?>">
              <?= e(format_int($commentCount)) ?> commentaire<?= $commentCount > 1 ? 's' : '' ?>
            </a>
          <?php endif; ?>
        </div>

        <p class="article-comments-intro">
          Votre commentaire ouvre ou rejoint un sujet dans la rubrique adaptée du forum, afin que toute la communauté puisse participer.
        </p>

        <?php if ($discussion): ?>
          <p class="article-comments-topic">
            Discussion classée dans
            <a href="<?= e(url((string) $discussion['category_href'])) ?>"><?= e((string) $discussion['category_name']) ?></a>.
            <a href="<?= e(url((string) $discussion['href'])) ?>">Voir tous les échanges</a>
          </p>
        <?php endif; ?>

        <?php if (!empty($logged) && (empty($discussion) || empty($discussion['is_locked']))): ?>
          <form class="forum-compose article-comment-form" method="post" action="<?= e(url($articleHref . '/commenter')) ?>" data-forum-compose data-min-chars="<?= (int) \Adl\Models\ForumPost::MIN_BODY ?>">
            <?= csrf_field() ?>
            <div class="forum-compose-head">
              <?= avatar_html($articleUser ?? [], 40, 'forum-avatar') ?>
              <div class="forum-compose-who">
                <div class="forum-post-name"><?= $discussion ? 'Votre commentaire' : 'Lancer la discussion' ?></div>
                <div class="forum-aside-meta">Votre message sera publié sur le forum.</div>
              </div>
              <span class="forum-pin">Sans IA</span>
            </div>
            <div class="forum-compose-body">
              <?php if ($commentError !== ''): ?>
                <p class="forum-compose-error" data-compose-error data-server-error><?= e($commentError) ?></p>
              <?php else: ?>
                <p class="forum-compose-error" data-compose-error hidden></p>
              <?php endif; ?>
              <?php
                $forumWysiwygName = 'body';
                $forumWysiwygValue = (string) ($commentOld['body'] ?? '');
                $forumWysiwygPlaceholder = 'Partagez votre point de vue, une expérience ou une information utile.';
                $forumWysiwygRows = 6;
                $forumWysiwygRequired = true;
                require ADL_ROOT . '/app/Views/partials/forum-wysiwyg.php';
              ?>
              <label class="forum-engage">
                <input type="checkbox" name="no_ai" value="1" required>
                <span>Je confirme que ce commentaire est de ma main et qu’aucune IA générative n’a été utilisée pour le produire.</span>
              </label>
              <p class="forum-compose-block" data-compose-block hidden role="status" aria-live="polite"></p>
              <div class="forum-compose-actions">
                <button type="submit" class="btn-orange"><?= $discussion ? 'Publier mon commentaire' : 'Commenter et ouvrir le sujet' ?></button>
                <span class="forum-draft-count" data-draft-count role="status" aria-live="polite">Minimum <?= (int) \Adl\Models\ForumPost::MIN_BODY ?> caractères pour publier</span>
              </div>
            </div>
          </form>
        <?php elseif (!empty($discussion['is_locked'])): ?>
          <p class="article-comments-locked">Cette discussion est actuellement fermée aux nouveaux commentaires.</p>
        <?php else: ?>
          <div class="article-comments-auth">
            <p>Connectez-vous ou créez votre compte gratuitement pour participer.</p>
            <div class="article-comments-actions">
              <a class="btn-orange" href="<?= e(url('/connexion?next=' . $authNext)) ?>">Se connecter</a>
              <a class="btn-ghost" href="<?= e(url('/inscription?next=' . $authNext)) ?>">Créer mon compte</a>
            </div>
          </div>
        <?php endif; ?>
      </section>

      <p class="article-back">
        <a href="<?= e(url('/journal')) ?>">
          <span aria-hidden="true">←</span>
          Retour au journal
        </a>
      </p>
    </div>
  </div>
</article>
