<?php
use Adl\Core\OAuth;

$s = $settings ?? [];
?>
<div class="admin-page">
  <h1>Connexion Google et Facebook</h1>
  <p style="color:#66768A;max-width:640px;margin:0 0 20px;">Les boutons n’apparaissent sur les pages connexion et inscription que lorsque les deux clés d’un fournisseur sont renseignées. Vous pouvez aussi les placer dans le fichier <code>.env</code>.</p>
  <?php if (!empty($saved)): ?><div class="flash flash-ok">Paramètres enregistrés.</div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>

  <form method="post" action="<?= e(url('/admin/sso')) ?>" style="max-width:640px;display:flex;flex-direction:column;gap:14px;background:#fff;padding:24px;border-radius:14px;">
    <?= csrf_field() ?>

    <h2 style="margin:0 0 4px;font-size:18px;color:#022746;">Google</h2>
    <p style="margin:0 0 6px;font-size:13px;color:#66768A;">Console Google Cloud → API et services → Identifiants → ID client OAuth (application Web). URI de redirection autorisée :</p>
    <code style="display:block;background:#F4F6F9;padding:10px 12px;border-radius:8px;font-size:13px;word-break:break-all;"><?= e(OAuth::redirectUri('google')) ?></code>
    <div><label class="field">Client ID</label><input class="input" name="google_client_id" value="<?= e($s['google_client_id'] ?? '') ?>" autocomplete="off"></div>
    <div><label class="field">Client secret</label><input class="input" type="password" name="google_client_secret" value="<?= e($s['google_client_secret'] ?? '') ?>" autocomplete="new-password"></div>

    <h2 style="margin:18px 0 4px;font-size:18px;color:#022746;">Facebook</h2>
    <p style="margin:0 0 6px;font-size:13px;color:#66768A;">Meta for Developers → votre application → Connexion Facebook → Paramètres. URI de redirection OAuth valide :</p>
    <code style="display:block;background:#F4F6F9;padding:10px 12px;border-radius:8px;font-size:13px;word-break:break-all;"><?= e(OAuth::redirectUri('facebook')) ?></code>
    <div><label class="field">Identifiant de l’application</label><input class="input" name="facebook_app_id" value="<?= e($s['facebook_app_id'] ?? '') ?>" autocomplete="off"></div>
    <div><label class="field">Clé secrète</label><input class="input" type="password" name="facebook_app_secret" value="<?= e($s['facebook_app_secret'] ?? '') ?>" autocomplete="new-password"></div>

    <button class="btn-orange" type="submit">Enregistrer</button>
  </form>
</div>
