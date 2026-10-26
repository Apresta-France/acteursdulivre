<?php
$s = $contest ?? [];
$upcoming = $upcoming ?? [];
$website = trim((string) ($s['website'] ?? ''));
$description = trim((string) ($s['description'] ?? ''));
$notes = trim((string) ($s['notes'] ?? ''));
$confirmed = !empty($s['confirmed']);
$cover = asset('img/covers/salons-evenements.webp') . '?v=1';
$discussion = is_array($contestForumTopic ?? null) ? $contestForumTopic : null;
$commentError = trim((string) ($contestCommentError ?? ''));
$commentOld = is_array($contestCommentOld ?? null) ? $contestCommentOld : [];
$contestHref = (string) ($s['href'] ?? '/prix-et-concours/' . ($s['slug'] ?? ''));
$authNext = rawurlencode($contestHref . '#commentaires');
$contestUser = auth_user();

$glance = [];
if (trim((string) ($s['deadline_label'] ?? '')) !== '') {
    $glance[] = ['Date limite', (string) $s['deadline_label']];
}
if (trim((string) ($s['opens_label'] ?? '')) !== '') {
    $glance[] = ['Ouverture', (string) $s['opens_label']];
}
if (trim((string) ($s['awarded_label'] ?? '')) !== '') {
    $glance[] = ['Proclamation', (string) $s['awarded_label']];
}
if (trim((string) ($s['prize'] ?? '')) !== '') {
    $glance[] = ['Dotation', (string) $s['prize']];
}
$glance[] = ['Participation', !empty($s['is_free']) ? 'Gratuite' : ((string) ($s['entry_fee'] ?? '') !== '' ? (string) $s['entry_fee'] : 'À vérifier')];
if (trim((string) ($s['eligibility'] ?? '')) !== '') {
    $glance[] = ['Conditions', (string) $s['eligibility']];
}
if (trim((string) ($s['place'] ?? '')) !== '') {
    $glance[] = ['Lieu', (string) $s['place']];
}
if (trim((string) ($s['organizer'] ?? '')) !== '') {
    $glance[] = ['Organisateur', (string) $s['organizer']];
}

