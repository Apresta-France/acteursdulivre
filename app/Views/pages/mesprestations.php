<?php
$services = $myServices ?? [];
$online = count(array_filter($services, static fn (array $s): bool => ($s['status'] ?? '') === 'published'));
?>
<div class="espace-page">
  <div class="espace-page-head">
    <div>
      <h1>Mes prestations</h1>
      <p><?= $online ?> en ligne · <?= count($services) ?> au total.</p>
    </div>
    <a class="btn-navy" href="<?= e(url('/espace/prestations/creer')) ?>">Proposer une prestation</a>
  </div>

  <?php if (!empty($saved)): ?>
    <div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div class="flash flash-error"><?= e((string) $error) ?></div>
  <?php endif; ?>
  <?php require ADL_ROOT . '/app/Views/partials/billing-banner.php'; ?>

  <?php if ($services === []): ?>
    <div class="search-empty">
      <strong>Vous n'avez pas encore de prestation.</strong>
      <span>Une offre packagée à prix et délai affichés se trouve plus facilement dans l'annuaire.</span>
      <a class="btn-orange" href="<?= e(url('/espace/prestations/creer')) ?>">Proposer une prestation</a>
    </div>
  <?php else: ?>
    <div class="my-missions">
      <?php foreach ($services as $s): ?>
        <article class="side-card">
          <div class="mission-row-title">
            <?= e((string) $s['title']) ?>
            <span class="status-pill status-<?= e((string) $s['status']) ?>"><?= e((string) $s['status_label']) ?></span>
          </div>
          <div class="mission-row-sub">
            <?= e((string) ($s['cat'] ? \Adl\Data\Catalog::tradeTitle((string) $s['cat']) : 'Prestation')) ?><?php if (!empty($s['specialty'])): ?> · <?= e((string) $s['specialty']) ?><?php endif; ?>
            <?php if (!empty($s['delay'])): ?> · <?= e((string) $s['delay']) ?><?php endif; ?>
          </div>
          <div class="side-foot">
            <span><?= (int) ($s['reviews'] ?? 0) ?> avis</span>
            <strong><?= e((string) $s['price']) ?></strong>
          </div>
          <div class="auth-actions" style="margin-top: 14px;">
            <a class="btn-ghost" href="<?= e(url((string) $s['href'])) ?>">Voir la fiche</a>
            <a class="btn-navy" href="<?= e(url('/espace/prestations/' . (int) $s['id'] . '/modifier')) ?>">Modifier</a>
            <form method="post" action="<?= e(url('/espace/prestations/' . (int) $s['id'] . '/supprimer')) ?>" onsubmit="return confirm('Supprimer cette prestation ? Elle disparaîtra de l’annuaire.');">
              <?= csrf_field() ?>
              <button class="btn-ghost btn-danger" type="submit">Supprimer</button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
