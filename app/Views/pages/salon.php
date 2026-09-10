<?php
$s = $salon ?? [];
$upcoming = $upcoming ?? [];
$website = trim((string) ($s['website'] ?? ''));
$description = trim((string) ($s['description'] ?? ''));
$notes = trim((string) ($s['notes'] ?? ''));
$confirmed = !empty($s['confirmed']);
$cover = asset('img/covers/salons-evenements.webp') . '?v=1';
$discussion = is_array($salonForumTopic ?? null) ? $salonForumTopic : null;
$commentError = trim((string) ($salonCommentError ?? ''));
$commentOld = is_array($salonCommentOld ?? null) ? $salonCommentOld : [];
$salonHref = (string) ($s['href'] ?? '/salons/' . ($s['slug'] ?? ''));
$authNext = rawurlencode($salonHref . '#commentaires');
$salonUser = auth_user();

$glance = [];
$when = trim((string) ($s['when_dates'] ?? $s['when'] ?? ''));
if ($when !== '') {
    $glance[] = ['Dates', $when];
}
if (trim((string) ($s['city'] ?? '')) !== '') {
    $glance[] = ['Ville', (string) $s['city']];
}
if (trim((string) ($s['region'] ?? '')) !== '') {
    $glance[] = ['Région', (string) $s['region']];
}
if (trim((string) ($s['country'] ?? '')) !== '' && (string) $s['country'] !== 'France') {
    $glance[] = ['Pays', (string) $s['country']];
}
if (trim((string) ($s['venue'] ?? '')) !== '') {
    $glance[] = ['Lieu', (string) $s['venue']];
}
if (trim((string) ($s['organizer'] ?? '')) !== '') {
    $glance[] = ['Organisateur', (string) $s['organizer']];
}
if (trim((string) ($s['ticket'] ?? '')) !== '') {
    $glance[] = ['Entrée', (string) $s['ticket']];
}
if (trim((string) ($s['audience'] ?? '')) !== '') {
    $glance[] = ['Public', (string) $s['audience']];
}
if (trim((string) ($s['exhibitors'] ?? '')) !== '') {
    $glance[] = ['Exposants', (string) $s['exhibitors']];
}
if (trim((string) ($s['attendance'] ?? '')) !== '') {
    $glance[] = ['Fréquentation', (string) $s['attendance']];
}

$stats = [];
if (trim((string) ($s['attendance'] ?? '')) !== '') {
    $stats[] = ['k' => 'Fréquentation', 'v' => (string) $s['attendance']];
}
if (trim((string) ($s['exhibitors'] ?? '')) !== '') {
    $stats[] = ['k' => 'Exposants', 'v' => (string) $s['exhibitors']];
}

