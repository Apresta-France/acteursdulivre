<?php
$live = $liveMission ?? null;
if (!$live) {
    not_found('Cette recherche n\'est plus disponible.');
}
$trade = (string) ($live['category_name'] ?? '');
$tradeHref = $trade !== '' ? '/missions?metier=' . rawurlencode($trade) : '/missions';
$applicants = (int) ($live['applicants'] ?? 0);
$isDraft = ($live['status'] ?? '') === 'draft';
$whenBits = [($isDraft ? 'enregistrée' : 'publiée') . ' ' . (string) ($live['when'] ?? '')];
if (!empty($isOwner) || $applicants > 0) {
    $whenBits[] = $applicants === 0
        ? 'aucune candidature'
        : ($applicants === 1 ? '1 candidature' : $applicants . ' candidatures');
}
$volumeLabel = (string) ($live['volume_label'] ?? 'Volume');
$volumeValue = (string) ($live['volume_display'] ?? $live['volume'] ?? '');
$loginNext = rawurlencode((string) ($live['href'] ?? '/missions'));
$ownerCity = trim((string) ($ownerCity ?? ''));
$attachmentName = trim((string) ($live['attachment_name'] ?? 'Document'));
$attachmentExt = strtoupper((string) pathinfo($attachmentName, PATHINFO_EXTENSION));
?>
<div class="mission-page">
  <nav class="search-crumb" aria-label="Fil d'Ariane">
    <a href="<?= e(url('/missions')) ?>">Appels d'offres</a>
    · <?php if ($trade !== ''): ?><a href="<?= e(url($tradeHref)) ?>"><?= e($trade) ?></a><?php else: ?>Recherche<?php endif; ?>
  </nav>
  <div class="publish-grid">
    <div>
      <div class="mission-row-title" style="margin-bottom: 10px;">
        <span class="status-pill status-<?= e((string) $live['status']) ?>"><?= e((string) $live['status_label']) ?></span>
        <span class="mission-row-sub"><?= e(implode(' · ', $whenBits)) ?></span>
        <?php if (!empty($isOwner) && in_array((string) ($live['status'] ?? ''), ['draft', 'open'], true)): ?>
          <a class="btn-ghost" href="<?= e(url('/espace/publier/' . (int) $live['id'])) ?>">Modifier</a>
        <?php endif; ?>
        <?php if (!empty($isOwner) && !empty($live['can_delete'])): ?>
          <?php
            $deleteConfirm = $isDraft
                ? 'Supprimer ce brouillon ? Cette action est irréversible.'
                : 'Supprimer cette recherche ? Elle disparaîtra des appels d’offres. Les candidatures en attente seront clôturées.';
          ?>
          <form method="post" action="<?= e(url('/espace/missions/' . (int) $live['id'] . '/supprimer')) ?>" onsubmit="return confirm(<?= json_encode($deleteConfirm, JSON_UNESCAPED_UNICODE) ?>);">
            <?= csrf_field() ?>
            <button class="btn-ghost btn-danger" type="submit">Supprimer</button>
          </form>
        <?php endif; ?>
      </div>
      <h1 class="mission-title"><?= e((string) $live['title']) ?></h1>
      <div class="facts">
        <div>
          <span>Métier</span>
          <strong><?php if ($trade !== ''): ?><a href="<?= e(url($tradeHref)) ?>"><?= e($trade) ?></a><?php else: ?>—<?php endif; ?></strong>
        </div>
        <div><span>Budget</span><strong><?= e((string) $live['budget']) ?></strong></div>
        <?php if ($volumeValue !== ''): ?>
          <div><span><?= e($volumeLabel) ?></span><strong><?= e($volumeValue) ?></strong></div>
        <?php endif; ?>
        <div><span>Échéance</span><strong><?= e((string) $live['deadline_label']) ?></strong></div>
      </div>
      <h2>Le besoin</h2>
      <p class="profile-text"><?= nl2br(e((string) ($live['brief'] ?? ''))) ?></p>
      <?php if (!empty($live['attachment_href'])): ?>
        <h2>Pièce jointe</h2>
        <a class="file-chip" href="<?= e((string) $live['attachment_href']) ?>">
          <span class="file-chip-ext"><?= e($attachmentExt !== '' ? $attachmentExt : 'DOC') ?></span>
          <span class="file-chip-name"><?= e($attachmentName) ?></span>
        </a>
      <?php elseif (!empty($live['attachment_locked'])): ?>
        <h2>Pièce jointe</h2>
        <p class="mission-row-sub">Un document est joint à cette recherche. Il est accessible au porteur de projet et aux prestataires qui ont candidaté.</p>
      <?php endif; ?>

      <div class="mission-share">
        <?php
          $shareUrl = $meta['url'] ?? \Adl\Data\Share::current();
          $shareTitle = $meta['title'] ?? (string) ($live['title'] ?? 'Recherche');
          $shareText = $meta['description'] ?? (string) ($live['brief'] ?? '');
          $shareLabel = 'Partager';
          $shareCompact = true;
          $shareNative = false;
          require ADL_ROOT . '/app/Views/partials/share.php';
        ?>
      </div>

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
            <em><?= $ownerCity !== '' ? e($ownerCity) : 'Recherche publiée sur la plateforme' ?></em>
          </span>
        </div>
      </div>
      <?php if (!empty($canApply)): ?>
        <div class="side-card" id="candidater">
          <div class="side-kicker">Proposer vos services</div>
          <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>
          <form method="post" action="<?= e(url('/missions/' . ($live['slug'] ?? '') . '/candidater')) ?>">
            <?= csrf_field() ?>
            <div class="form-grid-2">
              <div>
                <label class="field" for="price">Votre tarif (€)</label>
                <input class="input" id="price" name="price" inputmode="numeric" value="<?= e((string) ($old['price'] ?? '')) ?>" placeholder="780">
              </div>
              <div>
                <label class="field" for="delay">Délai</label>
                <input class="input" id="delay" name="delay" value="<?= e((string) ($old['delay'] ?? '')) ?>" placeholder="12 jours">
              </div>
            </div>
            <label class="field" for="message" style="margin-top: 12px;">Votre approche</label>
            <textarea class="textarea" id="message" name="message" rows="5" required placeholder="Ce que vous proposez, le calendrier, les questions."><?= e((string) ($old['message'] ?? '')) ?></textarea>
            <p class="field-help">Gratuit · aucune commission sur la candidature.</p>
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
          <p>Envoyez un devis — gratuit, sans commission à la candidature.</p>
          <div class="auth-actions">
            <a class="btn-orange" href="<?= e(url('/connexion?next=' . $loginNext)) ?>">Se connecter</a>
            <a class="btn-ghost" href="<?= e(url('/inscription?next=' . $loginNext)) ?>">Créer un compte</a>
          </div>
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
      <?php if (empty($isOwner)): ?>
        <details class="profile-report">
          <summary>Signaler cette recherche</summary>
          <form method="post" action="<?= e(url('/signaler')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="type" value="mission">
            <input type="hidden" name="id" value="<?= (int) ($live['id'] ?? 0) ?>">
            <input type="hidden" name="back" value="<?= e((string) ($live['href'] ?? '/missions')) ?>">
            <label class="field" for="report-reason">Motif</label>
            <select class="input" id="report-reason" name="reason" required>
              <?php foreach (\Adl\Models\Report::REASONS as $value => $label): ?>
                <option value="<?= e($value) ?>"><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
            <textarea class="textarea" name="body" rows="2" placeholder="Précision (optionnel)"></textarea>
            <button class="btn-ghost" type="submit">Envoyer le signalement</button>
          </form>
        </details>
      <?php endif; ?>
    </aside>
  </div>
  <?php if (!empty($relatedMissions)): ?>
    <section class="mission-related-block">
      <h2>Autres recherches</h2>
      <div class="mission-related">
        <?php foreach ($relatedMissions as $rel): ?>
          <a class="mission-related-item" href="<?= e(url((string) $rel['href'])) ?>">
            <strong><?= e((string) $rel['title']) ?></strong>
            <span><?= e(trim((string) ($rel['cat'] ?? '') . ' · ' . (string) ($rel['price'] ?? ''))) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</div>
