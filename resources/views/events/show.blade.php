{{-- resources/views/events/show.blade.php - VERSION COMPLÈTE --}}
@php
    // Layout adaptatif
    $user = auth()->user();
    $eventLayout = 'layouts.app'; // Layout public pour l'achat
@endphp

@extends($eventLayout)

@section('title', $event->title . ' - ClicBillet CI')

@push('styles')
<style>
/* ===== STYLES POUR LA PAGE ÉVÉNEMENT ===== */

/* Hero Section */
.event-hero {
    position: relative;
    min-height: 400px;
    background: linear-gradient(135deg, #FF6B35, #1a237e);
    overflow: hidden;
    margin-bottom: 3rem;
}

.hero-background {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1;
}

.hero-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(0,0,0,0.7), rgba(255,107,53,0.3));
    z-index: 2;
}

.hero-content {
    position: relative;
    z-index: 3;
    padding: 4rem 0;
    color: white;
}

.event-title {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
}

.category-badge {
    background: linear-gradient(135deg, #FF6B35, #E55A2B);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    display: inline-block;
}

.event-quick-info {
    display: flex;
    flex-wrap: wrap;
    gap: 2rem;
    margin-top: 2rem;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: rgba(255,255,255,0.9);
}

.info-item i {
    color: #FF6B35;
    width: 20px;
}

/* Content Cards */
.content-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    padding: 2rem;
    margin-bottom: 2rem;
    border: none;
}

.section-title {
    color: #1a237e;
    font-weight: 600;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Booking Sidebar */
.booking-sidebar {
    position: sticky;
    top: calc(80px + 2rem);
    max-height: calc(100vh - 80px - 4rem);
    overflow-y: auto;
    z-index: 10;
}

.booking-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border: none;
    overflow: hidden;
}

.card-header {
    background: linear-gradient(135deg, #FF6B35, #1a237e);
    color: white;
    padding: 1.5rem;
    border-bottom: none;
}

.card-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
}

/* Étapes de réservation */
.booking-steps {
    display: flex;
    justify-content: center;
    margin-bottom: 1.5rem;
    gap: 1rem;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    opacity: 0.6;
    color: rgba(255,255,255,0.7);
}

.step.active {
    opacity: 1;
    color: white;
}

.step-number {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.5rem;
    font-weight: bold;
    font-size: 0.9rem;
}

.step.active .step-number {
    background: white;
    color: #FF6B35;
}

.step-label {
    font-size: 0.8rem;
    text-align: center;
}

