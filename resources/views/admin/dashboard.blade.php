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
                    <a class="nav-link active text-white" href="{{ route('admin.dashboard') }}">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a class="nav-link text-light" href="{{ route('admin.users') }}">
                        <i class="fas fa-users me-2"></i>Utilisateurs
                    </a>
                    <a class="nav-link text-light" href="{{ route('admin.events') }}">
                        <i class="fas fa-calendar me-2"></i>Événements
                    </a>
                    <a class="nav-link text-light" href="{{ route('admin.orders') }}">
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
            <!-- En-tête avec filtres -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Dashboard Administrateur</h2>
                    <p class="text-muted mb-0">Vue d'ensemble de la plateforme ClicBillet</p>
                </div>
                <div class="d-flex align-items-center">
                    <form method="GET" action="{{ route('admin.dashboard') }}" class="d-flex align-items-center">
                        <select name="period" class="form-select" onchange="this.form.submit()">
                            <option value="today" {{ ($period ?? 'this_month') == 'today' ? 'selected' : '' }}>Aujourd'hui</option>
                            <option value="this_week" {{ ($period ?? 'this_month') == 'this_week' ? 'selected' : '' }}>Cette semaine</option>
                            <option value="this_month" {{ ($period ?? 'this_month') == 'this_month' ? 'selected' : '' }}>Ce mois</option>
                            <option value="last_month" {{ ($period ?? 'this_month') == 'last_month' ? 'selected' : '' }}>Mois dernier</option>
                            <option value="this_year" {{ ($period ?? 'this_month') == 'this_year' ? 'selected' : '' }}>Cette année</option>
                        </select>
                    </form>
                </div>
            </div>

            <!-- Alertes si disponibles -->
            @if(isset($alerts) && $alerts->count() > 0)
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>Alertes système</h6>
                            <ul class="mb-0">
                                @foreach($alerts as $alert)
                                    <li>{{ $alert }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Statistiques principales -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-primary">
                                <i class="fas fa-euro-sign"></i>
                            </div>
                            <div class="stat-info ms-3">
                                <h4 class="mb-0">{{ number_format($stats['total_revenue'] ?? 0, 0, ',', ' ') }} FCFA</h4>
                                <p class="text-muted mb-0">Revenus totaux</p>
                                @if(isset($stats['revenue_growth']))
                                    <small class="growth-{{ $stats['revenue_growth'] >= 0 ? 'positive' : 'negative' }}">
                                        <i class="fas fa-arrow-{{ $stats['revenue_growth'] >= 0 ? 'up' : 'down' }}"></i>
                                        {{ abs($stats['revenue_growth']) }}%
                                    </small>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-warning">
                                <i class="fas fa-coins"></i>
                            </div>
                            <div class="stat-info ms-3">
                                <h4 class="mb-0">{{ number_format($stats['total_commissions'] ?? 0, 0, ',', ' ') }} FCFA</h4>
                                <p class="text-muted mb-0">Commissions</p>
                                @if(isset($stats['pending_commissions']))
                                    <small class="text-warning">
                                        {{ $stats['pending_commissions'] }} en attente
                                    </small>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-success">
                                <i class="fas fa-ticket-alt"></i>
                            </div>
                            <div class="stat-info ms-3">
                                <h4 class="mb-0">{{ number_format($stats['total_tickets'] ?? 0) }}</h4>
                                <p class="text-muted mb-0">Billets vendus</p>
                                @if(isset($stats['tickets_growth']))
                                    <small class="growth-{{ $stats['tickets_growth'] >= 0 ? 'positive' : 'negative' }}">
                                        <i class="fas fa-arrow-{{ $stats['tickets_growth'] >= 0 ? 'up' : 'down' }}"></i>
                                        {{ abs($stats['tickets_growth']) }}%
                                    </small>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-info">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-info ms-3">
                                <h4 class="mb-0">{{ number_format($stats['total_users'] ?? 0) }}</h4>
                                <p class="text-muted mb-0">Utilisateurs actifs</p>
                                @if(isset($stats['new_users']))
                                    <small class="text-info">
                                        +{{ $stats['new_users'] }} nouveaux
                                    </small>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Graphiques et données détaillées -->
            <div class="row mb-4">
                <!-- Graphique des revenus -->
                <div class="col-lg-8 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-line text-orange me-2"></i>
                                Évolution des revenus
                            </h5>
                        </div>
                        <div class="card-body">
                            @if(isset($chartData))
                                <canvas id="revenueChart" height="100"></canvas>
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Données de graphique en cours de chargement...</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Top promoteurs -->
                <div class="col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-trophy text-orange me-2"></i>
                                Top Promoteurs
                            </h5>
                        </div>
                        <div class="card-body">
                            @if(isset($topPromoters) && $topPromoters->count() > 0)
                                @foreach($topPromoters->take(5) as $promoter)
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <h6 class="mb-0">{{ $promoter->name }}</h6>
                                            <small class="text-muted">{{ $promoter->events_count ?? 0 }} événements</small>
                                        </div>
                                        <div class="text-end">
                                            <strong class="text-orange">{{ number_format($promoter->total_revenue ?? 0, 0, ',', ' ') }} FCFA</strong>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center py-3">
                                    <i class="fas fa-users fa-2x text-muted mb-2"></i>
                                    <p class="text-muted mb-0">Aucun promoteur actif</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistiques par catégorie -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-tags text-orange me-2"></i>
                                Performance par catégorie
                            </h5>
                        </div>
                        <div class="card-body">
                            @if(isset($categoryStats) && $categoryStats->count() > 0)
                                <div class="row">
                                    @foreach($categoryStats as $category)
                                        <div class="col-md-3 mb-3">
                                            <div class="text-center p-3 bg-light rounded">
                                                <i class="{{ $category->icon ?? 'fas fa-tag' }} fa-2x text-orange mb-2"></i>
                                                <h6>{{ $category->name }}</h6>
                                                <div class="d-flex justify-content-between">
                                                    <small class="text-muted">Événements: {{ $category->events_count ?? 0 }}</small>
                                                    <small class="text-muted">Billets: {{ $category->tickets_sold ?? 0 }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Aucune donnée de catégorie disponible</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Commandes récentes -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-shopping-cart text-orange me-2"></i>
                                Commandes récentes
                            </h5>
                            <a href="{{ route('admin.orders') }}" class="btn btn-outline-primary btn-sm">
                                Voir toutes
                            </a>
                        </div>
                        <div class="card-body">
                            @if(isset($recentOrders) && $recentOrders->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Commande</th>
                                                <th>Client</th>
                                                <th>Événement</th>
                                                <th>Montant</th>
                                                <th>Statut</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($recentOrders->take(10) as $order)
                                                <tr>
                                                    <td>
                                                        <strong>#{{ $order->order_number ?? $order->id }}</strong>
                                                    </td>
                                                    <td>{{ $order->user->name ?? 'N/A' }}</td>
                                                    <td>{{ $order->event->title ?? 'Événement supprimé' }}</td>
                                                    <td>
                                                        <strong class="text-orange">{{ number_format($order->total_amount ?? 0, 0, ',', ' ') }} FCFA</strong>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-{{ $order->payment_status === 'paid' ? 'success' : ($order->payment_status === 'pending' ? 'warning' : 'danger') }}">
                                                            {{ ucfirst($order->payment_status ?? 'inconnu') }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small>{{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : 'N/A' }}</small>
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-outline-primary btn-sm">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Aucune commande récente</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.admin-page {
    background-color: #f8f9fa;
}

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

.growth-positive {
    color: #28a745;
}

.growth-negative {
    color: #dc3545;
}

.card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.card-header {
    border-radius: 12px 12px 0 0 !important;
    border-bottom: 1px solid #e9ecef;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
}

.badge {
    font-size: 0.75rem;
    padding: 0.4em 0.8em;
}

@media (max-width: 768px) {
    .admin-sidebar {
        margin-bottom: 2rem;
    }
    
    .stat-card {
        margin-bottom: 1rem;
    }
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Graphique des revenus
    @if(isset($chartData))
        const ctx = document.getElementById('revenueChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($chartData['labels'] ?? []) !!},
                    datasets: [{
                        label: 'Revenus (FCFA)',
                        data: {!! json_encode($chartData['revenue'] ?? []) !!},
                        borderColor: '#FF6B35',
                        backgroundColor: 'rgba(255, 107, 53, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString() + ' FCFA';
                                }
                            }
                        }
                    }
                }
            });
        }
    @endif
});
</script>
@endpush
@endsection