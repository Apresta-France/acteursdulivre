<?php
$contests = $contests ?? [];
$pager = $pager ?? ['page' => 1, 'pages' => 1, 'total' => 0];
$filters = $filters ?? [];
$facets = $facets ?? ['categories' => [], 'countries' => [], 'zones' => [], 'kinds' => []];
$q = (string) ($filters['q'] ?? '');
$category = (string) ($filters['category'] ?? '');
$kind = (string) ($filters['kind'] ?? '');
$fee = (string) ($filters['fee'] ?? '');
$country = (string) ($filters['country'] ?? '');
$zone = (string) ($filters['zone'] ?? '');
$tri = (string) ($filters['tri'] ?? '');
$pagerPath = (string) ($pagerPath ?? '/prix-et-concours');
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
if ($kind !== '') {
    $label = $kind;
    foreach (($facets['kinds'] ?? []) as $opt) {
        if ($opt['v'] === $kind) {
            $label = $opt['l'];
            break;
        }
    }
    $active[] = ['label' => $label, 'href' => catalog_listing_url($pagerPath, 1, array_diff_key($_GET, ['type' => 1, 'page' => 1]))];
}
if ($fee !== '') {
    $active[] = ['label' => $fee === 'gratuit' ? 'Gratuit' : 'Payant', 'href' => catalog_listing_url($pagerPath, 1, array_diff_key($_GET, ['frais' => 1, 'page' => 1]))];
}
if ($zone !== '') {
    $label = $zone;
    foreach (($facets['zones'] ?? []) as $opt) {
        if ($opt['v'] === $zone) {
            $label = $opt['l'];
            break;
        }
    }
    $active[] = ['label' => $label, 'href' => catalog_listing_url($pagerPath, 1, array_diff_key($_GET, ['zone' => 1, 'pays' => 1, 'page' => 1]))];
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
if ($tri !== '') {
    $active[] = ['label' => 'Tri : ' . (\Adl\Models\Contest::SORTS[$tri] ?? $tri), 'href' => catalog_listing_url($pagerPath, 1, array_diff_key($_GET, ['tri' => 1, 'page' => 1]))];
}

$hidden = static function (string $name, string $value): string {
    return $value === '' ? '' : '<input type="hidden" name="' . e($name) . '" value="' . e($value) . '">';
};
$countLabel = count_label($total, 'prix ou concours', 'prix et concours');
if ((int) $pager['pages'] > 1) {
    $countLabel .= ' · page ' . (int) $pager['page'] . ' / ' . (int) $pager['pages'];
}

$groups = [];
foreach ($contests as $event) {
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
        <span>Prix et concours</span>
      </nav>
      <h1><?= e((string) ($contestHeading ?? 'Prix et concours littéraires')) ?></h1>
      <p class="profile-hero-sub"><?= e((string) ($contestIntro ?? 'Prix littéraires, concours et appels à textes en France, en Europe et dans la francophonie : dates limites de candidature, dotations et conditions. À reconfirmer dans le règlement de chaque récompense.')) ?></p>
    </div>
    <div class="profile-hero-actions">
      <a class="btn-orange" href="<?= e(url('/prix-et-concours/ajouter')) ?>">Proposer un prix</a>
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
        <?php $hubGeo = str_contains($pagerPath, '/prix-et-concours/pays/') || str_contains($pagerPath, '/prix-et-concours/zone/'); ?>
        <div class="sf-group">
          <label class="sf-group-label" for="prix-tri">Trier par</label>
          <select class="input" id="prix-tri" name="tri" onchange="this.form.submit()">
            <?php foreach (\Adl\Models\Contest::SORTS as $v => $l): ?>
              <option value="<?= e($v) ?>"<?= $v === $tri ? ' selected' : '' ?>><?= e($l) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php if (!$hubGeo && !empty($facets['zones'])): ?>
          <div class="sf-group">
            <label class="sf-group-label" for="prix-zone">Zone</label>
            <select class="input" id="prix-zone" name="zone" onchange="this.form.submit()">
              <option value="">Toutes les zones</option>
              <?php foreach ($facets['zones'] as $opt): ?>
                <option value="<?= e($opt['v']) ?>"<?= $opt['v'] === $zone ? ' selected' : '' ?>><?= e($opt['l']) ?> (<?= (int) $opt['n'] ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>
        <?php if (!$hubGeo && $facets['countries'] !== []): ?>
          <div class="sf-group">
            <label class="sf-group-label" for="prix-pays">Pays</label>
            <select class="input" id="prix-pays" name="pays" onchange="this.form.submit()">
              <option value="">Tous les pays</option>
              <?php foreach ($facets['countries'] as $opt): ?>
                <option value="<?= e($opt['v']) ?>"<?= $opt['v'] === $country ? ' selected' : '' ?>><?= e($opt['l']) ?> (<?= (int) $opt['n'] ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>
        <?php if (!empty($facets['kinds'])): ?>
          <div class="sf-group">
            <label class="sf-group-label" for="prix-type">Type</label>
            <select class="input" id="prix-type" name="type" onchange="this.form.submit()">
              <option value="">Tous les types</option>
              <?php foreach ($facets['kinds'] as $opt): ?>
                <option value="<?= e($opt['v']) ?>"<?= $opt['v'] === $kind ? ' selected' : '' ?>><?= e($opt['l']) ?> (<?= (int) $opt['n'] ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>
        <div class="sf-group">
          <label class="sf-group-label" for="prix-frais">Participation</label>
          <select class="input" id="prix-frais" name="frais" onchange="this.form.submit()">
            <option value="">Gratuit et payant</option>
            <option value="gratuit"<?= $fee === 'gratuit' ? ' selected' : '' ?>>Gratuit</option>
            <option value="payant"<?= $fee === 'payant' ? ' selected' : '' ?>>Payant</option>
          </select>
        </div>
        <?php if ($facets['categories'] !== []): ?>
          <div class="sf-group">
            <div class="sf-group-label">Genre</div>
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
        <div class="search-aside-title">Un prix manque ?</div>
        <p>Cette liste couvre la France, l’Europe et la francophonie. Proposez une récompense ou un concours : l’équipe la publie après vérification.</p>
        <a class="btn-navy" href="<?= e(url('/prix-et-concours/ajouter')) ?>">Proposer un prix</a>
      </div>
    </aside>

    <div>
      <div class="salon-toolbar">
        <span class="salon-count"><?= e($countLabel) ?></span>
      </div>

      <?php if ($contests === []): ?>
        <div class="search-empty" style="padding: 26px;">
          <strong>Aucun prix ou concours ne correspond à ces critères.</strong>
          <span>Essayez un autre mot-clé, retirez un filtre, ou <a href="<?= e(url('/prix-et-concours/ajouter')) ?>">proposez une récompense</a>.</span>
        </div>
      <?php else: ?>
        <div class="salon-agenda">
          <?php foreach ($groups as $group): ?>
            <?php
              $n = count($group['items']);
              $groupCount = count_label($n, 'prix ou concours', 'prix et concours');
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
                    $metaParts = array_filter([
                        (string) ($event['deadline_label'] ?? ''),
                        (string) ($event['place'] ?? ''),
                        (string) ($event['prize'] ?? ''),
                        !empty($event['is_free']) ? 'Gratuit' : '',
                    ], static fn (string $v): bool => $v !== '' && $v !== '—');
                  ?>
                  <a class="salon-row" href="<?= e(url((string) ($event['href'] ?? '/prix-et-concours'))) ?>">
                    <time class="salon-row-date" datetime="<?= e((string) ($event['iso'] ?? '')) ?>">
                      <strong><?= e((string) ($event['day'] ?? '')) ?></strong>
                      <span><?= e((string) ($event['month'] ?? '')) ?></span>
                    </time>
                    <div class="salon-row-main">
                      <div class="salon-row-top">
                        <h3><?= e((string) ($event['name'] ?? '')) ?></h3>
                        <?php if (!empty($event['kind'])): ?><span class="mk-tag"><?= e((string) $event['kind']) ?></span><?php endif; ?>
                        <?php if (!empty($event['is_closed'])): ?>
                          <span class="salon-flag">Candidatures closes</span>
                        <?php elseif (empty($event['confirmed'])): ?>
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
        $pagerLabel = 'Pagination des prix et concours';
        require ADL_ROOT . '/app/Views/partials/search-pager.php';
      ?>

      <?php if (!empty($contestIndex)): ?>
        <details class="me-geo">
          <summary><?= e((string) ($contestIndexLabel ?? 'Tous les prix à venir')) ?> (<?= e(count_label(count($contestIndex), 'prix ou concours', 'prix et concours')) ?>)</summary>
          <div class="me-geo-links">
            <?php foreach ($contestIndex as $item): ?>
              <a href="<?= e(url((string) $item['href'])) ?>"><?= e((string) $item['name']) ?></a>
            <?php endforeach; ?>
          </div>
        </details>
      <?php endif; ?>

      <?php if (!empty($contestPastIndex)): ?>
        <details class="me-geo">
          <summary>Éditions passées (<?= count($contestPastIndex) ?>)</summary>
          <div class="me-geo-links">
            <?php foreach ($contestPastIndex as $item): ?>
              <a href="<?= e(url((string) $item['href'])) ?>"><?= e((string) $item['name']) ?></a>
            <?php endforeach; ?>
          </div>
        </details>
      <?php endif; ?>

      <?php if (!empty($contestHubNav)): ?>
        <nav class="me-geo" aria-label="Prix et concours par pays, genre et mois">
          <h2>Par pays, genre et mois</h2>
          <div class="me-geo-links">
            <?php foreach ($contestHubNav as $hubLink): ?>
              <a href="<?= e(url((string) $hubLink['href'])) ?>"><?= e((string) $hubLink['label']) ?></a>
            <?php endforeach; ?>
          </div>
        </nav>
      <?php endif; ?>

      <aside class="salon-cta">
        <div>
          <strong>Votre prix ou concours n’est pas dans l’annuaire ?</strong>
          <span>Proposez-le : l’équipe vérifie les dates et le règlement avant publication.</span>
        </div>
        <a class="btn-orange" href="<?= e(url('/prix-et-concours/ajouter')) ?>">Proposer un prix</a>
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
