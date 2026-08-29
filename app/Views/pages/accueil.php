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
$aideFaq = $aideFaq ?? [];
$homeQuick = $homeQuick ?? [];
$query = (string) ($query ?? '');
?>
<div class="mk-page">
  <section class="mk-hero">
    <div>
      <p class="mk-kicker">La place de marché des métiers du livre</p>
      <h1>Un livre, ça se fait à plusieurs.</h1>
      <p class="mk-lead">Dix-huit métiers, de l'écriture au salon : trouvez les bonnes personnes, comparez des prestations à prix affichés, ou publiez votre recherche.</p>
      <form class="mk-search" method="get" action="<?= e(url('/recherche')) ?>">
        <input name="q" value="<?= e($query) ?>" placeholder="De quoi votre livre a-t-il besoin ?" aria-label="Recherche">
        <button class="btn-orange" type="submit">Chercher</button>
      </form>
      <div class="mk-chips">
        <?php foreach ($homeQuick as $q): ?>
          <a href="<?= e(url('/recherche?q=' . rawurlencode((string) $q))) ?>"><?= e((string) $q) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="mk-mosaic" aria-hidden="true">
      <div class="mk-mosaic-a">
        <img src="<?= e((string) ($homeImg1 ?? photo(0))) ?>" alt="" width="440" height="312" fetchpriority="high" decoding="async">
      </div>
      <div class="mk-mosaic-b">
        <img src="<?= e((string) ($homeImg2 ?? photo(5))) ?>" alt="" width="214" height="150" loading="lazy" decoding="async">
      </div>
      <div class="mk-mosaic-c">
        <img src="<?= e((string) ($homeImg3 ?? photo(4))) ?>" alt="" width="214" height="150" loading="lazy" decoding="async">
      </div>
    </div>
  </section>

  <?php if ($homeStats !== []): ?>
    <section class="mk-stats">
      <?php foreach ($homeStats as $s): ?>
        <div class="mk-stat">
          <strong><?= e((string) $s['v']) ?></strong>
          <span><?= e((string) $s['k']) ?></span>
        </div>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>

  <section class="mk-block">
    <div class="mk-head">
      <h2>Les métiers du livre</h2>
      <a href="<?= e(url('/prestataires')) ?>">Parcourir l'annuaire →</a>
    </div>
    <div class="mk-trades">
      <?php foreach ($homeMetiers as $m): ?>
        <a class="mk-trade" href="<?= e(url((string) $m['href'])) ?>">
          <span class="mk-num"><?= e((string) $m['num']) ?></span>
          <span>
            <strong><?= e((string) $m['name']) ?></strong>
            <em><?= e((string) $m['countLabel']) ?></em>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="mk-block mk-fog">
    <div class="mk-head">
      <div>
        <h2>Prestations mises en avant</h2>
        <p>Prix, délai et périmètre annoncés. Vous ouvrez un suivi à jalons : le règlement se fait hors plateforme.</p>
      </div>
      <a href="<?= e(url('/prestations')) ?>">Tout voir →</a>
    </div>
    <?php if ($homeFeatured === []): ?>
      <p class="mk-empty">Aucune prestation publiée pour le moment.</p>
    <?php else: ?>
      <div class="mk-cards-3">
        <?php foreach ($homeFeatured as $s): ?>
          <a class="mk-card" href="<?= e(url((string) $s['href'])) ?>">
            <?= !empty($s['has_image'])
              ? '<div class="mk-card-media" style="background-image:url(\'' . e((string) $s['img']) . '\')"></div>'
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
    <section class="mk-block mk-fog">
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
              ? '<div class="mk-card-media" style="background-image:url(\'' . e((string) $s['img']) . '\')"></div>'
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

  <section class="mk-block">
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
      <a class="mk-dashed" href="<?= e(url('/missions')) ?>">Voir toutes les recherches</a>
    </div>
  </section>

  <section class="mk-block mk-cards-3">
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
      <p>Aucun texte, aucune illustration, aucune voix livrée sur cette plateforme ne peut être produit par une IA générative. Les prestataires s'y engagent à l'inscription ; les manuscrits confiés ne sont jamais utilisés pour entraîner un modèle.</p>
    </div>
    <div class="mk-ia-box">
      <?php foreach ($iaPoints as $p): ?>
        <div><span>✕</span><?= e((string) $p) ?></div>
      <?php endforeach; ?>
      <a href="<?= e(url('/confiance')) ?>">Lire la charte qualité →</a>
    </div>
  </section>

  <section class="mk-block mk-fog mk-mega">
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
    <section class="mk-block">
      <h2>Ils ont fabriqué leur livre ici</h2>
      <div class="mk-cards-3">
        <?php foreach ($homeTemoins as $t): ?>
          <div class="mk-quote">
            <div class="mk-kicker"><?= !empty($t['note']) ? '★ ' . e((string) $t['note']) : 'Avis' ?></div>
            <p><?= e((string) $t['txt']) ?></p>
            <div class="mk-card-by"><?= avatar_html($t, 34) ?>
              <span>
                <strong><?= e((string) $t['who']) ?></strong>
                <?php if (!empty($t['role'])): ?><em><?= e((string) $t['role']) ?></em><?php endif; ?>
              </span>
            </div>
          </div>
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
              <div class="mk-card-media" style="background-image:url('<?= e((string) $a['img']) ?>')"></div>
            <?php endif; ?>
            <div class="mk-kicker"><?= e((string) ($a['cat'] ?? '')) ?> · <?= e((string) ($a['read'] ?? '')) ?></div>
            <strong><?= e((string) $a['title']) ?></strong>
            <span><?= e((string) ($a['chapo'] ?? '')) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="mk-cta">
    <div>
      <h2>Vous exercez un métier du livre ?</h2>
      <p>Créez votre vitrine, fixez vos tarifs, recevez des demandes qualifiées. Le client vous règle hors plateforme. Aucun abonnement : la première mission est offerte, puis 8 % de commission facturés au prestataire lorsqu'il confirme et note.</p>
    </div>
    <div class="mk-cta-actions">
      <a class="btn-orange" href="<?= e(url('/inscription')) ?>">Proposer mes services</a>
      <a class="btn-ghost" href="<?= e(url('/tarifs')) ?>">Voir les tarifs</a>
    </div>
  </section>

  <section class="mk-faq">
    <div>
      <h2>Questions fréquentes</h2>
      <p>Tout le détail dans le centre d'aide.</p>
      <a href="<?= e(url('/aide')) ?>">Centre d'aide →</a>
    </div>
    <div class="mk-faq-list">
      <?php foreach ($aideFaq as $f): ?>
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
