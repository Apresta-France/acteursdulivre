<?php
$authors = $authors ?? [];
$pager = $pager ?? ['page' => 1, 'pages' => 1, 'total' => 0];
$pageNum = (int) ($pager['page'] ?? 1);
$pages = (int) ($pager['pages'] ?? 1);
$total = (int) ($pager['total'] ?? 0);
$viewer = \Adl\Core\Auth::user();
?>
<div class="journal-page auteurs-page">
  <nav class="search-crumb" aria-label="Fil d'Ariane">
    <a href="<?= e(url('/')) ?>">Accueil</a>
    <span aria-hidden="true"> · </span>
    <span>Auteurs</span>
  </nav>
  <div class="search-head">
    <div>
      <h1>Les auteurs et autrices <span><?= $total === 1 ? '1 fiche' : format_int($total) . ' fiches' ?></span></h1>
      <p class="journal-lead">Les membres d'acteursdulivre.fr qui écrivent, illustrent ou traduisent des livres : bibliographie, biographie, presse, rencontres et liens d'achat, sur une seule page.</p>
    </div>
    <?php if ($viewer): ?>
      <a class="btn-navy" href="<?= e(url('/espace/auteur')) ?>">Ma fiche auteur</a>
    <?php else: ?>
      <a class="btn-navy" href="<?= e(url('/inscription')) ?>">Créer ma fiche auteur</a>
    <?php endif; ?>
  </div>

  <?php if ($authors === []): ?>
    <div class="auteur-empty" style="margin-top: 26px;">
      <span class="dash-ico"><?= icon('book', 22) ?></span>
      <div>
        <strong>Aucune fiche auteur publiée pour le moment</strong>
        <p>Chaque membre peut activer sa fiche auteur depuis son espace, qu'il propose des services ou porte un projet.</p>
      </div>
    </div>
  <?php else: ?>
    <div class="auteurs-grid">
      <?php foreach ($authors as $a): ?>
        <a class="auteur-card" href="<?= e(url((string) $a['href'])) ?>">
          <?= avatar_html($a, 64, 'avatar auteur-card-avatar') ?>
          <div class="auteur-card-body">
            <strong><?= e((string) $a['name']) ?></strong>
            <?php if (trim((string) ($a['tagline'] ?? '')) !== ''): ?>
              <span class="auteur-card-tagline"><?= e((string) $a['tagline']) ?></span>
            <?php endif; ?>
            <?php if (trim((string) ($a['short_bio'] ?? '')) !== ''): ?>
              <p><?= e(\Adl\Data\Seo::clip((string) $a['short_bio'], 150)) ?></p>
            <?php endif; ?>
            <div class="auteur-card-meta">
              <?php $n = (int) ($a['works_count'] ?? 0); ?>
              <span><?= $n === 0 ? 'Fiche auteur' : ($n === 1 ? '1 œuvre' : $n . ' œuvres') ?></span>
              <?php foreach (array_slice($a['genres'] ?? [], 0, 3) as $genre): ?>
                <span class="chip-static dark"><?= e((string) $genre) ?></span>
              <?php endforeach; ?>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if ($pages > 1): ?>
      <nav class="pager" aria-label="Pagination">
        <?php if ($pageNum > 1): ?>
          <a class="btn-ghost" href="<?= e(url('/auteurs' . ($pageNum - 1 > 1 ? '?page=' . ($pageNum - 1) : ''))) ?>">Précédent</a>
        <?php endif; ?>
        <span class="pager-info">Page <?= $pageNum ?> / <?= $pages ?></span>
        <?php if ($pageNum < $pages): ?>
          <a class="btn-ghost" href="<?= e(url('/auteurs?page=' . ($pageNum + 1))) ?>">Suivant</a>
        <?php endif; ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</div>
