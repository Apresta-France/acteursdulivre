<?php
$old = is_array($old ?? null) ? $old : [];
$selected = (string) ($old['category_name'] ?? 'Correction');
$trades = $trades ?? \Adl\Data\Catalog::trades();
$packages = is_array($old['packages'] ?? null) ? $old['packages'] : [
    ['name' => 'Essentielle', 'description' => '', 'price' => '', 'delay' => ''],
    ['name' => 'Standard', 'description' => '', 'price' => '', 'delay' => ''],
    ['name' => 'Complète', 'description' => '', 'price' => '', 'delay' => ''],
];
?>
<div class="espace-page publish-page">
  <div class="espace-page-head">
    <div>
      <h1>Créer une prestation</h1>
      <p>Prix, délai et périmètre affichés : les porteurs de projet comparent et commandent.</p>
    </div>
  </div>

  <?php if (!empty($error)): ?>
    <div class="flash flash-error"><?= e((string) $error) ?></div>
  <?php endif; ?>

  <form class="param-form publish-form" method="post" action="<?= e(url('/espace/prestations/creer')) ?>">
    <?= csrf_field() ?>

    <div>
      <span class="field">Métier</span>
      <div class="chip-row">
        <?php foreach ($trades as $trade): ?>
          <label class="chip<?= $selected === $trade ? ' is-on' : '' ?>">
            <input type="radio" name="category_name" value="<?= e($trade) ?>"<?= $selected === $trade ? ' checked' : '' ?>>
            <?= e($trade) ?>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div>
      <label class="field" for="service-title">Titre de la prestation</label>
      <input class="input" id="service-title" name="title" required maxlength="255"
             value="<?= e((string) ($old['title'] ?? '')) ?>"
             placeholder="Je corrige votre roman jusqu'à 90 000 signes">
    </div>

    <div>
      <label class="field" for="service-excerpt">Périmètre</label>
      <textarea class="textarea" id="service-excerpt" name="excerpt" rows="5"
                placeholder="Ce qui est inclus, les exclusions, le format de livraison…"><?= e((string) ($old['excerpt'] ?? '')) ?></textarea>
    </div>

    <div class="form-grid-3">
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
      <?php foreach ($packages as $i => $package): ?>
        <div class="form-grid-3" style="margin-bottom: 12px;">
          <input class="input" name="packages[<?= (int) $i ?>][name]" value="<?= e((string) ($package['name'] ?? '')) ?>" placeholder="Nom">
          <input class="input" name="packages[<?= (int) $i ?>][price]" value="<?= e((string) ($package['price'] ?? '')) ?>" placeholder="Prix €" inputmode="numeric">
          <input class="input" name="packages[<?= (int) $i ?>][delay]" value="<?= e((string) ($package['delay'] ?? '')) ?>" placeholder="Délai">
        </div>
        <input class="input" name="packages[<?= (int) $i ?>][description]" value="<?= e((string) ($package['description'] ?? '')) ?>" placeholder="Ce que comprend cette formule" style="margin-bottom: 16px;">
      <?php endforeach; ?>
    </div>

    <div class="auth-actions publish-actions">
      <button class="btn-orange" type="submit" name="intent" value="publish">Publier la prestation</button>
      <button class="btn-ghost" type="submit" name="intent" value="draft">Enregistrer le brouillon</button>
    </div>
  </form>
</div>
