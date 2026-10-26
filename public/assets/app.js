// Confirm dialogs for destructive forms
document.querySelectorAll('form[data-confirm]').forEach(function (form) {
  form.addEventListener('submit', function (e) {
    if (!window.confirm(form.getAttribute('data-confirm'))) e.preventDefault();
  });
});

// Close the menu when tapping outside it
document.addEventListener('click', function (e) {
  document.querySelectorAll('details.menu[open]').forEach(function (d) {
    if (!d.contains(e.target)) d.removeAttribute('open');
  });
});

// Disable submit buttons after the first tap (avoids double orders on slow mobile networks)
document.querySelectorAll('form').forEach(function (form) {
  form.addEventListener('submit', function (e) {
    if (e.defaultPrevented) return;
    var btn = form.querySelector('button[type="submit"]');
    if (btn) setTimeout(function () { btn.disabled = true; }, 0);
  });
});

// Real QR codes: admin production pages only (parents never receive the link)
document.querySelectorAll('[data-qr]').forEach(function (el) {
  if (!window.qrcode) return;
  var qr = window.qrcode(0, 'M');
  qr.addData(el.getAttribute('data-qr'));
  qr.make();
  el.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 0, scalable: true });
});
document.querySelectorAll('[data-print]').forEach(function (btn) {
  btn.addEventListener('click', function () { window.print(); });
});
document.querySelectorAll('[data-copy]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var text = btn.getAttribute('data-copy');
    if (navigator.clipboard) navigator.clipboard.writeText(text).then(function () { btn.textContent = btn.getAttribute('data-done'); });
  });
});
