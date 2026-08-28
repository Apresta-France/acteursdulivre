<?php
$p = $profile ?? [];
$skills = !empty($p['skills']) ? $p['skills'] : [['label' => '', 'niveau' => 'Confirmée']];
$experiences = !empty($p['experiences']) ? $p['experiences'] : [['periode' => '', 'poste' => '', 'lieu' => '', 'detail' => '']];
$education = !empty($p['education']) ? $p['education'] : [['annee' => '', 'intitule' => '', 'ecole' => '']];
$languagesList = !empty($p['languages_list']) ? $p['languages_list'] : [['langue' => '', 'niveau' => 'Langue de travail']];
$portfolio = $p['portfolio'] ?? [];
if ($portfolio === []) {
    $portfolio = [['id' => '', 'title' => '', 'description' => '', 'year' => '', 'kind' => 'creation', 'image_path' => '', 'image_url' => '']];
}
$selectedTrades = $p['trades'] ?? [];
$selectedGenres = $p['genres'] ?? [];
$trades = $trades ?? \Adl\Data\Catalog::trades();
$genres = $genres ?? \Adl\Data\Catalog::specialties();
$tools = implode(', ', $p['tools'] ?? []);
$completion = (int) ($completion ?? ($p['completion'] ?? 0));
$publicHref = !empty($p['slug']) ? url('/prestataires/' . $p['slug']) : '';
$rateKind = \Adl\Models\Profile::isPercentRate($p) || (\Adl\Models\Profile::isBookstore($p) && trim((string) ($p['hourly_rate'] ?? '')) === '')
    ? 'percent'
    : 'price';
