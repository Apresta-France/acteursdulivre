<?php
$live = $liveMission ?? null;
if (!$live) {
    echo \Adl\Core\DcEngine::render(
        (string) file_get_contents(ADL_ROOT . '/app/Views/pages/mission.html'),
        get_defined_vars()
    );
    return;
}
?>
<div class="mission-page">
  <div class="search-crumb">Appels d'offres · <?= e((string) ($live['category_name'] ?? 'Mission')) ?></div>
  <div class="publish-grid">
    <div>
      <div class="mission-row-title" style="margin-bottom: 10px;">
        <span class="status-pill status-<?= e((string) $live['status']) ?>"><?= e((string) $live['status_label']) ?></span>
        <span class="mission-row-sub">publiée <?= e((string) $live['when']) ?></span>
      </div>
      <h1 class="mission-title"><?= e((string) $live['title']) ?></h1>
      <div class="facts">
        <div><span>Métier</span><strong><?= e((string) ($live['category_name'] ?: '—')) ?></strong></div>
        <div><span>Budget</span><strong><?= e((string) $live['budget']) ?></strong></div>
        <div><span>Volume</span><strong><?= e((string) ($live['volume'] ?: 'à préciser')) ?></strong></div>
        <div><span>Échéance</span><strong><?= e((string) $live['deadline_label']) ?></strong></div>
      </div>
      <h2>Le besoin</h2>
      <p class="profile-text"><?= nl2br(e((string) ($live['brief'] ?? ''))) ?></p>
      <?php if (!empty($live['attachment_href'])): ?>
        <h2>Pièce jointe</h2>
        <a class="file-chip" href="<?= e((string) $live['attachment_href']) ?>">
          <?= e((string) ($live['attachment_name'] ?? 'Document')) ?>
        </a>
      <?php endif; ?>
    </div>
    <aside class="publish-side">
      <div class="side-card">
        <div class="side-kicker">Le porteur de projet</div>
        <div class="suggest-row" style="padding: 0;">
          <span class="avatar" style="<?= e(avatar_style((string) $live['initials'], 46)) ?>"><?= e((string) $live['initials']) ?></span>
          <span>
            <strong><?= e((string) $live['by']) ?></strong>
            <em>Mission publiée sur la plateforme</em>
          </span>
        </div>
      </div>
      <?php if (!empty($suggestions)): ?>
        <div class="side-card">
          <div class="side-title-sm">Prestataires qui correspondent</div>
          <?php foreach ($suggestions as $p): ?>
            <a class="suggest-row" href="<?= e(url((string) $p['href'])) ?>">
              <span class="avatar" style="<?= e(avatar_style((string) $p['initials'], 34)) ?>"><?= e((string) $p['initials']) ?></span>
              <span>
                <strong><?= e((string) $p['title']) ?></strong>
                <em><?= e((string) $p['subtitle']) ?></em>
              </span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </aside>
  </div>
</div>
