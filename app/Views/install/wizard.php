<div class="install-card">
  <div class="install-head">
    <div class="install-kicker">Première installation</div>
    <h1>Acteurs du Livre</h1>
    <p>Renseignez l'environnement local. Un fichier <code>.env</code> sera créé à la racine, puis les tables seront migrées.</p>
  </div>
  <div class="install-body-inner">
    <?php if (!empty($error)): ?>
      <div class="flash flash-error"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" action="/install">
      <div class="install-section">Application</div>
      <div class="install-grid">
        <div>
          <label class="field">Nom</label>
          <input class="input" name="APP_NAME" value="<?= e($values['APP_NAME'] ?? '') ?>">
        </div>
        <div>
          <label class="field">URL</label>
          <input class="input" name="APP_URL" value="<?= e($values['APP_URL'] ?? '') ?>">
        </div>
      </div>

      <div class="install-section">Base de données</div>
      <div class="install-grid">
        <div>
          <label class="field">Hôte</label>
          <input class="input" name="DB_HOST" value="<?= e($values['DB_HOST'] ?? '') ?>">
        </div>
        <div>
          <label class="field">Port</label>
          <input class="input" name="DB_PORT" value="<?= e($values['DB_PORT'] ?? '') ?>">
        </div>
        <div>
          <label class="field">Nom de la base</label>
          <input class="input" name="DB_NAME" value="<?= e($values['DB_NAME'] ?? '') ?>">
        </div>
        <div>
          <label class="field">Utilisateur</label>
          <input class="input" name="DB_USER" value="<?= e($values['DB_USER'] ?? '') ?>">
        </div>
        <div>
          <label class="field">Mot de passe</label>
          <input class="input" type="password" name="DB_PASS" value="<?= e($values['DB_PASS'] ?? '') ?>">
        </div>
      </div>

      <div class="install-section">Compte administrateur</div>
      <div class="install-grid">
        <div>
          <label class="field">Prénom</label>
          <input class="input" name="ADMIN_FIRST" value="<?= e($values['ADMIN_FIRST'] ?? '') ?>">
        </div>
        <div>
          <label class="field">Nom</label>
          <input class="input" name="ADMIN_LAST" value="<?= e($values['ADMIN_LAST'] ?? '') ?>">
        </div>
        <div>
          <label class="field">E-mail</label>
          <input class="input" type="email" name="ADMIN_EMAIL" value="<?= e($values['ADMIN_EMAIL'] ?? '') ?>" required>
        </div>
        <div>
          <label class="field">Mot de passe</label>
          <input class="input" type="password" name="ADMIN_PASSWORD" minlength="8" required>
        </div>
      </div>

      <div class="install-section">SMTP (optionnel)</div>
      <div class="install-grid">
        <div>
          <label class="field">Hôte</label>
          <input class="input" name="MAIL_HOST" value="<?= e($values['MAIL_HOST'] ?? '') ?>" placeholder="smtp.exemple.fr">
        </div>
        <div>
          <label class="field">Port</label>
          <input class="input" name="MAIL_PORT" value="<?= e($values['MAIL_PORT'] ?? '587') ?>">
        </div>
        <div>
          <label class="field">Utilisateur</label>
          <input class="input" name="MAIL_USERNAME" value="<?= e($values['MAIL_USERNAME'] ?? '') ?>">
        </div>
        <div>
          <label class="field">Mot de passe</label>
          <input class="input" type="password" name="MAIL_PASSWORD" value="<?= e($values['MAIL_PASSWORD'] ?? '') ?>">
        </div>
        <div>
          <label class="field">Chiffrement</label>
          <select class="input" name="MAIL_ENCRYPTION">
            <option value="tls" <?= (($values['MAIL_ENCRYPTION'] ?? '') === 'tls') ? 'selected' : '' ?>>TLS</option>
            <option value="ssl" <?= (($values['MAIL_ENCRYPTION'] ?? '') === 'ssl') ? 'selected' : '' ?>>SSL</option>
            <option value="" <?= (($values['MAIL_ENCRYPTION'] ?? '') === '') ? 'selected' : '' ?>>Aucun</option>
          </select>
        </div>
        <div>
          <label class="field">E-mail expéditeur</label>
          <input class="input" name="MAIL_FROM_ADDRESS" value="<?= e($values['MAIL_FROM_ADDRESS'] ?? '') ?>">
        </div>
      </div>
      <div style="margin-top: 14px;">
        <label class="field">Nom de l'expéditeur</label>
        <input class="input" name="MAIL_FROM_NAME" value="<?= e($values['MAIL_FROM_NAME'] ?? '') ?>">
      </div>

      <div style="margin-top: 28px;">
        <button class="btn-orange" type="submit">Créer l'environnement et installer</button>
      </div>
    </form>
  </div>
</div>
