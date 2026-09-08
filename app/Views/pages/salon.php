<?php
$s = $salon ?? [];
$website = trim((string) ($s['website'] ?? ''));
$fields = [
    'Lieu' => trim((string) ($s['venue'] ?? '')),
    'Organisateur' => trim((string) ($s['organizer'] ?? '')),
    'Entrée' => trim((string) ($s['ticket'] ?? '')),
    'Public' => trim((string) ($s['audience'] ?? '')),
    'Exposants' => trim((string) ($s['exhibitors'] ?? '')),
    'Fréquentation' => trim((string) ($s['attendance'] ?? '')),
];
?>
<div class="mk-page co-salon-page">
  <section class="forum-hero forum-hero-compact">
    <nav class="search-crumb" aria-label="Fil d'Ariane">
      <a href="<?= e(url('/')) ?>">Accueil</a>
      <span aria-hidden="true"> · </span>
      <a href="<?= e(url('/communaute')) ?>">Communauté</a>
      <span aria-hidden="true"> · </span>
      <a href="<?= e(url('/salons')) ?>">Agenda</a>
      <span aria-hidden="true"> · </span>
      <span><?= e((string) ($s['name'] ?? '')) ?></span>
    </nav>
    <?php if (!empty($s['kind'])): ?><div class="forum-kicker"><?= e((string) $s['kind']) ?></div><?php endif; ?>
    <h1><?= e((string) ($s['name'] ?? '')) ?></h1>
    <p class="forum-lead"><?= e(trim((string) ($s['when'] ?? '') . (!empty($s['place']) ? ' · ' . $s['place'] : ''))) ?></p>
    <?php if ($website !== ''): ?>
      <p style="margin-top: 18px;"><a class="btn-navy" href="<?= e($website) ?>" target="_blank" rel="noopener noreferrer">Site officiel</a></p>
    <?php endif; ?>
  </section>

  <section class="mk-block co-salon-body">
    <?php if (trim((string) ($s['description'] ?? '')) !== ''): ?>
      <p><?= e((string) $s['description']) ?></p>
    <?php endif; ?>
    <dl class="co-salon-meta">
      <?php foreach ($fields as $label => $value): ?>
        <?php if ($value === '') {
            continue;
        } ?>
        <div>
          <dt><?= e($label) ?></dt>
          <dd><?= e($value) ?></dd>
        </div>
      <?php endforeach; ?>
    </dl>
    <?php if (trim((string) ($s['notes'] ?? '')) !== '' && (string) $s['notes'] !== '-'): ?>
      <p class="co-salon-notes"><?= e((string) $s['notes']) ?></p>
    <?php endif; ?>
    <p><a href="<?= e(url('/salons')) ?>">← Tous les salons</a></p>
  </section>
</div>
