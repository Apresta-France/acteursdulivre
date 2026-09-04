<?php
$reviews = $reviews ?? [];
$recommendations = $recommendations ?? [];
?>
<div class="admin-page">
  <h1>Avis</h1>
  <p class="admin-lead">Un avis n’est publiable qu’après une mission réalisée ici. Les recommandations hors plateforme s’affichent à part et n’entrent pas dans la note. Masquez un texte abusif : il disparaît des vitrines. La suppression est définitive.</p>
  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <?php if ($reviews === []): ?>
    <p class="admin-muted">Aucun avis pour le moment.</p>
  <?php endif; ?>

  <div class="admin-stack">
    <?php foreach ($reviews as $r): ?>
      <article class="admin-card<?= !empty($r['hidden']) ? ' is-dim' : '' ?>">
        <div class="admin-dossier-who">
          <div>
            <strong><?= e($r['note']) ?> / 5 — <?= e($r['cible']) ?></strong>
            <span>par <?= e($r['auteur']) ?><?= !empty($r['order_number']) ? ' · commande ' . e((string) $r['order_number']) : '' ?></span>
            <?php if ($r['txt'] !== ''): ?><p class="admin-quote"><?= e($r['txt']) ?></p><?php endif; ?>
            <em><?= e($r['when']) ?><?= !empty($r['hidden']) ? ' · masqué' : '' ?></em>
          </div>
        </div>
        <div class="admin-actions">
          <form method="post" action="<?= e(url('/admin/avis/' . (int) $r['id'])) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= !empty($r['hidden']) ? 'show' : 'hide' ?>">
            <button class="admin-ghost" type="submit"><?= !empty($r['hidden']) ? 'Rétablir' : 'Masquer' ?></button>
          </form>
          <form method="post" action="<?= e(url('/admin/avis/' . (int) $r['id'])) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <button class="admin-ghost" type="submit" onclick="return confirm('Supprimer définitivement cet avis ?');">Supprimer</button>
          </form>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <h2 class="admin-h3" style="margin-top: 36px;">Recommandations hors plateforme</h2>
  <?php if ($recommendations === []): ?>
    <p class="admin-muted">Aucune recommandation pour le moment.</p>
  <?php endif; ?>
  <div class="admin-stack">
    <?php foreach ($recommendations as $r): ?>
      <article class="admin-card<?= !empty($r['hidden']) ? ' is-dim' : '' ?>">
        <div class="admin-dossier-who">
          <div>
            <strong><?= e((string) $r['who']) ?><?= $r['role'] !== '' ? ' · ' . e((string) $r['role']) : '' ?> — <?= e((string) $r['cible']) ?></strong>
            <span><?= e((string) $r['author_email']) ?><?= $r['context'] !== '' ? ' · ' . e((string) $r['context']) : '' ?></span>
            <?php if ($r['txt'] !== ''): ?><div class="admin-quote recommendation-text"><?= recommendation_html((string) $r['txt']) ?></div><?php endif; ?>
            <em><?= e((string) $r['when']) ?><?= !empty($r['hidden']) ? ' · masquée' : '' ?></em>
          </div>
        </div>
        <div class="admin-actions">
          <form method="post" action="<?= e(url('/admin/recommandations/' . (int) $r['id'])) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= !empty($r['hidden']) ? 'show' : 'hide' ?>">
            <button class="admin-ghost" type="submit"><?= !empty($r['hidden']) ? 'Rétablir' : 'Masquer' ?></button>
          </form>
          <form method="post" action="<?= e(url('/admin/recommandations/' . (int) $r['id'])) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <button class="admin-ghost" type="submit" onclick="return confirm('Supprimer définitivement cette recommandation ?');">Supprimer</button>
          </form>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</div>
