<?php
$category = $forumCategory ?? [];
$topic = $forumTopic ?? [];
$op = $forumOp ?? null;
$replies = $forumReplies ?? [];
$replyTotal = (int) ($forumReplyTotal ?? 0);
$sort = (string) ($forumSort ?? 'chrono');
$participants = $forumParticipants ?? [];
$participantCount = (int) ($forumParticipantCount ?? 0);
$related = $forumRelated ?? [];
$following = !empty($forumFollowing);
$authorPostCount = (int) ($forumAuthorPostCount ?? 0);
$old = is_array($old ?? null) ? $old : [];
$pager = $pager ?? ['page' => 1, 'pages' => 1, 'total' => 0];
$page = (int) ($pager['page'] ?? 1);
$pages = (int) ($pager['pages'] ?? 1);
$logged = !empty($logged);
$user = $user ?? auth_user();
$isAuthor = $logged && (int) ($user['id'] ?? 0) === (int) ($topic['user_id'] ?? 0);
$isAdmin = !empty($isAdmin);
$topicHref = (string) ($topic['href'] ?? '/forum');
$canReply = $logged && empty($topic['is_locked']);
?>
<div class="forum-page">
  <section class="forum-hero forum-hero-compact">
    <div class="forum-crumb">
      <a href="<?= e(url('/forum')) ?>">Forum</a>
      <span>/</span>
      <a href="<?= e(url((string) ($category['href'] ?? '/forum'))) ?>"><?= e((string) ($category['name'] ?? '')) ?></a>
      <span>/</span>
      <span>Discussion</span>
    </div>
    <div class="forum-hero-grid">
      <div>
        <div class="forum-hero-badges">
          <span class="forum-cat forum-cat-soft"><?= e((string) ($topic['category_short'] ?? '')) ?></span>
          <?php if (!empty($topic['is_solved'])): ?>
            <span class="forum-badge forum-badge-resolu is-on-dark">Résolu</span>
          <?php endif; ?>
        </div>
        <h1><?= e((string) ($topic['title'] ?? '')) ?></h1>
        <div class="forum-hero-meta">
          Ouverte par <?= e((string) ($topic['author']['name'] ?? '')) ?>
          · <?= e((string) ($topic['author']['role'] ?? '')) ?>
          · <?= e((string) ($topic['when'] ?? '')) ?>
          · <?= e(format_int($replyTotal)) ?> réponses
          · <?= e((string) ($topic['views_label'] ?? '0')) ?> vues
        </div>
      </div>
      <div class="forum-hero-actions">
        <?php if ($canReply): ?>
          <a class="btn-orange forum-hero-cta" href="#repondre">Répondre</a>
        <?php elseif (!$logged): ?>
          <a class="btn-orange forum-hero-cta" href="<?= e(url('/connexion')) ?>">Se connecter pour répondre</a>
        <?php endif; ?>
        <div class="forum-hero-secondary">
          <?php if ($logged): ?>
            <form method="post" action="<?= e(url($topicHref . '/suivre')) ?>">
              <?= csrf_field() ?>
              <button type="submit" class="btn-ghost-light"><?= $following ? 'Ne plus suivre' : 'Suivre' ?></button>
            </form>
          <?php endif; ?>
          <?php if (!empty($share)): ?>
            <?php /* share partial handled below if available */ ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <div class="forum-body forum-split forum-split-narrow">
    <div>
      <?php if ($op): ?>
        <article class="forum-post" id="post-<?= (int) $op['id'] ?>">
          <aside class="forum-post-side">
            <?= avatar_html($op['author'] ?? [], 54, 'forum-avatar') ?>
            <div class="forum-post-name"><?= e((string) ($op['author']['name'] ?? '')) ?><?= forum_cofounder_star($op['author'] ?? null) ?></div>
            <div class="forum-aside-meta"><?= e((string) ($op['author']['meta'] ?? '')) ?></div>
            <div class="forum-aside-meta forum-post-joined">
              <?php if (!empty($op['author']['member_since'])): ?>Membre depuis <?= e((string) $op['author']['member_since']) ?><br><?php endif; ?>
              <?= e(format_int($authorPostCount)) ?> messages
            </div>
          </aside>
          <div class="forum-post-body">
            <div class="forum-post-head">
              <span><?= e((string) ($op['num'] ?? '#1')) ?> · <?= e((string) ($op['when'] ?? '')) ?></span>
              <?php if ($logged): ?>
                <form class="forum-inline-form" method="post" action="<?= e(url('/signaler')) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="type" value="forum_post">
                  <input type="hidden" name="id" value="<?= (int) $op['id'] ?>">
                  <input type="hidden" name="reason" value="ia">
                  <input type="hidden" name="body" value="Signalement depuis le forum">
                  <input type="hidden" name="back" value="<?= e($topicHref) ?>">
                  <button type="submit" class="forum-text-btn">Signaler</button>
                </form>
              <?php endif; ?>
            </div>
            <div class="forum-post-content"><?= $op['body_html'] ?? '' ?></div>
            <?php if (!empty($topic['tags'])): ?>
              <div class="forum-tags" style="margin-top:16px;">
                <?php foreach ($topic['tags'] as $tag): ?>
                  <span class="forum-tag"><?= e((string) $tag) ?></span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </article>
      <?php endif; ?>

      <div class="forum-replies-head" id="reponses">
        <span><?= e(format_int($replyTotal)) ?> réponse<?= $replyTotal > 1 ? 's' : '' ?></span>
        <div class="forum-replies-line"></div>
        <a class="chip<?= $sort === 'useful' ? ' is-on' : '' ?>" href="<?= e(url($topicHref . '?tri=utiles')) ?>">Les plus utiles</a>
        <a class="chip<?= $sort === 'chrono' ? ' is-on' : '' ?>" href="<?= e(url($topicHref)) ?>">Chronologique</a>
      </div>

      <div class="forum-replies">
        <?php if ($replies === []): ?>
          <div class="search-empty forum-empty">
            <strong>Pas encore de réponse.</strong>
            <span>La première réponse utile change souvent tout.</span>
          </div>
        <?php else: ?>
          <?php foreach ($replies as $r): ?>
            <article class="forum-post<?= !empty($r['is_solution']) ? ' is-solution' : '' ?>" id="post-<?= (int) $r['id'] ?>">
              <aside class="forum-post-side">
                <?= avatar_html($r['author'] ?? [], 46, 'forum-avatar') ?>
                <div class="forum-post-name"><?= e((string) ($r['author']['name'] ?? '')) ?><?= forum_cofounder_star($r['author'] ?? null) ?></div>
                <div class="forum-aside-meta"><?= e((string) ($r['author']['meta'] ?? '')) ?></div>
                <?php if (!empty($r['author']['verified'])): ?>
                  <span class="forum-verif">Profil vérifié</span>
                <?php endif; ?>
              </aside>
              <div class="forum-post-body">
                <div class="forum-post-head">
                  <div class="forum-post-head-left">
                    <span><?= e((string) ($r['num'] ?? '')) ?> · <?= e((string) ($r['when'] ?? '')) ?></span>
                    <?php if (!empty($r['is_solution'])): ?>
                      <span class="forum-badge forum-badge-resolu">Réponse retenue</span>
                    <?php endif; ?>
                  </div>
                  <div class="forum-post-actions-top">
                    <?php if ($canReply): ?>
                      <a class="forum-text-btn" href="#repondre" data-cite="<?= (int) $r['id'] ?>" data-cite-name="<?= e((string) ($r['author']['name'] ?? '')) ?>">Citer</a>
                    <?php endif; ?>
                    <?php if ($logged): ?>
                      <form class="forum-inline-form" method="post" action="<?= e(url('/signaler')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="type" value="forum_post">
                        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                        <input type="hidden" name="reason" value="ia">
                        <input type="hidden" name="body" value="Signalement depuis le forum">
                        <input type="hidden" name="back" value="<?= e($topicHref . '#post-' . (int) $r['id']) ?>">
                        <button type="submit" class="forum-text-btn">Signaler</button>
                      </form>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="forum-post-content"><?= $r['body_html'] ?? '' ?></div>
                <div class="forum-post-foot">
                  <?php if ($logged): ?>
                    <form method="post" action="<?= e(url($topicHref . '/utile/' . (int) $r['id'])) ?>" class="forum-inline-form">
                      <?= csrf_field() ?>
                      <button type="submit" class="forum-useful<?= !empty($r['liked']) ? ' is-on' : '' ?>">▲ <?= (int) ($r['useful_count'] ?? 0) ?> utile</button>
                    </form>
                  <?php else: ?>
                    <span class="forum-useful">▲ <?= (int) ($r['useful_count'] ?? 0) ?> utile</span>
                  <?php endif; ?>
                  <?php if ($canReply): ?>
                    <a href="#repondre" class="forum-text-btn" data-cite="<?= (int) $r['id'] ?>">Répondre</a>
                  <?php endif; ?>
                  <?php if (!empty($r['citation'])): ?>
                    <span class="forum-aside-meta">en réponse à <?= e((string) $r['citation']) ?></span>
                  <?php endif; ?>
                  <?php if (($isAuthor || $isAdmin) && empty($r['is_solution'])): ?>
                    <form method="post" action="<?= e(url($topicHref . '/retenir/' . (int) $r['id'])) ?>" class="forum-inline-form">
                      <?= csrf_field() ?>
                      <button type="submit" class="forum-text-btn">Retenir comme solution</button>
                    </form>
                  <?php endif; ?>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <?php if ($pages > 1): ?>
        <nav class="forum-pager" aria-label="Pagination des réponses">
          <?php
            $base = $topicHref . ($sort === 'useful' ? '?tri=utiles&' : '?');
          ?>
          <?php if ($page > 1): ?>
            <a href="<?= e(url($topicHref . ($sort === 'useful' ? '?tri=utiles&page=' : '?page=') . ($page - 1))) ?>">‹</a>
          <?php endif; ?>
          <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
            <a class="<?= $i === $page ? 'is-on' : '' ?>" href="<?= e(url($topicHref . ($sort === 'useful' ? '?tri=utiles&page=' : '?page=') . $i)) ?>"><?= $i ?></a>
          <?php endfor; ?>
          <?php if ($page < $pages): ?>
            <a href="<?= e(url($topicHref . ($sort === 'useful' ? '?tri=utiles&page=' : '?page=') . ($page + 1))) ?>">›</a>
          <?php endif; ?>
        </nav>
      <?php endif; ?>

      <?php if ($canReply): ?>
        <form class="forum-compose" id="repondre" method="post" action="<?= e(url($topicHref . '/repondre')) ?>" data-forum-compose>
          <?= csrf_field() ?>
          <input type="hidden" name="parent_id" value="" data-parent-id>
          <div class="forum-compose-head">
            <?= avatar_html($user ?? [], 40, 'forum-avatar') ?>
            <div class="forum-compose-who">
              <div class="forum-post-name">Votre réponse</div>
              <div class="forum-aside-meta"><?= e(Adl\Models\User::displayName($user ?? [])) ?> · profil connecté</div>
            </div>
            <span class="forum-pin">Sans IA</span>
          </div>
          <div class="forum-compose-body">
            <p class="forum-cite-hint" data-cite-hint hidden></p>
            <?php
              $forumWysiwygName = 'body';
              $forumWysiwygValue = (string) ($old['body'] ?? '');
              $forumWysiwygPlaceholder = 'Partagez un chiffre, une expérience, une clause précise. Les réponses vagues n\'aident personne.';
              $forumWysiwygRows = 7;
              $forumWysiwygRequired = true;
              require ADL_ROOT . '/app/Views/partials/forum-wysiwyg.php';
            ?>
            <label class="forum-engage">
              <input type="checkbox" name="no_ai" value="1" required>
              <span>Je confirme que cette réponse est de ma main et qu'aucune IA générative n'a été utilisée pour la produire.</span>
            </label>
            <div class="forum-compose-actions">
              <button type="submit" class="btn-orange">Publier la réponse</button>
              <span class="forum-muted" data-draft-count>Minimum 80 caractères</span>
            </div>
          </div>
        </form>
      <?php elseif (!$logged): ?>
        <div class="forum-compose forum-compose-locked">
          <p><a href="<?= e(url('/connexion')) ?>">Connectez-vous</a> pour répondre — et confirmer que votre message est écrit de votre main.</p>
        </div>
      <?php endif; ?>
    </div>

    <aside class="forum-aside">
      <div class="forum-panel">
        <div class="forum-panel-title">Cette discussion</div>
        <div class="forum-meta-list">
          <div><span>Rubrique</span><a href="<?= e(url((string) ($category['href'] ?? '/forum'))) ?>"><?= e((string) ($category['name'] ?? '')) ?></a></div>
          <div><span>Ouverte</span><strong><?= e((string) ($topic['when'] ?? '')) ?></strong></div>
          <div><span>Dernière réponse</span><strong><?= e((string) ($topic['last_when'] ?? '')) ?></strong></div>
          <div><span>Participants</span><strong><?= e(format_int($participantCount)) ?></strong></div>
          <div><span>Statut</span><strong><?= !empty($topic['is_solved']) ? 'Résolue' : (!empty($topic['is_locked']) ? 'Verrouillée' : 'Ouverte') ?></strong></div>
        </div>
        <?php if ($participants !== []): ?>
          <div class="forum-participants">
            <?php foreach ($participants as $p): ?>
              <?= avatar_html($p, 30, 'forum-avatar') ?>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="forum-notice forum-notice-aside">
        <div class="forum-notice-title">
          <span class="forum-notice-icon" aria-hidden="true">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><circle cx="12" cy="12" r="8.5"/><path d="M6 18L18 6"/></svg>
          </span>
          <span>Réponses humaines</span>
        </div>
        <p>Chaque réponse est écrite par un professionnel identifié. Un message généré par IA est retiré et son auteur perd son badge vérifié.</p>
      </div>

      <?php if ($related !== []): ?>
        <div class="forum-panel">
          <div class="forum-panel-title">Discussions liées</div>
          <div class="forum-aside-list">
            <?php foreach ($related as $l): ?>
              <a href="<?= e(url((string) $l['href'])) ?>">
                <div class="forum-aside-link"><?= e((string) ($l['title'] ?? '')) ?></div>
                <div class="forum-aside-meta"><?= e((string) (($l['category_short'] ?? '') . ' · ' . format_int((int) ($l['reply_count'] ?? 0)) . ' réponses')) ?></div>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="forum-panel forum-panel-dark">
        <div class="forum-panel-heading">Besoin d'un prestataire ?</div>
        <p>Le forum sert à comprendre. Pour faire faire, publiez un appel d'offres avec un périmètre clair.</p>
        <a class="btn-orange" href="<?= e(url('/espace/publier')) ?>">Publier un appel d'offres</a>
      </div>
    </aside>
  </div>
</div>
