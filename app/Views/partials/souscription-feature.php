<?php
$featured = is_array($featured ?? null) ? $featured : [];
if ($featured === []) {
    return;
}
$sponsor = ($featured['kind'] ?? '') === 'sponsorise';
?>
<a class="sub-feature<?= $sponsor ? ' is-sponsor' : '' ?>" href="<?= e(url((string) ($featured['href'] ?? '/souscriptions'))) ?>">
  <?php
    $item = $featured;
    $large = true;
    require ADL_ROOT . '/app/Views/partials/souscription-cover.php';
    $large = false;
  ?>
  <span class="sub-feature-body">
    <span class="sub-spotlight-kicker">Mis en avant</span>
    <span class="sub-card-line">
      <span class="mk-tag"><?= e((string) ($featured['kind_label'] ?? '')) ?></span>
      <span class="sub-when"><?= e((string) ($featured['when'] ?? '')) ?></span>
    </span>
    <h2><?= e((string) ($featured['title'] ?? '')) ?></h2>
    <p><?= e((string) ($featured['pitch'] ?? '')) ?></p>
    <span class="sub-feature-who"><?= e((string) ($featured['bearer'] ?? '')) ?><?php if (trim((string) ($featured['bearer_role'] ?? '')) !== ''): ?> · <?= e((string) $featured['bearer_role']) ?><?php endif; ?></span>
    <span class="sub-where"><?= e((string) ($featured['where_line'] ?? '')) ?></span>
    <?php if ($sponsor): ?>
      <span class="sub-sponsor-note">Annonce sponsorisée</span>
    <?php endif; ?>
    <span class="sub-feature-cta">Voir la fiche →</span>
  </span>
</a>
