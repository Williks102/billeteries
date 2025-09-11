{{-- ================================================ --}}
{{-- resources/views/checkout/guest.blade.php --}}
{{-- Checkout invité intégré avec sélection de paiement --}}
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

                {{-- ===== MODE DE COMMANDE ===== --}}
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Comment souhaitez-vous commander ?</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            {{-- Mode invité --}}
                            <div class="col-md-6">
                                <div class="mode-option selected" id="guestMode">
                                    <input type="radio" class="btn-check" name="mode" id="guest" value="guest" checked>
                                    <label class="btn btn-outline-primary w-100 p-3" for="guest">
                                        <i class="fas fa-bolt fa-2x mb-2"></i>
                                        <br><strong>Commande express</strong>
                                        <br><small>Sans créer de compte</small>
                                    </label>
                                </div>
                            </div>
                            
                            {{-- Mode avec compte --}}
                            <div class="col-md-6">
                                <div class="mode-option" id="accountMode">
                                    <input type="radio" class="btn-check" name="mode" id="account" value="account">
                                    <label class="btn btn-outline-success w-100 p-3" for="account">
                                        <i class="fas fa-user-plus fa-2x mb-2"></i>
                                        <br><strong>Créer un compte</strong>
                                        <br><small>Gérer mes billets</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ===== INFORMATIONS PERSONNELLES ===== --}}
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Vos informations</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">Prénom *</label>
                                <input type="text" class="form-control @error('first_name') is-invalid @enderror" 
                                       id="first_name" name="first_name" value="{{ old('first_name') }}" required>
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Nom *</label>
                                <input type="text" class="form-control @error('last_name') is-invalid @enderror" 
                                       id="last_name" name="last_name" value="{{ old('last_name') }}" required>
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                       id="email" name="email" value="{{ old('email') }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Téléphone *</label>
                                <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                                       id="phone" name="phone" value="{{ old('phone') }}" required>
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Section création de compte (cachée par défaut) --}}
                        <div id="accountSection" style="display: none;">
                            <hr class="my-4">
                            <h6><i class="fas fa-key me-2"></i>Sécurité du compte</h6>
                            
                            <input type="hidden" name="create_account" id="create_account" value="0">
                            
                            <div id="passwordFields" style="display: none;">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="password" class="form-label">Mot de passe *</label>
                                        <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                               id="password" name="password">
                                        @error('password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="password_confirmation" class="form-label">Confirmer mot de passe *</label>
                                        <input type="password" class="form-control" 
                                               id="password_confirmation" name="password_confirmation">
                                    </div>
                                </div>
                                
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Avantages du compte :</strong> Gérer vos billets, historique des commandes, réservations futures
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ===== MOYEN DE PAIEMENT ===== --}}
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
                                       id="paiementpro_guest" value="paiementpro" checked>
                                <label class="btn btn-outline-primary w-100 mb-3" for="paiementpro_guest">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-credit-card fa-2x me-3 text-primary"></i>
                                        <div class="text-start">
                                            <h6 class="mb-1">Paiement en ligne</h6>
                                            <small class="text-muted">Carte bancaire, Mobile Money, Orange Money, Flooz</small>
                                        </div>
                                        <div class="ms-auto">
                                            <span class="badge bg-success">Instantané</span>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            {{-- Virement bancaire --}}
                            <div class="payment-method-option">
                                <input type="radio" class="btn-check" name="payment_method" 
                                       id="bank_transfer_guest" value="bank_transfer">
                                <label class="btn btn-outline-secondary w-100" for="bank_transfer_guest">
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

                        {{-- Canaux PaiementPro --}}
                        <div id="paiementpro-channels-guest" class="paiementpro-options">
                            <h6 class="mb-3">Choisissez votre méthode :</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <input type="radio" class="btn-check" name="channel" 
                                           id="card_guest" value="CARD" checked>
                                    <label class="btn btn-outline-info w-100 p-3" for="card_guest">
                                        <i class="fas fa-credit-card fa-2x mb-2"></i>
                                        <br><strong>Carte bancaire</strong>
                                        <br><small>Visa, Mastercard</small>
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <input type="radio" class="btn-check" name="channel" 
                                           id="momo_guest" value="MOMO">
                                    <label class="btn btn-outline-warning w-100 p-3" for="momo_guest">
                                        <i class="fas fa-mobile-alt fa-2x mb-2"></i>
                                        <br><strong>Mobile Money</strong>
                                        <br><small>MTN, Moov</small>
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <input type="radio" class="btn-check" name="channel" 
                                           id="orange_guest" value="OMCIV2">
                                    <label class="btn btn-outline-warning w-100 p-3" for="orange_guest">
                                        <i class="fas fa-mobile fa-2x mb-2"></i>
                                        <br><strong>Orange Money</strong>
                                        <br><small>Orange CI</small>
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <input type="radio" class="btn-check" name="channel" 
                                           id="flooz_guest" value="FLOOZ">
                                    <label class="btn btn-outline-info w-100 p-3" for="flooz_guest">
                                        <i class="fas fa-wallet fa-2x mb-2"></i>
                                        <br><strong>Flooz</strong>
                                        <br><small>Portefeuille digital</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Informations virement --}}
                        <div id="bank-transfer-info-guest" class="bank-transfer-options" style="display: none;">
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
                            <div class="d-flex justify-content-between mb-2">
                                <span>Frais de service</span>
                                <span>{{ number_format($serviceFee) }} FCFA</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Total à payer</strong>
                                <strong class="text-orange">{{ number_format($finalTotal) }} FCFA</strong>
                            </div>
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
                        <button type="submit" class="btn btn-orange btn-lg w-100" id="pay-button-guest">
                            <i class="fas fa-bolt me-2"></i>
                            <span id="pay-button-text-guest">Commande express</span>
                        </button>

                        {{-- Lien connexion --}}
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                Déjà client ? 
                                <a href="{{ route('login') }}?redirect={{ urlencode(route('checkout.show')) }}">
                                    Se connecter
                                </a>
                            </small>
                        </div>

                        {{-- Informations de sécurité --}}
                        <div class="text-center mt-2">
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

