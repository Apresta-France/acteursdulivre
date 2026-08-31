<?php
$garanties = $garanties ?? [];
$charte = $charte ?? [];
$litige = $litige ?? [];
?>
<div class="mk-page">
  <section class="mk-banner">
    <h1>Confiance, suivi, litiges</h1>
    <p>Le métier du livre marche à la parole donnée. La plateforme n'encaisse pas les missions : elle ajoute des profils vérifiés, un suivi à jalons, et une médiation interne en cas de désaccord.</p>
  </section>

  <section class="mk-block mk-cards-3">
    <?php foreach ($garanties as $g): ?>
      <div class="mk-way">
        <div class="mk-kicker"><?= e((string) $g['kicker']) ?></div>
        <h3><?= e((string) $g['title']) ?></h3>
        <p><?= e((string) $g['body']) ?></p>
      </div>
    <?php endforeach; ?>
  </section>

  <section class="mk-block">
    <h2>La charte qualité — six engagements</h2>
    <div class="mk-charte">
      <?php foreach ($charte as $c): ?>
        <div>
          <span class="mk-kicker"><?= e((string) $c['num']) ?></span>
          <div>
            <strong><?= e((string) $c['title']) ?></strong>
            <p><?= e((string) $c['body']) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="mk-split mk-fog-pad">
    <div>
      <h2>En cas de litige</h2>
      <div class="mk-litige">
        <?php foreach ($litige as $l): ?>
          <div>
            <span class="mk-kicker"><?= e((string) $l['num']) ?></span>
            <div>
              <strong><?= e((string) $l['title']) ?></strong>
              <p><?= e((string) $l['body']) ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div>
      <h2>Le process, jalon par jalon</h2>
      <p>Pas de contrat type, pas d'encaissement. L'acceptation du devis dans le suivi vaut accord. Ensuite, chaque étape se confirme ici.</p>
      <ol class="mk-ol">
        <li>Le prestataire envoie le devis</li>
        <li>Le client l'accepte, le refuse (nouveau devis possible) ou annule</li>
        <li>Facture d'acompte, règlement déclaré, accusé — si le devis le prévoit</li>
        <li>Livraison, puis facture et solde le cas échéant</li>
        <li>Le client valide et note : la commission est le dernier jalon prestataire</li>
      </ol>
    </div>
  </section>
</div>
