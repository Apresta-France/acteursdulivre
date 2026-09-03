<?php
$p = $page ?? [];
$work = $work ?? null;
$old = is_array($old ?? null) ? $old : [];
$kinds = $kinds ?? \Adl\Models\AuthorWork::KINDS;
$roles = $roles ?? \Adl\Models\AuthorWork::ROLES;
$statuses = $statuses ?? \Adl\Models\AuthorWork::STATUSES;
$formats = $formats ?? \Adl\Models\AuthorWork::FORMATS;
$maxImages = (int) ($maxImages ?? \Adl\Models\AuthorWork::MAX_IMAGES);
$isEdit = is_array($work);
$action = $isEdit ? '/espace/auteur/oeuvres/' . (int) $work['id'] : '/espace/auteur/oeuvres/creer';

$v = static function (string $key, string $default = '') use ($work, $old): string {
    if (array_key_exists($key, $old) && is_scalar($old[$key])) {
        return (string) $old[$key];
    }
    return (string) ($work[$key] ?? $default);
};
$selectedFormats = isset($old['formats']) && is_array($old['formats']) ? $old['formats'] : ($work['formats'] ?? []);
$featured = isset($old['featured']) ? !empty($old['featured']) : !empty($work['featured']);
$existing = $isEdit ? array_map(null, $work['image_paths'], $work['images']) : [];
$remaining = max(0, $maxImages - count($existing));
?>
<div class="espace-page auteur-page">
  <div class="espace-page-head">
    <div>
      <h1><?= $isEdit ? 'Modifier une œuvre' : 'Ajouter une œuvre' ?></h1>
      <p><?= $isEdit ? e((string) $work['title']) : 'Toutes les informations qu\'on attend d\'un livre : titre, éditeur, année, ISBN, résumé, extrait, visuels et lien d\'achat.' ?></p>
    </div>
    <div class="vitrine-head-actions">
      <a class="btn-ghost" href="<?= e(url('/espace/auteur/oeuvres')) ?>">Retour aux œuvres</a>
      <button class="btn-orange" type="submit" form="oeuvre-form">Enregistrer</button>
    </div>
  </div>

  <form id="oeuvre-form" class="vitrine-form" method="post" action="<?= e(url($action)) ?>" enctype="multipart/form-data" data-work-form data-max-images="<?= $maxImages ?>">
    <?= csrf_field() ?>

    <div class="espace-panel">
      <h2 class="espace-group-title">L'ouvrage</h2>
      <div class="form-grid-2">
        <div>
          <label class="field" for="work-title">Titre</label>
          <input class="input" id="work-title" name="title" value="<?= e($v('title')) ?>" required maxlength="190" placeholder="Le titre tel qu'il figure sur la couverture">
        </div>
        <div>
          <label class="field" for="work-subtitle">Sous-titre (optionnel)</label>
          <input class="input" id="work-subtitle" name="subtitle" value="<?= e($v('subtitle')) ?>" maxlength="190">
        </div>
      </div>
      <div class="form-grid-3">
        <div>
          <label class="field" for="work-kind">Type d'ouvrage</label>
          <select class="input" id="work-kind" name="kind">
            <?php foreach ($kinds as $value => $label): ?>
              <option value="<?= e($value) ?>"<?= $v('kind', 'roman') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="field" for="work-role">Votre rôle</label>
          <select class="input" id="work-role" name="role">
            <?php foreach ($roles as $value => $label): ?>
              <option value="<?= e($value) ?>"<?= $v('role', 'auteur') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="field" for="work-status">Disponibilité</label>
          <select class="input" id="work-status" name="status">
            <?php foreach ($statuses as $value => $label): ?>
              <option value="<?= e($value) ?>"<?= $v('status', 'published') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <label class="check-row">
        <input type="checkbox" name="featured" value="1"<?= $featured ? ' checked' : '' ?>>
        Mettre cette œuvre en avant (affichée en tête de la fiche, avec un visuel plus grand)
      </label>
    </div>

    <div class="espace-panel">
      <h2 class="espace-group-title">Édition</h2>
      <div class="form-grid-3">
        <div>
          <label class="field" for="work-publisher">Éditeur</label>
          <input class="input" id="work-publisher" name="publisher" value="<?= e($v('publisher')) ?>" maxlength="190" placeholder="Maison d'édition ou « Auto-édition »">
        </div>
        <div>
          <label class="field" for="work-collection">Collection (optionnel)</label>
          <input class="input" id="work-collection" name="collection" value="<?= e($v('collection')) ?>" maxlength="190">
        </div>
        <div>
          <label class="field" for="work-year">Parution</label>
          <input class="input" id="work-year" name="year" value="<?= e($v('year')) ?>" maxlength="7" placeholder="2024 ou 2024-09" pattern="\d{4}(-\d{2})?">
        </div>
      </div>
      <div class="form-grid-3">
        <div>
          <label class="field" for="work-isbn">ISBN</label>
          <input class="input" id="work-isbn" name="isbn" value="<?= e($v('isbn')) ?>" maxlength="32" placeholder="978-2-…" inputmode="numeric">
        </div>
        <div>
          <label class="field" for="work-pages">Nombre de pages</label>
          <input class="input" id="work-pages" name="pages" type="number" min="1" max="60000" value="<?= e($v('pages') !== '0' ? $v('pages') : '') ?>">
        </div>
        <div>
          <label class="field" for="work-language">Langue</label>
          <input class="input" id="work-language" name="language" value="<?= e($v('language')) ?>" maxlength="60" placeholder="Français">
        </div>
      </div>
      <div class="form-grid-2">
        <div>
          <span class="field">Formats disponibles</span>
          <div class="chip-row">
            <?php foreach ($formats as $value => $label): ?>
              <label class="chip<?= in_array($value, $selectedFormats, true) ? ' is-on' : '' ?>">
                <input type="checkbox" name="formats[]" value="<?= e($value) ?>"<?= in_array($value, $selectedFormats, true) ? ' checked' : '' ?>>
                <?= e($label) ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
        <div>
          <label class="field" for="work-price">Prix public (optionnel)</label>
          <input class="input" id="work-price" name="price" value="<?= e($v('price')) ?>" maxlength="40" placeholder="19,90 €">
        </div>
      </div>
    </div>

    <div class="espace-panel">
      <h2 class="espace-group-title">Contenu</h2>
      <div>
        <label class="field" for="work-summary">Résumé / quatrième de couverture</label>
        <textarea class="textarea" id="work-summary" name="summary" rows="8" placeholder="Le texte de quatrième de couverture, ou votre propre résumé. Les sauts de ligne sont conservés."><?= e($v('summary')) ?></textarea>
      </div>
      <div>
        <label class="field" for="work-excerpt">Extrait (optionnel)</label>
        <textarea class="textarea" id="work-excerpt" name="excerpt" rows="6" placeholder="Les premières lignes, un passage que vous aimez faire lire."><?= e($v('excerpt')) ?></textarea>
        <p class="field-help">Affiché en retrait sur la fiche, comme une citation.</p>
      </div>
    </div>

    <div class="espace-panel">
      <h2 class="espace-group-title">Visuels</h2>
      <p class="field-help">Jusqu'à <?= $maxImages ?> images : couverture, quatrième, planche intérieure ou photo de l'objet. La première sert de couverture. JPG, PNG ou WebP, 5 Mo maximum chacune.</p>
      <div class="service-gallery auteur-gallery" data-work-gallery<?= $existing === [] ? ' hidden' : '' ?>>
        <?php foreach ($existing as [$path, $url]): ?>
          <figure class="service-gallery-thumb" data-keep-image>
            <input type="hidden" name="keep_images[]" value="<?= e((string) $path) ?>">
            <div class="service-gallery-media" style="background-image:url('<?= e((string) $url) ?>')"></div>
            <button type="button" class="service-gallery-remove" data-work-remove aria-label="Retirer ce visuel">✕</button>
          </figure>
        <?php endforeach; ?>
      </div>
      <span class="field" id="work-images-label">Ajouter des visuels</span>
      <?php
        $filePickId = 'work-images';
        $filePickName = 'images[]';
        $filePickAccept = 'image/jpeg,image/png,image/webp';
        $filePickMultiple = true;
        $filePickButton = 'Choisir des images';
        $filePickEmpty = $remaining > 0 ? 'ou déposez-les ici (' . $remaining . ' emplacement' . ($remaining > 1 ? 's' : '') . ' libre' . ($remaining > 1 ? 's' : '') . ')' : 'Retirez un visuel pour en ajouter un autre';
        $filePickDrop = true;
        $filePickAttrs = 'data-work-files aria-labelledby="work-images-label"' . ($remaining < 1 ? ' disabled' : '');
        require ADL_ROOT . '/app/Views/partials/file-pick.php';
      ?>
      <div>
        <label class="field" for="work-image-url">Ou l'adresse d'un visuel en ligne (optionnel)</label>
        <input class="input" id="work-image-url" name="image_url" value="<?= e((string) ($old['image_url'] ?? '')) ?>" placeholder="https://…/couverture.jpg" inputmode="url">
        <p class="field-help">Pratique si la couverture est déjà hébergée chez votre éditeur.</p>
      </div>
    </div>

    <div class="espace-panel">
      <h2 class="espace-group-title">Liens</h2>
      <div class="form-grid-2">
        <div>
          <label class="field" for="work-buy">Lien d'achat</label>
          <input class="input" id="work-buy" name="buy_url" value="<?= e($v('buy_url')) ?>" placeholder="https://" inputmode="url">
          <p class="field-help">Librairie indépendante, site de l'éditeur, plateforme… Le bouton « Acheter » de la fiche pointe ici.</p>
        </div>
        <div>
          <label class="field" for="work-more">Autre lien (optionnel)</label>
          <input class="input" id="work-more" name="more_url" value="<?= e($v('more_url')) ?>" placeholder="https://" inputmode="url">
          <p class="field-help">Page de l'éditeur, chronique, extrait audio, bande-annonce.</p>
        </div>
      </div>
    </div>

    <div class="auth-actions" style="margin-top: 28px;">
      <button class="btn-orange" type="submit"><?= $isEdit ? 'Enregistrer les modifications' : 'Ajouter cette œuvre' ?></button>
      <a class="btn-ghost" href="<?= e(url('/espace/auteur/oeuvres')) ?>">Annuler</a>
    </div>
  </form>
</div>
