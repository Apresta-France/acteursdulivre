<?php
$filePickId = (string) ($filePickId ?? '');
$filePickName = (string) ($filePickName ?? 'file');
$filePickAccept = (string) ($filePickAccept ?? '');
$filePickRequired = !empty($filePickRequired);
$filePickMultiple = !empty($filePickMultiple);
$filePickButton = (string) ($filePickButton ?? 'Choisir un fichier');
$filePickDrop = !empty($filePickDrop);
$filePickEmpty = (string) ($filePickEmpty ?? ($filePickDrop ? 'ou déposez-le ici' : 'Aucun fichier choisi'));
$filePickAttrs = (string) ($filePickAttrs ?? '');
$filePickHint = (string) ($filePickHint ?? '');
?>
<label class="file-pick<?= $filePickDrop ? ' is-drop' : '' ?>" data-file-pick>
  <input
    class="file-pick-input"
    <?= $filePickId !== '' ? 'id="' . e($filePickId) . '"' : '' ?>
    type="file"
    name="<?= e($filePickName) ?>"
    <?= $filePickAccept !== '' ? 'accept="' . e($filePickAccept) . '"' : '' ?>
    <?= $filePickRequired ? 'required' : '' ?>
    <?= $filePickMultiple ? 'multiple' : '' ?>
    <?= $filePickAttrs ?>
    data-file-input
  >
  <span class="btn-ghost file-pick-btn"><?= e($filePickButton) ?></span>
  <span class="file-pick-name" data-file-name><?= e($filePickEmpty) ?></span>
  <?php if ($filePickHint !== ''): ?>
    <span class="file-pick-hint"><?= e($filePickHint) ?></span>
  <?php endif; ?>
</label>
<?php
unset(
    $filePickId,
    $filePickName,
    $filePickAccept,
    $filePickRequired,
    $filePickMultiple,
    $filePickButton,
    $filePickEmpty,
    $filePickAttrs,
    $filePickDrop,
    $filePickHint
);
?>
