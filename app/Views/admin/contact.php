<?php

use Adl\Models\ContactMessage;

$messages = $messages ?? [];
$filters = $contactFilters ?? [];
$pager = $pager ?? ['page' => 1, 'pages' => 1, 'total' => 0];
$page = (int) ($pager['page'] ?? 1);
$pages = (int) ($pager['pages'] ?? 1);
$q = (string) ($contactQuery ?? '');
$filtre = (string) ($filtre ?? ContactMessage::STATUS_OPEN);
$openCount = (int) ($openCount ?? 0);
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
      <h1>Messages</h1>
      <p class="admin-lead" style="margin-bottom: 0;"><?= e($contactSubtitle ?? 'Formulaire « Nous écrire ».') ?></p>
    </div>
  </div>

  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <div class="me-admin-kpis">
    <div class="admin-card me-admin-kpi<?= $openCount > 0 ? ' is-hot' : '' ?>"><strong><?= (int) $openCount ?></strong><span>message<?= $openCount > 1 ? 's' : '' ?> à traiter</span></div>
  </div>

  <form class="admin-envois-search" method="get" action="<?= e(url('/admin/contact')) ?>">
    <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Nom, e-mail ou extrait…">
    <?php if ($filtre !== ContactMessage::STATUS_OPEN): ?>
      <input type="hidden" name="filtre" value="<?= e($filtre) ?>">
    <?php endif; ?>
    <button class="btn-navy" type="submit">Rechercher</button>
  </form>

  <div class="chip-row" style="margin-bottom: 18px;">
    <?php foreach ($filters as $f): ?>
      <a class="chip<?= !empty($f['on']) ? ' is-on' : '' ?>" href="<?= e(url($f['href'])) ?>"><?= e($f['label']) ?></a>
    <?php endforeach; ?>
  </div>

  <div class="admin-envois-wrap admin-contact-wrap">
    <div class="admin-envois-head admin-contact-head">
      <span>Date</span>
      <span>Expéditeur</span>
      <span>Message</span>
      <span>Statut</span>
    </div>
    <?php if ($messages === []): ?>
      <p class="admin-users-empty">Aucun message pour ce filtre.</p>
    <?php endif; ?>
    <?php foreach ($messages as $m):
        $tone = (string) ($m['status_tone'] ?? 'orange');
        $open = !empty($m['is_open']);
        ?>
      <a class="admin-envois-row admin-contact-row<?= $open ? ' is-open' : '' ?>" href="<?= e(url('/admin/contact/' . (int) $m['id'])) ?>">
        <time class="admin-envois-when" datetime="<?= e(datetime_iso((string) ($m['created_at'] ?? ''))) ?>"><?= e($fmt((string) ($m['created_at'] ?? ''))) ?></time>
        <span class="admin-envois-to">
          <strong class="admin-contact-who"><?= e((string) ($m['who'] ?? 'Visiteur')) ?></strong>
          <span class="admin-envois-source"><?= e((string) ($m['email'] ?? '')) ?></span>
        </span>
        <div class="admin-envois-msg">
          <div class="admin-envois-excerpt"><?= e((string) ($m['excerpt'] ?? '')) ?></div>
        </div>
        <span>
          <span class="admin-pill tone-<?= e($tone) ?>"><?= e((string) ($m['status_label'] ?? 'À traiter')) ?></span>
        </span>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if ($pages > 1): ?>
    <nav class="admin-pager" aria-label="Pagination des messages de contact">
      <?php if ($page > 1): ?>
        <a href="<?= e(url(ContactMessage::listUrl($q, $filtre, $page - 1))) ?>" rel="prev">Précédent</a>
      <?php else: ?>
        <span class="is-off" aria-disabled="true">Précédent</span>
      <?php endif; ?>
      <span aria-current="page"><?= (int) $page ?> / <?= (int) $pages ?></span>
      <?php if ($page < $pages): ?>
        <a href="<?= e(url(ContactMessage::listUrl($q, $filtre, $page + 1))) ?>" rel="next">Suivant</a>
      <?php else: ?>
        <span class="is-off" aria-disabled="true">Suivant</span>
      <?php endif; ?>
    </nav>
  <?php endif; ?>
</div>
