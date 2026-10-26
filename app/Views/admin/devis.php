<?php
$kpis = $kpis ?? [];
$orders = $orders ?? [];
?>
<div class="admin-page">
  <h1>Devis</h1>
  <p class="admin-lead">Suivi du cycle devis : demande guidée du client, réponse du prestataire, révisions et acceptation.</p>
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

  <div class="chip-row" style="margin-bottom: 14px;">
    <?php foreach ($quoteFilters ?? [] as $f): ?>
      <a class="chip<?= !empty($f['on']) ? ' is-on' : '' ?>" href="<?= e(url($f['href'])) ?>"><?= e($f['label']) ?></a>
    <?php endforeach; ?>
  </div>
  <p class="admin-muted">Cliquez une ligne pour ouvrir le dossier complet (brief, échanges, jalons).</p>
  <div class="r-scroll">
    <table class="table">
      <thead><tr><th>N°</th><th>Objet</th><th>Parties</th><th>Devis</th><th>Délai annoncé</th><th>État</th><th>Qui doit agir</th><th>Dernier mouvement</th><th></th></tr></thead>
      <tbody>
        <?php if ($orders === []): ?>
          <tr><td colspan="9" class="admin-muted">Aucune commande dans cet état.</td></tr>
        <?php endif; ?>
        <?php foreach ($orders as $o): ?>
          <?php
          $dossier = (string) ($o['admin_href'] ?? '/admin/finances/' . (int) $o['id']);
          $qs = is_array($o['quote_state'] ?? null) ? $o['quote_state'] : [];
          ?>
          <tr>
            <td><a class="admin-order-link" href="<?= e(url($dossier)) ?>"><?= e($o['num']) ?></a></td>
            <td><a class="admin-order-link" href="<?= e(url($dossier)) ?>"><?= e($o['title']) ?></a></td>
            <td><?= e($o['parties']) ?></td>
            <td><?= e((string) ($qs['amount_label'] ?? '') ?: '—') ?></td>
            <td><?= e((string) ($qs['delay'] ?? '') ?: '—') ?></td>
            <td><span class="admin-pill tone-<?= e((string) ($qs['tone'] ?? 'grey')) ?>"><?= e((string) ($qs['label'] ?? '')) ?></span></td>
            <td><?= e((string) ($qs['actor'] ?? '—')) ?></td>
            <td><?= e((string) ($qs['when'] ?? '') ?: '—') ?></td>
            <td><a class="admin-ghost" href="<?= e(url($dossier)) ?>">Ouvrir</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
