<?php
$similar = $similar ?? [];
$categories = $categories ?? [];
$countries = $countries ?? ['France'];
$kinds = $kinds ?? \Adl\Models\Contest::KINDS;
$suggestedEmail = (string) ($suggestedEmail ?? '');
$suggestedName = (string) ($suggestedName ?? '');
$sent = !empty($sent);
$catOld = (string) old('category');
$kindOld = (string) old('kind', 'prix');
$countryOld = (string) old('country', 'France');
?>
<div class="journal-page me-claim-page me-add-page">
  <nav class="search-crumb" aria-label="Fil d'Ariane">
    <a href="<?= e(url('/')) ?>">Accueil</a>
    <span aria-hidden="true"> · </span>
    <a href="<?= e(url('/communaute')) ?>">Communauté</a>
    <span aria-hidden="true"> · </span>
    <a href="<?= e(url('/prix-et-concours')) ?>">Prix et concours</a>
    <span aria-hidden="true"> · </span>
    <span>Proposer un prix</span>
  </nav>

  <div class="me-claim-layout">
    <div>
      <h1>Proposer un prix ou un concours</h1>
      <p class="journal-lead">Un prix littéraire, un concours ou un appel à textes manque dans l’annuaire ? Décrivez-le ci-dessous. L’équipe vérifie chaque proposition avant de la publier.</p>

      <?php if ($sent): ?>
        <div class="flash flash-ok">Proposition envoyée. Nous vous écrivons dès que la fiche est publiée, en général sous deux jours ouvrés.</div>
      <?php endif; ?>
      <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

      <?php if (!$sent): ?>
        <?php if ($similar !== []): ?>
          <section class="me-dup" id="doublons">
            <h2>Ces récompenses existent déjà : est-ce la vôtre ?</h2>
            <p>Si le prix ou le concours figure ci-dessous, inutile d’en créer une seconde fiche.</p>
            <ul class="me-dup-list">
              <?php foreach ($similar as $item): ?>
                <li>
                  <span class="me-dup-main">
                    <strong><?= e((string) ($item['name'] ?? '')) ?></strong>
                    <span><?= e(trim((string) ($item['when'] ?? '') . (!empty($item['place']) ? ' · ' . $item['place'] : ''))) ?></span>
                  </span>
                  <a class="btn-ghost" href="<?= e(url((string) ($item['href'] ?? '/prix-et-concours'))) ?>">Voir la fiche</a>
                </li>
              <?php endforeach; ?>
            </ul>
          </section>
        <?php endif; ?>

        <form class="me-claim-form me-add-form" method="post" action="<?= e(url('/prix-et-concours/ajouter')) ?>">
          <?= csrf_field() ?>
          <?= form_guard_fields('contest-add') ?>

          <h2 class="me-form-title">La récompense</h2>
          <div>
            <label class="field" for="name">Nom du prix ou du concours</label>
            <input class="input" id="name" name="name" required minlength="2" maxlength="255" value="<?= e((string) old('name')) ?>" placeholder="Prix du premier roman…">
          </div>
          <div class="form-grid-2">
            <div>
              <label class="field" for="kind">Type</label>
              <select class="input" id="kind" name="kind">
                <?php foreach ($kinds as $v => $l): ?>
                  <option value="<?= e((string) $v) ?>"<?= (string) $v === $kindOld ? ' selected' : '' ?>><?= e((string) $l) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="field" for="category">Genre <span class="me-optional">(facultatif)</span></label>
              <select class="input" id="category" name="category">
                <option value="">À préciser</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= e((string) $cat) ?>"<?= (string) $cat === $catOld ? ' selected' : '' ?>><?= e((string) $cat) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-grid-3">
            <div>
              <label class="field" for="opens_on">Ouverture des candidatures <span class="me-optional">(facultatif)</span></label>
              <input class="input" id="opens_on" name="opens_on" type="date" value="<?= e((string) old('opens_on')) ?>">
            </div>
            <div>
              <label class="field" for="deadline_on">Date limite de candidature</label>
              <input class="input" id="deadline_on" name="deadline_on" type="date" value="<?= e((string) old('deadline_on')) ?>">
            </div>
            <div>
              <label class="field" for="awarded_on">Proclamation <span class="me-optional">(facultatif)</span></label>
              <input class="input" id="awarded_on" name="awarded_on" type="date" value="<?= e((string) old('awarded_on')) ?>">
            </div>
          </div>
          <div class="form-grid-2">
            <div>
              <label class="field" for="prize">Dotation <span class="me-optional">(facultatif)</span></label>
              <input class="input" id="prize" name="prize" maxlength="255" value="<?= e((string) old('prize')) ?>" placeholder="3 000 €, publication, résidence…">
            </div>
            <div>
              <label class="field" for="entry_fee">Participation <span class="me-optional">(facultatif)</span></label>
              <input class="input" id="entry_fee" name="entry_fee" maxlength="255" value="<?= e((string) old('entry_fee')) ?>" placeholder="Gratuit, 15 €…">
            </div>
          </div>
          <div>
            <label class="field" for="eligibility">Conditions d’éligibilité <span class="me-optional">(facultatif)</span></label>
            <input class="input" id="eligibility" name="eligibility" maxlength="255" value="<?= e((string) old('eligibility')) ?>" placeholder="Premier roman, moins de 35 ans, texte inédit…">
          </div>
          <div class="form-grid-3">
            <div>
              <label class="field" for="city">Ville <span class="me-optional">(facultatif)</span></label>
              <input class="input" id="city" name="city" maxlength="120" value="<?= e((string) old('city')) ?>">
            </div>
            <div>
              <label class="field" for="country">Pays</label>
              <input class="input" id="country" name="country" list="prix-countries" required maxlength="120" value="<?= e($countryOld) ?>">
              <datalist id="prix-countries"><?php foreach ($countries as $n): ?><option value="<?= e((string) $n) ?>"><?php endforeach; ?></datalist>
            </div>
            <div>
              <label class="field" for="venue">Lieu de remise <span class="me-optional">(facultatif)</span></label>
              <input class="input" id="venue" name="venue" maxlength="255" value="<?= e((string) old('venue')) ?>" placeholder="Festival, mairie, salon…">
            </div>
          </div>
          <div class="form-grid-2">
            <div>
              <label class="field" for="organizer">Organisateur <span class="me-optional">(facultatif)</span></label>
              <input class="input" id="organizer" name="organizer" maxlength="255" value="<?= e((string) old('organizer')) ?>">
            </div>
            <div>
              <label class="field" for="website">Site officiel <span class="me-optional">(facultatif)</span></label>
              <input class="input" id="website" name="website" inputmode="url" maxlength="255" value="<?= e((string) old('website')) ?>" placeholder="https://">
            </div>
          </div>
          <div>
            <label class="field" for="description">Présentation <span class="me-optional">(facultatif)</span></label>
            <textarea class="textarea" id="description" name="description" rows="4" maxlength="2000" placeholder="Quelques lignes sur la récompense, son jury, son public."><?= e((string) old('description')) ?></textarea>
          </div>

          <h2 class="me-form-title">Vous</h2>
          <div class="form-grid-2">
            <div>
              <label class="field" for="contact_name">Votre nom</label>
              <input class="input" id="contact_name" name="contact_name" required maxlength="190" value="<?= e((string) old('contact_name', $suggestedName)) ?>">
            </div>
            <div>
              <label class="field" for="contact_email">Votre e-mail</label>
              <input class="input" id="contact_email" name="contact_email" type="email" required maxlength="190" value="<?= e((string) old('contact_email', $suggestedEmail)) ?>">
              <p class="field-help">Pour vous prévenir une fois la fiche publiée.</p>
            </div>
          </div>
          <div>
            <label class="field" for="notes">Un mot pour l’équipe <span class="me-optional">(facultatif)</span></label>
            <textarea class="textarea" id="notes" name="notes" rows="3" maxlength="2000" placeholder="Lien vers le règlement, précisions sur les dates, votre rôle…"><?= e((string) old('notes')) ?></textarea>
          </div>

          <?php if ($similar !== []): ?>
            <label class="search-check me-dup-confirm">
              <input type="checkbox" name="not_duplicate" value="1" required>
              <span>Aucune des récompenses listées plus haut n’est celle-ci : envoyer une nouvelle fiche.</span>
            </label>
          <?php endif; ?>

          <div class="admin-actions">
            <button class="btn-orange" type="submit">Proposer cette récompense</button>
            <a class="btn-ghost" href="<?= e(url('/prix-et-concours')) ?>">Retour à l’annuaire</a>
          </div>
        </form>
      <?php else: ?>
        <p><a class="btn-navy" href="<?= e(url('/prix-et-concours')) ?>">Retour à l’annuaire</a></p>
      <?php endif; ?>
    </div>

    <aside class="me-side">
      <div class="side-card">
        <div class="side-kicker">Comment ça se passe</div>
        <ol class="me-steps">
          <li><strong>Vous décrivez la récompense</strong><span>nom, dates, dotation, conditions, et un site officiel si vous en avez un.</span></li>
          <li><strong>L’équipe vérifie</strong><span>existence de la récompense, dates, absence de doublon. Nous pouvons vous écrire pour confirmer.</span></li>
          <li><strong>La fiche est publiée</strong><span>dans l’annuaire, visible par tous. Vous recevez un e-mail.</span></li>
        </ol>
      </div>
      <div class="side-card">
        <div class="side-kicker">Déjà dans l’annuaire ?</div>
        <p class="me-side-text"><a href="<?= e(url('/prix-et-concours')) ?>">Cherchez d’abord le prix</a>. S’il y figure déjà, inutile de le proposer une seconde fois. Une date ou un lien à corriger : <a href="<?= e(url('/contact')) ?>">écrivez-nous</a>.</p>
      </div>
    </aside>
  </div>
</div>
