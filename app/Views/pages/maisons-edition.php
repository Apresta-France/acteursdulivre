<?php

use Adl\Models\Publisher;

$publishers = $publishers ?? [];
$pager = $pager ?? ['page' => 1, 'pages' => 1, 'total' => 0];
$total = (int) ($pager['total'] ?? 0);
$filters = $filters ?? [];
$facets = $facets ?? ['genres' => [], 'sizes' => [], 'countries' => []];
$scope = (string) ($scope ?? 'all');
$pagerPath = (string) ($pagerPath ?? '/maisons-edition');
$crumbs = $breadcrumbs ?? [];
$viewer = \Adl\Core\Auth::user();
$q = (string) ($filters['q'] ?? '');
$genre = (string) ($filters['genre'] ?? '');
$size = (string) ($filters['size'] ?? '');
$typology = (string) ($filters['typology'] ?? '');
$independent = ($filters['independent'] ?? '') === '1';
$countrySel = (string) ($filters['country'] ?? '');
$citySel = (string) ($filters['city'] ?? '');
$sort = (string) ($filters['sort'] ?? 'az');
if (!isset(Publisher::SORTS[$sort])) {
    $sort = 'az';
}
$cities = $cities ?? [];
$sizes = $sizes ?? Publisher::SIZES;
$typologies = $typologies ?? Publisher::TYPOLOGIES;

$active = [];
if ($q !== '') {
    $active[] = ['label' => '« ' . $q . ' »', 'href' => catalog_listing_url($pagerPath, 1, array_diff_key($_GET, ['q' => 1, 'page' => 1]))];
}
if ($genre !== '') {
    $active[] = ['label' => (string) ($genreLabel ?? $genre), 'href' => catalog_listing_url($pagerPath, 1, array_diff_key($_GET, ['genre' => 1, 'page' => 1]))];
}
if ($size !== '' && isset($sizes[$size])) {
    $active[] = ['label' => $sizes[$size], 'href' => catalog_listing_url($pagerPath, 1, array_diff_key($_GET, ['taille' => 1, 'page' => 1]))];
}
if ($typology !== '' && isset($typologies[$typology])) {
    $active[] = ['label' => $typologies[$typology], 'href' => catalog_listing_url($pagerPath, 1, array_diff_key($_GET, ['typologie' => 1, 'page' => 1]))];
}
if ($independent) {
    $active[] = ['label' => 'Indépendants', 'href' => catalog_listing_url($pagerPath, 1, array_diff_key($_GET, ['independant' => 1, 'page' => 1]))];
}
if ($scope === 'all' && $countrySel !== '') {
    $c = Publisher::country($countrySel);
    $active[] = ['label' => $c ? $c['name'] : $countrySel, 'href' => catalog_listing_url($pagerPath, 1, array_diff_key($_GET, ['pays' => 1, 'ville' => 1, 'page' => 1]))];
}
if ($scope !== 'city' && $citySel !== '') {
    $active[] = ['label' => ucfirst(str_replace('-', ' ', $citySel)), 'href' => catalog_listing_url($pagerPath, 1, array_diff_key($_GET, ['ville' => 1, 'page' => 1]))];
}

$hidden = static function (string $name, string $value): string {
    return $value === '' ? '' : '<input type="hidden" name="' . e($name) . '" value="' . e($value) . '">';
};
$countLabel = format_int($total) . ' ' . ($total > 1 ? 'maisons' : 'maison');
if ((int) $pager['pages'] > 1) {
    $countLabel .= ' · page ' . (int) $pager['page'] . ' / ' . (int) $pager['pages'];
}

$countryOpts = array_slice($facets['countries'], 0, 8);
if ($scope === 'all' && $countrySel !== '') {
    $inList = false;
    foreach ($countryOpts as $c) {
        if ($c['slug'] === $countrySel) {
            $inList = true;
            break;
        }
    }
    if (!$inList) {
        foreach ($facets['countries'] as $c) {
            if ($c['slug'] === $countrySel) {
                $countryOpts[] = $c;
                break;
            }
        }
    }
}

$groupByLetter = $q === '' && $sort === 'az';
$groups = [];
if ($groupByLetter) {
    foreach ($publishers as $p) {
        $letter = Publisher::letterKey((string) ($p['name'] ?? ''));
        if (!isset($groups[$letter])) {
            $groups[$letter] = ['letter' => $letter, 'items' => []];
        }
        $groups[$letter]['items'][] = $p;
    }
} elseif ($publishers !== []) {
    $groups[] = ['letter' => '', 'items' => $publishers];
}

