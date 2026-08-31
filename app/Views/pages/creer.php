<?php
$old = is_array($old ?? null) ? $old : [];
$emptyPackage = ['id' => '', 'name' => '', 'description' => '', 'price' => '', 'delay' => ''];
$selectedSpecialty = (string) ($old['specialty'] ?? '');
$trades = is_array($trades ?? null) ? $trades : [];
$specialties = $specialties ?? \Adl\Data\Catalog::specialties();
$commission = (string) ($commission ?? '8');
$firstFree = !empty($firstMissionFree);
$standardCommission = (string) ($standardCommission ?? '8');
$blocked = !empty($billingBlock);
$founder = !empty($isFounder);
$selected = (string) ($old['category_name'] ?? '');
if ($trades !== [] && !in_array($selected, $trades, true)) {
    $selected = (string) $trades[0];
}
$packages = is_array($old['packages'] ?? null) ? $old['packages'] : [];
if ($packages === []) {
    $packages = [$emptyPackage];
}
$options = is_array($old['options'] ?? null) ? $old['options'] : [];
if ($options === []) {
    $options = [['id' => '', 'name' => '', 'price' => '']];
}
$coverLabel = \Adl\Data\Catalog::tradeTitle($selected);
$noTrades = $trades === [];
?>
<div class="espace-page publish-page">
  <div class="espace-page-head">
    <div>
      <h1><?= !empty($editing) ? 'Modifier la prestation' : 'Proposer une prestation' ?></h1>
      <p>Prix, délai et périmètre affichés : les porteurs de projet comparent et commandent.</p>
    </div>
  </div>

  <?php if (!empty($error)): ?>
    <div class="flash flash-error"><?= e((string) $error) ?></div>
  <?php endif; ?>
  <?php require ADL_ROOT . '/app/Views/partials/billing-banner.php'; ?>

  <div class="publish-grid">
    <div>
    <form class="param-form publish-form" method="post" action="<?= e(url(!empty($editing) ? '/espace/prestations/' . (int) ($serviceId ?? 0) . '/modifier' : '/espace/prestations/creer')) ?>" enctype="multipart/form-data" data-service-cover>
      <?= csrf_field() ?>

      <div>
        <label class="field" for="service-title">Titre — commencez par « Je … »</label>
        <input class="input" id="service-title" name="title" required maxlength="80"
               value="<?= e((string) ($old['title'] ?? '')) ?>"
               placeholder="Je corrige votre roman jusqu'à 90 000 signes">
        <p class="field-help">Un titre concret se trouve plus facilement dans l'annuaire.</p>
      </div>

      <div class="form-grid-2">
        <div>
          <label class="field" for="service-trade">Métier</label>
          <?php if ($noTrades): ?>
            <input type="hidden" name="category_name" value="">
            <p class="field-help" style="margin-top: 8px;">Ajoutez d'abord vos métiers sur <a href="<?= e(url('/espace/vitrine')) ?>">votre vitrine</a> (trois maximum). Seuls ceux-là apparaissent ici.</p>
          <?php else: ?>
            <select class="input" id="service-trade" name="category_name" required data-cover-trade>
              <?php foreach ($trades as $trade): ?>
                <option value="<?= e($trade) ?>"<?= $selected === $trade ? ' selected' : '' ?>>
                  <?= e(\Adl\Data\Catalog::tradeTitle($trade)) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <p class="field-help">Uniquement les métiers choisis sur votre vitrine.</p>
          <?php endif; ?>
        </div>
        <div>
          <label class="field" for="service-specialty">Spécialité</label>
          <select class="input" id="service-specialty" name="specialty">
            <option value="">Choisir une spécialité</option>
            <?php foreach ($specialties as $genre): ?>
              <option value="<?= e($genre) ?>"<?= $selectedSpecialty === $genre ? ' selected' : '' ?>>
                <?= e($genre === \Adl\Models\Taxonomy::GLOBAL_NAME ? 'Global — tous types de textes' : $genre) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <p class="field-help">Global convient si vous travaillez sur tous les types de textes.</p>
        </div>
      </div>

      <div>
        <label class="field" for="service-excerpt" id="service-excerpt-label">Description et périmètre de la prestation</label>
        <div class="wysiwyg" data-wysiwyg>
          <div class="wysiwyg-toolbar" hidden>
            <button type="button" data-wysiwyg-cmd="bold" aria-label="Gras" title="Gras"><strong>G</strong></button>
            <button type="button" data-wysiwyg-cmd="italic" aria-label="Italique" title="Italique"><em>I</em></button>
            <button type="button" data-wysiwyg-cmd="insertUnorderedList" aria-label="Liste à puces" title="Liste à puces">• Liste</button>
            <button type="button" data-wysiwyg-cmd="insertOrderedList" aria-label="Liste numérotée" title="Liste numérotée">1. Liste</button>
            <button type="button" data-wysiwyg-cmd="createLink" aria-label="Lien" title="Ajouter un lien">Lien</button>
          </div>
          <textarea class="textarea wysiwyg-source" id="service-excerpt" name="excerpt" rows="8"
                    placeholder="Ce qui est inclus, les exclusions, le format de livraison…"><?= e((string) ($old['excerpt'] ?? '')) ?></textarea>
          <div class="wysiwyg-editor" contenteditable="true" role="textbox" aria-multiline="true" aria-labelledby="service-excerpt-label" hidden></div>
        </div>
        <p class="field-help">Présentez l’offre : ce qui est inclus, les exclusions, le format et le volume livrés.</p>
      </div>

      <div class="form-grid-2">
        <div>
          <label class="field" for="service-price">Prix à partir de (€)</label>
          <input class="input" id="service-price" name="price_from" inputmode="numeric" value="<?= e((string) ($old['price_from'] ?? '')) ?>" placeholder="420">
        </div>
        <div>
          <label class="field" for="service-delay">Délai annoncé</label>
          <input class="input" id="service-delay" name="delay" value="<?= e((string) ($old['delay'] ?? '')) ?>" placeholder="8 jours">
        </div>
      </div>

      <div>
        <span class="field">Formules (optionnel)</span>
        <p class="field-help" style="margin-top: 0; margin-bottom: 12px;">Ajoutez autant de formules que nécessaire. Chaque formule enregistrée doit avoir un nom et un prix. Le prix « à partir de » reprend la moins chère si vous le laissez vide.</p>
        <div class="repeat-list" data-repeat="packages">
          <?php foreach ($packages as $i => $package): ?>
            <div class="repeat-package" data-repeat-row>
              <input type="hidden" name="packages[<?= (int) $i ?>][id]" value="<?= e((string) ($package['id'] ?? '')) ?>">
              <div class="repeat-row is-formule">
                <input class="input" name="packages[<?= (int) $i ?>][name]" value="<?= e((string) ($package['name'] ?? '')) ?>" placeholder="Nom" maxlength="80">
                <input class="input" name="packages[<?= (int) $i ?>][price]" value="<?= e((string) ($package['price'] ?? '')) ?>" placeholder="Prix €" inputmode="numeric">
                <input class="input" name="packages[<?= (int) $i ?>][delay]" value="<?= e((string) ($package['delay'] ?? '')) ?>" placeholder="Délai">
                <button type="button" class="icon-btn" data-repeat-remove aria-label="Retirer">✕</button>
              </div>
              <input class="input" name="packages[<?= (int) $i ?>][description]" value="<?= e((string) ($package['description'] ?? '')) ?>" placeholder="Ce que comprend cette formule">
            </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="btn-ghost" data-repeat-add="packages">Ajouter une formule</button>
      </div>

      <div>
        <span class="field">Options (optionnel)</span>
        <p class="field-help" style="margin-top: 0; margin-bottom: 12px;">Le client peut les ajouter à la formule ou au prix de base. Leur montant s'ajoute au total.</p>
        <div class="repeat-list" data-repeat="options">
          <?php foreach ($options as $i => $option): ?>
            <div class="repeat-row is-price" data-repeat-row>
              <input type="hidden" name="options[<?= (int) $i ?>][id]" value="<?= e((string) ($option['id'] ?? '')) ?>">
              <input class="input" name="options[<?= (int) $i ?>][name]" value="<?= e((string) ($option['name'] ?? '')) ?>" placeholder="Livraison accélérée">
              <input class="input" name="options[<?= (int) $i ?>][price]" value="<?= e((string) ($option['price'] ?? '')) ?>" placeholder="Prix €" inputmode="numeric">
              <button type="button" class="icon-btn" data-repeat-remove aria-label="Retirer">✕</button>
            </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="btn-ghost" data-repeat-add="options">Ajouter une option</button>
      </div>

      <div>
        <span class="field" id="service-image-label">Visuel (optionnel)</span>
        <?php
          $filePickId = 'service-image';
          $filePickName = 'image';
          $filePickAccept = 'image/jpeg,image/png,image/webp,image/gif';
          $filePickButton = 'Choisir un visuel';
          $filePickDrop = true;
          $filePickAttrs = 'data-cover-file aria-labelledby="service-image-label"';
          require ADL_ROOT . '/app/Views/partials/file-pick.php';
        ?>
        <p class="field-help">JPG, PNG ou WebP, 5 Mo max. Sans visuel, un visuel charté affiche le métier.</p>
        <div class="service-cover-preview">
          <?= service_cover_html($coverLabel) ?>
          <div class="service-cover-file" data-cover-photo hidden></div>
        </div>
      </div>

      <div class="form-notice">
        <div>
          <strong>Contenus sans IA générative</strong>
          <p>Les visuels et les textes déposés ici doivent être de votre main. Une image ou une description générée par IA entraîne le retrait de la prestation.</p>
        </div>
      </div>

      <div class="auth-actions publish-actions">
        <button class="btn-orange" type="submit" name="intent" value="publish"<?= ($blocked || $noTrades) ? ' disabled' : '' ?>>Mettre en ligne</button>
        <button class="btn-ghost" type="submit" name="intent" value="draft"<?= $noTrades ? ' disabled' : '' ?>>Enregistrer le brouillon</button>
      </div>
    </form>
    </div>

    <aside class="publish-side">
      <div class="side-card">
        <div class="side-kicker">Avant publication</div>
        <ul class="create-check">
          <li>Titre clair, sans superlatif</li>
          <li>Métier et spécialité renseignés</li>
          <li>Prix ou au moins une formule</li>
          <li>Description, périmètre et exclusions précisés</li>
        </ul>
      </div>
      <div class="side-card side-card-warm">
        <div class="side-kicker">Ce que vous toucherez</div>
        <?php if ($firstFree): ?>
        <p>Votre première mission est offerte. À partir de la deuxième, commission de <?= e($standardCommission) ?> % facturée lorsque le client confirme et note<?= $founder ? ' (membre fondateur)' : '' ?>. Aucun abonnement.</p>
        <?php else: ?>
        <p>Commission de <?= e($commission) ?> % facturée à la validation client<?= $founder ? ' (membre fondateur)' : '' ?>, sans abonnement. Exemple : 780 € vendus, commission <?= e((string) (int) round(780 * ((int) $commission) / 100)) ?> €.</p>
        <?php endif; ?>
      </div>
    </aside>
  </div>
</div>
<template id="tpl-packages">
  <div class="repeat-package" data-repeat-row>
    <input type="hidden" name="packages[__i__][id]" value="">
    <div class="repeat-row is-formule">
      <input class="input" name="packages[__i__][name]" placeholder="Nom" maxlength="80">
      <input class="input" name="packages[__i__][price]" placeholder="Prix €" inputmode="numeric">
      <input class="input" name="packages[__i__][delay]" placeholder="Délai">
      <button type="button" class="icon-btn" data-repeat-remove aria-label="Retirer">✕</button>
    </div>
    <input class="input" name="packages[__i__][description]" placeholder="Ce que comprend cette formule">
  </div>
</template>
<template id="tpl-options">
  <div class="repeat-row is-price" data-repeat-row>
    <input type="hidden" name="options[__i__][id]" value="">
    <input class="input" name="options[__i__][name]" placeholder="Livraison accélérée">
    <input class="input" name="options[__i__][price]" placeholder="Prix €" inputmode="numeric">
    <button type="button" class="icon-btn" data-repeat-remove aria-label="Retirer">✕</button>
  </div>
</template>
