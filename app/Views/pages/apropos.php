<?php
$homeStats = $homeStats ?? [];
$valeurs = $valeurs ?? [];
$equipe = $equipe ?? [];
?>
<div class="mk-page">
  <section class="mk-intro">
    <h1>Nous voulons que le travail du livre se voie.</h1>
    <p class="mk-lead">Un livre passe entre une dizaine de mains avant d'arriver en librairie. La plupart de ces mains travaillent en indépendant, se trouvent par bouche-à-oreille et négocient sans repère de prix. Nous avons construit un endroit où ce travail est visible, comparable et payé.</p>
    <p>Nous ne sommes ni éditeur, ni imprimeur, ni agence. Nous ne prenons pas de droits sur les livres. Nous mettons en relation, nous suivons les jalons, nous facturons notre commission au prestataire — et nous nous effaçons. Le prix de la mission se règle hors plateforme, entre les parties.</p>
  </section>

  <?php if ($homeStats !== []): ?>
    <section class="mk-stats">
      <?php foreach ($homeStats as $s): ?>
        <a class="mk-stat" href="<?= e(url((string) ($s['href'] ?? '#'))) ?>">
          <strong><?= e((string) $s['v']) ?></strong>
          <span><?= e((string) $s['k']) ?></span>
        </a>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>

  <section class="mk-block mk-cards-3">
    <?php foreach ($valeurs as $v): ?>
      <div>
        <div class="mk-kicker"><?= e((string) $v['kicker']) ?></div>
        <h3><?= e((string) $v['title']) ?></h3>
        <p><?= e((string) $v['body']) ?></p>
      </div>
    <?php endforeach; ?>
  </section>

  <section class="mk-block mk-fog">
    <h2>L'équipe</h2>
    <p class="mk-sub">EDITIONS TESSERACT édite la plateforme. Les dirigeants sont ceux déclarés aux mentions légales.</p>
    <div class="mk-team">
      <?php foreach ($equipe as $e): ?>
        <div class="mk-card-by">
          <span class="mk-initials"><?= e((string) $e['initials']) ?></span>
          <span>
            <strong><?= e((string) $e['name']) ?></strong>
            <em><?= e((string) $e['role']) ?></em>
          </span>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="mk-cta-actions">
      <a class="btn-navy" href="<?= e(url('/contact')) ?>">Nous écrire</a>
      <a class="btn-ghost" href="<?= e(url('/journal')) ?>">Lire le journal</a>
    </div>
  </section>
</div>
