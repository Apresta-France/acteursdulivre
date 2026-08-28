<?php
$orders = $orders ?? [];
$total = (int) ($totalAmount ?? 0);
?>
<div class="espace-page">
  <div class="espace-page-head">
    <div>
      <h1>Facturation</h1>
      <p><?= count($orders) ?> commande<?= count($orders) > 1 ? 's' : '' ?> · <?= e(format_euros($total)) ?> au total.</p>
    </div>
  </div>

  <?php if ($orders === []): ?>
    <div class="search-empty">
      <strong>Aucun encaissement pour le moment.</strong>
      <span>Les commandes livrées et les relevés de commission apparaîtront ici.</span>
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
            <?= e((string) $order['num']) ?> · <?= e((string) $order['parties']) ?> · <?= e((string) $order['when']) ?>
          </div>
          <div class="side-foot">
            <span>Montant</span>
            <strong><?= e((string) $order['amount_label']) ?></strong>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
