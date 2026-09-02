<?php
$litiges = $litiges ?? [];
?>
<div class="admin-page">
  <h1>Litiges</h1>
  <p class="admin-lead">Commandes en médiation interne. Les jalons sont en pause. Vous pouvez annoter le dossier, rétablir le suivi ou annuler.</p>
  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <?php if ($litiges === []): ?>
    <p class="admin-muted">Aucun litige ouvert pour le moment.</p>
  <?php endif; ?>

  <div class="admin-stack">
    <?php foreach ($litiges as $l): ?>
      <article class="admin-card">
        <div class="admin-dossier-who">
          <div>
            <strong><?= e($l['num'] ?: 'Commande') ?> — <?= e($l['title']) ?></strong>
            <span><?= e($l['parties']) ?></span>
            <em><?= e($l['amount_label']) ?> · ouvert <?= e((string) ($l['dispute_when'] ?: $l['when'])) ?></em>
          </div>
        </div>
        <?php if (!empty($l['dispute_reason'])): ?>
          <p class="admin-muted"><strong>Motif : </strong><?= nl2br(e((string) $l['dispute_reason'])) ?></p>
        <?php endif; ?>
        <?php if (!empty($l['dispute_admin_note'])): ?>
          <p class="admin-muted"><strong>Note interne : </strong><?= nl2br(e((string) $l['dispute_admin_note'])) ?></p>
        <?php endif; ?>
        <div class="admin-actions">
          <a class="btn-ghost" href="<?= e(url('/admin/finances/' . (int) $l['id'])) ?>">Voir le dossier</a>
          <a class="btn-ghost" href="<?= e(url('/espace/suivi/' . (int) $l['id'])) ?>">Voir le suivi</a>
        </div>
        <form method="post" action="<?= e(url('/admin/litiges/' . (int) $l['id'])) ?>" class="jalon-form">
          <?= csrf_field() ?>
          <label class="field" for="note-<?= (int) $l['id'] ?>">Note de médiation (visible dans le dossier)</label>
          <textarea class="textarea" id="note-<?= (int) $l['id'] ?>" name="note" rows="2" placeholder="Proposition ou décision…"><?= e((string) ($l['dispute_admin_note'] ?? '')) ?></textarea>
          <div class="admin-actions">
            <button class="btn-navy" type="submit" name="status" value="in_progress">Reprendre le suivi</button>
            <button class="admin-ghost" type="submit" name="status" value="cancelled" onclick="return confirm('Annuler cette commande ?');">Annuler la commande</button>
          </div>
        </form>
      </article>
    <?php endforeach; ?>
  </div>
</div>
