<?php
$categories = $forumCategories ?? [];
$stats = $forumStats ?? ['topics' => 0, 'posts' => 0, 'unanswered' => 0, 'week' => 0];
$recent = $forumRecent ?? [];
$unanswered = $forumUnanswered ?? [];
$contributors = $forumContributors ?? [];
$tags = $forumTags ?? [];
$topics = $forumTopics ?? [];
$filter = (string) ($forumFilter ?? 'recent');
$q = (string) ($forumQ ?? '');
$pager = $pager ?? ['page' => 1, 'pages' => 1, 'total' => 0];
$page = (int) ($pager['page'] ?? 1);
$pages = (int) ($pager['pages'] ?? 1);
$total = (int) ($pager['total'] ?? 0);
$logged = !empty($logged);

$filterTabs = [
    'recent' => 'Discussions récentes',
    'unanswered' => 'Sans réponse' . ((int) $stats['unanswered'] > 0 ? ' (' . format_int((int) $stats['unanswered']) . ')' : ''),
    'popular' => 'Les plus suivies',
];
if ($logged) {
    $filterTabs['mine'] = 'Mes discussions';
}

$sortLabels = [
    'recent' => 'Dernière activité d\'abord',
    'unanswered' => 'Sans réponse d\'abord',
    'popular' => 'Les plus suivies d\'abord',
    'mine' => 'Vos discussions',
];

