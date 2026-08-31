<?php
$service = is_array($service ?? null) ? $service : null;
$package = is_array($selectedPackage ?? null) ? $selectedPackage : null;
$old = is_array($old ?? null) ? $old : [];
$serviceOptions = ($service !== null && is_array($service['options'] ?? null)) ? $service['options'] : [];
$selectedOptionIds = is_array($selectedOptionIds ?? null) ? $selectedOptionIds : [];
$baseAmount = $package
    ? (int) ($package['price'] ?? 0)
    : ($service !== null ? (int) ($service['price_from'] ?? 0) : 0);
?>
<div class="espace-page">
  <div class="espace-page-head">
    <div>
      <h1>Confirmer la commande</h1>
      <p>Aucun paiement n’est encaissé ici. Vous ouvrez un suivi à jalons (devis, factures, règlements déclarés, validation). La commission prestataire est le dernier jalon, après votre validation.</p>
    </div>
  </div>

  <?php if (!empty($error)): ?>
    <div class="flash flash-error"><?= e((string) $error) ?></div>
  <?php endif; ?>

  <?php if (!$service): ?>
    <div class="search-empty">
      <strong>Aucune prestation sélectionnée.</strong>
      <span>Choisissez une offre dans l'annuaire pour ouvrir une commande.</span>
      <a class="btn-orange" href="<?= e(url('/prestations')) ?>">Parcourir les prestations</a>
    </div>
  <?php else: ?>
    <div class="publish-grid" data-order-total data-base="<?= (int) $baseAmount ?>">
      <form class="param-form" method="post" action="<?= e(url('/espace/commande')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="service_id" value="<?= (int) $service['id'] ?>">
        <input type="hidden" name="package_id" value="<?= (int) ($package['id'] ?? 0) ?>">
        <div>
          <label class="field" for="brief">Brief pour le prestataire</label>
          <textarea class="textarea" id="brief" name="brief" rows="6" placeholder="Calendrier, format de livraison, points de vigilance…"><?= e((string) ($old['brief'] ?? '')) ?></textarea>
          <p class="field-help">Ce message ouvre aussi la conversation. Vous pourrez préciser ensuite.</p>
        </div>
        <?php if ($serviceOptions !== []): ?>
          <div>
            <span class="field">Options</span>
            <p class="field-help" style="margin-top: 0; margin-bottom: 12px;">Chaque option s'ajoute au prix de la formule ou de la prestation.</p>
            <div class="option-list">
              <?php foreach ($serviceOptions as $option): ?>
                <?php $optionId = (int) ($option['id'] ?? 0); ?>
                <label class="option-row">
                  <input type="checkbox" name="options[]" value="<?= $optionId ?>"
                         data-price="<?= (int) ($option['price'] ?? 0) ?>"
                         <?= in_array($optionId, $selectedOptionIds, true) ? ' checked' : '' ?>>
                  <span><?= e((string) ($option['name'] ?? '')) ?></span>
                  <strong>+<?= e((string) ($option['price_label'] ?? format_euros((int) ($option['price'] ?? 0)))) ?></strong>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
        <div class="auth-actions">
          <button class="btn-orange" type="submit">Ouvrir la commande</button>
          <a class="btn-ghost" href="<?= e(url((string) $service['href'])) ?>">Retour à la fiche</a>
        </div>
      </form>
      <aside class="publish-side">
        <div class="side-card">
          <div class="side-kicker">Récapitulatif</div>
          <strong><?= e((string) $service['title']) ?></strong>
          <p class="mission-row-sub"><?= e((string) $service['by']) ?></p>
          <?php if ($package): ?>
            <div class="side-foot">
              <span><?= e((string) $package['name']) ?><?= !empty($package['delay']) ? ' · ' . e((string) $package['delay']) : '' ?></span>
              <strong><?= e((string) ($package['price_label'] ?? $service['price'])) ?></strong>
            </div>
          <?php else: ?>
            <div class="side-foot">
              <span><?= e((string) ($service['delay'] ?: 'Délai à convenir')) ?></span>
              <strong><?= e((string) $service['price']) ?></strong>
            </div>
          <?php endif; ?>
          <?php if ($serviceOptions !== []): ?>
            <div class="side-foot">
              <span>Total</span>
              <strong data-order-total-value><?= e(format_euros($baseAmount)) ?></strong>
            </div>
          <?php endif; ?>
        </div>
      </aside>
    </div>
  <?php endif; ?>
</div>
