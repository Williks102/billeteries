{{-- ================================================ --}}
{{-- resources/views/checkout/guest.blade.php ADAPTÉ --}}
{{-- Design moderne + logique métier complète --}}
{{-- ================================================ --}}

@extends('layouts.app')

@section('title', 'Commande rapide')

@section('content')
<div class="container py-4">
    {{-- Header --}}
    <div class="checkout-header text-center mb-4">
        <h1 class="h3 mb-2">
            <i class="fas fa-bolt me-2"></i>
            Commande express
        </h1>
        <p class="text-muted">Achetez rapidement sans créer de compte</p>
        
        <div class="checkout-steps">
            <div class="step completed">
                <span class="step-number">1</span>
                <span>Sélection</span>
            </div>
            <div class="step active">
                <span class="step-number">2</span>
                <span>Informations</span>
            </div>
            <div class="step">
                <span class="step-number">3</span>
                <span>Paiement</span>
            </div>
        </div>
    </div>

    <form action="{{ route('checkout.guest.process') }}" method="POST" id="guest-checkout-form">
        @csrf
        
        <div class="row">
            {{-- Colonne principale --}}
            <div class="col-lg-8 mb-4">
                
                {{-- ===== RÉSUMÉ DES BILLETS ===== --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-ticket-alt me-2"></i>Vos billets</h5>
                    </div>
                    <div class="card-body p-0">
                        @foreach($cart as $item)
                        <div class="cart-item border-bottom p-3">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    @if($item['event_image'])
                                        <img src="{{ Storage::url($item['event_image']) }}" 
                                             class="img-fluid rounded" alt="Événement">
                                    @else
                                        <div class="event-placeholder rounded">
                                            <i class="fas fa-calendar-alt"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <h6 class="mb-1">{{ $item['event_title'] }}</h6>
                                    <p class="text-muted mb-1">
                                        <i class="fas fa-calendar me-1"></i>{{ $item['event_date'] ?? 'Date à confirmer' }}
                                    </p>
                                    <small class="text-muted">{{ $item['ticket_name'] }} - {{ $item['quantity'] }}x</small>
                                    @if(isset($item['event_venue']))
                                        <div class="text-muted mt-1">
                                            <i class="fas fa-map-marker-alt me-1"></i>{{ $item['event_venue'] }}
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-2 text-center">
                                    <span class="badge bg-primary">{{ $item['quantity'] }}</span>
                                </div>
                                <div class="col-md-2 text-end">
                                    <strong>{{ number_format($item['total_price']) }} FCFA</strong>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Le compte se crée APRÈS l'achat (page de confirmation), jamais pendant la commande invité. --}}

                {{-- ===== INFORMATIONS PERSONNELLES ===== --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Vos informations</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">Prénom *</label>
                                <input type="text" class="form-control @error('first_name') is-invalid @enderror" 
                                       id="first_name" name="first_name" value="{{ old('first_name') }}" required>
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Nom *</label>
                                <input type="text" class="form-control @error('last_name') is-invalid @enderror" 
                                       id="last_name" name="last_name" value="{{ old('last_name') }}" required>
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                       id="email" name="email" value="{{ old('email') }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Téléphone *</label>
                                <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                                       id="phone" name="phone" value="{{ old('phone') }}" required>
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                    </div>
                </div>

                {{-- ===== MOYEN DE PAIEMENT ===== --}}
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Moyen de paiement</h5>
                    </div>
                    <div class="card-body">
                        {{-- Options de paiement manuel (confirmées par l'administrateur) --}}
                        @php($manualIcons = ['bank_transfer' => 'fa-university', 'mobile_money' => 'fa-mobile-alt', 'cash' => 'fa-money-bill-wave'])
                        <div class="payment-methods mb-4">
                            @foreach(\App\Services\CheckoutService::manualMethodKeys() as $methodKey)
                                @php($methodInfo = \App\Services\CheckoutService::manualPaymentInfo($methodKey))
                                <div class="payment-method-option mb-3">
                                    <input type="radio" class="btn-check" name="payment_method"
                                           id="pm_guest_{{ $methodKey }}" value="{{ $methodKey }}"
                                           {{ old('payment_method', \App\Services\CheckoutService::manualMethodKeys()[0] ?? null) === $methodKey ? 'checked' : '' }}>
                                    <label class="btn btn-outline-secondary w-100 p-3 text-start" for="pm_guest_{{ $methodKey }}">
                                        <div class="d-flex align-items-center">
                                            <i class="fas {{ $manualIcons[$methodKey] ?? 'fa-wallet' }} fa-2x me-3"></i>
                                            <div>
                                                <strong>{{ $methodInfo['label'] }}</strong>
                                                <small class="d-block text-muted">Confirmation manuelle par l'organisateur</small>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        {{-- Instructions par méthode (affichées selon le choix) --}}
                        @foreach(\App\Services\CheckoutService::manualMethodKeys() as $methodKey)
                            @php($methodInfo = \App\Services\CheckoutService::manualPaymentInfo($methodKey))
                            <div class="alert alert-info manual-method-info" data-method="{{ $methodKey }}" style="display: none;">
                                <h6><i class="fas fa-info-circle me-2"></i>{{ $methodInfo['label'] }}</h6>
                                <p class="mb-2">{{ $methodInfo['instructions'] }}</p>
                                <ol class="mb-0">
                                    <li>Vous validez votre commande : le stock est réservé pendant {{ (int) round(config('payments.manual_reservation_minutes', 2880) / 60) }} h</li>
                                    <li>Les instructions détaillées s'affichent et vous sont envoyées par email</li>
                                    <li>Vous effectuez le paiement avant la date limite</li>
                                    <li>Vos billets sont envoyés dès que l'organisateur confirme la réception du paiement</li>
                                </ol>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Sidebar droite - Résumé --}}
            <div class="col-lg-4">
                <div class="summary-sidebar">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Récapitulatif</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Sous-total</span>
                                <span>{{ number_format($cartTotal) }} FCFA</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Frais de service</span>
                                <span>{{ number_format($serviceFee) }} FCFA</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Total à payer</strong>
                                <strong class="text-primary">{{ number_format($finalTotal) }} FCFA</strong>
                            </div>

                            {{-- Avantages commande express --}}
                            <div class="alert alert-success mb-3">
                                <h6><i class="fas fa-bolt me-2"></i>Commande express</h6>
                                <ul class="mb-0 small">
                                    <li>✅ Pas d'inscription nécessaire</li>
                                    <li>✅ Billets par email instantané</li>
                                    <li>✅ Paiement 100% sécurisé</li>
                                </ul>
                            </div>

                            {{-- Conditions générales --}}
                            <div class="form-check mb-3">
                                <input class="form-check-input @error('terms_accepted') is-invalid @enderror" 
                                       type="checkbox" name="terms_accepted" id="terms_accepted_guest" required>
                                <label class="form-check-label small" for="terms_accepted_guest">
                                    J'accepte les <a href="{{ route('pages.terms') }}" target="_blank">conditions générales</a>
                                    et la <a href="{{ route('pages.privacy') }}" target="_blank">politique de confidentialité</a>
                                </label>
                                @error('terms_accepted')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Bouton de paiement --}}
                            <button type="submit" class="btn btn-primary btn-lg w-100" id="pay-button-guest">
                                <i class="fas fa-bolt me-2"></i>
                                <span id="pay-button-text-guest">Commande express</span>
                            </button>

                            {{-- Informations de sécurité --}}
                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-shield-alt me-1 text-success"></i>
                                    Paiement 100% sécurisé
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- CSS MODERNE avec correction header --}}
<style>
/* ====================== VARIABLES & BASE ====================== */
:root {
    --primary-orange: #FF6B35;
    --primary-dark: #E55A2B;
    --header-height: 70px;
    --shadow-light: 0 2px 20px rgba(0,0,0,0.08);
    --shadow-medium: 0 4px 25px rgba(0,0,0,0.15);
    --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* FIX HEADER - Solution principale */
.navbar.fixed-top,
.main-navbar,
header.navbar {
    position: fixed !important;
    top: 0 !important;
    z-index: 9999 !important;
    backdrop-filter: blur(10px);
    transition: var(--transition-smooth);
}

.navbar.scrolled {
    box-shadow: var(--shadow-medium) !important;
    background: rgba(255,255,255,0.95) !important;
}

body {
    padding-top: var(--header-height) !important;
}

/* ====================== CHECKOUT DESIGN ====================== */
.checkout-steps {
    display: flex;
    justify-content: center;
    margin: 2rem 0;
    padding: 0 1rem;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin: 0 1.5rem;
    position: relative;
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e9ecef;
    color: #6c757d;
    font-weight: bold;
    margin-bottom: 0.5rem;
    transition: var(--transition-smooth);
}

.step.completed .step-number {
    background: #28a745;
    color: white;
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

.step.active .step-number {
    background: var(--primary-orange);
    color: white;
    box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
}

/* ====================== CARTES MODERNES ====================== */
.card {
    border: none;
    box-shadow: var(--shadow-light);
    border-radius: 12px;
    margin-bottom: 1.5rem;
    overflow: hidden;
    transition: var(--transition-smooth);
}

.card:hover {
    box-shadow: var(--shadow-medium);
    transform: translateY(-2px);
}

.card-header {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-bottom: 1px solid #e9ecef;
    padding: 1rem 1.25rem;
    font-weight: 600;
}

/* ====================== MODE SELECTION ====================== */
.mode-option {
    transition: var(--transition-smooth);
    cursor: pointer;
    border-radius: 12px;
    overflow: hidden;
}

.mode-option .btn {
    border-radius: 12px;
    transition: var(--transition-smooth);
}

.mode-option.selected .btn {
    background: var(--primary-orange);
    color: white;
    border-color: var(--primary-orange);
    transform: scale(1.02);
    box-shadow: 0 8px 25px rgba(255, 107, 53, 0.3);
}

/* ====================== PAYMENT OPTIONS SIMPLE ====================== */
.payment-options .form-check {
    padding-left: 1.5rem;
}

.payment-options .form-check-input {
    margin-top: 0.25rem;
}

.payment-options .form-check-label {
    font-size: 1rem;
    cursor: pointer;
    margin-left: 0.5rem;
}

.manual-method-info {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    border: 1px solid #dee2e6;
}

/* ====================== CART ITEMS ====================== */
.cart-item {
    transition: var(--transition-smooth);
}

.cart-item:hover {
    background: linear-gradient(135deg, #f8f9fa, #ffffff);
    transform: translateX(5px);
}

.event-placeholder {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #e9ecef, #dee2e6);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    border-radius: 8px;
}

/* ====================== SIDEBAR ====================== */
.summary-sidebar {
    position: sticky;
    top: calc(var(--header-height) + 2rem);
    max-height: calc(100vh - var(--header-height) - 4rem);
    overflow-y: auto;
}

/* ====================== BOUTONS ====================== */
.btn-primary {
    background: var(--primary-orange);
    border-color: var(--primary-orange);
    border-radius: 8px;
    font-weight: 600;
    transition: var(--transition-smooth);
}

.btn-primary:hover,
.btn-primary:focus {
    background: var(--primary-dark);
    border-color: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(255, 107, 53, 0.3);
}

/* ====================== ANIMATIONS ====================== */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    animation: slideInUp 0.6s ease-out forwards;
}

.card:nth-child(2) { animation-delay: 0.1s; }
.card:nth-child(3) { animation-delay: 0.2s; }
.card:nth-child(4) { animation-delay: 0.3s; }

/* ====================== RESPONSIVE ====================== */
@media (max-width: 768px) {
    :root {
        --header-height: 60px;
    }
    
    body {
        padding-top: 60px !important;
    }
    
    .checkout-steps {
        margin: 1rem 0;
    }
    
    .step {
        margin: 0 0.75rem;
    }
    
    .summary-sidebar {
        position: relative;
        top: auto;
        max-height: none;
        margin-bottom: 2rem;
    }
    
    .mode-option .btn {
        padding: 1.5rem 1rem;
    }
}

@media (max-width: 576px) {
    .checkout-steps {
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .step {
        margin: 0;
        min-width: 80px;
    }
}
</style>

<script src="{{ asset('js/checkout-guest.js') }}" defer></script>



@endsection