<?php

use Adl\Core\OAuth;

$oauthProviders = OAuth::enabledProviders();
?>
<div class="espace-page">
  <h1 class="espace-page-title">Paramètres</h1>
  <p class="espace-page-lead">Votre compte reste unique. Vous pouvez chercher des prestataires, proposer vos services, ou les deux.</p>

  <?php if (!empty($saved)): ?>
    <div class="flash flash-ok">Vos paramètres ont été enregistrés.</div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div class="flash flash-error"><?= e((string) $error) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= e(url('/espace/parametres')) ?>" class="param-form" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php
      $avatarSrc = (string) ($avatarSrc ?? $userAvatarUrl ?? '');
      $initials = (string) ($userInitials ?? 'AD');
      $inputId = 'param-avatar';
      $help = 'JPG, PNG ou WebP, 2 Mo maximum. Visible dans l’espace et sur votre fiche.';
      require ADL_ROOT . '/app/Views/partials/avatar-field.php';
    ?>
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
      <label class="field" for="email" data-email-label><?= !empty($offersChecked) ? 'E-mail professionnel' : 'E-mail' ?></label>
      <input class="input" id="email" type="email" name="email" value="<?= e((string) ($email ?? '')) ?>" required>
    </div>

    <div>
      <p class="field" style="margin-bottom: 10px;">Mes usages</p>
      <div class="intent-grid">
        <label class="intent-card<?= !empty($seeksChecked) ? ' is-on' : '' ?>" data-intent-card>
          <input type="checkbox" name="seeks_services" value="1"<?= !empty($seeksChecked) ? ' checked' : '' ?>>
          <div class="intent-card-title">Je cherche des prestataires</div>
          <p>Publier des recherches, commander des prestations, suivre vos projets.</p>
        </label>
        <label class="intent-card<?= !empty($offersChecked) ? ' is-on' : '' ?>" data-intent-card>
          <input type="checkbox" name="offers_services" value="1"<?= !empty($offersChecked) ? ' checked' : '' ?>>
          <div class="intent-card-title">Je propose mes services</div>
          <p>Vitrine, prestations à prix affiché, candidatures aux appels d'offres.</p>
        </label>
      </div>
    </div>

    <?php if (empty($offersChecked) && empty(($user ?? [])['offers_services'])): ?>
      <div class="ia-box" data-if-offers hidden>
        <div class="ia-box-title">Engagement sans IA générative</div>
        <p>Obligatoire pour proposer vos services. Les outils de métier — correcteur orthographique, mémoire de traduction — restent autorisés.</p>
        <label class="ia-box-check">
          <input type="checkbox" name="charte_ia" value="1">
          Je m'engage à ne fournir aucun livrable produit par une IA générative.
        </label>
        <a class="ia-box-cta" href="<?= e(url('/regles-ia')) ?>">Lire nos règles d'intelligence artificielle →</a>
      </div>
    <?php endif; ?>

    <div class="auth-actions">
      <button class="btn-orange" type="submit">Enregistrer</button>
    </div>
  </form>

  <form method="post" action="<?= e(url('/espace/parametres/mot-de-passe')) ?>" class="param-form" style="margin-top: 36px;">
    <?= csrf_field() ?>
    <p class="field">Mot de passe</p>
    <p class="espace-page-lead" style="margin-top: 0;">8 caractères minimum. Laissez le champ actuel vide si votre compte n'a pas encore de mot de passe.</p>
    <div>
      <label class="field" for="current_password">Mot de passe actuel</label>
      <input class="input" id="current_password" type="password" name="current_password">
    </div>
    <div class="auth-name-grid">
      <div>
        <label class="field" for="password">Nouveau mot de passe</label>
        <input class="input" id="password" type="password" name="password" required minlength="8">
      </div>
      <div>
        <label class="field" for="password_confirmation">Confirmation</label>
        <input class="input" id="password_confirmation" type="password" name="password_confirmation" required minlength="8">
      </div>
    </div>
    <div class="auth-actions">
      <button class="btn-navy" type="submit">Mettre à jour le mot de passe</button>
    </div>
  </form>

  <form method="post" action="<?= e(url('/espace/parametres/notifications')) ?>" class="param-form" style="margin-top: 36px;">
    <?= csrf_field() ?>
    <p class="field">Notifications</p>
    <p class="espace-page-lead" style="margin-top: 0;">Les messages restent visibles ici. Ces cases concernent les e-mails (à l’instant et les relances).</p>
    <label class="check-row"><input type="checkbox" name="notify_messages" value="1"<?= !empty($notifyMessages) ? ' checked' : '' ?>> Nouveau message et relances si une conversation reste sans réponse</label>
    <label class="check-row"><input type="checkbox" name="notify_jalons" value="1"<?= !empty($notifyJalons) ? ' checked' : '' ?>> Commandes : acceptation, livraison, facture de commission, facture échue</label>
    <label class="check-row"><input type="checkbox" name="notify_missions" value="1"<?= !empty($notifyMissions) ? ' checked' : '' ?>> Missions : nouvelle candidature, réponse, vitrine incomplète</label>
    <label class="check-row"><input type="checkbox" name="notify_newsletter" value="1"<?= !empty($notifyNewsletter) ? ' checked' : '' ?>> Lettre d'information hebdomadaire</label>
    <div class="auth-actions">
      <button class="btn-navy" type="submit">Enregistrer les notifications</button>
    </div>
  </form>

  <form method="post" action="<?= e(url('/espace/parametres/facturation')) ?>" class="param-form" style="margin-top: 36px;">
    <?= csrf_field() ?>
    <p class="field">Facturation</p>
    <p class="espace-page-lead" style="margin-top: 0;">Mentions reprises sur votre facture de commission. L'IBAN n'est jamais utilisé pour encaisser une mission.</p>
    <label class="field" for="company_name">Raison sociale</label>
    <input class="input" id="company_name" name="company_name" value="<?= e((string) ($companyName ?? '')) ?>">
    <div class="auth-name-grid">
      <div>
        <label class="field" for="siret">SIRET</label>
        <input class="input" id="siret" name="siret" inputmode="numeric" autocomplete="off" value="<?= e((string) ($siret ?? '')) ?>">
      </div>
      <div>
        <label class="field" for="vat_number">N° TVA</label>
        <input class="input" id="vat_number" name="vat_number" autocomplete="off" value="<?= e((string) ($vatNumber ?? '')) ?>">
      </div>
    </div>
    <label class="field" for="billing_address">Adresse de facturation</label>
    <textarea class="textarea" id="billing_address" name="billing_address" rows="3"><?= e((string) ($billingAddress ?? '')) ?></textarea>
    <label class="field" for="iban">IBAN (facultatif, pour vos propres mentions)</label>
    <input class="input" id="iban" name="iban" autocomplete="off" value="<?= e((string) ($iban ?? '')) ?>">
    <div class="auth-actions">
      <button class="btn-navy" type="submit">Enregistrer la facturation</button>
    </div>
  </form>

  <div class="param-form" style="margin-top: 36px;">
    <p class="field">Vos données</p>
    <p class="espace-page-lead" style="margin-top: 0;">Téléchargez une copie JSON de votre compte, profil, commandes, messages envoyés et avis.</p>
    <form method="post" action="<?= e(url('/espace/parametres/export')) ?>">
      <?= csrf_field() ?>
      <button class="btn-ghost" type="submit">Exporter mes données</button>
    </form>
  </div>

  <form method="post" action="<?= e(url('/espace/parametres/cloture')) ?>" class="param-form param-danger" style="margin-top: 36px;">
    <?= csrf_field() ?>
    <p class="field">Clôturer le compte</p>
    <p class="espace-page-lead" style="margin-top: 0;">Impossible s'il reste une commande en cours, un litige ou une facture de commission ouverte. Les factures déjà émises sont conservées. Saisissez <strong>CLOTURER</strong> pour confirmer.</p>
    <label class="field" for="confirm">Confirmation</label>
    <input class="input" id="confirm" name="confirm" autocomplete="off">
    <div class="auth-actions">
      <button class="btn-ghost" type="submit">Clôturer mon compte</button>
    </div>
  </form>

  <?php if ($oauthProviders !== []): ?>
    <div class="param-oauth">
      <p class="field">Connexions sociales</p>
      <p class="espace-page-lead" style="margin-top: 0;">Liez Google ou Facebook pour vous connecter sans mot de passe.</p>
      <div class="oauth-stack oauth-stack-inline">
        <?php foreach ($oauthProviders as $provider):
          $linked = !empty($linkedProviders[$provider]);
          ?>
          <?php if ($linked): ?>
            <div class="oauth-btn oauth-<?= e($provider) ?> is-linked">
              <span>Compte <?= e(OAuth::label($provider)) ?> lié</span>
            </div>
          <?php else: ?>
            <a class="oauth-btn oauth-<?= e($provider) ?>" href="<?= e(url('/auth/' . $provider . '?next=parametres')) ?>">
              Lier <?= e(OAuth::label($provider)) ?>
            </a>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>
