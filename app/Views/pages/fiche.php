<?php
$service = $service ?? null;
if (!$service) {
    not_found('Cette prestation n\'est plus disponible.');
}
$packages = $service['packages'] ?? [];
if (count($packages) > 1) {
    usort($packages, static function (array $left, array $right): int {
        return ((int) ($left['price'] ?? 0)) <=> ((int) ($right['price'] ?? 0));
    });
}
$options = $service['options'] ?? [];
$viewer = \Adl\Core\Auth::user();
$isOwner = $viewer && (int) ($viewer['id'] ?? 0) === (int) ($service['user_id'] ?? 0);
$canOrder = !empty($canOrder) && !$isOwner;
$selectedPackage = $packages[0] ?? null;
$onQuote = $selectedPackage === null && !isset($service['price_from']);
$baseAmount = $selectedPackage
    ? (int) ($selectedPackage['price'] ?? 0)
    : ($onQuote ? 0 : (int) ($service['price_from'] ?? 0));
$orderHref = '/espace/commande?prestation=' . rawurlencode((string) $service['slug']);
if ($selectedPackage) {
    $orderHref .= '&formule=' . (int) ($selectedPackage['id'] ?? 0);
}
$hasSeveralPackages = count($packages) > 1;
$hasOptions = $options !== [];
$ctaLabel = $onQuote ? 'Demander un devis' : 'Commander';
$formulePriceLabel = is_array($selectedPackage)
    ? (string) ($selectedPackage['price_label'] ?? $service['price'])
    : (string) $service['price'];
