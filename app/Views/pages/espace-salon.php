<?php
$s = $salon ?? null;
$claims = $claims ?? [];
$categories = $categories ?? [];
?>
<div class="espace-page me-espace">
  <div class="espace-page-head">
    <div>
      <h1>Mon salon</h1>
      <p><?= $s
          ? 'Vous gérez la fiche « ' . e((string) $s['name']) . ' » dans l’agenda des salons.'
          : 'Revendiquez la fiche de votre salon ou festival pour la tenir à jour vous-même — dates, programme, exposants, logo.' ?></p>
    </div>
    <div class="vitrine-head-actions">
      <?php if ($s): ?>
        <a class="btn-ghost" href="<?= e(url((string) $s['href'])) ?>">Voir la fiche publique</a>
        <button class="btn-orange" type="submit" form="salon-form">Enregistrer</button>
      <?php else: ?>
        <a class="btn-ghost" href="<?= e(url('/salons')) ?>">Trouver mon salon</a>
        <a class="btn-orange" href="<?= e(url('/salons/ajouter')) ?>">Proposer un salon</a>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <?php if (!$s): ?>
    <section class="avail-banner">
      <span class="dash-ico"><?= icon('store', 18) ?></span>
      <div>
        <strong>Aucune fiche attribuée pour le moment</strong>
        <em>Cherchez votre salon dans <a href="<?= e(url('/salons')) ?>">l’agenda</a> puis cliquez « Gérer ma fiche » sur sa page. S’il n’y figure pas encore, <a href="<?= e(url('/salons/ajouter')) ?>">proposez-le</a> : la fiche est publiée après vérification. Pour la mise en avant partenaire, découvrez <a href="<?= e(url('/salons/visibilite')) ?>">l’offre de visibilité</a>.</em>
      </div>
    </section>

    <?php if ($claims !== []): ?>
      <div class="espace-panel">
        <h2 class="espace-group-title">Mes demandes</h2>
        <div class="admin-stack">
          <?php foreach ($claims as $c): ?>
            <div class="me-claim-row">
              <div>
                <a href="<?= e(url((string) $c['salon_href'])) ?>"><strong><?= e((string) $c['salon_name']) ?></strong></a>
                <span>envoyée le <?= e((string) $c['when']) ?><?= (string) ($c['decided_label'] ?? '') !== '' ? ' · traitée le ' . e((string) $c['decided_label']) : '' ?></span>
                <?php if (($c['status'] ?? '') === 'refused' && !empty($c['admin_note'])): ?><em>Motif : <?= e((string) $c['admin_note']) ?></em><?php endif; ?>
              </div>
              <span class="status-pill status-<?= e((string) $c['status']) ?>"><?= e((string) $c['status_label']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  <?php else: ?>
    <section class="avail-banner is-available">
      <span class="dash-ico dash-ico-accent"><?= icon('check-circle', 18) ?></span>
      <div>
        <strong>Fiche gérée par l’organisateur</strong>
        <em>Attribuée<?= !empty($s['claimed_at']) ? ' le ' . e(admin_date((string) $s['claimed_at'])) : '' ?>. Cochez « dates confirmées » pour qu’elles portent la mention « confirmées par l’organisateur ».<?= !empty($s['is_featured']) ? ' Mise en avant partenaire active jusqu’au ' . e((string) $s['featured_label']) . '.' : '' ?></em>
      </div>
    </section>

    <form id="salon-form" class="vitrine-form" method="post" action="<?= e(url('/espace/salon')) ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <div class="espace-panel">
        <h2 class="espace-group-title">Identité</h2>
        <div class="me-logo-field">
          <div class="me-logo-preview">
            <?php if ((string) ($s['logo_src'] ?? '') !== ''): ?>
              <img src="<?= e((string) $s['logo_src']) ?>" alt="" width="88" height="88">
            <?php else: ?>
              <span class="me-mono"><?= e(mb_strtoupper(mb_substr((string) $s['name'], 0, 2))) ?></span>
            <?php endif; ?>
          </div>
          <div>
            <label class="field" for="logo">Logo</label>
            <input class="input" id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp">
            <p class="field-help">JPG, PNG ou WebP, 2 Mo maximum. Affiché en haut de la fiche.</p>
            <?php if ((string) ($s['logo_src'] ?? '') !== ''): ?>
              <label class="search-check" style="margin-top: 8px;"><input type="checkbox" name="remove_logo" value="1"><span>Retirer le logo actuel</span></label>
            <?php endif; ?>
          </div>
        </div>
        <div class="form-grid-2">
          <div>
            <label class="field" for="name">Nom de la manifestation</label>
            <input class="input" id="name" name="name" required minlength="2" maxlength="255" value="<?= e((string) $s['name']) ?>">
            <p class="field-help">L'adresse de la fiche (<?= e((string) $s['slug']) ?>) reste identique pour ne pas casser les liens.</p>
          </div>
          <div>
            <label class="field" for="category">Catégorie</label>
            <select class="input" id="category" name="category">
              <option value="">À préciser</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= e((string) $cat) ?>"<?= (string) $cat === (string) ($s['category'] ?? '') ? ' selected' : '' ?>><?= e((string) $cat) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div class="espace-panel">
        <h2 class="espace-group-title">Dates et lieu</h2>
        <div class="form-grid-3">
          <div>
            <label class="field" for="starts_on">Date de début</label>
            <input class="input" id="starts_on" name="starts_on" type="date" value="<?= e((string) ($s['starts_on'] ?? '')) ?>">
          </div>
          <div>
            <label class="field" for="ends_on">Date de fin</label>
            <input class="input" id="ends_on" name="ends_on" type="date" value="<?= e((string) ($s['ends_on'] ?? '')) ?>">
          </div>
          <div>
            <label class="field" for="venue">Lieu</label>
            <input class="input" id="venue" name="venue" maxlength="255" value="<?= e((string) ($s['venue'] ?? '')) ?>" placeholder="Halle, médiathèque, parc…">
          </div>
        </div>
        <label class="search-check">
          <input type="checkbox" name="dates_confirmed" value="1"<?= !empty($s['confirmed']) ? ' checked' : '' ?>>
          <span>Les dates indiquées sont confirmées — la fiche affichera « Dates confirmées par l’organisateur ».</span>
        </label>
        <div class="form-grid-3">
          <div>
            <label class="field" for="city">Ville</label>
            <input class="input" id="city" name="city" required minlength="2" maxlength="120" value="<?= e((string) ($s['city'] ?? '')) ?>">
          </div>
          <div>
            <label class="field" for="region">Région</label>
            <input class="input" id="region" name="region" maxlength="120" value="<?= e((string) ($s['region'] ?? '')) ?>">
          </div>
          <div>
            <label class="field" for="country">Pays</label>
            <input class="input" id="country" name="country" maxlength="120" value="<?= e((string) ($s['country'] ?? 'France')) ?>">
          </div>
        </div>
      </div>

      <div class="espace-panel">
        <h2 class="espace-group-title">Pratique</h2>
        <div class="form-grid-2">
          <div>
            <label class="field" for="website">Site officiel</label>
            <input class="input" id="website" name="website" inputmode="url" maxlength="255" value="<?= e((string) ($s['website'] ?? '')) ?>" placeholder="https://">
          </div>
          <div>
            <label class="field" for="organizer">Organisateur</label>
            <input class="input" id="organizer" name="organizer" maxlength="255" value="<?= e((string) ($s['organizer'] ?? '')) ?>">
          </div>
        </div>
        <div class="form-grid-2">
          <div>
            <label class="field" for="ticket">Entrée</label>
            <input class="input" id="ticket" name="ticket" maxlength="255" value="<?= e((string) ($s['ticket'] ?? '')) ?>" placeholder="Libre, 5 €, sur invitation…">
          </div>
          <div>
            <label class="field" for="audience">Public</label>
            <input class="input" id="audience" name="audience" maxlength="190" value="<?= e((string) ($s['audience'] ?? '')) ?>" placeholder="Tout public, professionnels, jeunesse…">
          </div>
        </div>
        <div class="form-grid-2">
          <div>
            <label class="field" for="contact">Contact exposants / public</label>
            <input class="input" id="contact" name="contact" maxlength="255" value="<?= e((string) ($s['contact'] ?? '')) ?>" placeholder="stands@salon.fr, formulaire…">
          </div>
          <div>
            <label class="field" for="socials">Réseaux sociaux</label>
            <input class="input" id="socials" name="socials" maxlength="255" value="<?= e((string) ($s['socials'] ?? '')) ?>" placeholder="@salondulivre, lien Facebook…">
          </div>
        </div>
      </div>

      <div class="espace-panel">
        <h2 class="espace-group-title">Contenu</h2>
        <div>
          <label class="field" for="description">Présentation</label>
          <textarea class="textarea" id="description" name="description" rows="5" maxlength="4000" placeholder="Quelques lignes sur la manifestation, son édition, son public."><?= e((string) ($s['description'] ?? '')) ?></textarea>
          <p class="field-help">Les premières phrases servent de résumé pour les moteurs de recherche.</p>
        </div>
        <div>
          <label class="field" for="program">Programme de l’édition</label>
          <textarea class="textarea" id="program" name="program" rows="6" maxlength="6000" placeholder="Invités d’honneur, rencontres, remises de prix, horaires…"><?= e((string) ($s['program'] ?? '')) ?></textarea>
        </div>
        <div>
          <label class="field" for="exhibitors_list">Exposants</label>
          <textarea class="textarea" id="exhibitors_list" name="exhibitors_list" rows="5" maxlength="4000" placeholder="Liste ou description des exposants attendus : éditeurs, auteurs, associations…"><?= e((string) ($s['exhibitors_list'] ?? '')) ?></textarea>
        </div>
      </div>

      <div class="admin-actions">
        <button class="btn-orange" type="submit">Enregistrer</button>
        <a class="btn-ghost" href="<?= e(url((string) $s['href'])) ?>">Voir la fiche publique</a>
      </div>
    </form>
  <?php endif; ?>
</div>
