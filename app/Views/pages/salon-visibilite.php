<?php
$featured = $featured ?? [];
?>
<div class="journal-page me-claim-page me-add-page">
  <nav class="search-crumb" aria-label="Fil d'Ariane">
    <a href="<?= e(url('/')) ?>">Accueil</a>
    <span aria-hidden="true"> · </span>
    <a href="<?= e(url('/communaute')) ?>">Communauté</a>
    <span aria-hidden="true"> · </span>
    <a href="<?= e(url('/salons')) ?>">Agenda</a>
    <span aria-hidden="true"> · </span>
    <span>Visibilité pour votre salon</span>
  </nav>

  <div class="me-claim-layout">
    <div>
      <h1>Faites connaître votre salon</h1>
      <p class="journal-lead">L’agenda d’acteursdulivre.fr est consulté par des auteurs, des éditeurs et des prestataires du livre — ceux qui choisissent où exposer. Voici ce que nous proposons aux organisateurs.</p>

      <section class="me-panel">
        <h2 class="me-form-title">Gratuit : prenez la main sur votre fiche</h2>
        <ul class="me-check-list">
          <li><strong>Votre fiche, vos informations</strong> — dates, lieu, site, présentation, programme, liste d’exposants, logo : vous les mettez à jour vous-même, sans passer par nous.</li>
          <li><strong>Dates confirmées par l’organisateur</strong> — un badge distinct de notre propre vérification, qui rassure lecteurs et exposants.</li>
          <li><strong>Votre voix sur le forum</strong> — vous répondez aux questions dans le sujet rattaché à votre salon, identifié comme « Organisateur ».</li>
        </ul>
        <p class="me-note">La revendication est gratuite et vérifiée à la main. Ouvrez la fiche de votre salon dans <a href="<?= e(url('/salons')) ?>">l’agenda</a> et cliquez « Gérer ma fiche ». Votre salon n’y figure pas encore ? <a href="<?= e(url('/salons/ajouter')) ?>">Proposez-le</a>, c’est gratuit aussi.</p>
      </section>

      <section class="me-panel">
        <h2 class="me-form-title">Sur devis : la mise en avant « Partenaire »</h2>
        <ul class="me-check-list">
          <li><strong>Tête d’agenda</strong> — votre édition épinglée en haut de son mois dans <a href="<?= e(url('/salons')) ?>">l’agenda</a> et sur les pages région, pays, genre qui la concernent, avec le badge « Partenaire ».</li>
          <li><strong>Sidebar et accueil</strong> — présence dans le bloc « À venir » des autres fiches et une carte « Salon partenaire » sur l’accueil pendant la période choisie.</li>
          <li><strong>Éditorial</strong> — un article de présentation dans le journal et un encart dans la newsletter, clairement indiqués comme du contenu partenaire.</li>
        </ul>
        <p class="me-note">La mise en avant se vend <strong>par édition</strong> : elle s’éteint à la date choisie ensemble, sans reconduction tacite. Les placements payants sont toujours signalés par le badge « Partenaire ». Écrivez-nous pour un devis adapté à la taille de votre manifestation — beaucoup sont des associations ou des collectivités, nous en tenons compte.</p>
      </section>

      <?php if ($featured !== []): ?>
        <section class="me-panel">
          <h2 class="me-form-title">Les partenaires en ce moment</h2>
          <ul class="me-check-list">
            <?php foreach ($featured as $item): ?>
              <li>
                <a href="<?= e(url((string) $item['href'])) ?>"><strong><?= e((string) $item['name']) ?></strong></a>
                <?php if (!empty($item['dates_label'])): ?> — <?= e((string) $item['dates_label']) ?><?php endif; ?>
                <?php if (!empty($item['place'])): ?>, <?= e((string) $item['place']) ?><?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>
      <?php endif; ?>

      <div class="admin-actions">
        <a class="btn-orange" href="<?= e(url('/contact')) ?>">Demander un devis</a>
        <a class="btn-ghost" href="<?= e(url('/salons')) ?>">Retour à l’agenda</a>
      </div>
    </div>

    <aside class="me-side">
      <div class="side-card">
        <div class="side-kicker">Comment ça se passe</div>
        <ol class="me-steps">
          <li><strong>Vous nous écrivez</strong><span>via la page contact — indiquez la manifestation et l’édition visée.</span></li>
          <li><strong>On convient du pack</strong><span>période de mise en avant, article, newsletter. Vous recevez une facture d’EDITIONS TESSERACT.</span></li>
          <li><strong>C’est en ligne</strong><span>l’équipe active la mise en avant après règlement. Elle s’arrête seule à la date convenue.</span></li>
        </ol>
      </div>
      <div class="side-card">
        <div class="side-kicker">Transparence</div>
        <p class="me-side-text">Un placement payant reste identifiable : le badge « Partenaire » l’accompagne partout. L’agenda garde son tri chronologique pour les fiches non partenaires.</p>
      </div>
      <div class="side-card">
        <div class="side-kicker">Votre salon n’est pas listé ?</div>
        <p class="me-side-text"><a href="<?= e(url('/salons/ajouter')) ?>">Proposez-le à l’agenda</a> — l’inscription est gratuite et ouverte à tous, partenaires ou non.</p>
      </div>
    </aside>
  </div>
</div>
