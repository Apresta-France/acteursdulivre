<?php
$shareUrl = $shareUrl ?? ($meta['url'] ?? \Adl\Data\Share::current());
$shareTitle = $shareTitle ?? ($meta['title'] ?? ($title ?? 'Acteurs du Livre'));
$shareText = $shareText ?? ($meta['description'] ?? '');
$shareLabel = $shareLabel ?? 'Partager';
$shareCompact = !empty($shareCompact);
$shareNative = $shareNative ?? true;
$networks = \Adl\Data\Share::networks($shareUrl, $shareTitle, $shareText);
?>
<div class="share-bar<?= $shareCompact ? ' is-compact' : '' ?>"
     data-share
     data-url="<?= e($shareUrl) ?>"
     data-title="<?= e($shareTitle) ?>"
     data-text="<?= e($shareText) ?>">
  <div class="share-label"><?= e($shareLabel) ?></div>
  <div class="share-actions">
    <?php foreach ($networks as $network): ?>
      <a class="share-btn share-<?= e($network['id']) ?>"
         href="<?= e($network['href']) ?>"
         data-share-network="<?= e($network['id']) ?>"
         <?= !empty($network['copy']) ? 'data-share-copy' : 'target="_blank" rel="noopener noreferrer"' ?>
         title="<?= e($network['label']) ?>">
        <?= icon('share-' . $network['id'], $shareCompact ? 15 : 16) ?>
        <span><?= e($network['label']) ?></span>
      </a>
    <?php endforeach; ?>
    <?php if ($shareNative): ?>
      <button type="button" class="share-btn share-native" data-share-native hidden title="Partager">
        <?= icon('share', $shareCompact ? 15 : 16) ?>
        <span>Plus</span>
      </button>
    <?php endif; ?>
  </div>
</div>
