<?php
$article = $article ?? [];
$id = (int) ($article['id'] ?? 0);
$action = $id ? '/admin/journal/' . $id : '/admin/journal/nouveau';
?>
<div class="admin-page">
  <p class="admin-back"><a href="<?= e(url('/admin/journal')) ?>">← Tous les articles</a></p>
  <h1><?= e($id ? (string) $article['title'] : 'Nouvel article') ?></h1>
  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <form class="admin-form" method="post" action="<?= e(url($action)) ?>">
    <?= csrf_field() ?>
    <div>
      <label class="field" for="title">Titre</label>
      <input class="input" id="title" name="title" required value="<?= e((string) ($article['title'] ?? '')) ?>">
    </div>
    <div>
      <label class="field" for="slug">Slug</label>
      <input class="input" id="slug" name="slug" value="<?= e((string) ($article['slug'] ?? '')) ?>" placeholder="généré automatiquement si vide">
    </div>
    <div>
      <label class="field" for="category">Rubrique</label>
      <input class="input" id="category" name="category" value="<?= e((string) ($article['category'] ?? 'Journal')) ?>">
    </div>
    <div>
      <label class="field" for="excerpt">Chapô</label>
      <textarea class="textarea" id="excerpt" name="excerpt" rows="3"><?= e((string) ($article['excerpt'] ?? '')) ?></textarea>
    </div>
    <div>
      <label class="field" for="body">Corps (HTML possible)</label>
      <textarea class="textarea" id="body" name="body" rows="16"><?= e((string) ($article['body'] ?? '')) ?></textarea>
    </div>
    <label class="admin-tax-check">
      <input type="checkbox" name="published" value="1"<?= !empty($article['published']) ? ' checked' : '' ?>>
      Publier sur le journal
    </label>
    <div class="admin-actions">
      <button class="btn-orange" type="submit">Enregistrer</button>
      <?php if ($id): ?>
        <a class="admin-ghost" href="<?= e(url('/journal/' . ($article['slug'] ?? ''))) ?>">Aperçu public</a>
      <?php endif; ?>
    </div>
  </form>

  <?php if ($id): ?>
    <form method="post" action="<?= e(url('/admin/journal/' . $id . '/supprimer')) ?>" style="margin-top: 22px;">
      <?= csrf_field() ?>
      <button class="admin-ghost" type="submit" onclick="return confirm('Supprimer cet article ?');">Supprimer l’article</button>
    </form>
  <?php endif; ?>
</div>
