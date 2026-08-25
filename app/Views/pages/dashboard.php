<?php
$first = $userFirst ?? 'vous';
$seeks = !empty($seeksServices);
$offers = !empty($offersServices);
$missionCount = (int) ($missionCount ?? 0);
$openMissionCount = (int) ($openMissionCount ?? 0);
$profileCompletion = (int) ($profileCompletion ?? 0);
$unreadMessages = (int) ($unreadMessages ?? 0);
$unreadAlerts = (int) ($unreadAlerts ?? 0);

if ($seeks && $offers) {
    $subtitle = 'Vous pouvez chercher des prestataires et proposer vos services.';
} elseif ($seeks) {
    $subtitle = 'Vous cherchez des prestataires pour vos projets.';
} elseif ($offers) {
    $subtitle = 'Vous proposez vos services aux porteurs de projet.';
} else {
    $subtitle = 'Activez un usage dans vos paramètres pour afficher vos actions.';
}

$todos = [];
if ($seeks && $missionCount === 0) {
    $todos[] = [
        'icon' => 'file-plus',
        'title' => 'Publier votre première mission',
        'body' => 'Décrivez le besoin et le budget : les prestataires du métier choisi pourront y répondre.',
        'href' => '/espace/publier',
        'cta' => 'Rédiger l\'annonce',
    ];
}
if ($offers && $profileCompletion < 80) {
    $todos[] = [
        'icon' => 'id',
        'title' => 'Compléter votre vitrine',
        'body' => 'Profil complété à ' . $profileCompletion . ' %. Les vitrines précises reçoivent nettement plus de demandes.',
        'href' => '/espace/vitrine',
        'cta' => 'Continuer',
    ];
}
?>
<div class="espace-page dash-page">
  <div class="espace-page-head">
    <div>
      <h1>Bonjour <?= e($first) ?></h1>
      <p><?= e($subtitle) ?></p>
    </div>
    <div class="dash-hero-actions">
      <?php if ($seeks): ?>
        <a class="btn-orange" href="<?= e(url('/espace/publier')) ?>"><?= icon('file-plus', 16) ?> Publier une mission</a>
      <?php endif; ?>
      <?php if ($offers): ?>
        <a class="btn-navy" href="<?= e(url('/espace/prestations/creer')) ?>"><?= icon('plus-box', 16) ?> Créer une prestation</a>
      <?php endif; ?>
      <a class="btn-ghost" href="<?= e(url('/espace/parametres')) ?>"><?= icon('sliders', 16) ?> Mes usages</a>
    </div>
  </div>

  <?php if (!empty($error)): ?>
    <div class="flash flash-error"><?= e((string) $error) ?></div>
  <?php endif; ?>

  <div class="dash-stats">
    <a class="dash-stat" href="<?= e(url('/espace/messages')) ?>">
      <span class="dash-ico"><?= icon('mail', 18) ?></span>
      <span>
        <strong>Messages</strong>
        <em><?= $unreadMessages ?> non lu<?= $unreadMessages > 1 ? 's' : '' ?></em>
      </span>
    </a>
    <a class="dash-stat" href="<?= e(url('/espace/notifications')) ?>">
      <span class="dash-ico"><?= icon('bell', 18) ?></span>
      <span>
        <strong>Alertes</strong>
        <em><?= $unreadAlerts ?> nouvelle<?= $unreadAlerts > 1 ? 's' : '' ?></em>
      </span>
    </a>
    <?php if ($seeks): ?>
      <a class="dash-stat" href="<?= e(url('/espace/missions')) ?>">
        <span class="dash-ico"><?= icon('clipboard', 18) ?></span>
        <span>
          <strong>Missions</strong>
          <em><?= $openMissionCount ?> ouverte<?= $openMissionCount > 1 ? 's' : '' ?> · <?= $missionCount ?> au total</em>
        </span>
      </a>
    <?php endif; ?>
    <?php if ($offers): ?>
      <a class="dash-stat" href="<?= e(url('/espace/vitrine')) ?>">
        <span class="dash-ico"><?= icon('id', 18) ?></span>
        <span>
          <strong>Vitrine</strong>
          <em>Complétée à <?= $profileCompletion ?> %</em>
        </span>
      </a>
    <?php endif; ?>
  </div>

  <?php if ($todos !== []): ?>
    <section class="dash-section">
      <h2>À faire en priorité</h2>
      <div class="dash-todos">
        <?php foreach ($todos as $todo): ?>
          <a class="dash-todo" href="<?= e(url($todo['href'])) ?>">
            <span class="dash-ico dash-ico-accent"><?= icon($todo['icon'], 18) ?></span>
            <span>
              <strong><?= e($todo['title']) ?></strong>
              <em><?= e($todo['body']) ?></em>
            </span>
            <span class="dash-card-cta"><?= e($todo['cta']) ?> <?= icon('arrow', 14) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($seeks): ?>
    <section class="dash-section">
      <h2>Chercher des prestataires</h2>
      <div class="dash-cards">
        <a class="dash-card" href="<?= e(url('/recherche')) ?>">
          <span class="dash-ico"><?= icon('search', 20) ?></span>
          <strong>Parcourir l'annuaire</strong>
          <span>Filtrez par métier, spécialité, ville et tarif, puis engagez la discussion.</span>
          <span class="dash-card-cta">Ouvrir l'annuaire <?= icon('arrow', 14) ?></span>
        </a>
        <a class="dash-card dash-card-accent" href="<?= e(url('/espace/publier')) ?>">
          <span class="dash-ico"><?= icon('file-plus', 20) ?></span>
          <strong>Publier une mission</strong>
          <span>Décrivez le besoin et le budget : les prestataires qualifiés vous envoient leur devis.</span>
          <span class="dash-card-cta">Rédiger l'annonce <?= icon('arrow', 14) ?></span>
        </a>
        <a class="dash-card" href="<?= e(url('/espace/missions')) ?>">
          <span class="dash-ico"><?= icon('clipboard', 20) ?></span>
          <strong>Mes missions</strong>
          <span>Suivez les appels d'offres que vous avez publiés et les devis reçus.</span>
          <span class="dash-card-cta">Voir le suivi <?= icon('arrow', 14) ?></span>
        </a>
        <a class="dash-card" href="<?= e(url('/espace/commandes')) ?>">
          <span class="dash-ico"><?= icon('bag', 20) ?></span>
          <strong>Mes commandes</strong>
          <span>Prestations achetées, jalons et factures au même endroit.</span>
          <span class="dash-card-cta">Ouvrir les commandes <?= icon('arrow', 14) ?></span>
        </a>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($offers): ?>
    <section class="dash-section">
      <h2>Proposer mes services</h2>
      <div class="dash-cards">
        <a class="dash-card" href="<?= e(url('/espace/vitrine')) ?>">
          <span class="dash-ico"><?= icon('id', 20) ?></span>
          <strong>Compléter ma vitrine</strong>
          <span>Métiers, spécialités, présentation : c'est ce que les porteurs de projet voient en premier.</span>
          <span class="dash-card-cta">Éditer la vitrine <?= icon('arrow', 14) ?></span>
        </a>
        <a class="dash-card dash-card-accent" href="<?= e(url('/espace/prestations/creer')) ?>">
          <span class="dash-ico"><?= icon('plus-box', 20) ?></span>
          <strong>Créer une prestation</strong>
          <span>Une offre packagée à prix, délai et périmètre affichés.</span>
          <span class="dash-card-cta">Composer l'offre <?= icon('arrow', 14) ?></span>
        </a>
        <a class="dash-card" href="<?= e(url('/missions')) ?>">
          <span class="dash-ico"><?= icon('megaphone', 20) ?></span>
          <strong>Voir les appels d'offres</strong>
          <span>Candidatez gratuitement aux missions publiées par les porteurs de projet.</span>
          <span class="dash-card-cta">Parcourir les missions <?= icon('arrow', 14) ?></span>
        </a>
        <a class="dash-card" href="<?= e(url('/espace/candidatures')) ?>">
          <span class="dash-ico"><?= icon('send', 20) ?></span>
          <strong>Mes candidatures</strong>
          <span>Le suivi des devis que vous avez envoyés.</span>
          <span class="dash-card-cta">Voir les devis <?= icon('arrow', 14) ?></span>
        </a>
      </div>
    </section>
  <?php endif; ?>

  <?php if (!$seeks && !$offers): ?>
    <div class="search-empty">
      <strong>Aucun usage n'est encore activé.</strong>
      <span>Choisissez si vous cherchez des prestataires, si vous proposez vos services, ou les deux.</span>
      <a class="btn-orange" href="<?= e(url('/espace/parametres')) ?>"><?= icon('sliders', 16) ?> Modifier mes usages</a>
    </div>
  <?php endif; ?>

  <section class="dash-section">
    <h2>Raccourcis</h2>
    <div class="dash-quick">
      <a class="dash-chip" href="<?= e(url('/espace/messages')) ?>"><?= icon('mail', 16) ?> Messages</a>
      <a class="dash-chip" href="<?= e(url('/espace/notifications')) ?>"><?= icon('bell', 16) ?> Alertes</a>
      <?php if ($seeks): ?>
        <a class="dash-chip" href="<?= e(url('/espace/favoris')) ?>"><?= icon('heart', 16) ?> Favoris</a>
        <a class="dash-chip" href="<?= e(url('/recherche')) ?>"><?= icon('search', 16) ?> Annuaire</a>
      <?php endif; ?>
      <?php if ($offers): ?>
        <a class="dash-chip" href="<?= e(url('/espace/prestations')) ?>"><?= icon('grid', 16) ?> Mes prestations</a>
        <a class="dash-chip" href="<?= e(url('/espace/facturation')) ?>"><?= icon('invoice', 16) ?> Facturation</a>
        <?php if (($profileHref ?? '') !== '' && $profileHref !== '/recherche'): ?>
          <a class="dash-chip" href="<?= e(url($profileHref)) ?>"><?= icon('store', 16) ?> Voir en public</a>
        <?php endif; ?>
      <?php endif; ?>
      <a class="dash-chip" href="<?= e(url('/espace/parametres')) ?>"><?= icon('gear', 16) ?> Paramètres</a>
      <a class="dash-chip" href="<?= e(url('/aide')) ?>"><?= icon('book', 16) ?> Centre d'aide</a>
    </div>
  </section>
</div>
