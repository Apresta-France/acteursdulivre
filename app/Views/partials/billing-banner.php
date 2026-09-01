<?php
$block = $billingBlock ?? null;
$warning = $billingWarning ?? null;
if (!$block && !$warning) {
    return;
}
$invoice = $block ?: $warning;
?>
<div class="flash <?= $block ? 'flash-error' : 'flash-warn' ?>">
  <?php if ($block): ?>
    <strong>Prestations suspendues.</strong>
    La facture <?= e((string) $invoice['number']) ?> (<?= e((string) ($invoice['amount_due_label'] ?? $invoice['amount_label'])) ?>)
    était due le <?= e((string) $invoice['due_label']) ?>.
    Tant qu'elle n'est pas réglée, vos fiches ne sont plus proposées sur la plateforme.
    <a href="<?= e(url('/espace/facturation')) ?>">Voir la facture</a>
  <?php else: ?>
    <strong>Facture de commission à régler.</strong>
    <?= e((string) $invoice['number']) ?> · <?= e((string) ($invoice['amount_due_label'] ?? $invoice['amount_label'])) ?>,
    avant le <?= e((string) $invoice['due_label']) ?>.
    Passé ce délai, vos prestations seront retirées de l'annuaire.
    <a href="<?= e(url('/espace/facturation')) ?>">Voir la facturation</a>
  <?php endif; ?>
</div>
