<?php

use Adl\Data\AdminCatalog;
use Adl\Models\EmailLog;

$mail = $mail ?? [];
$status = (string) ($mail['status'] ?? 'sent');
$tone = EmailLog::statusTone($status);
$fmt = static function (?string $dt): string {
    if ($dt === null || $dt === '') {
        return '—';
    }
    $ts = strtotime($dt);
    return $ts === false ? $dt : date('d/m/Y à H:i', $ts);
};
?>
<div class="admin-page">
  <p class="admin-back"><a href="<?= e(url('/admin/envois')) ?>">← Tous les e-mails envoyés</a></p>
  <h1><?= e((string) ($mail['subject'] ?? 'E-mail')) ?></h1>
  <p class="admin-lead">Envoyé le <?= e($fmt((string) ($mail['created_at'] ?? ''))) ?> à <?= e((string) ($mail['recipient'] ?? '')) ?>.</p>

  <dl class="admin-envoi-meta">
    <div>
      <dt>Destinataire</dt>
      <dd><?= e((string) ($mail['recipient'] ?? '')) ?></dd>
    </div>
    <div>
      <dt>Date et heure</dt>
      <dd><?= e($fmt((string) ($mail['created_at'] ?? ''))) ?></dd>
    </div>
    <div>
      <dt>Statut</dt>
      <dd><span class="admin-pill" style="<?= e(AdminCatalog::pill($tone)) ?>"><?= e(EmailLog::statusLabel($status)) ?></span></dd>
    </div>
    <div>
      <dt>Source</dt>
      <dd><?= e(EmailLog::sourceLabel((string) ($mail['source'] ?? ''))) ?></dd>
    </div>
    <?php if (!empty($mail['template_slug'])): ?>
      <div>
        <dt>Modèle</dt>
        <dd><?= e((string) $mail['template_slug']) ?></dd>
      </div>
    <?php endif; ?>
    <?php if (!empty($mail['error'])): ?>
      <div>
        <dt>Erreur</dt>
        <dd><?= e((string) $mail['error']) ?></dd>
      </div>
    <?php endif; ?>
  </dl>

  <h2 class="admin-h2">Contenu</h2>
  <iframe class="admin-envoi-frame" sandbox title="Contenu de l’e-mail" srcdoc="<?= e((string) ($mail['body_html'] ?? '')) ?>"></iframe>
</div>
