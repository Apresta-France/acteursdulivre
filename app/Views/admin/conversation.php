<?php
$thread = $thread ?? [];
$messages = $messages ?? [];
$participants = $thread['participants'] ?? [];
$context = $thread['context'] ?? [];
$reports = $thread['reports'] ?? [];
?>
<div class="admin-page">
  <p class="admin-back"><a href="<?= e(url('/admin/moderation')) ?>">← Modération</a></p>
  <h1><?= e((string) ($thread['subject'] ?? 'Conversation')) ?></h1>
  <p class="admin-lead">Lecture des échanges signalés. Les pièces jointes restent privées : elles ne sont servies qu’ici.</p>

  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <div class="admin-user-grid" style="margin-bottom: 22px;">
    <div class="admin-user-card">
      <h2>Participants</h2>
      <?php if ($participants === []): ?>
        <p class="admin-muted">Aucun participant.</p>
      <?php endif; ?>
      <ul class="admin-thread-people">
        <?php foreach ($participants as $p): ?>
          <li>
            <?= avatar_html($p, 28) ?>
            <span>
              <a href="<?= e(url((string) $p['href'])) ?>"><?= e((string) $p['name']) ?></a>
              <em><?= e((string) $p['email']) ?></em>
            </span>
          </li>
        <?php endforeach; ?>
      </ul>
      <?php if ($context !== []): ?>
        <div class="admin-thread-context">
          <?php foreach ($context as $link): ?>
            <a class="admin-ghost" href="<?= e(url((string) $link['href'])) ?>"><?= e((string) $link['label']) ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <div class="admin-user-card">
      <h2>Signalements</h2>
      <?php if ($reports === []): ?>
        <p class="admin-muted">Aucun signalement sur cette conversation.</p>
      <?php endif; ?>
      <?php foreach ($reports as $r): ?>
        <article class="admin-thread-report">
          <div>
            <strong><?= e((string) $r['reason_label']) ?></strong>
            <span><?= e((string) $r['who']) ?> · <?= e((string) $r['when']) ?></span>
            <?php if (!empty($r['body'])): ?><em><?= e((string) $r['body']) ?></em><?php endif; ?>
          </div>
          <span class="admin-pill tone-<?= ($r['status'] ?? '') === 'closed' ? 'green' : 'orange' ?>"><?= e((string) $r['status_label']) ?></span>
          <?php if (($r['status'] ?? '') !== 'closed'): ?>
            <form method="post" action="<?= e(url('/admin/signalements/' . (int) $r['id'])) ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="status" value="closed">
              <input type="hidden" name="back" value="<?= e('/admin/conversations/' . (int) ($thread['id'] ?? 0)) ?>">
              <button class="btn-navy" type="submit">Marquer traité</button>
            </form>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="admin-thread">
    <div class="inbox-messages">
      <?php if ($messages === []): ?>
        <p class="admin-muted">Aucun message dans cette conversation.</p>
      <?php endif; ?>
      <?php foreach ($messages as $msg): ?>
        <article class="msg"<?= !empty($msg['id']) ? ' data-msg-id="' . (int) $msg['id'] . '"' : '' ?>>
          <div class="msg-meta"><?= e((string) $msg['who']) ?> · <time datetime="<?= e((string) ($msg['created_iso'] ?? '')) ?>"><?= e((string) $msg['when']) ?></time></div>
          <?php $body = trim((string) ($msg['body'] ?? '')); ?>
          <?php if ($body !== ''): ?>
            <p><?= nl2br(e($body)) ?></p>
          <?php endif; ?>
          <?php if (!empty($msg['has_file'])): ?>
            <a class="msg-file" href="<?= e(url((string) $msg['file_href'])) ?>" title="Télécharger">
              <?= icon('download', 16) ?>
              <?= e((string) $msg['file_label']) ?><?= !empty($msg['file_size']) ? ' · ' . e((string) $msg['file_size']) : '' ?>
            </a>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</div>
