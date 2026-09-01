<?php
$orderId = (int) ($order['id'] ?? 0);
$suiviTab = (string) ($suiviTab ?? 'jalons');
$depotCount = (int) ($depotCount ?? 0);
$depotOpen = !empty($depotOpen);
$jalonsHref = '/espace/suivi/' . $orderId;
$depotHref = '/espace/suivi/' . $orderId . '/depot';
?>
<nav class="tab-row suivi-tabs" aria-label="Sections du suivi">
  <a class="tab<?= $suiviTab === 'jalons' ? ' is-on' : '' ?>" href="<?= e(url($jalonsHref)) ?>"<?= $suiviTab === 'jalons' ? ' aria-current="page"' : '' ?>>Jalons</a>
  <a class="tab<?= $suiviTab === 'fichiers' ? ' is-on' : '' ?>" href="<?= e(url($depotHref)) ?>"<?= $suiviTab === 'fichiers' ? ' aria-current="page"' : '' ?>>
    Fichiers
    <?php if ($depotCount > 0): ?>
      <span class="tab-count"><?= $depotCount ?></span>
    <?php elseif ($depotOpen): ?>
      <span class="tab-count is-open">Ouvert</span>
    <?php endif; ?>
  </a>
</nav>
