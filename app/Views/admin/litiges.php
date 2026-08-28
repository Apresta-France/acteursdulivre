<?php
$litiges = $litiges ?? [];
?>
<div class="admin-page">
  <h1>Litiges</h1>
  <p class="admin-lead">Commandes passées en médiation. Vous pouvez rétablir le suivi ou annuler la commande.</p>
  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <?php if ($litiges === []): ?>
    <p class="admin-muted">Aucun litige ouvert pour le moment. Une commande peut être passée en litige depuis Commandes &amp; finances.</p>
  <?php endif; ?>

  <div class="admin-stack">
    <?php foreach ($litiges as $l): ?>
      <article class="admin-card">
        <div class="admin-dossier-who">
          <div>
            <strong><?= e($l['num'] ?: 'Commande') ?> — <?= e($l['title']) ?></strong>
            <span><?= e($l['parties']) ?></span>
            <em><?= e($l['amount_label']) ?> · <?= e($l['when']) ?></em>
          </div>
        </div>
        <div class="admin-actions">
          <form method="post" action="<?= e(url('/admin/litiges/' . (int) $l['id'])) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="status" value="in_progress">
            <button class="btn-navy" type="submit">Reprendre le suivi</button>
          </form>
          <form method="post" action="<?= e(url('/admin/litiges/' . (int) $l['id'])) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="status" value="cancelled">
            <button class="admin-ghost" type="submit" onclick="return confirm('Annuler cette commande ?');">Annuler la commande</button>
          </form>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</div>
