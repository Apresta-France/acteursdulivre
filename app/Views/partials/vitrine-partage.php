<?php
$kit = $shareKit ?? [];
$ready = !empty($kit['ready']);
$url = (string) ($kit['url'] ?? '');
$tagline = (string) ($kit['tagline'] ?? \Adl\Data\ProfileShare::TAGLINE);
?>
<div data-tab-panel="partage" class="vitrine-partage"<?= ($tab ?? '') === 'partage' ? '' : ' hidden' ?>>
  <section class="espace-panel">
    <div class="espace-panel-head">
      <h2 class="espace-section-title">Partagez votre profil</h2>
      <p class="espace-section-lead">Vos statistiques de vues sont disponibles dans votre espace. Plus vous faites circuler votre fiche, plus elle est ouverte.</p>
    </div>
    <div class="share-kit-incentive">
      <span class="dash-ico dash-ico-accent"><?= icon('chart', 18) ?></span>
      <div>
        <strong>Partagez votre profil : vos statistiques de vues sont disponibles dans votre espace.</strong>
        <em>Lien, visuels, QR code, signature et badge reprennent la phrase « <?= e($tagline) ?> ».</em>
      </div>
      <a class="btn-ghost" href="<?= e(url('/espace/statistiques')) ?>">Voir les vues</a>
    </div>
  </section>

  <?php if (!$ready): ?>
    <section class="espace-panel">
      <p class="espace-section-lead" style="margin: 0;">Enregistrez d’abord votre identité pour obtenir l’adresse publique de la fiche. Les outils de partage apparaîtront ensuite ici.</p>
      <div class="auth-actions" style="margin-top: 16px;">
        <button type="button" class="btn-orange" data-tab-goto="identite">Compléter l’identité</button>
      </div>
    </section>
  <?php else: ?>
    <?php if (empty($kit['visible'])): ?>
      <div class="flash flash-error">Votre fiche n’est pas encore visible dans l’annuaire. Les supports sont prêts : ils pointeront vers votre page dès qu’elle sera en ligne.</div>
    <?php endif; ?>

    <section class="espace-panel share-kit-block">
      <h3 class="espace-group-title">Lien vers la fiche</h3>
      <p class="field-help">À coller dans une bio Instagram, un site, une carte de visite ou un message.</p>
      <div class="share-kit-copy">
        <input class="input" type="url" readonly value="<?= e($url) ?>" aria-label="Lien public de la fiche">
        <button type="button" class="btn-navy" data-copy="<?= e($url) ?>">Copier</button>
        <a class="btn-ghost" href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer">Ouvrir</a>
      </div>
      <?php
        $shareUrl = $url;
        $shareTitle = (string) ($kit['share_title'] ?? '');
        $shareText = (string) ($kit['share_text'] ?? $tagline);
        $shareLabel = 'Diffuser';
        $shareCompact = false;
        $shareNative = true;
        require ADL_ROOT . '/app/Views/partials/share.php';
      ?>
    </section>

    <section class="espace-panel share-kit-block">
      <h3 class="espace-group-title">Visuels à télécharger</h3>
      <p class="field-help">Enregistrez le PNG pour Instagram, LinkedIn ou Facebook, puis ajoutez le lien de la fiche dans la légende.</p>
      <div class="share-kit-visuals">
        <?php foreach ($kit['visuals'] ?? [] as $visual): ?>
          <article class="share-kit-card" data-share-visual data-file="<?= e((string) $visual['file']) ?>" data-width="<?= (int) $visual['width'] ?>" data-height="<?= (int) $visual['height'] ?>">
            <div class="share-kit-preview is-<?= e((string) $visual['id']) ?>"><?= $visual['svg'] ?></div>
            <strong><?= e((string) $visual['label']) ?></strong>
            <span><?= e((string) $visual['hint']) ?></span>
            <div class="share-kit-actions">
              <button type="button" class="btn-navy" data-share-png>Télécharger le PNG</button>
              <button type="button" class="btn-ghost" data-share-svg>SVG</button>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="espace-panel share-kit-block">
      <h3 class="espace-group-title">QR code</h3>
      <p class="field-help">À imprimer sur une carte, un flyer ou un signet. Le scan ouvre votre fiche.</p>
      <div class="share-kit-qr" data-share-visual data-file="acteursdulivre-qr" data-width="512" data-height="512">
        <div class="share-kit-qr-frame"><?= $kit['qr_svg'] ?? '' ?></div>
        <div class="share-kit-actions">
          <button type="button" class="btn-navy" data-share-png>Télécharger le PNG</button>
          <button type="button" class="btn-ghost" data-share-svg>SVG</button>
        </div>
      </div>
    </section>

    <section class="espace-panel share-kit-block">
      <h3 class="espace-group-title">Signature e-mail</h3>
      <p class="field-help">Collez ce bloc HTML dans la signature de votre messagerie (Gmail, Outlook, Apple Mail).</p>
      <div class="share-kit-embed-preview"><?= $kit['signature_html'] ?? '' ?></div>
      <textarea class="textarea share-kit-code" id="share-kit-signature" readonly rows="6" aria-label="Code HTML de la signature"><?= e((string) ($kit['signature_html'] ?? '')) ?></textarea>
      <div class="share-kit-actions">
        <button type="button" class="btn-navy" data-copy-from="#share-kit-signature">Copier le HTML</button>
      </div>
    </section>

    <section class="espace-panel share-kit-block">
      <h3 class="espace-group-title">Badge pour votre site</h3>
      <p class="field-help">À placer en pied de page ou dans une page « Prestations ». Le bouton renvoie vers votre fiche.</p>
      <div class="share-kit-embed-preview is-badge"><?= $kit['badge_html'] ?? '' ?></div>
      <textarea class="textarea share-kit-code" id="share-kit-badge" readonly rows="5" aria-label="Code HTML du badge"><?= e((string) ($kit['badge_html'] ?? '')) ?></textarea>
      <div class="share-kit-actions">
        <button type="button" class="btn-navy" data-copy-from="#share-kit-badge">Copier le HTML</button>
        <button type="button" class="btn-ghost" data-copy="<?= e((string) ($kit['badge_svg'] ?? '')) ?>">Copier le SVG</button>
      </div>
    </section>
  <?php endif; ?>
</div>
