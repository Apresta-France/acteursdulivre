<?php
$clientLandings = $clientLandings ?? [];
$offererLandings = $offererLandings ?? [];
?>
<div class="lp-page lp-hub">
  <section class="mk-intro">
    <p class="mk-kicker">Trouver un prestataire</p>
    <h1>De quoi votre livre a-t-il besoin ?</h1>
    <p class="mk-lead">Une page par besoin, pour aller droit au métier : correction, couverture, impression, traduction… Inscription gratuite, travail humain, règlement hors plateforme.</p>
  </section>

  <section class="lp-hub-grid">
    <?php foreach ($clientLandings as $item): ?>
      <a class="lp-hub-card" href="<?= e(url((string) $item['href'])) ?>">
        <span class="mk-kicker"><?= e((string) $item['kicker']) ?></span>
        <strong><?= e((string) $item['need']) ?></strong>
        <p><?= e((string) $item['lead']) ?></p>
        <?php if (!empty($item['price'])): ?>
          <em><?= e((string) $item['price']) ?></em>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
  </section>

  <?php if ($offererLandings !== []): ?>
    <section class="mk-cta">
      <?php $off = $offererLandings[0]; ?>
      <div>
        <p class="mk-kicker">Vous exercez un métier du livre</p>
        <h2><?= e((string) $off['h1']) ?></h2>
        <p><?= e((string) $off['lead']) ?></p>
      </div>
      <div class="mk-cta-actions">
        <a class="btn-orange" href="<?= e(url((string) $off['href'])) ?>"><?= e((string) ($off['cta_primary'] ?? 'Créer ma vitrine')) ?></a>
        <a class="btn-ghost" href="<?= e(url('/missions')) ?>">Voir les recherches</a>
      </div>
    </section>
  <?php endif; ?>
</div>
