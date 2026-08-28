<?php $favorites = $favorites ?? []; ?>
<div class="espace-page">
  <div class="espace-page-head">
    <div>
      <h1>Favoris</h1>
      <p><?= count($favorites) ?> prestation<?= count($favorites) > 1 ? 's' : '' ?> enregistrée<?= count($favorites) > 1 ? 's' : '' ?>.</p>
    </div>
    <a class="btn-navy" href="<?= e(url('/prestations')) ?>">Parcourir les prestations</a>
  </div>

  <?php if (!empty($saved)): ?>
    <div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div>
  <?php endif; ?>

  <?php if ($favorites === []): ?>
    <div class="search-empty">
      <strong>Aucun favori pour le moment.</strong>
      <span>Enregistrez les prestations que vous voulez comparer plus tard.</span>
      <a class="btn-orange" href="<?= e(url('/recherche')) ?>">Ouvrir l'annuaire</a>
    </div>
  <?php else: ?>
    <div class="my-missions">
      <?php foreach ($favorites as $item): ?>
        <article class="side-card">
          <div class="mission-row-title"><?= e((string) $item['title']) ?></div>
          <div class="mission-row-sub"><?= e((string) $item['by']) ?> · <?= e((string) ($item['cat'] ?: '')) ?></div>
          <div class="side-foot">
            <span><?= e((string) ($item['delay'] ?: '')) ?></span>
            <strong><?= e((string) $item['price']) ?></strong>
          </div>
          <div class="auth-actions" style="margin-top: 14px;">
            <a class="btn-ghost" href="<?= e(url((string) $item['href'])) ?>">Voir la fiche</a>
            <form method="post" action="<?= e(url('/espace/favoris/' . (int) $item['id'])) ?>">
              <?= csrf_field() ?>
              <button class="btn-ghost" type="submit">Retirer</button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
