<?php

use Adl\Models\Publisher;

$p = $publisher ?? null;
if (!$p) {
    not_found();
}
$viewer = \Adl\Core\Auth::user();
$isOwner = !empty($isOwner);
$isAdmin = !empty($isAdmin);
$revealed = !empty($revealed);
$pendingClaim = $pendingClaim ?? null;
$related = $related ?? [];
$contactLeft = (int) ($contactLeft ?? 0);
$isHidden = !Publisher::isPublic($p);
$description = trim((string) ($p['description'] ?? ''));
$submissions = trim((string) ($p['submissions_note'] ?? ''));
$facts = [];
if ($p['country'] !== '') {
    $facts[] = ['Pays', $p['country'] . ($p['region'] !== '' ? ' (' . $p['region'] . ')' : ''), $p['country_href']];
}
if ($p['city'] !== '') {
    $facts[] = ['Ville du siège', $p['city'], $p['city_href']];
}
if ($p['founded_label'] !== '') {
    $facts[] = ['Fondation', $p['founded_label'], null];
}
if ($p['group_label'] !== '') {
    $facts[] = [$p['is_independent'] ? 'Actionnariat' : 'Groupe', $p['group_label'], null];
}
$facts[] = ['Taille', $p['size_label'], '/maisons-edition?taille=' . $p['size_key']];
$facts[] = ['Ligne éditoriale', $p['typology_label'], '/maisons-edition?typologie=' . $p['typology_key']];
$contactHref = $p['href'] . '/contact';
?>
<div class="profile-page me-fiche">
  <nav class="search-crumb me-fiche-crumb" aria-label="Fil d'Ariane">
    <a href="<?= e(url('/')) ?>">Accueil</a>
    <span aria-hidden="true"> · </span>
    <a href="<?= e(url('/communaute')) ?>">Communauté</a>
    <span aria-hidden="true"> · </span>
    <a href="<?= e(url('/maisons-edition')) ?>">Maisons d'édition</a>
    <?php if ($p['country_slug'] !== ''): ?>
      <span aria-hidden="true"> · </span>
      <a href="<?= e(url($p['country_href'])) ?>"><?= e($p['country']) ?></a>
    <?php endif; ?>
    <span aria-hidden="true"> · </span>
    <span><?= e($p['name']) ?></span>
  </nav>

  <?php if ($isHidden): ?>
    <div class="flash flash-warn me-fiche-flash"><?= ($p['status'] ?? '') === \Adl\Models\Publisher::STATUS_PENDING
        ? 'Cette fiche est en attente de validation par l\'équipe : seuls son gestionnaire et l\'administration peuvent la voir. Elle sera publiée dans l\'annuaire dès validation.'
        : 'Cette fiche est masquée : seuls son gestionnaire et l\'administration peuvent la voir.' ?></div>
  <?php endif; ?>

  <div class="profile-hero me-hero">
    <div class="me-hero-logo">
      <?php if ($p['logo_src'] !== ''): ?>
        <img src="<?= e($p['logo_src']) ?>" alt="Logo <?= e($p['name']) ?>" width="104" height="104">
      <?php else: ?>
        <span class="me-mono" aria-hidden="true"><?= e($p['initials']) ?></span>
      <?php endif; ?>
    </div>
    <div class="profile-hero-main">
      <div class="profile-hero-line">
        <div class="profile-hero-name"><h1><?= e($p['name']) ?></h1></div>
        <span class="profile-badge">Maison d'édition</span>
        <?php if (($p['status'] ?? '') === \Adl\Models\Publisher::STATUS_PENDING): ?>
          <span class="profile-badge me-badge-pending"><?= icon('clock', 13) ?> En attente de validation</span>
        <?php elseif (($p['status'] ?? '') !== 'published'): ?>
          <span class="profile-badge me-badge-pending">Fiche masquée</span>
        <?php elseif ($p['is_claimed']): ?>
          <span class="profile-badge me-badge-verified"><?= icon('check-circle', 13) ?> Fiche gérée par la maison</span>
        <?php endif; ?>
        <?php if ($p['is_independent']): ?>
          <span class="profile-badge me-badge-indie">Indépendant</span>
        <?php endif; ?>
      </div>
      <div class="profile-hero-sub">
        <?= e($p['typology_label']) ?> · <?= e($p['size_label']) ?><?= $p['location_label'] !== '' ? ' · ' . e($p['location_label']) : '' ?><?= $p['founded_year'] ? ' · fondée en ' . (int) $p['founded_year'] : '' ?>
      </div>
      <?php if ($description !== ''): ?>
        <p class="auteur-hero-bio"><?= e($description) ?></p>
      <?php endif; ?>
      <?php if ($p['genres'] !== []): ?>
        <div class="chip-row">
          <?php foreach (array_slice($p['genre_links'], 0, 10) as $g): ?>
            <a class="chip-static me-genre-link" href="<?= e(url($g['href'])) ?>"><?= e($g['label']) ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <div class="profile-hero-actions">
      <?php if ($isOwner): ?>
        <p class="profile-avail-note"><?= ($p['status'] ?? '') === \Adl\Models\Publisher::STATUS_PENDING ? 'Prévisualisation : cette fiche n\'est visible que par vous pour l\'instant.' : 'Vous gérez cette fiche.' ?></p>
        <a class="btn-orange" href="<?= e(url('/espace/maison-edition')) ?>">Modifier ma fiche</a>
      <?php elseif (!$viewer): ?>
        <p class="profile-avail-note">Coordonnées et prise de contact réservées aux membres.</p>
        <a class="btn-orange" href="<?= e(url('/connexion?next=' . rawurlencode($p['href']))) ?>">Se connecter pour contacter</a>
        <a class="btn-ghost-light" href="<?= e(url('/inscription')) ?>">Créer un compte gratuit</a>
      <?php else: ?>
        <?php if ($p['is_claimed'] && !empty($p['owner_user_id'])): ?>
          <form method="post" action="<?= e(url('/espace/messages')) ?>">
            <?= csrf_field() ?>
            <?= form_guard_fields('message') ?>
            <input type="hidden" name="avec" value="<?= (int) $p['owner_user_id'] ?>">
            <input type="hidden" name="sujet" value="<?= e('Contact via la fiche ' . $p['name']) ?>">
            <button class="btn-orange" type="submit">Écrire à la maison</button>
          </form>
        <?php endif; ?>
        <?php if ($revealed): ?>
          <a class="btn-navy me-coord-btn is-done" href="#contact">Coordonnées affichées</a>
        <?php elseif ($p['has_contact']): ?>
          <form method="post" action="<?= e(url($contactHref)) ?>">
            <?= csrf_field() ?>
            <?= form_guard_fields('publisher-contact') ?>
            <button class="btn-orange" type="submit"<?= $contactLeft <= 0 ? ' disabled title="Plafond quotidien atteint"' : '' ?>>Voir les coordonnées</button>
          </form>
          <p class="profile-avail-note"><?= $contactLeft > 0 ? 'Encore ' . $contactLeft . ' ' . ($contactLeft > 1 ? 'fiches de contact' : 'fiche de contact') . ' aujourd\'hui.' : 'Plafond quotidien atteint : revenez demain.' ?></p>
        <?php else: ?>
          <p class="profile-avail-note">Aucune coordonnée publique n'a été trouvée pour cette maison.</p>
        <?php endif; ?>
      <?php endif; ?>
      <?php
        $shareUrl = $meta['url'] ?? \Adl\Data\Share::current();
        $shareTitle = $meta['title'] ?? ($p['name'] . ' — acteursdulivre.fr');
        $shareText = $meta['description'] ?? $description;
        $shareLabel = 'Partager';
        $shareCompact = true;
        $shareNative = true;
        require ADL_ROOT . '/app/Views/partials/share.php';
      ?>
    </div>
  </div>

  <div class="me-fiche-layout">
    <div class="me-fiche-main">
      <h2 id="presentation">Présentation</h2>
      <?php if ($description !== ''): ?>
        <div class="profile-text"><?= nl2br(e($description)) ?></div>
      <?php else: ?>
        <p class="profile-text"><?= e($p['name']) ?> est une maison d'édition <?= e(mb_strtolower($p['typology_label'])) ?><?= $p['location_label'] !== '' ? ' installée à ' . e($p['location_label']) : '' ?>. La présentation détaillée sera complétée par la maison lorsqu'elle aura revendiqué sa fiche.</p>
      <?php endif; ?>

      <?php if ($p['genres'] !== []): ?>
        <h2 id="genres">Genres et domaines publiés</h2>
        <div class="chip-row">
          <?php foreach ($p['genre_links'] as $g): ?>
            <a class="chip" href="<?= e(url($g['href'])) ?>"><?= e($g['label']) ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <h2 id="contact">Coordonnées et contact</h2>
      <?php if ($revealed && ($isOwner || $isAdmin || $viewer)): ?>
        <?php if ($p['has_contact']): ?>
          <dl class="me-contact">
            <?php if ($p['website'] !== ''): ?>
              <div><dt>Site web</dt><dd><a href="<?= e($p['website']) ?>" target="_blank" rel="noopener noreferrer nofollow"><?= e($p['website_host']) ?></a></dd></div>
            <?php endif; ?>
            <?php if ($p['contact_email'] !== ''): ?>
              <div><dt>E-mail</dt><dd><a href="mailto:<?= e($p['contact_email']) ?>"><?= e($p['contact_email']) ?></a></dd></div>
            <?php endif; ?>
            <?php if (($p['contact_phone'] ?? '') !== ''): ?>
              <div><dt>Téléphone</dt><dd><?= e((string) $p['contact_phone']) ?></dd></div>
            <?php endif; ?>
            <?php if ($p['contact_address'] !== ''): ?>
              <div><dt>Adresse</dt><dd><?= e($p['contact_address']) ?></dd></div>
            <?php endif; ?>
          </dl>
          <?php if (!$p['is_claimed']): ?>
            <p class="me-contact-note">Coordonnées génériques relevées sur le site officiel ou les mentions légales de la maison ; elles peuvent avoir changé. Une maison peut <a href="<?= e(url($p['href'] . '/revendiquer')) ?>">revendiquer sa fiche</a> pour les tenir à jour.</p>
          <?php endif; ?>
        <?php else: ?>
          <p class="profile-text">Aucune coordonnée publique fiable n'a été trouvée pour cette maison.</p>
        <?php endif; ?>
      <?php elseif ($viewer): ?>
        <div class="me-gate">
          <div>
            <strong>Coordonnées réservées aux membres</strong>
            <p>Site, adresse et contact éditorial. Vous pouvez consulter les coordonnées de <?= Publisher::CONTACT_DAILY_LIMIT ?> maisons par jour<?= $contactLeft > 0 ? ' — il vous en reste ' . $contactLeft . ' aujourd\'hui' : '' ?>.</p>
          </div>
          <?php if ($p['has_contact'] && $contactLeft > 0): ?>
            <form method="post" action="<?= e(url($contactHref)) ?>">
              <?= csrf_field() ?>
              <?= form_guard_fields('publisher-contact') ?>
              <button class="btn-orange" type="submit">Voir les coordonnées</button>
            </form>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="me-gate">
          <div>
            <strong>Réservé aux membres d'acteursdulivre.fr</strong>
            <p>Créez un compte gratuit pour consulter les coordonnées des maisons d'édition et leur écrire. Cette règle protège les éditeurs des envois automatisés et garantit des échanges entre vrais professionnels du livre.</p>
            <div class="me-gate-actions">
              <a class="btn-navy" href="<?= e(url('/inscription')) ?>">Créer un compte</a>
              <a class="btn-ghost" href="<?= e(url('/connexion?next=' . rawurlencode($p['href']))) ?>">Se connecter</a>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($viewer && !$isOwner && !$p['is_claimed']): ?>
        <div class="me-claim-box">
          <div>
            <strong>Vous travaillez pour <?= e($p['name']) ?> ?</strong>
            <p>Revendiquez cette fiche pour la compléter, ajouter votre logo, préciser votre politique de manuscrits et recevoir les messages des membres.</p>
          </div>
          <?php if ($pendingClaim): ?>
            <span class="status-pill status-pending">Demande en cours d'examen</span>
          <?php else: ?>
            <a class="btn-ghost" href="<?= e(url($p['href'] . '/revendiquer')) ?>">Revendiquer la fiche</a>
          <?php endif; ?>
        </div>
      <?php elseif (!$viewer && !$p['is_claimed']): ?>
        <p class="me-contact-note">Vous représentez cette maison ? <a href="<?= e(url('/inscription')) ?>">Créez un compte</a> puis revendiquez la fiche pour en prendre la main.</p>
      <?php endif; ?>

      <?php if ($related !== []): ?>
        <section class="me-related">
          <h2>Autres maisons <?= e(\Adl\Controllers\PublisherController::countryPhrase($p['country'])) ?></h2>
          <div class="me-mini-grid">
            <?php foreach ($related as $r): ?>
              <a class="me-mini" href="<?= e(url((string) $r['href'])) ?>">
                <div class="me-card-logo">
                  <?php if ($r['logo_src'] !== ''): ?>
                    <img src="<?= e($r['logo_src']) ?>" alt="" width="44" height="44" loading="lazy">
                  <?php else: ?>
                    <span class="me-mono" aria-hidden="true"><?= e($r['initials']) ?></span>
                  <?php endif; ?>
                </div>
                <div class="me-mini-body">
                  <div class="me-mini-kicker"><?= e($r['size_label']) ?></div>
                  <strong><?= e((string) $r['name']) ?></strong>
                  <span><?= e($r['location_label']) ?></span>
                  <?php if ($r['genres'] !== []): ?>
                    <div class="me-row-genres">
                      <?php foreach (array_slice($r['genres'], 0, 2) as $g): ?><span><?= e($g) ?></span><?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
          <a class="btn-ghost me-related-more" href="<?= e(url($p['country_href'])) ?>">Toutes les maisons <?= e(\Adl\Controllers\PublisherController::countryPhrase($p['country'])) ?></a>
        </section>
      <?php endif; ?>
    </div>

    <aside class="me-side">
      <div class="side-card">
        <div class="side-kicker">En bref</div>
        <dl class="me-facts">
          <?php foreach ($facts as [$label, $value, $href]): ?>
            <div>
              <dt><?= e($label) ?></dt>
              <dd><?php if ($href): ?><a href="<?= e(url($href)) ?>"><?= e($value) ?></a><?php else: ?><?= e($value) ?><?php endif; ?></dd>
            </div>
          <?php endforeach; ?>
        </dl>
      </div>
      <?php if ($submissions !== ''): ?>
        <div class="side-card">
          <div class="side-kicker">Manuscrits</div>
          <div class="me-manuscrits">
            <span class="me-manuscrits-dot" aria-hidden="true"></span>
            <p><?= nl2br(e($submissions)) ?></p>
          </div>
        </div>
      <?php endif; ?>
      <?php if ($isAdmin): ?>
        <div class="side-card">
          <div class="side-kicker">Administration</div>
          <a class="btn-ghost" href="<?= e(url('/admin/maisons-edition/' . (int) $p['id'])) ?>">Modifier la fiche</a>
        </div>
      <?php endif; ?>
      <div class="side-card side-card-warm">
        <div class="side-kicker">Une erreur ?</div>
        <p class="me-side-note">Une information obsolète, une collection oubliée ou une maison qui a fermé : dites-le-nous.</p>
        <a class="btn-ghost" href="<?= e(url('/contact')) ?>">Signaler</a>
      </div>
      <?php if (!$isOwner): ?>
        <div class="side-card forum-panel-dark">
          <div class="forum-panel-heading">Préparer votre envoi</div>
          <p>Un manuscrit relu et une lettre d'accompagnement soignée changent tout. Correcteurs et lecteurs éditoriaux répondent en 48 h.</p>
          <a class="btn-orange" href="<?= e(url($viewer ? '/espace/publier' : '/inscription')) ?>">Publier une recherche</a>
        </div>
      <?php endif; ?>
    </aside>
  </div>
</div>
