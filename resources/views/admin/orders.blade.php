@extends('layouts.app')

@section('title', 'Administration - ClicBillet CI')
@section('body-class', 'admin-page')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar Admin -->
        <div class="col-md-3 col-lg-2">
            <div class="admin-sidebar bg-dark text-white rounded p-3 sticky-top">
                <h5 class="mb-4">
                    <i class="fas fa-shield-alt text-orange me-2"></i>
                    Administration
                </h5>
                
                <nav class="nav flex-column">
                    <a class="nav-link text-light" href="{{ route('admin.dashboard') }}">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a class="nav-link text-light" href="{{ route('admin.users') }}">
                        <i class="fas fa-users me-2"></i>Utilisateurs
                    </a>
                    <a class="nav-link text-light" href="{{ route('admin.events') }}">
                        <i class="fas fa-calendar me-2"></i>Événements
                    </a>
                    <a class="nav-link active text-white" href="{{ route('admin.orders') }}">
                        <i class="fas fa-shopping-cart me-2"></i>Commandes
                    </a>
                    <a class="nav-link text-light" href="{{ route('admin.commissions') }}">
                        <i class="fas fa-coins me-2"></i>Commissions
                    </a>
                    <a class="nav-link text-light" href="{{ route('admin.reports') }}">
                        <i class="fas fa-chart-line me-2"></i>Rapports
                    </a>
                    
                    <hr class="my-3" style="border-color: #444;">
                    
                    <a class="nav-link text-light" href="{{ route('home') }}">
                        <i class="fas fa-eye me-2"></i>Voir le site
                    </a>
                </nav>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="col-md-9 col-lg-10">
            <!-- En-tête -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Gestion des commandes</h2>
                    <p class="text-muted mb-0">Gérez toutes les commandes de la plateforme</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-download me-2"></i>Export
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('admin.export.orders') }}"><i class="fas fa-file-excel me-2"></i>Excel</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-file-csv me-2"></i>CSV</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-receipt me-2"></i>Rapport de ventes</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Statistiques rapides -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-primary">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="stat-info ms-3">
                                <h4 class="mb-0">{{ $orders->total() }}</h4>
                                <p class="text-muted mb-0">Total commandes</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-info ms-3">
                                <h4 class="mb-0">{{ $orders->where('payment_status', 'paid')->count() }}</h4>
                                <p class="text-muted mb-0">Payées</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-warning">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-info ms-3">
                                <h4 class="mb-0">{{ $orders->where('payment_status', 'pending')->count() }}</h4>
                                <p class="text-muted mb-0">En attente</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-info">
                                <i class="fas fa-euro-sign"></i>
                            </div>
                            <div class="stat-info ms-3">
                                <h4 class="mb-0">{{ number_format($orders->where('payment_status', 'paid')->sum('total_amount'), 0, ',', ' ') }}</h4>
                                <p class="text-muted mb-0">CA (FCFA)</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres et recherche -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.orders') }}" class="row align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Rechercher</label>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="N° commande, client..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Statut</label>
                            <select name="status" class="form-select">
                                <option value="">Tous</option>
                                <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Payé</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                                <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Échoué</option>
                                <option value="refunded" {{ request('status') == 'refunded' ? 'selected' : '' }}>Remboursé</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Période</label>
                            <select name="period" class="form-select">
                                <option value="">Toutes</option>
                                <option value="today" {{ request('period') == 'today' ? 'selected' : '' }}>Aujourd'hui</option>
                                <option value="week" {{ request('period') == 'week' ? 'selected' : '' }}>Cette semaine</option>
                                <option value="month" {{ request('period') == 'month' ? 'selected' : '' }}>Ce mois</option>
                                <option value="year" {{ request('period') == 'year' ? 'selected' : '' }}>Cette année</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Montant</label>
                            <select name="amount" class="form-select">
                                <option value="">Tous</option>
                                <option value="0-10000" {{ request('amount') == '0-10000' ? 'selected' : '' }}>0 - 10K</option>
                                <option value="10000-50000" {{ request('amount') == '10000-50000' ? 'selected' : '' }}>10K - 50K</option>
                                <option value="50000-100000" {{ request('amount') == '50000-100000' ? 'selected' : '' }}>50K - 100K</option>
                                <option value="100000+" {{ request('amount') == '100000+' ? 'selected' : '' }}>100K+</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Filtrer
                            </button>
                        </div>
                        <div class="col-md-1">
                            <a href="{{ route('admin.orders') }}" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-refresh"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Liste des commandes -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-shopping-cart text-orange me-2"></i>
                            Liste des commandes ({{ $orders->total() }})
                        </h5>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-cog"></i> Actions
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#"><i class="fas fa-check me-2"></i>Marquer comme payé</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-times me-2"></i>Marquer comme échoué</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-envelope me-2"></i>Envoyer notification</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($orders->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>
                                            <input type="checkbox" class="form-check-input" id="selectAll">
                                        </th>
                                        <th>Commande</th>
                                        <th>Client</th>
                                        <th>Événement</th>
                                        <th>Billets</th>
                                        <th>Montant</th>
                                        <th>Statut</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($orders as $order)
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="form-check-input order-checkbox" value="{{ $order->id }}">
                                            </td>
                                            <td>
                                                <div>
                                                    <strong>#{{ $order->order_number ?? $order->id }}</strong><br>
                                                    <small class="text-muted">ID: {{ $order->id }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <span class="fw-medium">{{ $order->user->name ?? 'Client supprimé' }}</span><br>
                                                    <small class="text-muted">{{ $order->billing_email ?? $order->user->email ?? 'N/A' }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    @if($order->event && $order->event->image)
                                                        <img src="{{ asset('storage/' . $order->event->image) }}" 
                                                             class="event-thumbnail me-3" alt="{{ $order->event->title }}">
                                                    @else
                                                        <div class="event-thumbnail-placeholder me-3">
                                                            <i class="fas fa-calendar-alt"></i>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <h6 class="mb-0">{{ Str::limit($order->event->title ?? 'Événement supprimé', 30) }}</h6>
                                                        <small class="text-muted">{{ $order->event->promoteur->name ?? 'N/A' }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-center">
                                                    <strong class="text-primary">{{ $order->orderItems->sum('quantity') ?? 0 }}</strong><br>
                                                    <small class="text-muted">billets</small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-end">
                                                    <strong class="text-orange">{{ number_format($order->total_amount, 0, ',', ' ') }} FCFA</strong><br>
                                                    <small class="text-muted">{{ $order->payment_method ?? 'N/A' }}</small>
                                                </div>
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
                                                    @case('refunded')
                                                        <span class="badge bg-info">Remboursé</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary">{{ ucfirst($order->payment_status) }}</span>
                                                @endswitch
                                            </td>
                                            <td>
                                                <div>
                                                    <strong>{{ $order->created_at->format('d/m/Y') }}</strong><br>
                                                    <small class="text-muted">{{ $order->created_at->format('H:i') }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('admin.orders.show', $order) }}" 
                                                       class="btn btn-outline-primary" title="Voir détails">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <div class="dropdown">
                                                        <button class="btn btn-outline-secondary dropdown-toggle" 
                                                                type="button" data-bs-toggle="dropdown">
                                                            <i class="fas fa-cog"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            @if($order->payment_status == 'pending')
                                                                <li>
                                                                    <button class="dropdown-item" onclick="updateOrderStatus({{ $order->id }}, 'paid')">
                                                                        <i class="fas fa-check text-success me-2"></i>Marquer comme payé
                                                                    </button>
                                                                </li>
                                                                <li>
                                                                    <button class="dropdown-item" onclick="updateOrderStatus({{ $order->id }}, 'failed')">
                                                                        <i class="fas fa-times text-danger me-2"></i>Marquer comme échoué
                                                                    </button>
                                                                </li>
                                                            @endif
                                                            @if($order->payment_status == 'paid')
                                                                <li>
                                                                    <button class="dropdown-item" onclick="updateOrderStatus({{ $order->id }}, 'refunded')">
                                                                        <i class="fas fa-undo text-warning me-2"></i>Rembourser
                                                                    </button>
                                                                </li>
                                                            @endif
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <a class="dropdown-item" href="mailto:{{ $order->user->email ?? $order->billing_email }}">
                                                                    <i class="fas fa-envelope me-2"></i>Contacter client
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <button class="dropdown-item" onclick="downloadOrderPDF({{ $order->id }})">
                                                                    <i class="fas fa-download me-2"></i>Télécharger PDF
                                                                </button>
                                                            </li>
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
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted">
                                        Affichage de {{ $orders->firstItem() }} à {{ $orders->lastItem() }} 
                                        sur {{ $orders->total() }} commandes
                                    </small>
                                </div>
                                <div>
                                    {{ $orders->links() }}
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Aucune commande trouvée</h5>
                            <p class="text-muted">Modifiez vos critères de recherche ou attendez les nouvelles commandes</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Actions groupées -->
            <div class="card border-0 shadow-sm mt-4" id="bulkActions" style="display: none;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong id="selectedCount">0</strong> commande(s) sélectionnée(s)
                        </div>
                        <div class="btn-group">
                            <button class="btn btn-success btn-sm" onclick="bulkUpdateStatus('paid')">
                                <i class="fas fa-check me-1"></i>Marquer comme payé
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="bulkUpdateStatus('failed')">
                                <i class="fas fa-times me-1"></i>Marquer comme échoué
                            </button>
                            <button class="btn btn-warning btn-sm" onclick="bulkUpdateStatus('refunded')">
                                <i class="fas fa-undo me-1"></i>Rembourser
                            </button>
                            <button class="btn btn-info btn-sm" onclick="bulkExport()">
                                <i class="fas fa-download me-1"></i>Exporter sélection
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
/* Variables CSS */
:root {
    --primary-orange: #FF6B35;
    --secondary-orange: #ff8c61;
    --dark-blue: #1a237e;
    --light-gray: #f8f9fa;
}

.admin-page {
    background-color: #f8f9fa;
}

/* Sidebar admin */
.admin-sidebar {
    background: linear-gradient(135deg, #2c3e50, #34495e) !important;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.admin-sidebar .nav-link {
    color: rgba(255,255,255,0.8) !important;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 5px;
    transition: all 0.3s ease;
}

.admin-sidebar .nav-link:hover,
.admin-sidebar .nav-link.active {
    background: var(--primary-orange) !important;
    color: white !important;
}

.text-orange {
    color: var(--primary-orange) !important;
}

/* Cards et statistiques */
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
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: white;
}

.stat-info h4 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dark-blue);
}

