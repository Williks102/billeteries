document.addEventListener('DOMContentLoaded', function () {
  const header = document.querySelector('.navbar, .main-navbar, header');
  if (header) {
    header.classList.add('fixed-top');
    window.addEventListener('scroll', function () {
      if (window.scrollY > 50) header.classList.add('scrolled');
      else header.classList.remove('scrolled');
    });
  }

  const guestMode = document.getElementById('guestMode');
  const accountMode = document.getElementById('accountMode');
  const accountSection = document.getElementById('accountSection');
  const createAccountInput = document.getElementById('create_account');
  const passwordFields = document.getElementById('passwordFields');
  const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
  const paiementproChannels = document.getElementById('paiementpro-channels-guest');
  const bankTransferInfo = document.getElementById('bank-transfer-info-guest');
  const payButton = document.getElementById('pay-button-guest');
  const payButtonText = document.getElementById('pay-button-text-guest');
  const form = document.getElementById('guest-checkout-form');

  if (!guestMode || !accountMode || !accountSection || !createAccountInput || !passwordFields || !paymentMethods.length || !paiementproChannels || !bankTransferInfo || !payButton || !payButtonText || !form) return;

  function updateButtonText(mode = null) {
    if (!mode) mode = createAccountInput.value === '1' ? 'account' : 'guest';
    const selectedPayment = document.querySelector('input[name="payment_method"]:checked')?.value;

    if (selectedPayment === 'bank_transfer') {
      payButtonText.textContent = mode === 'account' ? 'Créer compte et commander' : 'Valider la commande';
    } else {
      payButtonText.textContent = mode === 'account' ? 'Créer compte et payer' : 'Commande express';
    }
  }

  function selectMode(mode) {
    document.querySelectorAll('.mode-option').forEach((option) => option.classList.remove('selected'));

    if (mode === 'guest') {
      guestMode.classList.add('selected');
      accountSection.style.display = 'none';
      createAccountInput.value = '0';
      passwordFields.style.display = 'none';
      updateButtonText('guest');
    } else {
      accountMode.classList.add('selected');
      accountSection.style.display = 'block';
      createAccountInput.value = '1';
      passwordFields.style.display = 'block';
      updateButtonText('account');
    }
  }

  function togglePaymentOptions() {
    const selectedMethod = document.querySelector('input[name="payment_method"]:checked')?.value;

    if (selectedMethod === 'paiementpro') {
      paiementproChannels.style.display = 'block';
      bankTransferInfo.style.display = 'none';
    } else if (selectedMethod === 'bank_transfer') {
      paiementproChannels.style.display = 'none';
      bankTransferInfo.style.display = 'block';
    }

    updateButtonText();
  }

  guestMode.addEventListener('click', function () { selectMode('guest'); });
  accountMode.addEventListener('click', function () { selectMode('account'); });

  paymentMethods.forEach((method) => {
    method.addEventListener('change', togglePaymentOptions);
  });

  selectMode('guest');
  togglePaymentOptions();

  form.addEventListener('submit', function (e) {
    const termsAccepted = document.getElementById('terms_accepted_guest')?.checked;
    if (!termsAccepted) {
      e.preventDefault();
      alert('Veuillez accepter les conditions générales');
      return;
    }

    payButton.disabled = true;
    payButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Traitement en cours...';
  });

  const cards = document.querySelectorAll('.card');
  cards.forEach((card, index) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    setTimeout(() => {
      card.style.opacity = '1';
      card.style.transform = 'translateY(0)';
    }, index * 100);
  });
});
