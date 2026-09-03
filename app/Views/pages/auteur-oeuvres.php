<?php
$p = $page ?? [];
$works = $works ?? [];
$auteurTab = 'oeuvres';
$total = count($works);
?>
<div class="espace-page auteur-page">
  <?php require ADL_ROOT . '/app/Views/partials/auteur-head.php'; ?>

  <section class="espace-panel">
    <div class="espace-panel-head auteur-works-head">
      <div>
        <h2 class="espace-section-title">Mes œuvres</h2>
        <p class="espace-section-lead">Romans, recueils, albums, essais, livres que vous avez écrits, illustrés ou traduits. Chaque œuvre a sa fiche : jusqu'à trois visuels, un résumé, les informations d'édition et un lien pour l'acheter.</p>
      </div>
      <a class="btn-orange" href="<?= e(url('/espace/auteur/oeuvres/creer')) ?>"><?= icon('plus', 16) ?> Ajouter une œuvre</a>
    </div>

    <?php if ($works === []): ?>
      <div class="auteur-empty">
        <span class="dash-ico"><?= icon('book', 22) ?></span>
        <div>
          <strong>Aucune œuvre pour le moment</strong>
          <p>Commencez par votre dernier livre paru : titre, éditeur, année, un résumé et la couverture. Vous pourrez l'épingler en tête de fiche.</p>
          <a class="btn-navy" href="<?= e(url('/espace/auteur/oeuvres/creer')) ?>">Ajouter ma première œuvre</a>
        </div>
      </div>
    <?php else: ?>
      <ol class="auteur-works-list">
        <?php foreach ($works as $i => $work): ?>
          <?php
            // Les œuvres mises en avant restent en tête : on ne propose que les déplacements au sein du même groupe.
            $featured = !empty($work['featured']);
            $canUp = $i > 0 && !empty($works[$i - 1]['featured']) === $featured;
            $canDown = $i < $total - 1 && !empty($works[$i + 1]['featured']) === $featured;
          ?>
          <li class="auteur-work-row<?= $featured ? ' is-featured' : '' ?>">
            <a class="auteur-work-cover<?= $work['cover'] === '' ? ' is-empty' : '' ?>" href="<?= e(url('/espace/auteur/oeuvres/' . (int) $work['id'])) ?>" aria-hidden="true" tabindex="-1"<?= $work['cover'] !== '' ? ' style="background-image:url(\'' . e((string) $work['cover']) . '\')"' : '' ?>>
              <?php if ($work['cover'] === ''): ?><?= icon('book', 20) ?><?php endif; ?>
            </a>
            <div class="auteur-work-main">
              <div class="auteur-work-title">
                <a href="<?= e(url('/espace/auteur/oeuvres/' . (int) $work['id'])) ?>"><?= e((string) $work['title']) ?></a>
                <?php if (!empty($work['featured'])): ?><span class="profile-badge">En avant</span><?php endif; ?>
                <?php if (($work['status'] ?? '') !== 'published'): ?><span class="status-pill"><?= e((string) $work['status_label']) ?></span><?php endif; ?>
              </div>
              <div class="mission-row-sub">
                <?= e((string) $work['kind_label']) ?>
                <?php if (($work['role'] ?? 'auteur') !== 'auteur'): ?> · <?= e((string) $work['role_label']) ?><?php endif; ?>
                <?php if ($work['meta_label'] !== ''): ?> · <?= e((string) $work['meta_label']) ?><?php endif; ?>
                <?php if (count($work['images']) > 0): ?> · <?= count($work['images']) ?> visuel<?= count($work['images']) > 1 ? 's' : '' ?><?php endif; ?>
                <?php if (!empty($work['buy_url'])): ?> · lien d'achat<?php endif; ?>
              </div>
            </div>
            <div class="auteur-work-actions">
              <form method="post" action="<?= e(url('/espace/auteur/oeuvres/' . (int) $work['id'] . '/deplacer')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="direction" value="up">
                <button class="icon-btn" type="submit" aria-label="Monter"<?= $canUp ? '' : ' disabled' ?>>↑</button>
              </form>
              <form method="post" action="<?= e(url('/espace/auteur/oeuvres/' . (int) $work['id'] . '/deplacer')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="direction" value="down">
                <button class="icon-btn" type="submit" aria-label="Descendre"<?= $canDown ? '' : ' disabled' ?>>↓</button>
              </form>
              <a class="btn-ghost" href="<?= e(url('/espace/auteur/oeuvres/' . (int) $work['id'])) ?>">Modifier</a>
              <form method="post" action="<?= e(url('/espace/auteur/oeuvres/' . (int) $work['id'] . '/supprimer')) ?>" onsubmit="return confirm('Retirer cette œuvre de votre fiche auteur ? Ses visuels seront supprimés.');">
                <?= csrf_field() ?>
                <button class="text-btn" type="submit">Supprimer</button>
              </form>
            </div>
          </li>
        <?php endforeach; ?>
      </ol>
      <p class="field-help">L'ordre ci-dessus est celui de votre fiche publique. Les œuvres « en avant » restent en tête.</p>
    <?php endif; ?>
  </section>
</div>
