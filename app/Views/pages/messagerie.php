<?php
$threads = $threads ?? [];
$thread = $thread ?? null;
$messages = $messages ?? [];
$quoteHref = trim((string) ($quoteHref ?? ''));
$alreadyReported = !empty($alreadyReported);
?>
<div class="espace-page">
  <div class="espace-page-head">
    <div>
      <h1>Messagerie</h1>
      <p>Les échanges autour des recherches, des devis et des commandes. Vous pouvez joindre un fichier (PDF, image, Word, 8&nbsp;Mo max.) et signaler une conversation qui sort du cadre.</p>
    </div>
  </div>

  <?php if (!empty($error)): ?>
    <div class="flash flash-error"><?= e((string) $error) ?></div>
  <?php endif; ?>
  <?php if (!empty($saved)): ?>
    <div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Envoyé.') ?></div>
  <?php endif; ?>

  <?php if ($threads === [] && !$thread): ?>
    <div class="search-empty">
      <strong>Aucun message pour le moment.</strong>
      <span>Demandez un devis depuis une vitrine, ou écrivez au porteur d'une recherche.</span>
    </div>
  <?php else: ?>
    <div class="inbox">
      <aside class="inbox-list">
        <?php foreach ($threads as $item): ?>
          <a class="inbox-item<?= !empty($thread) && (int) $item['id'] === (int) $thread['id'] ? ' is-on' : '' ?><?= !empty($item['unread']) ? ' is-unread' : '' ?>" href="<?= e(url((string) $item['href'])) ?>">
            <?= avatar_html($item['other'] ?? [], 36) ?>
            <span>
              <strong><?= e((string) ($item['other']['name'] ?? $item['subject'])) ?></strong>
              <em data-inbox-preview><?= e(mb_strimwidth((string) ($item['preview'] ?? ''), 0, 70, '…')) ?></em>
            </span>
            <small<?= !empty($item['created_iso']) ? ' data-time-ago="' . e((string) $item['created_iso']) . '"' : '' ?>><?= e((string) ($item['when'] ?? '')) ?></small>
          </a>
        <?php endforeach; ?>
      </aside>
      <div class="inbox-thread">
        <?php if (!$thread): ?>
          <div class="search-empty">
            <strong>Choisissez une conversation.</strong>
          </div>
        <?php else: ?>
          <div class="inbox-thread-head">
            <div class="inbox-thread-who">
              <?= avatar_html($thread['other'] ?? [], 40) ?>
              <div>
                <strong><?= e((string) ($thread['other']['name'] ?? 'Conversation')) ?></strong>
                <em><?= e((string) ($thread['subject'] ?? '')) ?></em>
              </div>
            </div>
            <?php if ($alreadyReported): ?>
              <p class="inbox-report-done">Signalement envoyé</p>
            <?php else: ?>
              <details class="inbox-report">
                <summary>Signaler</summary>
                <form method="post" action="<?= e(url('/espace/messages/' . (int) $thread['id'] . '/signaler')) ?>">
                  <?= csrf_field() ?>
                  <p>Signalez un contournement, des propos abusifs ou une usurpation. L'équipe lit la conversation.</p>
                  <label class="field" for="inbox-report-reason">Motif</label>
                  <select class="input" id="inbox-report-reason" name="reason" required>
                    <?php foreach (\Adl\Models\Report::reasonsFor('conversation') as $value => $label): ?>
                      <option value="<?= e($value) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <label class="field" for="inbox-report-body">Précision</label>
                  <textarea class="textarea" id="inbox-report-body" name="body" rows="3" placeholder="Faits observés, extraits concernés…"></textarea>
                  <button class="btn-ghost" type="submit">Envoyer le signalement</button>
                </form>
              </details>
            <?php endif; ?>
          </div>
          <?php
            $inboxUserId = (int) (\Adl\Core\Auth::id() ?? 0);
            $inboxLastId = 0;
            foreach ($messages as $msg) {
                $inboxLastId = max($inboxLastId, (int) ($msg['id'] ?? 0));
            }
          ?>
          <div
            class="inbox-messages"
            data-inbox-thread
            data-sync="<?= e(url('/espace/messages/' . (int) $thread['id'] . '/sync')) ?>"
            data-last-id="<?= $inboxLastId ?>"
          >
            <?php foreach ($messages as $msg): ?>
              <?= inbox_message_html($msg, $inboxUserId) ?>
            <?php endforeach; ?>
          </div>
          <form class="inbox-compose" method="post" action="<?= e(url('/espace/messages/' . (int) $thread['id'])) ?>" enctype="multipart/form-data" data-dropzone>
            <?= csrf_field() ?>
            <textarea class="textarea" name="body" rows="3" placeholder="Votre message…"></textarea>
            <label class="dropzone" data-dropzone-zone>
              <input class="file-pick-input" type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.webp,.txt,.doc,.docx,.odt">
              <span class="btn-ghost file-pick-btn">Joindre un fichier</span>
              <span data-dropzone-label>Glissez-déposez ou choisissez depuis votre ordinateur</span>
            </label>
            <div class="inbox-compose-actions">
              <button class="btn-orange" type="submit">Envoyer</button>
              <?php if ($quoteHref !== ''): ?>
                <a class="btn-ghost" href="<?= e(url($quoteHref)) ?>">Gérer le devis</a>
              <?php endif; ?>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>
