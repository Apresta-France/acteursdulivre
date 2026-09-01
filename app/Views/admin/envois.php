<?php

use Adl\Data\AdminCatalog;
use Adl\Models\EmailLog;

$mails = $mails ?? [];
$filters = $envoisFilters ?? [];
$pager = $pager ?? ['page' => 1, 'pages' => 1, 'total' => 0];
$page = (int) ($pager['page'] ?? 1);
$pages = (int) ($pager['pages'] ?? 1);
$q = (string) ($envoisQuery ?? '');
$filtre = (string) ($filtre ?? 'tous');
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
      <h1>E-mails envoyés</h1>
      <p class="admin-lead" style="margin-bottom: 0;"><?= e($envoisSubtitle ?? '') ?></p>
    </div>
    <a class="admin-ghost" href="<?= e(url('/admin/emails')) ?>">Modèles d’e-mails</a>
  </div>

  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <form class="admin-envois-search" method="get" action="<?= e(url('/admin/envois')) ?>">
    <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Destinataire, sujet ou modèle…">
    <?php if ($filtre !== 'tous'): ?>
      <input type="hidden" name="filtre" value="<?= e($filtre) ?>">
    <?php endif; ?>
    <button class="btn-navy" type="submit">Rechercher</button>
  </form>

  <div class="chip-row" style="margin-bottom: 18px;">
    <?php foreach ($filters as $f): ?>
      <a class="chip<?= !empty($f['on']) ? ' is-on' : '' ?>" href="<?= e(url($f['href'])) ?>"><?= e($f['label']) ?></a>
    <?php endforeach; ?>
  </div>

  <div class="admin-envois-wrap">
    <div class="admin-envois-head">
      <span>Date</span>
      <span>Destinataire</span>
      <span>Message</span>
      <span>Statut</span>
    </div>
    <?php if ($mails === []): ?>
      <p class="admin-users-empty">Aucun e-mail pour ce filtre.</p>
    <?php endif; ?>
    <?php foreach ($mails as $m):
        $status = (string) ($m['status'] ?? 'sent');
        $tone = EmailLog::statusTone($status);
        ?>
      <a class="admin-envois-row" href="<?= e(url('/admin/envois/' . (int) $m['id'])) ?>">
        <time class="admin-envois-when" datetime="<?= e(datetime_iso((string) ($m['created_at'] ?? ''))) ?>"><?= e($fmt((string) ($m['created_at'] ?? ''))) ?></time>
        <span class="admin-envois-to"><?= e((string) ($m['recipient'] ?? '')) ?></span>
        <div class="admin-envois-msg">
          <div class="admin-envois-subject"><?= e((string) ($m['subject'] ?? '')) ?></div>
          <div class="admin-envois-excerpt"><?= e(EmailLog::excerpt((string) ($m['body_html'] ?? ''))) ?></div>
        </div>
        <span>
          <span class="admin-pill" style="<?= e(AdminCatalog::pill($tone)) ?>"><?= e(EmailLog::statusLabel($status)) ?></span>
          <span class="admin-envois-source"><?= e(EmailLog::sourceLabel((string) ($m['source'] ?? ''))) ?></span>
        </span>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if ($pages > 1): ?>
    <nav class="admin-pager" aria-label="Pagination des e-mails envoyés">
      <?php if ($page > 1): ?>
        <a href="<?= e(url(EmailLog::listUrl($q, $filtre, $page - 1))) ?>" rel="prev">Précédent</a>
      <?php else: ?>
        <span class="is-off" aria-disabled="true">Précédent</span>
      <?php endif; ?>
      <span aria-current="page"><?= (int) $page ?> / <?= (int) $pages ?></span>
      <?php if ($page < $pages): ?>
        <a href="<?= e(url(EmailLog::listUrl($q, $filtre, $page + 1))) ?>" rel="next">Suivant</a>
      <?php else: ?>
        <span class="is-off" aria-disabled="true">Suivant</span>
      <?php endif; ?>
    </nav>
  <?php endif; ?>
</div>
