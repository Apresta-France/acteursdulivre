<?php
$q = (string) ($query ?? '');
$type = (string) ($searchType ?? 'all');
$cat = (string) ($searchCat ?? '');
$results = $searchResults ?? [];
$count = (int) ($searchCount ?? count($results));
$types = $searchTypes ?? \Adl\Data\Catalog::TYPES;
$trades = $trades ?? \Adl\Data\Catalog::trades();
$heading = (string) ($catalogHeading ?? ($q !== '' ? $q : ($cat !== '' ? $cat : 'Tous les métiers du livre')));
$typeHub = \Adl\Data\Catalog::typePath($type);
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
        <a href="<?= e(url($typeHub)) ?>">Réinitialiser</a>
      </div>
      <form class="search-filters" data-search-filters>
        <label class="field" for="search-q">Recherche</label>
        <input class="input" id="search-q" type="search" name="q" value="<?= e($q) ?>" placeholder="correcteur, illustration, jeunesse…" autocomplete="off" data-search-input>

        <p class="field">Type</p>
        <div class="chip-row">
          <?php foreach ($types as $value => $label): ?>
            <a class="chip<?= $type === $value ? ' is-on' : '' ?>" href="<?= e(url(\Adl\Data\Catalog::typePath($value))) ?>"><?= e($label) ?></a>
          <?php endforeach; ?>
        </div>

        <p class="field">Métier</p>
        <div class="chip-row">
          <a class="chip<?= $cat === '' ? ' is-on' : '' ?>" href="<?= e(url($typeHub)) ?>">Tous</a>
          <?php foreach ($trades as $trade): ?>
            <a class="chip<?= $cat === $trade ? ' is-on' : '' ?>" href="<?= e(url(\Adl\Data\Catalog::tradePath($trade))) ?>"><?= e($trade) ?></a>
          <?php endforeach; ?>
        </div>

        <label class="search-check">
          <input type="checkbox" name="dispo" value="1"<?= !empty($availableOnly) ? ' checked' : '' ?>>
          Prestataires disponibles uniquement
        </label>
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
