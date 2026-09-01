<?php
/** @var array<string, mixed> $cityField */
$cityField = is_array($cityField ?? null) ? $cityField : [];
$mode = (string) ($cityField['mode'] ?? 'profile');
$visibleName = (string) ($cityField['name'] ?? ($mode === 'search' ? 'ville_label' : 'city'));
$slugName = (string) ($cityField['slug_name'] ?? ($mode === 'search' ? 'ville' : 'city_slug'));
$inseeName = (string) ($cityField['insee_name'] ?? 'city_insee');
$id = (string) ($cityField['id'] ?? ($mode === 'search' ? 'search-city' : 'city'));
$value = (string) ($cityField['value'] ?? '');
$slug = (string) ($cityField['slug'] ?? '');
$insee = (string) ($cityField['insee'] ?? '');
$placeholder = (string) ($cityField['placeholder'] ?? 'Paris, Lyon, Nantes…');
$inputClass = (string) ($cityField['input_class'] ?? 'input');
$extra = (string) ($cityField['extra'] ?? '');
$compact = !empty($cityField['compact']);
$api = url('/api/villes');
?>
<div class="city-ac<?= $compact ? ' is-compact' : '' ?>" data-city-ac data-city-api="<?= e($api) ?>">
  <input
    class="<?= e($inputClass) ?>"
    type="text"
    id="<?= e($id) ?>"
    name="<?= e($visibleName) ?>"
    value="<?= e($value) ?>"
    placeholder="<?= e($placeholder) ?>"
    autocomplete="off"
    spellcheck="false"
    data-city-input
    aria-autocomplete="list"
    aria-expanded="false"
    <?= $extra !== '' ? $extra : '' ?>
  >
  <input type="hidden" name="<?= e($slugName) ?>" value="<?= e($slug) ?>" data-city-slug>
  <?php if ($mode !== 'search'): ?>
    <input type="hidden" name="<?= e($inseeName) ?>" value="<?= e($insee) ?>" data-city-insee>
  <?php endif; ?>
  <div class="search-suggest city-ac-panel" data-city-panel hidden></div>
</div>
