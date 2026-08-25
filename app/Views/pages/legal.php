<div class="legal-page">
  <nav class="legal-nav" aria-label="Pages juridiques">
    <?php foreach ($legalDoc['nav'] ?? [] as $item): ?>
      <a href="<?= e(url($item['href'])) ?>" class="<?= !empty($item['active']) ? 'is-active' : '' ?>"><?= e($item['label']) ?></a>
    <?php endforeach; ?>
  </nav>
  <article class="legal-doc">
    <h1><?= e($legalDoc['title'] ?? 'Mentions légales') ?></h1>
    <?php foreach ($legalDoc['sections'] ?? [] as $section): ?>
      <section>
        <h2><?= e($section['title']) ?></h2>
        <?php foreach ($section['blocks'] ?? [] as $block): ?>
          <?php if (isset($block['p'])): ?>
            <p><?= e($block['p']) ?></p>
          <?php elseif (isset($block['html'])): ?>
            <?= $block['html'] ?>
          <?php endif; ?>
        <?php endforeach; ?>
      </section>
    <?php endforeach; ?>
    <p class="legal-meta">Dernière mise à jour : <?= e($legalDoc['updated'] ?? '') ?> · Version <?= e($legalDoc['version'] ?? '') ?></p>
  </article>
</div>
