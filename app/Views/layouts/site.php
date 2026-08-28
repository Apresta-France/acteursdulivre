<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="google-site-verification" content="4X7SyO1uk8XOvTr1QK5qLEe4qtWYmzp6qgG_nbeUSiI">
  <title><?= e(($title ?? 'Acteurs du Livre') . ' — acteursdulivre.fr') ?></title>
  <?php
    $meta = $meta ?? [];
    $metaTitle = (string) ($meta['title'] ?? (($title ?? 'Acteurs du Livre') . ' — acteursdulivre.fr'));
    $metaDesc = (string) ($meta['description'] ?? 'La place de marché des métiers du livre. Auteurs et professionnels, sans IA générative.');
    $metaUrl = (string) ($meta['url'] ?? \Adl\Data\Share::current());
    $metaImage = (string) ($meta['image'] ?? asset('img/logo.png'));
    $metaType = (string) ($meta['type'] ?? 'website');
  ?>
  <meta name="description" content="<?= e($metaDesc) ?>">
  <link rel="canonical" href="<?= e($metaUrl) ?>">
  <meta property="og:type" content="<?= e($metaType) ?>">
  <meta property="og:site_name" content="acteursdulivre.fr">
  <meta property="og:title" content="<?= e($metaTitle) ?>">
  <meta property="og:description" content="<?= e($metaDesc) ?>">
  <meta property="og:url" content="<?= e($metaUrl) ?>">
  <meta property="og:image" content="<?= e($metaImage) ?>">
  <meta property="og:locale" content="fr_FR">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($metaTitle) ?>">
  <meta name="twitter:description" content="<?= e($metaDesc) ?>">
  <meta name="twitter:image" content="<?= e($metaImage) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>?v=m36">
  <link rel="icon" href="<?= e(asset('img/favicon.ico')) ?>?v=3" sizes="any">
  <link rel="icon" type="image/png" href="<?= e(asset('img/favicon-32x32.png')) ?>?v=3" sizes="32x32">
  <link rel="apple-touch-icon" href="<?= e(asset('img/apple-touch-icon.png')) ?>?v=3">
