@extends('layouts.app')

@section('title', $event->title . ' - ClicBillet CI')
@section('body-class', 'event-page')

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
                        <span class="badge category-badge">
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
                            <span>{{ $event->venue ?? 'Lieu à confirmer' }}</span>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-user"></i>
                            <span>Par {{ $event->promoteur->name ?? 'Organisateur' }}</span>
                        </div>
                    </div>
                </div>
                
                <!-- Quick action sur mobile -->
                <div class="col-lg-4 d-lg-none">
                    <div class="mobile-cta">
                        <a href="#tickets" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-ticket-alt me-2"></i>Réserver maintenant
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section principale -->
<div class="main-content">
    <div class="container py-5">
        <div class="row">
            <!-- Contenu principal -->
            <div class="col-lg-8">
                <!-- Statistiques rapides -->
                <div class="row mb-5">
                    @php
                        $totalAvailable = $event->ticketTypes->where('is_active', true)->sum('quantity_available');
                        $totalSold = $event->ticketTypes->where('is_active', true)->sum('quantity_sold');
                        $remainingTotal = $totalAvailable - $totalSold;
                        $minPrice = $event->ticketTypes->where('is_active', true)->min('price') ?? 0;
                    @endphp
                    
                    <div class="col-6 col-md-3 mb-3">
                        <div class="stat-card text-center">
                            <div class="stat-icon bg-primary">
                                <i class="fas fa-ticket-alt"></i>
                            </div>
                            <h4 class="stat-number">{{ $remainingTotal }}</h4>
                            <p class="stat-label">Places disponibles</p>
                        </div>
                    </div>
                    
                    <div class="col-6 col-md-3 mb-3">
                        <div class="stat-card text-center">
                            <div class="stat-icon bg-success">
                                <i class="fas fa-euro-sign"></i>
                            </div>
                            <h4 class="stat-number">{{ number_format($minPrice, 0, ',', ' ') }}</h4>
                            <p class="stat-label">À partir de (FCFA)</p>
                        </div>
                    </div>
                    
                    <div class="col-6 col-md-3 mb-3">
                        <div class="stat-card text-center">
                            <div class="stat-icon bg-warning">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <h4 class="stat-number">
                                @if($event->event_date)
                                    {{ max(0, $event->event_date->diffInDays(now())) }}
                                @else
                                    --
                                @endif
                            </h4>
                            <p class="stat-label">Jours restants</p>
                        </div>
                    </div>
                    
                    <div class="col-6 col-md-3 mb-3">
                        <div class="stat-card text-center">
                            <div class="stat-icon bg-info">
                                <i class="fas fa-users"></i>
                            </div>
                            <h4 class="stat-number">{{ $totalSold }}</h4>
                            <p class="stat-label">Participants</p>
                        </div>
                    </div>
                </div>

                <!-- Description de l'événement -->
                <div class="content-card mb-5">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-info-circle text-orange me-2"></i>
                            À propos de cet événement
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="event-description">
                            {!! nl2br(e($event->description ?? 'Description non disponible.')) !!}
                        </div>
                        
                        @if($event->address)
                        <div class="venue-info mt-4">
                            <h5><i class="fas fa-map-marker-alt me-2"></i>Lieu exact</h5>
                            <p class="text-muted">{{ $event->address }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Programme (si disponible) -->
                @if($event->program)
                <div class="content-card mb-5">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list-ul text-orange me-2"></i>
                            Programme
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="program-content">
                            {!! nl2br(e($event->program)) !!}
                        </div>
                    </div>
                </div>
                @endif

                <!-- Organisateur -->
                <div class="content-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-tie text-orange me-2"></i>
                            Organisateur
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="organizer-info">
                            <div class="d-flex align-items-center">
                                <div class="organizer-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="ms-3">
                                    <h5 class="mb-1">{{ $event->promoteur->name ?? 'Organisateur' }}</h5>
                                    <p class="text-muted mb-0">Promoteur d'événements</p>
                                </div>
                            </div>
                            
                            @if($event->promoteur && $event->promoteur->bio)
                            <div class="organizer-bio mt-3">
                                <p>{{ $event->promoteur->bio }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar droite - Réservation -->
            <div class="col-lg-4">
                <div class="booking-sidebar sticky-top">
                    <!-- Widget de réservation -->
                    <div class="booking-card" id="tickets">
                        <div class="card-header">
                            <h4 class="card-title mb-0">
                                <i class="fas fa-ticket-alt text-orange me-2"></i>
                                Réserver vos billets
                            </h4>
                        </div>
                        <div class="card-body">
                            @if($event->ticketTypes->where('is_active', true)->count() > 0)
                                <form id="ticketForm" action="{{ route('cart.add') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="event_id" value="{{ $event->id }}">
                                    
                                    <div class="ticket-types">
                                        @foreach($event->ticketTypes->where('is_active', true) as $ticketType)
                                            @php
                                                $remainingTickets = $ticketType->quantity_available - $ticketType->quantity_sold;
                                                $maxPerOrder = min($remainingTickets, $ticketType->max_per_order ?? 10);
                                            @endphp
                                            
                                            <div class="ticket-type-item">
                                                <div class="ticket-info">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <div>
                                                            <h6 class="ticket-name">{{ $ticketType->name }}</h6>
                                                            @if($ticketType->description)
                                                            <p class="ticket-description">{{ $ticketType->description }}</p>
                                                            @endif
                                                        </div>
                                                        <div class="ticket-price">
                                                            <span class="price">{{ number_format($ticketType->price, 0, ',', ' ') }}</span>
                                                            <small class="currency">FCFA</small>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="ticket-actions">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <small class="text-muted">{{ $remainingTickets }} restants</small>
                                                            
                                                            @if($remainingTickets > 0)
                                                                <div class="quantity-control">
                                                                    <button type="button" class="btn btn-outline-secondary btn-sm" 
                                                                            onclick="changeQuantity({{ $ticketType->id }}, -1)"
                                                                            id="minus_{{ $ticketType->id }}">
                                                                        <i class="fas fa-minus"></i>
                                                                    </button>
                                                                    
                                                                    <span class="quantity-display mx-2" id="display_{{ $ticketType->id }}">0</span>
                                                                    
                                                                    <button type="button" class="btn btn-outline-secondary btn-sm" 
                                                                            onclick="changeQuantity({{ $ticketType->id }}, 1)"
                                                                            id="plus_{{ $ticketType->id }}">
                                                                        <i class="fas fa-plus"></i>
                                                                    </button>
                                                                    
                                                                    <input type="hidden" 
                                                                           name="tickets[{{ $ticketType->id }}]" 
                                                                           id="qty_{{ $ticketType->id }}" 
                                                                           value="0" 
                                                                           max="{{ $maxPerOrder }}"
                                                                           data-price="{{ $ticketType->price }}">
                                                                </div>
                                                            @else
                                                                <span class="btn btn-secondary btn-sm disabled">Épuisé</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    
                                    <!-- Total et bouton d'ajout au panier -->
                                    <div class="booking-total">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong>Total :</strong>
                                            <strong class="total-amount" id="totalAmount">0 FCFA</strong>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary btn-lg w-100 mb-3" id="addToCartBtn" disabled>
                                        <i class="fas fa-cart-plus me-2"></i>Ajouter au panier
                                    </button>
                                </form>
                                
                                <!-- Bouton partage -->
                                <div class="booking-actions">
                                    <button type="button" class="btn btn-outline-primary w-100" onclick="shareEvent()">
                                        <i class="fas fa-share-alt me-2"></i>Partager
                                    </button>
                                </div>
                            @else
                                <div class="no-tickets-available">
                                    <div class="text-center py-4">
                                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                                        <h5>Billets non disponibles</h5>
                                        <p class="text-muted">Les billets pour cet événement ne sont pas encore en vente.</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Informations pratiques -->
                    <div class="info-card mt-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info text-orange me-2"></i>
                                Informations pratiques
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="info-list">
                                <div class="info-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <div>
                                        <strong>Date</strong>
                                        <p>{{ $event->event_date ? $event->event_date->format('l d F Y') : 'À déterminer' }}</p>
                                    </div>
                                </div>
                                
                                @if($event->event_time)
                                <div class="info-item">
                                    <i class="fas fa-clock"></i>
                                    <div>
                                        <strong>Heure</strong>
                                        <p>{{ $event->event_time->format('H:i') }}
                                        @if($event->end_time)
                                            - {{ $event->end_time->format('H:i') }}
                                        @endif
                                        </p>
                                    </div>
                                </div>
                                @endif
                                
                                <div class="info-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <div>
                                        <strong>Lieu</strong>
                                        <p>{{ $event->venue ?? 'À confirmer' }}</p>
                                    </div>
                                </div>
                                
                                @if($event->age_restriction)
                                <div class="info-item">
                                    <i class="fas fa-user-check"></i>
                                    <div>
                                        <strong>Âge requis</strong>
                                        <p>{{ $event->age_restriction }}</p>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Section événements similaires -->
@if(isset($similarEvents) && $similarEvents->count() > 0)
<section class="similar-events py-5 bg-light">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="section-title">Événements similaires</h3>
                <p class="section-subtitle">Découvrez d'autres événements dans la même catégorie</p>
            </div>
        </div>
        
        <div class="row">
            @foreach($similarEvents as $similarEvent)
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="event-card">
                    <a href="{{ route('events.show', $similarEvent) }}" class="card-link">
                        @if($similarEvent->image)
                            <img src="{{ asset('storage/' . $similarEvent->image) }}" class="card-img-top" alt="{{ $similarEvent->title }}">
                        @else
                            <div class="card-img-placeholder">
                                <i class="{{ $similarEvent->category->icon ?? 'fas fa-calendar' }} fa-3x"></i>
                            </div>
                        @endif
                        
                        <div class="card-body">
                            <span class="badge category-badge mb-2">{{ $similarEvent->category->name }}</span>
                            <h5 class="card-title">{{ Str::limit($similarEvent->title, 50) }}</h5>
                            
                            <div class="event-meta">
                                <div class="meta-item">
                                    <i class="fas fa-calendar"></i>
                                    <span>{{ $similarEvent->event_date ? $similarEvent->event_date->format('d/m/Y') : 'Date TBD' }}</span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>{{ Str::limit($similarEvent->venue, 30) }}</span>
                                </div>
                            </div>
                            
                            @if($similarEvent->ticketTypes->where('is_active', true)->count() > 0)
                            <div class="card-footer">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="price">
                                        À partir de <strong>{{ number_format($similarEvent->ticketTypes->where('is_active', true)->min('price'), 0, ',', ' ') }} FCFA</strong>
                                    </span>
                                    <span class="btn btn-outline-primary btn-sm">Voir</span>
                                </div>
                            </div>
                            @endif
                        </div>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

@push('styles')
<style>
/* Variables CSS */
:root {
    --primary-orange: #FF6B35;
    --secondary-orange: #ff8c61;
    --dark-blue: #1a237e;
    --light-gray: #f8f9fa;
}

.event-page {
    background-color: #f8f9fa;
}

/* Hero Section */
.event-hero {
    position: relative;
    min-height: 60vh;
    display: flex;
    align-items: center;
    color: white;
    overflow: hidden;
}

.hero-background {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
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
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(0,0,0,0.7), rgba(255,107,53,0.3));
}

.hero-content {
    position: relative;
    z-index: 2;
    width: 100%;
}

.event-title {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
}

.category-badge {
    background: linear-gradient(135deg, var(--primary-orange), var(--secondary-orange));
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
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
    color: var(--primary-orange);
    width: 20px;
}

/* Cards */
.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    border: none;
    height: 100%;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: white;
    margin: 0 auto 1rem;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: var(--dark-blue);
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 0;
}

