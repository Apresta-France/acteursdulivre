<?php
$order = $order ?? [];
$status = (string) ($order['status'] ?? 'pending');
$depotFiles = is_array($depotFiles ?? null) ? $depotFiles : [];
$depotOpen = !empty($depotOpen);
$depotActive = (int) ($depotCount ?? 0);
?>
<div class="espace-page jalon-page depot-page">
  <div class="espace-page-head">
    <div>
      <h1><?= e((string) ($order['title'] ?? 'Commande')) ?></h1>
      <p><?= e((string) ($order['num'] ?? '')) ?> · <?= e((string) ($order['by'] ?? '')) ?> · <?= e((string) ($order['amount_label'] ?? '')) ?></p>
    </div>
    <a class="btn-ghost jalon-msg" href="<?= e(url((string) ($threadHref ?? '/espace/messages'))) ?>">Ouvrir la messagerie</a>
  </div>

  <?php require ADL_ROOT . '/app/Views/partials/suivi-tabs.php'; ?>

  <?php if (!empty($error)): ?>
    <div class="flash flash-error"><?= e((string) $error) ?></div>
  <?php endif; ?>
  <?php if (!empty($saved)): ?>
    <div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div>
  <?php endif; ?>

  <section class="depot-zone<?= $depotOpen ? ' is-open' : ' is-locked' ?>">
    <div class="depot-zone-head">
      <div>
        <div class="side-kicker"><?= $depotOpen ? 'Ouvert' : ($depotFiles !== [] ? 'Consultation' : 'Bientôt') ?></div>
        <h2>Espace de fichiers</h2>
        <p class="jalon-lead">
          <?php if ($depotOpen): ?>
            Zone privée entre vous deux : déposez, consultez et téléchargez les fichiers du projet. Chaque accès est enregistré.
          <?php elseif ($depotFiles !== []): ?>
            Les dépôts sont clos. L’historique reste consultable et les fichiers encore disponibles peuvent être téléchargés.
          <?php else: ?>
            Cet espace s’ouvre dès que le projet est en cours, après acceptation du devis.
          <?php endif; ?>
        </p>
      </div>
      <?php if ($depotOpen || $depotActive > 0): ?>
        <span class="depot-count"><?= $depotActive ?> fichier<?= $depotActive > 1 ? 's' : '' ?></span>
      <?php endif; ?>
    </div>

    <?php if ($depotOpen): ?>
      <form class="jalon-form depot-form" method="post" action="<?= e(url('/espace/suivi/' . (int) $order['id'] . '/depot')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div>
          <span class="field" id="depot-doc-label">Déposer un fichier</span>
          <?php
            $filePickId = 'depot-doc';
            $filePickName = 'document';
            $filePickAccept = '.pdf,.doc,.docx,.odt,.txt,image/jpeg,image/png,image/webp,image/gif';
            $filePickButton = 'Choisir un fichier';
            $filePickDrop = true;
            $filePickHint = 'PDF, images, Word ou texte — 20 Mo max. Stockage privé, hors du web.';
            $filePickAttrs = 'aria-labelledby="depot-doc-label"';
            $filePickRequired = true;
            require ADL_ROOT . '/app/Views/partials/file-pick.php';
          ?>
        </div>
        <div>
          <label class="field" for="depot-note">Note (facultatif)</label>
          <input class="input" id="depot-note" name="note" maxlength="400" placeholder="Version, chapitre, correction…">
        </div>
        <div class="jalon-actions">
          <button class="btn-orange" type="submit">Déposer</button>
        </div>
      </form>
    <?php endif; ?>

    <?php if ($depotFiles !== []): ?>
      <ol class="depot-list">
        <?php foreach ($depotFiles as $depot): ?>
          <li class="depot-item<?= !empty($depot['is_withdrawn']) ? ' is-withdrawn' : '' ?><?= !empty($depot['mine']) ? ' is-mine' : '' ?>">
            <div class="depot-item-main">
              <strong class="depot-name"><?= e((string) ($depot['file_name'] ?? 'Fichier')) ?></strong>
              <p class="jalon-meta">
                <?= e((string) ($depot['who'] ?? '')) ?>
                · <?= e((string) ($depot['role_label'] ?? '')) ?>
                <?php if (!empty($depot['when'])): ?> · <?= e((string) $depot['when']) ?><?php endif; ?>
                <?php if (!empty($depot['size_label'])): ?> · <?= e((string) $depot['size_label']) ?><?php endif; ?>
              </p>
              <?php if (!empty($depot['note'])): ?>
                <p class="depot-note"><?= e((string) $depot['note']) ?></p>
              <?php endif; ?>
              <?php if (!empty($depot['is_withdrawn'])): ?>
                <p class="jalon-meta">Retiré — l’historique du dépôt est conservé.</p>
              <?php endif; ?>
            </div>
            <div class="depot-item-stats">
              <span title="Consultations"><?= (int) ($depot['views'] ?? 0) ?> vue<?= (int) ($depot['views'] ?? 0) > 1 ? 's' : '' ?></span>
              <span title="Téléchargements"><?= (int) ($depot['downloads'] ?? 0) ?> tél.</span>
              <?php if (empty($depot['is_withdrawn']) && !empty($depot['mine'])): ?>
                <span class="depot-receipt<?= !empty($depot['seen_by_other']) ? ' is-seen' : '' ?>">
                  <?= !empty($depot['seen_by_other']) ? 'Vu par l’autre partie' : 'En attente de lecture' ?>
                </span>
              <?php endif; ?>
            </div>
            <?php if (empty($depot['is_withdrawn'])): ?>
              <div class="depot-item-actions">
                <a class="btn-ghost" href="<?= e(url((string) $depot['view_href'])) ?>"><?= !empty($depot['can_preview']) ? 'Voir' : 'Détail' ?></a>
                <a class="btn-ghost" href="<?= e(url((string) $depot['download_href'])) ?>">Télécharger</a>
                <?php if (!empty($depot['can_delete'])): ?>
                  <form method="post" action="<?= e(url('/espace/suivi/' . (int) $order['id'] . '/depot/' . (int) $depot['id'] . '/retirer')) ?>">
                    <?= csrf_field() ?>
                    <button class="btn-ghost btn-danger" type="submit" onclick="return confirm('Retirer ce fichier ? L’historique du dépôt reste visible.');">Retirer</button>
                  </form>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php elseif ($depotOpen): ?>
      <p class="jalon-hint depot-empty">Aucun fichier pour l’instant. Le premier dépôt apparaîtra ici, avec son historique d’accès.</p>
    <?php elseif ($status === 'pending'): ?>
      <p class="jalon-hint depot-empty">Revenez ici dès que le devis est accepté : l’espace s’ouvrira automatiquement.</p>
    <?php endif; ?>
  </section>
</div>
