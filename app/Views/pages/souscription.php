<?php
$s = $item ?? [];
$others = $others ?? [];
$body = is_array($s['body'] ?? null) ? $s['body'] : [];
$trades = is_array($s['trades'] ?? null) ? $s['trades'] : [];
$facts = is_array($s['facts'] ?? null) ? $s['facts'] : [];
$open = !empty($s['open']);
$host = trim((string) ($s['host'] ?? ''));
$url = trim((string) ($s['external_url'] ?? ''));
$cta = trim((string) ($s['cta'] ?? ''));
if (!$open) {
    $cta = trim((string) ($s['outcome'] ?? '')) ?: 'Annonce terminée';
} elseif ($cta === '') {
    $cta = $host !== '' ? 'Voir sur ' . $host : 'Voir le site';
}
?>
<div class="mk-page co-page sub-page">
  <section class="forum-hero forum-hero-compact">
    <nav class="forum-crumb" aria-label="Fil d'Ariane">
      <a href="<?= e(url('/')) ?>">Accueil</a>
      <span aria-hidden="true">·</span>
      <a href="<?= e(url('/communaute')) ?>">Communauté</a>
      <span aria-hidden="true">·</span>
      <a href="<?= e(url('/souscriptions')) ?>">Souscriptions</a>
      <span aria-hidden="true">·</span>
      <span><?= e((string) ($s['title'] ?? '')) ?></span>
    </nav>
    <div class="forum-hero-badges">
      <span class="mk-tag"><?= e((string) ($s['kind_label'] ?? '')) ?></span>
      <?php if ($open): ?>
        <span class="sub-pill"><?= e((string) ($s['when'] ?? '')) ?></span>
      <?php else: ?>
        <span class="sub-pill is-closed"><?= e((string) ($s['outcome'] ?? 'Clôturée')) ?></span>
      <?php endif; ?>
    </div>
    <h1><?= e((string) ($s['title'] ?? '')) ?></h1>
    <p class="forum-lead"><?= e((string) ($s['pitch'] ?? '')) ?></p>
    <p class="sub-hero-who"><?= e((string) ($s['bearer'] ?? '')) ?> · <?= e((string) ($s['bearer_role'] ?? '')) ?> · <?= e((string) ($s['genre'] ?? '')) ?></p>
  </section>

  <section class="mk-block">
    <div class="sub-fiche">
      <div class="sub-fiche-main">
        <div class="sub-fiche-top">
          <?php $item = $s; $large = true; require ADL_ROOT . '/app/Views/partials/souscription-cover.php'; $large = false; ?>
          <div>
            <?php foreach ($body as $paragraph): ?>
              <p><?= e((string) $paragraph) ?></p>
            <?php endforeach; ?>
          </div>
        </div>
        <?php if ($trades !== []): ?>
          <h2>Qui fait ce livre</h2>
          <ul class="sub-trades">
            <?php foreach ($trades as $trade): ?>
              <li>
                <span><?= e((string) ($trade['role'] ?? '')) ?></span>
                <strong><?= e((string) ($trade['name'] ?? '')) ?></strong>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>

      <aside class="sub-side">
        <div class="sub-order" id="ailleurs">
          <div class="mk-kicker">Ailleurs</div>
          <?php if ($host !== ''): ?>
            <p class="sub-order-host"><?= e($host) ?></p>
          <?php endif; ?>
          <?php if (trim((string) ($s['closes_label'] ?? '')) !== ''): ?>
            <p class="sub-order-meta"><?= $open ? 'Jusqu’au ' . e((string) $s['closes_label']) : 'Close le ' . e((string) $s['closes_label']) ?></p>
          <?php endif; ?>
          <?php if ($open && $url !== ''): ?>
            <a class="btn-orange sub-cta" href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer"><?= e($cta) ?></a>
            <p class="sub-cta-note"><?= ($s['kind'] ?? '') === 'sponsorise' ? 'Annonce sponsorisée. ' : '' ?>Le lien ouvre <?= e($host !== '' ? $host : 'un site extérieur') ?>.</p>
          <?php elseif ($open): ?>
            <span class="btn-ghost sub-cta">Lien à venir</span>
          <?php else: ?>
            <span class="btn-ghost sub-cta"><?= e($cta) ?></span>
          <?php endif; ?>
        </div>

        <?php if ($facts !== []): ?>
          <dl class="sub-facts">
            <?php foreach ($facts as $fact): ?>
              <div>
                <dt><?= e((string) ($fact[0] ?? '')) ?></dt>
                <dd><?= e((string) ($fact[1] ?? '')) ?></dd>
              </div>
            <?php endforeach; ?>
          </dl>
        <?php endif; ?>
      </aside>
    </div>
  </section>

  <?php if ($others !== []): ?>
    <section class="mk-block mk-wash-cool">
      <div class="mk-head">
        <div>
          <h2>Encore ouverts</h2>
          <p>D’autres annonces encore ouvertes.</p>
        </div>
        <a href="<?= e(url('/souscriptions')) ?>">Toutes les souscriptions →</a>
      </div>
      <div class="sub-grid">
        <?php foreach ($others as $item): ?>
          <?php require ADL_ROOT . '/app/Views/partials/souscription-card.php'; ?>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</div>
