<?php
$p = $page ?? [];
$genres = $genres ?? \Adl\Models\AuthorPage::GENRES;
$openTo = $openTo ?? \Adl\Models\AuthorPage::OPEN_TO;
$linkKinds = $linkKinds ?? \Adl\Models\AuthorPage::LINK_KINDS;
$selectedGenres = $p['genres'] ?? [];
$selectedOpenTo = $p['open_to'] ?? [];
$press = !empty($p['press']) ? $p['press'] : [['title' => '', 'source' => '', 'date' => '', 'url' => '']];
$links = !empty($p['links']) ? $p['links'] : [['kind' => 'site', 'label' => '', 'url' => '']];
$awards = !empty($p['awards']) ? $p['awards'] : [['year' => '', 'label' => '', 'work' => '']];
$events = !empty($p['events']) ? $p['events'] : [['date' => '', 'label' => '', 'place' => '', 'url' => '']];
$auteurTab = 'fiche';
$auteurSubmitForm = 'auteur-form';
$bioLen = mb_strlen((string) ($p['bio'] ?? ''));
?>
<div class="espace-page auteur-page">
  <?php require ADL_ROOT . '/app/Views/partials/auteur-head.php'; ?>

  <form id="auteur-form" class="vitrine-form" method="post" action="<?= e(url('/espace/auteur')) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="espace-panel">
      <h2 class="espace-group-title">Identité d'auteur</h2>
      <?php
        $avatarSrc = (string) ($p['avatar_src'] ?? '');
        $initials = (string) ($p['initials'] ?? 'AD');
        $inputId = 'auteur-avatar';
        $help = 'Portrait utilisé sur la fiche auteur et partagé avec votre compte. JPG, PNG ou WebP, 2 Mo maximum.';
        require ADL_ROOT . '/app/Views/partials/avatar-field.php';
      ?>
      <div class="form-grid-2">
        <div>
          <label class="field" for="pen_name">Nom de plume (optionnel)</label>
          <input class="input" id="pen_name" name="pen_name" value="<?= e((string) ($p['pen_name'] ?? '')) ?>" placeholder="<?= e(trim((string) ($p['first_name'] ?? '') . ' ' . (string) ($p['last_name'] ?? ''))) ?>" maxlength="190">
          <p class="field-help">Laissez vide pour afficher <?= e(trim((string) ($p['first_name'] ?? '') . ' ' . (string) ($p['last_name'] ?? ''))) ?>. L'adresse publique suit ce nom jusqu'à la première publication de la fiche.</p>
        </div>
        <div>
          <label class="field" for="tagline">En une ligne</label>
          <input class="input" id="tagline" name="tagline" value="<?= e((string) ($p['tagline'] ?? '')) ?>" placeholder="Romancière, autrice jeunesse, poète…" maxlength="190">
          <p class="field-help">Affiché sous votre nom et dans l'annuaire des auteurs.</p>
        </div>
      </div>
      <div>
        <h3 class="espace-group-title">Genres et univers</h3>
        <p class="field-help">Huit au maximum. Ils aident les lecteurs, libraires et journalistes à vous situer.</p>
        <div class="chip-row" data-max-checks="8">
          <?php foreach ($genres as $genre): ?>
            <label class="chip<?= in_array($genre, $selectedGenres, true) ? ' is-on' : '' ?>">
              <input type="checkbox" name="genres[]" value="<?= e($genre) ?>"<?= in_array($genre, $selectedGenres, true) ? ' checked' : '' ?>>
              <?= e($genre) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="espace-panel">
      <h2 class="espace-group-title">Biographie</h2>
      <div>
        <label class="field" for="short_bio">Présentation courte</label>
        <textarea class="textarea" id="short_bio" name="short_bio" rows="3" maxlength="<?= \Adl\Models\AuthorPage::SHORT_BIO_MAX ?>" placeholder="Trois phrases qui donnent envie : qui vous êtes, ce que vous écrivez, un fait marquant."><?= e((string) ($p['short_bio'] ?? '')) ?></textarea>
        <p class="field-help">500 caractères maximum. Sert de résumé dans l'annuaire, sur les réseaux et pour les moteurs de recherche.</p>
      </div>
      <div>
        <label class="field" for="bio">Biographie complète</label>
        <textarea class="textarea" id="bio" name="bio" rows="12" placeholder="Votre parcours d'écriture, vos influences, vos publications marquantes, ce qui vous occupe en ce moment. Les sauts de ligne sont conservés."><?= e((string) ($p['bio'] ?? '')) ?></textarea>
        <p class="field-help"><?= $bioLen > 0 ? format_int($bioLen) . ' caractères. ' : '' ?>Une biographie d'au moins 200 caractères compte dans la complétion de la fiche.</p>
      </div>
    </div>

    <div class="espace-panel">
      <h2 class="espace-group-title">Presse et références</h2>
      <p class="field-help">Articles, chroniques, interviews, podcasts : un titre, la source, une date et le lien. Les lecteurs pourront les consulter depuis votre fiche.</p>
      <div class="repeat-list" data-repeat="press">
        <?php foreach ($press as $i => $row): ?>
          <div class="repeat-card auteur-repeat" data-repeat-row>
            <div class="form-grid-3">
              <input class="input" name="press[<?= $i ?>][title]" value="<?= e((string) ($row['title'] ?? '')) ?>" placeholder="Titre de l'article ou de l'émission" maxlength="190">
              <input class="input" name="press[<?= $i ?>][source]" value="<?= e((string) ($row['source'] ?? '')) ?>" placeholder="Source (Le Monde des livres, France Inter…)" maxlength="190">
              <input class="input" name="press[<?= $i ?>][date]" value="<?= e((string) ($row['date'] ?? '')) ?>" placeholder="mars 2025" maxlength="40">
            </div>
            <input class="input" name="press[<?= $i ?>][url]" value="<?= e((string) ($row['url'] ?? '')) ?>" placeholder="https://" inputmode="url">
            <button type="button" class="text-btn" data-repeat-remove>Retirer cette référence</button>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="btn-ghost" data-repeat-add="press">Ajouter un article ou une émission</button>

      <div class="form-grid-2" style="margin-top: 18px;">
        <div>
          <label class="field" for="wikipedia_url">Page Wikipédia</label>
          <input class="input" id="wikipedia_url" name="wikipedia_url" value="<?= e((string) ($p['wikipedia_url'] ?? '')) ?>" placeholder="https://fr.wikipedia.org/wiki/…" inputmode="url">
        </div>
        <div>
          <label class="field" for="website">Site ou blog d'auteur</label>
          <input class="input" id="website" name="website" value="<?= e((string) ($p['website'] ?? '')) ?>" placeholder="https://" inputmode="url">
        </div>
      </div>
    </div>

    <div class="espace-panel">
      <h2 class="espace-group-title">Autres liens</h2>
      <p class="field-help">Page éditeur, Babelio, Goodreads, chaîne vidéo, lettre d'information… Choisissez le type, ajoutez un libellé si besoin, puis l'adresse.</p>
      <div class="repeat-list" data-repeat="links">
        <?php foreach ($links as $i => $row): ?>
          <div class="repeat-row auteur-link-row" data-repeat-row>
            <select class="input" name="links[<?= $i ?>][kind]" aria-label="Type de lien">
              <?php foreach ($linkKinds as $value => $label): ?>
                <option value="<?= e($value) ?>"<?= (($row['kind'] ?? 'site') === $value) ? ' selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
            <input class="input" name="links[<?= $i ?>][label]" value="<?= e((string) ($row['label'] ?? '')) ?>" placeholder="Libellé (optionnel)" maxlength="190">
            <input class="input" name="links[<?= $i ?>][url]" value="<?= e((string) ($row['url'] ?? '')) ?>" placeholder="https://" inputmode="url">
            <button type="button" class="icon-btn" data-repeat-remove aria-label="Retirer">✕</button>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="btn-ghost" data-repeat-add="links">Ajouter un lien</button>
    </div>

    <div class="espace-panel">
      <h2 class="espace-group-title">Prix et distinctions</h2>
      <div class="repeat-list" data-repeat="awards">
        <?php foreach ($awards as $i => $row): ?>
          <div class="repeat-row" data-repeat-row>
            <input class="input" name="awards[<?= $i ?>][year]" value="<?= e((string) ($row['year'] ?? '')) ?>" placeholder="2023" maxlength="20">
            <input class="input" name="awards[<?= $i ?>][label]" value="<?= e((string) ($row['label'] ?? '')) ?>" placeholder="Prix, sélection, bourse…" maxlength="190">
            <input class="input" name="awards[<?= $i ?>][work]" value="<?= e((string) ($row['work'] ?? '')) ?>" placeholder="Pour quel titre (optionnel)" maxlength="190">
            <button type="button" class="icon-btn" data-repeat-remove aria-label="Retirer">✕</button>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="btn-ghost" data-repeat-add="awards">Ajouter une distinction</button>
    </div>

    <div class="espace-panel">
      <h2 class="espace-group-title">Rencontres et actualités</h2>
      <p class="field-help">Dédicaces, salons, lectures, rencontres à venir. Les dates passées restent affichées : retirez-les quand vous le souhaitez.</p>
      <div class="repeat-list" data-repeat="events">
        <?php foreach ($events as $i => $row): ?>
          <div class="repeat-card auteur-repeat" data-repeat-row>
            <div class="form-grid-3">
              <input class="input" name="events[<?= $i ?>][date]" value="<?= e((string) ($row['date'] ?? '')) ?>" placeholder="14 novembre 2026" maxlength="60">
              <input class="input" name="events[<?= $i ?>][label]" value="<?= e((string) ($row['label'] ?? '')) ?>" placeholder="Dédicace, salon, lecture…" maxlength="190">
              <input class="input" name="events[<?= $i ?>][place]" value="<?= e((string) ($row['place'] ?? '')) ?>" placeholder="Lieu, ville" maxlength="190">
            </div>
            <input class="input" name="events[<?= $i ?>][url]" value="<?= e((string) ($row['url'] ?? '')) ?>" placeholder="Lien vers l'événement (optionnel)" inputmode="url">
            <button type="button" class="text-btn" data-repeat-remove>Retirer cette date</button>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="btn-ghost" data-repeat-add="events">Ajouter une rencontre</button>

      <div style="margin-top: 18px;">
        <h3 class="espace-group-title">Je suis disponible pour</h3>
        <p class="field-help">Ces propositions apparaissent sur votre fiche : libraires, bibliothécaires et organisateurs vous écrivent directement.</p>
        <div class="chip-row">
          <?php foreach ($openTo as $value => $label): ?>
            <label class="chip<?= in_array($value, $selectedOpenTo, true) ? ' is-on' : '' ?>">
              <input type="checkbox" name="open_to[]" value="<?= e($value) ?>"<?= in_array($value, $selectedOpenTo, true) ? ' checked' : '' ?>>
              <?= e($label) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="auth-actions" style="margin-top: 28px;">
      <button class="btn-orange" type="submit">Enregistrer la fiche auteur</button>
      <a class="btn-ghost" href="<?= e(url('/espace/auteur/oeuvres')) ?>">Gérer mes œuvres</a>
    </div>
  </form>
