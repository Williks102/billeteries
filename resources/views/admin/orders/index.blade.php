{{-- resources/views/admin/orders/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Gestion des commandes - ClicBillet CI')

@section('breadcrumb')
    <li class="breadcrumb-item active">Commandes</li>
@endsection

@section('content')
    <!-- Header avec actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Gestion des commandes</h2>
            <p class="text-muted mb-0">Gérez toutes les commandes de la plateforme</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.orders.export', request()->all()) }}" class="btn btn-outline-orange">
                <i class="fas fa-download me-2"></i>Exporter
            </a>
            <button type="button" class="btn btn-orange" data-bs-toggle="modal" data-bs-target="#bulkActionModal">
                <i class="fas fa-cogs me-2"></i>Actions groupées
            </button>
        </div>
    </div>

    <!-- Statistiques des commandes -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-primary me-3">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div>
                            <h4 class="mb-1">{{ $stats['total'] ?? 0 }}</h4>
                            <p class="text-muted mb-0 small">Total commandes</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-success me-3">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div>
                            <h4 class="mb-1">{{ $stats['paid'] ?? 0 }}</h4>
                            <p class="text-muted mb-0 small">Payées</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-warning me-3">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <h4 class="mb-1">{{ $stats['pending'] ?? 0 }}</h4>
                            <p class="text-muted mb-0 small">En attente</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-info me-3">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div>
                            <h4 class="mb-1">{{ number_format($stats['total_revenue'] ?? 0) }}</h4>
                            <p class="text-muted mb-0 small">Revenus (FCFA)</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.orders.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small">Recherche</label>
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="N° commande, client, email..." 
                               value="{{ request('search') }}">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label small">Statut</label>
                        <select name="payment_status" class="form-select">
                            <option value="">Tous les statuts</option>
                            <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Payé</option>
                            <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>En attente</option>
                            <option value="failed" {{ request('payment_status') == 'failed' ? 'selected' : '' }}>Échoué</option>
                            <option value="refunded" {{ request('payment_status') == 'refunded' ? 'selected' : '' }}>Remboursé</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label small">Événement</label>
                        <select name="event" class="form-select">
                            <option value="">Tous les événements</option>
                            @foreach($events ?? [] as $event)
                                <option value="{{ $event->id }}" {{ request('event') == $event->id ? 'selected' : '' }}>
                                    {{ Str::limit($event->title, 30) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label small">Date début</label>
                        <input type="date" 
                               name="date_from" 
                               class="form-control" 
                               value="{{ request('date_from') }}">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label small">Date fin</label>
                        <input type="date" 
                               name="date_to" 
                               class="form-control" 
                               value="{{ request('date_to') }}">
                    </div>
                    
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-orange w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des commandes -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-list me-2 text-primary"></i>
                Liste des commandes
            </h5>
            <span class="badge bg-light text-dark">{{ $orders->total() }} résultat(s)</span>
        </div>
        
        <div class="card-body p-0">
            @if($orders->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th width="40">
                                    <input type="checkbox" class="form-check-input" id="select-all">
                                </th>
                                <th>N° Commande</th>
                                <th>Client</th>
                                <th>Événement</th>
                                <th>Montant</th>
                                <th>Statut</th>
                                <th>Date</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                                <tr>
                                    <td>
                                        <input type="checkbox" 
                                               class="form-check-input order-checkbox" 
                                               value="{{ $order->id }}">
                                    </td>
                                    <td>
                                        <div class="fw-semibold">#{{ $order->order_number ?? $order->id }}</div>
                                        <small class="text-muted">{{ $order->created_at->format('d/m/Y H:i') }}</small>
                                    </td>
                                    <td>
                                        <div>{{ $order->user->name ?? 'N/A' }}</div>
                                        <small class="text-muted">{{ $order->user->email ?? 'N/A' }}</small>
                                    </td>
                                    <td>
                                        @if($order->event)
                                            <div class="fw-medium">{{ Str::limit($order->event->title, 30) }}</div>
                                            <small class="text-muted">
                                                {{ $order->event->event_date ? $order->event->event_date->format('d/m/Y') : 'Date TBD' }}
                                            </small>
                                        @else
                                            <span class="text-muted">Événement supprimé</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="fw-bold text-primary">{{ number_format($order->total_amount ?? 0) }} FCFA</span>
                                        @if($order->orderItems && $order->orderItems->count() > 0)
                                            <br><small class="text-muted">{{ $order->orderItems->sum('quantity') }} billet(s)</small>
                                        @endif
                                    </td>
                                    <td>
                                        @switch($order->payment_status)
                                            @case('paid')
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check-circle me-1"></i>Payé
                                                </span>
                                                @break
                                            @case('pending')
                                                <span class="badge bg-warning">
                                                    <i class="fas fa-clock me-1"></i>En attente
                                                </span>
                                                @break
                                            @case('failed')
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-times-circle me-1"></i>Échoué
                                                </span>
                                                @break
                                            @case('refunded')
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-undo me-1"></i>Remboursé
                                                </span>
                                                @break
                                            @default
                                                <span class="badge bg-light text-dark">{{ ucfirst($order->payment_status ?? 'unknown') }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        <div>{{ $order->created_at->format('d/m/Y') }}</div>
                                        <small class="text-muted">{{ $order->created_at->format('H:i') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.orders.show', $order) }}" 
                                               class="btn btn-sm btn-outline-info" 
                                               title="Voir détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            @if($order->payment_status !== 'paid')
                                                <a href="{{ route('admin.orders.edit', $order) }}" 
                                                   class="btn btn-sm btn-outline-primary" 
                                                   title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endif
                                            
                                            @if($order->payment_status === 'paid')
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-success" 
                                                        onclick="resendEmail({{ $order->id }})" 
                                                        title="Renvoyer email">
                                                    <i class="fas fa-envelope"></i>
                                                </button>
                                            @endif
                                            
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                        type="button" 
                                                        data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    @if($order->payment_status === 'pending')
                                                        <li>
                                                            <button class="dropdown-item" onclick="updateStatus({{ $order->id }}, 'paid')">
                                                                <i class="fas fa-check text-success me-2"></i>Marquer comme payé
                                                            </button>
                                                        </li>
                                                        <li>
                                                            <button class="dropdown-item" onclick="updateStatus({{ $order->id }}, 'failed')">
                                                                <i class="fas fa-times text-danger me-2"></i>Marquer comme échoué
                                                            </button>
                                                        </li>
                                                    @endif
                                                    
                                                    @if($order->payment_status === 'paid')
                                                        <li>
                                                            <button class="dropdown-item text-danger" onclick="refundOrder({{ $order->id }})">
                                                                <i class="fas fa-undo me-2"></i>Rembourser
                                                            </button>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="card-footer bg-white">
                    {{ $orders->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Aucune commande trouvée</h5>
                    <p class="text-muted">Aucune commande ne correspond aux critères de recherche.</p>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('styles')
<style>
.stat-card {
    transition: all 0.3s ease;
    cursor: pointer;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

.table th {
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
}

.order-checkbox {
    cursor: pointer;
}

.btn-group .btn {
    border-radius: 4px;
}

.dropdown-menu {
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}
</style>
@endpush

@push('scripts')
<script>
// Sélection multiple
document.getElementById('select-all').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.order-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    updateSelectedCount();
});

document.querySelectorAll('.order-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateSelectedCount);
});

function updateSelectedCount() {
    const selected = document.querySelectorAll('.order-checkbox:checked').length;
    const countElement = document.getElementById('selected-count');
    if (countElement) {
        countElement.textContent = `${selected} commande(s) sélectionnée(s)`;
    }
}

// Mise à jour du statut
function updateStatus(orderId, status) {
    if (!confirm(`Êtes-vous sûr de vouloir changer le statut de cette commande ?`)) {
        return;
    }

    fetch(`/admin/orders/${orderId}/status`, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ payment_status: status })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erreur : ' + (data.message || 'Une erreur est survenue'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la mise à jour');
    });
}

// Renvoyer email
function resendEmail(orderId) {
    if (!confirm('Renvoyer l\'email de confirmation à ce client ?')) {
        return;
    }

    fetch(`/admin/orders/${orderId}/resend-email`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Email renvoyé avec succès !');
        } else {
            alert('Erreur : ' + (data.message || 'Impossible d\'envoyer l\'email'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de l\'envoi de l\'email');
    });
}

// Remboursement
function refundOrder(orderId) {
    const reason = prompt('Raison du remboursement (optionnel):');
    if (reason === null) return; // Annulé
    
    if (!confirm('Êtes-vous sûr de vouloir rembourser cette commande ? Cette action est irréversible.')) {
        return;
    }

    fetch(`/admin/orders/${orderId}/refund`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ reason: reason })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erreur : ' + (data.message || 'Impossible de rembourser'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors du remboursement');
    });
}
</script>
@endpush