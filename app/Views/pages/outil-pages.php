<?php
$input = is_array($input ?? null) ? $input : [];
$result = is_array($result ?? null) ? $result : [];
$formats = is_array($formats ?? null) ? $formats : [];
$gabarits = is_array($gabarits ?? null) ? $gabarits : [];
$unitLabels = is_array($unitLabels ?? null) ? $unitLabels : [];
$faqs = is_array($faqs ?? null) ? $faqs : [];
$jsConfig = is_array($jsConfig ?? null) ? $jsConfig : [];
$amount = (float) ($input['amount'] ?? 520000);
$unit = (string) ($input['unit'] ?? 'signes');
$format = (string) ($input['format'] ?? 'roman');
$gabarit = (string) ($input['gabarit'] ?? 'roman');
$width = (float) ($input['width'] ?? 140);
$height = (float) ($input['height'] ?? 210);
$corps = (float) ($input['corps'] ?? 11);
$leading = (float) ($input['leading'] ?? 13.5);
$extra = (int) ($input['extra'] ?? 8);
$chapters = (int) ($input['chapters'] ?? 0);
$signsPerLine = (int) ($result['signs_per_line'] ?? 0);
$lines = (int) ($result['lines'] ?? 0);
$signsPerPage = (int) ($result['signs_per_page'] ?? 0);
$textPages = (int) ($result['text_pages'] ?? 0);
$bookPages = (int) ($result['book_pages'] ?? 0);
$sig16 = (int) ($result['sig16'] ?? 0);
$sig8 = (int) ($result['sig8'] ?? 0);
$sig4 = (int) ($result['sig4'] ?? 0);
$textW = (float) ($result['text_w'] ?? 0);
$textH = (float) ($result['text_h'] ?? 0);
$inner = (float) ($result['inner'] ?? 0);
$outer = (float) ($result['outer'] ?? 0);
$head = (float) ($result['head'] ?? 0);
$foot = (float) ($result['foot'] ?? 0);
$warnings = is_array($result['warnings'] ?? null) ? $result['warnings'] : [];
$summary = (string) ($result['summary'] ?? '');
$note = (string) ($result['note'] ?? '');
$spine = (string) ($result['spine'] ?? '/outils/dos');
$has = !empty($result['has']);
$fmt = static fn (float $n, int $d = 1): string => \Adl\Data\Tools::formatNumber($n, $d);
$pct = static function (float $part, float $whole): string {
    if ($whole <= 0) {
        return '0';
    }
    return number_format($part / $whole * 100, 2, '.', '');
};
$dash = '—';
?>
<div class="mk-page tool-page" data-pages-tool data-pages-config="<?= e(json_encode($jsConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>" data-dos-base="<?= e(url('/outils/dos')) ?>">
  <section class="forum-hero forum-hero-compact">
    <nav class="search-crumb" aria-label="Fil d'Ariane">
      <a href="<?= e(url('/')) ?>">Accueil</a>
      <span aria-hidden="true"> · </span>
      <a href="<?= e(url('/outils')) ?>">Outils</a>
      <span aria-hidden="true"> · </span>
      <span>Pages estimées</span>
    </nav>
    <h1>Combien de pages pour un manuscrit</h1>
    <p class="forum-lead">Indiquez un volume et un format. Vous obtenez les signes par ligne, les pages de texte et l’arrondi au cahier. Le maquettiste confirme sur la police réelle.</p>
  </section>

  <div class="tool-layout">
    <div class="tool-board espace-panel">
      <form class="tool-pages-fields" method="get" action="<?= e(url('/outils/pages')) ?>" data-pages-form>
        <div class="tool-number-row">
          <div>
            <label class="field" for="pages-amount">Volume</label>
            <input class="input" id="pages-amount" name="n" inputmode="decimal" value="<?= e($fmt($amount, 1)) ?>" data-pages-amount>
          </div>
          <div>
            <label class="field" for="pages-unit">Unité</label>
            <select class="input" id="pages-unit" name="u" data-pages-unit>
              <?php foreach ($unitLabels as $value => $label): ?>
                <option value="<?= e((string) $value) ?>"<?= $unit === $value ? ' selected' : '' ?>><?= e((string) $label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="tool-number-row">
          <div>
            <label class="field" for="pages-format">Format fini</label>
            <select class="input" id="pages-format" name="f" data-pages-format>
              <?php foreach ($formats as $value => $item): ?>
                <option value="<?= e((string) $value) ?>"<?= $format === $value ? ' selected' : '' ?>><?= e((string) ($item['label'] ?? $value)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="field" for="pages-gabarit">Gabarit</label>
            <select class="input" id="pages-gabarit" name="gab" data-pages-gabarit>
              <?php foreach ($gabarits as $value => $item): ?>
                <option value="<?= e((string) $value) ?>"<?= $gabarit === $value ? ' selected' : '' ?>><?= e((string) ($item['label'] ?? $value)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="tool-number-row tool-custom-row<?= $format === 'custom' ? '' : ' is-hidden' ?>" data-pages-custom-format<?= $format === 'custom' ? '' : ' hidden' ?>>
          <div>
            <label class="field" for="pages-width">Largeur (mm)</label>
            <input class="input" id="pages-width" name="w" inputmode="decimal" value="<?= e($fmt($width, 1)) ?>" data-pages-width>
          </div>
          <div>
            <label class="field" for="pages-height">Hauteur (mm)</label>
            <input class="input" id="pages-height" name="h" inputmode="decimal" value="<?= e($fmt($height, 1)) ?>" data-pages-height>
          </div>
        </div>

        <div class="tool-number-row tool-custom-row<?= $gabarit === 'libre' ? '' : ' is-hidden' ?>" data-pages-libre<?= $gabarit === 'libre' ? '' : ' hidden' ?>>
          <div>
            <label class="field" for="pages-corps">Corps (pt)</label>
            <input class="input" id="pages-corps" name="c" inputmode="decimal" value="<?= e($fmt($corps, 1)) ?>" data-pages-corps>
          </div>
          <div>
            <label class="field" for="pages-leading">Interligne (pt)</label>
            <input class="input" id="pages-leading" name="l" inputmode="decimal" value="<?= e($fmt($leading, 1)) ?>" data-pages-leading>
          </div>
        </div>

        <div class="tool-number-row">
          <div>
            <label class="field" for="pages-extra">Pages hors texte</label>
            <input class="input" id="pages-extra" name="ht" inputmode="numeric" value="<?= e((string) $extra) ?>" data-pages-extra>
            <p class="field-help">Faux-titre, titre, copyright, blancs. Souvent 8 à 16.</p>
          </div>
          <div>
            <label class="field" for="pages-chapters">Chapitres</label>
            <input class="input" id="pages-chapters" name="ch" inputmode="numeric" value="<?= e((string) $chapters) ?>" data-pages-chapters>
            <p class="field-help">Chaque ouverture laisse souvent une demi-page blanche.</p>
          </div>
        </div>

        <p class="field-help" data-pages-note><?= e($note) ?></p>

        <noscript>
          <button class="btn-navy" type="submit">Estimer les pages</button>
        </noscript>
      </form>

      <div class="tool-page-preview">
        <div class="tool-page-sheet" data-pages-sheet style="aspect-ratio: <?= e(number_format($width, 2, '.', '')) ?> / <?= e(number_format($height, 2, '.', '')) ?>" aria-hidden="true">
          <div class="tool-page-block" data-pages-block style="top: <?= e($pct($head, $height)) ?>%; right: <?= e($pct($outer, $width)) ?>%; bottom: <?= e($pct($foot, $height)) ?>%; left: <?= e($pct($inner, $width)) ?>%; --lines: <?= e((string) max(1, $lines)) ?>"></div>
        </div>
        <p class="tool-spread-caption" data-pages-caption>Bloc de texte <?= e(\Adl\Data\Spine::formatSize($textW, $textH)) ?>.</p>
      </div>

      <div class="tool-results" data-pages-results aria-live="polite">
        <div class="tool-stat">
          <strong data-pages-out="signs-line"><?= $signsPerLine > 0 ? e((string) $signsPerLine) : $dash ?></strong>
          <span>Signes par ligne</span>
        </div>
        <div class="tool-stat">
          <strong data-pages-out="lines"><?= $lines > 0 ? e((string) $lines) : $dash ?></strong>
          <span>Lignes par page</span>
        </div>
        <div class="tool-stat">
          <strong data-pages-out="signs-page"><?= $signsPerPage > 0 ? e(\Adl\Data\Tools::formatNumber((float) $signsPerPage, 0)) : $dash ?></strong>
          <span>Signes par page</span>
        </div>
        <div class="tool-stat">
          <strong data-pages-out="text"><?= $has ? e(format_int($textPages)) : $dash ?></strong>
          <span>Pages de texte</span>
        </div>
        <div class="tool-stat">
          <strong data-pages-out="book"><?= $has ? e(format_int($bookPages)) : $dash ?></strong>
          <span>Avec le hors-texte</span>
        </div>
        <div class="tool-stat is-emphasis">
          <strong data-pages-out="sig16"><?= $has ? e(format_int($sig16)) : $dash ?></strong>
          <span>Cahier de 16</span>
        </div>
      </div>

      <p class="tool-page-sigs" data-pages-sigs<?= $has ? '' : ' hidden' ?>>Cahier de 8 : <strong data-pages-out="sig8"><?= e(format_int($sig8)) ?></strong> pages · cahier de 4 : <strong data-pages-out="sig4"><?= e(format_int($sig4)) ?></strong> pages. Le numérique peut garder <strong data-pages-out="book-inline"><?= e(format_int($bookPages)) ?></strong>.</p>

      <div class="tool-warn" data-pages-warnings<?= $warnings === [] ? ' hidden' : '' ?>>
        <?php foreach ($warnings as $warning): ?>
          <p><?= e((string) $warning) ?></p>
        <?php endforeach; ?>
      </div>

      <div class="tool-actions">
        <a class="btn-navy" data-pages-dos href="<?= e(url($spine)) ?>"<?= $has ? '' : ' hidden' ?>>Voir le dos sur <?= e(format_int($sig16)) ?> pages →</a>
        <button type="button" class="btn-ghost" data-pages-copy<?= $has ? '' : ' disabled' ?>>Copier le résultat</button>
        <button type="button" class="btn-ghost" data-pages-reset>Réinitialiser</button>
      </div>
    </div>

    <aside class="tool-side">
      <div class="side-card">
        <div class="side-kicker">Méthode</div>
        <p>Chasse moyenne : <strong>0,45 cadratin</strong>, espaces compris, pour un romain de labeur. Lignes = hauteur du bloc ÷ interligne, vers le bas.</p>
        <p>Le hors-texte part à <strong>8 pages</strong>. Mettez 0 pour ne voir que le manuscrit. Ce n’est pas une maquette.</p>
        <a href="<?= e(url('/journal/combien-de-pages-fera-ce-manuscrit')) ?>">Lire la méthode →</a>
      </div>

      <div class="side-card">
        <div class="side-kicker">Volume</div>
        <p>Le feuillet français fait 1&nbsp;500 signes espaces compris. Collez un texte dans le compteur : le décompte reste dans le navigateur.</p>
        <a href="<?= e(url('/outils/volume')) ?>">Compter des signes →</a>
      </div>

      <div class="side-card">
        <div class="side-kicker">Maquette</div>
        <p>La police change la chasse. Garamond et Minion ne tombent pas sur le même nombre de pages. Le maquettiste cale sur le fichier.</p>
        <a href="<?= e(url('/besoin/maquette-de-livre')) ?>">Trouver un maquettiste →</a>
      </div>

      <div class="side-card">
        <div class="side-kicker">Cahier</div>
        <p>L’offset travaille souvent par 16 pages, parfois par 8 ou par 4. Le lien vers le dos prend le cahier de 16. Le numérique peut rester sur le total.</p>
        <a href="<?= e(url('/outils/dos')) ?>">Calculer un dos →</a>
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
  <p class="is-hidden" data-pages-summary hidden><?= e($summary) ?></p>
</div>
