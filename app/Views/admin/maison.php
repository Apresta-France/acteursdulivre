<?php

use Adl\Models\Publisher;

$p = $publisher ?? null;
$isNew = $p === null;
$id = $p ? (int) $p['id'] : 0;
$action = $isNew ? '/admin/maisons-edition/nouvelle' : '/admin/maisons-edition/' . $id;
$sizes = $sizes ?? Publisher::SIZES;
$typologies = $typologies ?? Publisher::TYPOLOGIES;
$owner = $owner ?? null;
$claims = $claims ?? [];
$countryNames = array_map(static fn (array $c): string => (string) $c['name'], $countries ?? []);
$val = static fn (string $k, string $d = ''): string => (string) ($p[$k] ?? $d);
?>
<div class="admin-page">
  <p class="admin-back"><a href="<?= e(url('/admin/maisons-edition?onglet=fiches')) ?>">← Toutes les maisons</a></p>
  <div class="admin-page-head">
    <div>
      <h1><?= e($isNew ? 'Nouvelle maison d\'édition' : (string) $p['name']) ?></h1>
      <?php if (!$isNew): ?>
        <p class="admin-lead" style="margin-bottom: 0;">
          <?= e((string) $p['location_label']) ?> · <?= e((string) $p['size_label']) ?> · <?= e((string) $p['typology_label']) ?>
          · source <?= e((string) ($p['source'] ?? 'import')) ?> · créée le <?= e(admin_date((string) ($p['created_at'] ?? ''))) ?>
        </p>
      <?php endif; ?>
    </div>
    <?php if (!$isNew): ?>
      <a class="admin-ghost" href="<?= e(url((string) $p['href'])) ?>" target="_blank" rel="noopener">Voir la fiche publique</a>
    <?php endif; ?>
  </div>

  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <div class="me-admin-layout">
    <form class="admin-form" method="post" action="<?= e(url($action)) ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <h2 class="admin-section-title">Identité</h2>
      <div class="form-grid-2">
        <div>
          <label class="field" for="name">Nom</label>
          <input class="input" id="name" name="name" required maxlength="190" value="<?= e($val('name')) ?>">
          <?php if (!$isNew): ?><p class="field-help">Adresse : /maisons-edition/<?= e((string) $p['slug']) ?> (inchangée pour préserver le référencement).</p><?php endif; ?>
        </div>
        <div>
          <label class="field" for="founded">Année de fondation</label>
          <input class="input" id="founded" name="founded" maxlength="120" value="<?= e($val('founded')) ?>" placeholder="1978">
        </div>
      </div>
      <div class="form-grid-3">
        <div>
          <label class="field" for="country">Pays</label>
          <input class="input" id="country" name="country" list="me-countries" required maxlength="120" value="<?= e($val('country')) ?>">
          <datalist id="me-countries"><?php foreach ($countryNames as $n): ?><option value="<?= e($n) ?>"><?php endforeach; ?></datalist>
        </div>
        <div>
          <label class="field" for="region">Région (facultatif)</label>
          <input class="input" id="region" name="region" maxlength="120" value="<?= e($val('region')) ?>" placeholder="Catalogne, Angleterre…">
        </div>
        <div>
          <label class="field" for="city">Ville</label>
          <input class="input" id="city" name="city" maxlength="120" value="<?= e($val('city')) ?>">
        </div>
      </div>
      <div class="form-grid-3">
        <div>
          <label class="field" for="parent_group">Groupe / actionnariat</label>
          <input class="input" id="parent_group" name="parent_group" maxlength="190" value="<?= e($val('parent_group')) ?>" placeholder="Indépendant, Madrigall…">
        </div>
        <div>
          <label class="field" for="size_key">Taille</label>
          <select class="input" id="size_key" name="size_key">
            <?php foreach ($sizes as $k => $l): ?><option value="<?= e($k) ?>"<?= $k === $val('size_key', 'pme') ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="field" for="typology_key">Typologie</label>
          <select class="input" id="typology_key" name="typology_key">
            <?php foreach ($typologies as $k => $l): ?><option value="<?= e($k) ?>"<?= $k === $val('typology_key', 'specialise') ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>

      <h2 class="admin-section-title">Catalogue</h2>
      <div>
        <label class="field" for="genres">Genres (séparés par des virgules)</label>
        <input class="input" id="genres" name="genres" maxlength="600" value="<?= e(implode(', ', $p['genres'] ?? [])) ?>">
      </div>
      <div>
        <label class="field" for="description">Présentation</label>
        <textarea class="textarea" id="description" name="description" rows="6" maxlength="1200"><?= e($val('description')) ?></textarea>
      </div>
      <div>
        <label class="field" for="submissions_note">Envoi de manuscrits</label>
        <textarea class="textarea" id="submissions_note" name="submissions_note" rows="2" maxlength="600"><?= e($val('submissions_note')) ?></textarea>
      </div>
      <div>
        <label class="field" for="segments">Segments de recherche (interne)</label>
        <input class="input" id="segments" name="segments" maxlength="255" value="<?= e($val('segments')) ?>">
        <p class="field-help">Mots-clés hérités de l'import, utilisés pour la recherche. Non affichés.</p>
      </div>

      <h2 class="admin-section-title">Coordonnées (réservées aux membres)</h2>
      <div class="form-grid-2">
        <div>
          <label class="field" for="website">Site web</label>
          <input class="input" id="website" name="website" inputmode="url" maxlength="255" value="<?= e($val('website')) ?>" placeholder="https://">
        </div>
        <div>
          <label class="field" for="contact_email">E-mail</label>
          <input class="input" id="contact_email" name="contact_email" type="email" maxlength="190" value="<?= e($val('contact_email')) ?>">
        </div>
      </div>
      <div class="form-grid-2">
        <div>
          <label class="field" for="contact_phone">Téléphone</label>
          <input class="input" id="contact_phone" name="contact_phone" maxlength="40" value="<?= e($val('contact_phone')) ?>">
        </div>
        <div>
          <label class="field" for="contact_address">Adresse postale</label>
          <input class="input" id="contact_address" name="contact_address" maxlength="255" value="<?= e($val('contact_address')) ?>">
        </div>
      </div>

      <h2 class="admin-section-title">Logo et publication</h2>
      <div class="form-grid-2">
        <div>
          <label class="field" for="logo">Logo</label>
          <?php if (!$isNew && $p['logo_src'] !== ''): ?>
            <p><img src="<?= e((string) $p['logo_src']) ?>" alt="" style="width: 72px; height: 72px; object-fit: contain; border-radius: 10px; border: 1px solid var(--line);"></p>
            <label class="admin-tax-check"><input type="checkbox" name="remove_logo" value="1"> Retirer le logo</label>
          <?php endif; ?>
          <input class="input" id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp">
        </div>
        <div>
          <label class="admin-tax-check" style="margin-top: 28px;">
            <input type="checkbox" name="published" value="1"<?= $isNew || $val('status', 'published') === 'published' ? ' checked' : '' ?>>
            Visible dans l'annuaire public
          </label>
          <?php if (!$isNew && $val('status') === Publisher::STATUS_PENDING): ?>
            <p class="field-help">Fiche proposée par un membre, <strong>en attente de validation</strong>. Publiez-la depuis l'onglet Revendications pour prévenir le demandeur par e-mail, ou cochez la case ci-dessus pour la rendre visible sans notification.</p>
          <?php endif; ?>
        </div>
      </div>

      <div class="admin-actions">
        <button class="btn-orange" type="submit"><?= $isNew ? 'Créer la maison' : 'Enregistrer' ?></button>
        <a class="admin-ghost" href="<?= e(url('/admin/maisons-edition?onglet=fiches')) ?>">Annuler</a>
      </div>
    </form>

    <?php if (!$isNew): ?>
      <aside class="me-admin-side">
        <div class="admin-card">
          <h3 class="admin-card-title">Propriétaire de la fiche</h3>
          <?php if ($owner): ?>
            <div class="admin-dossier-who" style="margin-bottom: 12px;">
              <?= avatar_html($owner, 36) ?>
              <div>
                <strong><?= e(\Adl\Models\User::displayName($owner)) ?></strong>
                <span><?= e((string) $owner['email']) ?></span>
                <em>Attribuée le <?= e(admin_date((string) ($p['claimed_at'] ?? ''))) ?></em>
              </div>
            </div>
            <div class="admin-actions">
              <a class="admin-ghost" href="<?= e(url('/admin/utilisateurs/' . (int) $owner['id'])) ?>">Compte</a>
              <form method="post" action="<?= e(url('/admin/maisons-edition/' . $id . '/proprietaire')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="detach">
                <button class="admin-ghost" type="submit" onclick="return confirm('Retirer la gestion de cette fiche à ce compte ?');">Retirer l'attribution</button>
              </form>
            </div>
          <?php else: ?>
            <p class="admin-muted" style="margin: 0 0 10px;">Aucun compte ne gère cette fiche. Les demandes arrivent dans l'onglet Revendications ; vous pouvez aussi attribuer directement à un compte existant.</p>
            <form method="post" action="<?= e(url('/admin/maisons-edition/' . $id . '/proprietaire')) ?>" class="me-owner-form">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="assign">
              <input class="input" type="email" name="owner_email" required placeholder="E-mail du compte membre">
              <button class="btn-navy" type="submit">Attribuer</button>
            </form>
          <?php endif; ?>
        </div>

        <div class="admin-card">
          <h3 class="admin-card-title">Demandes reçues</h3>
          <?php if ($claims === []): ?>
            <p class="admin-muted" style="margin: 0;">Aucune demande pour cette fiche.</p>
          <?php else: ?>
            <ul class="me-admin-claims">
              <?php foreach ($claims as $c): ?>
                <li>
                  <strong><?= e((string) $c['who']) ?></strong> · <?= e((string) $c['role_title']) ?><br>
                  <span><?= e((string) $c['company_email']) ?> · <?= e((string) $c['when']) ?></span>
                  <span class="admin-pill tone-<?= e($c['status'] === 'approved' ? 'green' : ($c['status'] === 'refused' ? 'orange' : 'navy')) ?>"><?= e((string) $c['status_label']) ?></span>
                  <?php if (($c['status'] ?? '') === 'pending'): ?>
                    <form method="post" action="<?= e(url('/admin/maisons-edition/revendications/' . (int) $c['id'])) ?>" style="margin-top: 6px;">
                      <?= csrf_field() ?>
                      <input type="hidden" name="status" value="approved">
                      <input type="hidden" name="back" value="/admin/maisons-edition/<?= $id ?>">
                      <button class="btn-navy" type="submit">Attribuer</button>
                    </form>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
            <p class="admin-muted" style="margin: 10px 0 0;"><a href="<?= e(url('/admin/maisons-edition')) ?>">Traiter les demandes en détail</a></p>
          <?php endif; ?>
        </div>

        <div class="admin-card">
          <h3 class="admin-card-title">Danger</h3>
          <p class="admin-muted" style="margin: 0 0 10px;">Préférez masquer la fiche (case « Visible » décochée) : la suppression est définitive et casse les liens indexés.</p>
          <form method="post" action="<?= e(url('/admin/maisons-edition/' . $id . '/supprimer')) ?>">
            <?= csrf_field() ?>
            <button class="admin-ghost" type="submit" onclick="return confirm('Supprimer définitivement cette fiche et ses demandes ?');">Supprimer la fiche</button>
          </form>
        </div>
      </aside>
    <?php endif; ?>
  </div>
</div>
