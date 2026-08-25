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
})();
