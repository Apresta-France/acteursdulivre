<?php
$missions = $missions ?? [];
$people = $people ?? [];
$urlItems = $urlItems ?? [];
$week = (string) ($week ?? '');
?>
<p>Bonjour,</p>
<p>Voici le point de la semaine<?= $week !== '' ? ' (à partir du ' . e($week) . ')' : '' ?> : les recherches ouvertes, les nouveaux profils, et une lecture utile.</p>

<?php if ($missions !== []): ?>
  <h2 style="font-size:17px;margin:28px 0 10px;color:#022746;">Dernières recherches</h2>
  <?php foreach ($missions as $item): ?>
    <p style="margin:0 0 14px;">
      <a href="<?= e((string) ($item['href'] ?? '#')) ?>" style="color:#D85D3F;font-weight:600;"><?= e((string) ($item['title'] ?? '')) ?></a>
      <?php if (!empty($item['meta'])): ?><br><span style="color:#66768A;font-size:13px;"><?= e((string) $item['meta']) ?></span><?php endif; ?>
      <?php if (!empty($item['excerpt'])): ?><br><?= e((string) $item['excerpt']) ?><?php endif; ?>
    </p>
  <?php endforeach; ?>
<?php endif; ?>

<?php if ($people !== []): ?>
  <h2 style="font-size:17px;margin:28px 0 10px;color:#022746;">Nouveaux profils</h2>
  <?php foreach ($people as $item): ?>
    <p style="margin:0 0 14px;">
      <a href="<?= e((string) ($item['href'] ?? '#')) ?>" style="color:#D85D3F;font-weight:600;"><?= e((string) ($item['title'] ?? '')) ?></a>
      <?php if (!empty($item['meta'])): ?><br><span style="color:#66768A;font-size:13px;"><?= e((string) $item['meta']) ?></span><?php endif; ?>
      <?php if (!empty($item['excerpt'])): ?><br><?= e((string) $item['excerpt']) ?><?php endif; ?>
    </p>
  <?php endforeach; ?>
<?php endif; ?>

<?php if ($urlItems !== []): ?>
  <h2 style="font-size:17px;margin:28px 0 10px;color:#022746;">À lire</h2>
  <?php foreach ($urlItems as $item): ?>
    <p style="margin:0 0 14px;">
      <a href="<?= e((string) ($item['href'] ?? '#')) ?>" style="color:#D85D3F;font-weight:600;"><?= e((string) ($item['title'] ?? '')) ?></a>
      <?php if (!empty($item['meta'])): ?><br><span style="color:#66768A;font-size:13px;"><?= e((string) $item['meta']) ?></span><?php endif; ?>
      <?php if (!empty($item['excerpt'])): ?><br><?= e((string) $item['excerpt']) ?><?php endif; ?>
    </p>
  <?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($empty)): ?>
  <p>Peu de nouveautés cette semaine. Le journal et l’annuaire restent ouverts : <a href="<?= e(url('/journal')) ?>">lire le journal</a>, <a href="<?= e(url('/prestataires')) ?>">voir les prestataires</a>.</p>
<?php endif; ?>

<p style="margin-top:28px;">À la semaine prochaine,<br>L’équipe d’Acteurs du Livre</p>
