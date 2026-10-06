<?php
$item = $item ?? [];
$large = !empty($large);
$cover = is_array($item['cover'] ?? null) ? $item['cover'] : [];
$ink = (string) ($cover['ink'] ?? '#15212f');
$paper = (string) ($cover['paper'] ?? '#f4efe6');
$rule = (string) ($cover['rule'] ?? '#eb963b');
$photo = trim((string) ($item['cover_image'] ?? ''));
$coverClass = 'sub-cover' . ($large ? ' sub-cover-lg' : '');
$title = (string) ($item['title'] ?? '');
?>
<?php if ($photo !== ''): ?>
  <span class="<?= e($coverClass) ?> sub-cover-photo">
    <img src="<?= e(uploaded($photo)) ?>" alt="<?= e($title !== '' ? 'Couverture de ' . $title : 'Couverture') ?>">
  </span>
<?php else: ?>
  <span class="<?= e($coverClass) ?>" style="--sub-ink: <?= e($ink) ?>; --sub-paper: <?= e($paper) ?>; --sub-rule: <?= e($rule) ?>" aria-hidden="true">
    <span class="sub-cover-spine"></span>
    <span class="sub-cover-face">
      <span><?= e((string) ($item['genre'] ?? '')) ?></span>
      <strong><?= e($title) ?></strong>
      <?php if ($large && trim((string) ($item['bearer'] ?? '')) !== ''): ?>
        <em><?= e((string) $item['bearer']) ?></em>
      <?php endif; ?>
    </span>
  </span>
<?php endif; ?>
