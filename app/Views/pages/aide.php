<?php
$q = search_norm((string) ($helpQuery ?? ''));
$cats = $aideCats ?? [];
$faq = $aideFaq ?? [];
if ($q !== '') {
    $faq = array_values(array_filter($faq, static function (array $item) use ($q): bool {
        return str_contains(search_norm((string) ($item['q'] ?? '')), $q)
            || str_contains(search_norm((string) ($item['a'] ?? '')), $q);
    }));
}
?>
<div class="legal-page" style="padding: 44px;">
  <div style="max-width: 700px; margin-bottom: 34px;">
    <h1>Centre d'aide</h1>
    <form method="get" action="<?= e(url('/aide')) ?>" class="footer-news-form" style="max-width: 100%;">
      <input type="search" name="q" value="<?= e((string) ($helpQuery ?? '')) ?>" placeholder="annulation, facture, TVA, commission…">
      <button type="submit">Rechercher</button>
    </form>
  </div>

  <?php if (!empty($reportSaved)): ?>
    <div class="flash flash-ok"><?= e(is_string($reportSaved) ? $reportSaved : 'Message envoyé.') ?></div>
  <?php endif; ?>
  <?php if (!empty($reportError)): ?>
    <div class="flash flash-error"><?= e((string) $reportError) ?></div>
  <?php endif; ?>

  <div class="help-cats">
    <?php foreach ($cats as $c): ?>
      <div class="side-card">
        <strong><?= e((string) ($c['title'] ?? '')) ?></strong>
        <p class="mission-row-sub"><?= e((string) ($c['desc'] ?? '')) ?></p>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="publish-grid" style="margin-top: 34px;">
    <div>
      <h2>Questions fréquentes</h2>
      <?php if ($faq === []): ?>
        <p class="mission-row-sub">Aucun article pour « <?= e((string) ($helpQuery ?? '')) ?> ».</p>
      <?php endif; ?>
      <div class="side-card" style="padding: 0; overflow: hidden;">
        <?php foreach ($faq as $f): ?>
          <details class="help-faq"<?= !empty($f['open']) || $q !== '' ? ' open' : '' ?>>
            <summary><?= e((string) ($f['q'] ?? '')) ?></summary>
            <p><?= e((string) ($f['a'] ?? '')) ?></p>
          </details>
        <?php endforeach; ?>
      </div>
    </div>
    <aside class="publish-side">
      <div class="side-card">
        <div class="side-kicker">Nous écrire</div>
        <p>Une équipe en France, du lundi au vendredi, 9 h – 18 h.</p>
        <a class="btn-orange" href="<?= e(url('/contact')) ?>">Ouvrir un ticket</a>
      </div>
      <div class="side-card">
        <div class="side-kicker">Signaler un abus</div>
        <form method="post" action="<?= e(url('/signaler')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="type" value="user">
          <input type="hidden" name="back" value="/aide">
          <label class="field" for="help-reason">Motif</label>
          <select class="input" id="help-reason" name="reason" required>
            <?php foreach (\Adl\Models\Report::REASONS as $value => $label): ?>
              <option value="<?= e($value) ?>"><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
          <label class="field" for="help-body" style="margin-top: 10px;">Précisions</label>
          <textarea class="textarea" id="help-body" name="body" rows="4" required placeholder="Lien concerné, faits observés."></textarea>
          <button class="btn-ghost" type="submit" style="margin-top: 12px;">Envoyer le signalement</button>
        </form>
      </div>
    </aside>
  </div>
</div>
