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
  <?php if (!empty($article['author_id'])): ?>
    <div class="form-notice">
      <strong>Tribune proposée par <?= e((string) ($article['author_name'] ?: 'un membre')) ?></strong>
      <p>Statut : <?= e((string) $article['status_label']) ?>. La décision et son motif se gèrent depuis <a href="<?= e(url('/admin/moderation')) ?>">la modération</a>.</p>
    </div>
  <?php endif; ?>

  <form class="admin-form" method="post" action="<?= e(url($action)) ?>" enctype="multipart/form-data">
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
      <input class="input" id="category" name="category" value="<?= e((string) ($article['category'] ?? 'Journal')) ?>"<?= !empty($article['author_id']) ? ' readonly' : '' ?>>
    </div>
    <div>
      <label class="field" for="excerpt">Chapô</label>
      <textarea class="textarea" id="excerpt" name="excerpt" rows="3"><?= e((string) ($article['excerpt'] ?? '')) ?></textarea>
    </div>
    <div>
      <label class="field" for="image">Image de l’article</label>
      <?php if (!empty($article['img'])): ?>
        <p><img src="<?= e((string) $article['img']) ?>" alt="" style="max-width: 320px; border-radius: 10px;"></p>
      <?php endif; ?>
      <input class="input" id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
    </div>
    <div>
      <label class="field" for="image_alt">Texte alternatif de l’image</label>
      <input class="input" id="image_alt" name="image_alt" value="<?= e((string) ($article['image_alt'] ?? '')) ?>">
    </div>
    <div>
      <label class="field" for="body">Corps (HTML : h2, tableaux, listes, liens internes)</label>
      <textarea class="textarea" id="body" name="body" rows="16"><?= e((string) ($article['body'] ?? '')) ?></textarea>
    </div>
    <?php if (empty($article['author_id'])): ?>
      <label class="admin-tax-check">
        <input type="checkbox" name="published" value="1"<?= !empty($article['published']) ? ' checked' : '' ?>>
        Publier sur le journal
      </label>
    <?php elseif (!empty($article['published'])): ?>
      <input type="hidden" name="published" value="1">
    <?php endif; ?>
    <div class="admin-actions">
      <button class="btn-orange" type="submit">Enregistrer</button>
      <?php if ($id): ?>
        <?php if (!empty($article['published'])): ?><a class="admin-ghost" href="<?= e(url('/journal/' . ($article['slug'] ?? ''))) ?>">Aperçu public</a><?php endif; ?>
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
