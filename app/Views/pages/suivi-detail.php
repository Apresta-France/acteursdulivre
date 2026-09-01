<?php
$order = $order ?? [];
$milestones = $milestones ?? [];
$action = is_array($action ?? null) ? $action : null;
$isBuyer = !empty($isBuyer);
$isSeller = !empty($isSeller);
$status = (string) ($order['status'] ?? 'pending');
$actionUrl = url('/espace/suivi/' . (int) ($order['id'] ?? 0) . '/jalon');
$quote = null;
$quoteDone = false;
foreach ($milestones as $row) {
    if (($row['code'] ?? '') === 'quote') {
        $quote = $row;
        $quoteDone = !empty($row['is_done']);
        break;
    }
}
$hiddenUntilQuote = ['deposit_invoice', 'deposit_paid', 'deposit_ack', 'final_invoice', 'final_paid'];
$visibleMilestones = [];
foreach ($milestones as $step) {
    if (!empty($step['is_skipped'])) {
        continue;
    }
    if (!$quoteDone && in_array((string) ($step['code'] ?? ''), $hiddenUntilQuote, true)) {
        continue;
    }
    $visibleMilestones[] = $step;
}
$form = (string) ($action['form'] ?? '');
$mine = !empty($action['mine']);
?>
<div class="espace-page jalon-page">
  <div class="espace-page-head">
    <div>
      <h1><?= e((string) ($order['title'] ?? 'Commande')) ?></h1>
      <p><?= e((string) ($order['num'] ?? '')) ?> · <?= e((string) ($order['by'] ?? '')) ?> · <?= e((string) ($order['amount_label'] ?? '')) ?></p>
    </div>
    <a class="btn-ghost jalon-msg" href="<?= e(url((string) ($threadHref ?? '/espace/messages'))) ?>">Ouvrir la messagerie</a>
  </div>

  <?php
    $suiviTab = 'jalons';
    require ADL_ROOT . '/app/Views/partials/suivi-tabs.php';
  ?>

  <?php if (!empty($error)): ?>
    <div class="flash flash-error"><?= e((string) $error) ?></div>
  <?php endif; ?>
  <?php if (!empty($saved)): ?>
    <div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div>
  <?php endif; ?>

  <?php if ($status === 'dispute'): ?>
    <div class="flash flash-error">Litige ouvert<?= !empty($order['dispute_reason']) ? ' : ' . e((string) $order['dispute_reason']) : '' ?>. Les jalons sont en pause le temps de la médiation.</div>
  <?php elseif ($status === 'cancelled'): ?>
    <div class="flash flash-warn">Cette commande est clôturée. Aucun jalon n’est plus attendu.</div>
  <?php endif; ?>

  <div class="publish-grid">
    <div>
      <?php if ($action && $status !== 'cancelled' && $status !== 'dispute'): ?>
        <section class="jalon-action<?= $mine ? ' is-mine' : ' is-wait' ?>">
          <div class="side-kicker"><?= $mine ? 'À faire maintenant' : 'En attente' ?></div>
          <h2><?= e((string) $action['title']) ?></h2>
          <p class="jalon-lead"><?= e((string) ($action['lead'] ?? '')) ?></p>

          <?php if ($form === 'waiting'): ?>
            <p class="jalon-hint">Tout se confirme ici. Le règlement d’argent se fait entre vous, hors de la plateforme.</p>
            <?php if ($isBuyer && !empty($order['can_cancel_order'])): ?>
              <div class="jalon-actions">
                <form method="post" action="<?= e(url('/espace/suivi/' . (int) $order['id'] . '/annuler')) ?>">
                  <?= csrf_field() ?>
                  <button class="btn-ghost btn-danger" type="submit" onclick="return confirm('Annuler définitivement cette commande ? Cette action est irréversible.');">Annuler la commande</button>
                </form>
              </div>
            <?php endif; ?>
          <?php elseif ($form === 'validate'): ?>
            <div class="jalon-actions">
              <a class="btn-orange" href="<?= e(url('/espace/avis')) ?>"><?= e((string) $action['cta']) ?></a>
            </div>
          <?php elseif ($form === 'quote'): ?>
            <?php if (!empty($action['revision'])): ?>
              <p class="jalon-hint">Le client a refusé le devis précédent. Les champs sont préremplis : ajustez votre proposition.</p>
            <?php endif; ?>
            <form class="jalon-form" method="post" action="<?= e($actionUrl) ?>" enctype="multipart/form-data">
              <?= csrf_field() ?>
              <input type="hidden" name="code" value="quote">
              <div class="jalon-fields">
                <div>
                  <label class="field" for="jalon-amount">Montant du devis (€)</label>
                  <input class="input" id="jalon-amount" name="amount" inputmode="decimal" required value="<?= e((string) ((int) ($order['amount'] ?? 0) ?: '')) ?>" placeholder="780">
                </div>
                <div>
                  <label class="field" for="jalon-deposit">Acompte (€)</label>
                  <input class="input" id="jalon-deposit" name="deposit_amount" inputmode="decimal" value="<?= e((string) ((int) ($order['deposit_amount'] ?? 0) ?: '')) ?>" placeholder="0 si aucun">
                  <p class="field-help">Souvent 30 %. Laissez vide s’il n’y a pas d’acompte.</p>
                </div>
              </div>
              <div>
                <label class="field" for="jalon-delay">Délai</label>
                <input class="input" id="jalon-delay" name="delay" value="<?= e((string) ($order['quote_form_delay'] ?? '')) ?>" placeholder="3 semaines">
              </div>
              <div>
                <label class="field" for="jalon-note">Précisions (périmètre, formats, allers-retours)</label>
                <textarea class="textarea" id="jalon-note" name="note" rows="8" placeholder="Ce qui est inclus, ce qui ne l’est pas…"><?= e((string) ($order['quote_form_note'] ?? '')) ?></textarea>
              </div>
              <div>
                <span class="field" id="jalon-doc-quote-label">Devis PDF (facultatif)</span>
                <?php
                  $filePickId = 'jalon-doc';
                  $filePickName = 'document';
                  $filePickAccept = '.pdf,.doc,.docx,.odt,image/jpeg,image/png,image/webp';
                  $filePickButton = 'Choisir un devis';
                  $filePickDrop = true;
                  $filePickAttrs = 'aria-labelledby="jalon-doc-quote-label"';
                  require ADL_ROOT . '/app/Views/partials/file-pick.php';
                ?>
                <?php if (!empty($quote['file_href'])): ?>
                  <p class="jalon-meta">Fichier actuel : <a class="file-chip" href="<?= e((string) $quote['file_href']) ?>"><?= e((string) ($quote['file_name'] ?? 'Devis')) ?></a></p>
                <?php endif; ?>
              </div>
              <div class="jalon-actions">
                <button class="btn-orange" type="submit"><?= e((string) $action['cta']) ?></button>
              </div>
            </form>
          <?php elseif ($form === 'quote_accept'): ?>
            <div class="jalon-recap">
              <div class="jalon-recap-row"><span>Montant</span><strong><?= e((string) ($order['amount_label'] ?? '')) ?></strong></div>
              <div class="jalon-recap-row"><span>Acompte</span><strong><?= (int) ($order['deposit_amount'] ?? 0) > 0 ? e((string) $order['deposit_label']) : 'Aucun' ?></strong></div>
              <?php if (!empty($order['quote_delay'])): ?>
                <div class="jalon-recap-row"><span>Délai</span><strong><?= e((string) $order['quote_delay']) ?></strong></div>
              <?php endif; ?>
            </div>
            <?php if (!empty($quote['note'])): ?>
              <p class="jalon-lead"><?= nl2br(e((string) $quote['note'])) ?></p>
            <?php endif; ?>
            <?php if (!empty($quote['file_href'])): ?>
              <p><a class="file-chip" href="<?= e((string) $quote['file_href']) ?>"><?= e((string) ($quote['file_name'] ?? 'Devis')) ?></a></p>
            <?php endif; ?>
            <div class="jalon-actions">
              <form method="post" action="<?= e($actionUrl) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="code" value="quote_accept">
                <button class="btn-orange" type="submit"><?= e((string) $action['cta']) ?></button>
              </form>
            </div>
            <div class="jalon-secondary">
              <p class="jalon-hint">Refuser le devis laisse la commande ouverte : le prestataire peut en proposer un autre. Annuler clôture définitivement le dossier.</p>
              <form class="jalon-form" method="post" action="<?= e(url('/espace/suivi/' . (int) $order['id'] . '/devis/refuser')) ?>">
                <?= csrf_field() ?>
                <div>
                  <label class="field" for="jalon-refuse-note">Message au prestataire (facultatif)</label>
                  <textarea class="textarea" id="jalon-refuse-note" name="note" rows="3" placeholder="Budget, délai, périmètre… il pourra ajuster sa proposition."></textarea>
                </div>
                <div class="jalon-actions">
                  <button class="btn-ghost" type="submit" onclick="return confirm('Refuser ce devis ? Le prestataire pourra en proposer un autre.');">Refuser le devis</button>
                </div>
              </form>
              <form method="post" action="<?= e(url('/espace/suivi/' . (int) $order['id'] . '/annuler')) ?>">
                <?= csrf_field() ?>
                <div class="jalon-actions">
                  <button class="btn-ghost btn-danger" type="submit" onclick="return confirm('Annuler définitivement cette commande ? Cette action est irréversible.');">Annuler la commande</button>
                </div>
              </form>
            </div>
          <?php elseif ($form === 'invoice'): ?>
            <form class="jalon-form" method="post" action="<?= e($actionUrl) ?>" enctype="multipart/form-data">
              <?= csrf_field() ?>
              <input type="hidden" name="code" value="<?= e((string) $action['code']) ?>">
              <p class="jalon-amount">Montant : <strong><?= e((string) ($action['amount_label'] ?? $order['amount_label'] ?? '')) ?></strong></p>
              <div>
                <label class="field" for="jalon-note">Message au client</label>
                <textarea class="textarea" id="jalon-note" name="note" rows="3" placeholder="IBAN, référence, échéance…"></textarea>
              </div>
              <div>
                <span class="field" id="jalon-doc-invoice-label">Facture (PDF ou image)</span>
                <?php
                  $filePickId = 'jalon-doc';
                  $filePickName = 'document';
                  $filePickAccept = '.pdf,.doc,.docx,.odt,image/jpeg,image/png,image/webp';
                  $filePickButton = 'Choisir une facture';
                  $filePickDrop = true;
                  $filePickAttrs = 'aria-labelledby="jalon-doc-invoice-label"';
                  require ADL_ROOT . '/app/Views/partials/file-pick.php';
                ?>
              </div>
              <div class="jalon-actions">
                <button class="btn-orange" type="submit"><?= e((string) $action['cta']) ?></button>
              </div>
            </form>
          <?php elseif ($form === 'confirm_pay'): ?>
            <p class="jalon-amount">À régler au prestataire : <strong><?= e((string) ($action['amount_label'] ?? '')) ?></strong></p>
            <form class="jalon-form" method="post" action="<?= e($actionUrl) ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="code" value="<?= e((string) $action['code']) ?>">
              <div>
                <label class="field" for="jalon-note">Référence du règlement (facultatif)</label>
                <input class="input" id="jalon-note" name="note" placeholder="Virement du 12 mars, réf. ADL-…">
              </div>
              <div class="jalon-actions">
                <button class="btn-orange" type="submit"><?= e((string) $action['cta']) ?></button>
              </div>
            </form>
          <?php elseif ($form === 'confirm'): ?>
            <form class="jalon-form" method="post" action="<?= e($actionUrl) ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="code" value="<?= e((string) $action['code']) ?>">
              <div class="jalon-actions">
                <button class="btn-orange" type="submit"><?= e((string) $action['cta']) ?></button>
              </div>
            </form>
          <?php elseif ($form === 'deliver'): ?>
            <form class="jalon-form" method="post" action="<?= e($actionUrl) ?>" enctype="multipart/form-data">
              <?= csrf_field() ?>
              <input type="hidden" name="code" value="deliver">
              <div>
                <label class="field" for="jalon-note">Note de livraison</label>
                <textarea class="textarea" id="jalon-note" name="note" rows="3" placeholder="Fichiers transmis, points d’attention…"></textarea>
              </div>
              <div>
                <span class="field" id="jalon-doc-deliver-label">Livrable (facultatif)</span>
                <?php
                  $filePickId = 'jalon-doc';
                  $filePickName = 'document';
                  $filePickAccept = '.pdf,.doc,.docx,.odt,image/jpeg,image/png,image/webp';
                  $filePickButton = 'Choisir un livrable';
                  $filePickDrop = true;
                  $filePickAttrs = 'aria-labelledby="jalon-doc-deliver-label"';
                  require ADL_ROOT . '/app/Views/partials/file-pick.php';
                ?>
              </div>
              <div class="jalon-actions">
                <button class="btn-orange" type="submit"><?= e((string) $action['cta']) ?></button>
              </div>
            </form>
          <?php elseif ($form === 'commission'): ?>
            <p class="jalon-amount">Commission : <strong><?= e((string) ($order['commission_label'] ?: $order['amount_label'] ?? '')) ?></strong></p>
            <form class="jalon-form" method="post" action="<?= e($actionUrl) ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="code" value="commission_paid">
              <div>
                <label class="field" for="jalon-note">Référence du règlement (facultatif)</label>
                <input class="input" id="jalon-note" name="note" placeholder="Virement du…">
              </div>
              <div class="jalon-actions">
                <button class="btn-orange" type="submit"><?= e((string) $action['cta']) ?></button>
                <a class="btn-ghost" href="<?= e(url('/espace/facturation')) ?>">Voir la facture</a>
              </div>
            </form>
          <?php endif; ?>
        </section>
      <?php endif; ?>

      <section class="jalon-timeline">
        <h2 class="espace-section-title">Jalons</h2>
        <ol class="jalon-list">
          <?php foreach ($visibleMilestones as $step): ?>
            <?php
              $state = 'is-todo';
              if (!empty($step['is_done'])) {
                  $state = 'is-done';
              } elseif (!empty($step['is_current'])) {
                  $state = 'is-current';
              }
            ?>
            <li class="jalon <?= $state ?>">
              <span class="jalon-dot" aria-hidden="true"></span>
              <div class="jalon-body">
                <div class="jalon-head">
                  <strong><?= e((string) $step['base_title']) ?></strong>
                  <span class="jalon-pill"><?= e((string) $step['actor_label']) ?> · <?= e((string) $step['status_label']) ?></span>
                </div>
                <?php if (!empty($step['amount_label'])): ?>
                  <p class="jalon-meta"><?= e((string) $step['amount_label']) ?><?= !empty($step['delay']) ? ' · ' . e((string) $step['delay']) : '' ?></p>
                <?php endif; ?>
                <?php if (!empty($step['note'])): ?>
                  <p class="jalon-meta"><?= nl2br(e((string) $step['note'])) ?></p>
                <?php endif; ?>
                <?php if (!empty($step['file_href'])): ?>
                  <p><a class="file-chip" href="<?= e((string) $step['file_href']) ?>"><?= e((string) ($step['file_name'] ?? 'Document')) ?></a></p>
                <?php endif; ?>
                <?php if (!empty($step['when'])): ?>
                  <p class="jalon-meta"><?= e((string) $step['when']) ?></p>
                <?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
          <?php if (!$quoteDone): ?>
            <li class="jalon is-hint">
              <span class="jalon-dot" aria-hidden="true"></span>
              <div class="jalon-body">
                <strong>Acompte et solde</strong>
                <p class="jalon-meta">Ces étapes s’ajoutent automatiquement si le devis prévoit un acompte ou un reste à payer.</p>
              </div>
            </li>
          <?php endif; ?>
        </ol>
      </section>

      <?php if (!empty($order['brief'])): ?>
        <div class="jalon-brief">
          <div class="side-kicker">Brief</div>
          <p><?= nl2br(e((string) $order['brief'])) ?></p>
        </div>
      <?php endif; ?>

      <?php if (!empty($order['package_name']) || !empty($order['options'])): ?>
        <div class="jalon-brief">
          <div class="side-kicker">Formule et options</div>
          <?php if (!empty($order['package_name'])): ?>
            <p class="jalon-meta">Formule : <?= e((string) $order['package_name']) ?></p>
          <?php endif; ?>
          <?php foreach ($order['options'] ?? [] as $option): ?>
            <p class="jalon-meta"><?= e((string) ($option['name'] ?? '')) ?> · +<?= e((string) ($option['price_label'] ?? format_euros((int) ($option['price'] ?? 0)))) ?></p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($order['can_dispute'])): ?>
        <form class="jalon-dispute" method="post" action="<?= e(url('/espace/suivi/' . (int) $order['id'] . '/litige')) ?>">
          <?= csrf_field() ?>
          <label class="field" for="reason">Signaler un litige</label>
          <textarea class="textarea" id="reason" name="reason" rows="3" required placeholder="Décrivez le désaccord. Les jalons sont mis en pause ; l’équipe reprend le dossier."></textarea>
          <div class="jalon-actions">
            <button class="btn-ghost" type="submit">Ouvrir un litige</button>
          </div>
        </form>
      <?php endif; ?>
    </div>

    <aside class="publish-side">
      <div class="jalon-aside">
        <div class="side-kicker">Règlements</div>
        <p>Client et prestataire se règlent <strong>entre eux</strong>. La plateforme n’encaisse rien : elle suit les jalons, puis facture sa commission au prestataire à la validation.</p>
        <div class="jalon-recap" data-quote-recap>
          <div class="jalon-recap-row"><span>Mission</span><strong data-quote-recap-amount><?= e((string) ($order['amount_label'] ?? '')) ?></strong></div>
          <div class="jalon-recap-row"><span>Acompte</span><strong data-quote-recap-deposit><?= (int) ($order['deposit_amount'] ?? 0) > 0 ? e((string) $order['deposit_label']) : '—' ?></strong></div>
          <div class="jalon-recap-row"><span>Solde</span><strong data-quote-recap-balance><?= e((string) ($order['balance_label'] ?? '—')) ?></strong></div>
          <?php if (!empty($order['commission_label'])): ?>
            <div class="jalon-recap-row"><span>Commission</span><strong><?= e((string) $order['commission_label']) ?></strong></div>
          <?php endif; ?>
        </div>
      </div>
    </aside>
  </div>
</div>
