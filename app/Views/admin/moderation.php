<?php
$services = $services ?? [];
$missions = $missions ?? [];
?>
<?php $reports = $reports ?? []; ?>
<div class="admin-page">
  <h1>Modération</h1>
  <p class="admin-lead">Retirez une prestation ou clôturez un appel d’offres s’il enfreint la charte (IA générative, hors plateforme, droits). Traitez aussi les signalements utilisateurs.</p>
  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <h2 class="admin-h2">Signalements</h2>
  <?php if ($reports === []): ?><p class="admin-muted">Aucun signalement.</p><?php endif; ?>
  <div class="admin-stack" style="margin-bottom: 28px;">
    <?php foreach ($reports as $r): ?>
      <article class="admin-card">
        <div class="admin-dossier-who">
          <div>
            <strong><?= e((string) $r['type_label']) ?> · <?= e((string) $r['reason_label']) ?></strong>
            <span><?= e((string) $r['who']) ?> · <?= e((string) $r['when']) ?></span>
            <?php if (!empty($r['body'])): ?><em><?= e((string) $r['body']) ?></em><?php endif; ?>
          </div>
          <span class="admin-pill tone-<?= ($r['status'] ?? '') === 'closed' ? 'green' : 'orange' ?>"><?= e((string) $r['status_label']) ?></span>
        </div>
        <div class="admin-actions">
          <a class="admin-ghost" href="<?= e(url((string) $r['href'])) ?>">Voir</a>
          <?php if (($r['status'] ?? '') !== 'closed'): ?>
            <form method="post" action="<?= e(url('/admin/signalements/' . (int) $r['id'])) ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="status" value="closed">
              <button class="btn-navy" type="submit">Marquer traité</button>
            </form>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <h2 class="admin-h2">Prestations</h2>
  <?php if ($services === []): ?><p class="admin-muted">Aucune prestation.</p><?php endif; ?>
  <div class="r-scroll">
    <table class="table">
      <thead><tr><th>Prestation</th><th>Prestataire</th><th>Statut</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($services as $s): ?>
          <tr>
            <td>
              <a href="<?= e(url($s['href'])) ?>"><?= e($s['title']) ?></a>
              <div class="admin-sub"><?= e($s['cat'] ?? '') ?></div>
            </td>
            <td><?= e($s['by']) ?></td>
            <td><?= e($s['status_label']) ?></td>
            <td>
              <form class="admin-actions" method="post" action="<?= e(url('/admin/moderation/prestation/' . (int) $s['id'])) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="back" value="/admin/moderation">
                <?php if (($s['status'] ?? '') === 'published'): ?>
                  <input type="hidden" name="status" value="draft">
                  <button class="admin-ghost" type="submit">Retirer</button>
                <?php else: ?>
                  <input type="hidden" name="status" value="published">
                  <button class="btn-navy" type="submit">Remettre en ligne</button>
                <?php endif; ?>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <h2 class="admin-h2">Appels d’offres</h2>
  <?php if ($missions === []): ?><p class="admin-muted">Aucun appel d’offres.</p><?php endif; ?>
  <div class="r-scroll">
    <table class="table">
      <thead><tr><th>Mission</th><th>Par</th><th>Statut</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($missions as $m): ?>
          <tr>
            <td><a href="<?= e(url($m['href'])) ?>"><?= e($m['title']) ?></a></td>
            <td><?= e($m['by']) ?></td>
            <td><?= e($m['status_label']) ?></td>
            <td>
              <?php if (($m['status'] ?? '') === 'open'): ?>
                <form class="admin-actions" method="post" action="<?= e(url('/admin/moderation/mission/' . (int) $m['id'])) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="back" value="/admin/moderation">
                  <input type="hidden" name="status" value="closed">
                  <button class="admin-ghost" type="submit">Clôturer</button>
                </form>
              <?php elseif (($m['status'] ?? '') === 'closed'): ?>
                <form class="admin-actions" method="post" action="<?= e(url('/admin/moderation/mission/' . (int) $m['id'])) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="back" value="/admin/moderation">
                  <input type="hidden" name="status" value="open">
                  <button class="btn-navy" type="submit">Rouvrir</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
