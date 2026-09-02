<?php
$articles = $articles ?? [];
$hero = $hero ?? null;
$rest = $rest ?? [];
$journalQ = (string) ($journalQ ?? '');
$journalCat = (string) ($journalCat ?? '');
$journalCategories = $journalCategories ?? [];
$journalHasContent = !empty($journalHasContent);
$journalFiltered = !empty($journalFiltered);
$pager = $pager ?? ['page' => 1, 'pages' => 1, 'total' => 0];
$page = (int) ($pager['page'] ?? 1);
$pages = (int) ($pager['pages'] ?? 1);
$total = (int) ($pager['total'] ?? 0);

$pageItems = [];
if ($pages <= 7) {
    $pageItems = range(1, max(1, $pages));
} else {
    $keep = [1, $pages, $page];
    if ($page > 1) {
        $keep[] = $page - 1;
    }
    if ($page < $pages) {
        $keep[] = $page + 1;
    }
    $keep = array_values(array_unique(array_filter($keep, static fn (int $n): bool => $n >= 1 && $n <= $pages)));
    sort($keep);
    $prevN = 0;
    foreach ($keep as $n) {
        if ($prevN !== 0 && $n > $prevN + 1) {
            $pageItems[] = null;
        }
        $pageItems[] = $n;
        $prevN = $n;
    }
}

