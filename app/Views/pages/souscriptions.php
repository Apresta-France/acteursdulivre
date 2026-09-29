<?php
$items = $items ?? [];
$featured = is_array($featured ?? null) ? $featured : null;
$counts = $counts ?? ['open' => 0, 'souscription' => 0, 'prevente' => 0, 'vente' => 0, 'closed' => 0];
$type = (string) ($type ?? '');
$filters = [
    '' => ['Ouvertes', (int) ($counts['open'] ?? 0)],
    'souscription' => ['Souscriptions', (int) ($counts['souscription'] ?? 0)],
    'prevente' => ['Préventes', (int) ($counts['prevente'] ?? 0)],
    'vente' => ['Ventes', (int) ($counts['vente'] ?? 0)],
    'terminees' => ['Terminées', (int) ($counts['closed'] ?? 0)],
];
$featCover = is_array($featured['cover'] ?? null) ? $featured['cover'] : [];
?>
<div class="mk-page co-page sub-page">
  <section class="forum-hero co-hero">
    <div class="forum-hero-copy">
      <nav class="forum-crumb" aria-label="Fil d'Ariane">
        <a href="<?= e(url('/')) ?>">Accueil</a>
        <span aria-hidden="true">·</span>
        <a href="<?= e(url('/communaute')) ?>">Communauté</a>
        <span aria-hidden="true">·</span>
        <span>Souscriptions</span>
      </nav>
      <div class="forum-kicker">Communauté</div>
      <h1>Livres en cours, ailleurs.</h1>
      <p class="forum-lead">Des campagnes déjà ouvertes ailleurs. On les montre, on ne les héberge pas.</p>
    </div>
    <div class="forum-hero-actions">
      <div class="forum-hero-stats">
        <span class="forum-stat">
          <strong><?= e(format_int((int) ($counts['open'] ?? 0))) ?></strong>
          <span>ouvertes</span>
        </span>
        <span class="forum-stat">
          <strong><?= e(format_int((int) ($counts['souscription'] ?? 0))) ?></strong>
          <span>souscriptions</span>
        </span>
        <span class="forum-stat">
          <strong><?= e(format_int((int) ($counts['prevente'] ?? 0) + (int) ($counts['vente'] ?? 0))) ?></strong>
          <span>préventes et ventes</span>
        </span>
      </div>
    </div>
  </section>

  <section class="mk-block">
    <div class="sub-filters" role="tablist" aria-label="Filtrer les livres">
      <?php foreach ($filters as $key => $filter): ?>
        <?php
          $href = $key === '' ? '/souscriptions' : '/souscriptions?type=' . $key;
          $on = $type === $key;
        ?>
        <a class="sub-filter<?= $on ? ' is-on' : '' ?>" href="<?= e(url($href)) ?>"<?= $on ? ' aria-current="page"' : '' ?>>
          <?= e($filter[0]) ?>
          <span><?= (int) $filter[1] ?></span>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if ($featured !== null): ?>
      <?php
        $ink = (string) ($featCover['ink'] ?? '#15212f');
        $paper = (string) ($featCover['paper'] ?? '#f4efe6');
        $rule = (string) ($featCover['rule'] ?? '#eb963b');
      ?>
      <a class="sub-feature" href="<?= e(url((string) $featured['href'])) ?>">
        <span class="sub-cover sub-cover-lg" style="--sub-ink: <?= e($ink) ?>; --sub-paper: <?= e($paper) ?>; --sub-rule: <?= e($rule) ?>" aria-hidden="true">
          <span class="sub-cover-spine"></span>
          <span class="sub-cover-face">
            <span><?= e((string) $featured['genre']) ?></span>
            <strong><?= e((string) $featured['title']) ?></strong>
            <em><?= e((string) $featured['bearer']) ?></em>
          </span>
        </span>
        <span class="sub-feature-body">
          <span class="sub-card-line">
            <span class="mk-tag"><?= e((string) $featured['kind_label']) ?></span>
            <span class="sub-when"><?= e((string) $featured['when']) ?></span>
          </span>
          <h2><?= e((string) $featured['title']) ?></h2>
          <p><?= e((string) $featured['pitch']) ?></p>
          <span class="sub-feature-who"><?= e((string) $featured['bearer']) ?> · <?= e((string) $featured['bearer_role']) ?></span>
          <span class="sub-where"><?= e((string) ($featured['where_line'] ?? '')) ?></span>
          <span class="sub-feature-cta">Voir la fiche →</span>
        </span>
      </a>
    <?php endif; ?>

    <?php if ($items === [] && $featured === null): ?>
      <p class="mk-empty">Aucun livre dans cette sélection. <a href="<?= e(url('/souscriptions')) ?>">Voir les annonces ouvertes</a></p>
    <?php elseif ($items !== []): ?>
      <div class="sub-grid">
        <?php foreach ($items as $item): ?>
          <?php require ADL_ROOT . '/app/Views/partials/souscription-card.php'; ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>