$forumListUrl = static function (string $filtre = 'recent', int $p = 1, string $query = '') use ($q): string {
    $params = [];
    if ($filtre !== 'recent') {
        $params['filtre'] = match ($filtre) {
            'unanswered' => 'sans-reponse',
            'popular' => 'suivies',
            'mine' => 'mine',
            default => $filtre,
        };
    }
    $search = $query !== '' ? $query : $q;
    if ($search !== '') {
        $params['q'] = $search;
    }
    if ($p > 1) {
        $params['page'] = $p;
    }
    $qs = http_build_query($params);
    return '/forum' . ($qs !== '' ? '?' . $qs : '');
};
?>
<div class="forum-page">
  <section class="forum-hero">
    <div class="forum-hero-copy">
      <div class="forum-kicker">Communauté</div>
      <h1>Le forum des métiers du livre</h1>
      <p class="forum-lead">On y parle tarifs, contrats, papier, délais et cas concrets. Les réponses viennent de gens qui font le métier — pas d'une machine.</p>
    </div>
    <div class="forum-hero-actions">
      <?php if ($logged): ?>
        <a class="btn-orange forum-hero-cta" href="<?= e(url('/forum/nouveau')) ?>">Ouvrir une discussion</a>
      <?php else: ?>
        <a class="btn-orange forum-hero-cta" href="<?= e(url('/connexion')) ?>">Se connecter pour discuter</a>
      <?php endif; ?>
      <div class="forum-hero-stats">
        <div class="forum-stat">
          <strong><?= e(format_int((int) $stats['topics'])) ?></strong>
          <span>discussions</span>
        </div>
        <div class="forum-stat">
          <strong><?= e(format_int((int) $stats['posts'])) ?></strong>
          <span>messages</span>
        </div>
        <div class="forum-stat">
          <strong><?= e(format_int((int) $stats['week'])) ?></strong>
          <span>cette semaine</span>
        </div>
      </div>
    </div>
  </section>

  <div class="forum-body">
    <div class="forum-notice">
      <span class="forum-notice-icon" aria-hidden="true">
        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8.5"/><path d="M6 18L18 6"/></svg>
      </span>
      <div class="forum-notice-copy">
        <div class="forum-notice-title">
          <span class="forum-pin">Épinglé</span>
          <span>Charte du forum : aucune réponse générée par IA</span>
        </div>
        <p>Les réponses sont écrites par des humains qui exercent le métier. Un message généré est retiré, et l'engagement signé à l'inscription s'applique ici comme sur les prestations.</p>
      </div>
      <a class="forum-notice-link" href="<?= e(url('/regles-ia')) ?>">Lire la charte →</a>
    </div>

    <?php if ($recent !== []): ?>
      <div class="forum-section-head">
        <h2>Dernières discussions</h2>
        <span class="forum-muted">mises à jour récemment</span>
        <div class="forum-section-spacer"></div>
        <a href="<?= e(url($forumListUrl('recent'))) ?>">Voir les <?= e(format_int((int) $stats['topics'])) ?> discussions →</a>
      </div>
      <div class="forum-list forum-list-compact">
        <?php foreach ($recent as $s): ?>
          <a class="forum-row" href="<?= e(url((string) $s['href'])) ?>">
            <?= avatar_html($s['author'] ?? [], 34, 'forum-avatar') ?>
            <span class="forum-cat forum-cat-<?= e((string) ($s['tone'] ?? 'navy')) ?>"><?= e((string) ($s['category_short'] ?? '')) ?></span>
            <span class="forum-row-title"><?= e((string) ($s['title'] ?? '')) ?></span>
            <?php if (!empty($s['badge'])): ?>
              <span class="forum-badge forum-badge-<?= e(slugify((string) $s['badge'])) ?>"><?= e((string) $s['badge']) ?></span>
            <?php endif; ?>
            <span class="forum-row-meta"><?= e((string) (($s['author']['name'] ?? '') . ' · ' . ($s['last_when'] ?? ''))) ?></span>
            <span class="forum-row-num"><?= (int) ($s['reply_count'] ?? 0) ?> rép.</span>
            <span class="forum-row-num is-muted"><?= e((string) ($s['views_label'] ?? '0')) ?> vues</span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($categories !== []): ?>
      <div class="forum-section-head">
        <h2>Les rubriques</h2>
        <span class="forum-muted">une par grande question de métier — chacune a ses habitués</span>
      </div>
      <div class="forum-cats">
        <?php foreach ($categories as $r): ?>
          <a class="forum-cat-card" href="<?= e(url((string) $r['href'])) ?>">
            <div class="forum-cat-card-top">
              <span class="forum-cat-num"><?= e((string) ($r['n'] ?? '')) ?></span>
              <div class="forum-cat-card-name"><?= e((string) ($r['name'] ?? '')) ?></div>
            </div>
            <p><?= e((string) ($r['description'] ?? '')) ?></p>
            <div class="forum-cat-card-foot">
              <span><?= e(format_int((int) ($r['topic_count'] ?? 0))) ?> discussions</span>
              <span class="forum-cat-card-when"><?= e((string) (($r['last_activity'] ?? '') ?: '—')) ?></span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="forum-split">
      <div>
        <form class="forum-search" method="get" action="<?= e(url('/forum')) ?>" role="search">
          <input type="search" name="q" value="<?= e($q) ?>" placeholder="Chercher dans le forum : tarifs, cession de droits, imprimeur…" aria-label="Rechercher dans le forum">
          <button type="submit" class="btn-orange">Chercher</button>
        </form>

        <div class="forum-tabs">
          <?php foreach ($filterTabs as $key => $label): ?>
            <a class="chip<?= $filter === $key && $q === '' ? ' is-on' : '' ?>" href="<?= e(url($forumListUrl($key))) ?>"><?= e($label) ?></a>
          <?php endforeach; ?>
          <div class="forum-section-spacer"></div>
          <span class="forum-muted"><?= e($q !== '' ? (format_int($total) . ' résultat' . ($total > 1 ? 's' : '')) : ($sortLabels[$filter] ?? '')) ?></span>
        </div>

        <?php if ($topics === []): ?>
          <div class="search-empty forum-empty">
            <strong><?= $q !== '' ? 'Aucune discussion pour cette recherche.' : 'Aucune aucune discussion pour le moment.' ?></strong>
            <span><?php if ($logged): ?>Soyez le premier à <a href="<?= e(url('/forum/nouveau')) ?>">ouvrir une discussion</a>.<?php else: ?><a href="<?= e(url('/connexion')) ?>">Connectez-vous</a> pour lancer la première discussion.<?php endif; ?></span>
          </div>
        <?php else: ?>
          <div class="forum-list">
            <?php foreach ($topics as $s): ?>
              <a class="forum-row forum-row-rich" href="<?= e(url((string) $s['href'])) ?>">
                <?= avatar_html($s['author'] ?? [], 36, 'forum-avatar') ?>
                <div class="forum-row-main">
                  <div class="forum-row-line">
                    <span class="forum-cat forum-cat-<?= e((string) ($s['tone'] ?? 'navy')) ?>"><?= e((string) ($s['category_short'] ?? '')) ?></span>
                    <span class="forum-row-title"><?= e((string) ($s['title'] ?? '')) ?></span>
                    <?php if (!empty($s['badge'])): ?>
                      <span class="forum-badge forum-badge-<?= e(slugify((string) $s['badge'])) ?>"><?= e((string) $s['badge']) ?></span>
                    <?php endif; ?>
                  </div>
                  <div class="forum-row-sub">
                    <?= e((string) ($s['author']['name'] ?? '')) ?>
                    · <?= e((string) ($s['author']['role'] ?? '')) ?>
                    · <?= e((string) ($s['when'] ?? '')) ?>
                    · dernière réponse de <?= e((string) ($s['last_by'] ?? '')) ?>, <?= e((string) ($s['last_when'] ?? '')) ?>
                  </div>
                </div>
                <span class="forum-row-num"><?= (int) ($s['reply_count'] ?? 0) ?> rép.</span>
                <span class="forum-row-num is-muted"><?= e((string) ($s['views_label'] ?? '0')) ?> vues</span>
              </a>
            <?php endforeach; ?>
          </div>

          <?php if ($pages > 1): ?>
            <nav class="forum-pager" aria-label="Pagination du forum">
              <?php if ($page > 1): ?>
                <a href="<?= e(url($forumListUrl($filter, $page - 1, $q))) ?>">‹</a>
              <?php endif; ?>
              <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
                <a class="<?= $i === $page ? 'is-on' : '' ?>" href="<?= e(url($forumListUrl($filter, $i, $q))) ?>"><?= $i ?></a>
              <?php endfor; ?>
              <?php if ($page < $pages): ?>
                <a href="<?= e(url($forumListUrl($filter, $page + 1, $q))) ?>">›</a>
              <?php endif; ?>
            </nav>
          <?php endif; ?>
        <?php endif; ?>
      </div>

      <aside class="forum-aside">
        <div class="forum-panel">
          <div class="forum-panel-title">Questions sans réponse</div>
          <?php if ($unanswered === []): ?>
            <p class="forum-muted">Toutes les questions ont trouvé réponse pour l'instant.</p>
          <?php else: ?>
            <div class="forum-aside-list">
              <?php foreach ($unanswered as $qItem): ?>
                <a href="<?= e(url((string) $qItem['href'])) ?>">
                  <div class="forum-aside-link"><?= e((string) ($qItem['title'] ?? '')) ?></div>
                  <div class="forum-aside-meta"><?= e((string) (($qItem['category_short'] ?? '') . ' · ' . ($qItem['when'] ?? ''))) ?></div>
                </a>
              <?php endforeach; ?>
            </div>
            <?php if ((int) $stats['unanswered'] > 0): ?>
              <a class="forum-aside-more" href="<?= e(url($forumListUrl('unanswered'))) ?>">Voir les <?= e(format_int((int) $stats['unanswered'])) ?> questions →</a>
            <?php endif; ?>
          <?php endif; ?>
        </div>

        <?php if ($contributors !== []): ?>
          <div class="forum-panel">
            <div class="forum-panel-title">Ils répondent le plus</div>
            <div class="forum-contrib">
              <?php foreach ($contributors as $c): ?>
                <div class="forum-contrib-row">
                  <?= avatar_html($c, 38, 'forum-avatar') ?>
                  <div class="forum-contrib-copy">
                    <div class="forum-contrib-name">
                      <?= e((string) ($c['name'] ?? '')) ?>
                      <?php if (!empty($c['verified'])): ?><span class="forum-verif">vérifié</span><?php endif; ?>
                    </div>
                    <div class="forum-aside-meta"><?= e((string) ($c['meta'] ?? '')) ?></div>
                  </div>
                  <span class="forum-contrib-n"><?= e(format_int((int) ($c['post_count'] ?? 0))) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <div class="forum-panel forum-panel-dark">
          <div class="forum-panel-heading">Une question précise ?</div>
          <p>Le forum sert à comprendre. Pour faire faire, publiez un appel d'offres : c'est gratuit et les devis arrivent en 48 h.</p>
          <a class="btn-orange" href="<?= e(url('/espace/publier')) ?>">Publier un appel d'offres</a>
        </div>

        <div class="forum-panel">
          <div class="forum-panel-title">Règles du forum</div>
          <div class="forum-rules">
            <div><span class="is-no">✕</span>Aucune réponse générée par IA — la charte s'applique au forum.</div>
            <div><span class="is-no">✕</span>Pas de démarchage : les offres passent par les appels d'offres.</div>
            <div><span class="is-yes">✓</span>Chiffres et tarifs bienvenus, l'opacité ne sert personne.</div>
            <div><span class="is-yes">✓</span>On critique une pratique, jamais une personne.</div>
          </div>
        </div>

        <?php if ($tags !== []): ?>
          <div class="forum-panel">
            <div class="forum-panel-title">Étiquettes fréquentes</div>
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
