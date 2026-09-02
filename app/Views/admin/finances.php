<?php
$kpis = $kpis ?? [];
$orders = $orders ?? [];
$invoices = $invoices ?? [];
?>
<div class="admin-page">
  <h1>Commandes &amp; finances</h1>
  <p class="admin-lead"><?= e($financesSubtitle ?? 'Montants hors taxes. La plateforme n’encaisse pas le prix des missions : seules les factures de commission au prestataire figurent ici.') ?></p>
  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <div class="admin-kpi-row">
    <?php foreach ($kpis as $k): ?>
      <div class="admin-kpi">
        <div class="admin-kpi-k"><?= e($k['k']) ?></div>
        <div class="admin-kpi-v"><?= e((string) $k['v']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <h2 class="admin-h2">Commandes</h2>
  <div class="chip-row" style="margin-bottom: 14px;">
    <?php foreach ($orderFilters ?? [] as $f): ?>
      <a class="chip<?= !empty($f['on']) ? ' is-on' : '' ?>" href="<?= e(url($f['href'])) ?>"><?= e($f['label']) ?></a>
    <?php endforeach; ?>
  </div>
  <?php if (!empty($ordersTruncated)): ?>
    <p class="admin-muted">Les <?= count($orders) ?> dernières sur <?= format_int((int) ($orderTotal ?? 0)) ?> commandes. Cliquez une ligne pour voir le dossier, les échanges, changer le statut ou supprimer.</p>
  <?php else: ?>
    <p class="admin-muted">Cliquez une commande pour voir le détail, les échanges, changer le statut ou la supprimer.</p>
  <?php endif; ?>
  <div class="r-scroll">
    <table class="table">
      <thead><tr><th>N°</th><th>Objet</th><th>Parties</th><th>Montant</th><th>Commission</th><th>Statut</th><th></th></tr></thead>
      <tbody>
        <?php if ($orders === []): ?>
          <tr><td colspan="7" class="admin-muted">Aucune commande.</td></tr>
        <?php endif; ?>
        <?php foreach ($orders as $o): ?>
          <?php $dossier = (string) ($o['admin_href'] ?? '/admin/finances/' . (int) $o['id']); ?>
          <tr>
            <td><a class="admin-order-link" href="<?= e(url($dossier)) ?>"><?= e($o['num']) ?></a></td>
            <td><a class="admin-order-link" href="<?= e(url($dossier)) ?>"><?= e($o['title']) ?></a></td>
            <td><?= e($o['parties']) ?></td>
            <td><?= e($o['amount_label']) ?></td>
            <td><?= e($o['commission_label'] ?: '—') ?></td>
            <td><span class="admin-pill tone-<?= e((string) ($o['status_tone'] ?? 'navy')) ?>"><?= e($o['status_label']) ?></span></td>
            <td>
              <a class="admin-ghost" href="<?= e(url($dossier)) ?>">Ouvrir</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <h2 class="admin-h2">Factures de commission</h2>
  <div class="r-scroll">
    <table class="table">
      <thead><tr><th>N°</th><th>Prestataire</th><th>Commande</th><th>Montant</th><th>Échéance</th><th>Statut</th><th></th></tr></thead>
      <tbody>
        <?php if ($invoices === []): ?>
          <tr><td colspan="7" class="admin-muted">Aucune facture.</td></tr>
        <?php endif; ?>
        <?php foreach ($invoices as $i): ?>
          <tr>
            <td><?= e($i['number'] ?? '') ?></td>
            <td><?= e($i['seller'] ?: '—') ?></td>
            <td>
              <?php if (!empty($i['order_id'])): ?>
                <a class="admin-order-link" href="<?= e(url('/admin/finances/' . (int) $i['order_id'])) ?>"><?= e($i['order_number'] ?? '') ?></a>
              <?php else: ?>
                <?= e($i['order_number'] ?? '') ?>
              <?php endif; ?>
            </td>
            <td><?= e((string) ($i['amount_due_label'] ?? $i['amount_label'])) ?></td>
            <td><?= e($i['due_label'] ?: '—') ?></td>
            <td><?= e($i['status_label']) ?></td>
            <td>
              <?php if (!empty($i['is_open'])): ?>
                <form method="post" action="<?= e(url('/admin/finances/factures/' . (int) $i['id'])) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="paid">
                  <button class="btn-navy" type="submit">Marquer réglée</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
