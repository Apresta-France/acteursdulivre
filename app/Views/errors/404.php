<?php
$excuses = [
    'On a fouillé l\'index, le sommaire, même les notes de bas de page.',
    'Peut-être partie en dédicace, sans laisser d\'adresse.',
    'Le correcteur l\'a raturée un peu trop fort.',
    'Elle est chez l\'imprimeur. Depuis 2009.',
    'Un relieur l\'a trop bien fermée.',
    'Elle a demandé un droit à l\'oubli — ISBN 404-404-404.',
];
$bubbles = [
    'Je suis là, moi !',
    'Ce n\'est pas moi, le 404.',
    'J\'ai vu une page passer par là…',
    'On devrait appeler un indexeur.',
    'Chapitre suivant, peut-être ?',
    'Je tiens le marque-page, pas le plan.',
];
?>
<section class="err404">
  <div class="err404-sky" aria-hidden="true">
    <span class="err404-speck"></span>
    <span class="err404-speck"></span>
    <span class="err404-speck"></span>
    <span class="err404-speck"></span>
    <span class="err404-speck"></span>
    <span class="err404-plane"></span>
    <span class="err404-page is-a"></span>
    <span class="err404-page is-b"></span>
    <span class="err404-page is-c"></span>
    <span class="err404-page is-d"></span>
    <span class="err404-page is-e"></span>
  </div>

  <div class="err404-stage" aria-hidden="true">
    <div class="err404-digits">
      <span class="err404-digit">4</span>
      <span class="err404-digit is-zero">0</span>
      <span class="err404-digit">4</span>
    </div>

    <button
      type="button"
      class="err404-mascot"
      data-err404-mascot
      data-lines="<?= e(json_encode($bubbles, JSON_UNESCAPED_UNICODE)) ?>"
      aria-label="Le livre égaré. Cliquez pour le faire parler."
    >
      <span class="err404-bubble" hidden></span>
      <svg class="err404-svg" viewBox="0 0 260 230" xmlns="http://www.w3.org/2000/svg" focusable="false">
        <ellipse class="err404-svg-shadow" cx="130" cy="214" rx="52" ry="8"/>
        <g class="err404-svg-leg is-left">
          <rect x="104" y="178" width="11" height="30" rx="5.5" fill="#15212f"/>
          <ellipse cx="106" cy="208" rx="11" ry="5" fill="#15212f"/>
        </g>
        <g class="err404-svg-leg is-right">
          <rect x="145" y="178" width="11" height="30" rx="5.5" fill="#15212f"/>
          <ellipse cx="155" cy="208" rx="11" ry="5" fill="#15212f"/>
        </g>
        <g class="err404-svg-book">
          <rect x="78" y="38" width="118" height="142" rx="10" fill="#0f1a26"/>
          <rect x="84" y="44" width="108" height="130" rx="7" fill="#15212f"/>
          <rect x="84" y="44" width="16" height="130" rx="4" fill="#eb963b"/>
          <rect x="104" y="52" width="80" height="114" rx="4" fill="#f7efe4"/>
          <rect x="110" y="58" width="68" height="102" rx="3" fill="#fff"/>
          <rect x="118" y="72" width="52" height="4" rx="2" fill="#E1E7ED"/>
          <rect x="118" y="84" width="44" height="4" rx="2" fill="#E1E7ED"/>
          <rect x="118" y="96" width="50" height="4" rx="2" fill="#E1E7ED"/>
          <g class="err404-svg-eyes">
            <ellipse cx="128" cy="128" rx="13" ry="15" fill="#fff" stroke="#15212f" stroke-width="2.5"/>
            <ellipse cx="160" cy="128" rx="13" ry="15" fill="#fff" stroke="#15212f" stroke-width="2.5"/>
            <g class="err404-svg-pupils">
              <circle cx="130" cy="130" r="5.5" fill="#15212f"/>
              <circle cx="162" cy="130" r="5.5" fill="#15212f"/>
              <circle cx="132" cy="128" r="1.6" fill="#fff"/>
              <circle cx="164" cy="128" r="1.6" fill="#fff"/>
            </g>
          </g>
          <ellipse cx="116" cy="150" rx="7" ry="3.5" fill="#f0b372" opacity=".7"/>
          <ellipse cx="174" cy="150" rx="7" ry="3.5" fill="#f0b372" opacity=".7"/>
          <path class="err404-svg-mark" d="M188 50h16v58l-8-8-8 8z" fill="#eb963b"/>
          <path d="M196 50h8v10h-8z" fill="#f0b372"/>
        </g>
        <g class="err404-svg-arm">
          <path d="M196 118c18 4 28 18 26 34" fill="none" stroke="#15212f" stroke-width="7" stroke-linecap="round"/>
        </g>
        <g class="err404-svg-glass">
          <circle cx="228" cy="168" r="18" fill="rgba(255,255,255,.35)" stroke="#15212f" stroke-width="5"/>
          <circle cx="223" cy="162" r="6" fill="rgba(255,255,255,.45)"/>
          <path d="M240 182l14 16" stroke="#15212f" stroke-width="6" stroke-linecap="round"/>
        </g>
      </svg>
    </button>
  </div>

  <p class="err404-kicker">Chapitre introuvable · erreur 404</p>
  <h1>Cette page a sauté du manuscrit.</h1>
  <p class="err404-lead"><?= e((string) ($message ?? 'Le lien est peut-être ancien, ou la page a été retirée.')) ?></p>
  <div class="err404-quotes" aria-hidden="true">
    <?php foreach ($excuses as $line): ?>
      <p><?= e($line) ?></p>
    <?php endforeach; ?>
  </div>

  <form class="mk-search err404-search" method="get" action="<?= e(url('/recherche')) ?>">
    <input name="q" placeholder="Retrouver un métier, une prestation, une mission…" aria-label="Recherche">
    <button class="btn-orange" type="submit">Chercher</button>
  </form>

  <div class="err404-actions">
    <a class="btn-orange" href="<?= e(url('/')) ?>">Retour à l'accueil</a>
    <a class="btn-ghost" href="<?= e(url('/prestataires')) ?>">Voir les métiers</a>
    <a class="btn-ghost" href="<?= e(url('/aide')) ?>">Centre d'aide</a>
  </div>
</section>
<script>
(function () {
  var mascot = document.querySelector('[data-err404-mascot]');
  if (!mascot) return;
  var bubble = mascot.querySelector('.err404-bubble');
  var lines = [];
  try { lines = JSON.parse(mascot.getAttribute('data-lines') || '[]'); } catch (e) {}
  if (!bubble || !lines.length) return;
  var i = 0;
  mascot.addEventListener('click', function () {
    mascot.classList.remove('is-hop');
    void mascot.offsetWidth;
    mascot.classList.add('is-hop');
    bubble.textContent = lines[i % lines.length];
    i += 1;
    bubble.hidden = false;
  });
  mascot.addEventListener('animationend', function (ev) {
    if (ev.animationName === 'err404-hop') {
      mascot.classList.remove('is-hop');
    }
  });
})();
</script>
