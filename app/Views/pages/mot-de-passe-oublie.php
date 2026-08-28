<div style="padding: 60px 44px; display: flex; justify-content: center;">
  <div style="width: 420px;">
    <h1 style="font-family: 'Space Grotesk', sans-serif; font-size: 30px; font-weight: 700; color: #022746; margin: 0 0 8px;">Mot de passe oublié</h1>
    <p style="font-size: 15px; color: #66768A; margin: 0 0 24px;">Indiquez l'e-mail de votre compte. Si un compte existe, vous recevrez un lien valable une heure.</p>
    <?php if (!empty($sent)): ?>
      <div class="flash flash-ok">Si un compte correspond, un e-mail vient d'être envoyé.</div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
      <div class="flash flash-error"><?= e((string) $error) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= e(url('/mot-de-passe-oublie')) ?>" style="display: flex; flex-direction: column; gap: 16px;">
      <?= csrf_field() ?>
      <div>
        <label class="field" for="email">E-mail</label>
        <input class="input" id="email" type="email" name="email" required placeholder="vous@exemple.fr">
      </div>
      <button class="btn-orange" type="submit">Envoyer le lien</button>
      <div style="text-align: center; font-size: 14px; color: #66768A;">
        <a href="<?= e(url('/connexion')) ?>">Retour à la connexion</a>
      </div>
    </form>
  </div>
</div>
