{{-- resources/views/cart/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Mon Panier - ClicBillet CI')

@push('styles')
<style>
:root {
    --primary-orange: #FF6B35;
    --secondary-orange: #ff8c61;
    --primary-dark: #E55A2B;
    --dark-blue: #1a237e;
    --black-primary: #2c3e50;
    --border-color: #e9ecef;
    --light-gray: #f8f9fa;
    --success-color: #28a745;
    --warning-color: #ffc107;
    --danger-color: #dc3545;
    --shadow-light: 0 2px 10px rgba(0,0,0,0.08);
    --shadow-medium: 0 4px 20px rgba(0,0,0,0.12);
    --transition-smooth: all 0.3s ease;
}

body {
    background-color: var(--light-gray);
}

/* ===== CONTENEUR PRINCIPAL ===== */
.cart-container {
    margin-top: 100px; /* Compense le header */
    margin-bottom: 3rem;
}

/* ===== HEADER PANIER ===== */
.cart-header {
    background: linear-gradient(135deg, var(--primary-orange), var(--primary-dark));
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: var(--shadow-medium);
}

.cart-header h1 {
    margin-bottom: 0.5rem;
    font-size: 2rem;
    font-weight: 700;
}

.cart-header p {
    margin-bottom: 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

/* ===== ÉTAPES DU PROCESSUS ===== */
.cart-steps {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 2rem;
    background: white;
    padding: 1rem;
    border-radius: 15px;
    box-shadow: var(--shadow-light);
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    flex: 1;
    max-width: 120px;
}

.step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 15px;
    right: -50%;
    width: 100%;
    height: 2px;
    background: #e9ecef;
    z-index: 1;
}

.step.active:not(:last-child)::after {
    background: var(--primary-orange);
}

.step-number {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-bottom: 0.5rem;
    position: relative;
    z-index: 2;
}

.step.active .step-number {
    background: var(--primary-orange);
    color: white;
}

.step span:last-child {
    font-size: 0.8rem;
    color: #6c757d;
    text-align: center;
}

.step.active span:last-child {
    color: var(--primary-orange);
    font-weight: 600;
}

/* ===== CARTES PRINCIPALES ===== */
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
}

.cart-modal-handle {
    width: 40px;
    height: 4px;
    background: rgba(255,255,255,0.5);
    border-radius: 2px;
    margin: 0 auto 1rem;
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
    object-fit: cover;
    border-radius: 8px;
    margin-right: 0.75rem;
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
}

.mobile-qty-display {
    min-width: 30px;
    text-align: center;
    font-weight: bold;
    font-size: 0.9rem;
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
    gap: 0.75rem;
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
    }
    
    .mobile-cart-button:hover {
        color: white;
        transform: translateY(-2px);
    }
}

