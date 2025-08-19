@extends('layouts.admin')

@section('title', 'Dashboard Administration')

@section('content')
<div class="container-fluid py-4">
    
    <!-- Header avec titre et actions -->
    <div class="row align-items-center mb-4">
        <div class="col">
            <h1 class="h3 mb-0">📊 Dashboard Administration</h1>
            <p class="text-muted mb-0">Vue d'ensemble de la plateforme ClicBillet CI</p>
        </div>
        <div class="col-auto">
            <div class="btn-group" role="group">
                <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-primary">
                    <i class="fas fa-chart-line me-1"></i> Rapports
                </a>
                <a href="{{ route('admin.analytics') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-analytics me-1"></i> Analytics
                </a>
            </div>
        </div>
    </div>

    <!-- Statistiques principales -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                                <i class="fas fa-users text-primary fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small mb-1">Utilisateurs totaux</div>
                            <div class="h4 mb-0 fw-bold">{{ number_format($stats['total_users']) }}</div>
                            <div class="text-success small">
                                <i class="fas fa-user-check me-1"></i>
                                {{ number_format($stats['verified_users']) }} vérifiés
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 rounded-3 p-3">
                                <i class="fas fa-calendar-alt text-success fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small mb-1">Événements</div>
                            <div class="h4 mb-0 fw-bold">{{ number_format($stats['total_events']) }}</div>
                            @if($stats['pending_events'] > 0)
                            <div class="text-warning small">
                                <i class="fas fa-clock me-1"></i>
                                {{ $stats['pending_events'] }} en attente
                            </div>
                            @else
                            <div class="text-muted small">
                                <i class="fas fa-check-circle me-1"></i>
                                Tout à jour
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 rounded-3 p-3">
                                <i class="fas fa-shopping-cart text-info fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small mb-1">Commandes</div>
                            <div class="h4 mb-0 fw-bold">{{ number_format($stats['total_orders']) }}</div>
                            <div class="text-muted small">
                                <i class="fas fa-chart-line me-1"></i>
                                Ce mois
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 rounded-3 p-3">
                                <i class="fas fa-coins text-warning fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small mb-1">Revenus totaux</div>
                            <div class="h4 mb-0 fw-bold">{{ number_format($stats['total_revenue']) }} F</div>
                            <div class="text-success small">
                                <i class="fas fa-arrow-up me-1"></i>
                                {{ number_format($stats['this_month_revenue']) }} F ce mois
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        
        <!-- Évolution des revenus -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pb-0">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">📈 Évolution des Revenus</h5>
                        <div class="btn-group btn-group-sm" role="group">
                            <input type="radio" class="btn-check" name="period" id="period3" checked>
                            <label class="btn btn-outline-secondary" for="period3">3M</label>
                            
                            <input type="radio" class="btn-check" name="period" id="period6">
                            <label class="btn btn-outline-secondary" for="period6">6M</label>
                            
                            <input type="radio" class="btn-check" name="period" id="period12">
                            <label class="btn btn-outline-secondary" for="period12">1A</label>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- Promoteurs actifs -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h5 class="card-title mb-0">🏆 Top Promoteurs</h5>
                </div>
                <div class="card-body">
                    @forelse($topPromoters as $promoter)
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <span class="fw-bold text-primary">{{ substr($promoter->name, 0, 1) }}</span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="fw-semibold">{{ $promoter->name }}</div>
                            <div class="text-muted small">
                                {{ $promoter->events_this_month }} événements ce mois
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-success">
                                {{ number_format($promoter->revenue_this_month ?? 0) }} F
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-users fa-2x mb-2 opacity-50"></i>
                        <p class="mb-0">Aucun promoteur actif ce mois</p>
                    </div>
                    @endforelse
                </div>
                @if($topPromoters->count() > 0)
                <div class="card-footer bg-transparent border-0 pt-0">
                    <a href="{{ route('admin.users.index', ['role' => 'promoteur']) }}" class="btn btn-sm btn-outline-primary w-100">
                        Voir tous les promoteurs
                    </a>
                </div>
                @endif
            </div>
        </div>

        <!-- Événements en attente -->
        @if($pendingEvents->count() > 0)
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pb-0">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">⏰ Événements en Attente</h5>
                        <span class="badge bg-warning">{{ $pendingEvents->count() }}</span>
                    </div>
                </div>
                <div class="card-body">
                    @foreach($pendingEvents as $event)
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{{ $event->title }}</div>
                            <div class="text-muted small">
                                Par {{ $event->promoteur->name }} • {{ $event->created_at->diffForHumans() }}
                            </div>
                        </div>
                        <div class="text-end">
                            <a href="{{ route('admin.events.show', $event) }}" class="btn btn-sm btn-outline-primary">
                                Examiner
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <a href="{{ route('admin.events.index', ['status' => 'pending']) }}" class="btn btn-sm btn-primary w-100">
                        Voir tous les événements en attente
                    </a>
                </div>
            </div>
        </div>
        @endif

        <!-- Commandes récentes -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h5 class="card-title mb-0">🛒 Commandes Récentes</h5>
                </div>
                <div class="card-body">
                    @forelse($recentOrders->take(5) as $order)
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            @if($order->payment_status === 'paid')
                                <i class="fas fa-check-circle text-success"></i>
                            @elseif($order->payment_status === 'pending')
                                <i class="fas fa-clock text-warning"></i>
                            @else
                                <i class="fas fa-times-circle text-danger"></i>
                            @endif
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="fw-semibold">{{ $order->order_number }}</div>
                            <div class="text-muted small">
                                {{ $order->user->name }} • {{ $order->event->title }}
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold">{{ number_format($order->total_amount) }} F</div>
                            <div class="text-muted small">{{ $order->created_at->format('d/m H:i') }}</div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-shopping-cart fa-2x mb-2 opacity-50"></i>
                        <p class="mb-0">Aucune commande récente</p>
                    </div>
                    @endforelse
                </div>
                @if($recentOrders->count() > 0)
                <div class="card-footer bg-transparent border-0 pt-0">
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-primary w-100">
                        Voir toutes les commandes
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Actions rapides -->
    <div class="row g-3 mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <h5 class="card-title mb-0">⚡ Actions Rapides</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="{{ route('admin.events.create') }}" class="btn btn-primary w-100">
                                <i class="fas fa-plus-circle me-2"></i>
                                Créer un Événement
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('admin.users.create') }}" class="btn btn-success w-100">
                                <i class="fas fa-user-plus me-2"></i>
                                Ajouter un Utilisateur
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('admin.categories.create') }}" class="btn btn-info w-100">
                                <i class="fas fa-tags me-2"></i>
                                Nouvelle Catégorie
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary w-100">
                                <i class="fas fa-cog me-2"></i>
                                Paramètres
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Graphique des revenus
const ctx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: {!! json_encode($monthlyStats->pluck('month')) !!},
        datasets: [{
            label: 'Revenus (F CFA)',
            data: {!! json_encode($monthlyStats->pluck('revenue')) !!},
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Commandes',
            data: {!! json_encode($monthlyStats->pluck('orders')) !!},
            borderColor: 'rgb(255, 99, 132)',
            backgroundColor: 'rgba(255, 99, 132, 0.1)',
            tension: 0.4,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                grid: {
                    color: 'rgba(0,0,0,0.1)'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                grid: {
                    drawOnChartArea: false,
                }
            }
        },
        plugins: {
            legend: {
                position: 'bottom',
            },
            tooltip: {
                backgroundColor: 'rgba(0,0,0,0.8)',
                titleColor: 'white',
                bodyColor: 'white',
                borderColor: 'rgba(255,255,255,0.1)',
                borderWidth: 1
            }
        }
    }
});

// Animation des compteurs
function animateCounters() {
    const counters = document.querySelectorAll('.h4');
    
    counters.forEach(counter => {
        const target = parseInt(counter.textContent.replace(/[^\d]/g, ''));
        const duration = 2000;
        const increment = target / (duration / 16);
        let current = 0;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            counter.textContent = Math.floor(current).toLocaleString();
        }, 16);
    });
}

// Démarrer les animations au chargement
document.addEventListener('DOMContentLoaded', animateCounters);

// Actualisation automatique des données (optionnel)
setInterval(() => {
    // Ici vous pouvez ajouter une requête AJAX pour actualiser les données
    // sans recharger la page
}, 300000); // 5 minutes
</script>
@endpush

@push('styles')
<style>
.card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 25px rgba(0,0,0,0.1) !important;
}

.bg-opacity-10 {
    background-color: rgba(var(--bs-primary-rgb), 0.1) !important;
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

.badge {
    font-size: 0.75em;
    font-weight: 600;
}

.btn-group-sm .btn {
    border-radius: 0.375rem;
}

.chart-container {
    position: relative;
    height: 300px;
}
</style>
@endpush