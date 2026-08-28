<?php
$items = $items ?? [];
?>
<div class="admin-page">
  <h1>Appels d’offres</h1>
  <p class="admin-lead"><?= count($items) === 0 ? 'Aucun appel d’offres.' : format_int(count($items)) . ' mission' . (count($items) > 1 ? 's' : '') ?></p>
  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <div class="chip-row" style="margin-bottom: 18px;">
    <?php foreach ($filters ?? [] as $f): ?>
      <a class="chip<?= !empty($f['on']) ? ' is-on' : '' ?>" href="<?= e(url($f['href'])) ?>"><?= e($f['label']) ?></a>
    <?php endforeach; ?>
  </div>

  <div class="r-scroll">
    <table class="table">
      <thead><tr><th>Mission</th><th>Par</th><th>Budget</th><th>Candidatures</th><th>Statut</th><th></th></tr></thead>
      <tbody>
        <?php if ($items === []): ?>
          <tr><td colspan="6" class="admin-muted">Aucune mission pour ce filtre.</td></tr>
        <?php endif; ?>
        <?php foreach ($items as $m): ?>
          <tr>
            <td>
              <a href="<?= e(url($m['href'])) ?>"><?= e($m['title']) ?></a>
              <div class="admin-sub"><?= e((string) ($m['category_name'] ?? '')) ?> · <?= e($m['when']) ?></div>
            </td>
            <td><?= e($m['by']) ?></td>
            <td><?= e($m['budget']) ?></td>
            <td><?= (int) $m['applicants'] ?></td>
            <td><?= e($m['status_label']) ?></td>
            <td>
              <?php if (($m['status'] ?? '') === 'open'): ?>
                <form class="admin-actions" method="post" action="<?= e(url('/admin/moderation/mission/' . (int) $m['id'])) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="back" value="/admin/missions">
                  <input type="hidden" name="status" value="closed">
                  <button class="admin-ghost" type="submit">Clôturer</button>
                </form>
              <?php elseif (($m['status'] ?? '') === 'closed' || ($m['status'] ?? '') === 'draft'): ?>
                <form class="admin-actions" method="post" action="<?= e(url('/admin/moderation/mission/' . (int) $m['id'])) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="back" value="/admin/missions">
                  <input type="hidden" name="status" value="open">
                  <button class="btn-navy" type="submit">Publier</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
