@extends('layouts.app')

@section('title', 'Mon Panier')

@push('styles')
<style>
/* ===== VARIABLES CSS ===== */
:root {
    --primary-orange: #FF6B35;
    --primary-dark: #E55A2B;
    --success-color: #28a745;
    --danger-color: #dc3545;
    --warning-color: #ffc107;
    --black-primary: #2c3e50;
    --border-color: #e9ecef;
    --light-gray: #f8f9fa;
    --shadow-light: 0 2px 10px rgba(0,0,0,0.1);
    --shadow-medium: 0 4px 20px rgba(0,0,0,0.15);
    --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* ===== LAYOUT DE BASE ===== */
.cart-container {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    min-height: calc(100vh - 100px);
    padding: 2rem 0;
}

/* ===== HEADER DU PANIER ===== */
.cart-header {
    background: linear-gradient(135deg, var(--primary-orange), var(--primary-dark));
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    text-align: center;
    box-shadow: var(--shadow-medium);
}

.cart-header h1 {
    margin-bottom: 0.5rem;
    font-weight: 700;
}

.cart-header p {
    margin-bottom: 0;
    opacity: 0.9;
}

/* ===== ÉTAPES DU PROCESSUS ===== */
.cart-steps {
    display: flex;
    justify-content: center;
    align-items: center;
    background: white;
    padding: 1.5rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: var(--shadow-light);
    gap: 2rem;
}

.step {
    display: flex;
    align-items: center;
    color: #6c757d;
    transition: var(--transition-smooth);
}

.step.active {
    color: var(--primary-orange);
    font-weight: 600;
}

.step-number {
    background: #e9ecef;
    color: #6c757d;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-right: 0.75rem;
    transition: var(--transition-smooth);
}

.step.active .step-number {
    background: var(--primary-orange);
    color: white;
    transform: scale(1.1);
}

/* ===== CARTES DE CONTENU ===== */
.cart-card {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: var(--shadow-light);
    margin-bottom: 2rem;
    border: 1px solid var(--border-color);
    transition: var(--transition-smooth);
}

.cart-card:hover {
    box-shadow: var(--shadow-medium);
    border-color: var(--primary-orange);
}

/* ===== ARTICLES DU PANIER ===== */
.cart-item {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow-light);
    transition: var(--transition-smooth);
}

.cart-item:hover {
    box-shadow: var(--shadow-medium);
    border-color: var(--primary-orange);
}

.cart-item:last-child {
    margin-bottom: 0;
}

.event-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.event-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--black-primary);
    margin-bottom: 0.5rem;
}

.ticket-info {
    font-size: 0.9rem;
    color: #6c757d;
}

.price-info {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--primary-orange);
}

/* ===== QUANTITÉ ===== */
.quantity-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: var(--light-gray);
    border-radius: 20px;
    padding: 0.25rem;
}

.qty-btn {
    width: 35px;
    height: 35px;
    border: none;
    border-radius: 50%;
    background: var(--primary-orange);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition-smooth);
    cursor: pointer;
    font-size: 0.9rem;
}

.qty-btn:hover:not(:disabled) {
    background: var(--primary-dark);
    transform: scale(1.1);
}

.qty-btn:disabled {
    background: #dee2e6;
    cursor: not-allowed;
}

.qty-display {
    min-width: 40px;
    text-align: center;
    font-weight: bold;
    font-size: 1rem;
}

/* ===== RÉSUMÉ TOTAL ===== */
.total-summary {
    background: linear-gradient(135deg, #fff3e0, #ffe0b2);
    padding: 1.5rem;
    border-radius: 15px;
    border: 1px solid #ffcc02;
    position: sticky;
    top: 2rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(0,0,0,0.1);
}

.summary-row:last-child {
    border-bottom: none;
    font-size: 1.2rem;
    font-weight: bold;
    color: var(--primary-orange);
}

/* ===== BOUTONS ===== */
.btn-primary {
    background: linear-gradient(135deg, var(--primary-orange), var(--primary-dark)) !important;
    border: none !important;
    color: white !important;
    font-weight: 600;
    border-radius: 12px;
    padding: 12px 24px;
    transition: var(--transition-smooth);
    box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 107, 53, 0.4);
    color: white !important;
}

