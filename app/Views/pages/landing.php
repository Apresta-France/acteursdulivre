<?php
$lp = is_array($landing ?? null) ? $landing : [];
$trade = (string) ($lp['trade'] ?? '');
$h1 = (string) ($lp['h1'] ?? $title ?? '');
$lead = (string) ($lp['lead'] ?? '');
$kicker = (string) ($lp['kicker'] ?? '');
$price = (string) ($lp['price'] ?? '');
$priceDetail = (string) ($lp['price_detail'] ?? '');
$priceNote = (string) ($lp['price_note'] ?? '');
$includes = $lp['includes'] ?? [];
$excludes = $lp['excludes'] ?? [];
$steps = $lp['steps'] ?? [];
$proofs = $lp['proofs'] ?? [];
$faq = $lp['faq'] ?? [];
$others = $lp['others'] ?? [];
$journal = is_array($lp['journal_page'] ?? null) ? $lp['journal_page'] : null;
$primaryHref = (string) ($lp['primary_href'] ?? '/inscription');
$secondaryHref = (string) ($lp['secondary_href'] ?? '/prestataires');
$primaryLabel = (string) ($lp['primary_label'] ?? 'Créer un compte');
$secondaryLabel = (string) ($lp['secondary_label'] ?? 'Voir les prestataires');
$heroImg = (string) ($lp['hero_img'] ?? photo(0));
$providerCount = (int) ($providerCount ?? 0);
$serviceCount = (int) ($serviceCount ?? 0);
$missionCount = (int) ($missionCount ?? 0);
$providers = $providers ?? [];
$services = $services ?? [];
$audience = (string) ($lp['audience'] ?? 'client');
$isOfferer = $audience === 'prestataire';
?>
<div class="lp-page">
  <section class="lp-hero">
    <div class="lp-hero-copy">
      <nav class="search-crumb" aria-label="Fil d'Ariane">
        <a href="<?= e(url('/')) ?>">Accueil</a>
        · <a href="<?= e(url(\Adl\Data\Landings::hubPath())) ?>">Par besoin</a>
        · <?= e((string) ($lp['need'] ?? $h1)) ?>
      </nav>
      <?php if ($kicker !== ''): ?>
        <p class="mk-kicker"><?= e($kicker) ?></p>
      <?php endif; ?>
      <h1><?= e($h1) ?></h1>
      <p class="mk-lead"><?= e($lead) ?></p>
      <div class="lp-hero-actions">
        <a class="btn-orange" href="<?= e(url($primaryHref)) ?>"><?= e($primaryLabel) ?></a>
        <a class="btn-ghost" href="<?= e(url($secondaryHref)) ?>"><?= e($secondaryLabel) ?></a>
      </div>
      <?php if ($providerCount > 0 || $serviceCount > 0 || $missionCount > 0): ?>
        <div class="lp-stats" role="group" aria-label="Chiffres de ce besoin">
          <?php if (!$isOfferer && $providerCount > 0): ?>
            <a href="<?= e(url((string) ($lp['prestataires_href'] ?? '/prestataires'))) ?>">
              <strong><?= (int) $providerCount ?></strong>
              <span><?= $providerCount > 1 ? 'prestataires' : 'prestataire' ?></span>
            </a>
          <?php endif; ?>
          <?php if ($serviceCount > 0): ?>
            <a href="<?= e(url((string) ($lp['prestations_href'] ?? '/prestations'))) ?>">
              <strong><?= (int) $serviceCount ?></strong>
              <span><?= $serviceCount > 1 ? 'prestations' : 'prestation' ?></span>
            </a>
          <?php endif; ?>
          <?php if ($missionCount > 0): ?>
            <a href="<?= e(url((string) ($lp['missions_href'] ?? '/missions'))) ?>">
              <strong><?= (int) $missionCount ?></strong>
              <span><?= $missionCount > 1 ? 'recherches ouvertes' : 'recherche ouverte' ?></span>
            </a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
    <div class="lp-hero-visual" aria-hidden="true">
      <img src="<?= e($heroImg) ?>" alt="" width="480" height="340" fetchpriority="high" decoding="async">
    </div>
  </section>

  <?php if ($price !== ''): ?>
    <section class="lp-price">
      <div>
        <p class="mk-kicker">Ordre de grandeur</p>
        <p class="lp-price-value"><?= e($price) ?></p>
        <?php if ($priceDetail !== ''): ?>
          <p><?= e($priceDetail) ?></p>
        <?php endif; ?>
        <?php if ($priceNote !== ''): ?>
          <p class="lp-price-note"><?= e($priceNote) ?></p>
        <?php endif; ?>
      </div>
      <?php if ($trade !== ''): ?>
        <a class="btn-ghost" href="<?= e(url((string) ($lp['trade_path'] ?? '/recherche'))) ?>">Page métier <?= e($trade) ?> →</a>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <section class="mk-steps lp-steps">
    <?php foreach ($steps as $s): ?>
      <div>
        <div class="mk-kicker"><?= e((string) ($s['num'] ?? '')) ?></div>
        <h3><?= e((string) ($s['title'] ?? '')) ?></h3>
        <p><?= e((string) ($s['body'] ?? '')) ?></p>
      </div>
    <?php endforeach; ?>
  </section>

  <?php if ($includes !== [] || $excludes !== []): ?>
    <section class="lp-split">
      <?php if ($includes !== []): ?>
        <div>
          <h2>Ce que vous achetez</h2>
          <ul class="mk-points">
            <?php foreach ($includes as $item): ?>
              <li><?= e((string) $item) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
      <?php if ($excludes !== []): ?>
        <div>
          <h2>Ce que ce n’est pas</h2>
          <ul class="mk-points lp-excludes">
            <?php foreach ($excludes as $item): ?>
              <li><?= e((string) $item) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <?php if ($proofs !== []): ?>
    <section class="lp-proofs">
      <h2>Le cadre de la plateforme</h2>
      <ul>
        <?php foreach ($proofs as $p): ?>
          <li><?= e((string) $p) ?></li>
        <?php endforeach; ?>
      </ul>
      <p><a href="<?= e(url('/comment-ca-marche')) ?>">Comment ça marche</a> · <a href="<?= e(url('/tarifs')) ?>">Tarifs</a> · <a href="<?= e(url('/confiance')) ?>">Confiance</a> · <a href="<?= e(url('/regles-ia')) ?>">Règles IA</a></p>
    </section>
  <?php endif; ?>

  <?php if ($services !== [] || $providers !== []): ?>
    <section class="lp-live">
      <?php if ($services !== []): ?>
        <div class="mk-head">
          <h2>Prestations à prix affiché</h2>
          <a href="<?= e(url((string) ($lp['prestations_href'] ?? '/prestations'))) ?>">Voir les offres →</a>
        </div>
        <div class="search-grid">
          <?php foreach ($services as $item): ?>
            <?= search_card_html($item) ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <?php if ($providers !== []): ?>
        <div class="mk-head">
          <h2><?= $isOfferer ? 'Profils déjà en ligne' : 'Quelques prestataires' ?></h2>
          <a href="<?= e(url((string) ($lp['prestataires_href'] ?? '/prestataires'))) ?>">Voir l’annuaire →</a>
        </div>
        <div class="search-grid">
          <?php foreach ($providers as $item): ?>
            <?= search_card_html($item) ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <?php if ($faq !== []): ?>
    <section class="mk-block">
      <h2>Questions fréquentes</h2>
      <div class="mk-faq-list mk-faq-narrow">
        <?php foreach ($faq as $i => $f): ?>
          <div>
            <button type="button" data-accordion aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>" class="faq-q">
              <?= e((string) ($f['q'] ?? '')) ?><span data-accordion-sign><?= $i === 0 ? '−' : '+' ?></span>
            </button>
            <div <?= $i === 0 ? '' : 'hidden' ?> class="mk-faq-a"><?= e((string) ($f['a'] ?? '')) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($journal): ?>
    <section class="lp-journal">
      <p class="mk-kicker">Pour cadrer le budget</p>
      <h2><a href="<?= e(url((string) $journal['href'])) ?>"><?= e((string) $journal['title']) ?></a></h2>
      <?php if (!empty($journal['excerpt'])): ?>
        <p><?= e((string) $journal['excerpt']) ?></p>
      <?php endif; ?>
      <a href="<?= e(url((string) $journal['href'])) ?>">Lire l’article →</a>
    </section>
  <?php endif; ?>

  <section class="mk-cta">
    <div>
      <h2><?= $isOfferer ? 'Créer votre vitrine' : 'Passer à l’action' ?></h2>
      <p><?= $isOfferer
        ? 'Inscription gratuite. La première mission réalisée est offerte.'
        : 'Inscription gratuite. Comparez des profils ou publiez une recherche.' ?></p>
    </div>
    <div class="mk-cta-actions">
      <a class="btn-orange" href="<?= e(url($primaryHref)) ?>"><?= e($primaryLabel) ?></a>
      <a class="btn-ghost" href="<?= e(url($secondaryHref)) ?>"><?= e($secondaryLabel) ?></a>
    </div>
  </section>

  <?php if ($others !== []): ?>
    <section class="lp-others">
      <h2>Autres besoins</h2>
      <div class="lp-other-grid">
        <?php foreach ($others as $o): ?>
          <a href="<?= e(url((string) $o['href'])) ?>">
            <span><?= e((string) $o['kicker']) ?></span>
            <strong><?= e((string) $o['need']) ?></strong>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</div>

<div class="lp-sticky" data-lp-sticky>
  <a class="btn-orange" href="<?= e(url($primaryHref)) ?>"><?= e($primaryLabel) ?></a>
</div>
