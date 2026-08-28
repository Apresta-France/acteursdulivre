<?php

use Adl\Models\User;

$account = $account ?? [];
$profile = $profile ?? null;
$isSelf = !empty($isSelf);
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
    <div>
      <h1><?= e(User::displayName($account)) ?></h1>
      <p class="admin-lead" style="margin: 4px 0 0;"><?= e((string) ($account['email'] ?? '')) ?></p>
    </div>
  </div>

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
        <p class="field-help"><?= $isSelf ? 'Vous ne pouvez pas retirer votre propre accès administrateur.' : 'Impossible de retirer le dernier administrateur.' ?></p>
      <?php endif; ?>

      <label class="field" for="user-status" style="margin-top: 16px;">Statut du compte</label>
      <select class="input" id="user-status" name="status" <?= $lockStatus ? 'disabled' : '' ?>>
        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Actif</option>
        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>En attente</option>
        <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspendu</option>
      </select>
      <?php if ($lockStatus): ?>
        <input type="hidden" name="status" value="<?= e($status) ?>">
        <p class="field-help">Vous ne pouvez pas suspendre votre propre compte.</p>
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
      </dl>
    </div>
  </div>
</div>