$rateValue = (string) ($p['hourly_rate'] ?? '');
if ($rateKind === 'percent') {
    $rateValue = trim(str_replace('%', '', $rateValue));
}
?>
<div class="espace-page vitrine-page">
  <div class="espace-page-head">
    <div>
      <h1>Ma vitrine</h1>
      <p>Profil complété à <?= $completion ?> %. Les vitrines précises reçoivent nettement plus de demandes.<?php if (!empty($p['is_founder'])): ?> <span class="profile-badge profile-badge-founder">Membre fondateur</span><?php endif; ?></p>
    </div>
    <div class="vitrine-head-actions">
      <?php if ($publicHref): ?>
        <a class="btn-ghost" href="<?= e($publicHref) ?>">Voir en public</a>
      <?php endif; ?>
      <button class="btn-orange" type="submit" form="vitrine-form">Enregistrer</button>
    </div>
  </div>

  <div class="vitrine-progress"><span style="width: <?= max(6, $completion) ?>%"></span></div>

  <?php if (!empty($saved)): ?>
    <div class="flash flash-ok">Votre vitrine a été enregistrée.</div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div class="flash flash-error"><?= e((string) $error) ?></div>
  <?php endif; ?>

  <div class="tab-row" data-tabs>
    <button type="button" class="tab is-on" data-tab="identite">Identité</button>
    <button type="button" class="tab" data-tab="competences">Compétences</button>
    <button type="button" class="tab" data-tab="parcours">Parcours</button>
    <button type="button" class="tab" data-tab="portfolio">Créations &amp; exemples</button>
  </div>

  <form id="vitrine-form" class="vitrine-form" method="post" action="<?= e(url('/espace/vitrine')) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div data-tab-panel="identite">
      <?php
        $avatarSrc = (string) ($profile['avatar_src'] ?? $userAvatarUrl ?? '');
        $initials = (string) ($userInitials ?? \Adl\Models\Profile::initials($p));
        $inputId = 'vitrine-avatar';
        $help = 'JPG, PNG ou WebP, 2 Mo maximum. Visible sur votre fiche publique.';
        require ADL_ROOT . '/app/Views/partials/avatar-field.php';
      ?>
      <div class="form-grid-2">
        <div>
          <label class="field" for="first_name">Prénom</label>
          <input class="input" id="first_name" name="first_name" value="<?= e((string) ($prenom ?? '')) ?>" required>
        </div>
        <div>
          <label class="field" for="last_name">Nom</label>
          <input class="input" id="last_name" name="last_name" value="<?= e((string) ($nom ?? '')) ?>" required>
        </div>
      </div>
      <div>
        <label class="field" for="vitrine-title">Titre de la vitrine</label>
        <input class="input" id="vitrine-title" name="title" value="<?= e((string) ($p['title'] ?? '')) ?>" placeholder="Correctrice-relectrice, romans et essais">
      </div>
      <div>
        <label class="field" for="presentation">Présentation</label>
        <textarea class="textarea" id="presentation" name="presentation" rows="6" placeholder="Votre parcours, vos spécialités, votre façon de travailler. Évitez le jargon vide : un client doit comprendre ce que vous faites mieux que d'autres."><?= e((string) ($p['presentation'] ?? '')) ?></textarea>
      </div>
      <div>
        <span class="field" id="availability-status-label">Disponibilité</span>
        <div class="mode-switch" data-mode-switch role="group" aria-labelledby="availability-status-label">
          <?php $busy = !empty($p['is_busy']); ?>
          <label class="mode-option<?= !$busy ? ' is-on is-available' : '' ?>">
            <input type="radio" name="availability_status" value="available"<?= !$busy ? ' checked' : '' ?>>
            Disponible
          </label>
          <label class="mode-option<?= $busy ? ' is-on is-busy' : '' ?>">
            <input type="radio" name="availability_status" value="busy"<?= $busy ? ' checked' : '' ?>>
            Occupé
          </label>
        </div>
        <p class="field-help">Si votre planning est déjà plein, passez en Occupé : les porteurs de projet voient le statut sur votre vitrine et dans l'annuaire. Vous restez visible et joignable.</p>
      </div>
      <div class="form-grid-3">
        <div>
          <label class="field" for="city">Ville</label>
          <input class="input" id="city" name="city" value="<?= e((string) ($p['city'] ?? '')) ?>" placeholder="Nantes">
        </div>
        <div>
          <label class="field" for="availability">Précision (optionnel)</label>
          <input class="input" id="availability" name="availability" value="<?= e((string) ($p['availability'] ?? '')) ?>" placeholder="<?= $busy ? 'reprend le 15 octobre' : 'dès maintenant, sous 48 h' ?>">
        </div>
        <div>
          <label class="field" for="languages">Langues (résumé)</label>
          <input class="input" id="languages" name="languages" value="<?= e((string) ($p['languages'] ?? '')) ?>" placeholder="FR, EN">
        </div>
      </div>
      <div data-rate-fields data-bookstore-trade="<?= e(\Adl\Models\Profile::TRADE_BOOKSTORE) ?>">
        <span class="field">Mode de tarification</span>
        <p class="field-help">Les libraires indiquent une commission sur les ventes, pas un prix de prestation.</p>
        <div class="chip-row" style="margin: 10px 0 16px;">
          <label class="chip<?= $rateKind === 'price' ? ' is-on' : '' ?>">
            <input type="radio" name="rate_kind" value="price"<?= $rateKind === 'price' ? ' checked' : '' ?> data-rate-kind>
            Tarif (€)
          </label>
          <label class="chip<?= $rateKind === 'percent' ? ' is-on' : '' ?>">
            <input type="radio" name="rate_kind" value="percent"<?= $rateKind === 'percent' ? ' checked' : '' ?> data-rate-kind>
            Commission (%)
          </label>
        </div>
        <div class="form-grid-3">
          <div>
            <label class="field" for="hourly_rate" data-rate-label><?= $rateKind === 'percent' ? 'Commission' : 'Tarif' ?></label>
            <input class="input" id="hourly_rate" name="hourly_rate" value="<?= e($rateValue) ?>"
                   placeholder="<?= $rateKind === 'percent' ? '35' : '32 € / heure' ?>"
                   inputmode="<?= $rateKind === 'percent' ? 'decimal' : 'text' ?>"
                   data-rate-input
                   data-placeholder-price="32 € / heure"
                   data-placeholder-percent="35">
          </div>
          <div>
            <label class="field" for="rate_note" data-rate-note-label><?= $rateKind === 'percent' ? 'Précision' : 'Précision tarifaire' ?></label>
            <input class="input" id="rate_note" name="rate_note" value="<?= e((string) ($p['rate_note'] ?? '')) ?>"
                   placeholder="<?= $rateKind === 'percent' ? 'sur le prix public TTC' : 'ou 4,50 € / 1 000 signes' ?>"
                   data-rate-note
                   data-placeholder-price="ou 4,50 € / 1 000 signes"
                   data-placeholder-percent="sur le prix public TTC">
          </div>
          <div>
            <label class="field" for="website">Site ou portfolio externe</label>
            <input class="input" id="website" name="website" value="<?= e((string) ($p['website'] ?? '')) ?>" placeholder="https://">
          </div>
        </div>
      </div>
    </div>

    <div data-tab-panel="competences" hidden>
      <div>
        <span class="field">Mes métiers</span>
        <p class="field-help">Trois maximum : ce sont eux qui vous font apparaître dans l'annuaire.</p>
        <div class="chip-row" data-max-checks="3" data-trades>
          <?php foreach ($trades as $trade): ?>
            <label class="chip<?= in_array($trade, $selectedTrades, true) ? ' is-on' : '' ?>">
              <input type="checkbox" name="trades[]" value="<?= e($trade) ?>"<?= in_array($trade, $selectedTrades, true) ? ' checked' : '' ?>>
              <?= e($trade) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div>
        <span class="field">Compétences et niveau</span>
        <div class="repeat-list" data-repeat="skills">
          <?php foreach ($skills as $i => $skill): ?>
            <div class="repeat-row" data-repeat-row>
              <input class="input" name="skills[<?= $i ?>][label]" value="<?= e((string) ($skill['label'] ?? '')) ?>" placeholder="Correction orthotypographique">
              <select class="input" name="skills[<?= $i ?>][niveau]">
                <?php foreach ($skillLevels as $level): ?>
                  <option value="<?= e($level) ?>"<?= (($skill['niveau'] ?? '') === $level) ? ' selected' : '' ?>><?= e($level) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="button" class="icon-btn" data-repeat-remove aria-label="Retirer">✕</button>
            </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="btn-ghost" data-repeat-add="skills">Ajouter une compétence</button>
      </div>

      <div>
        <label class="field" for="tools">Logiciels et outils</label>
        <input class="input" id="tools" name="tools" value="<?= e($tools) ?>" placeholder="Antidote, InDesign, Word, Pro Tools…">
        <p class="field-help">Séparez les outils par une virgule.</p>
      </div>

      <div>
        <span class="field">Langues de travail</span>
        <div class="repeat-list" data-repeat="languages_list">
          <?php foreach ($languagesList as $i => $lang): ?>
            <div class="repeat-row" data-repeat-row>
              <input class="input" name="languages_list[<?= $i ?>][langue]" value="<?= e((string) ($lang['langue'] ?? '')) ?>" placeholder="Français">
              <select class="input" name="languages_list[<?= $i ?>][niveau]">
                <?php foreach ($langLevels as $level): ?>
                  <option value="<?= e($level) ?>"<?= (($lang['niveau'] ?? '') === $level) ? ' selected' : '' ?>><?= e($level) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="button" class="icon-btn" data-repeat-remove aria-label="Retirer">✕</button>
            </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="btn-ghost" data-repeat-add="languages_list">Ajouter une langue</button>
      </div>

      <div>
        <span class="field">Spécialités</span>
        <p class="field-help">Types de textes que vous travaillez. Choisissez Global si vous intervenez sur tous les genres.</p>
        <div class="chip-row">
          <?php foreach ($genres as $genre): ?>
            <label class="chip<?= in_array($genre, $selectedGenres, true) ? ' is-on' : '' ?>">
              <input type="checkbox" name="genres[]" value="<?= e($genre) ?>"<?= in_array($genre, $selectedGenres, true) ? ' checked' : '' ?>>
              <?= e($genre) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div data-tab-panel="parcours" hidden>
      <div>
        <span class="field">Expériences</span>
        <div class="repeat-list" data-repeat="experiences">
          <?php foreach ($experiences as $i => $exp): ?>
            <div class="repeat-card" data-repeat-row>
              <div class="form-grid-3">
                <input class="input" name="experiences[<?= $i ?>][periode]" value="<?= e((string) ($exp['periode'] ?? '')) ?>" placeholder="2018 – 2024">
                <input class="input" name="experiences[<?= $i ?>][poste]" value="<?= e((string) ($exp['poste'] ?? '')) ?>" placeholder="Correctrice indépendante">
                <input class="input" name="experiences[<?= $i ?>][lieu]" value="<?= e((string) ($exp['lieu'] ?? '')) ?>" placeholder="Nantes / à distance">
              </div>
              <textarea class="textarea" name="experiences[<?= $i ?>][detail]" rows="3" placeholder="Ce que vous y faisiez, pour qui, sur quels types d'ouvrages."><?= e((string) ($exp['detail'] ?? '')) ?></textarea>
              <button type="button" class="text-btn" data-repeat-remove>Retirer cette expérience</button>
            </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="btn-ghost" data-repeat-add="experiences">Ajouter une expérience</button>
      </div>

      <div>
        <span class="field">Formation et certifications</span>
        <div class="repeat-list" data-repeat="education">
          <?php foreach ($education as $i => $edu): ?>
            <div class="repeat-row" data-repeat-row>
              <input class="input" name="education[<?= $i ?>][annee]" value="<?= e((string) ($edu['annee'] ?? '')) ?>" placeholder="2012">
              <input class="input" name="education[<?= $i ?>][intitule]" value="<?= e((string) ($edu['intitule'] ?? '')) ?>" placeholder="Master lettres / certification">
              <input class="input" name="education[<?= $i ?>][ecole]" value="<?= e((string) ($edu['ecole'] ?? '')) ?>" placeholder="Établissement">
              <button type="button" class="icon-btn" data-repeat-remove aria-label="Retirer">✕</button>
            </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="btn-ghost" data-repeat-add="education">Ajouter une formation</button>
      </div>
    </div>

    <div data-tab-panel="portfolio" hidden>
      <div class="side-card side-card-warm" style="margin-bottom: 18px;">
        Ajoutez des créations déjà réalisées et des exemples : un titre, une année, une courte description, et un visuel (fichier ou lien). Chaque pièce doit être une réalisation humaine dont vous détenez les droits.
      </div>
      <div class="repeat-list portfolio-list" data-repeat="portfolio">
        <?php foreach ($portfolio as $i => $item): ?>
          <div class="repeat-card" data-repeat-row>
            <input type="hidden" name="portfolio[<?= $i ?>][id]" value="<?= e((string) ($item['id'] ?? '')) ?>">
            <input type="hidden" name="portfolio[<?= $i ?>][image_path]" value="<?= e((string) ($item['image_path'] ?? '')) ?>">
            <div class="form-grid-2">
              <input class="input" name="portfolio[<?= $i ?>][title]" value="<?= e((string) ($item['title'] ?? '')) ?>" placeholder="Titre de la création ou de l'exemple">
              <input class="input" name="portfolio[<?= $i ?>][year]" value="<?= e((string) ($item['year'] ?? '')) ?>" placeholder="2026">
            </div>
            <div class="form-grid-2">
              <select class="input" name="portfolio[<?= $i ?>][kind]">
                <?php foreach ($portfolioKinds as $value => $label): ?>
                  <option value="<?= e($value) ?>"<?= (($item['kind'] ?? 'creation') === $value) ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
              <input class="input" name="portfolio[<?= $i ?>][image_url]" value="<?= e((string) ($item['image_url'] ?? '')) ?>" placeholder="Lien vers un visuel (optionnel)">
            </div>
            <textarea class="textarea" name="portfolio[<?= $i ?>][description]" rows="3" placeholder="Ce que vous avez fait, pour qui, dans quelles contraintes."><?= e((string) ($item['description'] ?? '')) ?></textarea>
            <label class="field">Visuel (JPG, PNG, WebP — 5 Mo max)</label>
            <input class="input" type="file" name="portfolio_file[<?= $i ?>]" accept="image/jpeg,image/png,image/webp,image/gif">
            <?php if (!empty($item['image'])): ?>
              <div class="portfolio-preview" style="background-image:url('<?= e((string) $item['image']) ?>')"></div>
            <?php endif; ?>
            <button type="button" class="text-btn" data-repeat-remove>Retirer cette pièce</button>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="btn-ghost" data-repeat-add="portfolio">Ajouter une création ou un exemple</button>
    </div>

    <div class="auth-actions" style="margin-top: 28px;">
      <button class="btn-orange" type="submit">Enregistrer la vitrine</button>
    </div>
  </form>