.content-card,
.booking-card,
.info-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    border: none;
    margin-bottom: 2rem;
}

.card-header {
    background: white;
    border-bottom: 1px solid #e9ecef;
    border-radius: 12px 12px 0 0 !important;
    padding: 1.5rem;
}

.card-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark-blue);
    margin: 0;
}

.card-body {
    padding: 1.5rem;
}

/* Booking sidebar */
.booking-sidebar {
    top: 2rem;
}

.ticket-type-item {
    padding: 1.5rem;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.ticket-type-item:hover {
    border-color: var(--primary-orange);
    background: rgba(255,107,53,0.02);
}

.ticket-name {
    font-weight: 600;
    color: var(--dark-blue);
    margin-bottom: 0.25rem;
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
    color: var(--primary-orange);
}

.currency {
    color: #6c757d;
}

.ticket-actions {
    margin-top: 1rem;
}

.quantity-control {
    display: flex;
    align-items: center;
    gap: 8px;
}

.quantity-control button {
    width: 32px;
    height: 32px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
}

.quantity-display {
    min-width: 20px;
    text-align: center;
    font-weight: 600;
    color: var(--dark-blue);
}

.quantity-display.selected {
    color: var(--primary-orange);
}

.booking-total {
    background: var(--light-gray);
    padding: 1rem;
    border-radius: 8px;
    margin: 1.5rem 0;
}

.total-amount {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--primary-orange);
}

