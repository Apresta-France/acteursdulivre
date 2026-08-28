<?php $orders = $orders ?? []; ?>
<div class="espace-page">
  <div class="espace-page-head">
    <div>
      <h1>Suivi de commande</h1>
      <p>Jalons, livrables et validation une fois la mission attribuée.</p>
    </div>
    <a class="btn-navy" href="<?= e(url('/espace/commandes')) ?>">Mes commandes</a>
  </div>

  <?php if ($orders === []): ?>
    <div class="search-empty">
      <strong>Aucune commande en cours.</strong>
      <span>Le suivi (brief, livraison, validation) apparaîtra dès qu'une commande sera ouverte.</span>
    </div>
  <?php else: ?>
    <div class="my-missions">
      <?php foreach ($orders as $order): ?>
        <article class="side-card">
          <div class="mission-row-title">
            <?= e((string) $order['title']) ?>
            <span class="status-pill status-<?= e((string) $order['status']) ?>"><?= e((string) $order['status_label']) ?></span>
          </div>
          <div class="mission-row-sub">
            <?= e((string) $order['num']) ?> · <?= e((string) $order['by']) ?> · <?= e((string) $order['when']) ?>
          </div>
          <div class="side-foot">
            <span><?= e((string) $order['status_label']) ?></span>
            <strong><?= e((string) $order['amount_label']) ?></strong>
          </div>
          <div class="auth-actions" style="margin-top: 14px;">
            <a class="btn-ghost" href="<?= e(url((string) ($order['href'] ?? '/espace/suivi'))) ?>">Ouvrir le suivi</a>
            <?php if (!empty($order['can_confirm']) && (int) ($order['buyer_id'] ?? 0) === (int) (\Adl\Core\Auth::id() ?? 0)): ?>
              <a class="btn-orange" href="<?= e(url('/espace/avis')) ?>">Valider et noter</a>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
