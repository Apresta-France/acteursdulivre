<?php $orders = $orders ?? []; ?>
<div class="espace-page">
  <div class="espace-page-head">
    <div>
      <h1>Mes commandes</h1>
      <p><?= count($orders) ?> commande<?= count($orders) > 1 ? 's' : '' ?> · suivi à jalons, règlement hors plateforme.</p>
    </div>
    <a class="btn-navy" href="<?= e(url('/prestations')) ?>">Trouver une prestation</a>
  </div>

  <?php if ($orders === []): ?>
    <div class="search-empty">
      <strong>Aucune commande pour le moment.</strong>
      <span>Les missions attribuées apparaîtront ici, avec leur suivi à jalons. Le règlement se fait hors plateforme.</span>
      <a class="btn-orange" href="<?= e(url('/recherche')) ?>">Parcourir l'annuaire</a>
    </div>
  <?php else: ?>
    <div class="my-missions">
      <?php foreach ($orders as $order): ?>
        <article class="side-card">
          <div class="mission-row-title">
            <?= e((string) $order['title']) ?>
            <span class="status-pill status-<?= e((string) ($order['status_tone'] ?? $order['status'])) ?>"><?= e((string) $order['status_label']) ?></span>
          </div>
          <div class="mission-row-sub">
            <?= e((string) $order['num']) ?>
            · <?= e((string) $order['by']) ?>
            · <?= e((string) $order['when']) ?>
          </div>
          <div class="side-foot">
            <span><?= e((string) (($order['next_jalon_label'] ?? '') !== '' ? $order['next_jalon_label'] : $order['status_label'])) ?></span>
            <strong><?= e((string) $order['amount_label']) ?></strong>
          </div>
          <div class="auth-actions" style="margin-top: 14px;">
            <a class="btn-ghost" href="<?= e(url((string) ($order['href'] ?? '/espace/suivi'))) ?>">Suivre</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
