<div class="auth-simple">
  <div class="auth-simple-box">
    <h1 class="auth-title">Nouveau mot de passe</h1>
    <p class="auth-lead">Choisissez un mot de passe d'au moins 8 caractères.</p>
    <?php if (!empty($error)): ?>
      <div class="flash flash-error"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= e(url('/mot-de-passe/' . ($token ?? ''))) ?>" class="auth-form">
      <?= csrf_field() ?>
      <div>
        <label class="field" for="password">Nouveau mot de passe</label>
        <input class="input" id="password" type="password" name="password" required minlength="8">
      </div>
      <div>
        <label class="field" for="password_confirmation">Confirmation</label>
        <input class="input" id="password_confirmation" type="password" name="password_confirmation" required minlength="8">
      </div>
      <button class="btn-orange" type="submit">Enregistrer</button>
    </form>
  </div>
</div>
