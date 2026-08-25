<div style="display: grid; grid-template-columns: 1fr 480px;">
  <div style="padding: 44px;">
    <h1 style="font-family: 'Space Grotesk', sans-serif; font-size: 32px; font-weight: 700; color: #022746; margin: 0 0 8px;">Créer un compte</h1>
    <p style="font-size: 15px; color: #66768A; margin: 0 0 26px;">Un seul compte : vous pouvez commander et proposer vos services.</p>
    <?php if (!empty($error)): ?>
      <div class="flash flash-error"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= e(url('/inscription')) ?>" style="display: flex; flex-direction: column; gap: 18px; max-width: 560px;">
      <?= csrf_field() ?>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
        <?php foreach ($roles ?? [] as $i => $r): ?>
          <label style="<?= e($r['style']) ?>">
            <input type="radio" name="role" value="<?= $i === 0 ? 'client' : 'prestataire' ?>" <?= $i === 1 ? 'checked' : '' ?> style="margin-bottom: 8px;">
            <div style="font-family: 'Space Grotesk', sans-serif; font-size: 17px; font-weight: 500; color: #022746;"><?= e($r['title']) ?></div>
            <p style="font-size: 14px; color: #66768A; line-height: 1.55; margin: 8px 0 0;"><?= e($r['desc']) ?></p>
          </label>
        <?php endforeach; ?>
      </div>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
        <div>
          <label class="field" for="first_name">Prénom</label>
          <input class="input" id="first_name" name="first_name" placeholder="Marion" required>
        </div>
        <div>
          <label class="field" for="last_name">Nom</label>
          <input class="input" id="last_name" name="last_name" placeholder="Vasseur" required>
        </div>
      </div>
      <div>
        <label class="field" for="email">E-mail professionnel</label>
        <input class="input" id="email" type="email" name="email" placeholder="marion@exemple.fr" required>
      </div>
      <div>
        <label class="field" for="password">Mot de passe</label>
        <input class="input" id="password" type="password" name="password" placeholder="8 caractères minimum" minlength="8" required>
      </div>
      <div style="border: 1.5px solid #D85D3F; background: #FDF3F0; border-radius: 12px; padding: 18px 20px;">
        <div style="font-family: 'Space Grotesk', sans-serif; font-size: 16px; color: #022746; font-weight: 500;">Engagement sans IA générative</div>
        <p style="font-size: 14px; color: #4A5A6B; line-height: 1.6; margin: 6px 0 0;">Obligatoire pour créer un compte prestataire. Les outils de métier restent autorisés.</p>
      </div>
      <label style="display: flex; gap: 10px; align-items: flex-start; font-size: 14px; color: #4A5A6B; line-height: 1.55;">
        <input type="checkbox" name="charte" value="1" required>
        J'accepte la charte qualité, les <a href="<?= e(url('/cgu')) ?>">CGU</a> et la <a href="<?= e(url('/confidentialite')) ?>">politique de confidentialité</a>.
      </label>
      <div style="display: flex; gap: 14px; align-items: center;">
        <button class="btn-orange" type="submit">Créer mon compte</button>
        <span style="font-size: 14px; color: #66768A;">Déjà inscrit ? <a href="<?= e(url('/connexion')) ?>">Se connecter</a></span>
      </div>
    </form>
  </div>
  <div style="background: #022746; color: #E4EDF5; padding: 44px;">
    <div style="font-family: 'Space Grotesk', sans-serif; font-size: 22px; color: #FFF; font-weight: 500; margin-bottom: 22px;">Ce que vous obtenez en trois étapes</div>
    <div style="display: flex; flex-direction: column; gap: 22px;">
      <?php foreach ($onboarding ?? [] as $o): ?>
        <div style="display: flex; gap: 14px;">
          <span style="font-family: 'Space Grotesk', monospace; font-size: 13px; color: #E8845F; min-width: 26px;"><?= e($o['num']) ?></span>
          <div>
            <div style="font-size: 16px; color: #FFF;"><?= e($o['title']) ?></div>
            <p style="font-size: 14px; color: #A9C0D5; line-height: 1.6; margin: 6px 0 0;"><?= e($o['body']) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
