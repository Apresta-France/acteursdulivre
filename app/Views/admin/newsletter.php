<?php
use Adl\Data\AdminCatalog;

$s = $settings ?? [];
$smtp = $smtp ?? [];
$onglet = (string) ($onglet ?? 'lettres');
$nlTabs = $nlTabs ?? [];
$letters = $letters ?? [];
$days = [
    '1' => 'Lundi',
    '2' => 'Mardi',
    '3' => 'Mercredi',
    '4' => 'Jeudi',
    '5' => 'Vendredi',
    '6' => 'Samedi',
    '7' => 'Dimanche',
];
$preview = $preview ?? null;
$campaigns = $campaigns ?? [];
$subscribers = $subscribers ?? [];
$statusLabels = [
    'pending' => ['En attente', 'orange'],
    'confirmed' => ['Confirmé', 'green'],
    'unsubscribed' => ['Désinscrit', 'grey'],
    'queued' => ['En file', 'navy'],
    'sending' => ['Envoi', 'orange'],
    'sent' => ['Envoyée', 'green'],
    'empty' => ['Vide', 'grey'],
    'failed' => ['Échec', 'orange'],
    'skipped' => ['Ignoré', 'grey'],
];
?>
<div class="admin-page">
  <div class="admin-page-head">
    <div>
      <h1>Newsletter</h1>
      <p class="admin-lead" style="margin-bottom: 0;">Concevez des lettres, envoyez-les manuellement, et paramétrez la boîte SMTP dédiée. La lettre hebdomadaire automatique reste disponible.</p>
    </div>
    <a class="btn-orange" href="<?= e(url('/admin/newsletter/nouvelle')) ?>">Nouvelle lettre</a>
  </div>

  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div><?php endif; ?>
  <?php if (!empty($tested)): ?><div class="flash flash-ok"><?= e((string) $tested) ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <div class="admin-nl-kpis">
    <div><strong><?= (int) ($counts['letters'] ?? 0) ?></strong><span>lettres conçues</span></div>
    <div><strong><?= (int) ($counts['confirmed'] ?? 0) ?></strong><span>abonnés confirmés</span></div>
    <div><strong><?= (int) ($counts['queue'] ?? 0) ?></strong><span>envois encore en file</span></div>
    <div><strong><?= !empty($s['newsletter_enabled']) ? 'Oui' : 'Non' ?></strong><span>envoi automatique</span></div>
  </div>

  <div class="chip-row" style="margin-bottom: 22px;">
    <?php foreach ($nlTabs as $tab): ?>
      <a class="chip<?= !empty($tab['on']) ? ' is-on' : '' ?>" href="<?= e(url($tab['href'])) ?>"><?= e($tab['label']) ?></a>
    <?php endforeach; ?>
  </div>

  <?php if ($onglet === 'lettres'): ?>
    <section class="admin-nl-block">
      <div class="admin-page-head">
        <div>
          <h2 class="admin-nl-h2" style="margin-top:0;">Lettres</h2>
          <p class="field-help" style="margin:4px 0 0;">Composez le contenu bloc par bloc, enregistrez un brouillon, puis lancez l’envoi.</p>
        </div>
        <form method="post" action="<?= e(url('/admin/newsletter/depuis-hebdo')) ?>">
          <?= csrf_field() ?>
          <button class="btn-navy" type="submit">Partir de la lettre hebdo</button>
        </form>
      </div>
      <?php if ($letters === []): ?>
        <p class="admin-users-empty">Aucune lettre pour le moment. Créez-en une, ou partez du modèle hebdomadaire.</p>
      <?php else: ?>
        <div class="admin-users-wrap">
          <div class="admin-users-head admin-nl-letters-head">
            <span>Sujet</span>
            <span>Statut</span>
            <span>Mise à jour</span>
            <span></span>
          </div>
          <?php foreach ($letters as $letter):
              $tone = (string) ($letter['status_tone'] ?? 'navy');
              ?>
            <a class="admin-users-row admin-nl-letters-row" href="<?= e(url('/admin/newsletter/lettre/' . (int) $letter['id'])) ?>">
              <span><?= e((string) ($letter['subject'] ?? '')) ?></span>
              <span><span class="admin-pill" style="<?= e(AdminCatalog::pill($tone)) ?>"><?= e((string) ($letter['status_label'] ?? '')) ?></span></span>
              <span><?= e((string) ($letter['updated_at'] ?? '')) ?></span>
              <span>Modifier</span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <section class="admin-nl-block">
      <h2>Derniers envois</h2>
      <?php if ($campaigns === []): ?>
        <p class="admin-users-empty">Aucun envoi pour le moment.</p>
      <?php else: ?>
        <div class="admin-users-wrap">
          <div class="admin-users-head admin-nl-head">
            <span>Date</span>
            <span>Sujet</span>
            <span>Source</span>
            <span>Statut</span>
          </div>
          <?php foreach ($campaigns as $c):
              $st = (string) ($c['status'] ?? '');
              [$label, $tone] = $statusLabels[$st] ?? [$st, 'grey'];
              ?>
            <div class="admin-users-row admin-nl-row-static">
              <span><?= e((string) ($c['created_at'] ?? '')) ?></span>
              <span><?= e((string) ($c['subject'] ?? '')) ?><br><em><?= (int) ($c['sent_count'] ?? 0) ?> envoyés · <?= (int) ($c['fail_count'] ?? 0) ?> échecs</em></span>
              <span><?= e(($c['source'] ?? '') === 'weekly' ? 'Hebdo' : 'Manuel') ?></span>
              <span><span class="admin-pill" style="<?= e(AdminCatalog::pill($tone)) ?>"><?= e($label) ?></span></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <?php if ($onglet === 'reglages'): ?>
  <form class="admin-form admin-nl-form" method="post" action="<?= e(url('/admin/newsletter')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="section" value="settings">

    <h2 class="admin-nl-h2">SMTP dédié à la newsletter</h2>
    <p class="field-help" style="margin-top:0;">Cette boîte sert uniquement les lettres. Si l’hôte est vide, l’envoi utilise le SMTP général.</p>
    <div><label class="field">Hôte</label><input class="input" name="newsletter_smtp_host" value="<?= e((string) ($smtp['newsletter_smtp_host'] ?? '')) ?>" placeholder="smtp.exemple.fr" autocomplete="off"></div>
    <div class="admin-nl-row">
      <div><label class="field">Port</label><input class="input" name="newsletter_smtp_port" value="<?= e((string) ($smtp['newsletter_smtp_port'] ?? '587')) ?>"></div>
      <div>
        <label class="field">Chiffrement</label>
        <select class="input" name="newsletter_smtp_encryption">
          <?php $enc = (string) ($smtp['newsletter_smtp_encryption'] ?? 'tls'); ?>
          <option value="tls"<?= $enc === 'tls' ? ' selected' : '' ?>>TLS (STARTTLS, 587)</option>
          <option value="ssl"<?= $enc === 'ssl' ? ' selected' : '' ?>>SSL (465)</option>
          <option value=""<?= $enc === '' ? ' selected' : '' ?>>Aucun</option>
        </select>
      </div>
    </div>
    <div><label class="field">Utilisateur</label><input class="input" name="newsletter_smtp_username" value="<?= e((string) ($smtp['newsletter_smtp_username'] ?? '')) ?>" autocomplete="off"></div>
    <div>
      <label class="field">Mot de passe</label>
      <input class="input" type="password" name="newsletter_smtp_password" value="" placeholder="<?= !empty($smtp['newsletter_smtp_password_set']) ? 'Laisser vide pour conserver le mot de passe actuel' : '' ?>" autocomplete="new-password">
    </div>
    <div><label class="field">E-mail expéditeur</label><input class="input" type="email" name="newsletter_smtp_from_address" value="<?= e((string) ($smtp['newsletter_smtp_from_address'] ?? '')) ?>"></div>
    <div><label class="field">Nom de l’expéditeur</label><input class="input" name="newsletter_smtp_from_name" value="<?= e((string) ($smtp['newsletter_smtp_from_name'] ?? '')) ?>"></div>
    <?php if (!empty($smtp['uses_fallback'])): ?>
      <p class="field-help">Aucun hôte newsletter : repli sur le SMTP général.</p>
    <?php elseif (empty($smtp['uses_smtp'])): ?>
      <p class="field-help">Aucun SMTP : les envois sont écrits dans <code>storage/mail</code>.</p>
    <?php endif; ?>

    <h2 class="admin-nl-h2">Lettre hebdomadaire automatique</h2>
    <label class="check-row">
      <input type="checkbox" name="newsletter_enabled" value="1"<?= !empty($s['newsletter_enabled']) ? ' checked' : '' ?>>
      Activer l’envoi automatique hebdomadaire
    </label>

    <div class="admin-nl-row">
      <div>
        <label class="field" for="newsletter_weekday">Jour d’envoi</label>
        <select class="input" id="newsletter_weekday" name="newsletter_weekday">
          <?php foreach ($days as $n => $label): ?>
            <option value="<?= e($n) ?>"<?= ((string) ($s['newsletter_weekday'] ?? '3') === $n) ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="field" for="newsletter_hour">Heure (Europe/Paris)</label>
        <input class="input" id="newsletter_hour" name="newsletter_hour" type="number" min="0" max="23" value="<?= e((string) ($s['newsletter_hour'] ?? '8')) ?>">
      </div>
      <div>
        <label class="field" for="newsletter_batch_size">Envoi par lots (messages / passage)</label>
        <input class="input" id="newsletter_batch_size" name="newsletter_batch_size" type="number" min="1" max="200" value="<?= e((string) ($s['newsletter_batch_size'] ?? '25')) ?>">
      </div>
    </div>
    <p class="field-help">Le cron horaire compose la lettre le jour choisi, puis envoie par lots à chaque passage suivant.</p>

    <p class="field" style="margin-top:8px;">Contenu de la lettre automatique</p>
    <label class="check-row"><input type="checkbox" name="newsletter_include_missions" value="1"<?= !empty($s['newsletter_include_missions']) ? ' checked' : '' ?>> Dernières recherches / projets publiés</label>
    <label class="check-row"><input type="checkbox" name="newsletter_include_people" value="1"<?= !empty($s['newsletter_include_people']) ? ' checked' : '' ?>> Derniers profils inscrits (vitrines publiques)</label>
    <label class="check-row"><input type="checkbox" name="newsletter_include_url" value="1"<?= !empty($s['newsletter_include_url']) ? ' checked' : '' ?>> Brief généré automatiquement à partir d’une URL</label>

    <div>
      <label class="field" for="newsletter_source_url">URL source</label>
      <input class="input" id="newsletter_source_url" name="newsletter_source_url" type="text" placeholder="https://acteursdulivre.fr/journal/…" value="<?= e((string) ($s['newsletter_source_url'] ?? '')) ?>">
      <p class="field-help">Article du journal, page du site, flux RSS, ou page https externe. Vide = trois derniers articles du journal.</p>
    </div>

    <button class="btn-orange" type="submit">Enregistrer</button>
  </form>

  <div class="admin-nl-actions">
    <form method="post" action="<?= e(url('/admin/newsletter/apercu')) ?>">
      <?= csrf_field() ?>
      <button class="btn-navy" type="submit">Aperçu de la prochaine lettre auto</button>
    </form>
    <form method="post" action="<?= e(url('/admin/newsletter/test')) ?>">
      <?= csrf_field() ?>
      <input class="input" type="email" name="test_email" value="<?= e(auth_user()['email'] ?? '') ?>" required>
      <button class="btn-navy" type="submit">Tester le SMTP</button>
    </form>
    <form method="post" action="<?= e(url('/admin/newsletter/envoyer')) ?>" onsubmit="return confirm('Mettre la lettre automatique en file d’envoi pour tous les abonnés confirmés ?');">
      <?= csrf_field() ?>
      <button class="btn-orange" type="submit">Envoyer la lettre auto</button>
    </form>
  </div>

  <?php if (is_array($preview)): ?>
    <section class="admin-nl-preview">
      <h2>Aperçu</h2>
      <p><strong><?= e((string) ($preview['subject'] ?? '')) ?></strong></p>
      <?php if (!empty($preview['empty'])): ?>
        <p class="field-help">Aucun bloc pour l’instant : la lettre partirait presque vide.</p>
      <?php endif; ?>
      <div class="admin-nl-preview-body"><?= $preview['html'] ?? '' ?></div>
    </section>
  <?php endif; ?>
  <?php endif; ?>

  <?php if ($onglet === 'abonnes'): ?>
  <section class="admin-nl-block">
    <h2>Abonnés</h2>
    <p class="field-help"><a href="<?= e(url('/admin/newsletter/export')) ?>">Exporter en CSV</a></p>
    <?php if ($subscribers === []): ?>
      <p class="admin-users-empty">Personne n’est encore inscrit.</p>
    <?php else: ?>
      <div class="admin-users-wrap">
        <div class="admin-users-head admin-nl-head">
          <span>E-mail</span>
          <span>Statut</span>
          <span>Depuis</span>
          <span></span>
        </div>
        <?php foreach ($subscribers as $sub):
            $st = (string) ($sub['status'] ?? '');
            [$label, $tone] = $statusLabels[$st] ?? [$st, 'grey'];
            ?>
          <div class="admin-users-row admin-nl-row-static">
            <span><?= e((string) ($sub['email'] ?? '')) ?></span>
            <span><span class="admin-pill" style="<?= e(AdminCatalog::pill($tone)) ?>"><?= e($label) ?></span></span>
            <span><?= e((string) ($sub['created_at'] ?? '')) ?></span>
            <span>
              <?php if ($st === 'confirmed'): ?>
                <form method="post" action="<?= e(url('/admin/newsletter/desinscrire')) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="email" value="<?= e((string) $sub['email']) ?>">
                  <button class="admin-ghost" type="submit">Désinscrire</button>
                </form>
              <?php endif; ?>
            </span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>
</div>
