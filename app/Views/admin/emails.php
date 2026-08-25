<div class="admin-page">
<h1>Modèles d'e-mails</h1>
<?php if (!empty($saved)): ?><div class="flash flash-ok">Modèle enregistré.</div><?php endif; ?>
<table class="table">
  <thead><tr><th>Nom</th><th>Slug</th><th>Sujet</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($templates ?? [] as $t): ?>
      <tr>
        <td><?= e($t['name']) ?></td>
        <td><?= e($t['slug']) ?></td>
        <td><?= e($t['subject']) ?></td>
        <td><a href="<?= e(url('/admin/emails/' . $t['id'])) ?>">Modifier</a></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
