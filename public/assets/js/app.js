(function () {
  document.querySelectorAll('[data-go]').forEach(function (el) {
    el.addEventListener('click', function (e) {
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
})();