.btn-outline-primary {
    border: 2px solid var(--primary-orange) !important;
    color: var(--primary-orange) !important;
    background: transparent !important;
    font-weight: 600;
    border-radius: 12px;
    padding: 12px 24px;
    transition: var(--transition-smooth);
}

.btn-outline-primary:hover {
    background: var(--primary-orange) !important;
    border-color: var(--primary-orange) !important;
    color: white !important;
    transform: translateY(-2px);
}

.btn-outline-success {
    border: 2px solid var(--success-color) !important;
    color: var(--success-color) !important;
    background: transparent !important;
    font-weight: 600;
    border-radius: 12px;
    padding: 12px 24px;
    transition: var(--transition-smooth);
}

.btn-outline-success:hover {
    background: var(--success-color) !important;
    border-color: var(--success-color) !important;
    color: white !important;
    transform: translateY(-2px);
}

.btn-danger {
    background: var(--danger-color) !important;
    border: none !important;
    color: white !important;
    border-radius: 8px;
    transition: var(--transition-smooth);
}

.btn-danger:hover {
    background: #c82333 !important;
    transform: scale(1.05);
    color: white !important;
}

/* ===== CHOIX DE CHECKOUT ===== */
.checkout-options {
    background: linear-gradient(135deg, #e3f2fd, #bbdefb);
    border-radius: 15px;
    padding: 1.5rem;
    margin-top: 2rem;
    border: 1px solid #2196f3;
}

.checkout-options h6 {
    color: #1976d2;
    font-weight: 600;
    margin-bottom: 1rem;
}

.checkout-option {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 1rem;
    transition: var(--transition-smooth);
    text-decoration: none;
    display: block;
    margin-bottom: 1rem;
}

.checkout-option:hover {
    border-color: var(--primary-orange);
    transform: translateY(-2px);
    box-shadow: var(--shadow-medium);
    text-decoration: none;
}

.option-icon {
    font-size: 1.2rem;
    margin-bottom: 0.5rem;
}

.option-title {
    font-weight: 600;
    color: var(--black-primary);
    margin-bottom: 0.25rem;
}

.option-description {
    font-size: 0.9rem;
    color: #6c757d;
    margin: 0;
}

/* ===== PANIER VIDE ===== */
.empty-cart {
    text-align: center;
    padding: 3rem 2rem;
}

.empty-cart-icon {
    font-size: 4rem;
    color: #dee2e6;
    margin-bottom: 1rem;
}

.empty-cart h3 {
    color: var(--black-primary);
    margin-bottom: 1rem;
}

.empty-cart p {
    color: #6c757d;
    margin-bottom: 2rem;
}

/* ===== ALERTES ===== */
.alert {
    border-radius: 12px;
    border: none;
    box-shadow: var(--shadow-light);
}

.alert-success {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    color: #155724;
}

.alert-warning {
    background: linear-gradient(135deg, #fff3cd, #ffeaa7);
    color: #856404;
}

.alert-danger {
    background: linear-gradient(135deg, #f8d7da, #f5c6cb);
    color: #721c24;
}

/* ===== BOTTOM MODAL MOBILE ===== */
.cart-bottom-modal {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: white;
    border-radius: 20px 20px 0 0;
    box-shadow: 0 -10px 30px rgba(0,0,0,0.3);
    transform: translateY(100%);
    transition: transform 0.3s ease;
    z-index: 1060;
    max-height: 85vh;
    overflow-y: auto;
}

.cart-bottom-modal.show {
    transform: translateY(0);
}

.cart-modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1059;
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
}

.cart-modal-backdrop.show {
    opacity: 1;
    pointer-events: all;
}

.cart-modal-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: linear-gradient(135deg, var(--primary-orange), var(--primary-dark));
    color: white;
    border-radius: 20px 20px 0 0;
    position: relative;
}

.cart-modal-header::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 2px;
    background: rgba(255,255,255,0.3);
    border-radius: 1px;
}

.cart-modal-handle {
    width: 40px;
    height: 4px;
    background: rgba(255,255,255,0.5);
    border-radius: 2px;
    margin: 0.75rem auto 0;
    cursor: grab;
}

.cart-modal-body {
    padding: 1rem 1.5rem;
    max-height: 60vh;
    overflow-y: auto;
}

.cart-modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #e9ecef;
    background: #f8f9fa;
}

