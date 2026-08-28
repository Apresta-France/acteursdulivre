<?php
$kpis = $kpis ?? [];
$chart = $chart ?? [];
$files = $files ?? [];
$activity = $activity ?? [];
?>
<div class="admin-page">
  <h1>Tableau de bord</h1>
  <p class="admin-lead"><?= e($dashSubtitle ?? 'Pilotage de la plateforme') ?></p>

  <div class="admin-kpi-row">
    <?php foreach ($kpis as $k): ?>
      <div class="admin-kpi">
        <div class="admin-kpi-k"><?= e($k['k']) ?></div>
        <div class="admin-kpi-v"><?= e((string) $k['v']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="admin-dash-grid">
    <div class="admin-card">
      <h2>Inscriptions · 8 semaines</h2>
      <?php if ($chart === []): ?>
        <p class="admin-muted">Pas encore d’historique.</p>
      <?php else: ?>
        <div class="admin-chart">
          <?php foreach ($chart as $c): ?>
            <div class="admin-chart-col">
              <div class="admin-chart-bars">
                <?php if (!empty($c['pendingH'])): ?><span class="is-pending" style="height: <?= (int) $c['pendingH'] ?>px"></span><?php endif; ?>
                <?php if (!empty($c['okH'])): ?><span class="is-ok" style="height: <?= (int) $c['okH'] ?>px"></span><?php endif; ?>
              </div>
              <em><?= e($c['label']) ?></em>
            </div>
          <?php endforeach; ?>
        </div>
        <p class="admin-muted" style="margin-top: 10px;">Bleu : comptes actifs · Orange : en attente ou suspendus</p>
      <?php endif; ?>
    </div>

    <div class="admin-card">
      <h2>Activité récente</h2>
      <?php if ($activity === []): ?>
        <p class="admin-muted">Aucune activité récente.</p>
      <?php endif; ?>
      <ul class="admin-activity">
        <?php foreach ($activity as $a): ?>
          <li>
            <strong><?= e($a['txt']) ?></strong>
            <span><?= e($a['meta'] ?? '') ?> · <?= e(time_ago($a['when'] ?? null) ?: admin_date($a['when'] ?? null)) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>

  <div class="admin-card" style="margin-top: 18px;">
    <h2>Files à traiter</h2>
    <div class="admin-files">
      <?php foreach ($files as $f): ?>
        <a class="admin-file" href="<?= e(url($f['href'])) ?>">
          <div>
            <strong><?= e($f['label']) ?></strong>
            <span><?= e($f['note'] ?? '') ?></span>
          </div>
          <em><?= (int) $f['n'] ?></em>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
