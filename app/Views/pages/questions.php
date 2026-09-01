<?php
$questionsFaq = $questionsFaq ?? [];
?>
<div class="mk-page questions-page">
  <div class="questions-layout">
    <div class="questions-main">
      <h1>Les questions qu'on nous pose</h1>
      <p class="mk-lead">Commission, clients, règlement, inscription : les réponses au même endroit. Vous pouvez donner cette adresse à quelqu'un qui s'interroge.</p>
      <div class="mk-faq-list">
        <?php foreach ($questionsFaq as $f): ?>
          <div>
            <button type="button" data-accordion aria-expanded="<?= e((string) ($f['expanded'] ?? 'false')) ?>" class="faq-q">
              <?= e((string) $f['q']) ?><span data-accordion-sign><?= e((string) ($f['sign'] ?? '+')) ?></span>
            </button>
            <div <?= !empty($f['open']) ? '' : 'hidden' ?> class="mk-faq-a">
              <?php foreach (preg_split('/\n\s*\n/', trim((string) ($f['a'] ?? ''))) ?: [] as $para): ?>
                <p><?= e($para) ?></p>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <aside class="questions-side">
      <div class="questions-cta">
        <?php if (!empty($logged)): ?>
          <div class="mk-kicker">Votre espace</div>
          <h2>Vous êtes déjà parmi nous.</h2>
          <p>Complétez votre vitrine, publiez une recherche, ou écrivez-nous s'il reste une question.</p>
          <a class="btn-orange" href="<?= e(url('/espace')) ?>">Ouvrir mon espace</a>
          <a class="btn-ghost" href="<?= e(url('/contact')) ?>">Nous écrire</a>
        <?php else: ?>
          <div class="mk-kicker">Participer</div>
          <h2>Vous avez compris ? Rejoignez-nous.</h2>
          <p>Aucun abonnement. Créez votre compte, proposez vos services ou publiez une recherche — et participez à l'aventure.</p>
          <a class="btn-orange" href="<?= e(url('/inscription')) ?>">Créer mon compte</a>
          <a class="btn-ghost" href="<?= e(url('/contact')) ?>">Une autre question</a>
        <?php endif; ?>
      </div>

      <div class="questions-aside-visual">
        <strong>Devis → jalons → validation</strong>
        <span>Le règlement se fait entre vous. La commission est le dernier jalon.</span>
      </div>

      <div class="questions-proof">
        <div>
          <strong>0 €</strong>
          <span>aucun abonnement</span>
        </div>
        <div>
          <strong>1ʳᵉ</strong>
          <span>mission offerte au prestataire</span>
        </div>
        <div>
          <strong>8 %</strong>
          <span>dès la 2ᵉ mission, hors taxes</span>
        </div>
      </div>

      <div class="side-card">
        <div class="side-kicker">Pour aller plus loin</div>
        <div class="questions-more">
          <a href="<?= e(url('/tarifs')) ?>">Tarifs et commission</a>
          <a href="<?= e(url('/comment-ca-marche')) ?>">Comment ça marche</a>
          <a href="<?= e(url('/aide')) ?>">Centre d'aide</a>
        </div>
      </div>
    </aside>
  </div>
</div>
