<?php
$s = $salon ?? [];
$upcoming = $upcoming ?? [];
$website = trim((string) ($s['website'] ?? ''));
$description = trim((string) ($s['description'] ?? ''));
$notes = trim((string) ($s['notes'] ?? ''));
$confirmed = !empty($s['confirmed']);
$cover = asset('img/covers/salons-evenements.webp') . '?v=1';

$glance = [];
$when = trim((string) ($s['when_dates'] ?? $s['when'] ?? ''));
if ($when !== '') {
    $glance[] = ['Dates', $when];
}
if (trim((string) ($s['city'] ?? '')) !== '') {
    $glance[] = ['Ville', (string) $s['city']];
}
if (trim((string) ($s['region'] ?? '')) !== '') {
    $glance[] = ['Région', (string) $s['region']];
}
if (trim((string) ($s['country'] ?? '')) !== '' && (string) $s['country'] !== 'France') {
    $glance[] = ['Pays', (string) $s['country']];
}
if (trim((string) ($s['venue'] ?? '')) !== '') {
    $glance[] = ['Lieu', (string) $s['venue']];
}
if (trim((string) ($s['organizer'] ?? '')) !== '') {
    $glance[] = ['Organisateur', (string) $s['organizer']];
}
if (trim((string) ($s['ticket'] ?? '')) !== '') {
    $glance[] = ['Entrée', (string) $s['ticket']];
}
if (trim((string) ($s['audience'] ?? '')) !== '') {
    $glance[] = ['Public', (string) $s['audience']];
}
if (trim((string) ($s['exhibitors'] ?? '')) !== '') {
    $glance[] = ['Exposants', (string) $s['exhibitors']];
}
if (trim((string) ($s['attendance'] ?? '')) !== '') {
    $glance[] = ['Fréquentation', (string) $s['attendance']];
}

$stats = [];
if (trim((string) ($s['attendance'] ?? '')) !== '') {
    $stats[] = ['k' => 'Fréquentation', 'v' => (string) $s['attendance']];
}
if (trim((string) ($s['exhibitors'] ?? '')) !== '') {
    $stats[] = ['k' => 'Exposants', 'v' => (string) $s['exhibitors']];
}

