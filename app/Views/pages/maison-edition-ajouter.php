<?php

use Adl\Models\Publisher;

$owned = $owned ?? null;
$pendingCreation = $pendingCreation ?? null;
$similar = $similar ?? [];
$sizes = $sizes ?? Publisher::SIZES;
$typologies = $typologies ?? Publisher::TYPOLOGIES;
$countryNames = array_map(static fn (array $c): string => (string) $c['name'], $countries ?? []);
sort($countryNames);
$suggested = (string) ($suggestedEmail ?? '');
$sizeOld = (string) old('size_key', 'pme');
$typoOld = (string) old('typology_key', 'specialise');
?>
<div class="journal-page me-claim-page me-add-page">
  <nav class="search-crumb" aria-label="Fil d'Ariane">
    <a href="<?= e(url('/')) ?>">Accueil</a>
    <span aria-hidden="true"> · </span>
    <a href="<?= e(url('/communaute')) ?>">Communauté</a>
    <span aria-hidden="true"> · </span>
    <a href="<?= e(url('/maisons-edition')) ?>">Maisons d'édition</a>
    <span aria-hidden="true"> · </span>
    <span>Ajouter ma maison</span>
  </nav>

  <div class="me-claim-layout">
    <div>
      <h1>Ajouter ma maison d'édition</h1>
      <p class="journal-lead">Votre maison n'est pas encore dans l'annuaire ? Décrivez-la ci-dessous : la fiche est créée immédiatement, rattachée à votre compte, et publiée après vérification par l'équipe. Vous la gérez ensuite depuis votre espace.</p>

      <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

      <?php if ($owned): ?>
        <div class="form-notice"><strong>Vous gérez déjà la fiche « <?= e((string) $owned['name']) ?> ».</strong><p>Un compte gère une seule maison. <a href="<?= e(url('/espace/maison-edition')) ?>">Ouvrir mon espace maison d'édition</a> · pour une seconde maison, <a href="<?= e(url('/contact')) ?>">écrivez-nous</a>.</p></div>
      <?php elseif ($pendingCreation): ?>
        <div class="form-notice"><strong>Votre proposition « <?= e((string) $pendingCreation['publisher_name']) ?> » est en cours d'examen.</strong><p>Envoyée le <?= e((string) $pendingCreation['when']) ?>. Vous pouvez déjà <a href="<?= e(url('/espace/maison-edition')) ?>">compléter la fiche</a> ; elle sera publiée dès validation.</p></div>
      <?php else: ?>

        <?php if ($similar !== []): ?>
          <section class="me-dup" id="doublons">
            <h2>Ces maisons existent déjà : est-ce la vôtre ?</h2>
            <p>Si votre maison figure ci-dessous, revendiquez sa fiche au lieu d'en créer une seconde.</p>
            <ul class="me-dup-list">
              <?php foreach ($similar as $s): ?>
                <li>
                  <span class="me-dup-logo"><?php if ($s['logo_src'] !== ''): ?><img src="<?= e($s['logo_src']) ?>" alt="" width="36" height="36"><?php else: ?><?= e($s['initials']) ?><?php endif; ?></span>
                  <span class="me-dup-main">
                    <strong><?= e($s['name']) ?></strong>
                    <span><?= e($s['location_label'] !== '' ? $s['location_label'] : 'Lieu non renseigné') ?><?= $s['is_claimed'] ? ' · déjà gérée par la maison' : '' ?></span>
                  </span>
                  <?php if (Publisher::isPublic($s)): ?>
                    <a class="btn-ghost" href="<?= e(url($s['href'] . '/revendiquer')) ?>">C'est ma maison</a>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          </section>
        <?php endif; ?>

        <form class="me-claim-form me-add-form" method="post" action="<?= e(url('/maisons-edition/ajouter')) ?>" enctype="multipart/form-data">
          <?= csrf_field() ?>

          <h2 class="me-form-title">La maison</h2>
          <div class="form-grid-2">
            <div>
              <label class="field" for="name">Nom de la maison</label>
              <input class="input" id="name" name="name" required minlength="2" maxlength="190" value="<?= e((string) old('name')) ?>" placeholder="Éditions de l'Aube">
            </div>
            <div>
              <label class="field" for="founded">Année de fondation <span class="me-optional">(facultatif)</span></label>
              <input class="input" id="founded" name="founded" maxlength="120" value="<?= e((string) old('founded')) ?>" placeholder="2015">
            </div>
          </div>
          <div class="form-grid-3">
            <div>
              <label class="field" for="country">Pays du siège</label>
              <input class="input" id="country" name="country" list="me-countries" required maxlength="120" value="<?= e((string) old('country', 'France')) ?>">
              <datalist id="me-countries"><?php foreach ($countryNames as $n): ?><option value="<?= e($n) ?>"><?php endforeach; ?></datalist>
            </div>
            <div>
              <label class="field" for="city">Ville</label>
              <input class="input" id="city" name="city" required maxlength="120" value="<?= e((string) old('city')) ?>">
            </div>
            <div>
              <label class="field" for="parent_group">Groupe ou actionnariat</label>
              <input class="input" id="parent_group" name="parent_group" maxlength="190" value="<?= e((string) old('parent_group', 'Indépendant')) ?>" placeholder="Indépendant, Madrigall, Editis…">
            </div>
          </div>
          <div class="form-grid-2">
            <div>
              <label class="field" for="size_key">Taille de la structure</label>
              <select class="input" id="size_key" name="size_key">
                <?php foreach ($sizes as $k => $l): ?><option value="<?= e($k) ?>"<?= $k === $sizeOld ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="field" for="typology_key">Ligne éditoriale</label>
              <select class="input" id="typology_key" name="typology_key">
                <?php foreach ($typologies as $k => $l): ?><option value="<?= e($k) ?>"<?= $k === $typoOld ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
              </select>
            </div>
          </div>
          <div>
            <label class="field" for="genres">Genres et domaines publiés</label>
            <input class="input" id="genres" name="genres" required maxlength="600" value="<?= e((string) old('genres')) ?>" placeholder="Littérature française, Jeunesse, BD, Essais…">
            <p class="field-help">Séparez par des virgules, 20 au maximum. Ils servent de filtres dans l'annuaire.</p>
          </div>
          <div>
            <label class="field" for="description">Présentation</label>
            <textarea class="textarea" id="description" name="description" rows="6" required minlength="40" maxlength="1200" placeholder="Votre ligne éditoriale, vos collections, vos auteurs emblématiques, ce qui vous distingue."><?= e((string) old('description')) ?></textarea>
            <p class="field-help">De 40 à 1 200 caractères. Les premières phrases servent de résumé dans l'annuaire et pour les moteurs de recherche.</p>
          </div>
          <div>
            <label class="field" for="submissions_note">Envoi de manuscrits <span class="me-optional">(facultatif)</span></label>
            <textarea class="textarea" id="submissions_note" name="submissions_note" rows="2" maxlength="600" placeholder="Acceptez-vous les manuscrits ? Par quel canal, dans quels genres, avec quel délai de réponse ?"><?= e((string) old('submissions_note')) ?></textarea>
          </div>
          <div class="form-grid-2">
            <div>
              <label class="field" for="logo">Logo <span class="me-optional">(facultatif)</span></label>
              <input class="input" id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp">
              <p class="field-help">JPG, PNG ou WebP, 2 Mo maximum, de préférence carré.</p>
            </div>
            <div>
              <label class="field" for="website">Site web</label>
              <input class="input" id="website" name="website" inputmode="url" maxlength="255" value="<?= e((string) old('website')) ?>" placeholder="https://">
              <p class="field-help">Un site officiel accélère la vérification.</p>
            </div>
          </div>

          <h2 class="me-form-title">Coordonnées de la maison</h2>
          <p class="field-help" style="margin-top: -6px;">Réservées aux membres connectés, jamais exposées aux robots.</p>
          <div class="form-grid-3">
            <div>
              <label class="field" for="contact_email">E-mail de contact</label>
              <input class="input" id="contact_email" name="contact_email" type="email" maxlength="190" value="<?= e((string) old('contact_email')) ?>">
            </div>
            <div>
              <label class="field" for="contact_phone">Téléphone</label>
              <input class="input" id="contact_phone" name="contact_phone" maxlength="40" value="<?= e((string) old('contact_phone')) ?>">
            </div>
            <div>
              <label class="field" for="contact_address">Adresse postale</label>
              <input class="input" id="contact_address" name="contact_address" maxlength="255" value="<?= e((string) old('contact_address')) ?>">
            </div>
          </div>

          <h2 class="me-form-title">Vous</h2>
          <div class="form-grid-2">
            <div>
              <label class="field" for="role_title">Votre fonction dans la maison</label>
              <input class="input" id="role_title" name="role_title" required maxlength="120" value="<?= e((string) old('role_title')) ?>" placeholder="Fondatrice, éditeur, responsable communication…">
            </div>
            <div>
              <label class="field" for="company_email">E-mail professionnel</label>
              <input class="input" id="company_email" name="company_email" type="email" required maxlength="190" value="<?= e((string) old('company_email', $suggested)) ?>" placeholder="prenom@maison.fr">
              <p class="field-help">Une adresse sur le domaine du site de la maison accélère la validation.</p>
            </div>
          </div>
          <div>
            <label class="field" for="phone">Téléphone direct <span class="me-optional">(facultatif)</span></label>
            <input class="input" id="phone" name="phone" maxlength="40" value="<?= e((string) old('phone')) ?>" placeholder="Pour une vérification rapide si besoin">
          </div>
          <div>
            <label class="field" for="message">Quelques mots sur la maison et votre rôle</label>
            <textarea class="textarea" id="message" name="message" rows="4" required minlength="20" maxlength="2000" placeholder="Depuis quand la maison existe, combien de titres au catalogue, votre rôle… Ces précisions restent internes à l'équipe."><?= e((string) old('message')) ?></textarea>
          </div>

          <?php if ($similar !== []): ?>
            <label class="search-check me-dup-confirm">
              <input type="checkbox" name="not_duplicate" value="1" required>
              <span>Aucune des maisons listées plus haut n'est la mienne : créer une nouvelle fiche.</span>
            </label>
          <?php endif; ?>
          <label class="search-check">
            <input type="checkbox" name="attest" value="1" required>
            <span>J'atteste être habilité à représenter cette maison et à gérer sa présence sur acteursdulivre.fr. Une fausse déclaration entraîne la fermeture du compte.</span>
          </label>
          <div class="admin-actions">
            <button class="btn-orange" type="submit">Proposer ma maison</button>
            <a class="btn-ghost" href="<?= e(url('/maisons-edition')) ?>">Retour à l'annuaire</a>
          </div>
        </form>
      <?php endif; ?>
    </div>

    <aside class="me-side">
      <div class="side-card">
        <div class="side-kicker">Comment ça se passe</div>
        <ol class="me-steps">
          <li><strong>Vous décrivez la maison</strong><span>et votre fonction, avec un e-mail professionnel.</span></li>
          <li><strong>La fiche est créée, masquée</strong><span>et rattachée à votre compte : vous pouvez la compléter tout de suite depuis votre espace.</span></li>
          <li><strong>L'équipe vérifie</strong><span>site officiel, domaine de l'e-mail, absence de doublon. Nous pouvons vous écrire pour confirmer.</span></li>
          <li><strong>La fiche est publiée</strong><span>avec le badge « Fiche gérée par la maison ». Les membres connectés peuvent vous contacter.</span></li>
        </ol>
      </div>
      <div class="side-card">
        <div class="side-kicker">Déjà dans l'annuaire ?</div>
        <p class="me-side-text">Plus de 600 maisons sont déjà recensées. <a href="<?= e(url('/maisons-edition')) ?>">Cherchez la vôtre</a> puis cliquez sur « Revendiquer la fiche » : c'est plus rapide et cela évite les doublons.</p>
      </div>
    </aside>
  </div>
</div>
