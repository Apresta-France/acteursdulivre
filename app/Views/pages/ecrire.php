<?php
$pending = is_array($pending ?? null) ? $pending : [];
$recipientName = (string) ($recipientName ?? 'le prestataire');
$showLogin = !empty($showLogin);
$draft = trim((string) old('message', (string) ($pending['message'] ?? '')));
$sujet = trim((string) ($pending['sujet'] ?? ''));
?>
<div class="auth-simple ecrire-page">
  <div class="auth-simple-box">
    <h1 class="auth-title">Envoyer votre message</h1>
    <p class="auth-lead">Pour que <?= e($recipientName) ?> puisse vous répondre, indiquez simplement votre nom et votre e-mail. Vous pourrez compléter vos informations ensuite.</p>
    <?php if (!empty($error)): ?>
      <div class="flash flash-error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($sujet !== '' || $draft !== ''): ?>
      <div class="ecrire-preview">
        <?php if ($sujet !== ''): ?>
          <p class="ecrire-sujet"><?= e($sujet) ?></p>
        <?php endif; ?>
        <?php if ($draft !== ''): ?>
          <p><?= nl2br(e($draft)) ?></p>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($showLogin): ?>
      <?php
        $oauthLead = 'Se connecter avec';
        require ADL_ROOT . '/app/Views/partials/oauth-buttons.php';
      ?>
      <form method="post" action="<?= e(url('/connexion')) ?>" class="auth-form">
        <?= csrf_field() ?>
        <?= form_guard_fields('login') ?>
        <div>
          <label class="field" for="login-email">E-mail</label>
          <input class="input" id="login-email" type="email" name="email" value="<?= e((string) old('email')) ?>" required autocomplete="email">
        </div>
        <div>
          <label class="field" for="login-password">Mot de passe</label>
          <input class="input" id="login-password" type="password" name="password" required autocomplete="current-password">
        </div>
        <button class="btn-orange" type="submit">Se connecter</button>
      </form>
      <p class="auth-simple-foot"><a href="<?= e(url('/mot-de-passe-oublie')) ?>">Mot de passe oublié ?</a></p>
    <?php else: ?>
      <form method="post" action="<?= e(url('/ecrire')) ?>" class="auth-form">
        <?= csrf_field() ?>
        <?= form_guard_fields('ecrire') ?>
        <div>
          <label class="field" for="name">Votre nom</label>
          <input class="input" id="name" name="name" value="<?= e((string) old('name')) ?>" placeholder="Camille Dupont" required autocomplete="name">
        </div>
        <div>
          <label class="field" for="email">Votre e-mail</label>
          <input class="input" id="email" type="email" name="email" value="<?= e((string) old('email')) ?>" placeholder="vous@exemple.fr" required autocomplete="email">
        </div>
        <?php if ($draft === ''): ?>
          <div>
            <label class="field" for="message">Votre message</label>
            <textarea class="textarea" id="message" name="message" rows="4" maxlength="8000" placeholder="Bonjour, je voudrais…"><?= e((string) old('message')) ?></textarea>
          </div>
        <?php else: ?>
          <input type="hidden" name="message" value="<?= e($draft) ?>">
        <?php endif; ?>
        <p class="auth-legal-text">En continuant, vous acceptez les <a href="<?= e(url('/cgu')) ?>">CGU</a>, les <a href="<?= e(url('/cgv')) ?>">CGV</a> et la <a href="<?= e(url('/confidentialite')) ?>">politique de confidentialité</a>.</p>
        <div class="ecrire-actions">
          <a class="btn-ghost" href="<?= e(url('/connexion')) ?>">Se connecter</a>
          <button class="btn-orange" type="submit" name="intent" value="later">Plus tard</button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>