$sortLinks = [];
foreach (Publisher::SORTS as $key => $label) {
    $query = array_diff_key($_GET, ['page' => 1, 'tri' => 1]);
    if ($key !== 'az') {
        $query['tri'] = $key;
    }
    $sortLinks[] = [
        'key' => $key,
        'label' => $label,
        'href' => catalog_listing_url($pagerPath, 1, $query),
        'on' => $sort === $key,
    ];
}
?>
<div class="search-page me-page">
  <div class="search-banner">
    <span class="search-banner-badge">Annuaire</span>
    <?php if ($viewer): ?>
      Les coordonnées de chaque maison s'affichent sur sa fiche, sur demande, dans la limite de <?= Publisher::CONTACT_DAILY_LIMIT ?> maisons par jour.
    <?php else: ?>
      Fiches en accès libre ; les coordonnées et la prise de contact sont réservées aux membres. <a href="<?= e(url('/inscription')) ?>">Créer un compte gratuit</a> ou <a href="<?= e(url('/connexion')) ?>">se connecter</a>.
    <?php endif; ?>
  </div>

  <div class="search-layout">
    <aside class="search-aside">
      <button type="button" class="me-filters-toggle" data-me-filters-toggle aria-expanded="false">
        <span><?= $active !== [] ? 'Filtres · ' . count($active) . ' actif' . (count($active) > 1 ? 's' : '') : 'Filtres' ?></span>
        <span aria-hidden="true">▼</span>
      </button>
      <div class="me-filters-panel" data-me-filters-panel>
        <?php if ($active !== []): ?>
          <div class="search-aside-head me-filters-reset">
            <span>Filtres actifs</span>
            <a href="<?= e(url($pagerPath)) ?>">Réinitialiser</a>
          </div>
        <?php endif; ?>

        <form class="search-filters me-filters" method="get" action="<?= e(url($pagerPath)) ?>">
          <?= $hidden('q', $q) ?>
          <?= $hidden('tri', $sort === 'az' ? '' : $sort) ?>
          <?php if ($scope === 'all'): ?>
            <div class="sf-group">
              <div class="sf-group-label">Pays</div>
              <div class="sf-opts">
                <?php foreach ($countryOpts as $c): ?>
                  <label class="sf-opt">
                    <input type="radio" name="pays" value="<?= e($c['slug']) ?>"<?= $c['slug'] === $countrySel ? ' checked' : '' ?> data-me-autosubmit>
                    <span class="sf-box" aria-hidden="true"></span>
                    <span class="sf-txt"><?= e($c['name']) ?></span>
                    <span class="sf-n"><?= (int) $c['n'] ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
              <a class="me-filter-more" href="<?= e(url('/maisons-edition/pays')) ?>">Voir les <?= count($facets['countries']) ?> pays →</a>
            </div>
          <?php elseif ($scope === 'country' && $cities !== []): ?>
            <div class="sf-group">
              <div class="sf-group-label">Ville</div>
              <div class="me-city-links">
                <?php foreach (array_slice($cities, 0, 14) as $c): ?>
                  <a href="<?= e(url($c['href'])) ?>"<?= $c['slug'] === $citySel ? ' aria-current="page"' : '' ?>><?= e($c['name']) ?> <em><?= (int) $c['n'] ?></em></a>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if ($facets['genres'] !== []): ?>
            <div class="sf-group">
              <div class="sf-group-label">Genre éditorial</div>
              <div class="me-genre-chips">
                <?php foreach ($facets['genres'] as $opt): ?>
                  <label class="me-chip<?= $opt['v'] === $genre ? ' is-on' : '' ?>">
                    <input type="radio" name="genre" value="<?= e($opt['v']) ?>"<?= $opt['v'] === $genre ? ' checked' : '' ?> data-me-autosubmit>
                    <span><?= e($opt['l']) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <div class="sf-group">
            <div class="sf-group-label">Taille de la structure</div>
            <div class="sf-opts">
              <?php foreach ($facets['sizes'] as $opt): ?>
                <label class="sf-opt">
                  <input type="radio" name="taille" value="<?= e($opt['v']) ?>"<?= $opt['v'] === $size ? ' checked' : '' ?> data-me-autosubmit>
                  <span class="sf-box" aria-hidden="true"></span>
                  <span class="sf-txt"><?= e($opt['l']) ?></span>
                  <span class="sf-n"><?= (int) $opt['n'] ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="sf-group">
            <div class="sf-group-label">Ligne éditoriale</div>
            <div class="me-switch-list">
              <?php foreach ($typologies as $key => $label): ?>
                <label class="me-switch">
                  <span class="me-switch-txt"><?= e($label) ?></span>
                  <input type="radio" name="typologie" value="<?= e($key) ?>"<?= $key === $typology ? ' checked' : '' ?> data-me-autosubmit>
                  <span class="me-switch-track" aria-hidden="true"><span class="me-switch-knob"></span></span>
                </label>
              <?php endforeach; ?>
              <label class="me-switch">
                <span class="me-switch-txt">Indépendants uniquement</span>
                <input type="checkbox" name="independant" value="1"<?= $independent ? ' checked' : '' ?> data-me-autosubmit>
                <span class="me-switch-track" aria-hidden="true"><span class="me-switch-knob"></span></span>
              </label>
            </div>
          </div>
          <button class="btn-ghost me-filters-submit" type="submit">Appliquer les filtres</button>
        </form>

        <div class="search-aside-card me-aside-cta">
          <div class="search-aside-title">Vous êtes une maison d'édition ?</div>
          <p>Revendiquez votre fiche pour la compléter, ajouter votre logo et recevoir les messages des auteurs et des professionnels.</p>
          <a class="btn-navy" href="<?= e(url($viewer ? '/maisons-edition?q=' : '/inscription')) ?>"><?= $viewer ? 'Trouver ma maison' : 'Créer un compte' ?></a>
        </div>
      </div>
    </aside>

    <div>
      <nav class="search-crumb" aria-label="Fil d'Ariane">
        <a href="<?= e(url('/')) ?>">Accueil</a>
        <?php foreach ($crumbs as $i => $crumb): ?>
          <span aria-hidden="true"> · </span>
          <?php if ($i === array_key_last($crumbs)): ?>
            <span><?= e((string) $crumb['name']) ?></span>
          <?php else: ?>
            <a href="<?= e(url((string) $crumb['url'])) ?>"><?= e((string) $crumb['name']) ?></a>
          <?php endif; ?>
        <?php endforeach; ?>
      </nav>

      <div class="search-head me-head">
        <div>
          <h1><?= e((string) ($heading ?? 'Maisons d\'édition')) ?></h1>
          <div class="me-count"><?= e($countLabel) ?></div>
          <?php if (!empty($lead)): ?><p class="journal-lead me-lead"><?= e((string) $lead) ?></p><?php endif; ?>
        </div>
      </div>

      <div class="me-toolbar">
        <?php if ($active !== []): ?>
          <div class="sf-active me-toolbar-chips">
            <?php foreach ($active as $chip): ?>
              <a class="sf-chip" href="<?= e(url($chip['href'])) ?>"><?= e($chip['label']) ?> ✕</a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <?php if ($q === ''): ?>
          <div class="me-sort">
            <span>Trier par</span>
            <?php foreach ($sortLinks as $link): ?>
              <a class="me-chip<?= $link['on'] ? ' is-on' : '' ?>" href="<?= e(url($link['href'])) ?>"<?= $link['on'] ? ' aria-current="true"' : '' ?>><?= e($link['label']) ?></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <?php if ($publishers === []): ?>
        <div class="search-empty" style="padding: 26px;">
          <strong>Aucune maison ne correspond à ces critères.</strong>
          <span>Essayez un autre mot-clé, retirez un filtre, ou <a href="<?= e(url('/maisons-edition/ajouter')) ?>">ajoutez votre maison à l'annuaire</a>.</span>
        </div>
      <?php else: ?>
        <div class="me-agenda">
          <?php foreach ($groups as $group): ?>
            <?php
              $n = count($group['items']);
              $groupCount = format_int($n) . ' ' . ($n > 1 ? 'maisons' : 'maison');
            ?>
            <section class="me-letter">
              <?php if ($group['letter'] !== ''): ?>
                <div class="me-letter-head">
                  <h2><?= e($group['letter']) ?></h2>
                  <span><?= e($groupCount) ?></span>
                  <span class="me-letter-line" aria-hidden="true"></span>
                </div>
              <?php endif; ?>
              <div class="me-letter-list">
                <?php foreach ($group['items'] as $p): ?>
                  <?php
                    $typoKey = (string) ($p['typology_key'] ?? '');
                    $typoClass = $typoKey === 'generaliste' ? 'is-gen' : 'is-spec';
                  ?>
                  <a class="me-row<?= !empty($p['is_claimed']) ? ' is-claimed' : '' ?>" href="<?= e(url((string) $p['href'])) ?>">
                    <div class="me-card-logo">
                      <?php if ($p['logo_src'] !== ''): ?>
                        <img src="<?= e($p['logo_src']) ?>" alt="" width="48" height="48" loading="lazy" decoding="async">
                      <?php else: ?>
                        <span class="me-mono" aria-hidden="true"><?= e($p['initials']) ?></span>
                      <?php endif; ?>
                    </div>
                    <div class="me-row-main">
                      <div class="me-row-top">
                        <h3><?= e((string) $p['name']) ?></h3>
                        <span class="me-typo <?= $typoClass ?>"><?= e($p['typology_label']) ?></span>
                        <span class="me-row-size"><?= e($p['size_label']) ?></span>
                        <?php if (!empty($p['is_claimed'])): ?><span class="me-claimed-pill" title="Fiche gérée par la maison"><?= icon('check-circle', 12) ?> Fiche vérifiée</span><?php endif; ?>
                      </div>
                      <div class="me-row-meta"><?= e($p['location_label'] !== '' ? $p['location_label'] : 'Europe') ?><?= $p['founded_year'] ? ' · depuis ' . (int) $p['founded_year'] : '' ?></div>
                      <?php if (trim((string) $p['description']) !== ''): ?>
                        <p><?= e(\Adl\Data\Seo::clip((string) $p['description'], 160)) ?></p>
                      <?php endif; ?>
                      <?php if ($p['genres'] !== []): ?>
                        <div class="me-row-genres">
                          <?php foreach (array_slice($p['genres'], 0, 3) as $g): ?>
                            <span><?= e($g) ?></span>
                          <?php endforeach; ?>
                          <?php if (count($p['genres']) > 3): ?><span>+<?= count($p['genres']) - 3 ?></span><?php endif; ?>
                        </div>
                      <?php endif; ?>
                    </div>
                    <span class="btn-navy me-row-cta">Voir la fiche</span>
                  </a>
                <?php endforeach; ?>
              </div>
            </section>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php
        $pagerLabel = 'Pagination des maisons d\'édition';
        require ADL_ROOT . '/app/Views/partials/search-pager.php';
      ?>

      <?php if ($publishers !== [] || $scope !== 'all'): ?>
        <aside class="me-add-cta">
          <div>
            <strong>Vous représentez une maison d'édition absente de l'annuaire ?</strong>
            <span>Ajoutez-la en quelques minutes : la fiche est rattachée à votre compte et publiée après vérification par l'équipe.</span>
          </div>
          <a class="btn-navy" href="<?= e(url('/maisons-edition/ajouter')) ?>">Ajouter ma maison</a>
        </aside>
      <?php endif; ?>

      <?php if ($scope === 'country' && $cities !== [] && (int) $pager['page'] === 1): ?>
        <section class="me-geo">
          <h2>Villes <?= e(\Adl\Controllers\PublisherController::countryPhrase((string) ($country['name'] ?? ''))) ?></h2>
          <div class="me-geo-links">
            <?php foreach ($cities as $c): ?>
              <a href="<?= e(url($c['href'])) ?>"><?= e($c['name']) ?> <em><?= (int) $c['n'] ?></em></a>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endif; ?>

      <?php if ($scope === 'all' && (int) $pager['page'] === 1 && $active === [] && $sort === 'az'): ?>
        <section class="me-about">
          <h2>Un annuaire vivant des éditeurs européens</h2>
          <p>Cet annuaire recense les maisons d'édition en activité en France et dans les principaux pays d'Europe : grands groupes et leurs marques, éditeurs indépendants, micro-structures, presses universitaires, maisons régionales. Chaque fiche présente la ligne éditoriale, les genres publiés, la taille de la structure, la ville du siège et l'année de fondation quand elle est connue.</p>
          <p>Les coordonnées sont réservées aux membres connectés, pour protéger les maisons des sollicitations automatisées. Les éditeurs peuvent revendiquer leur fiche, ou <a href="<?= e(url('/maisons-edition/ajouter')) ?>">ajouter leur maison</a> si elle manque, pour la tenir à jour et échanger avec les auteurs, correcteurs, illustrateurs et traducteurs de la plateforme.</p>
        </section>
      <?php endif; ?>
    </div>
  </div>
</div>
<script>
(function () {
  var form = document.querySelector('.me-filters');
  if (form) {
    form.querySelectorAll('[data-me-autosubmit]').forEach(function (el) {
      if (el.type === 'radio') {
        el.addEventListener('mousedown', function () {
          el.dataset.wasOn = el.checked ? '1' : '';
        });
        el.addEventListener('click', function () {
          if (el.dataset.wasOn === '1') el.checked = false;
          form.requestSubmit ? form.requestSubmit() : form.submit();
        });
      } else {
        el.addEventListener('change', function () { form.requestSubmit ? form.requestSubmit() : form.submit(); });
      }
    });
    var submit = form.querySelector('.me-filters-submit');
    if (submit) submit.hidden = true;
  }
  var toggle = document.querySelector('[data-me-filters-toggle]');
  var panel = document.querySelector('[data-me-filters-panel]');
  if (toggle && panel) {
    toggle.addEventListener('click', function () {
      var open = panel.classList.toggle('is-open');
      toggle.classList.toggle('is-open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }
})();
</script>