.organizer-avatar {
    width: 60px;
    height: 60px;
    background: rgba(255,107,53,0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-orange);
    font-size: 1.5rem;
}

.info-list .info-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.info-list .info-item:last-child {
    border-bottom: none;
}

.info-list .info-item i {
    color: var(--primary-orange);
    width: 20px;
    margin-top: 4px;
}

.info-list .info-item strong {
    color: var(--dark-blue);
    display: block;
    margin-bottom: 0.25rem;
}

.info-list .info-item p {
    color: #6c757d;
    margin-bottom: 0;
}

/* Événements similaires */
.event-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    height: 100%;
}

.event-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.card-link {
    text-decoration: none;
    color: inherit;
    display: block;
    height: 100%;
}

.card-img-placeholder {
    height: 200px;
    background: linear-gradient(135deg, var(--primary-orange), var(--secondary-orange));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.event-meta {
    margin: 1rem 0;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.meta-item i {
    color: var(--primary-orange);
    width: 16px;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .event-title {
        font-size: 2rem;
    }
    
    .event-quick-info {
        flex-direction: column;
        gap: 1rem;
    }
    
    .stat-card {
        margin-bottom: 1rem;
    }
    
    .booking-sidebar {
        position: static;
        margin-top: 2rem;
    }
    
    .mobile-cta {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: white;
        padding: 1rem;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        z-index: 1000;
    }
}

@media (max-width: 576px) {
    .quantity-control {
        justify-content: center;
        margin-top: 8px;
    }
    
    .ticket-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .ticket-actions > div:first-child {
        margin-bottom: 8px;
    }
    
    .ticket-type-item {
        padding: 1rem;
    }
}
</style>
@endpush

@push('scripts')
<script>
// Gestion des quantités de billets
function changeQuantity(ticketTypeId, change) {
    console.log('changeQuantity appelée:', ticketTypeId, change); // Debug
    
    const input = document.getElementById('qty_' + ticketTypeId);
    const display = document.getElementById('display_' + ticketTypeId);
    
    if (!input || !display) {
        console.error('Éléments non trouvés:', {
            input: !!input,
            display: !!display,
            ticketTypeId: ticketTypeId
        });
        return;
    }
    
    const currentValue = parseInt(input.value) || 0;
    const maxValue = parseInt(input.getAttribute('max')) || 0;
    const newValue = Math.max(0, Math.min(currentValue + change, maxValue));
    
    console.log('Valeurs:', { currentValue, maxValue, newValue }); // Debug
    
    input.value = newValue;
    display.textContent = newValue;
    
    // Ajouter une classe visuelle si quantité > 0
    if (newValue > 0) {
        display.classList.add('selected');
    } else {
        display.classList.remove('selected');
    }
    
    updateTotal();
}

// Mise à jour du total
function updateTotal() {
    let total = 0;
    let hasItems = false;
    
    // Parcourir tous les inputs cachés pour calculer le total
    const ticketInputs = document.querySelectorAll('input[name^="tickets"]');
    ticketInputs.forEach(input => {
        const quantity = parseInt(input.value) || 0;
        const price = parseInt(input.getAttribute('data-price')) || 0;
        total += quantity * price;
        if (quantity > 0) hasItems = true;
    });
    
    document.getElementById('totalAmount').textContent = total.toLocaleString() + ' FCFA';
    
    const addToCartBtn = document.getElementById('addToCartBtn');
    if (addToCartBtn) {
        addToCartBtn.disabled = !hasItems;
    }
}

// Gestion des changements de quantité via input
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les affichages de quantité
    const quantityDisplays = document.querySelectorAll('.quantity-display');
    quantityDisplays.forEach(display => {
        display.textContent = '0';
    });
    
    // Initialiser le total
    updateTotal();
    
    // Scroll smooth vers la section billets
    const bookingLinks = document.querySelectorAll('a[href="#tickets"]');
    bookingLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('tickets').scrollIntoView({
                behavior: 'smooth'
            });
        });
    });
    
    // Animation des statistiques au scroll
    const observerOptions = {
        threshold: 0.5,
        rootMargin: '0px 0px -100px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const statNumbers = entry.target.querySelectorAll('.stat-number');
                statNumbers.forEach(number => {
                    animateNumber(number);
                });
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    const statsSection = document.querySelector('.row.mb-5');
    if (statsSection) {
        observer.observe(statsSection);
    }
    
    // Debug: vérifier que les boutons sont bien attachés
    console.log('Boutons +/- trouvés:', document.querySelectorAll('.quantity-control button').length);
    console.log('Inputs cachés trouvés:', document.querySelectorAll('input[name^="tickets"]').length);
    
    // Gestion du formulaire de billets
    const ticketForm = document.getElementById('ticketForm');
    if (ticketForm) {
        ticketForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Vérifier qu'au moins un billet est sélectionné
            let hasTickets = false;
            const ticketInputs = this.querySelectorAll('input[name^="tickets"]');
            ticketInputs.forEach(input => {
                if (parseInt(input.value) > 0) {
                    hasTickets = true;
                }
            });
            
            if (!hasTickets) {
                showNotification('Veuillez sélectionner au moins un billet', 'error');
                return;
            }
            
            const formData = new FormData(this);
            const submitBtn = document.getElementById('addToCartBtn');
            const originalText = submitBtn.innerHTML;
            
            // État de chargement
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Ajout en cours...';
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showNotification('Billets ajoutés au panier avec succès!', 'success');
                    
                    // Mettre à jour le badge du panier si présent
                    const cartBadge = document.querySelector('.cart-badge');
                    if (cartBadge && data.cartCount) {
                        cartBadge.textContent = data.cartCount;
                        cartBadge.style.display = 'block';
                    }
                    
                    // Réinitialiser les quantités
                    ticketInputs.forEach(input => {
                        input.value = 0;
                        const ticketId = input.name.match(/\d+/)[0];
                        const display = document.getElementById('display_' + ticketId);
                        if (display) {
                            display.textContent = '0';
                            display.classList.remove('selected');
                        }
                    });
                    updateTotal();
                } else {
                    showNotification(data.message || 'Erreur lors de l\'ajout au panier', 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur lors de l\'ajout au panier. Veuillez réessayer.', 'error');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }
});

