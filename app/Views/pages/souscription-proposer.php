<?php
use Adl\Models\Souscription;

$sent = !empty($sent);
$suggestedEmail = (string) ($suggestedEmail ?? '');
$suggestedName = (string) ($suggestedName ?? '');
$kindOld = (string) old('kind', 'souscription');
?>
<div class="journal-page me-claim-page me-add-page">
  <nav class="search-crumb" aria-label="Fil d'Ariane">
    <a href="<?= e(url('/')) ?>">Accueil</a>
    <span aria-hidden="true"> · </span>
    <a href="<?= e(url('/communaute')) ?>">Communauté</a>
    <span aria-hidden="true"> · </span>
    <a href="<?= e(url('/souscriptions')) ?>">Souscriptions</a>
    <span aria-hidden="true"> · </span>
    <span>Proposer un livre</span>
  </nav>

  <div class="me-claim-layout">
    <div>
      <h1>Proposer un livre</h1>
      <p class="journal-lead">Une souscription, une prévente, une vente ou une mise en avant sponsorisée, déjà ouverte ailleurs. L’équipe vérifie la fiche avant de la publier. Le paiement ne passe pas par ici.</p>

      <?php if ($sent): ?>
        <div class="flash flash-ok">Proposition envoyée. Le livre n’est pas encore visible : nous vous écrivons après vérification.</div>
      <?php endif; ?>
      <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

      <?php if (!$sent): ?>
        <form class="me-claim-form me-add-form" method="post" action="<?= e(url('/souscriptions/proposer')) ?>">
          <?= csrf_field() ?>
          <?= form_guard_fields('souscription-add') ?>

          <h2 class="me-form-title">Le livre</h2>
          <div>
            <label class="field" for="title">Titre</label>
            <input class="input" id="title" name="title" required minlength="2" maxlength="190" value="<?= e((string) old('title')) ?>">
          </div>
          <div class="form-grid-2">
            <div>
              <label class="field" for="kind">Ce qui est en cours</label>
              <select class="input" id="kind" name="kind">
                <?php foreach (Souscription::KINDS as $key => $label): ?>
                  <option value="<?= e($key) ?>"<?= $key === $kindOld ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
              <p class="field-help">Sponsorisé : la fiche publiée le dira clairement.</p>
            </div>
            <div>
              <label class="field" for="genre">Genre <span class="me-optional">(facultatif)</span></label>
              <input class="input" id="genre" name="genre" maxlength="120" value="<?= e((string) old('genre')) ?>" placeholder="Roman, essai, album…">
            </div>
          </div>
          <div class="form-grid-2">
            <div>
              <label class="field" for="bearer">Qui le porte</label>
              <input class="input" id="bearer" name="bearer" required maxlength="190" value="<?= e((string) old('bearer')) ?>" placeholder="Nom de la personne, de l’atelier ou de la maison">
            </div>
            <div>
              <label class="field" for="bearer_role">Rôle <span class="me-optional">(facultatif)</span></label>
              <input class="input" id="bearer_role" name="bearer_role" maxlength="120" value="<?= e((string) old('bearer_role')) ?>" placeholder="Autrice, relieur, maison…">
            </div>
          </div>
          <div>
            <label class="field" for="pitch">Chapô</label>
            <textarea class="textarea" id="pitch" name="pitch" required minlength="40" maxlength="600" rows="4"><?= e((string) old('pitch')) ?></textarea>
            <p class="field-help">Quelques phrases : de quoi il s’agit, et où en est le livre. 40 caractères minimum.</p>
          </div>

          <h2 class="me-form-title">Ailleurs</h2>
          <div class="form-grid-2">
            <div>
              <label class="field" for="host">Où c’est ouvert</label>
              <input class="input" id="host" name="host" required maxlength="190" value="<?= e((string) old('host')) ?>" placeholder="Ulule, boutique de l’autrice…">
            </div>
            <div>
              <label class="field" for="closes">Date de clôture</label>
              <input class="input" id="closes" name="closes" type="date" value="<?= e((string) old('closes')) ?>">
              <p class="field-help">Obligatoire, sauf pour une annonce sponsorisée sans date de fin.</p>
            </div>
          </div>
          <div>
            <label class="field" for="external_url">Lien</label>
            <input class="input" id="external_url" name="external_url" type="url" required maxlength="500" value="<?= e((string) old('external_url')) ?>" placeholder="https://">
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
            </div>
          </div>
          <div>
            <label class="field" for="note">Message pour l’équipe <span class="me-optional">(facultatif, non publié)</span></label>
            <textarea class="textarea" id="note" name="note" rows="3" maxlength="2000"><?= e((string) old('note')) ?></textarea>
          </div>

          <div class="admin-actions">
            <button class="btn-orange" type="submit">Envoyer la proposition</button>
            <a class="btn-ghost" href="<?= e(url('/souscriptions')) ?>">Retour aux annonces</a>
          </div>
        </form>
      <?php else: ?>
        <p><a class="btn-navy" href="<?= e(url('/souscriptions')) ?>">Retour aux annonces</a></p>
      <?php endif; ?>
    </div>

    <aside class="me-side">
      <div class="side-card">
        <div class="side-kicker">Comment ça se passe</div>
        <ol class="me-steps">
          <li><strong>Vous décrivez le livre</strong><span>titre, porteur, et le lien de la campagne déjà ouverte.</span></li>
          <li><strong>L’équipe vérifie</strong><span>que le lien existe, que l’annonce est encore ouverte, et que le sponsoring est assumé s’il y en a un.</span></li>
          <li><strong>La fiche est publiée</strong><span>le temps de la campagne. Le bouton ramène sur le site qui l’héberge.</span></li>
        </ol>
      </div>
      <div class="side-card">
        <div class="side-kicker">Déjà annoncé ?</div>
        <p class="me-side-text"><a href="<?= e(url('/souscriptions')) ?>">Regardez d’abord la liste</a>. Un même lien ne peut pas être proposé deux fois.</p>
      </div>
    </aside>
  </div>
</div>
