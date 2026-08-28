<?php
$old = is_array($old ?? null) ? $old : [];
$selected = (string) ($old['category_name'] ?? 'Correction');
$selectedSpecialty = (string) ($old['specialty'] ?? '');
$trades = $trades ?? \Adl\Data\Catalog::trades();
$specialties = $specialties ?? \Adl\Data\Catalog::specialties();
$commission = (string) ($commission ?? '8');
$firstFree = !empty($firstMissionFree);
$standardCommission = (string) ($standardCommission ?? '8');
$blocked = !empty($billingBlock);
$founder = !empty($isFounder);
$packages = is_array($old['packages'] ?? null) ? $old['packages'] : [
    ['name' => 'Essentielle', 'description' => '', 'price' => '', 'delay' => ''],
    ['name' => 'Standard', 'description' => '', 'price' => '', 'delay' => ''],
    ['name' => 'Complète', 'description' => '', 'price' => '', 'delay' => ''],
];
$coverLabel = \Adl\Data\Catalog::tradeTitle($selected);
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
          <select class="input" id="service-trade" name="category_name" required data-cover-trade>
            <?php foreach ($trades as $trade): ?>
              <option value="<?= e($trade) ?>"<?= $selected === $trade ? ' selected' : '' ?>>
                <?= e(\Adl\Data\Catalog::tradeTitle($trade)) ?>
              </option>
            <?php endforeach; ?>
          </select>
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
        <label class="field" for="service-excerpt">Périmètre</label>
        <textarea class="textarea" id="service-excerpt" name="excerpt" rows="5"
                  placeholder="Ce qui est inclus, les exclusions, le format de livraison…"><?= e((string) ($old['excerpt'] ?? '')) ?></textarea>
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
        <p class="field-help" style="margin-top: 0; margin-bottom: 12px;">Trois niveaux aident à comparer. Le prix « à partir de » reprend la formule la moins chère si vous le laissez vide.</p>
        <?php foreach ($packages as $i => $package): ?>
          <div class="form-grid-3" style="margin-bottom: 12px;">
            <input class="input" name="packages[<?= (int) $i ?>][name]" value="<?= e((string) ($package['name'] ?? '')) ?>" placeholder="Nom">
            <input class="input" name="packages[<?= (int) $i ?>][price]" value="<?= e((string) ($package['price'] ?? '')) ?>" placeholder="Prix €" inputmode="numeric">
            <input class="input" name="packages[<?= (int) $i ?>][delay]" value="<?= e((string) ($package['delay'] ?? '')) ?>" placeholder="Délai">
          </div>
          <input class="input" name="packages[<?= (int) $i ?>][description]" value="<?= e((string) ($package['description'] ?? '')) ?>" placeholder="Ce que comprend cette formule" style="margin-bottom: 16px;">
        <?php endforeach; ?>
      </div>

      <div>
        <label class="field" for="service-image">Visuel (optionnel)</label>
        <input class="input" id="service-image" type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" data-cover-file>
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
        <button class="btn-orange" type="submit" name="intent" value="publish"<?= $blocked ? ' disabled' : '' ?>>Mettre en ligne</button>
        <button class="btn-ghost" type="submit" name="intent" value="draft">Enregistrer le brouillon</button>
      </div>
    </form>

    <aside class="publish-side">
      <div class="side-card">
        <div class="side-kicker">Avant publication</div>
        <ul class="create-check">
          <li>Titre clair, sans superlatif</li>
          <li>Métier et spécialité renseignés</li>
          <li>Prix ou au moins une formule</li>
          <li>Périmètre et exclusions précisés</li>
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