</div>

<template id="tpl-press">
  <div class="repeat-card auteur-repeat" data-repeat-row>
    <div class="form-grid-3">
      <input class="input" name="press[__i__][title]" placeholder="Titre de l'article ou de l'émission" maxlength="190">
      <input class="input" name="press[__i__][source]" placeholder="Source (Le Monde des livres, France Inter…)" maxlength="190">
      <input class="input" name="press[__i__][date]" placeholder="mars 2025" maxlength="40">
    </div>
    <input class="input" name="press[__i__][url]" placeholder="https://" inputmode="url">
    <button type="button" class="text-btn" data-repeat-remove>Retirer cette référence</button>
  </div>
</template>
<template id="tpl-links">
  <div class="repeat-row auteur-link-row" data-repeat-row>
    <select class="input" name="links[__i__][kind]" aria-label="Type de lien">
      <?php foreach ($linkKinds as $value => $label): ?><option value="<?= e($value) ?>"><?= e($label) ?></option><?php endforeach; ?>
    </select>
    <input class="input" name="links[__i__][label]" placeholder="Libellé (optionnel)" maxlength="190">
    <input class="input" name="links[__i__][url]" placeholder="https://" inputmode="url">
    <button type="button" class="icon-btn" data-repeat-remove aria-label="Retirer">✕</button>
  </div>
</template>
<template id="tpl-awards">
  <div class="repeat-row" data-repeat-row>
    <input class="input" name="awards[__i__][year]" placeholder="2023" maxlength="20">
    <input class="input" name="awards[__i__][label]" placeholder="Prix, sélection, bourse…" maxlength="190">
    <input class="input" name="awards[__i__][work]" placeholder="Pour quel titre (optionnel)" maxlength="190">
    <button type="button" class="icon-btn" data-repeat-remove aria-label="Retirer">✕</button>
  </div>
</template>
<template id="tpl-events">
  <div class="repeat-card auteur-repeat" data-repeat-row>
    <div class="form-grid-3">
      <input class="input" name="events[__i__][date]" placeholder="14 novembre 2026" maxlength="60">
      <input class="input" name="events[__i__][label]" placeholder="Dédicace, salon, lecture…" maxlength="190">
      <input class="input" name="events[__i__][place]" placeholder="Lieu, ville" maxlength="190">
    </div>
    <input class="input" name="events[__i__][url]" placeholder="Lien vers l'événement (optionnel)" inputmode="url">
    <button type="button" class="text-btn" data-repeat-remove>Retirer cette date</button>
  </div>
</template>
