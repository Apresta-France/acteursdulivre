<?php
$invoice = $invoice ?? [];
$seller = $seller ?? [];
$company = trim((string) ($seller['company_name'] ?? ''));
$who = $company !== '' ? $company : trim(($seller['first_name'] ?? '') . ' ' . ($seller['last_name'] ?? ''));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title><?= e((string) ($title ?? 'Facture')) ?></title>
  <style>
    body { font-family: Georgia, "Times New Roman", serif; color: #14202C; margin: 40px; }
    h1 { font-size: 22px; margin: 0 0 6px; }
    .muted { color: #4A5A6B; font-size: 13px; }
    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 28px; margin: 28px 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { text-align: left; padding: 10px 0; border-bottom: 1px solid #E8ECF1; }
    .total { font-size: 18px; font-weight: 700; }
    .actions { margin: 24px 0; }
    .actions button { padding: 8px 16px; }
    @media print { .actions { display: none; } body { margin: 16px; } }
  </style>
</head>
<body>
  <div class="actions">
    <button type="button" onclick="window.print()">Imprimer ou enregistrer en PDF</button>
    <a href="<?= e(url('/espace/facturation')) ?>">Retour à la facturation</a>
  </div>
  <p class="muted">EDITIONS TESSERACT · SAS · 486 rue Sadi Carnot, 59184 Sainghin-en-Weppes<br>RCS Lille Métropole 980 005 292 · TVA FR14 980 005 292</p>
  <h1>Facture <?= e((string) ($invoice['number'] ?? '')) ?></h1>
  <p class="muted">Commission d'intermédiation · émise le <?= e((string) ($invoice['issued_label'] ?? '')) ?><?= !empty($invoice['due_label']) ? ' · à régler avant le ' . e((string) $invoice['due_label']) : '' ?></p>
  <div class="grid">
    <div>
      <strong>Émetteur</strong>
      <p>EDITIONS TESSERACT<br>bonjour@acteursdulivre.fr</p>
    </div>
    <div>
      <strong>Destinataire</strong>
      <p>
        <?= e($who) ?><br>
        <?= e((string) ($seller['email'] ?? '')) ?><br>
        <?php if (!empty($seller['billing_address'])): ?><?= nl2br(e((string) $seller['billing_address'])) ?><br><?php endif; ?>
        <?php if (!empty($seller['siret'])): ?>SIRET <?= e((string) $seller['siret']) ?><br><?php endif; ?>
        <?php if (!empty($seller['vat_number'])): ?>TVA <?= e((string) $seller['vat_number']) ?><?php endif; ?>
      </p>
    </div>
  </div>
  <table>
    <thead>
      <tr><th>Désignation</th><th>Commande</th><th>Taux</th><th>Montant</th></tr>
    </thead>
    <tbody>
      <tr>
        <td>Commission plateforme<?= !empty($invoice['is_open']) || ($invoice['status'] ?? '') === 'waived' && (int) ($invoice['amount'] ?? 0) === 0 ? '' : '' ?></td>
        <td><?= e((string) ($invoice['order_number'] ?? '')) ?></td>
        <td><?= (int) ($invoice['amount'] ?? 0) === 0 ? '0 % (1ʳᵉ mission)' : e(rtrim(rtrim((string) ($invoice['commission_percent'] ?? '8'), '0'), '.') . ' %') ?></td>
        <td><?= e((string) ($invoice['amount_label'] ?? '0 €')) ?></td>
      </tr>
    </tbody>
  </table>
  <p class="total">Net à payer : <?= e((string) ($invoice['amount_label'] ?? '0 €')) ?></p>
  <p class="muted">Cette facture porte uniquement sur la commission due à EDITIONS TESSERACT. Le prix de la mission se règle hors plateforme, entre le client et le prestataire. Mentions de facture du prestataire : à sa charge.</p>
</body>
</html>
