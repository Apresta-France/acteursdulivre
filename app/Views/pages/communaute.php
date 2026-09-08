<?php
$logged = !empty($logged);
$forumStats = $forumStats ?? ['topics' => 0, 'posts' => 0, 'week' => 0];
$homeForum = $homeForum ?? [];
$publishers = $publishers ?? [];
$publisherCount = (int) ($publisherCount ?? 0);
$agenda = $agenda ?? [];
$salonCount = (int) ($salonCount ?? count($agenda));
?>
<div class="mk-page co-page">
  <section class="forum-hero co-hero">
    <div class="forum-hero-copy">
      <div class="forum-kicker">Communauté</div>
      <h1>Se retrouver hors des missions.</h1>
      <p class="forum-lead">Le forum, l’annuaire des maisons d’édition et l’agenda des salons : trois portes pour parler métier, trouver un éditeur, ou savoir où poser un stand.</p>
    </div>
    <div class="forum-hero-actions">
      <div class="forum-hero-stats">
        <a class="forum-stat" href="<?= e(url('/forum')) ?>">
          <strong><?= e(format_int((int) ($forumStats['topics'] ?? 0))) ?></strong>
          <span>discussions</span>
        </a>
        <a class="forum-stat" href="<?= e(url('/maisons-edition')) ?>">
          <strong><?= e(format_int($publisherCount)) ?></strong>
          <span>maisons</span>
        </a>
        <a class="forum-stat" href="<?= e(url('/salons')) ?>">
          <strong><?= e(format_int($salonCount)) ?></strong>
          <span>salons</span>
        </a>
      </div>
    </div>
  </section>

  <section class="mk-block">
    <div class="co-doors">
      <a class="co-door" href="<?= e(url('/forum')) ?>">
        <span class="co-door-ico" aria-hidden="true"><?= icon('chat', 22) ?></span>
        <h2>Forum</h2>
        <p>Tarifs, contrats, papier, délais : les réponses viennent de gens qui font le métier — pas d’une machine.</p>
        <span class="co-door-cta"><?= $logged ? 'Ouvrir le forum' : 'Lire les discussions' ?> →</span>
      </a>
      <a class="co-door" href="<?= e(url('/maisons-edition')) ?>">
        <span class="co-door-ico" aria-hidden="true"><?= icon('store', 22) ?></span>
        <h2>Annuaire</h2>
        <p>Maisons d’édition en France et en Europe, par ville et genre. Les coordonnées sont réservées aux membres.</p>
        <span class="co-door-cta">Parcourir l’annuaire →</span>
      </a>
      <a class="co-door" href="<?= e(url('/salons')) ?>">
        <span class="co-door-ico" aria-hidden="true"><?= icon('calendar', 22) ?></span>
        <h2>Agenda</h2>
        <p>Dates, villes, type de manifestation. Pour préparer un stand, une dédicace, ou simplement y aller.</p>
        <span class="co-door-cta">Voir les prochains salons →</span>
      </a>
    </div>
  </section>

  <section class="mk-block mk-wash-cool">
    <div class="co-split">
      <div>
        <div class="mk-head">
          <div>
            <h2>Derniers échanges</h2>
            <p>Ce qui s’écrit en ce moment sur le forum.</p>
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
                  </div>
                  <strong><?= e((string) ($t['title'] ?? '')) ?></strong>
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
      </div>

      <div>
        <div class="mk-head">
          <div>
            <h2>Maisons à parcourir</h2>
            <p>Un extrait de l’annuaire, sans filtre.</p>
          </div>
          <a href="<?= e(url('/maisons-edition')) ?>">Tout l’annuaire →</a>
        </div>
        <?php if ($publishers === []): ?>
          <p class="mk-empty">Aucune maison publiée pour le moment. <a href="<?= e(url('/maisons-edition/ajouter')) ?>">Ajouter une maison</a></p>
        <?php else: ?>
          <div class="co-me-list">
            <?php foreach ($publishers as $p): ?>
              <a class="co-me-row" href="<?= e(url((string) ($p['href'] ?? '/maisons-edition'))) ?>">
                <span class="co-me-logo">
                  <?php if (!empty($p['logo_src'])): ?>
                    <img src="<?= e((string) $p['logo_src']) ?>" alt="" width="44" height="44" loading="lazy" decoding="async">
                  <?php else: ?>
                    <span class="me-mono" aria-hidden="true"><?= e((string) ($p['initials'] ?? '')) ?></span>
                  <?php endif; ?>
                </span>
                <span class="co-me-body">
                  <strong><?= e((string) ($p['name'] ?? '')) ?></strong>
                  <em><?= e(trim((string) ($p['typology_label'] ?? '') . (!empty($p['location_label']) ? ' · ' . $p['location_label'] : ''))) ?></em>
                </span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="mk-block" id="agenda">
    <div class="mk-head">
      <div>
        <h2>Prochains salons</h2>
        <p>France et Europe, sept. 2026 – sept. 2027.</p>
      </div>
      <a href="<?= e(url('/salons')) ?>">Tout l’agenda →</a>
    </div>
    <div class="co-agenda-layout">
      <div class="co-agenda">
        <?php foreach ($agenda as $event): ?>
          <a class="co-event" href="<?= e(url((string) ($event['href'] ?? '/salons'))) ?>">
            <time class="co-event-date" datetime="<?= e((string) ($event['iso'] ?? '')) ?>">
              <strong><?= e((string) ($event['day'] ?? '')) ?></strong>
              <span><?= e((string) ($event['month'] ?? '')) ?></span>
            </time>
            <div class="co-event-main">
              <div class="co-event-tags">
                <span class="mk-tag"><?= e((string) ($event['kind'] ?? '')) ?></span>
                <?php if (!empty($event['when'])): ?>
                  <span class="co-event-when"><?= e((string) $event['when']) ?></span>
                <?php endif; ?>
              </div>
              <h3><?= e((string) ($event['name'] ?? $event['title'] ?? '')) ?></h3>
              <p><?= e((string) ($event['place'] ?? '')) ?></p>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
      <aside class="co-agenda-aside">
        <div class="mk-kicker">Agenda</div>
        <h3>Parcourir les <?= e(format_int($salonCount)) ?> salons</h3>
        <p>Filtres par pays, région et catégorie. Un salon manque : <a href="<?= e(url('/salons/ajouter')) ?>">proposez-le</a>.</p>
        <a class="btn-navy" href="<?= e(url('/salons')) ?>">Ouvrir l’agenda</a>
        <div class="co-agenda-aside-sep"></div>
        <h3>Premier stand</h3>
        <p>Table, stock, droit de table : ce qu’il faut prévoir avant d’y aller.</p>
        <a href="<?= e(url('/journal/salon-du-livre-budget-premier-stand')) ?>">Lire le budget d’un premier stand →</a>
      </aside>
    </div>
  </section>
</div>
