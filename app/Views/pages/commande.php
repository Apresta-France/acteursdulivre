<?php
$service = is_array($service ?? null) ? $service : null;
$package = is_array($selectedPackage ?? null) ? $selectedPackage : null;
$old = is_array($old ?? null) ? $old : [];
$serviceOptions = ($service !== null && is_array($service['options'] ?? null)) ? $service['options'] : [];
$selectedOptionIds = is_array($selectedOptionIds ?? null) ? $selectedOptionIds : [];
$onQuote = $package === null && $service !== null && !isset($service['price_from']);
$baseAmount = $package
    ? (int) ($package['price'] ?? 0)
    : ($onQuote ? 0 : ($service !== null ? (int) ($service['price_from'] ?? 0) : 0));
$optionsTotal = 0;
foreach ($serviceOptions as $option) {
    $optionId = (int) ($option['id'] ?? 0);
    if (in_array($optionId, $selectedOptionIds, true)) {
        $optionsTotal += (int) ($option['price'] ?? 0);
    }
}
$displayTotal = $baseAmount + $optionsTotal;
$wizard = $service !== null
    ? \Adl\Data\Catalog::quoteWizard((string) ($service['cat'] ?? $service['category_name'] ?? ''))
    : ['steps' => []];
$wizardSteps = $wizard['steps'];
$lastStep = count($wizardSteps) + 1;
$recapMap = ['brief' => 'Précision', 'q_date' => 'Date'];
foreach ($wizardSteps as $stepRow) {
    foreach ($stepRow['questions'] as $question) {
        $recapMap['q_' . (string) $question['key']] = (string) $question['recap'];
    }
}
$oldList = static function (string $key) use ($old): array {
    $value = $old['q_' . $key] ?? [];
    return is_array($value) ? array_map('strval', $value) : [$value];
};
?>
<div class="espace-page">
  <div class="espace-page-head">
    <div>
      <h1>Demander un devis</h1>
      <p>Des choix simples, pas de rédaction : le prestataire reçoit un brief clair et peut chiffrer sans aller-retour. Aucun paiement n’est encaissé ici.</p>
    </div>
  </div>

  <?php if (!empty($error)): ?>
    <div class="flash flash-error"><?= e((string) $error) ?></div>
  <?php endif; ?>

  <?php if (!$service): ?>
    <div class="search-empty">
      <strong>Aucune prestation sélectionnée.</strong>
      <span>Choisissez une offre dans l'annuaire pour ouvrir une commande.</span>
      <a class="btn-orange" href="<?= e(url('/prestations')) ?>">Parcourir les prestations</a>
    </div>
  <?php else: ?>
    <div class="publish-grid">
      <form class="param-form quote-wizard" method="post" action="<?= e(url('/espace/commande')) ?>" enctype="multipart/form-data"
            data-quote-wizard data-quote-last="<?= (int) $lastStep ?>"
            data-quote-recap-map="<?= e(json_encode($recapMap, JSON_UNESCAPED_UNICODE)) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="service_id" value="<?= (int) $service['id'] ?>">
        <input type="hidden" name="package_id" value="<?= (int) ($package['id'] ?? 0) ?>">
        <input type="hidden" name="q_mode" value="1">

        <div class="quote-progress" data-quote-progress hidden>
          <div class="quote-progress-meta">
            <span class="quote-progress-title" data-quote-step-title></span>
            <span class="quote-progress-count" data-quote-step-count></span>
          </div>
          <div class="quote-bar"><span class="quote-bar-fill" data-quote-bar></span></div>
        </div>

        <?php foreach ($wizardSteps as $stepRow): ?>
          <section class="quote-step espace-panel" data-quote-step="<?= (int) $stepRow['num'] ?>"
                   data-required="<?= e(implode(',', array_map(static fn (array $q): string => (string) $q['key'], array_filter($stepRow['questions'], static fn (array $q): bool => !empty($q['required']))))) ?>">
            <h2 class="espace-group-title"><?= e((string) $stepRow['title']) ?></h2>
            <?php foreach ($stepRow['questions'] as $question): ?>
              <?php
                $qKey = (string) $question['key'];
                $qName = 'q_' . $qKey;
                $qMulti = !empty($question['multi']);
                $qRequired = !empty($question['required']);
                $qOld = $qMulti ? $oldList($qKey) : [trim((string) ($old[$qName] ?? ''))];
              ?>
              <div class="quote-q">
                <p class="quote-q-label">
                  <?= e((string) $question['label']) ?>
                  <?php if (!$qRequired): ?>
                    <span class="quote-q-tag">facultatif</span>
                  <?php elseif ($qMulti): ?>
                    <span class="quote-q-tag">plusieurs choix possibles</span>
                  <?php endif; ?>
                </p>
                <?php if (!empty($question['help'])): ?>
                  <p class="field-help"><?= e((string) $question['help']) ?></p>
                <?php endif; ?>
                <div class="chip-row">
                  <?php foreach ((array) $question['options'] as $opt): ?>
                    <label class="chip">
                      <input type="<?= $qMulti ? 'checkbox' : 'radio' ?>"
                             name="<?= e($qName) ?><?= $qMulti ? '[]' : '' ?>"
                             value="<?= e((string) $opt) ?>"
                             <?= in_array((string) $opt, $qOld, true) ? ' checked' : '' ?>>
                      <?= e((string) $opt) ?>
                    </label>
                  <?php endforeach; ?>
                </div>
                <?php if (!empty($question['date_when'])): ?>
                  <div class="quote-date" data-quote-date-wrap data-quote-date-trigger="<?= e((string) $question['date_when']) ?>"<?= trim((string) ($old[$qName] ?? '')) !== (string) $question['date_when'] ? ' hidden' : '' ?>>
                    <label class="field" for="q-date">Date souhaitée</label>
                    <input class="input" id="q-date" type="date" name="q_date"
                           value="<?= e((string) ($old['q_date'] ?? '')) ?>"
                           min="<?= e(date('Y-m-d')) ?>">
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>

            <?php if ((int) $stepRow['num'] === 2 && $serviceOptions !== []): ?>
              <div class="quote-q">
                <p class="quote-q-label">Options proposées par le prestataire <span class="quote-q-tag">facultatif</span></p>
                <p class="field-help">Chaque option s'ajoute au prix de la formule ou de la prestation.</p>
                <div class="option-list">
                  <?php foreach ($serviceOptions as $option): ?>
                    <?php $optionId = (int) ($option['id'] ?? 0); ?>
                    <label class="option-row">
                      <input type="checkbox" name="options[]" value="<?= $optionId ?>"
                             data-price="<?= (int) ($option['price'] ?? 0) ?>"
                             <?= in_array($optionId, $selectedOptionIds, true) ? ' checked' : '' ?>>
                      <span><?= e((string) ($option['name'] ?? '')) ?></span>
                      <strong>+<?= e((string) ($option['price_label'] ?? format_euros_ttc((int) ($option['price'] ?? 0)))) ?></strong>
                    </label>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
          </section>
        <?php endforeach; ?>

        <section class="quote-step espace-panel" data-quote-step="<?= (int) $lastStep ?>">
          <h2 class="espace-group-title">Dernières touches</h2>
          <div class="quote-q">
            <label class="quote-q-label" for="brief">Une précision à ajouter ? <span class="quote-q-tag">facultatif</span></label>
            <textarea class="textarea" id="brief" name="brief" rows="4" maxlength="4000"
                      placeholder="Ex. : le roman se passe en Bretagne, j'aimerais une relecture attentive au vocabulaire maritime…"><?= e((string) ($old['brief'] ?? '')) ?></textarea>
            <p class="field-help">L'essentiel est déjà couvert par vos choix — rien à rédiger si ce n'est pas utile.</p>
          </div>
          <div class="quote-q">
            <span class="quote-q-label" id="quote-file-label">Un fichier à joindre ? <span class="quote-q-tag">facultatif</span></span>
            <?php
              $filePickId = 'quote-attachment';
              $filePickName = 'attachment';
              $filePickAccept = '.pdf,.doc,.docx,.odt,.txt,image/jpeg,image/png,image/webp';
              $filePickButton = 'Joindre un extrait ou un cahier des charges';
              $filePickDrop = true;
              $filePickAttrs = 'aria-labelledby="quote-file-label"';
              $filePickHint = 'PDF, DOCX, ODT, TXT ou image — 8 Mo max. Un extrait aide à chiffrer juste.';
              require ADL_ROOT . '/app/Views/partials/file-pick.php';
            ?>
          </div>
          <div class="quote-q">
            <p class="quote-q-label">Votre demande, telle que le prestataire la recevra</p>
            <dl class="quote-recap" data-quote-recap-final></dl>
          </div>
          <div class="quote-submit-row">
            <button class="btn-orange" type="submit">Envoyer ma demande</button>
            <p class="field-help">Le prestataire répond avec un devis ; le règlement se fait ensuite entre vous, hors plateforme.</p>
          </div>
        </section>

        <div class="quote-nav" data-quote-nav hidden>
          <button class="btn-ghost" type="button" data-quote-prev>Retour</button>
          <span class="quote-nav-spacer"></span>
          <button class="btn-orange" type="button" data-quote-next>Continuer</button>
        </div>
      </form>

      <aside class="publish-side" data-order-total data-base="<?= (int) $baseAmount ?>"<?= $onQuote ? ' data-on-quote' : '' ?>>
        <div class="side-card">
          <div class="side-kicker">Récapitulatif</div>
          <strong><?= e((string) $service['title']) ?></strong>
          <p class="mission-row-sub"><?= e((string) $service['by']) ?></p>
          <?php if ($package): ?>
            <div class="side-foot">
              <span><?= e((string) $package['name']) ?><?= !empty($package['delay']) ? ' · ' . e((string) $package['delay']) : '' ?></span>
              <strong><?= e((string) ($package['price_label'] ?? $service['price'])) ?></strong>
            </div>
          <?php else: ?>
            <div class="side-foot">
              <span><?= e((string) ($service['delay'] ?: 'Délai à convenir')) ?></span>
              <strong><?= e((string) $service['price']) ?></strong>
            </div>
          <?php endif; ?>
          <?php if ($serviceOptions !== []): ?>
            <div class="side-foot">
              <span>Total</span>
              <strong data-order-total-value><?= $onQuote ? 'sur devis' : e(format_euros_ttc($displayTotal)) ?></strong>
            </div>
          <?php endif; ?>
          <?php if (!empty($service['startup_enabled'])): ?>
            <div class="side-foot">
              <span>Accompagnement de démarrage</span>
              <strong><?= e((string) $service['startup_label']) ?></strong>
            </div>
          <?php endif; ?>
          <div class="side-foot"><span>Délai de réponse habituel</span><strong>~48 h</strong></div>
        </div>

        <div class="side-card">
          <div class="side-kicker">Votre brief en direct</div>
          <dl class="quote-recap" data-quote-recap-side>
            <div class="quote-recap-row"><dd class="quote-recap-empty">Vos réponses s'affichent au fil des choix.</dd></div>
          </dl>
        </div>

        <div class="side-card side-card-warm">
          <div class="side-kicker">Pourquoi ces questions ?</div>
          <div class="side-title-sm">Un devis juste du premier coup</div>
          <p>Volume, avancement, budget, calendrier : c'est ce dont un prestataire a besoin pour chiffrer. En le demandant d'emblée, on évite l'aller-retour « pouvez-vous préciser… » qui fait perdre des jours.</p>
        </div>
      </aside>
    </div>
  <?php endif; ?>
</div>