if ($journalQ !== '') {
    $countLabel = match (true) {
        $total === 0 => 'Aucun résultat pour « ' . $journalQ . ' »',
        $total === 1 => '1 résultat pour « ' . $journalQ . ' »',
        default => format_int($total) . ' résultats pour « ' . $journalQ . ' »',
    };
} else {
    $countLabel = $total === 1 ? '1 article' : format_int($total) . ' articles';
}
if ($journalCat !== '') {
    $countLabel .= ' · ' . $journalCat;
}
if ($pages > 1) {
    $countLabel .= ' · page ' . $page . ' / ' . $pages;
}
?>
<div class="journal-page">
  <nav class="search-crumb" aria-label="Fil d'Ariane">
    <a href="<?= e(url('/')) ?>">Accueil</a>
    <span aria-hidden="true"> · </span>
    <?php if ($journalCat !== ''): ?>
      <a href="<?= e(url('/journal')) ?>">Le journal</a>
      <span aria-hidden="true"> · </span>
      <span><?= e($journalCat) ?></span>
    <?php else: ?>
      <span>Le journal</span>
    <?php endif; ?>
  </nav>
  <h1><?= $journalCat !== '' && $journalQ === '' ? 'Le journal — ' . e($journalCat) : 'Le journal' ?></h1>
  <p class="journal-lead">Prix, méthodes, contrats, retours d'expérience : ce qu'on apprend en regardant les métiers du livre travailler.</p>

  <?php if ($journalHasContent): ?>
    <div class="journal-toolbar">
      <form class="journal-search" method="get" action="<?= e(url('/journal')) ?>" role="search" data-live-search data-api="<?= e(url('/api/journal')) ?>" data-empty="Aucun article pour cette recherche." autocomplete="off">
        <?php if ($journalCat !== ''): ?>
          <input type="hidden" name="cat" value="<?= e($journalCat) ?>">
        <?php endif; ?>
        <input type="search" name="q" value="<?= e($journalQ) ?>" placeholder="Rechercher un article…" aria-label="Rechercher dans le journal" data-live-input autocomplete="off">
        <button type="submit">Chercher</button>
        <div class="search-suggest" data-live-panel hidden></div>
      </form>

      <?php if ($journalCategories !== []): ?>
        <nav class="journal-cats chip-row" aria-label="Rubriques du journal">
          <a class="chip<?= $journalCat === '' ? ' is-on' : '' ?>" href="<?= e(journal_listing_url($journalQ, '', 1)) ?>"<?= $journalCat === '' ? ' aria-current="page"' : '' ?>>Tout</a>
          <?php foreach ($journalCategories as $c): ?>
            <?php $label = (string) ($c['label'] ?? ''); ?>
            <a class="chip<?= $journalCat === $label ? ' is-on' : '' ?>" href="<?= e(journal_listing_url($journalQ, $label, 1)) ?>"<?= $journalCat === $label ? ' aria-current="page"' : '' ?>>
              <?= e($label) ?><span class="journal-cat-n"> · <?= (int) ($c['n'] ?? 0) ?></span>
            </a>
          <?php endforeach; ?>
        </nav>
      <?php endif; ?>

      <div class="journal-meta">
        <p class="journal-count"><?= e($countLabel) ?></p>
        <?php if ($journalFiltered): ?>
          <a class="journal-clear" href="<?= e(url('/journal')) ?>">Tout le journal</a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($articles === []): ?>
    <div class="search-empty">
      <?php if ($journalFiltered): ?>
        <strong>Aucun article pour cette recherche.</strong>
        <span>Essayez un autre mot, ou <a href="<?= e(url('/journal')) ?>">affichez tout le journal</a>.</span>
      <?php else: ?>
        <strong>Aucun article publié pour le moment.</strong>
        <span>Les textes du journal apparaîtront ici dès leur mise en ligne.</span>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <?php if ($hero): ?>
      <a class="journal-hero" href="<?= e(url((string) $hero['href'])) ?>">
        <?php if (!empty($hero['img'])): ?>
          <img class="journal-hero-media" src="<?= e((string) $hero['img']) ?>" alt="<?= e((string) ($hero['image_alt'] ?? $hero['title'] ?? '')) ?>" width="720" height="260" decoding="async">
        <?php endif; ?>
        <div class="journal-hero-body">
          <div class="journal-kicker"><?= e((string) $hero['cat']) ?> · <?= e((string) $hero['read']) ?> de lecture</div>
          <h2><?= e((string) $hero['title']) ?></h2>
          <p><?= e((string) $hero['chapo']) ?></p>
        </div>
      </a>
    <?php endif; ?>

    <?php if ($rest !== []): ?>
      <div class="journal-grid">
        <?php foreach ($rest as $a): ?>
          <a class="journal-card" href="<?= e(url((string) $a['href'])) ?>">
            <?php if (!empty($a['img'])): ?>
              <img class="journal-card-media" src="<?= e((string) $a['img']) ?>" alt="<?= e((string) ($a['image_alt'] ?? $a['title'] ?? '')) ?>" width="400" height="170" loading="lazy" decoding="async">
            <?php endif; ?>
            <div class="journal-kicker"><?= e((string) $a['cat']) ?> · <?= e((string) $a['read']) ?></div>
            <strong><?= e((string) $a['title']) ?></strong>
            <span><?= e((string) $a['chapo']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($pages > 1): ?>
      <nav class="journal-pager" aria-label="Pagination du journal">
        <?php if ($page > 1): ?>
          <a href="<?= e(journal_listing_url($journalQ, $journalCat, $page - 1)) ?>" rel="prev">Précédent</a>
        <?php else: ?>
          <span class="is-off" aria-disabled="true">Précédent</span>
        <?php endif; ?>
        <?php foreach ($pageItems as $n): ?>
          <?php if ($n === null): ?>
            <span class="is-gap" aria-hidden="true">…</span>
          <?php elseif ((int) $n === $page): ?>
            <span aria-current="page"><?= (int) $n ?></span>
          <?php else: ?>
            <a href="<?= e(journal_listing_url($journalQ, $journalCat, (int) $n)) ?>"><?= (int) $n ?></a>
          <?php endif; ?>
        <?php endforeach; ?>
        <?php if ($page < $pages): ?>
          <a href="<?= e(journal_listing_url($journalQ, $journalCat, $page + 1)) ?>" rel="next">Suivant</a>
        <?php else: ?>
          <span class="is-off" aria-disabled="true">Suivant</span>
        <?php endif; ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</div>
