<?php
$a = $author ?? null;
if (!$a) {
    not_found('Cette fiche auteur n\'est pas publiée.');
}
$works = $works ?? [];
$viewer = \Adl\Core\Auth::user();
$isOwner = !empty($isOwner);
$isDraft = empty($a['enabled']);
$featured = array_values(array_filter($works, static fn (array $w): bool => !empty($w['featured'])));
$others = array_values(array_filter($works, static fn (array $w): bool => empty($w['featured'])));
$bio = trim((string) ($a['bio'] ?? ''));
$shortBio = trim((string) ($a['short_bio'] ?? ''));
$press = $a['press'] ?? [];
$links = $a['links'] ?? [];
$awards = $a['awards'] ?? [];
$events = $a['events'] ?? [];
$openTo = $a['open_to_labels'] ?? [];
$website = trim((string) ($a['website'] ?? ''));
$wikipedia = trim((string) ($a['wikipedia_url'] ?? ''));
$hasSide = $website !== '' || $wikipedia !== '' || $links !== [] || $openTo !== [] || !empty($a['profile_href']) || ($a['member_since_label'] ?? '') !== '';

$renderWork = static function (array $w, bool $big): void {
    $images = $w['images'] ?? [];
    $summary = trim((string) ($w['summary'] ?? ''));
    $excerpt = trim((string) ($w['excerpt'] ?? ''));
    $facts = [];
    if (($w['role'] ?? 'auteur') !== 'auteur') {
        $facts[] = ['Rôle', (string) $w['role_label']];
    }
    if (trim((string) ($w['publisher'] ?? '')) !== '') {
        $facts[] = ['Éditeur', (string) $w['publisher'] . (trim((string) ($w['collection'] ?? '')) !== '' ? ' · ' . $w['collection'] : '')];
    }
    if (trim((string) ($w['year'] ?? '')) !== '') {
        $facts[] = ['Parution', (string) $w['year']];
    }
    if (trim((string) ($w['isbn'] ?? '')) !== '') {
        $facts[] = ['ISBN', (string) $w['isbn']];
    }
    if ((int) ($w['pages'] ?? 0) > 0) {
        $facts[] = ['Pages', (string) (int) $w['pages']];
    }
    if (trim((string) ($w['language'] ?? '')) !== '') {
        $facts[] = ['Langue', (string) $w['language']];
    }
    if (($w['formats_labels'] ?? []) !== []) {
        $facts[] = ['Formats', implode(', ', $w['formats_labels'])];
    }
    if (trim((string) ($w['price'] ?? '')) !== '') {
        $facts[] = ['Prix', (string) $w['price']];
    }
    ?>
    <article class="auteur-work<?= $big ? ' is-featured' : '' ?>" id="oeuvre-<?= (int) $w['id'] ?>">
      <div class="auteur-work-gallery<?= $images === [] ? ' is-empty' : '' ?>">
        <?php if ($images === []): ?>
          <div class="auteur-work-placeholder" aria-hidden="true"><?= icon('book', 28) ?><span><?= e((string) $w['kind_label']) ?></span></div>
        <?php else: ?>
          <?php foreach ($images as $k => $img): ?>
            <a class="auteur-work-img<?= $k === 0 ? ' is-main' : '' ?>"
               href="<?= e((string) $img) ?>"
               style="background-image:url('<?= e((string) $img) ?>')"
               data-zoom
               data-zoom-title="<?= e((string) $w['title']) ?>"
               data-zoom-caption="<?= e((string) $w['meta_label']) ?>"
               data-zoom-desc=""
               aria-haspopup="dialog"
               aria-controls="portfolio-zoom"
               aria-label="<?= e('Agrandir : ' . $w['title'] . ' (visuel ' . ($k + 1) . ')') ?>">
              <span class="portfolio-item-zoom" aria-hidden="true"><?= icon('search', 14) ?></span>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <div class="auteur-work-body">
        <div class="auteur-work-kicker">
          <span><?= e((string) $w['kind_label']) ?></span>
          <?php if (($w['status'] ?? 'published') !== 'published'): ?>
            <span class="status-pill<?= $w['status'] === 'upcoming' ? ' is-available' : '' ?>"><?= e((string) $w['status_label']) ?></span>
          <?php endif; ?>
        </div>
        <h3><?= e((string) $w['title']) ?></h3>
        <?php if (trim((string) ($w['subtitle'] ?? '')) !== ''): ?>
          <p class="auteur-work-subtitle"><?= e((string) $w['subtitle']) ?></p>
        <?php endif; ?>
        <?php if ($w['meta_label'] !== ''): ?>
          <p class="auteur-work-meta"><?= e((string) $w['meta_label']) ?></p>
        <?php endif; ?>
        <?php if ($summary !== ''): ?>
          <div class="auteur-work-summary profile-text"><?= nl2br(e($summary)) ?></div>
        <?php endif; ?>
        <?php if ($excerpt !== ''): ?>
          <blockquote class="auteur-work-excerpt"><?= nl2br(e($excerpt)) ?></blockquote>
        <?php endif; ?>
        <?php if ($facts !== []): ?>
          <dl class="auteur-work-facts">
            <?php foreach ($facts as [$label, $value]): ?>
              <div><dt><?= e($label) ?></dt><dd><?= e($value) ?></dd></div>
            <?php endforeach; ?>
          </dl>
        <?php endif; ?>
        <?php if (!empty($w['buy_url']) || !empty($w['more_url'])): ?>
          <div class="auteur-work-actions">
            <?php if (!empty($w['buy_url'])): ?>
              <a class="btn-orange" href="<?= e((string) $w['buy_url']) ?>" target="_blank" rel="noopener noreferrer sponsored">Acheter ce livre</a>
            <?php endif; ?>
            <?php if (!empty($w['more_url'])): ?>
              <a class="btn-ghost" href="<?= e((string) $w['more_url']) ?>" target="_blank" rel="noopener noreferrer">En savoir plus</a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </article>
    <?php
};
?>
<div class="profile-page auteur-public">
  <nav class="search-crumb" aria-label="Fil d'Ariane">
    <a href="<?= e(url('/')) ?>">Accueil</a>
    <span aria-hidden="true"> · </span>
    <a href="<?= e(url('/auteurs')) ?>">Auteurs</a>
    <span aria-hidden="true"> · </span>
    <span><?= e((string) $a['name']) ?></span>
  </nav>

  <?php if ($isDraft): ?>
    <div class="flash flash-warn" style="margin: 16px 44px 0;">
      Cette fiche est désactivée : seuls vous<?= $isOwner ? '' : ' (et l\'administration)' ?> pouvez la voir. Activez-la depuis votre espace pour la rendre accessible.
    </div>
  <?php endif; ?>

  <div class="profile-hero auteur-hero">
    <?= avatar_html($a, 104, 'avatar profile-avatar') ?>
    <div class="profile-hero-main">
      <div class="profile-hero-line">
        <div class="profile-hero-name">
          <h1><?= e((string) $a['name']) ?></h1>
        </div>
        <span class="profile-badge">Auteur</span>
        <?php if (!empty($a['is_platform_cofounder'])): ?>
          <span class="profile-badge profile-badge-cofounder">Co-fondateur de la plateforme</span>
        <?php endif; ?>
        <?php if (!empty($a['is_founder'])): ?>
          <span class="profile-badge profile-badge-founder">Membre fondateur</span>
        <?php endif; ?>
      </div>
      <?php if (trim((string) ($a['tagline'] ?? '')) !== ''): ?>
        <div class="profile-hero-sub"><?= e((string) $a['tagline']) ?></div>
      <?php endif; ?>
      <?php if ($shortBio !== ''): ?>
        <p class="auteur-hero-bio"><?= e($shortBio) ?></p>
      <?php endif; ?>
      <?php
        $worksCount = count($works);
        $hasStats = $worksCount > 0 || $awards !== [] || $press !== [];
      ?>
      <?php if ($hasStats): ?>
        <div class="profile-hero-stats">
          <?php if ($worksCount > 0): ?>
            <div><strong><?= $worksCount ?></strong><span><?= $worksCount > 1 ? 'œuvres' : 'œuvre' ?></span></div>
          <?php endif; ?>
          <?php if ($awards !== []): ?>
            <div><strong><?= count($awards) ?></strong><span><?= count($awards) > 1 ? 'distinctions' : 'distinction' ?></span></div>
          <?php endif; ?>
          <?php if ($press !== []): ?>
            <div><strong><?= count($press) ?></strong><span><?= count($press) > 1 ? 'articles de presse' : 'article de presse' ?></span></div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      <?php if (!empty($a['genres'])): ?>
        <div class="chip-row">
          <?php foreach (array_slice($a['genres'], 0, 8) as $genre): ?>
            <span class="chip-static"><?= e((string) $genre) ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <div class="profile-hero-actions">
      <?php if ($isOwner): ?>
        <p class="profile-avail-note"><?= $isDraft ? 'Cette fiche n\'est pas encore visible dans l\'annuaire des auteurs.' : 'C\'est votre fiche auteur publique.' ?></p>
        <form method="post" action="<?= e(url('/espace/auteur/publication')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="enabled" value="<?= $isDraft ? '1' : '0' ?>">
          <button class="<?= $isDraft ? 'btn-orange' : 'btn-ghost-light' ?>" type="submit"><?= $isDraft ? 'Activer ma fiche' : 'Désactiver la fiche' ?></button>
        </form>
        <a class="<?= $isDraft ? 'btn-ghost-light' : 'btn-orange' ?>" href="<?= e(url('/espace/auteur')) ?>">Modifier ma fiche</a>
        <a class="btn-ghost-light" href="<?= e(url('/espace/auteur/oeuvres')) ?>">Gérer mes œuvres</a>
      <?php else: ?>
        <form method="post" action="<?= e(url('/espace/messages')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="avec" value="<?= (int) ($a['user_id'] ?? 0) ?>">
          <input type="hidden" name="sujet" value="Contact auteur">
          <button class="btn-orange" type="submit">Écrire à l'auteur</button>
        </form>
        <?php if (!empty($a['profile_href'])): ?>
          <a class="btn-ghost-light" href="<?= e(url((string) $a['profile_href'])) ?>">Voir sa vitrine pro</a>
        <?php endif; ?>
      <?php endif; ?>
      <?php
        $shareUrl = $meta['url'] ?? \Adl\Data\Share::current();
        $shareTitle = $meta['title'] ?? ((string) $a['name'] . ' — acteursdulivre.fr');
        $shareText = $meta['description'] ?? $shortBio;
        $shareLabel = 'Partager';
        $shareCompact = true;
        $shareNative = true;
        require ADL_ROOT . '/app/Views/partials/share.php';
      ?>
    </div>
  </div>

  <div class="profile-body auteur-body<?= $hasSide ? '' : ' is-full' ?>">
    <div>
      <?php if ($works !== []): ?>
        <h2 id="oeuvres">Œuvres</h2>
        <?php foreach ($featured as $w): ?>
          <?php $renderWork($w, true); ?>
        <?php endforeach; ?>
        <?php if ($others !== []): ?>
          <div class="auteur-works<?= $featured === [] ? '' : ' has-featured' ?>">
            <?php foreach ($others as $w): ?>
              <?php $renderWork($w, false); ?>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <dialog class="zoom-modal" id="portfolio-zoom" data-zoom-dialog aria-labelledby="portfolio-zoom-title">
          <div class="zoom-modal-inner">
            <div class="zoom-modal-bar">
              <h2 id="portfolio-zoom-title"></h2>
              <button type="button" class="zoom-modal-close" data-zoom-close aria-label="Fermer">×</button>
            </div>
            <figure class="zoom-modal-figure">
              <img data-zoom-img alt="">
              <figcaption>
                <span data-zoom-caption hidden></span>
                <p data-zoom-desc hidden></p>
              </figcaption>
            </figure>
          </div>
          <button type="button" class="zoom-modal-nav is-prev" data-zoom-prev aria-label="Visuel précédent" hidden>‹</button>
          <button type="button" class="zoom-modal-nav is-next" data-zoom-next aria-label="Visuel suivant" hidden>›</button>
        </dialog>
      <?php endif; ?>

      <?php if ($bio !== ''): ?>
        <h2 id="biographie">Biographie</h2>
        <div class="profile-text auteur-bio"><?= nl2br(e($bio)) ?></div>
      <?php elseif ($works === []): ?>
        <p class="profile-text" style="margin-top: 20px;">Cette fiche auteur est en cours de rédaction.</p>
      <?php endif; ?>

      <?php if ($awards !== []): ?>
        <h2 id="prix">Prix et distinctions</h2>
        <div class="timeline">
          <?php foreach ($awards as $award): ?>
            <div class="timeline-row">
              <span><?= e((string) ($award['year'] ?? '')) ?></span>
              <div>
                <strong><?= e((string) ($award['label'] ?? '')) ?></strong>
                <?php if (trim((string) ($award['work'] ?? '')) !== ''): ?><em>pour <?= e((string) $award['work']) ?></em><?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($press !== []): ?>
        <h2 id="presse">Presse et médias</h2>
        <ul class="auteur-press">
          <?php foreach ($press as $item): ?>
            <?php
              $url = (string) ($item['url'] ?? '');
              $title = trim((string) ($item['title'] ?? '')) ?: $url;
              $sub = implode(' · ', array_filter([trim((string) ($item['source'] ?? '')), trim((string) ($item['date'] ?? ''))]));
            ?>
            <li>
              <a href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer"><?= e($title) ?></a>
              <?php if ($sub !== ''): ?><span><?= e($sub) ?></span><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <?php if ($events !== []): ?>
        <h2 id="rencontres">Rencontres et actualités</h2>
        <div class="timeline">
          <?php foreach ($events as $ev): ?>
            <div class="timeline-row">
              <span><?= e((string) ($ev['date'] ?? '')) ?></span>
              <div>
                <strong>
                  <?php if (trim((string) ($ev['url'] ?? '')) !== ''): ?>
                    <a href="<?= e((string) $ev['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e((string) ($ev['label'] ?? '')) ?></a>
                  <?php else: ?>
                    <?= e((string) ($ev['label'] ?? '')) ?>
                  <?php endif; ?>
                </strong>
                <?php if (trim((string) ($ev['place'] ?? '')) !== ''): ?><em><?= e((string) $ev['place']) ?></em><?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($hasSide): ?>
      <aside class="profile-side">
        <div class="side-card">
          <?php if ($website !== '' || $wikipedia !== '' || $links !== []): ?>
            <div class="side-kicker">Sur le web</div>
            <ul class="auteur-links">
              <?php if ($website !== ''): ?>
                <li><a href="<?= e($website) ?>" target="_blank" rel="noopener noreferrer me"><?= icon('share', 16) ?> Site de l'auteur</a></li>
              <?php endif; ?>
              <?php if ($wikipedia !== ''): ?>
                <li><a href="<?= e($wikipedia) ?>" target="_blank" rel="noopener noreferrer"><?= icon('book', 16) ?> Wikipédia</a></li>
              <?php endif; ?>
              <?php foreach ($links as $link): ?>
                <?php
                  $label = trim((string) ($link['label'] ?? ''));
                  if ($label === '') {
                      $label = (string) ($link['kind_label'] ?? 'Lien');
                  }
                ?>
                <li><a href="<?= e((string) $link['url']) ?>" target="_blank" rel="noopener noreferrer"><?= icon('arrow', 16) ?> <?= e($label) ?></a></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
          <?php if ($openTo !== []): ?>
            <div class="side-kicker" style="margin-top: 18px;">Disponible pour</div>
            <ul class="auteur-open-to">
              <?php foreach ($openTo as $label): ?>
                <li><?= icon('check', 14) ?> <?= e((string) $label) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
          <div class="info-list">
            <?php if (!empty($a['profile_href'])): ?>
              <div><span>Aussi prestataire</span><a href="<?= e(url((string) $a['profile_href'])) ?>">Voir la vitrine</a></div>
            <?php endif; ?>
            <?php if (($a['member_since_label'] ?? '') !== ''): ?>
              <div><span>Sur la plateforme</span><strong><?= e((string) $a['member_since_label']) ?></strong></div>
            <?php endif; ?>
          </div>
          <?php if (!$isOwner): ?>
            <details class="profile-report">
              <summary>Signaler cette fiche</summary>
              <form method="post" action="<?= e(url('/signaler')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="type" value="user">
                <input type="hidden" name="id" value="<?= (int) ($a['user_id'] ?? 0) ?>">
                <input type="hidden" name="back" value="<?= e((string) ($a['href'] ?? '/auteurs')) ?>">
                <label class="field" for="report-reason">Motif</label>
                <select class="input" id="report-reason" name="reason" required>
                  <?php foreach (\Adl\Models\Report::REASONS as $value => $label): ?>
                    <option value="<?= e($value) ?>"><?= e($label) ?></option>
                  <?php endforeach; ?>
                </select>
                <textarea class="textarea" name="body" rows="2" placeholder="Précision (optionnel)"></textarea>
                <button class="btn-ghost" type="submit">Envoyer le signalement</button>
              </form>
            </details>
          <?php endif; ?>
        </div>
      </aside>
    <?php endif; ?>
  </div>
</div>
