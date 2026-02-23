@extends('layouts.admin')

@section('title', 'Gestion des tickets')

@section('styles')
<style>
.ticket-status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.status-sold { background-color: #d1fae5; color: #065f46; }
.status-used { background-color: #bfdbfe; color: #1e40af; }
.status-pending { background-color: #fef3c7; color: #92400e; }
.status-cancelled { background-color: #fecaca; color: #991b1b; }
.status-available { background-color: #f3f4f6; color: #374151; }

.stats-card {
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    padding: 1.5rem;
    border-left: 4px solid #3b82f6;
}

.stats-card.success { border-left-color: #10b981; }
.stats-card.warning { border-left-color: #f59e0b; }
.stats-card.danger { border-left-color: #ef4444; }
.stats-card.info { border-left-color: #6366f1; }

.filter-section {
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.ticket-actions .btn {
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
}

.ticket-code {
    font-family: 'Courier New', monospace;
    font-weight: bold;
    font-size: 0.9rem;
    background: #f8fafc;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    border: 1px solid #e2e8f0;
}

.event-info {
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.buyer-info {
    display: flex;
    flex-direction: column;
}

.buyer-name {
    font-weight: 600;
    color: #1f2937;
}

.buyer-contact {
    font-size: 0.875rem;
    color: #6b7280;
}

.value-display {
    font-weight: bold;
    color: #059669;
}

.table-responsive {
    border-radius: 0.75rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.table thead th {
    background-color: #f8fafc;
    border-bottom: 2px solid #e5e7eb;
    font-weight: 600;
    color: #374151;
    padding: 1rem 0.75rem;
}

.table tbody td {
    padding: 0.875rem 0.75rem;
    vertical-align: middle;
    border-bottom: 1px solid #f3f4f6;
}

.table tbody tr:hover {
    background-color: #f9fafb;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #6b7280;
}

.empty-state i {
    font-size: 4rem;
    color: #d1d5db;
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .filter-form {
        flex-direction: column;
    }
    
    .filter-form .form-group {
        margin-bottom: 1rem;
    }
    
    .ticket-actions {
        flex-direction: column;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
}
</style>
@endsection

@section('content')
<div class="admin-content">
    <!-- En-tête avec titre et actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Gestion des tickets</h1>
            <p class="text-muted mb-0">Vue d'ensemble et gestion des billets vendus</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.tickets.export') }}" class="btn btn-outline-primary">
                <i class="fas fa-download me-2"></i>Exporter
            </a>
            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-cog me-2"></i>Actions
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">
                        <i class="fas fa-check-circle me-2"></i>Vérification en lot
                    </a></li>
                    <li><a class="dropdown-item" href="#">
                        <i class="fas fa-chart-bar me-2"></i>Statistiques détaillées
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#">
                        <i class="fas fa-cog me-2"></i>Paramètres tickets
                    </a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Statistiques globales -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">{{ number_format($stats['total']) }}</h5>
                        <small class="text-muted">Total tickets</small>
                    </div>
                    <div class="text-primary">
                        <i class="fas fa-ticket-alt fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">{{ number_format($stats['sold']) }}</h5>
                        <small class="text-muted">Tickets vendus</small>
                    </div>
                    <div class="text-success">
                        <i class="fas fa-shopping-cart fa-2x"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="text-success">
                        <i class="fas fa-arrow-up me-1"></i>
                        {{ $stats['total'] > 0 ? round(($stats['sold'] / $stats['total']) * 100, 1) : 0 }}% du total
                    </small>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card info">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">{{ number_format($stats['used']) }}</h5>
                        <small class="text-muted">Tickets utilisés</small>
                    </div>
                    <div class="text-info">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="text-info">
                        <i class="fas fa-percentage me-1"></i>
                        {{ $stats['usage_rate'] }}% d'utilisation
                    </small>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">{{ number_format($stats['total_value'] / 100, 0, ',', ' ') }}</h5>
                        <small class="text-muted">Valeur totale (FCFA)</small>
                    </div>
                    <div class="text-warning">
                        <i class="fas fa-coins fa-2x"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="text-warning">
                        <i class="fas fa-calendar me-1"></i>
                        {{ number_format($stats['this_month']) }} ce mois
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertes de statut -->
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

    <!-- Section des filtres -->
    <div class="filter-section">
        <h5 class="mb-3">
            <i class="fas fa-filter me-2"></i>Filtres et recherche
        </h5>
        
        <form method="GET" action="{{ route('admin.tickets.index') }}" class="filter-form">
            <div class="row">
                <!-- Recherche générale -->
                <div class="col-lg-3 col-md-6 mb-3">
                    <label for="search" class="form-label">Recherche</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Code, événement, acheteur...">
                </div>

                <!-- Filtre par statut -->
                <div class="col-lg-2 col-md-6 mb-3">
                    <label for="status" class="form-label">Statut</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Tous les statuts</option>
                        <option value="sold" {{ request('status') === 'sold' ? 'selected' : '' }}>Vendus</option>
                        <option value="used" {{ request('status') === 'used' ? 'selected' : '' }}>Utilisés</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Annulés</option>
                        <option value="available" {{ request('status') === 'available' ? 'selected' : '' }}>Disponibles</option>
                    </select>
                </div>

                <!-- Filtre par événement -->
                <div class="col-lg-3 col-md-6 mb-3">
                    <label for="event" class="form-label">Événement</label>
                    <select class="form-select" id="event" name="event">
                        <option value="">Tous les événements</option>
                        @foreach($events as $event)
                            <option value="{{ $event->id }}" {{ request('event') == $event->id ? 'selected' : '' }}>
                                {{ Str::limit($event->title, 40) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtre par promoteur -->
                <div class="col-lg-2 col-md-6 mb-3">
                    <label for="promoteur" class="form-label">Promoteur</label>
                    <select class="form-select" id="promoteur" name="promoteur">
                        <option value="">Tous</option>
                        @foreach($promoteurs as $promoteur)
                            <option value="{{ $promoteur->id }}" {{ request('promoteur') == $promoteur->id ? 'selected' : '' }}>
                                {{ Str::limit($promoteur->name, 20) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Date de création -->
                <div class="col-lg-2 col-md-6 mb-3">
                    <label for="date_from" class="form-label">Date de</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="{{ request('date_from') }}">
                </div>
            </div>

            <div class="row">
                <div class="col-lg-2 col-md-6 mb-3">
                    <label for="date_to" class="form-label">Date à</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="{{ request('date_to') }}">
                </div>

                <div class="col-lg-10 col-md-6 mb-3 d-flex align-items-end">
                    <div class="btn-group w-100" role="group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Rechercher
                        </button>
                        <a href="{{ route('admin.tickets.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Réinitialiser
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Tableau des tickets -->
    <div class="card border-0">
        <div class="card-header bg-transparent border-bottom-0 pb-0">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    Liste des tickets 
                    <span class="badge bg-secondary">{{ $tickets->total() }} résultats</span>
                </h5>
                <div class="d-flex gap-2">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-sort me-1"></i>Trier par
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['sort' => 'created_desc']) }}">Plus récents</a></li>
                            <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['sort' => 'created_asc']) }}">Plus anciens</a></li>
                            <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['sort' => 'code']) }}">Code ticket</a></li>
                            <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['sort' => 'event']) }}">Événement</a></li>
                            <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['sort' => 'value']) }}">Valeur</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            @if($tickets->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="5%">
                                    <input type="checkbox" id="select-all" class="form-check-input">
                                </th>
                                <th width="15%">Code ticket</th>
                                <th width="25%">Événement</th>
                                <th width="20%">Acheteur</th>
                                <th width="10%">Statut</th>
                                <th width="10%">Valeur</th>
                                <th width="10%">Date</th>
                                <th width="5%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tickets as $ticket)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input ticket-checkbox" 
                                               value="{{ $ticket->id }}">
                                    </td>
                                    
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="ticket-code">{{ $ticket->ticket_code }}</span>
                                            @if($ticket->qr_code)
                                                <small class="text-success">
                                                    <i class="fas fa-qrcode me-1"></i>QR Code
                                                </small>
                                            @endif
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <div class="event-info">
                                            <div class="fw-bold text-dark">{{ $ticket->ticketType->event->title }}</div>
                                            <small class="text-muted">
                                                <i class="fas fa-ticket-alt me-1"></i>{{ $ticket->ticketType->name }}
                                            </small>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                {{ $ticket->ticketType->event->event_date ? 
                                                   \Carbon\Carbon::parse($ticket->ticketType->event->event_date)->format('d/m/Y') : 
                                                   'Date non définie' }}
                                            </small>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        @php
                                            $buyer = null;
                                            // Essayer via orderItem.order.user
                                            if ($ticket->orderItem && $ticket->orderItem->order && $ticket->orderItem->order->user) {
                                                $buyer = $ticket->orderItem->order->user;
                                            }
                                            // Alternative via orders.user
                                            elseif ($ticket->orders->isNotEmpty() && $ticket->orders->first()->user) {
                                                $buyer = $ticket->orders->first()->user;
                                            }
                                        @endphp
                                        
                                        @if($buyer)
                                            <div class="buyer-info">
                                                <span class="buyer-name">{{ $buyer->name }}</span>
                                                @if($buyer->email)
                                                    <span class="buyer-contact">{{ $buyer->email }}</span>
                                                @endif
                                                @if($buyer->phone)
                                                    <span class="buyer-contact">{{ $buyer->phone }}</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">
                                                <i class="fas fa-user-slash me-1"></i>Non assigné
                                            </span>
                                        @endif
                                    </td>
                                    
                                    <td>
                                        <span class="ticket-status-badge status-{{ $ticket->status }}">
                                            @switch($ticket->status)
                                                @case('sold')
                                                    <i class="fas fa-shopping-cart me-1"></i>Vendu
                                                    @break
                                                @case('used')
                                                    <i class="fas fa-check-circle me-1"></i>Utilisé
                                                    @break
                                                @case('pending')
                                                    <i class="fas fa-clock me-1"></i>En attente
                                                    @break
                                                @case('cancelled')
                                                    <i class="fas fa-times-circle me-1"></i>Annulé
                                                    @break
                                                @case('available')
                                                    <i class="fas fa-circle me-1"></i>Disponible
                                                    @break
                                                @default
                                                    {{ ucfirst($ticket->status) }}
                                            @endswitch
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <div class="value-display">
                                            {{ number_format($ticket->ticketType->price / 100, 0, ',', ' ') }} FCFA
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <div class="d-flex flex-column">
                                            <small class="text-muted">
                                                {{ $ticket->created_at->format('d/m/Y') }}
                                            </small>
                                            <small class="text-muted">
                                                {{ $ticket->created_at->format('H:i') }}
                                            </small>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                    type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('admin.tickets.show', $ticket) }}">
                                                        <i class="fas fa-eye me-2"></i>Détails
                                                    </a>
                                                </li>
                                                @if($ticket->qr_code)
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('verify.ticket', $ticket->ticket_code) }}" target="_blank">
                                                            <i class="fas fa-qrcode me-2"></i>Vérifier
                                                        </a>
                                                    </li>
                                                @endif
                                                @if($ticket->status === 'sold')
                                                    <li>
                                                        <button class="dropdown-item" onclick="markUsed('{{ $ticket->id }}')">
                                                            <i class="fas fa-check me-2"></i>Marquer utilisé
                                                        </button>
                                                    </li>
                                                @endif
                                                @if($ticket->status !== 'cancelled')
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button class="dropdown-item text-danger" onclick="cancelTicket('{{ $ticket->id }}')">
                                                            <i class="fas fa-times me-2"></i>Annuler
                                                        </button>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state">
                    <i class="fas fa-ticket-alt"></i>
                    <h5>Aucun ticket trouvé</h5>
                    <p class="text-muted">
                        @if(request()->hasAny(['search', 'status', 'event', 'promoteur', 'date_from', 'date_to']))
                            Aucun ticket ne correspond à vos critères de recherche.
                            <br><a href="{{ route('admin.tickets.index') }}" class="btn btn-link">Réinitialiser les filtres</a>
                        @else
                            Il n'y a encore aucun ticket dans le système.
                        @endif
                    </p>
                </div>
            @endif
        </div>

        @if($tickets->hasPages())
            <div class="card-footer bg-transparent border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        Affichage de {{ $tickets->firstItem() }} à {{ $tickets->lastItem() }} 
                        sur {{ $tickets->total() }} résultats
                    </div>
                    {{ $tickets->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Actions en lot -->
<div class="position-fixed bottom-0 start-50 translate-middle-x mb-4" id="bulk-actions" style="display: none;">
    <div class="bg-dark rounded-pill px-4 py-2 text-white">
        <span id="selected-count">0</span> ticket(s) sélectionné(s)
        <div class="btn-group ms-3" role="group">
            <button type="button" class="btn btn-sm btn-outline-light" onclick="bulkMarkAsUsed()">
                <i class="fas fa-check me-1"></i>Marquer utilisés
            </button>
            <button type="button" class="btn btn-sm btn-outline-light" onclick="bulkExport()">
                <i class="fas fa-download me-1"></i>Exporter
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="bulkCancel()">
                <i class="fas fa-times me-1"></i>Annuler
            </button>
        </div>
        <button type="button" class="btn-close btn-close-white ms-3" onclick="clearSelection()"></button>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/admin-tickets-index.js') }}" defer></script>
@endsection