<?php
$articles = $articles ?? [];
?>
<div class="admin-page">
  <div class="admin-page-head">
    <div>
      <h1>Journal</h1>
      <p class="admin-lead" style="margin-bottom: 0;"><?= e($cmsSubtitle ?? '') ?></p>
    </div>
    <a class="btn-navy" href="<?= e(url('/admin/journal/nouveau')) ?>">Nouvel article</a>
  </div>
  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <div class="r-scroll">
    <table class="table">
      <thead><tr><th>Titre</th><th>Rubrique</th><th>Statut</th><th></th></tr></thead>
      <tbody>
        <?php if ($articles === []): ?>
          <tr><td colspan="4" class="admin-muted">Aucun article.</td></tr>
        <?php endif; ?>
        <?php foreach ($articles as $a): ?>
          <tr>
            <td><a href="<?= e(url('/admin/journal/' . (int) $a['id'])) ?>"><?= e($a['title']) ?></a></td>
            <td><?= e($a['cat']) ?></td>
            <td><?= e($a['status']) ?></td>
            <td class="admin-actions">
              <?php if (!empty($a['published'])): ?>
                <a class="admin-ghost" href="<?= e(url($a['href'])) ?>">Voir</a>
              <?php endif; ?>
              <a class="admin-ghost" href="<?= e(url('/admin/journal/' . (int) $a['id'])) ?>">Modifier</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
