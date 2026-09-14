<?php
$stats = is_array($stats ?? null) ? $stats : [];
$unitLabels = is_array($unitLabels ?? null) ? $unitLabels : [];
$rateUnitLabels = is_array($rateUnitLabels ?? null) ? $rateUnitLabels : [];
$faqs = is_array($faqs ?? null) ? $faqs : [];
$jsConfig = is_array($jsConfig ?? null) ? $jsConfig : [];
$unit = (string) ($unit ?? 'signes');
$amount = $amount ?? null;
$rate = $rate ?? null;
$rateUnit = (string) ($rateUnit ?? 'feuillets');
$simulated = $simulated ?? null;
$sec = (int) ($stats['sec'] ?? 0);
$senc = (int) ($stats['senc'] ?? 0);
$words = (int) ($stats['words'] ?? 0);
$feuillets = (float) ($stats['feuillets'] ?? 0);
$readMin = (float) ($stats['read_min'] ?? 0);
$audioMin = (float) ($stats['audio_min'] ?? 0);
$hasVolume = $sec > 0;
$correction = $hasVolume ? \Adl\Data\Tools::correctionRange($stats) : null;
$translation = $hasVolume ? \Adl\Data\Tools::translationRange($stats) : null;
$startMode = $amount !== null ? 'number' : 'text';
$amountValue = $amount !== null ? \Adl\Data\Tools::formatNumber((float) $amount, 2) : '';
$rateValue = $rate !== null ? \Adl\Data\Tools::formatNumber((float) $rate, 2) : '';
$hasSimulated = $simulated !== null && (float) $simulated > 0;
?>
<div class="mk-page tool-page" data-volume-tool data-volume-config="<?= e(json_encode($jsConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
  <section class="forum-hero forum-hero-compact">
    <nav class="search-crumb" aria-label="Fil d'Ariane">
      <a href="<?= e(url('/')) ?>">Accueil</a>
      <span aria-hidden="true"> · </span>
      <a href="<?= e(url('/communaute')) ?>">Communauté</a>
      <span aria-hidden="true"> · </span>
      <a href="<?= e(url('/outils')) ?>">Outils</a>
      <span aria-hidden="true"> · </span>
      <span>Signes et feuillets</span>
    </nav>
    <h1>Compteur de signes et feuillets</h1>
    <p class="forum-lead">Collez un manuscrit, un chapitre, une quatrième de couverture — ou partez d’un nombre. Le feuillet vaut 1&nbsp;500 signes espaces compris.</p>
  </section>

  <div class="tool-layout">
    <div class="tool-board espace-panel">
      <div class="tool-modes" role="tablist" aria-label="Mode de saisie">
        <button type="button" class="tool-mode<?= $startMode === 'text' ? ' is-on' : '' ?>" role="tab" id="volume-tab-text" aria-selected="<?= $startMode === 'text' ? 'true' : 'false' ?>" aria-controls="volume-panel-text" data-volume-mode="text">Coller un texte</button>
        <button type="button" class="tool-mode<?= $startMode === 'number' ? ' is-on' : '' ?>" role="tab" id="volume-tab-number" aria-selected="<?= $startMode === 'number' ? 'true' : 'false' ?>" aria-controls="volume-panel-number" data-volume-mode="number">Partir d’un nombre</button>
      </div>

      <div class="tool-panel<?= $startMode === 'text' ? '' : ' is-hidden' ?>" id="volume-panel-text" role="tabpanel" aria-labelledby="volume-tab-text"<?= $startMode === 'text' ? '' : ' hidden' ?>>
        <label class="field" for="volume-text">Texte à compter</label>
        <textarea class="textarea tool-textarea" id="volume-text" data-volume-text maxlength="500000" rows="12" placeholder="Collez ici le chapitre, le manuscrit, ou un extrait…"></textarea>
        <p class="field-help">Le décompte se fait dans votre navigateur. Le texte n’est pas envoyé, ni enregistré.</p>
      </div>

      <form class="tool-panel<?= $startMode === 'number' ? '' : ' is-hidden' ?>" id="volume-panel-number" role="tabpanel" aria-labelledby="volume-tab-number" method="get" action="<?= e(url('/outils/volume')) ?>"<?= $startMode === 'number' ? '' : ' hidden' ?>>
        <div class="tool-number-row">
          <div>
            <label class="field" for="volume-amount">Volume</label>
            <input class="input" id="volume-amount" name="n" inputmode="decimal" value="<?= e($amountValue) ?>" placeholder="520 000" data-volume-amount>
          </div>
          <div>
            <label class="field" for="volume-unit">Unité</label>
            <select class="input" id="volume-unit" name="u" data-volume-unit>
              <?php foreach ($unitLabels as $value => $label): ?>
                <option value="<?= e((string) $value) ?>"<?= $unit === $value ? ' selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <noscript>
          <button class="btn-navy" type="submit">Convertir</button>
        </noscript>
      </form>

      <div class="tool-results" data-volume-results aria-live="polite">
        <div class="tool-stat">
          <strong data-volume-sec><?= $hasVolume ? e(\Adl\Data\Tools::formatNumber((float) $sec, 0)) : '—' ?></strong>
          <span>Signes espaces compris</span>
        </div>
        <div class="tool-stat">
          <strong data-volume-senc><?= $hasVolume ? e(\Adl\Data\Tools::formatNumber((float) $senc, 0)) : '—' ?></strong>
          <span>Signes hors espaces</span>
        </div>
        <div class="tool-stat">
          <strong data-volume-words><?= $hasVolume ? e(\Adl\Data\Tools::formatNumber((float) $words, 0)) : '—' ?></strong>
          <span>Mots</span>
        </div>
        <div class="tool-stat">
          <strong data-volume-feuillets><?= $hasVolume ? e(\Adl\Data\Tools::formatNumber($feuillets, 1)) : '—' ?></strong>
          <span>Feuillets (1 500)</span>
        </div>
        <div class="tool-stat">
          <strong data-volume-read><?= $hasVolume ? e(\Adl\Data\Tools::formatDuration($readMin)) : '—' ?></strong>
          <span>Lecture silencieuse</span>
        </div>
        <div class="tool-stat">
          <strong data-volume-audio><?= $hasVolume ? e(\Adl\Data\Tools::formatDuration($audioMin)) : '—' ?></strong>
          <span>Narration audio</span>
        </div>
      </div>

      <div class="tool-actions">
        <button type="button" class="btn-ghost" data-volume-copy<?= $hasVolume ? '' : ' disabled' ?>>Copier le résultat</button>
        <button type="button" class="btn-ghost" data-volume-reset>Effacer</button>
      </div>
    </div>

    <aside class="tool-side">
      <div class="side-card tool-rate">
        <div class="side-kicker">Votre tarif</div>
        <div class="tool-rate-row">
          <div>
            <label class="field" for="volume-rate">Prix</label>
            <input class="input" id="volume-rate" name="tarif" form="volume-panel-number" inputmode="decimal" value="<?= e($rateValue) ?>" placeholder="2,50" data-volume-rate>
          </div>
          <div>
            <label class="field" for="volume-rate-unit">Unité</label>
            <select class="input" id="volume-rate-unit" name="tu" form="volume-panel-number" data-volume-rate-unit>
              <?php foreach ($rateUnitLabels as $value => $label): ?>
                <option value="<?= e((string) $value) ?>"<?= $rateUnit === $value ? ' selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <p class="tool-rate-total<?= $hasSimulated ? '' : ' is-empty' ?>" data-volume-rate-total><?= $hasSimulated ? e(\Adl\Data\Tools::formatEuros((float) $simulated)) : '—' ?></p>
        <p class="tool-rate-hint" data-volume-rate-hint><?php
          if ($hasSimulated) {
              echo 'Volume × votre tarif. Ce n’est pas un devis.';
          } elseif ($rate !== null && !$hasVolume) {
              echo 'Indiquez d’abord un volume, à gauche.';
          } else {
              echo 'Correction : souvent au feuillet. Traduction : souvent au mot.';
          }
        ?></p>
      </div>

      <div class="side-card">
        <div class="side-kicker">Méthode</div>
        <p>Feuillet = <strong>1&nbsp;500 signes espaces compris</strong>. Un mot français pèse en moyenne 6 signes (espace comprise) quand on part d’un nombre, pas d’un texte réel.</p>
        <p>Lecture vers 230 mots/min, narration vers 155 mots/min. Une page dactylo correspond ici à un feuillet.</p>
      </div>

      <div class="side-card tool-estimate" data-volume-correction<?= $correction ? '' : ' hidden' ?>>
        <div class="side-kicker">Correction</div>
        <p>Pour ce volume, une passe orthotypographique se situe souvent entre <strong data-volume-correction-range><?php
          if ($correction) {
              echo e(format_int($correction['low']) . ' et ' . format_int($correction['high']) . ' €');
          }
        ?></strong>.</p>
        <a href="<?= e(url('/besoin/corriger-un-manuscrit')) ?>">Trouver un correcteur →</a>
      </div>

      <div class="side-card tool-estimate" data-volume-translation<?= $translation ? '' : ' hidden' ?>>
        <div class="side-kicker">Traduction</div>
        <p>Au mot source, le marché 2026 tourne autour de <strong data-volume-translation-range><?php
          if ($translation) {
              echo e(format_int($translation['low']) . ' et ' . format_int($translation['high']) . ' €');
          }
        ?></strong>.</p>
        <a href="<?= e(url('/besoin/traduire-un-livre')) ?>">Trouver un traducteur →</a>
      </div>

      <div class="side-card">
        <div class="side-kicker">Livre audio</div>
        <p>La durée ci-contre est le texte lu. Le studio dure souvent trois à cinq fois plus longtemps.</p>
        <a href="<?= e(url('/journal/livre-audio-temps-studio-300-pages')) ?>">Temps de studio pour 300 pages →</a>
      </div>
    </aside>
  </div>

  <?php if ($faqs !== []): ?>
    <section class="mk-block tool-faq">
      <h2>Questions fréquentes</h2>
      <div class="mk-faq-list">
        <?php foreach ($faqs as $f): ?>
          <div>
            <button type="button" data-accordion aria-expanded="false" class="faq-q">
              <?= e((string) ($f['q'] ?? '')) ?><span data-accordion-sign>+</span>
            </button>
            <div hidden class="mk-faq-a">
              <p><?= e((string) ($f['a'] ?? '')) ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</div>