// Animation des chiffres
function animateNumber(element) {
    const finalValue = parseInt(element.textContent.replace(/\D/g, '')) || 0;
    if (finalValue === 0) return;
    
    let currentValue = 0;
    const increment = finalValue / 30;
    
    const timer = setInterval(() => {
        currentValue += increment;
        if (currentValue >= finalValue) {
            currentValue = finalValue;
            clearInterval(timer);
        }
        element.textContent = Math.floor(currentValue).toLocaleString();
    }, 50);
}

// Partage de l'événement
function shareEvent() {
    if (navigator.share) {
        navigator.share({
            title: '{{ addslashes($event->title) }}',
            text: 'Découvrez cet événement sur ClicBillet CI',
            url: window.location.href
        }).catch(console.error);
    } else {
        // Fallback pour les navigateurs sans support natif
        const shareModal = document.getElementById('shareModal');
        if (shareModal) {
            const modal = new bootstrap.Modal(shareModal);
            modal.show();
        } else {
            copyLink();
        }
    }
}

// Copier le lien dans le presse-papiers
function copyLink() {
    navigator.clipboard.writeText(window.location.href).then(() => {
        showNotification('Lien copié dans le presse-papiers!', 'success');
    }).catch(() => {
        showNotification('Erreur lors de la copie du lien', 'error');
    });
}

