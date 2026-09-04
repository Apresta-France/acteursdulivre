<?php

use Adl\Models\Analytics;

$period = $period ?? [];
$compare = !empty($compare);
$periods = $periods ?? [];
$kpis = $kpis ?? [];
$series = $series ?? [];
$live = $live ?? [];
$community = $community ?? [];
$vue = (string) ($vue ?? 'audience');
$statsTabs = $statsTabs ?? [];
$tribunes = $tribunes ?? [];
$keep = [
    'periode' => $period['id'] ?? '7j',
    'compare' => $compare ? '1' : '0',
    'jours' => (int) ($period['jours'] ?? 21),
    'du' => (string) ($period['du'] ?? ''),
    'au' => (string) ($period['au'] ?? ''),
    'vue' => $vue === 'audience' ? null : $vue,
];
$href = static function (array $override = []) use ($keep): string {
    return url(Analytics::periodQuery($keep, $override));
};
$maxSeries = 1;
foreach ($series as $row) {
    $maxSeries = max($maxSeries, (int) ($row['current'] ?? 0), (int) ($row['previous'] ?? 0), (int) ($row['uniques'] ?? 0));
}
$liveMinutes = $live['minutes'] ?? [];
$maxLive = 1;
foreach ($liveMinutes as $m) {
    $maxLive = max($maxLive, (int) ($m['n'] ?? 0));
}

