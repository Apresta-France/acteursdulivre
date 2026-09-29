<?php
$item = $item ?? [];
$row = !empty($row);
$cover = is_array($item['cover'] ?? null) ? $item['cover'] : [];
$ink = (string) ($cover['ink'] ?? '#15212f');
$paper = (string) ($cover['paper'] ?? '#f4efe6');
$rule = (string) ($cover['rule'] ?? '#eb963b');
?>
<a class="sub-card<?= $row ? ' is-row' : '' ?><?= empty($item['open']) ? ' is-closed' : '' ?>" href="<?= e(url((string) ($item['href'] ?? '/souscriptions'))) ?>">
  <span class="sub-cover" style="--sub-ink: <?= e($ink) ?>; --sub-paper: <?= e($paper) ?>; --sub-rule: <?= e($rule) ?>" aria-hidden="true">
    <span class="sub-cover-spine"></span>
    <span class="sub-cover-face">
      <span><?= e((string) ($item['genre'] ?? '')) ?></span>
      <strong><?= e((string) ($item['title'] ?? '')) ?></strong>
    </span>
  </span>
  <span class="sub-card-body">
    <span class="sub-card-line">
      <span class="mk-tag"><?= e((string) ($item['kind_label'] ?? '')) ?></span>
      <span class="sub-when"><?= e((string) ($item['when'] ?? '')) ?></span>
    </span>
    <strong class="sub-card-title"><?= e((string) ($item['title'] ?? '')) ?></strong>
    <em><?= e(trim((string) ($item['bearer'] ?? '') . (!empty($item['genre']) ? ' · ' . $item['genre'] : ''))) ?></em>
    <span class="sub-where"><?= e((string) ($item['where_line'] ?? '')) ?></span>
  </span>
</a>
