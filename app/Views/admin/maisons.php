<?php
$onglet = (string) ($onglet ?? 'revendications');
$claims = $claims ?? [];
$fiches = $fiches ?? ['items' => [], 'total' => 0, 'pages' => 1, 'page' => 1];
$stats = $stats ?? ['total' => 0, 'published' => 0, 'claimed' => 0, 'contacts' => ['total' => 0, 'users' => 0, 'week' => 0]];
$pendingCount = (int) ($pendingCount ?? 0);
$q = (string) ($q ?? '');
$liste = (string) ($liste ?? 'toutes');
$back = '/admin/maisons-edition';
$pageUrl = static function (int $p) use ($q, $liste): string {
    $params = ['onglet' => 'fiches'];
    if ($q !== '') {
        $params['q'] = $q;
    }
    if ($liste !== 'toutes') {
        $params['liste'] = $liste;
    }
    if ($p > 1) {
        $params['page'] = $p;
    }
    return '/admin/maisons-edition?' . http_build_query($params);
};
?>
<div class="admin-page">
  <div class="admin-page-head">
    <div>
      <h1>Maisons d'édition</h1>
      <p class="admin-lead" style="margin-bottom: 0;">Annuaire public des éditeurs et demandes de prise en main. Une fiche attribuée affiche « Fiche gérée par la maison » et devient modifiable par son propriétaire.</p>
    </div>
    <a class="btn-navy" href="<?= e(url('/admin/maisons-edition/nouvelle')) ?>">Ajouter une maison</a>
  </div>

  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <div class="me-admin-kpis">
    <div class="admin-card me-admin-kpi"><strong><?= e(format_int((int) $stats['total'])) ?></strong><span>fiches · <?= e(format_int((int) $stats['published'])) ?> publiées</span></div>
    <div class="admin-card me-admin-kpi"><strong><?= e(format_int((int) $stats['claimed'])) ?></strong><span>gérées par leur maison</span></div>
    <div class="admin-card me-admin-kpi<?= $pendingCount > 0 ? ' is-hot' : '' ?>"><strong><?= $pendingCount ?></strong><span>demande<?= $pendingCount > 1 ? 's' : '' ?> à traiter</span></div>
    <div class="admin-card me-admin-kpi"><strong><?= e(format_int((int) $stats['contacts']['week'])) ?></strong><span>coordonnées consultées sur 7 jours · <?= e(format_int((int) $stats['contacts']['users'])) ?> membres au total</span></div>
  </div>

  <div class="tab-row">
    <a class="tab<?= $onglet === 'revendications' ? ' is-on' : '' ?>" href="<?= e(url($back)) ?>">Revendications<?php if ($pendingCount > 0): ?> <span class="tab-count"><?= $pendingCount ?></span><?php endif; ?></a>
    <a class="tab<?= $onglet === 'fiches' ? ' is-on' : '' ?>" href="<?= e(url($back . '?onglet=fiches')) ?>">Toutes les fiches <span class="tab-count"><?= e(format_int((int) $stats['total'])) ?></span></a>
  </div>

  <?php if ($onglet === 'revendications'): ?>
    <div class="chip-row" style="margin-bottom: 18px;">
      <?php foreach ($claimFilters ?? [] as $f): ?>
        <a class="chip<?= !empty($f['on']) ? ' is-on' : '' ?>" href="<?= e(url($f['href'])) ?>"><?= e($f['label']) ?></a>
      <?php endforeach; ?>
    </div>

    <?php if ($claims === []): ?>
      <p class="admin-muted">Aucune demande pour ce filtre.</p>
    <?php endif; ?>

    <div class="admin-stack">
      <?php foreach ($claims as $c):
          $pending = ($c['status'] ?? '') === 'pending';
          $tone = $c['status'] === 'approved' ? 'green' : ($c['status'] === 'refused' ? 'orange' : 'navy');
          ?>
        <article class="admin-card admin-dossier me-claim-card">
          <div class="admin-dossier-who">
            <?= avatar_html(['avatar_url' => $c['avatar_url'] ?? '', 'first_name' => $c['first_name'] ?? '', 'last_name' => $c['last_name'] ?? ''], 40) ?>
            <div>
              <strong><?= e((string) $c['who']) ?> <span class="me-claim-arrow">→</span> <a href="<?= e(url('/admin/maisons-edition/' . (int) $c['publisher_id'])) ?>"><?= e((string) $c['publisher_name']) ?></a></strong>
              <span><?= e((string) $c['role_title']) ?> · <?= e((string) $c['company_email']) ?><?= !empty($c['phone']) ? ' · ' . e((string) $c['phone']) : '' ?></span>
              <em>Compte <?= e((string) $c['user_email']) ?> (membre depuis le <?= e(admin_date((string) ($c['user_since'] ?? ''))) ?>) · demande du <?= e((string) $c['when']) ?><?= $c['decided_label'] !== '' ? ' · traitée le ' . e($c['decided_label']) : '' ?></em>
            </div>
            <span class="admin-pill tone-<?= e($tone) ?>"><?= e((string) $c['status_label']) ?></span>
          </div>

          <div class="me-claim-signals">
            <?php if ($c['domain_match']): ?>
              <span class="me-signal is-ok"><?= icon('check-circle', 14) ?> Domaine de l'e-mail cohérent avec le site (<?= e(\Adl\Models\Publisher::websiteHost((string) ($c['publisher_website'] ?? '')) ?: 'contact connu') ?>)</span>
            <?php else: ?>
              <span class="me-signal is-warn"><?= icon('dot', 14) ?> Domaine de l'e-mail différent du site connu<?= !empty($c['publisher_website']) ? ' (' . e(\Adl\Models\Publisher::websiteHost((string) $c['publisher_website'])) . ')' : '' ?> : vérification recommandée</span>
            <?php endif; ?>
            <?php if (!empty($c['already_owned'])): ?>
              <span class="me-signal is-warn"><?= icon('dot', 14) ?> La fiche est déjà attribuée à un autre compte</span>
            <?php endif; ?>
            <?php if (!empty($c['publisher_country'])): ?>
              <span class="me-signal"><?= e(implode(', ', array_filter([(string) ($c['publisher_city'] ?? ''), (string) $c['publisher_country']]))) ?></span>
            <?php endif; ?>
          </div>

          <blockquote class="me-claim-message"><?= nl2br(e((string) $c['message'])) ?></blockquote>

          <?php if (!$pending && !empty($c['admin_note'])): ?>
            <p class="admin-muted" style="margin: 0 0 12px;">Note : <?= e((string) $c['admin_note']) ?></p>
          <?php endif; ?>

          <div class="admin-actions">
            <a class="admin-ghost" href="<?= e(url((string) $c['publisher_href'])) ?>" target="_blank" rel="noopener">Voir la fiche</a>
            <?php if (!empty($c['publisher_website'])): ?>
              <a class="admin-ghost" href="<?= e((string) $c['publisher_website']) ?>" target="_blank" rel="noopener nofollow">Site officiel</a>
            <?php endif; ?>
            <a class="admin-ghost" href="<?= e(url('/admin/utilisateurs/' . (int) $c['user_id'])) ?>">Compte</a>
            <?php if ($pending): ?>
              <form method="post" action="<?= e(url('/admin/maisons-edition/revendications/' . (int) $c['id'])) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="status" value="approved">
                <input type="hidden" name="back" value="<?= e($back) ?>">
                <button class="btn-navy" type="submit"<?= !empty($c['already_owned']) ? ' onclick="return confirm(\'La fiche est déjà attribuée à un autre compte. Réattribuer ?\');"' : '' ?>>Attribuer la fiche</button>
              </form>
              <details class="me-refuse">
                <summary class="admin-ghost">Refuser…</summary>
                <form method="post" action="<?= e(url('/admin/maisons-edition/revendications/' . (int) $c['id'])) ?>" class="me-refuse-form">
                  <?= csrf_field() ?>
                  <input type="hidden" name="status" value="refused">
                  <input type="hidden" name="back" value="<?= e($back) ?>">
                  <textarea class="textarea" name="note" rows="2" required minlength="5" maxlength="600" placeholder="Motif transmis au demandeur (ex. : adresse e-mail non rattachée à la maison, fonction non vérifiable…)"></textarea>
                  <button class="admin-ghost" type="submit">Confirmer le refus</button>
                </form>
              </details>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

  <?php else: ?>
    <form class="admin-envois-search" method="get" action="<?= e(url($back)) ?>">
      <input type="hidden" name="onglet" value="fiches">
      <?php if ($liste !== 'toutes'): ?><input type="hidden" name="liste" value="<?= e($liste) ?>"><?php endif; ?>
      <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Nom, ville, pays, genre, e-mail de contact…">
      <button class="btn-navy" type="submit">Rechercher</button>
    </form>

    <div class="chip-row" style="margin-bottom: 18px;">
      <?php foreach ($listFilters ?? [] as $f): ?>
        <a class="chip<?= !empty($f['on']) ? ' is-on' : '' ?>" href="<?= e(url($f['href'])) ?>"><?= e($f['label']) ?></a>
      <?php endforeach; ?>
    </div>

    <p class="admin-muted" style="margin: 0 0 12px;"><?= e(format_int((int) $fiches['total'])) ?> fiche<?= (int) $fiches['total'] > 1 ? 's' : '' ?><?= $q !== '' ? ' pour « ' . e($q) . ' »' : '' ?>.</p>

    <div class="me-admin-list">
      <?php if ($fiches['items'] === []): ?>
        <p class="admin-users-empty">Aucune fiche ne correspond.</p>
      <?php endif; ?>
      <?php foreach ($fiches['items'] as $p): ?>
        <a class="me-admin-row" href="<?= e(url('/admin/maisons-edition/' . (int) $p['id'])) ?>">
          <span class="me-admin-logo"><?php if ($p['logo_src'] !== ''): ?><img src="<?= e($p['logo_src']) ?>" alt="" width="36" height="36"><?php else: ?><?= e($p['initials']) ?><?php endif; ?></span>
          <span class="me-admin-main">
            <strong><?= e($p['name']) ?></strong>
            <span><?= e($p['location_label'] !== '' ? $p['location_label'] : 'Lieu non renseigné') ?> · <?= e($p['size_label']) ?><?= $p['group_label'] !== '' ? ' · ' . e($p['group_label']) : '' ?></span>
          </span>
          <span class="me-admin-meta">
            <?php if ($p['status'] !== 'published'): ?><span class="admin-pill tone-grey">Masquée</span><?php endif; ?>
            <?php if ($p['is_claimed']): ?><span class="admin-pill tone-green">Gérée · <?= e($p['owner_name'] !== '' ? $p['owner_name'] : (string) ($p['owner_email'] ?? '')) ?></span><?php endif; ?>
            <?php if (!$p['has_contact']): ?><span class="admin-pill tone-orange">Sans coordonnées</span><?php endif; ?>
          </span>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if ((int) $fiches['pages'] > 1): ?>
      <nav class="admin-pager" aria-label="Pagination des maisons">
        <?php if ((int) $fiches['page'] > 1): ?>
          <a href="<?= e(url($pageUrl((int) $fiches['page'] - 1))) ?>" rel="prev">Précédent</a>
        <?php else: ?>
          <span class="is-off" aria-disabled="true">Précédent</span>
        <?php endif; ?>
        <span aria-current="page"><?= (int) $fiches['page'] ?> / <?= (int) $fiches['pages'] ?></span>
        <?php if ((int) $fiches['page'] < (int) $fiches['pages']): ?>
          <a href="<?= e(url($pageUrl((int) $fiches['page'] + 1))) ?>" rel="next">Suivant</a>
        <?php else: ?>
          <span class="is-off" aria-disabled="true">Suivant</span>
        <?php endif; ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</div>
