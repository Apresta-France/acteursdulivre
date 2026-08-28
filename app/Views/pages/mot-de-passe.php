<div style="padding: 60px 44px; display: flex; justify-content: center;">
  <div style="width: 420px;">
    <h1 style="font-family: 'Space Grotesk', sans-serif; font-size: 30px; font-weight: 700; color: #022746; margin: 0 0 8px;">Nouveau mot de passe</h1>
    <p style="font-size: 15px; color: #66768A; margin: 0 0 24px;">Choisissez un mot de passe d'au moins 8 caractères.</p>
    <?php if (!empty($error)): ?>
      <div class="flash flash-error"><?= e((string) $error) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= e(url('/mot-de-passe/' . ($token ?? ''))) ?>" style="display: flex; flex-direction: column; gap: 16px;">
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