$totalLabel = $onQuote ? 'sur devis' : format_euros_ttc($baseAmount);
$priceFactLabel = $hasSeveralPackages ? 'À partir de' : 'Prix TTC';
$packageDelays = [];
foreach ($packages as $package) {
    $delay = trim((string) ($package['delay'] ?? ''));
    if ($delay !== '') {
        $packageDelays[$delay] = true;
    }
}
$delayFact = (string) ($service['delay'] ?: 'à convenir');
if ($hasSeveralPackages && count($packageDelays) > 1) {
    $delayFact = 'selon la formule';
} elseif ($hasSeveralPackages && count($packageDelays) === 1) {
    $delayFact = (string) array_key_first($packageDelays);
}
?>
<div class="fiche-page">
  <nav class="search-crumb" aria-label="Fil d'Ariane">
    <a href="<?= e(url('/')) ?>">Accueil</a>
    <span aria-hidden="true"> · </span>
    <a href="<?= e(url('/prestations')) ?>">Prestations</a>
    <?php if (!empty($service['cat'])): ?>
      <span aria-hidden="true"> · </span>
      <a href="<?= e(url(\Adl\Data\Catalog::tradePath((string) $service['cat']))) ?>"><?= e((string) (\Adl\Data\Catalog::tradeTitle((string) $service['cat']))) ?></a>
    <?php endif; ?>
    <?php if (!empty($service['specialty'])): ?>
      <span aria-hidden="true"> · </span>
      <span><?= e((string) $service['specialty']) ?></span>
    <?php endif; ?>
  </nav>
  <div class="publish-grid">
    <div>
      <?php
        $gallery = is_array($service['images'] ?? null) ? $service['images'] : [];
        $portfolioUrl = trim((string) ($service['portfolio_url'] ?? ''));
      ?>
      <?php if ($gallery !== []): ?>
        <a class="service-cover-hero" href="<?= e((string) $gallery[0]) ?>" style="background-image:url('<?= e((string) $gallery[0]) ?>')"
           data-zoom data-zoom-title="<?= e((string) $service['title']) ?>"
           aria-haspopup="dialog" aria-controls="portfolio-zoom"
           aria-label="<?= e('Agrandir le visuel de la prestation') ?>"></a>
        <?php if (count($gallery) > 1): ?>
          <div class="service-fiche-gallery">
            <?php foreach (array_slice($gallery, 1) as $i => $src): ?>
              <a class="service-fiche-gallery-item" href="<?= e((string) $src) ?>" style="background-image:url('<?= e((string) $src) ?>')"
                 data-zoom data-zoom-title="<?= e((string) $service['title']) ?>"
                 aria-haspopup="dialog" aria-controls="portfolio-zoom"
                 aria-label="<?= e('Agrandir le visuel ' . (string) ($i + 2)) ?>"></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <dialog class="zoom-modal" id="portfolio-zoom" data-zoom-dialog aria-labelledby="portfolio-zoom-title">
          <div class="zoom-modal-inner">
            <div class="zoom-modal-bar">
              <h2 id="portfolio-zoom-title"></h2>
              <button type="button" class="zoom-modal-close" data-zoom-close aria-label="Fermer">×</button>
            </div>
            <figure class="zoom-modal-figure">
              <img data-zoom-img alt="">
              <figcaption>
                <span data-zoom-caption hidden></span>
                <p data-zoom-desc hidden></p>
              </figcaption>
            </figure>
          </div>
          <button type="button" class="zoom-modal-nav is-prev" data-zoom-prev aria-label="Visuel précédent" hidden>‹</button>
          <button type="button" class="zoom-modal-nav is-next" data-zoom-next aria-label="Visuel suivant" hidden>›</button>
        </dialog>
      <?php else: ?>
        <?= service_cover_html((string) ($service['cat'] ?? ''), 'is-hero') ?>
      <?php endif; ?>
      <?php if ($portfolioUrl !== ''): ?>
        <p class="service-portfolio-link">
          <?php if (preg_match('#^https?://#i', $portfolioUrl)): ?>
            <a href="<?= e($portfolioUrl) ?>" target="_blank" rel="noopener noreferrer">Voir le portfolio externe</a>
          <?php else: ?>
            <?= e($portfolioUrl) ?>
          <?php endif; ?>
        </p>
      <?php endif; ?>
      <h1><?= e((string) $service['title']) ?></h1>
      <div class="service-excerpt"><?= user_html(
          (string) ($service['excerpt'] ?? ''),
          '<p>Prestation proposée par ' . e((string) $service['by']) . '.</p>'
      ) ?></div>
      <div class="facts">
        <div><span><?= e($priceFactLabel) ?></span><strong><?= e((string) $service['price']) ?></strong></div>
        <div><span>Délai</span><strong><?= e($delayFact) ?></strong></div>
        <div><span>Métier</span><strong><?= e((string) ($service['cat'] ? \Adl\Data\Catalog::tradeTitle((string) $service['cat']) : '—')) ?></strong></div>
        <?php if (!empty($service['specialty'])): ?>
          <div><span>Spécialité</span><strong><?= e((string) $service['specialty']) ?></strong></div>
        <?php endif; ?>
        <div><span>Avis</span><strong><?= $service['reviews'] > 0 ? e((string) $service['rating']) . ' · ' . (int) $service['reviews'] : 'Pas encore d\'avis' ?></strong></div>
        <?php if (!empty($service['startup_enabled'])): ?>
          <div><span>Démarrage</span><strong><?= e((string) $service['startup_label']) ?></strong></div>
        <?php endif; ?>
      </div>

    </div>
    <aside class="publish-side fiche-side" data-order-total data-base="<?= (int) $baseAmount ?>"<?= $onQuote ? ' data-on-quote' : '' ?> data-order-href="<?= e(url($orderHref)) ?>">
      <div class="side-card fiche-order">
        <div class="fiche-order-body">
          <?php
            $formuleDesc = is_array($selectedPackage) ? (string) ($selectedPackage['description'] ?? '') : '';
            $formuleDelay = is_array($selectedPackage)
                ? (string) ($selectedPackage['delay'] ?? $service['delay'] ?? '')
                : (string) ($service['delay'] ?? '');
          ?>
          <?php if ($hasSeveralPackages): ?>
            <div class="side-title-sm">Choisir une formule</div>
            <div class="fiche-formule-list" role="radiogroup" aria-label="Formules">
              <?php foreach ($packages as $index => $package): ?>
                <?php
                  $isOn = $index === 0;
                  $pkgDesc = trim((string) ($package['description'] ?? ''));
                  $pkgDelay = trim((string) ($package['delay'] ?? ''));
                ?>
                <button type="button"
                        class="fiche-formule-choice<?= $isOn ? ' is-on' : '' ?>"
                        role="radio"
                        aria-checked="<?= $isOn ? 'true' : 'false' ?>"
                        tabindex="<?= $isOn ? '0' : '-1' ?>"
                        data-order-formule
                        data-id="<?= (int) ($package['id'] ?? 0) ?>"
                        data-price="<?= (int) ($package['price'] ?? 0) ?>"
                        data-name="<?= e((string) ($package['name'] ?? '')) ?>"
                        data-delay="<?= e($pkgDelay) ?>"
                        data-desc="<?= e($pkgDesc) ?>">
                  <span class="fiche-formule-choice-top">
                    <span class="fiche-formule-choice-name"><?= e((string) ($package['name'] ?? 'Formule')) ?></span>
                    <strong class="fiche-formule-choice-price"><?= e((string) ($package['price_label'] ?? format_euros_ttc((int) ($package['price'] ?? 0)))) ?></strong>
                  </span>
                  <span class="fiche-formule-choice-delay"><?= e($pkgDelay !== '' ? $pkgDelay : 'Délai à convenir') ?></span>
                  <?php if ($pkgDesc !== ''): ?>
                    <span class="fiche-formule-choice-desc"><?= e($pkgDesc) ?></span>
                  <?php endif; ?>
                </button>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="fiche-order-price-row">
              <span data-order-formule-name><?= e((string) ($selectedPackage['name'] ?? 'Prestation')) ?></span>
              <strong class="fiche-order-price" data-order-formule-price><?= e($formulePriceLabel) ?></strong>
            </div>
            <p class="mission-row-sub" data-order-formule-desc<?= $formuleDesc === '' ? ' hidden' : '' ?>><?= e($formuleDesc) ?></p>
            <div class="fiche-order-meta">
              <span data-order-formule-delay><?= e($formuleDelay !== '' ? $formuleDelay : 'Délai à convenir') ?></span>
            </div>
          <?php endif; ?>
          <?php if ($hasOptions): ?>
            <div class="fiche-order-options">
              <div class="side-title-sm">Options</div>
              <p class="field-help fiche-order-options-help">Elles s’ajoutent à la formule choisie.</p>
              <div class="option-list">
                <?php foreach ($options as $option): ?>
                  <?php $optionId = (int) ($option['id'] ?? 0); ?>
                  <label class="option-row">
                    <input type="checkbox" name="options[]" value="<?= $optionId ?>"
                           form="fiche-order-form"
                           data-price="<?= (int) ($option['price'] ?? 0) ?>"
                           <?= $isOwner ? ' disabled' : '' ?>>
                    <span><?= e((string) ($option['name'] ?? '')) ?></span>
                    <strong>+<?= e((string) ($option['price_label'] ?? format_euros_ttc((int) ($option['price'] ?? 0)))) ?></strong>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
          <?php if ($hasOptions || $onQuote): ?>
            <div class="fiche-order-total">
              <span>Total TTC</span>
              <strong data-order-total-value><?= e($totalLabel) ?></strong>
            </div>
          <?php endif; ?>
          <?php if (!empty($service['startup_enabled'])): ?>
            <p class="field-help" style="margin: 8px 0 0;">Accompagnement de démarrage : <?= e((string) $service['startup_label']) ?></p>
          <?php endif; ?>
          <?php if ($canOrder): ?>
            <form id="fiche-order-form" method="get" action="<?= e(url('/espace/commande')) ?>">
              <input type="hidden" name="prestation" value="<?= e((string) ($service['slug'] ?? '')) ?>">
              <?php if ($selectedPackage): ?>
                <input type="hidden" name="formule" value="<?= (int) ($selectedPackage['id'] ?? 0) ?>" data-order-formule-input>
              <?php endif; ?>
              <button class="btn-orange" type="submit" data-order-cta-label><?= e($ctaLabel) ?></button>
            </form>
            <p class="field-help" style="margin: 12px 0 0;">Aucun paiement ici : le prestataire envoie un devis, vous vous réglez hors plateforme, jalon par jalon.</p>
          <?php elseif ($isOwner): ?>
            <p class="field-help" style="margin: 12px 0 0;">C’est votre prestation. Connectez-vous avec un autre compte pour tester une commande.</p>
          <?php else: ?>
            <p class="field-help" style="margin: 12px 0 0;">Cette prestation n’est pas commandable pour le moment.</p>
          <?php endif; ?>
        </div>
      </div>
      <?php if ($canOrder): ?>
        <?php
          $askName = trim((string) ($service['by'] ?? ''));
          if ($askName === '') {
              $askName = trim((string) ($service['first_name'] ?? '')) ?: 'le prestataire';
          }
        ?>
        <div class="side-card fiche-ask">
          <div class="side-kicker">Une question ?</div>
          <div class="side-title-sm">Écrire à <?= e($askName) ?></div>
          <p class="field-help">Délai, adaptation, disponibilité… la réponse arrive dans votre messagerie.</p>
          <form method="post" action="<?= e(url('/espace/messages')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="avec" value="<?= (int) ($service['user_id'] ?? 0) ?>">
            <input type="hidden" name="sujet" value="<?= e((string) $service['title']) ?>">
            <input type="hidden" name="prestation" value="<?= (int) ($service['id'] ?? 0) ?>">
            <input type="hidden" name="retour" value="<?= e((string) ($service['href'] ?? '')) ?>">
            <label class="field" for="fiche-question">Votre question</label>
            <textarea class="textarea" id="fiche-question" name="message" required maxlength="8000" rows="4" placeholder="Bonjour, je voudrais savoir…"><?= e((string) old('message')) ?></textarea>
            <button class="btn-ghost" type="submit">Poser une question</button>
            <?php if (!$viewer): ?>
              <p class="field-help fiche-ask-note">Vous pourrez vous connecter ou créer un compte juste après l’envoi.</p>
            <?php endif; ?>
          </form>
        </div>
      <?php endif; ?>
      <div class="side-card side-card-warm fiche-ia">
        <div class="side-kicker">Garantie plateforme</div>
        <div class="side-title-sm">Sans IA générative</div>
        <p>Cette prestation est garantie sans usage d’IA générative. Le livrable est produit par un humain&nbsp;; le prestataire s’y est engagé.</p>
        <a href="<?= e(url('/regles-ia')) ?>">Lire nos règles IA</a>
      </div>
      <div class="side-card">
        <div class="side-kicker">Le prestataire</div>
        <div class="suggest-row" style="padding: 0;">
          <?= avatar_html($service, 46) ?>
          <span>
            <strong><?= e((string) $service['by']) ?></strong>
            <em><?= e((string) ($service['cat'] ?: 'Prestataire')) ?></em>
          </span>
        </div>
        <?php if (!empty($service['profile_href'])): ?>
          <div class="auth-actions" style="margin-top: 16px;">
            <a class="btn-ghost" href="<?= e(url((string) $service['profile_href'])) ?>">Voir la vitrine</a>
          </div>
        <?php endif; ?>
        <?php if ($viewer && !$isOwner && \Adl\Models\User::seeksServices($viewer)): ?>
          <form method="post" action="<?= e(url('/espace/favoris/' . (int) $service['id'])) ?>" style="margin-top: 12px;">
            <?= csrf_field() ?>
            <input type="hidden" name="back" value="<?= e((string) $service['href']) ?>">
            <button class="btn-ghost" type="submit"><?= !empty($isFavorite) ? 'Retirer des favoris' : 'Ajouter aux favoris' ?></button>
          </form>
        <?php endif; ?>
      </div>
      <?php if ($canOrder): ?>
        <div class="fiche-buy-bar">
          <strong data-order-total-value><?= e($totalLabel) ?></strong>
          <button class="btn-orange" type="submit" form="fiche-order-form" data-order-cta-label><?= e($ctaLabel) ?></button>
        </div>
      <?php endif; ?>
    </aside>
  </div>
</div>
