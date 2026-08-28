<?php
$dossiers = $dossiers ?? [];
?>
<div class="admin-page">
  <h1>Vérifications</h1>
  <p class="admin-lead">Validez les vitrines des prestataires. Un profil vérifié reste public ; le refus n’empêche pas le compte de fonctionner, il signale un dossier à reprendre.</p>
  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <div class="chip-row" style="margin-bottom: 18px;">
    <?php foreach ($filters ?? [] as $f): ?>
      <a class="chip<?= !empty($f['on']) ? ' is-on' : '' ?>" href="<?= e(url($f['href'])) ?>"><?= e($f['label']) ?></a>
    <?php endforeach; ?>
  </div>

  <?php if ($dossiers === []): ?>
    <p class="admin-muted">Aucun dossier pour ce filtre.</p>
  <?php endif; ?>

  <div class="admin-stack">
    <?php foreach ($dossiers as $d): ?>
      <article class="admin-card admin-dossier">
        <div class="admin-dossier-who">
          <?= avatar_html($d, 40) ?>
          <div>
            <strong><?= e($d['name']) ?></strong>
            <span><?= e((string) $d['email']) ?></span>
            <em><?= e(implode(' · ', $d['trades'] ?? []) ?: 'Métier non renseigné') ?><?= !empty($d['city']) ? ' · ' . e((string) $d['city']) : '' ?></em>
          </div>
          <span class="admin-pill tone-<?= e($d['status'] === 'verified' ? 'green' : ($d['status'] === 'refused' ? 'orange' : 'navy')) ?>"><?= e($d['status_label']) ?></span>
        </div>
        <div class="admin-actions">
          <?php if (!empty($d['doc_href'])): ?>
            <a class="admin-ghost" href="<?= e((string) $d['doc_href']) ?>" target="_blank" rel="noopener">Justificatif</a>
          <?php endif; ?>
          <?php if (!empty($d['slug'])): ?>
            <a class="admin-ghost" href="<?= e(url('/prestataires/' . $d['slug'])) ?>">Voir la vitrine</a>
          <?php endif; ?>
          <a class="admin-ghost" href="<?= e(url('/admin/utilisateurs/' . (int) $d['user_id'])) ?>">Compte</a>
          <?php if ($d['status'] !== 'verified'): ?>
            <form method="post" action="<?= e(url('/admin/verifications/' . (int) $d['user_id'])) ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="status" value="verified">
              <button class="btn-navy" type="submit">Valider</button>
            </form>
          <?php endif; ?>
          <?php if ($d['status'] !== 'refused'): ?>
            <form method="post" action="<?= e(url('/admin/verifications/' . (int) $d['user_id'])) ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="status" value="refused">
              <button class="admin-ghost" type="submit">Refuser</button>
            </form>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</div>
