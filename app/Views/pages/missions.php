<?php
$missions = $liveMissions ?? [];
$cat = (string) ($searchCat ?? '');
$trades = $trades ?? \Adl\Data\Catalog::trades();
$count = count($missions);
?>
<div class="missions-page">
  <div class="missions-head">
    <div>
      <h1>Appels d'offres <span>· <?= $count ?> recherche<?= $count > 1 ? 's' : '' ?></span></h1>
      <p>Les porteurs de projet publient, vous postulez avec votre devis. Aucune commission sur la candidature.</p>
    </div>
    <a class="btn-navy" href="<?= e(url('/espace/publier')) ?>">Publier une recherche</a>
  </div>

  <form class="chip-row missions-filters" method="get" action="<?= e(url('/missions')) ?>">
    <a class="chip<?= $cat === '' ? ' is-on' : '' ?>" href="<?= e(url('/missions')) ?>">Tous les métiers</a>
    <?php foreach ($trades as $trade): ?>
      <a class="chip<?= $cat === $trade ? ' is-on' : '' ?>" href="<?= e(url('/missions?metier=' . rawurlencode($trade))) ?>"><?= e($trade) ?></a>
    <?php endforeach; ?>
  </form>

  <div class="missions-layout">
    <div class="missions-list">
      <?php if ($missions === []): ?>
        <div class="search-empty">
          <strong>Aucune recherche ouverte pour ce métier.</strong>
          <span>Publiez la vôtre, ou élargissez le filtre.</span>
        </div>
      <?php else: ?>
        <?php foreach ($missions as $m): ?>
          <a class="mission-row" href="<?= e(url((string) $m['href'])) ?>">
            <div>
              <div class="mission-row-title">
                <?= e((string) $m['title']) ?>
                <?php if (!empty($m['live'])): ?><span class="search-live">Nouvelle</span><?php endif; ?>
              </div>
              <div class="mission-row-sub"><?= e((string) $m['subtitle']) ?></div>
              <?php if (!empty($m['tags'])): ?>
                <div class="chip-row">
                  <?php foreach ($m['tags'] as $tag): ?>
                    <span class="chip-static dark"><?= e((string) $tag) ?></span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
            <div>
              <strong><?= e((string) $m['price']) ?></strong>
              <span><?= e((string) $m['meta']) ?></span>
            </div>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <aside>
      <div class="side-card side-card-warm">
        <div class="side-title-sm">Publier une recherche</div>
        <p>Décrivez le besoin et le budget : les prestataires qualifiés vous envoient leur devis. Gratuit pour le porteur de projet.</p>
        <a class="btn-ghost" href="<?= e(url('/espace/publier')) ?>">Décrire mon besoin</a>
      </div>
    </aside>
  </div>
</div>
