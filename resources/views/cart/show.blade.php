@extends('layouts.app')

@section('title', 'Mon Panier')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8">
            <h2 class="mb-4">Mon Panier</h2>
            
            @if(!empty($cart))
                @foreach($cart as $cartKey => $item)
                <div class="card mb-3" id="cart-item-{{ $cartKey }}">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="card-title mb-1">{{ $item['event_title'] }}</h5>
                                <p class="text-muted mb-1">{{ $item['ticket_name'] }}</p>
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>{{ $item['event_date'] }}
                                    <i class="fas fa-map-marker-alt ms-3 me-1"></i>{{ $item['event_venue'] }}
                                </small>
                            </div>
                            <div class="col-md-2">
                                <div class="d-flex align-items-center">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" 
                                            onclick="updateQuantity('{{ $cartKey }}', -1)">-</button>
                                    <input type="number" class="form-control form-control-sm mx-2 text-center" 
                                           value="{{ $item['quantity'] }}" 
                                           min="1" 
                                           max="{{ $item['max_per_order'] }}"
                                           data-cart-key="{{ $cartKey }}"
                                           onchange="updateQuantityDirect('{{ $cartKey }}', this.value)"
                                           style="width: 60px;">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" 
                                            onclick="updateQuantity('{{ $cartKey }}', 1)">+</button>
                                </div>
                            </div>
                            <div class="col-md-2 text-center">
                                <strong>{{ number_format($item['unit_price']) }} FCFA</strong>
                            </div>
                            <div class="col-md-2 text-center">
                                <strong>{{ number_format($item['total_price']) }} FCFA</strong>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="removeFromCart('{{ $cartKey }}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
                
                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-danger" onclick="clearCart()">
                        <i class="fas fa-trash me-2"></i>Vider le panier
                    </button>
                    <a href="{{ route('events.all') }}" class="btn btn-outline-primary">
                        <i class="fas fa-plus me-2"></i>Ajouter d'autres billets
                    </a>
                </div>
            @endif
        </div>
        
        <div class="col-lg-4">
            @if(!empty($cart))
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Récapitulatif</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Sous-total:</span>
                        <span id="cart-subtotal">{{ number_format($cartTotal) }} FCFA</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Frais de service:</span>
                        <span>500 FCFA</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total:</strong>
                        <strong id="cart-total">{{ number_format($cartTotal + 500) }} FCFA</strong>
                    </div>
                    <a href="{{ route('checkout.show') }}" class="btn btn-orange w-100">
                        <i class="fas fa-credit-card me-2"></i>Procéder au paiement
                    </a>
                </div>
            </div>
            @else
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                    <h3 class="mb-3">Votre panier est vide</h3>
                    <p class="mb-4">Découvrez nos événements et ajoutez des billets à votre panier</p>
                    <a href="{{ route('events.all') }}" class="btn btn-orange btn-lg">
                        <i class="fas fa-search me-2"></i>Découvrir les événements
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* ===== STYLES POUR CHECKOUT PAGE ===== */

:root {
    --primary-orange: #FF6B35;
    --primary-dark: #E55A2B;
    --success-color: #28a745;
    --danger-color: #dc3545;
    --warning-color: #ffc107;
    --light-gray: #f8f9fa;
    --border-color: #e9ecef;
    --shadow-light: 0 2px 10px rgba(0,0,0,0.1);
    --shadow-medium: 0 4px 20px rgba(0,0,0,0.15);
    --shadow-strong: 0 8px 30px rgba(0,0,0,0.2);
    --transition-smooth: all 0.3s ease;
}

/* ===== CONTENEUR CHECKOUT ===== */
.checkout-container {
    padding: 2rem 0;
    background: var(--light-gray);
    min-height: calc(100vh - 80px);
}

/* ===== CARTES CHECKOUT ===== */
.checkout-card, .checkout-item {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow-light);
    transition: var(--transition-smooth);
}

.checkout-card:hover {
    box-shadow: var(--shadow-medium);
    border-color: var(--primary-orange);
}

/* ===== SECTIONS CHECKOUT ===== */
.checkout-section {
    margin-bottom: 2rem;
}

.checkout-section:last-child {
    margin-bottom: 0;
}

.checkout-section h5 {
    color: var(--primary-dark);
    margin-bottom: 1rem;
    font-weight: 600;
}

/* ===== ÉTAPES DE CHECKOUT ===== */
.checkout-steps {
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

/* ===== FORMULAIRE CHECKOUT ===== */
.form-control:focus {
    border-color: var(--primary-orange);
    box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
}

.form-check-input:checked {
    background-color: var(--primary-orange);
    border-color: var(--primary-orange);
}

/* ===== RÉSUMÉ TOTAL ===== */
.total-summary {
    background: linear-gradient(135deg, #fff3e0, #ffe0b2);
    padding: 1.5rem;
    border-radius: 15px;
    border: 1px solid #ffcc02;
}

.total-summary .text-orange {
    color: var(--primary-orange) !important;
}

/* ===== BOUTON CHECKOUT ===== */
.btn-checkout {
    background: linear-gradient(135deg, var(--primary-orange), var(--primary-dark));
    border: none;
    color: white;
    font-weight: bold;
    font-size: 1.1rem;
    padding: 1rem 2rem;
    border-radius: 15px;
    width: 100%;
    transition: var(--transition-smooth);
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
}

.btn-checkout:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(255, 107, 53, 0.4);
    color: white;
}

.btn-checkout:active {
    transform: translateY(-1px);
}

.btn-checkout::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s ease;
}

