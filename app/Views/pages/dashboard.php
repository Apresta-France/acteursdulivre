<?php
$first = $userFirst ?? 'vous';
$seeks = !empty($seeksServices);
$offers = !empty($offersServices);
if ($seeks && $offers) {
    $subtitle = 'Vous pouvez chercher des prestataires et proposer vos services.';
} elseif ($seeks) {
    $subtitle = 'Vous cherchez des prestataires pour vos projets.';
} elseif ($offers) {
    $subtitle = 'Vous proposez vos services aux porteurs de projet.';
} else {
    $subtitle = 'Activez un usage dans vos paramètres pour afficher vos actions.';
}
?>
<div class="espace-page">
  <div class="espace-page-head">
    <div>
      <h1>Bonjour <?= e($first) ?></h1>
      <p><?= e($subtitle) ?></p>
    </div>
    <a class="btn-ghost" href="<?= e(url('/espace/parametres')) ?>">Modifier mes usages</a>
  </div>

  <?php if (!empty($error)): ?>
    <div class="flash flash-error"><?= e((string) $error) ?></div>
  <?php endif; ?>

  <?php if ($seeks): ?>
    <section class="dash-section">
      <h2>Chercher des prestataires</h2>
      <div class="dash-cards">
        <a class="dash-card" href="<?= e(url('/recherche')) ?>">
          <strong>Parcourir l'annuaire</strong>
          <span>Filtrez par métier, spécialité, ville et tarif, puis engagez la discussion.</span>
        </a>
        <a class="dash-card dash-card-accent" href="<?= e(url('/espace/publier')) ?>">
          <strong>Publier une mission</strong>
          <span>Décrivez le besoin et le budget : les prestataires qualifiés vous envoient leur devis.</span>
        </a>
        <a class="dash-card" href="<?= e(url('/espace/missions')) ?>">
          <strong>Mes missions</strong>
          <span>Suivez les appels d'offres que vous avez publiés et les devis reçus.</span>
        </a>
        <a class="dash-card" href="<?= e(url('/espace/commandes')) ?>">
          <strong>Mes commandes</strong>
          <span>Prestations achetées, jalons et factures au même endroit.</span>
        </a>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($offers): ?>
    <section class="dash-section">
      <h2>Proposer mes services</h2>
      <div class="dash-cards">
        <a class="dash-card" href="<?= e(url('/espace/vitrine')) ?>">
          <strong>Compléter ma vitrine</strong>
          <span>Métiers, spécialités, présentation : c'est ce que les porteurs de projet voient en premier.</span>
        </a>
        <a class="dash-card dash-card-accent" href="<?= e(url('/espace/prestations/creer')) ?>">
          <strong>Créer une prestation</strong>
          <span>Une offre packagée à prix, délai et périmètre affichés.</span>
        </a>
        <a class="dash-card" href="<?= e(url('/missions')) ?>">
          <strong>Voir les appels d'offres</strong>
          <span>Candidatez gratuitement aux missions publiées par les porteurs de projet.</span>
        </a>
        <a class="dash-card" href="<?= e(url('/espace/candidatures')) ?>">
          <strong>Mes candidatures</strong>
          <span>Le suivi des devis que vous avez envoyés.</span>
        </a>
      </div>
    </section>
  <?php endif; ?>
</div>
