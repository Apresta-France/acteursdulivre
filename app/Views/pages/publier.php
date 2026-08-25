<?php
$old = is_array($old ?? null) ? $old : [];
$selected = (string) ($old['category_name'] ?? 'Correction');
$trades = $trades ?? \Adl\Data\Catalog::trades();
?>
<div class="espace-page publish-page">
  <div class="espace-page-head">
    <div>
      <h1>Décrivez votre mission</h1>
      <p>Plus le brief est précis, plus les devis sont justes. Trois minutes suffisent.</p>
    </div>
  </div>

  <?php if (!empty($error)): ?>
    <div class="flash flash-error"><?= e((string) $error) ?></div>
  <?php endif; ?>

  <div class="publish-grid">
    <form class="param-form publish-form" method="post" action="<?= e(url('/espace/publier')) ?>" enctype="multipart/form-data" data-publish-form>
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
        <label class="field" for="mission-title">Titre de la mission</label>
        <input class="input" id="mission-title" name="title" required maxlength="255"
               value="<?= e((string) ($old['title'] ?? '')) ?>"
               placeholder="Recherche correcteur pour essai historique, 240 pages"
               data-preview-title>
      </div>

      <div>
        <label class="field" for="mission-brief">Brief</label>
        <textarea class="textarea" id="mission-brief" name="brief" required rows="6"
                  placeholder="Genre, volume en signes, état du texte, attentes, contraintes de calendrier…"
                  data-preview-brief><?= e((string) ($old['brief'] ?? '')) ?></textarea>
      </div>

      <div class="form-grid-3">
        <div>
          <label class="field" for="mission-volume">Volume</label>
          <input class="input" id="mission-volume" name="volume" value="<?= e((string) ($old['volume'] ?? '')) ?>" placeholder="420 000 signes">
        </div>
        <div>
          <label class="field" for="mission-min">Budget min. (€)</label>
          <input class="input" id="mission-min" name="budget_min" inputmode="numeric" value="<?= e((string) ($old['budget_min'] ?? '')) ?>" placeholder="600" data-preview-min>
        </div>
        <div>
          <label class="field" for="mission-max">Budget max. (€)</label>
          <input class="input" id="mission-max" name="budget_max" inputmode="numeric" value="<?= e((string) ($old['budget_max'] ?? '')) ?>" placeholder="900" data-preview-max>
        </div>
      </div>

      <div>
        <label class="field" for="mission-deadline">Échéance</label>
        <input class="input" id="mission-deadline" type="date" name="deadline" value="<?= e((string) ($old['deadline'] ?? '')) ?>">
      </div>

      <div>
        <label class="field" for="mission-file">Pièce jointe (optionnel)</label>
        <input class="input" id="mission-file" type="file" name="attachment" accept=".pdf,.doc,.docx,.odt,.txt">
        <p class="field-help">Extrait, sommaire ou cahier des charges — PDF, DOCX, ODT, 20 Mo max.</p>
      </div>

      <div class="auth-actions publish-actions">
        <button class="btn-orange" type="submit" name="intent" value="publish">Publier la mission</button>
        <button class="btn-ghost" type="submit" name="intent" value="draft">Enregistrer le brouillon</button>
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
        <p>Votre mission est visible par les prestataires du métier choisi. Vous recevez en moyenne trois devis en 48 heures. Le paiement n'intervient qu'après accord — il n'est pas demandé ici.</p>
      </div>
    </aside>
  </div>
</div>
