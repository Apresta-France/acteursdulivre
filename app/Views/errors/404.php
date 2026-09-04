<div class="mk-page err404-page">
  <header class="err404-heading">
    <p class="mk-kicker">Erreur 404 · page égarée</p>
    <h1>Cette page n’est pas dans le sommaire.</h1>
    <p><?= e((string) ($message ?? 'On a regardé sous les livres, entre les lignes et même dans les notes de bas de page. Rien.')) ?></p>
  </header>

  <div class="err404-scene" aria-hidden="true">
    <span class="err404-status">Recherche en cours…</span>
    <svg viewBox="0 0 1400 450" preserveAspectRatio="xMidYMid slice">
      <defs>
        <linearGradient id="err404-sky" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0" stop-color="#fdf8f2"/>
          <stop offset="1" stop-color="#f3e6d7"/>
        </linearGradient>
        <linearGradient id="err404-floor" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0" stop-color="#eadbc9"/>
          <stop offset="1" stop-color="#e0cdb6"/>
        </linearGradient>
        <radialGradient id="err404-halo">
          <stop offset="0" stop-color="#ffffff" stop-opacity=".9"/>
          <stop offset="1" stop-color="#ffffff" stop-opacity="0"/>
        </radialGradient>
        <radialGradient id="err404-beam">
          <stop offset="0" stop-color="#f3b66a" stop-opacity=".55"/>
          <stop offset="1" stop-color="#f3b66a" stop-opacity="0"/>
        </radialGradient>
        <filter id="err404-shadow" x="-30%" y="-40%" width="160%" height="190%">
          <feDropShadow dx="0" dy="6" stdDeviation="6" flood-color="#15212f" flood-opacity=".12"/>
        </filter>
        <symbol id="err404-sheet" viewBox="0 0 60 78">
          <rect width="60" height="78" rx="3" fill="#fff" stroke="#dccfc0" stroke-width="1.5"/>
          <path d="M11 17h38M11 29h32M11 41h38M11 53h24" stroke="#cbd5de" stroke-width="3" stroke-linecap="round"/>
        </symbol>
      </defs>

      <rect width="1400" height="450" fill="url(#err404-sky)"/>
      <ellipse cx="700" cy="250" rx="520" ry="230" fill="url(#err404-halo)"/>
      <path d="M0 336C240 322 460 350 700 344s470-20 700-8v114H0Z" fill="url(#err404-floor)"/>
      <path d="M0 338c240-14 460 14 700 8s470-20 700-8" fill="none" stroke="#fff" stroke-opacity=".55" stroke-width="2"/>
      <text x="700" y="332" class="err404-big-number" text-anchor="middle">404</text>

      <g class="err404-dust">
        <circle cx="240" cy="230" r="3"/>
        <circle cx="410" cy="160" r="2.5"/>
        <circle cx="990" cy="190" r="3"/>
        <circle cx="1180" cy="240" r="2.5"/>
        <circle cx="560" cy="120" r="2"/>
        <circle cx="880" cy="110" r="2"/>
      </g>

      <g class="err404-fall err404-fall--1"><use href="#err404-sheet" x="270" y="0" width="54" height="70"/></g>
      <g class="err404-fall err404-fall--2"><use href="#err404-sheet" x="1030" y="0" width="60" height="78"/></g>
      <g class="err404-fall err404-fall--3"><use href="#err404-sheet" x="520" y="0" width="48" height="62"/></g>

      <g filter="url(#err404-shadow)">
        <g transform="translate(112 356)">
          <rect width="200" height="30" rx="5" fill="#2c3f55"/>
          <rect x="12" y="5" width="186" height="20" rx="2" fill="#f7efe5"/>
          <path d="M20 12h172M20 18h172" stroke="#e2d6c8" stroke-width="1.5"/>
        </g>
        <g transform="translate(128 326)">
          <rect width="170" height="30" rx="5" fill="#d9825f"/>
          <rect x="12" y="5" width="156" height="20" rx="2" fill="#f7efe5"/>
          <path d="M20 12h142M20 18h142" stroke="#e2d6c8" stroke-width="1.5"/>
        </g>
        <g transform="translate(120 296)">
          <rect width="184" height="30" rx="5" fill="#4f7c8a"/>
          <rect x="12" y="5" width="170" height="20" rx="2" fill="#f7efe5"/>
          <path d="M20 12h156M20 18h156" stroke="#e2d6c8" stroke-width="1.5"/>
        </g>
        <g transform="translate(160 272)">
          <rect width="110" height="24" rx="4" fill="#b86b6b"/>
          <rect x="10" y="4" width="98" height="16" rx="2" fill="#f7efe5"/>
        </g>
        <g transform="translate(318 300) rotate(-18)">
          <rect width="30" height="84" rx="4" fill="#e5a84b"/>
          <rect x="5" y="8" width="20" height="68" rx="1.5" fill="#f7efe5"/>
        </g>

        <g transform="translate(1088 356)">
          <rect width="210" height="30" rx="5" fill="#4f7c8a"/>
          <rect x="12" y="5" width="196" height="20" rx="2" fill="#f7efe5"/>
          <path d="M20 12h182M20 18h182" stroke="#e2d6c8" stroke-width="1.5"/>
        </g>
        <g transform="translate(1074 326)">
          <rect width="190" height="30" rx="5" fill="#1f3347"/>
          <rect x="12" y="5" width="176" height="20" rx="2" fill="#f7efe5"/>
          <path d="M20 12h162M20 18h162" stroke="#e2d6c8" stroke-width="1.5"/>
        </g>
        <g transform="translate(1100 296)">
          <rect width="176" height="30" rx="5" fill="#e5a84b"/>
          <rect x="12" y="5" width="162" height="20" rx="2" fill="#f7efe5"/>
          <path d="M20 12h148M20 18h148" stroke="#e2d6c8" stroke-width="1.5"/>
        </g>
        <g transform="translate(1130 240) rotate(-4)">
          <path d="M0 10Q40-5 82 8v54Q41 48 0 62Z" fill="#fff" stroke="#d3c4b3" stroke-width="2"/>
          <path d="M82 8q42-13 82 2v54q-40-12-82 4z" fill="#fbf6ef" stroke="#d3c4b3" stroke-width="2"/>
          <path d="M82 10v56M14 24q27-7 54 0M14 38q27-7 54 0M96 24q27-7 54 0M96 38q27-7 54 0" fill="none" stroke="#cbd5de" stroke-width="2"/>
        </g>

        <g transform="translate(402 372) rotate(3)">
          <path d="M0 10Q45-6 92 8v58Q46 50 0 66Z" fill="#fff" stroke="#d3c4b3" stroke-width="2"/>
          <path d="M92 8q46-14 92 2v58q-46-13-92 6z" fill="#fbf6ef" stroke="#d3c4b3" stroke-width="2"/>
          <path d="M92 10v62M16 26q30-8 60 0M16 42q30-8 60 0M108 26q30-8 60 0M108 42q30-8 60 0" fill="none" stroke="#cbd5de" stroke-width="2"/>
        </g>
        <g transform="translate(930 368) rotate(-3)">
          <path d="M0 10Q45-6 92 8v58Q46 50 0 66Z" fill="#fff" stroke="#d3c4b3" stroke-width="2"/>
          <path d="M92 8q46-14 92 2v58q-46-13-92 6z" fill="#fbf6ef" stroke="#d3c4b3" stroke-width="2"/>
          <path d="M92 10v62M16 26q30-8 60 0M16 42q30-8 60 0M108 26q30-8 60 0M108 42q30-8 60 0" fill="none" stroke="#cbd5de" stroke-width="2"/>
        </g>

        <use href="#err404-sheet" width="56" height="72" transform="translate(500 388) rotate(-78)"/>
        <use href="#err404-sheet" width="50" height="64" transform="translate(608 424) rotate(-96)"/>
        <use href="#err404-sheet" width="56" height="72" transform="translate(874 426) rotate(-100)"/>
        <use href="#err404-sheet" width="54" height="70" transform="translate(1020 404) rotate(-82)"/>
        <use href="#err404-sheet" width="50" height="64" transform="translate(348 420) rotate(-84)"/>
        <use href="#err404-sheet" width="56" height="72" transform="translate(30 400) rotate(-76)"/>
        <use href="#err404-sheet" width="52" height="66" transform="translate(1320 396) rotate(-98)"/>
      </g>

      <g class="err404-beam">
        <ellipse cx="840" cy="410" rx="82" ry="20" fill="url(#err404-beam)"/>
      </g>

      <ellipse cx="704" cy="402" rx="96" ry="10" fill="#15212f" opacity=".12"/>

      <g class="err404-person">
        <rect x="668" y="270" width="32" height="126" rx="15" fill="#2c3f55"/>
        <rect x="706" y="270" width="32" height="126" rx="15" fill="#34495f"/>
        <rect x="662" y="388" width="44" height="12" rx="6" fill="#15212f"/>
        <rect x="700" y="388" width="44" height="12" rx="6" fill="#15212f"/>

        <path d="M645 190Q700 170 755 190L746 300Q700 314 654 300Z" fill="#d9825f"/>
        <path d="M700 186v112" stroke="#c96f4d" stroke-width="2.5" stroke-linecap="round" opacity=".7"/>

        <g class="err404-arm-search">
          <path d="M748 200C790 228 802 278 794 316" fill="none" stroke="#d9825f" stroke-width="22" stroke-linecap="round"/>
          <circle cx="794" cy="320" r="12" fill="#efc4a4"/>
          <path d="M798 330L822 364" stroke="#15212f" stroke-width="10" stroke-linecap="round"/>
          <circle cx="836" cy="386" r="30" fill="#fff" fill-opacity=".28" stroke="#15212f" stroke-width="8"/>
          <path d="M818 372a24 24 0 0 1 22-10" fill="none" stroke="#fff" stroke-opacity=".8" stroke-width="4" stroke-linecap="round"/>
        </g>

        <rect x="690" y="164" width="20" height="24" rx="8" fill="#efc4a4"/>
        <g class="err404-head">
          <circle cx="700" cy="140" r="36" fill="#2f2723"/>
          <circle cx="722" cy="106" r="13" fill="#2f2723"/>
          <path d="M676 124a28 28 0 0 1 30-18" fill="none" stroke="#4a3b36" stroke-width="5" stroke-linecap="round"/>
          <circle cx="716" cy="100" r="4" fill="#4a3b36"/>
        </g>

        <g class="err404-arm-scratch">
          <path d="M652 200C618 190 628 132 668 122" fill="none" stroke="#d9825f" stroke-width="22" stroke-linecap="round"/>
          <circle cx="672" cy="120" r="12" fill="#efc4a4"/>
        </g>
      </g>

      <g class="err404-question err404-question--1" fill="#d9825f" font-family="Space Grotesk, sans-serif" font-weight="700">
        <text x="748" y="96" font-size="38">?</text>
      </g>
      <g class="err404-question err404-question--2" fill="#e5a84b" font-family="Space Grotesk, sans-serif" font-weight="700">
        <text x="786" y="70" font-size="24">?</text>
      </g>
    </svg>
  </div>

  <section class="err404-tools" aria-label="Retrouver votre chemin">
    <div>
      <h2>On reprend depuis le début&nbsp;?</h2>
      <p>Essayez une recherche, ou choisissez une destination parmi les plus fréquentées.</p>
    </div>
    <form class="mk-search err404-search" method="get" action="<?= e(url('/recherche')) ?>">
      <input name="q" placeholder="Un métier, une prestation, une mission…" aria-label="Rechercher un métier, une prestation ou une mission">
      <button class="btn-orange" type="submit">Chercher</button>
    </form>
    <nav class="mk-chips err404-links" aria-label="Liens utiles">
      <a href="<?= e(url('/')) ?>">Accueil</a>
      <a href="<?= e(url('/prestataires')) ?>">Annuaire</a>
      <a href="<?= e(url('/prestations')) ?>">Prestations</a>
      <a href="<?= e(url('/missions')) ?>">Appels d’offres</a>
      <a href="<?= e(url('/aide')) ?>">Aide</a>
    </nav>
  </section>
</div>
