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

$groups = [];
foreach ($salons as $event) {
    $key = (string) ($event['month_key'] ?? 'sans-date');
    if (!isset($groups[$key])) {
        $groups[$key] = [
            'label' => (string) ($event['month_heading'] ?? 'Dates à confirmer'),
            'items' => [],
        ];
    }
    $groups[$key]['items'][] = $event;
}
?>
<div class="search-page co-salons-page">
  <section class="profile-hero salon-hero">
    <div class="profile-hero-main">
      <nav class="search-crumb salon-crumb" aria-label="Fil d'Ariane">
        <a href="<?= e(url('/')) ?>">Accueil</a>
        <span aria-hidden="true"> · </span>
        <a href="<?= e(url('/communaute')) ?>">Communauté</a>
        <span aria-hidden="true"> · </span>
        <span>Agenda des salons</span>
      </nav>
      <h1>Agenda des salons du livre</h1>
      <p class="profile-hero-sub">Salons du livre, festivals et foires en France et en Europe, de septembre 2026 à septembre 2027. Dates à reconfirmer sur le site de chaque manifestation.</p>
    </div>
    <div class="profile-hero-actions">
      <a class="btn-orange" href="<?= e(url('/salons/ajouter')) ?>">Ajouter un salon</a>
    </div>
  </section>

  <div class="search-layout">
    <aside class="search-aside">
      <div class="search-aside-head">
        <span>Filtres</span>
        <?php if ($active !== []): ?>
          <a href="<?= e(url($pagerPath)) ?>">Tout effacer</a>
        <?php endif; ?>
      </div>
      <?php if ($active !== []): ?>
        <div class="sf-active">
          <?php foreach ($active as $chip): ?>
            <a class="sf-chip" href="<?= e(url($chip['href'])) ?>"><?= e($chip['label']) ?> ✕</a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form class="search-filters salon-filters" method="get" action="<?= e(url($pagerPath)) ?>">
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
                  <input type="checkbox" name="region" value="<?= e($opt['v']) ?>"<?= $opt['v'] === $region ? ' checked' : '' ?> data-salon-autosubmit>
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
            <div class="salon-cat-chips">
              <?php foreach ($facets['categories'] as $opt): ?>
                <label class="salon-chip<?= $opt['v'] === $category ? ' is-on' : '' ?>">
                  <input type="checkbox" name="cat" value="<?= e($opt['v']) ?>"<?= $opt['v'] === $category ? ' checked' : '' ?> data-salon-autosubmit>
                  <span><?= e($opt['l']) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
        <button class="btn-ghost me-filters-submit" type="submit">Appliquer les filtres</button>
      </form>

      <div class="search-aside-card side-card-warm">
        <div class="search-aside-title">Un salon manque ?</div>
        <p>Cette liste est une sélection France + Europe, sept. 2026 – sept. 2027. Proposez le vôtre : l’équipe le publie après vérification.</p>
        <a class="btn-navy" href="<?= e(url('/salons/ajouter')) ?>">Ajouter un salon</a>
      </div>
    </aside>

    <div>
      <div class="salon-toolbar">
        <span class="salon-count"><?= e($countLabel) ?></span>
      </div>

      <?php if ($salons === []): ?>
        <div class="search-empty" style="padding: 26px;">
          <strong>Aucun salon ne correspond à ces critères.</strong>
          <span>Essayez un autre mot-clé, retirez un filtre, ou <a href="<?= e(url('/salons/ajouter')) ?>">proposez un salon</a>.</span>
        </div>
      <?php else: ?>
        <div class="salon-agenda">
          <?php foreach ($groups as $group): ?>
            <?php
              $n = count($group['items']);
              $groupCount = format_int($n) . ' ' . ($n > 1 ? 'salons' : 'salon');
            ?>
            <section class="salon-month">
              <div class="salon-month-head">
                <h2><?= e($group['label']) ?></h2>
                <span><?= e($groupCount) ?></span>
                <span class="salon-month-line" aria-hidden="true"></span>
              </div>
              <div class="salon-month-list">
                <?php foreach ($group['items'] as $event): ?>
                  <?php
                    $regionOrCountry = (string) ($event['region'] ?? '');
                    if ($regionOrCountry === '' || $regionOrCountry === '—') {
                        $countryName = (string) ($event['country'] ?? '');
                        $regionOrCountry = ($countryName !== '' && $countryName !== 'France') ? $countryName : '';
                    }
                    $attendance = (string) ($event['attendance'] ?? '');
                    if (str_starts_with(mb_strtolower($attendance), 'non trouvé')) {
                        $attendance = '';
                    }
                    $metaParts = array_filter([
                        (string) ($event['when_dates'] ?? $event['when'] ?? ''),
                        (string) ($event['city'] ?? ''),
                        $regionOrCountry,
                        $attendance,
                    ], static fn (string $v): bool => $v !== '' && $v !== '—');
                  ?>
                  <a class="salon-row" href="<?= e(url((string) ($event['href'] ?? '/salons'))) ?>">
                    <time class="salon-row-date" datetime="<?= e((string) ($event['iso'] ?? '')) ?>">
                      <strong><?= e((string) ($event['day'] ?? '')) ?></strong>
                      <span><?= e((string) ($event['month'] ?? '')) ?></span>
                    </time>
                    <div class="salon-row-main">
                      <div class="salon-row-top">
                        <h3><?= e((string) ($event['name'] ?? '')) ?></h3>
                        <?php if (!empty($event['kind'])): ?><span class="mk-tag"><?= e((string) $event['kind']) ?></span><?php endif; ?>
                        <?php if (empty($event['confirmed'])): ?>
                          <span class="salon-flag">Dates à confirmer</span>
                        <?php endif; ?>
                      </div>
                      <?php if ($metaParts !== []): ?>
                        <p><?= e(implode(' · ', $metaParts)) ?></p>
                      <?php endif; ?>
                    </div>
                    <span class="btn-navy salon-row-cta">Voir la fiche</span>
                  </a>
                <?php endforeach; ?>
              </div>
            </section>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php
        $pagerLabel = 'Pagination des salons';
        require ADL_ROOT . '/app/Views/partials/search-pager.php';
      ?>

      <aside class="salon-cta">
        <div>
          <strong>Votre salon n’est pas dans l’agenda ?</strong>
          <span>Proposez-le : l’équipe vérifie les dates auprès de l’organisateur avant publication.</span>
        </div>
        <a class="btn-orange" href="<?= e(url('/salons/ajouter')) ?>">Ajouter un salon</a>
      </aside>
    </div>
  </div>
</div>
<script>
(function () {
  var form = document.querySelector('.salon-filters');
  if (!form) return;
  form.querySelectorAll('[data-salon-autosubmit]').forEach(function (el) {
    el.addEventListener('change', function () {
      if (el.checked) {
        form.querySelectorAll('input[name="' + el.name + '"]').forEach(function (other) {
          if (other !== el) other.checked = false;
        });
      }
      form.requestSubmit ? form.requestSubmit() : form.submit();
    });
  });
  var submit = form.querySelector('.me-filters-submit');
  if (submit) submit.hidden = true;
})();
</script>
