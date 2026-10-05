<?php

$lp = is_array($landing ?? null) ? $landing : [];
$period = $period ?? [];
$compare = !empty($compare);
$periods = $periods ?? [];
$kpis = $kpis ?? [];
$series = $series ?? [];
$grain = (string) ($grain ?? 'day');
$tranche = $tranche ?? null;
$journeys = $journeys ?? [];
$pager = $pager ?? [];
$base = (string) ($base ?? '/admin/landings');
$maxSeries = 1;
foreach ($series as $row) {
    $maxSeries = max($maxSeries, (int) ($row['current'] ?? 0), (int) ($row['previous'] ?? 0));
}

$rankTable = static function (array $rows, string $empty): void {
    if ($rows === []) {
        echo '<p class="admin-muted">' . e($empty) . '</p>';
        return;
    }
    echo '<ol class="stats-rank">';
    foreach ($rows as $row) {
        $href = (string) ($row['href'] ?? '');
        $label = (string) ($row['label'] ?? '');
        echo '<li>';
        echo '<div class="stats-rank-top">';
        if ($href !== '') {
            echo '<a href="' . e(url($href)) . '" target="_blank" rel="noopener">' . e($label) . '</a>';
        } else {
            echo '<span>' . e($label) . '</span>';
        }
        echo '<em>' . e((string) ($row['v'] ?? format_int((int) ($row['n'] ?? 0)))) . '</em>';
        echo '</div>';
        echo '<div class="admin-bar"><i style="width:' . (int) ($row['pct'] ?? 0) . '%"></i></div>';
        $bits = [];
        if ((int) ($row['share'] ?? 0) > 0) {
            $bits[] = (int) $row['share'] . ' % des arrivées';
        }
        $sub = (string) ($row['sub'] ?? '');
        if ($sub !== '' && $sub !== $label) {
            $bits[] = $sub;
        }
        if ($bits !== []) {
            echo '<small class="admin-muted">' . e(implode(' · ', $bits)) . '</small>';
        }
        echo '</li>';
    }
    echo '</ol>';
};
?>
<div class="admin-page stats-page lp-stats-page">
  <div class="admin-page-head">
    <div>
      <p class="admin-muted"><a href="<?= e(url('/admin/landings')) ?>">Landings pub</a></p>
      <h1><?= e((string) ($lp['need'] ?? 'Landing')) ?></h1>
      <p class="admin-lead">Parcours anonymes des personnes qui arrivent sur cette page. Aucun nom, e-mail, adresse IP ni compte n’est enregistré. Chaque visite garde au plus dix actions, puis s’arrête.</p>
      <?php if (!empty($lp['kicker']) || !empty($lp['audience_label'])): ?>
        <p class="admin-muted"><?= e(trim((string) ($lp['kicker'] ?? '') . ((($lp['audience_label'] ?? '') !== '') ? ' · ' . (string) $lp['audience_label'] : ''))) ?></p>
      <?php endif; ?>
    </div>
    <?php if (!empty($lp['url'])): ?>
      <a class="admin-ghost" href="<?= e((string) $lp['url']) ?>" target="_blank" rel="noopener">Voir la page</a>
    <?php endif; ?>
  </div>

  <form class="stats-toolbar admin-card" method="get" action="<?= e(url($base)) ?>">
    <div>
      <h2>Période</h2>
      <p class="admin-muted"><?= e((string) ($period['range_label'] ?? '')) ?><?php if ($compare): ?> · vs <?= e((string) ($period['prev_label'] ?? '')) ?><?php endif; ?></p>
    </div>
    <div class="stats-periods">
      <?php foreach ($periods as $chip): ?>
        <a class="chip<?= !empty($chip['on']) ? ' is-on' : '' ?>" href="<?= e(url((string) $chip['href'])) ?>"><?= e((string) $chip['label']) ?></a>
      <?php endforeach; ?>
    </div>
    <input type="hidden" name="periode" value="<?= e((string) ($period['id'] ?? '7j')) ?>">
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
  </form>

  <?php if (empty($ready)): ?>
    <div class="admin-card">
      <h2>Suivi des parcours</h2>
      <p>Les tables de parcours ne sont pas encore en place. Lancez les migrations, puis revenez sur cette page.</p>
      <p><a class="admin-ghost" href="<?= e(url('/admin/migrations')) ?>">Ouvrir les migrations</a></p>
    </div>
  <?php endif; ?>

  <div class="admin-kpi-row">
    <?php foreach ($kpis as $k): ?>
      <div class="admin-kpi">
        <div class="admin-kpi-k"><?= e((string) $k['k']) ?></div>
        <div class="admin-kpi-v"><?= e((string) $k['v']) ?></div>
        <?php if (!empty($k['note'])): ?><div class="admin-muted"><?= e((string) $k['note']) ?></div><?php endif; ?>
        <?php if (!empty($k['delta'])): ?><div class="stats-delta is-<?= e((string) $k['delta']['tone']) ?>"><?= e((string) $k['delta']['text']) ?> vs période préc.</div><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if (!empty($ready)): ?>
    <section class="admin-card">
      <h2><?= e((string) ($grain_label ?? 'Évolution')) ?></h2>
      <?php if (($filter_label ?? '') !== ''): ?>
        <p class="admin-muted">Filtre actif : <?= e((string) $filter_label) ?>. <a href="<?= e(url((string) ($day_href ?? $base))) ?>">Toute la journée</a></p>
      <?php elseif ((int) ($arrivals_n ?? 0) === 0 && (int) ($views_n ?? 0) > 0): ?>
        <p class="admin-muted">Le compteur de pages vues inclut l’historique. Le détail des parcours commence avec les visites enregistrées après la mise en place de ce suivi.</p>
      <?php elseif ((int) ($arrivals_n ?? 0) === 0): ?>
        <p class="admin-muted">Aucune arrivée suivie sur cette période.</p>
      <?php endif; ?>
      <?php if ($series === []): ?>
        <p class="admin-muted">Pas encore de parcours sur cette période.</p>
      <?php else: ?>
        <div class="stats-chart<?= count($series) > 16 ? ' is-dense' : '' ?>">
          <?php foreach ($series as $c): ?>
            <?php
              $colTag = !empty($c['href']) ? 'a' : 'div';
              $colAttr = $colTag === 'a' ? ' href="' . e(url((string) $c['href'])) . '"' : '';
            ?>
            <<?= $colTag ?> class="stats-chart-col<?= !empty($c['on']) ? ' is-on' : '' ?>"<?= $colAttr ?>>
              <div class="stats-chart-bars">
                <?php if ($compare && (int) ($c['previous'] ?? 0) > 0): ?><span class="is-prev" style="height: <?= (int) round(120 * (int) $c['previous'] / $maxSeries) ?>px" title="Période préc. : <?= format_int((int) $c['previous']) ?>"></span><?php endif; ?>
                <span class="is-now" style="height: <?= max(2, (int) round(120 * (int) ($c['current'] ?? 0) / $maxSeries)) ?>px" title="<?= e((string) ($c['label'] ?? '')) ?> · <?= format_int((int) ($c['current'] ?? 0)) ?>"></span>
              </div>
              <em><?= e((string) ($c['label'] ?? '')) ?></em>
            </<?= $colTag ?>>
          <?php endforeach; ?>
        </div>
        <?php if (($zoom_hint ?? '') !== ''): ?>
          <p class="admin-muted lp-stats-hint"><?= e((string) $zoom_hint) ?><?php if ($compare): ?> Les barres orange rappellent la période précédente.<?php endif; ?></p>
        <?php endif; ?>
      <?php endif; ?>
      <?php if ($grain === 'hour'): ?>
        <div class="stats-periods lp-stats-hours">
          <a class="chip<?= $tranche === null ? ' is-on' : '' ?>" href="<?= e(url((string) ($day_href ?? $base))) ?>">Journée</a>
          <?php foreach ($series as $c): ?>
            <a class="chip<?= !empty($c['on']) ? ' is-on' : '' ?>" href="<?= e(url((string) ($c['href'] ?? $base))) ?>"><?= e((string) ($c['label'] ?? '')) ?><?php if ((int) ($c['current'] ?? 0) > 0): ?> · <?= format_int((int) $c['current']) ?><?php endif; ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <div class="stats-grid">
      <div class="admin-card">
        <h2>Interactions sur la page</h2>
        <p class="admin-muted">Clics sur les boutons, les cartes, les ancres et la vidéo.</p>
        <?php $rankTable($interactions ?? [], 'Aucun clic enregistré.'); ?>
      </div>
      <div class="admin-card">
        <h2>Première action</h2>
        <p class="admin-muted">Ce qui suit immédiatement l’arrivée.</p>
        <?php $rankTable($first_steps ?? [], 'Aucune suite enregistrée.'); ?>
      </div>
      <div class="admin-card">
        <h2>Pages suivantes</h2>
        <p class="admin-muted">Pages ouvertes après la landing, dans la limite des dix actions.</p>
        <?php $rankTable($pages_next ?? [], 'Aucune page suivante.'); ?>
      </div>
      <div class="admin-card">
        <h2>Actions suivantes</h2>
        <p class="admin-muted">Inscription, message, favori, candidature, recherche, etc.</p>
        <?php $rankTable($actions_next ?? [], 'Aucune action enregistrée.'); ?>
      </div>
      <div class="admin-card">
        <h2>Enchaînements</h2>
        <p class="admin-muted">Suites identiques, de la première action jusqu’à la dixième.</p>
        <?php $rankTable($paths ?? [], 'Pas encore d’enchaînement répété.'); ?>
      </div>
      <div class="admin-card">
        <h2>Origine et appareil</h2>
        <p class="admin-muted">Source de campagne ou site référent, sans adresse complète.</p>
        <h3 class="lp-stats-sub">Sources</h3>
        <?php $rankTable($sources ?? [], 'Aucune source.'); ?>
        <h3 class="lp-stats-sub">Appareils</h3>
        <?php $rankTable($devices ?? [], 'Aucun appareil.'); ?>
      </div>
    </div>

    <section class="admin-card lp-stats-list">
      <h2>Parcours anonymes</h2>
      <?php if ((int) ($pager['total'] ?? 0) === 0): ?>
        <p class="admin-muted">Aucun parcours sur cette période.</p>
      <?php else: ?>
        <p class="admin-muted"><?= format_int((int) $pager['from']) ?>–<?= format_int((int) $pager['to']) ?> sur <?= format_int((int) $pager['total']) ?>. L’arrivée n’est pas comptée dans les dix actions.</p>
        <div class="lp-journey-list">
          <?php foreach ($journeys as $journey): ?>
            <article class="lp-journey">
              <header>
                <time><?= e((string) ($journey['when'] ?? '')) ?></time>
                <span><?= e((string) ($journey['device'] ?? '')) ?></span>
                <span><?= e((string) ($journey['source'] ?? '')) ?></span>
                <span><?= (int) ($journey['steps_n'] ?? 0) === 0 ? 'Sans suite' : format_int((int) $journey['steps_n']) . ' action' . ((int) $journey['steps_n'] > 1 ? 's' : '') ?></span>
              </header>
              <ol class="lp-stats-chain">
                <li><span class="lp-step-kind">Arrivée</span></li>
                <?php if (!empty($journey['idle'])): ?>
                  <li><span>Aucune interaction ensuite</span></li>
                <?php endif; ?>
                <?php foreach ($journey['events'] ?? [] as $event): ?>
                  <li>
                    <span class="lp-step-kind"><?= e((string) ($event['kind'] ?? '')) ?></span>
                    <?php if (!empty($event['href'])): ?>
                      <a href="<?= e(url((string) $event['href'])) ?>" target="_blank" rel="noopener"><?= e((string) ($event['label'] ?? '')) ?></a>
                    <?php else: ?>
                      <span><?= e((string) ($event['label'] ?? '')) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($event['time'])): ?><time><?= e((string) $event['time']) ?></time><?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ol>
              <?php if (!empty($journey['capped'])): ?>
                <p class="admin-muted">Plafond de 10 actions atteint.</p>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>
        <?php if ((int) ($pager['pages'] ?? 1) > 1): ?>
          <p class="lp-stats-pager">
            <?php if (!empty($pager['prev'])): ?><a class="admin-ghost" href="<?= e(url((string) $pager['prev'])) ?>">Précédent</a><?php endif; ?>
            <span class="admin-muted">Page <?= (int) $pager['page'] ?> / <?= (int) $pager['pages'] ?></span>
            <?php if (!empty($pager['next'])): ?><a class="admin-ghost" href="<?= e(url((string) $pager['next'])) ?>">Suivant</a><?php endif; ?>
          </p>
        <?php endif; ?>
      <?php endif; ?>
    </section>
  <?php endif; ?>
</div>
