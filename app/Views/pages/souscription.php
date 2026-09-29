<?php
$s = $item ?? [];
$others = $others ?? [];
$cover = is_array($s['cover'] ?? null) ? $s['cover'] : [];
$ink = (string) ($cover['ink'] ?? '#15212f');
$paper = (string) ($cover['paper'] ?? '#f4efe6');
$rule = (string) ($cover['rule'] ?? '#eb963b');
$body = is_array($s['body'] ?? null) ? $s['body'] : [];
$trades = is_array($s['trades'] ?? null) ? $s['trades'] : [];
$facts = is_array($s['facts'] ?? null) ? $s['facts'] : [];
$open = !empty($s['open']);
$host = trim((string) ($s['host'] ?? ''));
$cta = $open
    ? ($host !== '' ? 'Voir sur ' . $host : 'Voir le site')
    : 'Annonce terminée';
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
          <div class="sub-cover sub-cover-lg" style="--sub-ink: <?= e($ink) ?>; --sub-paper: <?= e($paper) ?>; --sub-rule: <?= e($rule) ?>" aria-hidden="true">
            <span class="sub-cover-spine"></span>
            <span class="sub-cover-face">
              <span><?= e((string) ($s['genre'] ?? '')) ?></span>
              <strong><?= e((string) ($s['title'] ?? '')) ?></strong>
              <em><?= e((string) ($s['bearer'] ?? '')) ?></em>
            </span>
          </div>
          <div>
            <?php if ($body !== []): ?>
              <p><?= e((string) $body[0]) ?></p>
            <?php endif; ?>
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
          <p class="sub-order-meta"><?= $open ? 'Jusqu’au ' . e((string) ($s['closes_label'] ?? '')) : 'Close le ' . e((string) ($s['closes_label'] ?? '')) ?></p>
          <?php if ($open): ?>
            <span class="btn-orange sub-cta-example"><?= e($cta) ?></span>
            <p class="sub-cta-note">Exemple de lien. Il quittera acteursdulivre.fr.</p>
          <?php else: ?>
            <span class="btn-ghost sub-cta-example"><?= e($cta) ?></span>
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
