<?php
$rate = is_array($commissionRate ?? null) ? $commissionRate : [];
if ($rate === []) {
    return;
}
$compact = !empty($commissionRateCompact);
?>
<section class="commission-rate<?= $compact ? ' is-compact' : '' ?>">
  <div class="commission-rate-main">
    <div class="commission-rate-pct"><?= e((string) ($rate['label'] ?? '8 %')) ?></div>
    <div>
      <strong>Votre commission</strong>
      <p><?= e((string) ($rate['detail'] ?? '')) ?></p>
      <?php if (!empty($rate['progress'])): ?>
        <em><?= e((string) $rate['progress']) ?></em>
      <?php endif; ?>
      <?php if (empty($commissionRateCompact)): ?>
        <a href="<?= e(url('/tarifs')) ?>">Voir le détail des tarifs</a>
      <?php endif; ?>
    </div>
  </div>
  <?php
    $example = is_array($rate['example'] ?? null) ? $rate['example'] : [];
    if ($example !== [] && empty($commissionRateCompact)):
  ?>
    <p class="commission-rate-example">
      <span class="commission-rate-example-kicker"><?= !empty($example['first_free']) ? 'Exemple dès la 2ᵉ' : 'Exemple' ?></span>
      <span class="commission-rate-example-hl"><strong><?= e((string) $example['ttc']) ?></strong> TTC facturés</span>
      <span class="commission-rate-example-sep" aria-hidden="true"></span>
      <span><strong><?= e((string) $example['ht']) ?></strong> HT</span>
      <span class="commission-rate-example-sep" aria-hidden="true"></span>
      <span class="commission-rate-example-hl">commission <strong><?= e((string) $example['fee']) ?></strong> HT <em>(<?= (int) ($example['percent'] ?? 0) ?>&nbsp;%)</em></span>
      <span class="commission-rate-example-sep" aria-hidden="true"></span>
      <span><strong><?= e((string) ($example['fee_ttc'] ?? $example['fee'])) ?></strong> TTC <em>TVA 20&nbsp;%</em></span>
    </p>
  <?php endif; ?>
</section>
