<?php $articles = $articles ?? []; ?>
<div class="espace-page tribune-page">
  <div class="espace-page-head">
    <div>
      <h1>Tribune</h1>
      <p>Proposez, si vous le souhaitez, un texte au journal de la plateforme.</p>
    </div>
    <a class="btn-orange" href="<?= e(url('/espace/tribune/nouvelle')) ?>">Écrire une tribune</a>
  </div>

  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e((string) $saved) ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <div class="tribune-intro espace-panel">
    <div class="tribune-intro-mark" aria-hidden="true">T</div>
    <div>
      <h2 class="espace-group-title">Une parole ouverte à tous les membres</h2>
      <p>Cette contribution est entièrement facultative. Chacun peut écrire un mot, partager une expérience, un point de vue ou une réflexion autour du livre et de ses métiers.</p>
      <p>Vous restez libre d’enregistrer un brouillon. Lorsque vous le soumettez, l’équipe le relit avant toute publication. Vous recevez un e-mail dès qu’il est accepté ou refusé ; en cas de refus, le motif apparaît aussi ici et vous pouvez reprendre votre texte.</p>
    </div>
  </div>

  <?php if ($articles === []): ?>
    <div class="search-empty">
      <strong>Vous n’avez encore écrit aucune tribune.</strong>
      <span>Commencez tranquillement : votre texte reste en brouillon tant que vous ne l’avez pas soumis.</span>
      <a class="btn-orange" href="<?= e(url('/espace/tribune/nouvelle')) ?>">Commencer un article</a>
    </div>
  <?php else: ?>
    <div class="my-missions tribune-list">
      <?php foreach ($articles as $article): ?>
        <?php $status = (string) ($article['submission_status'] ?? 'draft'); ?>
        <article class="side-card">
          <div class="mission-row-title">
            <?= e((string) $article['title']) ?>
            <span class="status-pill status-<?= e($status) ?>"><?= e((string) $article['status_label']) ?></span>
          </div>
          <div class="mission-row-sub">
            <?php if (!empty($article['submitted_at'])): ?>
              Soumise le <?= e(date('d/m/Y à H:i', strtotime((string) $article['submitted_at']))) ?>
            <?php else: ?>
              Créée le <?= e(date('d/m/Y', strtotime((string) $article['created_at']))) ?>
            <?php endif; ?>
            <?php if (!empty($article['moderated_at'])): ?>
              · décision le <?= e(date('d/m/Y', strtotime((string) $article['moderated_at']))) ?>
            <?php endif; ?>
          </div>
          <?php if ($status === 'pending'): ?>
            <p class="tribune-state-note">Votre texte est en cours de relecture. Vous recevrez un e-mail dès que l’équipe aura rendu sa décision.</p>
          <?php elseif ($status === 'rejected' && !empty($article['moderation_note'])): ?>
            <div class="tribune-rejection"><strong>Motif :</strong> <?= nl2br(e((string) $article['moderation_note'])) ?></div>
          <?php endif; ?>
          <div class="auth-actions" style="margin-top: 14px;">
            <?php if ($status === 'approved'): ?>
              <a class="btn-navy" href="<?= e(url((string) $article['href'])) ?>">Lire dans le journal</a>
            <?php else: ?>
              <a class="btn-navy" href="<?= e(url('/espace/tribune/' . (int) $article['id'])) ?>"><?= $status === 'pending' ? 'Voir le texte' : 'Continuer' ?></a>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
