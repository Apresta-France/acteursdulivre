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
$specialtyTradeIndex = \Adl\Data\Catalog::specialtyTradeIndex();
$displayGenres = \Adl\Data\Catalog::mappedSpecialtyNames();
foreach (array_merge($genres, $selectedGenres) as $genre) {
    if (is_string($genre) && $genre !== '' && !in_array($genre, $displayGenres, true)) {
        $displayGenres[] = $genre;
    }
}
$tools = implode(', ', $p['tools'] ?? []);
$socials = !empty($p['socials']) ? $p['socials'] : [['network' => '', 'url' => '']];
$socialNetworks = $socialNetworks ?? \Adl\Models\Profile::SOCIAL_NETWORKS;
$completion = (int) ($completion ?? ($p['completion'] ?? 0));
$publicHref = !empty($p['slug']) ? url('/prestataires/' . $p['slug']) : '';
$tab = in_array(($tab ?? ''), ['identite', 'competences', 'parcours', 'portfolio', 'avis'], true)
    ? (string) $tab
    : 'identite';
$pendingReviews = $pendingReviews ?? [];
$receivedReviews = $receivedReviews ?? [];
$reviewStats = $reviewStats ?? ['avg' => '', 'count' => 0];
$pendingInvites = $pendingInvites ?? [];
$recommendations = $recommendations ?? [];
$avisBadge = count($pendingReviews) + count($pendingInvites);
$rateKind = \Adl\Models\Profile::rateKind($p);
$rateHelp = \Adl\Models\Profile::rateHelp($p);
$rateValue = (string) ($p['hourly_rate'] ?? '');
if ($rateKind === \Adl\Models\Profile::RATE_PERCENT || str_contains($rateValue, '%')) {
    $rateValue = trim(str_replace('%', '', $rateValue));
}
?>
<div class="espace-page vitrine-page">
  <div class="espace-page-head">
    <div>
      <h1>Ma vitrine</h1>
      <p>Profil complété à <?= $completion ?> %. Les vitrines précises reçoivent nettement plus de demandes.</p>
      <?php if (!empty($p['is_platform_cofounder']) || !empty($p['is_founder'])): ?>
        <div class="espace-page-badges">
          <?php if (!empty($p['is_platform_cofounder'])): ?>
            <span class="profile-badge profile-badge-cofounder">Co-fondateur de la plateforme</span>
          <?php endif; ?>
          <?php if (!empty($p['is_founder'])): ?>
            <span class="profile-badge profile-badge-founder">Membre fondateur</span>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
    <div class="vitrine-head-actions">
      <a class="btn-ghost" href="<?= e(url('/espace/statistiques')) ?>">Statistiques</a>
      <?php if ($publicHref): ?>
        <a class="btn-ghost" href="<?= e($publicHref) ?>">Voir en public</a>
      <?php endif; ?>
      <button class="btn-orange" type="submit" form="vitrine-form" data-hide-on-tab="avis"<?= $tab === 'avis' ? ' hidden' : '' ?>>Enregistrer</button>
    </div>
  </div>

  <div class="vitrine-progress"><span style="width: <?= max(6, $completion) ?>%"></span></div>

  <?php if (!empty($saved)): ?>
    <div class="flash flash-ok"><?= e(is_string($saved) && $saved !== '1' ? $saved : 'Votre vitrine a été enregistrée.') ?></div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div class="flash flash-error"><?= e((string) $error) ?></div>
  <?php endif; ?>

  <div class="tab-row" data-tabs>
    <a class="tab<?= $tab === 'identite' ? ' is-on' : '' ?>" href="<?= e(url('/espace/vitrine')) ?>" data-tab="identite">Identité</a>
    <a class="tab<?= $tab === 'competences' ? ' is-on' : '' ?>" href="<?= e(url('/espace/vitrine?onglet=competences')) ?>" data-tab="competences">Compétences</a>
    <a class="tab<?= $tab === 'parcours' ? ' is-on' : '' ?>" href="<?= e(url('/espace/vitrine?onglet=parcours')) ?>" data-tab="parcours">Parcours</a>
    <a class="tab<?= $tab === 'portfolio' ? ' is-on' : '' ?>" href="<?= e(url('/espace/vitrine?onglet=portfolio')) ?>" data-tab="portfolio">Créations &amp; exemples</a>
    <a class="tab<?= $tab === 'avis' ? ' is-on' : '' ?>" href="<?= e(url('/espace/vitrine?onglet=avis')) ?>" data-tab="avis">Avis<?php if ($avisBadge > 0): ?> <span class="tab-count"><?= (int) $avisBadge ?></span><?php endif; ?></a>
  </div>

  <form id="vitrine-form" class="vitrine-form" method="post" action="<?= e(url('/espace/vitrine')) ?>" enctype="multipart/form-data" data-hide-on-tab="avis"<?= $tab === 'avis' ? ' hidden' : '' ?>>
    <?= csrf_field() ?>
    <input type="hidden" name="onglet" value="<?= e($tab) ?>" data-vitrine-tab>

    <div data-tab-panel="identite"<?= $tab === 'identite' ? '' : ' hidden' ?>>
      <div class="espace-panel">
        <h2 class="espace-group-title">Identité</h2>
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
      <?php
        $nameMode = \Adl\Models\Profile::normalizeNameMode($p['name_mode'] ?? null);
        $nameModes = $nameModes ?? \Adl\Models\Profile::NAME_MODES;
      ?>
      <div data-name-mode>
        <h3 class="espace-group-title">Nom affiché sur la vitrine</h3>
        <p class="field-help">Le prénom et le nom du compte restent utilisés pour la facturation et les messages.</p>
        <div class="chip-row">
          <?php foreach ($nameModes as $value => $label): ?>
            <label class="chip<?= $nameMode === $value ? ' is-on' : '' ?>">
              <input type="radio" name="name_mode" value="<?= e($value) ?>"<?= $nameMode === $value ? ' checked' : '' ?>>
              <?= e($label) ?>
            </label>
          <?php endforeach; ?>
        </div>
        <div data-public-name-wrap<?= $nameMode === \Adl\Models\Profile::NAME_CUSTOM ? '' : ' hidden' ?> style="margin-top: 12px;">
          <label class="field" for="public_name">Nom de structure</label>
          <input class="input" id="public_name" name="public_name" value="<?= e((string) ($p['public_name'] ?? '')) ?>" placeholder="Atelier Virgule">
        </div>
      </div>
      <div>
        <label class="field" for="vitrine-title">Titre de la vitrine</label>
        <input class="input" id="vitrine-title" name="title" value="<?= e((string) ($p['title'] ?? '')) ?>" placeholder="Correctrice-relectrice, romans et essais">
      </div>
      </div>

      <div class="espace-panel">
        <h2 class="espace-group-title">À propos</h2>
      <div>
        <label class="field" for="presentation">Présentation</label>
        <textarea class="textarea" id="presentation" name="presentation" rows="6" placeholder="Votre parcours, vos spécialités, votre façon de travailler. Évitez le jargon vide : un client doit comprendre ce que vous faites mieux que d'autres."><?= e((string) ($p['presentation'] ?? '')) ?></textarea>
      </div>
      <div class="form-grid-2">
        <div>
          <label class="field" for="does">Ce que je fais</label>
          <textarea class="textarea" id="does" name="does" rows="5" placeholder="Correction de romans et essais, relecture de cohérence, rapport de lecture."><?= e((string) ($p['does'] ?? '')) ?></textarea>
          <p class="field-help">Une idée par ligne. Visible sur votre profil public.</p>
        </div>
        <div>
          <label class="field" for="does_not">Ce que je ne fais pas</label>
          <textarea class="textarea" id="does_not" name="does_not" rows="5" placeholder="Ghostwriting, traduction, mise en page, manuscrits techniques."><?= e((string) ($p['does_not'] ?? '')) ?></textarea>
          <p class="field-help">Les exclusions évitent les demandes hors sujet.</p>
        </div>
      </div>
      </div>

      <div class="espace-panel">
        <h2 class="espace-group-title">Disponibilité et lieu</h2>
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
      <div class="form-grid-2">
        <div>
          <label class="field" for="city">Ville</label>
          <?php
            $cityField = [
                'mode' => 'profile',
                'id' => 'city',
                'name' => 'city',
                'value' => (string) ($p['city'] ?? ''),
                'slug' => (string) ($p['city_slug'] ?? ''),
                'insee' => (string) ($p['city_insee'] ?? ''),
                'placeholder' => 'Commencez à taper une ville',
            ];
            require ADL_ROOT . '/app/Views/partials/city-field.php';
          ?>
          <p class="field-help">Choisissez une commune dans la liste : elle sert à filtrer l'annuaire (ex. correctrice à Paris).</p>
        </div>
        <div>
          <label class="field" for="availability">Précision (optionnel)</label>
          <input class="input" id="availability" name="availability" value="<?= e((string) ($p['availability'] ?? '')) ?>" placeholder="<?= $busy ? 'reprend le 15 octobre' : 'dès maintenant, sous 48 h' ?>">
        </div>
      </div>
      <div>
        <h3 class="espace-group-title">Lieu de travail</h3>
        <?php $workMode = (string) ($p['work_mode'] ?? ''); ?>
        <div class="chip-row">
          <label class="chip<?= $workMode === '' ? ' is-on' : '' ?>">
            <input type="radio" name="work_mode" value=""<?= $workMode === '' ? ' checked' : '' ?>>
            Non précisé
          </label>
          <?php foreach (\Adl\Models\Profile::WORK_MODES as $value => $label): ?>
            <label class="chip<?= $workMode === $value ? ' is-on' : '' ?>">
              <input type="radio" name="work_mode" value="<?= e($value) ?>"<?= $workMode === $value ? ' checked' : '' ?>>
              <?= e($label) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div>
        <h3 class="espace-group-title">Délai de réponse</h3>
        <p class="field-help">Le temps dans lequel vous répondez habituellement à une première demande.</p>
        <?php $responseTime = (string) ($p['response_time'] ?? ''); ?>
        <div class="chip-row">
          <label class="chip<?= $responseTime === '' ? ' is-on' : '' ?>">
            <input type="radio" name="response_time" value=""<?= $responseTime === '' ? ' checked' : '' ?>>
            Non précisé
          </label>
          <?php foreach (\Adl\Models\Profile::RESPONSE_TIMES as $value => $label): ?>
            <label class="chip<?= $responseTime === $value ? ' is-on' : '' ?>">
              <input type="radio" name="response_time" value="<?= e($value) ?>"<?= $responseTime === $value ? ' checked' : '' ?>>
              <?= e($label) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
      </div>

      <div class="espace-panel">
        <h2 class="espace-group-title">Tarifs et présence</h2>
      <div data-rate-fields
           data-bookstore-trade="<?= e(\Adl\Models\Profile::TRADE_BOOKSTORE) ?>"
           data-rights-trades="<?= e(implode(',', \Adl\Models\Profile::RIGHTS_TRADES)) ?>"
           data-help-default="Les libraires indiquent une commission sur les ventes. Les illustrateurs précisent parfois un droit d’exploitation commerciale ou une cession de droits."
           data-help-rights="Les illustrateurs précisent parfois un droit d’exploitation commerciale ou une cession de droits."
           data-help-photo="Les photographes et iconographes précisent parfois un droit d’exploitation commerciale ou une cession de droits."
           data-help-bookstore="Les libraires indiquent une commission sur les ventes, pas un prix de prestation."
           data-help-both="Les libraires indiquent une commission sur les ventes. Les illustrateurs précisent parfois un droit d’exploitation commerciale ou une cession de droits.">
        <h3 class="espace-group-title">Mode de tarification</h3>
        <p class="field-help" data-rate-help><?= e($rateHelp) ?></p>
        <div class="chip-row" style="margin: 10px 0 16px;">
          <label class="chip<?= $rateKind === 'price' ? ' is-on' : '' ?>">
            <input type="radio" name="rate_kind" value="price"<?= $rateKind === 'price' ? ' checked' : '' ?> data-rate-kind>
            Tarif (€)
          </label>
          <label class="chip<?= $rateKind === 'percent' ? ' is-on' : '' ?>">
            <input type="radio" name="rate_kind" value="percent"<?= $rateKind === 'percent' ? ' checked' : '' ?> data-rate-kind>
            Commission (%)
          </label>
          <label class="chip<?= $rateKind === 'exploitation' ? ' is-on' : '' ?>" data-rate-rights>
            <input type="radio" name="rate_kind" value="exploitation"<?= $rateKind === 'exploitation' ? ' checked' : '' ?> data-rate-kind>
            Exploitation com.
          </label>
          <label class="chip<?= $rateKind === 'cession' ? ' is-on' : '' ?>" data-rate-rights>
            <input type="radio" name="rate_kind" value="cession"<?= $rateKind === 'cession' ? ' checked' : '' ?> data-rate-kind>
            Cession de droits
          </label>
        </div>
        <div class="form-grid-3">
          <div>
            <?php
              $rateLabels = [
                  'percent' => 'Commission',
                  'exploitation' => 'Exploitation',
                  'cession' => 'Cession',
              ];
              $ratePlaceholders = [
                  'percent' => '35',
                  'exploitation' => '15 % ou 400 €',
                  'cession' => '800 €',
              ];
              $noteLabels = [
                  'percent' => 'Précision',
                  'exploitation' => 'Précision',
                  'cession' => 'Précision',
              ];
              $notePlaceholders = [
                  'percent' => 'sur le prix public TTC',
                  'exploitation' => 'durée, territoires, supports',
                  'cession' => '5 ans, monde, livre + numérique',
              ];
            ?>
            <label class="field" for="hourly_rate" data-rate-label><?= e($rateLabels[$rateKind] ?? 'Tarif') ?></label>
            <input class="input" id="hourly_rate" name="hourly_rate" value="<?= e($rateValue) ?>"
                   placeholder="<?= e($ratePlaceholders[$rateKind] ?? '32 € / heure') ?>"
                   inputmode="<?= $rateKind === 'percent' ? 'decimal' : 'text' ?>"
                   data-rate-input
                   data-placeholder-price="32 € / heure"
                   data-placeholder-percent="35"
                   data-placeholder-exploitation="15 % ou 400 €"
                   data-placeholder-cession="800 €">
          </div>
          <div>
            <label class="field" for="rate_note" data-rate-note-label><?= e($noteLabels[$rateKind] ?? 'Précision tarifaire') ?></label>
            <input class="input" id="rate_note" name="rate_note" value="<?= e((string) ($p['rate_note'] ?? '')) ?>"
                   placeholder="<?= e($notePlaceholders[$rateKind] ?? 'ou 4,50 € / 1 000 signes') ?>"
                   data-rate-note
                   data-placeholder-price="ou 4,50 € / 1 000 signes"
                   data-placeholder-percent="sur le prix public TTC"
                   data-placeholder-exploitation="durée, territoires, supports"
                   data-placeholder-cession="5 ans, monde, livre + numérique">
          </div>
          <div>
            <label class="field" for="website">Site ou portfolio externe</label>
            <input class="input" id="website" name="website" value="<?= e((string) ($p['website'] ?? '')) ?>" placeholder="https://">
          </div>
        </div>
      </div>
      <div>
        <h3 class="espace-group-title">Réseaux sociaux</h3>
        <p class="field-help">Les liens apparaissent sur votre vitrine publique. Un compte ou une adresse https:// suffit.</p>
        <div class="repeat-list" data-repeat="socials">
          <?php foreach ($socials as $i => $social): ?>
            <div class="repeat-row repeat-row-social" data-repeat-row>
              <select class="input" name="socials[<?= $i ?>][network]" aria-label="Réseau">
                <option value="">Réseau</option>
                <?php foreach ($socialNetworks as $value => $label): ?>
                  <option value="<?= e($value) ?>"<?= (($social['network'] ?? '') === $value) ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
              <input class="input" name="socials[<?= $i ?>][url]" value="<?= e((string) ($social['url'] ?? '')) ?>" placeholder="https:// ou @compte">
              <button type="button" class="icon-btn" data-repeat-remove aria-label="Retirer">✕</button>
            </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="btn-ghost" data-repeat-add="socials">Ajouter un réseau</button>
      </div>
      </div>
    </div>

    <div data-tab-panel="competences"<?= $tab === 'competences' ? '' : ' hidden' ?>>
      <div class="espace-panel">
        <h2 class="espace-group-title">Mes métiers</h2>
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

      <div class="espace-panel">
        <h2 class="espace-group-title">Compétences et niveau</h2>
        <div class="repeat-list" data-repeat="skills">
          <?php foreach ($skills as $i => $skill): ?>
            <div class="repeat-row" data-repeat-row>
              <div class="field-suggest">
                <input class="input" name="skills[<?= $i ?>][label]" value="<?= e((string) ($skill['label'] ?? '')) ?>" placeholder="Correction orthotypographique" autocomplete="off" autocorrect="off" spellcheck="false" data-suggest="skills" role="combobox" aria-autocomplete="list" aria-expanded="false">
              </div>
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

      <div class="espace-panel">
        <label class="field" for="tools">Logiciels et outils</label>
        <div class="field-suggest">
          <input class="input" id="tools" name="tools" value="<?= e($tools) ?>" placeholder="Antidote, InDesign, Word, Pro Tools…" autocomplete="off" autocorrect="off" spellcheck="false" data-suggest="tools" data-suggest-split="," role="combobox" aria-autocomplete="list" aria-expanded="false">
        </div>
        <p class="field-help">Séparez les outils par une virgule.</p>
      </div>

      <div class="espace-panel">
        <h2 class="espace-group-title">Langues de travail</h2>
        <p class="field-help">Langue et niveau : ils s’affichent sur votre fiche publique. Le résumé (Français, Anglais…) se construit tout seul.</p>
        <div class="repeat-list" data-repeat="languages_list">
          <?php foreach ($languagesList as $i => $lang): ?>
            <div class="repeat-row" data-repeat-row>
              <div class="field-suggest">
                <input class="input" name="languages_list[<?= $i ?>][langue]" value="<?= e((string) ($lang['langue'] ?? '')) ?>" placeholder="Français" autocomplete="off" autocorrect="off" spellcheck="false" data-suggest="languages" role="combobox" aria-autocomplete="list" aria-expanded="false">
              </div>
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

      <div class="espace-panel" data-vitrine-specialties
           data-help-none="<?= e(\Adl\Data\Catalog::specialtyHelp('')) ?>"
           data-help-text="<?= e(\Adl\Data\Catalog::specialtyHelpForTrades(['Correction'])) ?>"
           data-help-other="<?= e(\Adl\Data\Catalog::specialtyHelpForTrades(['Illustration'])) ?>"
           data-help-both="<?= e(\Adl\Data\Catalog::specialtyHelpForTrades(['Correction', 'Illustration'])) ?>"
           data-text-trades="<?= e(implode(',', \Adl\Data\Catalog::TEXT_TRADES)) ?>">
        <h2 class="espace-group-title">Spécialités</h2>
        <p class="field-help" data-specialty-help><?= e(\Adl\Data\Catalog::specialtyHelpForTrades($selectedTrades)) ?></p>
        <div class="chip-row" data-specialty-chips>
          <?php foreach ($displayGenres as $genre): ?>
            <?php
              $forTrades = $specialtyTradeIndex[$genre] ?? [];
              $checked = in_array($genre, $selectedGenres, true);
              $visible = $checked || array_intersect($selectedTrades, $forTrades) !== [];
            ?>
            <label class="chip<?= $checked ? ' is-on' : '' ?>" data-specialty-chip data-for-trades="<?= e(implode(',', $forTrades)) ?>"<?= $visible ? '' : ' hidden' ?>>
              <input type="checkbox" name="genres[]" value="<?= e($genre) ?>"<?= $checked ? ' checked' : '' ?>>
              <?= e($genre) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div data-tab-panel="parcours"<?= $tab === 'parcours' ? '' : ' hidden' ?>>
      <div class="espace-panel">
        <h2 class="espace-group-title">Expériences</h2>
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

      <div class="espace-panel">
        <h2 class="espace-group-title">Formation et certifications</h2>
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

    <div data-tab-panel="portfolio"<?= $tab === 'portfolio' ? '' : ' hidden' ?>>
      <div class="espace-panel">
        <h2 class="espace-group-title">Créations et exemples</h2>
        <p class="espace-section-lead">Ajoutez des créations déjà réalisées et des exemples : un titre, une année, une courte description, et un visuel (fichier ou lien). Le visuel s’affiche dès que vous le choisissez ; enregistrez ensuite la vitrine. Chaque pièce doit être une réalisation humaine dont vous détenez les droits.</p>
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
            <span class="field">Visuel (JPG, PNG, WebP ou GIF — 5 Mo max)</span>
            <?php
              $hasRealImage = trim((string) ($item['image_path'] ?? '')) !== '' || trim((string) ($item['image_url'] ?? '')) !== '';
              $previewSrc = $hasRealImage ? (string) ($item['image'] ?? '') : '';
              $filePickName = 'portfolio_file[' . $i . ']';
              $filePickAccept = 'image/jpeg,image/png,image/webp,image/gif';
              $filePickButton = 'Choisir un visuel';
              $filePickEmpty = $hasRealImage ? 'Visuel actuel — choisir un autre fichier pour le remplacer' : null;
              $filePickDrop = true;
              require ADL_ROOT . '/app/Views/partials/file-pick.php';
            ?>
            <p class="field-help" data-portfolio-file-error hidden>Ce format n’est pas accepté. Envoyez un JPG, PNG, WebP ou GIF.</p>
            <div class="portfolio-preview" data-portfolio-preview<?= $previewSrc !== '' ? ' style="background-image:url(\'' . e($previewSrc) . '\')"' : ' hidden' ?>></div>
            <button type="button" class="text-btn" data-repeat-remove>Retirer cette pièce</button>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="btn-ghost" data-repeat-add="portfolio">Ajouter une création ou un exemple</button>
      </div>
    </div>

    <div class="auth-actions" style="margin-top: 28px;" data-hide-on-tab="avis"<?= $tab === 'avis' ? ' hidden' : '' ?>>
      <button class="btn-orange" type="submit">Enregistrer la vitrine</button>
    </div>
  </form>

  <div data-tab-panel="avis" class="vitrine-avis"<?= $tab === 'avis' ? '' : ' hidden' ?>>
    <section class="espace-panel">
      <div class="espace-panel-head">
        <h2 class="espace-section-title">Avis et recommandations</h2>
        <p class="espace-section-lead">Les avis (étoiles) viennent uniquement des missions réalisées ici. Vous pouvez relancer un client en attente de validation, ou inviter un client externe à écrire une recommandation. Les recommandations hors plateforme s’affichent à part et ne comptent pas dans la note.</p>
      </div>
      <div class="vitrine-avis-stats">
        <div>
          <strong><?= (int) ($reviewStats['count'] ?? 0) ?></strong>
          <span>avis plateforme<?= !empty($reviewStats['avg']) ? ' · ' . e((string) $reviewStats['avg']) . ' / 5' : '' ?></span>
        </div>
        <div>
          <strong><?= count(array_filter($recommendations, static fn (array $r): bool => empty($r['hidden']))) ?></strong>
          <span>recommandation<?= count(array_filter($recommendations, static fn (array $r): bool => empty($r['hidden']))) > 1 ? 's' : '' ?> publique<?= count(array_filter($recommendations, static fn (array $r): bool => empty($r['hidden']))) > 1 ? 's' : '' ?></span>
        </div>
      </div>
    </section>

    <section class="espace-panel">
      <h2 class="espace-group-title">Clients de la plateforme</h2>
      <p class="field-help">Après livraison et déclaration du solde, le client valide la mission et laisse un avis. Vous pouvez le relancer s’il n’a pas encore passé ce jalon.</p>
      <?php if ($pendingReviews === []): ?>
        <p class="mission-row-sub">Aucune mission n’attend d’avis pour le moment.</p>
      <?php else: ?>
        <div class="vitrine-avis-list">
          <?php foreach ($pendingReviews as $order): ?>
            <?php
              $buyerName = trim((string) (($order['buyer_first'] ?? '') . ' ' . ($order['buyer_last'] ?? '')));
              $req = is_array($order['review_request'] ?? null) ? $order['review_request'] : null;
            ?>
            <article class="vitrine-avis-item">
              <div>
                <div class="mission-row-title"><?= e((string) $order['title']) ?></div>
                <div class="mission-row-sub">
                  <?= e((string) $order['num']) ?><?= $buyerName !== '' ? ' · ' . e($buyerName) : '' ?>
                  <?php if ($req): ?> · demandée <?= e((string) ($req['sent_when'] ?? '')) ?><?php endif; ?>
                </div>
              </div>
              <form method="post" action="<?= e(url('/espace/vitrine/avis')) ?>">
                <?= csrf_field() ?>
                <?php if ($req && empty($req['can_resend'])): ?>
                  <input type="hidden" name="action" value="resend">
                  <input type="hidden" name="request_id" value="<?= (int) $req['id'] ?>">
                  <button class="btn-ghost" type="submit" disabled>Relancée récemment</button>
                <?php elseif ($req): ?>
                  <input type="hidden" name="action" value="resend">
                  <input type="hidden" name="request_id" value="<?= (int) $req['id'] ?>">
                  <button class="btn-navy" type="submit">Relancer</button>
                <?php else: ?>
                  <input type="hidden" name="action" value="platform">
                  <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                  <button class="btn-navy" type="submit">Demander un avis</button>
                <?php endif; ?>
              </form>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($receivedReviews !== []): ?>
        <h3 class="espace-group-title" style="margin-top: 22px;">Avis reçus</h3>
        <div class="vitrine-avis-list">
          <?php foreach ($receivedReviews as $review): ?>
            <article class="vitrine-avis-item is-static">
              <div>
                <div class="mission-row-title"><?= e((string) $review['who']) ?> · <?= e((string) $review['note']) ?> / 5</div>
                <?php if (!empty($review['txt'])): ?>
                  <p class="profile-text"><?= e((string) $review['txt']) ?></p>
                <?php endif; ?>
                <div class="mission-row-sub"><?= e((string) $review['when']) ?></div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <section class="espace-panel">
      <h2 class="espace-group-title">Clients hors plateforme</h2>
      <p class="field-help">Invitez un client avec qui vous avez travaillé ailleurs. Son texte sera publié comme recommandation, clairement distincte des avis de mission, et n’entrera pas dans votre note.</p>
      <form method="post" action="<?= e(url('/espace/vitrine/avis')) ?>" class="vitrine-invite-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="external">
        <div class="form-grid-2">
          <div>
            <label class="field" for="invite-name">Nom</label>
            <input class="input" id="invite-name" name="name" required placeholder="Camille Dupont" value="<?= e((string) old('name')) ?>">
          </div>
          <div>
            <label class="field" for="invite-email">E-mail</label>
            <input class="input" id="invite-email" type="email" name="email" required placeholder="camille@exemple.fr" value="<?= e((string) old('email')) ?>">
          </div>
        </div>
        <div>
          <label class="field" for="invite-context">Contexte (optionnel)</label>
          <input class="input" id="invite-context" name="context" maxlength="160" placeholder="Correction d’un roman, 90 000 signes" value="<?= e((string) old('context')) ?>">
        </div>
        <div class="auth-actions">
          <button class="btn-orange" type="submit">Envoyer une invitation</button>
        </div>
      </form>

      <?php if ($pendingInvites !== []): ?>
        <h3 class="espace-group-title" style="margin-top: 22px;">Invitations en attente</h3>
        <div class="vitrine-avis-list">
          <?php foreach ($pendingInvites as $invite): ?>
            <article class="vitrine-avis-item">
              <div>
                <div class="mission-row-title"><?= e((string) $invite['recipient_name']) ?></div>
                <div class="mission-row-sub">
                  <?= e((string) $invite['recipient_email']) ?>
                  <?php if ($invite['context'] !== ''): ?> · <?= e((string) $invite['context']) ?><?php endif; ?>
                  · envoyée <?= e((string) $invite['sent_when']) ?>
                  <?php if ($invite['expires_label'] !== ''): ?> · expire le <?= e((string) $invite['expires_label']) ?><?php endif; ?>
                </div>
              </div>
              <div class="vitrine-avis-actions">
                <form method="post" action="<?= e(url('/espace/vitrine/avis')) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="resend">
                  <input type="hidden" name="request_id" value="<?= (int) $invite['id'] ?>">
                  <button class="btn-ghost" type="submit"<?= empty($invite['can_resend']) ? ' disabled' : '' ?>><?= empty($invite['can_resend']) ? 'Relancée récemment' : 'Relancer' ?></button>
                </form>
                <form method="post" action="<?= e(url('/espace/vitrine/avis')) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="cancel">
                  <input type="hidden" name="request_id" value="<?= (int) $invite['id'] ?>">
                  <button class="text-btn" type="submit">Annuler</button>
                </form>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($recommendations !== []): ?>
        <h3 class="espace-group-title" style="margin-top: 22px;">Recommandations reçues</h3>
        <div class="vitrine-avis-list">
          <?php foreach ($recommendations as $reco): ?>
            <article class="vitrine-avis-item is-static<?= !empty($reco['hidden']) ? ' is-dim' : '' ?>">
              <div>
                <div class="mission-row-title">
                  <?= e((string) $reco['who']) ?>
                  <?php if ($reco['role'] !== ''): ?> · <?= e((string) $reco['role']) ?><?php endif; ?>
                  <?php if (!empty($reco['hidden'])): ?> · masquée<?php endif; ?>
                </div>
                <?php if ($reco['context'] !== ''): ?>
                  <div class="mission-row-sub"><?= e((string) $reco['context']) ?></div>
                <?php endif; ?>
                <?php if ($reco['txt'] !== ''): ?>
                  <p class="profile-text"><?= e((string) $reco['txt']) ?></p>
                <?php endif; ?>
                <div class="mission-row-sub"><?= e((string) $reco['when']) ?> · hors plateforme</div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>

  <section class="espace-panel" style="margin-top: 8px; max-width: 860px;" data-hide-on-tab="avis"<?= $tab === 'avis' ? ' hidden' : '' ?>>
    <div class="espace-panel-head">
      <h2 class="espace-section-title">Justificatif d'activité</h2>
    </div>
    <?php
      $verify = (string) ($p['verification_status'] ?? '');
      $verifyLabel = match ($verify) {
          'verified' => 'Profil vérifié',
          'refused' => 'Dossier refusé — vous pouvez renvoyer un justificatif',
          'pending' => 'Dossier en cours de vérification',
          default => 'Aucun justificatif envoyé',
      };
    ?>
    <p><?= e($verifyLabel) ?><?php if (!empty($p['verification_doc_name'])): ?> · <?= e((string) $p['verification_doc_name']) ?><?php endif; ?></p>
    <form method="post" action="<?= e(url('/espace/vitrine/justificatif')) ?>" enctype="multipart/form-data" class="param-form">
      <?= csrf_field() ?>
      <span class="field" id="justificatif-label">KBIS, avis SIREN, attestation URSSAF ou équivalent (PDF, JPG, PNG — 8 Mo)</span>
      <?php
        $filePickId = 'justificatif';
        $filePickName = 'justificatif';
        $filePickAccept = '.pdf,image/jpeg,image/png,image/webp';
        $filePickRequired = empty($p['verification_doc_name']);
        $filePickButton = 'Choisir un justificatif';
        $filePickEmpty = null;
        $filePickHint = !empty($p['verification_doc_name'])
            ? 'Fichier actuel : ' . (string) $p['verification_doc_name']
            : '';
        $filePickDrop = true;
        $filePickAttrs = 'aria-labelledby="justificatif-label"';
        require ADL_ROOT . '/app/Views/partials/file-pick.php';
      ?>
      <label class="field" for="verify-note" style="margin-top: 12px;">Note (optionnel)</label>
      <input class="input" id="verify-note" name="note" value="<?= e((string) ($p['verification_note'] ?? '')) ?>" placeholder="Numéro SIRET, forme juridique…">
      <div class="auth-actions" style="margin-top: 14px;">
        <button class="btn-navy" type="submit">Envoyer pour vérification</button>
      </div>
    </form>
  </section>