</div>

<template id="tpl-skills">
  <div class="repeat-row" data-repeat-row>
    <input class="input" name="skills[__i__][label]" placeholder="Correction orthotypographique">
    <select class="input" name="skills[__i__][niveau]">
      <?php foreach ($skillLevels as $level): ?><option value="<?= e($level) ?>"><?= e($level) ?></option><?php endforeach; ?>
    </select>
    <button type="button" class="icon-btn" data-repeat-remove aria-label="Retirer">✕</button>
  </div>
</template>
<template id="tpl-languages_list">
  <div class="repeat-row" data-repeat-row>
    <input class="input" name="languages_list[__i__][langue]" placeholder="Français">
    <select class="input" name="languages_list[__i__][niveau]">
      <?php foreach ($langLevels as $level): ?><option value="<?= e($level) ?>"><?= e($level) ?></option><?php endforeach; ?>
    </select>
    <button type="button" class="icon-btn" data-repeat-remove aria-label="Retirer">✕</button>
  </div>
</template>
<template id="tpl-experiences">
  <div class="repeat-card" data-repeat-row>
    <div class="form-grid-3">
      <input class="input" name="experiences[__i__][periode]" placeholder="2018 – 2024">
      <input class="input" name="experiences[__i__][poste]" placeholder="Correctrice indépendante">
      <input class="input" name="experiences[__i__][lieu]" placeholder="Nantes / à distance">
    </div>
    <textarea class="textarea" name="experiences[__i__][detail]" rows="3" placeholder="Ce que vous y faisiez."></textarea>
    <button type="button" class="text-btn" data-repeat-remove>Retirer cette expérience</button>
  </div>
