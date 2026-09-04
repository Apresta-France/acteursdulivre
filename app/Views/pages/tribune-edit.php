<?php
$article = $article ?? [];
$id = (int) ($article['id'] ?? 0);
$status = (string) ($article['submission_status'] ?? 'draft');
$canEdit = !empty($article['can_edit']);
$action = $id ? '/espace/tribune/' . $id : '/espace/tribune/nouvelle';
$autosaveAction = $id ? '/espace/tribune/' . $id . '/autosauvegarde' : '/espace/tribune/autosauvegarde';
$previewAction = $id ? '/espace/tribune/' . $id . '/apercu' : '';
?>
<div class="espace-page tribune-page tribune-studio">
  <div class="espace-page-head">
    <div>
      <h1><?= $id ? e((string) $article['title']) : 'Nouvelle tribune' ?></h1>
      <p>
        <span class="status-pill status-<?= e($status) ?>"><?= e((string) ($article['status_label'] ?? 'Brouillon')) ?></span>
        <?php if ($status === 'pending'): ?> · soumise le <?= e(date('d/m/Y à H:i', strtotime((string) $article['submitted_at']))) ?><?php endif; ?>
      </p>
    </div>
    <div class="dash-hero-actions">
      <?php if ($canEdit): ?>
        <button class="btn-navy" type="button" form="tribune-form" data-tribune-preview-open>Aperçu de l’article</button>
      <?php elseif ($id): ?>
        <a class="btn-navy" href="<?= e(url($previewAction)) ?>" target="_blank" rel="noopener">Aperçu de l’article</a>
      <?php endif; ?>
      <a class="btn-ghost" href="<?= e(url('/espace/tribune')) ?>">Mes tribunes</a>
      <?php if ($status === 'approved'): ?><a class="btn-orange" href="<?= e(url((string) $article['href'])) ?>">Voir dans le journal</a><?php endif; ?>
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
    <form
      id="tribune-form"
      class="vitrine-form tribune-form"
      method="post"
      action="<?= e(url($action)) ?>"
      enctype="multipart/form-data"
      data-tribune-form
      data-autosave-url="<?= e(url($autosaveAction)) ?>"
      data-preview-url="<?= e($previewAction !== '' ? url($previewAction) : '') ?>"
    >
      <?= csrf_field() ?>

      <div class="espace-panel tribune-meta-panel">
        <h2 class="espace-group-title">Votre article</h2>
        <div>
          <label class="field" for="tribune-title">Titre</label>
          <input class="input tribune-title-input" id="tribune-title" name="title" required maxlength="255" value="<?= e((string) ($article['title'] ?? '')) ?>" placeholder="Un titre clair et vivant">
        </div>
        <div>
          <label class="field" for="tribune-excerpt">Chapô</label>
          <textarea class="textarea tribune-chapo-input" id="tribune-excerpt" name="excerpt" rows="3" maxlength="600" placeholder="En quelques lignes, donnez envie de lire la suite."><?= e((string) ($article['excerpt'] ?? '')) ?></textarea>
        </div>
      </div>

      <div class="espace-panel tribune-cover-panel">
        <h2 class="espace-group-title">Image de couverture</h2>
        <div class="tribune-cover-layout">
          <div class="tribune-cover-preview<?= empty($article['has_cover']) ? ' is-empty' : '' ?>"<?php if (!empty($article['has_cover'])): ?> style="background-image:url('<?= e((string) $article['img']) ?>')"<?php endif; ?> data-tribune-cover-preview>
            <span>Format horizontal recommandé</span>
          </div>
          <div class="tribune-cover-fields">
            <?php
              $filePickId = 'tribune-image';
              $filePickName = 'image';
              $filePickAccept = 'image/jpeg,image/png,image/webp';
              $filePickButton = 'Sélectionner une image';
              $filePickDrop = true;
              $filePickEmpty = 'ou glissez-déposez votre image ici';
              $filePickHint = 'JPG, PNG ou WebP · 5 Mo maximum';
              $filePickAttrs = 'data-tribune-image data-max-bytes="5242880"';
              require ADL_ROOT . '/app/Views/partials/file-pick.php';
            ?>
            <?php if (!empty($article['has_cover'])): ?>
              <label class="check-row"><input type="checkbox" name="remove_image" value="1" data-tribune-remove-image> Retirer l’image actuelle</label>
            <?php endif; ?>
            <div>
              <label class="field" for="tribune-image-alt">Description de l’image</label>
              <input class="input" id="tribune-image-alt" name="image_alt" maxlength="255" value="<?= e((string) ($article['image_alt'] ?? '')) ?>" placeholder="Décrivez l’image pour les lecteurs qui ne la voient pas.">
            </div>
          </div>
        </div>
      </div>

      <div class="espace-panel tribune-writing-panel">
        <div class="tribune-writing-head">
          <div>
            <h2 class="espace-group-title">Contenu</h2>
            <p>Sélectionnez du texte pour afficher les outils rapides de mise en forme.</p>
          </div>
          <div class="tribune-save-state" data-save-state data-state="<?= $id ? 'saved' : 'idle' ?>" role="status" aria-live="polite">
            <span class="tribune-save-dot" aria-hidden="true"></span>
            <span data-save-label><?= $id ? 'Toutes les modifications sont enregistrées' : 'Commencez par donner un titre à votre article' ?></span>
          </div>
        </div>
        <div>
          <label class="sr-only" for="tribune-body" id="tribune-body-label">Contenu de l’article</label>
          <div class="wysiwyg tribune-wysiwyg" data-wysiwyg data-floating-toolbar>
            <div class="wysiwyg-toolbar tribune-main-toolbar" hidden>
              <button type="button" data-wysiwyg-cmd="formatBlock" data-wysiwyg-value="p" title="Paragraphe">Texte</button>
              <button type="button" data-wysiwyg-cmd="formatBlock" data-wysiwyg-value="h2" title="Intertitre">Titre 2</button>
              <button type="button" data-wysiwyg-cmd="formatBlock" data-wysiwyg-value="h3" title="Sous-titre">Titre 3</button>
              <span class="tribune-toolbar-sep" aria-hidden="true"></span>
              <button type="button" data-wysiwyg-cmd="bold" title="Gras (Ctrl+B)"><strong>G</strong></button>
              <button type="button" data-wysiwyg-cmd="italic" title="Italique (Ctrl+I)"><em>I</em></button>
              <button type="button" data-wysiwyg-cmd="underline" title="Souligné (Ctrl+U)"><u>S</u></button>
              <button type="button" data-wysiwyg-cmd="formatBlock" data-wysiwyg-value="blockquote" title="Citation">« Citation »</button>
              <button type="button" data-wysiwyg-cmd="insertUnorderedList" title="Liste à puces">• Liste</button>
              <button type="button" data-wysiwyg-cmd="insertOrderedList" title="Liste numérotée">1. Liste</button>
              <button type="button" data-wysiwyg-cmd="createLink" title="Ajouter un lien">Lien</button>
            </div>
            <textarea class="textarea wysiwyg-source" id="tribune-body" name="body" rows="24" required placeholder="Écrivez votre texte ici…"><?= e((string) ($article['body'] ?? '')) ?></textarea>
            <div class="wysiwyg-editor" contenteditable="true" role="textbox" aria-multiline="true" aria-labelledby="tribune-body-label" hidden></div>
            <div class="wysiwyg-bubble" data-wysiwyg-bubble hidden role="toolbar" aria-label="Mise en forme de la sélection">
              <button type="button" data-wysiwyg-cmd="bold" title="Gras"><strong>G</strong></button>
              <button type="button" data-wysiwyg-cmd="italic" title="Italique"><em>I</em></button>
              <button type="button" data-wysiwyg-cmd="underline" title="Souligné"><u>S</u></button>
              <button type="button" data-wysiwyg-cmd="formatBlock" data-wysiwyg-value="h2" title="Intertitre">T2</button>
              <button type="button" data-wysiwyg-cmd="formatBlock" data-wysiwyg-value="blockquote" title="Citation">« »</button>
              <button type="button" data-wysiwyg-cmd="createLink" title="Lien">Lien</button>
            </div>
          </div>
          <div class="tribune-editor-help">
            <span><kbd>Ctrl</kbd>/<kbd>⌘</kbd> + <kbd>S</kbd> enregistrer</span>
            <span><kbd>Ctrl</kbd>/<kbd>⌘</kbd> + <kbd>B</kbd> gras</span>
            <span><kbd>Ctrl</kbd>/<kbd>⌘</kbd> + <kbd>I</kbd> italique</span>
            <span>Sauvegarde automatique après quelques secondes</span>
          </div>
        </div>
      </div>

      <div class="tribune-form-actions">
        <button class="btn-ghost" type="submit" name="action" value="draft">Enregistrer maintenant</button>
        <button class="btn-navy" type="button" data-tribune-preview-open>Aperçu de l’article</button>
        <button class="btn-orange" type="submit" name="action" value="submit" onclick="return confirm('Soumettre cette tribune à la modération ? Elle ne sera plus modifiable pendant la relecture.');">Soumettre à la modération</button>
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
