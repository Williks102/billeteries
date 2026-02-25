document.addEventListener('DOMContentLoaded', function () {
  const config = document.getElementById('checkout-verify-phone-config');
  const resendButton = document.getElementById('resend-code');
  const otpInput = document.getElementById('otp_code');

  if (!config || !resendButton || !otpInput) return;

  const resendUrl = config.dataset.resendUrl;
  const csrf = config.dataset.csrf;
  const phone = config.dataset.phone;

  resendButton.addEventListener('click', function () {
    const originalText = resendButton.textContent;
    resendButton.textContent = 'Envoi en cours...';
    resendButton.disabled = true;

    fetch(resendUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
      },
      body: JSON.stringify({ phone }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          alert('Code renvoyé avec succès !');
        } else {
          alert('Erreur lors du renvoi : ' + (data.error || 'Erreur inconnue'));
        }
      })
      .catch(() => {
        alert('Erreur de connexion');
      })
      .finally(() => {
        resendButton.textContent = originalText;
        resendButton.disabled = false;
        setTimeout(() => {
          resendButton.disabled = false;
        }, 60000);
      });
  });

  otpInput.addEventListener('input', function (e) {
    const value = e.target.value.replace(/\D/g, '');
    e.target.value = value;
    if (value.length === 6) {
      e.target.form.submit();
    }
  });
});
