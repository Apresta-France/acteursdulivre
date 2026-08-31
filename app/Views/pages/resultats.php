<?php
$q = (string) ($query ?? '');
$type = (string) ($searchType ?? 'all');
$cat = (string) ($searchCat ?? '');
$results = $searchResults ?? [];
$count = (int) ($searchCount ?? count($results));
$heading = (string) ($catalogHeading ?? ($q !== '' ? $q : ($cat !== '' ? $cat : 'Tous les métiers du livre')));
$typeHub = \Adl\Data\Catalog::typePath($type);
$filters = $searchFilters ?? ['kinds' => [], 'metiers' => [], 'specs' => [], 'delays' => [], 'levels' => [], 'trust' => [], 'bmin' => null, 'bmax' => null];
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
    ['name' => 'kind', 'label' => 'Type', 'key' => 'kinds', 'facet' => 'kind'],
    ['name' => 'metier', 'label' => 'Métier', 'key' => 'metiers', 'facet' => 'metier'],
    ['name' => 'spec', 'label' => 'Spécialité', 'key' => 'specs', 'facet' => 'spec'],
    ['name' => 'delay', 'label' => 'Délai', 'key' => 'delays', 'facet' => 'delay'],
    ['name' => 'level', 'label' => 'Niveau du prestataire', 'key' => 'levels', 'facet' => 'level'],
    ['name' => 'trust', 'label' => 'Confiance', 'key' => 'trust', 'facet' => 'trust'],
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
<div class="search-page" data-search-page
     data-api="<?= e(url('/api/recherche')) ?>"
     data-type="<?= e($type) ?>"
     data-initial="<?= e(json_encode($searchState ?? ['results' => $results, 'count' => $count, 'query' => $q, 'type' => $type, 'cat' => $cat, 'available_only' => !empty($availableOnly)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
  <div class="search-banner">
    <span class="search-banner-badge">Annuaire</span>
    Les résultats se mettent à jour pendant que vous tapez — prestations, profils et recherches au même endroit.
  </div>

  <div class="search-layout">
    <aside class="search-aside">
      <div class="search-aside-head">
        <span>Filtres</span>
        <a href="<?= e(url($typeHub)) ?>" data-search-reset>Réinitialiser</a>
      </div>
      <div class="sf-active" data-search-active<?= $active === [] ? ' hidden' : '' ?>>
        <?php foreach ($active as $chip): ?>
          <button type="button" class="sf-chip" data-clear-name="<?= e($chip['name']) ?>" data-clear-value="<?= e($chip['value']) ?>"><?= e($chip['label']) ?> ✕</button>
        <?php endforeach; ?>
      </div>
      <form class="search-filters" data-search-filters>
        <input type="hidden" name="q" value="<?= e($q) ?>" data-search-q>
        <?php if ($cat !== ''): ?>
          <input type="hidden" name="cat" value="<?= e($cat) ?>">
        <?php endif; ?>
        <?php foreach ($groups as $group): ?>
          <?php if (!empty($facets[$group['facet']])): ?>
            <?= search_filter_group($group['name'], $group['label'], $facets[$group['facet']], $filters[$group['key']] ?? []) ?>
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

      <div class="search-aside-card">
        <div class="search-aside-title">Pas le temps de comparer ?</div>
        <p>Publiez votre recherche : les prestataires vous envoient leurs devis.</p>
        <a class="btn-ghost" href="<?= e(url('/espace/publier')) ?>">Publier une recherche</a>
      </div>
    </aside>

    <div>
      <div class="search-crumb">Accueil · Annuaire</div>
      <div class="search-head">
        <div>
          <h1><?= e($heading) ?> <span data-search-count>· <?= (int) $count ?> résultat<?= $count > 1 ? 's' : '' ?></span></h1>
        </div>
        <?php
          $shareUrl = $meta['url'] ?? \Adl\Data\Share::current();
          $shareTitle = $meta['title'] ?? ('Recherche : ' . $heading);
          $shareText = $meta['description'] ?? 'Prestataires des métiers du livre sur acteursdulivre.fr';
          $shareLabel = 'Partager cette recherche';
          require ADL_ROOT . '/app/Views/partials/share.php';
        ?>
      </div>
      <div class="search-grid" data-search-results>
        <?php if ($results === []): ?>
          <div class="search-empty">
            <strong>Aucun résultat pour cette recherche.</strong>
            <span>Essayez un métier (illustration, traduction…) ou publiez une recherche.</span>
          </div>
        <?php else: ?>
          <?php foreach ($results as $item): ?>
            <a class="search-card<?= !empty($item['is_busy']) ? ' is-busy' : '' ?>" href="<?= e(url((string) $item['href'])) ?>">
              <?= search_card_media($item) ?>
              <div class="search-card-body">
                <div class="search-card-kicker">
                  <span><?= e((string) $item['kind_label']) ?></span>
                  <?php if (!empty($item['cat'])): ?><span><?= e((string) $item['cat']) ?></span><?php endif; ?>
                  <?php if (!empty($item['live'])): ?><span class="search-live">Votre réseau</span><?php endif; ?>
                  <?php if (($item['kind'] ?? '') === 'prestataires' && !empty($item['availability_label'])): ?>
                    <span class="status-pill<?= !empty($item['is_busy']) ? ' is-busy' : ' is-available' ?>"><?= e((string) $item['availability_label']) ?></span>
                  <?php endif; ?>
                </div>
                <div class="search-card-title"><?= e((string) $item['title']) ?></div>
                <div class="search-card-sub"><?= e((string) $item['subtitle']) ?></div>
                <div class="search-card-meta">
                  <span><?= e((string) $item['meta']) ?></span>
                  <?php if (!empty($item['price'])): ?><strong><?= e((string) $item['price']) ?></strong><?php endif; ?>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
