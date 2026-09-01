<?php
$items = $items ?? [];
$pending = (int) ($pending ?? 0);
$applied = (int) ($applied ?? 0);
$missing = (int) ($missing ?? 0);
$total = (int) ($total ?? 0);
$upToDate = !empty($upToDate);
?>
<div class="admin-page">
  <div class="admin-page-head">
    <div>
      <h1>Migrations</h1>
      <p class="admin-lead" style="margin-bottom: 0;"><?= e($migrationsSubtitle ?? 'État du schéma de la base.') ?></p>
    </div>
    <?php if ($pending > 0): ?>
      <form method="post" action="<?= e(url('/admin/migrations')) ?>" onsubmit="return confirm('Appliquer les migrations en attente ?');">
        <?= csrf_field() ?>
        <button class="btn-orange" type="submit">Appliquer les migrations en attente</button>
      </form>
    <?php endif; ?>
  </div>

  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <div class="admin-mig-banner <?= $upToDate ? 'is-ok' : 'is-pending' ?>">
    <strong><?= $upToDate ? 'Base à jour' : 'Migrations en attente' ?></strong>
    <span>
      <?php if ($upToDate): ?>
        Tous les fichiers de <code>database/migrations</code> sont enregistrés dans la table <code>migrations</code>.
      <?php else: ?>
        <?= $pending > 1 ? $pending . ' fichiers n’ont pas encore été appliqués.' : '1 fichier n’a pas encore été appliqué.' ?>
      <?php endif; ?>
    </span>
  </div>

  <?php if ($missing > 0): ?>
    <div class="flash flash-warn"><?= $missing > 1 ? $missing . ' enregistrements' : '1 enregistrement' ?> dans la table sans fichier correspondant. Ils restent listés ci-dessous.</div>
  <?php endif; ?>

  <div class="admin-kpi-row">
    <div class="admin-kpi">
      <div class="admin-kpi-k">Fichiers</div>
      <div class="admin-kpi-v"><?= format_int($total) ?></div>
    </div>
    <div class="admin-kpi">
      <div class="admin-kpi-k">Appliquées</div>
      <div class="admin-kpi-v"><?= format_int($applied) ?></div>
    </div>
    <div class="admin-kpi">
      <div class="admin-kpi-k">En attente</div>
      <div class="admin-kpi-v"><?= format_int($pending) ?></div>
    </div>
    <?php if ($missing > 0): ?>
      <div class="admin-kpi">
        <div class="admin-kpi-k">Fichiers absents</div>
        <div class="admin-kpi-v"><?= format_int($missing) ?></div>
      </div>
    <?php endif; ?>
  </div>

  <div class="r-scroll">
    <table class="table">
      <thead>
        <tr>
          <th>Fichier</th>
          <th>Statut</th>
          <th>Appliquée le</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($items === []): ?>
          <tr><td colspan="3" class="admin-muted">Aucun fichier de migration.</td></tr>
        <?php endif; ?>
        <?php foreach ($items as $m): ?>
          <?php
            $status = (string) ($m['status'] ?? '');
            $tone = $status === 'applied' ? 'green' : ($status === 'pending' ? 'orange' : 'grey');
            $label = $status === 'applied' ? 'Appliquée' : ($status === 'pending' ? 'En attente' : 'Fichier absent');
            $at = $m['applied_at'] ?? null;
            $when = '—';
            if (is_string($at) && $at !== '') {
                $ts = strtotime($at);
                $when = $ts === false ? $at : date('d/m/Y à H:i', $ts);
            }
            $base = preg_replace('/\.php$/', '', (string) ($m['name'] ?? ''));
            $pretty = is_string($base) && preg_match('/^(\d+)_(.+)$/', $base, $parts) === 1
                ? $parts[1] . ' · ' . str_replace('_', ' ', $parts[2])
                : (string) ($m['name'] ?? '');
          ?>
          <tr class="<?= $status === 'pending' ? 'is-pending' : ($status === 'missing' ? 'is-missing' : '') ?>">
            <td>
              <strong><?= e($pretty) ?></strong>
              <div class="admin-sub"><?= e((string) ($m['name'] ?? '')) ?></div>
            </td>
            <td><span class="admin-pill tone-<?= e($tone) ?>"><?= e($label) ?></span></td>
            <td><?= e($when) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <p class="admin-muted" style="margin-top: 16px;">Les migrations ne s’appliquent pas automatiquement depuis l’administration. Sur le site public, elles sont encore lancées au chargement. Vous pouvez aussi exécuter <code>php bin/migrate.php</code>.</p>
</div>
