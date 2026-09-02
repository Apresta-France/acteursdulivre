<?php
$notes = [
    'Pas dans l\'index. Ni dans les notes de bas de page.',
    'Le correcteur l\'a peut-être trop bien raturée.',
    'ISBN introuvable — on a vérifié deux fois.',
    'Elle a demandé un droit à l\'oubli.',
];
?>
<div class="mk-page">
  <section class="mk-hero err404">
    <div>
      <p class="mk-kicker">Erreur 404</p>
      <h1>Cette page n'est pas dans le sommaire.</h1>
      <p class="mk-lead"><?= e((string) ($message ?? 'Le lien est peut-être ancien, ou la page a été retirée.')) ?></p>
      <form class="mk-search" method="get" action="<?= e(url('/recherche')) ?>">
        <input name="q" placeholder="Retrouver un métier, une prestation, une mission…" aria-label="Rechercher un métier, une prestation ou une mission">
        <button class="btn-orange" type="submit">Chercher</button>
      </form>
      <div class="mk-chips">
        <a href="<?= e(url('/prestataires')) ?>">Annuaire</a>
        <a href="<?= e(url('/prestations')) ?>">Prestations</a>
        <a href="<?= e(url('/missions')) ?>">Appels d'offres</a>
        <a href="<?= e(url('/aide')) ?>">Aide</a>
      </div>
      <div class="mk-cta-actions err404-actions">
        <a class="btn-orange" href="<?= e(url('/')) ?>">Retour à l'accueil</a>
        <a class="btn-ghost" href="<?= e(url('/contact')) ?>">Nous écrire</a>
      </div>
    </div>

    <aside class="err404-folio" aria-hidden="true">
      <div class="err404-folio-meta">Folio</div>
      <div class="err404-folio-num">404</div>
      <p class="err404-folio-line">page manquante<span class="err404-caret"></span></p>
      <div class="err404-notes">
        <?php foreach ($notes as $line): ?>
          <p><?= e($line) ?></p>
        <?php endforeach; ?>
      </div>
    </aside>
  </section>
</div>
