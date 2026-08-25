<div class="admin-page">
<h1><?= e($template['name']) ?></h1>
<p style="color:#66768A;">Variables : <?= e($template['variables']) ?></p>
<form method="post" action="<?= e(url('/admin/emails/' . $template['id'])) ?>" style="max-width:860px;display:flex;flex-direction:column;gap:14px;">
  <?= csrf_field() ?>
  <div>
    <label class="field">Sujet</label>
    <input class="input" name="subject" value="<?= e($template['subject']) ?>">
  </div>
  <div>
    <label class="field">Corps HTML</label>
    <textarea class="textarea" name="body_html" style="min-height:360px;font-family:ui-monospace,monospace;font-size:13px;"><?= e($template['body_html']) ?></textarea>
  </div>
  <button class="btn-orange" type="submit">Enregistrer</button>
</form>
</div>