/* Mobile Cart Item */
.mobile-cart-item {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: opacity 0.3s ease;
}

.mobile-cart-item.updating {
    opacity: 0.6;
}

.mobile-cart-item:last-child {
    margin-bottom: 0;
}

.mobile-item-header {
    display: flex;
    align-items: center;
    margin-bottom: 0.75rem;
}

.mobile-event-image {
    width: 50px;
    height: 50px;
    border-radius: 8px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    margin-right: 0.75rem;
}

.mobile-event-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.mobile-event-image i {
    font-size: 1.2rem;
    color: var(--primary-orange);
}

.mobile-event-info {
    flex: 1;
}

.mobile-event-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--black-primary);
    margin-bottom: 0.25rem;
}

.mobile-ticket-info {
    font-size: 0.8rem;
    color: #6c757d;
}

.mobile-venue-info {
    font-size: 0.75rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

.mobile-item-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.mobile-quantity-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #f8f9fa;
    border-radius: 15px;
    padding: 0.25rem;
}

.mobile-qty-btn {
    width: 30px;
    height: 30px;
    border: none;
    border-radius: 50%;
    background: var(--primary-orange);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    transition: var(--transition-smooth);
}

.mobile-qty-btn:hover:not(:disabled) {
    background: var(--primary-dark);
    transform: scale(1.05);
}

.mobile-qty-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.mobile-qty-display {
    min-width: 30px;
    text-align: center;
    font-weight: bold;
    font-size: 0.9rem;
}

.mobile-item-actions {
    display: flex;
    align-items: center;
    flex-direction: column;
    gap: 0.25rem;
}

.mobile-price {
    font-size: 1rem;
    font-weight: 600;
    color: var(--primary-orange);
}

