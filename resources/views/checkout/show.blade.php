{{-- Checkout connecté (aussi utilisé par la réservation directe). Mobile d'abord : une colonne, puis 2 colonnes >= 992 px. --}}
@extends('layouts.app')

@section('title', 'Finaliser ma commande')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/parcours-achat.css') }}">
@endpush

@section('content')
<div class="container parcours-page">
    <h1>Finaliser ma commande</h1>
    <x-checkout-steps :current="2" />

    <form action="{{ route('checkout.process') }}" method="POST" id="checkout-form">
        @csrf

        <div class="parcours-layout">
            <div class="stack">
                <section class="parcours-card" aria-labelledby="lines-title">
                    <h2 class="parcours-card__title" id="lines-title"><i class="fas fa-ticket-alt" aria-hidden="true"></i>Vos billets</h2>
                    <x-checkout-lines :cart="$cart" />
                </section>

                <section class="parcours-card" aria-labelledby="contact-title">
                    <h2 class="parcours-card__title" id="contact-title"><i class="fas fa-user" aria-hidden="true"></i>Informations de contact</h2>
                    <div class="stack">
                        <div class="field">
                            <label for="billing_email" class="form-label">Email <span class="required-mark" aria-hidden="true">*</span></label>
                            <input type="email" class="form-control @error('billing_email') is-invalid @enderror"
                                   id="billing_email" name="billing_email" autocomplete="email" inputmode="email" required
                                   value="{{ old('billing_email', auth()->user()->email) }}"
                                   @error('billing_email') aria-invalid="true" aria-describedby="billing_email-error" @enderror>
                            <x-field-error name="billing_email" />
                        </div>
                        <div class="field">
                            <label for="billing_phone" class="form-label">Téléphone <span class="required-mark" aria-hidden="true">*</span></label>
                            <input type="tel" class="form-control @error('billing_phone') is-invalid @enderror"
                                   id="billing_phone" name="billing_phone" autocomplete="tel" inputmode="tel" required
                                   value="{{ old('billing_phone', auth()->user()->phone) }}"
                                   @error('billing_phone') aria-invalid="true" aria-describedby="billing_phone-error" @enderror>
                            <x-field-error name="billing_phone" />
                        </div>
                    </div>
                </section>

                <section class="parcours-card" aria-label="Mode de paiement">
                    <x-payment-method-cards prefix="pm_" />
                </section>
            </div>

            <aside class="parcours-aside parcours-card" aria-labelledby="summary-title">
                <h2 class="parcours-card__title" id="summary-title"><i class="fas fa-receipt" aria-hidden="true"></i>Récapitulatif</h2>
                <div class="summary-row">
                    <span>Sous-total</span>
                    <span class="summary-amount">{{ \App\Helpers\CurrencyHelper::formatPrice($cartTotal) }}</span>
                </div>
                <x-checkout-submit :total="$cartTotal" :guest="false" terms-id="terms_accepted" button-id="pay-button" />
            </aside>
        </div>
    </form>
</div>
<script src="{{ asset('js/checkout-form.js') }}" defer></script>
@endsection
