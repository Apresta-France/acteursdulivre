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
?>
<div class="espace-page-head">
  <div>
    <h1>Ma fiche auteur</h1>
    <p>
      <?php if ($enabled): ?>
        <span class="status-pill is-available">En ligne</span>
      <?php else: ?>
        <span class="status-pill">Brouillon</span>
      <?php endif; ?>
      Fiche complétée à <?= $completion ?> %<?= $worksCount > 0 ? ' · ' . $worksCount . ' œuvre' . ($worksCount > 1 ? 's' : '') : '' ?>.
      Mettez en avant vos livres, votre parcours et vos actualités, que vous soyez porteur de projet ou prestataire.
    </p>
  </div>
  <div class="vitrine-head-actions">
    <?php if ($publicHref): ?>
      <a class="btn-ghost" href="<?= e($publicHref) ?>"><?= $enabled ? 'Voir en public' : 'Prévisualiser' ?></a>
    <?php endif; ?>
    <form method="post" action="<?= e(url('/espace/auteur/publication')) ?>" class="auteur-publish-form">
      <?= csrf_field() ?>
      <input type="hidden" name="enabled" value="<?= $enabled ? '0' : '1' ?>">
      <button class="<?= $enabled ? 'btn-ghost' : 'btn-navy' ?>" type="submit"><?= $enabled ? 'Repasser en brouillon' : 'Publier ma fiche' ?></button>
    </form>
    <?php if (!empty($auteurSubmitForm)): ?>
      <button class="btn-orange" type="submit" form="<?= e((string) $auteurSubmitForm) ?>">Enregistrer</button>
    <?php endif; ?>
  </div>
</div>

<div class="vitrine-progress"><span style="width: <?= max(6, $completion) ?>%"></span></div>

<div class="tab-row">
  <a class="tab<?= $auteurTab === 'fiche' ? ' is-on' : '' ?>" href="<?= e(url('/espace/auteur')) ?>">Ma fiche</a>
  <a class="tab<?= $auteurTab === 'oeuvres' ? ' is-on' : '' ?>" href="<?= e(url('/espace/auteur/oeuvres')) ?>">Mes œuvres<?php if ($worksCount > 0): ?> <span class="tab-count"><?= $worksCount ?></span><?php endif; ?></a>
</div>
<?php unset($auteurSubmitForm); ?>
