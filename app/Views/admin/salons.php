<?php
$proposals = $proposals ?? [];
$pendingCount = (int) ($pendingCount ?? 0);
$filters = $filters ?? [];
$categories = $categories ?? [];
$back = '/admin/salons';
?>
<div class="admin-page">
  <div class="admin-page-head">
    <div>
      <h1>Agenda des salons</h1>
      <p class="admin-lead" style="margin-bottom: 0;">Propositions envoyées depuis le site. Une fiche validée rejoint l’agenda public ; le demandeur est prévenu par e-mail.</p>
    </div>
  </div>

  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <div class="me-admin-kpis">
    <div class="admin-card me-admin-kpi<?= $pendingCount > 0 ? ' is-hot' : '' ?>"><strong><?= $pendingCount ?></strong><span>proposition<?= $pendingCount > 1 ? 's' : '' ?> à traiter</span></div>
  </div>

  <div class="chip-row" style="margin-bottom: 18px;">
    <?php foreach ($filters as $f): ?>
      <a class="chip<?= !empty($f['on']) ? ' is-on' : '' ?>" href="<?= e(url($f['href'])) ?>"><?= e($f['label']) ?></a>
    <?php endforeach; ?>
  </div>

  <?php if ($proposals === []): ?>
    <p class="admin-muted">Aucune proposition pour ce filtre.</p>
  <?php endif; ?>

  <div class="admin-stack">
    <?php foreach ($proposals as $c):
        $pending = ($c['status'] ?? '') === 'pending';
        $tone = $c['status'] === 'approved' ? 'green' : ($c['status'] === 'refused' ? 'orange' : 'navy');
        $similar = $c['similar'] ?? [];
        $salon = $c['salon'] ?? null;
        $pid = (int) $c['id'];
        ?>
      <article class="admin-card admin-dossier me-claim-card">
        <div class="admin-dossier-who">
          <?= avatar_html(['avatar_url' => $c['avatar_url'] ?? '', 'first_name' => $c['first_name'] ?? ($c['contact_name'] ?? ''), 'last_name' => $c['last_name'] ?? ''], 40) ?>
          <div>
            <strong><?= e((string) $c['who']) ?> <span class="me-claim-arrow">propose</span> <?= e((string) $c['name']) ?></strong>
            <span><?= e((string) $c['contact_email']) ?><?= !empty($c['user_email']) && $c['user_email'] !== $c['contact_email'] ? ' · compte ' . e((string) $c['user_email']) : '' ?></span>
            <em>Demande du <?= e((string) $c['when']) ?><?= $c['decided_label'] !== '' ? ' · traitée le ' . e((string) $c['decided_label']) : '' ?></em>
          </div>
          <span class="admin-pill tone-<?= e($tone) ?>"><?= e((string) $c['status_label']) ?></span>
        </div>

        <?php if ($pending): ?>
          <form method="post" action="<?= e(url('/admin/salons/' . $pid)) ?>" class="me-add-form" style="margin-top: 14px;">
            <?= csrf_field() ?>
            <input type="hidden" name="status" value="approved">
            <input type="hidden" name="back" value="<?= e($back) ?>">
            <div class="form-grid-2">
              <div>
                <label class="field" for="name-<?= $pid ?>">Nom</label>
                <input class="input" id="name-<?= $pid ?>" name="name" required value="<?= e((string) $c['name']) ?>">
              </div>
              <div>
                <label class="field" for="category-<?= $pid ?>">Catégorie</label>
                <select class="input" id="category-<?= $pid ?>" name="category">
                  <option value="">À préciser</option>
                  <?php foreach ($categories as $cat): ?>
                    <option value="<?= e((string) $cat) ?>"<?= (string) $cat === (string) ($c['category'] ?? '') ? ' selected' : '' ?>><?= e((string) $cat) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="form-grid-3">
              <div>
                <label class="field" for="city-<?= $pid ?>">Ville</label>
                <input class="input" id="city-<?= $pid ?>" name="city" required value="<?= e((string) ($c['city'] ?? '')) ?>">
              </div>
              <div>
                <label class="field" for="region-<?= $pid ?>">Région</label>
                <input class="input" id="region-<?= $pid ?>" name="region" value="<?= e((string) ($c['region'] ?? '')) ?>">
              </div>
              <div>
                <label class="field" for="country-<?= $pid ?>">Pays</label>
                <input class="input" id="country-<?= $pid ?>" name="country" value="<?= e((string) ($c['country'] ?? 'France')) ?>">
              </div>
            </div>
            <div class="form-grid-2">
              <div>
                <label class="field" for="starts-<?= $pid ?>">Début</label>
                <input class="input" id="starts-<?= $pid ?>" name="starts_on" type="date" required value="<?= e((string) ($c['starts_on'] ?? '')) ?>">
              </div>
              <div>
                <label class="field" for="ends-<?= $pid ?>">Fin</label>
                <input class="input" id="ends-<?= $pid ?>" name="ends_on" type="date" value="<?= e((string) ($c['ends_on'] ?? '')) ?>">
              </div>
            </div>
            <div class="form-grid-2">
              <div>
                <label class="field" for="venue-<?= $pid ?>">Lieu</label>
                <input class="input" id="venue-<?= $pid ?>" name="venue" value="<?= e((string) ($c['venue'] ?? '')) ?>">
              </div>
              <div>
                <label class="field" for="organizer-<?= $pid ?>">Organisateur</label>
                <input class="input" id="organizer-<?= $pid ?>" name="organizer" value="<?= e((string) ($c['organizer'] ?? '')) ?>">
              </div>
            </div>
            <div class="form-grid-2">
              <div>
                <label class="field" for="website-<?= $pid ?>">Site</label>
                <input class="input" id="website-<?= $pid ?>" name="website" value="<?= e((string) ($c['website'] ?? '')) ?>">
              </div>
              <div>
                <label class="field" for="ticket-<?= $pid ?>">Entrée</label>
                <input class="input" id="ticket-<?= $pid ?>" name="ticket" value="<?= e((string) ($c['ticket'] ?? '')) ?>">
              </div>
            </div>
            <div>
              <label class="field" for="audience-<?= $pid ?>">Public</label>
              <input class="input" id="audience-<?= $pid ?>" name="audience" value="<?= e((string) ($c['audience'] ?? '')) ?>">
            </div>
            <div>
              <label class="field" for="desc-<?= $pid ?>">Présentation</label>
              <textarea class="textarea" id="desc-<?= $pid ?>" name="description" rows="3"><?= e((string) ($c['description'] ?? '')) ?></textarea>
            </div>
            <label class="search-check">
              <input type="checkbox" name="dates_confirmed" value="1">
              <span>Dates confirmées (ne pas afficher « à confirmer »)</span>
            </label>

            <?php if (!empty($c['notes'])): ?>
              <blockquote class="me-claim-message"><?= nl2br(e((string) $c['notes'])) ?></blockquote>
            <?php endif; ?>

            <div class="me-claim-signals">
              <?php if ($similar === []): ?>
                <span class="me-signal is-ok"><?= icon('check-circle', 14) ?> Aucun salon au nom proche</span>
              <?php else: ?>
                <span class="me-signal is-warn"><?= icon('dot', 14) ?> Doublon possible :
                  <?php foreach ($similar as $i => $item): ?><?= $i > 0 ? ', ' : '' ?><a href="<?= e(url((string) $item['href'])) ?>" target="_blank" rel="noopener"><?= e((string) $item['name']) ?></a><?php endforeach; ?>
                </span>
              <?php endif; ?>
              <?php if (!empty($c['website'])): ?>
                <span class="me-signal"><a href="<?= e((string) $c['website']) ?>" target="_blank" rel="noopener nofollow">Site déclaré</a></span>
              <?php endif; ?>
            </div>

            <div class="admin-actions">
              <button class="btn-navy" type="submit"<?= $similar !== [] ? ' onclick="return confirm(\'Des salons au nom proche existent déjà. Publier quand même ?\');"' : '' ?>>Publier dans l’agenda</button>
            </div>
          </form>

          <details class="me-refuse">
            <summary class="admin-ghost">Refuser…</summary>
            <form method="post" action="<?= e(url('/admin/salons/' . $pid)) ?>" class="me-refuse-form">
              <?= csrf_field() ?>
              <input type="hidden" name="status" value="refused">
              <input type="hidden" name="back" value="<?= e($back) ?>">
              <textarea class="textarea" name="note" rows="2" required minlength="5" maxlength="600" placeholder="Motif transmis au demandeur (salon déjà présent, dates introuvables, hors périmètre…)"></textarea>
              <button class="admin-ghost" type="submit">Confirmer le refus</button>
            </form>
          </details>
        <?php else: ?>
          <div class="me-claim-preview">
            <div>
              <strong><?= e((string) $c['name']) ?></strong>
              <span><?= e(implode(' · ', array_filter([(string) ($c['dates'] ?? ''), (string) ($c['place'] ?? ''), (string) ($c['category'] ?? '')]))) ?></span>
              <?php if (!empty($c['description'])): ?><p><?= e(mb_substr((string) $c['description'], 0, 280)) ?><?= mb_strlen((string) $c['description']) > 280 ? '…' : '' ?></p><?php endif; ?>
            </div>
          </div>
          <?php if (!$pending && !empty($c['admin_note'])): ?>
            <p class="admin-muted" style="margin: 0 0 12px;">Note : <?= e((string) $c['admin_note']) ?></p>
          <?php endif; ?>
          <div class="admin-actions">
            <?php if ($salon): ?>
              <a class="admin-ghost" href="<?= e(url((string) $salon['href'])) ?>" target="_blank" rel="noopener">Voir la fiche</a>
            <?php endif; ?>
            <?php if (!empty($c['website'])): ?>
              <a class="admin-ghost" href="<?= e((string) $c['website']) ?>" target="_blank" rel="noopener nofollow">Site officiel</a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>
</div>