$placeCards = [];
$lieuLines = [];
if (trim((string) ($s['venue'] ?? '')) !== '') {
    $lieuLines[] = ['Adresse', (string) $s['venue']];
}
if (trim((string) ($s['city'] ?? '')) !== '') {
    $cityLine = (string) $s['city'];
    if (trim((string) ($s['department'] ?? '')) !== '') {
        $cityLine .= ' · ' . $s['department'];
    }
    $lieuLines[] = ['Ville', $cityLine];
}
if (trim((string) ($s['region'] ?? '')) !== '') {
    $lieuLines[] = ['Région', (string) $s['region']];
}
if (trim((string) ($s['country'] ?? '')) !== '') {
    $lieuLines[] = ['Pays', (string) $s['country']];
}
if ($lieuLines !== []) {
    $placeCards[] = ['titre' => 'Lieu', 'lignes' => $lieuLines];
}
$infoLines = [];
if (trim((string) ($s['organizer'] ?? '')) !== '') {
    $infoLines[] = ['Organisateur', (string) $s['organizer']];
}
if (trim((string) ($s['ticket'] ?? '')) !== '') {
    $infoLines[] = ['Entrée', (string) $s['ticket']];
}
if (trim((string) ($s['audience'] ?? '')) !== '') {
    $infoLines[] = ['Public', (string) $s['audience']];
}
if (trim((string) ($s['exhibitors'] ?? '')) !== '') {
    $infoLines[] = ['Exposants', (string) $s['exhibitors']];
}
if (trim((string) ($s['attendance'] ?? '')) !== '') {
    $infoLines[] = ['Fréquentation', (string) $s['attendance']];
}
if ($infoLines !== []) {
    $placeCards[] = ['titre' => 'Sur place', 'lignes' => $infoLines];
}
?>
<div class="co-salon-page">
  <section class="profile-hero salon-hero salon-hero-cover" style="--salon-cover: url('<?= e($cover) ?>')">
    <div class="profile-hero-main">
      <nav class="search-crumb salon-crumb" aria-label="Fil d'Ariane">
        <a href="<?= e(url('/')) ?>">Accueil</a>
        <span aria-hidden="true"> · </span>
        <a href="<?= e(url('/communaute')) ?>">Communauté</a>
        <span aria-hidden="true"> · </span>
        <a href="<?= e(url('/salons')) ?>">Agenda des salons</a>
        <span aria-hidden="true"> · </span>
        <span><?= e((string) ($s['name'] ?? '')) ?></span>
      </nav>
      <div class="profile-hero-line">
        <?php if (!empty($s['kind'])): ?><span class="profile-badge"><?= e((string) $s['kind']) ?></span><?php endif; ?>
        <?php if ($confirmed): ?>
          <span class="salon-pill salon-pill-ok">Dates confirmées</span>
        <?php else: ?>
          <span class="salon-pill">Dates à confirmer</span>
        <?php endif; ?>
      </div>
      <h1><?= e((string) ($s['name'] ?? '')) ?></h1>
      <p class="profile-hero-sub"><?= e(trim($when . (!empty($s['place']) ? ' · ' . $s['place'] : '') . (!empty($s['venue']) ? ' · ' . $s['venue'] : ''))) ?></p>
      <?php if ($description !== ''): ?>
        <p class="auteur-hero-bio"><?= e($description) ?></p>
      <?php endif; ?>
    </div>
    <div class="profile-hero-actions">
      <?php if ($website !== ''): ?>
        <a class="btn-orange" href="<?= e($website) ?>" target="_blank" rel="noopener noreferrer">Site de l’organisateur ↗</a>
      <?php endif; ?>
      <a class="btn-ghost-light" href="<?= e(url('/salons')) ?>">Tout l’agenda</a>
    </div>
  </section>

  <section class="salon-fiche">
    <div class="salon-fiche-main">
      <?php if ($stats !== []): ?>
        <div class="salon-stats">
          <?php foreach ($stats as $stat): ?>
            <div class="salon-stat">
              <strong><?= e($stat['v']) ?></strong>
              <span><?= e($stat['k']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($placeCards !== []): ?>
        <div class="salon-cards">
          <?php foreach ($placeCards as $card): ?>
            <div class="salon-card">
              <div class="salon-card-kicker"><?= e($card['titre']) ?></div>
              <dl>
                <?php foreach ($card['lignes'] as $line): ?>
                  <div>
                    <dt><?= e($line[0]) ?></dt>
                    <dd><?= e($line[1]) ?></dd>
                  </div>
                <?php endforeach; ?>
              </dl>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($notes !== '' && $notes !== '-'): ?>
        <p class="salon-notes"><?= e($notes) ?></p>
      <?php endif; ?>

      <section class="article-comments" id="commentaires" aria-labelledby="salon-comments-title">
        <div class="article-comments-head">
          <div>
            <p class="journal-kicker">La discussion continue sur le forum</p>
            <h2 id="salon-comments-title">Et vous, qu’en pensez-vous&nbsp;?</h2>
          </div>
          <?php if ($discussion && (string) ($discussion['href'] ?? '') !== ''): ?>
            <?php $commentCount = (int) ($discussion['reply_count'] ?? 0) + 1; ?>
            <a class="article-comments-count" href="<?= e(url((string) $discussion['href'])) ?>">
              <?= e(format_int($commentCount)) ?> commentaire<?= $commentCount > 1 ? 's' : '' ?>
            </a>
          <?php endif; ?>
        </div>

        <p class="article-comments-intro">
          Votre commentaire ouvre ou rejoint un sujet dans la rubrique Diffusion et librairies du forum, afin que toute la communauté puisse participer.
        </p>

        <?php if ($discussion && (string) ($discussion['href'] ?? '') !== ''): ?>
          <p class="article-comments-topic">
            Discussion classée dans
            <a href="<?= e(url((string) $discussion['category_href'])) ?>"><?= e((string) $discussion['category_name']) ?></a>.
            <a href="<?= e(url((string) $discussion['href'])) ?>">Voir tous les échanges</a>
          </p>
        <?php endif; ?>

        <?php if (!empty($logged) && (empty($discussion) || empty($discussion['is_locked']))): ?>
          <form class="forum-compose article-comment-form" method="post" action="<?= e(url($salonHref . '/commenter')) ?>" data-forum-compose data-min-chars="<?= (int) \Adl\Models\ForumPost::MIN_BODY ?>">
            <?= csrf_field() ?>
            <?= form_guard_fields('salon-comment') ?>
            <div class="forum-compose-head">
              <?= avatar_html($salonUser ?? [], 40, 'forum-avatar') ?>
              <div class="forum-compose-who">
                <div class="forum-post-name"><?= $discussion ? 'Votre commentaire' : 'Lancer la discussion' ?></div>
                <div class="forum-aside-meta">Votre message sera publié sur le forum.</div>
              </div>
              <span class="forum-pin">Sans IA</span>
            </div>
            <div class="forum-compose-body">
              <?php if ($commentError !== ''): ?>
                <p class="forum-compose-error" data-compose-error data-server-error><?= e($commentError) ?></p>
              <?php else: ?>
                <p class="forum-compose-error" data-compose-error hidden></p>
              <?php endif; ?>
              <?php
                $forumWysiwygName = 'body';
                $forumWysiwygValue = (string) ($commentOld['body'] ?? '');
                $forumWysiwygPlaceholder = 'Partagez un retour de stand, un conseil pratique ou une information utile sur cet événement.';
                $forumWysiwygRows = 6;
                $forumWysiwygRequired = true;
                require ADL_ROOT . '/app/Views/partials/forum-wysiwyg.php';
              ?>
              <label class="forum-engage">
                <input type="checkbox" name="no_ai" value="1" required>
                <span>Je confirme que ce commentaire est de ma main et qu’aucune IA générative n’a été utilisée pour le produire.</span>
              </label>
              <p class="forum-compose-block" data-compose-block hidden role="status" aria-live="polite"></p>
              <div class="forum-compose-actions">
                <button type="submit" class="btn-orange"><?= $discussion ? 'Publier mon commentaire' : 'Commenter et ouvrir le sujet' ?></button>
                <span class="forum-draft-count" data-draft-count role="status" aria-live="polite">Minimum <?= (int) \Adl\Models\ForumPost::MIN_BODY ?> caractères pour publier</span>
              </div>
            </div>
          </form>
        <?php elseif (!empty($discussion['is_locked'])): ?>
          <p class="article-comments-locked">Cette discussion est actuellement fermée aux nouveaux commentaires.</p>
        <?php else: ?>
          <div class="article-comments-auth">
            <p>Connectez-vous ou créez votre compte gratuitement pour participer.</p>
            <div class="article-comments-actions">
              <a class="btn-orange" href="<?= e(url('/connexion?next=' . $authNext)) ?>">Se connecter</a>
              <a class="btn-ghost" href="<?= e(url('/inscription?next=' . $authNext)) ?>">Créer mon compte</a>
            </div>
          </div>
        <?php endif; ?>
      </section>
    </div>

    <aside class="salon-fiche-side">
      <?php if ($glance !== []): ?>
        <div class="side-card">
          <div class="side-kicker">En un coup d’œil</div>
          <dl class="salon-glance">
            <?php foreach ($glance as $row): ?>
              <div>
                <dt><?= e($row[0]) ?></dt>
                <dd><?= e($row[1]) ?></dd>
              </div>
            <?php endforeach; ?>
          </dl>
        </div>
      <?php endif; ?>

      <?php if ($upcoming !== []): ?>
        <div class="side-card">
          <div class="side-kicker">À venir</div>
          <div class="salon-near-list">
            <?php foreach ($upcoming as $event): ?>
              <a class="salon-near" href="<?= e(url((string) ($event['href'] ?? '/salons'))) ?>">
                <strong><?= e((string) ($event['name'] ?? '')) ?></strong>
                <span><?= e(trim((string) ($event['when_dates'] ?? $event['when'] ?? '') . (!empty($event['place']) ? ' · ' . $event['place'] : ''))) ?></span>
              </a>
            <?php endforeach; ?>
          </div>
          <a class="salon-side-more" href="<?= e(url('/salons')) ?>">Tout l’agenda →</a>
        </div>
      <?php endif; ?>

      <div class="side-card forum-panel-dark">
        <div class="forum-panel-heading">Votre salon</div>
        <p>Vous organisez un salon, un festival ou une foire du livre ? Proposez-le : l’équipe le publie après vérification.</p>
        <a class="btn-orange" href="<?= e(url('/salons/ajouter')) ?>">Ajouter un salon</a>
      </div>
    </aside>
  </section>
</div>
