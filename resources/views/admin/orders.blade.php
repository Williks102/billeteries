@extends('layouts.admin')

@push('styles')
<style>
    .page-header {
        background: linear-gradient(135deg, #FF6B35, #E55A2B);
        color: white;
        padding: 2rem;
        border-radius: 15px;
        margin-bottom: 2rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .btn-black {
        background-color: #000;
        border-color: #000;
        color: white;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .btn-black:hover {
        background-color: #333;
        border-color: #333;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    .table-container {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-left: 4px solid #FF6B35;
        overflow: hidden;
    }
    
    .table-orange thead th {
        background: linear-gradient(135deg, #FF6B35, #E55A2B);
        color: white;
        border: none;
        font-weight: 600;
        padding: 1rem;
    }
    
    .table tbody tr {
        transition: all 0.3s ease;
    }
    
    .table tbody tr:hover {
        background-color: rgba(255, 107, 53, 0.05);
        transform: translateX(5px);
    }
    
    .table td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #eee;
    }
    
    .order-number {
        font-weight: 700;
        color: #FF6B35;
        font-family: 'Courier New', monospace;
    }
    
    .amount {
        font-weight: 600;
        color: #000;
        font-size: 1.1rem;
    }
    
    .status-paid {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
    }
    
    .status-pending {
        background: linear-gradient(135deg, #ffc107, #fd7e14);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
    }
    
    .status-failed {
        background: linear-gradient(135deg, #dc3545, #e83e8c);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
    }
    
    .status-refunded {
        background: linear-gradient(135deg, #6c757d, #495057);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
    }
    
    .customer-info {
        font-weight: 600;
        color: #000;
    }
    
    .customer-email {
        color: #666;
        font-size: 0.9rem;
    }
    
    .event-title {
        color: #000;
        font-weight: 500;
        text-decoration: none;
    }
    
    .event-title:hover {
        color: #FF6B35;
    }
    
    .filter-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-left: 4px solid #FF6B35;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .search-box, .filter-select {
        border: 2px solid #ddd;
        border-radius: 8px;
        padding: 0.75rem;
        transition: all 0.3s ease;
    }
    
    .search-box:focus, .filter-select:focus {
        border-color: #FF6B35;
        outline: none;
        box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
    }
    
    .stats-row {
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        text-align: center;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-card.paid {
        border-left: 4px solid #28a745;
    }
    
    .stat-card.pending {
        border-left: 4px solid #ffc107;
    }
    
    .stat-card.total {
        border-left: 4px solid #FF6B35;
    }
    
    .stat-card.revenue {
        border-left: 4px solid #007bff;
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="mb-2">
                <i class="fas fa-shopping-cart me-3"></i>
                Gestion des Commandes
            </h1>
            <p class="mb-0 opacity-75">Suivi et gestion de toutes les commandes</p>
        </div>
        <div>
            <a href="#" class="btn btn-black btn-lg">
                <i class="fas fa-file-export me-2"></i>Exporter
            </a>
        </div>
    </div>
</div>

<!-- Statistiques -->
<div class="row stats-row">
    <div class="col-md-3">
        <div class="stat-card total">
            <i class="fas fa-shopping-cart fa-2x mb-3" style="color: #FF6B35;"></i>
            <h3 style="color: #FF6B35;">{{ $totalOrders ?? 0 }}</h3>
            <p class="text-muted mb-0">Total Commandes</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card paid">
            <i class="fas fa-check-circle fa-2x text-success mb-3"></i>
            <h3 class="text-success">{{ $paidOrders ?? 0 }}</h3>
            <p class="text-muted mb-0">Payées</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card pending">
            <i class="fas fa-clock fa-2x text-warning mb-3"></i>
            <h3 class="text-warning">{{ $pendingOrders ?? 0 }}</h3>
            <p class="text-muted mb-0">En attente</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card revenue">
            <i class="fas fa-euro-sign fa-2x text-primary mb-3"></i>
            <h3 class="text-primary">{{ number_format($totalRevenue ?? 0) }} F</h3>
            <p class="text-muted mb-0">Chiffre d'affaires</p>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="filter-card">
    <div class="row">
        <div class="col-md-4">
            <label class="form-label fw-bold">Rechercher</label>
            <input type="text" class="form-control search-box" placeholder="N° commande, client, email...">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-bold">Statut</label>
            <select class="form-select filter-select">
                <option value="">Tous les statuts</option>
                <option value="paid">Payé</option>
                <option value="pending">En attente</option>
                <option value="failed">Échoué</option>
                <option value="refunded">Remboursé</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-bold">Période</label>
            <select class="form-select filter-select">
                <option value="">Toutes les périodes</option>
                <option value="today">Aujourd'hui</option>
                <option value="week">Cette semaine</option>
                <option value="month">Ce mois</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-bold">&nbsp;</label>
            <button class="btn btn-black w-100">
                <i class="fas fa-filter me-2"></i>Filtrer
            </button>
        </div>
    </div>
</div>

<!-- Tableau des commandes -->
<div class="table-container">
    <table class="table table-orange table-hover mb-0">
        <thead>
            <tr>
                <th><i class="fas fa-hashtag me-2"></i>N° Commande</th>
                <th><i class="fas fa-user me-2"></i>Client</th>
                <th><i class="fas fa-envelope me-2"></i>Email</th>
                <th><i class="fas fa-calendar-alt me-2"></i>Événement</th>
                <th><i class="fas fa-euro-sign me-2"></i>Montant</th>
                <th><i class="fas fa-info-circle me-2"></i>Statut</th>
                <th><i class="fas fa-clock me-2"></i>Date</th>
                <th><i class="fas fa-cogs me-2"></i>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
            <tr>
                <td>
                    <span class="order-number">#{{ $order->order_number }}</span>
                </td>
                <td>
                    <div class="customer-info">{{ $order->user->name }}</div>
                </td>
                <td>
                    <div class="customer-email">{{ $order->billing_email }}</div>
                </td>
                <td>
                    <a href="#" class="event-title">
                        {{ Str::limit($order->event->title, 30) }}
                    </a>
                </td>
                <td>
                    <span class="amount">{{ number_format($order->total_amount) }} FCFA</span>
                </td>
                <td>
                    @switch($order->payment_status)
                        @case('paid')
                            <span class="status-paid">
                                <i class="fas fa-check-circle me-1"></i>Payé
                            </span>
                            @break
                        @case('pending')
                            <span class="status-pending">
                                <i class="fas fa-clock me-1"></i>En attente
                            </span>
                            @break
                        @case('failed')
                            <span class="status-failed">
                                <i class="fas fa-times-circle me-1"></i>Échoué
                            </span>
                            @break
                        @case('refunded')
                            <span class="status-refunded">
                                <i class="fas fa-undo me-1"></i>Remboursé
                            </span>
                            @break
                        @default
                            <span class="status-pending">
                                <i class="fas fa-question-circle me-1"></i>{{ ucfirst($order->payment_status) }}
                            </span>
                    @endswitch
                </td>
                <td>
                    <div class="fw-bold">{{ $order->created_at->format('d/m/Y') }}</div>
                    <small class="text-muted">{{ $order->created_at->format('H:i') }}</small>
                </td>
                <td>
                    <div class="btn-group" role="group">
                        <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-sm btn-black" title="Voir les détails">
                            <i class="fas fa-eye"></i>
                        </a>
                        @if($order->payment_status === 'paid')
                        <button class="btn btn-sm btn-outline-warning" title="Rembourser">
                            <i class="fas fa-undo"></i>
                        </button>
                        @endif
                        <button class="btn btn-sm btn-outline-primary" title="Envoyer par email">
                            <i class="fas fa-envelope"></i>
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center py-5">
                    <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                    <h5>Aucune commande trouvée</h5>
                    <p class="text-muted">Il n'y a pas encore de commandes dans le système.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if(isset($orders) && $orders->hasPages())
<div class="d-flex justify-content-center mt-4">
    {{ $orders->links('pagination::bootstrap-4') }}
</div>
@endif
@endsection

@push('scripts')
<script>
    // Recherche en temps réel
    document.querySelector('.search-box').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const orderNumber = row.querySelector('.order-number')?.textContent.toLowerCase() || '';
            const customer = row.querySelector('.customer-info')?.textContent.toLowerCase() || '';
            const email = row.querySelector('.customer-email')?.textContent.toLowerCase() || '';
            
            if (orderNumber.includes(searchTerm) || customer.includes(searchTerm) || email.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
    
    // Confirmation de remboursement
    document.querySelectorAll('.btn-outline-warning').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Êtes-vous sûr de vouloir rembourser cette commande ?')) {
                e.preventDefault();
            }
        });
    });
</script>
@endpush