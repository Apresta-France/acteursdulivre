<?php
$request = $request ?? null;
$sellerName = trim((string) ($sellerName ?? ''));
$profileHref = (string) ($profileHref ?? '');
$ok = !empty($ok);
$invalid = !empty($invalid);
?>
<div class="recommandation-page">
  <?php if ($ok): ?>
    <h1>Merci</h1>
    <p>Votre recommandation est publiée sur la vitrine<?= $sellerName !== '' ? ' de ' . e($sellerName) : '' ?>. Elle apparaît comme un texte hors plateforme, distinct des avis liés à une mission réalisée ici.</p>
    <?php if ($profileHref !== ''): ?>
      <p style="margin-top: 28px;"><a class="btn-navy" href="<?= e(url($profileHref)) ?>">Voir la vitrine</a></p>
    <?php endif; ?>
  <?php elseif ($request): ?>
    <h1>Laisser une recommandation</h1>
    <p><?= $sellerName !== '' ? e($sellerName) . ' vous demande' : 'On vous demande' ?> un mot sur un travail réalisé hors de la plateforme. Ce texte sera publié comme recommandation — il ne note pas le prestataire et ne se confond pas avec un avis de mission.</p>
    <?php if (!empty($request['context'])): ?>
      <p class="recommandation-context">À propos de : <?= e((string) $request['context']) ?></p>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
      <div class="flash flash-error"><?= e((string) $error) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= e(url('/recommandation/' . rawurlencode((string) ($request['token'] ?? '')))) ?>" class="recommandation-form">
      <?= csrf_field() ?>
      <div>
        <label class="field" for="reco-name">Votre nom</label>
        <input class="input" id="reco-name" name="name" required value="<?= e((string) (old('name') ?: $request['recipient_name'] ?? '')) ?>">
      </div>
      <div>
        <label class="field" for="reco-role">Fonction ou structure (optionnel)</label>
        <input class="input" id="reco-role" name="role" value="<?= e((string) old('role')) ?>" placeholder="Autrice, Éditions La Ligne…">
      </div>
      <div>
        <label class="field" for="reco-body">Votre recommandation</label>
        <textarea class="textarea" id="reco-body" name="body" rows="7" required minlength="40" maxlength="2000" placeholder="Ce que vous avez confié, ce qui s’est bien passé."><?= e((string) old('body')) ?></textarea>
      </div>
      <div class="auth-legal">
        <input id="reco-sincere" type="checkbox" name="sincere" value="1" required>
        <label class="auth-legal-text" for="reco-sincere">Je confirme que cette recommandation est sincère et porte sur un travail réellement réalisé.</label>
      </div>
      <div class="auth-actions">
        <button class="btn-orange" type="submit">Publier la recommandation</button>
      </div>
    </form>
  <?php else: ?>
    <h1>Lien invalide</h1>
    <p>Cette invitation n’est plus valable : elle a déjà été utilisée, annulée ou expirée. Demandez un nouveau lien à la personne qui vous l’a envoyé.</p>
    <?php if ($profileHref !== ''): ?>
      <p style="margin-top: 28px;"><a class="btn-navy" href="<?= e(url($profileHref)) ?>">Voir la vitrine</a></p>
    <?php endif; ?>
  <?php endif; ?>
</div>
