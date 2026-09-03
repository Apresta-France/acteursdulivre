<?php
$i = $portfolioIndex ?? 0;
$item = is_array($item ?? null) ? $item : [];
$hasRealImage = trim((string) ($item['image_path'] ?? '')) !== '' || trim((string) ($item['image_url'] ?? '')) !== '';
$previewSrc = $hasRealImage ? (string) ($item['image'] ?? '') : '';
$titleId = 'portfolio-title-' . $i;
$yearId = 'portfolio-year-' . $i;
$kindId = 'portfolio-kind-' . $i;
$descId = 'portfolio-desc-' . $i;
$urlId = 'portfolio-url-' . $i;
?>
<div class="repeat-card portfolio-card" data-repeat-row>
  <input type="hidden" name="portfolio[<?= e((string) $i) ?>][id]" value="<?= e((string) ($item['id'] ?? '')) ?>">
  <input type="hidden" name="portfolio[<?= e((string) $i) ?>][image_path]" value="<?= e((string) ($item['image_path'] ?? '')) ?>">
  <div class="portfolio-card-grid">
    <div class="portfolio-card-fields">
      <div>
        <label class="field" for="<?= e($titleId) ?>">Titre</label>
        <input class="input" id="<?= e($titleId) ?>" name="portfolio[<?= e((string) $i) ?>][title]" value="<?= e((string) ($item['title'] ?? '')) ?>" placeholder="Couverture, extrait, planche…">
      </div>
      <div class="form-grid-2">
        <div>
          <label class="field" for="<?= e($yearId) ?>">Année</label>
          <input class="input" id="<?= e($yearId) ?>" name="portfolio[<?= e((string) $i) ?>][year]" value="<?= e((string) ($item['year'] ?? '')) ?>" placeholder="2024">
        </div>
        <div>
          <label class="field" for="<?= e($kindId) ?>">Type</label>
          <select class="input" id="<?= e($kindId) ?>" name="portfolio[<?= e((string) $i) ?>][kind]">
            <?php foreach ($portfolioKinds as $value => $label): ?>
              <option value="<?= e($value) ?>"<?= (($item['kind'] ?? 'creation') === $value) ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div>
        <label class="field" for="<?= e($descId) ?>">Description</label>
        <textarea class="textarea" id="<?= e($descId) ?>" name="portfolio[<?= e((string) $i) ?>][description]" rows="4" placeholder="Ce que vous avez fait, pour qui, dans quelles contraintes."><?= e((string) ($item['description'] ?? '')) ?></textarea>
      </div>
    </div>
    <div class="portfolio-card-media">
      <span class="field" id="portfolio-visual-<?= e((string) $i) ?>">Visuel</span>
      <div class="portfolio-preview" data-portfolio-preview<?= $previewSrc !== '' ? ' style="background-image:url(\'' . e($previewSrc) . '\')"' : ' hidden' ?>></div>
      <?php
        $filePickName = 'portfolio_file[' . $i . ']';
        $filePickAccept = 'image/jpeg,image/png,image/webp,image/gif';
        $filePickButton = $hasRealImage ? 'Remplacer' : 'Choisir un visuel';
        $filePickEmpty = $hasRealImage ? 'ou déposez un autre fichier' : null;
        $filePickDrop = true;
        $filePickAttrs = 'aria-labelledby="portfolio-visual-' . e((string) $i) . '"';
        require ADL_ROOT . '/app/Views/partials/file-pick.php';
      ?>
      <p class="field-help" data-portfolio-file-error hidden>Ce format n’est pas accepté. Envoyez un JPG, PNG, WebP ou GIF.</p>
      <div>
        <label class="field" for="<?= e($urlId) ?>">Ou un lien</label>
        <input class="input" id="<?= e($urlId) ?>" name="portfolio[<?= e((string) $i) ?>][image_url]" value="<?= e((string) ($item['image_url'] ?? '')) ?>" placeholder="https://" inputmode="url">
      </div>
      <p class="field-help">JPG, PNG, WebP ou GIF — 5 Mo max.</p>
    </div>
  </div>
  <button type="button" class="text-btn" data-repeat-remove>Retirer cette pièce</button>
</div>
<?php
unset($titleId, $yearId, $kindId, $descId, $urlId, $hasRealImage, $previewSrc);
?>
