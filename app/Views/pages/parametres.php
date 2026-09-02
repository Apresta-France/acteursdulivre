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

  <form method="post" action="<?= e(url('/espace/parametres')) ?>" class="param-form espace-panel" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="espace-panel-head">
      <h2 class="espace-section-title">Profil</h2>
      <p class="espace-section-lead">Nom, e-mail et usages de votre compte unique.</p>
    </div>
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
      <label class="field" for="account_password">Mot de passe (pour changer d’e-mail)</label>
      <input class="input" id="account_password" type="password" name="account_password" autocomplete="current-password">
      <p class="field-help">Requis uniquement si vous modifiez l’adresse e-mail.</p>
    </div>

    <div class="espace-subblock">
      <h3 class="espace-group-title">Mes usages</h3>
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

  <form method="post" action="<?= e(url('/espace/parametres/mot-de-passe')) ?>" class="param-form espace-panel">
    <?= csrf_field() ?>
    <div class="espace-panel-head">
      <h2 class="espace-section-title">Mot de passe</h2>
      <p class="espace-section-lead">8 caractères minimum. Laissez le champ actuel vide si votre compte n'a pas encore de mot de passe.</p>
    </div>
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

  <form method="post" action="<?= e(url('/espace/parametres/notifications')) ?>" class="param-form espace-panel">
    <?= csrf_field() ?>
    <div class="espace-panel-head">
      <h2 class="espace-section-title">Notifications</h2>
      <p class="espace-section-lead">Les messages restent visibles ici. Ces cases concernent les e-mails (à l’instant et les relances).</p>
    </div>
    <label class="check-row"><input type="checkbox" name="notify_messages" value="1"<?= !empty($notifyMessages) ? ' checked' : '' ?>> Nouveau message et relances si une conversation reste sans réponse</label>
    <label class="check-row"><input type="checkbox" name="notify_jalons" value="1"<?= !empty($notifyJalons) ? ' checked' : '' ?>> Commandes : acceptation, livraison, facture de commission, facture échue</label>
    <label class="check-row"><input type="checkbox" name="notify_missions" value="1"<?= !empty($notifyMissions) ? ' checked' : '' ?>> Missions : nouvelle recherche correspondant à vos métiers, candidature, réponse, vitrine incomplète</label>
    <label class="check-row"><input type="checkbox" name="notify_newsletter" value="1"<?= !empty($notifyNewsletter) ? ' checked' : '' ?>> Lettre d'information hebdomadaire</label>
    <label class="check-row"><input type="checkbox" name="notify_forum_followed" value="1"<?= !empty($notifyForumFollowed) ? ' checked' : '' ?>> Forum : nouvelle réponse sur une discussion suivie</label>
    <label class="check-row"><input type="checkbox" name="notify_forum_mine" value="1"<?= !empty($notifyForumMine) ? ' checked' : '' ?>> Forum : nouvelle réponse sur une discussion que j’ai ouverte</label>
    <div class="auth-actions">
      <button class="btn-navy" type="submit">Enregistrer les notifications</button>
    </div>
  </form>

  <form method="post" action="<?= e(url('/espace/parametres/facturation')) ?>" class="param-form espace-panel" data-billing-ids>
    <?= csrf_field() ?>
    <div class="espace-panel-head">
      <h2 class="espace-section-title">Facturation</h2>
      <p class="espace-section-lead">Mentions reprises sur votre facture de commission. Le SIREN (9 chiffres) est l’identifiant exigé par la facturation électronique française. L'IBAN n'est jamais utilisé pour encaisser une mission.</p>
    </div>
    <div class="auth-name-grid">
      <div>
        <label class="field" for="company_name">Raison sociale</label>
        <input class="input" id="company_name" name="company_name" value="<?= e((string) ($companyName ?? '')) ?>">
      </div>
      <div>
        <label class="field" for="legal_form">Forme juridique</label>
        <select class="input" id="legal_form" name="legal_form">
          <option value="">—</option>
          <?php foreach (($legalForms ?? []) as $code => $label): ?>
            <option value="<?= e((string) $code) ?>"<?= ($legalForm ?? '') === $code ? ' selected' : '' ?>><?= e((string) $label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="auth-name-grid">
      <div>
        <label class="field" for="siren">SIREN</label>
        <input class="input" id="siren" name="siren" inputmode="numeric" autocomplete="off" maxlength="11" value="<?= e((string) ($siren ?? '')) ?>">
        <p class="field-help">9 chiffres. C’est le numéro de routage de la facture électronique, sur votre Kbis ou avis INSEE.</p>
      </div>
      <div>
        <label class="field" for="siret">SIRET</label>
        <input class="input" id="siret" name="siret" inputmode="numeric" autocomplete="off" maxlength="17" value="<?= e((string) ($siret ?? '')) ?>">
        <p class="field-help">14 chiffres. Les 9 premiers remplissent le SIREN s’il est vide.</p>
      </div>
    </div>
    <div class="auth-name-grid">
      <div>
        <label class="field" for="vat_number">N° TVA intracommunautaire</label>
        <input class="input" id="vat_number" name="vat_number" autocomplete="off" value="<?= e((string) ($vatNumber ?? '')) ?>" placeholder="FRXX999999999">
      </div>
      <div>
        <label class="field" for="einvoice_routing">Code de routage (facultatif)</label>
        <input class="input" id="einvoice_routing" name="einvoice_routing" autocomplete="off" maxlength="50" value="<?= e((string) ($einvoiceRouting ?? '')) ?>">
        <p class="field-help">Uniquement si votre plateforme de dématérialisation (PDP) vous en a attribué un. Sinon le SIREN suffit.</p>
      </div>
    </div>
    <label class="check-row">
      <input type="checkbox" name="vat_exempt" value="1"<?= !empty($vatExempt) ? ' checked' : '' ?>>
      Franchise en base de TVA (art. 293 B du CGI)
    </label>
    <label class="field" for="billing_address">Adresse de facturation</label>
    <textarea class="textarea" id="billing_address" name="billing_address" rows="3"><?= e((string) ($billingAddress ?? '')) ?></textarea>
    <label class="field" for="iban">IBAN (facultatif, pour vos propres mentions)</label>
    <input class="input" id="iban" name="iban" autocomplete="off" value="<?= e((string) ($iban ?? '')) ?>">
    <div class="auth-actions">
      <button class="btn-navy" type="submit">Enregistrer la facturation</button>
    </div>
  </form>

  <div class="param-form espace-panel">
    <div class="espace-panel-head">
      <h2 class="espace-section-title">Vos données</h2>
      <p class="espace-section-lead">Téléchargez une copie JSON de votre compte, profil, commandes, messages envoyés et avis.</p>
    </div>
    <form method="post" action="<?= e(url('/espace/parametres/export')) ?>">
      <?= csrf_field() ?>
      <button class="btn-ghost" type="submit">Exporter mes données</button>
    </form>
  </div>

  <form method="post" action="<?= e(url('/espace/parametres/cloture')) ?>" class="param-form espace-panel param-danger">
    <?= csrf_field() ?>
    <div class="espace-panel-head">
      <h2 class="espace-section-title">Clôturer le compte</h2>
      <p class="espace-section-lead">Impossible s'il reste une commande en cours, un litige ou une facture de commission ouverte. Les factures déjà émises sont conservées. Saisissez <strong>CLOTURER</strong> pour confirmer.</p>
    </div>
    <label class="field" for="confirm">Confirmation</label>
    <input class="input" id="confirm" name="confirm" autocomplete="off">
    <div class="auth-actions">
      <button class="btn-ghost" type="submit">Clôturer mon compte</button>
    </div>
  </form>

  <?php if ($oauthProviders !== []): ?>
    <div class="param-oauth espace-panel">
      <div class="espace-panel-head">
        <h2 class="espace-section-title">Connexions sociales</h2>
        <p class="espace-section-lead">Liez Google ou Facebook pour vous connecter sans mot de passe.</p>
      </div>
      <div class="oauth-stack oauth-stack-inline">
        <?php
          $hasPassword = !empty($hasPassword);
          $linkedCount = (int) !empty($linkedProviders['google']) + (int) !empty($linkedProviders['facebook']);
        ?>
        <?php foreach ($oauthProviders as $provider):
          $linked = !empty($linkedProviders[$provider]);
          $canUnlink = $linked && ($hasPassword || $linkedCount > 1);
          ?>
          <?php if ($linked): ?>
            <div class="oauth-btn oauth-<?= e($provider) ?> is-linked">
              <span>Compte <?= e(OAuth::label($provider)) ?> lié</span>
              <?php if ($canUnlink): ?>
                <form method="post" action="<?= e(url('/espace/parametres/oauth/unlink')) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="provider" value="<?= e($provider) ?>">
                  <button type="submit" class="oauth-unlink">Délier</button>
                </form>
              <?php endif; ?>
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
