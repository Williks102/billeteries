{{-- resources/views/admin/reports.blade.php --}}
@extends('layouts.admin')

@section('title', 'Rapports et statistiques - ClicBillet CI')

@section('breadcrumb')
    <li class="breadcrumb-item active">Rapports</li>
@endsection

@section('content')
    <!-- Header avec actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Rapports et statistiques</h2>
            <p class="text-muted mb-0">Analysez les performances de votre plateforme</p>
        </div>
        <div class="d-flex gap-2">
            <div class="dropdown">
                <button class="btn btn-outline-orange dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-download me-2"></i>Exports
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="{{ route('admin.export.financial') }}">
                        <i class="fas fa-chart-line me-2"></i>Rapport financier
                    </a></li>
                    <li><a class="dropdown-item" href="{{ route('admin.export.users') }}">
                        <i class="fas fa-users me-2"></i>Liste utilisateurs
                    </a></li>
                    <li><a class="dropdown-item" href="{{ route('admin.export.events') }}">
                        <i class="fas fa-calendar me-2"></i>Liste événements
                    </a></li>
                    <li><a class="dropdown-item" href="{{ route('admin.export.orders') }}">
                        <i class="fas fa-shopping-cart me-2"></i>Liste commandes
                    </a></li>
                    <li><a class="dropdown-item" href="{{ route('admin.export.commissions') }}">
                        <i class="fas fa-coins me-2"></i>Rapport commissions
                    </a></li>
                </ul>
            </div>
            <button class="btn btn-orange" onclick="generateReport()">
                <i class="fas fa-chart-bar me-2"></i>Générer rapport
            </button>
        </div>
    </div>

    <!-- Sélecteur de période -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports') }}" id="period-form">
                <div class="row align-items-end">
                    <div class="col-md-2">
                        <label class="form-label">Période</label>
                        <select name="period" class="form-select" onchange="updatePeriod()">
                            <option value="today" {{ request('period', 'month') == 'today' ? 'selected' : '' }}>Aujourd'hui</option>
                            <option value="week" {{ request('period', 'month') == 'week' ? 'selected' : '' }}>Cette semaine</option>
                            <option value="month" {{ request('period', 'month') == 'month' ? 'selected' : '' }}>Ce mois</option>
                            <option value="quarter" {{ request('period', 'month') == 'quarter' ? 'selected' : '' }}>Ce trimestre</option>
                            <option value="year" {{ request('period', 'month') == 'year' ? 'selected' : '' }}>Cette année</option>
                            <option value="custom" {{ request('period', 'month') == 'custom' ? 'selected' : '' }}>Personnalisée</option>
                        </select>
                    </div>
                    <div class="col-md-2" id="date-start" style="display: {{ request('period') == 'custom' ? 'block' : 'none' }};">
                        <label class="form-label">Date début</label>
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-2" id="date-end" style="display: {{ request('period') == 'custom' ? 'block' : 'none' }};">
                        <label class="form-label">Date fin</label>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Comparaison</label>
                        <select name="compare" class="form-select">
                            <option value="" {{ request('compare') == '' ? 'selected' : '' }}>Aucune</option>
                            <option value="previous" {{ request('compare') == 'previous' ? 'selected' : '' }}>Période précédente</option>
                            <option value="year" {{ request('compare') == 'year' ? 'selected' : '' }}>Année précédente</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-orange w-100">
                            <i class="fas fa-sync me-2"></i>Actualiser
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Métriques principales -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="metric-card revenue">
                <div class="metric-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="metric-content">
                    <h3>{{ number_format($metrics['revenue'] ?? 0) }} F</h3>
                    <p>Chiffre d'affaires</p>
                    @if(isset($metrics['revenue_growth']))
                        <div class="metric-change {{ $metrics['revenue_growth'] >= 0 ? 'positive' : 'negative' }}">
                            <i class="fas fa-arrow-{{ $metrics['revenue_growth'] >= 0 ? 'up' : 'down' }}"></i>
                            {{ abs($metrics['revenue_growth']) }}%
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card orders">
                <div class="metric-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="metric-content">
                    <h3>{{ number_format($metrics['orders'] ?? 0) }}</h3>
                    <p>Commandes</p>
                    @if(isset($metrics['orders_growth']))
                        <div class="metric-change {{ $metrics['orders_growth'] >= 0 ? 'positive' : 'negative' }}">
                            <i class="fas fa-arrow-{{ $metrics['orders_growth'] >= 0 ? 'up' : 'down' }}"></i>
                            {{ abs($metrics['orders_growth']) }}%
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card tickets">
                <div class="metric-icon">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div class="metric-content">
                    <h3>{{ number_format($metrics['tickets'] ?? 0) }}</h3>
                    <p>Billets vendus</p>
                    @if(isset($metrics['tickets_growth']))
                        <div class="metric-change {{ $metrics['tickets_growth'] >= 0 ? 'positive' : 'negative' }}">
                            <i class="fas fa-arrow-{{ $metrics['tickets_growth'] >= 0 ? 'up' : 'down' }}"></i>
                            {{ abs($metrics['tickets_growth']) }}%
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card users">
                <div class="metric-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="metric-content">
                    <h3>{{ number_format($metrics['users'] ?? 0) }}</h3>
                    <p>Nouveaux utilisateurs</p>
                    @if(isset($metrics['users_growth']))
                        <div class="metric-change {{ $metrics['users_growth'] >= 0 ? 'positive' : 'negative' }}">
                            <i class="fas fa-arrow-{{ $metrics['users_growth'] >= 0 ? 'up' : 'down' }}"></i>
                            {{ abs($metrics['users_growth']) }}%
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Graphique des ventes -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>Évolution des ventes
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Top événements -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-star me-2"></i>Top événements
                    </h5>
                </div>
                <div class="card-body">
                    @forelse($topEvents ?? [] as $index => $event)
                        <div class="top-event-item">
                            <div class="d-flex align-items-center">
                                <div class="rank-badge">{{ $index + 1 }}</div>
                                <div class="event-info flex-grow-1">
                                    <h6 class="mb-1">{{ Str::limit($event->title, 30) }}</h6>
                                    <small class="text-muted">{{ $event->sales_count ?? 0 }} ventes</small>
                                </div>
                                <div class="event-revenue">
                                    <span class="fw-bold">{{ number_format($event->total_revenue ?? 0) }} F</span>
                                </div>
                            </div>
                        </div>
                        @if(!$loop->last)<hr class="my-2">@endif
                    @empty
                        <div class="text-center py-4">
                            <i class="fas fa-calendar fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">Aucun événement trouvé</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Répartition par catégorie -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>Répartition par catégorie
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Commissions par promoteur -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-coins me-2"></i>Top promoteurs
                    </h5>
                    <a href="{{ route('admin.commissions') }}" class="btn btn-outline-orange btn-sm">
                        Voir tout
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Promoteur</th>
                                    <th>Ventes</th>
                                    <th>Commission</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topPromoters ?? [] as $promoter)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $promoter->name }}</div>
                                        <small class="text-muted">{{ $promoter->email }}</small>
                                    </td>
                                    <td>{{ number_format($promoter->total_sales ?? 0) }} F</td>
                                    <td class="fw-bold text-success">{{ number_format($promoter->total_commission ?? 0) }} F</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center py-3">
                                        <span class="text-muted">Aucun promoteur trouvé</span>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Métriques détaillées -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-table me-2"></i>Métriques détaillées
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Métrique</th>
                                    <th>Valeur actuelle</th>
                                    <th>Période précédente</th>
                                    <th>Évolution</th>
                                    <th>Objectif</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><i class="fas fa-money-bill-wave me-2 text-success"></i>Chiffre d'affaires</td>
                                    <td class="fw-bold">{{ number_format($detailed['revenue']['current'] ?? 0) }} F</td>
                                    <td>{{ number_format($detailed['revenue']['previous'] ?? 0) }} F</td>
                                    <td>
                                        @php $growth = $detailed['revenue']['growth'] ?? 0; @endphp
                                        <span class="badge {{ $growth >= 0 ? 'bg-success' : 'bg-danger' }}">
                                            {{ $growth >= 0 ? '+' : '' }}{{ $growth }}%
                                        </span>
                                    </td>
                                    <td>{{ number_format($detailed['revenue']['target'] ?? 0) }} F</td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-shopping-cart me-2 text-primary"></i>Nombre de commandes</td>
                                    <td class="fw-bold">{{ number_format($detailed['orders']['current'] ?? 0) }}</td>
                                    <td>{{ number_format($detailed['orders']['previous'] ?? 0) }}</td>
                                    <td>
                                        @php $growth = $detailed['orders']['growth'] ?? 0; @endphp
                                        <span class="badge {{ $growth >= 0 ? 'bg-success' : 'bg-danger' }}">
                                            {{ $growth >= 0 ? '+' : '' }}{{ $growth }}%
                                        </span>
                                    </td>
                                    <td>{{ number_format($detailed['orders']['target'] ?? 0) }}</td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-calculator me-2 text-info"></i>Panier moyen</td>
                                    <td class="fw-bold">{{ number_format($detailed['average']['current'] ?? 0) }} F</td>
                                    <td>{{ number_format($detailed['average']['previous'] ?? 0) }} F</td>
                                    <td>
                                        @php $growth = $detailed['average']['growth'] ?? 0; @endphp
                                        <span class="badge {{ $growth >= 0 ? 'bg-success' : 'bg-danger' }}">
                                            {{ $growth >= 0 ? '+' : '' }}{{ $growth }}%
                                        </span>
                                    </td>
                                    <td>{{ number_format($detailed['average']['target'] ?? 0) }} F</td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-percentage me-2 text-warning"></i>Taux de conversion</td>
                                    <td class="fw-bold">{{ number_format($detailed['conversion']['current'] ?? 0, 2) }}%</td>
                                    <td>{{ number_format($detailed['conversion']['previous'] ?? 0, 2) }}%</td>
                                    <td>
                                        @php $growth = $detailed['conversion']['growth'] ?? 0; @endphp
                                        <span class="badge {{ $growth >= 0 ? 'bg-success' : 'bg-danger' }}">
                                            {{ $growth >= 0 ? '+' : '' }}{{ number_format($growth, 2) }}%
                                        </span>
                                    </td>
                                    <td>{{ number_format($detailed['conversion']['target'] ?? 0, 2) }}%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .metric-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border: 1px solid #f0f0f0;
        position: relative;
        overflow: hidden;
        transition: transform 0.2s ease;
    }
    
    .metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    }
    
    .metric-card.revenue { border-left: 4px solid #28a745; }
    .metric-card.orders { border-left: 4px solid #007bff; }
    .metric-card.tickets { border-left: 4px solid #FF6B35; }
    .metric-card.users { border-left: 4px solid #6f42c1; }
    
    .metric-icon {
        position: absolute;
        top: 20px;
        right: 20px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
        opacity: 0.8;
    }
    
    .metric-card.revenue .metric-icon { background: linear-gradient(135deg, #28a745, #20c997); }
    .metric-card.orders .metric-icon { background: linear-gradient(135deg, #007bff, #17a2b8); }
    .metric-card.tickets .metric-icon { background: linear-gradient(135deg, #FF6B35, #fd7e14); }
    .metric-card.users .metric-icon { background: linear-gradient(135deg, #6f42c1, #e83e8c); }
    
    .metric-content h3 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 5px;
        color: #2d3748;
    }
    
    .metric-content p {
        color: #718096;
        margin-bottom: 10px;
        font-weight: 500;
    }
    
    .metric-change {
        font-size: 0.85rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .metric-change.positive { color: #28a745; }
    .metric-change.negative { color: #dc3545; }
    
    .top-event-item {
        padding: 10px 0;
    }
    
    .rank-badge {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: linear-gradient(135deg, #FF6B35, #fd7e14);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.9rem;
        margin-right: 15px;
    }
    
    .event-info h6 {
        margin-bottom: 2px;
        font-weight: 600;
    }
    
    .event-revenue {
        text-align: right;
    }
    
    .card {
        border: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border-radius: 12px;
    }
    
    .card-header {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        border-radius: 12px 12px 0 0 !important;
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
    
    #period-form .form-select,
    #period-form .form-control {
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        transition: border-color 0.2s ease;
    }
    
    #period-form .form-select:focus,
    #period-form .form-control:focus {
        border-color: #FF6B35;
        box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function updatePeriod() {
        const period = document.querySelector('select[name="period"]').value;
        const startDate = document.getElementById('date-start');
        const endDate = document.getElementById('date-end');
        
        if (period === 'custom') {
            startDate.style.display = 'block';
            endDate.style.display = 'block';
        } else {
            startDate.style.display = 'none';
            endDate.style.display = 'none';
            // Auto-submit pour les périodes prédéfinies
            document.getElementById('period-form').submit();
        }
    }
    
    function generateReport() {
        const form = document.getElementById('period-form');
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);
        
        // Ouvrir l'export en PDF
        window.open(`{{ route('admin.export.financial') }}?${params.toString()}`, '_blank');
    }
    
    // Graphique des ventes
    document.addEventListener('DOMContentLoaded', function() {
        const salesData = @json($chartData['sales'] ?? []);
        const salesLabels = @json($chartData['labels'] ?? []);
        
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: salesLabels,
                datasets: [{
                    label: 'Ventes (FCFA)',
                    data: salesData,
                    borderColor: '#FF6B35',
                    backgroundColor: 'rgba(255, 107, 53, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#FF6B35',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8
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
                                return new Intl.NumberFormat('fr-FR').format(value) + ' F';
                            }
                        },
                        grid: {
                            color: '#e2e8f0'
                        }
                    },
                    x: {
                        grid: {
                            color: '#e2e8f0'
                        }
                    }
                },
                elements: {
                    point: {
                        hoverBackgroundColor: '#FF6B35'
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
        
        // Graphique en secteurs pour les catégories
        const categoryData = @json($chartData['categories'] ?? []);
        const categoryLabels = @json($chartData['categoryLabels'] ?? []);
        
        if (categoryData.length > 0) {
            const ctxCategory = document.getElementById('categoryChart').getContext('2d');
            new Chart(ctxCategory, {
                type: 'doughnut',
                data: {
                    labels: categoryLabels,
                    datasets: [{
                        data: categoryData,
                        backgroundColor: [
                            '#FF6B35',
                            '#28a745',
                            '#007bff',
                            '#ffc107',
                            '#6f42c1',
                            '#17a2b8',
                            '#dc3545',
                            '#fd7e14'
                        ],
                        borderWidth: 3,
                        borderColor: '#fff'
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
                                usePointStyle: true
                            }
                        }
                    },
                    cutout: '60%'
                }
            });
        }
    });
    
    // Animation des métriques au chargement
    function animateMetrics() {
        const metrics = document.querySelectorAll('.metric-content h3');
        metrics.forEach((metric, index) => {
            const finalValue = parseInt(metric.textContent.replace(/[^0-9]/g, ''));
            let currentValue = 0;
            const increment = finalValue / 50;
            
            const timer = setInterval(() => {
                currentValue += increment;
                if (currentValue >= finalValue) {
                    currentValue = finalValue;
                    clearInterval(timer);
                }
                
                // Formater avec séparateurs de milliers
                const formattedValue = new Intl.NumberFormat('fr-FR').format(Math.round(currentValue));
                const unit = metric.textContent.includes('F') ? ' F' : '';
                metric.textContent = formattedValue + unit;
            }, 20);
        });
    }
    
    // Lancer l'animation après le chargement
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(animateMetrics, 500);
    });
</script>
@endpush