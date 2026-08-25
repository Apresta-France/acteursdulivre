<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(($title ?? 'Administration') . ' — Acteurs du Livre') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
  <link rel="icon" href="<?= e(asset('img/logo.png')) ?>">
</head>
<body>
  <div class="admin-outer">
    <div class="admin-canvas">
      <aside class="admin-aside">
        <div class="admin-brand">
          <a href="<?= e(url('/')) ?>" class="admin-logo">
            <img src="<?= e(asset('img/logo.png')) ?>" alt="acteursdulivre.fr">
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
                <span class="admin-count"><?= e($n['badge']) ?></span>
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
          <form class="admin-search" action="<?= e(url('/admin/utilisateurs')) ?>" method="get">
            <input type="search" name="q" value="<?= e($query ?? '') ?>" placeholder="Rechercher un profil, une commande, une mission…">
          </form>
          <span class="admin-countdown">Pré-ouverture · J-37 avant l'ouverture clients</span>
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
  <script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
