{{-- resources/views/acheteur/orders.blade.php --}}
@extends('layouts.acheteur')

@section('title', 'Mes Commandes - ClicBillet CI')

@section('breadcrumb')
    <li class="breadcrumb-item active">Mes Commandes</li>
@endsection

@section('content')
    <!-- Header avec stats -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">
                <i class="fas fa-shopping-bag text-primary me-2"></i>
                Mes Commandes
            </h2>
            <p class="text-muted mb-0">Historique de tous vos achats de billets</p>
        </div>
        <a href="{{ route('home') }}" class="btn btn-acheteur">
            <i class="fas fa-plus me-2"></i>Découvrir des événements
        </a>
    </div>
    
    <!-- Statistiques rapides -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h4 class="mb-0">{{ $stats['total_orders'] ?? 0 }}</h4>
                            <small>Commandes totales</small>
                        </div>
                        <i class="fas fa-shopping-cart fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h4 class="mb-0">{{ $stats['paid_orders'] ?? 0 }}</h4>
                            <small>Commandes payées</small>
                        </div>
                        <i class="fas fa-check-circle fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h4 class="mb-0">{{ $stats['total_tickets'] ?? 0 }}</h4>
                            <small>Billets achetés</small>
                        </div>
                        <i class="fas fa-ticket-alt fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h4 class="mb-0">{{ number_format($stats['total_spent'] ?? 0) }}</h4>
                            <small>FCFA dépensés</small>
                        </div>
                        <i class="fas fa-coins fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('acheteur.orders') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">Statut de paiement</label>
                    <select name="payment_status" id="payment_status" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Payées</option>
                        <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>En attente</option>
                        <option value="failed" {{ request('payment_status') == 'failed' ? 'selected' : '' }}>Échouées</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="date_from" class="form-label">Du</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" 
                           value="{{ request('date_from') }}">
                </div>
                
                <div class="col-md-3">
                    <label for="date_to" class="form-label">Au</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" 
                           value="{{ request('date_to') }}">
                </div>
                
                <div class="col-md-3">
                    <label for="search" class="form-label">Rechercher</label>
                    <input type="text" name="search" id="search" class="form-control" 
                           placeholder="Numéro, événement..." value="{{ request('search') }}">
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Filtrer
                    </button>
                    <a href="{{ route('acheteur.orders') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i>Réinitialiser
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Liste des commandes -->
    @if($orders->count() > 0)
        <div class="row">
            @foreach($orders as $order)
                <div class="col-12 mb-4">
                    <div class="card border-start border-4 {{ $order->payment_status === 'paid' ? 'border-success' : ($order->payment_status === 'pending' ? 'border-warning' : 'border-danger') }}">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <!-- Image de l'événement -->
                                <div class="col-md-2">
                                    @if($order->event && $order->event->image)
                                        <img src="{{ asset('storage/' . $order->event->image) }}" 
                                             alt="{{ $order->event->title }}" 
                                             class="img-fluid rounded" style="max-height: 100px; object-fit: cover;">
                                    @else
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                             style="height: 100px;">
                                            <i class="fas fa-calendar-alt text-muted fa-2x"></i>
                                        </div>
                                    @endif
                                </div>
                                
                                <!-- Informations de la commande -->
                                <div class="col-md-6">
                                    <div class="row">
                                        <div class="col-12">
                                            <h5 class="mb-1">
                                                <a href="{{ route('acheteur.order.detail', $order) }}" 
                                                   class="text-decoration-none text-dark">
                                                    {{ $order->event->title ?? 'Événement supprimé' }}
                                                </a>
                                            </h5>
                                            
                                            <div class="row text-muted small mb-2">
                                                <div class="col-sm-6">
                                                    <i class="fas fa-hashtag me-1"></i>
                                                    <strong>{{ $order->order_number }}</strong>
                                                </div>
                                                <div class="col-sm-6">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    {{ $order->created_at->format('d/m/Y H:i') }}
                                                </div>
                                            </div>
                                            
                                            @if($order->event)
                                                <div class="row text-muted small">
                                                    <div class="col-sm-6">
                                                        <i class="fas fa-map-marker-alt me-1"></i>
                                                        {{ $order->event->venue ?? 'Lieu TBD' }}
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <i class="fas fa-clock me-1"></i>
                                                        {{ $order->event->formatted_event_date ?? 'Date TBD' }}
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Prix et statuts -->
                                <div class="col-md-2 text-center">
                                    <h4 class="text-primary mb-1">{{ number_format($order->total_amount) }}</h4>
                                    <small class="text-muted">FCFA</small>
                                    
                                    <div class="mt-2">
                                        <span class="badge {{ $order->payment_status === 'paid' ? 'bg-success' : ($order->payment_status === 'pending' ? 'bg-warning' : 'bg-danger') }}">
                                            {{ $order->payment_status === 'paid' ? 'Payée' : ($order->payment_status === 'pending' ? 'En attente' : 'Échouée') }}
                                        </span>
                                    </div>
                                    
                                    @if($order->tickets->count() > 0)
                                        <div class="mt-1">
                                            <small class="text-muted">
                                                <i class="fas fa-ticket-alt me-1"></i>
                                                {{ $order->tickets->count() }} billet(s)
                                            </small>
                                        </div>
                                    @endif
                                </div>
                                
                                <!-- Actions -->
                                <div class="col-md-2 text-end">
                                    <div class="btn-group-vertical d-grid gap-2">
                                        <a href="{{ route('acheteur.order.detail', $order) }}" 
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye me-1"></i>Détails
                                        </a>
                                        
                                        @if($order->payment_status === 'paid')
                                            <a href="{{ route('acheteur.order.download', $order) }}" 
                                               class="btn btn-success btn-sm">
                                                <i class="fas fa-download me-1"></i>PDF
                                            </a>
                                        @endif
                                        
                                        @if($order->event && $order->event->event_date >= now()->toDateString())
                                            <a href="{{ route('events.show', $order->event) }}" 
                                               class="btn btn-outline-info btn-sm">
                                                <i class="fas fa-info-circle me-1"></i>Événement
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Détails des billets (repliable) -->
                            @if($order->payment_status === 'paid' && $order->tickets->count() > 0)
                                <div class="mt-3 pt-3 border-top">
                                    <a class="btn btn-sm btn-outline-secondary" 
                                       data-bs-toggle="collapse" 
                                       href="#tickets-{{ $order->id }}" 
                                       role="button">
                                        <i class="fas fa-chevron-down me-1"></i>
                                        Voir les billets ({{ $order->tickets->count() }})
                                    </a>
                                    
                                    <div class="collapse mt-3" id="tickets-{{ $order->id }}">
                                        <div class="row g-2">
                                            @foreach($order->tickets->take(4) as $ticket)
                                                <div class="col-md-3">
                                                    <div class="card card-body p-2 bg-light">
                                                        <small class="text-muted">{{ $ticket->ticketType->name ?? 'Billet' }}</small>
                                                        <code class="small">{{ $ticket->ticket_code }}</code>
                                                        <span class="badge badge-sm {{ $ticket->status === 'sold' ? 'bg-success' : 'bg-warning' }}">
                                                            {{ ucfirst($ticket->status) }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @endforeach
                                            
                                            @if($order->tickets->count() > 4)
                                                <div class="col-md-3">
                                                    <div class="card card-body p-2 bg-light text-center">
                                                        <small class="text-muted">
                                                            +{{ $order->tickets->count() - 4 }} autres billets
                                                        </small>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Pagination -->
        <div class="d-flex justify-content-center">
            {{ $orders->withQueryString()->links() }}
        </div>
        
    @else
        <!-- État vide -->
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-shopping-bag text-muted mb-3" style="font-size: 4rem;"></i>
                <h4 class="text-muted">Aucune commande trouvée</h4>
                
                @if(request()->hasAny(['payment_status', 'date_from', 'date_to', 'search']))
                    <p class="text-muted mb-4">
                        Aucune commande ne correspond à vos critères de recherche.
                    </p>
                    <a href="{{ route('acheteur.orders') }}" class="btn btn-outline-primary me-2">
                        <i class="fas fa-times me-2"></i>Effacer les filtres
                    </a>
                @else
                    <p class="text-muted mb-4">
                        Vous n'avez pas encore passé de commande.<br>
                        Découvrez nos événements et réservez vos billets !
                    </p>
                @endif
                
                <a href="{{ route('home') }}" class="btn btn-acheteur">
                    <i class="fas fa-search me-2"></i>Découvrir des événements
                </a>
            </div>
        </div>
    @endif

@endsection

@push('scripts')
<script>
// Auto-submit du formulaire de filtre quand on change le statut
document.getElementById('payment_status').addEventListener('change', function() {
    this.form.submit();
});

// Raccourci clavier pour la recherche
document.getElementById('search').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        this.form.submit();
    }
});

// Collapse automatique des anciens billets
document.addEventListener('DOMContentLoaded', function() {
    // Optionnel: auto-collapse pour les commandes anciennes
    const oldOrders = document.querySelectorAll('[id^="tickets-"]:not(.show)');
    // Logique pour auto-collapse si nécessaire
});
</script>
@endpush