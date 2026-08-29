<?php
$niveaux = $niveaux ?? [];
$exemple = $exemple ?? [];
$gratuit = $gratuit ?? [];
$tarifsFaq = $tarifsFaq ?? [];
?>
<div class="mk-page mk-pad">
  <h1>Tarifs et commission</h1>
  <p class="mk-lead">Aucun abonnement. Publier une recherche, créer une vitrine et proposer des fiches sont libres. Le client règle le prestataire hors plateforme. La première mission est offerte ; ensuite, 8&nbsp;% de commission sur les missions réalisées.</p>

  <div class="tarif-grid">
    <?php foreach ($niveaux as $i => $n): ?>
      <div class="tarif-card<?= $i === 0 ? ' is-navy' : '' ?>">
        <div class="mk-kicker"><?= e((string) $n['kicker']) ?></div>
        <h2><?= e((string) $n['name']) ?></h2>
        <div class="tarif-pct"><?= e((string) $n['pct']) ?></div>
        <p>de commission</p>
        <ul class="mk-points">
          <?php foreach ($n['items'] as $item): ?>
            <li><?= e((string) $item) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="mk-split">
    <div class="tarif-exemple">
      <div class="tarif-exemple-head">Exemple de calcul</div>
      <?php foreach ($exemple as $e): ?>
        <div class="tarif-row">
          <span><?= e((string) $e['k']) ?></span>
          <strong><?= e((string) $e['v']) ?></strong>
        </div>
      <?php endforeach; ?>
    </div>
    <div>
      <h2>Ce qui reste gratuit</h2>
      <ul class="mk-points">
        <?php foreach ($gratuit as $g): ?>
          <li><?= e((string) $g) ?></li>
        <?php endforeach; ?>
      </ul>
      <p class="mk-note">Le client règle le prestataire hors plateforme. Quand il confirme la mission et note la prestation, la plateforme facture sa commission au prestataire — dernier jalon — payable sous 15 jours. Une facture impayée à l'échéance suspend les offres.</p>
    </div>
  </div>

  <section>
    <h2>Questions fréquentes</h2>
    <div class="mk-faq-list">
      <?php foreach ($tarifsFaq as $f): ?>
        <div>
          <button type="button" data-accordion aria-expanded="<?= e((string) ($f['expanded'] ?? 'false')) ?>" class="faq-q">
            <?= e((string) $f['q']) ?><span data-accordion-sign><?= e((string) ($f['sign'] ?? '+')) ?></span>
          </button>
          <div <?= !empty($f['open']) ? '' : 'hidden' ?> class="mk-faq-a"><?= e((string) $f['a']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
</div>
