<?php
$order = $order ?? [];
$file = $file ?? [];
$clicks = is_array($clicks ?? null) ? $clicks : [];
$withdrawn = !empty($file['is_withdrawn']);
$orderHref = '/espace/suivi/' . (int) ($order['id'] ?? 0) . '/depot';
$suiviTab = 'fichiers';
?>
<div class="espace-page jalon-page depot-page">
  <div class="espace-page-head">
    <div>
      <h1><?= e((string) ($file['file_name'] ?? 'Fichier')) ?></h1>
      <p><?= e((string) ($order['num'] ?? '')) ?> · <?= e((string) ($order['title'] ?? 'Commande')) ?></p>
    </div>
    <a class="btn-ghost jalon-msg" href="<?= e(url($orderHref)) ?>">Retour aux fichiers</a>
  </div>

  <?php require ADL_ROOT . '/app/Views/partials/suivi-tabs.php'; ?>

  <?php if (!empty($error)): ?>
    <div class="flash flash-error"><?= e((string) $error) ?></div>
  <?php endif; ?>
  <?php if (!empty($saved)): ?>
    <div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div>
  <?php endif; ?>

  <div class="publish-grid">
    <div>
      <section class="depot-viewer">
        <?php if ($withdrawn): ?>
          <p class="jalon-lead">Ce fichier a été retiré. Seul l’historique du dépôt et des accès est conservé.</p>
        <?php elseif (!empty($file['can_preview']) && !empty($file['preview_href'])): ?>
          <?php if (!empty($file['is_image'])): ?>
            <img class="depot-preview-img" src="<?= e(url((string) $file['preview_href'])) ?>" alt="<?= e((string) ($file['file_name'] ?? 'Aperçu')) ?>">
          <?php else: ?>
            <iframe class="depot-preview-frame" title="Aperçu du fichier" src="<?= e(url((string) $file['preview_href'])) ?>"></iframe>
          <?php endif; ?>
        <?php else: ?>
          <p class="jalon-lead">Aperçu indisponible pour ce format. Téléchargez le fichier pour l’ouvrir sur votre ordinateur.</p>
        <?php endif; ?>

        <?php if (!$withdrawn && !empty($file['download_href'])): ?>
          <div class="jalon-actions">
            <a class="btn-orange" href="<?= e(url((string) $file['download_href'])) ?>">Télécharger</a>
          </div>
        <?php endif; ?>
      </section>

      <section class="jalon-timeline depot-clicks">
        <h2 class="espace-section-title">Historique des accès</h2>
        <?php if ($clicks === []): ?>
          <p class="jalon-hint">Aucun clic enregistré pour le moment.</p>
        <?php else: ?>
          <ol class="depot-click-list">
            <?php foreach ($clicks as $click): ?>
              <li>
                <strong><?= e((string) ($click['action_label'] ?? 'Accès')) ?></strong>
                <span><?= e((string) ($click['who'] ?? '')) ?><?php if (!empty($click['when'])): ?> · <?= e((string) $click['when']) ?><?php endif; ?></span>
              </li>
            <?php endforeach; ?>
          </ol>
        <?php endif; ?>
      </section>
    </div>

    <aside class="publish-side">
      <div class="jalon-aside">
        <div class="side-kicker">Dépôt</div>
        <div class="jalon-recap">
          <div class="jalon-recap-row"><span>Auteur</span><strong><?= e((string) ($file['who'] ?? '')) ?></strong></div>
          <div class="jalon-recap-row"><span>Rôle</span><strong><?= e((string) ($file['role_label'] ?? '')) ?></strong></div>
          <div class="jalon-recap-row"><span>Date</span><strong><?= e((string) ($file['when'] ?? '—')) ?></strong></div>
          <div class="jalon-recap-row"><span>Poids</span><strong><?= e((string) ($file['size_label'] ?? '—')) ?></strong></div>
          <div class="jalon-recap-row"><span>Vues</span><strong><?= (int) ($file['views'] ?? 0) ?></strong></div>
          <div class="jalon-recap-row"><span>Téléchargements</span><strong><?= (int) ($file['downloads'] ?? 0) ?></strong></div>
        </div>
        <?php if (!empty($file['note'])): ?>
          <p class="depot-note"><?= e((string) $file['note']) ?></p>
        <?php endif; ?>
        <p class="jalon-hint">Fichier privé : seul le porteur de projet, le prestataire et l’équipe de médiation peuvent y accéder.</p>
      </div>
    </aside>
  </div>
</div>
