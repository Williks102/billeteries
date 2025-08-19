@extends('layouts.admin')

@section('title', 'Gestion des Commandes')

@section('content')
<div class="container-fluid py-4">
    
    <!-- Header avec statistiques -->
    <div class="row align-items-center mb-4">
        <div class="col">
            <h1 class="h3 mb-0">🛒 Gestion des Commandes</h1>
            <p class="text-muted mb-0">{{ $orders->total() }} commande(s) au total</p>
        </div>
        <div class="col-auto">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-download me-1"></i> Exporter
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="{{ route('admin.orders.export', request()->query()) }}">
                        <i class="fas fa-file-csv me-2"></i> Export CSV
                    </a></li>
                    <li><a class="dropdown-item" href="#" onclick="window.print()">
                        <i class="fas fa-print me-2"></i> Imprimer
                    </a></li>
                </ul>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#analyticsModal">
                    <i class="fas fa-chart-line me-1"></i> Analytics
                </button>
            </div>
        </div>
    </div>

    <!-- Statistiques rapides -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 bg-success bg-opacity-10">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                    <h4 class="mb-1 text-success">{{ number_format($stats['paid']) }}</h4>
                    <small class="text-muted">Payées</small>
                    <div class="progress mt-2" style="height: 3px;">
                        <div class="progress-bar bg-success" style="width: {{ $stats['total'] > 0 ? round(($stats['paid'] / $stats['total']) * 100) : 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 bg-warning bg-opacity-10">
                <div class="card-body text-center">
                    <i class="fas fa-clock text-warning fa-2x mb-2"></i>
                    <h4 class="mb-1 text-warning">{{ number_format($stats['pending']) }}</h4>
                    <small class="text-muted">En attente</small>
                    <div class="progress mt-2" style="height: 3px;">
                        <div class="progress-bar bg-warning" style="width: {{ $stats['total'] > 0 ? round(($stats['pending'] / $stats['total']) * 100) : 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 bg-danger bg-opacity-10">
                <div class="card-body text-center">
                    <i class="fas fa-times-circle text-danger fa-2x mb-2"></i>
                    <h4 class="mb-1 text-danger">{{ number_format($stats['failed']) }}</h4>
                    <small class="text-muted">Échouées</small>
                    <div class="progress mt-2" style="height: 3px;">
                        <div class="progress-bar bg-danger" style="width: {{ $stats['total'] > 0 ? round(($stats['failed'] / $stats['total']) * 100) : 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 bg-primary bg-opacity-10">
                <div class="card-body text-center">
                    <i class="fas fa-coins text-primary fa-2x mb-2"></i>
                    <h4 class="mb-1 text-primary">{{ number_format($stats['total_revenue']) }} F</h4>
                    <small class="text-muted">Revenus totaux</small>
                    <div class="text-success small mt-1">
                        +{{ number_format($stats['this_month_revenue']) }} F ce mois
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.orders.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">RECHERCHE</label>
                    <input type="text" class="form-control" name="search" value="{{ request('search') }}" 
                           placeholder="N° commande, client, événement...">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted">STATUT PAIEMENT</label>
                    <select class="form-select" name="payment_status">
                        <option value="">Tous les statuts</option>
                        <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Payé</option>
                        <option value="pending" {{ request('payment_status') === 'pending' ? 'selected' : '' }}>En attente</option>
                        <option value="failed" {{ request('payment_status') === 'failed' ? 'selected' : '' }}>Échec</option>
                        <option value="refunded" {{ request('payment_status') === 'refunded' ? 'selected' : '' }}>Remboursé</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted">ÉVÉNEMENT</label>
                    <select class="form-select" name="event">
                        <option value="">Tous événements</option>
                        @foreach($events as $event)
                        <option value="{{ $event->id }}" {{ request('event') == $event->id ? 'selected' : '' }}>
                            {{ Str::limit($event->title, 30) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted">PÉRIODE</label>
                    <select class="form-select" name="period" onchange="this.form.submit()">
                        <option value="">Toutes dates</option>
                        <option value="today" {{ request('period') === 'today' ? 'selected' : '' }}>Aujourd'hui</option>
                        <option value="week" {{ request('period') === 'week' ? 'selected' : '' }}>Cette semaine</option>
                        <option value="month" {{ request('period') === 'month' ? 'selected' : '' }}>Ce mois</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">ACTIONS</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-1"></i> Filtrer
                        </button>
                        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Actions en lot -->
    <div class="card border-0 shadow-sm mb-4" id="bulk-actions" style="display: none;">
        <div class="card-body bg-light">
            <form id="bulk-form" action="{{ route('admin.orders.bulk-action') }}" method="POST" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">ACTIONS EN LOT</label>
                    <select class="form-select" name="action" required>
                        <option value="">Choisir une action...</option>
                        <option value="mark_paid">Marquer comme payées</option>
                        <option value="mark_failed">Marquer comme échouées</option>
                        <option value="resend_emails">Renvoyer les emails</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <span id="selected-count" class="text-muted">0 commande(s) sélectionnée(s)</span>
                </div>
                <div class="col-md-3 text-end">
                    <button type="submit" class="btn btn-warning me-2">
                        <i class="fas fa-bolt me-1"></i> Exécuter
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="clearSelection()">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des commandes -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($orders->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th width="40">
                                <input type="checkbox" class="form-check-input" id="select-all">
                            </th>
                            <th>Commande</th>
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
                                <input type="checkbox" class="form-check-input row-checkbox" 
                                       name="orders[]" value="{{ $order->id }}">
                            </td>
                            <td>
                                <div>
                                    <div class="fw-bold">{{ $order->order_number }}</div>
                                    <div class="text-muted small">
                                        <i class="fas fa-ticket-alt me-1"></i>
                                        {{ $order->orderItems->sum('quantity') }} ticket(s)
                                    </div>
                                    @if($order->admin_notes)
                                    <div class="text-info small">
                                        <i class="fas fa-sticky-note me-1"></i>
                                        {{ Str::limit($order->admin_notes, 30) }}
                                    </div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                         style="width: 35px; height: 35px;">
                                        <span class="fw-bold text-primary small">
                                            {{ substr($order->user->name, 0, 1) }}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">{{ $order->user->name }}</div>
                                        <div class="text-muted small">{{ $order->user->email }}</div>
                                        @if($order->user->phone)
                                        <div class="text-muted small">
                                            <i class="fas fa-phone me-1"></i>{{ $order->user->phone }}
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <div class="fw-semibold">{{ Str::limit($order->event->title, 25) }}</div>
                                    <div class="text-muted small">
                                        <i class="fas fa-calendar me-1"></i>
                                        {{ \Carbon\Carbon::parse($order->event->event_date)->format('d/m/Y') }}
                                    </div>
                                    <div class="text-muted small">
                                        <i class="fas fa-user me-1"></i>
                                        {{ $order->event->promoteur->name ?? 'N/A' }}
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-primary h5 mb-1">{{ number_format($order->total_amount) }} F</div>
                                @if($order->refund_amount)
                                <div class="text-danger small">
                                    <i class="fas fa-undo me-1"></i>
                                    -{{ number_format($order->refund_amount) }} F remboursé
                                </div>
                                @endif
                                <div class="text-muted small">
                                    {{ number_format($order->total_amount / ($order->orderItems->sum('quantity') ?: 1)) }} F/ticket
                                </div>
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
                                            <i class="fas fa-times-circle me-1"></i>Échec
                                        </span>
                                        @break
                                    @case('refunded')
                                        <span class="badge bg-info">
                                            <i class="fas fa-undo me-1"></i>Remboursé
                                        </span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary">{{ $order->payment_status }}</span>
                                @endswitch
                                
                                @if($order->payment_status === 'paid')
                                <div class="text-success small mt-1">
                                    <i class="fas fa-coins me-1"></i>
                                    Commission: {{ number_format($order->commissions->sum('amount') ?? 0) }} F
                                </div>
                                @endif
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $order->created_at->format('d/m/Y') }}</div>
                                <div class="text-muted small">{{ $order->created_at->format('H:i') }}</div>
                                <div class="text-muted small">{{ $order->created_at->diffForHumans() }}</div>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary" 
                                       title="Voir détails">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle dropdown-toggle-split" 
                                            data-bs-toggle="dropdown"></button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.orders.edit', $order) }}">
                                                <i class="fas fa-edit me-2"></i>Modifier
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        @if($order->payment_status === 'pending')
                                        <li>
                                            <form action="{{ route('admin.orders.update-status', $order) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="payment_status" value="paid">
                                                <button type="submit" class="dropdown-item text-success">
                                                    <i class="fas fa-check-circle me-2"></i>Marquer payé
                                                </button>
                                            </form>
                                        </li>
                                        <li>
                                            <form action="{{ route('admin.orders.update-status', $order) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="payment_status" value="failed">
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="fas fa-times-circle me-2"></i>Marquer échec
                                                </button>
                                            </form>
                                        </li>
                                        @endif
                                        @if($order->payment_status === 'paid')
                                        <li>
                                            <button type="button" class="dropdown-item text-warning" 
                                                    onclick="showRefundModal('{{ $order->id }}', '{{ $order->order_number }}', {{ $order->total_amount }})">
                                                <i class="fas fa-undo me-2"></i>Rembourser
                                            </button>
                                        </li>
                                        <li>
                                            <form action="{{ route('admin.orders.resend-email', $order) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="dropdown-item text-info">
                                                    <i class="fas fa-envelope me-2"></i>Renvoyer email
                                                </button>
                                            </form>
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
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Aucune commande trouvée</h5>
                <p class="text-muted mb-0">
                    @if(request()->hasAny(['search', 'payment_status', 'event', 'period']))
                        Aucune commande ne correspond à vos critères de recherche.
                    @else
                        Il n'y a pas encore de commande dans la plateforme.
                    @endif
                </p>
            </div>
            @endif
        </div>

        @if($orders->hasPages())
        <div class="card-footer bg-transparent border-0">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Affichage de {{ $orders->firstItem() }} à {{ $orders->lastItem() }} 
                    sur {{ $orders->total() }} commande(s)
                </div>
                {{ $orders->appends(request()->query())->links() }}
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Modal de remboursement -->
<div class="modal fade" id="refundModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">💰 Remboursement de commande</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="refundForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Vous êtes sur le point de rembourser la commande <strong id="refundOrderNumber"></strong>.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Montant du remboursement</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="partial_amount" id="refundAmount" 
                                   step="0.01" min="0">
                            <span class="input-group-text">F CFA</span>
                        </div>
                        <div class="form-text">Laissez vide pour un remboursement complet</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Raison du remboursement *</label>
                        <textarea class="form-control" name="refund_reason" rows="3" required
                                  placeholder="Ex: Annulation événement, demande client, erreur technique..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-undo me-1"></i> Confirmer le remboursement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Analytics -->
<div class="modal fade" id="analyticsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">📊 Analytics des Commandes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Taux de conversion</h6>
                                <h3 class="text-success">{{ $stats['total'] > 0 ? round(($stats['paid'] / $stats['total']) * 100, 1) : 0 }}%</h3>
                                <small class="text-muted">Commandes payées / Total</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Panier moyen</h6>
                                <h3 class="text-primary">{{ $stats['paid'] > 0 ? number_format($stats['total_revenue'] / $stats['paid']) : 0 }} F</h3>
                                <small class="text-muted">Montant moyen par commande</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <canvas id="ordersChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Gestion des sélections en lot
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('select-all');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const bulkActions = document.getElementById('bulk-actions');
    const selectedCount = document.getElementById('selected-count');
    const bulkForm = document.getElementById('bulk-form');

    // Sélectionner/désélectionner tout
    selectAll.addEventListener('change', function() {
        rowCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkActions();
    });

    // Gestion des sélections individuelles
    rowCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateBulkActions();
            
            const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
            selectAll.checked = checkedCount === rowCheckboxes.length;
            selectAll.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
        });
    });

    function updateBulkActions() {
        const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
        const count = checkedBoxes.length;

        if (count > 0) {
            bulkActions.style.display = 'block';
            selectedCount.textContent = `${count} commande(s) sélectionnée(s)`;
            
            // Ajouter les IDs sélectionnés au formulaire
            const existingInputs = bulkForm.querySelectorAll('input[name="orders[]"]');
            existingInputs.forEach(input => input.remove());
            
            checkedBoxes.forEach(checkbox => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'orders[]';
                input.value = checkbox.value;
                bulkForm.appendChild(input);
            });
        } else {
            bulkActions.style.display = 'none';
        }
    }

    window.clearSelection = function() {
        rowCheckboxes.forEach(checkbox => checkbox.checked = false);
        selectAll.checked = false;
        selectAll.indeterminate = false;
        bulkActions.style.display = 'none';
    };

    // Confirmation pour les actions en lot
    bulkForm.addEventListener('submit', function(e) {
        const action = this.querySelector('select[name="action"]').value;
        const count = document.querySelectorAll('.row-checkbox:checked').length;
        
        if (!confirm(`Confirmer l'action "${action}" sur ${count} commande(s) ?`)) {
            e.preventDefault();
        }
    });
});

