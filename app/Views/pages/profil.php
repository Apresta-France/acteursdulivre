<?php
$p = $liveProfile ?? null;
if (!$p) {
    not_found('Ce profil n\'est pas publié.');
}
$viewer = \Adl\Core\Auth::user();
$isOwnProfile = $viewer && (int) ($viewer['id'] ?? 0) === (int) ($p['user_id'] ?? 0);
?>
<div class="profile-page">
  <div class="profile-hero">
    <?= avatar_html($p, 104, 'avatar profile-avatar') ?>
    <div class="profile-hero-main">
      <div class="profile-hero-line">
        <h1><?= e((string) $p['name']) ?></h1>
        <?php if (!empty($p['is_founder'])): ?>
          <span class="profile-badge profile-badge-founder">Membre fondateur</span>
        <?php endif; ?>
        <?php if (!empty($p['is_verified'])): ?>
          <span class="profile-badge">Vérifié</span>
        <?php endif; ?>
        <span class="profile-badge"><?= e((string) ($p['level'] ?: 'Prestataire')) ?></span>
        <span class="profile-badge profile-badge-avail<?= !empty($p['is_busy']) ? ' is-busy' : ' is-available' ?>"><?= e((string) ($p['availability_label'] ?? (!empty($p['is_busy']) ? 'Occupé' : 'Disponible'))) ?></span>
      </div>
      <div class="profile-hero-sub">
        <?= e(trim(($p['title'] ?? '') . (!empty($p['location_label']) ? ' · ' . $p['location_label'] : ($p['city'] ? ' · ' . $p['city'] : '')) . ($p['languages'] ? ' · ' . $p['languages'] : ''))) ?>
      </div>
      <?php
        $reviewStats = $p['review_stats'] ?? ['avg' => '', 'count' => 0];
        $reviewCount = (int) ($reviewStats['count'] ?? 0);
        $hasHeroStats = $reviewCount > 0 || ($p['response_time_label'] ?? '') !== '' || ($p['member_since_label'] ?? '') !== '';
      ?>
      <?php if ($hasHeroStats): ?>
        <div class="profile-hero-stats">
          <?php if ($reviewCount > 0): ?>
            <div>
              <strong><?= e((string) $reviewStats['avg']) ?></strong>
              <span><?= $reviewCount > 1 ? $reviewCount . ' avis' : '1 avis' ?></span>
            </div>
          <?php endif; ?>
          <?php if (($p['response_time_label'] ?? '') !== ''): ?>
            <div>
              <strong><?= e((string) $p['response_time_label']) ?></strong>
              <span>délai de réponse</span>
            </div>
          <?php endif; ?>
          <?php if (($p['member_since_label'] ?? '') !== ''): ?>
            <div>
              <strong><?= e(preg_replace('/^Membre depuis /', '', (string) $p['member_since_label'])) ?></strong>
              <span>membre depuis</span>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      <?php if (!empty($p['trades']) || !empty($p['tags'])): ?>
        <div class="chip-row">
          <?php foreach (array_slice($p['tags'] ?: $p['trades'], 0, 8) as $tag): ?>
            <span class="chip-static"><?= e((string) $tag) ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <div class="profile-hero-actions">
      <?php if ($isOwnProfile): ?>
        <p class="profile-avail-note">C’est votre vitrine publique. Les porteurs de projet vous écrivent depuis cette page.</p>
        <a class="btn-orange" href="<?= e(url('/espace/vitrine')) ?>">Modifier la vitrine</a>
      <?php elseif (!empty($p['is_busy'])): ?>
        <p class="profile-avail-note">Planning actuellement chargé. Vous pouvez laisser un message pour une date ultérieure.</p>
        <form method="post" action="<?= e(url('/espace/messages')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="avec" value="<?= (int) ($p['user_id'] ?? 0) ?>">
          <input type="hidden" name="sujet" value="Message">
          <button class="btn-orange" type="submit">Envoyer un message</button>
        </form>
      <?php else: ?>
        <form method="post" action="<?= e(url('/espace/messages')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="avec" value="<?= (int) ($p['user_id'] ?? 0) ?>">
          <input type="hidden" name="sujet" value="Demande de devis">
          <button class="btn-orange" type="submit">Demander un devis</button>
        </form>
        <form method="post" action="<?= e(url('/espace/messages')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="avec" value="<?= (int) ($p['user_id'] ?? 0) ?>">
          <button class="btn-ghost-light" type="submit">Envoyer un message</button>
        </form>
      <?php endif; ?>
      <?php
        $shareUrl = $meta['url'] ?? \Adl\Data\Share::current();
        $shareTitle = $meta['title'] ?? ((string) ($p['name'] ?? 'Prestataire') . ' — acteursdulivre.fr');
        $shareText = $meta['description'] ?? trim((string) ($p['title'] ?? '') . ($p['city'] ? ' · ' . $p['city'] : ''));
        $shareCompact = true;
        $shareNative = false;
        require ADL_ROOT . '/app/Views/partials/share.php';
      ?>
    </div>
  </div>

  <div class="profile-body">
    <div>
      <?php if ($p['presentation'] !== ''): ?>
        <h2>À propos</h2>
        <p class="profile-text"><?= nl2br(e((string) $p['presentation'])) ?></p>
      <?php endif; ?>

      <?php
        $doesLines = \Adl\Models\Profile::scopeLines($p['does'] ?? '');
        $doesNotLines = \Adl\Models\Profile::scopeLines($p['does_not'] ?? '');
      ?>
      <?php if ($doesLines !== [] || $doesNotLines !== []): ?>
        <div class="profile-scope<?= ($doesLines === [] || $doesNotLines === []) ? ' is-single' : '' ?>">
          <?php if ($doesLines !== []): ?>
            <div class="profile-scope-card">
              <div class="side-kicker">Ce que je fais</div>
              <ul>
                <?php foreach ($doesLines as $line): ?>
                  <li><?= e($line) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>
          <?php if ($doesNotLines !== []): ?>
            <div class="profile-scope-card is-not">
              <div class="side-kicker">Ce que je ne fais pas</div>
              <ul>
                <?php foreach ($doesNotLines as $line): ?>
                  <li><?= e($line) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>
        </div>
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
            <?php
              $itemTitle = (string) ($item['title'] ?? '');
              $itemCaption = (string) ($item['caption'] ?? $item['kind_label'] ?? '');
              $itemDesc = (string) ($item['description'] ?? '');
            ?>
            <figure class="portfolio-item">
              <?php if (!empty($item['img'])): ?>
                <a
                  class="portfolio-item-media"
                  href="<?= e((string) $item['img']) ?>"
                  style="background-image:url('<?= e((string) $item['img']) ?>')"
                  data-zoom
                  data-zoom-title="<?= e($itemTitle) ?>"
                  data-zoom-caption="<?= e($itemCaption) ?>"
                  data-zoom-desc="<?= e($itemDesc) ?>"
                  aria-haspopup="dialog"
                  aria-controls="portfolio-zoom"
                  aria-label="<?= e('Agrandir : ' . ($itemTitle !== '' ? $itemTitle : 'exemple')) ?>"
                >
                  <span class="portfolio-item-zoom" aria-hidden="true"><?= icon('search', 16) ?></span>
                </a>
              <?php endif; ?>
              <figcaption>
                <strong><?= e($itemTitle) ?></strong>
                <span><?= e($itemCaption) ?></span>
                <?php if ($itemDesc !== ''): ?>
                  <p><?= e($itemDesc) ?></p>
                <?php endif; ?>
              </figcaption>
            </figure>
          <?php endforeach; ?>
        </div>
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
          <button type="button" class="zoom-modal-nav is-prev" data-zoom-prev aria-label="Exemple précédent" hidden>‹</button>
          <button type="button" class="zoom-modal-nav is-next" data-zoom-next aria-label="Exemple suivant" hidden>›</button>
        </dialog>
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

      <?php if (!empty($p['services'])): ?>
        <h2>Prestations</h2>
        <div class="my-missions">
          <?php foreach ($p['services'] as $offer): ?>
            <a class="side-card" href="<?= e(url((string) $offer['href'])) ?>" style="text-decoration: none;">
              <div class="mission-row-title"><?= e((string) $offer['title']) ?></div>
              <div class="side-foot">
                <span><?= e((string) ($offer['delay'] ?: '')) ?></span>
                <strong><?= e((string) $offer['price']) ?></strong>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($p['reviews'])): ?>
        <h2>Avis<?php if (!empty($p['review_stats']['count'])): ?> · <?= e((string) $p['review_stats']['avg']) ?> / 5<?php endif; ?></h2>
        <div class="my-missions">
          <?php foreach ($p['reviews'] as $review): ?>
            <article class="side-card">
              <div class="mission-row-title"><?= e((string) $review['who']) ?> · <?= e((string) $review['note']) ?></div>
              <?php if (!empty($review['txt'])): ?>
                <p class="profile-text"><?= e((string) $review['txt']) ?></p>
              <?php endif; ?>
              <div class="mission-row-sub"><?= e((string) $review['when']) ?></div>
            </article>
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
          <div class="side-kicker"><?= e((string) ($p['rate_kicker'] ?? \Adl\Models\Profile::rateKicker($p))) ?></div>
          <div class="profile-rate"><?= e((string) $p['hourly_rate']) ?></div>
          <?php if ($p['rate_note'] !== ''): ?><div class="side-sub"><?= e((string) $p['rate_note']) ?></div><?php endif; ?>
        <?php endif; ?>
        <div class="info-list">
          <?php if (($p['location_label'] ?? '') !== '' || $p['city'] !== ''): ?>
            <div>
              <span>Localisation</span>
              <strong><?php
                $loc = (string) (($p['location_label'] ?? '') !== '' ? $p['location_label'] : $p['city']);
                $area = (string) ($p['city_area_slug'] ?? '');
                $geoLinks = [];
                if ($area !== '') {
                    foreach ($p['trades'] ?? [] as $tradeName) {
                        $tradeName = (string) $tradeName;
                        if ($tradeName === '' || !\Adl\Data\Catalog::tradeCityHasResults($tradeName, $area)) {
                            continue;
                        }
                        $geoLinks[] = '<a href="' . e(url(\Adl\Data\Catalog::catalogPath('prestataires', $tradeName, $area))) . '">' . e(\Adl\Data\Catalog::tradeGeoLabel($tradeName) . ' à ' . ($p['city'] ?? $area)) . '</a>';
                    }
                }
                echo e($loc);
                if ($geoLinks !== []) {
                    echo '<span class="profile-geo-links">' . implode(' · ', $geoLinks) . '</span>';
                }
              ?></strong>
            </div>
          <?php endif; ?>
          <div><span>Disponibilité</span><strong><?= e((string) ($p['availability_summary'] ?? ($p['availability'] !== '' ? $p['availability'] : 'Disponible'))) ?></strong></div>
          <?php if (($p['response_time_label'] ?? '') !== ''): ?>
            <div><span>Réponse</span><strong><?= e((string) $p['response_time_label']) ?></strong></div>
          <?php endif; ?>
          <?php
            $langLines = [];
            foreach ($p['languages_list'] ?? [] as $lang) {
                $label = trim((string) ($lang['langue'] ?? ''));
                if ($label === '') {
                    continue;
                }
                $level = trim((string) ($lang['niveau'] ?? ''));
                $langLines[] = $level !== '' ? $label . ' · ' . $level : $label;
            }
          ?>
          <?php if ($langLines !== []): ?>
            <div>
              <span>Langues</span>
              <strong><?= implode('<br>', array_map('e', $langLines)) ?></strong>
            </div>
          <?php elseif ($p['languages'] !== ''): ?>
            <div><span>Langues</span><strong><?= e((string) $p['languages']) ?></strong></div>
          <?php endif; ?>
          <?php if (!empty($p['trades'])): ?><div><span>Métiers</span><strong><?= e(implode(', ', $p['trades'])) ?></strong></div><?php endif; ?>
          <?php if (!empty($p['genres'])): ?><div><span>Spécialités</span><strong><?= e(implode(', ', $p['genres'])) ?></strong></div><?php endif; ?>
          <?php if (($p['member_since_label'] ?? '') !== ''): ?>
            <div><span>Sur la plateforme</span><strong><?= e((string) $p['member_since_label']) ?></strong></div>
          <?php endif; ?>
          <?php if ($p['website'] !== ''): ?>
            <?php $website = (string) $p['website']; ?>
            <div><span>Site</span><?php if (preg_match('#^https?://#i', $website)): ?><a href="<?= e($website) ?>" target="_blank" rel="noopener noreferrer"><?= e($website) ?></a><?php else: ?><strong><?= e($website) ?></strong><?php endif; ?></div>
          <?php endif; ?>
        </div>
        <?php if (!empty($p['socials'])): ?>
          <div class="profile-socials">
            <?php foreach ($p['socials'] as $social): ?>
              <a href="<?= e((string) $social['url']) ?>"
                 target="_blank" rel="noopener noreferrer"
                 title="<?= e((string) $social['label']) ?>"
                 aria-label="<?= e((string) $social['label']) ?>"><?= icon('share-' . (string) $social['network'], 16) ?></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <?php if (!$isOwnProfile): ?>
        <details class="profile-report">
          <summary>Signaler ce profil</summary>
          <form method="post" action="<?= e(url('/signaler')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="type" value="user">
            <input type="hidden" name="id" value="<?= (int) ($p['user_id'] ?? 0) ?>">
            <input type="hidden" name="back" value="<?= e((string) ($p['href'] ?? '/')) ?>">
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
