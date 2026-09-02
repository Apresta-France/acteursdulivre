<?php
$homeStats = $homeStats ?? [];
$homeMetiers = $homeMetiers ?? [];
$homeFeatured = $homeFeatured ?? [];
$homeEntry = $homeEntry ?? [];
$ways = $ways ?? [];
$homeMissions = $homeMissions ?? [];
$missionsBandStats = $missionsBandStats ?? [];
$garanties = $garanties ?? [];
$iaPoints = $iaPoints ?? [];
$mega = $mega ?? [];
$homeTemoins = $homeTemoins ?? [];
$journal = $journal ?? [];
$homeForum = $homeForum ?? [];
$homeFaq = $homeFaq ?? [];
$homeQuick = $homeQuick ?? [];
$query = (string) ($query ?? '');
?>
<div class="mk-page">
  <section class="mk-hero">
    <div>
      <p class="mk-kicker">La place de marché des métiers du livre</p>
      <h1>Un livre, ça se fait à plusieurs.</h1>
      <p class="mk-lead">Dix-huit métiers, de l'écriture au coaching : trouvez les bonnes personnes, comparez des prestations à prix affichés, ou publiez votre recherche.</p>
      <form class="mk-search" method="get" action="<?= e(url('/recherche')) ?>" role="search" data-live-search data-api="<?= e(url('/api/recherche')) ?>" autocomplete="off" toolname="search_home" tooldescription="Rechercher depuis l'accueil un prestataire, une prestation ou un besoin lié à un livre.">
        <label class="sr-only" for="home-search-q">Rechercher un prestataire ou une prestation</label>
        <input id="home-search-q" type="search" name="q" value="<?= e($query) ?>" placeholder="De quoi votre livre a-t-il besoin ?" aria-label="Rechercher un prestataire ou une prestation" data-live-input autocomplete="off" toolparamdescription="Mots-clés décrivant le besoin du livre.">
        <button class="btn-orange" type="submit">Chercher</button>
        <div class="search-suggest" data-live-panel hidden></div>
      </form>
      <div class="mk-chips">
        <?php foreach ($homeQuick as $q): ?>
          <a href="<?= e(url('/recherche') . '?q=' . rawurlencode((string) $q)) ?>"><?= e((string) $q) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="mk-hero-visual">
      <?php
        $heroImgs = is_array($homeHeroImgs ?? null) && count($homeHeroImgs) >= 3
          ? array_values($homeHeroImgs)
          : home_hero_photos();
        $heroSrcs = json_encode($heroImgs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
      ?>
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

  <?php if ($homeStats !== []): ?>
    <section class="mk-stats">
      <?php foreach ($homeStats as $s): ?>
        <a class="mk-stat" href="<?= e(url((string) ($s['href'] ?? '#'))) ?>">
          <strong><?= e((string) $s['v']) ?></strong>
          <span><?= e((string) $s['k']) ?></span>
        </a>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>

  <section class="mk-block">
    <div class="mk-head">
      <h2>Les métiers du livre</h2>
      <a href="<?= e(url('/prestataires')) ?>">Parcourir l'annuaire des prestataires →</a>
    </div>
    <div class="mk-trades">
      <?php foreach ($homeMetiers as $m): ?>
        <a class="mk-trade<?= !empty($m['empty']) ? ' is-empty' : '' ?>" href="<?= e(url((string) $m['href'])) ?>">
          <span class="mk-trade-ico" aria-hidden="true"><?= trade_icon((string) ($m['trade'] ?? ''), 18) ?></span>
          <span>
            <strong><?= e((string) $m['name']) ?></strong>
            <em><?= e((string) $m['countLabel']) ?></em>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="mk-block mk-wash-beige">
    <div class="mk-head">
      <div>
        <h2>Prestations mises en avant</h2>
        <p>Prix, délai et périmètre annoncés. Vous ouvrez un suivi à jalons : le règlement se fait hors plateforme.</p>
      </div>
      <a href="<?= e(url('/prestations')) ?>">Voir toutes les prestations →</a>
    </div>
    <?php if ($homeFeatured === []): ?>
      <p class="mk-empty">Aucune prestation publiée pour le moment.</p>
    <?php else: ?>
      <div class="mk-cards-3">
        <?php foreach ($homeFeatured as $s): ?>
          <a class="mk-card" href="<?= e(url((string) $s['href'])) ?>">
            <?= !empty($s['has_image'])
              ? '<img class="mk-card-media" src="' . e((string) $s['img']) . '" alt="' . e((string) ($s['title'] ?? '')) . '" width="400" height="168" loading="lazy" decoding="async">'
              : service_cover_html((string) ($s['cat'] ?? ''), 'mk-card-cover') ?>
            <div class="mk-card-body">
              <div class="mk-card-by"><?= avatar_html($s, 26) ?><span><?= e((string) ($s['by'] ?? '')) ?></span></div>
              <p><?= e((string) $s['title']) ?></p>
              <div class="mk-card-foot">
                <span><?= !empty($s['rating']) ? '★ ' . e((string) $s['rating']) : '' ?></span>
                <strong><?= e((string) ($s['price'] ?? '')) ?></strong>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <?php if ($homeEntry !== []): ?>
    <section class="mk-block">
      <div class="mk-head">
        <div>
          <h2>Formats d'entrée</h2>
          <p>Petits volumes, périmètre clair, prix affiché.</p>
        </div>
        <a href="<?= e(url('/prestations')) ?>">Voir les formats →</a>
      </div>
      <div class="mk-cards-3">
        <?php foreach ($homeEntry as $s): ?>
          <a class="mk-card" href="<?= e(url((string) $s['href'])) ?>">
            <?= !empty($s['has_image'])
              ? '<img class="mk-card-media" src="' . e((string) $s['img']) . '" alt="' . e((string) ($s['title'] ?? '')) . '" width="400" height="168" loading="lazy" decoding="async">'
              : service_cover_html((string) ($s['cat'] ?? ''), 'mk-card-cover') ?>
            <div class="mk-card-body">
              <div class="mk-card-by"><?= avatar_html($s, 26) ?><span><?= e((string) ($s['by'] ?? '')) ?></span></div>
              <?php if (!empty($s['kind_label'] ?? $s['cat'])): ?>
                <span class="mk-tag"><?= e((string) ($s['kind_label'] ?? $s['cat'])) ?></span>
              <?php endif; ?>
              <p><?= e((string) $s['title']) ?></p>
              <div class="mk-card-meta"><?= e(trim((string) ($s['delay'] ?? '') . (!empty($s['specialty']) ? ' · ' . $s['specialty'] : ''))) ?></div>
              <div class="mk-card-foot">
                <span><?= !empty($s['rating']) ? '★ ' . e((string) $s['rating']) : '' ?></span>
                <strong><?= e((string) ($s['price'] ?? '')) ?></strong>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <section class="mk-block mk-wash-peach">
    <h2>Trois façons de travailler ensemble</h2>
    <p class="mk-sub">Choisissez celle qui correspond à votre projet.</p>
    <div class="mk-cards-3">
      <?php foreach ($ways as $w): ?>
        <div class="mk-way">
          <div class="mk-kicker"><?= e((string) $w['kicker']) ?></div>
          <h3><?= e((string) $w['title']) ?></h3>
          <p><?= e((string) $w['body']) ?></p>
          <a href="<?= e(url((string) ($w['href'] ?? '/recherche'))) ?>"><?= e((string) $w['cta']) ?> →</a>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="mk-band">
    <div>
      <p class="mk-kicker mk-kicker-light"><?= e((string) ($openMissionsLabel ?? 'Appels d\'offres')) ?></p>
      <h2>Des projets cherchent leurs prestataires, en ce moment.</h2>
      <p>Auteurs, éditeurs et collectifs publient leur besoin ; vous répondez avec votre prix et votre délai. Candidater est gratuit et illimité.</p>
      <?php foreach ($missionsBandStats as $s): ?>
        <div class="mk-band-stat"><strong><?= e((string) $s['v']) ?></strong><span><?= e((string) $s['k']) ?></span></div>
      <?php endforeach; ?>
      <a class="btn-orange" href="<?= e(url('/missions')) ?>"><?= e((string) ($openMissionsCta ?? 'Voir les recherches')) ?></a>
    </div>
    <div>
      <?php if ($homeMissions === []): ?>
        <p class="mk-empty-light">Aucune recherche ouverte pour le moment.</p>
      <?php else: ?>
        <?php foreach ($homeMissions as $m): ?>
          <a class="mk-mission" href="<?= e(url((string) $m['href'])) ?>">
            <div>
              <?php if (!empty($m['cat'])): ?><span class="mk-tag-light"><?= e((string) $m['cat']) ?></span><?php endif; ?>
              <strong><?= e((string) $m['title']) ?></strong>
              <span><?= e(trim((string) ($m['by'] ?? '') . (!empty($m['volume']) ? ' · ' . $m['volume'] : '') . (!empty($m['when']) ? ' · publié ' . $m['when'] : ''))) ?></span>
            </div>
            <div>
              <em><?= e((string) ($m['budget'] ?? '')) ?></em>
              <span>livraison <?= e((string) ($m['deadline'] ?? 'à convenir')) ?></span>
            </div>
            <div>
              <span><?= (int) ($m['applicants'] ?? 0) ?> candidature<?= (int) ($m['applicants'] ?? 0) > 1 ? 's' : '' ?></span>
              <span class="mk-go">Postuler →</span>
            </div>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
      <a class="mk-dashed" href="<?= e(url('/espace/publier')) ?>">Publier une recherche</a>
    </div>
  </section>

  <section class="mk-block mk-cards-3 mk-wash-cool">
    <?php foreach ($garanties as $g): ?>
      <div>
        <div class="mk-kicker"><?= e((string) $g['kicker']) ?></div>
        <h3><?= e((string) $g['title']) ?></h3>
        <p><?= e((string) $g['body']) ?></p>
      </div>
    <?php endforeach; ?>
  </section>

  <section class="mk-ia">
    <div class="mk-ia-icon" aria-hidden="true">✕</div>
    <div>
      <div class="mk-kicker">Engagement de la plateforme</div>
      <h2>Ici, l'intelligence artificielle générative est interdite.</h2>
      <p>Aucun texte, aucune illustration, aucune voix livrée sur cette plateforme ne peut être produit par une IA générative. Les prestataires s'y engagent à l'inscription ; les manuscrits confiés ne sont jamais utilisés pour entraîner un modèle. Ce moratoire vise les missions entre acteurs du livre — pas la fabrication de ce site.</p>
    </div>
    <div class="mk-ia-box">
      <?php foreach ($iaPoints as $p): ?>
        <div><span>✕</span><?= e((string) $p) ?></div>
      <?php endforeach; ?>
      <a href="<?= e(url('/regles-ia')) ?>">Lire nos règles IA →</a>
    </div>
  </section>

  <section class="mk-block mk-wash-cool mk-mega">
    <?php foreach ($mega as $m): ?>
      <div>
        <div class="mk-kicker"><?= e((string) $m['group']) ?></div>
        <?php foreach ($m['items'] as $i): ?>
          <a href="<?= e(url((string) ($i['href'] ?? '/recherche'))) ?>"><?= e((string) ($i['label'] ?? $i)) ?></a>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </section>

  <?php if ($homeTemoins !== []): ?>
    <?php $temoinsCount = count($homeTemoins); ?>
    <section class="mk-block mk-wash-beige mk-quotes-block" data-count="<?= $temoinsCount ?>">
      <div class="mk-quotes-head">
        <div class="mk-kicker">Témoignages</div>
        <h2>Ils ont fabriqué leur livre ici</h2>
        <p>Avis publics, déposés après une mission livrée et notée.</p>
      </div>
      <div class="mk-quotes">
        <?php foreach ($homeTemoins as $t): ?>
          <?php
            $noteLabel = trim((string) ($t['note'] ?? ''));
            $filled = $noteLabel !== '' ? (int) round((float) str_replace(',', '.', $noteLabel)) : 0;
            $filled = max(0, min(5, $filled));
            $avatarSize = $temoinsCount === 1 ? 44 : 38;
          ?>
          <figure class="mk-quote">
            <span class="mk-quote-mark" aria-hidden="true"><svg width="48" height="36" viewBox="0 0 48 36" fill="currentColor"><path d="M0 20.4C0 10.2 6 3.4 16.4 0l2.8 5.5C13.3 8.3 9.7 12.5 9.7 18.2c0 1.1.2 2.1.5 2.9h9.1V36H0V20.4zm22.7 0C22.7 10.2 28.7 3.4 39.1 0l2.8 5.5c-5.9 2.8-9.5 7-9.5 12.7 0 1.1.2 2.1.5 2.9h9.2V36H22.7V20.4z"/></svg></span>
            <div class="mk-stars" role="img" aria-label="<?= $noteLabel !== '' ? 'Note ' . e($noteLabel) . ' sur 5' : 'Avis' ?>">
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <span<?= $i <= $filled ? ' class="is-on"' : '' ?> aria-hidden="true">★</span>
              <?php endfor; ?>
            </div>
            <blockquote>
              <p><?= e((string) $t['txt']) ?></p>
            </blockquote>
            <figcaption class="mk-card-by">
              <?= avatar_html($t, $avatarSize) ?>
              <span>
                <strong><?= e((string) $t['who']) ?></strong>
                <?php if (!empty($t['role'])): ?><em><?= e((string) $t['role']) ?></em><?php endif; ?>
              </span>
            </figcaption>
          </figure>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <section class="mk-block">
    <div class="mk-head">
      <h2>Le journal</h2>
      <a href="<?= e(url('/journal')) ?>">Tous les articles →</a>
    </div>
    <?php if ($journal === []): ?>
      <p class="mk-empty">Aucun article publié pour le moment.</p>
    <?php else: ?>
      <div class="mk-cards-3">
        <?php foreach ($journal as $a): ?>
          <a class="mk-journal" href="<?= e(url((string) $a['href'])) ?>">
            <?php if (!empty($a['img'])): ?>
              <img class="mk-card-media" src="<?= e((string) $a['img']) ?>" alt="<?= e((string) ($a['image_alt'] ?? $a['title'] ?? '')) ?>" width="400" height="168" loading="lazy" decoding="async">
            <?php endif; ?>
            <div class="mk-kicker"><?= e((string) ($a['cat'] ?? '')) ?> · <?= e((string) ($a['read'] ?? '')) ?></div>
            <strong><?= e((string) $a['title']) ?></strong>
            <span><?= e((string) ($a['chapo'] ?? '')) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="mk-block mk-wash-cool">
    <div class="mk-head">
      <div>
        <h2>Derniers échanges du forum</h2>
        <p>Tarifs, contrats, papier, délais : les réponses viennent de gens qui font le métier.</p>
      </div>
      <a href="<?= e(url('/forum')) ?>">Toutes les discussions →</a>
    </div>
    <?php if ($homeForum === []): ?>
      <p class="mk-empty">Aucune discussion pour le moment. <a href="<?= e(url('/forum')) ?>">Ouvrir le forum</a></p>
    <?php else: ?>
      <div class="mk-forum">
        <?php foreach ($homeForum as $t): ?>
          <?php
            $replies = (int) ($t['reply_count'] ?? 0);
            $lastAuthor = is_array($t['last_author'] ?? null) ? $t['last_author'] : ($t['author'] ?? []);
            $who = (string) ($lastAuthor['name'] ?? $t['last_by'] ?? '');
            $when = (string) ($t['last_when'] ?? $t['when'] ?? '');
            $verb = !empty($t['last_is_op']) || $replies === 0 ? 'a ouvert' : 'a répondu';
          ?>
          <a class="mk-forum-row" href="<?= e(url((string) ($t['href'] ?? '/forum'))) ?>">
            <?= avatar_html($lastAuthor, 42, 'mk-forum-avatar') ?>
            <div class="mk-forum-main">
              <div class="mk-forum-line">
                <span class="mk-tag"><?= e((string) ($t['category_short'] ?? '')) ?></span>
                <?php if (!empty($t['badge'])): ?>
                  <span class="forum-badge forum-badge-<?= e(slugify((string) $t['badge'])) ?>"><?= e((string) $t['badge']) ?></span>
                <?php endif; ?>
              </div>
              <strong><?= e((string) ($t['title'] ?? '')) ?></strong>
              <?php if (!empty($t['excerpt'])): ?>
                <p><?= e((string) $t['excerpt']) ?></p>
              <?php endif; ?>
              <span class="mk-forum-meta"><?= e(trim($who . ($who !== '' ? ' ' . $verb : '') . ($when !== '' ? ' · ' . $when : ''))) ?></span>
            </div>
            <div class="mk-forum-count">
              <strong><?= $replies ?></strong>
              <span><?= $replies > 1 ? 'réponses' : 'réponse' ?></span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="mk-faq">
    <div>
      <h2>Questions fréquentes</h2>
      <p>Commission, clients, règlement : les réponses au même endroit.</p>
      <a href="<?= e(url('/questions')) ?>">Toutes les questions →</a>
    </div>
    <div class="mk-faq-list">
      <?php foreach ($homeFaq as $f): ?>
        <div>
          <button type="button" data-accordion aria-expanded="<?= e((string) ($f['expanded'] ?? 'false')) ?>" class="faq-q">
            <?= e((string) $f['q']) ?><span data-accordion-sign><?= e((string) ($f['sign'] ?? '+')) ?></span>
          </button>
          <div <?= !empty($f['open']) ? '' : 'hidden' ?> class="mk-faq-a"><?= e((string) $f['a']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
</div>
