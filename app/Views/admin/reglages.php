<?php
$s = $settings ?? [];
?>
<div class="admin-page">
  <h1>Réglages</h1>
  <p class="admin-lead">Commission, membres fondateurs et délai des factures. SMTP, connexion sociale, métiers et modèles d’e-mails ont leurs propres pages.</p>
  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <form class="admin-form" method="post" action="<?= e(url('/admin/reglages')) ?>">
    <?= csrf_field() ?>
    <div>
      <label class="field" for="commission_percent">Commission après la première mission (%)</label>
      <input class="input" id="commission_percent" name="commission_percent" type="number" min="0" max="100" value="<?= e((string) ($s['commission_percent'] ?? '8')) ?>">
      <p class="field-help">La première mission validée est offerte (0 %).</p>
    </div>
    <div>
      <label class="field" for="founder_commission_percent">Commission membres fondateurs (%)</label>
      <input class="input" id="founder_commission_percent" name="founder_commission_percent" type="number" min="0" max="100" value="<?= e((string) ($s['founder_commission_percent'] ?? '6')) ?>">
    </div>
    <div>
      <label class="field" for="founder_limit">Plafond de membres fondateurs</label>
      <input class="input" id="founder_limit" name="founder_limit" type="number" min="0" value="<?= e((string) ($s['founder_limit'] ?? '100')) ?>">
    </div>
    <div>
      <label class="field" for="invoice_due_days">Délai de règlement des factures (jours)</label>
      <input class="input" id="invoice_due_days" name="invoice_due_days" type="number" min="1" value="<?= e((string) ($s['invoice_due_days'] ?? '15')) ?>">
    </div>
    <button class="btn-orange" type="submit">Enregistrer</button>
  </form>

  <div class="admin-settings-links">
    <a href="<?= e(url('/admin/listes')) ?>">Métiers &amp; spécialités</a>
    <a href="<?= e(url('/admin/newsletter')) ?>">Newsletter</a>
    <a href="<?= e(url('/admin/smtp')) ?>">SMTP</a>
    <a href="<?= e(url('/admin/sso')) ?>">Google / Facebook</a>
    <a href="<?= e(url('/admin/emails')) ?>">Modèles d’e-mails</a>
  </div>
</div>
