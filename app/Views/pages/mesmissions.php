<?php
$missions = $myMissions ?? [];
$open = count(array_filter($missions, static fn (array $m): bool => ($m['status'] ?? '') === 'open'));
?>
<div class="espace-page">
  <div class="espace-page-head">
    <div>
      <h1>Mes recherches publiées</h1>
      <p><?= $open ?> recherche<?= $open > 1 ? 's' : '' ?> ouverte<?= $open > 1 ? 's' : '' ?> · <?= count($missions) ?> au total.</p>
    </div>
    <a class="btn-navy" href="<?= e(url('/espace/publier')) ?>">Publier une recherche</a>
  </div>

  <?php if (!empty($saved)): ?>
    <div class="flash flash-ok"><?= e((string) $saved) ?></div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div class="flash flash-error"><?= e((string) $error) ?></div>
  <?php endif; ?>

  <?php if ($missions === []): ?>
    <div class="search-empty">
      <strong>Vous n'avez pas encore publié de recherche.</strong>
      <span>Décrivez un besoin : les prestataires du métier choisi pourront y répondre.</span>
      <a class="btn-orange" href="<?= e(url('/espace/publier')) ?>">Publier une recherche</a>
    </div>
  <?php else: ?>
    <div class="my-missions">
      <?php foreach ($missions as $m): ?>
        <article class="side-card">
          <div class="mission-row-title">
            <?= e((string) $m['title']) ?>
            <span class="status-pill status-<?= e((string) $m['status']) ?>"><?= e((string) $m['status_label']) ?></span>
          </div>
          <div class="mission-row-sub">
            <?= e((string) ($m['category_name'] ?? 'Recherche')) ?>
            · publiée <?= e((string) $m['when']) ?>
            · échéance <?= e((string) $m['deadline_label']) ?>
          </div>
          <div class="side-foot">
            <span><?= (int) $m['applicants'] ?> candidature<?= ((int) $m['applicants']) > 1 ? 's' : '' ?></span>
            <strong><?= e((string) $m['budget']) ?></strong>
          </div>
          <div class="auth-actions" style="margin-top: 14px;">
            <a class="btn-ghost" href="<?= e(url((string) $m['href'])) ?>">Voir l'annonce</a>
            <?php if ((int) ($m['applicants'] ?? 0) > 0): ?>
              <a class="btn-navy" href="<?= e(url((string) $m['href'] . '#candidatures')) ?>">Voir les candidatures</a>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
