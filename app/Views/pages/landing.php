<?php
$lp = is_array($landing ?? null) ? $landing : [];
$trade = (string) ($lp['trade'] ?? '');
$h1 = (string) ($lp['h1'] ?? $title ?? '');
$lead = (string) ($lp['lead'] ?? '');
$leadParagraphs = $lp['lead_paragraphs'] ?? [];
if (!is_array($leadParagraphs)) {
    $leadParagraphs = [];
}
$kicker = (string) ($lp['kicker'] ?? '');
$price = (string) ($lp['price'] ?? '');
$priceDetail = (string) ($lp['price_detail'] ?? '');
$priceNote = (string) ($lp['price_note'] ?? '');
$includes = $lp['includes'] ?? [];
$excludes = $lp['excludes'] ?? [];
$steps = $lp['steps'] ?? [];
$proofs = $lp['proofs'] ?? [];
$faq = $lp['faq'] ?? [];
$others = $lp['others'] ?? [];
$journal = is_array($lp['journal_page'] ?? null) ? $lp['journal_page'] : null;
$primaryHref = (string) ($lp['primary_href'] ?? '/inscription');
$secondaryHref = (string) ($lp['secondary_href'] ?? '/prestataires');
$primaryLabel = (string) ($lp['primary_label'] ?? 'Créer un compte');
$secondaryLabel = (string) ($lp['secondary_label'] ?? 'Voir les prestataires');
$providerCount = (int) ($providerCount ?? 0);
$serviceCount = (int) ($serviceCount ?? 0);
$missionCount = (int) ($missionCount ?? 0);
$providers = $providers ?? [];
$services = $services ?? [];
$audience = (string) ($lp['audience'] ?? 'client');
$isOfferer = $audience === 'prestataire';
$people = is_array($lp['people'] ?? null) ? $lp['people'] : \Adl\Data\Landings::people($trade);
$prestationsHref = (string) ($lp['prestations_href'] ?? '/prestations');
$prestatairesHref = (string) ($lp['prestataires_href'] ?? '/prestataires');
$heroCostLabel = (string) ($lp['hero_cost_label'] ?? 'Combien ça coûte');
$heroFindLabel = (string) ($lp['hero_find_label'] ?? ('Trouver un ' . ($people['one'] ?? 'prestataire')));
$ctaFind = (string) ($lp['cta_find'] ?? $heroFindLabel);
$ctaPublish = (string) ($lp['cta_publish'] ?? 'Publier une recherche');
$servicesTitle = (string) ($lp['services_title'] ?? 'Prestations à prix affiché');
$servicesIntro = (string) ($lp['services_intro'] ?? '');
$servicesCta = (string) ($lp['services_cta'] ?? $heroFindLabel);
$providersTitle = (string) ($lp['providers_title'] ?? ($people['title'] ?? 'Prestataires'));
$providersBridge = (string) ($lp['providers_bridge'] ?? '');
$iaPoints = $iaPoints ?? [];
$costAnchor = $services !== [] ? '#prestations' : ($providers !== [] ? '#prestataires' : '#tarif');
$peopleMany = (string) ($people['many'] ?? 'prestataires');
$providersCta = $providerCount > 0
    ? 'Voir les ' . format_int($providerCount) . ' ' . $peopleMany
    : 'Voir les ' . $peopleMany;
