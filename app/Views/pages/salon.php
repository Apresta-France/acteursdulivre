<?php
$s = $salon ?? [];
$upcoming = $upcoming ?? [];
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

  <section class="mk-block co-salon-layout">
    <div class="co-salon-body">
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
    </div>

    <aside class="me-side co-salon-side">
      <?php if ($upcoming !== []): ?>
        <div class="side-card">
          <div class="side-kicker">À venir</div>
          <div class="co-salon-side-list">
            <?php foreach ($upcoming as $event): ?>
              <a class="co-event co-event-compact" href="<?= e(url((string) ($event['href'] ?? '/salons'))) ?>">
                <time class="co-event-date" datetime="<?= e((string) ($event['iso'] ?? '')) ?>">
                  <strong><?= e((string) ($event['day'] ?? '')) ?></strong>
                  <span><?= e((string) ($event['month'] ?? '')) ?></span>
                </time>
                <div class="co-event-main">
                  <h3><?= e((string) ($event['name'] ?? '')) ?></h3>
                  <p><?= e((string) ($event['place'] ?? '')) ?></p>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
          <a class="co-salon-side-more" href="<?= e(url('/salons')) ?>">Tout l’agenda →</a>
        </div>
      <?php endif; ?>
      <div class="side-card side-card-warm">
        <div class="side-kicker">Votre salon</div>
        <p class="me-side-text">Vous organisez un salon, un festival ou une foire du livre ? Proposez-le : l’équipe le publie après vérification.</p>
        <a class="btn-navy" href="<?= e(url('/salons/ajouter')) ?>">Ajouter un salon</a>
      </div>
    </aside>
  </section>
</div>