// Modal de remboursement
function showRefundModal(orderId, orderNumber, totalAmount) {
    document.getElementById('refundOrderNumber').textContent = orderNumber;
    document.getElementById('refundAmount').setAttribute('max', totalAmount);
    document.getElementById('refundAmount').setAttribute('placeholder', `Montant max: ${totalAmount} F`);
    document.getElementById('refundForm').action = `/admin/orders/${orderId}/refund`;
    
    const modal = new bootstrap.Modal(document.getElementById('refundModal'));
    modal.show();
}

// Graphique analytics (si Chart.js est disponible)
if (typeof Chart !== 'undefined') {
    const ctx = document.getElementById('ordersChart');
    if (ctx) {
        new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Payées', 'En attente', 'Échecs', 'Remboursées'],
                datasets: [{
                    data: [{{ $stats['paid'] }}, {{ $stats['pending'] }}, {{ $stats['failed'] }}, {{ $stats['refunded'] }}],
                    backgroundColor: ['#198754', '#ffc107', '#dc3545', '#0dcaf0'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
}

// Actualisation automatique
setInterval(() => {
    // Optionnel: actualiser les statistiques
    const badges = document.querySelectorAll('.badge');
    badges.forEach(badge => {
        if (badge.textContent.includes('En attente')) {
            badge.classList.add('animate-pulse');
        }
    });
}, 5000);
</script>
@endpush

@push('styles')
<style>
.table th {
    border-top: none;
    font-weight: 600;
    font-size: 0.875rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table td {
    vertical-align: middle;
    border-top: 1px solid #f0f0f0;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

.badge {
    font-size: 0.75rem;
    font-weight: 600;
}

.progress {
    background-color: #e9ecef;
}

.btn-group .dropdown-toggle-split {
    padding-left: 0.375rem;
    padding-right: 0.375rem;
}

.card {
    transition: all 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-1px);
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: .5;
    }
}

.bg-opacity-10 {
    background-color: rgba(var(--bs-primary-rgb), 0.1) !important;
}

@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .btn-group .btn {
        padding: 0.25rem 0.5rem;
    }
    
    .h5 {
        font-size: 1rem;
    }
}
</style>
@endpush