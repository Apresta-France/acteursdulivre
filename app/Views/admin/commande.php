<?php

use Adl\Models\User;

$order = $order ?? [];
$buyer = is_array($buyer ?? null) ? $buyer : null;
$seller = is_array($seller ?? null) ? $seller : null;
$invoice = is_array($invoice ?? null) ? $invoice : null;
$review = is_array($review ?? null) ? $review : null;
$thread = is_array($thread ?? null) ? $thread : null;
$messages = $messages ?? [];
$files = $files ?? [];
$statuses = $statuses ?? [];
$milestones = $order['milestones'] ?? [];
$options = $order['options'] ?? [];
$orderId = (int) ($order['id'] ?? 0);
$status = (string) ($order['status'] ?? 'pending');
$fmt = static function (?string $dt, string $empty = '—'): string {
    if ($dt === null || $dt === '') {
        return $empty;
    }
    $ts = strtotime($dt);
    return $ts === false ? $dt : date('d/m/Y à H:i', $ts);
};
?>
<div class="admin-page">
  <p class="admin-back"><a href="<?= e(url('/admin/finances')) ?>">← Commandes &amp; finances</a></p>
  <div class="admin-order-hero">
    <div>
      <h1><?= e((string) ($order['num'] ?? 'Commande')) ?></h1>
      <p class="admin-lead" style="margin: 4px 0 0;"><?= e((string) ($order['title'] ?? '')) ?> · <?= e((string) ($order['parties'] ?? '')) ?></p>
    </div>
    <span class="admin-pill tone-<?= e((string) ($order['status_tone'] ?? 'navy')) ?>"><?= e((string) ($order['status_label'] ?? $status)) ?></span>
  </div>

  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <div class="admin-user-grid">
    <div>
      <div class="admin-user-card">
        <h2>Dossier</h2>
        <dl class="admin-user-meta">
          <div><dt>Montant</dt><dd><?= e((string) ($order['amount_label'] ?? '—')) ?></dd></div>
          <?php if ((int) ($order['deposit_amount'] ?? 0) > 0): ?>
            <div><dt><?= e((string) ($order['deposit_title'] ?? 'Acompte')) ?></dt><dd><?= e((string) ($order['deposit_label'] ?? '—')) ?></dd></div>
          <?php endif; ?>
          <div><dt>Commission</dt><dd><?= e((string) ($order['commission_label'] ?: '—')) ?></dd></div>
          <?php if (!empty($order['package_name'])): ?>
            <div><dt>Offre</dt><dd><?= e((string) $order['package_name']) ?></dd></div>
          <?php endif; ?>
          <div><dt>Ouverte</dt><dd><?= e($fmt($order['created_at'] ?? null)) ?></dd></div>
          <?php if (!empty($order['accepted_at'])): ?>
            <div><dt>Acceptée</dt><dd><?= e($fmt((string) $order['accepted_at'])) ?></dd></div>
          <?php endif; ?>
          <?php if (!empty($order['delivered_at'])): ?>
            <div><dt>Livrée</dt><dd><?= e($fmt((string) $order['delivered_at'])) ?></dd></div>
          <?php endif; ?>
          <?php if (!empty($order['confirmed_at'])): ?>
            <div><dt>Validée</dt><dd><?= e($fmt((string) $order['confirmed_at'])) ?></dd></div>
          <?php endif; ?>
        </dl>

        <?php if ($buyer || $seller): ?>
          <ul class="admin-thread-people" style="margin-top: 16px;">
            <?php if ($buyer): ?>
              <li>
                <?= avatar_html($buyer, 28) ?>
                <span>
                  <a href="<?= e(url('/admin/utilisateurs/' . (int) $buyer['id'])) ?>"><?= e(User::displayName($buyer)) ?></a>
                  <em>Client · <?= e((string) ($buyer['email'] ?? '')) ?></em>
                </span>
              </li>
            <?php endif; ?>
            <?php if ($seller): ?>
              <li>
                <?= avatar_html($seller, 28) ?>
                <span>
                  <a href="<?= e(url('/admin/utilisateurs/' . (int) $seller['id'])) ?>"><?= e(User::displayName($seller)) ?></a>
                  <em>Prestataire · <?= e((string) ($seller['email'] ?? '')) ?></em>
                </span>
              </li>
            <?php endif; ?>
          </ul>
        <?php endif; ?>

        <?php if (trim((string) ($order['brief'] ?? '')) !== ''): ?>
          <h3 class="admin-h3">Brief</h3>
          <p class="admin-brief"><?= nl2br(e((string) $order['brief'])) ?></p>
        <?php endif; ?>

        <?php if ($options !== []): ?>
          <h3 class="admin-h3">Options</h3>
          <ul class="admin-order-options">
            <?php foreach ($options as $option): ?>
              <li><?= e((string) ($option['name'] ?? '')) ?> · <?= e((string) ($option['price_label'] ?? '')) ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <?php if (!empty($order['dispute_reason'])): ?>
          <p class="admin-muted" style="margin-top: 16px;"><strong>Motif du litige : </strong><?= nl2br(e((string) $order['dispute_reason'])) ?></p>
        <?php endif; ?>
        <?php if (!empty($order['dispute_admin_note'])): ?>
          <p class="admin-muted"><strong>Note interne : </strong><?= nl2br(e((string) $order['dispute_admin_note'])) ?></p>
        <?php endif; ?>

        <?php if ($invoice): ?>
          <h3 class="admin-h3">Facture de commission</h3>
          <p class="admin-muted"><?= e((string) ($invoice['number'] ?? '')) ?> · <?= e((string) ($invoice['amount_due_label'] ?? $invoice['amount_label'] ?? '')) ?> · <?= e((string) ($invoice['status_label'] ?? '')) ?></p>
        <?php endif; ?>

        <?php if ($review): ?>
          <h3 class="admin-h3">Avis</h3>
          <p class="admin-muted"><?= e((string) $review['note']) ?> / 5 — <?= e((string) $review['cible']) ?><?= $review['txt'] !== '' ? ' · ' . e((string) $review['txt']) : '' ?></p>
        <?php endif; ?>

        <div class="admin-actions">
          <a class="admin-ghost" href="<?= e(url('/espace/suivi/' . $orderId)) ?>">Voir le suivi</a>
          <?php if ($thread && !empty($thread['id'])): ?>
            <a class="admin-ghost" href="<?= e(url('/admin/conversations/' . (int) $thread['id'])) ?>">Ouvrir la conversation</a>
          <?php endif; ?>
        </div>
      </div>

      <form class="admin-user-card" method="post" action="<?= e(url('/admin/finances/' . $orderId)) ?>" style="margin-top: 18px;">
        <?= csrf_field() ?>
        <h2>Statut</h2>
        <p class="admin-user-help">Annuler clôture les jalons ouverts et les factures de commission non réglées. Les autres changements sont un ajustement administratif.</p>
        <label class="field" for="order-status">Statut de la commande</label>
        <select class="input" id="order-status" name="status">
          <?php foreach ($statuses as $code => $label): ?>
            <option value="<?= e((string) $code) ?>"<?= $status === $code ? ' selected' : '' ?>><?= e((string) $label) ?></option>
          <?php endforeach; ?>
        </select>
        <label class="field" for="order-note" style="margin-top: 16px;">Note interne</label>
        <textarea class="textarea" id="order-note" name="note" rows="3" placeholder="Décision, médiation…"><?= e((string) ($order['dispute_admin_note'] ?? '')) ?></textarea>
        <div class="admin-actions">
          <button class="btn-navy" type="submit">Enregistrer le statut</button>
        </div>
      </form>

      <div class="admin-user-card admin-user-danger">
        <h2>Supprimer la commande</h2>
        <p class="admin-user-help">Efface le dossier, les jalons, les échanges, les fichiers déposés, l’avis et la facture de commission liés. Irréversible.</p>
        <form method="post" action="<?= e(url('/admin/finances/' . $orderId . '/supprimer')) ?>">
          <?= csrf_field() ?>
          <button class="admin-ghost" type="submit" onclick="return confirm('Supprimer définitivement <?= e((string) ($order['num'] ?? 'cette commande')) ?> ? Les échanges, jalons, avis et factures liés seront effacés.');">Supprimer la commande</button>
        </form>
      </div>
    </div>

    <div>
      <div class="admin-user-card">
        <h2>Jalons</h2>
        <?php if ($milestones === []): ?>
          <p class="admin-muted">Aucun jalon.</p>
        <?php else: ?>
          <ol class="admin-jalons">
            <?php foreach ($milestones as $step): ?>
              <li class="<?= !empty($step['is_skipped']) ? 'is-skip' : (!empty($step['is_current']) ? 'is-current' : '') ?>">
                <span>
                  <?= e((string) ($step['title'] ?? '')) ?>
                  <em><?= e((string) ($step['actor_label'] ?? '')) ?><?= !empty($step['amount_label']) ? ' · ' . e((string) $step['amount_label']) : '' ?></em>
                </span>
                <span class="admin-pill tone-<?= !empty($step['is_done']) ? 'green' : (!empty($step['is_current']) ? 'orange' : 'grey') ?>"><?= e((string) ($step['status_label'] ?? '')) ?></span>
              </li>
            <?php endforeach; ?>
          </ol>
        <?php endif; ?>
      </div>

      <div class="admin-user-card" style="margin-top: 18px;">
        <h2>Échanges avec le client</h2>
        <?php if ($messages === []): ?>
          <p class="admin-muted">Aucun message dans cette commande.</p>
        <?php else: ?>
          <div class="admin-thread" style="border: 0; border-radius: 0;">
            <div class="inbox-messages" style="padding: 0;">
              <?php foreach ($messages as $msg): ?>
                <article class="msg"<?= !empty($msg['id']) ? ' data-msg-id="' . (int) $msg['id'] . '"' : '' ?>>
                  <div class="msg-meta"><?= e((string) ($msg['who'] ?? '')) ?> · <time datetime="<?= e((string) ($msg['created_iso'] ?? '')) ?>"><?= e((string) ($msg['when'] ?? '')) ?></time></div>
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
        <?php endif; ?>
      </div>

      <?php if ($files !== []): ?>
        <div class="admin-user-card" style="margin-top: 18px;">
          <h2>Fichiers déposés</h2>
          <ul class="admin-order-files">
            <?php foreach ($files as $file): ?>
              <li>
                <?php if (!empty($file['is_withdrawn'])): ?>
                  <span><?= e((string) ($file['file_name'] ?? 'Fichier')) ?> <em>retiré</em></span>
                <?php else: ?>
                  <a href="<?= e(url((string) ($file['download_href'] ?? $file['view_href'] ?? '#'))) ?>"><?= e((string) ($file['file_name'] ?? 'Fichier')) ?></a>
                <?php endif; ?>
                <em><?= e((string) ($file['who'] ?? '')) ?> · <?= e((string) ($file['when'] ?? '')) ?><?= !empty($file['size_label']) ? ' · ' . e((string) $file['size_label']) : '' ?></em>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
