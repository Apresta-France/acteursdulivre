<?php
$headerOutilsOn = !empty($isOutils);
$headerOutilsScreen = (string) ($screen ?? '');
$headerTools = \Adl\Data\Tools::all();
?>
<div class="header-drop<?= $headerOutilsOn ? ' is-current' : '' ?>" data-header-drop>
  <button type="button" class="header-drop-btn" data-header-drop-toggle aria-expanded="false" aria-controls="header-outils-panel" aria-haspopup="true" aria-label="Menu outils">
    Outils
  </button>
  <div class="header-drop-flyout" id="header-outils-panel">
    <div class="header-drop-panel">
      <a href="<?= e(url('/outils')) ?>"<?= $headerOutilsScreen === 'outils' ? ' class="is-active"' : '' ?>>
        <strong>Tous les outils</strong>
        <span>Boîte à outils du livre</span>
      </a>
      <?php foreach ($headerTools as $headerTool): ?>
        <?php if (empty($headerTool['available'])) {
            continue;
        } ?>
        <a href="<?= e(url((string) ($headerTool['href'] ?? '/outils'))) ?>"<?= $headerOutilsScreen === 'outil-' . ($headerTool['slug'] ?? '') ? ' class="is-active"' : '' ?>>
          <strong><?= e((string) ($headerTool['title'] ?? '')) ?></strong>
          <span><?= e((string) ($headerTool['kicker'] ?? '')) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
