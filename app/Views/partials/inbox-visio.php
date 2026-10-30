<?php
$visio = is_array($visio ?? null) ? $visio : [];
if (empty($visio['show'])) {
    return;
}
$mode = (string) ($visio['mode'] ?? 'idle');
$slots = is_array($visio['slots'] ?? null) ? $visio['slots'] : [];
$respondUrl = trim((string) ($visio['respondUrl'] ?? ''));
$proposeUrl = trim((string) ($visio['proposeUrl'] ?? ''));
$withName = trim((string) ($visio['withName'] ?? ''));
$meetUrl = trim((string) ($visio['meetUrl'] ?? ''));
$chosen = trim((string) ($visio['chosenLabel'] ?? ''));
$reason = trim((string) ($visio['reason'] ?? ''));
$hint = trim((string) ($visio['hint'] ?? ''));
$selected = (int) ($visio['selected'] ?? 0);
$intention = (string) ($visio['intention'] ?? '');
$old = is_array($visio['old'] ?? null) ? $visio['old'] : [];
$panel = !in_array($mode, ['propose', 'idle'], true);
$future = 0;
foreach ($slots as $slot) {
    if (empty($slot['past'])) {
        $future++;
    }
}
?>
<section class="inbox-visio<?= $panel ? ' is-panel' : '' ?>" id="visio">
  <?php if ($mode === 'pending-provider'): ?>
    <h2>Visio proposée</h2>
    <p>En attente de <?= e($withName !== '' ? $withName : 'votre client') ?>. Un e-mail lui a été envoyé pour choisir un créneau.</p>
    <ul class="visio-slot-list">
      <?php foreach ($slots as $slot): ?>
        <li><?= e((string) ($slot['label'] ?? '')) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php elseif ($mode === 'provider-locked'): ?>
    <h2>Proposition envoyée</h2>
    <p>Ce lien est destiné à votre client. Vous serez prévenu dès qu’un créneau est choisi.</p>
    <ul class="visio-slot-list">
      <?php foreach ($slots as $slot): ?>
        <li><?= e((string) ($slot['label'] ?? '')) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php elseif ($mode === 'pending-client'): ?>
    <h2>Proposition de visio</h2>
    <p><?= e($withName !== '' ? $withName : 'Votre prestataire') ?> vous propose un rendez-vous sur kMeet. Choisissez l’un des trois créneaux, dites qu’aucun ne convient, ou refusez.</p>
    <?php if ($future > 0 && $respondUrl !== ''): ?>
      <form method="post" action="<?= e(url($respondUrl)) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="accept">
        <fieldset class="visio-choices">
          <legend class="field">Créneaux proposés</legend>
          <?php foreach ($slots as $slot): ?>
            <?php
              $n = (int) ($slot['n'] ?? 0);
              $past = !empty($slot['past']);
              $checked = !$past && $selected === $n;
            ?>
            <label class="visio-slot<?= $past ? ' is-past' : '' ?>">
              <input type="radio" name="slot" value="<?= $n ?>" <?= $past ? 'disabled' : 'required' ?> <?= $checked ? 'checked' : '' ?>>
              <span><?= e((string) ($slot['label'] ?? '')) ?><?= $past ? ' — passé' : '' ?></span>
            </label>
          <?php endforeach; ?>
        </fieldset>
        <button class="btn-orange" type="submit">Valider ce créneau</button>
      </form>
    <?php else: ?>
      <p>Ces créneaux sont passés. Indiquez qu’aucun ne convient, ou refusez la visio.</p>
    <?php endif; ?>
    <?php if ($respondUrl !== ''): ?>
      <form class="visio-alt<?= $intention === 'aucun' ? ' is-focus' : '' ?>" id="aucun" method="post" action="<?= e(url($respondUrl)) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="none">
        <button class="btn-ghost" type="submit">Aucun créneau ne me convient</button>
      </form>
      <form class="visio-refuse<?= $intention === 'refuser' ? ' is-focus' : '' ?>" id="refuser" method="post" action="<?= e(url($respondUrl)) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="refuse">
        <label class="field" for="visio-reason">Motif du refus</label>
        <textarea class="textarea" id="visio-reason" name="reason" rows="3" required minlength="10" maxlength="1000" placeholder="Dites pourquoi une visio ne vous convient pas." <?= $intention === 'refuser' ? 'autofocus' : '' ?>></textarea>
        <button class="btn-ghost" type="submit">Refuser la visio</button>
      </form>
    <?php endif; ?>
  <?php elseif ($mode === 'accepted' && $meetUrl !== ''): ?>
    <h2>Visio prévue</h2>
    <p>
      <?= e($chosen !== '' ? $chosen : 'Créneau confirmé') ?><?= $withName !== '' ? ' · avec ' . e($withName) : '' ?>.
      Le rendez-vous se tient sur kMeet.
    </p>
    <a class="btn-orange" href="<?= e($meetUrl) ?>" target="_blank" rel="noopener noreferrer">Rejoindre la visio</a>
    <p class="visio-url"><?= e($meetUrl) ?></p>
  <?php elseif ($mode === 'none-provider'): ?>
    <h2>Aucun créneau ne convient</h2>
    <p><?= e($withName !== '' ? $withName : 'Le client') ?> indique qu’aucun des trois créneaux ne convient. Proposez-en d’autres.</p>
  <?php elseif ($mode === 'none-client'): ?>
    <h2>Aucun créneau retenu</h2>
    <p>Vous avez indiqué qu’aucun créneau ne vous convenait. <?= e($withName !== '' ? $withName : 'Le prestataire') ?> peut vous en proposer d’autres.</p>
  <?php elseif ($mode === 'refused-provider'): ?>
    <h2>Visio refusée</h2>
    <p><?= e($withName !== '' ? $withName : 'Le client') ?> a refusé la visio.</p>
    <?php if ($reason !== ''): ?>
      <blockquote class="visio-reason"><?= nl2br(e($reason)) ?></blockquote>
    <?php endif; ?>
  <?php elseif ($mode === 'refused-client'): ?>
    <h2>Visio refusée</h2>
    <p>Vous avez refusé la visio. Le prestataire en est informé.</p>
    <?php if ($reason !== ''): ?>
      <blockquote class="visio-reason"><?= nl2br(e($reason)) ?></blockquote>
    <?php endif; ?>
  <?php endif; ?>

  <?php if ($hint !== ''): ?>
    <p class="visio-hint"><?= e($hint) ?></p>
  <?php endif; ?>

  <?php if (!empty($visio['canPropose']) && $proposeUrl !== ''): ?>
    <details class="inbox-visio-form" id="proposer-visio" <?= !empty($visio['openForm']) ? 'open' : '' ?>>
      <summary class="btn-ghost">Proposer une visio</summary>
      <form method="post" action="<?= e(url($proposeUrl)) ?>">
        <?= csrf_field() ?>
        <p>Trois dates et heures à venir, heure de Paris. Le client reçoit un e-mail pour en retenir une. La visio se tient sur kMeet.</p>
        <div class="visio-when">
          <?php foreach ([1, 2, 3] as $n): ?>
            <div>
              <label class="field" for="visio-slot-<?= $n ?>">Créneau <?= $n ?></label>
              <input class="input" id="visio-slot-<?= $n ?>" type="datetime-local" name="slot<?= $n ?>" required
                     step="900"
                     min="<?= e((string) ($visio['min'] ?? '')) ?>"
                     max="<?= e((string) ($visio['max'] ?? '')) ?>"
                     value="<?= e((string) ($old['slot' . $n] ?? '')) ?>">
            </div>
          <?php endforeach; ?>
        </div>
        <button class="btn-orange" type="submit">Envoyer les trois créneaux</button>
      </form>
    </details>
  <?php endif; ?>
</section>
