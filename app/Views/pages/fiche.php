<?php
$service = $service ?? null;
if (!$service) {
    not_found('Cette prestation n\'est plus disponible.');
}
$packages = $service['packages'] ?? [];
$options = $service['options'] ?? [];
$viewer = \Adl\Core\Auth::user();
$isOwner = $viewer && (int) ($viewer['id'] ?? 0) === (int) ($service['user_id'] ?? 0);
$canOrder = !empty($canOrder) && !$isOwner;
$selectedPackage = $packages[0] ?? null;
$baseAmount = $selectedPackage
    ? (int) ($selectedPackage['price'] ?? 0)
    : (int) ($service['price_from'] ?? 0);
$orderHref = '/espace/commande?prestation=' . rawurlencode((string) $service['slug']);
if ($selectedPackage) {
    $orderHref .= '&formule=' . (int) ($selectedPackage['id'] ?? 0);
}
$ctaLabel = 'Commander — ' . format_euros_ttc($baseAmount);
$formulePriceLabel = is_array($selectedPackage)
    ? (string) ($selectedPackage['price_label'] ?? $service['price'])
    : (string) $service['price'];
?>
<div class="fiche-page">
  <div class="search-crumb">Prestations · <?= e((string) ($service['cat'] ?: 'Offre')) ?><?php if (!empty($service['specialty'])): ?> · <?= e((string) $service['specialty']) ?><?php endif; ?></div>
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
      <div class="service-excerpt"><?= rich_html(
          (string) ($service['excerpt'] ?? ''),
          '<p>Prestation proposée par ' . e((string) $service['by']) . '.</p>'
      ) ?></div>
      <div class="facts">
        <div><span>Prix TTC</span><strong><?= e((string) $service['price']) ?></strong></div>
        <div><span>Délai</span><strong><?= e((string) ($service['delay'] ?: 'à convenir')) ?></strong></div>
        <div><span>Métier</span><strong><?= e((string) ($service['cat'] ? \Adl\Data\Catalog::tradeTitle((string) $service['cat']) : '—')) ?></strong></div>
        <?php if (!empty($service['specialty'])): ?>
          <div><span>Spécialité</span><strong><?= e((string) $service['specialty']) ?></strong></div>
        <?php endif; ?>
        <div><span>Avis</span><strong><?= $service['reviews'] > 0 ? e((string) $service['rating']) . ' · ' . (int) $service['reviews'] : 'Pas encore d\'avis' ?></strong></div>
      </div>

      <?php if ($packages !== []): ?>
        <h2>Formules</h2>
        <div class="my-missions">
          <?php foreach ($packages as $package): ?>
            <article class="side-card">
              <div class="mission-row-title"><?= e((string) $package['name']) ?></div>
              <?php if (!empty($package['description'])): ?>
                <p class="mission-row-sub"><?= e((string) $package['description']) ?></p>
              <?php endif; ?>
              <div class="side-foot">
                <span><?= e((string) ($package['delay'] ?: 'Délai à convenir')) ?></span>
                <strong><?= e((string) $package['price_label']) ?></strong>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($options !== []): ?>
        <h2>Options</h2>
        <p class="field-help" style="margin-top: 0;">Leur prix s'ajoute à la formule choisie, ou au prix de base s'il n'y a pas de formule. Vous les cochez dans le panneau Commander.</p>
        <div class="my-missions">
          <?php foreach ($options as $option): ?>
            <article class="side-card">
              <div class="side-foot" style="margin-top: 0; border-top: 0; padding-top: 0;">
                <span><?= e((string) ($option['name'] ?? '')) ?></span>
                <strong>+<?= e((string) ($option['price_label'] ?? format_euros_ttc((int) ($option['price'] ?? 0)))) ?></strong>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <aside class="publish-side fiche-side" data-order-total data-base="<?= (int) $baseAmount ?>" data-order-href="<?= e(url($orderHref)) ?>">
      <div class="side-card fiche-order">
        <?php if (count($packages) > 1): ?>
          <div class="fiche-formule-tabs" role="tablist" aria-label="Formules">
            <?php foreach ($packages as $index => $package): ?>
              <button type="button" class="fiche-formule-tab<?= $index === 0 ? ' is-on' : '' ?>"
                      role="tab" aria-selected="<?= $index === 0 ? 'true' : 'false' ?>"
                      data-order-formule
                      data-id="<?= (int) ($package['id'] ?? 0) ?>"
                      data-price="<?= (int) ($package['price'] ?? 0) ?>"
                      data-name="<?= e((string) ($package['name'] ?? '')) ?>"
                      data-delay="<?= e((string) ($package['delay'] ?? '')) ?>"
                      data-desc="<?= e((string) ($package['description'] ?? '')) ?>"><?= e((string) ($package['name'] ?? 'Formule')) ?></button>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <div class="fiche-order-body">
          <div class="fiche-order-price-row">
            <span data-order-formule-name><?= e((string) ($selectedPackage['name'] ?? 'Prestation')) ?></span>
            <strong class="fiche-order-price" data-order-formule-price><?= e($formulePriceLabel) ?></strong>
          </div>
          <?php
            $formuleDesc = (string) ($selectedPackage['description'] ?? '');
            $formuleDelay = (string) ($selectedPackage['delay'] ?? $service['delay'] ?? '');
          ?>
          <p class="mission-row-sub" data-order-formule-desc<?= $formuleDesc === '' ? ' hidden' : '' ?>><?= e($formuleDesc) ?></p>
          <div class="fiche-order-meta">
            <span data-order-formule-delay><?= e($formuleDelay !== '' ? $formuleDelay : 'Délai à convenir') ?></span>
          </div>
          <?php if ($options !== []): ?>
            <div class="fiche-order-options">
              <div class="side-title-sm">Options</div>
              <div class="option-list">
                <?php foreach ($options as $option): ?>
                  <?php $optionId = (int) ($option['id'] ?? 0); ?>
                  <label class="option-row">
                    <input type="checkbox" name="options[]" value="<?= $optionId ?>"
                           data-price="<?= (int) ($option['price'] ?? 0) ?>"
                           <?= $isOwner ? ' disabled' : '' ?>>
                    <span><?= e((string) ($option['name'] ?? '')) ?></span>
                    <strong>+<?= e((string) ($option['price_label'] ?? format_euros_ttc((int) ($option['price'] ?? 0)))) ?></strong>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
          <div class="fiche-order-total">
            <span>Total TTC</span>
            <strong data-order-total-value><?= e(format_euros_ttc($baseAmount)) ?></strong>
          </div>
          <?php if ($canOrder): ?>
            <a class="btn-orange" data-order-cta data-order-cta-label href="<?= e(url($orderHref)) ?>"><?= e($ctaLabel) ?></a>
            <form method="post" action="<?= e(url('/espace/messages')) ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="avec" value="<?= (int) ($service['user_id'] ?? 0) ?>">
              <input type="hidden" name="sujet" value="<?= e((string) $service['title']) ?>">
              <input type="hidden" name="prestation" value="<?= (int) ($service['id'] ?? 0) ?>">
              <button class="btn-ghost" type="submit">Envoyer un message</button>
            </form>
            <p class="field-help" style="margin: 12px 0 0;">Aucun paiement ici : le prestataire envoie un devis, vous vous réglez hors plateforme, jalon par jalon.</p>
          <?php else: ?>
            <p class="field-help" style="margin: 12px 0 0;">C’est votre prestation. Connectez-vous avec un autre compte pour tester une commande.</p>
          <?php endif; ?>
        </div>
      </div>
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
          <strong data-order-total-value><?= e(format_euros_ttc($baseAmount)) ?></strong>
          <a class="btn-orange" data-order-cta data-order-cta-label href="<?= e(url($orderHref)) ?>"><?= e($ctaLabel) ?></a>
        </div>
      <?php endif; ?>
    </aside>
  </div>
</div>
