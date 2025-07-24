@extends('layouts.promoteur')

@push('styles')
<style>
    .report-card {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-left: 4px solid #FF6B35;
        margin-bottom: 2rem;
        transition: transform 0.3s ease;
    }
    
    .report-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-box {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: 10px;
        padding: 1.5rem;
        text-align: center;
        margin-bottom: 1rem;
    }
    
    .event-rank-item {
        display: flex;
        align-items: center;
        padding: 1rem;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }
    
    .event-rank-item:hover {
        border-color: #FF6B35;
        box-shadow: 0 4px 8px rgba(255, 107, 53, 0.2);
    }
    
    .rank-number {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: white;
        margin-right: 1rem;
    }
    
    .rank-1 { background: linear-gradient(135deg, #FFD700, #FFA500); }
    .rank-2 { background: linear-gradient(135deg, #C0C0C0, #A9A9A9); }
    .rank-3 { background: linear-gradient(135deg, #CD7F32, #B8860B); }
    .rank-other { background: linear-gradient(135deg, #6c757d, #495057); }
    
    .chart-container {
        position: relative;
        height: 300px;
        margin: 1rem 0;
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
    
    .progress-ring {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: white;
        margin: 0 auto 1rem;
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
                        <i class="fas fa-chart-bar me-2"></i>
                        Rapports & Analyses
                    </h1>
                    <p class="text-muted">Vue d'ensemble de vos performances</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('promoteur.reports.export', ['type' => 'events']) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-file-excel me-2"></i>Export Excel
                    </a>
                    <a href="{{ route('promoteur.reports.export', ['type' => 'sales']) }}" class="btn btn-orange">
                        <i class="fas fa-download me-2"></i>Export Complet
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres de période -->
    <div class="filter-card">
        <form method="GET" action="{{ route('promoteur.reports') }}" class="row g-3">
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
                    <i class="fas fa-sync me-2"></i>Actualiser
                </button>
            </div>
        </form>
    </div>

    <!-- Statistiques générales -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-box" style="border-left: 4px solid #FF6B35;">
                <div class="progress-ring" style="background: linear-gradient(135deg, #FF6B35, #E55A2B);">
                    {{ $stats['total_events'] ?? 0 }}
                </div>
                <h6 class="fw-bold mb-1">Total Événements</h6>
                <small class="text-muted">
                    {{ $stats['published_events'] ?? 0 }} publiés, {{ $stats['draft_events'] ?? 0 }} brouillons
                </small>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-box" style="border-left: 4px solid #28a745;">
                <div class="progress-ring" style="background: linear-gradient(135deg, #28a745, #20c997);">
                    <i class="fas fa-euro-sign"></i>
                </div>
                <h6 class="fw-bold mb-1">{{ number_format($stats['total_revenue'] ?? 0) }} F</h6>
                <small class="text-muted">Revenus Totaux</small>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-box" style="border-left: 4px solid #007bff;">
                <div class="progress-ring" style="background: linear-gradient(135deg, #007bff, #0056b3);">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h6 class="fw-bold mb-1">{{ number_format($stats['period_revenue'] ?? 0) }} F</h6>
                <small class="text-muted">Revenus de la Période</small>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-box" style="border-left: 4px solid #6f42c1;">
                <div class="progress-ring" style="background: linear-gradient(135deg, #6f42c1, #5a32a3);">
                    {{ number_format((($stats['period_revenue'] ?? 0) / max($stats['total_revenue'] ?? 1, 1)) * 100, 1) }}%
                </div>
                <h6 class="fw-bold mb-1">Croissance</h6>
                <small class="text-muted">Part de la période</small>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Graphique évolution mensuelle -->
        <div class="col-lg-8">
            <div class="report-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-chart-area me-2" style="color: #FF6B35;"></i>
                        Évolution Mensuelle des Revenus
                    </h5>
                </div>
                <div class="chart-container">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Top événements performants -->
        <div class="col-lg-4">
            <div class="report-card">
                <h5 class="fw-bold mb-4">
                    <i class="fas fa-trophy me-2" style="color: #FF6B35;"></i>
                    Top Événements
                </h5>
                
                @if(isset($topEvents) && $topEvents->count() > 0)
                    @foreach($topEvents->take(5) as $index => $event)
                        <div class="event-rank-item">
                            <div class="rank-number {{ $index < 3 ? 'rank-' . ($index + 1) : 'rank-other' }}">
                                {{ $index + 1 }}
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">{{ Str::limit($event->title, 25) }}</h6>
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted">{{ $event->orders_count }} commandes</small>
                                    <strong style="color: #FF6B35;">{{ number_format($event->revenue ?? 0) }} F</strong>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Aucun événement à afficher</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Types de billets performants -->
    <div class="row">
        <div class="col-lg-6">
            <div class="report-card">
                <h5 class="fw-bold mb-4">
                    <i class="fas fa-ticket-alt me-2" style="color: #FF6B35;"></i>
                    Types de Billets les Plus Vendus
                </h5>
                
                @if(isset($topTicketTypes) && $topTicketTypes->count() > 0)
                    @foreach($topTicketTypes as $ticketType)
                        <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded">
                            <div>
                                <h6 class="mb-1">{{ $ticketType->name }}</h6>
                                <small class="text-muted">{{ $ticketType->tickets_sold }} billets vendus</small>
                            </div>
                            <div class="text-end">
                                <strong style="color: #FF6B35;">{{ number_format($ticketType->revenue ?? 0) }} F</strong>
                                <br>
                                <small class="text-muted">{{ number_format($ticketType->price) }} F / billet</small>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Aucune vente de billets</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Métriques détaillées -->
        <div class="col-lg-6">
            <div class="report-card">
                <h5 class="fw-bold mb-4">
                    <i class="fas fa-analytics me-2" style="color: #FF6B35;"></i>
                    Métriques Détaillées
                </h5>
                
                <div class="row">
                    <div class="col-6 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <h4 class="fw-bold text-primary mb-1">
                                {{ isset($topEvents) ? $topEvents->avg('orders_count') : 0 }}
                            </h4>
                            <small class="text-muted">Commandes / Événement</small>
                        </div>
                    </div>
                    
                    <div class="col-6 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <h4 class="fw-bold text-success mb-1">
                                {{ number_format(isset($topEvents) && $topEvents->sum('orders_count') > 0 ? ($stats['total_revenue'] ?? 0) / $topEvents->sum('orders_count') : 0) }} F
                            </h4>
                            <small class="text-muted">Panier Moyen</small>
                        </div>
                    </div>
                    
                    <div class="col-6 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <h4 class="fw-bold text-warning mb-1">
                                {{ number_format((($stats['published_events'] ?? 0) / max($stats['total_events'] ?? 1, 1)) * 100, 1) }}%
                            </h4>
                            <small class="text-muted">Taux de Publication</small>
                        </div>
                    </div>
                    
                    <div class="col-6 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <h4 class="fw-bold" style="color: #FF6B35;">
                                {{ isset($topEvents) ? $topEvents->where('revenue', '>', 0)->count() : 0 }}
                            </h4>
                            <small class="text-muted">Événements Rentables</small>
                        </div>
                    </div>
                </div>
                
                <!-- Recommandations -->
                <div class="mt-4 p-3" style="background: linear-gradient(135deg, #fff3cd, #ffeaa7); border-radius: 10px;">
                    <h6 class="fw-bold text-warning">
                        <i class="fas fa-lightbulb me-2"></i>Recommandations
                    </h6>
                    <ul class="small mb-0">
                        @if(($stats['published_events'] ?? 0) < ($stats['total_events'] ?? 1))
                            <li>Publiez vos événements en brouillon pour augmenter vos ventes</li>
                        @endif
                        @if(isset($topTicketTypes) && $topTicketTypes->count() > 0)
                            <li>Privilégiez les types "{{ $topTicketTypes->first()->name }}" qui performent bien</li>
                        @endif
                        <li>Créez plus d'événements pour diversifier vos revenus</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Graphique évolution mensuelle
    const ctx = document.getElementById('monthlyChart').getContext('2d');
    
    const monthlyData = {
        labels: [
            @if(isset($monthlyData))
                @foreach($monthlyData as $data)
                    '{{ $data['month'] }}',
                @endforeach
            @endif
        ],
        datasets: [{
            label: 'Revenus (FCFA)',
            data: [
                @if(isset($monthlyData))
                    @foreach($monthlyData as $data)
                        {{ $data['revenue'] }},
                    @endforeach
                @endif
            ],
            borderColor: '#FF6B35',
            backgroundColor: 'rgba(255, 107, 53, 0.1)',
            tension: 0.4,
            fill: true
        }]
    };
    
    new Chart(ctx, {
        type: 'line',
        data: monthlyData,
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
    
    // Animation des cartes
    const cards = document.querySelectorAll('.event-rank-item');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateX(-20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateX(0)';
        }, index * 100);
    });
});
</script>
@endpush