</head>
<body>
  <div class="nav-backdrop" data-nav-close hidden></div>
  <div class="site-shell">
    <div class="site-canvas">
      <div class="preopen">
        <span class="preopen-badge">Pré-ouverture</span>
        <span class="preopen-text">La plateforme accueille dès maintenant les <strong>auteurs et les professionnels du livre</strong> — ouverture aux clients en octobre 2026. Sans IA générative, jamais.</span>
        <span class="preopen-short">Inscriptions ouvertes — clients en octobre 2026. Sans IA générative.</span>
        <a href="<?= e(url('/inscription')) ?>">Réserver ma place</a>
      </div>
      <div class="topbar">
        <span class="topbar-stats"><?= e((string) ($topbarStats ?? 'Commission 8 % · devis gratuits')) ?></span>
        <div class="topbar-links">
          <a href="<?= e(url('/aide')) ?>">Aide</a>
          <span>Français · EUR</span>
          <?php if (!empty($logged)): ?>
            <a href="<?= e(url('/espace')) ?>"><?= e($userFirst ?? 'Mon espace') ?></a>
            <?php if (!empty($isAdmin)): ?>
              <a href="<?= e(url('/admin')) ?>">Admin</a>
            <?php endif; ?>
            <form method="post" action="<?= e(url('/deconnexion')) ?>" style="display:inline;">
              <?= csrf_field() ?>
              <button type="submit" style="background:none;border:0;color:#B9CBDC;font-size:13px;padding:0;">Déconnexion</button>
            </form>
          <?php endif; ?>
        </div>
      </div>

      <header class="site-header">
        <a href="<?= e(url('/')) ?>" class="brand">
          <picture>
            <source media="(max-width: 768px)" srcset="<?= e(asset('img/logo-mark.png')) ?>?v=1">
            <img src="<?= e(asset('img/logo.png')) ?>?v=4" alt="acteursdulivre.fr">
          </picture>
        </a>
        <form class="search" action="<?= e(url('/recherche')) ?>" method="get" data-live-search data-api="<?= e(url('/api/recherche')) ?>" autocomplete="off">
          <input type="search" name="q" value="<?= e($query ?? '') ?>" placeholder="correcteur roman, illustration jeunesse…" autocomplete="off" data-live-input>
          <button type="submit">Chercher</button>
          <div class="search-suggest" data-live-panel hidden></div>
        </form>
        <?php if (!empty($logged)): ?>
          <div class="user-menu">
            <button type="button" class="user-chip" data-user-menu aria-expanded="false" aria-controls="user-menu-panel" aria-haspopup="true">
              <?php if (!empty($userAvatarUrl)): ?>
                <img class="avatar avatar-photo" src="<?= e($userAvatarUrl) ?>" alt="" width="38" height="38">
              <?php else: ?>
                <span class="avatar" style="<?= e(avatar_style($userInitials ?? 'AD', 38)) ?>"><?= e($userInitials ?? 'AD') ?></span>
              <?php endif; ?>
              <span class="user-chip-name"><?= e($userFirst ?? '') ?></span>
            </button>
            <div class="user-menu-panel" id="user-menu-panel" hidden>
              <a href="<?= e(url('/espace')) ?>">Tableau de bord</a>
              <?php foreach ($espaceNav ?? [] as $group): ?>
                <?php
                  $groupItems = array_values(array_filter($group['items'] ?? [], static fn (array $item): bool => ($item['href'] ?? '') !== '/espace'));
                ?>
                <?php if ($groupItems !== []): ?>
                  <?php if (($group['title'] ?? '') !== '' && ($group['title'] ?? '') !== 'Espace'): ?>
                    <div class="user-menu-title"><?= e($group['title']) ?></div>
                  <?php else: ?>
                    <div class="user-menu-sep"></div>
                  <?php endif; ?>
                  <?php foreach ($groupItems as $item): ?>
                    <a href="<?= e(url($item['href'])) ?>"<?= !empty($item['active']) ? ' class="is-active"' : '' ?>><?= e($item['label']) ?></a>
                  <?php endforeach; ?>
                <?php endif; ?>
              <?php endforeach; ?>
              <?php if (!empty($isAdmin)): ?>
                <div class="user-menu-sep"></div>
                <a href="<?= e(url('/admin')) ?>">Administration</a>
              <?php endif; ?>
              <div class="user-menu-sep"></div>
              <form method="post" action="<?= e(url('/deconnexion')) ?>">
                <?= csrf_field() ?>
                <button type="submit">Déconnexion</button>
              </form>
            </div>
          </div>
        <?php endif; ?>
        <button type="button" class="nav-toggle" data-nav-toggle aria-expanded="false" aria-controls="header-panel" aria-label="Ouvrir le menu">
          <span></span><span></span><span></span>
        </button>
        <div class="header-panel" id="header-panel">
          <?php if (!empty($logged)): ?>
            <nav class="header-nav">
              <?php if (!empty($headerCta)): ?>
                <a href="<?= e(url($headerCta['href'])) ?>"><?= e($headerCta['label']) ?></a>
              <?php endif; ?>
              <?php
                $headerUnreadMessages = (int) ($unreadMessages ?? 0);
                $headerUnreadAlerts = (int) ($unreadAlerts ?? 0);
              ?>
              <div class="header-icons">
                <a href="<?= e(url('/espace/messages')) ?>" class="header-icon-link" aria-label="<?= $headerUnreadMessages > 0 ? 'Messages (' . $headerUnreadMessages . ' non lus)' : 'Messages' ?>" title="Messages">
                  <?= icon('chat', 20) ?>
                  <?php if ($headerUnreadMessages > 0): ?><span class="badge-orange"><?= $headerUnreadMessages ?></span><?php endif; ?>
                </a>
                <a href="<?= e(url('/espace/notifications')) ?>" class="header-icon-link" aria-label="<?= $headerUnreadAlerts > 0 ? 'Notifications (' . $headerUnreadAlerts . ')' : 'Notifications' ?>" title="Notifications">
                  <?= icon('bell', 20) ?>
                  <?php if ($headerUnreadAlerts > 0): ?><span class="badge-soft"><?= $headerUnreadAlerts ?></span><?php endif; ?>
                </a>
              </div>
            </nav>
          <?php else: ?>
            <nav class="header-nav">
              <a href="<?= e(url('/comment-ca-marche')) ?>">Comment ça marche</a>
              <a href="<?= e(url('/missions')) ?>">Appels d'offres</a>
              <a href="<?= e(url('/connexion')) ?>">Se connecter</a>
            </nav>
            <a class="btn-navy" href="<?= e(url('/inscription')) ?>">Créer un compte</a>
          <?php endif; ?>
        </div>
      </header>

      <div class="rail">
        <?php foreach ($rail ?? [] as $r): ?>
          <a href="<?= e(url($r['href'] ?? '/recherche')) ?>" style="<?= e($r['style'] ?? '') ?>"><?= e($r['name'] ?? '') ?></a>
        <?php endforeach; ?>
        <div class="rail-spacer"></div>
        <button type="button" class="mega-btn" data-mega-toggle style="<?= e($megaBtnStyle ?? '') ?>">Tous les métiers ▾</button>
      </div>

      <div class="mega" hidden>
        <?php foreach ($mega ?? [] as $m): ?>
          <div>
            <div class="mega-group"><?= e($m['group']) ?></div>
            <div class="mega-items">
              <?php foreach ($m['items'] as $item): ?>
                <a href="<?= e(url('/recherche?q=' . rawurlencode($item))) ?>"><?= e($item) ?></a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <main>
        <?php if (!empty($inEspace)): ?>
          <div class="espace-shell r-done">
            <?php require ADL_ROOT . '/app/Views/partials/espace-nav.php'; ?>
            <div class="espace-main">
              <?= $content ?? '' ?>
            </div>
          </div>
        <?php else: ?>
          <?= $content ?? '' ?>
        <?php endif; ?>
      </main>

      <footer class="site-footer">
        <div class="footer-news">
          <div>
            <div class="footer-news-title">Le point sur les métiers du livre, une fois par mois</div>
            <div>Tarifs observés, nouveaux prestataires, contrats types. Pas de publicité, désinscription en un clic.</div>
          </div>
          <form class="footer-news-form" action="<?= e(url('/contact')) ?>" method="get">
            <input type="email" name="email" placeholder="votre@email.fr">
            <button type="submit">S'inscrire</button>
          </form>
        </div>
        <div class="footer-cols">
          <div>
            <div class="footer-logo">
              <a href="<?= e(url('/')) ?>">
                <img src="<?= e(asset('img/logo-inv.png')) ?>?v=5" alt="acteursdulivre.fr">
              </a>
            </div>
            <p>La place de marché des métiers du livre. Auteurs, correcteurs, bêta-lecteurs, illustrateurs, traducteurs, maquettistes, éditeurs, imprimeurs, presse, libraires, narrateurs, agents, salons.</p>
            <div class="socials" data-share data-url="<?= e(\Adl\Data\Share::absolute('/')) ?>" data-title="acteursdulivre.fr" data-text="La place de marché des métiers du livre.">
              <?php foreach ($socials ?? [] as $s): ?>
                <?php if (is_array($s)): ?>
                  <?php $sid = (string) ($s['id'] ?? ''); ?>
                  <a href="<?= e((string) ($s['href'] ?? '#')) ?>"
                     data-share-network="<?= e($sid) ?>"
                     <?= !empty($s['copy']) ? 'data-share-copy' : 'target="_blank" rel="noopener noreferrer"' ?>
                     title="<?= e((string) ($s['label'] ?? $s['short'] ?? '')) ?>"
                     aria-label="<?= e((string) ($s['label'] ?? $s['short'] ?? '')) ?>"><?= icon('share-' . $sid, 16) ?></a>
                <?php else: ?>
                  <span><?= e((string) $s) ?></span>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
          </div>
          <?php foreach ($footerCols ?? [] as $c): ?>
            <div>
              <div class="footer-col-title"><?= e($c['title']) ?></div>
              <div class="footer-links">
                <?php foreach ($c['links'] as $l): ?>
                  <a href="<?= e(url($l['href'])) ?>"><?= e($l['label']) ?></a>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="footer-metiers">
          <div class="footer-col-title muted">Métiers</div>
          <div class="metier-links">
            <?php foreach ($footerMetiers ?? [] as $m): ?>
              <a href="<?= e(url('/metiers/' . slugify($m))) ?>"><?= e($m) ?></a>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="footer-legal">
          <span>© <?= date('Y') ?> acteursdulivre.fr — EDITIONS TESSERACT, SAS au capital de 6 100 €, Sainghin-en-Weppes</span>
          <div>
            <a href="<?= e(url('/mentions-legales')) ?>">Mentions légales</a>
            <a href="<?= e(url('/cgu')) ?>">CGU</a>
            <a href="<?= e(url('/cgv')) ?>">CGV</a>
            <a href="<?= e(url('/confidentialite')) ?>">Confidentialité</a>
            <a href="<?= e(url('/cookies')) ?>">Cookies</a>
            <span>Français · EUR</span>
          </div>
        </div>
      </footer>
    </div>
  </div>
  <script src="<?= e(asset('js/app.js')) ?>?v=m28"></script>
</body>
</html>
