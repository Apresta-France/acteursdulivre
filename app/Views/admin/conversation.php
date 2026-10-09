<?php
$thread = $thread ?? [];
$messages = $messages ?? [];
$participants = $thread['participants'] ?? [];
$context = $thread['context'] ?? [];
$reports = $thread['reports'] ?? [];
$mails = $mails ?? [];
$messageCount = count($messages);
$fileCount = 0;
$lastAt = '';
foreach ($messages as $message) {
    if (!empty($message['has_file'])) {
        $fileCount++;
    }
    $lastAt = (string) ($message['created_at'] ?? $lastAt);
}
$startedAt = (string) ($thread['created_at'] ?? '');
$fmt = static function (?string $dt): string {
    if ($dt === null || $dt === '') {
        return '—';
    }
    $ts = strtotime($dt);
    return $ts === false ? $dt : date('d/m/Y à H:i', $ts);
};
?>
<div class="admin-page">
  <p class="admin-back"><a href="<?= e(url('/admin/echanges')) ?>">← Échanges</a></p>
  <h1><?= e((string) ($thread['subject'] ?? 'Conversation')) ?></h1>
  <p class="admin-lead">Lecture intégrale de la discussion. Les pièces jointes restent privées : elles ne sont servies qu’ici.</p>
  <p class="admin-echanges-meta">
    <?= e(format_int($messageCount)) ?> message<?= $messageCount > 1 ? 's' : '' ?>
    · ouverte le <?= e($fmt($startedAt)) ?>
    · dernier message le <?= e($fmt($lastAt !== '' ? $lastAt : $startedAt)) ?>
    <?php if ($fileCount > 0): ?> · <?= e(format_int($fileCount)) ?> pièce<?= $fileCount > 1 ? 's' : '' ?> jointe<?= $fileCount > 1 ? 's' : '' ?><?php endif; ?>
    · <?= e(format_int(count($mails))) ?> e-mail<?= count($mails) > 1 ? 's' : '' ?>
  </p>

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
              <em><?= e((string) ($p['role_label'] ?? '')) ?><?= !empty($p['role_label']) && !empty($p['email']) ? ' · ' : '' ?><?= e((string) $p['email']) ?></em>
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

  <div class="admin-user-card" style="margin-bottom: 22px;">
    <h2>E-mails envoyés</h2>
    <p class="admin-user-help">Avis de nouveau message, relance sans réponse, et les e-mails du suivi lorsque la discussion est liée à une commande.</p>
    <?php if ($mails === []): ?>
      <p class="admin-muted">Aucun e-mail enregistré pour cette discussion.</p>
    <?php else: ?>
      <ul class="admin-mail-list">
        <?php foreach ($mails as $mail): ?>
          <li>
            <a class="admin-mail-row" href="<?= e(url((string) ($mail['href'] ?? ''))) ?>">
              <time datetime="<?= e(datetime_iso((string) ($mail['created_at'] ?? ''))) ?>"><?= e($fmt((string) ($mail['created_at'] ?? ''))) ?></time>
              <span>
                <span class="admin-mail-who"><?= e((string) ($mail['recipient'] ?? '')) ?></span>
                <?php if (!empty($mail['template'])): ?><span class="admin-mail-kind"><?= e((string) $mail['template']) ?></span><?php endif; ?>
              </span>
              <span>
                <span class="admin-mail-subject"><?= e((string) ($mail['subject'] ?? '')) ?></span>
                <?php if (!empty($mail['error'])): ?><span class="admin-mail-kind"><?= e((string) $mail['error']) ?></span><?php endif; ?>
              </span>
              <span class="admin-pill" style="<?= e(\Adl\Data\AdminCatalog::pill((string) ($mail['status_tone'] ?? 'grey'))) ?>"><?= e((string) ($mail['status_label'] ?? '')) ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

  <div class="admin-thread is-full">
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
