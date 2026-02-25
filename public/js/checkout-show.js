document.addEventListener('DOMContentLoaded', function () {
  const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
  const paiementproChannels = document.getElementById('paiementpro-channels');
  const bankTransferInfo = document.getElementById('bank-transfer-info');
  const payButton = document.getElementById('pay-button');
  const payButtonText = document.getElementById('pay-button-text');
  const form = document.getElementById('checkout-form');

  if (!paymentMethods.length || !paiementproChannels || !bankTransferInfo || !payButton || !payButtonText || !form) return;

  function togglePaymentOptions() {
    const checked = document.querySelector('input[name="payment_method"]:checked');
    const selectedMethod = checked ? checked.value : null;

    if (selectedMethod === 'paiementpro') {
      paiementproChannels.style.display = 'block';
      bankTransferInfo.style.display = 'none';
      payButtonText.textContent = 'Procéder au paiement';
    } else if (selectedMethod === 'bank_transfer') {
      paiementproChannels.style.display = 'none';
      bankTransferInfo.style.display = 'block';
      payButtonText.textContent = 'Valider la commande';
    }
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