// Fonction de notification
function showNotification(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        <i class="fas ${iconClass} me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.parentNode.removeChild(alertDiv);
        }
    }, 3000);
}
</script>
@endpush

<!-- Modal de partage (pour les navigateurs sans support natif) -->
<div class="modal fade" id="shareModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Partager cet événement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="share-options">
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->url()) }}" 
                       target="_blank" class="btn btn-outline-primary w-100 mb-2">
                        <i class="fab fa-facebook-f me-2"></i>Partager sur Facebook
                    </a>
                    <a href="https://twitter.com/intent/tweet?text={{ urlencode($event->title) }}&url={{ urlencode(request()->url()) }}" 
                       target="_blank" class="btn btn-outline-info w-100 mb-2">
                        <i class="fab fa-twitter me-2"></i>Partager sur Twitter
                    </a>
                    <a href="https://wa.me/?text={{ urlencode($event->title . ' - ' . request()->url()) }}" 
                       target="_blank" class="btn btn-outline-success w-100 mb-2">
                        <i class="fab fa-whatsapp me-2"></i>Partager sur WhatsApp
                    </a>
                    <button type="button" class="btn btn-outline-secondary w-100" onclick="copyLink()">
                        <i class="fas fa-copy me-2"></i>Copier le lien
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection