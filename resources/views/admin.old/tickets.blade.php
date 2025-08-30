{{-- resources/views/admin/tickets.blade.php --}}
@extends('layouts.admin')

@section('title', 'Gestion des billets - ClicBillet CI')

@section('breadcrumb')
    <li class="breadcrumb-item active">Billets</li>
@endsection

@section('content')
    <!-- Header avec actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Gestion des billets</h2>
            <p class="text-muted mb-0">Supervisez tous les billets de la plateforme</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.tickets.export') }}" class="btn btn-outline-orange">
                <i class="fas fa-download me-2"></i>Exporter
            </a>
            <div class="dropdown">
                <button class="btn btn-orange dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-cog me-2"></i>Actions groupées
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" onclick="bulkAction('mark_used')">
                        <i class="fas fa-check me-2"></i>Marquer comme utilisés
                    </a></li>
                    <li><a class="dropdown-item" href="#" onclick="bulkAction('cancel')">
                        <i class="fas fa-times me-2"></i>Annuler sélectionnés
                    </a></li>
                    <li><a class="dropdown-item" href="#" onclick="bulkAction('reactivate')">
                        <i class="fas fa-undo me-2"></i>Réactiver sélectionnés
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="#" onclick="bulkAction('delete')">
                        <i class="fas fa-trash me-2"></i>Supprimer sélectionnés
                    </a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Statistiques des billets -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon primary me-3">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h4>{{ $stats['total'] ?? 0 }}</h4>
                        <p class="text-muted mb-0">Total billets</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon success me-3">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <h4>{{ $stats['sold'] ?? 0 }}</h4>
                        <p class="text-muted mb-0">Vendus</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon info me-3">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h4>{{ $stats['used'] ?? 0 }}</h4>
                        <p class="text-muted mb-0">Utilisés</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon warning me-3">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <h4>{{ number_format($stats['total_value'] ?? 0) }} F</h4>
                        <p class="text-muted mb-0">Valeur totale</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.tickets') }}">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Recherche</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Code billet, événement, acheteur..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Statut</label>
                        <select name="status" class="form-select">
                            <option value="">Tous</option>
                            <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>Disponible</option>
                            <option value="sold" {{ request('status') == 'sold' ? 'selected' : '' }}>Vendu</option>
                            <option value="used" {{ request('status') == 'used' ? 'selected' : '' }}>Utilisé</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Annulé</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Événement</label>
                        <select name="event_id" class="form-select">
                            <option value="">Tous</option>
                            @foreach($events ?? [] as $event)
                                <option value="{{ $event->id }}" {{ request('event_id') == $event->id ? 'selected' : '' }}>
                                    {{ Str::limit($event->title, 30) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date</label>
                        <select name="date_filter" class="form-select">
                            <option value="">Toutes</option>
                            <option value="today" {{ request('date_filter') == 'today' ? 'selected' : '' }}>Aujourd'hui</option>
                            <option value="week" {{ request('date_filter') == 'week' ? 'selected' : '' }}>Cette semaine</option>
                            <option value="month" {{ request('date_filter') == 'month' ? 'selected' : '' }}>Ce mois</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-orange w-100">
                            <i class="fas fa-search me-2"></i>Filtrer
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des billets -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-ticket-alt me-2"></i>
                Liste des billets
                @if(isset($tickets) && $tickets->total() > 0)
                    <span class="badge bg-secondary ms-2">{{ $tickets->total() }}</span>
                @endif
            </h5>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm" onclick="selectAll()">
                    <i class="fas fa-check-square me-1"></i>Tout sélectionner
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="clearSelection()">
                    <i class="fas fa-square me-1"></i>Désélectionner
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="30">
                                <input type="checkbox" id="select-all" onchange="toggleAll(this)">
                            </th>
                            <th>Code Billet</th>
                            <th>Événement</th>
                            <th>Type</th>
                            <th>Prix</th>
                            <th>Acheteur</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets ?? [] as $ticket)
                        <tr>
                            <td>
                                <input type="checkbox" class="ticket-checkbox" value="{{ $ticket->id }}">
                            </td>
                            <td>
                                <div class="fw-bold">
                                    <a href="{{ route('admin.tickets.show', $ticket) }}" class="text-decoration-none">
                                        {{ $ticket->ticket_code }}
                                    </a>
                                </div>
                                @if($ticket->seat_number)
                                    <small class="text-muted">Siège: {{ $ticket->seat_number }}</small>
                                @endif
                            </td>
                            <td>
                                @if($ticket->ticketType->event)
                                    <div>
                                        <div class="fw-semibold">{{ Str::limit($ticket->ticketType->event->title, 25) }}</div>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            {{ $ticket->ticketType->event->date?->format('d/m/Y') ?? 'Date non définie' }}
                                        </small>
                                    </div>
                                @else
                                    <span class="text-muted">Événement supprimé</span>
                                @endif
                            </td>
                            <td>{{ $ticket->ticketType->name ?? 'N/A' }}</td>
                            <td class="fw-bold text-primary">{{ number_format($ticket->ticketType->price ?? 0) }} F</td>
                            <td>
                                @php $order = $ticket->orderItem->first()?->order; @endphp
                                @if($order && $order->user)
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar-sm me-2">
                                            {{ substr($order->user->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="fw-semibold">{{ $order->user->name }}</div>
                                            <small class="text-muted">{{ $order->user->email }}</small>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge ticket-status {{ 
                                    $ticket->status == 'available' ? 'bg-secondary' :
                                    ($ticket->status == 'sold' ? 'bg-success' : 
                                    ($ticket->status == 'used' ? 'bg-info' : 
                                    ($ticket->status == 'cancelled' ? 'bg-danger' : 'bg-warning'))) 
                                }}">
                                    @switch($ticket->status)
                                        @case('available')
                                            <i class="fas fa-circle me-1"></i>Disponible
                                            @break
                                        @case('sold')
                                            <i class="fas fa-shopping-cart me-1"></i>Vendu
                                            @break
                                        @case('used')
                                            <i class="fas fa-check me-1"></i>Utilisé
                                            @break
                                        @case('cancelled')
                                            <i class="fas fa-times me-1"></i>Annulé
                                            @break
                                        @default
                                            {{ ucfirst($ticket->status) }}
                                    @endswitch
                                </span>
                            </td>
                            <td>
                                <div>{{ $ticket->created_at->format('d/m/Y') }}</div>
                                <small class="text-muted">{{ $ticket->created_at->format('H:i') }}</small>
                                @if($ticket->used_at)
                                    <div><small class="text-info">Utilisé: {{ $ticket->used_at->format('d/m/Y H:i') }}</small></div>
                                @endif
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" 
                                            type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.tickets.show', $ticket) }}">
                                                <i class="fas fa-eye me-2"></i>Voir détails
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.tickets.pdf', $ticket) }}" target="_blank">
                                                <i class="fas fa-file-pdf me-2"></i>Télécharger PDF
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        
                                        @if($ticket->status === 'sold')
                                        <li>
                                            <a class="dropdown-item text-info" href="#" 
                                               onclick="markAsUsed({{ $ticket->id }})">
                                                <i class="fas fa-check me-2"></i>Marquer utilisé
                                            </a>
                                        </li>
                                        @endif
                                        
                                        @if(in_array($ticket->status, ['available', 'sold']))
                                        <li>
                                            <a class="dropdown-item text-warning" href="#" 
                                               onclick="cancelTicket({{ $ticket->id }})">
                                                <i class="fas fa-times me-2"></i>Annuler
                                            </a>
                                        </li>
                                        @endif
                                        
                                        @if($ticket->status === 'cancelled')
                                        <li>
                                            <a class="dropdown-item text-success" href="#" 
                                               onclick="reactivateTicket({{ $ticket->id }})">
                                                <i class="fas fa-undo me-2"></i>Réactiver
                                            </a>
                                        </li>
                                        @endif
                                        
                                        @if($order && $order->user)
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.orders.show', $order) }}">
                                                <i class="fas fa-shopping-cart me-2"></i>Voir commande
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.users.show', $order->user) }}">
                                                <i class="fas fa-user me-2"></i>Voir acheteur
                                            </a>
                                        </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">Aucun billet trouvé</p>
                                <small class="text-muted">
                                    @if(request()->hasAny(['search', 'status', 'event_id', 'date_filter']))
                                        Essayez de modifier vos filtres de recherche
                                    @else
                                        Les billets apparaîtront ici une fois créés
                                    @endif
                                </small>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        @if(isset($tickets) && $tickets->hasPages())
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    Affichage de {{ $tickets->firstItem() }} à {{ $tickets->lastItem() }} 
                    sur {{ $tickets->total() }} billets
                </div>
                {{ $tickets->links() }}
            </div>
        </div>
        @endif
    </div>

    <!-- Actions groupées sélectionnées -->
    <div id="bulk-actions" class="position-fixed bottom-0 start-50 translate-middle-x bg-white border rounded-3 p-3 shadow-lg" style="display: none; z-index: 1050;">
        <div class="d-flex align-items-center gap-3">
            <span class="fw-bold">
                <span id="selected-count">0</span> billet(s) sélectionné(s)
            </span>
            <div class="d-flex gap-2">
                <button class="btn btn-info btn-sm" onclick="bulkAction('mark_used')">
                    <i class="fas fa-check me-1"></i>Marquer utilisés
                </button>
                <button class="btn btn-warning btn-sm" onclick="bulkAction('cancel')">
                    <i class="fas fa-times me-1"></i>Annuler
                </button>
                <button class="btn btn-success btn-sm" onclick="bulkAction('reactivate')">
                    <i class="fas fa-undo me-1"></i>Réactiver
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="clearSelection()">
                    <i class="fas fa-times me-1"></i>Fermer
                </button>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border: 1px solid #f0f0f0;
        transition: transform 0.2s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
    }
    
    .stat-icon.primary { background: linear-gradient(135deg, #007bff, #0056b3); }
    .stat-icon.success { background: linear-gradient(135deg, #28a745, #20c997); }
    .stat-icon.info { background: linear-gradient(135deg, #17a2b8, #138496); }
    .stat-icon.warning { background: linear-gradient(135deg, #ffc107, #fd7e14); }
    
    .stat-info h4 {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 5px;
        color: #2d3748;
    }
    
    .user-avatar-sm {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: linear-gradient(135deg, #FF6B35, #fd7e14);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 0.75rem;
        flex-shrink: 0;
    }
    
    .ticket-status {
        font-size: 0.75rem;
        padding: 6px 10px;
        font-weight: 500;
    }
    
    .table th {
        font-weight: 600;
        color: #4a5568;
        border-bottom: 2px solid #e2e8f0;
        background-color: #f8fafc;
    }
    
    .table-hover tbody tr:hover {
        background-color: #f8fafc;
    }
    
    .dropdown-menu {
        border: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        border-radius: 8px;
        min-width: 180px;
    }
    
    .dropdown-item {
        padding: 8px 16px;
        transition: background-color 0.2s ease;
        font-size: 0.9rem;
    }
    
    .dropdown-item:hover {
        background-color: #f8fafc;
    }
    
    .dropdown-item i {
        width: 16px;
        text-align: center;
    }
    
    #bulk-actions {
        border: 1px solid #e2e8f0;
        min-width: 500px;
        max-width: 90vw;
    }
    
    .card {
        border: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border-radius: 12px;
    }
    
    .card-header {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        border-radius: 12px 12px 0 0 !important;
    }
</style>
@endpush

@push('scripts')
<script>
    let selectedTickets = [];
    
    // Sélection/désélection
    function toggleAll(checkbox) {
        const checkboxes = document.querySelectorAll('.ticket-checkbox');
        checkboxes.forEach(cb => cb.checked = checkbox.checked);
        updateSelection();
    }
    
    function selectAll() {
        const checkboxes = document.querySelectorAll('.ticket-checkbox');
        checkboxes.forEach(cb => cb.checked = true);
        document.getElementById('select-all').checked = true;
        updateSelection();
    }
    
    function clearSelection() {
        const checkboxes = document.querySelectorAll('.ticket-checkbox');
        checkboxes.forEach(cb => cb.checked = false);
        document.getElementById('select-all').checked = false;
        updateSelection();
    }
    
    function updateSelection() {
        const checkboxes = document.querySelectorAll('.ticket-checkbox:checked');
        selectedTickets = Array.from(checkboxes).map(cb => cb.value);
        
        const count = selectedTickets.length;
        document.getElementById('selected-count').textContent = count;
        
        const bulkActions = document.getElementById('bulk-actions');
        if (count > 0) {
            bulkActions.style.display = 'block';
        } else {
            bulkActions.style.display = 'none';
        }
    }
    
    // Actions individuelles
    function markAsUsed(ticketId) {
        if (confirm('Êtes-vous sûr de vouloir marquer ce billet comme utilisé ?')) {
            fetch(`/admin/tickets/${ticketId}/mark-used`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la mise à jour');
            });
        }
    }
    
    function cancelTicket(ticketId) {
        const reason = prompt('Raison d\'annulation:');
        if (reason) {
            fetch(`/admin/tickets/${ticketId}/cancel`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ reason: reason })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de l\'annulation');
            });
        }
    }
    
    function reactivateTicket(ticketId) {
        if (confirm('Êtes-vous sûr de vouloir réactiver ce billet ?')) {
            fetch(`/admin/tickets/${ticketId}/reactivate`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la réactivation');
            });
        }
    }
    
    // Actions groupées
    function bulkAction(action) {
        if (selectedTickets.length === 0) {
            alert('Veuillez sélectionner au moins un billet');
            return;
        }
        
        const actionNames = {
            'mark_used': 'marquer comme utilisés',
            'cancel': 'annuler',
            'reactivate': 'réactiver',
            'delete': 'supprimer'
        };
        
        const confirmMessage = `Êtes-vous sûr de vouloir ${actionNames[action]} ${selectedTickets.length} billet(s) ?`;
        
        if (confirm(confirmMessage)) {
            fetch('/admin/tickets/bulk-action', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    tickets: selectedTickets,
                    action: action
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de l\'action groupée');
            });
        }
    }
    
    // Écouter les changements de sélection
    document.addEventListener('DOMContentLoaded', function() {
        const checkboxes = document.querySelectorAll('.ticket-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateSelection);
        });
    });
</script>
@endpush