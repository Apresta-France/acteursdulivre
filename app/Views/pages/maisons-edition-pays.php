<?php
$countries = $countries ?? [];
$citiesByCountry = $citiesByCountry ?? [];
$total = 0;
foreach ($countries as $c) {
    $total += (int) $c['n'];
}
?>
<div class="journal-page me-countries-page">
  <nav class="search-crumb" aria-label="Fil d'Ariane">
    <a href="<?= e(url('/')) ?>">Accueil</a>
    <span aria-hidden="true"> · </span>
    <a href="<?= e(url('/maisons-edition')) ?>">Maisons d'édition</a>
    <span aria-hidden="true"> · </span>
    <span>Par pays</span>
  </nav>
  <div class="search-head">
    <div>
      <h1>Maisons d'édition par pays <span><?= e(format_int($total)) ?> maisons · <?= count($countries) ?> pays</span></h1>
      <p class="journal-lead">Choisissez un pays pour parcourir ses éditeurs, puis affinez par ville, genre ou taille de structure.</p>
    </div>
    <a class="btn-navy" href="<?= e(url('/maisons-edition')) ?>">Rechercher une maison</a>
  </div>

  <div class="me-countries">
    <?php foreach ($countries as $c): ?>
      <div class="me-country-card">
        <a class="me-country-name" href="<?= e(url($c['href'])) ?>"><?= e($c['name']) ?> <em><?= (int) $c['n'] ?></em></a>
        <?php if (!empty($citiesByCountry[$c['slug']])): ?>
          <div class="me-country-cities">
            <?php foreach ($citiesByCountry[$c['slug']] as $city): ?>
              <a href="<?= e(url($city['href'])) ?>"><?= e($city['name']) ?></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
