<?php
$trade = (string) ($trade ?? '');
$label = (string) ($tradeLabel ?? $trade);
$providers = $providers ?? [];
$services = $services ?? [];
$missions = $missions ?? [];
?>
<div class="metier-page">
  <div class="search-crumb">Métiers · <?= e($trade) ?></div>
  <h1><?= e($label) ?></h1>
  <p class="journal-lead">Prestataires, prestations à prix affiché et missions ouvertes pour le métier « <?= e($trade) ?> ».</p>

  <div class="metier-sections">
    <section>
      <div class="espace-page-head">
        <h2>Prestataires</h2>
        <a href="<?= e(url('/recherche?type=prestataires&cat=' . rawurlencode($trade))) ?>">Tout voir</a>
      </div>
      <?php if ($providers === []): ?>
        <div class="search-empty">
          <strong>Aucun profil publié pour ce métier.</strong>
          <span>Les prestataires qui renseignent « <?= e($trade) ?> » dans leur vitrine apparaîtront ici.</span>
        </div>
      <?php else: ?>
        <div class="my-missions">
          <?php foreach ($providers as $p): ?>
            <a class="side-card" href="<?= e(url((string) $p['href'])) ?>">
              <div class="mission-row-title"><?= e((string) $p['title']) ?></div>
              <div class="mission-row-sub"><?= e((string) $p['subtitle']) ?></div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <section>
      <div class="espace-page-head">
        <h2>Prestations</h2>
        <a href="<?= e(url('/recherche?type=prestations&cat=' . rawurlencode($trade))) ?>">Tout voir</a>
      </div>
      <?php if ($services === []): ?>
        <div class="search-empty">
          <strong>Aucune prestation publiée.</strong>
          <span>Les offres packagées de ce métier apparaîtront ici.</span>
        </div>
      <?php else: ?>
        <div class="my-missions">
          <?php foreach ($services as $s): ?>
            <a class="side-card" href="<?= e(url((string) $s['href'])) ?>">
              <div class="mission-row-title"><?= e((string) $s['title']) ?></div>
              <div class="mission-row-sub"><?= e((string) $s['subtitle']) ?> · <?= e((string) $s['price']) ?></div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <section>
      <div class="espace-page-head">
        <h2>Recherches ouvertes</h2>
        <a href="<?= e(url('/missions?cat=' . rawurlencode($trade))) ?>">Tout voir</a>
      </div>
      <?php if ($missions === []): ?>
        <div class="search-empty">
          <strong>Aucune recherche ouverte.</strong>
          <span>Publiez un appel d'offres pour recevoir des devis.</span>
          <a class="btn-ghost" href="<?= e(url('/espace/publier')) ?>">Publier une recherche</a>
        </div>
      <?php else: ?>
        <div class="my-missions">
          <?php foreach ($missions as $m): ?>
            <a class="side-card" href="<?= e(url((string) $m['href'])) ?>">
              <div class="mission-row-title"><?= e((string) $m['title']) ?></div>
              <div class="mission-row-sub"><?= e((string) $m['subtitle']) ?> · <?= e((string) $m['price']) ?></div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>
</div>