@media (min-width: 769px) {
    .cart-bottom-modal, .mobile-cart-button {
        display: none !important;
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
            <span>Checkout</span>
        </div>
        <div class="step">
            <div class="step-number">3</div>
            <span>Paiement</span>
        </div>
        <div class="step">
            <div class="step-number">4</div>
            <span>Confirmation</span>
        </div>
    </div>

    {{-- Messages flash --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(empty($cart))
        {{-- Panier vide --}}
        <div class="cart-card">
            <div class="empty-cart">
                <div class="empty-cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3>Votre panier est vide</h3>
                <p>Découvrez nos événements et commencez à ajouter des billets à votre panier.</p>
                <a href="{{ route('events.all') }}" class="btn btn-primary btn-lg">
                    <i class="fas fa-calendar-alt me-2"></i>
                    Découvrir les événements
                </a>
            </div>
        </div>
    @else
        <div class="row">
            {{-- Articles du panier --}}
            <div class="col-lg-8">
                <div class="cart-card">
                    <h5 class="mb-4">
                        <i class="fas fa-list me-2 text-orange"></i>
                        Articles dans votre panier ({{ count($cart) }})
                    </h5>

                    @foreach($cart as $key => $item)
                    <div class="cart-item">
                        <div class="row align-items-center">
                            {{-- Image de l'événement --}}
                            <div class="col-auto">
                                @php
                                    // Récupérer l'image de l'événement depuis la base de données
                                    $event = \App\Models\Event::find($item['event_id'] ?? null);
                                    $eventImage = $event && $event->image ? \Storage::url($event->image) : null;
                                @endphp
                                @if($eventImage)
                                    <img src="{{ $eventImage }}" alt="{{ $item['event_title'] }}" class="event-image">
                                @else
                                    <div class="event-image bg-light d-flex align-items-center justify-content-center">
                                        <i class="fas fa-calendar-alt text-muted"></i>
                                    </div>
                                @endif
                            </div>

                            {{-- Informations de l'événement --}}
                            <div class="col">
                                <div class="event-title">{{ $item['event_title'] }}</div>
                                <div class="ticket-info">
                                    <i class="fas fa-ticket-alt me-1"></i>
                                    {{ $item['ticket_name'] }}
                                    @if(isset($item['event_date']))
                                        <br><i class="fas fa-calendar me-1"></i>
                                        {{-- 🚨 LIGNE PROBLÉMATIQUE - CORRIGÉE 🚨 --}}
                                        {{ $item['event_date'] }}
                                    @endif
                                    @if(isset($item['event_time']))
                                        <i class="fas fa-clock ms-2 me-1"></i>
                                        {{ $item['event_time'] }}
                                    @endif
                                </div>
                            </div>

                            {{-- Contrôles de quantité --}}
                            <div class="col-auto">
                                <div class="quantity-controls">
                                    <button type="button" class="qty-btn" onclick="updateQuantity('{{ $key }}', -1)">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <span class="qty-display" id="qty-{{ $key }}">{{ $item['quantity'] }}</span>
                                    <button type="button" class="qty-btn" onclick="updateQuantity('{{ $key }}', 1)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>

                            {{-- Prix --}}
                            <div class="col-auto text-end">
                                <div class="price-info">
                                    {{ number_format($item['total_price']) }} FCFA
                                </div>
                                <small class="text-muted">
                                    {{ number_format($item['unit_price']) }} FCFA x {{ $item['quantity'] }}
                                </small>
                            </div>

                            {{-- Bouton supprimer --}}
                            <div class="col-auto">
                                <button type="button" class="btn btn-sm btn-danger" onclick="removeFromCart('{{ $key }}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach

                    {{-- Actions du panier --}}
                    <div class="mt-4 d-flex flex-wrap gap-2 justify-content-between">
                        <a href="{{ route('events.all') }}" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>
                            Continuer mes achats
                        </a>
                        <button type="button" class="btn btn-danger" onclick="clearCart()">
                            <i class="fas fa-trash-alt me-2"></i>
                            Vider le panier
                        </button>
                    </div>
                </div>
            </div>

            {{-- Résumé du panier --}}
            <div class="col-lg-4">
                <div class="total-summary">
                    <h5 class="mb-3 text-orange">
                        <i class="fas fa-calculator me-2"></i>
                        Résumé de la commande
                    </h5>

                    @php
                        $subtotal = array_sum(array_column($cart, 'total_price'));
                        $serviceFee = 500; // Frais de service
                        $total = $subtotal + $serviceFee;
                    @endphp

                    <div class="summary-row">
                        <span>Sous-total ({{ array_sum(array_column($cart, 'quantity')) }} billets)</span>
                        <span>{{ number_format($subtotal) }} FCFA</span>
                    </div>

                    <div class="summary-row">
                        <span>Frais de service</span>
                        <span>{{ number_format($serviceFee) }} FCFA</span>
                    </div>

                    <div class="summary-row">
                        <span><strong>Total</strong></span>
                        <span><strong>{{ number_format($total) }} FCFA</strong></span>
                    </div>

                    {{-- Bouton de checkout --}}
                    <div class="mt-4">
                        <a href="{{ route('checkout.show') }}" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-credit-card me-2"></i>
                            Passer commande
                        </a>
                    </div>

                    {{-- Informations de sécurité --}}
                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            <i class="fas fa-shield-alt me-1 text-success"></i>
                            Paiement 100% sécurisé
                        </small>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- BOTTOM MODAL MOBILE --}}
<div class="cart-modal-backdrop" id="mobileCartBackdrop" onclick="closeMobileCartModal()"></div>

<div class="cart-bottom-modal" id="mobileCartModal">
    <div class="cart-modal-header">
        <div class="cart-modal-handle"></div>
        <h5 class="mb-0">
            <i class="fas fa-shopping-cart me-2"></i>
            Mon Panier
        </h5>
        <button type="button" class="btn btn-link text-white p-0" onclick="closeMobileCartModal()">
            <i class="fas fa-times fa-lg"></i>
        </button>
    </div>
    
    <div class="cart-modal-body">
        @if(empty($cart))
            <div class="text-center py-4">
                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">Panier vide</h6>
                <p class="text-muted small">Ajoutez des billets pour commencer</p>
            </div>
        @else
            {{-- Articles du panier en mobile --}}
            <div id="mobileCartItems">
                @foreach($cart as $key => $item)
                <div class="mobile-cart-item" id="mobile-item-{{ $key }}">
                    <div class="mobile-item-header">
                        @php
                            // Récupérer l'image de l'événement depuis la base de données
                            $event = \App\Models\Event::find($item['event_id'] ?? null);
                            $eventImage = $event && $event->image ? \Storage::url($event->image) : null;
                        @endphp
                        @if($eventImage)
                            <img src="{{ $eventImage }}" alt="{{ $item['event_title'] }}" class="mobile-event-image">
                        @else
                            <div class="mobile-event-image bg-light d-flex align-items-center justify-content-center">
                                <i class="fas fa-calendar-alt text-muted"></i>
                            </div>
                        @endif
                        
                        <div class="mobile-event-info">
                            <div class="mobile-event-title">{{ $item['event_title'] }}</div>
                            <div class="mobile-ticket-info">
                                {{ $item['ticket_name'] }}
                                @if(isset($item['event_date']))
                                    {{-- 🚨 LIGNE PROBLÉMATIQUE - CORRIGÉE 🚨 --}}
                                    • {{ $item['event_date'] }}
                                @endif
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeFromCart('{{ $key }}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    
                    <div class="mobile-item-controls">
                        <div class="mobile-quantity-controls">
                            <button type="button" class="mobile-qty-btn" onclick="updateQuantity('{{ $key }}', -1)">
                                <i class="fas fa-minus"></i>
                            </button>
                            <span class="mobile-qty-display" id="mobile-qty-{{ $key }}">{{ $item['quantity'] }}</span>
                            <button type="button" class="mobile-qty-btn" onclick="updateQuantity('{{ $key }}', 1)">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        
                        <div class="mobile-price">
                            {{ number_format($item['total_price']) }} FCFA
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            
            {{-- Résumé mobile --}}
            <div class="mobile-summary">
                @php
                    $subtotal = array_sum(array_column($cart, 'total_price'));
                    $serviceFee = 500;
                    $total = $subtotal + $serviceFee;
                @endphp
                
                <div class="mobile-summary-row">
                    <span>Sous-total</span>
                    <span>{{ number_format($subtotal) }} FCFA</span>
                </div>
                <div class="mobile-summary-row">
                    <span>Frais de service</span>
                    <span>{{ number_format($serviceFee) }} FCFA</span>
                </div>
                <div class="mobile-summary-row">
                    <span>Total</span>
                    <span>{{ number_format($total) }} FCFA</span>
                </div>
            </div>
        @endif
    </div>
    
    <div class="cart-modal-footer">
        @if(!empty($cart))
            <div class="mobile-cart-actions">
                <a href="{{ route('events.all') }}" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-1"></i>
                    Continuer
                </a>
                <a href="{{ route('checkout.show') }}" class="btn btn-primary">
                    <i class="fas fa-credit-card me-1"></i>
                    Commander
                </a>
            </div>
        @else
            <a href="{{ route('events.all') }}" class="btn btn-primary w-100">
                <i class="fas fa-calendar-alt me-2"></i>
                Découvrir les événements
            </a>
        @endif
    </div>
</div>

{{-- BOUTON FLOTTANT MOBILE --}}
@if(!empty($cart))
    <button class="mobile-cart-button d-md-none" onclick="openMobileCartModal()">
        <i class="fas fa-shopping-cart"></i>
        <span>Voir mon panier ({{ count($cart) }})</span>
        <span class="ms-auto">{{ number_format(array_sum(array_column($cart, 'total_price')) + 500) }} FCFA</span>
    </button>
@endif
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Configuration CSRF pour toutes les requêtes AJAX
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

/**
 * Mettre à jour la quantité d'un article
 */
function updateQuantity(cartKey, change) {
    const qtyDisplay = document.getElementById(`qty-${cartKey}`);
    const currentQty = parseInt(qtyDisplay.textContent);
    const newQty = Math.max(1, currentQty + change);
    
    // Mettre à jour visuellement immédiatement
    qtyDisplay.textContent = newQty;
    
    // Envoyer la requête AJAX
    $.ajax({
        url: '{{ route("cart.update") }}',
        method: 'PATCH',
        data: {
            cart_key: cartKey,
            quantity: newQty
        },
        success: function(response) {
            if (response.success) {
                // Mettre à jour tous les affichages
                updateMobileQuantityDisplay(cartKey, newQty);
                updateCartDisplay(response);
                showNotification(response.message, 'success');
            } else {
                // Revenir à l'ancienne valeur en cas d'erreur
                updateMobileQuantityDisplay(cartKey, currentQty);
                showNotification(response.message, 'error');
            }
        },
        error: function(xhr) {
            // Revenir à l'ancienne valeur en cas d'erreur
            updateMobileQuantityDisplay(cartKey, currentQty);
            
            let message = 'Erreur lors de la mise à jour';
            try {
                const response = JSON.parse(xhr.responseText);
                message = response.message || message;
            } catch(e) {
                console.error('Erreur de parsing:', e);
            }
            showNotification(message, 'error');
        }
    });
}

/**
 * Supprimer un article du panier
 */
function removeFromCart(cartKey) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet article du panier ?')) {
        $.ajax({
            url: '{{ route("cart.remove") }}',
            method: 'DELETE',
            data: {
                cart_key: cartKey
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.message, 'success');
                    // Recharger la page après 1 seconde
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification(response.message, 'error');
                }
            },
            error: function(xhr) {
                console.error('Erreur AJAX:', xhr);
                showNotification('Erreur lors de la suppression', 'error');
            }
        });
    }
}

/**
 * Vider complètement le panier
 */
function clearCart() {
    if (confirm('Êtes-vous sûr de vouloir vider complètement votre panier ?')) {
        $.ajax({
            url: '{{ route("cart.clear") }}',
            method: 'DELETE',
            success: function(response) {
                if (response.success) {
                    showNotification(response.message, 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                }
            },
            error: function(xhr) {
                console.error('Erreur AJAX:', xhr);
                showNotification('Erreur lors du vidage du panier', 'error');
            }
        });
    }
}

/**
 * Mettre à jour l'affichage du panier après modification
 */
function updateCartDisplay(response) {
    if (response.cart_total !== undefined) {
        // Mettre à jour le sous-total et total
        const subtotal = response.cart_total;
        const serviceFee = 500;
        const total = subtotal + serviceFee;
        
        // Supposant que vous avez des éléments avec ces classes
        $('.cart-subtotal').text(subtotal.toLocaleString() + ' FCFA');
        $('.cart-total').text(total.toLocaleString() + ' FCFA');
    }
    
    // Mettre à jour le compteur dans le header (si la fonction existe)
    if (typeof updateCartCount === 'function') {
        updateCartCount();
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
 * Mettre à jour les affichages mobile et desktop
 */
function updateMobileQuantityDisplay(cartKey, quantity) {
    // Mettre à jour l'affichage mobile
    const mobileQty = document.getElementById(`mobile-qty-${cartKey}`);
    if (mobileQty) {
        mobileQty.textContent = quantity;
    }
    
    // Mettre à jour l'affichage desktop
    const desktopQty = document.getElementById(`qty-${cartKey}`);
    if (desktopQty) {
        desktopQty.textContent = quantity;
    }
}

/**
 * Afficher une notification stylée
 */
function showNotification(message, type = 'info') {
    const alertClass = type === 'success' ? 'alert-success' : type === 'error' ? 'alert-danger' : 'alert-info';
    const icon = type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle';
    
    const notification = `
        <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
            <i class="fas fa-${icon} me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('body').append(notification);
    
    // Auto-supprimer après 4 secondes
    setTimeout(() => {
        $('.alert').last().fadeOut();
    }, 4000);
}

// Gestionnaire de swipe pour fermer le modal
let startY = 0;
let currentY = 0;
let isDragging = false;

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('mobileCartModal');
    
    if (modal) {
        modal.addEventListener('touchstart', function(e) {
            if (e.target.closest('.cart-modal-header')) {
                startY = e.touches[0].clientY;
                isDragging = true;
            }
        });
        
        modal.addEventListener('touchmove', function(e) {
            if (!isDragging) return;
            
            currentY = e.touches[0].clientY;
            const deltaY = currentY - startY;
            
            if (deltaY > 0) {
                modal.style.transform = `translateY(${deltaY}px)`;
            }
        });
        
        modal.addEventListener('touchend', function(e) {
            if (!isDragging) return;
            
            const deltaY = currentY - startY;
            
            if (deltaY > 100) {
                closeMobileCartModal();
            } else {
                modal.style.transform = 'translateY(0)';
            }
            
            isDragging = false;
            modal.style.transform = '';
        });
    }
});

// Debug : Afficher les routes disponibles
console.log('Routes panier configurées:');
console.log('Update:', '{{ route("cart.update") }}');
console.log('Remove:', '{{ route("cart.remove") }}');
console.log('Clear:', '{{ route("cart.clear") }}');
</script>
@endpush