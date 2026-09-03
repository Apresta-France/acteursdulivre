<div class="admin-page">
  <p class="admin-back"><a href="<?= e(url('/admin/emails')) ?>">← Tous les modèles</a></p>
  <h1><?= e($template['name']) ?></h1>
  <p class="admin-lead">Variables : <?= e($template['variables']) ?></p>
  <form method="post" action="<?= e(url('/admin/emails/' . $template['id'])) ?>" style="max-width:860px;display:flex;flex-direction:column;gap:14px;">
    <?= csrf_field() ?>
    <div>
      <label class="field">Sujet</label>
      <input class="input" name="subject" value="<?= e($template['subject']) ?>">
    </div>
    <div>
      <label class="field">Corps HTML</label>
      <textarea class="textarea" name="body_html" style="min-height:280px;font-family:ui-monospace,monospace;font-size:13px;"><?= e($template['body_html']) ?></textarea>
    </div>
    <button class="btn-orange" type="submit">Enregistrer</button>
  </form>
  <?php if (!empty($previewHtml)): ?>
    <h2 class="admin-h2">Aperçu</h2>
    <p class="admin-muted">Les {{ variables }} restent visibles ici ; elles sont remplacées à l’envoi.</p>
    <iframe class="admin-envoi-frame" sandbox title="Aperçu du modèle" srcdoc="<?= e((string) $previewHtml) ?>"></iframe>
  <?php endif; ?>
</div>
