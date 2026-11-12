/*
 * Panier : quantités, suppression, vidage (requêtes JSON vers cart.update / cart.remove / cart.clear).
 * Le serveur reste seule source de vérité : le total affiché est celui renvoyé (cart_total) ; le sous-total
 * d'une ligne n'est qu'un affichage (prix unitaire x quantité) recalculé au rechargement de la page.
 * Messages dans une zone role="status" (lecteurs d'écran), pas de toast ni d'animation.
 */
(function () {
  'use strict';

  var root = document.getElementById('cart-root');
  if (!root) return;

  var MAX_QTY = 10; // même plafond que la validation serveur (cart.update : max:10)
  var csrf = document.querySelector('meta[name="csrf-token"]');
  var statusBox = document.getElementById('cart-status');
  var busy = false;

  function fcfa(n) {
    if (Math.round(n) === 0) return 'Gratuit';
    // Séparateur de milliers : espace insécable, comme CurrencyHelper::formatFCFA (« 10 000 FCFA »)
    return String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' FCFA';
  }

  function say(message, kind) {
    if (!statusBox) return;
    statusBox.className = 'cart-status alert alert-' + (kind === 'error' ? 'danger' : 'success');
    statusBox.textContent = message;
  }

  function call(url, method, payload) {
    var headers = { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf ? csrf.getAttribute('content') : '' };
    var options = { method: method, headers: headers };
    if (payload) {
      headers['Content-Type'] = 'application/json';
      options.body = JSON.stringify(payload);
    }
    return fetch(url, options).then(function (response) {
      return response.json().catch(function () { return {}; }).then(function (data) {
        if (!response.ok || !data.success) throw new Error(data.message || 'Une erreur est survenue. Réessayez.');
        return data;
      });
    });
  }

  function setBusy(on) {
    busy = on;
    root.setAttribute('aria-busy', on ? 'true' : 'false');
  }

  function refreshTotals(data) {
    var total = document.getElementById('cart-total');
    if (total && data.cart_total !== undefined) total.textContent = fcfa(data.cart_total);
    document.querySelectorAll('[data-cart-count]').forEach(function (el) {
      if (data.cart_count !== undefined) el.textContent = data.cart_count;
    });
  }

  function refreshLine(line, qty) {
    var input = line.querySelector('[data-qty-input]');
    input.value = qty;
    input.dataset.last = qty;
    line.querySelector('[data-qty-step="-1"]').disabled = qty <= 1;
    line.querySelector('[data-line-total]').textContent = fcfa(parseInt(line.dataset.unitPrice, 10) * qty);
  }

  function updateQty(line, qty) {
    var input = line.querySelector('[data-qty-input]');
    var previous = parseInt(input.dataset.last || input.defaultValue, 10) || 1;
    if (busy) { input.value = previous; return; }
    if (!(qty >= 1) || qty > MAX_QTY) {
      input.value = previous;
      say('La quantité doit être comprise entre 1 et ' + MAX_QTY + '.', 'error');
      return;
    }
    if (qty === previous) { input.value = previous; return; }

    setBusy(true);
    line.classList.add('is-busy');
    call(root.dataset.updateUrl, 'PATCH', { cart_key: line.dataset.cartKey, quantity: qty })
      .then(function (data) { refreshLine(line, qty); refreshTotals(data); say('Quantité mise à jour.', 'success'); })
      .catch(function (error) { input.value = previous; say(error.message, 'error'); })
      .then(function () { line.classList.remove('is-busy'); setBusy(false); });
  }

  root.addEventListener('click', function (event) {
    var line = event.target.closest('.cart-line');
    var step = event.target.closest('[data-qty-step]');
    var remove = event.target.closest('[data-remove]');

    if (step && line) {
      var input = line.querySelector('[data-qty-input]');
      updateQty(line, (parseInt(input.value, 10) || 1) + parseInt(step.dataset.qtyStep, 10));
    } else if (remove && line) {
      if (busy || !window.confirm('Retirer ' + line.dataset.name + ' du panier ?')) return;
      setBusy(true);
      call(root.dataset.removeUrl, 'DELETE', { cart_key: line.dataset.cartKey })
        .then(function (data) {
          line.remove();
          refreshTotals(data);
          if (!data.cart_count) { window.location.reload(); return; }
          say('Article retiré du panier.', 'success');
        })
        .catch(function (error) { say(error.message, 'error'); })
        .then(function () { setBusy(false); });
    }
  });

  root.addEventListener('change', function (event) {
    var input = event.target.closest('[data-qty-input]');
    if (input) updateQty(input.closest('.cart-line'), parseInt(input.value, 10));
  });

  var clear = document.getElementById('cart-clear');
  if (clear) {
    clear.addEventListener('click', function () {
      if (busy || !window.confirm('Vider complètement votre panier ?')) return;
      setBusy(true);
      call(root.dataset.clearUrl, 'DELETE')
        .then(function () { window.location.reload(); })
        .catch(function (error) { say(error.message, 'error'); setBusy(false); });
    });
  }

  document.querySelectorAll('[data-qty-input]').forEach(function (input) { input.dataset.last = input.value; });
})();
