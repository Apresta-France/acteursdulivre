<?php
$category = $forumCategory ?? [];
$topics = $forumTopics ?? [];
$filter = (string) ($forumFilter ?? 'recent');
$contributors = $forumContributors ?? [];
$tags = $forumTags ?? [];
$pinnedReads = $forumPinnedReads ?? [];
$postCount = (int) ($forumPostCount ?? 0);
$pager = $pager ?? ['page' => 1, 'pages' => 1, 'total' => 0];
$page = (int) ($pager['page'] ?? 1);
$pages = (int) ($pager['pages'] ?? 1);
$total = (int) ($pager['total'] ?? 0);
$logged = !empty($logged);
$catHref = (string) ($category['href'] ?? '/forum');

$tabs = [
    'recent' => 'Dernière activité',
    'unanswered' => 'Sans réponse',
    'useful' => 'Les plus utiles',
    'solved' => 'Résolues',
];

$catUrl = static function (string $filtre = 'recent', int $p = 1) use ($catHref): string {
    $params = [];
    if ($filtre !== 'recent') {
        $params['filtre'] = match ($filtre) {
            'unanswered' => 'sans-reponse',
            'useful' => 'utiles',
            'solved' => 'resolues',
            default => $filtre,
        };
    }
    if ($p > 1) {
        $params['page'] = $p;
    }
    $qs = http_build_query($params);
    return $catHref . ($qs !== '' ? '?' . $qs : '');
};
?>
<div class="forum-page">
  <section class="forum-hero forum-hero-compact">
    <div class="forum-crumb">
      <a href="<?= e(url('/forum')) ?>">Forum</a>
      <span>/</span>
      <span><?= e((string) ($category['name'] ?? '')) ?></span>
    </div>
    <div class="forum-hero-grid">
      <div>
        <div class="forum-hero-title-row">
          <span class="forum-cat-num is-lg"><?= e((string) ($category['n'] ?? '')) ?></span>
          <h1><?= e((string) ($category['name'] ?? '')) ?></h1>
        </div>
        <p class="forum-lead"><?= e((string) ($category['description'] ?? '')) ?></p>
      </div>
      <div class="forum-hero-actions">
        <?php if ($logged): ?>
          <a class="btn-orange forum-hero-cta" href="<?= e(url('/forum/nouveau?rubrique=' . rawurlencode((string) ($category['slug'] ?? '')))) ?>">Ouvrir une discussion</a>
        <?php else: ?>
          <a class="btn-orange forum-hero-cta" href="<?= e(url('/connexion')) ?>">Se connecter pour discuter</a>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <div class="forum-body">
    <div class="forum-tabs">
      <?php foreach ($tabs as $key => $label): ?>
        <a class="chip<?= $filter === $key ? ' is-on' : '' ?>" href="<?= e(url($catUrl($key))) ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
      <div class="forum-section-spacer"></div>
      <span class="forum-muted"><?= e(format_int($total)) ?> discussions · <?= e(format_int($postCount)) ?> messages</span>
    </div>

    <div class="forum-split forum-split-narrow">
      <div>
        <?php if ($topics === []): ?>
          <div class="search-empty forum-empty">
            <strong>Aucune discussion dans cette rubrique.</strong>
            <span><?php if ($logged): ?><a href="<?= e(url('/forum/nouveau?rubrique=' . rawurlencode((string) ($category['slug'] ?? '')))) ?>">Ouvrir la première</a><?php else: ?><a href="<?= e(url('/connexion')) ?>">Connectez-vous</a> pour en ouvrir une.<?php endif; ?></span>
          </div>
        <?php else: ?>
          <div class="forum-table">
            <div class="forum-table-head">
              <span>Discussion</span>
              <span>Auteur</span>
              <span class="is-right">Rép.</span>
              <span class="is-right">Vues</span>
              <span class="is-right">Dernier message</span>
            </div>
            <?php foreach ($topics as $s): ?>
              <a class="forum-table-row<?= !empty($s['is_pinned']) ? ' is-pinned' : '' ?>" href="<?= e(url((string) $s['href'])) ?>">
                <div class="forum-table-disc">
                  <div class="forum-row-line">
                    <?php if (!empty($s['is_pinned'])): ?><span class="forum-pin">Épinglé</span><?php endif; ?>
                    <span class="forum-row-title"><?= e((string) ($s['title'] ?? '')) ?></span>
                    <?php if (!empty($s['badge'])): ?>
                      <span class="forum-badge forum-badge-<?= e(slugify((string) $s['badge'])) ?>"><?= e((string) $s['badge']) ?></span>
                    <?php endif; ?>
                  </div>
                  <?php if (!empty($s['tags_label'])): ?>
                    <div class="forum-row-sub"><?= e((string) $s['tags_label']) ?></div>
                  <?php endif; ?>
                </div>
                <div class="forum-table-author">
                  <?= avatar_html($s['author'] ?? [], 28, 'forum-avatar') ?>
                  <span><?= e((string) ($s['author']['name'] ?? '')) ?></span>
                </div>
                <span class="forum-row-num is-right"><?= (int) ($s['reply_count'] ?? 0) ?></span>
                <span class="forum-row-num is-muted is-right"><?= e((string) ($s['views_label'] ?? '0')) ?></span>
                <div class="forum-table-last is-right">
                  <div><?= e((string) ($s['last_by'] ?? '')) ?></div>
                  <div class="forum-aside-meta"><?= e((string) ($s['last_when'] ?? '')) ?></div>
                </div>
              </a>
            <?php endforeach; ?>
          </div>

          <?php if ($pages > 1): ?>
            <nav class="forum-pager" aria-label="Pagination">
              <?php if ($page > 1): ?><a href="<?= e(url($catUrl($filter, $page - 1))) ?>">‹</a><?php endif; ?>
              <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
                <a class="<?= $i === $page ? 'is-on' : '' ?>" href="<?= e(url($catUrl($filter, $i))) ?>"><?= $i ?></a>
              <?php endfor; ?>
              <?php if ($page < $pages): ?><a href="<?= e(url($catUrl($filter, $page + 1))) ?>">›</a><?php endif; ?>
            </nav>
          <?php endif; ?>
        <?php endif; ?>
      </div>

      <aside class="forum-aside">
        <?php if ($pinnedReads !== []): ?>
          <div class="forum-panel">
            <div class="forum-panel-title">À lire avant de poster</div>
            <div class="forum-aside-list">
              <?php foreach (array_slice($pinnedReads, 0, 3) as $a): ?>
                <a href="<?= e(url((string) ($a['href'] ?? '#'))) ?>">
                  <div class="forum-aside-link"><?= e((string) ($a['title'] ?? '')) ?></div>
                  <div class="forum-aside-meta"><?= !empty($a['is_pinned']) ? 'épinglé · ' : '' ?><?= e((string) ($a['last_when'] ?? $a['when'] ?? '')) ?></div>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($contributors !== []): ?>
          <div class="forum-panel">
            <div class="forum-panel-title">Habitués de la rubrique</div>
            <div class="forum-contrib">
              <?php foreach ($contributors as $c): ?>
                <div class="forum-contrib-row">
                  <?= avatar_html($c, 38, 'forum-avatar') ?>
                  <div class="forum-contrib-copy">
                    <div class="forum-contrib-name"><?= e((string) ($c['name'] ?? '')) ?></div>
                    <div class="forum-aside-meta"><?= e((string) ($c['meta'] ?? '')) ?></div>
                  </div>
                  <span class="forum-contrib-n"><?= e(format_int((int) ($c['post_count'] ?? 0))) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($tags !== []): ?>
          <div class="forum-panel">
            <div class="forum-panel-title">Étiquettes de la rubrique</div>
            <div class="forum-tags">
              <?php foreach ($tags as $tag): ?>
                <a class="forum-tag" href="<?= e(url('/forum?q=' . rawurlencode((string) $tag))) ?>"><?= e((string) $tag) ?></a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </aside>
    </div>
  </div>
</div>
