@extends('layouts.app')

@section('title', 'Mon tableau de bord - ClicBillet CI')
@section('body-class', 'dashboard-page')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar navigation -->
        <div class="col-md-3 col-lg-2">
            <div class="sidebar bg-light rounded p-3">
                <h6 class="text-muted text-uppercase mb-3">Mon compte</h6>
                <nav class="nav flex-column">
                    <a class="nav-link active" href="{{ route('acheteur.dashboard') }}">
                        <i class="fas fa-tachometer-alt me-2"></i>Tableau de bord
                    </a>
                    <a class="nav-link" href="{{ route('acheteur.tickets') }}">
                        <i class="fas fa-ticket-alt me-2"></i>Mes billets
                    </a>
                    <a class="nav-link" href="{{ route('cart.show') }}">
                        <i class="fas fa-shopping-cart me-2"></i>Mon panier
                    </a>
                    <a class="nav-link" href="{{ route('acheteur.profile') }}">
                        <i class="fas fa-user-cog me-2"></i>Mon profil
                    </a>
                </nav>
            </div>
        </div>
        
        <!-- Contenu principal -->
        <div class="col-md-9 col-lg-10">
            <!-- En-tête de la page -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Bonjour, {{ Auth::user()->name }} 👋</h2>
                    <p class="text-muted mb-0">Voici un aperçu de votre activité</p>
                </div>
                <a href="{{ route('home') }}" class="btn btn-orange">
                    <i class="fas fa-plus me-2"></i>Découvrir des événements
                </a>
            </div>
            
            <!-- Statistiques -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="stat-icon bg-primary text-white rounded-circle mx-auto mb-3" 
                                 style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-ticket-alt fa-lg"></i>
                            </div>
                            <h3 class="mb-1">{{ $totalTickets ?? 0 }}</h3>
                            <p class="text-muted mb-0">Billets achetés</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="stat-icon bg-success text-white rounded-circle mx-auto mb-3" 
                                 style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-calendar-check fa-lg"></i>
                            </div>
                            <h3 class="mb-1">{{ $eventsAttended ?? 0 }}</h3>
                            <p class="text-muted mb-0">Événements assistés</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="stat-icon bg-warning text-white rounded-circle mx-auto mb-3" 
                                 style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-clock fa-lg"></i>
                            </div>
                            <h3 class="mb-1">{{ $upcomingEvents ?? 0 }}</h3>
                            <p class="text-muted mb-0">Événements à venir</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mes prochains événements -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-alt text-orange me-2"></i>
                            Mes prochains événements
                        </h5>
                        <a href="{{ route('acheteur.tickets') }}" class="btn btn-outline-primary btn-sm">
                            Voir tout
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(isset($upcomingTickets) && $upcomingTickets->count() > 0)
                        <div class="row">
                            @foreach($upcomingTickets->take(3) as $ticket)
                                <div class="col-md-4 mb-3">
                                    <div class="card border">
                                        <div class="card-body">
                                            <h6 class="card-title">{{ $ticket->ticketType->event->title }}</h6>
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    {{ $ticket->ticketType->event->event_date->format('d/m/Y') }}
                                                </small><br>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    {{ $ticket->ticketType->event->event_time ? $ticket->ticketType->event->event_time->format('H:i') : 'Heure à confirmer' }}
                                                </small><br>
                                                <small class="text-muted">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    {{ $ticket->ticketType->event->venue }}
                                                </small>
                                            </p>
                                            <a href="{{ route('acheteur.ticket.show', $ticket) }}" class="btn btn-orange btn-sm">
                                                <i class="fas fa-eye me-1"></i>Voir le billet
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-times text-muted fa-3x mb-3"></i>
                            <h5 class="text-muted">Aucun événement à venir</h5>
                            <p class="text-muted">Découvrez les événements disponibles et réservez vos billets</p>
                            <a href="{{ route('home') }}" class="btn btn-orange">
                                <i class="fas fa-search me-2"></i>Découvrir des événements
                            </a>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Activité récente -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-history text-orange me-2"></i>
                        Activité récente
                    </h5>
                </div>
                <div class="card-body">
                    @if(isset($recentOrders) && $recentOrders->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($recentOrders->take(5) as $order)
                                <div class="list-group-item border-0 px-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">Commande #{{ $order->order_number }}</h6>
                                            <p class="mb-1 text-muted">
                                                {{ $order->orderItems->count() }} billet(s) - 
                                                {{ number_format($order->total_amount, 0, ',', ' ') }} FCFA
                                            </p>
                                            <small class="text-muted">{{ $order->created_at->format('d/m/Y à H:i') }}</small>
                                        </div>
                                        <div>
                                            <span class="badge bg-{{ $order->payment_status === 'paid' ? 'success' : ($order->payment_status === 'pending' ? 'warning' : 'danger') }}">
                                                {{ ucfirst($order->payment_status) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-shopping-cart text-muted fa-3x mb-3"></i>
                            <h5 class="text-muted">Aucune commande récente</h5>
                            <p class="text-muted">Vos achats de billets apparaîtront ici</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.sidebar .nav-link {
    color: #6c757d;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 5px;
    transition: all 0.3s ease;
}

.sidebar .nav-link:hover,
.sidebar .nav-link.active {
    background: var(--primary-orange);
    color: white;
}

.stat-icon {
    background: linear-gradient(135deg, var(--primary-orange), var(--secondary-orange)) !important;
}

.dashboard-page {
    background-color: #f8f9fa;
}
</style>
@endpush
@endsection