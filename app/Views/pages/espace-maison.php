<?php

use Adl\Models\Publisher;

$p = $publisher ?? null;
$claims = $claims ?? [];
$sizes = $sizes ?? Publisher::SIZES;
$typologies = $typologies ?? Publisher::TYPOLOGIES;
$countries = $countries ?? [];
$countryNames = array_map(static fn (array $c): string => (string) $c['name'], $countries);
if ($p && $p['country'] !== '' && !in_array($p['country'], $countryNames, true)) {
    $countryNames[] = $p['country'];
}
sort($countryNames);
$isPending = $p && ($p['status'] ?? '') === Publisher::STATUS_PENDING;
?>
<div class="espace-page me-espace">
  <div class="espace-page-head">
    <div>
      <h1>Ma maison d'édition</h1>
      <p><?= $p
          ? ($isPending
              ? 'Votre fiche « ' . e($p['name']) . ' » est en attente de validation par l\'équipe.'
              : 'Vous gérez la fiche « ' . e($p['name']) . ' » dans l\'annuaire des maisons d\'édition.')
          : 'Revendiquez la fiche de votre maison, ou ajoutez-la si elle manque, pour la tenir à jour et recevoir les messages des membres.' ?></p>
    </div>
    <div class="vitrine-head-actions">
      <?php if ($p): ?>
        <a class="btn-ghost" href="<?= e(url($p['href'])) ?>"><?= $isPending ? 'Prévisualiser la fiche' : 'Voir la fiche publique' ?></a>
        <button class="btn-orange" type="submit" form="me-form">Enregistrer</button>
      <?php else: ?>
        <a class="btn-ghost" href="<?= e(url('/maisons-edition')) ?>">Trouver ma maison</a>
        <a class="btn-orange" href="<?= e(url('/maisons-edition/ajouter')) ?>">Ajouter ma maison</a>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <?php if (!$p): ?>
    <section class="avail-banner">
      <span class="dash-ico"><?= icon('store', 18) ?></span>
      <div>
        <strong>Aucune fiche attribuée pour le moment</strong>
        <em>Cherchez votre maison dans l'annuaire puis cliquez sur « Revendiquer la fiche ». Si elle n'y figure pas encore, <a href="<?= e(url('/maisons-edition/ajouter')) ?>">ajoutez-la</a> : la fiche est créée immédiatement et publiée après vérification.</em>
      </div>
    </section>

    <?php if ($claims !== []): ?>
      <div class="espace-panel">
        <h2 class="espace-group-title">Mes demandes</h2>
        <div class="admin-stack">
          <?php foreach ($claims as $c): ?>
            <?php $linkable = !$c['is_creation'] || ($c['publisher_status'] ?? '') === Publisher::STATUS_PUBLISHED; ?>
            <div class="me-claim-row">
              <div>
                <?php if ($linkable): ?><a href="<?= e(url($c['publisher_href'])) ?>"><strong><?= e((string) $c['publisher_name']) ?></strong></a><?php else: ?><strong><?= e((string) $c['publisher_name']) ?></strong><?php endif; ?>
                <span><?= e($c['kind_label']) ?> · envoyée le <?= e($c['when']) ?><?= $c['decided_label'] !== '' ? ' · traitée le ' . e($c['decided_label']) : '' ?></span>
                <?php if (($c['status'] ?? '') === 'refused' && !empty($c['admin_note'])): ?><em>Motif : <?= e((string) $c['admin_note']) ?></em><?php endif; ?>
              </div>
              <span class="status-pill status-<?= e((string) $c['status']) ?>"><?= e($c['status_label']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  <?php else: ?>
    <?php if ($isPending): ?>
      <section class="avail-banner">
        <span class="dash-ico"><?= icon('clock', 18) ?></span>
        <div>
          <strong>Fiche en attente de validation</strong>
          <em>Proposée le <?= e(admin_date((string) $p['created_at'])) ?>. Elle n'est pas encore visible dans l'annuaire : l'équipe la vérifie, en général sous deux jours ouvrés. Profitez-en pour la compléter, tout ce que vous enregistrez ici sera publié avec elle.</em>
        </div>
      </section>
    <?php else: ?>
      <section class="avail-banner is-available">
        <span class="dash-ico dash-ico-accent"><?= icon('check-circle', 18) ?></span>
        <div>
          <strong>Fiche gérée par votre maison</strong>
          <em>Attribuée le <?= e(admin_date((string) $p['claimed_at'])) ?>. Elle affiche le badge « Fiche gérée par la maison » ; les membres connectés peuvent vous écrire via la messagerie et consulter vos coordonnées.</em>
        </div>
      </section>
    <?php endif; ?>

    <form id="me-form" class="vitrine-form" method="post" action="<?= e(url('/espace/maison-edition')) ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <div class="espace-panel">
        <h2 class="espace-group-title">Identité</h2>
        <div class="me-logo-field">
          <div class="me-logo-preview">
            <?php if ($p['logo_src'] !== ''): ?>
              <img src="<?= e($p['logo_src']) ?>" alt="" width="88" height="88">
            <?php else: ?>
              <span class="me-mono"><?= e($p['initials']) ?></span>
            <?php endif; ?>
          </div>
          <div>
            <label class="field" for="logo">Logo</label>
            <input class="input" id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp">
            <p class="field-help">JPG, PNG ou WebP, 2 Mo maximum, de préférence carré.</p>
            <?php if ($p['logo_src'] !== ''): ?>
              <label class="search-check" style="margin-top: 8px;"><input type="checkbox" name="remove_logo" value="1"><span>Retirer le logo actuel</span></label>
            <?php endif; ?>
          </div>
        </div>
        <div class="form-grid-2">
          <div>
            <label class="field" for="name">Nom de la maison</label>
            <input class="input" id="name" name="name" required maxlength="190" value="<?= e($p['name']) ?>">
            <p class="field-help">L'adresse de la fiche (<?= e($p['slug']) ?>) reste identique pour ne pas casser les liens.</p>
          </div>
          <div>
            <label class="field" for="founded">Année de fondation</label>
            <input class="input" id="founded" name="founded" maxlength="120" value="<?= e($p['founded']) ?>" placeholder="1978">
          </div>
        </div>
        <div class="form-grid-3">
          <div>
            <label class="field" for="country">Pays</label>
            <input class="input" id="country" name="country" list="me-countries" required maxlength="120" value="<?= e($p['country']) ?>">
            <datalist id="me-countries"><?php foreach ($countryNames as $n): ?><option value="<?= e($n) ?>"><?php endforeach; ?></datalist>
          </div>
          <div>
            <label class="field" for="city">Ville du siège</label>
            <input class="input" id="city" name="city" maxlength="120" value="<?= e($p['city']) ?>">
          </div>
          <div>
            <label class="field" for="parent_group">Groupe ou actionnariat</label>
            <input class="input" id="parent_group" name="parent_group" maxlength="190" value="<?= e($p['parent_group']) ?>" placeholder="Indépendant, Madrigall, Editis…">
          </div>
        </div>
        <div class="form-grid-2">
          <div>
            <label class="field" for="size_key">Taille de la structure</label>
            <select class="input" id="size_key" name="size_key">
              <?php foreach ($sizes as $k => $l): ?><option value="<?= e($k) ?>"<?= $k === $p['size_key'] ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="field" for="typology_key">Ligne éditoriale</label>
            <select class="input" id="typology_key" name="typology_key">
              <?php foreach ($typologies as $k => $l): ?><option value="<?= e($k) ?>"<?= $k === $p['typology_key'] ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div class="espace-panel">
        <h2 class="espace-group-title">Catalogue</h2>
        <div>
          <label class="field" for="genres">Genres et domaines publiés</label>
          <input class="input" id="genres" name="genres" maxlength="600" value="<?= e(implode(', ', $p['genres'])) ?>" placeholder="Littérature française, Jeunesse, BD, Essais…">
          <p class="field-help">Séparez par des virgules, 20 au maximum. Ils servent de filtres dans l'annuaire.</p>
        </div>
        <div>
          <label class="field" for="description">Présentation</label>
          <textarea class="textarea" id="description" name="description" rows="7" maxlength="1200" placeholder="Votre ligne éditoriale, vos collections, ce qui vous distingue."><?= e($p['description']) ?></textarea>
          <p class="field-help">1 200 caractères maximum. Les premières phrases servent de résumé dans l'annuaire et pour les moteurs de recherche.</p>
        </div>
        <div>
          <label class="field" for="submissions_note">Envoi de manuscrits</label>
          <textarea class="textarea" id="submissions_note" name="submissions_note" rows="3" maxlength="600" placeholder="Acceptez-vous les manuscrits ? Par quel canal, dans quels genres, avec quel délai de réponse ?"><?= e((string) $p['submissions_note']) ?></textarea>
          <p class="field-help">Facultatif. Cette précision évite de nombreux envois hors sujet.</p>
        </div>
      </div>

      <div class="espace-panel">
        <h2 class="espace-group-title">Coordonnées</h2>
        <p class="field-help">Visibles uniquement par les membres connectés, sur demande et dans une limite quotidienne : elles ne sont pas exposées aux robots.</p>
        <div class="form-grid-2">
          <div>
            <label class="field" for="website">Site web</label>
            <input class="input" id="website" name="website" inputmode="url" maxlength="255" value="<?= e($p['website']) ?>" placeholder="https://">
          </div>
          <div>
            <label class="field" for="contact_email">E-mail de contact</label>
            <input class="input" id="contact_email" name="contact_email" type="email" maxlength="190" value="<?= e($p['contact_email']) ?>">
          </div>
        </div>
        <div class="form-grid-2">
          <div>
            <label class="field" for="contact_phone">Téléphone</label>
            <input class="input" id="contact_phone" name="contact_phone" maxlength="40" value="<?= e((string) $p['contact_phone']) ?>">
          </div>
          <div>
            <label class="field" for="contact_address">Adresse postale</label>
            <input class="input" id="contact_address" name="contact_address" maxlength="255" value="<?= e($p['contact_address']) ?>">
          </div>
        </div>
      </div>

      <div class="admin-actions">
        <button class="btn-orange" type="submit">Enregistrer</button>
        <a class="btn-ghost" href="<?= e(url($p['href'])) ?>">Voir la fiche publique</a>
      </div>
    </form>
  <?php endif; ?>
</div>
