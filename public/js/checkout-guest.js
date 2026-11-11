document.addEventListener('DOMContentLoaded', function () {
  const header = document.querySelector('.navbar, .main-navbar, header');
  if (header) {
    header.classList.add('fixed-top');
    window.addEventListener('scroll', function () {
      if (window.scrollY > 50) header.classList.add('scrolled');
      else header.classList.remove('scrolled');
    });
  }

  const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
  const methodInfos = document.querySelectorAll('.manual-method-info');
  const payButton = document.getElementById('pay-button-guest');
  const payButtonText = document.getElementById('pay-button-text-guest');
  const form = document.getElementById('guest-checkout-form');

  if (!paymentMethods.length || !payButton || !payButtonText || !form) return;

  function togglePaymentOptions() {
    const selectedMethod = document.querySelector('input[name="payment_method"]:checked')?.value;

    methodInfos.forEach((info) => {
      info.style.display = info.dataset.method === selectedMethod ? 'block' : 'none';
    });

  }

  paymentMethods.forEach((method) => {
    method.addEventListener('change', togglePaymentOptions);
  });

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
