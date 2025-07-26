@extends('layouts.app')

@section('title', 'Mon tableau de bord - ClicBillet CI')
@section('body-class', 'dashboard-page')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar navigation -->
        <div class="col-md-3 col-lg-2">
            <div class="sidebar bg-dark rounded p-3">
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
                <div class="col-md-3 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="stat-icon bg-primary text-white rounded-circle mx-auto mb-3" 
                                 style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-ticket-alt fa-lg"></i>
                            </div>
                            <h3 class="mb-1">{{ $stats['total_tickets'] ?? 0 }}</h3>
                            <p class="text-muted mb-0">Billets achetés</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="stat-icon bg-success text-white rounded-circle mx-auto mb-3" 
                                 style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-calendar-check fa-lg"></i>
                            </div>
                            <h3 class="mb-1">{{ $stats['past_events'] ?? 0 }}</h3>
                            <p class="text-muted mb-0">Événements assistés</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="stat-icon bg-warning text-white rounded-circle mx-auto mb-3" 
                                 style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-clock fa-lg"></i>
                            </div>
                            <h3 class="mb-1">{{ $stats['upcoming_events'] ?? 0 }}</h3>
                            <p class="text-muted mb-0">Événements à venir</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="stat-icon bg-info text-white rounded-circle mx-auto mb-3" 
                                 style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-shopping-bag fa-lg"></i>
                            </div>
                            <h3 class="mb-1">{{ $stats['total_orders'] ?? 0 }}</h3>
                            <p class="text-muted mb-0">Commandes</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Événements à venir -->
            @if(isset($upcomingEvents) && $upcomingEvents->count() > 0)
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-calendar-plus text-orange me-2"></i>
                                Mes prochains événements
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @foreach($upcomingEvents->take(3) as $order)
                                <div class="col-lg-4 mb-3">
                                    <div class="event-card">
                                        <div class="d-flex">
                                            @if($order->event->image)
                                                <img src="{{ asset('storage/' . $order->event->image) }}" 
                                                     class="event-thumbnail me-3" 
                                                     alt="{{ $order->event->title }}">
                                            @else
                                                <div class="event-thumbnail-placeholder me-3">
                                                    <i class="fas fa-calendar-alt"></i>
                                                </div>
                                            @endif
                                            
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">{{ Str::limit($order->event->title, 30) }}</h6>
                                                <p class="text-muted mb-1 small">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    {{ $order->event->event_date->format('d/m/Y') }}
                                                </p>
                                                <p class="text-muted mb-1 small">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    {{ Str::limit($order->event->venue, 25) }}
                                                </p>
                                                <a href="{{ route('acheteur.order.detail', $order) }}" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    Voir les billets
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            
                            @if($upcomingEvents->count() > 3)
                            <div class="text-center mt-3">
                                <a href="{{ route('acheteur.tickets') }}" class="btn btn-outline-orange">
                                    Voir tous mes billets
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Commandes récentes -->
            @if(isset($recentOrders) && $recentOrders->count() > 0)
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-receipt text-orange me-2"></i>
                                Mes commandes récentes
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Commande</th>
                                            <th>Événement</th>
                                            <th>Date commande</th>
                                            <th>Montant</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentOrders as $order)
                                        <tr>
                                            <td>
                                                <strong>#{{ $order->order_number }}</strong>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong>{{ Str::limit($order->event->title, 30) }}</strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        {{ $order->event->event_date->format('d/m/Y') }}
                                                    </small>
                                                </div>
                                            </td>
                                            <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                            <td>
                                                <strong>{{ number_format($order->total_amount, 0, ',', ' ') }} FCFA</strong>
                                            </td>
                                            <td>
                                                @switch($order->payment_status)
                                                    @case('paid')
                                                        <span class="badge bg-success">Payé</span>
                                                        @break
                                                    @case('pending')
                                                        <span class="badge bg-warning">En attente</span>
                                                        @break
                                                    @case('failed')
                                                        <span class="badge bg-danger">Échoué</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary">{{ $order->payment_status }}</span>
                                                @endswitch
                                            </td>
                                            <td>
                                                <a href="{{ route('acheteur.order.detail', $order) }}" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye me-1"></i>Voir
                                                </a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="text-center mt-3">
                                <a href="{{ route('acheteur.tickets') }}" class="btn btn-outline-orange">
                                    Voir toutes mes commandes
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @else
            <!-- État vide -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-ticket-alt fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted mb-3">Aucune commande pour le moment</h4>
                            <p class="text-muted mb-4">
                                Découvrez les événements disponibles et commencez à réserver vos billets !
                            </p>
                            <a href="{{ route('home') }}" class="btn btn-orange btn-lg">
                                <i class="fas fa-search me-2"></i>Découvrir des événements
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@push('styles')
<style>
.dashboard-page {
    background-color: #f8f9fa;
}

.btn-orange {
    background: linear-gradient(135deg, #FF6B35, #ff8c61);
    border: none;
    color: white;
    font-weight: 600;
    padding: 10px 20px;
    border-radius: 25px;
    transition: all 0.3s ease;
}

.btn-orange:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
    color: white;
}

.btn-outline-orange {
    border: 2px solid #FF6B35;
    color: #FF6B35;
    font-weight: 600;
    padding: 8px 20px;
    border-radius: 25px;
    transition: all 0.3s ease;
}

.btn-outline-orange:hover {
    background: #FF6B35;
    color: white;
    transform: translateY(-2px);
}

.sidebar .nav-link.active {
    background: linear-gradient(135deg, #FF6B35, #ff8c61);
    color: white !important;
    border-radius: 8px;
    font-weight: 600;
}

.sidebar .nav-link {
    color: #6c757d;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 5px;
    transition: all 0.3s ease;
}

.sidebar .nav-link:hover {
    background: rgba(255, 107, 53, 0.1);
    color: #FF6B35;
}

.event-card {
    padding: 1rem;
    background: white;
    border-radius: 8px;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.event-card:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.event-thumbnail {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
}

.event-thumbnail-placeholder {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #FF6B35, #ff8c61);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
    background: #f8f9fa;
}

.badge {
    font-size: 0.75rem;
    padding: 0.5rem 0.75rem;
    border-radius: 15px;
}
</style>
@endpush
@endsection