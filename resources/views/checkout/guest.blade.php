{{-- Checkout invité (sans compte). Mobile d'abord : une colonne, puis 2 colonnes >= 992 px. --}}
@extends('layouts.app')

@section('title', 'Commande rapide')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/parcours-achat.css') }}">
@endpush

@section('content')
<div class="container parcours-page">
    <h1>Commande express</h1>
    <p class="text-muted">Achetez sans créer de compte : vos billets vous sont envoyés par email.</p>
    <x-checkout-steps :current="2" />

    <form action="{{ route('checkout.guest.process') }}" method="POST" id="guest-checkout-form">
        @csrf

        <div class="parcours-layout">
            <div class="stack">
                <section class="parcours-card" aria-labelledby="lines-title">
                    <h2 class="parcours-card__title" id="lines-title"><i class="fas fa-ticket-alt" aria-hidden="true"></i>Vos billets</h2>
                    <x-checkout-lines :cart="$cart" />
                </section>

                {{-- Aucune création de compte depuis le checkout invité (N1) : suivi par le lien de confirmation. --}}
                <section class="parcours-card" aria-labelledby="contact-title">
                    <h2 class="parcours-card__title" id="contact-title"><i class="fas fa-user" aria-hidden="true"></i>Vos informations</h2>
                    <div class="stack">
                        <div class="field">
                            <label for="first_name" class="form-label">Prénom <span class="required-mark" aria-hidden="true">*</span></label>
                            <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                                   id="first_name" name="first_name" value="{{ old('first_name') }}" autocomplete="given-name" required
                                   @error('first_name') aria-invalid="true" aria-describedby="first_name-error" @enderror>
                            <x-field-error name="first_name" />
                        </div>
                        <div class="field">
                            <label for="last_name" class="form-label">Nom <span class="required-mark" aria-hidden="true">*</span></label>
                            <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                                   id="last_name" name="last_name" value="{{ old('last_name') }}" autocomplete="family-name" required
                                   @error('last_name') aria-invalid="true" aria-describedby="last_name-error" @enderror>
                            <x-field-error name="last_name" />
                        </div>
                        <div class="field">
                            <label for="email" class="form-label">Email <span class="required-mark" aria-hidden="true">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                   id="email" name="email" value="{{ old('email') }}" autocomplete="email" inputmode="email" required
                                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                            <x-field-error name="email" />
                        </div>
                        <div class="field">
                            <label for="phone" class="form-label">Téléphone <span class="required-mark" aria-hidden="true">*</span></label>
                            <input type="tel" class="form-control @error('phone') is-invalid @enderror"
                                   id="phone" name="phone" value="{{ old('phone') }}" autocomplete="tel" inputmode="tel" required
                                   @error('phone') aria-invalid="true" aria-describedby="phone-error" @enderror>
                            <x-field-error name="phone" />
                        </div>
                    </div>
                </section>

                <section class="parcours-card" aria-label="Mode de paiement">
                    <x-payment-method-cards prefix="pm_guest_" />
                </section>
            </div>

            <aside class="parcours-aside parcours-card" aria-labelledby="summary-title">
                <h2 class="parcours-card__title" id="summary-title"><i class="fas fa-receipt" aria-hidden="true"></i>Récapitulatif</h2>
                <div class="summary-row">
                    <span>Sous-total</span>
                    <span class="summary-amount">{{ \App\Helpers\CurrencyHelper::formatPrice($cartTotal) }}</span>
                </div>
                <x-checkout-submit :total="$cartTotal" :guest="true" terms-id="terms_accepted_guest" button-id="pay-button-guest" />
            </aside>
        </div>
    </form>
</div>
<script src="{{ asset('js/checkout-form.js') }}" defer></script>
@endsection
