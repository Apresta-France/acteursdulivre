<?php
$q = (string) ($query ?? '');
$type = (string) ($searchType ?? 'all');
$cat = (string) ($searchCat ?? '');
$results = $searchResults ?? [];
$count = (int) ($searchCount ?? count($results));
$heading = (string) ($catalogHeading ?? ($q !== '' ? $q : ($cat !== '' ? $cat : 'Tous les métiers du livre')));
$typeHub = \Adl\Data\Catalog::typePath($type);
$filters = $searchFilters ?? ['kinds' => [], 'metiers' => [], 'specs' => [], 'delays' => [], 'levels' => [], 'trust' => [], 'bmin' => null, 'bmax' => null, 'city' => ''];
$searchCity = (string) ($searchCity ?? ($filters['city'] ?? ''));
$searchCityLabel = (string) ($searchCityLabel ?? '');
if ($searchCityLabel === '' && $searchCity !== '') {
    $searchCityLabel = \Adl\Data\Cities::labelForSlug($searchCity);
}
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
$pager = $pager ?? ['page' => 1, 'pages' => 1, 'total' => $count];
$page = (int) ($pager['page'] ?? 1);
$pages = (int) ($pager['pages'] ?? 1);
$countLabel = '· ' . (int) $count . ' résultat' . ($count > 1 ? 's' : '');
if ($pages > 1) {
    $countLabel .= ' · page ' . $page . ' / ' . $pages;
}
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
if ($searchCity !== '') {
    $active[] = ['name' => 'ville', 'value' => $searchCity, 'label' => $searchCityLabel !== '' ? $searchCityLabel : $searchCity];
}
?>
<div class="search-page" data-search-page
     data-api="<?= e(url('/api/recherche')) ?>"
     data-type="<?= e($type) ?>"
     data-search-limit="<?= (int) \Adl\Data\Catalog::PER_PAGE ?>"
     data-initial="<?= e(json_encode($searchState ?? ['results' => $results, 'count' => $count, 'query' => $q, 'type' => $type, 'cat' => $cat, 'city' => $searchCity, 'available_only' => !empty($availableOnly), 'page' => $page, 'pages' => $pages], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
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
        <div class="sf-group">
          <div class="sf-group-label">Ville</div>
          <?php
            $cityField = [
                'mode' => 'search',
                'id' => 'filter-city',
                'value' => $searchCityLabel,
                'slug' => $searchCity,
                'placeholder' => 'France, Europe, une ville…',
            ];
            require ADL_ROOT . '/app/Views/partials/city-field.php';
          ?>
        </div>
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
          <h1><?= e($heading) ?> <span data-search-count><?= e($countLabel) ?></span></h1>
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
            <?= search_card_html($item, true) ?>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <?php
        $pagerPath = $typeHub;
        $pagerLabel = 'Pagination de l’annuaire';
        require ADL_ROOT . '/app/Views/partials/search-pager.php';
      ?>
    </div>
  </div>
</div>
