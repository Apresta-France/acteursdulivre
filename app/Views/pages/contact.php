<div style="padding: 44px; display: grid; grid-template-columns: 1fr 400px; gap: 44px; align-items: start;">
  <div>
    <h1 style="font-family: 'Space Grotesk', sans-serif; font-size: 34px; font-weight: 700; color: #022746; margin: 0 0 10px;">Nous écrire</h1>
    <p style="font-size: 16px; color: #66768A; margin: 0 0 26px;">Une équipe en France, du lundi au vendredi, 9 h – 18 h. Réponse moyenne : 4 h.</p>
    <?php if (!empty($sent)): ?>
      <div class="flash flash-ok">Message envoyé. Nous vous répondons sous 4 heures ouvrées.</div>
    <?php endif; ?>
    <?php if ($err = flash('error')): ?>
      <div class="flash flash-error"><?= e($err) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= e(url('/contact')) ?>" style="display: flex; flex-direction: column; gap: 20px; max-width: 620px;">
      <?= csrf_field() ?>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
        <div>
          <label class="field" for="name">Nom</label>
          <input class="input" id="name" name="name" value="<?= e((string) old('name')) ?>" placeholder="Votre nom">
        </div>
        <div>
          <label class="field" for="email">E-mail</label>
          <input class="input" id="email" type="email" name="email" value="<?= e((string) old('email')) ?>" placeholder="vous@exemple.fr" required>
        </div>
      </div>
      <div>
        <label class="field" for="message">Message</label>
        <textarea class="textarea" id="message" name="message" placeholder="Dites-nous tout." required style="height: 150px; resize: none; line-height: 1.6;"><?= e((string) old('message')) ?></textarea>
      </div>
      <button class="btn-orange" type="submit" style="align-self: flex-start;">Envoyer</button>
    </form>
  </div>
  <div style="display: flex; flex-direction: column; gap: 16px;">
    <div style="border: 1px solid #E8ECF1; border-radius: 14px; padding: 22px;">
      <div style="font-family: 'Space Grotesk', sans-serif; font-size: 16px; font-weight: 500; color: #022746; margin-bottom: 12px;">Autres canaux</div>
      <?php foreach ($contactCanaux ?? [] as $c): ?>
        <div style="display: flex; justify-content: space-between; font-size: 15px; margin-bottom: 12px; gap: 12px;">
          <span style="color: #8496A8;"><?= e($c['k']) ?></span>
          <?php
            $href = (string) ($c['href'] ?? '');
            $external = str_starts_with($href, 'http');
          ?>
          <?php if ($href !== ''): ?>
            <a href="<?= e($href) ?>"<?= $external ? ' target="_blank" rel="noopener noreferrer"' : '' ?> style="color: #14202C; text-align: right;"><?= e($c['v']) ?></a>
          <?php else: ?>
            <span style="color: #14202C; text-align: right;"><?= e($c['v']) ?></span>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <div style="border: 1px solid #E8ECF1; border-radius: 14px; padding: 22px;">
      <div style="font-family: 'Space Grotesk', sans-serif; font-size: 16px; font-weight: 500; color: #022746; margin-bottom: 10px;">Une question sur une commande ?</div>
      <p style="font-size: 14px; color: #66768A; line-height: 1.6; margin: 0 0 12px;">Passez par le centre d'aide : les réponses sur les jalons, la commission et les délais y sont déjà.</p>
      <a class="btn-navy" href="<?= e(url('/aide')) ?>" style="width: 100%; justify-content: center; background: #fff; color: #022746; border: 1px solid #E1E7ED;">Centre d'aide</a>
    </div>
  </div>
</div>