/* Mobile Summary */
.mobile-summary {
    background: linear-gradient(135deg, #fff3e0, #ffe0b2);
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.mobile-summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.mobile-summary-row:last-child {
    margin-bottom: 0;
    font-weight: bold;
    color: var(--primary-orange);
    border-top: 1px solid rgba(0,0,0,0.1);
    padding-top: 0.5rem;
}

/* Mobile Actions */
.mobile-cart-actions {
    display: flex;
    gap: 0.5rem;
}

.mobile-cart-actions .btn {
    flex: 1;
    border-radius: 12px;
    padding: 0.75rem;
    font-weight: 600;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .cart-container {
        margin-top: 80px;
        padding: 0 15px;
        padding-bottom: 100px; /* Espace pour le bouton flottant */
    }
    
    .cart-card, .total-summary {
        display: none; /* Cacher le contenu desktop */
    }
    
    .cart-header {
        padding: 1.5rem;
        text-align: center;
    }
    
    .cart-header h1 {
        font-size: 1.5rem;
    }
    
    .cart-steps {
        padding: 0.75rem;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .step span:last-child {
        font-size: 0.7rem;
    }
    
    /* Bouton flottant mobile */
    .mobile-cart-button {
        position: fixed;
        bottom: 20px;
        left: 20px;
        right: 20px;
        background: linear-gradient(135deg, var(--primary-orange), var(--primary-dark));
        color: white;
        border: none;
        border-radius: 15px;
        padding: 1rem;
        font-size: 1.1rem;
        font-weight: 600;
        box-shadow: 0 4px 20px rgba(255, 107, 53, 0.4);
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        cursor: pointer;
        transition: var(--transition-smooth);
    }
    
    .mobile-cart-button:hover {
        color: white;
        transform: translateY(-2px);
    }
    
    .checkout-options .row {
        flex-direction: column;
    }
}

@media (min-width: 769px) {
    .cart-bottom-modal, .mobile-cart-button {
        display: none !important;
    }
}

@media (max-width: 350px) {
    .mobile-cart-actions {
        flex-direction: column;
    }
    
    .mobile-event-title {
        font-size: 0.8rem;
    }
    
    .mobile-ticket-info {
        font-size: 0.7rem;
    }
}

/* Animation de suppression */
@keyframes slideOut {
    from {
        opacity: 1;
        transform: translateX(0);
    }
    to {
        opacity: 0;
        transform: translateX(100%);
    }
}
</style>
@endpush

@section('content')
<div class="container cart-container">
    {{-- Header du panier --}}
    <div class="cart-header">
        <h1><i class="fas fa-shopping-cart me-3"></i>Mon Panier</h1>
        <p>Vérifiez vos billets avant de procéder au paiement</p>
    </div>

    {{-- Étapes du processus --}}
    <div class="cart-steps">
        <div class="step active">
            <div class="step-number">1</div>
            <span>Panier</span>
        </div>
        <div class="step">
            <div class="step-number">2</div>
            <span>Informations</span>
        </div>
        <div class="step">
            <div class="step-number">3</div>
            <span>Confirmation</span>
        </div>
    </div>

    {{-- Contenu principal --}}
    @if(empty($cart))
        {{-- Panier vide --}}
        <div class="cart-card">
            <div class="empty-cart">
                <div class="empty-cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3>Votre panier est vide</h3>
                <p>Découvrez nos événements exceptionnels et ajoutez vos billets préférés !</p>
                <a href="{{ route('events.all') }}" class="btn btn-primary">
                    <i class="fas fa-search me-2"></i>Découvrir les événements
                </a>
            </div>
        </div>
    @else
        <div class="row">
            {{-- Contenu du panier (Desktop uniquement) --}}
            <div class="col-lg-8 d-none d-lg-block">
                <div class="cart-card">
                    <h4 class="mb-4">
                        <i class="fas fa-list-ul me-2"></i>
                        Articles sélectionnés ({{ $cartCount }})
                    </h4>

                    @foreach($cart as $key => $item)
                        <div class="cart-item" id="cart-item-{{ $key }}">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        @php
                                            $event = \App\Models\Event::find($item['event_id'] ?? null);
                                            $eventImage = $event && $event->image ? Storage::url($event->image) : null;
                                        @endphp
                                        
                                        <div class="me-3">
                                            @if($eventImage)
                                                <img src="{{ $eventImage }}" alt="{{ $item['event_title'] }}" class="event-image">
                                            @else
                                                <div class="event-image d-flex align-items-center justify-content-center bg-light">
                                                    <i class="fas fa-calendar-alt text-primary fa-2x"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div>
                                            <h6 class="event-title mb-1">{{ $item['event_title'] }}</h6>
                                            <div class="ticket-info">
                                                <div><strong>{{ $item['ticket_name'] }}</strong></div>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>{{ $item['event_date'] ?? 'Date à confirmer' }}
                                                </small><br>
                                                <small class="text-muted">
                                                    <i class="fas fa-map-marker-alt me-1"></i>{{ $item['event_venue'] ?? 'Lieu à confirmer' }}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-2 text-center">
                                    <div class="price-info">{{ number_format($item['unit_price']) }} FCFA</div>
                                </div>

                                <div class="col-md-2 text-center">
                                    <div class="quantity-controls">
                                        <button class="qty-btn" onclick="updateQuantity('{{ $key }}', {{ $item['quantity'] - 1 }})" {{ $item['quantity'] <= 1 ? 'disabled' : '' }}>
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <span class="qty-display" id="qty-{{ $key }}">{{ $item['quantity'] }}</span>
                                        <button class="qty-btn" onclick="updateQuantity('{{ $key }}', {{ $item['quantity'] + 1 }})">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="col-md-2 text-center">
                                    <div class="price-info mb-2">{{ number_format($item['total_price']) }} FCFA</div>
                                    <button class="btn btn-outline-danger btn-sm" onclick="removeFromCart('{{ $key }}')">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    {{-- Actions du panier --}}
                    <div class="d-flex justify-content-between align-items-center mt-4 pt-4 border-top">
                        <button class="btn btn-outline-danger" onclick="clearCart()">
                            <i class="fas fa-trash me-2"></i>Vider le panier
                        </button>
                        <a href="{{ route('events.all') }}" class="btn btn-outline-primary">
                            <i class="fas fa-plus me-2"></i>Ajouter d'autres billets
                        </a>
                    </div>
                </div>
            </div>

            {{-- Résumé et checkout (Desktop uniquement) --}}
            <div class="col-lg-4 d-none d-lg-block">
                <div class="total-summary">
                    <h5 class="mb-4">
                        <i class="fas fa-calculator me-2"></i>Résumé de la commande
                    </h5>

                    <div class="summary-row">
                        <span>Sous-total</span>
                     <span>{{ number_format($cartTotal) }} FCFA</span>
                    </div>
                        @if($cartTotal > 0)
                    <div class="summary-row">
                    <span>Frais de service</span>
                        <span>500 FCFA</span>
                    </div>
                    @endif
                    <div class="summary-row">
                <span><strong>Total</strong></span>
                <span><strong>{{ number_format($cartTotal + ($cartTotal > 0 ? 500 : 0)) }} FCFA</strong></span>
                        </div>
                    {{-- Boutons de checkout selon le statut --}}
                    <div class="d-grid mt-4">
                        @guest
                            {{-- Pour les visiteurs non connectés --}}
                            <a href="{{ route('checkout.guest.show') }}" class="btn btn-primary btn-lg mb-3">
                                <i class="fas fa-bolt me-2"></i>
                                Commande Express
                            </a>
                            
                            <div class="text-center mb-3">
                                <small class="text-muted">
                                    Vous avez déjà un compte ? 
                                    <a href="{{ route('login') }}?redirect={{ urlencode(route('checkout.show')) }}" class="text-primary">
                                        Connectez-vous
                                    </a>
                                </small>
                            </div>
                        @else
                            {{-- Pour les utilisateurs connectés --}}
                            <a href="{{ route('checkout.show') }}" class="btn btn-primary btn-lg">
                                <i class="fas fa-credit-card me-2"></i>
                                Passer commande
                            </a>
                        @endguest
                    </div>

                    {{-- Informations de sécurité --}}
                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            <i class="fas fa-shield-alt me-1 text-success"></i>
                            Paiement 100% sécurisé
                        </small>
                    </div>
                </div>

                {{-- Options de checkout détaillées pour les invités --}}
                @guest
                <div class="checkout-options">
                    <h6 class="text-center mb-3">Comment souhaitez-vous procéder ?</h6>
                    
                    <div class="row">
                        <div class="col-md-12 mb-2">
                            <a href="{{ route('checkout.guest.show') }}" class="checkout-option">
                                <div class="option-icon text-warning">
                                    <i class="fas fa-bolt"></i>
                                </div>
                                <div class="option-title">Commande express</div>
                                <div class="option-description">Rapide et simple, sans créer de compte</div>
                            </a>
                        </div>
                        <div class="col-md-12 mb-2">
                            <a href="{{ route('login') }}?redirect={{ urlencode(route('checkout.show')) }}" class="checkout-option">
                                <div class="option-icon text-success">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="option-title">Avec mon compte</div>
                                <div class="option-description">Connexion ou création de compte</div>
                            </a>
                        </div>
                    </div>
                </div>
                @endguest
            </div>
        </div>
    @endif
</div>

{{-- Bouton flottant mobile pour ouvrir le panier --}}
@if(!empty($cart))
<button class="mobile-cart-button d-lg-none" onclick="openMobileCartModal()">
    <i class="fas fa-shopping-cart me-2"></i>
    Mon Panier ({{ $cartCount }})
    <span class="ms-2">{{ number_format($cartTotal) }} FCFA</span>
</button>
@endif

{{-- Modal Backdrop --}}
<div class="cart-modal-backdrop" id="mobileCartBackdrop" onclick="closeMobileCartModal()"></div>

{{-- Bottom Modal Amélioré --}}
<div class="cart-bottom-modal" id="mobileCartModal">
    {{-- Handle de glissement --}}
    <div class="cart-modal-handle"></div>
    
    {{-- Header avec informations du panier --}}
    <div class="cart-modal-header">
        <div class="d-flex align-items-center">
            <i class="fas fa-shopping-cart me-2"></i>
            <div>
                <h5 class="mb-0">Mon Panier</h5>
                <small class="opacity-75">{{ $cartCount ?? 0 }} article{{ ($cartCount ?? 0) > 1 ? 's' : '' }}</small>
            </div>
        </div>
        <button type="button" class="btn btn-link text-white p-0" onclick="closeMobileCartModal()">
            <i class="fas fa-times fa-lg"></i>
        </button>
    </div>
    
    {{-- Corps du modal --}}
    <div class="cart-modal-body">
        @if(empty($cart))
            <div class="text-center py-4">
                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">Panier vide</h6>
                <p class="text-muted small">Découvrez nos événements</p>
                <a href="{{ route('events.all') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-calendar me-2"></i>Voir les événements
                </a>
            </div>
        @else
            {{-- Articles du panier optimisés --}}
            <div id="mobileCartItems">
                @foreach($cart as $key => $item)
                <div class="mobile-cart-item" id="mobile-item-{{ $key }}">
                    <div class="mobile-item-header">
                        <div class="mobile-event-image">
                            @php
                                $event = \App\Models\Event::find($item['event_id'] ?? null);
                                $eventImage = $event && $event->image ? Storage::url($event->image) : null;
                            @endphp
                            
                            @if($eventImage)
                                <img src="{{ $eventImage }}" alt="{{ $item['event_title'] }}">
                            @else
                                <i class="fas fa-calendar-alt"></i>
                            @endif
                        </div>
                        <div class="mobile-event-info">
                            <div class="mobile-event-title">{{ $item['event_title'] }}</div>
                            <div class="mobile-ticket-info">
                                {{ $item['ticket_name'] }} • {{ $item['event_date'] ?? 'Date à confirmer' }}
                            </div>
                            <div class="mobile-venue-info">
                                <i class="fas fa-map-marker-alt me-1"></i>{{ $item['event_venue'] ?? 'Lieu à confirmer' }}
                            </div>
                        </div>
                    </div>
                    
                    <div class="mobile-item-controls">
                        {{-- Contrôles de quantité améliorés --}}
                        <div class="mobile-quantity-controls">
                            <button class="mobile-qty-btn" 
                                    onclick="updateCartQuantity('{{ $key }}', {{ $item['quantity'] - 1 }})"
                                    {{ $item['quantity'] <= 1 ? 'disabled' : '' }}>
                                <i class="fas fa-minus"></i>
                            </button>
                            <span class="mobile-qty-display" id="mobile-qty-{{ $key }}">{{ $item['quantity'] }}</span>
                            <button class="mobile-qty-btn" 
                                    onclick="updateCartQuantity('{{ $key }}', {{ $item['quantity'] + 1 }})">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        
                        <div class="mobile-item-actions">
                            <div class="mobile-price">{{ number_format($item['total_price']) }} FCFA</div>
                            <button class="btn btn-link text-danger p-0 ms-2" 
                                    onclick="removeFromCart('{{ $key }}')"
                                    title="Supprimer">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            
            {{-- Actions rapides --}}
            <div class="mobile-cart-actions mt-3">
                <button class="btn btn-outline-danger btn-sm" onclick="clearCart()">
                    <i class="fas fa-trash me-2"></i>Vider le panier
                </button>
                <a href="{{ route('events.all') }}" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-plus me-2"></i>Ajouter des billets
                </a>
            </div>
        @endif
    </div>

    {{-- Footer avec résumé et action principale --}}
    @if(!empty($cart))
    <div class="cart-modal-footer">
        <div class="mobile-summary">
            <div class="mobile-summary-row">
    <span>Sous-total</span>
    <span id="mobileSubtotal">{{ number_format($cartTotal) }} FCFA</span>
</div>
@if($cartTotal > 0)
<div class="mobile-summary-row">
    <span>Frais de service</span>
    <span>500 FCFA</span>
</div>
@endif
<div class="mobile-summary-row">
    <span><strong>Total</strong></span>
    <span><strong id="mobileTotal">{{ number_format($cartTotal + ($cartTotal > 0 ? 500 : 0)) }} FCFA</strong></span>
</div>
        </div>
        
        {{-- Boutons d'action selon le statut de connexion --}}
        <div class="mobile-cart-actions mt-3">
            @guest
                {{-- Pour les visiteurs --}}
                <a href="{{ route('checkout.guest.show') }}" class="btn btn-primary flex-fill me-2">
                    <i class="fas fa-bolt me-2"></i>Commande Express
                </a>
                <a href="{{ route('login') }}?redirect={{ urlencode(route('checkout.show')) }}" class="btn btn-outline-success flex-fill">
                    <i class="fas fa-user me-2"></i>Me connecter
                </a>
            @else
                {{-- Pour les utilisateurs connectés --}}
                <a href="{{ route('checkout.show') }}" class="btn btn-primary w-100">
                    <i class="fas fa-credit-card me-2"></i>Passer commande
                </a>
            @endguest
        </div>
        
        {{-- Informations de sécurité --}}
        <div class="text-center mt-2">
            <small class="text-muted">
                <i class="fas fa-shield-alt me-1 text-success"></i>
                Paiement sécurisé • Billets instantanés
            </small>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
/**
 * Système de gestion du panier complet avec support mobile
 */

// État de chargement global
let isUpdating = false;

/**
 * Mettre à jour la quantité (Desktop)
 */
async function updateQuantity(cartKey, newQuantity) {
    if (isUpdating || newQuantity < 1) return;
    
    isUpdating = true;
    const itemElement = document.getElementById(`cart-item-${cartKey}`);
    const qtyDisplay = document.getElementById(`qty-${cartKey}`);
    
    // Feedback visuel
    if (itemElement) itemElement.style.opacity = '0.6';
    
    try {
        const response = await fetch('{{ route("cart.update") }}', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                cart_key: cartKey,
                quantity: newQuantity
            })
        });

        const data = await response.json();

        if (data.success) {
            // Mettre à jour l'affichage desktop
            if (qtyDisplay) qtyDisplay.textContent = newQuantity;
            
            // Mettre à jour les totaux
            updateDesktopTotals(data);
            
            // Notification de succès
            showToast('Quantité mise à jour', 'success', 2000);
        } else {
            throw new Error(data.message || 'Erreur de mise à jour');
        }

    } catch (error) {
        console.error('Erreur:', error);
        showToast('Erreur lors de la mise à jour', 'error');
    } finally {
        if (itemElement) itemElement.style.opacity = '1';
        isUpdating = false;
    }
}

/**
 * Mettre à jour la quantité (Mobile)
 */
async function updateCartQuantity(cartKey, newQuantity) {
    if (isUpdating || newQuantity < 1) return;
    
    isUpdating = true;
    const itemElement = document.getElementById(`mobile-item-${cartKey}`);
    const qtyDisplay = document.getElementById(`mobile-qty-${cartKey}`);
    
    // Feedback visuel
    itemElement.classList.add('updating');
    
    try {
        const response = await fetch('{{ route("cart.update") }}', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                cart_key: cartKey,
                quantity: newQuantity
            })
        });

        const data = await response.json();

        if (data.success) {
            // Mettre à jour l'affichage mobile
            qtyDisplay.textContent = newQuantity;
            updateMobileTotals(data);
            
            // Notification de succès discrète
            showToast('Quantité mise à jour', 'success', 2000);
        } else {
            throw new Error(data.message || 'Erreur de mise à jour');
        }

    } catch (error) {
        console.error('Erreur:', error);
        showToast('Erreur lors de la mise à jour', 'error');
    } finally {
        itemElement.classList.remove('updating');
        isUpdating = false;
    }
}

