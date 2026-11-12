@props(['prefix' => 'pm_'])
{{-- Choix du mode de paiement manuel : grandes cartes radio (>= 44 px), libellé + instructions courtes visibles
     pour le mode sélectionné (CSS seul, sans JavaScript). name="payment_method" et les value restent inchangés. --}}
@php
    $icons = ['bank_transfer' => 'fa-university', 'mobile_money' => 'fa-mobile-alt', 'cash' => 'fa-money-bill-wave'];
    $keys = \App\Services\CheckoutService::manualMethodKeys();
    $selected = old('payment_method', $keys[0] ?? null);
@endphp
<fieldset class="pm-group" @error('payment_method') aria-describedby="payment_method-error" @enderror>
    <legend>Mode de paiement</legend>
    @foreach($keys as $methodKey)
        @php($info = \App\Services\CheckoutService::manualPaymentInfo($methodKey))
        <div class="pm-card">
            <input type="radio" class="pm-card__input" name="payment_method" id="{{ $prefix }}{{ $methodKey }}" value="{{ $methodKey }}" {{ $selected === $methodKey ? 'checked' : '' }}>
            <label class="pm-card__box" for="{{ $prefix }}{{ $methodKey }}">
                <i class="fas {{ $icons[$methodKey] ?? 'fa-wallet' }} pm-card__icon" aria-hidden="true"></i>
                <span class="pm-card__text">
                    <span class="pm-card__label">{{ $info['label'] }}</span>
                    <span class="pm-card__hint">Confirmé par l'organisateur après réception</span>
                </span>
                <span class="pm-card__check" aria-hidden="true"><i class="far fa-circle"></i><i class="fas fa-check-circle"></i></span>
                <span class="pm-card__instructions">{{ $info['instructions'] }}</span>
            </label>
        </div>
    @endforeach
    <x-field-error name="payment_method" />
</fieldset>
