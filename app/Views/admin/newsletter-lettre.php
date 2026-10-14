<?php
use Adl\Data\AdminCatalog;
use Adl\Core\Csrf;
use Adl\Models\NewsletterCampaign;

$letter = $letter ?? [];
$id = (int) ($letter['id'] ?? 0);
$action = $id ? '/admin/newsletter/lettre/' . $id : '/admin/newsletter/nouvelle';
$catalog = $catalog ?? ['missions' => [], 'people' => [], 'articles' => []];
$campaign = is_array($campaign ?? null) ? $campaign : null;
$hasCampaign = $campaign !== null;
$audience = NewsletterCampaign::audience((string) ($campaign['audience'] ?? 'confirmed'));
$audienceCounts = is_array($audienceCounts ?? null) ? $audienceCounts : [];
$audienceGroups = [
    'Abonnés à la lettre' => [
        'confirmed' => 'Tous',
        'confirmed_presta' => 'Prestataires',
        'confirmed_client' => 'Clients',
    ],
    'Comptes, même sans la case lettre' => [
        'accounts' => 'Tous',
        'accounts_presta' => 'Prestataires',
        'accounts_client' => 'Clients',
    ],
];
$alreadySent = (int) ($campaign['sent_count'] ?? 0);
$builder = [
    'blocks' => $letter['blocks'] ?? [],
    'catalog' => $catalog,
    'uploadUrl' => url('/admin/newsletter/image'),
    'token' => Csrf::token(),
    'socialIcons' => [
        'facebook' => \Adl\Data\Socials::iconUrl('facebook', true),
        'instagram' => \Adl\Data\Socials::iconUrl('instagram', true),
        'linkedin' => \Adl\Data\Socials::iconUrl('linkedin', true),
    ],
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
      <p class="admin-lead">Cliquez un bloc à gauche pour l’ajouter. Cliquez ensuite dans la lettre pour écrire.</p>
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
        <form id="nl-send" method="post" action="<?= e(url('/admin/newsletter/lettre/' . $id . '/envoyer')) ?>" data-nl-sync data-nl-send<?= $hasCampaign ? ' data-nl-relaunch="1"' : '' ?> data-already="<?= $alreadySent ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="subject" value="<?= e((string) ($letter['subject'] ?? '')) ?>">
          <input type="hidden" name="preheader" value="<?= e((string) ($letter['preheader'] ?? '')) ?>">
          <input type="hidden" name="blocks" value="">
          <input type="hidden" name="audience" value="<?= e($audience) ?>">
          <button class="btn-navy" type="submit"><?= $hasCampaign ? 'Relancer' : 'Envoyer' ?></button>
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
        <p class="admin-nl-palette-hint">Cliquez pour ajouter</p>
        <div class="admin-nl-tiles">
          <?php foreach ($tiles as [$type, $label, $skin]): ?>
            <button type="button" class="admin-nl-tile" data-nl-add="<?= e($type) ?>" draggable="true" title="Cliquer pour ajouter">
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
        <div class="admin-nl-meta">
          <label class="field" for="nl-subject">Sujet de la lettre</label>
          <input class="input" id="nl-subject" name="subject" required maxlength="180" value="<?= e((string) ($letter['subject'] ?? '')) ?>" placeholder="Ex. : Le point de la semaine — 2 septembre" autocomplete="off">
          <p class="field-help">Titre affiché dans la boîte de réception du destinataire.</p>
          <label class="field" for="nl-preheader">Aperçu sous le sujet <span class="admin-nl-optional">(facultatif)</span></label>
          <input class="input" id="nl-preheader" name="preheader" maxlength="180" value="<?= e((string) ($letter['preheader'] ?? '')) ?>" placeholder="Une ligne visible sous le sujet, chez le destinataire" autocomplete="off">
          <?php if ($id): ?>
            <?php foreach ($audienceGroups as $legend => $options): ?>
              <fieldset class="admin-nl-audience">
                <legend><?= e($legend) ?></legend>
                <?php foreach ($options as $value => $label): ?>
                  <label>
                    <input type="radio" name="audience" value="<?= e($value) ?>" data-count="<?= (int) ($audienceCounts[$value] ?? 0) ?>"<?= $audience === $value ? ' checked' : '' ?>>
                    <span><?= e($label) ?> <em><?= (int) ($audienceCounts[$value] ?? 0) ?></em></span>
                  </label>
                <?php endforeach; ?>
              </fieldset>
            <?php endforeach; ?>
            <p class="field-help"><?php
              if ($hasCampaign) {
                  $received = $alreadySent > 1
                      ? $alreadySent . ' personnes l’ont déjà reçue : elles ne la recevront pas à nouveau.'
                      : ($alreadySent === 1
                          ? '1 personne l’a déjà reçue : elle ne la recevra pas à nouveau.'
                          : 'Personne ne l’a encore reçue.');
                  echo 'Relancer enregistre le texte modifié et n’écrit qu’à ceux qui n’ont pas encore reçu cette campagne. ' . $received . ' ';
              }
              echo 'Les clients sont les porteurs de projet, ceux qui cherchent des prestataires. Quelqu’un qui cherche et propose entre dans les deux profils. Les désinscrits restent exclus, et les inscrits sans compte ne figurent que dans « Tous » des abonnés. Cet envoi ne coche pas la case lettre à la place des comptes.';
            ?></p>
          <?php endif; ?>
        </div>

        <div class="admin-nl-mail" data-nl-mail>
          <div class="admin-nl-mail-brand">Acteurs du Livre</div>
          <div class="admin-nl-mail-body" data-nl-canvas></div>
          <div class="admin-nl-mail-foot">
            <?php require ADL_ROOT . '/app/Views/emails/socials.php'; ?>
            <div>acteursdulivre.fr — Se désinscrire de la lettre</div>
          </div>
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
