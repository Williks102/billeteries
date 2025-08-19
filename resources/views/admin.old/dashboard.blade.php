{{-- resources/views/admin/dashboard.blade.php --}}
@extends('layouts.admin')

@section('title', 'Dashboard Administration - ClicBillet CI')

@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
    <!-- Header avec actions rapides -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Tableau de bord</h2>
            <p class="text-muted mb-0">Vue d'ensemble de votre plateforme</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.reports') }}" class="btn btn-outline-orange">
                <i class="fas fa-chart-bar me-2"></i>Rapports
            </a>
            <a href="{{ route('admin.export.financial') }}" class="btn btn-orange">
                <i class="fas fa-download me-2"></i>Export
            </a>
        </div>
    </div>

    <!-- Statistiques principales -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon primary me-3">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h4>{{ $stats['total_users'] ?? 0 }}</h4>
                        <p class="text-muted mb-0">Utilisateurs</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon success me-3">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h4>{{ $stats['total_events'] ?? 0 }}</h4>
                        <p class="text-muted mb-0">Événements</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon warning me-3">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <h4>{{ $stats['total_orders'] ?? 0 }}</h4>
                        <p class="text-muted mb-0">Commandes</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon info me-3">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <h4>{{ number_format($stats['total_revenue'] ?? 0) }} FCFA</h4>
                        <p class="text-muted mb-0">Revenus</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphiques et commandes récentes -->
    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Évolution des revenus</h5>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Commandes récentes</h5>
                </div>
                <div class="card-body p-0">
                    @forelse($recentOrders ?? [] as $order)
                        <div class="d-flex align-items-center p-3 border-bottom">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">{{ $order->user->name ?? 'N/A' }}</h6>
                                <small class="text-muted">{{ $order->event->title ?? 'N/A' }}</small>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold">{{ number_format($order->total_amount ?? 0) }} FCFA</div>
                                <small class="text-muted">{{ $order->created_at->diffForHumans() }}</small>
                            </div>
                        </div>
                    @empty
                        <div class="text-center p-4">
                            <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                            <p class="text-muted">Aucune commande récente</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Alertes et actions rapides -->
    @if(isset($alerts) && count($alerts) > 0)
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Alertes système</h5>
                    </div>
                    <div class="card-body">
                        @foreach($alerts as $alert)
                            <div class="alert alert-{{ $alert['type'] }} alert-dismissible fade show">
                                <i class="fas fa-{{ $alert['icon'] }} me-2"></i>{{ $alert['message'] }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Graphique des revenus
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
                    tension: 0.4,
                    fill: true
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
                                return new Intl.NumberFormat('fr-FR').format(value) + ' FCFA';
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush