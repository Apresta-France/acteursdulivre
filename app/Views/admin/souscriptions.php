<?php
$rows = $rows ?? [];
$counts = $counts ?? ['all' => 0, 'open' => 0, 'draft' => 0];
?>
<div class="admin-page">
  <div class="admin-page-head">
    <div>
      <h1>Souscriptions de livres</h1>
      <p class="admin-lead" style="margin-bottom: 0;">Annonces affichées dans la communauté. Une souscription, une prévente ou une vente déjà ouverte ailleurs : on la montre, on ne l’héberge pas.</p>
    </div>
    <a class="btn-navy" href="<?= e(url('/admin/souscriptions/nouvelle')) ?>">Nouvelle annonce</a>
  </div>
  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <div class="r-scroll">
    <table class="table">
      <thead><tr><th>Livre</th><th>Type</th><th>Porteur</th><th>Clôture</th><th>Statut</th><th></th></tr></thead>
      <tbody>
        <?php if ($rows === []): ?>
          <tr><td colspan="6" class="admin-muted">Aucune annonce.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td>
              <a href="<?= e(url('/admin/souscriptions/' . (int) $row['id'])) ?>"><?= e((string) $row['title']) ?></a>
              <?php if (!empty($row['featured'])): ?><span class="admin-pill tone-orange">En avant</span><?php endif; ?>
            </td>
            <td><?= e((string) $row['kind_label']) ?></td>
            <td><?= e((string) ($row['bearer'] !== '' ? $row['bearer'] : '—')) ?></td>
            <td><?= e(admin_date((string) ($row['closes'] ?? ''))) ?></td>
            <td><span class="admin-pill tone-<?= e((string) $row['status_tone']) ?>"><?= e((string) $row['status_label']) ?></span></td>
            <td class="admin-actions">
              <?php if (($row['status'] ?? '') !== 'draft'): ?>
                <a class="admin-ghost" href="<?= e(url('/souscriptions/' . $row['slug'])) ?>">Voir</a>
              <?php endif; ?>
              <a class="admin-ghost" href="<?= e(url('/admin/souscriptions/' . (int) $row['id'])) ?>">Modifier</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($rows !== []): ?>
    <p class="admin-muted"><?= e(format_int((int) $counts['open'])) ?> ouverte<?= (int) $counts['open'] > 1 ? 's' : '' ?> · <?= e(format_int((int) $counts['draft'])) ?> brouillon<?= (int) $counts['draft'] > 1 ? 's' : '' ?></p>
  <?php endif; ?>
</div>
