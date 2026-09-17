<?php
$result = is_array($result ?? null) ? $result : [];
$faqs = is_array($faqs ?? null) ? $faqs : [];
$jsConfig = is_array($jsConfig ?? null) ? $jsConfig : [];
$raw = (string) ($raw ?? \Adl\Data\Isbn::DEFAULT);
$parts = is_array($result['parts'] ?? null) ? $result['parts'] : [];
$warnings = is_array($result['warnings'] ?? null) ? $result['warnings'] : [];
$notices = is_array($result['notices'] ?? null) ? $result['notices'] : [];
$status = (string) ($result['status'] ?? 'empty');
$compact = (string) ($result['compact'] ?? '');
$target = (int) ($result['target'] ?? 13);
$barcode = (string) ($result['barcode_svg'] ?? '');
$summary = (string) ($result['summary'] ?? '');
$isbn13 = (string) ($result['isbn13_hyphen'] ?: ($result['isbn13'] ?? ''));
$isbn10 = (string) ($result['isbn10_hyphen'] ?: ($result['isbn10'] ?? ''));
$ean = (string) ($result['ean'] ?? '');
$kindLabel = (string) ($result['kind_label'] ?? '');
$statusLabel = (string) ($result['status_label'] ?? '');
$hyphenated = (string) ($result['hyphenated'] ?? '');
$expected = $result['expected_check'] ?? null;
$checkOk = $result['check_ok'] ?? null;
?>
<div class="mk-page tool-page" data-isbn-tool data-isbn-config="<?= e(json_encode($jsConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
  <section class="forum-hero forum-hero-compact">
    <nav class="search-crumb" aria-label="Fil d'Ariane">
      <a href="<?= e(url('/')) ?>">Accueil</a>
      <span aria-hidden="true"> · </span>
      <a href="<?= e(url('/outils')) ?>">Outils</a>
      <span aria-hidden="true"> · </span>
      <span>ISBN et EAN</span>
    </nav>
    <h1>Validateur ISBN et EAN</h1>
    <p class="forum-lead">Collez un ISBN-10 ou ISBN-13. Vous voyez la clé, la structure, la conversion et le code-barres de la 4e. L’AFNIL attribue les numéros ; nous ne faisons que contrôler.</p>
  </section>

  <div class="tool-layout">
    <div class="tool-board espace-panel">
      <form class="tool-isbn-fields" method="get" action="<?= e(url('/outils/isbn')) ?>" data-isbn-form>
        <div>
          <label class="field" for="isbn-input">ISBN ou EAN</label>
          <input class="input tool-isbn-input" id="isbn-input" name="n" value="<?= e($raw) ?>" maxlength="40" spellcheck="false" autocomplete="off" placeholder="978-2-…" data-isbn-input>
          <p class="tool-field-note">Tirets, espaces et le préfixe « ISBN » sont ignorés. Un X n’est valable qu’en dernière position d’un ISBN-10.</p>
        </div>
        <noscript>
          <button class="btn-navy" type="submit">Vérifier l’ISBN</button>
        </noscript>
      </form>

      <div class="tool-isbn-status is-<?= e($status) ?>" data-isbn-status>
        <strong data-isbn-status-label><?= e($statusLabel) ?></strong>
        <span data-isbn-kind><?= e($kindLabel) ?></span>
      </div>

      <div class="tool-isbn-digits" data-isbn-digits aria-hidden="true">
        <?php for ($i = 0; $i < $target; $i++): ?>
          <?php $ch = $i < strlen($compact) ? $compact[$i] : ''; ?>
          <span class="tool-isbn-cell<?= $ch === '' ? ' is-empty' : '' ?><?= ($i === $target - 1) ? ' is-check' : '' ?>"><?= e($ch) ?></span>
        <?php endfor; ?>
      </div>

      <div class="tool-isbn-parts<?= $parts === [] ? ' is-hidden' : '' ?>" data-isbn-parts<?= $parts === [] ? ' hidden' : '' ?>>
        <?php foreach ($parts as $part): ?>
          <?php
            $partId = (string) ($part['id'] ?? '');
            $partVal = (string) ($part['value'] ?? '');
            $grow = max(1, strlen($partVal));
          ?>
          <div class="tool-isbn-part is-<?= e($partId) ?>" style="flex: <?= e((string) $grow) ?> 1 0">
            <strong><?= e($partVal !== '' ? $partVal : '—') ?></strong>
            <span><?= e((string) ($part['label'] ?? '')) ?></span>
            <em><?= e((string) ($part['hint'] ?? '')) ?></em>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="tool-isbn-barcode<?= $barcode === '' ? ' is-hidden' : '' ?>" data-isbn-barcode<?= $barcode === '' ? ' hidden' : '' ?>>
        <?= $barcode ?>
      </div>
      <p class="tool-spread-caption" data-isbn-caption><?php
        if ($hyphenated !== '') {
            echo e($hyphenated);
            if ($expected !== null && $checkOk === null) {
                echo ' · clé prévue ' . e((string) $expected);
            }
        } else {
            echo 'Le schéma se remplit au fur et à mesure de la saisie.';
        }
      ?></p>

      <div class="tool-results" data-isbn-results aria-live="polite">
        <div class="tool-stat">
          <strong data-isbn-out="isbn13"><?= e($isbn13 !== '' ? $isbn13 : '—') ?></strong>
          <span>ISBN-13</span>
        </div>
        <div class="tool-stat">
          <strong data-isbn-out="isbn10"><?= e($isbn10 !== '' ? $isbn10 : '—') ?></strong>
          <span>ISBN-10</span>
        </div>
        <div class="tool-stat">
          <strong data-isbn-out="ean"><?= e($ean !== '' ? $ean : '—') ?></strong>
          <span>EAN / code-barres</span>
        </div>
        <div class="tool-stat">
          <strong data-isbn-out="check"><?php
            if ($checkOk === true) {
                echo 'Valide';
            } elseif ($expected !== null) {
                echo e((string) $expected);
            } else {
                echo '—';
            }
          ?></strong>
          <span data-isbn-check-caption><?= $checkOk === true ? 'Clé de contrôle' : 'Clé attendue' ?></span>
        </div>
      </div>

      <div class="tool-warn" data-isbn-warnings<?= $warnings === [] ? ' hidden' : '' ?>>
        <?php foreach ($warnings as $warning): ?>
          <p><?= e((string) $warning) ?></p>
        <?php endforeach; ?>
      </div>
      <div class="tool-isbn-notes" data-isbn-notes<?= $notices === [] ? ' hidden' : '' ?>>
        <?php foreach ($notices as $notice): ?>
          <p><?= e((string) $notice) ?></p>
        <?php endforeach; ?>
      </div>

      <div class="tool-actions">
        <button type="button" class="btn-ghost" data-isbn-copy<?= $summary === '' ? ' disabled' : '' ?>>Copier le résultat</button>
        <button type="button" class="btn-ghost" data-isbn-reset>Réinitialiser</button>
      </div>
    </div>

    <aside class="tool-side">
      <div class="side-card">
        <div class="side-kicker">Méthode</div>
        <p>L’ISBN-13 est un <strong>EAN-13</strong> du livre : 978 ou 979, puis groupe, éditeur, publication, clé. Le code-barres de la 4e reprend ce nombre, rien d’autre.</p>
        <p>Un ISBN-10 se convertit vers 978. Un 979 n’a pas d’équivalent à dix chiffres.</p>
      </div>

      <div class="side-card">
        <div class="side-kicker">Attribution</div>
        <p>Nous ne délivrons pas d’ISBN. En zone francophone, c’est l’<strong>AFNIL</strong> qui attribue les listes aux éditeurs. Un autoédité passe souvent par un intermédiaire.</p>
        <a href="<?= e(url('/journal/isbn-depot-legal-afnil-france')) ?>">ISBN, dépôt légal, AFNIL →</a>
      </div>

      <div class="side-card">
        <div class="side-kicker">Maquette</div>
        <p>Le code-barres se pose sur la 4e, avec le prix. C’est un livrable de maquette, pas une décoration. Un format = un ISBN.</p>
        <a href="<?= e(url('/besoin/maquette-de-livre')) ?>">Trouver un maquettiste →</a>
      </div>

      <div class="side-card">
        <div class="side-kicker">Couverture</div>
        <p>La 4e porte aussi le résumé et le prix public. Le dos, lui, se calcule sur le papier réel.</p>
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
  <p class="is-hidden" data-isbn-summary hidden><?= e($summary) ?></p>
</div>
