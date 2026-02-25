{{-- ================================================ --}}
{{-- resources/views/checkout/show.blade.php --}}
{{-- Version intégrée avec sélection de paiement --}}
{{-- ================================================ --}}

@extends('layouts.app')

@section('title', 'Finaliser ma commande')

@section('content')
<div class="container py-4">
    {{-- Header avec étapes --}}
    <div class="checkout-header text-center mb-4">
        <h1 class="h3 mb-2">
            <i class="fas fa-credit-card me-2"></i>
            Finaliser ma commande
        </h1>
        <div class="checkout-steps">
            <div class="step completed">
                <span class="step-number">1</span>
                <span>Sélection</span>
            </div>
            <div class="step active">
                <span class="step-number">2</span>
                <span>Paiement</span>
            </div>
            <div class="step">
                <span class="step-number">3</span>
                <span>Confirmation</span>
            </div>
        </div>
    </div>

    <form action="{{ route('checkout.process') }}" method="POST" id="checkout-form">
        @csrf
        
        <div class="row">
            {{-- Colonne principale --}}
            <div class="col-lg-8 mb-4">
                
                {{-- ===== RÉSUMÉ DES BILLETS ===== --}}
                <div class="card mb-4">
                    <div class="card-header bg-light">
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
                                        <i class="fas fa-calendar me-1"></i>{{ $item['event_date'] ?? 'Date TBD' }}
                                    </p>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-map-marker-alt me-1"></i>{{ $item['event_venue'] ?? 'Lieu TBD' }}
                                    </p>
                                </div>
                                <div class="col-md-2 text-center">
                                    <div class="ticket-type-info">
                                        <strong>{{ $item['ticket_name'] }}</strong>
                                        <div class="text-muted">{{ $item['quantity'] }} billet(s)</div>
                                    </div>
                                </div>
                                <div class="col-md-2 text-end">
                                    <div class="price-info">
                                        <div class="unit-price text-muted">
                                            {{ number_format($item['unit_price']) }} FCFA/unité
                                        </div>
                                        <div class="total-price">
                                            <strong>{{ number_format($item['total_price']) }} FCFA</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- ===== INFORMATIONS DE FACTURATION ===== --}}
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Informations de contact</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="billing_email" class="form-label">Email *</label>
                                <input type="email" class="form-control @error('billing_email') is-invalid @enderror" 
                                       id="billing_email" name="billing_email" 
                                       value="{{ old('billing_email', auth()->user()->email) }}" required>
                                @error('billing_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="billing_phone" class="form-label">Téléphone *</label>
                                <input type="tel" class="form-control @error('billing_phone') is-invalid @enderror" 
                                       id="billing_phone" name="billing_phone" 
                                       value="{{ old('billing_phone', auth()->user()->phone) }}" required>
                                @error('billing_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ===== SÉLECTION DU MOYEN DE PAIEMENT ===== --}}
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Moyen de paiement</h5>
                    </div>
                    <div class="card-body">
                        {{-- Options de paiement --}}
                        <div class="payment-methods mb-4">
                            {{-- PaiementPro --}}
                            <div class="payment-method-option">
                                <input type="radio" class="btn-check" name="payment_method" 
                                       id="paiementpro" value="paiementpro" checked>
                                <label class="btn btn-outline-primary w-100 mb-3" for="paiementpro">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-credit-card fa-2x me-3 text-primary"></i>
                                        <div class="text-start">
                                            <h6 class="mb-1">Paiement en ligne</h6>
                                            <small class="text-muted">Carte bancaire, Mobile Money, Orange Money, Flooz</small>
                                        </div>
                                        <div class="ms-auto">
                                            <span class="badge bg-success">Recommandé</span>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            {{-- Virement bancaire (si vous l'avez) --}}
                            <div class="payment-method-option">
                                <input type="radio" class="btn-check" name="payment_method" 
                                       id="bank_transfer" value="bank_transfer">
                                <label class="btn btn-outline-secondary w-100" for="bank_transfer">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-university fa-2x me-3 text-secondary"></i>
                                        <div class="text-start">
                                            <h6 class="mb-1">Virement bancaire</h6>
                                            <small class="text-muted">Instructions envoyées par email</small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- Canaux PaiementPro (affiché conditionnellement) --}}
                        <div id="paiementpro-channels" class="paiementpro-options">
                            <h6 class="mb-3">Choisissez votre méthode :</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <input type="radio" class="btn-check" name="channel" 
                                           id="card" value="CARD" checked>
                                    <label class="btn btn-outline-info w-100 p-3" for="card">
                                        <i class="fas fa-credit-card fa-2x mb-2"></i>
                                        <br><strong>Carte bancaire</strong>
                                        <br><small>Visa, Mastercard</small>
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <input type="radio" class="btn-check" name="channel" 
                                           id="momo" value="MOMO">
                                    <label class="btn btn-outline-warning w-100 p-3" for="momo">
                                        <i class="fas fa-mobile-alt fa-2x mb-2"></i>
                                        <br><strong>Mobile Money</strong>
                                        <br><small>MTN, Moov</small>
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <input type="radio" class="btn-check" name="channel" 
                                           id="orange" value="OMCIV2">
                                    <label class="btn btn-outline-warning w-100 p-3" for="orange">
                                        <i class="fas fa-mobile fa-2x mb-2"></i>
                                        <br><strong>Orange Money</strong>
                                        <br><small>Orange CI</small>
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <input type="radio" class="btn-check" name="channel" 
                                           id="flooz" value="FLOOZ">
                                    <label class="btn btn-outline-info w-100 p-3" for="flooz">
                                        <i class="fas fa-wallet fa-2x mb-2"></i>
                                        <br><strong>Flooz</strong>
                                        <br><small>Portefeuille digital</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Informations virement (affiché conditionnellement) --}}
                        <div id="bank-transfer-info" class="bank-transfer-options" style="display: none;">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle me-2"></i>Comment ça marche ?</h6>
                                <ol class="mb-0">
                                    <li>Vous validez votre commande</li>
                                    <li>Nous vous envoyons les coordonnées bancaires par email</li>
                                    <li>Vous effectuez le virement</li>
                                    <li>Vos billets sont envoyés dès réception du paiement</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

                {{-- ===== SIDEBAR RÉCAPITULATIF ===== --}}
                <div class="col-lg-4">
                    <div class="card sticky-top" style="top: 2rem;">
                        <div class="card-header bg-orange text-white">
                            <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Récapitulatif</h5>
                        </div>
                        <div class="card-body">
                            {{-- Détail des prix --}}
                            <div class="order-summary">
                                <div class="d-flex justify-content-between mb-2">
                                <span>Sous-total</span>
                                <span>{{ number_format($cartTotal) }} FCFA</span>
                                </div>
                                @if($serviceFee > 0)
                                <div class="d-flex justify-content-between mb-2">
        <span>Frais de service</span>
    <span>{{ number_format($serviceFee) }} FCFA</span>
</div>
@endif
<hr>
<div class="d-flex justify-content-between mb-3">
    <strong>Total à payer</strong>
    <strong class="text-orange">{{ number_format($finalTotal) }} FCFA</strong>
</div>
                        </div>

                        {{-- Conditions générales --}}
                        <div class="form-check mb-3">
                            <input class="form-check-input @error('terms_accepted') is-invalid @enderror" 
                                   type="checkbox" name="terms_accepted" id="terms_accepted" required>
                            <label class="form-check-label small" for="terms_accepted">
                                J'accepte les <a href="{{ route('pages.terms') }}" target="_blank">conditions générales</a>
                                et la <a href="{{ route('pages.privacy') }}" target="_blank">politique de confidentialité</a>
                            </label>
                            @error('terms_accepted')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Bouton de paiement --}}
                        <button type="submit" class="btn btn-orange btn-lg w-100" id="pay-button">
                            <i class="fas fa-lock me-2"></i>
                            <span id="pay-button-text">Procéder au paiement</span>
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
    </form>
</div>

<style>
.checkout-steps {
    display: flex;
    justify-content: center;
    margin: 2rem 0;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin: 0 2rem;
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
}

.step.completed .step-number {
    background: #28a745;
    color: white;
}

.step.active .step-number {
    background: #ff6b35;
    color: white;
}

.payment-method-option .btn-check:checked + .btn {
    background-color: var(--bs-primary);
    color: white;
    border-color: var(--bs-primary);
}

.paiementpro-options, .bank-transfer-options {
    transition: all 0.3s ease;
}

.cart-item {
    transition: all 0.2s ease;
}

.cart-item:hover {
    background-color: #f8f9fa;
}

.event-placeholder {
    width: 60px;
    height: 60px;
    background: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
}

@media (max-width: 768px) {
    .step {
        margin: 0 1rem;
    }
    
    .checkout-steps span:not(.step-number) {
        font-size: 0.8rem;
    }
}
</style>

<script src="{{ asset('js/checkout-show.js') }}" defer></script>
@endsection