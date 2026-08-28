<?php
$items = $items ?? [];
$unread = (int) ($unreadCount ?? 0);
?>
<div class="espace-page">
  <div class="espace-page-head">
    <div>
      <h1>Notifications</h1>
      <p><?= $unread > 0 ? $unread . ' alerte' . ($unread > 1 ? 's' : '') . ' non lue' . ($unread > 1 ? 's' : '') : 'Aucune alerte en attente.' ?></p>
    </div>
    <?php if ($unread > 0): ?>
      <form method="post" action="<?= e(url('/espace/notifications/lues')) ?>">
        <?= csrf_field() ?>
        <button type="submit" class="btn-ghost">Tout marquer comme lu</button>
      </form>
    <?php endif; ?>
  </div>

  <?php if (!empty($saved)): ?>
    <div class="flash flash-ok"><?= e((string) $saved) ?></div>
  <?php endif; ?>

  <?php if ($items === []): ?>
    <div class="search-empty">
      <strong>Pas encore d’alerte.</strong>
      <span>Les relances de profil, de mission et les demandes en attente apparaîtront ici.</span>
    </div>
  <?php else: ?>
    <div class="notif-list">
      <?php foreach ($items as $n): ?>
        <a class="notif-item<?= !empty($n['unread']) ? ' is-unread' : '' ?>" href="<?= e(url('/espace/notifications/' . (int) $n['id'])) ?>">
          <span class="notif-dot" aria-hidden="true"></span>
          <span class="notif-body">
            <strong><?= e((string) $n['title']) ?></strong>
            <?php if (!empty($n['body'])): ?>
              <em><?= e((string) $n['body']) ?></em>
            <?php endif; ?>
          </span>
          <span class="notif-when"><?= e((string) ($n['when'] ?? '')) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