/* Vignettes d'événements */
.event-thumbnail {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 8px;
}

.event-thumbnail-placeholder {
    width: 50px;
    height: 50px;
    background: #f8f9fa;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
}

/* Cards génériques */
.card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.card-header {
    border-radius: 12px 12px 0 0 !important;
    border-bottom: 1px solid #e9ecef;
}

/* Tableaux */
.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
    font-size: 0.9rem;
}

.table td {
    vertical-align: middle;
    font-size: 0.9rem;
}

/* Badges */
.badge {
    font-size: 0.75rem;
    padding: 0.4em 0.8em;
}

/* Checkboxes */
.order-checkbox:checked {
    background-color: var(--primary-orange);
    border-color: var(--primary-orange);
}

/* Actions groupées */
#bulkActions {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border: 1px solid var(--primary-orange);
}

/* Responsive */
@media (max-width: 768px) {
    .admin-sidebar {
        margin-bottom: 2rem;
    }
    
    .stat-card {
        margin-bottom: 1rem;
    }
    
    .table-responsive {
        font-size: 0.8rem;
    }
}
</style>
@endpush

@push('scripts')
<script>
// Gestion de la sélection multiple
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.order-checkbox');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');

    // Sélectionner/désélectionner tout
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkActions();
        });
    }

    // Gestion des checkboxes individuelles
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActions);
    });

    function updateBulkActions() {
        const selectedBoxes = document.querySelectorAll('.order-checkbox:checked');
        const count = selectedBoxes.length;
        
        if (selectedCount) {
            selectedCount.textContent = count;
        }
        
        if (bulkActions) {
            if (count > 0) {
                bulkActions.style.display = 'block';
            } else {
                bulkActions.style.display = 'none';
            }
        }

        // Mettre à jour l'état du checkbox "Tout sélectionner"
        if (selectAll) {
            selectAll.indeterminate = count > 0 && count < checkboxes.length;
            selectAll.checked = count === checkboxes.length;
        }
    }
});

