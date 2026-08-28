<?php
$kpis = $kpis ?? [];
$couverture = $couverture ?? [];
?>
<div class="admin-page">
  <h1>Pré-ouverture</h1>
  <p class="admin-lead">Ouverture aux clients prévue en octobre 2026. Les inscriptions prestataires sont déjà ouvertes. Il n’y a pas de liste d’attente séparée : les comptes inscrits sont les vrais chiffres.</p>

  <div class="admin-kpi-row">
    <?php foreach ($kpis as $k): ?>
      <div class="admin-kpi">
        <div class="admin-kpi-k"><?= e($k['k']) ?></div>
        <div class="admin-kpi-v"><?= e((string) $k['v']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="admin-card">
    <h2>Couverture par métier</h2>
    <?php if ($couverture === []): ?>
      <p class="admin-muted">Aucun profil publié pour l’instant.</p>
    <?php endif; ?>
    <div class="admin-bars">
      <?php foreach ($couverture as $c): ?>
        <div class="admin-bar-row">
          <span><?= e($c['metier']) ?></span>
          <div class="admin-bar"><i style="width: <?= (int) $c['pct'] ?>%"></i></div>
          <em><?= format_int((int) $c['n']) ?></em>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
