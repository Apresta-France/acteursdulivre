<?php
use Adl\Core\OAuth;

$seeksOn = old('seeks_services', '1') !== '';
$offersOn = old('offers_services', '1') !== '';
$pending = $pending ?? [];
$providerLabel = OAuth::label((string) ($pending['provider'] ?? 'google'));
?>
<div class="auth-split">
  <div class="auth-split-form">
    <h1 class="auth-title">Dernière étape</h1>
    <p class="auth-lead">Votre identité <?= e($providerLabel) ?> est confirmée. Indiquez comment vous utiliserez la plateforme.</p>
    <?php if (!empty($error)): ?>
      <div class="flash flash-error"><?= e($error) ?></div>
    <?php endif; ?>
    <div class="oauth-identity">
      <div class="oauth-identity-name"><?= e(trim(($pending['first_name'] ?? '') . ' ' . ($pending['last_name'] ?? ''))) ?></div>
      <div class="oauth-identity-email"><?= e((string) ($pending['email'] ?? '')) ?></div>
      <div class="oauth-identity-via">via <?= e($providerLabel) ?></div>
    </div>
    <form method="post" action="<?= e(url('/inscription/sso')) ?>" class="auth-form">
      <?= csrf_field() ?>
      <p class="field" style="margin-bottom: 0;">Que souhaitez-vous faire ?</p>
      <div class="intent-grid">
        <label class="intent-card<?= $seeksOn ? ' is-on' : '' ?>" data-intent-card>
          <input type="checkbox" name="seeks_services" value="1"<?= $seeksOn ? ' checked' : '' ?>>
          <div class="intent-card-title">Je cherche des prestataires</div>
          <p>Auteur, éditeur, collectif : commandez des prestations ou publiez vos missions.</p>
        </label>
        <label class="intent-card<?= $offersOn ? ' is-on' : '' ?>" data-intent-card>
          <input type="checkbox" name="offers_services" value="1"<?= $offersOn ? ' checked' : '' ?>>
          <div class="intent-card-title">Je propose mes services</div>
          <p>Correcteur, bêta-lecteur, illustrateur, imprimeur, libraire : créez votre vitrine et vos formules.</p>
        </label>
      </div>
      <div class="ia-box" data-if-offers<?= $offersOn ? '' : ' hidden' ?>>
        <div class="ia-box-title">Engagement sans IA générative</div>
        <p>Obligatoire pour proposer vos services. Les outils de métier — correcteur orthographique, mémoire de traduction — restent autorisés.</p>
        <label class="ia-box-check">
          <input type="checkbox" name="charte_ia" value="1"<?= $offersOn ? ' required' : '' ?>>
          <span>Je m'engage à ne fournir aucun livrable produit par une IA générative.</span>
        </label>
        <a class="ia-box-cta" href="<?= e(url('/regles-ia')) ?>">Lire nos règles d'intelligence artificielle →</a>
      </div>
      <div class="auth-legal">
        <input id="charte" type="checkbox" name="charte" value="1" required>
        <label class="auth-legal-text" for="charte">J'accepte la charte qualité, les <a href="<?= e(url('/cgu')) ?>">CGU</a>, les <a href="<?= e(url('/cgv')) ?>">CGV</a>, la <a href="<?= e(url('/confidentialite')) ?>">politique de confidentialité</a> et les <a href="<?= e(url('/regles-ia')) ?>">règles IA</a>.</label>
      </div>
      <div class="auth-actions">
        <button class="btn-orange" type="submit">Créer mon compte</button>
        <span>ou <a href="<?= e(url('/connexion')) ?>">annuler</a></span>
      </div>
    </form>
  </div>
  <div class="auth-split-aside">
    <div class="auth-aside-title">Ce que vous obtenez</div>
    <div class="auth-steps">
      <?php foreach ($onboarding ?? [] as $o): ?>
        <div class="auth-step">
          <span><?= e($o['num']) ?></span>
          <div>
            <div><?= e($o['title']) ?></div>
            <p><?= e($o['body']) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