/* Types de billets */
.ticket-type-item {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.ticket-type-item:hover {
    border-color: #FF6B35;
    background: rgba(255,107,53,0.05);
}

.ticket-name {
    font-weight: 600;
    color: #1a237e;
    margin-bottom: 0.5rem;
}

.ticket-description {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 0;
}

.ticket-price {
    text-align: right;
}

.price {
    font-size: 1.25rem;
    font-weight: 700;
    color: #FF6B35;
}

.currency {
    color: #6c757d;
    font-size: 0.9rem;
}

/* Contrôles de quantité */
.quantity-control {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.quantity-control button {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    border: 1px solid #FF6B35;
    background: white;
    color: #FF6B35;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.quantity-control button:hover {
    background: #FF6B35;
    color: white;
}

.quantity-display {
    min-width: 30px;
    text-align: center;
    font-weight: 600;
    color: #1a237e;
    font-size: 1.1rem;
}

.quantity-display.selected {
    color: #FF6B35;
}

/* Résumé booking */
.booking-summary {
    background: rgba(255,107,53,0.1);
    border-radius: 12px;
    padding: 1.5rem;
    margin-top: 1.5rem;
}

/* Boutons d'action */
.booking-actions button {
    border-radius: 8px;
    font-weight: 600;
    padding: 12px 20px;
    transition: all 0.3s ease;
}

.booking-actions button small {
    font-size: 0.75rem;
    opacity: 0.8;
    margin-top: 0.25rem;
}

.btn-orange {
    background: linear-gradient(135deg, #FF6B35, #1a237e);
    border: none;
    color: white;
}

.btn-orange:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(255,107,53,0.3);
    color: white;
}

.btn-outline-orange {
    border: 2px solid #FF6B35;
    color: #FF6B35;
    background: white;
}

.btn-outline-orange:hover {
    background: #FF6B35;
    color: white;
}

/* Responsive */
@media (max-width: 767.98px) {
    .event-title {
        font-size: 2rem;
    }
    
    .event-quick-info {
        flex-direction: column;
        gap: 1rem;
    }
    
    .booking-sidebar {
        position: static;
        top: auto;
        max-height: none;
        margin-top: 2rem;
    }
    
    .quantity-control {
        justify-content: center;
        margin-top: 1rem;
    }
    
    .ticket-actions {
        flex-direction: column;
        gap: 1rem;
    }
}
</style>
@endpush

@section('content')
<!-- Hero Section avec image de l'événement -->
<section class="event-hero">
    <div class="hero-background">
        @if($event->image)
            <img src="{{ asset('storage/' . $event->image) }}" alt="{{ $event->title }}" class="hero-image">
        @endif
        <div class="hero-overlay"></div>
    </div>
    
    <div class="hero-content">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <!-- Breadcrumb -->
                    <nav aria-label="breadcrumb" class="mb-3">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('home') }}" class="text-white-50">
                                    <i class="fas fa-home me-1"></i>Accueil
                                </a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('categories.show', $event->category) }}" class="text-white-50">
                                    {{ $event->category->name }}
                                </a>
                            </li>
                            <li class="breadcrumb-item active text-white">{{ $event->title }}</li>
                        </ol>
                    </nav>
                    
                    <!-- Catégorie -->
                    <div class="event-category mb-3">
                        <span class="category-badge">
                            <i class="{{ $event->category->icon ?? 'fas fa-calendar' }} me-2"></i>
                            {{ $event->category->name }}
                        </span>
                    </div>
                    
                    <!-- Titre -->
                    <h1 class="event-title">{{ $event->title }}</h1>
                    
                    <!-- Informations rapides -->
                    <div class="event-quick-info">
                        <div class="info-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span>{{ $event->event_date ? $event->event_date->format('d/m/Y') : 'Date à déterminer' }}</span>
                        </div>
                        
                        @if($event->event_time)
                        <div class="info-item">
                            <i class="fas fa-clock"></i>
                            <span>{{ $event->event_time->format('H:i') }}</span>
                        </div>
                        @endif
                        
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>{{ $event->venue ?? 'Lieu à déterminer' }}</span>
                        </div>
                        
                        @if($event->ticketTypes->count() > 0)
                        <div class="info-item">
                            <i class="fas fa-ticket-alt"></i>
                            <span>À partir de {{ number_format($event->ticketTypes->min('price'), 0, ',', ' ') }} FCFA</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contenu principal -->
