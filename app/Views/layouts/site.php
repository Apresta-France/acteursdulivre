<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#15212f">
  <meta name="google-site-verification" content="4X7SyO1uk8XOvTr1QK5qLEe4qtWYmzp6qgG_nbeUSiI">
  <?php
    $meta = is_array($meta ?? null) ? $meta : [];
    $metaTitle = (string) ($meta['title'] ?? \Adl\Data\Seo::documentTitle((string) ($title ?? 'Acteurs du Livre')));
    $metaDesc = (string) ($meta['description'] ?? \Adl\Data\Seo::DEFAULT_DESC);
    $metaUrl = (string) ($meta['url'] ?? \Adl\Data\Share::current());
    $metaImage = (string) ($meta['image'] ?? \Adl\Data\Seo::defaultImage());
    $metaType = (string) ($meta['type'] ?? 'website');
    $metaRobots = (string) ($meta['robots'] ?? ((http_response_code() >= 400) ? \Adl\Data\Seo::ROBOTS_NONE : \Adl\Data\Seo::ROBOTS_INDEX));
    $metaImageW = (int) ($meta['image_width'] ?? \Adl\Data\Seo::OG_W);
    $metaImageH = (int) ($meta['image_height'] ?? \Adl\Data\Seo::OG_H);
    $metaImageAlt = (string) ($meta['image_alt'] ?? $metaTitle);
    $metaImageType = (string) ($meta['image_type'] ?? \Adl\Data\Seo::imageMime($metaImage));
    $jsonLd = $meta['json_ld'] ?? [];
    $founderOffer = null;
    if (empty($logged)) {
        try {
            $founderOffer = \Adl\Models\Commission::founderOffer();
        } catch (\Throwable) {
            $founderOffer = null;
        }
    }
    $bodyClass = [];
    if ($founderOffer) {
        $bodyClass[] = 'has-founder-banner';
    }
    if (\Adl\Core\Auth::isImpersonating()) {
        $bodyClass[] = 'has-impersonation-bar';
    }
  ?>
  <title><?= e($metaTitle) ?></title>
  <meta name="description" content="<?= e($metaDesc) ?>">
  <meta name="robots" content="<?= e($metaRobots) ?>">
  <meta name="author" content="EDITIONS TESSERACT">
  <link rel="canonical" href="<?= e($metaUrl) ?>">
  <link rel="sitemap" type="application/xml" title="Sitemap" href="<?= e(\Adl\Data\Share::absolute('/sitemap.xml')) ?>">
  <link rel="alternate" type="text/plain" href="<?= e(\Adl\Data\Share::absolute('/llms.txt')) ?>" title="llms.txt">
  <meta property="og:type" content="<?= e($metaType) ?>">
  <meta property="og:site_name" content="acteursdulivre.fr">
  <meta property="og:title" content="<?= e($metaTitle) ?>">
  <meta property="og:description" content="<?= e($metaDesc) ?>">
  <meta property="og:url" content="<?= e($metaUrl) ?>">
  <meta property="og:image" content="<?= e($metaImage) ?>">
  <meta property="og:image:secure_url" content="<?= e($metaImage) ?>">
  <meta property="og:image:type" content="<?= e($metaImageType) ?>">
  <meta property="og:image:width" content="<?= $metaImageW ?>">
  <meta property="og:image:height" content="<?= $metaImageH ?>">
  <meta property="og:image:alt" content="<?= e($metaImageAlt) ?>">
  <meta property="og:locale" content="fr_FR">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($metaTitle) ?>">
  <meta name="twitter:description" content="<?= e($metaDesc) ?>">
  <meta name="twitter:image" content="<?= e($metaImage) ?>">
  <meta name="twitter:image:alt" content="<?= e($metaImageAlt) ?>">
  <?php if (!empty($meta['published_time'])): ?>
  <meta property="article:published_time" content="<?= e((string) $meta['published_time']) ?>">
  <?php endif; ?>
  <?php if (!empty($meta['modified_time'])): ?>
  <meta property="article:modified_time" content="<?= e((string) $meta['modified_time']) ?>">
  <?php endif; ?>
  <?php if (!empty($meta['section'])): ?>
  <meta property="article:section" content="<?= e((string) $meta['section']) ?>">
  <?php endif; ?>
  <link rel="preload" href="<?= e(asset('fonts/space-grotesk-700.woff2')) ?>" as="font" type="font/woff2" crossorigin>
  <?php if (!empty($isAccueil)): ?>
  <link rel="preload" as="image" href="<?= e((string) ($homeImg1 ?? photo_asset('hero-write'))) ?>" type="image/webp">
  <?php endif; ?>
  <?php if (!empty($isArticle) && !empty($article['img'])): ?>
  <link rel="preload" as="image" href="<?= e((string) $article['img']) ?>">
  <?php endif; ?>
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>?v=m147">
  <link rel="icon" href="<?= e(asset('img/favicon.ico')) ?>?v=3" sizes="any">
  <link rel="icon" type="image/png" href="<?= e(asset('img/favicon-32x32.png')) ?>?v=3" sizes="32x32">
  <link rel="apple-touch-icon" href="<?= e(asset('img/apple-touch-icon.png')) ?>?v=3">
  <?php if (is_array($jsonLd) && $jsonLd !== []): ?>
  <script type="application/ld+json"><?= json_encode(
      ['@context' => 'https://schema.org', '@graph' => array_values($jsonLd)],
      JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP
  ) ?></script>
  <?php endif; ?>
