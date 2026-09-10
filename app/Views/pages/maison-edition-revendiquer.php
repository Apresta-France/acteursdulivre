<?php
$p = $publisher ?? null;
if (!$p) {
    not_found();
}
$pendingClaim = $pendingClaim ?? null;
$alreadyOwned = !empty($alreadyOwned);
$isOwner = !empty($isOwner);
$suggested = (string) ($suggestedEmail ?? '');
$hostHint = $p['website_host'] !== '' ? $p['website_host'] : '';
?>
<div class="journal-page me-claim-page">
  <nav class="search-crumb" aria-label="Fil d'Ariane">
    <a href="<?= e(url('/')) ?>">Accueil</a>
    <span aria-hidden="true"> · </span>
    <a href="<?= e(url('/communaute')) ?>">Communauté</a>
    <span aria-hidden="true"> · </span>
    <a href="<?= e(url('/maisons-edition')) ?>">Maisons d'édition</a>
    <span aria-hidden="true"> · </span>
    <a href="<?= e(url($p['href'])) ?>"><?= e($p['name']) ?></a>
    <span aria-hidden="true"> · </span>
    <span>Revendiquer</span>
  </nav>

  <div class="me-claim-layout">
    <div>
      <h1>Revendiquer la fiche <span><?= e($p['name']) ?></span></h1>
      <p class="journal-lead">Vous représentez cette maison ? Prenez la main sur sa fiche : vous pourrez corriger et compléter les informations, ajouter un logo, préciser votre politique de manuscrits et recevoir les messages des membres. Chaque demande est vérifiée par l'équipe avant attribution.</p>

      <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

      <?php if ($isOwner): ?>
        <div class="form-notice"><strong>Vous gérez déjà cette fiche.</strong><p><a href="<?= e(url('/espace/maison-edition')) ?>">Ouvrir mon espace maison d'édition</a>.</p></div>
      <?php elseif ($alreadyOwned): ?>
        <div class="form-notice"><strong>Cette fiche a déjà été attribuée.</strong><p>Si vous pensez qu'il s'agit d'une erreur ou d'un homonyme, <a href="<?= e(url('/contact')) ?>">écrivez-nous</a> en précisant votre fonction et une adresse sur le domaine de la maison.</p></div>
      <?php elseif ($pendingClaim): ?>
        <div class="form-notice"><strong>Votre demande est en cours d'examen.</strong><p>Envoyée le <?= e(admin_date((string) $pendingClaim['created_at'])) ?>. Vous serez prévenu par e-mail dès qu'elle sera traitée, en général sous deux jours ouvrés.</p></div>
      <?php else: ?>
        <form class="me-claim-form" method="post" action="<?= e(url($p['href'] . '/revendiquer')) ?>">
          <?= csrf_field() ?>
          <?= form_guard_fields('publisher-claim') ?>
          <div class="form-grid-2">
            <div>
              <label class="field" for="role_title">Votre fonction dans la maison</label>
              <input class="input" id="role_title" name="role_title" required maxlength="120" value="<?= e((string) old('role_title')) ?>" placeholder="Éditrice, directeur commercial, responsable communication…">
            </div>
            <div>
              <label class="field" for="company_email">E-mail professionnel</label>
              <input class="input" id="company_email" name="company_email" type="email" required maxlength="190" value="<?= e((string) old('company_email', $suggested)) ?>" placeholder="<?= e($hostHint !== '' ? 'prenom@' . $hostHint : 'prenom@maison.fr') ?>">
              <p class="field-help"><?= $hostHint !== '' ? 'Une adresse sur le domaine <strong>' . e($hostHint) . '</strong> accélère la validation.' : 'Une adresse sur le domaine de la maison accélère la validation.' ?></p>
            </div>
          </div>
          <div>
            <label class="field" for="phone">Téléphone <span class="me-optional">(facultatif)</span></label>
            <input class="input" id="phone" name="phone" maxlength="40" value="<?= e((string) old('phone')) ?>" placeholder="Pour une vérification rapide si besoin">
          </div>
          <div>
            <label class="field" for="message">Votre lien avec la maison</label>
            <textarea class="textarea" id="message" name="message" rows="5" required minlength="20" maxlength="2000" placeholder="Décrivez votre rôle, depuis quand vous travaillez avec la maison, et ce que vous souhaitez mettre à jour sur la fiche."><?= e((string) old('message')) ?></textarea>
          </div>
          <label class="search-check">
            <input type="checkbox" name="attest" value="1" required>
            <span>J'atteste être habilité à représenter <?= e($p['name']) ?> et à gérer sa présence sur acteursdulivre.fr. Une fausse déclaration entraîne la fermeture du compte.</span>
          </label>
          <div class="admin-actions">
            <button class="btn-orange" type="submit">Envoyer la demande</button>
            <a class="btn-ghost" href="<?= e(url($p['href'])) ?>">Retour à la fiche</a>
          </div>
        </form>
      <?php endif; ?>
    </div>

    <aside class="me-side">
      <div class="side-card">
        <div class="side-kicker">Comment ça se passe</div>
        <ol class="me-steps">
          <li><strong>Vous envoyez la demande</strong><span>avec votre fonction et un e-mail professionnel.</span></li>
          <li><strong>L'équipe vérifie</strong><span>domaine de l'e-mail, site officiel, cohérence des informations. Nous pouvons vous écrire pour confirmer.</span></li>
          <li><strong>La fiche vous est attribuée</strong><span>elle affiche « Fiche gérée par la maison » et vous la modifiez depuis votre espace.</span></li>
        </ol>
      </div>
      <div class="side-card">
        <div class="side-kicker">La fiche actuelle</div>
        <dl class="me-facts">
          <div><dt>Nom</dt><dd><?= e($p['name']) ?></dd></div>
          <?php if ($p['location_label'] !== ''): ?><div><dt>Siège</dt><dd><?= e($p['location_label']) ?></dd></div><?php endif; ?>
          <?php if ($hostHint !== ''): ?><div><dt>Site connu</dt><dd><?= e($hostHint) ?></dd></div><?php endif; ?>
          <div><dt>Taille</dt><dd><?= e($p['size_label']) ?></dd></div>
        </dl>
      </div>
    </aside>
  </div>
</div>
