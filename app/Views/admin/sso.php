<?php
/** @var array $sso */
$sso = $sso ?? ['feature' => false, 'live_count' => 0, 'providers' => []];
$google = $sso['providers']['google'] ?? [];
$facebook = $sso['providers']['facebook'] ?? [];
$liveCount = (int) ($sso['live_count'] ?? 0);
$feature = !empty($sso['feature']);

$pillClass = static function (string $status): string {
    return match ($status) {
        'live' => 'tone-green',
        'ready' => 'tone-navy',
        'incomplete' => 'tone-orange',
        default => 'tone-grey',
    };
};
?>
<div class="admin-page">
  <h1>Connexion Google et Facebook</h1>
  <p class="admin-lead">Activez le SSO, puis chaque fournisseur indépendamment. Renseignez les clés ici : aucune modification du fichier <code>.env</code> n’est nécessaire. Les comptes e-mail / mot de passe restent toujours disponibles. Les URI de redirection ci-dessous doivent être copiées telles quelles chez Google et Meta : elles correspondent à l’adresse publique du site.</p>

  <?php if (!empty($saved)): ?><div class="flash flash-ok">Paramètres enregistrés.</div><?php endif; ?>
  <?php if (!empty($warning)): ?><div class="flash flash-warn"><?= e((string) $warning) ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <div class="admin-sso-summary">
    <?php if ($liveCount > 0): ?>
      <p>Les boutons de connexion sociale apparaissent sur <a href="<?= e(url('/connexion')) ?>">Connexion</a> et <a href="<?= e(url('/inscription')) ?>">Inscription</a><?php
        $liveLabels = [];
        foreach (['google' => 'Google', 'facebook' => 'Facebook'] as $id => $label) {
            if (!empty($sso['providers'][$id]['live'])) {
                $liveLabels[] = $label;
            }
        }
        echo $liveLabels !== [] ? ' (' . e(implode(' et ', $liveLabels)) . ').' : '.';
      ?></p>
    <?php elseif ($feature): ?>
      <p>Le SSO est activé, mais aucun fournisseur n’est prêt. Cochez Google et/ou Facebook et renseignez les deux clés de chacun.</p>
    <?php else: ?>
      <p>Le SSO est désactivé. Les boutons Google et Facebook n’apparaissent pas sur le site.</p>
    <?php endif; ?>
  </div>

  <form class="admin-sso-form" method="post" action="<?= e(url('/admin/sso')) ?>">
    <?= csrf_field() ?>

    <section class="admin-sso-card">
      <label class="admin-sso-switch">
        <input type="checkbox" name="oauth_enabled" value="1"<?= $feature ? ' checked' : '' ?>>
        <span>
          <strong>Activer le SSO</strong>
          <em>Interrupteur général. Sans lui, aucun bouton n’est affiché, même si les clés sont renseignées.</em>
        </span>
      </label>
    </section>

    <?php
    $blocks = [
        'google' => [
            'data' => $google,
            'flag' => 'oauth_google_enabled',
            'id_name' => 'google_client_id',
            'id_label' => 'Client ID',
            'secret_name' => 'google_client_secret',
            'secret_label' => 'Client secret',
            'title' => 'Google',
            'console' => 'https://console.cloud.google.com/apis/credentials',
            'console_label' => 'Google Cloud Console',
            'steps' => [
                'créez un projet si besoin, puis un ID client OAuth de type « Application Web ».',
                'Dans « URI de redirection autorisés », ajoutez exactement l’adresse ci-dessous.',
                'Collez le Client ID et le Client secret, cochez « Activer Google », puis enregistrez.',
            ],
        ],
        'facebook' => [
            'data' => $facebook,
            'flag' => 'oauth_facebook_enabled',
            'id_name' => 'facebook_app_id',
            'id_label' => 'Identifiant de l’application',
            'secret_name' => 'facebook_app_secret',
            'secret_label' => 'Clé secrète de l’application',
            'title' => 'Facebook',
            'console' => 'https://developers.facebook.com/apps/',
            'console_label' => 'Meta for Developers',
            'steps' => [
                'créez une application et ajoutez le produit « Connexion Facebook ».',
                'Dans les paramètres OAuth, ajoutez exactement l’URI de redirection ci-dessous.',
                'Collez l’identifiant et la clé secrète (Paramètres → De base), cochez « Activer Facebook », puis enregistrez. Passez l’application en mode Live pour les utilisateurs réels.',
            ],
        ],
    ];
    foreach ($blocks as $id => $block):
        $p = $block['data'];
        $status = (string) ($p['status'] ?? 'off');
        $uri = (string) ($p['redirect_uri'] ?? '');
    ?>
    <section class="admin-sso-card" data-sso-provider="<?= e($id) ?>">
      <div class="admin-sso-card-head">
        <h2><?= e($block['title']) ?></h2>
        <span class="admin-pill <?= e($pillClass($status)) ?>"><?= e((string) ($p['status_label'] ?? 'Désactivé')) ?></span>
      </div>

      <label class="admin-sso-switch">
        <input type="checkbox" name="<?= e($block['flag']) ?>" value="1"<?= !empty($p['enabled']) ? ' checked' : '' ?>>
        <span>
          <strong>Activer <?= e($block['title']) ?></strong>
          <em>Le bouton n’apparaît que si le SSO est activé et que les deux clés sont renseignées.</em>
        </span>
      </label>

      <ol class="admin-sso-steps">
        <?php foreach ($block['steps'] as $i => $step): ?>
          <li><?php if ($i === 0): ?>
            <a href="<?= e($block['console']) ?>" target="_blank" rel="noopener noreferrer"><?= e($block['console_label']) ?></a> — <?= e($step) ?>
          <?php else: ?>
            <?= e($step) ?>
          <?php endif; ?></li>
        <?php endforeach; ?>
      </ol>

      <div>
        <label class="field">URI de redirection à coller chez <?= e($block['title']) ?></label>
        <div class="admin-sso-uri">
          <code><?= e($uri) ?></code>
          <button type="button" class="admin-ghost" data-copy="<?= e($uri) ?>">Copier</button>
        </div>
      </div>

      <div>
        <label class="field" for="<?= e($block['id_name']) ?>"><?= e($block['id_label']) ?></label>
        <input class="input" id="<?= e($block['id_name']) ?>" name="<?= e($block['id_name']) ?>" value="<?= e((string) ($p['client_id'] ?? '')) ?>" autocomplete="off" spellcheck="false">
      </div>
      <div>
        <label class="field" for="<?= e($block['secret_name']) ?>"><?= e($block['secret_label']) ?></label>
        <input class="input" id="<?= e($block['secret_name']) ?>" type="password" name="<?= e($block['secret_name']) ?>" value="" placeholder="<?= !empty($p['secret_set']) ? 'Laisser vide pour conserver le secret actuel' : '' ?>" autocomplete="new-password">
        <?php if (!empty($p['secret_set'])): ?>
          <p class="field-help">Un secret est déjà enregistré. Saisissez une nouvelle valeur uniquement pour le remplacer.</p>
        <?php endif; ?>
      </div>
      <?php if ($status === 'incomplete' && !empty($p['missing'])): ?>
        <p class="admin-sso-hint"><?= e(implode(' ', $p['missing'])) ?></p>
      <?php endif; ?>
    </section>
    <?php endforeach; ?>

    <button class="btn-orange" type="submit">Enregistrer</button>
  </form>
</div>
