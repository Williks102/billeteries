// Formulaire « Envoyer un email de test » (page Admin > Emails).
(function () {
  var form = document.getElementById('test-email-form');
  if (!form) return;
  var out = document.getElementById('test-email-result');
  var button = form.querySelector('button[type=submit]');
  var input = document.getElementById('email');

  function show(kind, text) {
    out.className = 'mt-3 ' + kind;
    out.textContent = text;
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    show('text-muted', 'Envoi en cours…');
    button.disabled = true;
    input.removeAttribute('aria-invalid');
    fetch(form.action, {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name=_token]').value },
      body: new FormData(form)
    }).then(function (r) {
      return r.json().then(function (d) { return { ok: r.ok, status: r.status, d: d }; });
    }).then(function (res) {
      if (res.ok) {
        show('text-success', res.d.message || 'Terminé.');
      } else {
        if (res.status === 422) input.setAttribute('aria-invalid', 'true');
        var msg = res.d.message || 'Erreur.';
        if (res.d.errors) { msg = Object.values(res.d.errors)[0][0]; }
        show('text-danger', msg);
      }
    }).catch(function () {
      show('text-danger', 'Connexion impossible. Réessayez.');
    }).then(function () { button.disabled = false; });
  });
})();