$rankTable = static function (array $rows, string $empty): void {
    if ($rows === []) {
        echo '<p class="admin-muted">' . e($empty) . '</p>';
        return;
    }
    echo '<ol class="stats-rank">';
    foreach ($rows as $row) {
        $href = $row['href'] ?? null;
        $label = (string) ($row['label'] ?? '');
        $delta = $row['delta'] ?? null;
        echo '<li>';
        echo '<div class="stats-rank-top">';
        if (is_string($href) && $href !== '') {
            echo '<a href="' . e(url($href)) . '" target="_blank" rel="noopener">' . e($label) . '</a>';
        } else {
            echo '<span>' . e($label) . '</span>';
        }
        echo '<em>' . e((string) ($row['v'] ?? format_int((int) ($row['n'] ?? 0)))) . '</em>';
        echo '</div>';
        echo '<div class="admin-bar"><i style="width:' . (int) ($row['pct'] ?? 0) . '%"></i></div>';
        if (is_array($delta)) {
            echo '<small class="stats-delta is-' . e((string) ($delta['tone'] ?? 'flat')) . '">' . e((string) ($delta['text'] ?? '')) . '</small>';
        }
        echo '</li>';
    }
    echo '</ol>';
};
?>
<div class="admin-page stats-page">
  <div class="admin-page-head">
    <div>
      <h1>Statistiques</h1>
      <p class="admin-lead">Suivez l’activité en direct, puis explorez l’audience et les contenus par période.</p>
    </div>
  </div>

  <section class="admin-card stats-live" data-stats-live data-live-url="<?= e(url('/admin/statistiques/live')) ?>">
    <div class="stats-live-head">
      <h2>Temps réel</h2>
      <span class="stats-pulse" aria-hidden="true"></span>
      <span class="admin-muted">mis à jour <span data-live-updated><?= e((string) ($live['updated'] ?? '—')) ?></span></span>
    </div>
    <div class="stats-live-kpis">
      <div>
        <div class="admin-kpi-k">En ce moment</div>
        <div class="admin-kpi-v" data-live-now><?= format_int((int) ($live['now'] ?? 0)) ?></div>
        <div class="admin-muted">visiteurs · 5 min</div>
      </div>
      <div>
        <div class="admin-kpi-k">15 dernières minutes</div>
        <div class="admin-kpi-v" data-live-15><?= format_int((int) ($live['views_15'] ?? 0)) ?></div>
        <div class="admin-muted">pages vues</div>
      </div>
      <div>
        <div class="admin-kpi-k">Dernière heure</div>
        <div class="admin-kpi-v" data-live-60><?= format_int((int) ($live['views_60'] ?? 0)) ?></div>
        <div class="admin-muted">pages vues</div>
      </div>
    </div>
    <div class="stats-spark" data-live-chart aria-hidden="true">
      <?php foreach ($liveMinutes as $m): ?>
        <span title="<?= e((string) ($m['t'] ?? '')) . ' · ' . (int) ($m['n'] ?? 0) ?>" style="height: <?= max(4, (int) round(72 * ((int) ($m['n'] ?? 0)) / $maxLive)) ?>px"></span>
      <?php endforeach; ?>
    </div>
    <div class="stats-live-grid">
      <div>
        <h3>Pages chaudes</h3>
        <ul class="stats-mini" data-live-pages>
          <?php if (($live['pages'] ?? []) === []): ?>
            <li class="admin-muted">Aucune visite récente.</li>
          <?php endif; ?>
          <?php foreach ($live['pages'] ?? [] as $row): ?>
            <li><span><?= e((string) $row['label']) ?></span><em><?= format_int((int) $row['n']) ?></em></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div>
        <h3>Prestataires vus</h3>
        <ul class="stats-mini" data-live-profiles>
          <?php if (($live['profiles'] ?? []) === []): ?>
            <li class="admin-muted">Aucun profil consulté.</li>
          <?php endif; ?>
          <?php foreach ($live['profiles'] ?? [] as $row): ?>
            <li><span><?= e((string) $row['label']) ?></span><em><?= format_int((int) $row['n']) ?></em></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div>
        <h3>Recherches</h3>
        <ul class="stats-mini" data-live-searches>
          <?php if (($live['searches'] ?? []) === []): ?>
            <li class="admin-muted">Aucune recherche récente.</li>
          <?php endif; ?>
          <?php foreach ($live['searches'] ?? [] as $row): ?>
            <li><span><?= e((string) $row['label']) ?></span><em><?= format_int((int) $row['n']) ?></em></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div>
        <h3>Actions</h3>
        <ul class="stats-mini" data-live-actions>
          <?php if (($live['actions'] ?? []) === []): ?>
            <li class="admin-muted">Aucune action récente.</li>
          <?php endif; ?>
          <?php foreach ($live['actions'] ?? [] as $row): ?>
            <li><span><?= e((string) $row['label']) ?></span><em><?= format_int((int) $row['n']) ?></em></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </section>

  <form class="stats-toolbar admin-card" method="get" action="<?= e(url('/admin/statistiques')) ?>">
    <div>
      <h2>Période d’analyse</h2>
      <p class="admin-muted">Ces filtres s’appliquent à tous les onglets.</p>
    </div>
    <div class="stats-periods">
      <?php foreach ($periods as $id => $label): ?>
        <?php if (in_array($id, ['xj', 'perso'], true)) {
            continue;
        } ?>
        <a class="chip<?= ($period['id'] ?? '') === $id ? ' is-on' : '' ?>" href="<?= e($href(['periode' => $id])) ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </div>
    <input type="hidden" name="periode" value="<?= e((string) ($period['id'] ?? '7j')) ?>">
    <?php if ($vue !== 'audience'): ?><input type="hidden" name="vue" value="<?= e($vue) ?>"><?php endif; ?>
    <div class="stats-toolbar-row">
      <label class="stats-xdays">
        <span>X derniers jours</span>
        <input class="input" type="number" name="jours" min="1" max="366" value="<?= (int) ($period['jours'] ?? 21) ?>">
        <button class="admin-ghost" type="submit" onclick="this.form.periode.value='xj'">Afficher</button>
      </label>
      <label class="stats-compare">
        <input type="hidden" name="compare" value="0">
        <input type="checkbox" name="compare" value="1"<?= $compare ? ' checked' : '' ?> onchange="this.form.submit()">
        Comparer à <?= e((string) ($period['prev_label'] ?? 'la période précédente')) ?>
      </label>
    </div>
    <div class="stats-toolbar-row">
      <label>Du <input class="input" type="date" name="du" value="<?= e((string) ($period['du'] ?? '')) ?>"></label>
      <label>Au <input class="input" type="date" name="au" value="<?= e((string) ($period['au'] ?? '')) ?>"></label>
      <button class="admin-ghost" type="submit" onclick="this.form.periode.value='perso'">Période personnalisée</button>
    </div>
    <p class="admin-muted"><?= e((string) ($period['range_label'] ?? '')) ?><?php if ($compare): ?> · vs <?= e((string) ($period['prev_label'] ?? '')) ?><?php endif; ?></p>
  </form>

  <nav class="chip-row stats-tabs" aria-label="Types de statistiques">
    <?php foreach ($statsTabs as $tab): ?>
      <a class="chip<?= !empty($tab['on']) ? ' is-on' : '' ?>" href="<?= e(url((string) $tab['href'])) ?>"<?= !empty($tab['on']) ? ' aria-current="page"' : '' ?>><?= e((string) $tab['label']) ?></a>
    <?php endforeach; ?>
  </nav>

  <?php if ($vue === 'audience'): ?>
    <section class="stats-panel">
      <h2 class="admin-h2">Audience</h2>
      <p class="admin-muted">Pages vues et visiteurs anonymisés — aucun visiteur n’est suivi d’un jour à l’autre.</p>
      <div class="admin-kpi-row">
        <?php foreach ($kpis as $k): ?>
          <div class="admin-kpi">
            <div class="admin-kpi-k"><?= e((string) $k['k']) ?></div>
            <div class="admin-kpi-v"><?= e((string) $k['v']) ?></div>
            <?php if (!empty($k['delta'])): ?><div class="stats-delta is-<?= e((string) $k['delta']['tone']) ?>"><?= e((string) $k['delta']['text']) ?> vs période préc.</div><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="admin-card">
        <h2><?= !empty($period['hourly']) ? 'Heure par heure' : 'Évolution' ?></h2>
        <?php if ($series === []): ?>
          <p class="admin-muted">Pas encore de trafic sur cette période.</p>
        <?php else: ?>
          <div class="stats-chart<?= count($series) > 20 ? ' is-dense' : '' ?>">
            <?php foreach ($series as $c): ?>
              <div class="stats-chart-col">
                <div class="stats-chart-bars">
                  <?php if ($compare && (int) ($c['previous'] ?? 0) > 0): ?><span class="is-prev" style="height: <?= (int) round(120 * (int) $c['previous'] / $maxSeries) ?>px" title="Période préc. : <?= format_int((int) $c['previous']) ?>"></span><?php endif; ?>
                  <span class="is-now" style="height: <?= max(2, (int) round(120 * (int) ($c['current'] ?? 0) / $maxSeries)) ?>px" title="Période : <?= format_int((int) ($c['current'] ?? 0)) ?>"></span>
                </div>
                <em><?= e((string) ($c['label'] ?? '')) ?></em>
              </div>
            <?php endforeach; ?>
          </div>
          <p class="admin-muted" style="margin-top:10px;">Bleu : période choisie<?= $compare ? ' · Orange : période précédente' : '' ?></p>
        <?php endif; ?>
      </div>
      <div class="stats-grid">
        <div class="admin-card"><h2>Pages les plus vues</h2><?php $rankTable($paths ?? [], 'Aucune page vue sur cette période.'); ?></div>
        <div class="admin-card"><h2>Types de pages</h2><?php $rankTable($pages ?? [], 'Aucun type de page pour le moment.'); ?></div>
        <div class="admin-card"><h2>Pages d’entrée</h2><?php $rankTable($entries ?? [], 'Aucun premier passage enregistré.'); ?></div>
        <div class="admin-card"><h2>Sources</h2><?php $rankTable($referrers ?? [], 'Aucun référent externe.'); ?></div>
        <div class="admin-card"><h2>Appareils</h2><?php $rankTable($devices ?? [], 'Aucun appareil distingué.'); ?></div>
        <div class="admin-card"><h2>Actions</h2><?php $rankTable($actions ?? [], 'Aucune action enregistrée sur cette période.'); ?></div>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($vue === 'recherche'): ?>
    <section class="stats-panel">
      <h2 class="admin-h2">Recherches</h2>
      <p class="admin-muted">Termes saisis et filtres utilisés sur la période sélectionnée.</p>
      <div class="stats-grid">
        <div class="admin-card"><h2>Recherches</h2><?php $rankTable($searches ?? [], 'Aucune recherche textuelle sur cette période.'); ?></div>
        <div class="admin-card"><h2>Recherches sans résultat</h2><?php $rankTable($search_empty ?? [], 'Aucune recherche sans résultat.'); ?></div>
        <div class="admin-card"><h2>Types de recherche</h2><?php $rankTable($search_types ?? [], 'Aucun filtre de type.'); ?></div>
        <div class="admin-card"><h2>Villes recherchées</h2><?php $rankTable($search_cities ?? [], 'Aucune ville utilisée comme filtre.'); ?></div>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($vue === 'catalogue'): ?>
    <section class="stats-panel">
      <h2 class="admin-h2">Catalogue</h2>
      <p class="admin-muted">Consultations des professionnels, prestations, missions et métiers.</p>
      <div class="stats-grid">
        <div class="admin-card"><h2>Prestataires les plus vus</h2><?php $rankTable($profiles ?? [], 'Aucun profil consulté sur cette période.'); ?></div>
        <div class="admin-card"><h2>Prestations les plus vues</h2><?php $rankTable($services ?? [], 'Aucune fiche prestation vue.'); ?></div>
        <div class="admin-card"><h2>Missions les plus vues</h2><?php $rankTable($missions ?? [], 'Aucune fiche mission vue.'); ?></div>
        <div class="admin-card"><h2>Pages métiers</h2><?php $rankTable($metiers ?? [], 'Aucune page métier consultée.'); ?></div>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($vue === 'journal'): ?>
    <section class="stats-panel">
      <h2 class="admin-h2">Journal</h2>
      <p class="admin-muted">Audience des articles éditoriaux, hors tribunes publiées par les membres.</p>
      <div class="stats-grid">
        <div class="admin-card"><h2>Articles les plus lus</h2><?php $rankTable($articles ?? [], 'Aucun article éditorial consulté.'); ?></div>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($vue === 'tribunes'): ?>
    <section class="stats-panel stats-tribunes">
      <h2 class="admin-h2">Tribunes</h2>
      <p class="admin-muted">Contributions des membres : état actuel, activité éditoriale et audience sur la période.</p>
      <h3 class="stats-section-title">État actuel</h3>
      <div class="admin-kpi-row">
        <?php foreach ($tribunes['totals'] ?? [] as $k): ?>
          <?php $kpiTag = !empty($k['href']) ? 'a' : 'div'; ?>
          <<?= $kpiTag ?> class="admin-kpi"<?php if ($kpiTag === 'a'): ?> href="<?= e(url((string) $k['href'])) ?>"<?php endif; ?>>
            <div class="admin-kpi-k"><?= e((string) $k['k']) ?></div>
            <div class="admin-kpi-v"><?= e((string) $k['v']) ?></div>
            <div class="admin-muted"><?= e((string) ($k['note'] ?? '')) ?></div>
          </<?= $kpiTag ?>>
        <?php endforeach; ?>
      </div>
      <h3 class="stats-section-title">Sur la période</h3>
      <div class="admin-kpi-row">
        <?php foreach ($tribunes['period'] ?? [] as $k): ?>
          <div class="admin-kpi">
            <div class="admin-kpi-k"><?= e((string) $k['k']) ?></div>
            <div class="admin-kpi-v"><?= e((string) $k['v']) ?></div>
            <?php if (!empty($k['delta'])): ?><div class="stats-delta is-<?= e((string) $k['delta']['tone']) ?>"><?= e((string) $k['delta']['text']) ?> vs période préc.</div><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="stats-grid">
        <div class="admin-card"><h2>Tribunes les plus lues</h2><?php $rankTable($tribunes['popular'] ?? [], 'Aucune tribune consultée sur cette période.'); ?></div>
        <div class="admin-card"><h2>Auteurs les plus publiés</h2><?php $rankTable($tribunes['authors'] ?? [], 'Aucune tribune publiée.'); ?></div>
        <div class="admin-card"><h2>Répartition par statut</h2><?php $rankTable($tribunes['status'] ?? [], 'Aucune tribune pour le moment.'); ?></div>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($vue === 'communaute'): ?>
    <section class="stats-panel stats-community">
      <h2 class="admin-h2">Communauté et contenus</h2>
      <p class="admin-muted">Totaux à l’instant T, indépendants de la période sélectionnée.</p>
      <div class="admin-kpi-row">
        <?php foreach ($community['kpis'] ?? [] as $k): ?>
          <?php $kpiTag = !empty($k['href']) ? 'a' : 'div'; ?>
          <<?= $kpiTag ?> class="admin-kpi"<?php if ($kpiTag === 'a'): ?> href="<?= e(url((string) $k['href'])) ?>"<?php endif; ?>>
            <div class="admin-kpi-k"><?= e((string) $k['k']) ?></div>
            <div class="admin-kpi-v"><?= e((string) $k['v']) ?></div>
            <?php if (!empty($k['note'])): ?><div class="admin-muted"><?= e((string) $k['note']) ?></div><?php endif; ?>
          </<?= $kpiTag ?>>
        <?php endforeach; ?>
      </div>
      <div class="stats-grid">
        <div class="admin-card"><h2>Auteurs et œuvres</h2><?php $rankTable($community['authors'] ?? [], 'Aucune fiche auteur pour le moment.'); ?></div>
        <div class="admin-card"><h2>Types d’œuvres</h2><?php $rankTable($community['author_kinds'] ?? [], 'Aucune œuvre cataloguée.'); ?></div>
        <div class="admin-card"><h2>Forum</h2><?php $rankTable($community['forum'] ?? [], 'Le forum n’a pas encore de discussions.'); ?></div>
        <div class="admin-card"><h2>Discussions par rubrique</h2><?php $rankTable($community['forum_categories'] ?? [], 'Aucune rubrique forum.'); ?></div>
        <div class="admin-card"><h2>Contributeurs du forum</h2><?php $rankTable($community['forum_contributors'] ?? [], 'Aucun message pour le moment.'); ?></div>
        <div class="admin-card"><h2>Avis et recommandations</h2><?php $rankTable($community['reviews'] ?? [], 'Aucun avis ni recommandation.'); ?></div>
        <div class="admin-card"><h2>Newsletter</h2><?php $rankTable($community['newsletter'] ?? [], 'Aucun abonné pour le moment.'); ?></div>
      </div>
    </section>
  <?php endif; ?>
</div>
