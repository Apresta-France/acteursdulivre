<?php
use Adl\Data\AdminCatalog;
use Adl\Core\Csrf;

$letter = $letter ?? [];
$id = (int) ($letter['id'] ?? 0);
$action = $id ? '/admin/newsletter/lettre/' . $id : '/admin/newsletter/nouvelle';
$catalog = $catalog ?? ['missions' => [], 'people' => [], 'articles' => []];
$confirmedCount = (int) ($confirmedCount ?? 0);
$builder = [
    'blocks' => $letter['blocks'] ?? [],
    'catalog' => $catalog,
    'uploadUrl' => url('/admin/newsletter/image'),
    'token' => Csrf::token(),
];
$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS;
if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
    $jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
}
$builderJson = json_encode($builder, $jsonFlags);
if ($builderJson === false) {
    $builderJson = '{"blocks":[],"catalog":{"missions":[],"people":[],"articles":[]}}';
}
?>
<div class="admin-page admin-nl-builder-page">
  <p class="admin-back"><a href="<?= e(url('/admin/newsletter')) ?>">← Toutes les lettres</a></p>
  <div class="admin-page-head">
    <div>
      <h1><?= $id ? 'Modifier la lettre' : 'Nouvelle lettre' ?></h1>
      <p class="admin-lead" style="margin-bottom: 0;">
        <?= $id
            ? e((string) ($letter['status_label'] ?? 'Brouillon'))
            : 'Ajoutez des blocs, prévisualisez, puis enregistrez.' ?>
        <?php if (!empty($letter['sent_at'])): ?>
          · dernier envoi le <?= e((string) $letter['sent_at']) ?>
        <?php endif; ?>
      </p>
    </div>
    <?php if ($id): ?>
      <span class="admin-pill" style="<?= e(AdminCatalog::pill((string) ($letter['status_tone'] ?? 'navy'))) ?>"><?= e((string) ($letter['status_label'] ?? 'Brouillon')) ?></span>
    <?php endif; ?>
  </div>

  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div><?php endif; ?>
  <?php if (!empty($tested)): ?><div class="flash flash-ok"><?= e((string) $tested) ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <form class="admin-nl-builder" method="post" action="<?= e(url($action)) ?>" data-nl-builder>
    <?= csrf_field() ?>
    <input type="hidden" name="blocks" data-nl-blocks value="">

    <div class="admin-nl-builder-meta">
      <div>
        <label class="field" for="nl-subject">Sujet</label>
        <input class="input" id="nl-subject" name="subject" required maxlength="180" value="<?= e((string) ($letter['subject'] ?? '')) ?>" placeholder="Le point sur les métiers du livre">
      </div>
      <div>
        <label class="field" for="nl-preheader">Pré-en-tête</label>
        <input class="input" id="nl-preheader" name="preheader" maxlength="180" value="<?= e((string) ($letter['preheader'] ?? '')) ?>" placeholder="Visible dans la boîte de réception, avant d’ouvrir">
      </div>
    </div>

    <div class="admin-nl-builder-grid">
      <div class="admin-nl-builder-main">
        <div class="admin-nl-palette" data-nl-palette>
          <p class="field">Ajouter un bloc</p>
          <div class="admin-nl-palette-row">
            <button type="button" data-nl-add="heading">Titre</button>
            <button type="button" data-nl-add="text">Texte</button>
            <button type="button" data-nl-add="image">Image</button>
            <button type="button" data-nl-add="button">Bouton</button>
            <button type="button" data-nl-add="quote">Citation</button>
            <button type="button" data-nl-add="divider">Séparateur</button>
            <button type="button" data-nl-add="spacer">Espace</button>
            <button type="button" data-nl-add="cards">Liste de liens</button>
          </div>
          <p class="field" style="margin-top:14px;">Insérer depuis le site</p>
          <div class="admin-nl-palette-row">
            <button type="button" data-nl-insert="missions">Recherches</button>
            <button type="button" data-nl-insert="people">Profils</button>
            <button type="button" data-nl-insert="articles">Journal</button>
          </div>
        </div>

        <div class="admin-nl-canvas" data-nl-canvas></div>
      </div>

      <aside class="admin-nl-preview-pane">
        <p class="field">Aperçu</p>
        <div class="admin-nl-mail">
          <div class="admin-nl-mail-brand">Acteurs du Livre</div>
          <div class="admin-nl-mail-body" data-nl-preview></div>
          <div class="admin-nl-mail-foot">acteursdulivre.fr — Se désinscrire de la lettre</div>
        </div>
      </aside>
    </div>

    <div class="admin-nl-builder-actions">
      <button class="btn-orange" type="submit">Enregistrer</button>
      <?php if ($id): ?>
        <span class="admin-muted"><?= $confirmedCount ?> abonné<?= $confirmedCount > 1 ? 's' : '' ?> confirmé<?= $confirmedCount > 1 ? 's' : '' ?></span>
      <?php endif; ?>
    </div>
  </form>

  <?php if ($id): ?>
    <div class="admin-nl-builder-send">
      <form class="admin-nl-actions" method="post" action="<?= e(url('/admin/newsletter/lettre/' . $id . '/test')) ?>" data-nl-sync>
        <?= csrf_field() ?>
        <input type="hidden" name="subject" value="<?= e((string) ($letter['subject'] ?? '')) ?>">
        <input type="hidden" name="preheader" value="<?= e((string) ($letter['preheader'] ?? '')) ?>">
        <input type="hidden" name="blocks" value="">
        <input class="input" type="email" name="test_email" value="<?= e(auth_user()['email'] ?? '') ?>" required>
        <button class="btn-navy" type="submit">Envoyer un test</button>
      </form>
      <form method="post" action="<?= e(url('/admin/newsletter/lettre/' . $id . '/envoyer')) ?>" data-nl-sync onsubmit="return confirm('Mettre cette lettre en file d’envoi pour <?= (int) $confirmedCount ?> abonné<?= $confirmedCount > 1 ? 's' : '' ?> confirmé<?= $confirmedCount > 1 ? 's' : '' ?> ?');">
        <?= csrf_field() ?>
        <input type="hidden" name="subject" value="<?= e((string) ($letter['subject'] ?? '')) ?>">
        <input type="hidden" name="preheader" value="<?= e((string) ($letter['preheader'] ?? '')) ?>">
        <input type="hidden" name="blocks" value="">
        <button class="btn-orange" type="submit">Envoyer maintenant</button>
      </form>
      <form method="post" action="<?= e(url('/admin/newsletter/lettre/' . $id . '/supprimer')) ?>" onsubmit="return confirm('Supprimer cette lettre ?');">
        <?= csrf_field() ?>
        <button class="admin-ghost" type="submit">Supprimer</button>
      </form>
    </div>
  <?php endif; ?>

  <script type="application/json" id="nl-builder-data"><?= $builderJson ?></script>
</div>
