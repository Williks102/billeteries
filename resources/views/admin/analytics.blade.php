@extends('layouts.admin')

@section('title', 'Analytics - Administration')

@section('styles')
<style>
.analytics-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
    transition: all 0.3s ease;
}

.analytics-card:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
}

.metric-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
}

.metric-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100px;
    height: 100px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
}

.metric-card.success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.metric-card.warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.metric-card.info {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.metric-card.danger {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.chart-container {
    position: relative;
    height: 400px;
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.small-chart {
    height: 250px;
}

.analytics-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem 0;
    margin: -1rem -1rem 2rem -1rem;
    border-radius: 0 0 20px 20px;
}

.period-selector {
    background: white;
    border-radius: 25px;
    padding: 0.5rem;
    display: inline-flex;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.period-btn {
    padding: 0.5rem 1rem;
    border: none;
    background: transparent;
    border-radius: 20px;
    transition: all 0.3s ease;
    font-weight: 500;
}

.period-btn.active {
    background: #667eea;
    color: white;
    box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
}

.trend-indicator {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 600;
}

.trend-up {
    background: #d1fae5;
    color: #065f46;
}

.trend-down {
    background: #fee2e2;
    color: #991b1b;
}

.trend-neutral {
    background: #f3f4f6;
    color: #374151;
}

.top-events-list {
    max-height: 300px;
    overflow-y: auto;
}

.event-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
    transition: background-color 0.2s ease;
}

.event-item:hover {
    background-color: #f9fafb;
}

.event-item:last-child {
    border-bottom: none;
}

.event-rank {
    background: #667eea;
    color: white;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.875rem;
    margin-right: 1rem;
}

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.filter-bar {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

@media (max-width: 768px) {
    .kpi-grid {
        grid-template-columns: 1fr;
    }
    
    .chart-container {
        height: 300px;
        padding: 1rem;
    }
    
    .analytics-header {
        padding: 1.5rem 0;
    }
    
    .period-selector {
        flex-direction: column;
        width: 100%;
    }
    
    .period-btn {
        margin: 0.25rem 0;
    }
}
</style>
@endsection

@section('content')
<div class="admin-content">
    <!-- En-tête Analytics -->
    <div class="analytics-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="h2 mb-1">📈 Analytics & Insights</h1>
                    <p class="mb-0 opacity-75">Analyses approfondies des performances de votre plateforme</p>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <div class="period-selector">
                        <button class="period-btn active" data-period="7days">7 jours</button>
                        <button class="period-btn" data-period="30days">30 jours</button>
                        <button class="period-btn" data-period="90days">3 mois</button>
                        <button class="period-btn" data-period="year">1 an</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres avancés -->
    <div class="filter-bar">
        <div class="row align-items-center">
            <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                <label class="form-label fw-bold">Catégorie</label>
                <select class="form-select" id="categoryFilter">
                    <option value="">Toutes les catégories</option>
                    @if(isset($categories))
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                <label class="form-label fw-bold">Promoteur</label>
                <select class="form-select" id="promoterFilter">
                    <option value="">Tous les promoteurs</option>
                    @if(isset($promoters))
                        @foreach($promoters as $promoter)
                            <option value="{{ $promoter->id }}">{{ $promoter->name }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                <label class="form-label fw-bold">Date de début</label>
                <input type="date" class="form-control" id="startDate" value="{{ now()->subDays(30)->format('Y-m-d') }}">
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="form-label fw-bold">Date de fin</label>
                <input type="date" class="form-control" id="endDate" value="{{ now()->format('Y-m-d') }}">
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-auto">
                <button class="btn btn-primary" onclick="applyFilters()">
                    <i class="fas fa-filter me-2"></i>Appliquer les filtres
                </button>
            </div>
            <div class="col-auto">
                <button class="btn btn-outline-secondary" onclick="resetFilters()">
                    <i class="fas fa-undo me-2"></i>Réinitialiser
                </button>
            </div>
            <div class="col-auto ms-auto">
                <button class="btn btn-success" onclick="exportAnalytics()">
                    <i class="fas fa-download me-2"></i>Exporter
                </button>
            </div>
        </div>
    </div>

    <!-- KPIs principaux -->
    <div class="kpi-grid">
        <div class="metric-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h3 class="h1 mb-1">{{ isset($stats['total_revenue']) ? number_format($stats['total_revenue'] / 100, 0, ',', ' ') : '0' }}</h3>
                    <p class="mb-2 opacity-75">Chiffre d'affaires (FCFA)</p>
                    <span class="trend-indicator trend-up">
                        <i class="fas fa-arrow-up me-1"></i>+12.5%
                    </span>
                </div>
                <i class="fas fa-coins fa-2x opacity-50"></i>
            </div>
        </div>

        <div class="metric-card success">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h3 class="h1 mb-1">{{ isset($stats['total_orders']) ? number_format($stats['total_orders']) : '0' }}</h3>
                    <p class="mb-2 opacity-75">Commandes totales</p>
                    <span class="trend-indicator trend-up">
                        <i class="fas fa-arrow-up me-1"></i>+8.3%
                    </span>
                </div>
                <i class="fas fa-shopping-cart fa-2x opacity-50"></i>
            </div>
        </div>

        <div class="metric-card warning">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h3 class="h1 mb-1">{{ isset($stats['avg_order_value']) ? number_format($stats['avg_order_value'] / 100, 0, ',', ' ') : '0' }}</h3>
                    <p class="mb-2 opacity-75">Panier moyen (FCFA)</p>
                    <span class="trend-indicator trend-neutral">
                        <i class="fas fa-minus me-1"></i>0%
                    </span>
                </div>
                <i class="fas fa-calculator fa-2x opacity-50"></i>
            </div>
        </div>

        <div class="metric-card info">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h3 class="h1 mb-1">{{ isset($stats['conversion_rate']) ? number_format($stats['conversion_rate'], 1) : '0' }}%</h3>
                    <p class="mb-2 opacity-75">Taux de conversion</p>
                    <span class="trend-indicator trend-up">
                        <i class="fas fa-arrow-up me-1"></i>+2.1%
                    </span>
                </div>
                <i class="fas fa-percentage fa-2x opacity-50"></i>
            </div>
        </div>
    </div>

    <!-- Graphiques principaux -->
    <div class="row mb-4">
        <!-- Évolution du chiffre d'affaires -->
        <div class="col-lg-8 mb-4">
            <div class="chart-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-chart-line text-primary me-2"></i>
                        Évolution du chiffre d'affaires
                    </h5>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary active" data-chart="revenue">CA</button>
                        <button type="button" class="btn btn-outline-primary" data-chart="orders">Commandes</button>
                        <button type="button" class="btn btn-outline-primary" data-chart="users">Utilisateurs</button>
                    </div>
                </div>
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <!-- Top 10 événements -->
        <div class="col-lg-4 mb-4">
            <div class="analytics-card h-100">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-trophy text-warning me-2"></i>
                        Top 10 Événements
                    </h5>
                    <div class="top-events-list">
                        @if(isset($topEvents))
                            @foreach($topEvents->take(10) as $index => $event)
                                <div class="event-item">
                                    <div class="event-rank">{{ $index + 1 }}</div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">{{ Str::limit($event->title, 30) }}</h6>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">{{ $event->tickets_sold ?? 0 }} billets vendus</small>
                                            <span class="badge bg-success">
                                                {{ number_format(($event->revenue ?? 0) / 100, 0, ',', ' ') }} FCFA
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Aucune donnée disponible</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analyses détaillées -->
    <div class="row mb-4">
        <!-- Répartition par catégorie -->
        <div class="col-lg-6 mb-4">
            <div class="chart-container small-chart">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-chart-pie text-info me-2"></i>
                    Répartition par catégorie
                </h5>
                <canvas id="categoryChart"></canvas>
            </div>
        </div>

        <!-- Performance mensuelle -->
        <div class="col-lg-6 mb-4">
            <div class="chart-container small-chart">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-chart-bar text-success me-2"></i>
                    Performance mensuelle
                </h5>
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Métriques avancées -->
    <div class="row mb-4">
        <!-- Analyse des utilisateurs -->
        <div class="col-lg-4 mb-4">
            <div class="analytics-card h-100">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-users text-primary me-2"></i>
                        Analyse des utilisateurs
                    </h5>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Nouveaux utilisateurs</span>
                            <strong>{{ isset($userStats['new_users']) ? $userStats['new_users'] : '0' }}</strong>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-primary" style="width: 75%"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Utilisateurs actifs</span>
                            <strong>{{ isset($userStats['active_users']) ? $userStats['active_users'] : '0' }}</strong>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: 85%"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Taux de rétention</span>
                            <strong>{{ isset($userStats['retention_rate']) ? number_format($userStats['retention_rate'], 1) : '0' }}%</strong>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-warning" style="width: 60%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Analyse des paiements -->
        <div class="col-lg-4 mb-4">
            <div class="analytics-card h-100">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-credit-card text-success me-2"></i>
                        Analyse des paiements
                    </h5>
                    
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <h4 class="text-success mb-1">{{ isset($paymentStats['success_rate']) ? number_format($paymentStats['success_rate'], 1) : '0' }}%</h4>
                                <small class="text-muted">Taux de succès</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="text-danger mb-1">{{ isset($paymentStats['failed_count']) ? $paymentStats['failed_count'] : '0' }}</h4>
                            <small class="text-muted">Échecs</small>
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="small">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Mobile Money</span>
                            <span class="fw-bold">{{ isset($paymentStats['mobile_money']) ? $paymentStats['mobile_money'] : '0' }}%</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Carte bancaire</span>
                            <span class="fw-bold">{{ isset($paymentStats['card']) ? $paymentStats['card'] : '0' }}%</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Autres</span>
                            <span class="fw-bold">{{ isset($paymentStats['other']) ? $paymentStats['other'] : '0' }}%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Prévisions -->
        <div class="col-lg-4 mb-4">
            <div class="analytics-card h-100">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-crystal-ball text-purple me-2"></i>
                        Prévisions
                    </h5>
                    
                    <div class="text-center mb-3">
                        <h3 class="text-primary mb-1">{{ isset($forecasts['next_month_revenue']) ? number_format($forecasts['next_month_revenue'] / 100, 0, ',', ' ') : '0' }}</h3>
                        <small class="text-muted">CA prévu mois prochain (FCFA)</small>
                    </div>

                    <div class="small">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Nouvelles commandes</span>
                            <span class="fw-bold text-success">+{{ isset($forecasts['new_orders']) ? $forecasts['new_orders'] : '0' }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Nouveaux utilisateurs</span>
                            <span class="fw-bold text-info">+{{ isset($forecasts['new_users']) ? $forecasts['new_users'] : '0' }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Taux de croissance</span>
                            <span class="fw-bold text-warning">{{ isset($forecasts['growth_rate']) ? number_format($forecasts['growth_rate'], 1) : '0' }}%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertes et recommandations -->
    <div class="row">
        <div class="col-12">
            <div class="analytics-card">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-lightbulb text-warning me-2"></i>
                        Recommandations & Alertes
                    </h5>
                    
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="alert alert-success d-flex align-items-center">
                                <i class="fas fa-check-circle me-3"></i>
                                <div>
                                    <strong>Performance excellente</strong><br>
                                    <small>Votre taux de conversion a augmenté de 15% ce mois-ci !</small>
                                </div>
                            </div>
                            
                            <div class="alert alert-info d-flex align-items-center">
                                <i class="fas fa-info-circle me-3"></i>
                                <div>
                                    <strong>Opportunité détectée</strong><br>
                                    <small>Les événements de la catégorie "Musique" performent 30% mieux.</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="alert alert-warning d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle me-3"></i>
                                <div>
                                    <strong>Point d'attention</strong><br>
                                    <small>Le taux d'abandon de panier a légèrement augmenté (12%).</small>
                                </div>
                            </div>
                            
                            <div class="alert alert-primary d-flex align-items-center">
                                <i class="fas fa-star me-3"></i>
                                <div>
                                    <strong>Suggestion</strong><br>
                                    <small>Considérez des promotions pour les événements à faible affluence.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/date-fns@2.29.3/index.min.js"></script>

<script>
// Configuration des graphiques
const chartColors = {
    primary: '#667eea',
    success: '#38ef7d',
    warning: '#f5576c',
    info: '#00f2fe',
    danger: '#fee140'
};

// Données pour les graphiques (vous devriez les passer depuis le contrôleur)
const analyticsData = @json($chartData ?? [
    'revenue' => [
        ['date' => '2024-01-01', 'value' => 150000],
        ['date' => '2024-01-02', 'value' => 200000],
        ['date' => '2024-01-03', 'value' => 180000],
        // ... plus de données
    ],
    'categories' => [
        ['name' => 'Musique', 'value' => 45],
        ['name' => 'Sport', 'value' => 25],
        ['name' => 'Culture', 'value' => 20],
        ['name' => 'Business', 'value' => 10]
    ]
]);

// Graphique principal - Évolution du chiffre d'affaires
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: analyticsData.revenue?.map(item => new Date(item.date).toLocaleDateString()) || [],
        datasets: [{
            label: 'Chiffre d\'affaires (FCFA)',
            data: analyticsData.revenue?.map(item => item.value) || [],
            borderColor: chartColors.primary,
            backgroundColor: chartColors.primary + '20',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: chartColors.primary,
            pointBorderWidth: 2,
            pointRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                mode: 'index',
                intersect: false,
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: 'white',
                bodyColor: 'white',
                borderColor: chartColors.primary,
                borderWidth: 1,
                callbacks: {
                    label: function(context) {
                        return 'CA: ' + new Intl.NumberFormat('fr-FR').format(context.parsed.y) + ' FCFA';
                    }
                }
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    color: '#6b7280'
                }
            },
            y: {
                grid: {
                    color: '#f3f4f6'
                },
                ticks: {
                    color: '#6b7280',
                    callback: function(value) {
                        return new Intl.NumberFormat('fr-FR').format(value);
                    }
                }
            }
        },
        interaction: {
            intersect: false,
            mode: 'index'
        }
    }
});

// Graphique en secteurs - Répartition par catégorie
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
const categoryChart = new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: analyticsData.categories?.map(item => item.name) || [],
        datasets: [{
            data: analyticsData.categories?.map(item => item.value) || [],
            backgroundColor: [
                chartColors.primary,
                chartColors.success,
                chartColors.warning,
                chartColors.info,
                chartColors.danger
            ],
            borderWidth: 0,
            hoverBorderWidth: 3,
            hoverBorderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    usePointStyle: true,
                    font: {
                        size: 12
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': ' + context.parsed + '%';
                    }
                }
            }
        },
        cutout: '60%'
    }
});

