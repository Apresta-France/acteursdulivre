<?php
$old = is_array($old ?? null) ? $old : [];
$editing = !empty($editing);
$missionId = (int) ($missionId ?? 0);
$missionStatus = (string) ($missionStatus ?? '');
$existingAttachment = trim((string) ($existingAttachment ?? ''));
$selected = (string) ($old['category_name'] ?? 'Correction');
$trades = $trades ?? \Adl\Data\Catalog::trades();
$hint = \Adl\Data\Catalog::volumeHint($selected) ?? [];
$briefHint = \Adl\Data\Catalog::briefHint($selected);
$formAction = $editing && $missionId > 0 ? '/espace/publier/' . $missionId : '/espace/publier';
?>
<div class="espace-page publish-page">
  <div class="espace-page-head">
    <div>
      <h1><?= $editing ? 'Modifier la recherche' : 'Décrivez votre recherche' ?></h1>
      <p>Plus le brief est précis, plus les devis sont justes. Trois minutes suffisent.</p>
    </div>
  </div>

  <?php if (!empty($error)): ?>
    <div class="flash flash-error"><?= e((string) $error) ?></div>
  <?php endif; ?>

  <div class="publish-grid">
    <form class="param-form publish-form" method="post" action="<?= e(url($formAction)) ?>" enctype="multipart/form-data" data-publish-form
          data-volume-hints="<?= e(json_encode(\Adl\Data\Catalog::VOLUME_HINTS, JSON_UNESCAPED_UNICODE)) ?>"
          data-brief-hints="<?= e(json_encode(\Adl\Data\Catalog::BRIEF_HINTS, JSON_UNESCAPED_UNICODE)) ?>">
      <?= csrf_field() ?>

      <div>
        <span class="field">Métier recherché</span>
        <div class="chip-row">
          <?php foreach ($trades as $trade): ?>
            <label class="chip<?= $selected === $trade ? ' is-on' : '' ?>">
              <input type="radio" name="category_name" value="<?= e($trade) ?>"<?= $selected === $trade ? ' checked' : '' ?>>
              <?= e($trade) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div>
        <label class="field" for="search-title">Titre de la recherche</label>
        <input class="input" id="search-title" name="title" required maxlength="255"
               value="<?= e((string) ($old['title'] ?? '')) ?>"
               placeholder="Recherche correcteur pour essai historique, 240 pages"
               data-preview-title>
      </div>

      <div>
        <label class="field" for="search-brief">Brief</label>
        <textarea class="textarea" id="search-brief" name="brief" required rows="6"
                  placeholder="<?= e($briefHint) ?>"
                  data-preview-brief><?= e((string) ($old['brief'] ?? '')) ?></textarea>
      </div>

      <div class="form-grid-3<?= $hint ? '' : ' is-two' ?>" data-publish-metrics>
        <div data-volume-wrap<?= $hint ? '' : ' hidden' ?>>
          <label class="field" for="search-volume" data-volume-label><?= e($hint['label'] ?? 'Volume') ?></label>
          <input class="input" id="search-volume" name="volume" data-volume-input
                 value="<?= e((string) ($old['volume'] ?? '')) ?>"
                 placeholder="<?= e($hint['placeholder'] ?? '') ?>"
                 <?= $hint ? '' : ' disabled' ?>>
        </div>
        <div>
          <label class="field" for="search-min">Budget min. (€)</label>
          <input class="input" id="search-min" name="budget_min" inputmode="numeric" value="<?= e((string) ($old['budget_min'] ?? '')) ?>" placeholder="600" data-preview-min>
        </div>
        <div>
          <label class="field" for="search-max">Budget max. (€)</label>
          <input class="input" id="search-max" name="budget_max" inputmode="numeric" value="<?= e((string) ($old['budget_max'] ?? '')) ?>" placeholder="900" data-preview-max>
        </div>
      </div>

      <div>
        <label class="field" for="search-deadline">Échéance</label>
        <input class="input" id="search-deadline" type="date" name="deadline" value="<?= e((string) ($old['deadline'] ?? '')) ?>">
      </div>

      <div>
        <span class="field" id="search-file-label">Pièce jointe (optionnel)</span>
        <?php
          $filePickId = 'search-file';
          $filePickName = 'attachment';
          $filePickAccept = '.pdf,.doc,.docx,.odt,.txt';
          $filePickButton = 'Joindre un fichier';
          $filePickDrop = true;
          $filePickAttrs = 'aria-labelledby="search-file-label"';
          require ADL_ROOT . '/app/Views/partials/file-pick.php';
        ?>
        <?php if ($existingAttachment !== ''): ?>
          <p class="field-help">Fichier actuel : <?= e($existingAttachment) ?>. Joindre un autre fichier le remplace.</p>
          <label class="check-row">
            <input type="checkbox" name="remove_attachment" value="1">
            Retirer la pièce jointe
          </label>
        <?php endif; ?>
        <p class="field-help">Extrait, sommaire ou cahier des charges — PDF, DOCX, ODT, 20 Mo max.</p>
      </div>

      <div class="auth-actions publish-actions">
        <?php if ($editing && $missionStatus === 'open'): ?>
          <button class="btn-orange" type="submit" name="intent" value="publish">Enregistrer les modifications</button>
        <?php else: ?>
          <button class="btn-orange" type="submit" name="intent" value="publish">Publier la recherche</button>
          <button class="btn-ghost" type="submit" name="intent" value="draft">Enregistrer le brouillon</button>
        <?php endif; ?>
      </div>
    </form>

    <aside class="publish-side">
      <div class="side-card">
        <div class="side-kicker">Aperçu de l'annonce</div>
        <div class="side-title" data-preview-out-title>Votre titre apparaîtra ici</div>
        <div class="side-sub"><span><?= e((string) ($publisherName ?? 'Vous')) ?></span> · <span data-preview-out-cat><?= e($selected) ?></span></div>
        <p class="side-brief" data-preview-out-brief>Le brief s'affiche au fil de la saisie.</p>
        <div class="side-foot">
          <span>0 candidature</span>
          <strong data-preview-out-budget>Budget à convenir</strong>
        </div>
      </div>
      <div class="side-card">
        <div class="side-title-sm">Prestataires qui correspondent</div>
        <?php foreach (($suggestions ?? []) as $p): ?>
          <a class="suggest-row" href="<?= e(url((string) $p['href'])) ?>">
            <span class="avatar" style="<?= e(avatar_style((string) $p['initials'], 34)) ?>"><?= e((string) $p['initials']) ?></span>
            <span>
              <strong><?= e((string) $p['title']) ?></strong>
              <em><?= e((string) $p['subtitle']) ?></em>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
      <div class="side-card side-card-warm">
        <div class="side-title-sm">Ce qui se passe ensuite</div>
        <p>Votre recherche est visible par les prestataires du métier choisi. Vous recevez en moyenne trois devis en 48 heures. Quand vous retenez une proposition, un suivi à jalons s’ouvre : le règlement se fait entre vous, hors plateforme.</p>
      </div>
    </aside>
  </div>
</div>
