<div class="auth-simple">
  <div class="auth-simple-box">
    <h1 class="auth-title">Se connecter</h1>
    <p class="auth-lead">Un seul compte pour commander et pour proposer vos services.</p>
    <?php if (!empty($error)): ?>
      <div class="flash flash-error"><?= e($error) ?></div>
    <?php endif; ?>
    <?php require ADL_ROOT . '/app/Views/partials/oauth-buttons.php'; ?>
    <form method="post" action="<?= e(url('/connexion')) ?>" class="auth-form">
      <?= csrf_field() ?>
      <div>
        <label class="field" for="email">E-mail</label>
        <input class="input" id="email" type="email" name="email" value="<?= e((string) old('email')) ?>" placeholder="vous@exemple.fr" required>
      </div>
      <div>
        <label class="field" for="password">Mot de passe</label>
        <input class="input" id="password" type="password" name="password" placeholder="••••••••••" required>
      </div>
      <div class="auth-simple-row">
        <label class="auth-remember">
          <input type="checkbox" name="remember" value="1"> Rester connecté
        </label>
        <a href="<?= e(url('/mot-de-passe-oublie')) ?>">Mot de passe oublié ?</a>
      </div>
      <button class="btn-orange" type="submit">Se connecter</button>
      <p class="auth-simple-foot">Pas encore de compte ? <a href="<?= e(url('/inscription')) ?>">Créer un compte</a></p>
    </form>
  </div>
</div>
