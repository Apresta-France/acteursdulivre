<?php
$avatarSrc = (string) ($avatarSrc ?? '');
$initials = (string) ($initials ?? 'AD');
$inputId = (string) ($inputId ?? 'avatar');
$size = (int) ($size ?? 88);
$help = (string) ($help ?? 'JPG, PNG ou WebP, 2 Mo maximum.');
?>
<div class="avatar-field" data-avatar-field>
  <div class="avatar-field-preview" data-avatar-preview>
    <?php if ($avatarSrc !== ''): ?>
      <img class="avatar avatar-photo" src="<?= e($avatarSrc) ?>" alt="" width="<?= $size ?>" height="<?= $size ?>">
    <?php else: ?>
      <span class="avatar" style="<?= e(avatar_style($initials, $size)) ?>"><?= e($initials) ?></span>
    <?php endif; ?>
  </div>
  <div>
    <label class="field" for="<?= e($inputId) ?>">Photo de profil</label>
    <input class="input" id="<?= e($inputId) ?>" type="file" name="avatar" accept="image/jpeg,image/png,image/webp" data-avatar-input>
    <p class="field-help"><?= e($help) ?></p>
  </div>
</div>