function updateOrderStatus(orderId, status) {
    const statusLabels = {
        'paid': 'payée',
        'failed': 'échouée',
        'refunded': 'remboursée',
        'pending': 'en attente'
    };
    
    const label = statusLabels[status] || status;
    
    if (confirm(`Êtes-vous sûr de vouloir marquer cette commande comme ${label} ?`)) {
        fetch(`/admin/orders/${orderId}/status`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ status: status })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(`Commande marquée comme ${label}`, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Erreur lors de la mise à jour', 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showNotification('Erreur lors de la mise à jour', 'error');
        });
    }
}

function bulkUpdateStatus(status) {
    const selectedOrders = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
    
    if (selectedOrders.length === 0) {
        alert('Veuillez sélectionner au moins une commande');
        return;
    }

    const statusLabels = {
        'paid': 'payées',
        'failed': 'échouées',
        'refunded': 'remboursées'
    };
    
    const label = statusLabels[status] || status;
    
    if (confirm(`Êtes-vous sûr de vouloir marquer ${selectedOrders.length} commande(s) comme ${label} ?`)) {
        fetch('/admin/orders/bulk-update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                order_ids: selectedOrders,
                status: status
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(`${selectedOrders.length} commande(s) mise(s) à jour`, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Erreur lors de la mise à jour', 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showNotification('Erreur lors de la mise à jour', 'error');
        });
    }
}

function downloadOrderPDF(orderId) {
    window.open(`/admin/orders/${orderId}/pdf`, '_blank');
}

function bulkExport() {
    const selectedOrders = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
    
    if (selectedOrders.length === 0) {
        alert('Veuillez sélectionner au moins une commande');
        return;
    }

    const params = new URLSearchParams({ order_ids: selectedOrders.join(',') });
    window.open(`/admin/orders/export?${params}`, '_blank');
}

// Fonction de notification (à adapter selon votre système)
function showNotification(message, type) {
    // Implémentation de votre système de notification
    console.log(`${type}: ${message}`);
    
    // Vous pouvez remplacer ceci par votre système de notification préféré
    if (type === 'success') {
        // Notification de succès
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alertDiv);
        
        // Auto-suppression après 3 secondes
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.parentNode.removeChild(alertDiv);
            }
        }, 3000);
    } else if (type === 'error') {
        // Notification d'erreur
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed';
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            <i class="fas fa-exclamation-circle me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alertDiv);
        
        // Auto-suppression après 5 secondes
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.parentNode.removeChild(alertDiv);
            }
        }, 5000);
    }
}
</script>
@endpush
@endsection