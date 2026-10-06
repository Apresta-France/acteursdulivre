<?php

use Adl\Models\Souscription;

$s = $item ?? Souscription::blank();
$id = (int) ($s['id'] ?? 0);
$action = $id ? '/admin/souscriptions/' . $id : '/admin/souscriptions/nouvelle';
$val = static fn (string $key, string $default = ''): string => (string) ($s[$key] ?? $default);
?>
<div class="admin-page">
  <p class="admin-back"><a href="<?= e(url('/admin/souscriptions')) ?>">← Toutes les annonces</a></p>
  <div class="admin-page-head">
    <div>
      <h1><?= e($id ? $val('title') : 'Nouvelle annonce') ?></h1>
      <?php if ($id && $val('status') !== 'draft'): ?>
        <p class="admin-lead" style="margin-bottom: 0;">Visible sur /souscriptions/<?= e($val('slug')) ?></p>
      <?php endif; ?>
    </div>
    <?php if ($id && $val('status') !== 'draft'): ?>
      <a class="admin-ghost" href="<?= e(url('/souscriptions/' . $val('slug'))) ?>" target="_blank" rel="noopener">Voir la fiche publique</a>
    <?php endif; ?>
  </div>
  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <form class="admin-form" method="post" action="<?= e(url($action)) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <h2 class="admin-section-title">Le livre</h2>
    <div>
      <label class="field" for="title">Titre</label>
      <input class="input" id="title" name="title" required maxlength="190" value="<?= e($val('title')) ?>">
    </div>
    <div class="form-grid-2">
      <div>
        <label class="field" for="slug">Adresse</label>
        <input class="input" id="slug" name="slug" maxlength="190" value="<?= e($val('slug')) ?>" placeholder="générée à partir du titre si vide">
      </div>
      <div>
        <label class="field" for="genre">Genre</label>
        <input class="input" id="genre" name="genre" maxlength="120" value="<?= e($val('genre')) ?>" placeholder="Roman, essai, album…">
      </div>
    </div>
    <div class="form-grid-2">
      <div>
        <label class="field" for="kind">Type</label>
        <select class="input" id="kind" name="kind">
          <?php foreach (Souscription::KINDS as $key => $label): ?>
            <option value="<?= e($key) ?>"<?= $key === $val('kind', 'souscription') ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="field" for="status">Statut</label>
        <select class="input" id="status" name="status">
          <?php foreach (Souscription::STATUSES as $key => $label): ?>
            <option value="<?= e($key) ?>"<?= $key === $val('status', 'draft') ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <p class="field-help">Le brouillon et une proposition restent invisibles. Une annonce ouverte dont la date est passée apparaît dans Terminées. Un sponsoring peut rester ouvert sans date de fin.</p>
      </div>
    </div>
    <p class="field-help">En création : une souscription. Prévente et En vente : le livre se vend déjà. Sponsorisé : la fiche publique le signale.</p>
    <label class="admin-tax-check">
      <input type="checkbox" name="featured" value="1"<?= !empty($s['featured']) ? ' checked' : '' ?>>
      Mettre ce livre en avant en haut de la page
    </label>
    <p class="field-help">Un seul livre ouvert à la fois. La case ne prend effet que si le statut est Ouverte.</p>
    <?php if ($val('proposer_email') !== ''): ?>
      <p class="admin-lead">
        Proposée par <?= e($val('proposer_name')) ?> · <a href="mailto:<?= e($val('proposer_email')) ?>"><?= e($val('proposer_email')) ?></a>
        <?php if ($val('proposer_note') !== ''): ?><br><?= e($val('proposer_note')) ?><?php endif; ?>
      </p>
    <?php endif; ?>

    <h2 class="admin-section-title">Qui le porte</h2>
    <div class="form-grid-2">
      <div>
        <label class="field" for="bearer">Porteur</label>
        <input class="input" id="bearer" name="bearer" maxlength="190" value="<?= e($val('bearer')) ?>" placeholder="Nom de la personne, de l’atelier ou de la maison">
      </div>
      <div>
        <label class="field" for="bearer_role">Rôle</label>
        <input class="input" id="bearer_role" name="bearer_role" maxlength="120" value="<?= e($val('bearer_role')) ?>" placeholder="Autrice, relieur, maison d’édition…">
      </div>
    </div>
    <div>
      <label class="field" for="trades">Intervenants</label>
      <textarea class="textarea" id="trades" name="trades" rows="4" placeholder="Reliure | Atelier du Plat"><?= e($val('trades_text')) ?></textarea>
      <p class="field-help">Une ligne par personne ou atelier : rôle, une barre verticale, puis le nom.</p>
    </div>

    <h2 class="admin-section-title">Texte</h2>
    <div>
      <label class="field" for="pitch">Chapô</label>
      <textarea class="textarea" id="pitch" name="pitch" rows="3" maxlength="600"><?= e($val('pitch')) ?></textarea>
    </div>
    <div>
      <label class="field" for="body">Présentation</label>
      <textarea class="textarea" id="body" name="body" rows="8"><?= e($val('body_text')) ?></textarea>
      <p class="field-help">Séparez les paragraphes par une ligne vide.</p>
    </div>
    <div>
      <label class="field" for="facts">Renseignements</label>
      <textarea class="textarea" id="facts" name="facts" rows="4" placeholder="Format | 13 × 21 cm"><?= e($val('facts_text')) ?></textarea>
      <p class="field-help">Une ligne par renseignement : libellé, une barre verticale, puis la valeur.</p>
    </div>

    <h2 class="admin-section-title">Ailleurs</h2>
    <div class="form-grid-2">
      <div>
        <label class="field" for="host">Plateforme</label>
        <input class="input" id="host" name="host" maxlength="190" value="<?= e($val('host')) ?>" placeholder="Ulule, boutique de l’autrice…">
      </div>
      <div>
        <label class="field" for="closes">Date de clôture</label>
        <input class="input" id="closes" name="closes" type="date" value="<?= e($val('closes')) ?>">
      </div>
    </div>
    <div>
      <label class="field" for="external_url">Lien</label>
      <input class="input" id="external_url" name="external_url" type="url" maxlength="500" value="<?= e($val('external_url')) ?>" placeholder="https://">
      <p class="field-help">Le bouton de la fiche ouvre cette adresse, hors du site.</p>
    </div>
    <div class="form-grid-2">
      <div>
        <label class="field" for="cta">Libellé du bouton</label>
        <input class="input" id="cta" name="cta" maxlength="120" value="<?= e($val('cta')) ?>" placeholder="Soutenir la souscription">
      </div>
      <div>
        <label class="field" for="outcome">Mention si l’annonce est close</label>
        <input class="input" id="outcome" name="outcome" maxlength="190" value="<?= e($val('outcome')) ?>" placeholder="Objectif atteint">
      </div>
    </div>

    <h2 class="admin-section-title">Couverture</h2>
    <?php if ($val('cover_image') !== ''): ?>
      <p><img src="<?= e(uploaded($val('cover_image'))) ?>" alt="" style="width: 88px; aspect-ratio: 2 / 3; object-fit: cover; border-radius: 4px;"></p>
      <label class="admin-tax-check">
        <input type="checkbox" name="remove_cover" value="1">
        Retirer la couverture et revenir au dos typographique
      </label>
    <?php endif; ?>
    <div>
      <label class="field" for="cover">Image de couverture</label>
      <input class="input" id="cover" name="cover" type="file" accept="image/jpeg,image/png,image/webp">
      <p class="field-help">JPG, PNG ou WebP, 4 Mo maximum. Sans image, le dos coloré ci-dessous est affiché.</p>
    </div>
    <div class="form-grid-3">
      <div>
        <label class="field" for="cover_ink">Dos</label>
        <input class="input" id="cover_ink" name="cover_ink" type="color" value="<?= e($val('cover_ink', '#15212f')) ?>">
      </div>
      <div>
        <label class="field" for="cover_paper">Papier</label>
        <input class="input" id="cover_paper" name="cover_paper" type="color" value="<?= e($val('cover_paper', '#f4efe6')) ?>">
      </div>
      <div>
        <label class="field" for="cover_rule">Filet</label>
        <input class="input" id="cover_rule" name="cover_rule" type="color" value="<?= e($val('cover_rule', '#eb963b')) ?>">
      </div>
    </div>

    <div class="admin-actions">
      <button class="btn-orange" type="submit">Enregistrer</button>
    </div>
  </form>

  <?php if ($id): ?>
    <form method="post" action="<?= e(url('/admin/souscriptions/' . $id . '/supprimer')) ?>" style="margin-top: 22px;">
      <?= csrf_field() ?>
      <button class="admin-ghost" type="submit" onclick="return confirm('Supprimer cette annonce ?');">Supprimer l’annonce</button>
    </form>
  <?php endif; ?>
</div>
