<?php
$questionsFaq = $questionsFaq ?? [];
?>
<div class="mk-page questions-page">
  <section class="mk-intro">
    <h1>Les questions qu'on nous pose</h1>
    <p class="mk-lead">Commission, clients, règlement, inscription : les réponses au même endroit. Vous pouvez donner cette adresse à quelqu'un qui s'interroge.</p>
  </section>

  <section class="mk-block">
    <div class="mk-faq-list mk-faq-narrow">
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
  </section>

  <section class="mk-cta">
    <?php if (!empty($logged)): ?>
      <div>
        <h2>Vous êtes déjà parmi nous.</h2>
        <p>Complétez votre vitrine, publiez une recherche, ou écrivez-nous s'il reste une question.</p>
      </div>
      <div class="mk-cta-actions">
        <a class="btn-orange" href="<?= e(url('/espace')) ?>">Ouvrir mon espace</a>
        <a class="btn-ghost" href="<?= e(url('/contact')) ?>">Nous écrire</a>
      </div>
    <?php else: ?>
      <div>
        <h2>Vous avez compris ? Rejoignez-nous.</h2>
        <p>Aucun abonnement. Créez votre compte, proposez vos services ou publiez une recherche — et participez à l'aventure.</p>
      </div>
      <div class="mk-cta-actions">
        <a class="btn-orange" href="<?= e(url('/inscription')) ?>">Créer mon compte</a>
        <a class="btn-ghost" href="<?= e(url('/contact')) ?>">Une autre question</a>
      </div>
    <?php endif; ?>
  </section>
</div>
