<?php

use Adl\Models\User;

$account = $account ?? [];
$profile = $profile ?? null;
$isSelf = !empty($isSelf);
$closed = !empty($closed);
$canDelete = !empty($canDelete);
$canImpersonate = !empty($canImpersonate);
$lockRole = !empty($lockRole);
$lockStatus = !empty($lockStatus);
$role = (string) ($account['role'] ?? 'client');
$status = (string) ($account['status'] ?? 'active');
$created = (string) ($account['created_at'] ?? '');
$login = (string) ($account['last_login_at'] ?? '');
$fmt = static function (string $dt, string $empty = '—'): string {
    if ($dt === '') {
        return $empty;
    }
    $ts = strtotime($dt);
    return $ts === false ? $dt : date('d/m/Y à H:i', $ts);
};
?>
<div class="admin-page">
  <p class="admin-back"><a href="<?= e(url('/admin/utilisateurs')) ?>">← Tous les utilisateurs</a></p>
  <div class="admin-user-hero">
    <?= avatar_html($account, 56) ?>
    <div class="admin-user-hero-text">
      <h1><?= e(User::displayName($account)) ?></h1>
      <p class="admin-lead" style="margin: 4px 0 0;"><?= e((string) ($account['email'] ?? '')) ?></p>
      <?php if (!empty($account['platform_cofounder'])): ?>
        <span class="profile-badge profile-badge-cofounder">Co-fondateur de la plateforme</span>
      <?php endif; ?>
      <?php if ($closed): ?>
        <p class="admin-user-closed">Compte clôturé<?= !empty($account['deleted_at']) ? ' le ' . e($fmt((string) $account['deleted_at'])) : '' ?>.</p>
      <?php endif; ?>
    </div>
    <?php if (!empty($canImpersonate)): ?>
      <form method="post" action="<?= e(url('/admin/utilisateurs/' . (int) ($account['id'] ?? 0) . '/impersonner')) ?>">
        <?= csrf_field() ?>
        <button class="btn-orange" type="submit">Se connecter en tant que…</button>
      </form>
    <?php endif; ?>
  </div>
  <?php if (empty($canImpersonate) && empty($closed) && empty($isSelf) && $status !== 'active'): ?>
    <p class="admin-user-help" style="margin-top: -10px;">L’impersonnation n’est possible que pour un compte actif.</p>
  <?php endif; ?>

  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Compte mis à jour.') ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <div class="admin-user-grid">
    <form class="admin-user-card" method="post" action="<?= e(url('/admin/utilisateurs/' . (int) ($account['id'] ?? 0))) ?>">
      <?= csrf_field() ?>
      <h2>Accès</h2>
      <p class="admin-user-help">Le rôle Administrateur ouvre tout le back-office. Le statut contrôle la connexion au site.</p>

      <label class="field" for="user-role">Rôle</label>
      <select class="input" id="user-role" name="role" <?= $lockRole ? 'disabled' : '' ?>>
        <option value="client" <?= $role === 'client' ? 'selected' : '' ?>>Porteur de projet</option>
        <option value="prestataire" <?= $role === 'prestataire' ? 'selected' : '' ?>>Prestataire</option>
        <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Administrateur</option>
      </select>
      <?php if ($lockRole): ?>
        <input type="hidden" name="role" value="<?= e($role) ?>">
        <?php if ($closed): ?>
          <p class="field-help">Un compte clôturé ne peut plus changer de rôle.</p>
        <?php elseif ($isSelf): ?>
          <p class="field-help">Vous ne pouvez pas retirer votre propre accès administrateur.</p>
        <?php else: ?>
          <p class="field-help">Impossible de retirer le dernier administrateur.</p>
        <?php endif; ?>
      <?php endif; ?>

      <label class="field" for="user-status" style="margin-top: 16px;">Statut du compte</label>
      <select class="input" id="user-status" name="status" <?= $lockStatus ? 'disabled' : '' ?>>
        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Actif</option>
        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>En attente</option>
        <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspendu</option>
      </select>
      <?php if ($lockStatus): ?>
        <input type="hidden" name="status" value="<?= e($status) ?>">
        <p class="field-help"><?= $closed ? 'Un compte clôturé ne peut plus être réactivé ici.' : 'Vous ne pouvez pas suspendre votre propre compte.' ?></p>
      <?php endif; ?>

      <?php if (!$lockRole || !$lockStatus): ?>
        <button class="btn-orange" type="submit" style="margin-top: 18px;">Enregistrer</button>
      <?php endif; ?>
    </form>

    <div class="admin-user-card">
      <h2>Fiche</h2>
      <dl class="admin-user-meta">
        <div><dt>Usage</dt><dd><?= e(User::usageLabel($account)) ?></dd></div>
        <div><dt>Inscription</dt><dd><?= e($fmt($created)) ?></dd></div>
        <div><dt>Dernière connexion</dt><dd><?= e($fmt($login, 'Jamais')) ?></dd></div>
        <?php if (!empty($profile['slug'])): ?>
          <div>
            <dt>Vitrine</dt>
            <dd><a href="<?= e(url('/prestataires/' . $profile['slug'])) ?>"><?= e((string) ($profile['title'] ?: $profile['slug'])) ?></a></dd>
          </div>
        <?php endif; ?>
        <?php if ($profile): ?>
          <div>
            <dt>Vérification</dt>
            <dd><a href="<?= e(url('/admin/verifications')) ?>">Dossiers prestataires</a></dd>
          </div>
        <?php endif; ?>
        <?php if (!empty($account['company_name'])): ?>
          <div><dt>Raison sociale</dt><dd><?= e((string) $account['company_name']) ?></dd></div>
        <?php endif; ?>
        <?php if (User::legalFormLabel((string) ($account['legal_form'] ?? '')) !== ''): ?>
          <div><dt>Forme</dt><dd><?= e(User::legalFormLabel((string) $account['legal_form'])) ?></dd></div>
        <?php endif; ?>
        <?php if (!empty($account['siren'])): ?>
          <div><dt>SIREN</dt><dd><?= e((string) $account['siren']) ?></dd></div>
        <?php endif; ?>
        <?php if (!empty($account['siret'])): ?>
          <div><dt>SIRET</dt><dd><?= e((string) $account['siret']) ?></dd></div>
        <?php endif; ?>
        <?php if (!empty($account['vat_number'])): ?>
          <div><dt>TVA</dt><dd><?= e((string) $account['vat_number']) ?></dd></div>
        <?php elseif (!empty($account['vat_exempt'])): ?>
          <div><dt>TVA</dt><dd>Franchise en base</dd></div>
        <?php endif; ?>
        <?php if (!empty($account['einvoice_routing'])): ?>
          <div><dt>Routage</dt><dd><?= e((string) $account['einvoice_routing']) ?></dd></div>
        <?php endif; ?>
        <div><dt>Statut</dt><dd><?= e(User::accountStatusLabel($account)) ?></dd></div>
        <div>
          <dt>Membre fondateur</dt>
          <dd><?= User::isFounder($account) ? 'Oui' : 'Non' ?></dd>
        </div>
      </dl>
    </div>
  </div>

  <?php if (!$closed): ?>
    <form class="admin-user-card" method="post" action="<?= e(url('/admin/utilisateurs/' . (int) ($account['id'] ?? 0))) ?>" style="margin-top: 18px;">
      <?= csrf_field() ?>
      <input type="hidden" name="section" value="badges">
      <h2>Badges</h2>
      <p class="admin-user-help">Le badge « Co-fondateur de la plateforme » est attribué à la main. Il est distinct du statut membre fondateur (100 premiers inscrits, commission réduite).</p>
      <label class="admin-sso-switch">
        <input type="checkbox" name="platform_cofounder" value="1"<?= User::isPlatformCofounder($account) ? ' checked' : '' ?>>
        <span>
          <strong>Co-fondateur de la plateforme</strong>
          <em>Affiché sur le profil public, la vitrine, le tableau de bord et le forum.</em>
        </span>
      </label>
      <button class="btn-orange" type="submit" style="margin-top: 18px;">Enregistrer le badge</button>
    </form>
  <?php endif; ?>

  <div class="admin-user-card admin-user-danger">
    <h2>Supprimer le compte</h2>
    <?php if ($closed): ?>
      <p class="admin-user-help">Ce compte est déjà clôturé. Les données personnelles ont été anonymisées. Les factures déjà émises sont conservées.</p>
    <?php elseif ($isSelf): ?>
      <p class="admin-user-help">Vous ne pouvez pas supprimer votre propre compte depuis l’administration. Utilisez les paramètres de votre espace, ou demandez à un autre administrateur.</p>
    <?php elseif (!$canDelete): ?>
      <p class="admin-user-help">Impossible de supprimer le dernier administrateur.</p>
    <?php else: ?>
      <p class="admin-user-help">Anonymise le compte et révoque l’accès. Impossible s’il reste une commande en cours, un litige ou une facture de commission ouverte. Les factures déjà émises sont conservées.</p>
      <form method="post" action="<?= e(url('/admin/utilisateurs/' . (int) ($account['id'] ?? 0) . '/supprimer')) ?>">
        <?= csrf_field() ?>
        <button class="admin-ghost" type="submit" onclick="return confirm('Supprimer définitivement ce compte ? Les données personnelles seront anonymisées.');">Supprimer le compte</button>
      </form>
    <?php endif; ?>
  </div>
</div>
