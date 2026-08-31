<div class="admin-page">
<h1>Paramètres SMTP</h1>
<?php if (!empty($saved)): ?><div class="flash flash-ok">Paramètres enregistrés.</div><?php endif; ?>
<?php if (!empty($tested)): ?><div class="flash flash-ok"><?= e($tested) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
<form method="post" action="<?= e(url('/admin/smtp')) ?>" style="max-width:640px;display:flex;flex-direction:column;gap:14px;background:#fff;padding:24px;border-radius:14px;">
  <?= csrf_field() ?>
  <?php $s = $settings ?? []; ?>
  <div><label class="field">Hôte</label><input class="input" name="mail_host" value="<?= e($s['mail_host'] ?? '') ?>" placeholder="smtp.exemple.fr" autocomplete="off"></div>
  <div><label class="field">Port</label><input class="input" name="mail_port" value="<?= e($s['mail_port'] ?? '587') ?>" placeholder="587"></div>
  <div><label class="field">Utilisateur</label><input class="input" name="mail_username" value="<?= e($s['mail_username'] ?? '') ?>" autocomplete="off"></div>
  <div>
    <label class="field">Mot de passe</label>
    <input class="input" type="password" name="mail_password" value="" placeholder="<?= !empty($s['mail_password_set']) ? 'Laisser vide pour conserver le mot de passe actuel' : '' ?>" autocomplete="new-password">
    <?php if (!empty($s['mail_password_set'])): ?>
      <p style="color:#8496A8;font-size:13px;margin:6px 0 0;">Un mot de passe est déjà enregistré.</p>
    <?php endif; ?>
  </div>
  <div>
    <label class="field">Chiffrement</label>
    <select class="input" name="mail_encryption">
      <option value="tls" <?= (($s['mail_encryption'] ?? 'tls') === 'tls') ? 'selected' : '' ?>>TLS (STARTTLS, port 587)</option>
      <option value="ssl" <?= (($s['mail_encryption'] ?? '') === 'ssl') ? 'selected' : '' ?>>SSL (port 465)</option>
      <option value="" <?= (($s['mail_encryption'] ?? '') === '') ? 'selected' : '' ?>>Aucun</option>
    </select>
  </div>
  <div><label class="field">E-mail expéditeur</label><input class="input" name="mail_from_address" type="email" value="<?= e($s['mail_from_address'] ?? '') ?>"></div>
  <div><label class="field">Nom de l'expéditeur</label><input class="input" name="mail_from_name" value="<?= e($s['mail_from_name'] ?? '') ?>"></div>
  <button class="btn-orange" type="submit">Enregistrer</button>
</form>
<form method="post" action="<?= e(url('/admin/smtp/test')) ?>" style="max-width:640px;margin-top:18px;display:flex;gap:10px;align-items:flex-end;">
  <?= csrf_field() ?>
  <div style="flex:1;">
    <label class="field">Envoyer un test à</label>
    <input class="input" type="email" name="test_email" value="<?= e(auth_user()['email'] ?? '') ?>" required>
  </div>
  <button class="btn-navy" type="submit">Tester</button>
</form>
<p style="color:#8496A8;font-size:13px;max-width:640px;">Enregistrez d'abord les paramètres, puis lancez le test. Sans hôte SMTP, les e-mails sont seulement écrits dans <code>storage/mail</code>.</p>
</div>