<div class="container">
    <div class="row">
        <!-- Colonne contenu -->
        <div class="col-lg-8">
            <!-- Description de l'événement -->
            <div class="content-card">
                <h3 class="section-title">
                    <i class="fas fa-info-circle"></i>
                    Description
                </h3>
                <div class="event-description">
                    {!! nl2br(e($event->description)) !!}
                </div>
            </div>
            
            <!-- Informations pratiques -->
            <div class="content-card">
                <h3 class="section-title">
                    <i class="fas fa-map-marked-alt"></i>
                    Informations pratiques
                </h3>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <h6><i class="fas fa-calendar-alt me-2 text-orange"></i>Date et heure</h6>
                        <p class="mb-0">
                            {{ $event->event_date ? $event->event_date->format('d/m/Y') : 'Date à déterminer' }}
                            @if($event->event_time)
                                à {{ $event->event_time->format('H:i') }}
                            @endif
                        </p>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <h6><i class="fas fa-map-marker-alt me-2 text-orange"></i>Lieu</h6>
                        <p class="mb-0">{{ $event->venue ?? 'Lieu à déterminer' }}</p>
                        @if($event->address)
                            <small class="text-muted">{{ $event->address }}</small>
                        @endif
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <h6><i class="fas fa-user me-2 text-orange"></i>Organisateur</h6>
                        <p class="mb-0">{{ $event->promoter->name ?? 'Organisateur' }}</p>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <h6><i class="fas fa-tag me-2 text-orange"></i>Catégorie</h6>
                        <p class="mb-0">{{ $event->category->name }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Événements similaires -->
            @if($similarEvents && $similarEvents->count() > 0)
            <div class="content-card">
                <h3 class="section-title">
                    <i class="fas fa-heart"></i>
                    Événements similaires
                </h3>
                
                <div class="row">
                    @foreach($similarEvents->take(3) as $similar)
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            @if($similar->image)
                                <img src="{{ asset('storage/' . $similar->image) }}" class="card-img-top" alt="{{ $similar->title }}" style="height: 150px; object-fit: cover;">
                            @else
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 150px;">
                                    <i class="fas fa-calendar-alt fa-2x text-muted"></i>
                                </div>
                            @endif
                            
                            <div class="card-body">
                                <h6 class="card-title">{{ $similar->title }}</h6>
                                <p class="card-text small text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    {{ $similar->event_date ? $similar->event_date->format('d/m/Y') : 'Date TBD' }}
                                </p>
                                <a href="{{ route('events.show', $similar) }}" class="btn btn-outline-orange btn-sm">
                                    Voir l'événement
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        
        <!-- Sidebar réservation -->
        <div class="col-lg-4">
            <div class="booking-sidebar">
                <div class="booking-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-ticket-alt me-2"></i>
                            Réserver vos billets
                        </h5>
                        
                        <!-- Indicateur d'étapes -->
                        <div class="booking-steps">
                            <div class="step active">
                                <span class="step-number">1</span>
                                <span class="step-label">Sélection</span>
                            </div>
                            <div class="step">
                                <span class="step-number">2</span>
                                <span class="step-label">Réservation</span>
                            </div>
                            <div class="step">
                                <span class="step-number">3</span>
                                <span class="step-label">Paiement</span>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <form id="ticketForm" method="POST" action="{{ route('checkout.direct') }}>
                            @csrf
                            <input type="hidden" name="event_id" value="{{ $event->id }}">

                            @forelse($event->ticketTypes as $ticketType)
                            <div class="ticket-type-item" data-ticket="{{ $ticketType->id }}">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="ticket-name">{{ $ticketType->name }}</h6>
                                        @if($ticketType->description)
                                            <p class="ticket-description">{{ $ticketType->description }}</p>
                                        @endif
                                    </div>
                                    <div class="ticket-price">
                                        <span class="price">{{ number_format($ticketType->price, 0, ',', ' ') }}</span>
                                        <span class="currency">FCFA</span>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        {{ $ticketType->quantity_available - $ticketType->quantity_sold }} restants
                                    </small>
                                    
                                    <div class="quantity-control">
                                        <button type="button" class="btn btn-sm" 
                                                onclick="changeQuantity({{ $ticketType->id }}, -1)">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        
                                        <span class="quantity-display" id="display_{{ $ticketType->id }}">0</span>
                                        <input type="hidden" name="tickets[{{ $ticketType->id }}]" 
                                               id="qty_{{ $ticketType->id }}" value="0">
                                        
                                        <button type="button" class="btn btn-sm" 
                                                onclick="changeQuantity({{ $ticketType->id }}, 1)">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="text-center py-4">
                                <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Aucun billet disponible pour le moment</p>
                            </div>
                            @endforelse

                            <!-- Résumé des prix -->
                            <div class="booking-summary">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Sous-total</span>
                                    <span id="subtotal">0 FCFA</span>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span>Frais de service</span>
                                    <span>500 FCFA</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <strong>Total</strong>
                                    <strong class="text-orange" id="total">500 FCFA</strong>
                                </div>
                            </div>

                            <!-- Boutons d'action -->
                            <div class="booking-actions mt-4">
                                <!-- Panier : Mise de côté temporaire -->
                                <button type="button" class="btn btn-outline-orange w-100 mb-2" 
                                        onclick="addToCartWithTimer()" id="addToCartBtn">
                                    <i class="fas fa-clock me-2"></i>
                                    Ajouter au panier
                                    <small class="d-block">Continuer à naviguer</small>
                                </button>
                                
                                <!-- Réservation directe -->
                                <button type="button" class="btn btn-orange w-100" 
                                        onclick="reserveDirectly()" id="reserveDirectBtn">
                                    <i class="fas fa-lock me-2"></i>
                                    Réserver maintenant (1h)
                                    <small class="d-block">Réservation immédiate</small>
                                </button> 
                            </div>

                            <!-- Informations de sécurité -->
                            <div class="booking-info mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    Réservation sécurisée - Aucun paiement à cette étape
                                </small>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ===== JAVASCRIPT POUR LA RÉSERVATION =====

// Variables globales
let selectedTickets = {};
let currentTotal = 0;

// Changer la quantité
function changeQuantity(ticketTypeId, change) {
    const input = document.getElementById('qty_' + ticketTypeId);
    const display = document.getElementById('display_' + ticketTypeId);
    
    if (!input || !display) {
        console.error('Éléments non trouvés pour ticket:', ticketTypeId);
        return;
    }
    
    let currentValue = parseInt(input.value) || 0;
    let newValue = Math.max(0, Math.min(10, currentValue + change));
    
    input.value = newValue;
    display.textContent = newValue;
    
    // Effet visuel
    if (newValue > 0) {
        display.classList.add('selected');
        selectedTickets[ticketTypeId] = newValue;
    } else {
        display.classList.remove('selected');
        delete selectedTickets[ticketTypeId];
    }
    
    updateTotals();
}

// Mettre à jour les totaux
function updateTotals() {
    let subtotal = 0;
    
    for (const [ticketTypeId, quantity] of Object.entries(selectedTickets)) {
        const priceElement = document.querySelector(`[data-ticket="${ticketTypeId}"] .price`);
        if (priceElement) {
            const price = parseInt(priceElement.textContent.replace(/[^\d]/g, ''));
            subtotal += price * quantity;
        }
    }
    
    const serviceFee = 500;
    const total = subtotal + serviceFee;
    
    document.getElementById('subtotal').textContent = `${subtotal.toLocaleString()} FCFA`;
    document.getElementById('total').textContent = `${total.toLocaleString()} FCFA`;
    
    // Activer/désactiver les boutons
    const hasSelection = Object.keys(selectedTickets).length > 0;
    document.getElementById('addToCartBtn').disabled = !hasSelection;
    document.getElementById('reserveDirectBtn').disabled = !hasSelection;
}

// Ajouter au panier avec timer (15 minutes)
function addToCartWithTimer() {
    if (Object.keys(selectedTickets).length === 0) {
        alert('Veuillez sélectionner au moins un billet');
        return;
    }
    
    const form = document.getElementById('ticketForm');
    form.action = '{{ route("cart.add") }}';
    
    showNotification('Billets mis de côté pendant 15 minutes', 'info');
    
    // Soumettre via AJAX
    submitFormAjax(form, (data) => {
        if (data.success) {
            showNotification('Billets ajoutés au panier avec succès!', 'success');
            updateCartCount(data.cartCount);
            
            // Redirection optionnelle vers le panier
            setTimeout(() => {
                window.location.href = '{{ route("cart.show") }}';
            }, 2000);
        }
    });
}

// Réserver directement (1 heure)
function reserveDirectly() {
    if (Object.keys(selectedTickets).length === 0) {
        alert('Veuillez sélectionner au moins un billet');
        return;
    }
    
    const confirmation = confirm(
        'Vos billets seront réservés pendant 1 heure.\n' +
        'Vous devrez finaliser votre achat dans ce délai.\n' +
        'Continuer ?'
    );
    
    if (!confirmation) return;
    
    const form = document.getElementById('ticketForm');
    form.action = '{{ route("checkout.direct") }}';
    form.method = 'POST';
    
    // Soumettre directement (pas AJAX)
    form.submit();
}

// Soumission AJAX
function submitFormAjax(form, callback) {
    const formData = new FormData(form);
    
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(callback)
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur lors de la soumission', 'error');
    });
}

// Notifications simples
function showNotification(message, type = 'info') {
    const alertClass = type === 'error' ? 'alert-danger' : type === 'success' ? 'alert-success' : 'alert-info';
    
    const notification = document.createElement('div');
    notification.className = `alert ${alertClass} position-fixed top-0 end-0 m-3`;
    notification.style.zIndex = '1060';
    notification.innerHTML = `
        <i class="fas fa-${type === 'error' ? 'exclamation-circle' : type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
        ${message}
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove après 4 secondes
    setTimeout(() => {
        notification.remove();
    }, 4000);
}

// Mettre à jour le compteur du panier
function updateCartCount(count) {
    const cartElements = document.querySelectorAll('#cart-count, .cart-count');
    cartElements.forEach(element => {
        element.textContent = count;
        element.style.display = count > 0 ? 'inline' : 'none';
    });
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    updateTotals();
});
</script>
@endpush