// Graphique en barres - Performance mensuelle
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
const monthlyChart = new Chart(monthlyCtx, {
    type: 'bar',
    data: {
        labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun'],
        datasets: [{
            label: 'Revenus',
            data: [300000, 450000, 320000, 580000, 420000, 650000],
            backgroundColor: chartColors.success + '80',
            borderColor: chartColors.success,
            borderWidth: 1,
            borderRadius: 4,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Revenus: ' + new Intl.NumberFormat('fr-FR').format(context.parsed.y) + ' FCFA';
                    }
                }
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    color: '#6b7280'
                }
            },
            y: {
                grid: {
                    color: '#f3f4f6'
                },
                ticks: {
                    color: '#6b7280',
                    callback: function(value) {
                        return new Intl.NumberFormat('fr-FR', {
                            notation: 'compact',
                            compactDisplay: 'short'
                        }).format(value);
                    }
                }
            }
        }
    }
});

// Gestion des périodes
document.querySelectorAll('.period-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        // Retirer la classe active de tous les boutons
        document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
        // Ajouter la classe active au bouton cliqué
        this.classList.add('active');
        
        // Mettre à jour les données selon la période
        const period = this.dataset.period;
        updateChartsByPeriod(period);
    });
});

// Fonction pour mettre à jour les graphiques selon la période
function updateChartsByPeriod(period) {
    // Simuler un appel AJAX pour récupérer les nouvelles données
    fetch(`/admin/analytics/data?period=${period}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        // Mettre à jour le graphique des revenus
        revenueChart.data.labels = data.revenue?.map(item => new Date(item.date).toLocaleDateString()) || [];
        revenueChart.data.datasets[0].data = data.revenue?.map(item => item.value) || [];
        revenueChart.update();
        
        // Mettre à jour les KPIs
        updateKPIs(data.kpis);
    })
    .catch(error => {
        console.error('Erreur lors de la mise à jour des données:', error);
    });
}

// Fonction pour mettre à jour les KPIs
function updateKPIs(kpis) {
    // Mettre à jour les valeurs des métriques
    if (kpis) {
        document.querySelector('.metric-card h3').textContent = 
            new Intl.NumberFormat('fr-FR').format(kpis.total_revenue / 100);
        // Ajouter d'autres mises à jour de KPIs...
    }
}

// Gestion des filtres
function applyFilters() {
    const filters = {
        category: document.getElementById('categoryFilter').value,
        promoter: document.getElementById('promoterFilter').value,
        start_date: document.getElementById('startDate').value,
        end_date: document.getElementById('endDate').value
    };
    
    // Afficher un indicateur de chargement
    showLoading();
    
    // Appel AJAX pour appliquer les filtres
    fetch('/admin/analytics/filter', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(filters)
    })
    .then(response => response.json())
    .then(data => {
        // Mettre à jour tous les graphiques et métriques
        updateAllCharts(data);
        hideLoading();
    })
    .catch(error => {
        console.error('Erreur lors de l\'application des filtres:', error);
        hideLoading();
        showError('Erreur lors de l\'application des filtres');
    });
}

function resetFilters() {
    document.getElementById('categoryFilter').value = '';
    document.getElementById('promoterFilter').value = '';
    document.getElementById('startDate').value = new Date(Date.now() - 30*24*60*60*1000).toISOString().split('T')[0];
    document.getElementById('endDate').value = new Date().toISOString().split('T')[0];
    
    applyFilters();
}

function updateAllCharts(data) {
    // Mettre à jour le graphique des revenus
    if (data.revenue) {
        revenueChart.data.labels = data.revenue.map(item => new Date(item.date).toLocaleDateString());
        revenueChart.data.datasets[0].data = data.revenue.map(item => item.value);
        revenueChart.update();
    }
    
    // Mettre à jour le graphique des catégories
    if (data.categories) {
        categoryChart.data.labels = data.categories.map(item => item.name);
        categoryChart.data.datasets[0].data = data.categories.map(item => item.value);
        categoryChart.update();
    }
    
    // Mettre à jour le graphique mensuel
    if (data.monthly) {
        monthlyChart.data.labels = data.monthly.map(item => item.month);
        monthlyChart.data.datasets[0].data = data.monthly.map(item => item.value);
        monthlyChart.update();
    }
    
    // Mettre à jour les KPIs
    updateKPIs(data.kpis);
}

// Export des analytics
function exportAnalytics() {
    const filters = {
        category: document.getElementById('categoryFilter').value,
        promoter: document.getElementById('promoterFilter').value,
        start_date: document.getElementById('startDate').value,
        end_date: document.getElementById('endDate').value,
        period: document.querySelector('.period-btn.active').dataset.period
    };
    
    // Créer un formulaire pour l'export
    const form = document.createElement('form');
    form.method = 'GET';
    form.action = '/admin/analytics/export';
    form.style.display = 'none';
    
    // Ajouter les paramètres de filtre
    Object.keys(filters).forEach(key => {
        if (filters[key]) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = filters[key];
            form.appendChild(input);
        }
    });
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

// Fonctions utilitaires
function showLoading() {
    const loader = document.createElement('div');
    loader.id = 'analytics-loader';
    loader.className = 'position-fixed top-50 start-50 translate-middle';
    loader.innerHTML = `
        <div class="d-flex align-items-center bg-white p-3 rounded shadow">
            <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
            <span>Chargement des données...</span>
        </div>
    `;
    document.body.appendChild(loader);
}

function hideLoading() {
    const loader = document.getElementById('analytics-loader');
    if (loader) {
        loader.remove();
    }
}

function showError(message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-danger alert-dismissible fade show position-fixed';
    alert.style.top = '20px';
    alert.style.right = '20px';
    alert.style.zIndex = '9999';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alert);
    
    // Supprimer automatiquement après 5 secondes
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

// Gestion du changement de type de graphique principal
document.querySelectorAll('[data-chart]').forEach(btn => {
    btn.addEventListener('click', function() {
        // Retirer la classe active
        document.querySelectorAll('[data-chart]').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const chartType = this.dataset.chart;
        updateMainChart(chartType);
    });
});

function updateMainChart(type) {
    // Simuler différents types de données
    let newData, newLabel;
    
    switch(type) {
        case 'revenue':
            newData = analyticsData.revenue?.map(item => item.value) || [];
            newLabel = 'Chiffre d\'affaires (FCFA)';
            break;
        case 'orders':
            newData = [50, 75, 60, 90, 70, 85]; // Données simulées
            newLabel = 'Nombre de commandes';
            break;
        case 'users':
            newData = [20, 35, 25, 45, 30, 40]; // Données simulées
            newLabel = 'Nouveaux utilisateurs';
            break;
    }
    
    revenueChart.data.datasets[0].data = newData;
    revenueChart.data.datasets[0].label = newLabel;
    revenueChart.update();
}

// Auto-actualisation des données (optionnel)
let autoRefreshInterval;

function startAutoRefresh() {
    autoRefreshInterval = setInterval(() => {
        const activePeriod = document.querySelector('.period-btn.active').dataset.period;
        updateChartsByPeriod(activePeriod);
    }, 300000); // 5 minutes
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
}

// Démarrer l'auto-actualisation au chargement
document.addEventListener('DOMContentLoaded', function() {
    startAutoRefresh();
    
    // Arrêter l'auto-actualisation quand la page n'est plus visible
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopAutoRefresh();
        } else {
            startAutoRefresh();
        }
    });
});

// Animation des métriques au chargement
function animateMetrics() {
    document.querySelectorAll('.metric-card h3').forEach(metric => {
        const finalValue = parseInt(metric.textContent.replace(/[^\d]/g, ''));
        let currentValue = 0;
        const increment = finalValue / 50;
        
        const timer = setInterval(() => {
            currentValue += increment;
            if (currentValue >= finalValue) {
                currentValue = finalValue;
                clearInterval(timer);
            }
            metric.textContent = new Intl.NumberFormat('fr-FR').format(Math.floor(currentValue));
        }, 30);
    });
}

// Lancer l'animation au chargement de la page
document.addEventListener('DOMContentLoaded', animateMetrics);

// Responsive handling pour les graphiques
window.addEventListener('resize', function() {
    setTimeout(() => {
        revenueChart.resize();
        categoryChart.resize();
        monthlyChart.resize();
    }, 100);
});
</script>
@endsection