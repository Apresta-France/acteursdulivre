<div class="auth-simple">
  <div class="auth-simple-box">
    <h1 class="auth-title">Mot de passe oublié</h1>
    <p class="auth-lead">Indiquez l'e-mail de votre compte. Si un compte existe, vous recevrez un lien valable une heure.</p>
    <?php if (!empty($sent)): ?>
      <div class="flash flash-ok">Si un compte correspond, un e-mail vient d'être envoyé.</div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
      <div class="flash flash-error"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= e(url('/mot-de-passe-oublie')) ?>" class="auth-form">
      <?= csrf_field() ?>
      <div>
        <label class="field" for="email">E-mail</label>
        <input class="input" id="email" type="email" name="email" required placeholder="vous@exemple.fr">
      </div>
      <button class="btn-orange" type="submit">Envoyer le lien</button>
      <p class="auth-simple-foot"><a href="<?= e(url('/connexion')) ?>">Retour à la connexion</a></p>
    </form>
  </div>
</div>
