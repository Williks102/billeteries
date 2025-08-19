{{-- resources/views/admin/orders.blade.php (CORRIGÉ) --}}
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
            <a href="{{ route('admin.export.orders') }}" class="btn btn-outline-orange">
                <i class="fas fa-download me-2"></i>Exporter
            </a>
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
                        <p class="text-muted mb-0">Payées</p>
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
                        <p class="text-muted mb-0">Échouées</p>
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
                    <div class="col-md-4">
                        <label class="form-label">Recherche</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="N° commande, client..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Statut de paiement</label>
                        <select name="status" class="form-select">
                            <option value="">Tous les statuts</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                            <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Payée</option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Échouée</option>
                            <option value="refunded" {{ request('status') == 'refunded' ? 'selected' : '' }}>Remboursée</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Période</label>
                        <select name="period" class="form-select">
                            <option value="">Toutes les périodes</option>
                            <option value="today" {{ request('period') == 'today' ? 'selected' : '' }}>Aujourd'hui</option>
                            <option value="week" {{ request('period') == 'week' ? 'selected' : '' }}>Cette semaine</option>
                            <option value="month" {{ request('period') == 'month' ? 'selected' : '' }}>Ce mois</option>
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

    <!-- Actions en lot -->
    <div class="card mb-4" id="bulkActions" style="display: none;">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span id="selectedCount">0</span> commande(s) sélectionnée(s)
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-warning btn-sm" onclick="bulkUpdateStatus('paid')">
                        <i class="fas fa-check me-2"></i>Marquer comme payées
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="bulkUpdateStatus('failed')">
                        <i class="fas fa-times me-2"></i>Marquer comme échouées
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des commandes -->
    <div class="card">
        <div class="card-header bg-white border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list text-orange me-2"></i>
                    Liste des commandes
                </h5>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="selectAll">
                    <label class="form-check-label" for="selectAll">Tout sélectionner</label>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="50px">
                                <input type="checkbox" class="form-check-input" id="selectAllTable">
                            </th>
                            <th>Commande</th>
                            <th>Client</th>
                            <th>Événement</th>
                            <th>Montant</th>
                            <th>Statut de paiement</th>
                            <th>Date</th>
                            <th width="100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders ?? [] as $order)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input order-checkbox" 
                                           value="{{ $order->id }}">
                                </td>
                                <td>
                                    <div class="fw-bold">
                                        <a href="{{ route('admin.orders.show', $order) }}" class="text-decoration-none">
                                            #{{ $order->order_number ?? $order->id }}
                                        </a>
                                    </div>
                                    @if($order->payment_reference)
                                        <small class="text-muted">Réf: {{ $order->payment_reference }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($order->user)
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
                                        <span class="text-muted">Utilisateur supprimé</span>
                                    @endif
                                </td>
                                <td>
                                    @if($order->event)
                                        <div>
                                            <div class="fw-semibold">{{ Str::limit($order->event->title, 30) }}</div>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                {{ $order->event->event_date?->format('d/m/Y') ?? 'Date non définie' }}
                                            </small>
                                        </div>
                                    @else
                                        <span class="text-muted">Événement supprimé</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-bold text-primary">{{ number_format($order->total_amount ?? 0) }} F</div>
                                    @if($order->billing_phone)
                                        <small class="text-muted">{{ $order->billing_phone }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge payment-status {{ 
                                        $order->payment_status == 'paid' ? 'bg-success' : 
                                        ($order->payment_status == 'pending' ? 'bg-warning' : 
                                        ($order->payment_status == 'refunded' ? 'bg-info' : 'bg-danger'))
                                    }}">
                                        @switch($order->payment_status)
                                            @case('paid')
                                                <i class="fas fa-check-circle me-1"></i>Payée
                                                @break
                                            @case('pending')
                                                <i class="fas fa-clock me-1"></i>En attente
                                                @break
                                            @case('failed')
                                                <i class="fas fa-times-circle me-1"></i>Échouée
                                                @break
                                            @case('refunded')
                                                <i class="fas fa-undo me-1"></i>Remboursée
                                                @break
                                            @default
                                                <i class="fas fa-question-circle me-1"></i>Inconnu
                                        @endswitch
                                    </span>
                                    @if($order->payment_method)
                                        <br><small class="text-muted">{{ ucfirst($order->payment_method) }}</small>
                                    @endif
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
                                            @if($order->payment_status == 'pending')
                                                <li>
                                                    <a class="dropdown-item text-success" href="#" 
                                                       onclick="updateOrderStatus({{ $order->id }}, 'paid')">
                                                        <i class="fas fa-check me-2"></i>Marquer payée
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item text-danger" href="#" 
                                                       onclick="updateOrderStatus({{ $order->id }}, 'failed')">
                                                        <i class="fas fa-times me-2"></i>Marquer échouée
                                                    </a>
                                                </li>
                                            @endif
                                            @if($order->payment_status == 'paid')
                                                <li>
                                                    <a class="dropdown-item text-warning" href="#" 
                                                       onclick="updateOrderStatus({{ $order->id }}, 'refunded')">
                                                        <i class="fas fa-undo me-2"></i>Rembourser
                                                    </a>
                                                </li>
                                            @endif
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('admin.orders.pdf', $order) }}">
                                                    <i class="fas fa-file-pdf me-2"></i>Télécharger PDF
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-shopping-cart fa-2x text-muted mb-2"></i>
                                    <p class="text-muted mb-0">Aucune commande trouvée</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        @if(isset($orders) && $orders->hasPages())
            <div class="card-footer bg-white">
                {{ $orders->appends(request()->query())->links() }}
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
    
    .stat-icon.primary { background: linear-gradient(135deg, #007bff, #0056b3); }
    .stat-icon.success { background: linear-gradient(135deg, #28a745, #20c997); }
    .stat-icon.info { background: linear-gradient(135deg, #17a2b8, #138496); }
    .stat-icon.warning { background: linear-gradient(135deg, #ffc107, #fd7e14); }
    .stat-icon.danger { background: linear-gradient(135deg, #dc3545, #c82333); }
    
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
    
    .payment-status {
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
    
    .card {
        border: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        border-radius: 12px;
    }
    
    .card-header {
        border-radius: 12px 12px 0 0 !important;
        padding: 1rem 1.25rem;
    }
</style>
@endpush

@push('scripts')
<script>
    // Sélection multiple
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.order-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkActions();
    });
    
    document.getElementById('selectAllTable').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.order-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkActions();
    });
    
    document.querySelectorAll('.order-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActions);
    });
    
    function updateBulkActions() {
        const selectedBoxes = document.querySelectorAll('.order-checkbox:checked');
        const bulkActions = document.getElementById('bulkActions');
        const selectedCount = document.getElementById('selectedCount');
        
        if (selectedBoxes.length > 0) {
            bulkActions.style.display = 'block';
            selectedCount.textContent = selectedBoxes.length;
        } else {
            bulkActions.style.display = 'none';
        }
    }
    
    // Mise à jour du statut d'une commande
    function updateOrderStatus(orderId, status) {
        if (confirm(`Êtes-vous sûr de vouloir changer le statut de cette commande ?`)) {
            fetch(`/admin/orders/${orderId}/status`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ payment_status: status })
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
                alert('Une erreur s\'est produite');
            });
        }
    }
    
    // Mise à jour en lot
    function bulkUpdateStatus(status) {
        const selectedBoxes = document.querySelectorAll('.order-checkbox:checked');
        const orderIds = Array.from(selectedBoxes).map(checkbox => checkbox.value);
        
        if (orderIds.length === 0) {
            alert('Veuillez sélectionner au moins une commande');
            return;
        }
        
        if (confirm(`Êtes-vous sûr de vouloir changer le statut de ${orderIds.length} commande(s) ?`)) {
            fetch('/admin/orders/bulk-update', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_ids: orderIds,
                    payment_status: status
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
                alert('Une erreur s\'est produite');
            });
        }
    }
</script>
@endpush