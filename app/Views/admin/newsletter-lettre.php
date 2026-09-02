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
$tiles = [
    ['heading', 'Titre', 'heading'],
    ['text', 'Texte', 'text'],
    ['image', 'Image', 'image'],
    ['button', 'Bouton', 'button'],
    ['quote', 'Citation', 'quote'],
    ['divider', 'Séparateur', 'divider'],
    ['spacer', 'Espace', 'spacer'],
    ['cards', 'Liste', 'cards'],
];
?>
<div class="admin-page admin-nl-studio">
  <div class="admin-nl-studio-bar">
    <div>
      <p class="admin-back"><a href="<?= e(url('/admin/newsletter')) ?>">← Toutes les lettres</a></p>
      <h1><?= $id ? 'Modifier la lettre' : 'Nouvelle lettre' ?></h1>
      <p class="admin-lead">Cliquez dans la lettre pour écrire. Glissez un bloc pour le déplacer.</p>
    </div>
    <div class="admin-nl-studio-bar-actions">
      <?php if ($id): ?>
        <span class="admin-pill" style="<?= e(AdminCatalog::pill((string) ($letter['status_tone'] ?? 'navy'))) ?>"><?= e((string) ($letter['status_label'] ?? 'Brouillon')) ?></span>
      <?php endif; ?>
      <button class="btn-orange" type="submit" form="nl-main">Enregistrer</button>
      <?php if ($id): ?>
        <form method="post" action="<?= e(url('/admin/newsletter/lettre/' . $id . '/test')) ?>" data-nl-sync>
          <?= csrf_field() ?>
          <input type="hidden" name="subject" value="<?= e((string) ($letter['subject'] ?? '')) ?>">
          <input type="hidden" name="preheader" value="<?= e((string) ($letter['preheader'] ?? '')) ?>">
          <input type="hidden" name="blocks" value="">
          <input class="input" type="email" name="test_email" value="<?= e(auth_user()['email'] ?? '') ?>" required aria-label="Adresse de test">
          <button class="btn-navy" type="submit">Tester</button>
        </form>
        <form method="post" action="<?= e(url('/admin/newsletter/lettre/' . $id . '/envoyer')) ?>" data-nl-sync onsubmit="return confirm('Mettre cette lettre en file d’envoi pour <?= (int) $confirmedCount ?> abonné<?= $confirmedCount > 1 ? 's' : '' ?> ?');">
          <?= csrf_field() ?>
          <input type="hidden" name="subject" value="<?= e((string) ($letter['subject'] ?? '')) ?>">
          <input type="hidden" name="preheader" value="<?= e((string) ($letter['preheader'] ?? '')) ?>">
          <input type="hidden" name="blocks" value="">
          <button class="btn-navy" type="submit">Envoyer</button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div><?php endif; ?>
  <?php if (!empty($tested)): ?><div class="flash flash-ok"><?= e((string) $tested) ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <form id="nl-main" class="admin-nl-builder" method="post" action="<?= e(url($action)) ?>" data-nl-builder>
    <?= csrf_field() ?>
    <input type="hidden" name="blocks" data-nl-blocks value="">

    <div class="admin-nl-studio-grid">
      <aside class="admin-nl-palette" data-nl-palette>
        <p class="admin-nl-palette-kicker">Blocs</p>
        <div class="admin-nl-tiles">
          <?php foreach ($tiles as [$type, $label, $skin]): ?>
            <button type="button" class="admin-nl-tile" data-nl-add="<?= e($type) ?>">
              <span class="admin-nl-tile-preview admin-nl-tile-<?= e($skin) ?>" aria-hidden="true"></span>
              <span><?= e($label) ?></span>
            </button>
          <?php endforeach; ?>
        </div>
        <p class="admin-nl-palette-kicker">Depuis le site</p>
        <div class="admin-nl-inserts">
          <button type="button" data-nl-insert="missions">Dernières recherches</button>
          <button type="button" data-nl-insert="people">Nouveaux profils</button>
          <button type="button" data-nl-insert="articles">Journal</button>
        </div>
      </aside>

      <div class="admin-nl-stage">
        <div class="admin-nl-inbox">
          <span class="admin-nl-inbox-dot" aria-hidden="true"></span>
          <div>
            <label class="sr-only" for="nl-subject">Sujet</label>
            <input class="admin-nl-inbox-subject" id="nl-subject" name="subject" required maxlength="180" value="<?= e((string) ($letter['subject'] ?? '')) ?>" placeholder="Sujet de la lettre">
            <label class="sr-only" for="nl-preheader">Pré-en-tête</label>
            <input class="admin-nl-inbox-pre" id="nl-preheader" name="preheader" maxlength="180" value="<?= e((string) ($letter['preheader'] ?? '')) ?>" placeholder="Aperçu dans la boîte de réception">
          </div>
        </div>

        <div class="admin-nl-mail" data-nl-mail>
          <div class="admin-nl-mail-brand">Acteurs du Livre</div>
          <div class="admin-nl-mail-body" data-nl-canvas></div>
          <div class="admin-nl-mail-foot">acteursdulivre.fr — Se désinscrire de la lettre</div>
        </div>
      </div>

      <aside class="admin-nl-inspector" data-nl-inspector>
        <p class="admin-nl-inspector-empty">Sélectionnez un bloc dans la lettre pour le régler.</p>
      </aside>
    </div>
  </form>

  <?php if ($id): ?>
    <form class="admin-nl-studio-delete" method="post" action="<?= e(url('/admin/newsletter/lettre/' . $id . '/supprimer')) ?>" onsubmit="return confirm('Supprimer cette lettre ?');">
      <?= csrf_field() ?>
      <button class="admin-ghost" type="submit">Supprimer la lettre</button>
    </form>
  <?php endif; ?>

  <script type="application/json" id="nl-builder-data"><?= $builderJson ?></script>
</div>
