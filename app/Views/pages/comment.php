<?php
$ways = $ways ?? [];
$steps4 = $steps4 ?? [];
$commentFaq = $commentFaq ?? [];
?>
<div class="mk-page">
  <section class="mk-intro">
    <h1>Trois façons de faire avancer un livre</h1>
    <p class="mk-lead">La plateforme ne choisit pas à votre place : selon votre projet, vous cherchez un profil, vous achetez une prestation cadrée, ou vous laissez les prestataires vous proposer un devis.</p>
  </section>

  <section class="mk-block mk-cards-3">
    <?php foreach ($ways as $w): ?>
      <div class="mk-way">
        <div class="mk-kicker"><?= e((string) $w['kicker']) ?></div>
        <h3><?= e((string) $w['title']) ?></h3>
        <p><?= e((string) $w['body']) ?></p>
        <?php if (!empty($w['points'])): ?>
          <ul class="mk-points">
            <?php foreach ($w['points'] as $p): ?>
              <li><?= e((string) $p) ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
        <a href="<?= e(url((string) ($w['href'] ?? '/recherche'))) ?>"><?= e((string) $w['cta']) ?> →</a>
      </div>
    <?php endforeach; ?>
  </section>

  <section class="mk-steps">
    <?php foreach ($steps4 as $s): ?>
      <div>
        <div class="mk-kicker"><?= e((string) $s['num']) ?></div>
        <h3><?= e((string) $s['title']) ?></h3>
        <p><?= e((string) $s['body']) ?></p>
      </div>
    <?php endforeach; ?>
  </section>

  <section class="mk-split">
    <div>
      <h2>Côté prestataire</h2>
      <p>Vous créez votre vitrine, vous fixez vos prix, vous envoyez le devis. Le client vous règle hors plateforme : le suivi impose les jalons. Aucun abonnement : la première mission est offerte, puis la plateforme facture 8 % au prestataire lorsque le client confirme et note la mission.</p>
      <div class="mk-cta-actions">
        <a class="btn-navy" href="<?= e(url('/espace/prestations/creer')) ?>">Proposer une prestation</a>
        <a class="btn-ghost" href="<?= e(url('/tarifs')) ?>">Voir les tarifs</a>
      </div>
    </div>
    <div class="mk-split-visual" aria-hidden="true">
      <strong>Devis → jalons → validation</strong>
      <span>Le règlement se fait entre vous. La commission est le dernier jalon.</span>
    </div>
  </section>

  <section class="mk-block">
    <h2>Questions fréquentes</h2>
    <div class="mk-faq-list mk-faq-narrow">
      <?php foreach ($commentFaq as $f): ?>
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
