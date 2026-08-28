<?php
$items = $items ?? [];
?>
<div class="admin-page">
  <h1>Prestations</h1>
  <p class="admin-lead"><?= count($items) === 0 ? 'Aucune prestation.' : format_int(count($items)) . ' prestation' . (count($items) > 1 ? 's' : '') ?></p>
  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <div class="chip-row" style="margin-bottom: 18px;">
    <?php foreach ($filters ?? [] as $f): ?>
      <a class="chip<?= !empty($f['on']) ? ' is-on' : '' ?>" href="<?= e(url($f['href'])) ?>"><?= e($f['label']) ?></a>
    <?php endforeach; ?>
  </div>

  <div class="r-scroll">
    <table class="table">
      <thead><tr><th>Prestation</th><th>Prestataire</th><th>Prix</th><th>Statut</th><th></th></tr></thead>
      <tbody>
        <?php if ($items === []): ?>
          <tr><td colspan="5" class="admin-muted">Aucune prestation pour ce filtre.</td></tr>
        <?php endif; ?>
        <?php foreach ($items as $s): ?>
          <tr>
            <td>
              <a href="<?= e(url($s['href'])) ?>"><?= e($s['title']) ?></a>
              <div class="admin-sub"><?= e(trim(($s['cat'] ?? '') . ($s['specialty'] ? ' · ' . $s['specialty'] : ''))) ?></div>
            </td>
            <td><?= e($s['by']) ?></td>
            <td><?= e($s['price']) ?></td>
            <td><?= e($s['status_label']) ?></td>
            <td>
              <form class="admin-actions" method="post" action="<?= e(url('/admin/moderation/prestation/' . (int) $s['id'])) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="back" value="/admin/prestations">
                <?php if (($s['status'] ?? '') === 'published'): ?>
                  <input type="hidden" name="status" value="draft">
                  <button class="admin-ghost" type="submit">Retirer</button>
                <?php else: ?>
                  <input type="hidden" name="status" value="published">
                  <button class="btn-navy" type="submit">Publier</button>
                <?php endif; ?>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
