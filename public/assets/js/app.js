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

  function initPortfolioZoom() {
    var triggers = Array.prototype.slice.call(document.querySelectorAll('[data-zoom]'));
    var dialog = document.querySelector('[data-zoom-dialog]') || document.getElementById('portfolio-zoom');
    if (!triggers.length || !dialog) return;

    var img = dialog.querySelector('[data-zoom-img]');
    var titleEl = document.getElementById('portfolio-zoom-title');
    var captionEl = dialog.querySelector('[data-zoom-caption]');
    var descEl = dialog.querySelector('[data-zoom-desc]');
    var prevBtn = dialog.querySelector('[data-zoom-prev]');
    var nextBtn = dialog.querySelector('[data-zoom-next]');
    var index = 0;
    var ignoreBackdrop = false;

    function itemData(el) {
      return {
        src: el.getAttribute('href') || '',
        title: el.getAttribute('data-zoom-title') || '',
        caption: el.getAttribute('data-zoom-caption') || '',
        desc: el.getAttribute('data-zoom-desc') || ''
      };
    }

    function show(i) {
      index = (i + triggers.length) % triggers.length;
      var data = itemData(triggers[index]);
      if (img) {
        img.src = data.src;
        img.alt = data.title || 'Exemple agrandi';
      }
      if (titleEl) titleEl.textContent = data.title;
      if (captionEl) {
        captionEl.textContent = data.caption;
        captionEl.hidden = !data.caption;
      }
      if (descEl) {
        descEl.textContent = data.desc;
        descEl.hidden = !data.desc;
      }
      var many = triggers.length > 1;
      if (prevBtn) prevBtn.hidden = !many;
      if (nextBtn) nextBtn.hidden = !many;
    }

    function open(i) {
      show(i);
      document.body.classList.add('is-zoom-open');
      ignoreBackdrop = true;
      if (typeof dialog.showModal === 'function') dialog.showModal();
      else dialog.setAttribute('open', '');
      window.setTimeout(function () { ignoreBackdrop = false; }, 250);
    }

    function close() {
      if (typeof dialog.close === 'function' && dialog.open) dialog.close();
      else dialog.removeAttribute('open');
      document.body.classList.remove('is-zoom-open');
      if (img) {
        img.removeAttribute('src');
        img.alt = '';
      }
    }

    function step(delta) {
      if (triggers.length < 2) return;
      show(index + delta);
    }

    triggers.forEach(function (el, i) {
      el.addEventListener('click', function (event) {
        event.preventDefault();
        open(i);
      });
    });
    dialog.querySelectorAll('[data-zoom-close]').forEach(function (btn) {
      btn.addEventListener('click', function (event) {
        event.stopPropagation();
        close();
      });
    });
    if (prevBtn) prevBtn.addEventListener('click', function (event) {
      event.stopPropagation();
      step(-1);
    });
    if (nextBtn) nextBtn.addEventListener('click', function (event) {
      event.stopPropagation();
      step(1);
    });
    dialog.addEventListener('click', function (event) {
      if (ignoreBackdrop) return;
      if (event.target === dialog) close();
    });
    dialog.addEventListener('close', function () {
      document.body.classList.remove('is-zoom-open');
      if (img) {
        img.removeAttribute('src');
        img.alt = '';
      }
    });
    dialog.addEventListener('keydown', function (event) {
      if (event.key === 'ArrowLeft') step(-1);
      if (event.key === 'ArrowRight') step(1);
    });
  }
  initPortfolioZoom();

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

  function renderMissionRows(items) {
    if (!items || !items.length) {
      return '<div class="search-empty"><strong>Aucune recherche ouverte pour ces critères.</strong><span>Publiez la vôtre, ou élargissez le filtre.</span></div>';
    }
    var rows = items.map(function (item) {
      var tags = (item.tags || []).filter(Boolean).map(function (tag) {
        return '<span class="chip-static dark">' + escapeHtml(String(tag)) + '</span>';
      }).join('');
      return '<a class="mission-row" href="' + escapeHtml(item.href) + '">' +
        '<div>' +
          '<div class="mission-row-title">' + escapeHtml(item.title) +
            (item.live ? '<span class="search-live">Nouvelle</span>' : '') +
          '</div>' +
          '<div class="mission-row-sub">' + escapeHtml(item.subtitle || '') + '</div>' +
          (tags ? '<div class="chip-row">' + tags + '</div>' : '') +
        '</div>' +
        '<div><strong>' + escapeHtml(item.price || '') + '</strong><span>' + escapeHtml(item.meta || '') + '</span></div>' +
      '</a>';
    }).join('');
    return '<div class="missions-list">' + rows + '</div>';
  }

  function searchCardAvatar(item, size) {
    var src = item.avatar_src || '';
    var initials = String(item.initials || item.title || 'AD').slice(0, 2).toUpperCase();
    if (src) {
      return '<img class="avatar avatar-photo search-card-avatar" src="' + escapeHtml(src) + '" alt="" width="' + size + '" height="' + size + '">';
    }
    var bg = ((initials.charCodeAt(0) || 65) * 7) % 2 === 0 ? '#15212f' : '#D85D3F';
    return '<span class="avatar search-card-avatar" style="width:' + size + 'px;height:' + size + 'px;min-width:' + size + 'px;border-radius:50%;background:' + bg + ';color:#FFF;display:flex;align-items:center;justify-content:center;font-family:\'Space Grotesk\',monospace;font-size:13px;">' + escapeHtml(initials) + '</span>';
  }

  function renderCards(items) {
    if (!items || !items.length) {
      return '<div class="search-empty"><strong>Aucun résultat pour cette recherche.</strong><span>Essayez un métier (illustration, traduction…) ou publiez une mission.</span></div>';
    }
    return items.map(function (item) {
      var isPerson = item.kind === 'prestataires';
      var media = item.thumb
        ? '<div class="search-card-media" style="background-image:url(\'' + escapeHtml(item.thumb) + '\')"></div>'
        : (item.kind === 'prestations'
          ? '<div class="search-card-media service-cover"' + (item.cover ? ' style="--service-cover-photo:url(\'' + escapeHtml(item.cover) + '\')"' : '') + ' role="img" aria-label="Visuel ' + escapeHtml(item.cat || 'Prestation') + '"><span class="service-cover-photo" aria-hidden="true"></span><span class="service-cover-kicker">acteursdulivre.fr</span><span class="service-cover-type">' + escapeHtml(item.cat || 'Prestation') + '</span></div>'
          : '');
      var title = '<div class="search-card-title">' + escapeHtml(item.title) + '</div>';
      var sub = '<div class="search-card-sub">' + escapeHtml(item.subtitle) + '</div>';
      var heading = isPerson
        ? '<div class="search-card-heading">' + searchCardAvatar(item, 40) + '<div class="search-card-who">' + title + sub + '</div></div>'
        : title + sub;
      return '<a class="search-card' + (item.is_busy ? ' is-busy' : '') + (isPerson ? ' search-card-person' : '') + '" href="' + escapeHtml(item.href) + '">' + media +
        '<div class="search-card-body">' +
          '<div class="search-card-kicker">' +
            (!isPerson && item.kind_label ? '<span>' + escapeHtml(item.kind_label) + '</span>' : '') +
            (item.cat && String(item.cat).toLowerCase() !== 'prestataire' ? '<span>' + escapeHtml(item.cat) + '</span>' : '') +
            (isPerson && item.availability_label
              ? '<span class="status-pill' + (item.is_busy ? ' is-busy' : ' is-available') + '">' + escapeHtml(item.availability_label) + '</span>'
              : '') +
          '</div>' +
          heading +
          '<div class="search-card-meta"><span>' + escapeHtml([item.meta || '', item.rating ? '★ ' + item.rating : ''].filter(Boolean).join(' · ')) + '</span>' +
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

  function syncHeaderVille(slug) {
    var hidden = document.querySelector('.site-header [data-header-ville]');
    if (hidden) hidden.value = slug || '';
  }

  function placeAtCity(city, slug) {
    city = String(city || '').trim();
    slug = String(slug || '').toLowerCase();
    if (!city) return '';
    if (slug === 'france' || slug === 'europe' || /^(france|europe)$/i.test(city)) return 'en ' + city;
    var m = city.match(/^Les\s+(.+)$/i);
    if (m) return 'aux ' + m[1];
    m = city.match(/^Le\s+(.+)$/i);
    if (m) return 'au ' + m[1];
    m = city.match(/^La\s+(.+)$/i);
    if (m) return 'à la ' + m[1];
    m = city.match(/^L['’](.+)$/i);
    if (m) return 'à l\'' + m[1];
    return 'à ' + city;
  }

  function fillCityField(root, name, nextSlug) {
    if (!root) return;
    var input = root.querySelector('[data-city-input]');
    var hidden = root.querySelector('[data-city-slug]');
    var insee = root.querySelector('[data-city-insee]');
    if (input) {
      input.value = name || '';
      if (name) input.dataset.cityLocked = name;
      else delete input.dataset.cityLocked;
    }
    if (hidden) hidden.value = nextSlug || '';
    if (insee && !nextSlug) insee.value = '';
  }

  function bindCityAutocomplete(root) {
    var input = root.querySelector('[data-city-input]');
    var slug = root.querySelector('[data-city-slug]');
    var insee = root.querySelector('[data-city-insee]');
    var panel = root.querySelector('[data-city-panel]');
    var api = root.getAttribute('data-city-api') || '/api/villes';
    var searchScope = root.getAttribute('data-city-scope') === 'search';
    if (!input || !panel) return;

    function emit() {
      root.dispatchEvent(new CustomEvent('city-change', { bubbles: true, detail: {
        slug: slug ? slug.value : '',
        name: input.value.trim()
      }}));
    }

    function applyCity(name, nextSlug, nextInsee, locked) {
      input.value = name || '';
      if (slug) slug.value = nextSlug || '';
      if (insee) insee.value = nextInsee || '';
      if (locked && input.value.trim()) input.dataset.cityLocked = input.value.trim();
      else delete input.dataset.cityLocked;
    }

    function setCity(item) {
      applyCity(
        item && item.name ? item.name : '',
        item && item.area_slug ? item.area_slug : (item && item.slug ? item.slug : ''),
        item && item.insee ? item.insee : '',
        true
      );
      panel.hidden = true;
      panel.innerHTML = '';
      input.setAttribute('aria-expanded', 'false');
      input.dispatchEvent(new Event('change', { bubbles: true }));
      emit();
    }

    function render(items) {
      if (!items || !items.length) {
        panel.innerHTML = '<div class="search-suggest-empty">Aucune ville pour cette saisie.</div>';
        panel.hidden = false;
        return;
      }
      panel.innerHTML = items.map(function (item) {
        var region = item.kind === 'region';
        return '<button type="button" role="option" aria-selected="false" class="city-ac-item' + (region ? ' is-region' : '') + '" data-city-pick>' +
          '<strong>' + escapeHtml(item.name || item.label || '') + '</strong>' +
          (item.hint ? '<em>' + escapeHtml(item.hint) + '</em>' : '') +
          '</button>';
      }).join('');
      panel.querySelectorAll('[data-city-pick]').forEach(function (btn, i) {
        btn.addEventListener('click', function () { setCity(items[i]); });
      });
      panel.hidden = false;
      input.setAttribute('aria-expanded', 'true');
    }

    function load(q) {
      var params = new URLSearchParams({ q: q, limit: '8' });
      if (searchScope) params.set('scope', 'search');
      fetchSearch(api, params, function (data) {
        render(data.results || []);
      });
    }

    var run = debounce(function () {
      var q = input.value.trim();
      if (slug && q === '') {
        slug.value = '';
        if (insee) insee.value = '';
        if (searchScope) {
          load('');
          emit();
          return;
        }
        panel.hidden = true;
        emit();
        return;
      }
      if (q.length < 2 && !searchScope) {
        panel.hidden = true;
        return;
      }
      if (slug && slug.value && input.dataset.cityLocked === q) {
        return;
      }
      if (slug) slug.value = '';
      if (insee) insee.value = '';
      load(q);
    }, 180);

    input.addEventListener('input', function () {
      delete input.dataset.cityLocked;
      run();
    });
    input.addEventListener('focus', function () {
      if (searchScope) {
        run();
        return;
      }
      if (panel.innerHTML) panel.hidden = false;
    });
    input.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        panel.hidden = true;
        input.setAttribute('aria-expanded', 'false');
      }
    });
    document.addEventListener('click', function (event) {
      if (!root.contains(event.target)) {
        panel.hidden = true;
        input.setAttribute('aria-expanded', 'false');
      }
    });
    if (slug && !slug.value.trim() && input.name === 'ville_label' && input.value.trim()) {
      input.value = '';
    }
    if (input.value.trim()) input.dataset.cityLocked = input.value.trim();
  }

  document.querySelectorAll('[data-city-ac]').forEach(bindCityAutocomplete);

  document.querySelectorAll('form.search, form.mk-search').forEach(function (form) {
    form.addEventListener('submit', function () {
      var hiddenVille = form.querySelector('[data-header-ville]');
      if (hiddenVille && !hiddenVille.value.trim()) {
        hiddenVille.disabled = true;
      }
      var q = form.querySelector('[name="q"]');
      var city = form.querySelector('[data-city-input]');
      var slug = form.querySelector('[data-city-slug]');
      if (!q || !city) return;
      if (q.value.trim() === '' && city.value.trim() !== '' && (!slug || slug.value.trim() === '')) {
        q.value = city.value.trim();
        city.value = '';
      }
    });
  });

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
    var standalone = searchPage.hasAttribute('data-search-standalone');
    var resultsKind = searchPage.getAttribute('data-results') || 'cards';
    var countUnit = searchPage.getAttribute('data-count-unit') || 'résultat';
    var BUDGET_DEFAULT_MIN = 200;
    var BUDGET_DEFAULT_MAX = 4000;
    var initialState = {};
    try { initialState = JSON.parse(searchPage.getAttribute('data-initial') || '{}'); } catch (e) {}
    var currentPage = Math.max(1, parseInt(initialState.page || new URLSearchParams(window.location.search).get('page') || '1', 10) || 1);

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

    function currentParams(pageOverride) {
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
      var ville = (data.get('ville') || '').toString().trim();
      if (ville) params.set('ville', ville);
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
      var limit = searchPage.getAttribute('data-search-limit');
      if (limit) params.set('limit', limit);
      var page = pageOverride !== undefined ? pageOverride : currentPage;
      if (page > 1) params.set('page', String(page));
      return params;
    }

    function displayParams(params) {
      var display = new URLSearchParams(params);
      if (!/\/recherche\/?$/.test(window.location.pathname)) {
        display.delete('type');
        display.delete('limit');
      }
      return display;
    }

    function pageHref(page) {
      var next = displayParams(currentParams(page)).toString();
      return window.location.pathname + (next ? '?' + next : '');
    }

    function pagerItems(page, pages) {
      if (pages <= 7) {
        var all = [];
        for (var i = 1; i <= pages; i++) all.push(i);
        return all;
      }
      var keep = [1, pages, page];
      if (page > 1) keep.push(page - 1);
      if (page < pages) keep.push(page + 1);
      keep = keep.filter(function (n, i, arr) { return n >= 1 && n <= pages && arr.indexOf(n) === i; });
      keep.sort(function (a, b) { return a - b; });
      var items = [];
      var prev = 0;
      keep.forEach(function (n) {
        if (prev && n > prev + 1) items.push(null);
        items.push(n);
        prev = n;
      });
      return items;
    }

    function renderPager(page, pages) {
      var nav = searchPage.querySelector('[data-search-pager]');
      if (!nav) return;
      if (pages <= 1) {
        nav.hidden = true;
        nav.innerHTML = '';
        return;
      }
      nav.hidden = false;
      var html = [];
      if (page > 1) {
        html.push('<a href="' + escapeHtml(pageHref(page - 1)) + '" data-search-page-num="' + (page - 1) + '" rel="prev">Précédent</a>');
      } else {
        html.push('<span class="is-off" aria-disabled="true">Précédent</span>');
      }
      pagerItems(page, pages).forEach(function (n) {
        if (n === null) html.push('<span class="is-gap" aria-hidden="true">…</span>');
        else if (n === page) html.push('<span aria-current="page">' + n + '</span>');
        else html.push('<a href="' + escapeHtml(pageHref(n)) + '" data-search-page-num="' + n + '">' + n + '</a>');
      });
      if (page < pages) {
        html.push('<a href="' + escapeHtml(pageHref(page + 1)) + '" data-search-page-num="' + (page + 1) + '" rel="next">Suivant</a>');
      } else {
        html.push('<span class="is-off" aria-disabled="true">Suivant</span>');
      }
      nav.innerHTML = html.join('');
    }

    function countLabel(n, page, pages) {
      var text = '· ' + n + ' ' + countUnit + (n > 1 ? 's' : '');
      if (pages > 1) text += ' · page ' + page + ' / ' + pages;
      return text;
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
      var cityInput = filters.querySelector('[data-city-input]');
      var citySlug = filters.querySelector('[data-city-slug]');
      if (citySlug && citySlug.value) {
        chips.push({ name: 'ville', value: citySlug.value, label: cityInput && cityInput.value ? cityInput.value : citySlug.value });
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
      if (name === 'ville') {
        fillCityField(filters.querySelector('[data-city-ac]'), '', '');
        syncHeaderVille('');
        return;
      }
      filters.querySelectorAll('input[name="' + name + '[]"]').forEach(function (input) {
        if (input.value === value) input.checked = false;
      });
    }

    function refresh() {
      var params = currentParams();
      fetchSearch(api, params, function (data) {
        if (window.AdlStats) window.AdlStats.action('filtre');
        results.innerHTML = resultsKind === 'rows' ? renderMissionRows(data.results || []) : renderCards(data.results || []);
        var n = data.count || 0;
        currentPage = Math.max(1, parseInt(data.page, 10) || 1);
        var pages = Math.max(1, parseInt(data.pages, 10) || 1);
        if (countEl) countEl.textContent = countLabel(n, currentPage, pages);
        renderPager(currentPage, pages);
        var label = data.query || data.cat || '';
        var citySlug = (filters.querySelector('[data-city-slug]') || {}).value || '';
        var cityName = citySlug ? ((filters.querySelector('[data-city-input]') || {}).value || '') : '';
        if (cityName && !data.query) {
          if (!label) {
            var hubType = data.type || searchPage.getAttribute('data-type') || '';
            if (hubType === 'prestations') label = 'Prestations à prix affiché';
            else if (hubType === 'prestataires') label = 'Prestataires du livre';
            else label = 'Tous les métiers du livre';
          }
          label = label + ' ' + placeAtCity(cityName, citySlug);
        }
        if (!label) {
          var hubTypeEmpty = data.type || searchPage.getAttribute('data-type') || '';
          if (hubTypeEmpty === 'prestations') label = 'Prestations à prix affiché';
          else if (hubTypeEmpty === 'prestataires') label = 'Prestataires du livre';
          else label = 'Tous les métiers du livre';
        }
        if (!standalone && titleEl && titleEl.childNodes[0]) titleEl.childNodes[0].textContent = label + ' ';
        var next = displayParams(currentParams()).toString();
        history.replaceState(null, '', window.location.pathname + (next ? '?' + next : ''));
        if (!standalone && headerInput) headerInput.value = data.query || '';
        syncActive();
        if (!standalone) {
          refreshShareBars(searchPage, {
            url: window.location.href,
            title: 'Recherche : ' + label,
            text: 'Prestataires des métiers du livre sur acteursdulivre.fr'
          });
        }
      });
    }

    var update = debounce(refresh, 160);

    function updateFromFilters() {
      currentPage = 1;
      update();
    }

    syncBudget();
    syncActive();
    filters.addEventListener('input', function (event) {
      syncBudget();
      if (event.target && event.target.closest && event.target.closest('[data-city-ac]')) {
        return;
      }
      updateFromFilters();
    });
    filters.addEventListener('change', function (event) {
      if (event.target && event.target.closest && event.target.closest('[data-city-ac]')) {
        return;
      }
      updateFromFilters();
    });
    filters.addEventListener('city-change', function (event) {
      syncHeaderVille((event.detail && event.detail.slug) || '');
      updateFromFilters();
    });
    if (headerInput && !standalone) {
      headerInput.addEventListener('input', function () {
        if (hiddenQ) hiddenQ.value = headerInput.value;
        updateFromFilters();
      });
    }
    searchPage.addEventListener('click', function (event) {
      var pageLink = event.target.closest('[data-search-page-num]');
      if (pageLink) {
        event.preventDefault();
        var nextPage = parseInt(pageLink.getAttribute('data-search-page-num'), 10) || 1;
        if (nextPage === currentPage) return;
        currentPage = nextPage;
        refresh();
        var head = searchPage.querySelector('.search-head');
        if (head && head.scrollIntoView) head.scrollIntoView({ behavior: 'smooth', block: 'start' });
        return;
      }
      var chip = event.target.closest('[data-clear-name]');
      if (chip) {
        clearFilter(chip.getAttribute('data-clear-name'), chip.getAttribute('data-clear-value') || '');
        syncBudget();
        syncActive();
        updateFromFilters();
        return;
      }
      var reset = event.target.closest('[data-search-reset]');
      if (!reset) return;
      event.preventDefault();
      filters.querySelectorAll('input[type="checkbox"]').forEach(function (input) { input.checked = false; });
      if (budgetMin) budgetMin.value = BUDGET_DEFAULT_MIN;
      if (budgetMax) budgetMax.value = BUDGET_DEFAULT_MAX;
      if (!standalone) {
        if (hiddenQ) hiddenQ.value = '';
        if (headerInput) headerInput.value = '';
        fillCityField(filters.querySelector('[data-city-ac]'), '', '');
        syncHeaderVille('');
      }
      syncBudget();
      syncActive();
      updateFromFilters();
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

  (function bindHiddenTabValidation() {
    var form = document.getElementById('vitrine-form');
    if (!form) return;
    var firstInvalid = false;
    form.addEventListener('invalid', function (e) {
      if (firstInvalid) return;
      firstInvalid = true;
      var field = e.target;
      if (!field || !field.closest) return;
      var panel = field.closest('[data-tab-panel]');
      if (!panel) return;
      var tab = panel.getAttribute('data-tab-panel');
      var nav = document.querySelector('[data-tabs]');
      if (nav) {
        nav.querySelectorAll('[data-tab]').forEach(function (btn) {
          btn.classList.toggle('is-on', btn.getAttribute('data-tab') === tab);
        });
      }
      form.querySelectorAll('[data-tab-panel]').forEach(function (p) {
        p.hidden = p.getAttribute('data-tab-panel') !== tab;
      });
    }, true);
    form.addEventListener('submit', function () {
      firstInvalid = false;
    });
    document.querySelectorAll('[type="submit"][form="vitrine-form"], #vitrine-form [type="submit"]').forEach(function (btn) {
      btn.addEventListener('click', function () { firstInvalid = false; });
    });
  })();

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
    setTimeout(function () { toast.remove(); }, 4600);
  }

  function copyText(value) {
    function fallback() {
      return new Promise(function (resolve, reject) {
        var input = document.createElement('textarea');
        input.value = value;
        input.setAttribute('readonly', '');
        input.style.position = 'fixed';
        input.style.top = '0';
        input.style.left = '0';
        input.style.width = '1px';
        input.style.height = '1px';
        input.style.opacity = '0';
        document.body.appendChild(input);
        input.focus();
        input.select();
        input.setSelectionRange(0, value.length);
        var ok = false;
        try {
          ok = document.execCommand('copy');
        } catch (err) {
          input.remove();
          reject(err);
          return;
        }
        input.remove();
        if (ok) resolve();
        else reject(new Error('copy failed'));
      });
    }
    if (navigator.clipboard && navigator.clipboard.writeText) {
      return navigator.clipboard.writeText(value).catch(function () {
        return fallback();
      });
    }
    return fallback();
  }

  function sharePayload(root) {
    return {
      url: (root && root.getAttribute('data-url')) || window.location.href,
      title: (root && root.getAttribute('data-title')) || document.title,
      text: (root && root.getAttribute('data-text')) || ''
    };
  }

  function isPublicShareUrl(value) {
    try {
      var parsed = new URL(value, window.location.href);
      if (parsed.protocol !== 'http:' && parsed.protocol !== 'https:') return false;
      var host = parsed.hostname.toLowerCase();
      if (!host || host === 'localhost' || host === '127.0.0.1' || host === '::1') return false;
      if (host.endsWith('.test') || host.endsWith('.local') || host.endsWith('.localhost')) return false;
      if (/^\d{1,3}(\.\d{1,3}){3}$/.test(host)) return false;
      return true;
    } catch (err) {
      return false;
    }
  }

  function trackShare() {
    if (window.AdlStats) window.AdlStats.action('partage');
  }

  function copyShareLink(url, message) {
    copyText(url).then(function () {
      showToast(message);
    }).catch(function () {
      window.prompt('Copiez ce lien :', url);
    });
  }

  function nativeShare(data) {
    if (!navigator.share) return false;
    navigator.share({ title: data.title, text: data.text, url: data.url }).catch(function () {});
    trackShare();
    return true;
  }

  function refreshShareBars(scope, payload) {
    (scope || document).querySelectorAll('[data-share]').forEach(function (bar) {
      if (payload.url) bar.setAttribute('data-url', payload.url);
      if (payload.title) bar.setAttribute('data-title', payload.title);
      if (payload.text) bar.setAttribute('data-text', payload.text);
      var rawUrl = bar.getAttribute('data-url') || window.location.href;
      var url = encodeURIComponent(rawUrl);
      var title = encodeURIComponent(bar.getAttribute('data-title') || document.title);
      var text = bar.getAttribute('data-text') || '';
      var message = encodeURIComponent((bar.getAttribute('data-title') || document.title) + (text ? '\n' + text : '') + '\n' + rawUrl);
      bar.querySelectorAll('[data-share-network]').forEach(function (link) {
        var id = link.getAttribute('data-share-network');
        if (id === 'facebook') link.href = 'https://www.facebook.com/sharer/sharer.php?u=' + url;
        if (id === 'linkedin') link.href = 'https://www.linkedin.com/sharing/share-offsite/?url=' + url;
        if (id === 'x') link.href = 'https://twitter.com/intent/tweet?url=' + url + '&text=' + title;
        if (id === 'whatsapp') link.href = 'https://api.whatsapp.com/send?text=' + message;
        if (id === 'instagram' || id === 'copy') link.href = rawUrl;
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

    var shareRoot = event.target.closest('[data-share]');
    if (!shareRoot) return;
    var nativeBtn = event.target.closest('[data-share-native]');
    var networkBtn = event.target.closest('[data-share-network]');
    if (!nativeBtn && !networkBtn) return;

    var data = sharePayload(shareRoot);
    var network = networkBtn ? (networkBtn.getAttribute('data-share-network') || '') : 'native';

    if (nativeBtn) {
      event.preventDefault();
      if (!nativeShare(data)) {
        copyShareLink(data.url, 'Lien copié.');
        trackShare();
      }
      return;
    }

    if (network === 'copy' || network === 'instagram') {
      event.preventDefault();
      if (network === 'instagram' && nativeShare(data)) return;
      copyShareLink(data.url, network === 'instagram'
        ? 'Lien copié. Ouvrez Instagram et collez-le dans une story, un message ou la bio.'
        : 'Lien copié.');
      trackShare();
      return;
    }

    if ((network === 'facebook' || network === 'linkedin') && !isPublicShareUrl(data.url)) {
      event.preventDefault();
      copyShareLink(data.url, 'Lien copié. Facebook et LinkedIn ne prévisualisent pas une adresse locale — testez le partage sur acteursdulivre.fr.');
      trackShare();
      return;
    }

    trackShare();
  });

  var rateBox = document.querySelector('[data-rate-fields]');
  if (rateBox) {
    var bookstore = rateBox.getAttribute('data-bookstore-trade') || 'Librairie';
    var rightsTrades = (rateBox.getAttribute('data-rights-trades') || 'Illustration,Photographie,Iconographie').split(',');
    var rateMeta = {
      price: { label: 'Tarif', note: 'Précision tarifaire', ph: 'data-placeholder-price', inputmode: 'text' },
      percent: { label: 'Commission', note: 'Précision', ph: 'data-placeholder-percent', inputmode: 'decimal' },
      exploitation: { label: 'Exploitation', note: 'Précision', ph: 'data-placeholder-exploitation', inputmode: 'text' },
      cession: { label: 'Cession', note: 'Précision', ph: 'data-placeholder-cession', inputmode: 'text' }
    };
    function selectedTrades() {
      return Array.prototype.slice.call(document.querySelectorAll('[data-trades] input[type="checkbox"]:checked'))
        .map(function (el) { return el.value; });
    }
    function hasRightsTrade(trades) {
      return rightsTrades.some(function (trade) { return trades.indexOf(trade) !== -1; });
    }
    function currentKind() {
      var on = rateBox.querySelector('[data-rate-kind]:checked');
      return on ? on.value : 'price';
    }
    function applyRateHelp(trades) {
      var help = rateBox.querySelector('[data-rate-help]');
      if (!help) return;
      var isBookstore = trades.indexOf(bookstore) !== -1;
      var rights = hasRightsTrade(trades);
      var text = rateBox.getAttribute('data-help-default') || '';
      if (rights && !isBookstore) {
        text = trades.indexOf('Illustration') !== -1
          ? (rateBox.getAttribute('data-help-rights') || text)
          : (rateBox.getAttribute('data-help-photo') || text);
      } else if (isBookstore && !rights) {
        text = rateBox.getAttribute('data-help-bookstore') || text;
      } else if (isBookstore && rights) {
        text = rateBox.getAttribute('data-help-both') || text;
      }
      help.textContent = text;
    }
    function applyRateKind(kind, fromTrade) {
      var meta = rateMeta[kind] || rateMeta.price;
      var input = rateBox.querySelector('[data-rate-input]');
      var note = rateBox.querySelector('[data-rate-note]');
      var label = rateBox.querySelector('[data-rate-label]');
      var noteLabel = rateBox.querySelector('[data-rate-note-label]');
      var radios = rateBox.querySelectorAll('[data-rate-kind]');
      radios.forEach(function (radio) {
        radio.checked = radio.value === kind;
      });
      syncChips(rateBox);
      if (label) label.textContent = meta.label;
      if (noteLabel) noteLabel.textContent = meta.note;
      if (input) {
        input.placeholder = input.getAttribute(meta.ph) || '';
        input.setAttribute('inputmode', meta.inputmode);
        if (fromTrade && kind === 'percent' && /€|eur|\/\s*heure/i.test(input.value)) input.value = '';
      }
      if (note) {
        note.placeholder = note.getAttribute(meta.ph) || '';
      }
    }
    function syncRateUi(kind, fromTrade) {
      applyRateKind(kind, fromTrade);
      applyRateHelp(selectedTrades());
    }
    rateBox.querySelectorAll('[data-rate-kind]').forEach(function (radio) {
      radio.addEventListener('change', function () { syncRateUi(currentKind(), false); });
    });
    document.querySelectorAll('[data-trades] input[type="checkbox"]').forEach(function (box) {
      box.addEventListener('change', function () {
        var kind = currentKind();
        if (box.value === bookstore && box.checked) {
          kind = 'percent';
          syncRateUi(kind, true);
          return;
        }
        syncRateUi(kind, false);
      });
    });
    syncRateUi(currentKind(), false);
  }

  (function bindVitrineSpecialties() {
    var box = document.querySelector('[data-vitrine-specialties]');
    if (!box) return;
    var textTrades = (box.getAttribute('data-text-trades') || '').split(',').filter(Boolean);
    function selectedTrades() {
      return Array.prototype.slice.call(document.querySelectorAll('[data-trades] input[type="checkbox"]:checked'))
        .map(function (el) { return el.value; });
    }
    function sync() {
      var trades = selectedTrades();
      var hasText = trades.some(function (trade) { return textTrades.indexOf(trade) !== -1; });
      var hasOther = trades.some(function (trade) { return textTrades.indexOf(trade) === -1; });
      var help = box.querySelector('[data-specialty-help]');
      if (help) {
        var key = 'data-help-none';
        if (trades.length && hasText && hasOther) key = 'data-help-both';
        else if (trades.length && hasOther) key = 'data-help-other';
        else if (trades.length && hasText) key = 'data-help-text';
        help.textContent = box.getAttribute(key) || help.textContent;
      }
      box.querySelectorAll('[data-specialty-chip]').forEach(function (chip) {
        var forTrades = (chip.getAttribute('data-for-trades') || '').split(',').filter(Boolean);
        var input = chip.querySelector('input');
        var match = trades.some(function (trade) { return forTrades.indexOf(trade) !== -1; });
        if (!match && input) {
          input.checked = false;
          chip.classList.remove('is-on');
        }
        chip.hidden = !match;
      });
    }
    document.querySelectorAll('[data-trades] input[type="checkbox"]').forEach(function (input) {
      input.addEventListener('change', sync);
    });
    sync();
  })();

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
    var coverBrandWrap = coverForm.querySelector('[data-cover-brand]');
    var coverThumbs = coverForm.querySelector('[data-cover-thumbs]');
    var coverFile = coverForm.querySelector('[data-cover-file]');
    var coverMax = parseInt(coverFile && coverFile.getAttribute('data-max-files') || '5', 10) || 5;
    function setCoverLabel(label) {
      if (coverType) coverType.textContent = label || 'Prestation';
    }
    function setCoverPhoto(url) {
      if (!coverBrand || !url) return;
      coverBrand.style.setProperty('--service-cover-photo', "url('" + url + "')");
    }
    function existingCoverCount() {
      return coverThumbs ? coverThumbs.querySelectorAll('[data-keep-image]').length : 0;
    }
    function newCoverCount() {
      return coverFile && coverFile.files ? coverFile.files.length : 0;
    }
    function remainingCoverSlots() {
      return Math.max(0, coverMax - existingCoverCount() - newCoverCount());
    }
    function listToFileList(files) {
      if (typeof DataTransfer === 'undefined') return null;
      var transfer = new DataTransfer();
      files.forEach(function (file) { transfer.items.add(file); });
      return transfer.files;
    }
    function syncCoverPreview() {
      var hasAny = existingCoverCount() > 0 || newCoverCount() > 0;
      if (coverBrandWrap) coverBrandWrap.hidden = !!hasAny;
      if (coverThumbs) coverThumbs.hidden = !hasAny;
      if (coverFile) {
        var left = remainingCoverSlots();
        coverFile.setAttribute('data-remaining-files', String(left));
        coverFile.disabled = left < 1 && newCoverCount() === 0;
        var pick = coverFile.closest('[data-file-pick]');
        if (pick) pick.classList.toggle('is-full', left < 1 && newCoverCount() === 0);
      }
    }
    function refreshCoverFileName() {
      if (!coverFile) return;
      var pick = coverFile.closest('[data-file-pick]');
      var nameEl = pick && pick.querySelector('[data-file-name]');
      if (!nameEl) return;
      var files = coverFile.files;
      var n = files ? files.length : 0;
      if (n === 0) {
        nameEl.textContent = (pick && pick.getAttribute('data-file-empty')) || 'ou déposez-les ici';
        if (pick) pick.classList.remove('has-file');
        return;
      }
      nameEl.textContent = n === 1 ? files[0].name : (n + ' fichiers choisis');
      if (pick) pick.classList.add('has-file');
    }
    function capCoverFiles() {
      if (!coverFile || !coverFile.files) return;
      var left = Math.max(0, coverMax - existingCoverCount());
      if (coverFile.files.length <= left) {
        refreshCoverFileName();
        return;
      }
      var next = listToFileList(Array.prototype.slice.call(coverFile.files, 0, left));
      if (next) coverFile.files = next;
      refreshCoverFileName();
    }
    function renderCoverPreviews() {
      if (!coverThumbs || !coverFile) return;
      coverThumbs.querySelectorAll('[data-new-preview]').forEach(function (el) {
        el.remove();
      });
      var files = coverFile.files ? Array.prototype.slice.call(coverFile.files) : [];
      files.forEach(function (file, index) {
        var fig = document.createElement('figure');
        fig.className = 'service-gallery-thumb';
        fig.setAttribute('data-new-preview', '');
        var media = document.createElement('div');
        media.className = 'service-gallery-media';
        media.style.backgroundImage = 'url(\'' + URL.createObjectURL(file) + '\')';
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'service-gallery-remove';
        btn.setAttribute('aria-label', 'Retirer ce visuel');
        btn.textContent = '✕';
        btn.addEventListener('click', function () {
          var kept = [];
          Array.prototype.slice.call(coverFile.files).forEach(function (item, i) {
            if (i !== index) kept.push(item);
          });
          var next = listToFileList(kept);
          if (next) coverFile.files = next;
          coverFile.dispatchEvent(new Event('change', { bubbles: true }));
        });
        fig.appendChild(media);
        fig.appendChild(btn);
        coverThumbs.appendChild(fig);
      });
      syncCoverPreview();
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
        capCoverFiles();
        renderCoverPreviews();
      });
    }
    if (coverThumbs) {
      coverThumbs.addEventListener('click', function (event) {
        var btn = event.target.closest('[data-remove-keep]');
        if (!btn) return;
        var fig = btn.closest('[data-keep-image]');
        if (fig) fig.remove();
        capCoverFiles();
        renderCoverPreviews();
      });
    }
    syncCoverPreview();
  }

  (function bindTradeSpecialties() {
    var form = document.querySelector('[data-service-cover]');
    if (!form) return;
    var tradeSelect = form.querySelector('[data-cover-trade]');
    var specSelect = form.querySelector('[data-trade-specialty]');
    if (!tradeSelect || !specSelect) return;
    var byTrade = {};
    var helps = {};
    try { byTrade = JSON.parse(form.getAttribute('data-specialties-by-trade') || '{}'); } catch (e) {}
    try { helps = JSON.parse(form.getAttribute('data-specialty-helps') || '{}'); } catch (e) {}
    var initialTrade = tradeSelect.value;
    var keepSpecialty = specSelect.value;
    var keepLabel = '';
    var keepOpt = specSelect.options[specSelect.selectedIndex];
    if (keepSpecialty && keepOpt) keepLabel = keepOpt.textContent.trim();
    function fill() {
      var trade = tradeSelect.value;
      var options = byTrade[trade] || [];
      var current = specSelect.value;
      var html = '<option value="">Choisir une spécialité</option>';
      var seen = {};
      options.forEach(function (opt) {
        var value = opt && opt.v ? opt.v : opt;
        var label = opt && opt.l ? opt.l : value;
        seen[value] = true;
        html += '<option value="' + escapeHtml(value) + '"' + (value === current ? ' selected' : '') + '>' + escapeHtml(label) + '</option>';
      });
      if (trade === initialTrade && keepSpecialty && !seen[keepSpecialty]) {
        html += '<option value="' + escapeHtml(keepSpecialty) + '"' + (current === keepSpecialty || current === '' ? ' selected' : '') + '>' + escapeHtml(keepLabel || keepSpecialty) + '</option>';
      }
      specSelect.innerHTML = html;
      var help = form.querySelector('[data-specialty-help]');
      if (help) help.textContent = helps[trade] || helps[''] || help.textContent;
    }
    tradeSelect.addEventListener('change', fill);
    fill();
  })();

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

  document.querySelectorAll('textarea[data-count], input[data-count]').forEach(function (area) {
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

  function fileMatchesAccept(file, accept) {
    if (!accept) return true;
    var type = (file.type || '').toLowerCase();
    var name = (file.name || '').toLowerCase();
    return accept.split(',').some(function (token) {
      token = token.trim().toLowerCase();
      if (!token) return false;
      if (token.charAt(0) === '.') return name.slice(-token.length) === token;
      if (token.slice(-2) === '/*') return type.indexOf(token.slice(0, -1)) === 0;
      return type === token;
    });
  }

  function bindFilePick(pick) {
    if (!pick || pick.dataset.filePickBound) return;
    pick.dataset.filePickBound = '1';
    var input = pick.querySelector('[data-file-input], input[type="file"]');
    var name = pick.querySelector('[data-file-name], [data-dropzone-label]');
    if (!input || !name) return;
    var empty = name.textContent;
    pick.setAttribute('data-file-empty', empty);
    function setFiles(files) {
      if (!files || !files.length) {
        name.textContent = empty;
        pick.classList.remove('has-file');
        return;
      }
      name.textContent = files.length === 1 ? files[0].name : (files.length + ' fichiers choisis');
      pick.classList.add('has-file');
    }
    input.addEventListener('change', function () {
      setFiles(input.files);
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
      if (!files || !files[0] || typeof DataTransfer === 'undefined') return;
      var maxBytes = parseInt(input.getAttribute('data-max-bytes') || '0', 10);
      var incoming = Array.prototype.slice.call(files).filter(function (file) {
        if (maxBytes > 0 && file.size > maxBytes) return false;
        return fileMatchesAccept(file, input.getAttribute('accept') || '');
      });
      if (!incoming.length) return;
      var transfer = new DataTransfer();
      if (input.multiple) {
        var existing = input.files ? Array.prototype.slice.call(input.files) : [];
        var remaining = parseInt(input.getAttribute('data-remaining-files') || '', 10);
        if (isNaN(remaining)) {
          var cap = parseInt(input.getAttribute('data-max-files') || '0', 10);
          remaining = cap > 0 ? Math.max(0, cap - existing.length) : incoming.length;
        }
        incoming = incoming.slice(0, Math.max(0, remaining));
        existing.forEach(function (file) { transfer.items.add(file); });
        incoming.forEach(function (file) { transfer.items.add(file); });
      } else {
        transfer.items.add(incoming[0]);
      }
      if (!transfer.files.length) return;
      input.files = transfer.files;
      setFiles(input.files);
      input.dispatchEvent(new Event('change', { bubbles: true }));
    });
  }

  function bindFilePicks(root) {
    var scope = root || document;
    scope.querySelectorAll('[data-file-pick], [data-dropzone-zone]').forEach(bindFilePick);
    if (root && root.matches && root.matches('[data-file-pick], [data-dropzone-zone]')) bindFilePick(root);
  }

  bindFilePicks(document);

  var EDITOR_TAGS = {
    p: true, br: true, strong: true, b: true, em: true, i: true, u: true,
    ul: true, ol: true, li: true, blockquote: true, a: true
  };
  var EDITOR_DROP = {
    script: true, style: true, iframe: true, object: true, embed: true, svg: true, math: true,
    video: true, audio: true, canvas: true, template: true, noscript: true, link: true, meta: true, base: true,
    input: true, button: true, select: true, option: true, optgroup: true, textarea: true,
    label: true, fieldset: true, legend: true
  };

  function escapeEditorText(text) {
    return String(text)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function safeEditorHref(href) {
    href = String(href || '').trim();
    if (!href) return '';
    if (/^(javascript|data|vbscript):/i.test(href)) return '';
    if (/^https?:\/\//i.test(href) || /^mailto:[^\s]+$/i.test(href)) return href;
    if (href.charAt(0) === '/' && href.charAt(1) !== '/') return href;
    return '';
  }

  function serializeEditorNode(node) {
    if (node.nodeType === 3) return escapeEditorText(node.nodeValue || '');
    if (node.nodeType !== 1) return '';
    var tag = (node.tagName || '').toLowerCase();
    if (EDITOR_DROP[tag]) return '';
    var inner = '';
    Array.prototype.forEach.call(node.childNodes, function (child) {
      inner += serializeEditorNode(child);
    });
    if (tag === 'br') return '<br>';
    if (tag === 'a') {
      var href = safeEditorHref(node.getAttribute('href'));
      if (!href) return inner;
      var extra = ' rel="noopener noreferrer" target="_blank"';
      if (/^https?:\/\//i.test(href)) {
        return '<a href="' + escapeEditorText(href) + '"' + extra + '>' + inner + '</a>';
      }
      return '<a href="' + escapeEditorText(href) + '">' + inner + '</a>';
    }
    if (EDITOR_TAGS[tag]) return '<' + tag + '>' + inner + '</' + tag + '>';
    return inner;
  }

  function sanitizeEditorHtml(html) {
    if (!html) return '';
    var doc;
    try {
      doc = new DOMParser().parseFromString('<div id="adl-rt">' + html + '</div>', 'text/html');
    } catch (err) {
      return '';
    }
    var root = doc.getElementById('adl-rt');
    if (!root) return '';
    var out = '';
    Array.prototype.forEach.call(root.childNodes, function (child) {
      out += serializeEditorNode(child);
    });
    return out;
  }

  function toEditorHtml(raw) {
    if (!raw) return '';
    if (/<[a-z][\s\S]*>/i.test(raw)) return sanitizeEditorHtml(raw);
    return raw.split(/\n\s*\n/).map(function (block) {
      return '<p>' + escapeEditorText(block).replace(/\n/g, '<br>') + '</p>';
    }).join('');
  }

  function editorIsEmpty(editor) {
    return (editor.innerText || '').replace(/\u00a0/g, ' ').trim() === '';
  }

  function insertEditorHtml(html) {
    if (!html) return;
    document.execCommand('insertHTML', false, html);
  }

  function clipboardToEditorHtml(data) {
    if (!data) return '';
    var html = data.getData('text/html') || '';
    var text = data.getData('text/plain') || '';
    var clean = html ? sanitizeEditorHtml(html) : '';
    if (clean && clean.replace(/<[^>]+>/g, '').replace(/\s+/g, '').length) return clean;
    return toEditorHtml(text);
  }

  function initWysiwyg(wrap) {
    if (!wrap || wrap.classList.contains('is-ready')) return;
    var source = wrap.querySelector('.wysiwyg-source');
    var editor = wrap.querySelector('.wysiwyg-editor');
    var toolbar = wrap.querySelector('.wysiwyg-toolbar');
    if (!source || !editor || !toolbar) return;

    editor.innerHTML = toEditorHtml(source.value);
    editor.setAttribute('data-placeholder', source.getAttribute('placeholder') || '');
    toolbar.hidden = false;
    editor.hidden = false;
    source.removeAttribute('required');
    source.setAttribute('tabindex', '-1');
    source.setAttribute('aria-hidden', 'true');
    wrap.classList.add('is-ready');

    function sync() {
      source.value = editorIsEmpty(editor) ? '' : sanitizeEditorHtml(editor.innerHTML);
    }

    editor.addEventListener('input', sync);
    editor.addEventListener('blur', function () {
      if (!editorIsEmpty(editor)) {
        var clean = sanitizeEditorHtml(editor.innerHTML);
        if (clean !== editor.innerHTML) editor.innerHTML = clean;
      }
      sync();
    });
    editor.addEventListener('paste', function (event) {
      event.preventDefault();
      insertEditorHtml(clipboardToEditorHtml(event.clipboardData));
      sync();
    });
    editor.addEventListener('drop', function (event) {
      if (!event.dataTransfer) return;
      event.preventDefault();
      insertEditorHtml(clipboardToEditorHtml(event.dataTransfer));
      sync();
    });
    var form = wrap.closest('form');
    if (form) form.addEventListener('submit', sync);

    toolbar.addEventListener('mousedown', function (event) {
      event.preventDefault();
    });

    toolbar.addEventListener('click', function (event) {
      var btn = event.target.closest('[data-wysiwyg-cmd]');
      if (!btn) return;
      var cmd = btn.getAttribute('data-wysiwyg-cmd');
      var arg = btn.getAttribute('data-wysiwyg-value');
      editor.focus();
      if (cmd === 'createLink') {
        var current = window.getSelection() && window.getSelection().toString();
        var href = window.prompt('Adresse du lien', current && /^https?:\/\//i.test(current) ? current : 'https://');
        if (!href) return;
        href = String(href).trim();
        if (!/^https?:\/\//i.test(href) && !/^mailto:/i.test(href)) href = 'https://' + href.replace(/^\/+/, '');
        href = safeEditorHref(href);
        if (!href) return;
        document.execCommand('createLink', false, href);
      } else if (cmd === 'formatBlock') {
        var tag = (arg || 'p').replace(/[^a-z0-9]/gi, '') || 'p';
        document.execCommand('formatBlock', false, tag);
      } else {
        document.execCommand(cmd, false, null);
      }
      sync();
    });
  }

  document.querySelectorAll('[data-wysiwyg]').forEach(initWysiwyg);

  (function initNewsletterBuilder() {
    var form = document.querySelector('[data-nl-builder]');
    if (!form) return;
    var canvas = form.querySelector('[data-nl-canvas]') || form.querySelector('.admin-nl-mail-body');
    if (!canvas) return;
    var inspector = form.querySelector('[data-nl-inspector]');
    var hidden = form.querySelector('[data-nl-blocks]');
    var dataEl = document.getElementById('nl-builder-data');
    var data = { blocks: [], catalog: {}, uploadUrl: '', token: '' };
    try {
      data = JSON.parse(dataEl ? dataEl.textContent : '{}') || data;
    } catch (err) {}
    var blocks = Array.isArray(data.blocks) ? data.blocks : [];
    var catalog = data.catalog || {};
    var selectedId = '';
    var labels = {
      heading: 'Titre',
      text: 'Texte',
      image: 'Image',
      button: 'Bouton',
      divider: 'Séparateur',
      spacer: 'Espace',
      quote: 'Citation',
      cards: 'Liste'
    };

    function uid() {
      return 'b' + Math.random().toString(16).slice(2, 10);
    }

    function escape(value) {
      return escapeHtml(String(value == null ? '' : value));
    }

    function indexOf(id) {
      return blocks.findIndex(function (b) { return b.id === id; });
    }

    function findBlock(id) {
      return blocks[indexOf(id)] || null;
    }

    function defaultBlock(type) {
      var id = uid();
      if (type === 'heading') return { id: id, type: 'heading', text: '', level: 'h2' };
      if (type === 'text') return { id: id, type: 'text', html: '' };
      if (type === 'image') return { id: id, type: 'image', src: '', alt: '', href: '' };
      if (type === 'button') return { id: id, type: 'button', label: 'En savoir plus', href: '', align: 'left' };
      if (type === 'divider') return { id: id, type: 'divider' };
      if (type === 'spacer') return { id: id, type: 'spacer', height: 28 };
      if (type === 'quote') return { id: id, type: 'quote', text: '', cite: '' };
      return { id: id, type: 'cards', title: '', items: [{ title: '', meta: '', excerpt: '', href: '' }] };
    }

    function editable(name, value, ph, extraClass) {
      return '<div class="' + (extraClass || '') + '" contenteditable="true" data-f="' + name + '" data-ph="' + escape(ph || '') + '">' + escape(value || '') + '</div>';
    }

    function tools() {
      return '<div class="admin-nl-vblock-tools">' +
        '<button type="button" data-nl-drag draggable="true" aria-label="Déplacer" title="Glisser pour déplacer">⋮⋮</button>' +
        '<button type="button" data-nl-del aria-label="Supprimer" title="Supprimer">×</button>' +
        '</div>';
    }

    function slotHtml(at) {
      return '<div class="admin-nl-slot" data-nl-slot="' + at + '"><button type="button" data-nl-plus="' + at + '" aria-label="Insérer un bloc">+</button></div>';
    }

    function imageSrc(block) {
      return block._url || (/^https:\/\//i.test(block.src || '') ? block.src : '') || '';
    }

    function blockHtml(block) {
      var type = block.type;
      var on = block.id === selectedId ? ' is-on' : '';
      var body = '';
      if (type === 'heading') {
        body = editable('text', block.text, 'Écrire le titre', 'admin-nl-vhead')
          .replace('data-f="text"', 'data-f="text" data-level="' + escape(block.level || 'h2') + '"');
      } else if (type === 'text') {
        body = '<div class="admin-nl-vtext"><div class="wysiwyg" data-wysiwyg>' +
          '<div class="wysiwyg-toolbar" hidden>' +
          '<button type="button" data-wysiwyg-cmd="bold" aria-label="Gras"><strong>G</strong></button>' +
          '<button type="button" data-wysiwyg-cmd="italic" aria-label="Italique"><em>I</em></button>' +
          '<button type="button" data-wysiwyg-cmd="insertUnorderedList" aria-label="Liste">•</button>' +
          '<button type="button" data-wysiwyg-cmd="createLink" aria-label="Lien">Lien</button>' +
          '</div>' +
          '<textarea class="textarea wysiwyg-source" data-f="html" hidden>' + escape(block.html || '') + '</textarea>' +
          '<div class="wysiwyg-editor" contenteditable="true" role="textbox" aria-multiline="true" data-placeholder="Écrire le texte…" hidden></div>' +
          '</div></div>';
      } else if (type === 'image') {
        var src = imageSrc(block);
        body = '<div class="admin-nl-vimg" data-src="' + escape(block.src || '') + '">' +
          (src
            ? '<img src="' + escape(src) + '" alt="' + escape(block.alt || '') + '">'
            : '<div class="admin-nl-vimg-empty" data-nl-pick>Déposez une image<br>ou cliquez pour en choisir une</div>') +
          '<input type="file" accept="image/jpeg,image/png,image/webp" data-nl-upload hidden>' +
          '</div>';
      } else if (type === 'button') {
        body = '<div class="admin-nl-vbtn" data-align="' + escape(block.align || 'left') + '">' +
          '<div class="admin-nl-vbtn-cta" contenteditable="true" data-f="label" data-ph="En savoir plus">' + escape(block.label || '') + '</div></div>';
      } else if (type === 'divider') {
        body = '<hr class="admin-nl-vdiv">';
      } else if (type === 'spacer') {
        var h = parseInt(block.height, 10) || 28;
        body = '<div class="admin-nl-vspace" style="height:' + h + 'px"><div class="admin-nl-vspace-bar" style="height:' + h + 'px">' + h + ' px</div></div>';
      } else if (type === 'quote') {
        body = '<div class="admin-nl-vquote">' +
          editable('text', block.text, 'Votre citation') +
          editable('cite', block.cite, 'Auteur', 'admin-nl-vquote-cite') +
          '</div>';
      } else if (type === 'cards') {
        var items = (block.items && block.items.length) ? block.items : [{ title: '', meta: '', excerpt: '', href: '' }];
        body = editable('title', block.title, 'Titre de la liste', 'admin-nl-vcards-title');
        items.forEach(function (item) {
          body += '<div class="admin-nl-vcard" data-nl-card>' +
            editable('title', item.title, 'Titre du lien', 'admin-nl-vcard-name') +
            editable('meta', item.meta, 'Accroche', 'admin-nl-vcard-meta') +
            editable('excerpt', item.excerpt, 'Texte') +
            '<input class="admin-nl-vcard-href" data-f="href" value="' + escape(item.href || '') + '" placeholder="https://">' +
            '<button type="button" class="admin-nl-vcard-del" data-nl-card-del>Retirer</button>' +
            '</div>';
        });
      }
      return '<article class="admin-nl-vblock' + on + '" data-nl-block data-nl-type="' + escape(type) + '" data-nl-id="' + escape(block.id || uid()) + '">' +
        tools() + body + '</article>';
    }

    function persist() {
      var json = JSON.stringify(blocks);
      if (hidden) hidden.value = json;
      document.querySelectorAll('[data-nl-sync] [name="blocks"]').forEach(function (el) { el.value = json; });
      var subject = (form.querySelector('[name="subject"]') || {}).value || '';
      var preheader = (form.querySelector('[name="preheader"]') || {}).value || '';
      document.querySelectorAll('[data-nl-sync] [name="subject"]').forEach(function (el) { el.value = subject; });
      document.querySelectorAll('[data-nl-sync] [name="preheader"]').forEach(function (el) { el.value = preheader; });
    }

    function readDom() {
      canvas.querySelectorAll('[data-nl-block]').forEach(function (el) {
        var block = findBlock(el.getAttribute('data-nl-id'));
        if (!block) return;
        if (block.type === 'heading') {
          var h = el.querySelector('[data-f="text"]');
          block.text = h ? (h.textContent || '').replace(/\s+/g, ' ').trim() : '';
          block.level = (h && h.getAttribute('data-level')) || block.level || 'h2';
        } else if (block.type === 'text') {
          var wys = el.querySelector('[data-wysiwyg]');
          if (wys) {
            var ed = wys.querySelector('.wysiwyg-editor');
            var src = wys.querySelector('.wysiwyg-source');
            if (ed && src) src.value = editorIsEmpty(ed) ? '' : sanitizeEditorHtml(ed.innerHTML);
            block.html = src ? src.value : '';
          }
        } else if (block.type === 'image') {
          var box = el.querySelector('.admin-nl-vimg');
          if (box && box.getAttribute('data-src')) block.src = box.getAttribute('data-src') || block.src;
        } else if (block.type === 'button') {
          var lab = el.querySelector('[data-f="label"]');
          block.label = lab ? (lab.textContent || '').replace(/\s+/g, ' ').trim() : block.label;
        } else if (block.type === 'quote') {
          var q = el.querySelector('[data-f="text"]');
          var c = el.querySelector('[data-f="cite"]');
          block.text = q ? (q.textContent || '').trim() : '';
          block.cite = c ? (c.textContent || '').trim() : '';
        } else if (block.type === 'cards') {
          var title = el.querySelector('[data-f="title"]');
          block.title = title && !title.closest('[data-nl-card]') ? (title.textContent || '').trim() : block.title;
          block.items = [];
          el.querySelectorAll('[data-nl-card]').forEach(function (row) {
            block.items.push({
              title: ((row.querySelector('[data-f="title"]') || {}).textContent || '').trim(),
              meta: ((row.querySelector('[data-f="meta"]') || {}).textContent || '').trim(),
              excerpt: ((row.querySelector('[data-f="excerpt"]') || {}).textContent || '').trim(),
              href: (row.querySelector('[data-f="href"]') || {}).value || ''
            });
          });
        }
      });
      persist();
    }

    function inspectorHtml(block) {
      if (!block) return '<p class="admin-nl-inspector-empty">Sélectionnez un bloc dans la lettre pour le régler.</p>';
      var html = '<h2>' + escape(labels[block.type] || 'Bloc') + '</h2>';
      if (block.type === 'heading') {
        html += '<p class="field">Style</p><div class="admin-nl-inspector-row">' +
          '<button type="button" class="admin-nl-chip' + (block.level !== 'h3' ? ' is-on' : '') + '" data-nl-set="level" data-v="h2">Titre</button>' +
          '<button type="button" class="admin-nl-chip' + (block.level === 'h3' ? ' is-on' : '') + '" data-nl-set="level" data-v="h3">Sous-titre</button></div>';
      } else if (block.type === 'image') {
        html += '<label class="field">Image</label><input class="input" type="file" accept="image/jpeg,image/png,image/webp" data-nl-upload>' +
          '<label class="field">ou adresse</label><input class="input" data-nl-set="src" value="' + escape(imageSrc(block) || block.src || '') + '" placeholder="https://">' +
          '<label class="field">Texte alternatif</label><input class="input" data-nl-set="alt" value="' + escape(block.alt || '') + '">' +
          '<label class="field">Lien au clic</label><input class="input" data-nl-set="href" value="' + escape(block.href || '') + '" placeholder="https://">';
      } else if (block.type === 'button') {
        html += '<label class="field">Lien du bouton</label><input class="input" data-nl-set="href" value="' + escape(block.href || '') + '" placeholder="https://">' +
          '<p class="field">Alignement</p><div class="admin-nl-inspector-row">' +
          '<button type="button" class="admin-nl-chip' + (block.align !== 'center' ? ' is-on' : '') + '" data-nl-set="align" data-v="left">Gauche</button>' +
          '<button type="button" class="admin-nl-chip' + (block.align === 'center' ? ' is-on' : '') + '" data-nl-set="align" data-v="center">Centre</button></div>';
      } else if (block.type === 'spacer') {
        html += '<label class="field">Hauteur (' + (block.height || 28) + ' px)</label>' +
          '<input class="input" type="range" min="8" max="80" data-nl-set="height" value="' + (block.height || 28) + '">';
      } else if (block.type === 'cards') {
        html += '<button type="button" class="btn-navy" data-nl-card-add>Ajouter un lien</button>';
      } else if (block.type === 'text') {
        html += '<p class="admin-nl-inspector-empty">Écrivez directement dans la lettre. La barre au-dessus du texte sert au gras, à l’italique et aux liens.</p>';
      } else if (block.type === 'divider') {
        html += '<p class="admin-nl-inspector-empty">Une ligne de séparation. Supprimez-la avec × sur le bloc.</p>';
      } else {
        html += '<p class="admin-nl-inspector-empty">Écrivez directement dans la lettre.</p>';
      }
      return html;
    }

    function renderInspector() {
      if (!inspector) return;
      inspector.innerHTML = inspectorHtml(findBlock(selectedId));
    }

    function select(id) {
      selectedId = id || '';
      canvas.querySelectorAll('[data-nl-block]').forEach(function (el) {
        el.classList.toggle('is-on', el.getAttribute('data-nl-id') === selectedId);
      });
      renderInspector();
    }

    function paint(keepId) {
      if (keepId) selectedId = keepId;
      if (!blocks.length) {
        canvas.innerHTML = '<p class="admin-nl-empty">Cliquez un bloc à gauche, ou le + entre deux éléments, pour composer la lettre.</p>' + slotHtml(0);
      } else {
        var html = slotHtml(0);
        blocks.forEach(function (block, i) {
          html += blockHtml(block) + slotHtml(i + 1);
        });
        canvas.innerHTML = html;
        canvas.querySelectorAll('[data-wysiwyg]').forEach(function (wrap) {
          try { initWysiwyg(wrap); } catch (err) {}
        });
      }
      persist();
      renderInspector();
      if (selectedId) {
        var on = canvas.querySelector('[data-nl-id="' + selectedId + '"]');
        if (on && on.scrollIntoView) on.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
      }
    }

    function addBlock(type, at) {
      type = String(type || '').trim();
      if (!type) return;
      readDom();
      if (blocks.length >= 40) {
        window.alert('Cette lettre a déjà 40 blocs.');
        return;
      }
      var block = defaultBlock(type);
      var idx = typeof at === 'number' && !isNaN(at) ? at : (selectedId ? indexOf(selectedId) + 1 : blocks.length);
      if (idx < 0) idx = blocks.length;
      blocks.splice(idx, 0, block);
      paint(block.id);
    }

    function insertCatalog(key) {
      readDom();
      var items = catalog[key] || [];
      var titles = { missions: 'Dernières recherches', people: 'Nouveaux profils', articles: 'À lire' };
      if (!items.length) {
        window.alert('Rien à insérer pour le moment.');
        return;
      }
      if (blocks.length >= 40) return;
      var block = { id: uid(), type: 'cards', title: titles[key] || '', items: items.slice(0, 12) };
      var idx = selectedId ? indexOf(selectedId) + 1 : blocks.length;
      blocks.splice(idx, 0, block);
      paint(block.id);
    }

    function uploadFile(file, blockId) {
      if (!file || !data.uploadUrl) return;
      var body = new FormData();
      body.append('_token', data.token || '');
      body.append('image', file);
      fetch(data.uploadUrl, { method: 'POST', body: body, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (!res || !res.ok) throw new Error((res && res.error) || 'Téléversement impossible.');
          var block = findBlock(blockId);
          if (!block) return;
          block.src = res.src || '';
          block._url = res.url || '';
          paint(blockId);
        })
        .catch(function (err) {
          window.alert(err.message || 'Téléversement impossible.');
        });
    }

    var paletteDrag = false;
    form.addEventListener('click', function (event) {
      if (paletteDrag) return;
      var add = event.target.closest('[data-nl-add]');
      if (add && form.contains(add)) {
        event.preventDefault();
        addBlock(add.getAttribute('data-nl-add'));
        return;
      }
      var insert = event.target.closest('[data-nl-insert]');
      if (insert && form.contains(insert)) {
        event.preventDefault();
        insertCatalog(insert.getAttribute('data-nl-insert'));
      }
    });
    form.addEventListener('dragstart', function (event) {
      var tile = event.target.closest('[data-nl-add]');
      if (!tile || !form.contains(tile)) return;
      var type = tile.getAttribute('data-nl-add') || '';
      if (!type) return;
      paletteDrag = true;
      event.dataTransfer.effectAllowed = 'copy';
      event.dataTransfer.setData('text/plain', type);
      event.dataTransfer.setData('text/nl-type', type);
    });
    form.addEventListener('dragend', function () {
      window.setTimeout(function () { paletteDrag = false; }, 0);
    });

    canvas.addEventListener('click', function (event) {
      var plus = event.target.closest('[data-nl-plus]');
      if (plus) {
        addBlock('text', parseInt(plus.getAttribute('data-nl-plus'), 10) || 0);
        return;
      }
      var card = event.target.closest('[data-nl-block]');
      if (!card) {
        select('');
        return;
      }
      var id = card.getAttribute('data-nl-id');
      if (event.target.closest('[data-nl-del]')) {
        readDom();
        blocks = blocks.filter(function (b) { return b.id !== id; });
        paint('');
        return;
      }
      if (event.target.closest('[data-nl-card-del]')) {
        readDom();
        var block = findBlock(id);
        var row = event.target.closest('[data-nl-card]');
        var rows = Array.prototype.slice.call(card.querySelectorAll('[data-nl-card]'));
        if (block && row) {
          block.items.splice(rows.indexOf(row), 1);
          if (!block.items.length) block.items = [{ title: '', meta: '', excerpt: '', href: '' }];
        }
        paint(id);
        return;
      }
      if (event.target.closest('[data-nl-pick]')) {
        var input = card.querySelector('[data-nl-upload]');
        if (input) input.click();
      }
      select(id);
    });

    canvas.addEventListener('input', function () {
      readDom();
    });

    canvas.addEventListener('paste', function (event) {
      var ed = event.target.closest('[contenteditable]');
      if (!ed || ed.classList.contains('wysiwyg-editor')) return;
      event.preventDefault();
      var text = (event.clipboardData && event.clipboardData.getData('text/plain')) || '';
      document.execCommand('insertText', false, text);
    });

    canvas.addEventListener('change', function (event) {
      var file = event.target.closest('[data-nl-upload]');
      if (!file || !file.files || !file.files[0]) return;
      var card = file.closest('[data-nl-block]');
      if (card) uploadFile(file.files[0], card.getAttribute('data-nl-id'));
    });

    function transferHas(event, type) {
      var types = event.dataTransfer && event.dataTransfer.types;
      return !!(types && Array.prototype.indexOf.call(types, type) !== -1);
    }

    canvas.addEventListener('dragover', function (event) {
      if (transferHas(event, 'Files') || transferHas(event, 'text/nl-type') || transferHas(event, 'text/plain')) {
        event.preventDefault();
      }
    });
    canvas.addEventListener('drop', function (event) {
      var card = event.target.closest('[data-nl-block]');
      if (!card || card.getAttribute('data-nl-type') !== 'image') return;
      var file = event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files[0];
      if (!file) return;
      event.preventDefault();
      uploadFile(file, card.getAttribute('data-nl-id'));
    });

    var dragId = '';
    canvas.addEventListener('dragstart', function (event) {
      var handle = event.target.closest('[data-nl-drag]');
      if (!handle) return;
      var card = handle.closest('[data-nl-block]');
      if (!card) return;
      dragId = card.getAttribute('data-nl-id');
      card.classList.add('is-drag');
      event.dataTransfer.effectAllowed = 'move';
      event.dataTransfer.setData('text/plain', dragId);
    });
    canvas.addEventListener('dragend', function () {
      dragId = '';
      canvas.querySelectorAll('.is-drag').forEach(function (el) { el.classList.remove('is-drag'); });
      canvas.querySelectorAll('.is-over').forEach(function (el) { el.classList.remove('is-over'); });
    });
    canvas.addEventListener('dragover', function (event) {
      if (!dragId && !transferHas(event, 'text/nl-type')) return;
      var slot = event.target.closest('[data-nl-slot]') || event.target.closest('[data-nl-canvas]') || canvas;
      if (!slot) return;
      event.preventDefault();
      canvas.querySelectorAll('.is-over').forEach(function (el) { el.classList.remove('is-over'); });
      var mark = event.target.closest('[data-nl-slot]');
      if (mark) mark.classList.add('is-over');
    });
    canvas.addEventListener('drop', function (event) {
      var droppedType = '';
      try {
        droppedType = (event.dataTransfer && (event.dataTransfer.getData('text/nl-type') || '')) || '';
      } catch (err) {}
      if (droppedType && !dragId) {
        event.preventDefault();
        var slot = event.target.closest('[data-nl-slot]');
        var at = slot ? parseInt(slot.getAttribute('data-nl-slot'), 10) : NaN;
        addBlock(droppedType, isNaN(at) ? undefined : at);
        return;
      }
      var slot = event.target.closest('[data-nl-slot]');
      if (!slot || !dragId) return;
      event.preventDefault();
      readDom();
      var from = indexOf(dragId);
      var to = parseInt(slot.getAttribute('data-nl-slot'), 10) || 0;
      if (from < 0) return;
      if (to > from) to -= 1;
      var moved = blocks.splice(from, 1)[0];
      blocks.splice(to, 0, moved);
      paint(moved.id);
    });

    if (inspector) {
      inspector.addEventListener('click', function (event) {
        var addCard = event.target.closest('[data-nl-card-add]');
        if (addCard) {
          readDom();
          var block = findBlock(selectedId);
          if (!block || block.type !== 'cards') return;
          if (!block.items) block.items = [];
          if (block.items.length >= 12) return;
          block.items.push({ title: '', meta: '', excerpt: '', href: '' });
          paint(selectedId);
          return;
        }
        var chip = event.target.closest('[data-nl-set]');
        if (!chip || chip.tagName !== 'BUTTON') return;
        var block = findBlock(selectedId);
        if (!block) return;
        readDom();
        block[chip.getAttribute('data-nl-set')] = chip.getAttribute('data-v');
        paint(selectedId);
      });
      inspector.addEventListener('input', function (event) {
        var field = event.target.closest('[data-nl-set]');
        if (!field || field.tagName === 'BUTTON') return;
        var block = findBlock(selectedId);
        if (!block) return;
        var key = field.getAttribute('data-nl-set');
        var value = field.value;
        if (key === 'height') {
          block.height = parseInt(value, 10) || 28;
          var bar = canvas.querySelector('[data-nl-id="' + selectedId + '"] .admin-nl-vspace');
          if (bar) {
            bar.style.height = block.height + 'px';
            var label = bar.querySelector('.admin-nl-vspace-bar');
            if (label) {
              label.style.height = block.height + 'px';
              label.textContent = block.height + ' px';
            }
          }
          var cap = inspector.querySelector('.field');
          if (cap) cap.textContent = 'Hauteur (' + block.height + ' px)';
        } else if (key === 'src') {
          block.src = value;
          block._url = /^https:\/\//i.test(value) ? value : block._url;
          var img = canvas.querySelector('[data-nl-id="' + selectedId + '"] img');
          var box = canvas.querySelector('[data-nl-id="' + selectedId + '"] .admin-nl-vimg');
          if (img && /^https:\/\//i.test(value)) img.src = value;
          if (box) box.setAttribute('data-src', value);
        } else {
          block[key] = value;
        }
        persist();
      });
      inspector.addEventListener('change', function (event) {
        var file = event.target.closest('[data-nl-upload]');
        if (file && file.files && file.files[0] && selectedId) {
          uploadFile(file.files[0], selectedId);
        }
        var src = event.target.closest('[data-nl-set="src"]');
        if (src) paint(selectedId);
      });
    }

    form.addEventListener('submit', readDom);
    var subject = form.querySelector('[name="subject"]');
    var preheader = form.querySelector('[name="preheader"]');
    if (subject) subject.addEventListener('input', persist);
    if (preheader) preheader.addEventListener('input', persist);
    document.querySelectorAll('[data-nl-sync]').forEach(function (extra) {
      extra.addEventListener('submit', readDom);
    });

    try {
      paint(blocks[0] ? blocks[0].id : '');
    } catch (err) {
      canvas.innerHTML = '<p class="admin-nl-empty">Cliquez un bloc à gauche pour composer la lettre.</p>' + slotHtml(0);
    }
  })();

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

    function computedStartup(amount) {
      if (!depositInput) return 0;
      var kind = depositInput.getAttribute('data-startup-kind') || '';
      var value = parseInt(depositInput.getAttribute('data-startup-value') || '0', 10) || 0;
      if (kind === 'percent') return Math.round(amount * Math.min(100, Math.max(0, value)) / 100);
      if (kind === 'amount') return Math.min(amount, Math.max(0, value));
      return 0;
    }

    var lastAutoDeposit = depositInput ? parseEuros(depositInput.value) : 0;
    var ready = false;
    var customized = false;

    function sync() {
      var amount = parseEuros(amountInput.value);
      if (ready && !customized && depositInput && depositInput.getAttribute('data-startup-kind')) {
        var current = parseEuros(depositInput.value);
        if (current === lastAutoDeposit || current === 0) {
          var next = computedStartup(amount);
          depositInput.value = next > 0 ? String(next) : '';
          lastAutoDeposit = next;
        }
      }
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
    if (depositInput) depositInput.addEventListener('input', function () {
      var computed = computedStartup(parseEuros(amountInput.value));
      var current = parseEuros(depositInput.value);
      customized = current !== computed && current !== 0;
      lastAutoDeposit = computed;
      sync();
    });
    sync();
    ready = true;
  }
  initQuoteRecap();

  (function initStartupFields() {
    var box = document.querySelector('[data-startup-box]');
    if (!box) return;
    var toggle = box.querySelector('[data-startup-enabled]');
    var fields = box.querySelector('[data-startup-fields]');
    var label = box.querySelector('[data-startup-value-label]');
    var help = box.querySelector('[data-startup-value-help]');
    var input = box.querySelector('[data-startup-value]');
    function kind() {
      var on = box.querySelector('[data-startup-kind]:checked');
      return on && on.value === 'percent' ? 'percent' : 'amount';
    }
    function sync() {
      var on = !!(toggle && toggle.checked);
      if (fields) fields.hidden = !on;
      var percent = kind() === 'percent';
      if (label) label.textContent = percent ? 'Pourcentage' : 'Montant (€ TTC)';
      if (help) {
        help.textContent = percent
          ? 'Pourcentage du montant du devis, prérempli dans le suivi.'
          : 'Montant TTC facturé au démarrage, prérempli dans le suivi.';
      }
      if (input) input.placeholder = percent ? '30' : '180';
    }
    if (toggle) toggle.addEventListener('change', sync);
    box.querySelectorAll('[data-startup-kind]').forEach(function (radio) {
      radio.addEventListener('change', sync);
    });
    sync();
  })();

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

  var statsEndpoint = document.body.getAttribute('data-stats') || '';
  window.AdlStats = {
    action: function (name) {
      if (!statsEndpoint || !name) return;
      var body = new URLSearchParams();
      body.set('a', name);
      try {
        if (navigator.sendBeacon) {
          navigator.sendBeacon(statsEndpoint, body);
          return;
        }
      } catch (e) {}
      fetch(statsEndpoint, { method: 'POST', body: body, credentials: 'same-origin', keepalive: true }).catch(function () {});
    }
  };
  document.addEventListener('click', function (event) {
    var el = event.target.closest('[data-stat]');
    if (!el) return;
    window.AdlStats.action(el.getAttribute('data-stat') || '');
  });

  var liveBox = document.querySelector('[data-stats-live]');
  if (liveBox) {
    var liveUrl = liveBox.getAttribute('data-live-url');
    function fmtInt(n) {
      n = parseInt(n, 10) || 0;
      return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '\u00a0');
    }
    function fillList(sel, rows, empty) {
      var ul = liveBox.querySelector(sel);
      if (!ul) return;
      if (!rows || !rows.length) {
        ul.innerHTML = '<li class="admin-muted">' + empty + '</li>';
        return;
      }
      ul.innerHTML = rows.map(function (row) {
        return '<li><span>' + escapeHtml(row.label || '') + '</span><em>' + fmtInt(row.n) + '</em></li>';
      }).join('');
    }
    function refreshLive() {
      if (!liveUrl || document.hidden) return;
      fetch(liveUrl, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (data) {
          if (!data) return;
          var nowEl = liveBox.querySelector('[data-live-now]');
          var m15 = liveBox.querySelector('[data-live-15]');
          var m60 = liveBox.querySelector('[data-live-60]');
          var upd = liveBox.querySelector('[data-live-updated]');
          if (nowEl) nowEl.textContent = fmtInt(data.now);
          if (m15) m15.textContent = fmtInt(data.views_15);
          if (m60) m60.textContent = fmtInt(data.views_60);
          if (upd) upd.textContent = data.updated || '';
          var chart = liveBox.querySelector('[data-live-chart]');
          if (chart && data.minutes) {
            var max = 1;
            data.minutes.forEach(function (m) { max = Math.max(max, parseInt(m.n, 10) || 0); });
            chart.innerHTML = data.minutes.map(function (m) {
              var h = Math.max(4, Math.round(72 * (parseInt(m.n, 10) || 0) / max));
              return '<span title="' + escapeHtml((m.t || '') + ' · ' + (m.n || 0)) + '" style="height:' + h + 'px"></span>';
            }).join('');
          }
          fillList('[data-live-pages]', data.pages, 'Aucune visite récente.');
          fillList('[data-live-profiles]', data.profiles, 'Aucun profil consulté.');
          fillList('[data-live-searches]', data.searches, 'Aucune recherche récente.');
          fillList('[data-live-actions]', data.actions, 'Aucune action récente.');
        })
        .catch(function () {});
    }
    setInterval(refreshLive, 12000);
  }

  document.querySelectorAll('[data-forum-compose]').forEach(function (form) {
    var ta = form.querySelector('textarea[name="body"]');
    var editor = form.querySelector('.wysiwyg-editor');
    var count = form.querySelector('[data-draft-count]');
    var parent = form.querySelector('[data-parent-id]');
    var hint = form.querySelector('[data-cite-hint]');
    var errorBox = form.querySelector('[data-compose-error]');
    var blockBox = form.querySelector('[data-compose-block]');
    var noAi = form.querySelector('[name="no_ai"]');
    var engage = noAi && noAi.closest('.forum-engage');
    var category = form.querySelector('[name="category_id"]');
    var title = form.querySelector('[name="title"]');
    var minChars = parseInt(form.getAttribute('data-min-chars'), 10) || 80;
    var attempted = false;
    form.setAttribute('novalidate', '');
    function plainLen() {
      if (editor && wrapReady(editor)) {
        return (editor.innerText || '').replace(/\u00a0/g, ' ').trim().length;
      }
      return ((ta && ta.value) || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().length;
    }
    function wrapReady(el) {
      var wrap = el.closest('[data-wysiwyg]');
      return !!(wrap && wrap.classList.contains('is-ready') && !el.hidden);
    }
    function syncBody() {
      if (editor && wrapReady(editor) && ta) {
        ta.value = editorIsEmpty(editor) ? '' : sanitizeEditorHtml(editor.innerHTML);
      }
    }
    function blockers() {
      var items = [];
      if (category && !String(category.value || '').trim()) {
        items.push({ field: 'category', message: 'Choisissez une rubrique.' });
      }
      if (title) {
        var titleLen = String(title.value || '').trim().length;
        if (titleLen < 8) {
          items.push({
            field: 'title',
            message: titleLen ? 'Le titre doit faire au moins 8 caractères.' : 'Indiquez un titre.'
          });
        }
      }
      var n = plainLen();
      var missing = Math.max(0, minChars - n);
      if (missing > 0) {
        items.push({
          field: 'body',
          message: n
            ? 'Encore ' + missing + ' caractère' + (missing > 1 ? 's' : '') + ' (minimum ' + minChars + ').'
            : 'Écrivez un message d’au moins ' + minChars + ' caractères.'
        });
      }
      if (noAi && !noAi.checked) {
        items.push({ field: 'no_ai', message: 'Cochez la confirmation « écrite de votre main, sans IA ».' });
      }
      return items;
    }
    function focusField(field) {
      if (field === 'category' && category) category.focus();
      else if (field === 'title' && title) title.focus();
      else if (field === 'no_ai' && noAi) noAi.focus();
      else if (editor && wrapReady(editor)) editor.focus();
      else if (ta) ta.focus();
    }
    function renderBlock(items) {
      var list = items || [];
      form.classList.toggle('is-invalid', attempted && list.length > 0);
      form.classList.toggle('is-short', list.some(function (item) { return item.field === 'body'; }));
      if (engage) engage.classList.toggle('is-warn', attempted && list.some(function (item) { return item.field === 'no_ai'; }));
      if (blockBox) {
        if (attempted && list.length) {
          blockBox.hidden = false;
          blockBox.textContent = list.length === 1
            ? 'Publication bloquée : ' + list[0].message
            : 'Publication bloquée : ' + list.map(function (item) { return item.message.replace(/\.$/, ''); }).join(' · ') + '.';
        } else {
          blockBox.hidden = true;
          blockBox.textContent = '';
        }
      }
      if (count) {
        var n = plainLen();
        var missing = Math.max(0, minChars - n);
        count.classList.toggle('is-error', missing > 0);
        count.classList.toggle('is-ready', missing === 0);
        if (n === 0) count.textContent = 'Minimum ' + minChars + ' caractères pour publier';
        else if (missing > 0) count.textContent = 'Encore ' + missing + ' caractère' + (missing > 1 ? 's' : '') + ' pour pouvoir publier';
        else count.textContent = n + ' caractères — vous pouvez publier';
      }
    }
    function refresh() {
      syncBody();
      renderBlock(blockers());
    }
    if (ta) ta.addEventListener('input', refresh);
    if (editor) {
      editor.addEventListener('input', refresh);
      editor.addEventListener('blur', refresh);
    }
    if (noAi) noAi.addEventListener('change', refresh);
    if (category) category.addEventListener('change', refresh);
    if (title) title.addEventListener('input', refresh);
    form.querySelectorAll('[type="submit"]').forEach(function (btn) {
      btn.addEventListener('pointerdown', syncBody);
      btn.addEventListener('click', syncBody);
    });
    form.addEventListener('submit', function (event) {
      attempted = true;
      var items = blockers();
      if (!items.length) return;
      event.preventDefault();
      renderBlock(items);
      focusField(items[0].field);
    });
    refresh();
    document.querySelectorAll('[data-cite]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        if (!parent) return;
        parent.value = btn.getAttribute('data-cite') || '';
        if (hint) {
          var name = btn.getAttribute('data-cite-name') || '';
          hint.hidden = false;
          hint.textContent = name ? ('En réponse à ' + name) : 'Réponse citée';
        }
        focusField('body');
      });
    });
  });

  document.querySelectorAll('[data-forum-similar]').forEach(function (root) {
    var api = root.getAttribute('data-api');
    var titleInput = root.querySelector('[data-similar-title]');
    var categorySelect = root.querySelector('[data-similar-category]');
    var idle = root.querySelector('[data-similar-idle]');
    var panel = root.querySelector('[data-similar-panel]');
    var empty = root.querySelector('[data-similar-empty]');
    var list = root.querySelector('[data-similar-list]');
    if (!api || !titleInput || !list) return;

    var seq = 0;

    function showState(state) {
      if (idle) idle.hidden = state !== 'idle';
      if (panel) panel.hidden = state !== 'results';
      if (empty) empty.hidden = state !== 'empty';
    }

    function renderItems(items) {
      list.innerHTML = items.map(function (item) {
        var meta = [item.subtitle || item.kind_label || '', item.meta || ''].filter(Boolean).join(' · ');
        return '<a href="' + escapeHtml(item.href || '#') + '" target="_blank" rel="noopener">' +
          '<div class="forum-aside-link">' + escapeHtml(item.title || '') + '</div>' +
          (meta ? '<div class="forum-aside-meta">' + escapeHtml(meta) + '</div>' : '') +
          '</a>';
      }).join('');
    }

    var run = debounce(function () {
      var q = (titleInput.value || '').trim();
      if (q.length < 4) {
        list.innerHTML = '';
        showState('idle');
        return;
      }
      var mySeq = ++seq;
      var params = new URLSearchParams({ q: q, limit: '6' });
      if (categorySelect && categorySelect.value) {
        params.set('category_id', categorySelect.value);
      }
      fetchSearch(api, params, function (data) {
        if (mySeq !== seq) return;
        var items = data.suggestions || data.results || [];
        if (!items.length) {
          list.innerHTML = '';
          showState('empty');
          return;
        }
        renderItems(items);
        showState('results');
      });
    }, 220);

    titleInput.addEventListener('input', run);
    if (categorySelect) categorySelect.addEventListener('change', run);
    if ((titleInput.value || '').trim().length >= 4) run();
  });
})();