/**
 * Supprimer un item du panier
 */
async function removeFromCart(cartKey) {
    if (!confirm('Supprimer cet article du panier ?')) return;
    
    isUpdating = true;
    const desktopItem = document.getElementById(`cart-item-${cartKey}`);
    const mobileItem = document.getElementById(`mobile-item-${cartKey}`);
    
    try {
        const response = await fetch('{{ route("cart.remove") }}', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ cart_key: cartKey })
        });

        const data = await response.json();

        if (data.success) {
            // Animation de suppression desktop
            if (desktopItem) {
                desktopItem.style.animation = 'slideOut 0.3s ease-out forwards';
                setTimeout(() => desktopItem.remove(), 300);
            }
            
            // Animation de suppression mobile
            if (mobileItem) {
                mobileItem.style.animation = 'slideOut 0.3s ease-out forwards';
                setTimeout(() => mobileItem.remove(), 300);
            }
            
            // Mettre à jour les totaux
            updateDesktopTotals(data);
            updateMobileTotals(data);
            
            showToast('Article supprimé', 'success');
            
            // Recharger si panier vide
            if (data.cart_count === 0) {
                setTimeout(() => location.reload(), 1000);
            }

        } else {
            throw new Error(data.message || 'Erreur de suppression');
        }

    } catch (error) {
        console.error('Erreur:', error);
        showToast('Erreur lors de la suppression', 'error');
    } finally {
        isUpdating = false;
    }
}

