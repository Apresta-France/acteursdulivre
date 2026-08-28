<?php
$service = $service ?? null;
if (!$service) {
    not_found('Cette prestation n\'est plus disponible.');
}
$packages = $service['packages'] ?? [];
?>
<div class="fiche-page">
  <div class="search-crumb">Prestations · <?= e((string) ($service['cat'] ?: 'Offre')) ?></div>
  <div class="publish-grid">
    <div>
      <h1><?= e((string) $service['title']) ?></h1>
      <p class="journal-lead"><?= e((string) ($service['excerpt'] ?: 'Prestation proposée par ' . $service['by'] . '.')) ?></p>
      <div class="facts">
        <div><span>Prix</span><strong><?= e((string) $service['price']) ?></strong></div>
        <div><span>Délai</span><strong><?= e((string) ($service['delay'] ?: 'à convenir')) ?></strong></div>
        <div><span>Métier</span><strong><?= e((string) ($service['cat'] ?: '—')) ?></strong></div>
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
    </div>
    <aside class="publish-side">
      <div class="side-card">
        <div class="side-kicker">Le prestataire</div>
        <div class="suggest-row" style="padding: 0;">
          <span class="avatar" style="<?= e(avatar_style((string) $service['initials'], 46)) ?>"><?= e((string) $service['initials']) ?></span>
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
      </div>
    </aside>
  </div>
</div>