{{-- CSS identique au checkout normal --}}
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

.mode-option {
    transition: all 0.3s ease;
    cursor: pointer;
}

.mode-option.selected .btn {
    background-color: var(--bs-primary);
    color: white;
    border-color: var(--bs-primary);
}

.payment-method-option .btn-check:checked + .btn {
    background-color: var(--bs-primary);
    color: white;
    border-color: var(--bs-primary);
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion des modes de commande
    const guestMode = document.getElementById('guestMode');
    const accountMode = document.getElementById('accountMode');
    const accountSection = document.getElementById('accountSection');
    const createAccountInput = document.getElementById('create_account');
    const passwordFields = document.getElementById('passwordFields');
    
    // Gestion des moyens de paiement
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    const paiementproChannels = document.getElementById('paiementpro-channels-guest');
    const bankTransferInfo = document.getElementById('bank-transfer-info-guest');
    const payButton = document.getElementById('pay-button-guest');
    const payButtonText = document.getElementById('pay-button-text-guest');

    // Mode invité par défaut
    guestMode.addEventListener('click', function() {
        selectMode('guest');
    });
    
    // Mode avec compte
    accountMode.addEventListener('click', function() {
        selectMode('account');
    });
    
    function selectMode(mode) {
        document.querySelectorAll('.mode-option').forEach(option => {
            option.classList.remove('selected');
        });
        
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

    // Gestion des moyens de paiement
    function togglePaymentOptions() {
        const selectedMethod = document.querySelector('input[name="payment_method"]:checked').value;
        
        if (selectedMethod === 'paiementpro') {
            paiementproChannels.style.display = 'block';
            bankTransferInfo.style.display = 'none';
        } else if (selectedMethod === 'bank_transfer') {
            paiementproChannels.style.display = 'none';
            bankTransferInfo.style.display = 'block';
        }
        
        updateButtonText();
    }

    function updateButtonText(mode = null) {
        if (!mode) {
            mode = createAccountInput.value === '1' ? 'account' : 'guest';
        }
        
        const selectedPayment = document.querySelector('input[name="payment_method"]:checked')?.value;
        
        if (selectedPayment === 'bank_transfer') {
            payButtonText.textContent = mode === 'account' ? 'Créer compte et commander' : 'Valider la commande';
        } else {
            payButtonText.textContent = mode === 'account' ? 'Créer compte et payer' : 'Commande express';
        }
    }

    // Écouter les changements
    paymentMethods.forEach(method => {
        method.addEventListener('change', togglePaymentOptions);
    });

    // Initialiser
    togglePaymentOptions();

    // Gestion du formulaire
    document.getElementById('guest-checkout-form').addEventListener('submit', function(e) {
        const termsAccepted = document.getElementById('terms_accepted_guest').checked;
        
        if (!termsAccepted) {
            e.preventDefault();
            alert('Veuillez accepter les conditions générales');
            return;
        }

        // Désactiver le bouton
        payButton.disabled = true;
        payButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Traitement en cours...';
    });
});
</script>
@endsection