</div>

<template id="tpl-socials">
  <div class="repeat-row repeat-row-social" data-repeat-row>
    <select class="input" name="socials[__i__][network]" aria-label="Réseau">
      <option value="">Réseau</option>
      <?php foreach ($socialNetworks as $value => $label): ?><option value="<?= e($value) ?>"><?= e($label) ?></option><?php endforeach; ?>
    </select>
    <input class="input" name="socials[__i__][url]" placeholder="https:// ou @compte">
    <button type="button" class="icon-btn" data-repeat-remove aria-label="Retirer">✕</button>
  </div>
</template>
<template id="tpl-skills">
  <div class="repeat-row" data-repeat-row>
    <div class="field-suggest">
      <input class="input" name="skills[__i__][label]" placeholder="Correction orthotypographique" autocomplete="off" autocorrect="off" spellcheck="false" data-suggest="skills" role="combobox" aria-autocomplete="list" aria-expanded="false">
    </div>
    <select class="input" name="skills[__i__][niveau]">
      <?php foreach ($skillLevels as $level): ?><option value="<?= e($level) ?>"><?= e($level) ?></option><?php endforeach; ?>
    </select>
    <button type="button" class="icon-btn" data-repeat-remove aria-label="Retirer">✕</button>
  </div>
</template>
<template id="tpl-languages_list">
  <div class="repeat-row" data-repeat-row>
    <div class="field-suggest">
      <input class="input" name="languages_list[__i__][langue]" placeholder="Français" autocomplete="off" autocorrect="off" spellcheck="false" data-suggest="languages" role="combobox" aria-autocomplete="list" aria-expanded="false">
    </div>
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
    <span class="field">Visuel (JPG, PNG, WebP ou GIF — 5 Mo max)</span>
    <?php
      $filePickName = 'portfolio_file[__i__]';
      $filePickAccept = 'image/jpeg,image/png,image/webp,image/gif';
      $filePickButton = 'Choisir un visuel';
      $filePickDrop = true;
      require ADL_ROOT . '/app/Views/partials/file-pick.php';
    ?>
    <p class="field-help" data-portfolio-file-error hidden>Ce format n’est pas accepté. Envoyez un JPG, PNG, WebP ou GIF.</p>
    <div class="portfolio-preview" data-portfolio-preview hidden></div>
    <button type="button" class="text-btn" data-repeat-remove>Retirer cette pièce</button>
  </div>
</template>
<script type="application/json" id="vitrine-suggests"><?= json_encode($profileSuggests ?? \Adl\Data\Catalog::profileSuggests(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?></script>
