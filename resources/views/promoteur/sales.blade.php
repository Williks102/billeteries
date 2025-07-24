{{-- resources/views/promoteur/sales.blade.php - VERSION CORRIGÉE --}}
@extends('layouts.promoteur')

@push('styles')
<style>
    .stats-card {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-left: 4px solid #FF6B35;
        margin-bottom: 1.5rem;
        transition: transform 0.3s ease;
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
    }
    
    .stats-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
        font-size: 1.5rem;
        color: white;
    }
    
    .chart-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-left: 4px solid #FF6B35;
        padding: 2rem;
        margin-bottom: 2rem;
    }
    
    .event-performance-card {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }
    
    .event-performance-card:hover {
        border-color: #FF6B35;
        box-shadow: 0 4px 8px rgba(255, 107, 53, 0.2);
    }
    
    .filter-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-left: 4px solid #FF6B35;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .btn-orange {
        background: linear-gradient(135deg, #FF6B35, #E55A2B);
        border: none;
        color: white;
    }
    
    .btn-orange:hover {
        background: linear-gradient(135deg, #E55A2B, #D4491F);
        color: white;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1" style="color: #FF6B35;">
                        <i class="fas fa-chart-line me-2"></i>
                        Analyse des Ventes
                    </h1>
                    <p class="text-muted">Suivez les performances de vos événements</p>
                </div>
                <div>
                    <a href="{{ route('promoteur.reports.export', ['type' => 'sales']) }}" class="btn btn-orange">
                        <i class="fas fa-download me-2"></i>Exporter
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="filter-card">
        <form method="GET" action="{{ route('promoteur.sales') }}" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Date de début</label>
                <input type="date" name="start_date" class="form-control" 
                       value="{{ $startDate ?? now()->startOfMonth()->format('Y-m-d') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Date de fin</label>
                <input type="date" name="end_date" class="form-control" 
                       value="{{ $endDate ?? now()->endOfMonth()->format('Y-m-d') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-orange d-block">
                    <i class="fas fa-filter me-2"></i>Filtrer
                </button>
            </div>
        </form>
    </div>

    <!-- Statistiques globales -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card text-center">
                <div class="stats-icon mx-auto" style="background: linear-gradient(135deg, #FF6B35, #E55A2B);">
                    <i class="fas fa-euro-sign"></i>
                </div>
                <h3 class="fw-bold" style="color: #FF6B35;">
                    {{ isset($totalStats['total_revenue']) ? number_format($totalStats['total_revenue']) : 0 }} F
                </h3>
                <p class="text-muted mb-0">Revenus Totaux</p>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stats-card text-center">
                <div class="stats-icon mx-auto" style="background: linear-gradient(135deg, #28a745, #20c997);">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <h3 class="fw-bold text-success">{{ isset($totalStats['total_tickets']) ? $totalStats['total_tickets'] : 0 }}</h3>
                <p class="text-muted mb-0">Billets Vendus</p>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stats-card text-center">
                <div class="stats-icon mx-auto" style="background: linear-gradient(135deg, #007bff, #0056b3);">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3 class="fw-bold text-primary">{{ isset($totalStats['total_orders']) ? $totalStats['total_orders'] : 0 }}</h3>
                <p class="text-muted mb-0">Commandes</p>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stats-card text-center">
                <div class="stats-icon mx-auto" style="background: linear-gradient(135deg, #6f42c1, #5a32a3);">
                    <i class="fas fa-calculator"></i>
                </div>
                <h3 class="fw-bold text-info">
                    {{ isset($totalStats['average_order']) ? number_format($totalStats['average_order']) : 0 }} F
                </h3>
                <p class="text-muted mb-0">Panier Moyen</p>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Graphique des ventes quotidiennes - VERSION SÉCURISÉE -->
        <div class="col-lg-8">
            <div class="chart-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-chart-area me-2" style="color: #FF6B35;"></i>
                        Évolution des Ventes
                    </h5>
                </div>
                <div style="position: relative; height: 300px;">
                    <canvas id="dailySalesChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Top événements -->
        <div class="col-lg-4">
            <div class="chart-card">
                <h5 class="fw-bold mb-4">
                    <i class="fas fa-trophy me-2" style="color: #FF6B35;"></i>
                    Top Événements
                </h5>
                
                @if(isset($salesByEvent) && $salesByEvent->count() > 0)
                    @foreach($salesByEvent->take(5) as $sale)
                        <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded">
                            <div>
                                <h6 class="mb-1">{{ Str::limit($sale['event']->title, 25) }}</h6>
                                <small class="text-muted">{{ $sale['tickets_sold'] }} billets</small>
                            </div>
                            <div class="text-end">
                                <strong style="color: #FF6B35;">{{ number_format($sale['revenue']) }} F</strong>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Aucune vente pour cette période</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Performance détaillée par événement -->
    <div class="chart-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0">
                <i class="fas fa-list-alt me-2" style="color: #FF6B35;"></i>
                Performance Détaillée des Événements
            </h5>
        </div>
        
        @if(isset($salesByEvent) && $salesByEvent->count() > 0)
            <div class="row">
                @foreach($salesByEvent as $sale)
                    <div class="col-lg-6 col-xl-4">
                        <div class="event-performance-card">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h6 class="fw-bold mb-1">{{ $sale['event']->title }}</h6>
                                <span class="badge bg-{{ $sale['event']->status === 'published' ? 'success' : 'warning' }}">
                                    {{ ucfirst($sale['event']->status) }}
                                </span>
                            </div>
                            
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="fw-bold" style="color: #FF6B35;">{{ number_format($sale['revenue']) }} F</div>
                                    <small class="text-muted">Revenus</small>
                                </div>
                                <div class="col-4">
                                    <div class="fw-bold text-success">{{ $sale['tickets_sold'] }}</div>
                                    <small class="text-muted">Billets</small>
                                </div>
                                <div class="col-4">
                                    <div class="fw-bold text-primary">{{ $sale['orders_count'] }}</div>
                                    <small class="text-muted">Commandes</small>
                                </div>
                            </div>
                            
                            <div class="progress mt-3" style="height: 8px;">
                                @php
                                    $totalAvailable = $sale['event']->totalTicketsAvailable();
                                    $percentage = $totalAvailable > 0 ? ($sale['tickets_sold'] / $totalAvailable) * 100 : 0;
                                @endphp
                                <div class="progress-bar" style="background: #FF6B35; width: {{ min($percentage, 100) }}%"></div>
                            </div>
                            <small class="text-muted">{{ number_format($percentage, 1) }}% vendu</small>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-chart-line fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">Aucune donnée de vente disponible</h5>
                <p class="text-muted">Les ventes apparaîtront ici dès que vos événements commenceront à être vendus.</p>
                <a href="{{ route('promoteur.events.create') }}" class="btn btn-orange">
                    <i class="fas fa-plus me-2"></i>Créer un événement
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier que l'élément canvas existe
    const canvas = document.getElementById('dailySalesChart');
    if (!canvas) {
        console.error('Canvas element not found');
        return;
    }
    
    const ctx = canvas.getContext('2d');
    
    // Données par défaut si pas de données du serveur
    let chartLabels = [];
    let chartData = [];
    
    // Récupérer les données du serveur (avec vérification)
    @if(isset($dailySales) && $dailySales->count() > 0)
        chartLabels = [
            @foreach($dailySales as $day)
                '{{ \Carbon\Carbon::parse($day->date)->format("d/m") }}',
            @endforeach
        ];
        
        chartData = [
            @foreach($dailySales as $day)
                {{ $day->revenue ?? 0 }},
            @endforeach
        ];
    @else
        // Données par défaut pour les 7 derniers jours
        @for($i = 6; $i >= 0; $i--)
            chartLabels.push('{{ \Carbon\Carbon::now()->subDays($i)->format("d/m") }}');
            chartData.push(0);
        @endfor
    @endif
    
    const chartDataObj = {
        labels: chartLabels,
        datasets: [{
            label: 'Revenus (FCFA)',
            data: chartData,
            borderColor: '#FF6B35',
            backgroundColor: 'rgba(255, 107, 53, 0.1)',
            tension: 0.4,
            fill: true
        }]
    };
    
    try {
        new Chart(ctx, {
            type: 'line',
            data: chartDataObj,
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
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Erreur lors de la création du graphique:', error);
        // Masquer le canvas et afficher un message d'erreur
        canvas.style.display = 'none';
        const errorMsg = document.createElement('div');
        errorMsg.className = 'alert alert-warning';
        errorMsg.textContent = 'Impossible de charger le graphique des ventes';
        canvas.parentNode.appendChild(errorMsg);
    }
});
</script>
@endpush