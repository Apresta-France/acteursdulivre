<?php
$item = $item ?? [];
$row = !empty($row);
$sponsor = ($item['kind'] ?? '') === 'sponsorise';
?>
<a class="sub-card<?= $row ? ' is-row' : '' ?><?= empty($item['open']) ? ' is-closed' : '' ?><?= $sponsor ? ' is-sponsor' : '' ?>" href="<?= e(url((string) ($item['href'] ?? '/souscriptions'))) ?>">
  <?php $large = false; require ADL_ROOT . '/app/Views/partials/souscription-cover.php'; ?>
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
