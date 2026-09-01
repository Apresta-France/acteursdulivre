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
      toggle.setAttribute('aria-expanded', mega.hidden ? 'false' : 'true');
    });
  }

  function initRail() {
    var list = document.querySelector('[data-rail-list]');
    if (!list) return;
    var compact = window.matchMedia('(max-width: 640px)');

    function gapSize() {
      var value = parseFloat(window.getComputedStyle(list).columnGap || window.getComputedStyle(list).gap);
      return isNaN(value) ? 0 : value;
    }

    function fit() {
      var items = Array.prototype.slice.call(list.querySelectorAll('a'));
      if (!items.length) return;
      if (compact.matches) {
        list.classList.remove('is-fitted', 'is-overflow');
        items.forEach(function (item) { item.hidden = false; });
        return;
      }

      items.forEach(function (item) { item.hidden = false; });
      var available = list.clientWidth;
      var gap = gapSize();
      var used = 0;
      var visible = [];

      items.forEach(function (item) {
        var need = visible.length === 0 ? item.offsetWidth : used + gap + item.offsetWidth;
        if (need <= available + 0.5) {
          used = need;
          visible.push(item);
        } else {
          item.hidden = true;
        }
      });

      var active = items.find(function (item) {
        return item.classList.contains('is-active') && item.hidden;
      });
      if (active) {
        active.hidden = false;
        var extra = active.offsetWidth + (visible.length ? gap : 0);
        while (visible.length && used + extra > available + 0.5) {
          var last = visible.pop();
          used -= last.offsetWidth + gap;
          last.hidden = true;
          if (used < 0) used = 0;
        }
      }

      list.classList.toggle('is-overflow', items.some(function (item) { return item.hidden; }));
      list.classList.add('is-fitted');
    }

    var scheduled = false;
    function schedule() {
      if (scheduled) return;
      scheduled = true;
      window.requestAnimationFrame(function () {
        scheduled = false;
        fit();
      });
    }

    fit();
    if (typeof ResizeObserver !== 'undefined') {
      new ResizeObserver(schedule).observe(list);
    } else {
      window.addEventListener('resize', schedule);
    }
    if (compact.addEventListener) compact.addEventListener('change', schedule);
    else if (compact.addListener) compact.addListener(schedule);
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(schedule);
  }
  initRail();

  document.querySelectorAll('[data-accordion]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var panel = btn.nextElementSibling;
      if (!panel) return;
      var open = panel.hidden;
      panel.hidden = !open;
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      var sign = btn.querySelector('[data-accordion-sign]');
      if (sign) sign.textContent = open ? '−' : '+';
    });
  });

  document.querySelectorAll('[data-user-menu]').forEach(function (btn) {
    var menu = btn.closest('.user-menu');
    var panel = menu && menu.querySelector('.user-menu-panel');
    if (!menu || !panel) return;
    function setOpen(open) {
      panel.hidden = !open;
      menu.classList.toggle('is-open', open);
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    btn.addEventListener('click', function (event) {
      event.stopPropagation();
      setOpen(panel.hidden);
    });
    document.addEventListener('click', function (event) {
      if (!menu.contains(event.target)) setOpen(false);
    });
    window.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') setOpen(false);
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

  function initHomeVideo() {
    var dialog = document.getElementById('home-video');
    if (!dialog) return;
    var frame = dialog.querySelector('[data-video-frame]');
    var src = dialog.getAttribute('data-video-src') || '';
    var title = dialog.getAttribute('data-video-title') || 'Vidéo de présentation';

    function clearFrame() {
      if (frame) frame.innerHTML = '';
      document.body.classList.remove('is-video-open');
    }

    function fillFrame() {
      if (!frame || frame.querySelector('iframe') || !src) return;
      var iframe = document.createElement('iframe');
      iframe.src = src;
      iframe.title = title;
      iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
      iframe.setAttribute('allowfullscreen', '');
      iframe.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
      frame.appendChild(iframe);
    }

    var ignoreBackdrop = false;

    function open() {
      fillFrame();
      document.body.classList.add('is-video-open');
      ignoreBackdrop = true;
      if (typeof dialog.showModal === 'function') dialog.showModal();
      else dialog.setAttribute('open', '');
      window.setTimeout(function () { ignoreBackdrop = false; }, 250);
    }

    function close() {
      if (typeof dialog.close === 'function' && dialog.open) dialog.close();
      else dialog.removeAttribute('open');
      clearFrame();
    }

    document.querySelectorAll('[data-video-open]').forEach(function (el) {
      el.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        open();
      });
    });
    dialog.querySelectorAll('[data-video-close]').forEach(function (btn) {
      btn.addEventListener('click', function (event) {
        event.stopPropagation();
        close();
      });
    });
    dialog.addEventListener('click', function (event) {
      if (ignoreBackdrop) return;
      if (event.target === dialog) close();
    });
    dialog.addEventListener('close', clearFrame);
  }
  initHomeVideo();

  function initHeroMosaic() {
    var root = document.querySelector('[data-hero-mosaic]');
    if (!root) return;
    var imgs = root.querySelectorAll('img');
    if (imgs.length < 2) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    var srcs = [];
    try {
      srcs = JSON.parse(root.getAttribute('data-hero-srcs') || '[]');
    } catch (err) {
      srcs = [];
    }
    if (!srcs.length) {
      srcs = Array.prototype.map.call(imgs, function (img) { return img.getAttribute('src'); });
    }
    if (srcs.length < 2) return;

    function shuffle(list) {
      var next = list.slice();
      for (var i = next.length - 1; i > 0; i--) {
        var j = Math.floor(Math.random() * (i + 1));
        var tmp = next[i];
        next[i] = next[j];
        next[j] = tmp;
      }
      return next;
    }

    var timer = window.setInterval(function () {
      if (document.hidden) return;
      var next = shuffle(srcs);
      var same = true;
      Array.prototype.forEach.call(imgs, function (img, i) {
        if (img.getAttribute('src') !== next[i]) same = false;
      });
      if (same) {
        next.push(next.shift());
      }
      Array.prototype.forEach.call(imgs, function (img) {
        img.classList.add('is-fading');
      });
      window.setTimeout(function () {
        Array.prototype.forEach.call(imgs, function (img, i) {
          if (next[i]) img.src = next[i];
          img.classList.remove('is-fading');
        });
      }, 280);
    }, 7000);

    window.addEventListener('pagehide', function () {
      window.clearInterval(timer);
    }, { once: true });
  }
  initHeroMosaic();

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

    main.querySelectorAll('aside.search-aside').forEach(function (aside) {
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

  function syncIntentCards() {
    var offers = document.querySelector('[name="offers_services"]');
    var offersOn = !!(offers && offers.checked);
    document.querySelectorAll('[data-if-offers]').forEach(function (box) {
      box.hidden = !offersOn;
      box.querySelectorAll('input[name="charte_ia"]').forEach(function (input) {
        input.required = offersOn;
      });
    });
    document.querySelectorAll('[data-intent-card]').forEach(function (card) {
      var input = card.querySelector('input[type="checkbox"]');
      card.classList.toggle('is-on', !!(input && input.checked));
    });
    document.querySelectorAll('[data-email-label]').forEach(function (label) {
      label.textContent = offersOn ? 'E-mail professionnel' : 'E-mail';
    });
  }
  document.querySelectorAll('[data-intent-card]').forEach(function (card) {
    var input = card.querySelector('input[type="checkbox"]');
    if (!input) return;
    input.addEventListener('change', syncIntentCards);
  });
  syncIntentCards();

  function debounce(fn, wait) {
    var timer;
    return function () {
      var args = arguments;
      clearTimeout(timer);
      timer = setTimeout(function () { fn.apply(null, args); }, wait);
    };
  }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function (ch) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
    });
  }

  function syncChips(scope) {
    (scope || document).querySelectorAll('.chip').forEach(function (chip) {
      var input = chip.querySelector('input');
      chip.classList.toggle('is-on', !!(input && input.checked));
    });
  }

  document.querySelectorAll('.chip input').forEach(function (input) {
    input.addEventListener('change', function () {
      var group = input.closest('[data-max-checks]');
      if (group && input.type === 'checkbox' && input.checked) {
        var max = parseInt(group.getAttribute('data-max-checks'), 10) || 3;
        var boxes = group.querySelectorAll('input[type="checkbox"]:checked');
        if (boxes.length > max) input.checked = false;
      }
      syncChips(input.closest('.chip-row') || document);
    });
  });
  syncChips();

  function renderSuggest(items, emptyMsg) {
    if (!items || !items.length) {
      return '<div class="search-suggest-empty">' + escapeHtml(emptyMsg || 'Aucun résultat pour l’instant. Essayez un métier ou un nom.') + '</div>';
    }
    return items.map(function (item) {
      return '<a href="' + escapeHtml(item.href) + '">' +
        '<span class="search-suggest-kind">' + escapeHtml(item.kind_label) + '</span>' +
        '<span><strong>' + escapeHtml(item.title) + '</strong><em>' + escapeHtml(item.subtitle) + (item.meta ? ' · ' + escapeHtml(item.meta) : '') + '</em></span>' +
        '</a>';
    }).join('');
  }

  function renderCards(items) {
    if (!items || !items.length) {
      return '<div class="search-empty"><strong>Aucun résultat pour cette recherche.</strong><span>Essayez un métier (illustration, traduction…) ou publiez une mission.</span></div>';
    }
    return items.map(function (item) {
      var media = item.thumb
        ? '<div class="search-card-media" style="background-image:url(\'' + escapeHtml(item.thumb) + '\')"></div>'
        : (item.kind === 'prestations'
          ? '<div class="search-card-media service-cover"' + (item.cover ? ' style="--service-cover-photo:url(\'' + escapeHtml(item.cover) + '\')"' : '') + ' role="img" aria-label="Visuel ' + escapeHtml(item.cat || 'Prestation') + '"><span class="service-cover-photo" aria-hidden="true"></span><span class="service-cover-kicker">acteursdulivre.fr</span><span class="service-cover-type">' + escapeHtml(item.cat || 'Prestation') + '</span></div>'
          : '<div class="search-card-media search-card-media-plain"><span class="avatar">' + escapeHtml((item.initials || item.title || 'AD').slice(0, 2).toUpperCase()) + '</span></div>');
      return '<a class="search-card' + (item.is_busy ? ' is-busy' : '') + '" href="' + escapeHtml(item.href) + '">' + media +
        '<div class="search-card-body">' +
          '<div class="search-card-kicker"><span>' + escapeHtml(item.kind_label) + '</span>' +
            (item.cat ? '<span>' + escapeHtml(item.cat) + '</span>' : '') +
            (item.live ? '<span class="search-live">Votre réseau</span>' : '') +
            (item.kind === 'prestataires' && item.availability_label
              ? '<span class="status-pill' + (item.is_busy ? ' is-busy' : ' is-available') + '">' + escapeHtml(item.availability_label) + '</span>'
              : '') +
          '</div>' +
          '<div class="search-card-title">' + escapeHtml(item.title) + '</div>' +
          '<div class="search-card-sub">' + escapeHtml(item.subtitle) + '</div>' +
          '<div class="search-card-meta"><span>' + escapeHtml(item.meta || '') + '</span>' +
            (item.price ? '<strong>' + escapeHtml(item.price) + '</strong>' : '') +
          '</div>' +
        '</div></a>';
    }).join('');
  }

  function fetchSearch(api, params, cb) {
    var url = api + (api.indexOf('?') >= 0 ? '&' : '?') + params.toString();
    fetch(url, { headers: { Accept: 'application/json' } })
      .then(function (res) { return res.json(); })
      .then(cb)
      .catch(function () {});
  }

  document.querySelectorAll('[data-live-search]').forEach(function (form) {
    var input = form.querySelector('[data-live-input]');
    var panel = form.querySelector('[data-live-panel]');
    var api = form.getAttribute('data-api');
    if (!input || !panel || !api) return;

    var emptyMsg = form.getAttribute('data-empty') || '';
    var run = debounce(function () {
      var q = input.value.trim();
      if (q.length < 2) {
        panel.hidden = true;
        panel.innerHTML = '';
        return;
      }
      var params = new URLSearchParams({ q: q, limit: '8' });
      form.querySelectorAll('input[type="hidden"]').forEach(function (hidden) {
        if (hidden.name && hidden.value) params.set(hidden.name, hidden.value);
      });
      fetchSearch(api, params, function (data) {
        panel.innerHTML = renderSuggest(data.suggestions || data.results || [], emptyMsg);
        panel.hidden = false;
      });
    }, 180);

    input.addEventListener('input', run);
    input.addEventListener('focus', function () {
      if (panel.innerHTML) panel.hidden = false;
    });
    document.addEventListener('click', function (event) {
      if (!form.contains(event.target)) panel.hidden = true;
    });
    input.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') panel.hidden = true;
    });
  });

  var searchPage = document.querySelector('[data-search-page]');
  if (searchPage) {
    var api = searchPage.getAttribute('data-api');
    var filters = searchPage.querySelector('[data-search-filters]');
    var results = searchPage.querySelector('[data-search-results]');
    var countEl = searchPage.querySelector('[data-search-count]');
    var titleEl = searchPage.querySelector('.search-head h1');
    var headerInput = document.querySelector('[data-live-input]');
    var hiddenQ = filters.querySelector('[data-search-q]');
    var activeBox = searchPage.querySelector('[data-search-active]');
    var budgetMin = filters.querySelector('[data-budget-min]');
    var budgetMax = filters.querySelector('[data-budget-max]');
    var budgetFill = searchPage.querySelector('[data-budget-fill]');
    var budgetLabel = searchPage.querySelector('[data-budget-label]');
    var BUDGET_DEFAULT_MIN = 200;
    var BUDGET_DEFAULT_MAX = 4000;

    function formatBudget(n) {
      return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' €';
    }

    function syncBudget() {
      if (!budgetMin || !budgetMax) return;
      var min = parseInt(budgetMin.value, 10);
      var max = parseInt(budgetMax.value, 10);
      if (min > max) {
        var tmp = min;
        min = max;
        max = tmp;
        budgetMin.value = min;
        budgetMax.value = max;
      }
      var span = parseInt(budgetMax.max, 10) || BUDGET_DEFAULT_MAX;
      if (budgetFill) {
        budgetFill.style.left = (min / span * 100) + '%';
        budgetFill.style.right = (100 - max / span * 100) + '%';
      }
      if (budgetLabel) budgetLabel.textContent = formatBudget(min) + ' — ' + formatBudget(max);
    }

    function currentParams() {
      var data = new FormData(filters);
      var params = new URLSearchParams();
      var forcedType = searchPage.getAttribute('data-type') || '';
      var q = (data.get('q') || '').toString().trim();
      if (q) params.set('q', q);
      var cat = (data.get('cat') || '').toString().trim();
      if (!cat) {
        try { cat = (new URLSearchParams(window.location.search).get('cat') || '').trim(); } catch (e) {}
      }
      if (cat) params.set('cat', cat);
      ['kind', 'metier', 'spec', 'delay', 'level', 'trust'].forEach(function (key) {
        data.getAll(key + '[]').forEach(function (value) {
          if (value) params.append(key + '[]', value);
        });
      });
      var min = budgetMin ? parseInt(budgetMin.value, 10) : BUDGET_DEFAULT_MIN;
      var max = budgetMax ? parseInt(budgetMax.value, 10) : BUDGET_DEFAULT_MAX;
      if (min !== BUDGET_DEFAULT_MIN) params.set('bmin', String(min));
      if (max !== BUDGET_DEFAULT_MAX) params.set('bmax', String(max));
      if (forcedType && forcedType !== 'all') params.set('type', forcedType);
      return params;
    }

    function syncActive() {
      if (!activeBox) return;
      var chips = [];
      filters.querySelectorAll('input[type="checkbox"]:checked').forEach(function (input) {
        var txt = input.closest('label') && input.closest('label').querySelector('.sf-txt');
        chips.push({
          name: (input.name || '').replace('[]', ''),
          value: input.value,
          label: txt ? txt.textContent : input.value
        });
      });
      var min = budgetMin ? parseInt(budgetMin.value, 10) : BUDGET_DEFAULT_MIN;
      var max = budgetMax ? parseInt(budgetMax.value, 10) : BUDGET_DEFAULT_MAX;
      if (min !== BUDGET_DEFAULT_MIN || max !== BUDGET_DEFAULT_MAX) {
        chips.push({ name: 'budget', value: '', label: formatBudget(min) + ' — ' + formatBudget(max) });
      }
      activeBox.hidden = chips.length === 0;
      activeBox.innerHTML = chips.map(function (chip) {
        return '<button type="button" class="sf-chip" data-clear-name="' + escapeHtml(chip.name) + '" data-clear-value="' + escapeHtml(chip.value) + '">' + escapeHtml(chip.label) + ' ✕</button>';
      }).join('');
    }

    function clearFilter(name, value) {
      if (name === 'budget') {
        if (budgetMin) budgetMin.value = BUDGET_DEFAULT_MIN;
        if (budgetMax) budgetMax.value = BUDGET_DEFAULT_MAX;
        syncBudget();
        return;
      }
      filters.querySelectorAll('input[name="' + name + '[]"]').forEach(function (input) {
        if (input.value === value) input.checked = false;
      });
    }

    var update = debounce(function () {
      var params = currentParams();
      fetchSearch(api, params, function (data) {
        results.innerHTML = renderCards(data.results || []);
        var n = data.count || 0;
        if (countEl) countEl.textContent = '· ' + n + ' résultat' + (n > 1 ? 's' : '');
        var label = data.query || data.cat || '';
        if (!label) {
          var hubType = data.type || searchPage.getAttribute('data-type') || '';
          if (hubType === 'prestations') label = 'Prestations à prix affiché';
          else if (hubType === 'prestataires') label = 'Prestataires du livre';
          else label = 'Tous les métiers du livre';
        }
        if (titleEl) titleEl.childNodes[0].textContent = label + ' ';
        var display = new URLSearchParams(params);
        if (!/\/recherche\/?$/.test(window.location.pathname)) {
          display.delete('type');
        }
        var next = display.toString();
        history.replaceState(null, '', window.location.pathname + (next ? '?' + next : ''));
        if (headerInput) headerInput.value = data.query || '';
        syncActive();
        refreshShareBars(searchPage, {
          url: window.location.href,
          title: 'Recherche : ' + label,
          text: 'Prestataires des métiers du livre sur acteursdulivre.fr'
        });
      });
    }, 160);

    syncBudget();
    syncActive();
    filters.addEventListener('input', function () {
      syncBudget();
      update();
    });
    filters.addEventListener('change', update);
    if (headerInput) {
      headerInput.addEventListener('input', function () {
        if (hiddenQ) hiddenQ.value = headerInput.value;
        update();
      });
    }
    searchPage.addEventListener('click', function (event) {
      var chip = event.target.closest('[data-clear-name]');
      if (chip) {
        clearFilter(chip.getAttribute('data-clear-name'), chip.getAttribute('data-clear-value') || '');
        syncBudget();
        syncActive();
        update();
        return;
      }
      var reset = event.target.closest('[data-search-reset]');
      if (!reset) return;
      event.preventDefault();
      filters.querySelectorAll('input[type="checkbox"]').forEach(function (input) { input.checked = false; });
      if (budgetMin) budgetMin.value = BUDGET_DEFAULT_MIN;
      if (budgetMax) budgetMax.value = BUDGET_DEFAULT_MAX;
      if (hiddenQ) hiddenQ.value = '';
      if (headerInput) headerInput.value = '';
      syncBudget();
      syncActive();
      update();
    });
  }

  document.querySelectorAll('[data-mode-switch]').forEach(function (group) {
    function sync() {
      group.querySelectorAll('.mode-option').forEach(function (option) {
        var input = option.querySelector('input');
        var on = !!(input && input.checked);
        option.classList.toggle('is-on', on);
        option.classList.toggle('is-available', on && input && input.value === 'available');
        option.classList.toggle('is-busy', on && input && input.value === 'busy');
      });
      var note = document.getElementById('availability');
      if (note) {
        var busy = !!(group.querySelector('input[value="busy"]:checked'));
        note.placeholder = busy ? 'reprend le 15 octobre' : 'dès maintenant, sous 48 h';
      }
    }
    group.addEventListener('change', sync);
    sync();
  });

  document.querySelectorAll('[data-tabs]').forEach(function (nav) {
    var form = nav.parentElement;
    nav.querySelectorAll('[data-tab]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        nav.querySelectorAll('[data-tab]').forEach(function (other) { other.classList.toggle('is-on', other === btn); });
        (form || document).querySelectorAll('[data-tab-panel]').forEach(function (panel) {
          panel.hidden = panel.getAttribute('data-tab-panel') !== btn.getAttribute('data-tab');
        });
      });
    });
  });

  document.querySelectorAll('[data-repeat-add]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var name = btn.getAttribute('data-repeat-add');
      var list = document.querySelector('[data-repeat="' + name + '"]');
      var tpl = document.getElementById('tpl-' + name);
      if (!list || !tpl) return;
      var index = 0;
      list.querySelectorAll('[name]').forEach(function (input) {
        var match = (input.getAttribute('name') || '').match(/\[(\d+)\]/);
        if (match) index = Math.max(index, parseInt(match[1], 10) + 1);
      });
      var html = tpl.innerHTML.replace(/__i__/g, String(index));
      list.insertAdjacentHTML('beforeend', html);
      bindFilePicks(list.lastElementChild);
    });
  });

  document.addEventListener('click', function (event) {
    var remove = event.target.closest('[data-repeat-remove]');
    if (!remove) return;
    var row = remove.closest('[data-repeat-row]');
    var list = remove.closest('[data-repeat]');
    if (row && list && list.querySelectorAll('[data-repeat-row]').length > 1) {
      row.remove();
    }
  });

  var publish = document.querySelector('[data-publish-form]');
  if (publish) {
    var volumeHints = {};
    var briefHints = {};
    try { volumeHints = JSON.parse(publish.getAttribute('data-volume-hints') || '{}'); } catch (e) {}
    try { briefHints = JSON.parse(publish.getAttribute('data-brief-hints') || '{}'); } catch (e) {}

    function selectedTrade() {
      return (publish.querySelector('input[name="category_name"]:checked') || {}).value || '';
    }

    function syncFields() {
      var cat = selectedTrade();
      var hint = volumeHints[cat];
      var wrap = publish.querySelector('[data-volume-wrap]');
      var input = publish.querySelector('[data-volume-input]');
      var label = publish.querySelector('[data-volume-label]');
      var grid = publish.querySelector('[data-publish-metrics]');
      var brief = publish.querySelector('[data-preview-brief]');
      if (wrap && input) {
        if (hint) {
          wrap.hidden = false;
          input.disabled = false;
          if (label) label.textContent = hint.label;
          input.placeholder = hint.placeholder || '';
          if (grid) grid.classList.remove('is-two');
        } else {
          wrap.hidden = true;
          input.disabled = true;
          if (grid) grid.classList.add('is-two');
        }
      }
      if (brief && briefHints[cat]) {
        brief.placeholder = briefHints[cat];
      }
    }

    function preview() {
      var title = (publish.querySelector('[data-preview-title]') || {}).value || 'Votre titre apparaîtra ici';
      var brief = (publish.querySelector('[data-preview-brief]') || {}).value || 'Le brief s’affiche au fil de la saisie.';
      var min = (publish.querySelector('[data-preview-min]') || {}).value;
      var max = (publish.querySelector('[data-preview-max]') || {}).value;
      var cat = selectedTrade();
      var budget = (min && max) ? min + ' – ' + max + ' €' : (max || min ? (max || min) + ' €' : 'Budget à convenir');
      var outTitle = document.querySelector('[data-preview-out-title]');
      var outBrief = document.querySelector('[data-preview-out-brief]');
      var outCat = document.querySelector('[data-preview-out-cat]');
      var outBudget = document.querySelector('[data-preview-out-budget]');
      if (outTitle) outTitle.textContent = title;
      if (outBrief) outBrief.textContent = brief;
      if (outCat) outCat.textContent = cat;
      if (outBudget) outBudget.textContent = budget;
    }

    publish.addEventListener('input', preview);
    publish.addEventListener('change', function () {
      syncFields();
      preview();
    });
    syncFields();
    preview();
  }

  function showToast(message) {
    var existing = document.querySelector('.share-toast');
    if (existing) existing.remove();
    var toast = document.createElement('div');
    toast.className = 'share-toast';
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(function () { toast.remove(); }, 3200);
  }

  function copyText(value) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      return navigator.clipboard.writeText(value);
    }
    return new Promise(function (resolve, reject) {
      var input = document.createElement('textarea');
      input.value = value;
      input.setAttribute('readonly', '');
      input.style.position = 'absolute';
      input.style.left = '-9999px';
      document.body.appendChild(input);
      input.select();
      try {
        document.execCommand('copy');
        resolve();
      } catch (err) {
        reject(err);
      }
      input.remove();
    });
  }

  function sharePayload(root) {
    return {
      url: (root && root.getAttribute('data-url')) || window.location.href,
      title: (root && root.getAttribute('data-title')) || document.title,
      text: (root && root.getAttribute('data-text')) || ''
    };
  }

  function refreshShareBars(scope, payload) {
    (scope || document).querySelectorAll('[data-share]').forEach(function (bar) {
      if (payload.url) bar.setAttribute('data-url', payload.url);
      if (payload.title) bar.setAttribute('data-title', payload.title);
      if (payload.text) bar.setAttribute('data-text', payload.text);
      var url = encodeURIComponent(bar.getAttribute('data-url') || window.location.href);
      var title = encodeURIComponent(bar.getAttribute('data-title') || document.title);
      var text = bar.getAttribute('data-text') || '';
      var message = encodeURIComponent((bar.getAttribute('data-title') || document.title) + (text ? '\n' + text : '') + '\n' + decodeURIComponent(url));
      bar.querySelectorAll('[data-share-network]').forEach(function (link) {
        var id = link.getAttribute('data-share-network');
        if (id === 'facebook') link.href = 'https://www.facebook.com/sharer/sharer.php?u=' + url;
        if (id === 'linkedin') link.href = 'https://www.linkedin.com/sharing/share-offsite/?url=' + url;
        if (id === 'x') link.href = 'https://twitter.com/intent/tweet?url=' + url + '&text=' + title;
        if (id === 'whatsapp') link.href = 'https://api.whatsapp.com/send?text=' + message;
      });
    });
  }

  document.querySelectorAll('[data-share-native]').forEach(function (btn) {
    if (navigator.share) btn.hidden = false;
  });

  document.addEventListener('click', function (event) {
    var copyField = event.target.closest('[data-copy]');
    if (copyField && !event.target.closest('[data-share]')) {
      event.preventDefault();
      var value = copyField.getAttribute('data-copy') || '';
      if (!value) return;
      copyText(value).then(function () {
        showToast('Copié dans le presse-papiers.');
      }).catch(function () {
        showToast('Impossible de copier.');
      });
      return;
    }
    var copyBtn = event.target.closest('[data-share-copy]');
    var nativeBtn = event.target.closest('[data-share-native]');
    if (!copyBtn && !nativeBtn) return;
    event.preventDefault();
    var root = (copyBtn || nativeBtn).closest('[data-share]');
    var data = sharePayload(root);
    if (nativeBtn && navigator.share) {
      navigator.share({ title: data.title, text: data.text, url: data.url }).catch(function () {});
      return;
    }
    var network = copyBtn ? copyBtn.getAttribute('data-share-network') : '';
    copyText(data.url).then(function () {
      showToast(network === 'instagram'
        ? 'Lien copié. Collez-le dans Instagram (story, message ou bio).'
        : 'Lien copié.');
    }).catch(function () {
      showToast('Impossible de copier le lien.');
    });
  });

  var rateBox = document.querySelector('[data-rate-fields]');
  if (rateBox) {
    var bookstore = rateBox.getAttribute('data-bookstore-trade') || 'Librairie';
    function currentKind() {
      var on = rateBox.querySelector('[data-rate-kind]:checked');
      return on ? on.value : 'price';
    }
    function applyRateKind(kind, fromTrade) {
      var input = rateBox.querySelector('[data-rate-input]');
      var note = rateBox.querySelector('[data-rate-note]');
      var label = rateBox.querySelector('[data-rate-label]');
      var noteLabel = rateBox.querySelector('[data-rate-note-label]');
      var radios = rateBox.querySelectorAll('[data-rate-kind]');
      radios.forEach(function (radio) {
        radio.checked = radio.value === kind;
      });
      syncChips(rateBox);
      if (label) label.textContent = kind === 'percent' ? 'Commission' : 'Tarif';
      if (noteLabel) noteLabel.textContent = kind === 'percent' ? 'Précision' : 'Précision tarifaire';
      if (input) {
        input.placeholder = input.getAttribute(kind === 'percent' ? 'data-placeholder-percent' : 'data-placeholder-price') || '';
        input.setAttribute('inputmode', kind === 'percent' ? 'decimal' : 'text');
        if (fromTrade && kind === 'percent' && /€|eur|\/\s*heure/i.test(input.value)) input.value = '';
      }
      if (note) {
        note.placeholder = note.getAttribute(kind === 'percent' ? 'data-placeholder-percent' : 'data-placeholder-price') || '';
      }
    }
    rateBox.querySelectorAll('[data-rate-kind]').forEach(function (radio) {
      radio.addEventListener('change', function () { applyRateKind(currentKind(), false); });
    });
    document.querySelectorAll('[data-trades] input[type="checkbox"]').forEach(function (box) {
      box.addEventListener('change', function () {
        var checked = Array.prototype.slice.call(document.querySelectorAll('[data-trades] input[type="checkbox"]:checked'))
          .map(function (el) { return el.value; });
        if (checked.indexOf(bookstore) !== -1) applyRateKind('percent', true);
      });
    });
    applyRateKind(currentKind(), false);
  }

  document.querySelectorAll('[data-name-mode]').forEach(function (box) {
    function syncNameMode() {
      var on = box.querySelector('input[name="name_mode"]:checked');
      var wrap = box.querySelector('[data-public-name-wrap]');
      if (wrap) wrap.hidden = !(on && on.value === 'custom');
    }
    box.addEventListener('change', syncNameMode);
    syncNameMode();
  });

  var coverForm = document.querySelector('[data-service-cover]');
  if (coverForm) {
    var coverType = coverForm.querySelector('.service-cover-type');
    var coverBrand = coverForm.querySelector('.service-cover');
    var coverPhoto = coverForm.querySelector('[data-cover-photo]');
    var coverFile = coverForm.querySelector('[data-cover-file]');
    function setCoverLabel(label) {
      if (coverType) coverType.textContent = label || 'Prestation';
    }
    function setCoverPhoto(url) {
      if (!coverBrand || !url) return;
      coverBrand.style.setProperty('--service-cover-photo', "url('" + url + "')");
    }
    coverForm.querySelectorAll('input[name="category_name"]').forEach(function (radio) {
      radio.addEventListener('change', function () {
        setCoverLabel(radio.value);
        setCoverPhoto(radio.getAttribute('data-cover-url') || '');
      });
    });
    var coverSelect = coverForm.querySelector('[data-cover-trade]');
    if (coverSelect) {
      coverSelect.addEventListener('change', function () {
        var option = coverSelect.options[coverSelect.selectedIndex];
        setCoverLabel(option ? option.textContent.trim() : coverSelect.value);
        setCoverPhoto(option ? (option.getAttribute('data-cover-url') || '') : '');
      });
    }
    if (coverFile) {
      coverFile.addEventListener('change', function () {
        var file = coverFile.files && coverFile.files[0];
        if (!file || !coverPhoto) {
          if (coverBrand) coverBrand.hidden = false;
          if (coverPhoto) coverPhoto.hidden = true;
          return;
        }
        var url = URL.createObjectURL(file);
        coverPhoto.style.backgroundImage = 'url(\'' + url + '\')';
        coverPhoto.hidden = false;
        if (coverBrand) coverBrand.hidden = true;
      });
    }
  }

  document.querySelectorAll('[data-avatar-field]').forEach(function (field) {
    var input = field.querySelector('[data-avatar-input]');
    var preview = field.querySelector('[data-avatar-preview]');
    if (!input || !preview) return;
    input.addEventListener('change', function () {
      var file = input.files && input.files[0];
      if (!file) return;
      var url = URL.createObjectURL(file);
      preview.innerHTML = '<img class="avatar avatar-photo" src="' + url + '" alt="" width="88" height="88">';
      document.querySelectorAll('[data-preview-out-avatar]').forEach(function (slot) {
        slot.innerHTML = '<img class="avatar avatar-photo" src="' + url + '" alt="" width="56" height="56">';
      });
    });
  });

  document.querySelectorAll('[data-count]').forEach(function (area) {
    var out = document.querySelector('[data-count-out]');
    var min = parseInt(area.getAttribute('data-count-min') || '0', 10);
    function tick() {
      if (!out) return;
      var n = (area.value || '').trim().length;
      out.textContent = n < min
        ? n + ' caractère' + (n > 1 ? 's' : '') + ' — ' + min + ' donnent déjà une fiche crédible.'
        : n + ' caractères. C’est suffisant pour commencer.';
    }
    area.addEventListener('input', tick);
    tick();
  });

  var onboard = document.querySelector('[data-onboard]');
  if (onboard) {
    var titleHints = {};
    var presHints = {};
    try { titleHints = JSON.parse(onboard.getAttribute('data-title-hints') || '{}'); } catch (e) {}
    try { presHints = JSON.parse(onboard.getAttribute('data-pres-hints') || '{}'); } catch (e) {}

    function firstTrade() {
      var checked = onboard.querySelector('[data-onboard-trades] input:checked');
      return checked ? checked.value : '';
    }

    function updateFichePreview() {
      if (!onboard.querySelector('[data-onboard-preview]')) return;
      var first = ((onboard.querySelector('[data-preview-first]') || {}).value || '').trim();
      var last = ((onboard.querySelector('[data-preview-last]') || {}).value || '').trim();
      var title = ((onboard.querySelector('[data-preview-title]') || {}).value || '').trim();
      var city = ((onboard.querySelector('[data-preview-city]') || {}).value || '').trim();
      var pres = ((onboard.querySelector('[data-preview-pres]') || {}).value || '').trim();
      var name = (first + ' ' + last).trim() || 'Vous';
      var subParts = [];
      if (title) subParts.push(title);
      if (city) subParts.push(city);
      onboard.querySelectorAll('[data-preview-out-name]').forEach(function (el) { el.textContent = name; });
      onboard.querySelectorAll('[data-preview-out-sub]').forEach(function (el) {
        el.textContent = subParts.length ? subParts.join(' · ') : 'Le titre et la ville apparaîtront ici';
      });
      onboard.querySelectorAll('[data-preview-out-title]').forEach(function (el) {
        el.textContent = title || 'Titre de la fiche à préciser';
        if (city) el.textContent += ' · ' + city;
      });
      onboard.querySelectorAll('[data-preview-out-pres]').forEach(function (el) {
        el.textContent = pres || 'Votre présentation s’affiche au fil de la saisie.';
      });
      var tradeBox = onboard.querySelector('[data-preview-out-trades]');
      if (tradeBox) {
        var labels = [];
        onboard.querySelectorAll('[data-onboard-trades] input:checked').forEach(function (box) {
          labels.push('<span class="chip-static">' + escapeHtml(box.value) + '</span>');
        });
        tradeBox.innerHTML = labels.join('');
      }
    }

    onboard.addEventListener('input', updateFichePreview);
    onboard.addEventListener('change', function (event) {
      var target = event.target;
      if (target && target.closest && target.closest('[data-onboard-trades]')) {
        var trade = firstTrade();
        var titleInput = onboard.querySelector('[data-title-input]');
        var presInput = onboard.querySelector('[data-pres-input]');
        if (titleInput && titleHints[trade]) titleInput.setAttribute('placeholder', titleHints[trade]);
        if (presInput && presHints[trade]) presInput.setAttribute('placeholder', presHints[trade]);
      }
      updateFichePreview();
    });
    updateFichePreview();
  }

  function bindFilePick(pick) {
    if (!pick || pick.dataset.filePickBound) return;
    pick.dataset.filePickBound = '1';
    var input = pick.querySelector('[data-file-input], input[type="file"]');
    var name = pick.querySelector('[data-file-name], [data-dropzone-label]');
    if (!input || !name) return;
    var empty = name.textContent;
    function setFile(file) {
      if (!file) {
        name.textContent = empty;
        pick.classList.remove('has-file');
        return;
      }
      name.textContent = file.name;
      pick.classList.add('has-file');
    }
    input.addEventListener('change', function () {
      setFile(input.files && input.files[0] ? input.files[0] : null);
    });
    ['dragenter', 'dragover'].forEach(function (ev) {
      pick.addEventListener(ev, function (e) {
        e.preventDefault();
        pick.classList.add('is-over');
      });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
      pick.addEventListener(ev, function (e) {
        e.preventDefault();
        pick.classList.remove('is-over');
      });
    });
    pick.addEventListener('drop', function (e) {
      var files = e.dataTransfer && e.dataTransfer.files;
      if (!files || !files[0]) return;
      var transfer = new DataTransfer();
      transfer.items.add(files[0]);
      input.files = transfer.files;
      setFile(files[0]);
      input.dispatchEvent(new Event('change', { bubbles: true }));
    });
  }

  function bindFilePicks(root) {
    var scope = root || document;
    scope.querySelectorAll('[data-file-pick], [data-dropzone-zone]').forEach(bindFilePick);
    if (root && root.matches && root.matches('[data-file-pick], [data-dropzone-zone]')) bindFilePick(root);
  }

  bindFilePicks(document);

  function toEditorHtml(raw) {
    if (!raw) return '';
    if (/<[a-z][\s\S]*>/i.test(raw)) return raw;
    return raw.split(/\n\s*\n/).map(function (block) {
      return '<p>' + block.replace(/\n/g, '<br>') + '</p>';
    }).join('');
  }

  function editorIsEmpty(editor) {
    return (editor.innerText || '').replace(/\u00a0/g, ' ').trim() === '';
  }

  document.querySelectorAll('[data-wysiwyg]').forEach(function (wrap) {
    var source = wrap.querySelector('.wysiwyg-source');
    var editor = wrap.querySelector('.wysiwyg-editor');
    var toolbar = wrap.querySelector('.wysiwyg-toolbar');
    if (!source || !editor || !toolbar) return;

    editor.innerHTML = toEditorHtml(source.value);
    editor.setAttribute('data-placeholder', source.getAttribute('placeholder') || '');
    toolbar.hidden = false;
    editor.hidden = false;
    wrap.classList.add('is-ready');

    function sync() {
      source.value = editorIsEmpty(editor) ? '' : editor.innerHTML;
    }

    editor.addEventListener('input', sync);
    editor.addEventListener('blur', sync);
    var form = wrap.closest('form');
    if (form) form.addEventListener('submit', sync);

    toolbar.addEventListener('mousedown', function (event) {
      event.preventDefault();
    });

    toolbar.addEventListener('click', function (event) {
      var btn = event.target.closest('[data-wysiwyg-cmd]');
      if (!btn) return;
      var cmd = btn.getAttribute('data-wysiwyg-cmd');
      editor.focus();
      if (cmd === 'createLink') {
        var current = window.getSelection() && window.getSelection().toString();
        var href = window.prompt('Adresse du lien', current && /^https?:\/\//i.test(current) ? current : 'https://');
        if (!href) return;
        if (!/^https?:\/\//i.test(href) && !/^mailto:/i.test(href)) href = 'https://' + href.replace(/^\/+/, '');
        document.execCommand('createLink', false, href);
      } else {
        document.execCommand(cmd, false, null);
      }
      sync();
    });
  });

  function initQuoteRecap() {
    var amountInput = document.getElementById('jalon-amount');
    var depositInput = document.getElementById('jalon-deposit');
    var recap = document.querySelector('[data-quote-recap]');
    if (!amountInput || !recap) return;

    function parseEuros(value) {
      var s = String(value || '').trim().replace(/\s/g, '').replace(',', '.');
      if (!s) return 0;
      var n = parseFloat(s);
      if (isNaN(n) || n < 0) return 0;
      return Math.floor(n);
    }

    function formatEuros(n) {
      return n.toLocaleString('fr-FR') + ' €';
    }

    function sync() {
      var amount = parseEuros(amountInput.value);
      var deposit = depositInput ? parseEuros(depositInput.value) : 0;
      var amountEl = recap.querySelector('[data-quote-recap-amount]');
      var depositEl = recap.querySelector('[data-quote-recap-deposit]');
      var balanceEl = recap.querySelector('[data-quote-recap-balance]');
      if (amountEl) amountEl.textContent = amount > 0 ? formatEuros(amount) : '—';
      if (depositEl) depositEl.textContent = deposit > 0 ? formatEuros(deposit) : '—';
      if (balanceEl) {
        balanceEl.textContent = amount > 0 ? formatEuros(Math.max(0, amount - deposit)) : '—';
      }
    }

    amountInput.addEventListener('input', sync);
    if (depositInput) depositInput.addEventListener('input', sync);
    sync();
  }
  initQuoteRecap();

  document.querySelectorAll('[data-order-total]').forEach(function (root) {
    var base = parseInt(root.getAttribute('data-base') || '0', 10) || 0;
    var orderHref = root.getAttribute('data-order-href') || '';
    function formatEuros(n) {
      return n.toLocaleString('fr-FR') + ' € TTC';
    }
    function currentTotal() {
      var total = base;
      root.querySelectorAll('input[type="checkbox"][data-price]:checked').forEach(function (box) {
        total += parseInt(box.getAttribute('data-price') || '0', 10) || 0;
      });
      return total;
    }
    function withOptions(href) {
      if (!href) return href;
      var url;
      try {
        url = new URL(href, window.location.origin);
      } catch (err) {
        return href;
      }
      url.searchParams.delete('options[]');
      url.searchParams.delete('options');
      root.querySelectorAll('input[type="checkbox"][data-price]:checked').forEach(function (box) {
        url.searchParams.append('options[]', box.value);
      });
      return url.pathname + url.search;
    }
    function syncTotal() {
      var total = currentTotal();
      var label = 'Commander — ' + formatEuros(total);
      root.querySelectorAll('[data-order-total-value]').forEach(function (el) {
        el.textContent = formatEuros(total);
      });
      root.querySelectorAll('[data-order-cta-label]').forEach(function (el) {
        el.textContent = label;
      });
      var href = withOptions(orderHref);
      if (href) {
        root.querySelectorAll('[data-order-cta]').forEach(function (el) {
          el.setAttribute('href', href);
        });
      }
    }
    root.querySelectorAll('[data-order-formule]').forEach(function (tab) {
      tab.addEventListener('click', function () {
        root.querySelectorAll('[data-order-formule]').forEach(function (other) {
          var on = other === tab;
          other.classList.toggle('is-on', on);
          other.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        base = parseInt(tab.getAttribute('data-price') || '0', 10) || 0;
        root.setAttribute('data-base', String(base));
        var nameEl = root.querySelector('[data-order-formule-name]');
        var delayEl = root.querySelector('[data-order-formule-delay]');
        var descEl = root.querySelector('[data-order-formule-desc]');
        var priceEl = root.querySelector('[data-order-formule-price]');
        var desc = tab.getAttribute('data-desc') || '';
        if (nameEl) nameEl.textContent = tab.getAttribute('data-name') || '';
        if (delayEl) delayEl.textContent = tab.getAttribute('data-delay') || 'Délai à convenir';
        if (descEl) {
          descEl.textContent = desc;
          descEl.hidden = desc === '';
        }
        if (priceEl) priceEl.textContent = formatEuros(base);
        if (orderHref) {
          try {
            var next = new URL(orderHref, window.location.origin);
            var packageId = tab.getAttribute('data-id') || '';
            if (packageId && packageId !== '0') next.searchParams.set('formule', packageId);
            else next.searchParams.delete('formule');
            orderHref = next.pathname + next.search;
            root.setAttribute('data-order-href', orderHref);
          } catch (err) {}
        }
        syncTotal();
      });
    });
    root.addEventListener('change', syncTotal);
    syncTotal();
  });

  function formatTimeAgo(iso) {
    var ts = Date.parse(iso);
    if (!ts) return '';
    var diff = Math.max(0, Math.round((Date.now() - ts) / 1000));
    if (diff < 45) return "à l'instant";
    if (diff < 3600) return 'il y a ' + Math.max(1, Math.floor(diff / 60)) + ' min';
    if (diff < 86400) return 'il y a ' + Math.floor(diff / 3600) + ' h';
    if (diff < 86400 * 7) return 'il y a ' + Math.floor(diff / 86400) + ' j';
    return '';
  }

  function refreshTimeAgo() {
    document.querySelectorAll('[data-time-ago]').forEach(function (el) {
      var label = formatTimeAgo(el.getAttribute('data-time-ago') || '');
      if (label) el.textContent = label;
    });
  }

  function initInboxLive() {
    var box = document.querySelector('[data-inbox-thread]');
    if (!box) return;
    var syncUrl = box.getAttribute('data-sync');
    if (!syncUrl) return;
    var lastId = parseInt(box.getAttribute('data-last-id') || '0', 10) || 0;
    var timer = null;
    var busy = false;

    function currentLastId() {
      var max = lastId;
      box.querySelectorAll('[data-msg-id]').forEach(function (el) {
        var id = parseInt(el.getAttribute('data-msg-id') || '0', 10) || 0;
        if (id > max) max = id;
      });
      return max;
    }

    function nearBottom() {
      return box.scrollHeight - box.scrollTop - box.clientHeight < 140;
    }

    function updateActivePreview(item) {
      var row = document.querySelector('.inbox-item.is-on');
      if (!row) return;
      var preview = row.querySelector('[data-inbox-preview]');
      var whenEl = row.querySelector('[data-time-ago]');
      if (preview && item.preview) preview.textContent = item.preview;
      if (whenEl && item.created_at) {
        whenEl.setAttribute('data-time-ago', item.created_at);
        whenEl.textContent = formatTimeAgo(item.created_at) || item.when || whenEl.textContent;
      }
    }

    function appendMessages(items) {
      if (!items || !items.length) return;
      var stick = nearBottom();
      items.forEach(function (item) {
        var id = parseInt(item.id, 10) || 0;
        if (id && box.querySelector('[data-msg-id="' + id + '"]')) return;
        box.insertAdjacentHTML('beforeend', item.html || '');
        if (id > lastId) lastId = id;
        updateActivePreview(item);
      });
      box.setAttribute('data-last-id', String(lastId));
      if (stick) box.scrollTop = box.scrollHeight;
    }

    function poll() {
      if (busy) {
        schedule();
        return;
      }
      if (document.hidden) {
        schedule();
        return;
      }
      busy = true;
      lastId = currentLastId();
      var url = syncUrl + (syncUrl.indexOf('?') >= 0 ? '&' : '?') + 'after=' + encodeURIComponent(String(lastId));
      fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
        .then(function (res) {
          var type = res.headers.get('content-type') || '';
          if (!res.ok || type.indexOf('json') < 0) return null;
          return res.json();
        })
        .then(function (data) {
          if (data && data.messages) appendMessages(data.messages);
        })
        .catch(function () {})
        .then(function () {
          busy = false;
          schedule();
        });
    }

    function schedule() {
      clearTimeout(timer);
      timer = setTimeout(poll, document.hidden ? 20000 : 4000);
    }

    document.addEventListener('visibilitychange', function () {
      if (!document.hidden) poll();
    });

    box.scrollTop = box.scrollHeight;
    schedule();
  }

  refreshTimeAgo();
  setInterval(refreshTimeAgo, 30000);
  initInboxLive();
  initArticleToc();
  initBillingIds();

  function initBillingIds() {
    var form = document.querySelector('[data-billing-ids]');
    if (!form) return;
    var siret = form.querySelector('#siret');
    var siren = form.querySelector('#siren');
    if (!siret || !siren) return;
    function digits(value) {
      return String(value || '').replace(/\D+/g, '');
    }
    var auto = '';
    var initialSiren = digits(siren.value);
    var initialSiret = digits(siret.value);
    if (initialSiren && initialSiret.indexOf(initialSiren) === 0) {
      auto = initialSiren;
    }
    siret.addEventListener('input', function () {
      var d = digits(siret.value);
      var current = digits(siren.value);
      if (d.length >= 9 && (current === '' || current === auto)) {
        auto = d.slice(0, 9);
        siren.value = auto;
      }
    });
  }

  function initArticleToc() {
    var toc = document.querySelector('[data-article-toc]');
    if (!toc) return;
    var links = toc.querySelectorAll('a[href^="#"]');
    var toggle = toc.querySelector('[data-toc-toggle]');
    if (toggle) {
      toggle.addEventListener('click', function () {
        var open = toc.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
      links.forEach(function (a) {
        a.addEventListener('click', function () {
          toc.classList.remove('is-open');
          toggle.setAttribute('aria-expanded', 'false');
        });
      });
    }
    if (!('IntersectionObserver' in window)) return;
    var observed = [];
    links.forEach(function (a) {
      var href = a.getAttribute('href') || '';
      var id = href.charAt(0) === '#' ? href.slice(1) : '';
      var el = id ? document.getElementById(id) : null;
      if (el) observed.push({ id: id, el: el, link: a });
    });
    if (!observed.length) return;
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        var id = entry.target.id;
        links.forEach(function (a) {
          a.classList.toggle('is-active', a.getAttribute('href') === '#' + id);
        });
      });
    }, { rootMargin: '-15% 0px -70% 0px', threshold: 0 });
    observed.forEach(function (item) {
      observer.observe(item.el);
    });
  }
})();
