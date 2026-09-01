<?php

use Adl\Core\Auth;
use Adl\Models\User;

$impersonator = null;
$impersonated = null;
try {
    $impersonator = Auth::impersonator();
    $impersonated = $impersonator ? Auth::user() : null;
} catch (Throwable) {
    $impersonator = null;
    $impersonated = null;
}
if (!$impersonator || !$impersonated) {
    return;
}
$impersonatedName = User::displayName($impersonated);
$impersonatedShort = trim((string) ($impersonated['first_name'] ?? '')) ?: $impersonatedName;
$impersonatedEmail = (string) ($impersonated['email'] ?? '');
?>
<div class="impersonation-bar" role="status">
  <span class="impersonation-bar-badge">Impersonnation</span>
  <span class="impersonation-bar-text">Vous êtes connecté en tant que <strong><?= e($impersonatedName) ?></strong><?php if ($impersonatedEmail !== ''): ?> <span class="impersonation-bar-mail">(<?= e($impersonatedEmail) ?>)</span><?php endif; ?>.</span>
  <span class="impersonation-bar-short">Connecté en tant que <strong><?= e($impersonatedShort) ?></strong></span>
  <form method="post" action="<?= e(url('/impersonnation/quitter')) ?>">
    <?= csrf_field() ?>
    <button type="submit">Revenir à mon compte</button>
  </form>
</div>
