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
    <span class="field" id="<?= e($inputId) ?>-label">Photo de profil</span>
    <?php
      $filePickId = $inputId;
      $filePickName = 'avatar';
      $filePickAccept = 'image/jpeg,image/png,image/webp';
      $filePickButton = 'Choisir une photo';
      $filePickEmpty = $avatarSrc !== '' ? 'Photo actuelle — choisir un autre fichier pour la remplacer' : 'ou déposez-la ici';
      $filePickDrop = true;
      $filePickAttrs = 'data-avatar-input aria-labelledby="' . e($inputId) . '-label"';
      require ADL_ROOT . '/app/Views/partials/file-pick.php';
    ?>
    <p class="field-help"><?= e($help) ?></p>
  </div>
</div>
