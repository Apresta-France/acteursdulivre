<?php
$step = (string) ($step ?? 'identite');
$plan = is_array($plan ?? null) ? $plan : [];
$coach = is_array($coach ?? null) ? $coach : ['title' => 'Bienvenue', 'body' => '', 'tip' => ''];
$profile = is_array($profile ?? null) ? $profile : [];
$old = is_array($old ?? null) ? $old : [];
$trades = $trades ?? \Adl\Data\Catalog::trades();
$selectedTrades = $old['trades'] ?? ($profile['trades'] ?? []);
if (!is_array($selectedTrades)) {
    $selectedTrades = [];
}
$priorities = is_array($priorities ?? null) ? $priorities : [];
$missions = is_array($missions ?? null) ? $missions : [];
$seeks = !empty($seeksServices);
$offers = !empty($offersServices);
$first = (string) ($old['first_name'] ?? $prenom ?? $userFirst ?? '');
$last = (string) ($old['last_name'] ?? $nom ?? '');
$titleValue = (string) ($old['title'] ?? $profile['title'] ?? '');
$cityValue = (string) ($old['city'] ?? $profile['city'] ?? '');
$presentationValue = (string) ($old['presentation'] ?? $profile['presentation'] ?? '');
$missionTitle = (string) ($old['title'] ?? '');
$missionBrief = (string) ($old['brief'] ?? '');
$missionCat = (string) ($old['category_name'] ?? ($selectedTrades[0] ?? 'Correction'));
$stepIndex = 0;
foreach ($plan as $i => $item) {
    if (($item['id'] ?? '') === $step) {
        $stepIndex = $i;
        break;
    }
}
$previewName = trim($first . ' ' . $last) ?: (string) ($userName ?? 'Vous');
$previewInitials = \Adl\Models\User::initials(['first_name' => $first, 'last_name' => $last]);
$avatarSrc = (string) ($userAvatarUrl ?? '');
$publicHref = !empty($profile['slug']) ? '/prestataires/' . $profile['slug'] : '';
$completion = (int) ($profile['completion'] ?? 0);
$titleHints = $titleHints ?? \Adl\Data\Onboarding::TITLE_HINTS;
$presentationHints = $presentationHints ?? \Adl\Data\Onboarding::PRESENTATION_HINTS;
$presHint = $presentationHints[$selectedTrades[0] ?? ''] ?? 'Votre parcours, vos spécialités, votre façon de travailler. Évitez le jargon vide.';
?>
<div class="onboard" data-onboard data-title-hints="<?= e(json_encode($titleHints, JSON_UNESCAPED_UNICODE)) ?>" data-pres-hints="<?= e(json_encode($presentationHints, JSON_UNESCAPED_UNICODE)) ?>">
  <p class="onboard-kicker">Installation de votre compte · étape <?= $stepIndex + 1 ?> / <?= max(count($plan), 1) ?></p>
  <h1><?= e((string) $coach['title']) ?></h1>
  <p class="onboard-lead"><?= e((string) $coach['body']) ?></p>

  <ol class="onboard-steps">
    <?php foreach ($plan as $i => $item): ?>
      <li class="<?= $i === $stepIndex ? 'is-on' : ($i < $stepIndex ? 'is-done' : '') ?>">
        <?php if ($i < $stepIndex): ?>
          <a href="<?= e(url('/espace/bienvenue?etape=' . $item['id'])) ?>"><?= e((string) $item['label']) ?></a>
        <?php else: ?>
          <span><?= e((string) $item['label']) ?></span>
        <?php endif; ?>
        <em><?= e((string) $item['short']) ?></em>
      </li>
    <?php endforeach; ?>
  </ol>

  <?php if (!empty($error)): ?>
    <div class="flash flash-error"><?= e((string) $error) ?></div>
  <?php endif; ?>

  <div class="onboard-grid">
    <div class="onboard-main">
      <?php if ($step === 'identite'): ?>
        <form class="onboard-form" method="post" action="<?= e(url('/espace/bienvenue')) ?>" enctype="multipart/form-data" data-onboard-form>
          <?= csrf_field() ?>
          <input type="hidden" name="etape" value="identite">
          <?php
            $inputId = 'onboard-avatar';
            $initials = $previewInitials;
            $help = 'Cette photo apparaît dans l’espace et sur votre fiche. JPG, PNG ou WebP, 2 Mo max.';
            require ADL_ROOT . '/app/Views/partials/avatar-field.php';
          ?>
          <div class="auth-name-grid">
            <div>
              <label class="field" for="onboard-first">Prénom</label>
              <input class="input" id="onboard-first" name="first_name" value="<?= e($first) ?>" required data-preview-first>
            </div>
            <div>
              <label class="field" for="onboard-last">Nom</label>
              <input class="input" id="onboard-last" name="last_name" value="<?= e($last) ?>" required data-preview-last>
            </div>
          </div>
          <div class="onboard-actions">
            <button class="btn-orange" type="submit" name="intent" value="continue">Continuer</button>
            <button class="btn-ghost" type="submit" name="intent" value="skip" formnovalidate>Passer cette étape</button>
          </div>
        </form>

      <?php elseif ($step === 'vitrine'): ?>
        <form class="onboard-form" method="post" action="<?= e(url('/espace/bienvenue')) ?>" data-onboard-form>
          <?= csrf_field() ?>
          <input type="hidden" name="etape" value="vitrine">
          <div>
            <span class="field">Vos métiers</span>
            <p class="field-help">Trois maximum : ce sont eux qui vous font apparaître dans l’annuaire.</p>
            <div class="chip-row" data-max-checks="3" data-onboard-trades>
              <?php foreach ($trades as $trade): ?>
                <label class="chip<?= in_array($trade, $selectedTrades, true) ? ' is-on' : '' ?>">
                  <input type="checkbox" name="trades[]" value="<?= e($trade) ?>"<?= in_array($trade, $selectedTrades, true) ? ' checked' : '' ?>>
                  <?= e($trade) ?>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
          <div>
            <label class="field" for="onboard-title">Titre de la fiche</label>
            <input class="input" id="onboard-title" name="title" value="<?= e($titleValue) ?>"
                   placeholder="<?= e($titleHints[$selectedTrades[0] ?? ''] ?? 'Correcteur·rice, romans et essais') ?>"
                   data-preview-title data-title-input>
          </div>
          <div>
            <label class="field" for="onboard-city">Ville</label>
            <input class="input" id="onboard-city" name="city" value="<?= e($cityValue) ?>" placeholder="Nantes" data-preview-city>
          </div>
          <div>
            <label class="field" for="onboard-pres">Présentation</label>
            <textarea class="textarea" id="onboard-pres" name="presentation" rows="5"
                      placeholder="<?= e($presHint) ?>"
                      data-preview-pres data-count data-count-min="80" data-pres-input><?= e($presentationValue) ?></textarea>
            <p class="field-help" data-count-out>Quelques lignes suffisent — 80 caractères donnent déjà une fiche crédible.</p>
          </div>
          <div class="onboard-actions">
            <button class="btn-orange" type="submit" name="intent" value="continue">Voir l’aperçu</button>
            <button class="btn-ghost" type="submit" name="intent" value="skip" formnovalidate>Passer cette étape</button>
          </div>
        </form>

      <?php elseif ($step === 'mission'): ?>
        <form class="onboard-form" method="post" action="<?= e(url('/espace/bienvenue')) ?>" data-onboard-form data-publish-form
              data-volume-hints="<?= e(json_encode(\Adl\Data\Catalog::VOLUME_HINTS, JSON_UNESCAPED_UNICODE)) ?>"
              data-brief-hints="<?= e(json_encode(\Adl\Data\Catalog::BRIEF_HINTS, JSON_UNESCAPED_UNICODE)) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="etape" value="mission">
          <div>
            <span class="field">Métier recherché</span>
            <div class="chip-row">
              <?php foreach ($trades as $trade): ?>
                <label class="chip<?= $missionCat === $trade ? ' is-on' : '' ?>">
                  <input type="radio" name="category_name" value="<?= e($trade) ?>"<?= $missionCat === $trade ? ' checked' : '' ?>>
                  <?= e($trade) ?>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
          <div>
            <label class="field" for="onboard-mission-title">Titre de la recherche</label>
            <input class="input" id="onboard-mission-title" name="title" maxlength="255"
                   value="<?= e($missionTitle) ?>"
                   placeholder="Recherche correcteur pour essai historique, 240 pages"
                   data-preview-title>
          </div>
          <div>
            <label class="field" for="onboard-mission-brief">Brief</label>
            <textarea class="textarea" id="onboard-mission-brief" name="brief" rows="5"
                      placeholder="<?= e(\Adl\Data\Catalog::briefHint($missionCat)) ?>"
                      data-preview-brief><?= e($missionBrief) ?></textarea>
          </div>
          <?php $hint = \Adl\Data\Catalog::volumeHint($missionCat) ?? []; ?>
          <div class="form-grid-3<?= $hint ? '' : ' is-two' ?>" data-publish-metrics>
            <div data-volume-wrap<?= $hint ? '' : ' hidden' ?>>
              <label class="field" for="onboard-volume" data-volume-label><?= e($hint['label'] ?? 'Volume') ?></label>
              <input class="input" id="onboard-volume" name="volume" data-volume-input
                     value="<?= e((string) ($old['volume'] ?? '')) ?>"
                     placeholder="<?= e($hint['placeholder'] ?? '') ?>"
                     <?= $hint ? '' : ' disabled' ?>>
            </div>
            <div>
              <label class="field" for="onboard-min">Budget min. (€)</label>
              <input class="input" id="onboard-min" name="budget_min" inputmode="numeric" value="<?= e((string) ($old['budget_min'] ?? '')) ?>" placeholder="600" data-preview-min>
            </div>
            <div>
              <label class="field" for="onboard-max">Budget max. (€)</label>
              <input class="input" id="onboard-max" name="budget_max" inputmode="numeric" value="<?= e((string) ($old['budget_max'] ?? '')) ?>" placeholder="900" data-preview-max>
            </div>
          </div>
          <div class="onboard-actions">
            <button class="btn-orange" type="submit" name="intent" value="continue">Publier la recherche</button>
            <button class="btn-ghost" type="submit" name="intent" value="skip" formnovalidate>Je le ferai plus tard</button>
          </div>
        </form>

      <?php else: ?>
        <div class="onboard-recap">
          <?php if ($offers): ?>
            <div class="onboard-fiche" data-fiche-preview>
              <?= avatar_html(['avatar_url' => $avatarSrc, 'first_name' => $first, 'last_name' => $last], 72, 'avatar') ?>
              <div>
                <strong data-preview-out-name><?= e($previewName) ?></strong>
                <em data-preview-out-title><?= e($titleValue !== '' ? $titleValue : 'Titre de la fiche à préciser') ?><?= $cityValue !== '' ? ' · ' . e($cityValue) : '' ?></em>
                <?php if ($selectedTrades !== []): ?>
                  <div class="chip-row">
                    <?php foreach ($selectedTrades as $trade): ?>
                      <span class="chip-static"><?= e((string) $trade) ?></span>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
                <?php if ($presentationValue !== ''): ?>
                  <p><?= nl2br(e($presentationValue)) ?></p>
                <?php else: ?>
                  <p class="onboard-muted">La présentation apparaîtra ici dès que vous l’aurez écrite.</p>
                <?php endif; ?>
                <div class="onboard-completion">
                  <span>Fiche complétée à <?= $completion ?> %</span>
                  <span class="vitrine-progress onboard-bar"><span style="width: <?= max(6, $completion) ?>%"></span></span>
                </div>
              </div>
            </div>
          <?php endif; ?>

          <?php if ($seeks && $missions !== []): ?>
            <?php $lastMission = $missions[0]; ?>
            <div class="side-card onboard-mission-card">
              <div class="side-kicker">Votre recherche</div>
              <div class="side-title"><?= e((string) ($lastMission['title'] ?? 'Recherche')) ?></div>
              <div class="side-sub"><?= e((string) ($lastMission['category_name'] ?? '')) ?></div>
              <p class="side-brief"><?= e(mb_strimwidth((string) ($lastMission['brief'] ?? ''), 0, 220, '…')) ?></p>
              <?php if (!empty($lastMission['slug'])): ?>
                <a class="dash-card-cta" href="<?= e(url('/missions/' . $lastMission['slug'])) ?>">Voir l’annonce <?= icon('arrow', 14) ?></a>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <?php if ($priorities !== []): ?>
            <h2>Pour aller plus loin</h2>
            <div class="onboard-next">
              <?php foreach (array_slice($priorities, 0, 3) as $item): ?>
                <a class="dash-todo" href="<?= e(url((string) $item['href'])) ?>">
                  <span class="dash-ico dash-ico-accent"><?= icon($item['id'] === 'mission' ? 'file-plus' : ($item['id'] === 'avatar' ? 'id' : 'store'), 18) ?></span>
                  <span>
                    <strong><?= e((string) $item['title']) ?></strong>
                    <em><?= e((string) $item['body']) ?></em>
                  </span>
                  <span class="dash-card-cta"><?= e((string) $item['cta']) ?> <?= icon('arrow', 14) ?></span>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <form class="onboard-actions" method="post" action="<?= e(url('/espace/bienvenue')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="etape" value="apercu">
            <button class="btn-orange" type="submit" name="intent" value="continue">Entrer dans mon espace</button>
            <?php if ($offers && $publicHref !== ''): ?>
              <a class="btn-ghost" href="<?= e(url($publicHref)) ?>">Voir la fiche en public</a>
            <?php endif; ?>
            <?php if ($offers): ?>
              <a class="btn-ghost" href="<?= e(url('/espace/prestations/creer')) ?>">Proposer une prestation</a>
            <?php endif; ?>
            <?php if ($seeks && $missions === []): ?>
              <a class="btn-ghost" href="<?= e(url('/recherche')) ?>">Parcourir l’annuaire</a>
            <?php endif; ?>
          </form>
        </div>
      <?php endif; ?>
    </div>

    <aside class="onboard-aside">
      <?php if ($step === 'identite' || $step === 'vitrine'): ?>
        <div class="side-card onboard-preview" data-onboard-preview>
          <div class="side-kicker"><?= $offers ? 'Aperçu de votre fiche' : 'Votre identité' ?></div>
          <div class="onboard-preview-hero">
            <span data-preview-out-avatar><?= avatar_html(['avatar_url' => $avatarSrc, 'first_name' => $first, 'last_name' => $last], 56, 'avatar') ?></span>
            <div>
              <strong data-preview-out-name><?= e($previewName) ?></strong>
              <em data-preview-out-sub><?php
                $sub = [];
                if ($titleValue !== '') {
                    $sub[] = $titleValue;
                }
                if ($cityValue !== '') {
                    $sub[] = $cityValue;
                }
                echo e($sub !== [] ? implode(' · ', $sub) : 'Le titre et la ville apparaîtront ici');
              ?></em>
            </div>
          </div>
          <div class="chip-row" data-preview-out-trades>
            <?php foreach ($selectedTrades as $trade): ?>
              <span class="chip-static"><?= e((string) $trade) ?></span>
            <?php endforeach; ?>
          </div>
          <p class="side-brief" data-preview-out-pres><?= e($presentationValue !== '' ? $presentationValue : 'Votre présentation s’affiche au fil de la saisie.') ?></p>
          <?php if ($offers): ?>
            <p class="onboard-preview-note">Visible dans l’annuaire dès qu’un métier ou un titre est renseigné.</p>
          <?php endif; ?>
        </div>
      <?php elseif ($step === 'mission'): ?>
        <div class="side-card">
          <div class="side-kicker">Aperçu de l’annonce</div>
          <div class="side-title" data-preview-out-title><?= e($missionTitle !== '' ? $missionTitle : 'Votre titre apparaîtra ici') ?></div>
          <div class="side-sub"><span><?= e($previewName) ?></span> · <span data-preview-out-cat><?= e($missionCat) ?></span></div>
          <p class="side-brief" data-preview-out-brief><?= e($missionBrief !== '' ? $missionBrief : 'Le brief s’affiche au fil de la saisie.') ?></p>
          <div class="side-foot">
            <span>0 candidature</span>
            <strong data-preview-out-budget>Budget à convenir</strong>
          </div>
        </div>
        <?php if (!empty($suggestions)): ?>
          <div class="side-card">
            <div class="side-title-sm">Prestataires qui correspondent</div>
            <?php foreach ($suggestions as $p): ?>
              <a class="suggest-row" href="<?= e(url((string) $p['href'])) ?>">
                <?php if (!empty($p['thumb'])): ?>
                  <img class="avatar avatar-photo" src="<?= e((string) $p['thumb']) ?>" alt="" width="34" height="34">
                <?php else: ?>
                  <span class="avatar" style="<?= e(avatar_style((string) ($p['initials'] ?? 'AD'), 34)) ?>"><?= e((string) ($p['initials'] ?? 'AD')) ?></span>
                <?php endif; ?>
                <span>
                  <strong><?= e((string) $p['title']) ?></strong>
                  <em><?= e((string) $p['subtitle']) ?></em>
                </span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <?php if ($coach['tip'] !== ''): ?>
          <div class="side-card side-card-warm">
            <div class="side-title-sm">Pourquoi c’est utile</div>
            <p><?= e((string) $coach['tip']) ?></p>
          </div>
        <?php endif; ?>
      <?php endif; ?>

      <?php if ($step !== 'apercu' && ($coach['tip'] ?? '') !== ''): ?>
        <div class="side-card side-card-warm">
          <div class="side-title-sm">Pour vous accompagner</div>
          <p><?= e((string) $coach['tip']) ?></p>
        </div>
      <?php endif; ?>

      <form class="onboard-later-form" method="post" action="<?= e(url('/espace/bienvenue')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="etape" value="<?= e($step) ?>">
        <button class="onboard-later" type="submit" name="intent" value="later" formnovalidate>Je termine plus tard, aller à l’espace</button>
      </form>
    </aside>
  </div>
</div>
