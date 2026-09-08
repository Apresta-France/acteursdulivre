<?php
$salons = $salons ?? [];
$pager = $pager ?? ['page' => 1, 'pages' => 1, 'total' => 0];
$filters = $filters ?? [];
$facets = $facets ?? ['categories' => [], 'regions' => [], 'countries' => []];
$q = (string) ($filters['q'] ?? '');
$category = (string) ($filters['category'] ?? '');
$region = (string) ($filters['region'] ?? '');
$country = (string) ($filters['country'] ?? '');
$pagerPath = '/salons';
$total = (int) ($pager['total'] ?? 0);

$active = [];
if ($q !== '') {
    $active[] = ['label' => '« ' . $q . ' »', 'href' => catalog_listing_url($pagerPath, 1, array_diff_key($_GET, ['q' => 1, 'page' => 1]))];
}
if ($category !== '') {
    $label = $category;
    foreach ($facets['categories'] as $opt) {
        if ($opt['v'] === $category) {
            $label = $opt['l'];
            break;
        }
    }
    $active[] = ['label' => $label, 'href' => catalog_listing_url($pagerPath, 1, array_diff_key($_GET, ['cat' => 1, 'page' => 1]))];
}
if ($region !== '') {
    $label = $region;
    foreach ($facets['regions'] as $opt) {
        if ($opt['v'] === $region) {
            $label = $opt['l'];
            break;
        }
    }
    $active[] = ['label' => $label, 'href' => catalog_listing_url($pagerPath, 1, array_diff_key($_GET, ['region' => 1, 'page' => 1]))];
}
if ($country !== '') {
    $label = $country;
    foreach ($facets['countries'] as $opt) {
        if ($opt['v'] === $country) {
            $label = $opt['l'];
            break;
        }
    }
    $active[] = ['label' => $label, 'href' => catalog_listing_url($pagerPath, 1, array_diff_key($_GET, ['pays' => 1, 'page' => 1]))];
}

$hidden = static function (string $name, string $value): string {
    return $value === '' ? '' : '<input type="hidden" name="' . e($name) . '" value="' . e($value) . '">';
};
$countLabel = format_int($total) . ' ' . ($total > 1 ? 'salons' : 'salon');
if ((int) $pager['pages'] > 1) {
    $countLabel .= ' · page ' . (int) $pager['page'] . ' / ' . (int) $pager['pages'];
}
?>
<div class="search-page co-salons-page">
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

      <form class="search-filters" method="get" action="<?= e(url($pagerPath)) ?>">
        <?= $hidden('q', $q) ?>
        <?php if ($facets['countries'] !== []): ?>
          <div class="sf-group">
            <label class="sf-group-label" for="salon-pays">Pays</label>
            <select class="input" id="salon-pays" name="pays" onchange="this.form.submit()">
              <option value="">France et Europe</option>
              <?php foreach ($facets['countries'] as $opt): ?>
                <option value="<?= e($opt['v']) ?>"<?= $opt['v'] === $country ? ' selected' : '' ?>><?= e($opt['l']) ?> (<?= (int) $opt['n'] ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>
        <?php if ($facets['regions'] !== [] && ($country === '' || $country === 'france')): ?>
          <div class="sf-group">
            <div class="sf-group-label">Région</div>
            <div class="sf-opts">
              <?php foreach ($facets['regions'] as $opt): ?>
                <label class="sf-opt">
                  <input type="radio" name="region" value="<?= e($opt['v']) ?>"<?= $opt['v'] === $region ? ' checked' : '' ?> onchange="this.form.submit()">
                  <span class="sf-box" aria-hidden="true"></span>
                  <span class="sf-txt"><?= e($opt['l']) ?></span>
                  <span class="sf-n"><?= (int) $opt['n'] ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
        <?php if ($facets['categories'] !== []): ?>
          <div class="sf-group">
            <div class="sf-group-label">Catégorie</div>
            <div class="sf-opts">
              <?php foreach ($facets['categories'] as $opt): ?>
                <label class="sf-opt">
                  <input type="radio" name="cat" value="<?= e($opt['v']) ?>"<?= $opt['v'] === $category ? ' checked' : '' ?> onchange="this.form.submit()">
                  <span class="sf-box" aria-hidden="true"></span>
                  <span class="sf-txt"><?= e($opt['l']) ?></span>
                  <span class="sf-n"><?= (int) $opt['n'] ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
        <button class="btn-ghost me-filters-submit" type="submit">Appliquer les filtres</button>
      </form>

      <div class="search-aside-card">
        <div class="search-aside-title">Un salon manque ?</div>
        <p>Cette liste est une sélection France + Europe, sept. 2026 – sept. 2027. Écrivez-nous pour en ajouter un.</p>
        <a class="btn-ghost" href="<?= e(url('/contact')) ?>">Signaler un salon</a>
      </div>
    </aside>

    <div>
      <nav class="search-crumb" aria-label="Fil d'Ariane">
        <a href="<?= e(url('/')) ?>">Accueil</a>
        <span aria-hidden="true"> · </span>
        <a href="<?= e(url('/communaute')) ?>">Communauté</a>
        <span aria-hidden="true"> · </span>
        <span>Agenda des salons</span>
      </nav>

      <div class="search-head">
        <div>
          <h1>Agenda des salons <span><?= e($countLabel) ?></span></h1>
          <p class="journal-lead">Salons du livre, festivals et foires en France et en Europe. Dates à reconfirmer sur le site de chaque manifestation.</p>
        </div>
      </div>

      <form class="search" method="get" action="<?= e(url($pagerPath)) ?>" role="search" style="max-width: 520px; margin-bottom: 22px;">
        <?= $hidden('cat', $category) ?>
        <?= $hidden('region', $region) ?>
        <?= $hidden('pays', $country) ?>
        <label class="sr-only" for="salon-q">Rechercher un salon</label>
        <input id="salon-q" type="search" name="q" value="<?= e($q) ?>" placeholder="Nom, ville, région…">
        <button type="submit">Chercher</button>
      </form>

      <?php if ($salons === []): ?>
        <div class="search-empty" style="padding: 26px;">
          <strong>Aucun salon ne correspond à ces critères.</strong>
          <span>Essayez un autre mot-clé, retirez un filtre, ou <a href="<?= e(url('/contact')) ?>">signalez un salon</a>.</span>
        </div>
      <?php else: ?>
        <div class="co-agenda">
          <?php foreach ($salons as $event): ?>
            <a class="co-event" href="<?= e(url((string) ($event['href'] ?? '/salons'))) ?>">
              <time class="co-event-date" datetime="<?= e((string) ($event['iso'] ?? '')) ?>">
                <strong><?= e((string) ($event['day'] ?? '')) ?></strong>
                <span><?= e((string) ($event['month'] ?? '')) ?></span>
              </time>
              <div class="co-event-main">
                <div class="co-event-tags">
                  <?php if (!empty($event['kind'])): ?><span class="mk-tag"><?= e((string) $event['kind']) ?></span><?php endif; ?>
                  <?php if (!empty($event['when'])): ?><span class="co-event-when"><?= e((string) $event['when']) ?></span><?php endif; ?>
                </div>
                <h3><?= e((string) ($event['name'] ?? '')) ?></h3>
                <p><?= e((string) ($event['place'] ?? '')) ?></p>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php
        $pagerLabel = 'Pagination des salons';
        require ADL_ROOT . '/app/Views/partials/search-pager.php';
      ?>
    </div>
  </div>
</div>
