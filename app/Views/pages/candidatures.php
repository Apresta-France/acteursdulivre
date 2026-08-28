<?php $applications = $applications ?? []; ?>
<div class="espace-page">
  <div class="espace-page-head">
    <div>
      <h1>Mes candidatures</h1>
      <p><?= count($applications) ?> devis envoyé<?= count($applications) > 1 ? 's' : '' ?>.</p>
    </div>
    <a class="btn-navy" href="<?= e(url('/missions')) ?>">Voir les appels d'offres</a>
  </div>

  <?php if ($applications === []): ?>
    <div class="search-empty">
      <strong>Vous n'avez pas encore candidaté.</strong>
      <span>Parcourez les missions ouvertes et envoyez un devis : aucune commission sur la candidature.</span>
      <a class="btn-orange" href="<?= e(url('/missions')) ?>">Parcourir les missions</a>
    </div>
  <?php else: ?>
    <div class="my-missions">
      <?php foreach ($applications as $app): ?>
        <article class="side-card">
          <div class="mission-row-title">
            <?= e((string) $app['title']) ?>
            <span class="status-pill status-<?= e((string) $app['status_tone']) ?>"><?= e((string) $app['status_label']) ?></span>
          </div>
          <div class="mission-row-sub">
            <?= e((string) $app['by']) ?>
            · <?= e((string) ($app['category_name'] ?? '')) ?>
            · <?= e((string) $app['when']) ?>
          </div>
          <div class="side-foot">
            <span><?= e((string) ($app['delay'] ?: 'Délai à convenir')) ?></span>
            <strong><?= e((string) $app['price']) ?></strong>
          </div>
          <div class="auth-actions" style="margin-top: 14px;">
            <a class="btn-ghost" href="<?= e(url((string) $app['href'])) ?>">Voir la mission</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
