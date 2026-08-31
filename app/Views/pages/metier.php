<?php
$trade = (string) ($trade ?? '');
$label = (string) ($tradeLabel ?? $trade);
$tradeTitle = (string) ($tradeTitle ?? $label);
$geo = $tradeGeo ?? \Adl\Data\Seo::tradeCopy($trade);
$providers = $providers ?? [];
$services = $services ?? [];
$missions = $missions ?? [];
$providerCount = (int) ($providerCount ?? count($providers));
$serviceCount = (int) ($serviceCount ?? count($services));
$missionCount = (int) ($missionCount ?? count($missions));
$tradeSpecs = $tradeSpecs ?? [];
$volumeHint = $volumeHint ?? null;
$briefHint = (string) ($briefHint ?? '');
$otherTrades = $otherTrades ?? [];
$heroImg = (string) ($heroImg ?? photo(0));
$catQ = rawurlencode($trade);

$plural = static function (int $n, string $one, string $many, string $none): string {
    if ($n === 0) {
        return $none;
    }
    return $n . ' ' . ($n > 1 ? $many : $one);
};

$blocks = [
    [
        'title' => 'Prestations à prix affiché',
        'count' => $serviceCount,
        'countLabel' => $plural($serviceCount, 'offre', 'offres', 'Aucune offre pour le moment'),
        'href' => url('/prestations?cat=' . $catQ),
        'items' => $services,
        'emptyTitle' => 'Aucune prestation publiée.',
        'emptyBody' => 'Les offres packagées de ce métier apparaîtront ici.',
        'emptyHref' => url('/espace/prestations/creer'),
        'emptyCta' => 'Proposer une prestation',
    ],
    [
        'title' => 'Prestataires',
        'count' => $providerCount,
        'countLabel' => $plural($providerCount, 'profil', 'profils', 'Aucun profil pour le moment'),
        'href' => url('/prestataires?cat=' . $catQ),
        'items' => $providers,
        'emptyTitle' => 'Aucun profil publié pour ce métier.',
        'emptyBody' => 'Les prestataires qui renseignent « ' . $trade . ' » dans leur vitrine apparaîtront ici.',
        'emptyHref' => url('/inscription'),
        'emptyCta' => 'Créer ma vitrine',
    ],
    [
        'title' => 'Recherches ouvertes',
        'count' => $missionCount,
        'countLabel' => $plural($missionCount, 'recherche', 'recherches', 'Aucune recherche pour le moment'),
        'href' => url('/missions?metier=' . $catQ),
        'items' => $missions,
        'emptyTitle' => 'Aucune recherche ouverte.',
        'emptyBody' => 'Publiez un appel d\'offres pour recevoir des devis.',
        'emptyHref' => url('/espace/publier'),
        'emptyCta' => 'Publier une recherche',
    ],
];
?>
<div class="metier-page">
  <section class="metier-hero">
    <div>
      <nav class="search-crumb" aria-label="Fil d'Ariane">
        <a href="<?= e(url('/')) ?>">Accueil</a>
        · <a href="<?= e(url('/recherche')) ?>">Métiers</a>
        · <?= e($tradeTitle) ?>
      </nav>
      <p class="mk-kicker">Métier · <?= e($tradeTitle) ?></p>
      <h1><?= e((string) ($geo['h1'] ?? $label)) ?></h1>
      <p class="mk-lead"><?= e((string) ($geo['lead'] ?? ('Prestataires, prestations à prix affiché et missions ouvertes pour le métier « ' . $trade . ' ».'))) ?></p>
      <div class="metier-hero-stats" aria-label="Chiffres de ce métier">
        <div>
          <strong><?= (int) $providerCount ?></strong>
          <span><?= $providerCount > 1 ? 'prestataires' : 'prestataire' ?></span>
        </div>
        <div>
          <strong><?= (int) $serviceCount ?></strong>
          <span><?= $serviceCount > 1 ? 'prestations' : 'prestation' ?></span>
        </div>
        <div>
          <strong><?= (int) $missionCount ?></strong>
          <span><?= $missionCount > 1 ? 'recherches ouvertes' : 'recherche ouverte' ?></span>
        </div>
      </div>
      <div class="metier-hero-actions">
        <a class="btn-navy" href="<?= e(url('/prestataires?cat=' . $catQ)) ?>">Voir les profils</a>
        <a class="btn-ghost" href="<?= e(url('/espace/publier')) ?>">Publier une recherche</a>
      </div>
      <?php if ($tradeSpecs !== []): ?>
        <div class="metier-chips">
          <?php foreach ($tradeSpecs as $spec): ?>
            <a href="<?= e(url('/prestations?cat=' . $catQ . '&spec=' . rawurlencode((string) $spec['v']))) ?>"><?= e((string) $spec['l']) ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <div class="metier-hero-visual" aria-hidden="true">
      <img src="<?= e($heroImg) ?>" alt="" width="400" height="280" loading="eager" decoding="async">
    </div>
  </section>

  <?php foreach ($blocks as $block): ?>
    <section class="metier-block">
      <div class="mk-head">
        <div>
          <h2><?= e((string) $block['title']) ?></h2>
          <p><?= e((string) $block['countLabel']) ?><?= (int) $block['count'] > 0 ? ' sur la plateforme.' : '.' ?></p>
        </div>
        <a href="<?= e((string) $block['href']) ?>">Tout voir →</a>
      </div>
      <?php if ($block['items'] === []): ?>
        <div class="search-empty">
          <strong><?= e((string) $block['emptyTitle']) ?></strong>
          <span><?= e((string) $block['emptyBody']) ?></span>
          <a class="btn-ghost" href="<?= e((string) $block['emptyHref']) ?>"><?= e((string) $block['emptyCta']) ?></a>
        </div>
      <?php else: ?>
        <div class="search-grid">
          <?php foreach ($block['items'] as $item): ?>
            <a class="search-card<?= !empty($item['is_busy']) ? ' is-busy' : '' ?>" href="<?= e(url((string) $item['href'])) ?>">
              <?= search_card_media($item) ?>
              <div class="search-card-body">
                <div class="search-card-kicker">
                  <span><?= e((string) ($item['kind_label'] ?? '')) ?></span>
                  <?php if (!empty($item['cat'])): ?><span><?= e((string) $item['cat']) ?></span><?php endif; ?>
                  <?php if (($item['kind'] ?? '') === 'prestataires' && !empty($item['availability_label'])): ?>
                    <span class="status-pill<?= !empty($item['is_busy']) ? ' is-busy' : ' is-available' ?>"><?= e((string) $item['availability_label']) ?></span>
                  <?php endif; ?>
                </div>
                <div class="search-card-title"><?= e((string) $item['title']) ?></div>
                <div class="search-card-sub"><?= e((string) $item['subtitle']) ?></div>
                <div class="search-card-meta">
                  <span><?php
                    $metaBits = array_filter([
                        (string) ($item['meta'] ?? ''),
                        !empty($item['rating']) ? '★ ' . $item['rating'] : '',
                    ]);
                    echo e(implode(' · ', $metaBits));
                  ?></span>
                  <?php if (!empty($item['price'])): ?><strong><?= e((string) $item['price']) ?></strong><?php endif; ?>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  <?php endforeach; ?>

  <section class="metier-block">
    <div class="metier-brief">
      <div>
        <p class="mk-kicker">Préparer le brief</p>
        <h2>Bien lancer une mission</h2>
        <p><?= e($briefHint !== '' ? $briefHint : 'Attentes, contraintes, calendrier…') ?></p>
      </div>
      <ul class="metier-tips">
        <?php if (is_array($volumeHint) && ($volumeHint['label'] ?? '') !== ''): ?>
          <li><span>→</span> Indiquez le <?= e(mb_strtolower((string) $volumeHint['label'])) ?><?= !empty($volumeHint['placeholder']) ? ' (ex. ' . e((string) $volumeHint['placeholder']) . ')' : '' ?>.</li>
        <?php else: ?>
          <li><span>→</span> Décrivez le périmètre : ce qui est inclus, ce qui ne l'est pas.</li>
        <?php endif; ?>
        <li><span>→</span> Annoncez une date de livraison plutôt que « dès que possible ».</li>
        <li><span>→</span> Précisez le format de livrable et les fichiers déjà en main.</li>
        <li><span>→</span> Le règlement se fait hors plateforme ; le suivi impose les jalons.</li>
      </ul>
    </div>
  </section>

  <section class="mk-cta">
    <div>
      <h2>Vous exercez ce métier ?</h2>
      <p>Créez votre vitrine, publiez des prestations à prix affiché, répondez aux recherches. Aucun abonnement : la première mission est offerte.</p>
    </div>
    <div class="mk-cta-actions">
      <a class="btn-orange" href="<?= e(url('/inscription')) ?>">Proposer mes services</a>
      <a class="btn-ghost" href="<?= e(url('/tarifs')) ?>">Voir les tarifs</a>
    </div>
  </section>

  <?php if ($otherTrades !== []): ?>
    <section class="metier-block">
      <div class="mk-head">
        <h2>Les autres métiers du livre</h2>
        <a href="<?= e(url('/recherche')) ?>">Tout l'annuaire →</a>
      </div>
      <div class="metier-others">
        <?php foreach ($otherTrades as $other): ?>
          <a href="<?= e(url((string) $other['href'])) ?>"><?= e((string) $other['label']) ?></a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</div>
