@props(['total', 'guest' => false, 'termsId' => 'terms_accepted', 'buttonId' => 'pay-button', 'label' => 'Valider la commande'])
{{-- Bloc final du checkout : total, délai de réservation, conditions, bouton unique de validation.
     data-submit-once : public/js/checkout-form.js désactive le bouton après un envoi valide (anti double envoi). --}}
<div class="summary-row summary-row--total">
    <span>Total à payer</span>
    <span class="summary-amount">{{ \App\Helpers\CurrencyHelper::formatPrice($total) }}</span>
</div>

<x-reservation-notice :guest="$guest" />

<div class="terms-check">
    <input class="form-check-input @error('terms_accepted') is-invalid @enderror" type="checkbox" name="terms_accepted" id="{{ $termsId }}" required
           @error('terms_accepted') aria-invalid="true" aria-describedby="terms_accepted-error" @enderror>
    <label for="{{ $termsId }}">J'accepte les conditions générales et la politique de confidentialité.</label>
</div>
<p class="terms-links">
    <a href="{{ route('pages.terms') }}" target="_blank" rel="noopener" class="link-touch">Lire les conditions générales</a>
    <a href="{{ route('pages.privacy') }}" target="_blank" rel="noopener" class="link-touch">Lire la politique de confidentialité</a>
</p>
<x-field-error name="terms_accepted" />

<div class="action-bar">
    <button type="submit" class="btn btn-primary btn-lg" id="{{ $buttonId }}" data-submit-once data-loading-label="Traitement…">
        <i class="fas fa-lock" aria-hidden="true"></i>
        <span data-submit-label>{{ $label }}</span>
    </button>
</div>
<ul class="checkout-promises small mt-3 mb-0">
    <li><i class="fas fa-check" aria-hidden="true"></i>Aucun paiement n'est demandé maintenant : les instructions s'affichent après validation.</li>
    <li><i class="fas fa-check" aria-hidden="true"></i>Billets (avec QR code) disponibles dès la confirmation de votre paiement.</li>
    @if($guest)
        <li><i class="fas fa-check" aria-hidden="true"></i>Aucun compte nécessaire.</li>
    @endif
</ul>
