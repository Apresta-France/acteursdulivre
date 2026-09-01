<?php
$missions = $liveMissions ?? [];
$count = (int) ($searchCount ?? count($missions));
$viewer = \Adl\Core\Auth::user();
$canPublish = $viewer && \Adl\Models\User::seeksServices($viewer);
$publishHref = !$viewer
    ? '/connexion?next=' . rawurlencode('/espace/publier')
    : ($canPublish ? '/espace/publier' : '');
$filters = $searchFilters ?? ['metiers' => [], 'delays' => [], 'bmin' => null, 'bmax' => null];
$facets = $searchFacets ?? [];
$bmin = $filters['bmin'] ?? \Adl\Data\Catalog::BUDGET_MIN;
$bmax = $filters['bmax'] ?? \Adl\Data\Catalog::BUDGET_MAX;
if ($bmin === null) {
    $bmin = \Adl\Data\Catalog::BUDGET_MIN;
}
if ($bmax === null) {
    $bmax = \Adl\Data\Catalog::BUDGET_MAX;
}
$groups = [
    ['name' => 'metier', 'label' => 'Métier', 'key' => 'metiers', 'facet' => 'metier'],
    ['name' => 'delay', 'label' => 'Échéance', 'key' => 'delays', 'facet' => 'delay'],
];
$active = [];
foreach ($groups as $group) {
    foreach ($filters[$group['key']] ?? [] as $value) {
        $label = $value;
        foreach ($facets[$group['facet']] ?? [] as $opt) {
            if (($opt['v'] ?? '') === $value) {
                $label = (string) $opt['l'];
                break;
            }
        }
        $active[] = ['name' => $group['name'], 'value' => $value, 'label' => $label];
    }
}
if ((int) $bmin !== \Adl\Data\Catalog::BUDGET_MIN || (int) $bmax !== \Adl\Data\Catalog::BUDGET_MAX) {
    $active[] = ['name' => 'budget', 'value' => '', 'label' => format_int((int) $bmin) . ' € — ' . format_int((int) $bmax) . ' €'];
}
?>
<div class="search-page missions-page"
     data-search-page
     data-search-standalone
     data-results="rows"
     data-count-unit="recherche"
     data-search-limit="48"
     data-type="missions"
     data-api="<?= e(url('/api/recherche')) ?>"
     data-initial="<?= e(json_encode($searchState ?? ['results' => $missions, 'count' => $count, 'type' => 'missions', 'filters' => $filters], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
  <div class="search-banner">
    <span class="search-banner-badge">Appels d'offres</span>
    Les porteurs de projet publient, vous postulez avec votre devis. Aucune commission sur la candidature.
  </div>

  <div class="search-layout">
    <aside class="search-aside">
      <div class="search-aside-head">
        <span>Filtres</span>
        <a href="<?= e(url('/missions')) ?>" data-search-reset>Réinitialiser</a>
      </div>
      <div class="sf-active" data-search-active<?= $active === [] ? ' hidden' : '' ?>>
        <?php foreach ($active as $chip): ?>
          <button type="button" class="sf-chip" data-clear-name="<?= e($chip['name']) ?>" data-clear-value="<?= e($chip['value']) ?>"><?= e($chip['label']) ?> ✕</button>
        <?php endforeach; ?>
      </div>
      <form class="search-filters" method="get" action="<?= e(url('/missions')) ?>" data-search-filters>
        <?php foreach ($groups as $group): ?>
          <?php if (!empty($facets[$group['facet']])): ?>
            <?= search_filter_group($group['name'], $group['label'], $facets[$group['facet']], $filters[$group['key']] ?? [], $group['name'] === 'metier') ?>
          <?php endif; ?>
        <?php endforeach; ?>

        <div class="sf-budget" data-budget>
          Budget<br>
          <span class="sf-budget-val" data-budget-label><?= format_int((int) $bmin) ?> € — <?= format_int((int) $bmax) ?> €</span>
          <div class="sf-slider">
            <div class="sf-slider-track"><div class="sf-slider-fill" data-budget-fill></div></div>
            <input type="range" name="bmin" min="0" max="<?= (int) \Adl\Data\Catalog::BUDGET_MAX ?>" step="50" value="<?= (int) $bmin ?>" data-budget-min>
            <input type="range" name="bmax" min="0" max="<?= (int) \Adl\Data\Catalog::BUDGET_MAX ?>" step="50" value="<?= (int) $bmax ?>" data-budget-max>
          </div>
        </div>
      </form>

      <div class="search-aside-card<?= $viewer && !$canPublish ? ' side-card-warm' : '' ?>">
        <div class="search-aside-title">Publier une recherche</div>
        <?php if ($viewer && !$canPublish): ?>
          <p>Pour publier un appel d’offres, activez l’usage « Je cherche des prestataires » dans vos paramètres.</p>
          <a class="btn-ghost" href="<?= e(url('/espace/parametres')) ?>">Modifier mes usages</a>
        <?php else: ?>
          <p>Décrivez le besoin et le budget : les prestataires qualifiés vous envoient leur devis. Gratuit pour le porteur de projet.</p>
          <a class="btn-ghost" href="<?= e(url($publishHref !== '' ? $publishHref : '/espace/publier')) ?>">Décrire mon besoin</a>
        <?php endif; ?>
      </div>
    </aside>

    <div>
      <div class="search-crumb"><a href="<?= e(url('/')) ?>">Accueil</a> · Appels d'offres</div>
      <div class="search-head missions-head">
        <div>
          <h1>Appels d'offres <span data-search-count>· <?= $count ?> recherche<?= $count > 1 ? 's' : '' ?></span></h1>
          <p class="missions-lead">Parcourez les recherches ouvertes et candidatez avec votre devis.</p>
        </div>
        <?php if ($publishHref !== ''): ?>
          <a class="btn-navy" href="<?= e(url($publishHref)) ?>">Publier une recherche</a>
        <?php endif; ?>
      </div>
      <div data-search-results>
        <?php if ($missions === []): ?>
          <div class="search-empty">
            <strong>Aucune recherche ouverte pour ces critères.</strong>
            <span>Publiez la vôtre, ou élargissez le filtre.</span>
          </div>
        <?php else: ?>
          <div class="missions-list">
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
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
