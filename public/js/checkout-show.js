document.addEventListener('DOMContentLoaded', function () {
  const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
  const methodInfos = document.querySelectorAll('.manual-method-info');
  const payButton = document.getElementById('pay-button');
  const payButtonText = document.getElementById('pay-button-text');
  const form = document.getElementById('checkout-form');

  if (!paymentMethods.length || !payButton || !payButtonText || !form) return;

  function togglePaymentOptions() {
    const checked = document.querySelector('input[name="payment_method"]:checked');
    const selectedMethod = checked ? checked.value : null;

    methodInfos.forEach((info) => {
      info.style.display = info.dataset.method === selectedMethod ? 'block' : 'none';
    });
    payButtonText.textContent = 'Valider la commande';
  }

  paymentMethods.forEach((method) => {
    method.addEventListener('change', togglePaymentOptions);
  });

  togglePaymentOptions();

  form.addEventListener('submit', function (e) {
    const termsAccepted = document.getElementById('terms_accepted')?.checked;
    if (!termsAccepted) {
      e.preventDefault();
      alert('Veuillez accepter les conditions générales');
      return;
    }

    payButton.disabled = true;
    payButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Traitement en cours...';
  });
});
