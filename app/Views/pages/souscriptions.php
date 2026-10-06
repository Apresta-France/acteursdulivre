<?php
$items = $items ?? [];
$featured = is_array($featured ?? null) ? $featured : null;
$counts = $counts ?? ['open' => 0, 'souscription' => 0, 'vente' => 0, 'sponsorise' => 0, 'closed' => 0];
$type = (string) ($type ?? '');
$filters = [
    '' => ['Ouvertes', (int) ($counts['open'] ?? 0)],
    'souscription' => ['En création', (int) ($counts['souscription'] ?? 0)],
    'vente' => ['En vente', (int) ($counts['vente'] ?? 0)],
    'sponsorise' => ['Sponsorisés', (int) ($counts['sponsorise'] ?? 0)],
    'terminees' => ['Terminées', (int) ($counts['closed'] ?? 0)],
];
?>
<div class="mk-page co-page sub-page">
  <section class="forum-hero co-hero">
    <div class="forum-hero-copy">
      <nav class="forum-crumb" aria-label="Fil d'Ariane">
        <a href="<?= e(url('/')) ?>">Accueil</a>
        <span aria-hidden="true">·</span>
        <a href="<?= e(url('/communaute')) ?>">Communauté</a>
        <span aria-hidden="true">·</span>
        <span>Souscriptions</span>
      </nav>
      <div class="forum-kicker">Communauté</div>
      <h1>Livres en cours, ailleurs.</h1>
      <p class="forum-lead">Un livre en création, en vente ou sponsorisé. La campagne reste sur son site : ici, on la montre le temps qu’elle est ouverte.</p>
    </div>
    <div class="forum-hero-actions">
      <a class="btn-orange forum-hero-cta" href="<?= e(url('/souscriptions/proposer')) ?>">Proposer un livre</a>
      <div class="forum-hero-stats">
        <span class="forum-stat">
          <strong><?= e(format_int((int) ($counts['souscription'] ?? 0))) ?></strong>
          <span>en création</span>
        </span>
        <span class="forum-stat">
          <strong><?= e(format_int((int) ($counts['vente'] ?? 0))) ?></strong>
          <span>en vente</span>
        </span>
        <span class="forum-stat">
          <strong><?= e(format_int((int) ($counts['sponsorise'] ?? 0))) ?></strong>
          <span>sponsorisés</span>
        </span>
      </div>
    </div>
  </section>

  <section class="mk-block">
    <div class="sub-filters" role="tablist" aria-label="Filtrer les livres">
      <?php foreach ($filters as $key => $filter): ?>
        <?php
          $href = $key === '' ? '/souscriptions' : '/souscriptions?type=' . $key;
          $on = $type === $key;
        ?>
        <a class="sub-filter<?= $on ? ' is-on' : '' ?>" href="<?= e(url($href)) ?>"<?= $on ? ' aria-current="page"' : '' ?>>
          <?= e($filter[0]) ?>
          <span><?= (int) $filter[1] ?></span>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if ($featured !== null): ?>
      <?php require ADL_ROOT . '/app/Views/partials/souscription-feature.php'; ?>
    <?php endif; ?>

    <?php if ($items === [] && $featured === null): ?>
      <?php if ((int) ($counts['open'] ?? 0) === 0 && (int) ($counts['closed'] ?? 0) === 0): ?>
        <p class="mk-empty">Aucune annonce pour le moment. <a href="<?= e(url('/souscriptions/proposer')) ?>">Proposer un livre</a></p>
      <?php else: ?>
        <p class="mk-empty">Aucun livre dans cette sélection. <a href="<?= e(url('/souscriptions')) ?>">Voir les annonces ouvertes</a></p>
      <?php endif; ?>
    <?php elseif ($items !== []): ?>
      <div class="sub-grid">
        <?php foreach ($items as $item): ?>
          <?php require ADL_ROOT . '/app/Views/partials/souscription-card.php'; ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="sub-how">
      <div>
        <h2>Trois façons d’être ici</h2>
        <div class="sub-steps">
          <p><strong>En création</strong> Une souscription déjà ouverte ailleurs, le temps de fabriquer le livre.</p>
          <p><strong>En vente</strong> Une prévente ou une vente directe, chez l’auteur, l’atelier ou la maison.</p>
          <p><strong>Sponsorisé</strong> Une mise en avant assumée. La fiche le dit, le lien quitte le site.</p>
        </div>
      </div>
      <aside class="sub-propose">
        <h3>Proposer un livre</h3>
        <p>La campagne est déjà ouverte ailleurs. Décrivez-la : l’équipe publie la fiche après vérification. Le paiement reste sur le site qui l’héberge.</p>
        <a class="btn-navy" href="<?= e(url('/souscriptions/proposer')) ?>">Proposer un livre</a>
      </aside>
    </div>
  </section>
</div>
