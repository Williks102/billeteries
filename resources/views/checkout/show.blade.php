{{-- resources/views/checkout/show.blade.php - VERSION ADAPTÉE --}}
@extends('layouts.app')

@section('title', 'Finaliser ma commande - ClicBillet CI')

@section('content')
<div class="checkout-container py-4">
    <div class="container">
        {{-- ===== SECTION TIMER DYNAMIQUE ===== --}}
        @if(isset($isDirectBooking) && $isDirectBooking && isset($timeRemaining))
            {{-- Timer pour réservation directe (1h) --}}
            <div class="alert alert-warning mb-4" id="reservation-timer">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-lock me-2"></i>
                        <strong>Réservation en cours</strong>
                    </div>
                    <div>
                        <span class="fw-bold">Temps restant: </span>
                        <span id="timer-display">{{ $timeRemaining }} minutes</span>
                    </div>
                </div>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar bg-warning" id="timer-progress" 
                         style="width: 100%; animation: countdown {{ $timeRemaining * 60 }}s linear forwards;"></div>
                </div>
                <small class="text-muted mt-1 d-block">
                    <i class="fas fa-info-circle me-1"></i>
                    Vos billets sont bloqués pour vous. Finalisez votre achat avant expiration.
                </small>
            </div>
        @elseif(isset($timeRemaining) && $timeRemaining)
            {{-- Timer pour panier classique (15 min) --}}
            <div class="alert alert-info mb-4" id="cart-timer">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-shopping-cart me-2"></i>
                        <strong>Billets mis de côté</strong>
                    </div>
                    <div>
                        <span class="fw-bold">Temps restant: </span>
                        <span id="cart-timer-display">{{ $timeRemaining }} minutes</span>
                    </div>
                </div>
                <div class="progress mt-2" style="height: 4px;">
                    <div class="progress-bar bg-info" 
                         style="width: 100%; animation: countdown {{ $timeRemaining * 60 }}s linear forwards;"></div>
                </div>
                <small class="text-muted mt-1 d-block">
                    Vos billets sont temporairement mis de côté.
                </small>
            </div>
        @endif

        {{-- ===== HEADER DYNAMIQUE ===== --}}
        <div class="checkout-header text-center">
            <h1 class="h3 mb-2">
                @if(isset($isDirectBooking) && $isDirectBooking)
                    <i class="fas fa-lock me-2"></i>
                    Finaliser ma réservation
                @else
                    <i class="fas fa-credit-card me-2"></i>
                    Finaliser ma commande
                @endif
            </h1>
            <p class="mb-0 opacity-90">
                @if(isset($isDirectBooking) && $isDirectBooking)
                    Vos billets sont réservés - Confirmez votre achat
                @else
                    Vérifiez vos informations et validez votre achat
                @endif
            </p>
        </div>
        
        {{-- ===== ÉTAPES DYNAMIQUES ===== --}}
        <div class="checkout-steps">
            <div class="step">
                <span class="step-number">1</span>
                <span>Sélection</span>
            </div>
            <div class="step active">
                <span class="step-number">2</span>
                <span>
                    @if(isset($isDirectBooking) && $isDirectBooking)
                        Réservation
                    @else
                        Panier
                    @endif
                </span>
            </div>
            <div class="step">
                <span class="step-number">3</span>
                <span>Paiement</span>
            </div>
        </div>
        
        <form action="{{ route('checkout.process') }}" method="POST" id="checkout-form">
            @csrf
            
            <div class="row">
                {{-- ===== COLONNE PRINCIPALE ===== --}}
                <div class="col-lg-8 mb-4">
                    <div class="checkout-card">
                        {{-- ===== RÉSUMÉ ADAPTATIF ===== --}}
                        <div class="checkout-section">
                            <h5>
                                <i class="fas fa-shopping-cart me-2"></i>
                                @if(isset($isDirectBooking) && $isDirectBooking)
                                    Billets réservés
                                @else
                                    Résumé de votre commande
                                @endif
                            </h5>
                            
                            {{-- Message spécifique selon le type --}}
                            @if(isset($isDirectBooking) && $isDirectBooking)
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <strong>Réservation confirmée !</strong> 
                                    Ces billets sont bloqués pour vous.
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Billets temporairement mis de côté depuis votre panier.
                                </div>
                            @endif
                            
                            {{-- Liste des billets (identique à votre code existant) --}}
                            @foreach($cart as $item)
                            <div class="cart-item">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        @if(isset($item['event_image']) && $item['event_image'])
                                            <img src="{{ Storage::url($item['event_image']) }}" 
                                                 alt="{{ $item['event_title'] }}" 
                                                 class="cart-item-image">
                                        @else
                                            <div class="cart-item-image bg-light d-flex align-items-center justify-content-center">
                                                <i class="fas fa-calendar-alt text-muted"></i>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <div class="col">
                                        <h6 class="mb-1 fw-bold">{{ $item['event_title'] }}</h6>
                                        <p class="text-muted mb-1 small">
                                            <i class="fas fa-ticket-alt me-1"></i>
                                            {{ $item['ticket_name'] }}
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted small">
                                                Quantité: {{ $item['quantity'] }}
                                            </span>
                                            <span class="fw-bold text-orange">
                                                {{ number_format($item['total_price'], 0, ',', ' ') }} FCFA
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                            
                            {{-- Action de retour adaptée --}}
                            <div class="text-center mt-3">
                                @if(isset($isDirectBooking) && $isDirectBooking)
                                    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-arrow-left me-1"></i>
                                        Retour à l'événement
                                    </a>
                                @else
                                    <a href="{{ route('cart.show') }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-edit me-1"></i>
                                        Modifier mon panier
                                    </a>
                                @endif
                            </div>
                        </div>
                        
                        {{-- ===== INFORMATIONS DE FACTURATION (identique) ===== --}}
                        <div class="checkout-section">
                            <h5>
                                <i class="fas fa-user me-2"></i>
                                Informations de facturation
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="billing_email" class="form-label">
                                        Email de facturation <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" 
                                           class="form-control @error('billing_email') is-invalid @enderror" 
                                           id="billing_email" 
                                           name="billing_email" 
                                           value="{{ old('billing_email', auth()->user()->email) }}" 
                                           required>
                                    @error('billing_email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="billing_phone" class="form-label">
                                        Téléphone <span class="text-danger">*</span>
                                    </label>
                                    <input type="tel" 
                                           class="form-control @error('billing_phone') is-invalid @enderror" 
                                           id="billing_phone" 
                                           name="billing_phone" 
                                           value="{{ old('billing_phone', auth()->user()->phone) }}" 
                                           required>
                                    @error('billing_phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            {{-- Message adaptatif --}}
                            @if(isset($isDirectBooking) && $isDirectBooking)
                                <div class="alert alert-warning">
                                    <i class="fas fa-clock me-2"></i>
                                    <strong>Attention :</strong> Vous avez {{ $timeRemaining ?? 60 }} minutes pour finaliser.
                                    Ces informations seront utilisées pour confirmer votre réservation.
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Ces informations seront utilisées pour vous envoyer vos billets.
                                </div>
                            @endif
                        </div>
                        
                        {{-- ===== CONDITIONS (identique) ===== --}}
                        <div class="checkout-section">
                            <h5>
                                <i class="fas fa-file-contract me-2"></i>
                                Conditions d'utilisation
                            </h5>
                            
                            <div class="form-check">
                                <input class="form-check-input @error('terms_accepted') is-invalid @enderror" 
                                       type="checkbox" 
                                       id="terms_accepted" 
                                       name="terms_accepted" 
                                       value="1" 
                                       {{ old('terms_accepted') ? 'checked' : '' }}
                                       required>
                                <label class="form-check-label" for="terms_accepted">
                                    J'accepte les <a href="#" target="_blank" class="text-orange">conditions générales</a> 
                                    et la <a href="#" target="_blank" class="text-orange">politique de confidentialité</a>
                                    <span class="text-danger">*</span>
                                </label>
                                @error('terms_accepted')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- ===== SIDEBAR RÉCAPITULATIF ===== --}}
                <div class="col-lg-4">
                    <div class="checkout-card sticky-top" style="top: 2rem;">
                        <div class="checkout-section">
                            <h5>
                                <i class="fas fa-calculator me-2"></i>
                                Récapitulatif
                            </h5>
                            
                            <div class="total-summary">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Sous-total</span>
                                    <span>{{ number_format($cartTotal, 0, ',', ' ') }} FCFA</span>
                                </div>
                                
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Frais de service</span>
                                    <span>{{ number_format($serviceFee, 0, ',', ' ') }} FCFA</span>
                                </div>
                                
                                <hr>
                                
                                <div class="d-flex justify-content-between mb-3">
                                    <strong>Total</strong>
                                    <strong class="text-orange fs-5">
                                        {{ number_format($finalTotal, 0, ',', ' ') }} FCFA
                                    </strong>
                                </div>
                                
                                {{-- Bouton adaptatif --}}
                                <button type="submit" class="btn btn-checkout w-100">
                                    <i class="fas fa-lock me-2"></i>
                                    @if(isset($isDirectBooking) && $isDirectBooking)
                                        Confirmer ma réservation
                                    @else
                                        Confirmer ma commande
                                    @endif
                                </button>
                                
                                <div class="text-center mt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-shield-alt me-1"></i>
                                        @if(isset($isDirectBooking) && $isDirectBooking)
                                            Réservation sécurisée
                                        @else
                                            Paiement sécurisé
                                        @endif
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        {{-- ===== INFORMATIONS SUPPLÉMENTAIRES (identique) ===== --}}
                        <div class="checkout-section">
                            <h6 class="text-muted mb-3">
                                <i class="fas fa-question-circle me-2"></i>
                                Besoin d'aide ?
                            </h6>
                            
                            <div class="small text-muted">
                                <div class="mb-2">
                                    <i class="fas fa-envelope me-2"></i>
                                    <a href="mailto:support@clicbillet.ci" class="text-muted">support@clicbillet.ci</a>
                                </div>
                                <div class="mb-2">
                                    <i class="fas fa-phone me-2"></i>
                                    <a href="tel:+22500000000" class="text-muted">+225 00 00 00 00</a>
                                </div>
                                <div>
                                    <i class="fas fa-clock me-2"></i>
                                    Lun-Ven: 8h-18h
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== GESTION DU TIMER UNIFIÉ =====
    
    @if(isset($isDirectBooking) && $isDirectBooking && isset($timeRemaining))
        // Timer pour réservation directe (1 heure)
        let timeLeft = {{ $timeRemaining * 60 }};
        
        function updateReservationTimer() {
            const hours = Math.floor(timeLeft / 3600);
            const minutes = Math.floor((timeLeft % 3600) / 60);
            const seconds = timeLeft % 60;
            
            let display = '';
            if (hours > 0) {
                display = `${hours}h ${minutes}m ${seconds}s`;
            } else if (minutes > 0) {
                display = `${minutes}m ${seconds.toString().padStart(2, '0')}s`;
            } else {
                display = `${seconds}s`;
            }
            
            const timerDisplay = document.getElementById('timer-display');
            if (timerDisplay) {
                timerDisplay.textContent = display;
            }
            
            // Alertes progressives
            if (timeLeft === 600) { // 10 minutes
                alert('⚠️ Plus que 10 minutes pour finaliser votre réservation !');
            } else if (timeLeft === 300) { // 5 minutes
                alert('🚨 Plus que 5 minutes ! Dépêchez-vous !');
            } else if (timeLeft === 60) { // 1 minute
                alert('🔥 Plus qu\'1 minute ! Votre réservation va expirer !');
            }
            
            if (timeLeft <= 0) {
                alert('⏰ Votre réservation a expiré. Redirection...');
                window.location.href = '{{ route("home") }}';
                return;
            }
            
            timeLeft--;
        }
        
        setInterval(updateReservationTimer, 1000);
        
    @elseif(isset($timeRemaining) && $timeRemaining)
        // Timer pour panier classique (15 minutes)
        let cartTimeLeft = {{ $timeRemaining * 60 }};
        
        function updateCartTimer() {
            const minutes = Math.floor(cartTimeLeft / 60);
            const seconds = cartTimeLeft % 60;
            
            const timerDisplay = document.getElementById('cart-timer-display');
            if (timerDisplay) {
                timerDisplay.textContent = `${minutes}m ${seconds.toString().padStart(2, '0')}s`;
            }
            
            if (cartTimeLeft <= 0) {
                alert('⏰ Votre panier a expiré. Redirection...');
                window.location.href = '{{ route("cart.show") }}';
                return;
            }
            
            cartTimeLeft--;
        }
        
        setInterval(updateCartTimer, 1000);
    @endif
    
    // ===== VALIDATION DU FORMULAIRE (identique à votre code) =====
    const form = document.getElementById('checkout-form');
    const submitBtn = form.querySelector('button[type="submit"]');
    
    form.addEventListener('submit', function(e) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Traitement en cours...';
        
        // Validation côté client...
        const email = document.getElementById('billing_email').value;
        const phone = document.getElementById('billing_phone').value;
        const terms = document.getElementById('terms_accepted').checked;
        
        if (!email || !phone || !terms) {
            e.preventDefault();
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-lock me-2"></i>Confirmer ma commande';
            alert('Veuillez remplir tous les champs obligatoires.');
            return false;
        }
    });
});
</script>
@endpush