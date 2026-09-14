<?php

use Adl\Models\ContactMessage;
use Adl\Models\User;

$message = $message ?? [];
$id = (int) ($message['id'] ?? 0);
$status = (string) ($message['status'] ?? ContactMessage::STATUS_OPEN);
$tone = (string) ($message['status_tone'] ?? 'orange');
$open = !empty($message['is_open']);
$email = (string) ($message['email'] ?? '');
$fmt = static function (?string $dt): string {
    if ($dt === null || $dt === '') {
        return '—';
    }
    $ts = strtotime($dt);
    return $ts === false ? $dt : date('d/m/Y à H:i', $ts);
};
?>
<div class="admin-page">
  <p class="admin-back"><a href="<?= e(url('/admin/contact')) ?>">← Tous les messages</a></p>
  <div class="admin-user-hero">
    <?= avatar_html([
        'first_name' => (string) ($message['first_name'] ?? $message['name'] ?? ''),
        'last_name' => (string) ($message['last_name'] ?? ''),
        'avatar_url' => (string) ($message['avatar_url'] ?? ''),
        'email' => $email,
    ], 56) ?>
    <div class="admin-user-hero-text">
      <h1><?= e((string) ($message['who'] ?? 'Message')) ?></h1>
      <p class="admin-lead" style="margin: 4px 0 0;">
        <?php if ($email !== ''): ?>
          <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
        <?php else: ?>
          Sans e-mail
        <?php endif; ?>
      </p>
    </div>
    <span class="admin-pill tone-<?= e($tone) ?>"><?= e((string) ($message['status_label'] ?? 'À traiter')) ?></span>
  </div>

  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <dl class="admin-envoi-meta">
    <div>
      <dt>Reçu le</dt>
      <dd><?= e($fmt((string) ($message['created_at'] ?? ''))) ?></dd>
    </div>
    <?php if (!empty($message['has_account'])): ?>
      <div>
        <dt>Compte</dt>
        <dd>
          <a href="<?= e(url('/admin/utilisateurs/' . (int) ($message['user_id'] ?? 0))) ?>"><?= e((string) ($message['account_name'] ?? 'Voir le compte')) ?></a>
          <?php if (!empty($message['user_role'])): ?>
            <span class="admin-envois-source"><?= e(User::roleLabel((string) $message['user_role'])) ?></span>
          <?php endif; ?>
        </dd>
      </div>
    <?php endif; ?>
    <?php if (!empty($message['handled_at'])): ?>
      <div>
        <dt><?= $status === ContactMessage::STATUS_REPLIED ? 'Répondu le' : 'Traité le' ?></dt>
        <dd>
          <?= e($fmt((string) $message['handled_at'])) ?>
          <?php if (!empty($message['handler_name'])): ?>
            <span class="admin-envois-source">par <?= e((string) $message['handler_name']) ?></span>
          <?php endif; ?>
        </dd>
      </div>
    <?php endif; ?>
    <?php if (!empty($message['email_log_id'])): ?>
      <div>
        <dt>E-mail interne</dt>
        <dd><a href="<?= e(url('/admin/envois/' . (int) $message['email_log_id'])) ?>">Voir l’envoi #<?= (int) $message['email_log_id'] ?></a></dd>
      </div>
    <?php endif; ?>
  </dl>

  <h2 class="admin-h2">Message</h2>
  <div class="admin-contact-body"><?= nl2br(e((string) ($message['body'] ?? ''))) ?></div>

  <?php if (trim((string) ($message['reply_body'] ?? '')) !== ''): ?>
    <h2 class="admin-h2">Réponse envoyée</h2>
    <div class="admin-contact-body is-reply"><?= nl2br(e((string) $message['reply_body'])) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= e(url('/admin/contact/' . $id)) ?>" class="admin-contact-form">
    <?= csrf_field() ?>
    <input type="hidden" name="back" value="<?= e('/admin/contact/' . $id) ?>">

    <label class="field" for="contact-note">Note interne</label>
    <textarea class="textarea" id="contact-note" name="note" rows="2" placeholder="Suivi, rappel, contexte…"><?= e((string) ($message['admin_note'] ?? '')) ?></textarea>
    <div class="admin-actions">
      <button class="admin-ghost" type="submit" name="action" value="note">Enregistrer la note</button>
      <?php if ($open): ?>
        <button class="btn-navy" type="submit" name="action" value="handle">Marquer comme traité</button>
      <?php else: ?>
        <button class="admin-ghost" type="submit" name="action" value="reopen">Rouvrir</button>
      <?php endif; ?>
    </div>

    <h2 class="admin-h2">Répondre par e-mail</h2>
    <p class="admin-muted">La réponse part à <?= $email !== '' ? e($email) : 'l’adresse du message' ?>. Le message passe alors en « Répondu ».</p>
    <label class="field" for="contact-reply">Votre réponse</label>
    <textarea class="textarea" id="contact-reply" name="reply" rows="6" placeholder="Bonjour…"><?= e((string) old('reply')) ?></textarea>
    <div class="admin-actions">
      <button class="btn-orange" type="submit" name="action" value="reply"<?= $email === '' ? ' disabled' : '' ?>>Envoyer la réponse</button>
    </div>
  </form>
</div>
