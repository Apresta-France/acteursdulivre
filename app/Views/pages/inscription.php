<?php
$seeksOn = old('seeks_services', '1') !== '';
$offersOn = old('offers_services', '1') !== '';
?>
<div class="auth-split">
  <div class="auth-split-form">
    <h1 class="auth-title">Créer un compte</h1>
    <p class="auth-lead">Un seul compte : vous pouvez chercher des prestataires, proposer vos services, ou les deux.</p>
    <?php if (!empty($error)): ?>
      <div class="flash flash-error"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= e(url('/inscription')) ?>" class="auth-form">
      <?= csrf_field() ?>
      <p class="field" style="margin-bottom: 0;">Que souhaitez-vous faire ?</p>
      <div class="intent-grid">
        <label class="intent-card<?= $seeksOn ? ' is-on' : '' ?>" data-intent-card>
          <input type="checkbox" name="seeks_services" value="1"<?= $seeksOn ? ' checked' : '' ?>>
          <div class="intent-card-title">Je cherche des prestataires</div>
          <p>Auteur, éditeur, collectif : commandez des prestations ou publiez vos missions.</p>
        </label>
        <label class="intent-card<?= $offersOn ? ' is-on' : '' ?>" data-intent-card>
          <input type="checkbox" name="offers_services" value="1"<?= $offersOn ? ' checked' : '' ?>>
          <div class="intent-card-title">Je propose mes services</div>
          <p>Correcteur, illustrateur, imprimeur, libraire : créez votre vitrine et vos formules.</p>
        </label>
      </div>
      <div class="auth-name-grid">
        <div>
          <label class="field" for="first_name">Prénom</label>
          <input class="input" id="first_name" name="first_name" value="<?= e((string) old('first_name')) ?>" placeholder="Marion" required>
        </div>
        <div>
          <label class="field" for="last_name">Nom</label>
          <input class="input" id="last_name" name="last_name" value="<?= e((string) old('last_name')) ?>" placeholder="Vasseur" required>
        </div>
      </div>
      <div>
        <label class="field" for="email">E-mail professionnel</label>
        <input class="input" id="email" type="email" name="email" value="<?= e((string) old('email')) ?>" placeholder="marion@exemple.fr" required>
      </div>
      <div>
        <label class="field" for="password">Mot de passe</label>
        <input class="input" id="password" type="password" name="password" placeholder="8 caractères minimum" minlength="8" required>
      </div>
      <div class="ia-box" data-if-offers<?= $offersOn ? '' : ' hidden' ?>>
        <div class="ia-box-title">Engagement sans IA générative</div>
        <p>Obligatoire pour proposer vos services. Les outils de métier — correcteur orthographique, mémoire de traduction — restent autorisés.</p>
        <label class="ia-box-check">
          <input type="checkbox" name="charte_ia" value="1"<?= $offersOn ? ' required' : '' ?>>
          Je m'engage à ne fournir aucun livrable produit par une IA générative.
        </label>
      </div>
      <label class="auth-legal">
        <input type="checkbox" name="charte" value="1" required>
        J'accepte la charte qualité, les <a href="<?= e(url('/cgu')) ?>">CGU</a> et la <a href="<?= e(url('/confidentialite')) ?>">politique de confidentialité</a>.
      </label>
      <div class="auth-actions">
        <button class="btn-orange" type="submit">Créer mon compte</button>
        <span>Déjà inscrit ? <a href="<?= e(url('/connexion')) ?>">Se connecter</a></span>
      </div>
    </form>
  </div>
  <div class="auth-split-aside">
    <div class="auth-aside-title">Ce que vous obtenez</div>
    <div class="auth-steps">
      <?php foreach ($onboarding ?? [] as $o): ?>
        <div class="auth-step">
          <span><?= e($o['num']) ?></span>
          <div>
            <div><?= e($o['title']) ?></div>
            <p><?= e($o['body']) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
