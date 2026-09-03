<?php
/**
 * En-tête commun des écrans « Fiche auteur » de l'espace.
 * Attend : $page (AuthorPage hydratée), $completion (int), $worksCount (int), $auteurTab ('fiche'|'oeuvres'),
 * optionnel $auteurSubmitForm (id du formulaire à soumettre depuis l'en-tête).
 */
$page = $page ?? [];
$completion = (int) ($completion ?? 0);
$worksCount = (int) ($worksCount ?? 0);
$auteurTab = (string) ($auteurTab ?? 'fiche');
$enabled = !empty($page['enabled']);
$publicHref = !empty($page['slug']) ? url('/auteurs/' . $page['slug']) : '';
$canEnable = $enabled || trim((string) ($page['bio'] ?? '')) !== '' || $worksCount > 0;
?>
<div class="espace-page-head">
  <div>
    <h1>Ma fiche auteur</h1>
    <p>
      Fiche complétée à <?= $completion ?> %<?= $worksCount > 0 ? ' · ' . $worksCount . ' œuvre' . ($worksCount > 1 ? 's' : '') : '' ?>.
    </p>
  </div>
  <div class="vitrine-head-actions">
    <?php if ($publicHref): ?>
      <a class="btn-ghost" href="<?= e($publicHref) ?>"><?= $enabled ? 'Voir en public' : 'Prévisualiser' ?></a>
    <?php endif; ?>
    <?php if (!empty($auteurSubmitForm)): ?>
      <button class="btn-orange" type="submit" form="<?= e((string) $auteurSubmitForm) ?>">Enregistrer</button>
    <?php endif; ?>
  </div>
</div>

<section class="avail-banner auteur-status-banner<?= $enabled ? ' is-available' : '' ?>" aria-labelledby="auteur-status-title">
  <span class="dash-ico<?= $enabled ? ' dash-ico-accent' : '' ?>"><?= icon('book', 18) ?></span>
  <div>
    <strong id="auteur-status-title"><?= $enabled ? 'Fiche activée' : 'Fiche désactivée' ?></strong>
    <em>
      Cet espace est optionnel : une page publique pour vos livres, votre parcours et vos actualités, distincte de la vitrine prestataire.
      <?php if ($enabled): ?>
        Elle apparaît dans l'annuaire des auteurs. Désactivez-la pour la retirer du public sans perdre son contenu.
      <?php elseif ($canEnable): ?>
        Si vous n'écrivez pas de livres, laissez-la désactivée. Sinon, activez-la pour apparaître dans l'annuaire.
      <?php else: ?>
        Si vous n'écrivez pas de livres, vous n'en avez pas besoin. Pour l'activer, ajoutez d'abord une biographie ou une œuvre.
      <?php endif; ?>
    </em>
  </div>
  <form method="post" action="<?= e(url('/espace/auteur/publication')) ?>" class="mode-switch" aria-label="Activer ou désactiver la fiche auteur">
    <?= csrf_field() ?>
    <button type="submit" name="enabled" value="0" class="mode-option<?= !$enabled ? ' is-on is-busy' : '' ?>">Désactivée</button>
    <button type="submit" name="enabled" value="1" class="mode-option<?= $enabled ? ' is-on is-available' : '' ?>"<?= $canEnable ? '' : ' title="Ajoutez une biographie ou une œuvre pour activer la fiche."' ?>>Activée</button>
  </form>
</section>

<div class="vitrine-progress"><span style="width: <?= max(6, $completion) ?>%"></span></div>

<div class="tab-row">
  <a class="tab<?= $auteurTab === 'fiche' ? ' is-on' : '' ?>" href="<?= e(url('/espace/auteur')) ?>">Ma fiche</a>
  <a class="tab<?= $auteurTab === 'oeuvres' ? ' is-on' : '' ?>" href="<?= e(url('/espace/auteur/oeuvres')) ?>">Mes œuvres<?php if ($worksCount > 0): ?> <span class="tab-count"><?= $worksCount ?></span><?php endif; ?></a>
</div>
<?php unset($auteurSubmitForm); ?>