$stickyHref = $isOfferer ? $primaryHref : $prestatairesHref;
$stickyLabel = $isOfferer ? $primaryLabel : $heroFindLabel;
?>
<div class="lp-page<?= $isOfferer ? ' lp-offerer' : ' lp-client' ?>">
  <section class="lp-hero">
    <div class="lp-hero-copy">
      <?php if ($kicker !== ''): ?>
        <p class="mk-kicker"><?= e($kicker) ?></p>
      <?php endif; ?>
      <h1><?= e($h1) ?></h1>
      <?php if ($leadParagraphs !== []): ?>
        <?php foreach ($leadParagraphs as $para): ?>
          <p class="mk-lead"><?= e((string) $para) ?></p>
        <?php endforeach; ?>
      <?php elseif ($lead !== ''): ?>
        <p class="mk-lead"><?= e($lead) ?></p>
      <?php endif; ?>
      <div class="lp-hero-actions">
        <?php if ($isOfferer): ?>
          <a class="btn-orange" href="<?= e(url($primaryHref)) ?>"><?= e($primaryLabel) ?></a>
          <a class="btn-ghost" href="<?= e(url($secondaryHref)) ?>"><?= e($secondaryLabel) ?></a>
        <?php else: ?>
          <a class="btn-orange" href="<?= e($costAnchor) ?>"><?= e($heroCostLabel) ?></a>
          <a class="btn-ghost" href="<?= e(url($prestatairesHref)) ?>"><?= e($heroFindLabel) ?></a>
        <?php endif; ?>
      </div>
      <?php if ($providerCount > 0 || $serviceCount > 0 || $missionCount > 0): ?>
        <div class="lp-stats" role="group" aria-label="Chiffres de ce besoin">
          <?php if (!$isOfferer && $providerCount > 0): ?>
            <a href="<?= e(url($prestatairesHref)) ?>">
              <strong><?= (int) $providerCount ?></strong>
              <span><?= $providerCount > 1 ? e($peopleMany) : e((string) ($people['one'] ?? 'prestataire')) ?></span>
            </a>
          <?php endif; ?>
          <?php if ($serviceCount > 0): ?>
            <a href="<?= e(url($prestationsHref)) ?>">
              <strong><?= (int) $serviceCount ?></strong>
              <span><?= $serviceCount > 1 ? 'prestations' : 'prestation' ?></span>
            </a>
          <?php endif; ?>
          <?php if ($missionCount > 0): ?>
            <a href="<?= e(url((string) ($lp['missions_href'] ?? '/missions'))) ?>">
              <strong><?= (int) $missionCount ?></strong>
              <span><?= $missionCount > 1 ? 'recherches ouvertes' : 'recherche ouverte' ?></span>
            </a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
    <?php
      $heroImgs = is_array($homeHeroImgs ?? null) && count($homeHeroImgs) >= 3
        ? array_values($homeHeroImgs)
        : home_hero_photos();
      $heroSrcs = json_encode($heroImgs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    ?>
    <div class="mk-hero-visual">
      <div class="mk-mosaic" aria-hidden="true" data-hero-mosaic data-hero-srcs="<?= e((string) $heroSrcs) ?>">
        <div class="mk-mosaic-a">
          <img src="<?= e((string) ($heroImgs[0] ?? '')) ?>" alt="" width="214" height="312" fetchpriority="high" decoding="async">
        </div>
        <div class="mk-mosaic-b">
          <img src="<?= e((string) ($heroImgs[1] ?? '')) ?>" alt="" width="214" height="150" decoding="async">
        </div>
        <div class="mk-mosaic-c">
          <img src="<?= e((string) ($heroImgs[2] ?? '')) ?>" alt="" width="214" height="150" decoding="async">
        </div>
      </div>
      <a class="mk-hero-play" href="https://youtu.be/3ceBiEN9RJ8" data-video-open aria-haspopup="dialog" aria-controls="home-video" aria-label="Lire la vidéo de présentation">
        <span class="mk-hero-play-btn" aria-hidden="true"><?= icon('play', 28) ?></span>
        <span class="mk-hero-play-label">Lecture</span>
      </a>
    </div>
  </section>

  <dialog
    class="mk-video-modal"
    id="home-video"
    aria-labelledby="home-video-title"
    data-video-src="https://www.youtube-nocookie.com/embed/3ceBiEN9RJ8?autoplay=1&amp;rel=0"
    data-video-title="Vidéo de présentation — Acteurs du livre"
  >
    <div class="mk-video-modal-inner">
      <div class="mk-video-modal-bar">
        <h2 id="home-video-title">Vidéo de présentation</h2>
        <button type="button" class="mk-video-modal-close" data-video-close aria-label="Fermer">×</button>
      </div>
      <div class="mk-video-frame" data-video-frame></div>
    </div>
  </dialog>

  <?php if ($isOfferer && $price !== ''): ?>
    <section class="lp-price">
      <div>
        <p class="mk-kicker">Ordre de grandeur</p>
        <p class="lp-price-value"><?= e($price) ?></p>
        <?php if ($priceDetail !== ''): ?>
          <p><?= e($priceDetail) ?></p>
        <?php endif; ?>
        <?php if ($priceNote !== ''): ?>
          <p class="lp-price-note"><?= e($priceNote) ?></p>
        <?php endif; ?>
      </div>
      <?php if ($trade !== ''): ?>
        <a class="btn-ghost" href="<?= e(url((string) ($lp['trade_path'] ?? '/recherche'))) ?>">Page métier <?= e($trade) ?> →</a>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <?php if ($isOfferer && $steps !== []): ?>
    <section class="mk-steps lp-steps">
      <?php foreach ($steps as $s): ?>
        <div>
          <div class="mk-kicker"><?= e((string) ($s['num'] ?? '')) ?></div>
          <h3><?= e((string) ($s['title'] ?? '')) ?></h3>
          <p><?= e((string) ($s['body'] ?? '')) ?></p>
        </div>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>

  <?php if ($isOfferer && ($includes !== [] || $excludes !== [])): ?>
    <section class="lp-split">
      <?php if ($includes !== []): ?>
        <div>
          <h2>Ce que vous achetez</h2>
          <ul class="mk-points">
            <?php foreach ($includes as $item): ?>
              <li><?= e((string) $item) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
      <?php if ($excludes !== []): ?>
        <div>
          <h2>Ce que ce n’est pas</h2>
          <ul class="mk-points lp-excludes">
            <?php foreach ($excludes as $item): ?>
              <li><?= e((string) $item) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <?php if ($isOfferer && $proofs !== []): ?>
    <section class="lp-proofs">
      <h2>Le cadre de la plateforme</h2>
      <ul>
        <?php foreach ($proofs as $p): ?>
          <li><?= e((string) $p) ?></li>
        <?php endforeach; ?>
      </ul>
      <p><a href="<?= e(url('/comment-ca-marche')) ?>">Comment ça marche</a> · <a href="<?= e(url('/tarifs')) ?>">Tarifs</a> · <a href="<?= e(url('/confiance')) ?>">Confiance</a> · <a href="<?= e(url('/regles-ia')) ?>">Règles IA</a></p>
    </section>
  <?php endif; ?>

  <?php if (!$isOfferer && ($services !== [] || $providers !== [])): ?>
    <?php if ($services !== []): ?>
      <section class="lp-live" id="prestations">
        <div class="mk-head">
          <h2><?= e($servicesTitle) ?></h2>
        </div>
        <?php if ($servicesIntro !== ''): ?>
          <p class="lp-section-lead"><?= e($servicesIntro) ?></p>
        <?php endif; ?>
        <div class="search-grid">
          <?php foreach ($services as $item): ?>
            <?= search_card_html($item) ?>
          <?php endforeach; ?>
        </div>
        <div class="lp-section-cta">
          <a class="btn-orange" href="<?= e(url($prestatairesHref)) ?>"><?= e($servicesCta) ?></a>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($providers !== []): ?>
      <?php if ($providersBridge !== ''): ?>
        <section class="lp-bridge">
          <p><?= e($providersBridge) ?></p>
        </section>
      <?php endif; ?>
      <section class="lp-live" id="prestataires">
        <div class="mk-head">
          <h2><?= e($providersTitle) ?></h2>
        </div>
        <div class="search-grid">
          <?php foreach ($providers as $item): ?>
            <?= search_card_html($item) ?>
          <?php endforeach; ?>
        </div>
        <div class="lp-section-cta">
          <a class="btn-ghost" href="<?= e(url($prestatairesHref)) ?>"><?= e($providersCta) ?></a>
        </div>
      </section>
    <?php endif; ?>
  <?php elseif ($isOfferer && ($services !== [] || $providers !== [])): ?>
    <section class="lp-live">
      <?php if ($services !== []): ?>
        <div class="mk-head">
          <h2>Prestations à prix affiché</h2>
          <a href="<?= e(url($prestationsHref)) ?>">Voir les offres →</a>
        </div>
        <div class="search-grid">
          <?php foreach ($services as $item): ?>
            <?= search_card_html($item) ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <?php if ($providers !== []): ?>
        <div class="mk-head">
          <h2>Profils déjà en ligne</h2>
          <a href="<?= e(url($prestatairesHref)) ?>">Voir l’annuaire →</a>
        </div>
        <div class="search-grid">
          <?php foreach ($providers as $item): ?>
            <?= search_card_html($item) ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <?php if ($faq !== []): ?>
    <section class="mk-block lp-faq-block">
      <h2><?php
        if ($isOfferer) {
            echo 'Questions fréquentes';
        } elseif ($trade === 'Correction') {
            echo 'Questions et réponses autour de la correction de manuscrit';
        } else {
            echo 'Questions et réponses';
        }
      ?></h2>
      <div class="lp-faq">
        <?php foreach ($faq as $f): ?>
          <div>
            <h3><?= e((string) ($f['q'] ?? '')) ?></h3>
            <p><?= e((string) ($f['a'] ?? '')) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <?php if (!$isOfferer): ?>
    <section class="mk-ia lp-ia">
      <div class="mk-ia-icon" aria-hidden="true">✕</div>
      <div>
        <div class="mk-kicker">Engagement de la plateforme</div>
        <h2>Ici, l'intelligence artificielle générative est interdite.</h2>
        <p>Aucun texte, aucune illustration, aucune voix livrée sur cette plateforme ne peut être produit par une IA générative. Les prestataires s'y engagent à l'inscription ; les manuscrits confiés ne sont jamais utilisés pour entraîner un modèle.</p>
      </div>
      <div class="mk-ia-box">
        <?php foreach ($iaPoints as $p): ?>
          <div><span>✕</span><?= e((string) $p) ?></div>
        <?php endforeach; ?>
        <a href="<?= e(url('/regles-ia')) ?>">Lire nos règles IA →</a>
      </div>
    </section>

    <section class="mk-cta">
      <div>
        <h2><?= e($ctaFind) ?></h2>
        <p>Parcourez les profils, ou publiez une recherche pour recevoir des devis.</p>
      </div>
      <div class="mk-cta-actions">
        <a class="btn-orange" href="<?= e(url($prestatairesHref)) ?>"><?= e($ctaFind) ?></a>
        <a class="btn-ghost" href="<?= e(url($primaryHref)) ?>"><?= e($ctaPublish) ?></a>
      </div>
    </section>
  <?php endif; ?>

  <?php if (!$isOfferer && ($price !== '' || $includes !== [] || $excludes !== [])): ?>
    <?php if ($price !== ''): ?>
      <section class="lp-price" id="tarif">
        <div>
          <p class="mk-kicker">Ordre de grandeur</p>
          <p class="lp-price-value"><?= e($price) ?></p>
          <?php if ($priceDetail !== ''): ?>
            <p><?= e($priceDetail) ?></p>
          <?php endif; ?>
          <?php if ($priceNote !== ''): ?>
            <p class="lp-price-note"><?= e($priceNote) ?></p>
          <?php endif; ?>
        </div>
        <?php if ($trade !== ''): ?>
          <a class="btn-ghost" href="<?= e(url((string) ($lp['trade_path'] ?? '/recherche'))) ?>">Page métier <?= e($trade) ?> →</a>
        <?php endif; ?>
      </section>
    <?php endif; ?>
    <?php if ($includes !== [] || $excludes !== []): ?>
      <section class="lp-split">
        <?php if ($includes !== []): ?>
          <div>
            <h2>Ce que vous achetez</h2>
            <ul class="mk-points">
              <?php foreach ($includes as $item): ?>
                <li><?= e((string) $item) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
        <?php if ($excludes !== []): ?>
          <div>
            <h2>Ce que ce n’est pas</h2>
            <ul class="mk-points lp-excludes">
              <?php foreach ($excludes as $item): ?>
                <li><?= e((string) $item) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
      </section>
    <?php endif; ?>
  <?php endif; ?>

  <?php if ($journal): ?>
    <section class="lp-journal" id="cout">
      <p class="mk-kicker">Pour cadrer le budget</p>
      <h2><?= e((string) $journal['title']) ?></h2>
      <?php if (!empty($journal['body_html'])): ?>
        <div class="article-body lp-journal-body"><?= (string) $journal['body_html'] ?></div>
      <?php elseif (!empty($journal['excerpt'])): ?>
        <p><?= e((string) $journal['excerpt']) ?></p>
      <?php endif; ?>
      <?php if (!empty($journal['href'])): ?>
        <a href="<?= e(url((string) $journal['href'])) ?>">Lire l’article dans le journal →</a>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <?php if ($isOfferer): ?>
    <section class="mk-cta">
      <div>
        <h2>Créer votre vitrine</h2>
        <p>Inscription gratuite. La première mission réalisée est offerte.</p>
      </div>
      <div class="mk-cta-actions">
        <a class="btn-orange" href="<?= e(url($primaryHref)) ?>"><?= e($primaryLabel) ?></a>
        <a class="btn-ghost" href="<?= e(url($secondaryHref)) ?>"><?= e($secondaryLabel) ?></a>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($others !== []): ?>
    <section class="lp-others">
      <h2>Autres besoins</h2>
      <div class="lp-other-grid">
        <?php foreach ($others as $o): ?>
          <a href="<?= e(url((string) $o['href'])) ?>">
            <span><?= e((string) $o['kicker']) ?></span>
            <strong><?= e((string) $o['need']) ?></strong>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <p class="lp-legal">
    <a href="<?= e(url('/mentions-legales')) ?>">Mentions légales</a>
    · <a href="<?= e(url('/cgu')) ?>">CGU</a>
    · <a href="<?= e(url('/confidentialite')) ?>">Confidentialité</a>
    · <a href="<?= e(url('/regles-ia')) ?>">Règles IA</a>
  </p>
</div>

<div class="lp-sticky" data-lp-sticky>
  <a class="btn-orange" href="<?= e(url($stickyHref)) ?>"><?= e($stickyLabel) ?></a>
</div>