$placeCards = [];
$dateLines = [];
if (trim((string) ($s['opens_label'] ?? '')) !== '') {
    $dateLines[] = ['Ouverture des candidatures', (string) $s['opens_label']];
}
if (trim((string) ($s['deadline_label'] ?? '')) !== '') {
    $dateLines[] = ['Date limite', (string) $s['deadline_label']];
}
if (trim((string) ($s['awarded_label'] ?? '')) !== '') {
    $dateLines[] = ['Proclamation', (string) $s['awarded_label']];
}
if (trim((string) ($s['when'] ?? '')) !== '' && $dateLines === []) {
    $dateLines[] = ['Dates', (string) $s['when']];
}
if ($dateLines !== []) {
    $placeCards[] = ['titre' => 'Calendrier', 'lignes' => $dateLines];
}
$rewardLines = [];
if (trim((string) ($s['prize'] ?? '')) !== '') {
    $rewardLines[] = ['Dotation', (string) $s['prize']];
}
if (trim((string) ($s['entry_fee'] ?? '')) !== '') {
    $rewardLines[] = ['Participation', (string) $s['entry_fee']];
}
if (trim((string) ($s['eligibility'] ?? '')) !== '') {
    $rewardLines[] = ['Conditions', (string) $s['eligibility']];
}
if (trim((string) ($s['audience'] ?? '')) !== '') {
    $rewardLines[] = ['Pour qui', (string) $s['audience']];
}
if ($rewardLines !== []) {
    $placeCards[] = ['titre' => 'Récompense et conditions', 'lignes' => $rewardLines];
}
$infoLines = [];
if (trim((string) ($s['organizer'] ?? '')) !== '') {
    $infoLines[] = ['Organisateur', (string) $s['organizer']];
}
$lieuParts = array_filter([
    (string) ($s['venue'] ?? ''),
    (string) ($s['city'] ?? ''),
    (string) ($s['country'] ?? ''),
], static fn (string $v): bool => $v !== '');
if ($lieuParts !== []) {
    $infoLines[] = ['Lieu', implode(' · ', $lieuParts)];
}
if ($infoLines !== []) {
    $placeCards[] = ['titre' => 'Organisation', 'lignes' => $infoLines];
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
        <a href="<?= e(url('/prix-et-concours')) ?>">Prix et concours</a>
        <span aria-hidden="true"> · </span>
        <span><?= e((string) ($s['name'] ?? '')) ?></span>
      </nav>
      <div class="profile-hero-line">
        <?php if (!empty($s['kind'])): ?><span class="profile-badge"><?= e((string) $s['kind']) ?></span><?php endif; ?>
        <?php if (!empty($s['is_past'])): ?>
          <span class="salon-pill">Édition passée</span>
        <?php elseif (!empty($s['is_closed'])): ?>
          <span class="salon-pill">Candidatures closes</span>
        <?php elseif ($confirmed): ?>
          <span class="salon-pill salon-pill-ok">Dates confirmées</span>
        <?php else: ?>
          <span class="salon-pill">Dates à confirmer</span>
        <?php endif; ?>
      </div>
      <h1><?= e((string) ($s['name'] ?? '')) ?></h1>
      <p class="profile-hero-sub"><?= e(trim((string) ($s['when'] ?? '') . (!empty($s['place']) ? ' · ' . $s['place'] : ''))) ?></p>
      <?php if (!empty($s['is_past'])): ?>
        <p class="profile-hero-sub">Prochaine édition : dates à venir.</p>
      <?php endif; ?>
      <?php if ($description !== ''): ?>
        <p class="auteur-hero-bio"><?= e($description) ?></p>
      <?php endif; ?>
    </div>
    <div class="profile-hero-actions">
      <?php if ($website !== ''): ?>
        <a class="btn-orange" href="<?= e($website) ?>" target="_blank" rel="noopener noreferrer">Site officiel ↗</a>
      <?php endif; ?>
      <a class="btn-ghost-light" href="<?= e(url('/prix-et-concours')) ?>">Tout l’annuaire</a>
    </div>
  </section>

  <section class="salon-fiche">
    <div class="salon-fiche-main">
      <?php if (!empty($s['deadline_label']) && empty($s['is_past'])): ?>
        <div class="salon-stats">
          <div class="salon-stat">
            <strong><?= e((string) $s['deadline_label']) ?></strong>
            <span>Date limite de candidature</span>
          </div>
          <?php if (!empty($s['prize'])): ?>
            <div class="salon-stat">
              <strong><?= e((string) $s['prize']) ?></strong>
              <span>Dotation</span>
            </div>
          <?php endif; ?>
          <div class="salon-stat">
            <strong><?= e(!empty($s['is_free']) ? 'Gratuite' : ((string) ($s['entry_fee'] ?? '') !== '' ? (string) $s['entry_fee'] : 'À vérifier')) ?></strong>
            <span>Participation</span>
          </div>
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

      <section class="article-comments" id="commentaires" aria-labelledby="prix-comments-title">
        <div class="article-comments-head">
          <div>
            <p class="journal-kicker">La discussion continue sur le forum</p>
            <h2 id="prix-comments-title">Et vous, qu’en pensez-vous&nbsp;?</h2>
          </div>
          <?php if ($discussion && (string) ($discussion['href'] ?? '') !== ''): ?>
            <?php $commentCount = (int) ($discussion['reply_count'] ?? 0) + 1; ?>
            <a class="article-comments-count" href="<?= e(url((string) $discussion['href'])) ?>">
              <?= e(format_int($commentCount)) ?> commentaire<?= $commentCount > 1 ? 's' : '' ?>
            </a>
          <?php endif; ?>
        </div>

        <p class="article-comments-intro">
          Votre commentaire ouvre ou rejoint un sujet dans la rubrique Édition du forum, afin que toute la communauté puisse participer.
        </p>

        <?php if ($discussion && (string) ($discussion['href'] ?? '') !== ''): ?>
          <p class="article-comments-topic">
            Discussion classée dans
            <a href="<?= e(url((string) $discussion['category_href'])) ?>"><?= e((string) $discussion['category_name']) ?></a>.
            <a href="<?= e(url((string) $discussion['href'])) ?>">Voir tous les échanges</a>
          </p>
        <?php endif; ?>

        <?php if (!empty($logged) && (empty($discussion) || empty($discussion['is_locked']))): ?>
          <form class="forum-compose article-comment-form" method="post" action="<?= e(url($contestHref . '/commenter')) ?>" data-forum-compose data-min-chars="<?= (int) \Adl\Models\ForumPost::MIN_BODY ?>">
            <?= csrf_field() ?>
            <?= form_guard_fields('contest-comment') ?>
            <div class="forum-compose-head">
              <?= avatar_html($contestUser ?? [], 40, 'forum-avatar') ?>
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
                $forumWysiwygPlaceholder = 'Partagez un retour de candidature, un conseil pratique ou une information utile sur cette récompense.';
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
              <a class="salon-near" href="<?= e(url((string) ($event['href'] ?? '/prix-et-concours'))) ?>">
                <strong><?= e((string) ($event['name'] ?? '')) ?></strong>
                <span><?= e(trim((string) ($event['deadline_label'] ?? $event['when'] ?? '') . (!empty($event['place']) ? ' · ' . $event['place'] : ''))) ?></span>
              </a>
            <?php endforeach; ?>
          </div>
          <a class="salon-side-more" href="<?= e(url('/prix-et-concours')) ?>">Tout l’annuaire →</a>
        </div>
      <?php endif; ?>

      <div class="side-card forum-panel-dark">
        <div class="forum-panel-heading">Votre récompense</div>
        <p>Vous organisez un prix littéraire, un concours ou un appel à textes ? Proposez-le : l’équipe le publie après vérification.</p>
        <a class="btn-orange" href="<?= e(url('/prix-et-concours/ajouter')) ?>">Proposer un prix</a>
      </div>
    </aside>
  </section>
</div>
