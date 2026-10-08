<?php

use Adl\Data\AdminCatalog;
use Adl\Models\Conversation;

$threads = $threads ?? [];
$filters = $echangesFilters ?? [];
$pager = $pager ?? ['page' => 1, 'pages' => 1, 'total' => 0];
$snapshot = $snapshot ?? ['threads' => 0, 'recent' => 0, 'files' => 0, 'reports' => 0];
$page = (int) ($pager['page'] ?? 1);
$pages = (int) ($pager['pages'] ?? 1);
$q = (string) ($echangesQuery ?? '');
$filtre = (string) ($filtre ?? 'tous');
$fmt = static function (?string $dt): string {
    if ($dt === null || $dt === '') {
        return '—';
    }
    $ts = strtotime($dt);
    return $ts === false ? $dt : date('d/m/Y à H:i', $ts);
};
$kpiHref = static function (string $id) use ($q): string {
    return Conversation::adminListUrl($q, $id);
};
?>
<div class="admin-page">
  <div class="admin-page-head">
    <div>
      <h1>Échanges</h1>
      <p class="admin-lead" style="margin-bottom: 0;">Discussions entre les comptes de la plateforme, du plus récent au plus ancien. Chaque ligne ouvre le fil complet, pièces jointes comprises.</p>
    </div>
  </div>

  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <div class="me-admin-kpis">
    <a class="admin-card me-admin-kpi<?= $filtre === 'tous' ? ' is-on' : '' ?>" href="<?= e(url($kpiHref('tous'))) ?>">
      <strong><?= e(format_int((int) $snapshot['threads'])) ?></strong>
      <span>discussions</span>
    </a>
    <a class="admin-card me-admin-kpi<?= $filtre === 'recent' ? ' is-on' : '' ?>" href="<?= e(url($kpiHref('recent'))) ?>">
      <strong><?= e(format_int((int) $snapshot['recent'])) ?></strong>
      <span>actives sur 7 jours</span>
    </a>
    <a class="admin-card me-admin-kpi<?= $filtre === 'fichiers' ? ' is-on' : '' ?>" href="<?= e(url($kpiHref('fichiers'))) ?>">
      <strong><?= e(format_int((int) $snapshot['files'])) ?></strong>
      <span>avec pièce jointe</span>
    </a>
    <a class="admin-card me-admin-kpi<?= (int) $snapshot['reports'] > 0 ? ' is-hot' : '' ?><?= $filtre === 'signalees' ? ' is-on' : '' ?>" href="<?= e(url($kpiHref('signalees'))) ?>">
      <strong><?= e(format_int((int) $snapshot['reports'])) ?></strong>
      <span>signalement<?= (int) $snapshot['reports'] > 1 ? 's' : '' ?> ouvert<?= (int) $snapshot['reports'] > 1 ? 's' : '' ?></span>
    </a>
  </div>

  <p class="admin-lead" style="margin-top: -6px;"><?= e($echangesSubtitle ?? '') ?></p>

  <form class="admin-envois-search" method="get" action="<?= e(url('/admin/echanges')) ?>">
    <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Nom, e-mail, sujet ou extrait d’un message…">
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

  <div class="admin-envois-wrap admin-echanges-wrap">
    <div class="admin-envois-head">
      <span>Dernier message</span>
      <span>Participants</span>
      <span>Discussion</span>
      <span>Messages</span>
    </div>
    <?php if ($threads === []): ?>
      <p class="admin-users-empty">Aucune discussion pour ce filtre.</p>
    <?php endif; ?>
    <?php foreach ($threads as $thread):
        $flagged = (int) ($thread['open_reports'] ?? 0) > 0;
        $files = (int) ($thread['file_count'] ?? 0);
        $count = (int) ($thread['message_count'] ?? 0);
        $people = $thread['people'] ?? [];
        ?>
      <a class="admin-envois-row<?= $flagged ? ' is-flagged' : '' ?>" href="<?= e(url((string) ($thread['href'] ?? ''))) ?>">
        <span>
          <?php if ($count > 0): ?>
            <time class="admin-envois-when" datetime="<?= e(datetime_iso((string) ($thread['last_at'] ?? ''))) ?>"><?= e($fmt((string) ($thread['last_at'] ?? ''))) ?></time>
            <span class="admin-echanges-ago"><?= e(time_ago((string) ($thread['last_at'] ?? ''))) ?></span>
          <?php else: ?>
            <span class="admin-envois-when">Aucun message</span>
            <span class="admin-echanges-ago">ouverte le <?= e($fmt((string) ($thread['last_at'] ?? ''))) ?></span>
          <?php endif; ?>
        </span>
        <span class="admin-echanges-people">
          <?php if ($people === []): ?>
            <span class="admin-echanges-person">Participants inconnus</span>
          <?php endif; ?>
          <?php foreach ($people as $person): ?>
            <span class="admin-echanges-person">
              <?= e((string) ($person['name'] ?? '')) ?>
              <em><?= e((string) ($person['role_label'] ?? '')) ?></em>
            </span>
          <?php endforeach; ?>
        </span>
        <span class="admin-envois-msg">
          <span class="admin-envois-subject"><?= e((string) ($thread['subject'] ?? 'Conversation')) ?></span>
          <span class="admin-echanges-context"><?= e((string) ($thread['context'] ?? '')) ?></span>
          <span class="admin-envois-excerpt"><?php if (!empty($thread['last_who'])): ?><?= e((string) $thread['last_who']) ?> · <?php endif; ?><?= e((string) ($thread['preview'] ?? '')) ?></span>
        </span>
        <span>
          <span class="admin-echanges-count"><?= e(format_int($count)) ?></span>
          <span class="admin-echanges-count-label">message<?= $count > 1 ? 's' : '' ?></span>
          <?php if ($files > 0): ?>
            <span class="admin-echanges-extra"><?= e(format_int($files)) ?> pièce<?= $files > 1 ? 's' : '' ?></span>
          <?php endif; ?>
          <?php if ($flagged): ?>
            <span class="admin-pill" style="<?= e(AdminCatalog::pill('orange')) ?>">Signalée</span>
          <?php endif; ?>
        </span>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if ($pages > 1): ?>
    <nav class="admin-pager" aria-label="Pagination des échanges">
      <?php if ($page > 1): ?>
        <a href="<?= e(url(Conversation::adminListUrl($q, $filtre, $page - 1))) ?>" rel="prev">Précédent</a>
      <?php else: ?>
        <span class="is-off" aria-disabled="true">Précédent</span>
      <?php endif; ?>
      <span aria-current="page"><?= (int) $page ?> / <?= (int) $pages ?></span>
      <?php if ($page < $pages): ?>
        <a href="<?= e(url(Conversation::adminListUrl($q, $filtre, $page + 1))) ?>" rel="next">Suivant</a>
      <?php else: ?>
        <span class="is-off" aria-disabled="true">Suivant</span>
      <?php endif; ?>
    </nav>
  <?php endif; ?>
</div>
