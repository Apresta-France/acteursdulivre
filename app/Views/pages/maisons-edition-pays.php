<?php
$countries = $countries ?? [];
$citiesByCountry = $citiesByCountry ?? [];
$total = 0;
foreach ($countries as $c) {
    $total += (int) $c['n'];
}
$grouped = [];
$ungrouped = [];
foreach ($countries as $c) {
    $zone = \Adl\Data\Geography::zoneSlugForCountry((string) $c['slug']);
    if ($zone === '' || !isset(\Adl\Data\Geography::ZONES[$zone])) {
        $ungrouped[] = $c;
        continue;
    }
    $grouped[$zone][] = $c;
}
$zoneOrder = array_keys(\Adl\Data\Geography::ZONES);
?>
<div class="journal-page me-countries-page">
  <nav class="search-crumb" aria-label="Fil d'Ariane">
    <a href="<?= e(url('/')) ?>">Accueil</a>
    <span aria-hidden="true"> · </span>
    <a href="<?= e(url('/communaute')) ?>">Communauté</a>
    <span aria-hidden="true"> · </span>
    <a href="<?= e(url('/maisons-edition')) ?>">Maisons d'édition</a>
    <span aria-hidden="true"> · </span>
    <span>Par pays</span>
  </nav>
  <div class="search-head">
    <div>
      <h1>Maisons d'édition par pays <span><?= e(format_int($total)) ?> maisons · <?= count($countries) ?> pays</span></h1>
      <p class="journal-lead">Choisissez une zone ou un pays pour parcourir ses éditeurs, puis affinez par ville, genre ou taille de structure.</p>
    </div>
    <a class="btn-navy" href="<?= e(url('/maisons-edition')) ?>">Rechercher une maison</a>
  </div>

  <?php foreach ($zoneOrder as $zoneSlug): ?>
    <?php if (empty($grouped[$zoneSlug])) { continue; } ?>
    <?php $zoneMeta = \Adl\Data\Geography::ZONES[$zoneSlug]; ?>
    <section class="me-geo" style="margin-top: 28px;">
      <h2><a href="<?= e(url('/maisons-edition/zone/' . $zoneSlug)) ?>"><?= e($zoneMeta['name']) ?></a></h2>
      <div class="me-countries">
        <?php foreach ($grouped[$zoneSlug] as $c): ?>
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
    </section>
  <?php endforeach; ?>

  <?php if ($ungrouped !== []): ?>
    <section class="me-geo" style="margin-top: 28px;">
      <h2>Autres pays</h2>
      <div class="me-countries">
        <?php foreach ($ungrouped as $c): ?>
          <div class="me-country-card">
            <a class="me-country-name" href="<?= e(url($c['href'])) ?>"><?= e($c['name']) ?> <em><?= (int) $c['n'] ?></em></a>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</div>
