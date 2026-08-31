<?php
$live = $liveMission ?? null;
if (!$live) {
    not_found('Cette recherche n\'est plus disponible.');
}
?>
<div class="mission-page">
  <div class="search-crumb">Appels d'offres · <?= e((string) ($live['category_name'] ?? 'Recherche')) ?></div>
  <div class="publish-grid">
    <div>
      <div class="mission-row-title" style="margin-bottom: 10px;">
        <span class="status-pill status-<?= e((string) $live['status']) ?>"><?= e((string) $live['status_label']) ?></span>
        <span class="mission-row-sub"><?= ($live['status'] ?? '') === 'draft' ? 'enregistrée' : 'publiée' ?> <?= e((string) $live['when']) ?></span>
        <?php if (!empty($isOwner) && in_array((string) ($live['status'] ?? ''), ['draft', 'open'], true)): ?>
          <a class="btn-ghost" href="<?= e(url('/espace/publier/' . (int) $live['id'])) ?>">Modifier</a>
        <?php endif; ?>
      </div>
      <h1 class="mission-title"><?= e((string) $live['title']) ?></h1>
      <div class="facts">
        <div><span>Métier</span><strong><?= e((string) ($live['category_name'] ?: '—')) ?></strong></div>
        <div><span>Budget</span><strong><?= e((string) $live['budget']) ?></strong></div>
        <?php if (trim((string) ($live['volume'] ?? '')) !== ''): ?>
          <div><span>Volume</span><strong><?= e((string) $live['volume']) ?></strong></div>
        <?php endif; ?>
        <div><span>Échéance</span><strong><?= e((string) $live['deadline_label']) ?></strong></div>
      </div>
      <h2>Le besoin</h2>
      <p class="profile-text"><?= nl2br(e((string) ($live['brief'] ?? ''))) ?></p>
      <?php if (!empty($live['attachment_href'])): ?>
        <h2>Pièce jointe</h2>
        <a class="file-chip" href="<?= e((string) $live['attachment_href']) ?>">
          <?= e((string) ($live['attachment_name'] ?? 'Document')) ?>
        </a>
      <?php endif; ?>

      <?php if (!empty($isOwner)): ?>
        <h2 id="candidatures">Candidatures (<?= count($applications ?? []) ?>)</h2>
        <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e((string) $saved) ?></div><?php endif; ?>
        <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>
        <?php if (($applications ?? []) === []): ?>
          <p class="mission-row-sub">Aucune candidature pour le moment.</p>
        <?php else: ?>
          <div class="my-missions">
            <?php foreach ($applications as $app): ?>
              <article class="side-card">
                <div class="mission-row-title">
                  <?= e((string) $app['by']) ?>
                  <span class="status-pill status-<?= e((string) $app['status_tone']) ?>"><?= e((string) $app['status_label']) ?></span>
                </div>
                <p class="profile-text"><?= nl2br(e((string) ($app['message'] ?? ''))) ?></p>
                <div class="side-foot">
                  <span><?= e((string) ($app['delay'] ?: 'Délai à convenir')) ?></span>
                  <strong><?= e((string) $app['price']) ?></strong>
                </div>
                <?php if (in_array((string) ($app['status'] ?? ''), ['sent', 'viewed'], true) && ($live['status'] ?? '') === 'open'): ?>
                  <div class="auth-actions" style="margin-top: 14px;">
                    <form method="post" action="<?= e(url('/espace/candidatures/' . (int) $app['id'] . '/accepter')) ?>">
                      <?= csrf_field() ?>
                      <button class="btn-orange" type="submit">Accepter et ouvrir la commande</button>
                    </form>
                    <form method="post" action="<?= e(url('/espace/candidatures/' . (int) $app['id'] . '/refuser')) ?>">
                      <?= csrf_field() ?>
                      <input type="hidden" name="back" value="<?= e('/missions/' . ($live['slug'] ?? '') . '#candidatures') ?>">
                      <button class="btn-ghost" type="submit">Écarter</button>
                    </form>
                    <?php if (!empty($app['profile_href'])): ?>
                      <a class="btn-ghost" href="<?= e(url((string) $app['profile_href'])) ?>">Vitrine</a>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
    <aside class="publish-side">
      <div class="side-card">
        <div class="side-kicker">Le porteur de projet</div>
        <div class="suggest-row" style="padding: 0;">
          <span class="avatar" style="<?= e(avatar_style((string) $live['initials'], 46)) ?>"><?= e((string) $live['initials']) ?></span>
          <span>
            <strong><?= e((string) $live['by']) ?></strong>
            <em>Recherche publiée sur la plateforme</em>
          </span>
        </div>
      </div>
      <?php if (!empty($canApply)): ?>
        <div class="side-card" id="candidater">
          <div class="side-kicker">Proposer vos services</div>
          <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>
          <form method="post" action="<?= e(url('/missions/' . ($live['slug'] ?? '') . '/candidater')) ?>">
            <?= csrf_field() ?>
            <label class="field" for="price">Votre tarif (€)</label>
            <input class="input" id="price" name="price" inputmode="numeric" value="<?= e((string) ($old['price'] ?? '')) ?>" placeholder="780">
            <label class="field" for="delay" style="margin-top: 12px;">Délai</label>
            <input class="input" id="delay" name="delay" value="<?= e((string) ($old['delay'] ?? '')) ?>" placeholder="12 jours">
            <label class="field" for="message" style="margin-top: 12px;">Votre approche</label>
            <textarea class="textarea" id="message" name="message" rows="5" required placeholder="Ce que vous proposez, le calendrier, les questions."><?= e((string) ($old['message'] ?? '')) ?></textarea>
            <div class="auth-actions" style="margin-top: 14px;">
              <button class="btn-orange" type="submit">Envoyer ma candidature</button>
            </div>
          </form>
        </div>
      <?php elseif (!empty($myApplication)): ?>
        <div class="side-card">
          <div class="side-kicker">Votre candidature</div>
          <span class="status-pill status-<?= e((string) $myApplication['status_tone']) ?>"><?= e((string) $myApplication['status_label']) ?></span>
          <p class="mission-row-sub" style="margin-top: 10px;"><?= e((string) $myApplication['price']) ?> · <?= e((string) ($myApplication['delay'] ?: 'Délai à convenir')) ?></p>
        </div>
      <?php elseif (!\Adl\Core\Auth::check() && ($live['status'] ?? '') === 'open'): ?>
        <div class="side-card">
          <div class="side-kicker">Prestataire ?</div>
          <p>Connectez-vous pour envoyer un devis. Aucune commission sur la candidature.</p>
          <a class="btn-orange" href="<?= e(url('/connexion?next=' . rawurlencode((string) ($live['href'] ?? '/missions')))) ?>">Se connecter</a>
        </div>
      <?php elseif (\Adl\Core\Auth::check() && empty($isOwner) && empty($offersServices) && ($live['status'] ?? '') === 'open'): ?>
        <div class="side-card">
          <div class="side-kicker">Proposer vos services</div>
          <p>Pour candidater, activez « Je propose mes services » dans vos <a href="<?= e(url('/espace/parametres')) ?>">paramètres</a>.</p>
        </div>
      <?php endif; ?>
      <?php if (!empty($suggestions)): ?>
        <div class="side-card">
          <div class="side-title-sm">Prestataires qui correspondent</div>
          <?php foreach ($suggestions as $p): ?>
            <a class="suggest-row" href="<?= e(url((string) $p['href'])) ?>">
              <span class="avatar" style="<?= e(avatar_style((string) $p['initials'], 34)) ?>"><?= e((string) $p['initials']) ?></span>
              <span>
                <strong><?= e((string) $p['title']) ?></strong>
                <em><?= e((string) $p['subtitle']) ?></em>
              </span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </aside>
  </div>
</div>