$placeCards = [];
$lieuLines = [];
if (trim((string) ($s['venue'] ?? '')) !== '') {
    $lieuLines[] = ['Adresse', (string) $s['venue']];
}
if (trim((string) ($s['city'] ?? '')) !== '') {
    $cityLine = (string) $s['city'];
    if (trim((string) ($s['department'] ?? '')) !== '') {
        $cityLine .= ' · ' . $s['department'];
    }
    $lieuLines[] = ['Ville', $cityLine];
}
if (trim((string) ($s['region'] ?? '')) !== '') {
    $lieuLines[] = ['Région', (string) $s['region']];
}
if (trim((string) ($s['country'] ?? '')) !== '') {
    $lieuLines[] = ['Pays', (string) $s['country']];
}
if ($lieuLines !== []) {
    $placeCards[] = ['titre' => 'Lieu', 'lignes' => $lieuLines];
}
$infoLines = [];
if (trim((string) ($s['organizer'] ?? '')) !== '') {
    $infoLines[] = ['Organisateur', (string) $s['organizer']];
}
if (trim((string) ($s['ticket'] ?? '')) !== '') {
    $infoLines[] = ['Entrée', (string) $s['ticket']];
}
if (trim((string) ($s['audience'] ?? '')) !== '') {
    $infoLines[] = ['Public', (string) $s['audience']];
}
if (trim((string) ($s['exhibitors'] ?? '')) !== '') {
    $infoLines[] = ['Exposants', (string) $s['exhibitors']];
}
if (trim((string) ($s['attendance'] ?? '')) !== '') {
    $infoLines[] = ['Fréquentation', (string) $s['attendance']];
}
if ($infoLines !== []) {
    $placeCards[] = ['titre' => 'Sur place', 'lignes' => $infoLines];
}
?>
<div class="co-salon-page">
  <section class="profile-hero salon-hero salon-hero-cover" style="--salon-cover: url('<?= e($cover) ?>')">
    <div class="profile-hero-main">
      <nav class="search-crumb salon-crumb" aria-label="Fil d'Ariane">
        <a href="<?= e(url('/')) ?>">Accueil</a>
        <span aria-hidden="true"> · </span>
        <a href="<?= e(url('/communaute')) ?>">Communauté</a>
        <span aria-hidden="true"> · </span>
        <a href="<?= e(url('/salons')) ?>">Agenda des salons</a>
        <span aria-hidden="true"> · </span>
        <span><?= e((string) ($s['name'] ?? '')) ?></span>
      </nav>
      <div class="profile-hero-line">
        <?php if (!empty($s['kind'])): ?><span class="profile-badge"><?= e((string) $s['kind']) ?></span><?php endif; ?>
        <?php if ($confirmed): ?>
          <span class="salon-pill salon-pill-ok">Dates confirmées</span>
        <?php else: ?>
          <span class="salon-pill">Dates à confirmer</span>
        <?php endif; ?>
      </div>
      <h1><?= e((string) ($s['name'] ?? '')) ?></h1>
      <p class="profile-hero-sub"><?= e(trim($when . (!empty($s['place']) ? ' · ' . $s['place'] : '') . (!empty($s['venue']) ? ' · ' . $s['venue'] : ''))) ?></p>
      <?php if ($description !== ''): ?>
        <p class="auteur-hero-bio"><?= e($description) ?></p>
      <?php endif; ?>
    </div>
    <div class="profile-hero-actions">
      <?php if ($website !== ''): ?>
        <a class="btn-orange" href="<?= e($website) ?>" target="_blank" rel="noopener noreferrer">Site de l’organisateur ↗</a>
      <?php endif; ?>
      <a class="btn-ghost-light" href="<?= e(url('/salons')) ?>">Tout l’agenda</a>
    </div>
  </section>

  <section class="salon-fiche">
    <div class="salon-fiche-main">
      <?php if ($stats !== []): ?>
        <div class="salon-stats">
          <?php foreach ($stats as $stat): ?>
            <div class="salon-stat">
              <strong><?= e($stat['v']) ?></strong>
              <span><?= e($stat['k']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($placeCards !== []): ?>
        <div class="salon-cards">
          <?php foreach ($placeCards as $card): ?>
            <div class="salon-card">
              <div class="salon-card-kicker"><?= e($card['titre']) ?></div>
              <dl>
                <?php foreach ($card['lignes'] as $line): ?>
                  <div>
                    <dt><?= e($line[0]) ?></dt>
                    <dd><?= e($line[1]) ?></dd>
                  </div>
                <?php endforeach; ?>
              </dl>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($notes !== '' && $notes !== '-'): ?>
        <p class="salon-notes"><?= e($notes) ?></p>
      <?php endif; ?>
    </div>

    <aside class="salon-fiche-side">
      <?php if ($glance !== []): ?>
        <div class="side-card">
          <div class="side-kicker">En un coup d’œil</div>
          <dl class="salon-glance">
            <?php foreach ($glance as $row): ?>
              <div>
                <dt><?= e($row[0]) ?></dt>
                <dd><?= e($row[1]) ?></dd>
              </div>
            <?php endforeach; ?>
          </dl>
        </div>
      <?php endif; ?>

      <?php if ($upcoming !== []): ?>
        <div class="side-card">
          <div class="side-kicker">À venir</div>
          <div class="salon-near-list">
            <?php foreach ($upcoming as $event): ?>
              <a class="salon-near" href="<?= e(url((string) ($event['href'] ?? '/salons'))) ?>">
                <strong><?= e((string) ($event['name'] ?? '')) ?></strong>
                <span><?= e(trim((string) ($event['when_dates'] ?? $event['when'] ?? '') . (!empty($event['place']) ? ' · ' . $event['place'] : ''))) ?></span>
              </a>
            <?php endforeach; ?>
          </div>
          <a class="salon-side-more" href="<?= e(url('/salons')) ?>">Tout l’agenda →</a>
        </div>
      <?php endif; ?>

      <div class="side-card forum-panel-dark">
        <div class="forum-panel-heading">Votre salon</div>
        <p>Vous organisez un salon, un festival ou une foire du livre ? Proposez-le : l’équipe le publie après vérification.</p>
        <a class="btn-orange" href="<?= e(url('/salons/ajouter')) ?>">Ajouter un salon</a>
      </div>
    </aside>
  </section>
</div>
