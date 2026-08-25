<div style="padding: 60px 44px; display: flex; justify-content: center;">
  <div style="width: 420px;">
    <h1 style="font-family: 'Space Grotesk', sans-serif; font-size: 30px; font-weight: 700; color: #022746; margin: 0 0 8px;">Se connecter</h1>
    <p style="font-size: 15px; color: #66768A; margin: 0 0 24px;">Un seul compte pour commander et pour proposer vos services.</p>
    <?php if (!empty($error)): ?>
      <div class="flash flash-error"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= e(url('/connexion')) ?>" style="display: flex; flex-direction: column; gap: 16px;">
      <?= csrf_field() ?>
      <div>
        <label class="field" for="email">E-mail</label>
        <input class="input" id="email" type="email" name="email" value="<?= e((string) old('email')) ?>" placeholder="vous@exemple.fr" required>
      </div>
      <div>
        <label class="field" for="password">Mot de passe</label>
        <input class="input" id="password" type="password" name="password" placeholder="••••••••••" required>
      </div>
      <div style="display: flex; justify-content: space-between; align-items: center; font-size: 14px;">
        <label style="display: flex; gap: 8px; align-items: center; color: #4A5A6B;">
          <input type="checkbox" name="remember" value="1"> Rester connecté
        </label>
        <span style="color: #D85D3F;">Mot de passe oublié ?</span>
      </div>
      <button class="btn-orange" type="submit">Se connecter</button>
      <div style="text-align: center; font-size: 14px; color: #66768A;">Pas encore de compte ? <a href="<?= e(url('/inscription')) ?>">Créer un compte</a></div>
    </form>
  </div>
</div>