</head>
<body<?= $bodyClass !== [] ? ' class="' . e(implode(' ', $bodyClass)) . '"' : '' ?> data-stats="<?= e(url('/api/stats')) ?>">
  <?php require ADL_ROOT . '/app/Views/partials/impersonation-bar.php'; ?>
  <div class="nav-backdrop" data-nav-close hidden></div>
  <div class="site-shell">
    <div class="site-canvas">
      <div class="preopen">
        <span class="preopen-badge">Pré-ouverture</span>
        <span class="preopen-text">La plateforme accueille dès maintenant les <strong>auteurs et les professionnels du livre</strong> — ouverture aux clients en octobre 2026. Sans IA générative sur les missions, jamais.</span>
        <span class="preopen-short">Inscriptions ouvertes — clients en octobre 2026. Sans IA générative sur les missions.</span>
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
        <a href="<?= e(url('/')) ?>" class="brand" aria-label="acteursdulivre.fr — accueil">
          <picture>
            <source media="(max-width: 768px)" srcset="<?= e(asset('img/logo-mark.png')) ?>?v=1">
            <img src="<?= e(asset('img/logo.png')) ?>?v=4" alt="acteursdulivre.fr — place de marché des métiers du livre" width="212" height="58" decoding="async">
          </picture>
        </a>
        <form class="search" role="search" action="<?= e(url('/recherche')) ?>" method="get" data-live-search data-api="<?= e(url('/api/recherche')) ?>" autocomplete="off" toolname="search_directory" tooldescription="Rechercher un prestataire, une prestation ou un appel d'offres parmi les métiers du livre.">
          <?php
            $headerSearchType = (string) ($searchType ?? '');
            if ($headerSearchType === '' || $headerSearchType === 'all') {
                $headerPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
                $headerPath = $headerPath !== '/' ? rtrim($headerPath, '/') : '/';
                $headerSearchType = match ($headerPath) {
                    '/prestations' => 'prestations',
                    '/prestataires' => 'prestataires',
                    '/missions' => array_key_exists('missions', \Adl\Data\Catalog::TYPES) ? 'missions' : '',
                    default => '',
                };
            }
          ?>
          <?php if ($headerSearchType !== '' && $headerSearchType !== 'all' && array_key_exists($headerSearchType, \Adl\Data\Catalog::TYPES)): ?>
            <input type="hidden" name="type" value="<?= e($headerSearchType) ?>">
          <?php endif; ?>
          <input type="search" name="q" value="<?= e($query ?? '') ?>" placeholder="correcteur roman, illustration jeunesse…" autocomplete="off" data-live-input aria-label="Rechercher un prestataire ou une prestation" toolparamdescription="Mots-clés : métier, genre littéraire ou besoin.">
          <input type="hidden" name="ville" value="<?= e($searchCity ?? '') ?>" data-header-ville>
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
              <a href="<?= e(url('/espace')) ?>"<?= !empty($isDashboard) ? ' class="is-active"' : '' ?>>Tableau de bord</a>
              <a href="<?= e(url('/espace/parametres')) ?>"<?= !empty($isParametres) ? ' class="is-active"' : '' ?>>Paramètres</a>
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
              <?php $headerUnreadForum = (int) ($unreadForum ?? 0); ?>
              <a href="<?= e(url($headerUnreadForum > 0 ? '/espace/forum?onglet=suivis' : '/forum')) ?>"<?= !empty($isForum) || !empty($isEspaceForum) ? ' aria-current="page"' : '' ?> class="header-forum-link" aria-label="<?= $headerUnreadForum > 0 ? 'Forum (' . $headerUnreadForum . ' réponses non lues)' : 'Forum' ?>">
                Forum<?php if ($headerUnreadForum > 0): ?><span class="badge-orange"><?= $headerUnreadForum > 99 ? '99+' : $headerUnreadForum ?></span><?php endif; ?>
              </a>
              <?php if (!empty($headerCta)): ?>
                <a href="<?= e(url($headerCta['href'])) ?>"><?= e($headerCta['label']) ?></a>
              <?php endif; ?>
              <?php
                $headerUnreadMessages = (int) ($unreadMessages ?? 0);
                $headerUnreadAlerts = (int) ($unreadAlerts ?? 0);
              ?>
              <div class="header-icons">
                <a href="<?= e(url('/espace/messages')) ?>" class="header-icon-link" aria-label="<?= $headerUnreadMessages > 0 ? 'Messages (' . $headerUnreadMessages . ' non lus)' : 'Messages' ?>" title="Messages">
                  <?= icon('chat', 22) ?>
                  <?php if ($headerUnreadMessages > 0): ?><span class="badge-orange"><?= $headerUnreadMessages ?></span><?php endif; ?>
                </a>
                <a href="<?= e(url('/espace/notifications')) ?>" class="header-icon-link" aria-label="<?= $headerUnreadAlerts > 0 ? 'Notifications (' . $headerUnreadAlerts . ')' : 'Notifications' ?>" title="Notifications">
                  <?= icon('bell', 22) ?>
                  <?php if ($headerUnreadAlerts > 0): ?><span class="badge-orange"><?= $headerUnreadAlerts ?></span><?php endif; ?>
                </a>
              </div>
            </nav>
          <?php else: ?>
            <nav class="header-nav">
              <a href="<?= e(url('/forum')) ?>"<?= !empty($isForum) ? ' aria-current="page"' : '' ?>>Forum</a>
              <a href="<?= e(url('/comment-ca-marche')) ?>">Comment ça marche</a>
              <a href="<?= e(url('/missions')) ?>">Appels d'offres</a>
              <a href="<?= e(url('/connexion')) ?>">Se connecter</a>
            </nav>
            <a class="btn-navy" href="<?= e(url('/inscription')) ?>">Créer un compte</a>
          <?php endif; ?>
        </div>
      </header>

      <div class="rail" data-rail>
        <div class="rail-list" data-rail-list>
          <?php foreach ($rail ?? [] as $r): ?>
            <?php $railOn = ($trade ?? '') !== '' && ($r['name'] ?? '') === $trade; ?>
            <a href="<?= e(url($r['href'] ?? '/recherche')) ?>"<?= $railOn ? ' class="is-active"' : '' ?>><?= e($r['name'] ?? '') ?></a>
          <?php endforeach; ?>
        </div>
        <button type="button" class="mega-btn" data-mega-toggle aria-expanded="false" aria-controls="header-mega">
          <span class="mega-btn-full">Tous les métiers</span>
          <span class="mega-btn-short">Métiers</span>
          ▾
        </button>
      </div>

      <div class="mega" id="header-mega" hidden>
        <?php foreach ($mega ?? [] as $m): ?>
          <div>
            <div class="mega-group"><?= e($m['group']) ?></div>
            <div class="mega-items">
              <?php foreach ($m['items'] as $item): ?>
                <?php if (is_array($item)): ?>
                  <a href="<?= e(url((string) ($item['href'] ?? '/recherche'))) ?>"><?= e((string) ($item['label'] ?? '')) ?></a>
                <?php else: ?>
                  <a href="<?= e(url(\Adl\Data\Catalog::tradePath(\Adl\Data\Catalog::resolveTrade((string) $item) ?? (string) $item))) ?>"><?= e((string) $item) ?></a>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <main>
        <?php if ($siteFlash = flash('saved')): ?>
          <div class="flash flash-ok" style="margin: 16px 24px 0;"><?= e(is_string($siteFlash) ? $siteFlash : 'Enregistré.') ?></div>
        <?php endif; ?>
        <?php if ($siteError = flash('error')): ?>
          <div class="flash flash-error" style="margin: 16px 24px 0;"><?= e((string) $siteError) ?></div>
        <?php endif; ?>
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
            <div class="footer-news-title">Le point sur les métiers du livre, une fois par semaine</div>
            <div>Nouveaux projets, nouveaux profils, une lecture utile. Pas de publicité, confirmation par e-mail, désinscription en un clic.</div>
          </div>
          <form class="footer-news-form" action="<?= e(url('/newsletter')) ?>" method="post" toolname="subscribe_newsletter" tooldescription="Inscrire une adresse e-mail à la lettre hebdomadaire des métiers du livre. Confirmation par e-mail, désinscription en un clic.">
            <?= csrf_field() ?>
            <input type="hidden" name="back" value="<?= e((string) ($_SERVER['REQUEST_URI'] ?? '/')) ?>">
            <label class="sr-only" for="footer-news-email">Adresse e-mail pour la lettre d'information</label>
            <input id="footer-news-email" type="email" name="email" placeholder="votre@email.fr" required autocomplete="email" toolparamdescription="Adresse e-mail pour recevoir la lettre.">
            <button type="submit">S'inscrire</button>
          </form>
        </div>
        <div class="footer-cols">
          <div>
            <div class="footer-logo">
              <a href="<?= e(url('/')) ?>" aria-label="acteursdulivre.fr — accueil">
                <img src="<?= e(asset('img/logo-inv.png')) ?>?v=5" alt="" width="154" height="42" loading="lazy" decoding="async">
              </a>
            </div>
            <p>La place de marché des métiers du livre. Dix-huit métiers, de l'écriture au coaching.</p>
            <div class="socials">
              <?php foreach ($socials ?? [] as $s): ?>
                <?php if (is_array($s)): ?>
                  <?php $sid = (string) ($s['id'] ?? ''); ?>
                  <a href="<?= e((string) ($s['href'] ?? '#')) ?>"
                     target="_blank" rel="noopener noreferrer"
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
              <?php if (is_array($m)): ?>
                <a href="<?= e(url((string) ($m['href'] ?? '/recherche'))) ?>"><?= e((string) ($m['label'] ?? '')) ?></a>
              <?php else: ?>
                <a href="<?= e(url('/metiers/' . slugify((string) $m))) ?>"><?= e((string) $m) ?></a>
              <?php endif; ?>
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
            <a href="<?= e(url('/regles-ia')) ?>">Règles IA</a>
            <span>Français · EUR</span>
          </div>
        </div>
      </footer>
    </div>
  </div>
  <?php if ($founderOffer): ?>
    <?php
      $founderLeft = (int) $founderOffer['remaining'];
      $founderLimit = (int) $founderOffer['limit'];
      $founderPct = (int) $founderOffer['percent'];
      $founderPlaces = $founderLeft > 1 ? 'places' : 'place';
    ?>
    <div class="founder-banner">
      <span class="founder-banner-badge">Taux fondateur</span>
      <span class="founder-banner-text">Il reste encore <strong><?= e(format_int($founderLeft)) ?> <?= $founderPlaces ?></strong> parmi les <?= e(format_int($founderLimit)) ?> premiers inscrits — <?= $founderPct ?>&nbsp;% de commission dès la 2ᵉ mission.</span>
      <span class="founder-banner-short">Encore <strong><?= e(format_int($founderLeft)) ?> <?= $founderPlaces ?></strong> au taux fondateur (<?= $founderPct ?>&nbsp;% dès la 2ᵉ mission).</span>
      <?php if (empty($isInscription)): ?>
        <a href="<?= e(url('/inscription')) ?>">Réserver ma place</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
  <script src="<?= e(asset('js/app.js')) ?>?v=m75"></script>
</body>
</html>
