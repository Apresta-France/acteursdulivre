<?php
$pending = $pendingReviews ?? [];
$criteria = $criteria ?? \Adl\Models\Review::CRITERIA;
?>
<div class="espace-page">
  <div class="espace-page-head">
    <div>
      <h1>Valider et noter</h1>
      <p>Lorsque la mission est terminée, confirmez-la et notez la qualité, l'efficacité et la satisfaction globale. C'est à ce moment que la commission prestataire est facturée.</p>
    </div>
  </div>

  <?php if (!empty($saved)): ?>
    <div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div class="flash flash-error"><?= e((string) $error) ?></div>
  <?php endif; ?>

  <?php if ($pending === []): ?>
    <div class="search-empty">
      <strong>Aucune mission à valider pour le moment.</strong>
      <span>Dès qu'une commande sera livrée, vous pourrez confirmer la fin de mission et laisser votre avis ici.</span>
      <a class="btn-ghost" href="<?= e(url('/espace/commandes')) ?>">Voir mes commandes</a>
    </div>
  <?php else: ?>
    <div class="review-stack">
      <?php foreach ($pending as $order): ?>
        <article class="side-card review-card">
          <div class="mission-row-title">
            <?= e((string) $order['title']) ?>
            <span class="status-pill status-<?= e((string) $order['status']) ?>"><?= e((string) $order['status_label']) ?></span>
          </div>
          <div class="mission-row-sub">
            <?= e((string) $order['num']) ?> · <?= e((string) $order['by']) ?> · <?= e((string) $order['amount_label']) ?>
          </div>

          <form method="post" action="<?= e(url('/espace/avis/' . (int) $order['id'])) ?>" class="review-form">
            <?= csrf_field() ?>
            <?php foreach ($criteria as $key => $label): ?>
              <fieldset class="review-criterion">
                <legend><?= e($label) ?></legend>
                <div class="review-stars" role="radiogroup" aria-label="<?= e($label) ?>">
                  <?php for ($n = 1; $n <= 5; $n++): ?>
                    <label>
                      <input type="radio" name="<?= e($key) ?>" value="<?= $n ?>" required<?= $n === 5 ? ' checked' : '' ?>>
                      <span><?= $n ?></span>
                    </label>
                  <?php endfor; ?>
                </div>
              </fieldset>
            <?php endforeach; ?>

            <div>
              <label class="field" for="review-body-<?= (int) $order['id'] ?>">Commentaire (facultatif)</label>
              <textarea class="input textarea" id="review-body-<?= (int) $order['id'] ?>" name="body" rows="3" placeholder="Ce qui a bien fonctionné, ce qui pourrait être amélioré…"></textarea>
            </div>

            <label class="auth-legal">
              <input type="checkbox" name="accept_cgv" value="1" required>
              J'accepte les <a href="<?= e(url('/cgv')) ?>">conditions générales de vente</a> et je confirme que la mission est finalisée.
            </label>

            <div class="auth-actions">
              <button class="btn-orange" type="submit">Valider la mission et publier l'avis</button>
            </div>
          </form>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
