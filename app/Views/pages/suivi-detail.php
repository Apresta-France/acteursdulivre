<?php
$order = $order ?? [];
$isBuyer = !empty($isBuyer);
$isSeller = !empty($isSeller);
$steps = [
    ['pending', 'Ouverte'],
    ['in_progress', 'En cours'],
    ['delivered', 'Livrée'],
    ['confirmed', 'Validée'],
];
$status = (string) ($order['status'] ?? 'pending');
$rank = array_search($status, array_column($steps, 0), true);
if ($status === 'paid') {
    $rank = 3;
}
if ($status === 'dispute') {
    $rank = 1;
}
?>
<div class="espace-page">
  <div class="espace-page-head">
    <div>
      <h1><?= e((string) ($order['title'] ?? 'Commande')) ?></h1>
      <p><?= e((string) ($order['num'] ?? '')) ?> · <?= e((string) ($order['by'] ?? '')) ?> · <?= e((string) ($order['amount_label'] ?? '')) ?></p>
    </div>
    <a class="btn-navy" href="<?= e(url((string) ($threadHref ?? '/espace/messages'))) ?>">Ouvrir la messagerie</a>
  </div>

  <?php if (!empty($error)): ?>
    <div class="flash flash-error"><?= e((string) $error) ?></div>
  <?php endif; ?>
  <?php if (!empty($saved)): ?>
    <div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div>
  <?php endif; ?>

  <div class="order-steps">
    <?php foreach ($steps as $i => $step): ?>
      <div class="order-step<?= $rank !== false && $i <= (int) $rank ? ' is-on' : '' ?>">
        <span><?= e($step[1]) ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="publish-grid">
    <div>
      <div class="side-card">
        <div class="mission-row-title">
          Statut
          <span class="status-pill status-<?= e($status) ?>"><?= e((string) ($order['status_label'] ?? '')) ?></span>
        </div>
        <?php if (!empty($order['brief'])): ?>
          <h2>Brief</h2>
          <p class="profile-text"><?= nl2br(e((string) $order['brief'])) ?></p>
        <?php endif; ?>
        <?php if (!empty($order['package_name'])): ?>
          <p class="mission-row-sub">Formule : <?= e((string) $order['package_name']) ?></p>
        <?php endif; ?>
        <?php if (!empty($order['dispute_reason'])): ?>
          <p class="flash flash-error">Litige : <?= e((string) $order['dispute_reason']) ?></p>
        <?php endif; ?>
      </div>

      <div class="auth-actions" style="margin-top: 18px; flex-wrap: wrap;">
        <?php if ($isSeller && !empty($order['can_accept'])): ?>
          <form method="post" action="<?= e(url('/espace/suivi/' . (int) $order['id'] . '/accepter')) ?>">
            <?= csrf_field() ?>
            <button class="btn-orange" type="submit">Accepter et démarrer</button>
          </form>
        <?php endif; ?>
        <?php if ($isSeller && !empty($order['can_deliver'])): ?>
          <form method="post" action="<?= e(url('/espace/suivi/' . (int) $order['id'] . '/livrer')) ?>">
            <?= csrf_field() ?>
            <button class="btn-orange" type="submit">Marquer comme livrée</button>
          </form>
        <?php endif; ?>
        <?php if ($isBuyer && !empty($order['can_confirm'])): ?>
          <a class="btn-orange" href="<?= e(url('/espace/avis')) ?>">Valider et noter</a>
        <?php endif; ?>
      </div>

      <?php if (!empty($order['can_dispute'])): ?>
        <form class="param-form" method="post" action="<?= e(url('/espace/suivi/' . (int) $order['id'] . '/litige')) ?>" style="margin-top: 28px;">
          <?= csrf_field() ?>
          <label class="field" for="reason">Signaler un litige</label>
          <textarea class="textarea" id="reason" name="reason" rows="3" required placeholder="Décrivez le désaccord. Un médiateur pourra reprendre le dossier."></textarea>
          <div class="auth-actions">
            <button class="btn-ghost" type="submit">Ouvrir un litige</button>
          </div>
        </form>
      <?php endif; ?>
    </div>
    <aside class="publish-side">
      <div class="side-card">
        <div class="side-kicker">Paiement</div>
        <p>Le règlement client se fera hors plateforme pour le moment. La commission prestataire est émise lorsque le porteur valide et note la mission.</p>
      </div>
    </aside>
  </div>
</div>
