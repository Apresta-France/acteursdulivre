<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e(($title ?? 'Administration') . ' — Acteurs du Livre') ?></title>
  <link rel="preload" href="<?= e(asset('fonts/space-grotesk-700.woff2')) ?>" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>?v=m91">
  <link rel="icon" href="<?= e(asset('img/favicon.ico')) ?>?v=3" sizes="any">
  <link rel="icon" type="image/png" href="<?= e(asset('img/favicon-32x32.png')) ?>?v=3" sizes="32x32">
  <link rel="apple-touch-icon" href="<?= e(asset('img/apple-touch-icon.png')) ?>?v=3">
</head>
<body<?= \Adl\Core\Auth::isImpersonating() ? ' class="has-impersonation-bar"' : '' ?>>
  <?php require ADL_ROOT . '/app/Views/partials/impersonation-bar.php'; ?>
  <div class="admin-backdrop" data-admin-close hidden></div>
  <div class="admin-outer">
    <div class="admin-canvas">
      <aside class="admin-aside">
        <div class="admin-brand">
          <a href="<?= e(url('/')) ?>" class="admin-logo">
            <img src="<?= e(asset('img/logo-inv.png')) ?>?v=5" alt="acteursdulivre.fr">
          </a>
          <span class="admin-badge">Admin</span>
        </div>

        <?php foreach ($nav ?? [] as $n): ?>
          <div>
            <?php if (!empty($n['group'])): ?>
              <div class="admin-group"><?= e($n['group']) ?></div>
            <?php endif; ?>
            <a href="<?= e(url($n['href'])) ?>" class="admin-link<?= !empty($n['active']) ? ' is-active' : '' ?>">
              <span><?= e($n['label']) ?></span>
              <?php if (!empty($n['badge'])): ?>
                <span class="admin-count" aria-label="<?= e($n['badge']) ?> en attente"><?= e($n['badge']) ?></span>
              <?php endif; ?>
            </a>
          </div>
        <?php endforeach; ?>

        <div class="admin-user">
          <span class="avatar" style="<?= e(avatar_style($adminInitials ?? 'AD', 34)) ?>"><?= e($adminInitials ?? 'AD') ?></span>
          <div>
            <div class="admin-user-name"><?= e($adminName ?? 'Administration') ?></div>
            <div class="admin-user-role"><?= e($adminRole ?? 'Accès complet') ?></div>
          </div>
        </div>
      </aside>

      <main class="admin-content">
        <header class="admin-top">
          <button type="button" class="admin-nav-toggle" data-admin-toggle aria-expanded="false" aria-label="Ouvrir le menu admin">
            <span></span><span></span><span></span>
          </button>
          <form class="admin-search" action="<?= e(url('/admin/utilisateurs')) ?>" method="get">
            <input type="search" name="q" value="<?= e($query ?? '') ?>" placeholder="Rechercher un profil, une commande, une mission…">
          </form>
          <span class="admin-countdown"><?= e($adminCountdown ?? 'Pré-ouverture · ouverture clients en octobre 2026') ?></span>
          <a class="admin-ghost" href="<?= e(url('/admin/journal')) ?>">Journal d'audit</a>
          <a class="admin-ghost" href="<?= e(url('/espace')) ?>">Mon espace</a>
          <form method="post" action="<?= e(url('/deconnexion')) ?>">
            <?= csrf_field() ?>
            <button class="admin-ghost" type="submit">Déconnexion</button>
          </form>
        </header>
        <?= $content ?? '' ?>
      </main>
    </div>
  </div>
  <script src="<?= e(asset('js/app.js')) ?>?v=m75"></script>
</body>
</html>
