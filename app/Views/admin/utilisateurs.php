<?php

use Adl\Data\AdminCatalog;
use Adl\Models\User;

$accounts = $accounts ?? [];
$filters = $userFilters ?? [];
?>
<div class="admin-page">
  <h1>Utilisateurs</h1>
  <p class="admin-lead" style="margin-bottom: 18px;"><?= e($usersSubtitle ?? '') ?></p>

  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <div class="chip-row" style="margin-bottom: 18px;">
    <?php foreach ($filters as $f): ?>
      <a class="chip<?= !empty($f['on']) ? ' is-on' : '' ?>" href="<?= e(url($f['href'])) ?>"><?= e($f['label']) ?></a>
    <?php endforeach; ?>
  </div>

  <div class="admin-users-wrap">
    <div class="admin-users-head">
      <span>Utilisateur</span>
      <span>Usage</span>
      <span>Rôle</span>
      <span>Statut</span>
    </div>
    <?php if ($accounts === []): ?>
      <p class="admin-users-empty">Aucun utilisateur pour ce filtre.</p>
    <?php endif; ?>
    <?php foreach ($accounts as $u):
        $closed = User::isClosed($u);
        $status = (string) ($u['status'] ?? 'active');
        $tone = $closed ? 'navy' : ($status === 'active' ? 'green' : ($status === 'suspended' ? 'orange' : 'navy'));
        $role = (string) ($u['role'] ?? 'client');
        ?>
      <a class="admin-users-row" href="<?= e(url('/admin/utilisateurs/' . (int) $u['id'])) ?>">
        <div class="admin-users-who">
          <?= avatar_html($u, 30) ?>
          <div>
            <div class="admin-users-name"><?= e(User::displayName($u)) ?></div>
            <div class="admin-users-mail"><?= e((string) $u['email']) ?></div>
          </div>
        </div>
        <span><?= e(User::usageLabel($u)) ?></span>
        <span><?= e(User::roleLabel($role)) ?></span>
        <span><span class="admin-pill" style="<?= e(AdminCatalog::pill($tone)) ?>"><?= e(User::accountStatusLabel($u)) ?></span></span>
      </a>
    <?php endforeach; ?>
  </div>
</div>
