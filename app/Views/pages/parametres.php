<div class="espace-page">
  <h1 class="espace-page-title">Paramètres</h1>
  <p class="espace-page-lead">Votre compte reste unique. Vous pouvez chercher des prestataires, proposer vos services, ou les deux.</p>

  <?php if (!empty($saved)): ?>
    <div class="flash flash-ok">Vos paramètres ont été enregistrés.</div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div class="flash flash-error"><?= e((string) $error) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= e(url('/espace/parametres')) ?>" class="param-form">
    <?= csrf_field() ?>
    <div class="auth-name-grid">
      <div>
        <label class="field" for="first_name">Prénom</label>
        <input class="input" id="first_name" name="first_name" value="<?= e($prenom ?? '') ?>" required>
      </div>
      <div>
        <label class="field" for="last_name">Nom</label>
        <input class="input" id="last_name" name="last_name" value="<?= e($nom ?? '') ?>" required>
      </div>
    </div>

    <div>
      <p class="field" style="margin-bottom: 10px;">Mes usages</p>
      <div class="intent-grid">
        <label class="intent-card<?= !empty($seeksChecked) ? ' is-on' : '' ?>" data-intent-card>
          <input type="checkbox" name="seeks_services" value="1"<?= !empty($seeksChecked) ? ' checked' : '' ?>>
          <div class="intent-card-title">Je cherche des prestataires</div>
          <p>Publier des missions, commander des prestations, suivre vos projets.</p>
        </label>
        <label class="intent-card<?= !empty($offersChecked) ? ' is-on' : '' ?>" data-intent-card>
          <input type="checkbox" name="offers_services" value="1"<?= !empty($offersChecked) ? ' checked' : '' ?>>
          <div class="intent-card-title">Je propose mes services</div>
          <p>Vitrine, prestations à prix affiché, candidatures aux appels d'offres.</p>
        </label>
      </div>
    </div>

    <div class="auth-actions">
      <button class="btn-orange" type="submit">Enregistrer</button>
    </div>
  </form>
</div>
