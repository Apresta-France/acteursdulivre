<?php

use Adl\Core\OAuth;

$oauthProviders = OAuth::enabledProviders();
if ($oauthProviders === [] && \Adl\Core\Env::bool('APP_DEBUG')) {
    $oauthProviders = OAuth::PROVIDERS;
}
?>
<div class="espace-page">
  <h1 class="espace-page-title">Paramètres</h1>
  <p class="espace-page-lead">Votre compte reste unique. Vous pouvez chercher des prestataires, proposer vos services, ou les deux.</p>

  <?php if (!empty($saved)): ?>
    <div class="flash flash-ok">Vos paramètres ont été enregistrés.</div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div class="flash flash-error"><?= e((string) $error) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= e(url('/espace/parametres')) ?>" class="param-form" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php
      $avatarSrc = (string) ($avatarSrc ?? $userAvatarUrl ?? '');
      $initials = (string) ($userInitials ?? 'AD');
      $inputId = 'param-avatar';
      $help = 'JPG, PNG ou WebP, 2 Mo maximum. Visible dans l’espace et sur votre fiche.';
      require ADL_ROOT . '/app/Views/partials/avatar-field.php';
    ?>
    <div class="auth-name-grid">
      <div>
        <label class="field" for="first_name">Prénom</label>
        <input class="input" id="first_name" name="first_name" value="<?= e($prenom ?? '') ?>" required>
      </div>
      <div>
        <label class="field" for="last_name">Nom</label>
        <input class="input" id="last_name" name="last_name" value="<?= e($nom ?? '') ?>" required>
      </div>
    </div>

    <div>
      <p class="field" style="margin-bottom: 10px;">Mes usages</p>
      <div class="intent-grid">
        <label class="intent-card<?= !empty($seeksChecked) ? ' is-on' : '' ?>" data-intent-card>
          <input type="checkbox" name="seeks_services" value="1"<?= !empty($seeksChecked) ? ' checked' : '' ?>>
          <div class="intent-card-title">Je cherche des prestataires</div>
          <p>Publier des recherches, commander des prestations, suivre vos projets.</p>
        </label>
        <label class="intent-card<?= !empty($offersChecked) ? ' is-on' : '' ?>" data-intent-card>
          <input type="checkbox" name="offers_services" value="1"<?= !empty($offersChecked) ? ' checked' : '' ?>>
          <div class="intent-card-title">Je propose mes services</div>
          <p>Vitrine, prestations à prix affiché, candidatures aux appels d'offres.</p>
        </label>
      </div>
    </div>

    <div class="auth-actions">
      <button class="btn-orange" type="submit">Enregistrer</button>
    </div>
  </form>

  <form method="post" action="<?= e(url('/espace/parametres/mot-de-passe')) ?>" class="param-form" style="margin-top: 36px;">
    <?= csrf_field() ?>
    <p class="field">Mot de passe</p>
    <p class="espace-page-lead" style="margin-top: 0;">8 caractères minimum. Laissez le champ actuel vide si votre compte n'a pas encore de mot de passe.</p>
    <div>
      <label class="field" for="current_password">Mot de passe actuel</label>
      <input class="input" id="current_password" type="password" name="current_password">
    </div>
    <div class="auth-name-grid">
      <div>
        <label class="field" for="password">Nouveau mot de passe</label>
        <input class="input" id="password" type="password" name="password" required minlength="8">
      </div>
      <div>
        <label class="field" for="password_confirmation">Confirmation</label>
        <input class="input" id="password_confirmation" type="password" name="password_confirmation" required minlength="8">
      </div>
    </div>
    <div class="auth-actions">
      <button class="btn-navy" type="submit">Mettre à jour le mot de passe</button>
    </div>
  </form>

  <?php if ($oauthProviders !== []): ?>
    <div class="param-oauth">
      <p class="field">Connexions sociales</p>
      <p class="espace-page-lead" style="margin-top: 0;">Liez Google ou Facebook pour vous connecter sans mot de passe.</p>
      <div class="oauth-stack oauth-stack-inline">
        <?php foreach ($oauthProviders as $provider):
          $linked = !empty($linkedProviders[$provider]);
          ?>
          <?php if ($linked): ?>
            <div class="oauth-btn oauth-<?= e($provider) ?> is-linked">
              <span>Compte <?= e(OAuth::label($provider)) ?> lié</span>
            </div>
          <?php else: ?>
            <a class="oauth-btn oauth-<?= e($provider) ?>" href="<?= e(url('/auth/' . $provider . '?next=parametres')) ?>">
              Lier <?= e(OAuth::label($provider)) ?>
            </a>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>