.btn-checkout:hover::before {
    left: 100%;
}

/* ===== HEADER CHECKOUT ===== */
.checkout-header {
    background: linear-gradient(135deg, var(--primary-orange), var(--primary-dark));
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: var(--shadow-medium);
}

.checkout-header h1 {
    margin-bottom: 0.5rem;
}

.checkout-header p {
    margin-bottom: 0;
    opacity: 0.9;
}

/* ===== LIENS ET TEXTES ===== */
.text-orange {
    color: var(--primary-orange) !important;
}

a.text-orange:hover {
    color: var(--primary-dark) !important;
    text-decoration: underline;
}

/* ===== ALERTES ===== */
.alert-success {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    border-color: var(--success-color);
    color: #155724;
    border-radius: 10px;
}

.alert-warning {
    background: linear-gradient(135deg, #fff3cd, #ffeaa7);
    border-color: var(--warning-color);
    color: #856404;
    border-radius: 10px;
}

/* ===== STICKY SIDEBAR ===== */
.sticky-top {
    position: sticky !important;
    top: 2rem !important;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .checkout-container {
        padding: 1rem 0;
    }
    
    .checkout-card {
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    .checkout-header {
        padding: 1.5rem;
        text-align: center;
    }
    
    .checkout-steps {
        flex-direction: column;
        gap: 1rem;
    }
    
    .step {
        flex-direction: row;
        max-width: none;
        width: 100%;
    }
    
    .step:not(:last-child)::after {
        display: none;
    }
    
    .sticky-top {
        position: relative !important;
        top: auto !important;
    }
    
    .btn-checkout {
        padding: 0.875rem 1.5rem;
        font-size: 1rem;
    }
}

/* ===== ANIMATIONS ===== */
@keyframes slideInFromBottom {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.checkout-card {
    animation: slideInFromBottom 0.6s ease-out;
}

.checkout-header {
    animation: slideInFromBottom 0.4s ease-out;
}
</style>
@endpush

@push('scripts')
<!-- Inclure jQuery si pas déjà inclus -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
// Configuration CSRF
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Mettre à jour la quantité
function updateQuantity(cartKey, delta) {
    const input = document.querySelector(`input[data-cart-key="${cartKey}"]`);
    const currentQuantity = parseInt(input.value);
    const newQuantity = Math.max(1, currentQuantity + delta);
    const maxQuantity = parseInt(input.getAttribute('max'));
    
    if (newQuantity <= maxQuantity) {
        updateQuantityDirect(cartKey, newQuantity);
    }
}

// Mise à jour directe de la quantité
function updateQuantityDirect(cartKey, quantity) {
    quantity = Math.max(1, parseInt(quantity));
    
    // Mettre à jour visuellement l'input
    const input = document.querySelector(`input[data-cart-key="${cartKey}"]`);
    if (input) {
        input.value = quantity;
    }
    
    $.ajax({
        url: '{{ route("cart.update") }}',
        method: 'PATCH',
        data: {
            cart_key: cartKey,
            quantity: quantity
        },
        success: function(response) {
            if (response.success) {
                // Mettre à jour l'affichage
                updateCartDisplay(response);
                
                // Afficher un message de succès
                showNotification(response.message, 'success');
            } else {
                showNotification(response.message, 'error');
            }
        },
        error: function(xhr) {
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

// Supprimer un article du panier
function removeFromCart(cartKey) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
        $.ajax({
            url: '{{ route("cart.remove") }}',
            method: 'DELETE',
            data: {
                cart_key: cartKey
            },
            success: function(response) {
                if (response.success) {
                    // Supprimer l'élément de l'affichage avec animation
                    $('#cart-item-' + cartKey).fadeOut(300, function() {
                        $(this).remove();
                        
                        // Si le panier est vide, recharger la page
                        if (response.cart_count === 0) {
                            location.reload();
                        }
                    });
                    
                    // Mettre à jour l'affichage
                    updateCartDisplay(response);
                    showNotification(response.message, 'success');
                } else {
                    showNotification(response.message, 'error');
                }
            },
            error: function(xhr) {
                let message = 'Erreur lors de la suppression';
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
}

// Vider le panier
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

// Mettre à jour l'affichage du panier
function updateCartDisplay(response) {
    if (response.cart_total !== undefined) {
        $('#cart-subtotal').text(response.cart_total.toLocaleString() + ' FCFA');
        $('#cart-total').text((response.cart_total + 500).toLocaleString() + ' FCFA');
    }
    
    // Mettre à jour le compteur dans le header
    if (typeof updateCartCount === 'function') {
        updateCartCount();
    }
    
    // Mettre à jour les totaux des lignes individuelles
    $('[data-cart-key]').each(function() {
        const cartKey = $(this).data('cart-key');
        const quantity = parseInt($(this).val());
        const row = $('#cart-item-' + cartKey);
        const unitPrice = parseFloat(row.find('.unit-price').data('price') || 0);
        
        if (unitPrice > 0) {
            const totalPrice = unitPrice * quantity;
            row.find('.total-price').text(totalPrice.toLocaleString() + ' FCFA');
        }
    });
}

// Afficher une notification
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
    
    // Auto-supprimer après 5 secondes
    setTimeout(() => {
        $('.alert').last().fadeOut();
    }, 5000);
}

// Debug : Vérifier que les routes existent
console.log('Routes disponibles:');
console.log('Update:', '{{ route("cart.update") }}');
console.log('Remove:', '{{ route("cart.remove") }}');
console.log('Clear:', '{{ route("cart.clear") }}');
</script>
@endpush