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

  function renderSuggest(items) {
    if (!items || !items.length) {
      return '<div class="search-suggest-empty">Aucun résultat pour l’instant. Essayez un métier ou un nom.</div>';
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
          ? '<div class="search-card-media service-cover" role="img" aria-label="Visuel ' + escapeHtml(item.cat || 'Prestation') + '"><span class="service-cover-kicker">acteursdulivre.fr</span><span class="service-cover-type">' + escapeHtml(item.cat || 'Prestation') + '</span></div>'
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

    var run = debounce(function () {
      var q = input.value.trim();
      if (q.length < 2) {
        panel.hidden = true;
        panel.innerHTML = '';
        return;
      }
      var params = new URLSearchParams({ q: q, limit: '8' });
      fetchSearch(api, params, function (data) {
        panel.innerHTML = renderSuggest(data.suggestions || data.results || []);
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

    function currentParams() {
      var data = new FormData(filters);
      var params = new URLSearchParams();
      ['q', 'type', 'cat', 'dispo'].forEach(function (key) {
        var value = (data.get(key) || '').toString().trim();
        if (value) params.set(key, value);
      });
      return params;
    }

    var update = debounce(function () {
      var params = currentParams();
      fetchSearch(api, params, function (data) {
        results.innerHTML = renderCards(data.results || []);
        var n = data.count || 0;
        if (countEl) countEl.textContent = '· ' + n + ' résultat' + (n > 1 ? 's' : '');
        if (titleEl) {
          var label = data.query || data.cat || 'Tous les métiers du livre';
          titleEl.childNodes[0].textContent = label + ' ';
        }
        var next = params.toString();
        history.replaceState(null, '', window.location.pathname + (next ? '?' + next : ''));
        if (headerInput) headerInput.value = data.query || '';
        syncChips(filters);
        refreshShareBars(searchPage, {
          url: window.location.href,
          title: 'Recherche : ' + (data.query || data.cat || 'Tous les métiers du livre'),
          text: 'Prestataires des métiers du livre sur acteursdulivre.fr'
        });
      });
    }, 160);

    filters.addEventListener('input', update);
    filters.addEventListener('change', update);
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
      var index = list.querySelectorAll('[data-repeat-row]').length;
      var html = tpl.innerHTML.replace(/__i__/g, String(index));
      list.insertAdjacentHTML('beforeend', html);
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

  var coverForm = document.querySelector('[data-service-cover]');
  if (coverForm) {
    var coverType = coverForm.querySelector('.service-cover-type');
    var coverBrand = coverForm.querySelector('.service-cover');
    var coverPhoto = coverForm.querySelector('[data-cover-photo]');
    var coverFile = coverForm.querySelector('[data-cover-file]');
    function setCoverLabel(label) {
      if (coverType) coverType.textContent = label || 'Prestation';
    }
    coverForm.querySelectorAll('input[name="category_name"]').forEach(function (radio) {
      radio.addEventListener('change', function () {
        setCoverLabel(radio.value);
      });
    });
    var coverSelect = coverForm.querySelector('[data-cover-trade]');
    if (coverSelect) {
      coverSelect.addEventListener('change', function () {
        var option = coverSelect.options[coverSelect.selectedIndex];
        setCoverLabel(option ? option.textContent.trim() : coverSelect.value);
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
})();
