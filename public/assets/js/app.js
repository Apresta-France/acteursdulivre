(function () {
  document.querySelectorAll('[data-go]').forEach(function (el) {
    el.addEventListener('click', function () {
      if (el.tagName === 'A' || el.closest('a') || el.closest('form')) return;
      var href = el.getAttribute('data-go');
      if (href && href.charAt(0) !== '#') window.location.href = href;
    });
  });

  var mega = document.querySelector('.mega');
  var toggle = document.querySelector('[data-mega-toggle]');
  if (mega && toggle) {
    toggle.addEventListener('click', function () {
      mega.hidden = !mega.hidden;
    });
  }

  document.querySelectorAll('[data-accordion]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var panel = btn.nextElementSibling;
      if (panel) panel.hidden = !panel.hidden;
    });
  });

  function bindToggle(openSel, closeSel, className, buttonSel) {
    var openers = document.querySelectorAll(openSel);
    var closers = document.querySelectorAll(closeSel);
    var header = document.querySelector('.site-header');
    function set(open) {
      document.body.classList.toggle(className, open);
      if (header && className === 'is-nav-open') header.classList.toggle('is-open', open);
      var backdrop = document.querySelector(className === 'is-nav-open' ? '.nav-backdrop' : '.admin-backdrop');
      if (backdrop) backdrop.hidden = !open;
      document.querySelectorAll(buttonSel).forEach(function (btn) {
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        btn.setAttribute('aria-label', open ? 'Fermer le menu' : (className === 'is-admin-open' ? 'Ouvrir le menu admin' : 'Ouvrir le menu'));
      });
    }
    openers.forEach(function (btn) {
      btn.addEventListener('click', function () {
        set(!document.body.classList.contains(className));
      });
    });
    closers.forEach(function (el) {
      el.addEventListener('click', function () { set(false); });
    });
    window.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') set(false);
    });
  }

  bindToggle('[data-nav-toggle]', '[data-nav-close]', 'is-nav-open', '[data-nav-toggle]');
  bindToggle('[data-admin-toggle]', '[data-admin-close]', 'is-admin-open', '[data-admin-toggle]');

  function parseColumns(spec) {
    if (!spec) return { count: 0, parts: [], repeat: 0, hasPx: false };
    var repeat = spec.match(/repeat\(\s*(\d+)/i);
    var parts = spec.replace(/repeat\([^)]+\)/gi, '').trim().split(/\s+/).filter(Boolean);
    var count = repeat ? parseInt(repeat[1], 10) : parts.length;
    return {
      count: count,
      parts: parts,
      repeat: repeat ? parseInt(repeat[1], 10) : 0,
      hasPx: /\d+px/.test(spec)
    };
  }

  function polishLayout() {
    var main = document.querySelector('main');
    if (!main || main.dataset.rPolished) return;
    main.dataset.rPolished = '1';

    main.querySelectorAll('[style*="grid-template-columns"]').forEach(function (el) {
      if (el.classList.contains('r-done')) return;
      el.classList.add('r-done');
      var style = el.getAttribute('style') || '';
      if (/grid-template-rows/.test(style)) {
        el.classList.add('r-cols-keep');
        return;
      }
      var match = style.match(/grid-template-columns:\s*([^;]+)/i);
      var info = parseColumns(match ? match[1].trim() : '');
      var isTable = info.count >= 4 && (info.hasPx || !info.repeat);
      if (isTable) {
        el.classList.add('r-table');
        var parent = el.parentElement;
        if (parent && !parent.classList.contains('r-scroll')) {
          parent.classList.add('r-scroll');
        }
        return;
      }
      if (/306px 1fr 316px/.test(style)) {
        el.classList.add('r-messenger');
      }
      if (info.count === 2 && /2[46]0px/.test(style) && el.children[0]) {
        el.children[0].classList.add('r-sidenav');
      }
      if (info.repeat >= 6 || info.count >= 6) el.classList.add('r-cols-6');
      else if (info.repeat === 5 || info.count === 5) el.classList.add('r-cols-5');
      else if (info.repeat === 4 || info.count === 4) el.classList.add('r-cols-4');
      else if (info.repeat === 3 || info.count === 3) el.classList.add('r-cols-3');
      else if (info.count === 2) el.classList.add('r-cols-2');
      else el.classList.add('r-stack');
    });

    main.querySelectorAll('[style*="padding"]').forEach(function (el) {
      var left = parseFloat(el.style.paddingLeft || getComputedStyle(el).paddingLeft);
      if (left >= 36) el.classList.add('r-pad');
    });

    main.querySelectorAll('[style*="width"]').forEach(function (el) {
      var width = parseInt(el.style.width, 10);
      if (width >= 280) el.classList.add('r-wide');
    });

    main.querySelectorAll('[style*="display: flex"][style*="justify-content: space-between"], [style*="display: flex"][style*="align-items: baseline"][style*="justify-content"]').forEach(function (el) {
      el.classList.add('r-flex-wrap');
    });

    var hero = main.querySelector('[style*="1fr 440px"]');
    if (hero && hero.children[1]) hero.children[1].classList.add('r-hero-media');

    main.querySelectorAll('aside').forEach(function (aside) {
      if (aside.dataset.rFilter) return;
      aside.dataset.rFilter = '1';
      aside.classList.add('r-filters');
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'r-filter-toggle';
      btn.textContent = 'Filtres';
      btn.addEventListener('click', function () {
        var open = aside.classList.toggle('is-open');
        btn.classList.toggle('is-open', open);
      });
      aside.parentNode.insertBefore(btn, aside);
    });
  }

  polishLayout();

  document.querySelectorAll('.header-panel a').forEach(function (link) {
    link.addEventListener('click', function () {
      document.body.classList.remove('is-nav-open');
      var header = document.querySelector('.site-header');
      if (header) header.classList.remove('is-open');
      var backdrop = document.querySelector('.nav-backdrop');
      if (backdrop) backdrop.hidden = true;
    });
  });

  (function themeWidget() {
    var KEY = 'adl-charte-v2';
    var DEFAULTS = { navy: '#022746', orange: '#eb963b', beige: '#efdfce' };

    function hexToRgb(hex) {
      hex = hex.replace('#', '');
      if (hex.length === 3) hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
      var n = parseInt(hex, 16);
      return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
    }
    function rgbToHex(r, g, b) {
      return '#' + [r, g, b].map(function (v) {
        return ('0' + Math.max(0, Math.min(255, v)).toString(16)).slice(-2);
      }).join('');
    }
    function mix(a, b, t) {
      var A = hexToRgb(a);
      var B = hexToRgb(b);
      return rgbToHex(
        Math.round(A[0] + (B[0] - A[0]) * t),
        Math.round(A[1] + (B[1] - A[1]) * t),
        Math.round(A[2] + (B[2] - A[2]) * t)
      );
    }
    function normalizeHex(value) {
      var m = String(value || '').trim().match(/^#?([0-9a-f]{3}|[0-9a-f]{6})$/i);
      if (!m) return null;
      var h = m[1].toLowerCase();
      if (h.length === 3) h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2];
      return '#' + h;
    }
    function replaceRgb(css, r, g, b, next) {
      var n = hexToRgb(next);
      return css.replace(
        new RegExp('rgba?\\(\\s*' + r + '\\s*,\\s*' + g + '\\s*,\\s*' + b + '(\\s*,\\s*[^)]+)?\\)', 'gi'),
        function (_, alpha) {
          return alpha ? 'rgba(' + n.join(', ') + alpha + ')' : 'rgb(' + n.join(', ') + ')';
        }
      );
    }
    function rewrite(css, colors) {
      var next = css
        .replace(/#022746/gi, colors.navy)
        .replace(/#d85d3f/gi, colors.orange)
        .replace(/#eb963b/gi, colors.orange)
        .replace(/#e8845f/gi, colors.orangeSoft)
        .replace(/#e8e3da/gi, colors.beige)
        .replace(/#ece0d4/gi, colors.beige)
        .replace(/#efdfce/gi, colors.beige);
      next = replaceRgb(next, 2, 39, 70, colors.navy);
      next = replaceRgb(next, 216, 93, 63, colors.orange);
      next = replaceRgb(next, 235, 150, 59, colors.orange);
      next = replaceRgb(next, 232, 132, 95, colors.orangeSoft);
      next = replaceRgb(next, 232, 227, 218, colors.beige);
      next = replaceRgb(next, 236, 224, 212, colors.beige);
      next = replaceRgb(next, 239, 223, 206, colors.beige);
      return next;
    }
    function apply(colors) {
      colors.navy = normalizeHex(colors.navy) || DEFAULTS.navy;
      colors.orange = normalizeHex(colors.orange) || DEFAULTS.orange;
      colors.beige = normalizeHex(colors.beige) || DEFAULTS.beige;
      colors.orangeSoft = mix(colors.orange, '#ffffff', 0.28);
      var rgb = hexToRgb(colors.navy);
      var root = document.documentElement;
      root.style.setProperty('--navy', colors.navy);
      root.style.setProperty('--navy-rgb', rgb.join(', '));
      root.style.setProperty('--orange', colors.orange);
      root.style.setProperty('--orange-soft', colors.orangeSoft);
      root.style.setProperty('--beige', colors.beige);
      root.style.setProperty('--peach', mix(colors.orange, '#ffffff', 0.92));
      document.querySelectorAll('[style]').forEach(function (el) {
        if (el.closest('.theme-widget')) return;
        var original = el.getAttribute('data-theme-style');
        if (original === null) {
          original = el.getAttribute('style') || '';
          el.setAttribute('data-theme-style', original);
        }
        el.setAttribute('style', rewrite(original, colors));
      });
    }
    function load() {
      try {
        var saved = JSON.parse(localStorage.getItem(KEY) || 'null');
        if (saved && saved.navy && saved.orange && saved.beige) return saved;
      } catch (err) {}
      return { navy: DEFAULTS.navy, orange: DEFAULTS.orange, beige: DEFAULTS.beige };
    }
    function save(colors) {
      localStorage.setItem(KEY, JSON.stringify({
        navy: colors.navy,
        orange: colors.orange,
        beige: colors.beige
      }));
    }

    var colors = load();
    apply(colors);

    var widget = document.createElement('div');
    widget.className = 'theme-widget';
    widget.innerHTML =
      '<button type="button" class="theme-fab" aria-expanded="false" aria-controls="theme-panel" aria-label="Personnaliser la charte">' +
        '<span class="theme-fab-dots" aria-hidden="true"><i></i><i></i><i></i></span>' +
      '</button>' +
      '<div class="theme-panel" id="theme-panel" hidden>' +
        '<div class="theme-panel-head">' +
          '<div><strong>Charte</strong><span>Testez les 3 couleurs en direct</span></div>' +
          '<button type="button" class="theme-close" aria-label="Fermer">×</button>' +
        '</div>' +
        '<div class="theme-row"><input type="color" data-theme="navy" value="' + colors.navy + '" aria-label="Marine"><label>Marine</label><input type="text" data-theme-hex="navy" value="' + colors.navy + '" spellcheck="false"></div>' +
        '<div class="theme-row"><input type="color" data-theme="orange" value="' + colors.orange + '" aria-label="Orange"><label>Orange</label><input type="text" data-theme-hex="orange" value="' + colors.orange + '" spellcheck="false"></div>' +
        '<div class="theme-row"><input type="color" data-theme="beige" value="' + colors.beige + '" aria-label="Beige"><label>Beige</label><input type="text" data-theme-hex="beige" value="' + colors.beige + '" spellcheck="false"></div>' +
        '<button type="button" class="theme-reset">Réinitialiser</button>' +
      '</div>';
    document.body.appendChild(widget);

    var fab = widget.querySelector('.theme-fab');
    var panel = widget.querySelector('.theme-panel');
    function setOpen(open) {
      panel.hidden = !open;
      fab.hidden = open;
      fab.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    fab.addEventListener('click', function () { setOpen(true); });
    widget.querySelector('.theme-close').addEventListener('click', function () { setOpen(false); });

    function syncFields() {
      widget.querySelectorAll('[data-theme]').forEach(function (input) {
        input.value = colors[input.getAttribute('data-theme')];
      });
      widget.querySelectorAll('[data-theme-hex]').forEach(function (input) {
        input.value = colors[input.getAttribute('data-theme-hex')];
      });
    }
    function setColor(key, value, persist) {
      var hex = normalizeHex(value);
      if (!hex) return;
      colors[key] = hex;
      apply(colors);
      if (persist !== false) save(colors);
      syncFields();
    }
    widget.querySelectorAll('[data-theme]').forEach(function (input) {
      input.addEventListener('input', function () {
        setColor(input.getAttribute('data-theme'), input.value);
      });
    });
    widget.querySelectorAll('[data-theme-hex]').forEach(function (input) {
      input.addEventListener('input', function () {
        setColor(input.getAttribute('data-theme-hex'), input.value);
      });
    });
    widget.querySelector('.theme-reset').addEventListener('click', function () {
      colors = { navy: DEFAULTS.navy, orange: DEFAULTS.orange, beige: DEFAULTS.beige };
      localStorage.removeItem(KEY);
      apply(colors);
      syncFields();
    });
  })();

  (function writingBg() {
    var layer = document.querySelector('.writing-bg');
    if (!layer) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    var WORDS = [
      'manuscrit', 'chapitre', 'brouillon', 'correction', 'réécriture',
      'épreuves', 'bon à tirer', 'lettrine', 'incipit', 'colophon',
      'dédicace', 'sommaire', 'reliure', 'typographie', 'maquette',
      'traduction', 'illustration', 'narrateur', 'libraire', 'éditeur',
      'imprimeur', 'correcteur', 'interligne', 'italique', 'folio',
      'errata', 'postface', 'avant-propos', 'cahier', 'signature',
      'page de garde', 'achevé d’imprimer', 'notes', 'index',
      '« Il était une fois »', 'Elle tourna la page.', 'Corriger, encore.',
      'Un livre se fait à plusieurs.', 'première épreuve', 'quatrième'
    ];

    function wait(ms) {
      return new Promise(function (resolve) { setTimeout(resolve, ms); });
    }
    function whenVisible() {
      if (!document.hidden) return Promise.resolve();
      return new Promise(function (resolve) {
        document.addEventListener('visibilitychange', function onVis() {
          if (!document.hidden) {
            document.removeEventListener('visibilitychange', onVis);
            resolve();
          }
        });
      });
    }
    function rand(min, max) {
      return min + Math.random() * (max - min);
    }
    function pick(list) {
      return list[Math.floor(Math.random() * list.length)];
    }

    function margins() {
      var canvas = document.querySelector('.site-canvas');
      if (!canvas) return null;
      var box = canvas.getBoundingClientRect();
      var vw = window.innerWidth;
      var vh = window.innerHeight;
      return {
        left: Math.max(0, box.left),
        right: Math.max(0, vw - box.right),
        top: 48,
        bottom: vh - 36,
        vh: vh
      };
    }

    function canRun() {
      if (window.innerWidth < 1280) return false;
      var m = margins();
      return !!(m && (m.left >= 56 || m.right >= 56));
    }

    function place(el, text, size) {
      var m = margins();
      if (!m) return false;
      var sides = [];
      if (m.left >= 56) sides.push('left');
      if (m.right >= 56) sides.push('right');
      if (!sides.length) return false;
      var side = pick(sides);
      var gutter = side === 'left' ? m.left : m.right;
      var maxW = Math.max(64, gutter - 16);
      if (text.length * size * 0.46 > maxW + 48) return false;
      el.style.fontSize = size + 'px';
      el.style.maxWidth = maxW + 56 + 'px';
      var y = rand(m.top, Math.max(m.top + 8, m.bottom - size * 1.4));
      el.style.top = y + 'px';
      if (side === 'left') {
        el.style.left = rand(10, Math.max(12, gutter - Math.min(maxW, text.length * size * 0.42))) + 'px';
        el.style.right = 'auto';
      } else {
        el.style.right = rand(10, Math.max(12, gutter - Math.min(maxW, text.length * size * 0.42))) + 'px';
        el.style.left = 'auto';
      }
      return true;
    }

    function chooseText(size) {
      var m = margins();
      var gutter = Math.max(m ? m.left : 0, m ? m.right : 0);
      var maxChars = Math.max(8, Math.floor((gutter + 40) / (size * 0.42)));
      var pool = WORDS.filter(function (w) { return w.length <= maxChars; });
      return pick(pool.length ? pool : WORDS.filter(function (w) { return w.length <= 12; }));
    }

    async function typeInto(el, text, speed) {
      var node = document.createTextNode('');
      var caret = document.createElement('i');
      caret.className = 'writing-caret';
      el.textContent = '';
      el.appendChild(node);
      el.appendChild(caret);
      for (var i = 0; i < text.length; i++) {
        await whenVisible();
        node.nodeValue = text.slice(0, i + 1);
        var pause = speed + rand(0, 42);
        if (/[\s,.’]/.test(text[i])) pause += rand(80, 180);
        await wait(pause);
      }
      return caret;
    }

    async function eraseFrom(el, caret) {
      var node = el.firstChild;
      if (!node || node.nodeType !== 3) return;
      var text = node.nodeValue || '';
      for (var i = text.length; i >= 0; i--) {
        await whenVisible();
        node.nodeValue = text.slice(0, i);
        await wait(rand(18, 42));
      }
      if (caret && caret.parentNode) caret.remove();
    }

    async function runWriter(delay) {
      await wait(delay);
      var el = document.createElement('span');
      el.className = 'writing-line';
      layer.appendChild(el);

      while (true) {
        if (!canRun()) {
          el.classList.remove('is-on');
          el.classList.add('is-off');
          await wait(1400);
          continue;
        }
        var space = margins();
        var gutter = Math.max(space ? space.left : 0, space ? space.right : 0);
        var size = gutter >= 160
          ? Math.round(rand(18, 30))
          : Math.round(rand(14, 18));
        var text = chooseText(size);
        if (!place(el, text, size)) {
          await wait(800);
          continue;
        }
        el.classList.toggle('is-accent', Math.random() < 0.18);
        el.style.setProperty('--w-op', String(rand(0.22, 0.42)));
        el.classList.remove('is-off');
        el.classList.add('is-on');
        var mode = Math.random() < 0.35 ? 'read' : 'write';
        var caret = await typeInto(el, text, mode === 'read' ? rand(16, 28) : rand(42, 78));
        await wait(rand(mode === 'read' ? 900 : 1400, mode === 'read' ? 2200 : 3600));
        if (Math.random() < 0.55) {
          await eraseFrom(el, caret);
        } else {
          el.classList.remove('is-on');
          el.classList.add('is-off');
          await wait(720);
          if (caret && caret.parentNode) caret.remove();
          el.textContent = '';
        }
        await wait(rand(400, 1600));
      }
    }

    function start() {
      if (layer.dataset.started || !canRun()) return;
      layer.dataset.started = '1';
      var count = window.innerWidth >= 1800 ? 4 : 3;
      for (var n = 0; n < count; n++) runWriter(n * rand(280, 900));
    }
    start();
    window.addEventListener('resize', start);
  })();
})();
