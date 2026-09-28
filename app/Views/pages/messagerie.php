<?php
$threads = $threads ?? [];
$thread = $thread ?? null;
$messages = $messages ?? [];
$quoteHref = trim((string) ($quoteHref ?? ''));
$quoteStart = is_array($quoteStart ?? null) ? $quoteStart : null;
$quoteWait = trim((string) ($quoteWait ?? ''));
$quoteOld = is_array($quoteOld ?? null) ? $quoteOld : [];
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
  <?php if (!empty($completeProfile)): ?>
    <div class="dash-onboard ecrire-complete">
      <div>
        <strong>Votre message est parti.</strong>
        <em>Pour la suite, quelques informations aident le prestataire à vous répondre dans de bonnes conditions. Vous pouvez aussi le faire plus tard.</em>
      </div>
      <div class="ecrire-complete-actions">
        <a class="btn-orange" href="<?= e(url('/espace/bienvenue')) ?>">Compléter mes infos</a>
        <a class="btn-ghost" href="<?= e(url(!empty($thread['id']) ? '/espace/messages/' . (int) $thread['id'] . '?plus-tard=1' : '/espace/messages?plus-tard=1')) ?>">Plus tard</a>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($threads === [] && !$thread): ?>
    <div class="search-empty">
      <strong>Aucun message pour le moment.</strong>
      <span>Demandez un devis depuis une vitrine, ou écrivez au porteur d'une recherche.</span>
    </div>
  <?php else: ?>
    <div class="inbox<?= $thread ? '' : ' is-list-only' ?>">
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
            <a class="inbox-back" href="<?= e(url('/espace/messages')) ?>">← Conversations</a>
            <div class="inbox-thread-who">
              <?= avatar_html($thread['other'] ?? [], 40) ?>
              <div>
                <strong><?= e((string) ($thread['other']['name'] ?? 'Conversation')) ?></strong>
                <em><?= e((string) ($thread['subject'] ?? '')) ?></em>
              </div>
            </div>
            <div class="inbox-thread-actions">
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
          </div>
          <?php if ($quoteWait !== ''): ?>
            <p class="inbox-quote-wait"><?= e($quoteWait) ?> peut envoyer un devis depuis cette conversation. La commande s’ouvre alors, et vous pourrez accepter ce devis dans le suivi.</p>
          <?php endif; ?>
          <?php if ($quoteStart): ?>
            <?php
              $quoteTitle = trim((string) ($quoteOld['title'] ?? $quoteStart['title'] ?? ''));
              $quoteAmount = trim((string) ($quoteOld['amount'] ?? ''));
              $quoteDeposit = trim((string) ($quoteOld['deposit_amount'] ?? ''));
              $quoteDelay = trim((string) ($quoteOld['delay'] ?? ''));
              $quoteNote = (string) ($quoteOld['note'] ?? '');
              $quoteBuyer = (string) ($quoteStart['buyerName'] ?? 'le porteur de projet');
            ?>
            <section class="inbox-quote" id="creer-devis">
              <h2>Créer un devis</h2>
              <p class="jalon-lead"><?= e($quoteBuyer) ?> pourra accepter ce devis dans le suivi. Cela ouvre la commande : le règlement se fait entre vous, hors de la plateforme.</p>
              <form class="jalon-form" method="post" action="<?= e(url('/espace/messages/' . (int) $thread['id'] . '/devis')) ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <?php if (!empty($quoteStart['titleLocked'])): ?>
                  <p class="jalon-amount">Mission : <strong><?= e((string) $quoteStart['title']) ?></strong></p>
                <?php else: ?>
                  <div>
                    <label class="field" for="inbox-quote-title">Intitulé</label>
                    <input class="input" id="inbox-quote-title" name="title" required maxlength="80" value="<?= e($quoteTitle) ?>" placeholder="Correction du roman, 300 pages">
                  </div>
                <?php endif; ?>
                <div class="jalon-fields">
                  <div>
                    <label class="field" for="jalon-amount">Montant du devis (€)</label>
                    <input class="input" id="jalon-amount" name="amount" inputmode="decimal" required value="<?= e($quoteAmount) ?>" placeholder="780">
                  </div>
                  <div>
                    <label class="field" for="jalon-deposit"><?= e((string) ($quoteStart['depositLabel'] ?? 'Acompte (€)')) ?></label>
                    <input class="input" id="jalon-deposit" name="deposit_amount" inputmode="decimal"
                           value="<?= e($quoteDeposit) ?>"
                           placeholder="0 si aucun"
                           <?php if (!empty($quoteStart['startupOn'])): ?>
                           data-startup-kind="<?= e((string) ($quoteStart['startupKind'] ?? 'amount')) ?>"
                           data-startup-value="<?= (int) ($quoteStart['startupValue'] ?? 0) ?>"
                           <?php endif; ?>>
                    <p class="field-help"><?= e((string) ($quoteStart['depositHelp'] ?? '')) ?></p>
                  </div>
                </div>
                <div>
                  <label class="field" for="inbox-quote-delay">Délai</label>
                  <input class="input" id="inbox-quote-delay" name="delay" maxlength="80" value="<?= e($quoteDelay) ?>" placeholder="3 semaines">
                </div>
                <div>
                  <label class="field" for="inbox-quote-note">Précisions (périmètre, formats, allers-retours)</label>
                  <textarea class="textarea" id="inbox-quote-note" name="note" rows="5" maxlength="4000" placeholder="Ce qui est inclus, ce qui ne l’est pas…"><?= e($quoteNote) ?></textarea>
                </div>
                <div>
                  <span class="field" id="inbox-quote-doc-label">Devis PDF (facultatif)</span>
                  <?php
                    $filePickId = 'inbox-quote-doc';
                    $filePickName = 'document';
                    $filePickAccept = '.pdf,.doc,.docx,.odt,image/jpeg,image/png,image/webp';
                    $filePickButton = 'Choisir un devis';
                    $filePickDrop = true;
                    $filePickAttrs = 'aria-labelledby="inbox-quote-doc-label"';
                    require ADL_ROOT . '/app/Views/partials/file-pick.php';
                  ?>
                </div>
                <div class="jalon-recap" data-quote-recap>
                  <div class="jalon-recap-row"><span>Mission</span><strong data-quote-recap-amount>—</strong></div>
                  <div class="jalon-recap-row"><span><?= !empty($quoteStart['startupOn']) ? 'Accompagnement' : 'Acompte' ?></span><strong data-quote-recap-deposit>—</strong></div>
                  <div class="jalon-recap-row"><span>Solde</span><strong data-quote-recap-balance>—</strong></div>
                </div>
                <div class="jalon-actions">
                  <button class="btn-orange" type="submit">Envoyer le devis</button>
                </div>
              </form>
            </section>
          <?php endif; ?>
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
            <?= form_guard_fields('message') ?>
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
