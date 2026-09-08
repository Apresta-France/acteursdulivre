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
$cities = $cities ?? [];
$sizes = $sizes ?? Publisher::SIZES;
$typologies = $typologies ?? Publisher::TYPOLOGIES;
$headingCount = (int) ($heading_count ?? $total);

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
      <div class="search-aside-head">
        <span>Filtres</span>
        <?php if ($active !== []): ?>
          <a href="<?= e(url($pagerPath)) ?>">Réinitialiser</a>
        <?php endif; ?>
      </div>
      <?php if ($active !== []): ?>
        <div class="sf-active">
          <?php foreach ($active as $chip): ?>
            <a class="sf-chip" href="<?= e(url($chip['href'])) ?>"><?= e($chip['label']) ?> ✕</a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form class="search-filters me-filters" method="get" action="<?= e(url($pagerPath)) ?>">
        <?= $hidden('q', $q) ?>
        <?php if ($scope === 'all'): ?>
          <div class="sf-group">
            <label class="sf-group-label" for="me-pays">Pays</label>
            <select class="input" id="me-pays" name="pays" data-me-autosubmit>
              <option value="">Toute l'Europe</option>
              <?php foreach ($facets['countries'] as $c): ?>
                <option value="<?= e($c['slug']) ?>"<?= $c['slug'] === $countrySel ? ' selected' : '' ?>><?= e($c['name']) ?> (<?= (int) $c['n'] ?>)</option>
              <?php endforeach; ?>
            </select>
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
            <div class="sf-opts">
              <?php foreach ($facets['genres'] as $opt): ?>
                <label class="sf-opt">
                  <input type="radio" name="genre" value="<?= e($opt['v']) ?>"<?= $opt['v'] === $genre ? ' checked' : '' ?> data-me-autosubmit>
                  <span class="sf-box" aria-hidden="true"></span>
                  <span class="sf-txt"><?= e($opt['l']) ?></span>
                  <span class="sf-n"><?= (int) $opt['n'] ?></span>
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
          <div class="sf-opts">
            <?php foreach ($typologies as $key => $label): ?>
              <label class="sf-opt">
                <input type="radio" name="typologie" value="<?= e($key) ?>"<?= $key === $typology ? ' checked' : '' ?> data-me-autosubmit>
                <span class="sf-box" aria-hidden="true"></span>
                <span class="sf-txt"><?= e($label) ?></span>
              </label>
            <?php endforeach; ?>
            <label class="sf-opt">
              <input type="checkbox" name="independant" value="1"<?= $independent ? ' checked' : '' ?> data-me-autosubmit>
              <span class="sf-box" aria-hidden="true"></span>
              <span class="sf-txt">Indépendants uniquement</span>
            </label>
          </div>
        </div>
        <button class="btn-ghost me-filters-submit" type="submit">Appliquer les filtres</button>
      </form>

      <div class="search-aside-card">
        <div class="search-aside-title">Vous êtes une maison d'édition ?</div>
        <p>Retrouvez votre fiche et revendiquez-la : vous pourrez la compléter, ajouter votre logo et recevoir les messages des auteurs et professionnels.</p>
        <a class="btn-ghost" href="<?= e(url($viewer ? '/maisons-edition?q=' : '/inscription')) ?>"><?= $viewer ? 'Trouver ma maison' : 'Créer un compte' ?></a>
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
          <h1><?= e((string) ($heading ?? 'Maisons d\'édition')) ?> <span><?= e($countLabel) ?></span></h1>
          <?php if (!empty($lead)): ?><p class="journal-lead me-lead"><?= e((string) $lead) ?></p><?php endif; ?>
        </div>
      </div>

      <?php if ($scope === 'all' && $q === '' && $genre === '' && $countrySel === ''): ?>
        <div class="me-country-strip">
          <?php foreach (array_slice($facets['countries'], 0, 12) as $c): ?>
            <a class="chip" href="<?= e(url($c['href'])) ?>"><?= e($c['name']) ?> <em><?= (int) $c['n'] ?></em></a>
          <?php endforeach; ?>
          <a class="chip is-more" href="<?= e(url('/maisons-edition/pays')) ?>">Tous les pays →</a>
        </div>
      <?php endif; ?>

      <?php if ($publishers === []): ?>
        <div class="search-empty" style="padding: 26px;">
          <strong>Aucune maison ne correspond à ces critères.</strong>
          <span>Essayez un autre mot-clé, retirez un filtre, ou <a href="<?= e(url('/contact')) ?>">suggérez-nous une maison manquante</a>.</span>
        </div>
      <?php else: ?>
        <div class="me-grid">
          <?php foreach ($publishers as $p): ?>
            <a class="me-card<?= !empty($p['is_claimed']) ? ' is-claimed' : '' ?>" href="<?= e(url((string) $p['href'])) ?>">
              <div class="me-card-logo">
                <?php if ($p['logo_src'] !== ''): ?>
                  <img src="<?= e($p['logo_src']) ?>" alt="" width="56" height="56" loading="lazy" decoding="async">
                <?php else: ?>
                  <span class="me-mono" aria-hidden="true"><?= e($p['initials']) ?></span>
                <?php endif; ?>
              </div>
              <div class="me-card-body">
                <div class="me-card-kicker">
                  <span><?= e($p['typology_label']) ?></span>
                  <span>·</span>
                  <span><?= e($p['size_label']) ?></span>
                  <?php if (!empty($p['is_claimed'])): ?><span class="me-claimed-pill" title="Fiche gérée par la maison"><?= icon('check-circle', 12) ?> Fiche vérifiée</span><?php endif; ?>
                </div>
                <strong><?= e((string) $p['name']) ?></strong>
                <span class="me-card-where"><?= e($p['location_label'] !== '' ? $p['location_label'] : 'Europe') ?><?= $p['founded_year'] ? ' · depuis ' . (int) $p['founded_year'] : '' ?></span>
                <?php if (trim((string) $p['description']) !== ''): ?>
                  <p><?= e(\Adl\Data\Seo::clip((string) $p['description'], 130)) ?></p>
                <?php endif; ?>
                <?php if ($p['genres'] !== []): ?>
                  <div class="me-card-genres">
                    <?php foreach (array_slice($p['genres'], 0, 3) as $g): ?>
                      <span class="chip-static dark"><?= e($g) ?></span>
                    <?php endforeach; ?>
                    <?php if (count($p['genres']) > 3): ?><span class="chip-static dark">+<?= count($p['genres']) - 3 ?></span><?php endif; ?>
                  </div>
                <?php endif; ?>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php
        $pagerLabel = 'Pagination des maisons d\'édition';
        require ADL_ROOT . '/app/Views/partials/search-pager.php';
      ?>

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

      <?php if ($scope === 'all' && (int) $pager['page'] === 1 && $active === []): ?>
        <section class="me-about">
          <h2>Un annuaire vivant des éditeurs européens</h2>
          <p>Cet annuaire recense les maisons d'édition en activité en France et dans les principaux pays d'Europe : grands groupes et leurs marques, éditeurs indépendants, micro-structures, presses universitaires, maisons régionales. Chaque fiche présente la ligne éditoriale, les genres publiés, la taille de la structure, la ville du siège et l'année de fondation quand elle est connue.</p>
          <p>Les coordonnées (site, e-mail, adresse) sont réservées aux membres connectés, pour protéger les maisons des sollicitations automatisées. Les éditeurs peuvent revendiquer leur fiche pour la tenir à jour et échanger avec les auteurs, correcteurs, illustrateurs et traducteurs de la plateforme.</p>
        </section>
      <?php endif; ?>
    </div>
  </div>
</div>
<script>
(function () {
  var form = document.querySelector('.me-filters');
  if (!form) return;
  form.querySelectorAll('[data-me-autosubmit]').forEach(function (el) {
    el.addEventListener('change', function () { form.requestSubmit ? form.requestSubmit() : form.submit(); });
  });
  var submit = form.querySelector('.me-filters-submit');
  if (submit) submit.hidden = true;
})();
</script>
