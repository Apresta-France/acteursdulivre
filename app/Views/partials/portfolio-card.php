<?php
$i = $portfolioIndex ?? 0;
$item = is_array($item ?? null) ? $item : [];
$portfolioMediaTypes = $portfolioMediaTypes ?? \Adl\Models\Profile::PORTFOLIO_MEDIA_TYPES;
$suggestedMedia = $suggestedMedia ?? ['image'];
$mediaType = \Adl\Models\PortfolioItem::normalizeMedia((string) ($item['media_type'] ?? ($defaultPortfolioMedia ?? 'image')));
$hasRealImage = $mediaType === 'image' && (trim((string) ($item['image_path'] ?? '')) !== '' || trim((string) ($item['image_url'] ?? '')) !== '');
$hasFile = trim((string) ($item['image_path'] ?? '')) !== '';
$previewSrc = $hasRealImage ? (string) ($item['image'] ?? '') : '';
$fileName = (string) ($item['file_name'] ?? '');
$titleId = 'portfolio-title-' . $i;
$yearId = 'portfolio-year-' . $i;
$kindId = 'portfolio-kind-' . $i;
$mediaId = 'portfolio-media-' . $i;
$descId = 'portfolio-desc-' . $i;
$excerptId = 'portfolio-excerpt-' . $i;
$urlId = 'portfolio-url-' . $i;
$maxLabel = format_bytes(\Adl\Models\PortfolioItem::MAX_BYTES);
$helpByMedia = [
    'image' => 'JPG, PNG, WebP ou GIF — ' . $maxLabel . ' max.',
    'pdf' => 'PDF — ' . $maxLabel . ' max.',
    'audio' => 'MP3, WAV, M4A, OGG ou AAC — ' . $maxLabel . ' max.',
    'text' => 'Quelques lignes ou un passage. Pas de fichier, 6 000 caractères max.',
];
$acceptByMedia = [
    'image' => 'image/jpeg,image/png,image/webp,image/gif',
    'pdf' => 'application/pdf,.pdf',
    'audio' => 'audio/mpeg,audio/wav,audio/mp4,audio/ogg,audio/aac,.mp3,.wav,.m4a,.ogg,.aac',
    'text' => '',
];
$filePickLabel = match ($mediaType) {
    'pdf' => $hasFile ? 'Remplacer le PDF' : 'Choisir un PDF',
    'audio' => $hasFile ? 'Remplacer le son' : 'Choisir un extrait sonore',
    default => $hasRealImage ? 'Remplacer' : 'Choisir un visuel',
};
?>
<div class="repeat-card portfolio-card" data-repeat-row data-portfolio-card data-media-type="<?= e($mediaType) ?>">
  <input type="hidden" name="portfolio[<?= e((string) $i) ?>][id]" value="<?= e((string) ($item['id'] ?? '')) ?>">
  <input type="hidden" name="portfolio[<?= e((string) $i) ?>][image_path]" value="<?= e((string) ($item['image_path'] ?? '')) ?>">
  <div class="portfolio-card-grid">
    <div class="portfolio-card-fields">
      <div>
        <label class="field" for="<?= e($mediaId) ?>">Type de démo</label>
        <select class="input" id="<?= e($mediaId) ?>" name="portfolio[<?= e((string) $i) ?>][media_type]" data-portfolio-media>
          <?php foreach ($portfolioMediaTypes as $value => $label): ?>
            <?php
              $optionLabel = $label;
              if (in_array($value, $suggestedMedia, true) && count($suggestedMedia) < 4) {
                  $optionLabel .= ' — conseillé';
              }
            ?>
            <option value="<?= e($value) ?>"<?= $mediaType === $value ? ' selected' : '' ?>><?= e($optionLabel) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
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
        <textarea class="textarea" id="<?= e($descId) ?>" name="portfolio[<?= e((string) $i) ?>][description]" rows="3" placeholder="Ce que vous avez fait, pour qui, dans quelles contraintes."><?= e((string) ($item['description'] ?? '')) ?></textarea>
      </div>
      <div data-portfolio-panel="text"<?= $mediaType === 'text' ? '' : ' hidden' ?>>
        <label class="field" for="<?= e($excerptId) ?>">Extrait de texte</label>
        <textarea class="textarea" id="<?= e($excerptId) ?>" name="portfolio[<?= e((string) $i) ?>][text_excerpt]" rows="6" placeholder="Quelques lignes que vous acceptez de montrer en démo." maxlength="6000"><?= e((string) ($item['text_excerpt'] ?? '')) ?></textarea>
      </div>
    </div>
    <div class="portfolio-card-media" data-portfolio-panel="file"<?= $mediaType === 'text' ? ' hidden' : '' ?>>
      <span class="field" id="portfolio-visual-<?= e((string) $i) ?>" data-portfolio-file-label><?= $mediaType === 'pdf' ? 'Fichier PDF' : ($mediaType === 'audio' ? 'Fichier sonore' : 'Visuel') ?></span>
      <div class="portfolio-preview" data-portfolio-preview<?= $previewSrc !== '' ? ' style="background-image:url(\'' . e($previewSrc) . '\')"' : ' hidden' ?>></div>
      <?php if ($fileName !== '' && $mediaType !== 'image'): ?>
        <p class="portfolio-file-name" data-portfolio-filename><?= e($fileName) ?></p>
      <?php else: ?>
        <p class="portfolio-file-name" data-portfolio-filename hidden></p>
      <?php endif; ?>
      <?php
        $filePickName = 'portfolio_file[' . $i . ']';
        $filePickAccept = $acceptByMedia[$mediaType] ?? $acceptByMedia['image'];
        $filePickButton = $filePickLabel;
        $filePickEmpty = $hasFile ? 'ou déposez un autre fichier' : null;
        $filePickDrop = true;
        $filePickAttrs = 'aria-labelledby="portfolio-visual-' . e((string) $i) . '" data-max-bytes="' . (int) \Adl\Models\PortfolioItem::MAX_BYTES . '"';
        require ADL_ROOT . '/app/Views/partials/file-pick.php';
      ?>
      <p class="field-help" data-portfolio-file-error hidden>Ce format n’est pas accepté.</p>
      <div data-portfolio-panel="image-url"<?= $mediaType === 'image' ? '' : ' hidden' ?>>
        <label class="field" for="<?= e($urlId) ?>">Ou un lien</label>
        <input class="input" id="<?= e($urlId) ?>" name="portfolio[<?= e((string) $i) ?>][image_url]" value="<?= e((string) ($item['image_url'] ?? '')) ?>" placeholder="https://" inputmode="url">
      </div>
      <p class="field-help" data-portfolio-help><?= e($helpByMedia[$mediaType] ?? $helpByMedia['image']) ?></p>
    </div>
  </div>
  <button type="button" class="text-btn" data-repeat-remove>Retirer cette pièce</button>
</div>
<?php
unset($titleId, $yearId, $kindId, $mediaId, $descId, $excerptId, $urlId, $hasRealImage, $previewSrc, $fileName, $helpByMedia, $acceptByMedia, $filePickLabel, $maxLabel);
?>
