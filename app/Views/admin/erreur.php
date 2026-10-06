<?php

use Adl\Data\AdminCatalog;
use Adl\Models\HttpError;

$group = is_array($group ?? null) ? $group : [];
$hits = $hits ?? [];
$status = (int) ($group['status'] ?? 0);
$path = (string) ($group['path'] ?? '/');
$back = (string) ($back ?? '/admin/erreurs');
$message = trim((string) ($group['message'] ?? ''));
$fmt = static function (?string $dt): string {
    if ($dt === null || $dt === '') {
        return '—';
    }
    $ts = strtotime($dt);
    return $ts === false ? $dt : date('d/m/Y à H:i', $ts);
};
?>
<div class="admin-page">
  <p class="admin-back"><a href="<?= e(url($back)) ?>">← Toutes les erreurs</a></p>
  <div class="admin-page-head">
    <div>
      <h1><?= e($path) ?></h1>
      <p class="admin-lead" style="margin-bottom: 0;"><?= e($message !== '' ? $message : HttpError::label($status)) ?></p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <form method="post" action="<?= e(url('/admin/erreurs/vu')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="code" value="<?= $status ?>">
        <input type="hidden" name="chemin" value="<?= e($path) ?>">
        <input type="hidden" name="retour" value="<?= e($back) ?>">
        <button class="btn-navy" type="submit"><?= !empty($group['open']) ? 'Marquer comme vu' : 'Marquer à nouveau comme vu' ?></button>
      </form>
      <form method="post" action="<?= e(url('/admin/erreurs/effacer')) ?>" onsubmit="return confirm('Effacer cette adresse du journal ?');">
        <?= csrf_field() ?>
        <input type="hidden" name="code" value="<?= $status ?>">
        <input type="hidden" name="chemin" value="<?= e($path) ?>">
        <input type="hidden" name="retour" value="<?= e($back) ?>">
        <button class="admin-ghost" type="submit">Effacer</button>
      </form>
    </div>
  </div>

  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <dl class="admin-envoi-meta">
    <div>
      <dt>Code</dt>
      <dd><span class="admin-pill" style="<?= e(AdminCatalog::pill(HttpError::tone($status))) ?>"><?= $status ?> · <?= e(HttpError::label($status)) ?></span></dd>
    </div>
    <div>
      <dt>Demandes</dt>
      <dd><?= e(format_int((int) ($group['hits'] ?? 0))) ?></dd>
    </div>
    <div>
      <dt>Première fois</dt>
      <dd><?= e($fmt((string) ($group['first_seen'] ?? ''))) ?></dd>
    </div>
    <div>
      <dt>Dernière fois</dt>
      <dd><?= e($fmt((string) ($group['last_seen'] ?? ''))) ?></dd>
    </div>
  </dl>

  <h2 class="admin-h2">Dernières demandes</h2>
  <div class="admin-envois-wrap">
    <div class="admin-envois-head">
      <span>Quand</span>
      <span>Requête</span>
      <span>Provenance</span>
      <span>Compte</span>
    </div>
    <?php if ($hits === []): ?>
      <p class="admin-users-empty">Aucune demande enregistrée.</p>
    <?php endif; ?>
    <?php foreach ($hits as $hit):
        $query = trim((string) ($hit['query_string'] ?? ''));
        $who = trim((string) ($hit['user_name'] ?? ''));
        $userId = (int) ($hit['user_id'] ?? 0);
        ?>
      <div class="admin-envois-row" style="cursor:default;">
        <div>
          <time class="admin-envois-when" datetime="<?= e(datetime_iso((string) ($hit['updated_at'] ?? ''))) ?>"><?= e($fmt((string) ($hit['updated_at'] ?? ''))) ?></time>
          <?php $times = (int) ($hit['hits'] ?? 1); ?>
          <span class="admin-envois-source"><?= e(format_int($times) . ($times > 1 ? ' demandes' : ' demande')) ?></span>
        </div>
        <div class="admin-envois-msg">
          <div class="admin-envois-subject"><?= e((string) ($hit['method'] ?? 'GET')) ?></div>
          <div class="admin-envois-excerpt"><?= $query !== '' ? e($query) : '—' ?></div>
        </div>
        <div class="admin-envois-msg">
          <div class="admin-envois-excerpt"><?= e((string) ($hit['referrer'] ?? '') !== '' ? (string) $hit['referrer'] : '—') ?></div>
          <?php if (!empty($hit['user_agent'])): ?>
            <div class="admin-envois-excerpt"><?= e((string) $hit['user_agent']) ?></div>
          <?php endif; ?>
        </div>
        <span>
          <?php if ($userId > 0): ?>
            <a href="<?= e(url('/admin/utilisateurs/' . $userId)) ?>"><?= e($who !== '' ? $who : 'Compte ' . $userId) ?></a>
          <?php else: ?>
            Visiteur
          <?php endif; ?>
        </span>
      </div>
    <?php endforeach; ?>
  </div>
</div>
