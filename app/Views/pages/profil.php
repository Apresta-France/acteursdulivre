<?php
$live = $liveProfile ?? null;
$catalog = $catalogProfile ?? null;
$useMock = $live === null && ($catalog === null || ($slug ?? '') === 'marion-vasseur');

if ($useMock) {
    echo \Adl\Core\DcEngine::render(
        (string) file_get_contents(ADL_ROOT . '/app/Views/pages/profil.html'),
        get_defined_vars()
    );
    return;
}

$p = $live ?? [
    'name' => $catalog['title'] ?? 'Prestataire',
    'initials' => $catalog['initials'] ?? 'AD',
    'title' => $catalog['subtitle'] ?? '',
    'city' => $catalog['city'] ?? '',
    'level' => 'Annuaire',
    'presentation' => $catalog['excerpt'] ?? '',
    'availability' => '',
    'hourly_rate' => '',
    'rate_note' => '',
    'languages' => '',
    'languages_list' => [],
    'trades' => array_filter([$catalog['cat'] ?? '']),
    'skills' => [],
    'tools' => [],
    'genres' => $catalog['genres'] ?? [],
    'experiences' => [],
    'education' => [],
    'portfolio' => [],
    'tags' => $catalog['genres'] ?? [],
    'website' => '',
];
?>
<div class="profile-page">
  <div class="profile-hero">
    <span class="avatar profile-avatar" style="<?= e(avatar_style((string) $p['initials'], 104)) ?>"><?= e((string) $p['initials']) ?></span>
    <div class="profile-hero-main">
      <div class="profile-hero-line">
        <h1><?= e((string) $p['name']) ?></h1>
        <span class="profile-badge"><?= e((string) ($p['level'] ?: 'Prestataire')) ?></span>
      </div>
      <div class="profile-hero-sub">
        <?= e(trim(($p['title'] ?? '') . ($p['city'] ? ' · ' . $p['city'] : '') . ($p['languages'] ? ' · ' . $p['languages'] : ''))) ?>
      </div>
      <?php if (!empty($p['trades']) || !empty($p['tags'])): ?>
        <div class="chip-row">
          <?php foreach (array_slice($p['tags'] ?: $p['trades'], 0, 8) as $tag): ?>
            <span class="chip-static"><?= e((string) $tag) ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <div class="profile-hero-actions">
      <a class="btn-orange" href="<?= e(url('/espace/messages')) ?>">Demander un devis</a>
      <a class="btn-ghost-light" href="<?= e(url('/espace/messages')) ?>">Envoyer un message</a>
    </div>
  </div>

  <div class="profile-body">
    <div>
      <?php if ($p['presentation'] !== ''): ?>
        <h2>À propos</h2>
        <p class="profile-text"><?= nl2br(e((string) $p['presentation'])) ?></p>
      <?php endif; ?>

      <?php if (!empty($p['skills'])): ?>
        <h2>Compétences</h2>
        <div class="skill-list">
          <?php foreach ($p['skills'] as $skill): ?>
            <div class="skill-row">
              <span><?= e((string) ($skill['label'] ?? '')) ?></span>
              <strong><?= e((string) ($skill['niveau'] ?? '')) ?></strong>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($p['portfolio'])): ?>
        <h2>Créations et exemples</h2>
        <div class="portfolio-grid">
          <?php foreach ($p['portfolio'] as $item): ?>
            <figure class="portfolio-item">
              <?php if (!empty($item['img'])): ?>
                <div class="portfolio-item-media" style="background-image:url('<?= e((string) $item['img']) ?>')"></div>
              <?php endif; ?>
              <figcaption>
                <strong><?= e((string) ($item['title'] ?? '')) ?></strong>
                <span><?= e((string) ($item['caption'] ?? $item['kind_label'] ?? '')) ?></span>
                <?php if (!empty($item['description'])): ?>
                  <p><?= e((string) $item['description']) ?></p>
                <?php endif; ?>
              </figcaption>
            </figure>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($p['experiences'])): ?>
        <h2>Parcours</h2>
        <div class="timeline">
          <?php foreach ($p['experiences'] as $exp): ?>
            <div class="timeline-row">
              <span><?= e((string) ($exp['periode'] ?? '')) ?></span>
              <div>
                <strong><?= e((string) ($exp['poste'] ?? '')) ?></strong>
                <em><?= e((string) ($exp['lieu'] ?? '')) ?></em>
                <?php if (!empty($exp['detail'])): ?><p><?= e((string) $exp['detail']) ?></p><?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($p['education'])): ?>
        <h2>Formation</h2>
        <div class="timeline">
          <?php foreach ($p['education'] as $edu): ?>
            <div class="timeline-row">
              <span><?= e((string) ($edu['annee'] ?? '')) ?></span>
              <div>
                <strong><?= e((string) ($edu['intitule'] ?? '')) ?></strong>
                <em><?= e((string) ($edu['ecole'] ?? '')) ?></em>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <aside class="profile-side">
      <div class="side-card">
        <?php if ($p['hourly_rate'] !== ''): ?>
          <div class="side-kicker">À partir de</div>
          <div class="profile-rate"><?= e((string) $p['hourly_rate']) ?></div>
          <?php if ($p['rate_note'] !== ''): ?><div class="side-sub"><?= e((string) $p['rate_note']) ?></div><?php endif; ?>
        <?php endif; ?>
        <div class="info-list">
          <?php if ($p['city'] !== ''): ?><div><span>Localisation</span><strong><?= e((string) $p['city']) ?></strong></div><?php endif; ?>
          <?php if ($p['availability'] !== ''): ?><div><span>Disponibilité</span><strong><?= e((string) $p['availability']) ?></strong></div><?php endif; ?>
          <?php if ($p['languages'] !== ''): ?><div><span>Langues</span><strong><?= e((string) $p['languages']) ?></strong></div><?php endif; ?>
          <?php if (!empty($p['trades'])): ?><div><span>Métiers</span><strong><?= e(implode(', ', $p['trades'])) ?></strong></div><?php endif; ?>
          <?php if ($p['website'] !== ''): ?><div><span>Site</span><a href="<?= e((string) $p['website']) ?>"><?= e((string) $p['website']) ?></a></div><?php endif; ?>
        </div>
      </div>
      <?php if (!empty($p['tools'])): ?>
        <div class="side-card">
          <div class="side-title-sm">Outils</div>
          <div class="chip-row">
            <?php foreach ($p['tools'] as $tool): ?>
              <span class="chip-static dark"><?= e((string) $tool) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </aside>
  </div>
</div>
