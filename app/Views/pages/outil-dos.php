<?php
$input = is_array($input ?? null) ? $input : [];
$result = is_array($result ?? null) ? $result : [];
$formats = is_array($formats ?? null) ? $formats : [];
$papers = is_array($papers ?? null) ? $papers : [];
$bindings = is_array($bindings ?? null) ? $bindings : [];
$faqs = is_array($faqs ?? null) ? $faqs : [];
$jsConfig = is_array($jsConfig ?? null) ? $jsConfig : [];
$format = (string) ($input['format'] ?? 'roman');
$paper = (string) ($input['paper'] ?? 'bouffant-80');
$binding = (string) ($input['binding'] ?? 'broche');
$pages = (int) ($input['pages'] ?? 280);
$width = (float) ($input['width'] ?? 140);
$height = (float) ($input['height'] ?? 210);
$grammage = (float) ($input['grammage'] ?? 80);
$bulk = (float) ($input['bulk'] ?? 1.8);
$bleed = (float) ($input['bleed'] ?? 5);
$flaps = !empty($input['flaps']);
$flap = (float) ($input['flap'] ?? 80);
$spine = (float) ($result['spine'] ?? 0);
$coverW = (float) ($result['cover_w'] ?? 0);
$coverH = (float) ($result['cover_h'] ?? 0);
$sheet = (float) ($result['sheet'] ?? 0);
$parts = is_array($result['parts'] ?? null) ? $result['parts'] : [];
$warnings = is_array($result['warnings'] ?? null) ? $result['warnings'] : [];
$summary = (string) ($result['summary'] ?? '');
$fmt = static fn (float $n, int $d = 1): string => \Adl\Data\Tools::formatNumber($n, $d);
?>
<div class="mk-page tool-page" data-spine-tool data-spine-config="<?= e(json_encode($jsConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
  <section class="forum-hero forum-hero-compact">
    <nav class="search-crumb" aria-label="Fil d'Ariane">
      <a href="<?= e(url('/')) ?>">Accueil</a>
      <span aria-hidden="true"> · </span>
      <a href="<?= e(url('/outils')) ?>">Outils</a>
      <span aria-hidden="true"> · </span>
      <span>Dos et couverture</span>
    </nav>
    <h1>Calculateur de dos et couverture</h1>
    <p class="forum-lead">Format, pagination, papier. Vous obtenez la largeur de dos, les fonds perdus et la taille du PDF. L’imprimeur confirme sur la rame réelle.</p>
  </section>

  <div class="tool-layout">
    <div class="tool-board espace-panel">
      <form class="tool-spine-fields" method="get" action="<?= e(url('/outils/dos')) ?>" data-spine-form>
        <div class="tool-number-row">
          <div>
            <label class="field" for="spine-format">Format intérieur</label>
            <select class="input" id="spine-format" name="f" data-spine-format>
              <?php foreach ($formats as $value => $item): ?>
                <option value="<?= e((string) $value) ?>"<?= $format === $value ? ' selected' : '' ?>><?= e((string) ($item['label'] ?? $value)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="field" for="spine-pages">Pages</label>
            <input class="input" id="spine-pages" name="p" inputmode="numeric" value="<?= e((string) $pages) ?>" data-spine-pages>
          </div>
        </div>

        <div class="tool-number-row tool-custom-row<?= $format === 'custom' ? '' : ' is-hidden' ?>" data-spine-custom-format<?= $format === 'custom' ? '' : ' hidden' ?>>
          <div>
            <label class="field" for="spine-width">Largeur (mm)</label>
            <input class="input" id="spine-width" name="w" inputmode="decimal" value="<?= e($fmt($width, 1)) ?>" data-spine-width>
          </div>
          <div>
            <label class="field" for="spine-height">Hauteur (mm)</label>
            <input class="input" id="spine-height" name="h" inputmode="decimal" value="<?= e($fmt($height, 1)) ?>" data-spine-height>
          </div>
        </div>

        <div class="tool-number-row">
          <div>
            <label class="field" for="spine-paper">Papier intérieur</label>
            <select class="input" id="spine-paper" name="papier" data-spine-paper>
              <?php foreach ($papers as $value => $item): ?>
                <option value="<?= e((string) $value) ?>"<?= $paper === $value ? ' selected' : '' ?>><?= e((string) ($item['label'] ?? $value)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="field" for="spine-binding">Façonnage</label>
            <select class="input" id="spine-binding" name="r" data-spine-binding>
              <?php foreach ($bindings as $value => $label): ?>
                <option value="<?= e((string) $value) ?>"<?= $binding === $value ? ' selected' : '' ?>><?= e((string) $label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="tool-number-row tool-custom-row<?= $paper === 'custom' ? '' : ' is-hidden' ?>" data-spine-custom-paper<?= $paper === 'custom' ? '' : ' hidden' ?>>
          <div>
            <label class="field" for="spine-grammage">Grammage (g/m²)</label>
            <input class="input" id="spine-grammage" name="g" inputmode="decimal" value="<?= e($fmt($grammage, 0)) ?>" data-spine-grammage>
          </div>
          <div>
            <label class="field" for="spine-bulk">Volume (cm³/g)</label>
            <input class="input" id="spine-bulk" name="v" inputmode="decimal" value="<?= e($fmt($bulk, 2)) ?>" data-spine-bulk>
          </div>
        </div>

        <div class="tool-number-row">
          <div>
            <label class="field" for="spine-bleed">Fond perdu (mm)</label>
            <input class="input" id="spine-bleed" name="b" inputmode="decimal" value="<?= e($fmt($bleed, 0)) ?>" data-spine-bleed>
          </div>
          <div>
            <span class="field" id="spine-flaps-label">Rabats</span>
            <label class="tool-switch<?= $flaps ? ' is-on' : '' ?>" for="spine-flaps">
              <input type="checkbox" id="spine-flaps" name="rabats" value="1"<?= $flaps ? ' checked' : '' ?> data-spine-flaps<?= $binding === 'relie' ? ' disabled' : '' ?> aria-labelledby="spine-flaps-label">
              <span data-spine-flaps-label><?= $binding === 'relie' ? 'Pas sur un cartonnage' : 'Broché avec rabats' ?></span>
            </label>
          </div>
        </div>

        <div class="tool-number-row tool-custom-row<?= $flaps ? '' : ' is-hidden' ?>" data-spine-flap-wrap<?= $flaps ? '' : ' hidden' ?>>
          <div>
            <label class="field" for="spine-flap">Largeur de rabat (mm)</label>
            <input class="input" id="spine-flap" name="rw" inputmode="decimal" value="<?= e($fmt($flap, 0)) ?>" data-spine-flap>
          </div>
          <p class="tool-field-note">Un rabat courant fait 70 à 100 mm. Il s’ajoute de chaque côté du PDF.</p>
        </div>

        <noscript>
          <button class="btn-navy" type="submit">Calculer le dos</button>
        </noscript>
      </form>

      <?php
        $spreadTotal = 0.0;
        foreach ($parts as $part) {
            $spreadTotal += (float) ($part['mm'] ?? 0);
        }
        if ($spreadTotal < 1) {
            $spreadTotal = 1;
        }
        $spreadH = (int) round(min(188, max(112, $height * 0.62)));
        $bleedPx = (int) round(min(28, max(6, $bleed * 2.2)));
      ?>
      <div class="tool-spread-wrap" data-spine-spread-wrap style="--spread-h: <?= $spreadH ?>px; --bleed-px: <?= $bleedPx ?>px">
        <p class="tool-spread-bleed-label">Fond perdu <span data-spine-out="bleed-label"><?= e(\Adl\Data\Spine::formatMm($bleed, 0)) ?></span></p>
        <div class="tool-spread-bleed" data-spine-bleed-frame>
          <div class="tool-spread" data-spine-spread aria-hidden="true">
            <?php foreach ($parts as $part): ?>
              <?php
                $partId = (string) ($part['id'] ?? '');
                $partMm = (float) ($part['mm'] ?? 0);
                $pct = $partMm / $spreadTotal * 100;
                $partDecimals = $partId === 'spine' ? 1 : 0;
              ?>
              <div class="tool-spread-part is-<?= e($partId) ?>" style="flex: 0 0 <?= e(number_format($pct, 2, '.', '')) ?>%">
                <strong><?= e((string) ($part['label'] ?? '')) ?></strong>
                <span><?= e(\Adl\Data\Spine::formatMm($partMm, $partDecimals)) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <p class="tool-spread-caption" data-spine-caption>À plat, face imprimée. Dos <?= e(\Adl\Data\Spine::formatMm($spine, 1)) ?> · hauteur <?= e(\Adl\Data\Spine::formatMm($height, 0)) ?>.</p>
      </div>

      <div class="tool-results" data-spine-results aria-live="polite">
        <div class="tool-stat">
          <strong data-spine-out="spine"><?= e(\Adl\Data\Spine::formatMm($spine, 1)) ?></strong>
          <span>Largeur de dos</span>
        </div>
        <div class="tool-stat">
          <strong data-spine-out="cover"><?= e(\Adl\Data\Spine::formatSize($coverW, $coverH)) ?></strong>
          <span>PDF couverture</span>
        </div>
        <div class="tool-stat">
          <strong data-spine-out="trim"><?= e(\Adl\Data\Spine::formatSize($width, $height)) ?></strong>
          <span>Format intérieur</span>
        </div>
        <div class="tool-stat">
          <strong data-spine-out="sheet"><?= e(\Adl\Data\Spine::formatMm($sheet, 2)) ?></strong>
          <span>Épaisseur d’une feuille</span>
        </div>
        <div class="tool-stat">
          <strong data-spine-out="pages"><?= e((string) $pages) ?></strong>
          <span>Pages</span>
        </div>
        <div class="tool-stat">
          <strong data-spine-out="bleed"><?= e(\Adl\Data\Spine::formatMm($bleed, 0)) ?></strong>
          <span>Fond perdu</span>
        </div>
      </div>

      <div class="tool-warn" data-spine-warnings<?= $warnings === [] ? ' hidden' : '' ?>>
        <?php foreach ($warnings as $warning): ?>
          <p><?= e((string) $warning) ?></p>
        <?php endforeach; ?>
      </div>

      <div class="tool-actions">
        <button type="button" class="btn-ghost" data-spine-copy>Copier le résultat</button>
        <button type="button" class="btn-ghost" data-spine-reset>Réinitialiser</button>
      </div>
    </div>

    <aside class="tool-side">
      <div class="side-card">
        <div class="side-kicker">Méthode</div>
        <p>Dos = <strong>(pages / 2) × grammage × volume / 1&nbsp;000</strong>. Le volume, ou main du papier, change tout : un bouffant 80&nbsp;g n’a pas le dos d’un offset 80&nbsp;g.</p>
        <p>Le fond perdu français courant est de <strong>5&nbsp;mm</strong>. Sans lui, un filet blanc apparaît au massicot.</p>
      </div>

      <div class="side-card">
        <div class="side-kicker">Maquette</div>
        <p>Le maquettiste a besoin du papier nommé, pas d’un « bouffant » tout court. Changer de grammage après la couverture, c’est refaire un BAT.</p>
        <a href="<?= e(url('/besoin/maquette-de-livre')) ?>">Trouver un maquettiste →</a>
      </div>

      <div class="side-card">
        <div class="side-kicker">Impression</div>
        <p>Ces cotes sont un brief. L’imprimeur mesure la rame. Un roman broché de 300 exemplaires se situe souvent entre 1&nbsp;100 et 1&nbsp;600&nbsp;€ hors port.</p>
        <a href="<?= e(url('/besoin/imprimer-un-livre')) ?>">Trouver un imprimeur →</a>
      </div>

      <div class="side-card">
        <div class="side-kicker">Papier</div>
        <p>Bouffant pour le roman français, offset plus compact, couché pour l’image. La main se juge dans la main, pas à l’écran.</p>
        <a href="<?= e(url('/journal/choisir-papier-bouffant-offset-recycle')) ?>">Bouffant, offset, recyclé →</a>
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
  <p class="is-hidden" data-spine-summary hidden><?= e($summary) ?></p>
</div>