/**
 * Vider tout le panier
 */
async function clearCart() {
    if (!confirm('Vider complètement votre panier ?')) return;
    
    try {
        const response = await fetch('{{ route("cart.clear") }}', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            showToast('Panier vidé', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            throw new Error(data.message || 'Erreur');
        }

    } catch (error) {
        console.error('Erreur:', error);
        showToast('Erreur lors du vidage', 'error');
    }
}

/**
 * Mettre à jour les totaux desktop
 */
function updateDesktopTotals(data) {
    if (data.cart_total !== undefined) {
        const subtotal = data.cart_total;
        const total = subtotal + 500;
        
        // Mettre à jour les éléments de résumé si ils existent
        document.querySelectorAll('.total-summary .summary-row').forEach((row, index) => {
            const span = row.querySelector('span:last-child');
            if (index === 0 && span) { // Sous-total
                span.textContent = subtotal.toLocaleString() + ' FCFA';
            } else if (index === 2 && span) { // Total
                span.textContent = total.toLocaleString() + ' FCFA';
            }
        });
    }
}

/**
 * Mettre à jour les totaux mobiles
 */
function updateMobileTotals(data) {
    const subtotalElement = document.getElementById('mobileSubtotal');
    const totalElement = document.getElementById('mobileTotal');
    const cartButton = document.querySelector('.mobile-cart-button');
    
    if (subtotalElement && data.cart_total !== undefined) {
        const subtotal = data.cart_total;
        const total = subtotal + 500;
        
        subtotalElement.textContent = subtotal.toLocaleString() + ' FCFA';
        totalElement.textContent = total.toLocaleString() + ' FCFA';
        
        // Mettre à jour le bouton flottant
        if (cartButton && data.cart_count !== undefined) {
            cartButton.innerHTML = `
                <i class="fas fa-shopping-cart me-2"></i>
                Mon Panier (${data.cart_count})
                <span class="ms-2">${subtotal.toLocaleString()} FCFA</span>
            `;
        }
    }
}

/**
 * Ouvrir le modal mobile du panier
 */
function openMobileCartModal() {
    const modal = document.getElementById('mobileCartModal');
    const backdrop = document.getElementById('mobileCartBackdrop');
    
    if (modal && backdrop) {
        backdrop.classList.add('show');
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

/**
 * Fermer le modal mobile du panier
 */
function closeMobileCartModal() {
    const modal = document.getElementById('mobileCartModal');
    const backdrop = document.getElementById('mobileCartBackdrop');
    
    if (modal && backdrop) {
        backdrop.classList.remove('show');
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
}

/**
 * Toast notification système
 */
function showToast(message, type = 'info', duration = 3000) {
    if (window.notifications) {
        window.notifications[type](message, { duration });
    } else {
        // Fallback simple
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const toast = document.createElement('div');
        toast.className = `alert ${alertClass} position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 250px; border-radius: 10px;';
        toast.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
            ${message}
        `;
        document.body.appendChild(toast);
        
        setTimeout(() => toast.remove(), duration);
    }
}

// Gestion du swipe pour fermer le modal mobile
let startY = 0;
let currentY = 0;
let isDragging = false;

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('mobileCartModal');
    
    if (modal) {
        // Touch events pour le swipe
        modal.addEventListener('touchstart', function(e) {
            if (e.target.closest('.cart-modal-header') || e.target.closest('.cart-modal-handle')) {
                startY = e.touches[0].clientY;
                isDragging = true;
            }
        }, { passive: true });
        
        modal.addEventListener('touchmove', function(e) {
            if (!isDragging) return;
            
            currentY = e.touches[0].clientY;
            const deltaY = currentY - startY;
            
            if (deltaY > 0) {
                modal.style.transform = `translateY(${deltaY}px)`;
            }
        }, { passive: true });
        
        modal.addEventListener('touchend', function(e) {
            if (!isDragging) return;
            
            const deltaY = currentY - startY;
            
            if (deltaY > 100) {
                closeMobileCartModal();
            } else {
                modal.style.transform = '';
            }
            
            isDragging = false;
        }, { passive: true });
    }
    
    // Gestion du clavier pour accessibilité
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeMobileCartModal();
        }
    });
});

// Debug : Afficher les routes disponibles
console.log('🛒 Système de panier initialisé');
console.log('Routes:', {
    update: '{{ route("cart.update") }}',
    remove: '{{ route("cart.remove") }}',
    clear: '{{ route("cart.clear") }}'
});
</script>
@endpush