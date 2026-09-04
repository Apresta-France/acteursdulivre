<?php
$article = $article ?? [];
$id = (int) ($article['id'] ?? 0);
$status = (string) ($article['submission_status'] ?? 'draft');
$canEdit = !empty($article['can_edit']);
$action = $id ? '/espace/tribune/' . $id : '/espace/tribune/nouvelle';
?>
<div class="espace-page tribune-page">
  <div class="espace-page-head">
    <div>
      <h1><?= $id ? e((string) $article['title']) : 'Nouvelle tribune' ?></h1>
      <p>
        <span class="status-pill status-<?= e($status) ?>"><?= e((string) ($article['status_label'] ?? 'Brouillon')) ?></span>
        <?php if ($status === 'pending'): ?> · soumise le <?= e(date('d/m/Y à H:i', strtotime((string) $article['submitted_at']))) ?><?php endif; ?>
      </p>
    </div>
    <div class="dash-hero-actions">
      <a class="btn-ghost" href="<?= e(url('/espace/tribune')) ?>">Mes tribunes</a>
      <?php if ($status === 'approved'): ?>
        <a class="btn-orange" href="<?= e(url((string) $article['href'])) ?>">Voir dans le journal</a>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e((string) $saved) ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <?php if ($status === 'pending'): ?>
    <div class="form-notice tribune-status-box">
      <strong>Votre tribune est en cours de modération</strong>
      <p>L’équipe vérifie qu’elle respecte la ligne éditoriale. La date de soumission reste visible ici et la décision vous sera envoyée par e-mail dès qu’elle sera rendue.</p>
    </div>
  <?php elseif ($status === 'rejected'): ?>
    <div class="tribune-rejection">
      <strong>Motif du refus</strong>
      <p><?= nl2br(e((string) ($article['moderation_note'] ?? 'Le texte doit être repris avant une nouvelle soumission.'))) ?></p>
      <span>Vous pouvez modifier votre article puis le soumettre de nouveau.</span>
    </div>
  <?php endif; ?>

  <?php if ($canEdit): ?>
    <form id="tribune-form" class="vitrine-form tribune-form" method="post" action="<?= e(url($action)) ?>" enctype="multipart/form-data" data-tribune-form>
      <?= csrf_field() ?>
      <div class="tribune-edit-grid">
        <div>
          <div class="espace-panel">
            <h2 class="espace-group-title">Votre article</h2>
            <div>
              <label class="field" for="tribune-title">Titre</label>
              <input class="input" id="tribune-title" name="title" required maxlength="255" value="<?= e((string) ($article['title'] ?? '')) ?>" placeholder="Un titre clair et vivant" data-tribune-title>
            </div>
            <div>
              <label class="field" for="tribune-excerpt">Chapô</label>
              <textarea class="textarea" id="tribune-excerpt" name="excerpt" rows="3" maxlength="600" placeholder="En quelques lignes, donnez envie de lire la suite." data-tribune-excerpt><?= e((string) ($article['excerpt'] ?? '')) ?></textarea>
            </div>
            <div>
              <label class="field" for="tribune-body" id="tribune-body-label">Contenu</label>
              <div class="wysiwyg tribune-wysiwyg" data-wysiwyg>
                <div class="wysiwyg-toolbar" hidden>
                  <button type="button" data-wysiwyg-cmd="formatBlock" data-wysiwyg-value="h2" title="Intertitre">Titre 2</button>
                  <button type="button" data-wysiwyg-cmd="formatBlock" data-wysiwyg-value="h3" title="Sous-titre">Titre 3</button>
                  <button type="button" data-wysiwyg-cmd="bold" title="Gras"><strong>G</strong></button>
                  <button type="button" data-wysiwyg-cmd="italic" title="Italique"><em>I</em></button>
                  <button type="button" data-wysiwyg-cmd="formatBlock" data-wysiwyg-value="blockquote" title="Citation">« »</button>
                  <button type="button" data-wysiwyg-cmd="insertUnorderedList" title="Liste à puces">• Liste</button>
                  <button type="button" data-wysiwyg-cmd="insertOrderedList" title="Liste numérotée">1.</button>
                  <button type="button" data-wysiwyg-cmd="createLink" title="Ajouter un lien">Lien</button>
                </div>
                <textarea class="textarea wysiwyg-source" id="tribune-body" name="body" rows="18" required placeholder="Écrivez votre texte ici…"><?= e((string) ($article['body'] ?? '')) ?></textarea>
                <div class="wysiwyg-editor" contenteditable="true" role="textbox" aria-multiline="true" aria-labelledby="tribune-body-label" hidden data-tribune-body></div>
              </div>
              <p class="field-help">Utilisez les intertitres, listes, citations, gras et liens pour structurer votre lecture.</p>
            </div>
          </div>

          <div class="espace-panel">
            <h2 class="espace-group-title">Image de couverture</h2>
            <?php if (!empty($article['has_cover'])): ?>
              <img class="tribune-current-image" src="<?= e((string) $article['img']) ?>" alt="">
              <label class="check-row"><input type="checkbox" name="remove_image" value="1"> Retirer cette image</label>
            <?php endif; ?>
            <input class="input" id="tribune-image" name="image" type="file" accept="image/jpeg,image/png,image/webp" data-tribune-image>
            <p class="field-help">JPG, PNG ou WebP, 5 Mo maximum. Une image horizontale est recommandée.</p>
            <div>
              <label class="field" for="tribune-image-alt">Description de l’image</label>
              <input class="input" id="tribune-image-alt" name="image_alt" maxlength="255" value="<?= e((string) ($article['image_alt'] ?? '')) ?>" placeholder="Décrivez brièvement l’image pour les lecteurs qui ne la voient pas.">
            </div>
          </div>

          <div class="tribune-form-actions">
            <button class="btn-ghost" type="submit" name="action" value="draft">Enregistrer le brouillon</button>
            <button class="btn-orange" type="submit" name="action" value="submit" onclick="return confirm('Soumettre cette tribune à la modération ? Elle ne sera plus modifiable pendant la relecture.');">Soumettre à la modération</button>
          </div>
        </div>

        <aside class="tribune-preview" data-tribune-preview>
          <div class="side-kicker">Aperçu</div>
          <div class="tribune-preview-image<?= empty($article['has_cover']) ? ' is-empty' : '' ?>"<?php if (!empty($article['has_cover'])): ?> style="background-image:url('<?= e((string) $article['img']) ?>')"<?php endif; ?> data-tribune-preview-image></div>
          <span class="tribune-preview-category">Tribune</span>
          <h2 data-tribune-preview-title><?= e((string) ($article['title'] ?: 'Titre de votre article')) ?></h2>
          <p class="tribune-preview-excerpt" data-tribune-preview-excerpt><?= e((string) ($article['excerpt'] ?: 'Le chapô apparaîtra ici.')) ?></p>
          <div class="tribune-preview-body article-body" data-tribune-preview-body><?= rich_html((string) ($article['body'] ?? ''), '<p>Le contenu de votre tribune apparaîtra ici.</p>') ?></div>
        </aside>
      </div>
    </form>
    <?php if ($id): ?>
      <form method="post" action="<?= e(url('/espace/tribune/' . $id . '/supprimer')) ?>" onsubmit="return confirm('Supprimer définitivement cette tribune ?');">
        <?= csrf_field() ?>
        <button class="btn-ghost btn-danger" type="submit">Supprimer cette tribune</button>
      </form>
    <?php endif; ?>
  <?php else: ?>
    <article class="tribune-readonly espace-panel">
      <?php if (!empty($article['has_cover'])): ?><img src="<?= e((string) $article['img']) ?>" alt="<?= e((string) $article['image_alt']) ?>"><?php endif; ?>
      <?php if (!empty($article['excerpt'])): ?><p class="article-lead"><?= e((string) $article['excerpt']) ?></p><?php endif; ?>
      <div class="article-body"><?= rich_html((string) $article['body']) ?></div>
    </article>
  <?php endif; ?>
</div>
