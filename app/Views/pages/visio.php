<?php $visio = is_array($visio ?? null) ? $visio : []; ?>
<div class="visio-page">
  <p class="visio-kicker">kMeet</p>
  <h1><?= e((string) ($title ?? 'Visio')) ?></h1>
  <?php if (!empty($error)): ?>
    <div class="flash flash-error"><?= e((string) $error) ?></div>
  <?php endif; ?>
  <?php if (!empty($saved)): ?>
    <div class="flash flash-ok"><?= e((string) $saved) ?></div>
  <?php endif; ?>
  <?php require ADL_ROOT . '/app/Views/partials/inbox-visio.php'; ?>
</div>
