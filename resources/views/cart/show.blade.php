{{-- resources/views/cart/show.blade.php - Vue mise à jour avec layout unifié --}}
@extends('layouts.app')

@section('title', 'Mon Panier - ClicBillet CI')

@push('styles')
<style>
    .cart-item {
        border: 1px solid #e9ecef;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 20px;
        transition: all 0.3s ease;
        background: white;
    }
    
    .cart-item:hover {
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    
    .cart-summary {
        background: linear-gradient(135deg, #f8f9fa, #ffffff);
        border-radius: 20px;
        padding: 30px;
        position: sticky;
        top: 100px;
        border: 1px solid #e9ecef;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    
    .quantity-control {
        display: inline-flex;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid #e9ecef;
    }
    
    .quantity-control button {
        background: #f8f9fa;
        border: none;
        width: 35px;
        height: 35px;
        font-weight: bold;
        transition: all 0.2s ease;
    }
    
    .quantity-control button:hover {
        background: #FF6B35;
        color: white;
    }
    
    .quantity-control input {
        border: none;
        width: 50px;
        text-align: center;
        background: white;
    }
    
    .price-tag {
        background: linear-gradient(135deg, #FF6B35, #E55A2B);
        color: white;
        padding: 8px 15px;
        border-radius: 20px;
        font-weight: bold;
        font-size: 1.1rem;
    }
    
    .empty-cart {
        text-align: center;
        padding: 60px 20px;
        color: #6c757d;
    }
    
    .empty-cart i {
        font-size: 4rem;
        margin-bottom: 20px;
        color: #dee2e6;
    }
    
    .btn-remove {
        background: none;
        border: none;
        color: #dc3545;
        font-size: 1.2rem;
        padding: 5px;
        border-radius: 50%;
        width: 35px;
        height: 35px;
        transition: all 0.2s ease;
    }
    
    .btn-remove:hover {
        background: #dc3545;
        color: white;
        transform: scale(1.1);
    }
</style>
@endpush

@section('content')
<div class="container py-5">
    <!-- En-tête de page -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h1 class="display-6 fw-bold mb-2">
                        <i class="fas fa-shopping-cart me-3 text-orange"></i>Mon Panier
                    </h1>
                    <p class="text-muted mb-0">
                        @if(count($cart) > 0)
                            {{ count($cart) }} article(s) dans votre panier
                        @else
                            Votre panier est vide
                        @endif
                    </p>
                </div>
                <a href="{{ route('events.all') }}" class="btn btn-outline-orange">
                    <i class="fas fa-arrow-left me-2"></i>Continuer les achats
                </a>
            </div>
        </div>
    </div>

    @if(count($cart) > 0)
        <div class="row">
            <!-- Articles du panier -->
            <div class="col-lg-8">
                @foreach($cart as $cartKey => $item)
                    <div class="cart-item" data-cart-key="{{ $cartKey }}" id="cart-item-{{ $cartKey }}">
                        <div class="row align-items-center">
                            <!-- Informations du billet -->
                            <div class="col-md-7">
                                <div class="d-flex align-items-start">
                                    <div class="me-3">
                                        <i class="fas fa-ticket-alt text-orange" style="font-size: 2rem;"></i>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold mb-2 text-dark">{{ $item['event_title'] }}</h5>
                                        <p class="mb-1">
                                            <span class="badge bg-orange me-2">{{ $item['ticket_name'] }}</span>
                                        </p>
                                        <p class="text-muted mb-1">
                                            <i class="fas fa-calendar me-2"></i>{{ $item['event_date'] }}
                                        </p>
                                        <p class="text-muted mb-0">
                                            <i class="fas fa-map-marker-alt me-2"></i>{{ $item['event_venue'] }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Contrôles quantité et prix -->
                            <div class="col-md-5">
                                <div class="d-flex align-items-center justify-content-between">
                                    <!-- Quantité -->
                                    <div class="me-3">
                                        <label class="form-label small text-muted mb-1">Quantité</label>
                                        <div class="quantity-control">
                                            <button type="button" onclick="updateQuantity('{{ $cartKey }}', -1)">-</button>
                                            <input type="number" 
                                                   value="{{ $item['quantity'] }}" 
                                                   min="1" 
                                                   max="{{ $item['max_per_order'] }}"
                                                   class="quantity-input"
                                                   data-cart-key="{{ $cartKey }}"
                                                   onchange="updateQuantityDirect('{{ $cartKey }}', this.value)">
                                            <button type="button" onclick="updateQuantity('{{ $cartKey }}', 1)">+</button>
                                        </div>
                                    </div>
                                    
                                    <!-- Prix -->
                                    <div class="text-center me-2">
                                        <div class="small text-muted">Prix unitaire</div>
                                        <div class="fw-bold">{{ number_format($item['unit_price']) }} FCFA</div>
                                        <div class="price-tag mt-2" id="total-{{ $cartKey }}">
                                            {{ number_format($item['total_price']) }} FCFA
                                        </div>
                                    </div>
                                    
                                    <!-- Bouton supprimer -->
                                    <button type="button" 
                                            class="btn-remove" 
                                            onclick="removeFromCart('{{ $cartKey }}')"
                                            title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <!-- Résumé du panier -->
            <div class="col-lg-4">
                <div class="cart-summary">
                    <h4 class="fw-bold mb-4">
                        <i class="fas fa-calculator me-2 text-orange"></i>Résumé
                    </h4>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <span>Sous-total:</span>
                        <span class="fw-bold" id="cart-subtotal">{{ number_format($cartTotal) }} FCFA</span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <span>Frais de service:</span>
                        <span>500 FCFA</span>
                    </div>
                    
                    <hr class="my-3">
                    
                    <div class="d-flex justify-content-between mb-4">
                        <span class="h5 fw-bold">Total:</span>
                        <span class="h5 fw-bold text-orange" id="cart-total">
                            {{ number_format($cartTotal + 500) }} FCFA
                        </span>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="{{ route('checkout.show') }}" class="btn btn-orange btn-lg">
                            <i class="fas fa-credit-card me-2"></i>Procéder au paiement
                        </a>
                        
                        <button type="button" class="btn btn-outline-danger" onclick="clearCart()">
                            <i class="fas fa-trash me-2"></i>Vider le panier
                        </button>
                    </div>
                    
                    <!-- Informations supplémentaires -->
                    <div class="mt-4 p-3 bg-light rounded">
                        <h6 class="fw-bold mb-2">
                            <i class="fas fa-info-circle me-2 text-info"></i>Informations
                        </h6>
                        <ul class="list-unstyled mb-0 small text-muted">
                            <li><i class="fas fa-shield-alt me-2"></i>Paiement sécurisé</li>
                            <li><i class="fas fa-mobile-alt me-2"></i>Billets électroniques</li>
                            <li><i class="fas fa-envelope me-2"></i>Confirmation par email</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Panier vide -->
        <div class="row">
            <div class="col-12">
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h3 class="mb-3">Votre panier est vide</h3>
                    <p class="mb-4">Découvrez nos événements et ajoutez des billets à votre panier</p>
                    <a href="{{ route('events.all') }}" class="btn btn-orange btn-lg">
                        <i class="fas fa-search me-2"></i>Découvrir les événements
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
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
            const response = JSON.parse(xhr.responseText);
            showNotification(response.message || 'Erreur lors de la mise à jour', 'error');
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
                    // Supprimer l'élément de l'affichage
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
                const response = JSON.parse(xhr.responseText);
                showNotification(response.message || 'Erreur lors de la suppression', 'error');
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
            error: function() {
                showNotification('Erreur lors du vidage du panier', 'error');
            }
        });
    }
}

// Mettre à jour l'affichage du panier
function updateCartDisplay(response) {
    $('#cart-subtotal').text(response.cart_total.toLocaleString() + ' FCFA');
    $('#cart-total').text((response.cart_total + 500).toLocaleString() + ' FCFA');
    
    // Mettre à jour le compteur dans le header
    if (typeof updateCartCount === 'function') {
        updateCartCount();
    }
}

// Afficher une notification
function showNotification(message, type = 'info') {
    const alertClass = type === 'success' ? 'alert-success' : type === 'error' ? 'alert-danger' : 'alert-info';
    const icon = type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle';
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            <i class="fas fa-${icon} me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('body').append(alertHtml);
    
    // Supprimer automatiquement après 3 secondes
    setTimeout(() => {
        $('.alert').fadeOut();
    }, 3000);
}

// Initialisation
$(document).ready(function() {
    console.log('Page panier chargée avec le layout unifié');
});
</script>
@endpush