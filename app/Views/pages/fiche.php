<?php
$service = $service ?? null;
if (!$service) {
    not_found('Cette prestation n\'est plus disponible.');
}
$packages = $service['packages'] ?? [];
?>
<div class="fiche-page">
  <div class="search-crumb">Prestations · <?= e((string) ($service['cat'] ?: 'Offre')) ?><?php if (!empty($service['specialty'])): ?> · <?= e((string) $service['specialty']) ?><?php endif; ?></div>
  <div class="publish-grid">
    <div>
      <?php if (!empty($service['has_image'])): ?>
        <div class="service-cover-hero" style="background-image:url('<?= e((string) $service['img']) ?>')" role="img" aria-label="<?= e('Visuel de la prestation') ?>"></div>
      <?php else: ?>
        <?= service_cover_html((string) ($service['cat'] ?? ''), 'is-hero') ?>
      <?php endif; ?>
      <h1><?= e((string) $service['title']) ?></h1>
      <p class="journal-lead"><?= e((string) ($service['excerpt'] ?: 'Prestation proposée par ' . $service['by'] . '.')) ?></p>
      <div class="facts">
        <div><span>Prix</span><strong><?= e((string) $service['price']) ?></strong></div>
        <div><span>Délai</span><strong><?= e((string) ($service['delay'] ?: 'à convenir')) ?></strong></div>
        <div><span>Métier</span><strong><?= e((string) ($service['cat'] ? \Adl\Data\Catalog::tradeTitle((string) $service['cat']) : '—')) ?></strong></div>
        <?php if (!empty($service['specialty'])): ?>
          <div><span>Spécialité</span><strong><?= e((string) $service['specialty']) ?></strong></div>
        <?php endif; ?>
        <div><span>Avis</span><strong><?= $service['reviews'] > 0 ? e((string) $service['rating']) . ' · ' . (int) $service['reviews'] : 'Pas encore d\'avis' ?></strong></div>
      </div>

      <?php if ($packages !== []): ?>
        <h2>Formules</h2>
        <div class="my-missions">
          <?php foreach ($packages as $package): ?>
            <article class="side-card">
              <div class="mission-row-title"><?= e((string) $package['name']) ?></div>
              <?php if (!empty($package['description'])): ?>
                <p class="mission-row-sub"><?= e((string) $package['description']) ?></p>
              <?php endif; ?>
              <div class="side-foot">
                <span><?= e((string) ($package['delay'] ?: 'Délai à convenir')) ?></span>
                <strong><?= e((string) $package['price_label']) ?></strong>
              </div>
              <?php if (!empty($canOrder)): ?>
                <div class="auth-actions" style="margin-top: 12px;">
                  <a class="btn-ghost" href="<?= e(url('/espace/commande?prestation=' . rawurlencode((string) $service['slug']) . '&formule=' . (int) ($package['id'] ?? 0))) ?>">Ouvrir cette formule</a>
                </div>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <aside class="publish-side">
      <div class="side-card">
        <div class="side-kicker">Le prestataire</div>
        <div class="suggest-row" style="padding: 0;">
          <span class="avatar" style="<?= e(avatar_style((string) $service['initials'], 46)) ?>"><?= e((string) $service['initials']) ?></span>
          <span>
            <strong><?= e((string) $service['by']) ?></strong>
            <em><?= e((string) ($service['cat'] ?: 'Prestataire')) ?></em>
          </span>
        </div>
        <?php if (!empty($service['profile_href'])): ?>
          <div class="auth-actions" style="margin-top: 16px;">
            <a class="btn-ghost" href="<?= e(url((string) $service['profile_href'])) ?>">Voir la vitrine</a>
          </div>
        <?php endif; ?>
        <?php
          $viewer = \Adl\Core\Auth::user();
          $owner = $viewer && (int) ($viewer['id'] ?? 0) === (int) ($service['user_id'] ?? 0);
        ?>
        <?php if (!$owner): ?>
          <div class="auth-actions" style="margin-top: 16px; flex-wrap: wrap;">
            <?php if (!empty($canOrder)): ?>
              <a class="btn-orange" href="<?= e(url('/espace/commande?prestation=' . rawurlencode((string) $service['slug']))) ?>">Ouvrir une commande</a>
            <?php elseif (!$viewer): ?>
              <a class="btn-orange" href="<?= e(url('/connexion')) ?>">Se connecter pour commander</a>
            <?php elseif ($viewer && !\Adl\Models\User::seeksServices($viewer)): ?>
              <a class="btn-ghost" href="<?= e(url('/espace/parametres')) ?>">Activer « je cherche » pour commander</a>
            <?php endif; ?>
            <p class="field-help" style="margin: 10px 0 0;">Aucun paiement ici : le prestataire envoie un devis, vous vous réglez hors plateforme, jalon par jalon.</p>
            <?php if ($viewer && \Adl\Models\User::seeksServices($viewer)): ?>
              <form method="post" action="<?= e(url('/espace/favoris/' . (int) $service['id'])) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="back" value="<?= e((string) $service['href']) ?>">
                <button class="btn-ghost" type="submit"><?= !empty($isFavorite) ? 'Retirer des favoris' : 'Ajouter aux favoris' ?></button>
              </form>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </aside>
  </div>
</div>
