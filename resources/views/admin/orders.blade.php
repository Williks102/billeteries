{{-- resources/views/admin/orders.blade.php --}}
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
            <p class="text-muted mb-0">Suivez et gérez toutes les commandes de la plateforme</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.export.orders') }}" class="btn btn-outline-orange">
                <i class="fas fa-download me-2"></i>Exporter
            </a>
            <div class="dropdown">
                <button class="btn btn-orange dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-cog me-2"></i>Actions groupées
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" onclick="bulkUpdate('completed')">
                        <i class="fas fa-check me-2"></i>Marquer comme terminées
                    </a></li>
                    <li><a class="dropdown-item" href="#" onclick="bulkUpdate('cancelled')">
                        <i class="fas fa-times me-2"></i>Annuler sélectionnées
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" onclick="bulkExport()">
                        <i class="fas fa-file-export me-2"></i>Exporter sélectionnées
                    </a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Statistiques des commandes -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon success me-3">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h4>{{ $stats['completed'] ?? 0 }}</h4>
                        <p class="text-muted mb-0">Terminées</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon warning me-3">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h4>{{ $stats['pending'] ?? 0 }}</h4>
                        <p class="text-muted mb-0">En attente</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon danger me-3">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h4>{{ $stats['cancelled'] ?? 0 }}</h4>
                        <p class="text-muted mb-0">Annulées</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon info me-3">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <h4>{{ number_format($stats['total_revenue'] ?? 0) }} F</h4>
                        <p class="text-muted mb-0">Chiffre d'affaires</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.orders') }}">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Recherche</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="N° commande, client..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Statut</label>
                        <select name="status" class="form-select">
                            <option value="">Tous</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Terminées</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Annulées</option>
                            <option value="refunded" {{ request('status') == 'refunded' ? 'selected' : '' }}>Remboursées</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Événement</label>
                        <select name="event_id" class="form-select">
                            <option value="">Tous</option>
                            @foreach($events ?? [] as $event)
                                <option value="{{ $event->id }}" {{ request('event_id') == $event->id ? 'selected' : '' }}>
                                    {{ Str::limit($event->title, 20) }}
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
                        <label class="form-label">Montant</label>
                        <select name="amount_filter" class="form-select">
                            <option value="">Tous</option>
                            <option value="0-10000" {{ request('amount_filter') == '0-10000' ? 'selected' : '' }}>0 - 10 000 F</option>
                            <option value="10000-50000" {{ request('amount_filter') == '10000-50000' ? 'selected' : '' }}>10 000 - 50 000 F</option>
                            <option value="50000+" {{ request('amount_filter') == '50000+' ? 'selected' : '' }}>50 000 F+</option>
                        </select>
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
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-shopping-cart me-2"></i>
                Liste des commandes
                @if(isset($orders) && $orders->total() > 0)
                    <span class="badge bg-secondary ms-2">{{ $orders->total() }}</span>
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
                            <th>N° Commande</th>
                            <th>Client</th>
                            <th>Événement</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Paiement</th>
                            <th>Date</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders ?? [] as $order)
                        <tr>
                            <td>
                                <input type="checkbox" class="order-checkbox" value="{{ $order->id }}">
                            </td>
                            <td>
                                <div class="fw-bold">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="text-decoration-none">
                                        #{{ $order->order_number ?? $order->id }}
                                    </a>
                                </div>
                                <small class="text-muted">{{ $order->reference ?? '' }}</small>
                            </td>
                            <td>
                                @if($order->user)
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-2">
                                            {{ substr($order->user->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="fw-semibold">{{ $order->user->name }}</div>
                                            <small class="text-muted">{{ $order->user->email }}</small>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">Client supprimé</span>
                                @endif
                            </td>
                            <td>
                                @if($order->event)
                                    <div>
                                        <div class="fw-semibold">{{ Str::limit($order->event->title, 25) }}</div>
                                        <small class="text-muted">{{ $order->event->date?->format('d/m/Y') ?? 'Date non définie' }}</small>
                                    </div>
                                @else
                                    <span class="text-muted">Événement supprimé</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold text-primary">{{ number_format($order->total_amount ?? 0) }} F</div>
                                @if($order->discount_amount > 0)
                                    <small class="text-success">-{{ number_format($order->discount_amount) }} F</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ 
                                    $order->status == 'completed' ? 'bg-success' : 
                                    ($order->status == 'pending' ? 'bg-warning' : 
                                    ($order->status == 'cancelled' ? 'bg-danger' : 'bg-secondary')) 
                                }}">
                                    {{ ucfirst($order->status ?? 'pending') }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ 
                                    $order->payment_status == 'paid' ? 'bg-success' : 
                                    ($order->payment_status == 'pending' ? 'bg-warning' : 'bg-danger') 
                                }}">
                                    {{ ucfirst($order->payment_status ?? 'pending') }}
                                </span>
                                <br>
                                <small class="text-muted">{{ $order->payment_method ?? 'N/A' }}</small>
                            </td>
                            <td>
                                <div>{{ $order->created_at->format('d/m/Y') }}</div>
                                <small class="text-muted">{{ $order->created_at->format('H:i') }}</small>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" 
                                            type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.orders.show', $order) }}">
                                                <i class="fas fa-eye me-2"></i>Voir détails
                                            </a>
                                        </li>
                                        @if($order->status == 'pending')
                                        <li>
                                            <a class="dropdown-item text-success" href="#" 
                                               onclick="updateOrderStatus({{ $order->id }}, 'completed')">
                                                <i class="fas fa-check me-2"></i>Confirmer
                                            </a>
                                        </li>
                                        @endif
                                        @if($order->status != 'cancelled')
                                        <li>
                                            <a class="dropdown-item text-warning" href="#" 
                                               onclick="updateOrderStatus({{ $order->id }}, 'cancelled')">
                                                <i class="fas fa-times me-2"></i>Annuler
                                            </a>
                                        </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">Aucune commande trouvée</p>
                                <small class="text-muted">Les commandes apparaîtront ici une fois créées</small>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        @if(isset($orders) && $orders->hasPages())
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    Affichage de {{ $orders->firstItem() }} à {{ $orders->lastItem() }} 
                    sur {{ $orders->total() }} commandes
                </div>
                {{ $orders->links() }}
            </div>
        </div>
        @endif
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
    
    .stat-icon.success { background: linear-gradient(135deg, #28a745, #20c997); }
    .stat-icon.warning { background: linear-gradient(135deg, #ffc107, #fd7e14); }
    .stat-icon.danger { background: linear-gradient(135deg, #dc3545, #e83e8c); }
    .stat-icon.info { background: linear-gradient(135deg, #17a2b8, #6f42c1); }
    
    .stat-info h4 {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 5px;
        color: #2d3748;
    }
    
    .user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg, #FF6B35, #fd7e14);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 0.85rem;
    }
</style>
@endpush

@push('scripts')
<script>
    let selectedOrders = [];
    
    function toggleAll(checkbox) {
        const checkboxes = document.querySelectorAll('.order-checkbox');
        checkboxes.forEach(cb => cb.checked = checkbox.checked);
        updateSelection();
    }
    
    function selectAll() {
        const checkboxes = document.querySelectorAll('.order-checkbox');
        checkboxes.forEach(cb => cb.checked = true);
        document.getElementById('select-all').checked = true;
        updateSelection();
    }
    
    function clearSelection() {
        const checkboxes = document.querySelectorAll('.order-checkbox');
        checkboxes.forEach(cb => cb.checked = false);
        document.getElementById('select-all').checked = false;
        updateSelection();
    }
    
    function updateSelection() {
        const checkboxes = document.querySelectorAll('.order-checkbox:checked');
        selectedOrders = Array.from(checkboxes).map(cb => cb.value);
        console.log('Sélection mise à jour:', selectedOrders);
    }
    
    function updateOrderStatus(orderId, status) {
        if (confirm(`Êtes-vous sûr de vouloir changer le statut de cette commande ?`)) {
            // Ici vous pouvez ajouter la logique AJAX pour mettre à jour le statut
            console.log('Mise à jour statut:', orderId, status);
        }
    }
    
    function bulkUpdate(status) {
        if (selectedOrders.length === 0) {
            alert('Veuillez sélectionner au moins une commande');
            return;
        }
        
        if (confirm(`Êtes-vous sûr de vouloir modifier ${selectedOrders.length} commande(s) ?`)) {
            console.log('Mise à jour groupée:', selectedOrders, status);
        }
    }
    
    function bulkExport() {
        if (selectedOrders.length === 0) {
            alert('Veuillez sélectionner au moins une commande');
            return;
        }
        
        console.log('Export groupé:', selectedOrders);
    }
    
    // Écouter les changements de sélection
    document.addEventListener('DOMContentLoaded', function() {
        const checkboxes = document.querySelectorAll('.order-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateSelection);
        });
    });
</script>
@endpush