<?php

use Adl\Data\AdminCatalog;
use Adl\Models\HttpError;

$groups = $groups ?? [];
$filters = $erreursFilters ?? [];
$pager = $pager ?? ['page' => 1, 'pages' => 1, 'total' => 0];
$page = (int) ($pager['page'] ?? 1);
$pages = (int) ($pager['pages'] ?? 1);
$q = (string) ($erreursQuery ?? '');
$filtre = (string) ($filtre ?? 'suivi');
$ready = !empty($ready);
$stats = is_array($stats ?? null) ? $stats : ['open' => 0, 'hits404' => 0, 'hitsOther' => 0];
$listUrl = HttpError::listUrl($q, $filtre, $page);
$fmt = static function (?string $dt): string {
    if ($dt === null || $dt === '') {
        return '—';
    }
    $ts = strtotime($dt);
    return $ts === false ? $dt : date('d/m/Y à H:i', $ts);
};
?>
<div class="admin-page">
  <div class="admin-page-head">
    <div>
      <h1>Erreurs</h1>
      <p class="admin-lead" style="margin-bottom: 0;"><?= e($erreursSubtitle ?? '') ?></p>
    </div>
    <?php if ($ready && (int) ($stats['open'] ?? 0) > 0): ?>
      <form method="post" action="<?= e(url('/admin/erreurs/vu')) ?>" onsubmit="return confirm('Marquer toutes les adresses en attente comme vues ?');">
        <?= csrf_field() ?>
        <input type="hidden" name="tout" value="1">
        <input type="hidden" name="retour" value="<?= e($listUrl) ?>">
        <button class="admin-ghost" type="submit">Tout marquer comme vu</button>
      </form>
    <?php endif; ?>
  </div>

  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <?php if (!$ready): ?>
    <div class="admin-card">
      <h2>Journal pas encore créé</h2>
      <p class="admin-muted">Appliquez les migrations pour commencer à conserver les 404 et les autres erreurs.</p>
      <p style="margin:16px 0 0;"><a class="btn-navy" href="<?= e(url('/admin/migrations')) ?>">Ouvrir les migrations</a></p>
    </div>
  <?php else: ?>
    <div class="admin-kpi-row">
      <div class="admin-kpi">
        <div class="admin-kpi-k">À suivre</div>
        <div class="admin-kpi-v"><?= e(format_int((int) ($stats['open'] ?? 0))) ?></div>
      </div>
      <div class="admin-kpi">
        <div class="admin-kpi-k">404 · 7 jours</div>
        <div class="admin-kpi-v"><?= e(format_int((int) ($stats['hits404'] ?? 0))) ?></div>
      </div>
      <div class="admin-kpi">
        <div class="admin-kpi-k">Autres erreurs · 7 jours</div>
        <div class="admin-kpi-v"><?= e(format_int((int) ($stats['hitsOther'] ?? 0))) ?></div>
      </div>
    </div>

    <form class="admin-envois-search" method="get" action="<?= e(url('/admin/erreurs')) ?>">
      <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Adresse, message ou provenance…">
      <?php if ($filtre !== 'suivi'): ?>
        <input type="hidden" name="filtre" value="<?= e($filtre) ?>">
      <?php endif; ?>
      <button class="btn-navy" type="submit">Rechercher</button>
    </form>

    <div class="chip-row" style="margin-bottom: 18px;">
      <?php foreach ($filters as $f): ?>
        <a class="chip<?= !empty($f['on']) ? ' is-on' : '' ?>" href="<?= e(url($f['href'])) ?>"><?= e($f['label']) ?></a>
      <?php endforeach; ?>
    </div>

    <div class="admin-envois-wrap admin-errors-wrap">
      <div class="admin-envois-head">
        <span>Code</span>
        <span>Adresse</span>
        <span>Hits</span>
        <span>Dernière fois</span>
        <span>Suivi</span>
      </div>
      <?php if ($groups === []): ?>
        <p class="admin-users-empty"><?= $filtre === 'suivi' ? 'Aucune erreur en attente.' : 'Aucune erreur pour ce filtre.' ?></p>
      <?php endif; ?>
      <?php foreach ($groups as $g):
          $status = (int) ($g['status'] ?? 0);
          $path = (string) ($g['path'] ?? '/');
          $message = trim((string) ($g['message'] ?? ''));
          $referrer = trim((string) ($g['referrer'] ?? ''));
          ?>
        <a class="admin-envois-row" href="<?= e(url(HttpError::detailUrl($status, $path, $listUrl))) ?>">
          <span><span class="admin-pill" style="<?= e(AdminCatalog::pill(HttpError::tone($status))) ?>"><?= (int) $status ?></span></span>
          <div class="admin-envois-msg">
            <div class="admin-envois-subject"><?= e($path) ?></div>
            <div class="admin-envois-excerpt">
              <?= e($message !== '' ? $message : HttpError::label($status)) ?>
              <?php if ($referrer !== ''): ?> · <?= e($referrer) ?><?php endif; ?>
            </div>
          </div>
          <span><?= e(format_int((int) ($g['hits'] ?? 0))) ?></span>
          <time class="admin-envois-when" datetime="<?= e(datetime_iso((string) ($g['last_seen'] ?? ''))) ?>"><?= e($fmt((string) ($g['last_seen'] ?? ''))) ?></time>
          <span>
            <?php if (!empty($g['open'])): ?>
              <span class="admin-pill" style="<?= e(AdminCatalog::pill('orange')) ?>">À suivre</span>
            <?php else: ?>
              <span class="admin-pill" style="<?= e(AdminCatalog::pill('grey')) ?>">Vu</span>
            <?php endif; ?>
          </span>
        </a>
      <?php endforeach; ?>
    </div>

    <p class="admin-muted" style="margin-top: 14px;">Les erreurs sont conservées 90 jours. Une adresse marquée comme vue réapparaît si elle est demandée à nouveau.</p>

    <?php if ($pages > 1): ?>
      <nav class="admin-pager" aria-label="Pagination des erreurs">
        <?php if ($page > 1): ?>
          <a href="<?= e(url(HttpError::listUrl($q, $filtre, $page - 1))) ?>" rel="prev">Précédent</a>
        <?php else: ?>
          <span class="is-off" aria-disabled="true">Précédent</span>
        <?php endif; ?>
        <span aria-current="page"><?= (int) $page ?> / <?= (int) $pages ?></span>
        <?php if ($page < $pages): ?>
          <a href="<?= e(url(HttpError::listUrl($q, $filtre, $page + 1))) ?>" rel="next">Suivant</a>
        <?php else: ?>
          <span class="is-off" aria-disabled="true">Suivant</span>
        <?php endif; ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</div>
