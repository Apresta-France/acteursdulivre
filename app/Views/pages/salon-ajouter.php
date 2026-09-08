<?php
$similar = $similar ?? [];
$categories = $categories ?? [];
$countries = $countries ?? ['France'];
$suggestedEmail = (string) ($suggestedEmail ?? '');
$suggestedName = (string) ($suggestedName ?? '');
$sent = !empty($sent);
$catOld = (string) old('category');
$countryOld = (string) old('country', 'France');
?>
<div class="journal-page me-claim-page me-add-page">
  <nav class="search-crumb" aria-label="Fil d'Ariane">
    <a href="<?= e(url('/')) ?>">Accueil</a>
    <span aria-hidden="true"> · </span>
    <a href="<?= e(url('/communaute')) ?>">Communauté</a>
    <span aria-hidden="true"> · </span>
    <a href="<?= e(url('/salons')) ?>">Agenda</a>
    <span aria-hidden="true"> · </span>
    <span>Ajouter un salon</span>
  </nav>

  <div class="me-claim-layout">
    <div>
      <h1>Ajouter un salon</h1>
      <p class="journal-lead">Votre salon, festival ou foire du livre n’est pas encore dans l’agenda ? Décrivez-le ci-dessous. L’équipe vérifie chaque proposition avant de la publier.</p>

      <?php if ($sent): ?>
        <div class="flash flash-ok">Proposition envoyée. Nous vous écrivons dès que le salon est publié, en général sous deux jours ouvrés.</div>
      <?php endif; ?>
      <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

      <?php if (!$sent): ?>
        <?php if ($similar !== []): ?>
          <section class="me-dup" id="doublons">
            <h2>Ces salons existent déjà : est-ce le vôtre ?</h2>
            <p>Si votre manifestation figure ci-dessous, inutile d’en créer une seconde.</p>
            <ul class="me-dup-list">
              <?php foreach ($similar as $item): ?>
                <li>
                  <span class="me-dup-main">
                    <strong><?= e((string) ($item['name'] ?? '')) ?></strong>
                    <span><?= e(trim((string) ($item['when'] ?? '') . (!empty($item['place']) ? ' · ' . $item['place'] : ''))) ?></span>
                  </span>
                  <a class="btn-ghost" href="<?= e(url((string) ($item['href'] ?? '/salons'))) ?>">Voir la fiche</a>
                </li>
              <?php endforeach; ?>
            </ul>
          </section>
        <?php endif; ?>

        <form class="me-claim-form me-add-form" method="post" action="<?= e(url('/salons/ajouter')) ?>">
          <?= csrf_field() ?>

          <h2 class="me-form-title">Le salon</h2>
          <div>
            <label class="field" for="name">Nom de la manifestation</label>
            <input class="input" id="name" name="name" required minlength="2" maxlength="255" value="<?= e((string) old('name')) ?>" placeholder="Salon du livre de…">
          </div>
          <div class="form-grid-3">
            <div>
              <label class="field" for="city">Ville</label>
              <input class="input" id="city" name="city" required minlength="2" maxlength="120" value="<?= e((string) old('city')) ?>">
            </div>
            <div>
              <label class="field" for="country">Pays</label>
              <input class="input" id="country" name="country" list="salon-countries" required maxlength="120" value="<?= e($countryOld) ?>">
              <datalist id="salon-countries"><?php foreach ($countries as $n): ?><option value="<?= e((string) $n) ?>"><?php endforeach; ?></datalist>
            </div>
            <div>
              <label class="field" for="region">Région <span class="me-optional">(facultatif)</span></label>
              <input class="input" id="region" name="region" maxlength="120" value="<?= e((string) old('region')) ?>">
            </div>
          </div>
          <div class="form-grid-2">
            <div>
              <label class="field" for="starts_on">Date de début</label>
              <input class="input" id="starts_on" name="starts_on" type="date" required value="<?= e((string) old('starts_on')) ?>">
            </div>
            <div>
              <label class="field" for="ends_on">Date de fin <span class="me-optional">(facultatif)</span></label>
              <input class="input" id="ends_on" name="ends_on" type="date" value="<?= e((string) old('ends_on')) ?>">
            </div>
          </div>
          <div class="form-grid-2">
            <div>
              <label class="field" for="category">Catégorie</label>
              <select class="input" id="category" name="category">
                <option value="">À préciser</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= e((string) $cat) ?>"<?= (string) $cat === $catOld ? ' selected' : '' ?>><?= e((string) $cat) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="field" for="venue">Lieu <span class="me-optional">(facultatif)</span></label>
              <input class="input" id="venue" name="venue" maxlength="255" value="<?= e((string) old('venue')) ?>" placeholder="Halle, médiathèque, parc…">
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
          <div class="form-grid-2">
            <div>
              <label class="field" for="ticket">Entrée <span class="me-optional">(facultatif)</span></label>
              <input class="input" id="ticket" name="ticket" maxlength="255" value="<?= e((string) old('ticket')) ?>" placeholder="Libre, 5 €, sur invitation…">
            </div>
            <div>
              <label class="field" for="audience">Public <span class="me-optional">(facultatif)</span></label>
              <input class="input" id="audience" name="audience" maxlength="190" value="<?= e((string) old('audience')) ?>" placeholder="Tout public, professionnels, jeunesse…">
            </div>
          </div>
          <div>
            <label class="field" for="description">Présentation <span class="me-optional">(facultatif)</span></label>
            <textarea class="textarea" id="description" name="description" rows="4" maxlength="2000" placeholder="Quelques lignes sur la manifestation, son édition, son public."><?= e((string) old('description')) ?></textarea>
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
              <p class="field-help">Pour vous prévenir une fois le salon publié.</p>
            </div>
          </div>
          <div>
            <label class="field" for="notes">Un mot pour l’équipe <span class="me-optional">(facultatif)</span></label>
            <textarea class="textarea" id="notes" name="notes" rows="3" maxlength="2000" placeholder="Lien vers le programme, précisions sur les dates, votre rôle…"><?= e((string) old('notes')) ?></textarea>
          </div>

          <?php if ($similar !== []): ?>
            <label class="search-check me-dup-confirm">
              <input type="checkbox" name="not_duplicate" value="1" required>
              <span>Aucun des salons listés plus haut n’est celui-ci : envoyer une nouvelle fiche.</span>
            </label>
          <?php endif; ?>

          <div class="admin-actions">
            <button class="btn-orange" type="submit">Proposer ce salon</button>
            <a class="btn-ghost" href="<?= e(url('/salons')) ?>">Retour à l’agenda</a>
          </div>
        </form>
      <?php else: ?>
        <p><a class="btn-navy" href="<?= e(url('/salons')) ?>">Retour à l’agenda</a></p>
      <?php endif; ?>
    </div>

    <aside class="me-side">
      <div class="side-card">
        <div class="side-kicker">Comment ça se passe</div>
        <ol class="me-steps">
          <li><strong>Vous décrivez le salon</strong><span>nom, dates, ville, et un site officiel si vous en avez un.</span></li>
          <li><strong>L’équipe vérifie</strong><span>existence de la manifestation, dates, absence de doublon. Nous pouvons vous écrire pour confirmer.</span></li>
          <li><strong>La fiche est publiée</strong><span>dans l’agenda, visible par tous. Vous recevez un e-mail.</span></li>
        </ol>
      </div>
      <div class="side-card">
        <div class="side-kicker">Déjà dans l’agenda ?</div>
        <p class="me-side-text"><a href="<?= e(url('/salons')) ?>">Cherchez d’abord le salon</a>. S’il y figure déjà, inutile de le proposer une seconde fois. Une date ou un lien à corriger : <a href="<?= e(url('/contact')) ?>">écrivez-nous</a>.</p>
      </div>
    </aside>
  </div>
</div>
