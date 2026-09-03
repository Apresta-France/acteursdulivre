<nav class="espace-nav" aria-label="Navigation de l'espace">
  <?php foreach ($espaceNav ?? [] as $group): ?>
    <div class="espace-nav-group">
      <?php if (($group['title'] ?? '') !== ''): ?>
        <div class="espace-nav-title"><?= e($group['title']) ?></div>
      <?php endif; ?>
      <?php foreach ($group['items'] ?? [] as $item): ?>
        <a href="<?= e(url($item['href'])) ?>"<?= !empty($item['active']) ? ' class="is-active"' : '' ?>>
          <?= icon((string) ($item['icon'] ?? 'dot'), 18) ?>
          <span><?= e($item['label']) ?></span>
          <?php if (!empty($item['badge'])): ?>
            <span class="espace-nav-count" aria-label="<?= e((string) (($item['badge_aria'] ?? '') ?: ($item['badge'] . ' en attente'))) ?>"><?= e((string) $item['badge']) ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
</nav>
