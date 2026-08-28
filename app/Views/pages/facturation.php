<?php
$orders = $orders ?? [];
$invoices = $invoices ?? [];
$total = (int) ($totalAmount ?? 0);
$due = (int) ($dueAmount ?? 0);
?>
<div class="espace-page">
  <div class="espace-page-head">
    <div>
      <h1>Facturation</h1>
      <p><?= count($invoices) ?> facture<?= count($invoices) > 1 ? 's' : '' ?> de commission · <?= e(format_euros($due)) ?> en attente · <?= e(format_euros($total)) ?> de missions.</p>
    </div>
  </div>

  <?php require ADL_ROOT . '/app/Views/partials/billing-banner.php'; ?>

  <?php if ($invoices !== []): ?>
    <h2 class="espace-section-title">Factures de commission</h2>
    <div class="my-missions">
      <?php foreach ($invoices as $invoice): ?>
        <article class="side-card">
          <div class="mission-row-title">
            <?= e((string) $invoice['number']) ?>
            <span class="status-pill status-<?= e((string) ($invoice['is_overdue'] ? 'overdue' : $invoice['status'])) ?>"><?= e((string) $invoice['status_label']) ?></span>
          </div>
          <div class="mission-row-sub">
            Commande <?= e((string) ($invoice['order_number'] ?? '')) ?>
            · émise le <?= e((string) $invoice['issued_label']) ?>
            <?php if (!empty($invoice['due_label']) && !empty($invoice['is_open'])): ?>
              · à régler avant le <?= e((string) $invoice['due_label']) ?>
            <?php endif; ?>
          </div>
          <div class="side-foot">
            <span><?= (int) ($invoice['amount'] ?? 0) === 0 ? 'Première mission offerte' : rtrim(rtrim((string) ($invoice['commission_percent'] ?? '8'), '0'), '.') . ' %' ?></span>
            <strong><?= e((string) $invoice['amount_label']) ?></strong>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <h2 class="espace-section-title">Missions réalisées</h2>
  <?php if ($orders === []): ?>
    <div class="search-empty">
      <strong>Aucun encaissement pour le moment.</strong>
      <span>Quand un client confirmera une mission et la notera, la facture de commission apparaîtra ici. La première mission est offerte.</span>
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
            <span>Montant mission<?= !empty($order['commission_label']) ? ' · commission ' . e((string) $order['commission_label']) : '' ?></span>
            <strong><?= e((string) $order['amount_label']) ?></strong>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
