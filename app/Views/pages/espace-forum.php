<?php
$stats = $forumStats ?? ['mine' => 0, 'followed' => 0, 'posts' => 0];
$topics = $forumTopics ?? [];
$tab = (string) ($forumTab ?? 'suivis');
$pager = $pager ?? ['page' => 1, 'pages' => 1, 'total' => 0];
$page = (int) ($pager['page'] ?? 1);
$pages = (int) ($pager['pages'] ?? 1);
?>
<div class="espace-page">
  <div class="espace-page-head">
    <div>
      <h1 class="espace-page-title">Forum</h1>
      <p class="espace-page-lead">Vos discussions, vos suivis et les e-mails liés au forum.</p>
    </div>
    <div class="dash-hero-actions">
      <a class="btn-orange" href="<?= e(url('/forum/nouveau')) ?>"><?= icon('plus-box', 16) ?> Ouvrir une discussion</a>
      <a class="btn-ghost" href="<?= e(url('/forum')) ?>">Voir le forum</a>
    </div>
  </div>

  <?php if (!empty($saved)): ?>
    <div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div class="flash flash-error"><?= e((string) $error) ?></div>
  <?php endif; ?>

  <div class="dash-forum-stats" style="margin-bottom: 28px;">
    <div class="dash-stat is-static">
      <span class="dash-ico"><?= icon('bell', 18) ?></span>
      <span><strong>Suivies</strong><em><?= (int) $stats['followed'] ?></em></span>
    </div>
    <div class="dash-stat is-static">
      <span class="dash-ico"><?= icon('chat', 18) ?></span>
      <span><strong>Ouvertes</strong><em><?= (int) $stats['mine'] ?></em></span>
    </div>
    <div class="dash-stat is-static">
      <span class="dash-ico"><?= icon('megaphone', 18) ?></span>
      <span><strong>Messages</strong><em><?= (int) $stats['posts'] ?></em></span>
    </div>
  </div>

  <form method="post" action="<?= e(url('/espace/forum')) ?>" class="param-form espace-panel">
    <?= csrf_field() ?>
    <input type="hidden" name="back" value="/espace/forum">
    <div class="espace-panel-head">
      <h2 class="espace-section-title">Notifications du forum</h2>
      <p class="espace-section-lead">Les messages privés et les alertes marketplace se règlent dans Paramètres.</p>
    </div>
    <label class="check-row">
      <input type="checkbox" name="notify_forum_followed" value="1"<?= !empty($notifyForumFollowed) ? ' checked' : '' ?>>
      M’écrire quand une discussion que je suis reçoit une nouvelle réponse
    </label>
    <label class="check-row">
      <input type="checkbox" name="notify_forum_mine" value="1"<?= !empty($notifyForumMine) ? ' checked' : '' ?>>
      M’écrire quand quelqu’un répond à une discussion que j’ai ouverte
    </label>
    <div class="auth-actions">
      <button type="submit" class="btn-navy">Enregistrer</button>
    </div>
  </form>

  <h2 class="espace-section-title">Vos discussions</h2>
  <div class="forum-tabs" style="margin-bottom: 16px;">
    <a class="chip<?= $tab === 'suivis' ? ' is-on' : '' ?>" href="<?= e(url('/espace/forum?onglet=suivis')) ?>">Discussions suivies</a>
    <a class="chip<?= $tab === 'mine' ? ' is-on' : '' ?>" href="<?= e(url('/espace/forum?onglet=mine')) ?>">Mes discussions</a>
  </div>

  <?php if ($topics === []): ?>
    <div class="search-empty">
      <strong><?= $tab === 'mine' ? 'Aucune discussion ouverte.' : 'Aucune discussion suivie.' ?></strong>
      <span><?php if ($tab === 'mine'): ?>
        <a href="<?= e(url('/forum/nouveau')) ?>">Ouvrir une discussion</a> sur le forum.
      <?php else: ?>
        Suivez un fil depuis une discussion pour le retrouver ici.
      <?php endif; ?></span>
    </div>
  <?php else: ?>
    <div class="dash-forum-list is-page">
      <?php foreach ($topics as $t): ?>
        <div class="dash-forum-item">
          <a href="<?= e(url((string) ($t['href'] ?? '/forum'))) ?>">
            <strong><?= e((string) ($t['title'] ?? '')) ?><?php if ((int) ($t['unread_replies'] ?? 0) > 0): ?> <span class="badge-orange"><?= (int) $t['unread_replies'] ?></span><?php endif; ?></strong>
            <em><?= e((string) (($t['category_name'] ?? '') . ' · ' . format_int((int) ($t['reply_count'] ?? 0)) . ' rép. · ' . ($t['last_when'] ?? ''))) ?></em>
          </a>
          <?php if ($tab === 'suivis'): ?>
            <form method="post" action="<?= e(url('/espace/forum/unfollow/' . (int) ($t['id'] ?? 0))) ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="back" value="/espace/forum?onglet=suivis">
              <button type="submit" class="btn-ghost">Ne plus suivre</button>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($pages > 1): ?>
      <nav class="forum-pager" aria-label="Pagination">
        <?php
          $base = '/espace/forum?onglet=' . rawurlencode($tab) . '&page=';
        ?>
        <?php if ($page > 1): ?><a href="<?= e(url($base . ($page - 1))) ?>">‹</a><?php endif; ?>
        <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
          <a class="<?= $i === $page ? 'is-on' : '' ?>" href="<?= e(url($base . $i)) ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $pages): ?><a href="<?= e(url($base . ($page + 1))) ?>">›</a><?php endif; ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</div>
