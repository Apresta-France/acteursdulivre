<?php
use Adl\Core\OAuth;

$s = $settings ?? [];
?>
<div class="admin-page">
  <h1>Connexion Google et Facebook</h1>
  <p style="color:#66768A;max-width:640px;margin:0 0 20px;">La connexion Google / Facebook est désactivée pour le moment. Cochez l’option ci-dessous pour la rouvrir ; les boutons n’apparaissent ensuite que si les deux clés d’un fournisseur sont renseignées (ici ou dans le fichier <code>.env</code>).</p>
  <?php if (!empty($saved)): ?><div class="flash flash-ok">Paramètres enregistrés.</div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>

  <form method="post" action="<?= e(url('/admin/sso')) ?>" style="max-width:640px;display:flex;flex-direction:column;gap:14px;background:#fff;padding:24px;border-radius:14px;">
    <?= csrf_field() ?>

    <label style="display:flex;gap:10px;align-items:flex-start;padding:12px 14px;background:#F4F6F9;border-radius:10px;">
      <input type="checkbox" name="oauth_enabled" value="1"<?= OAuth::featureEnabled() ? ' checked' : '' ?> style="margin-top:3px;">
      <span>
        <strong style="display:block;color:#022746;">Activer la connexion et l’inscription via Google ou Facebook</strong>
        <span style="display:block;margin-top:4px;font-size:13px;color:#66768A;">Désactivé par défaut. Les comptes e-mail / mot de passe restent disponibles.</span>
      </span>
    </label>

    <h2 style="margin:0 0 4px;font-size:18px;color:#022746;">Google</h2>
    <p style="margin:0 0 6px;font-size:13px;color:#66768A;">Console Google Cloud → API et services → Identifiants → ID client OAuth (application Web). URI de redirection autorisée :</p>
    <code style="display:block;background:#F4F6F9;padding:10px 12px;border-radius:8px;font-size:13px;word-break:break-all;"><?= e(OAuth::redirectUri('google')) ?></code>
    <div><label class="field">Client ID</label><input class="input" name="google_client_id" value="<?= e($s['google_client_id'] ?? '') ?>" autocomplete="off"></div>
    <div>
      <label class="field">Client secret</label>
      <input class="input" type="password" name="google_client_secret" value="" placeholder="<?= !empty($googleSecretSet) ? 'Laisser vide pour conserver le secret actuel' : '' ?>" autocomplete="new-password">
      <?php if (!empty($googleSecretSet)): ?>
        <p style="color:#8496A8;font-size:13px;margin:6px 0 0;">Un secret Google est déjà enregistré.</p>
      <?php endif; ?>
    </div>

    <h2 style="margin:18px 0 4px;font-size:18px;color:#022746;">Facebook</h2>
    <p style="margin:0 0 6px;font-size:13px;color:#66768A;">Meta for Developers → votre application → Connexion Facebook → Paramètres. URI de redirection OAuth valide :</p>
    <code style="display:block;background:#F4F6F9;padding:10px 12px;border-radius:8px;font-size:13px;word-break:break-all;"><?= e(OAuth::redirectUri('facebook')) ?></code>
    <div><label class="field">Identifiant de l’application</label><input class="input" name="facebook_app_id" value="<?= e($s['facebook_app_id'] ?? '') ?>" autocomplete="off"></div>
    <div>
      <label class="field">Clé secrète</label>
      <input class="input" type="password" name="facebook_app_secret" value="" placeholder="<?= !empty($facebookSecretSet) ? 'Laisser vide pour conserver le secret actuel' : '' ?>" autocomplete="new-password">
      <?php if (!empty($facebookSecretSet)): ?>
        <p style="color:#8496A8;font-size:13px;margin:6px 0 0;">Un secret Facebook est déjà enregistré.</p>
      <?php endif; ?>
    </div>

    <button class="btn-orange" type="submit">Enregistrer</button>
  </form>
</div>
