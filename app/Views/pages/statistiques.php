<?php

use Adl\Models\Analytics;

$period = $period ?? [];
$compare = !empty($compare);
$periods = $periods ?? [];
$kpis = $kpis ?? [];
$series = $series ?? [];
$profile = $profile ?? [];
$services = $services ?? [];
$tips = $tips ?? [];
$keep = [
    'periode' => $period['id'] ?? '30j',
    'compare' => $compare ? '1' : '0',
    'jours' => (int) ($period['jours'] ?? 21),
    'du' => (string) ($period['du'] ?? ''),
    'au' => (string) ($period['au'] ?? ''),
];
$href = static function (array $override = []) use ($keep): string {
    return url(Analytics::periodQuery($keep, $override, '/espace/statistiques'));
};
$maxSeries = 1;
foreach ($series as $row) {
    $maxSeries = max($maxSeries, (int) ($row['current'] ?? 0), (int) ($row['previous'] ?? 0));
}
$profileHref = (string) ($profile['href'] ?? '');
$profileN = (int) ($profile['n'] ?? 0);
$profileDelta = $profile['delta'] ?? null;
?>
<div class="espace-page offer-stats-page">
  <div class="espace-page-head">
    <div>
      <h1>Statistiques</h1>
      <p>Vues de votre vitrine et de vos prestations. Vos propres consultations ne sont pas comptées. Les visiteurs ne sont pas identifiés.</p>
    </div>
    <?php if ($profileHref !== ''): ?>
      <a class="btn-ghost" href="<?= e(url($profileHref)) ?>"><?= icon('store', 16) ?> Voir en public</a>
    <?php endif; ?>
  </div>

  <form class="stats-toolbar" method="get" action="<?= e(url('/espace/statistiques')) ?>">
    <div class="stats-periods">
      <?php foreach ($periods as $id => $label): ?>
        <?php if (in_array($id, ['xj', 'perso'], true)) {
            continue;
        } ?>
        <a class="chip<?= ($period['id'] ?? '') === $id ? ' is-on' : '' ?>" href="<?= e($href(['periode' => $id])) ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </div>
    <input type="hidden" name="periode" value="<?= e((string) ($period['id'] ?? '30j')) ?>">
    <div class="stats-toolbar-row">
      <label class="stats-xdays">
        <span>X derniers jours</span>
        <input class="input" type="number" name="jours" min="1" max="366" value="<?= (int) ($period['jours'] ?? 21) ?>">
        <button class="btn-ghost" type="submit" onclick="this.form.periode.value='xj'">Afficher</button>
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
      <button class="btn-ghost" type="submit" onclick="this.form.periode.value='perso'">Période personnalisée</button>
    </div>
    <p class="mission-row-sub"><?= e((string) ($period['range_label'] ?? '')) ?><?php if ($compare): ?> · vs <?= e((string) ($period['prev_label'] ?? '')) ?><?php endif; ?></p>
  </form>

  <div class="dash-stats">
    <?php
      $kpiIcons = ['store', 'grid', 'chart', 'search'];
      $kpiIndex = 0;
    ?>
    <?php foreach ($kpis as $k): ?>
      <?php
        $unit = (string) ($k['unit'] ?? 'vue');
        $n = (int) ($k['n'] ?? 0);
      ?>
      <div class="dash-stat">
        <span class="dash-ico"><?= icon($kpiIcons[$kpiIndex] ?? 'chart', 18) ?></span>
        <span>
          <strong><?= e((string) $k['k']) ?></strong>
          <em><?= e((string) $k['v']) ?> <?= e($unit . ($n > 1 ? 's' : '')) ?></em>
          <?php if (!empty($k['delta'])): ?>
            <small class="stats-delta is-<?= e((string) $k['delta']['tone']) ?>"><?= e((string) $k['delta']['text']) ?> vs période préc.</small>
          <?php endif; ?>
        </span>
      </div>
      <?php $kpiIndex++; ?>
    <?php endforeach; ?>
  </div>

  <section class="dash-section">
    <h2>Évolution</h2>
    <?php if ($series === [] || empty(array_filter($series, static fn (array $c): bool => (int) ($c['current'] ?? 0) > 0 || (int) ($c['previous'] ?? 0) > 0))): ?>
      <p class="mission-row-sub">Pas encore de vues sur cette période. Une vitrine complète et une prestation en ligne aident à apparaître dans l’annuaire.</p>
    <?php else: ?>
      <div class="stats-chart<?= count($series) > 20 ? ' is-dense' : '' ?>">
        <?php foreach ($series as $c): ?>
          <div class="stats-chart-col">
            <div class="stats-chart-bars">
              <?php if ($compare && (int) ($c['previous'] ?? 0) > 0): ?>
                <span class="is-prev" style="height: <?= (int) round(120 * (int) $c['previous'] / $maxSeries) ?>px" title="Période préc. : <?= format_int((int) $c['previous']) ?>"></span>
              <?php endif; ?>
              <span class="is-now" style="height: <?= max(2, (int) round(120 * (int) ($c['current'] ?? 0) / $maxSeries)) ?>px" title="Période : <?= format_int((int) ($c['current'] ?? 0)) ?>"></span>
            </div>
            <em><?= e((string) ($c['label'] ?? '')) ?></em>
          </div>
        <?php endforeach; ?>
      </div>
      <p class="mission-row-sub" style="margin-top: 10px;">
        Bleu : vitrine + prestations<?= $compare ? ' · Orange : période précédente' : '' ?>
      </p>
    <?php endif; ?>
  </section>

  <div class="stats-grid">
    <section class="side-card">
      <h2 class="espace-section-title" style="margin-top: 0;">Votre vitrine</h2>
      <?php if ($profileHref === ''): ?>
        <p class="mission-row-sub">Publiez d’abord une vitrine pour suivre ses vues.</p>
        <a class="btn-navy" href="<?= e(url('/espace/vitrine')) ?>">Compléter la vitrine</a>
      <?php else: ?>
        <div class="stats-rank-top">
          <a href="<?= e(url($profileHref)) ?>">Fiche publique</a>
          <em><?= e((string) ($profile['v'] ?? format_int($profileN))) ?></em>
        </div>
        <div class="admin-bar"><i style="width: <?= $profileN > 0 ? 100 : 0 ?>%"></i></div>
        <?php if (is_array($profileDelta)): ?>
          <small class="stats-delta is-<?= e((string) ($profileDelta['tone'] ?? 'flat')) ?>"><?= e((string) ($profileDelta['text'] ?? '')) ?></small>
        <?php endif; ?>
        <p class="mission-row-sub" style="margin-top: 10px;">Complétée à <?= (int) ($profile['completion'] ?? 0) ?> %.</p>
        <div class="auth-actions" style="margin-top: 14px;">
          <a class="btn-ghost" href="<?= e(url($profileHref)) ?>">Voir la fiche</a>
          <a class="btn-navy" href="<?= e(url('/espace/vitrine')) ?>">Modifier</a>
        </div>
      <?php endif; ?>
    </section>

    <section class="side-card">
      <h2 class="espace-section-title" style="margin-top: 0;">Prestations</h2>
      <?php if ($services === []): ?>
        <p class="mission-row-sub">Aucune prestation pour le moment.</p>
        <a class="btn-orange" href="<?= e(url('/espace/prestations/creer')) ?>">Proposer une prestation</a>
      <?php else: ?>
        <ol class="stats-rank">
          <?php foreach ($services as $row): ?>
            <li>
              <div class="stats-rank-top">
                <a href="<?= e(url((string) ($row['href'] ?? '#'))) ?>"><?= e((string) ($row['label'] ?? '')) ?></a>
                <em><?= e((string) ($row['v'] ?? '0')) ?></em>
              </div>
              <div class="admin-bar"><i style="width: <?= (int) ($row['pct'] ?? 0) ?>%"></i></div>
              <div class="mission-row-sub">
                <?= e((string) ($row['status_label'] ?? '')) ?>
                <?php if (!empty($row['delta'])): ?>
                  · <span class="stats-delta is-<?= e((string) $row['delta']['tone']) ?>"><?= e((string) $row['delta']['text']) ?></span>
                <?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>
    </section>
  </div>

  <?php if ($tips !== []): ?>
    <section class="dash-section">
      <h2>Pour être plus visible</h2>
      <div class="dash-todos">
        <?php foreach ($tips as $tip): ?>
          <a class="dash-todo" href="<?= e(url((string) ($tip['href'] ?? '/espace'))) ?>">
            <span class="dash-ico dash-ico-accent"><?= icon('chart', 18) ?></span>
            <span>
              <strong><?= e((string) ($tip['title'] ?? '')) ?></strong>
              <em><?= e((string) ($tip['body'] ?? '')) ?></em>
            </span>
            <span class="dash-card-cta"><?= e((string) ($tip['cta'] ?? 'Ouvrir')) ?> <?= icon('arrow', 14) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</div>