</template>
<template id="tpl-education">
  <div class="repeat-row" data-repeat-row>
    <input class="input" name="education[__i__][annee]" placeholder="2012">
    <input class="input" name="education[__i__][intitule]" placeholder="Master lettres / certification">
    <input class="input" name="education[__i__][ecole]" placeholder="Établissement">
    <button type="button" class="icon-btn" data-repeat-remove aria-label="Retirer">✕</button>
  </div>
</template>
<template id="tpl-portfolio">
  <div class="repeat-card" data-repeat-row>
    <input type="hidden" name="portfolio[__i__][id]" value="">
    <input type="hidden" name="portfolio[__i__][image_path]" value="">
    <div class="form-grid-2">
      <input class="input" name="portfolio[__i__][title]" placeholder="Titre de la création ou de l'exemple">
      <input class="input" name="portfolio[__i__][year]" placeholder="2026">
    </div>
    <div class="form-grid-2">
      <select class="input" name="portfolio[__i__][kind]">
        <?php foreach ($portfolioKinds as $value => $label): ?><option value="<?= e($value) ?>"><?= e($label) ?></option><?php endforeach; ?>
      </select>
      <input class="input" name="portfolio[__i__][image_url]" placeholder="Lien vers un visuel (optionnel)">
    </div>
    <textarea class="textarea" name="portfolio[__i__][description]" rows="3" placeholder="Ce que vous avez fait, pour qui."></textarea>
    <label class="field">Visuel (JPG, PNG, WebP — 5 Mo max)</label>
    <input class="input" type="file" name="portfolio_file[__i__]" accept="image/jpeg,image/png,image/webp,image/gif">
    <button type="button" class="text-btn" data-repeat-remove>Retirer cette pièce</button>
  </div>
</template>
