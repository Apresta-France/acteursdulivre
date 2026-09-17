<?php
$tools = is_array($tools ?? null) ? $tools : [];
?>
<div class="mk-page co-page tool-hub">
  <section class="forum-hero co-hero">
    <div class="forum-hero-copy">
      <nav class="search-crumb" aria-label="Fil d'Ariane">
        <a href="<?= e(url('/')) ?>">Accueil</a>
        <span aria-hidden="true"> · </span>
        <span>Outils</span>
      </nav>
      <h1>Outils du livre</h1>
      <p class="forum-lead">De petits compteurs pour le quotidien : signes, feuillets, dos de couverture, clé ISBN. Pas un logiciel d’écriture. Rien n’est généré, rien n’est envoyé.</p>
    </div>
    <?php if ($tools !== []): ?>
      <div class="forum-hero-actions">
        <a class="btn-orange forum-hero-cta" href="<?= e(url((string) ($tools[0]['href'] ?? '/outils/volume'))) ?>">Compter des signes</a>
        <?php if (isset($tools[1])): ?>
          <a class="btn-ghost forum-hero-cta" href="<?= e(url((string) ($tools[1]['href'] ?? '/outils/dos'))) ?>">Calculer un dos</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="mk-block">
    <div class="co-doors tool-hub-doors">
      <?php foreach ($tools as $tool): ?>
        <a class="co-door" href="<?= e(url((string) ($tool['href'] ?? '/outils'))) ?>">
          <span class="co-door-ico" aria-hidden="true"><?= icon((string) ($tool['icon'] ?? 'counter'), 22) ?></span>
          <span class="mk-kicker"><?= e((string) ($tool['kicker'] ?? '')) ?></span>
          <h2><?= e((string) ($tool['title'] ?? '')) ?></h2>
          <p><?= e((string) ($tool['lead'] ?? '')) ?></p>
          <span class="co-door-cta">Ouvrir l’outil →</span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="mk-block mk-wash-cool">
    <div class="tool-hub-note">
      <div>
        <div class="mk-kicker">Méthode</div>
        <h2>Des unités du métier, pas du web.</h2>
        <p>Le feuillet français fait 1&nbsp;500 signes espaces compris. Un roman courant d’environ 520&nbsp;000 signes, c’est un peu moins de 350 feuillets. Le dos se calcule sur le papier réel. L’ISBN identifie une édition, pas un titre : broché et e-pub n’ont pas le même numéro.</p>
      </div>
      <div class="tool-hub-links">
        <a href="<?= e(url('/besoin/corriger-un-manuscrit')) ?>">Faire corriger un manuscrit →</a>
        <a href="<?= e(url('/besoin/maquette-de-livre')) ?>">Faire maquetter un livre →</a>
        <a href="<?= e(url('/journal/isbn-depot-legal-afnil-france')) ?>">ISBN, dépôt légal, AFNIL →</a>
      </div>
    </div>
  </section>
</div>
