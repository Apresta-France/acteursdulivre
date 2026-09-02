<?php
$pager = $pager ?? ['page' => 1, 'pages' => 1];
$page = (int) ($pager['page'] ?? 1);
$pages = (int) ($pager['pages'] ?? 1);
$pagerPath = (string) ($pagerPath ?? '/');
$pagerLabel = (string) ($pagerLabel ?? 'Pagination');
$pageItems = pager_page_items($page, $pages);
?>
<nav class="search-pager" data-search-pager aria-label="<?= e($pagerLabel) ?>"<?= $pages <= 1 ? ' hidden' : '' ?>>
  <?php if ($pages > 1): ?>
    <?php if ($page > 1): ?>
      <a href="<?= e(catalog_listing_href($pagerPath, $page - 1)) ?>" data-search-page-num="<?= $page - 1 ?>" rel="prev">Précédent</a>
    <?php else: ?>
      <span class="is-off" aria-disabled="true">Précédent</span>
    <?php endif; ?>
    <?php foreach ($pageItems as $n): ?>
      <?php if ($n === null): ?>
        <span class="is-gap" aria-hidden="true">…</span>
      <?php elseif ((int) $n === $page): ?>
        <span aria-current="page"><?= (int) $n ?></span>
      <?php else: ?>
        <a href="<?= e(catalog_listing_href($pagerPath, (int) $n)) ?>" data-search-page-num="<?= (int) $n ?>"><?= (int) $n ?></a>
      <?php endif; ?>
    <?php endforeach; ?>
    <?php if ($page < $pages): ?>
      <a href="<?= e(catalog_listing_href($pagerPath, $page + 1)) ?>" data-search-page-num="<?= $page + 1 ?>" rel="next">Suivant</a>
    <?php else: ?>
      <span class="is-off" aria-disabled="true">Suivant</span>
    <?php endif; ?>
  <?php endif; ?>
</nav>
