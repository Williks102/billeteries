@extends('layouts.admin')

@push('styles')
<style>
    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        margin-bottom: 1.5rem;
        border-left: 1px solid #706d6cff;
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.15);
    }
    
    .stat-card h4 {
        color: #FF6B35;
        font-weight: bold;
        margin-bottom: 0;
    }
    
    .stat-card .text-muted {
        color: #666 !important;
    }
    
    .btn-black {
        background-color: #000;
        border-color: #000;
        color: white;
    }
    
    .btn-black:hover {
        background-color: #333;
        border-color: #333;
        color: white;
    }
    
    .card-orange {
        border-left: 1px solid #ffffffff;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .card-header-orange {
        background: linear-gradient(135deg, #0f0c0bff, #1d1715ff);
        color: white;
        border-radius: 15px 15px 0 0 !important;
    }
    
    .table-orange thead th {
        background-color: #FF6B35;
        color: white;
        border: none;
    }
    
    .badge-orange {
        background-color: #FF6B35;
    }
    
    .alert-orange {
        background-color: rgba(255, 107, 53, 0.1);
        border-color: #FF6B35;
        color: #d63031;
    }
    
    .growth-positive {
        color: #27ae60;
        font-weight: bold;
    }
    
    .growth-negative {
        color: #e74c3c;
        font-weight: bold;
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold" style="color: #272626ff;">Dashboard Administrateur</h2>
        <p class="text-muted">Vue d'ensemble de la plateforme</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <form method="GET" action="{{ route('admin.dashboard') }}" class="d-flex align-items-center">
            <select name="period" class="form-select me-2" onchange="this.form.submit()">
                <option value="today" {{ $period == 'today' ? 'selected' : '' }}>Aujourd'hui</option>
                <option value="this_week" {{ $period == 'this_week' ? 'selected' : '' }}>Cette semaine</option>
                <option value="this_month" {{ $period == 'this_month' ? 'selected' : '' }}>Ce mois</option>
                <option value="last_month" {{ $period == 'last_month' ? 'selected' : '' }}>Mois dernier</option>
                <option value="this_year" {{ $period == 'this_year' ? 'selected' : '' }}>Cette année</option>
            </select>
        </form>
        <a href="{{ route('admin.export.accounting', ['period' => $period]) }}" class="btn btn-black">
            <i class="fas fa-file-export me-1"></i>Exporter
        </a>
    </div>
</div>

@if(count($alerts))
    @foreach($alerts as $alert)
        <div class="alert alert-orange d-flex justify-content-between align-items-center mb-3">
            <div><i class="{{ $alert['icon'] }} me-2"></i> {{ $alert['message'] }}</div>
            @if(isset($alert['action']))
                <a href="{{ $alert['action'] }}" class="btn btn-sm btn-black">Voir</a>
            @endif
        </div>
    @endforeach
@endif

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="mb-2">
                <i class="fas fa-euro-sign fa-2x" style="color: #FF6B35;"></i>
            </div>
            <div class="text-muted">Revenus</div>
            <h4>{{ \App\Helpers\CurrencyHelper::formatFCFA($revenues['period_revenue']) }}</h4>
            <small class="growth-positive">
                <i class="fas fa-arrow-up"></i> +{{ $revenues['revenue_growth'] }}%
            </small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="mb-2">
                <i class="fas fa-percentage fa-2x" style="color: #FF6B35;"></i>
            </div>
            <div class="text-muted">Commissions</div>
            <h4>{{ \App\Helpers\CurrencyHelper::formatFCFA($revenues['period_commissions']) }}</h4>
            <small class="text-info">{{ number_format($revenues['commission_rate'], 1) }}%</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="mb-2">
                <i class="fas fa-users fa-2x" style="color: #FF6B35;"></i>
            </div>
            <div class="text-muted">Nouveaux Utilisateurs</div>
            <h4>{{ $stats['new_users'] }}</h4>
            <small class="growth-positive">
                <i class="fas fa-arrow-up"></i> +{{ $stats['users_growth'] }}%
            </small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="mb-2">
                <i class="fas fa-calendar-alt fa-2x" style="color: #FF6B35;"></i>
            </div>
            <div class="text-muted">Nouveaux Événements</div>
            <h4>{{ $stats['new_events'] }}</h4>
            <small class="growth-positive">
                <i class="fas fa-arrow-up"></i> +{{ $stats['events_growth'] }}%
            </small>
        </div>
    </div>
</div>

<div class="card card-orange mb-4">
    <div class="card-header card-header-orange">
        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Évolution des revenus</h5>
    </div>
    <div class="card-body p-4">
        <canvas id="revenueChart" height="100"></canvas>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card card-orange h-100">
            <div class="card-header card-header-orange">
                <h6 class="mb-0"><i class="fas fa-users-cog me-2"></i>Top Promoteurs</h6>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @foreach($topPromoters->take(5) as $p)
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong style="color: #000;">{{ $p->name }}</strong>
                                <div class="text-muted small">{{ $p->events_count }} événements</div>
                            </div>
                            <div class="text-end">
                                <span class="fw-bold" style="color: #FF6B35;">
                                    {{ \App\Helpers\CurrencyHelper::formatFCFA($p->total_earned) }}
                                </span>
                                <div class="small text-muted">
                                    Plateforme : {{ \App\Helpers\CurrencyHelper::formatFCFA($p->platform_earned) }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-orange h-100">
            <div class="card-header card-header-orange">
                <h6 class="mb-0"><i class="fas fa-tags me-2"></i>Par catégorie</h6>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @foreach($categoryStats->take(5) as $cat)
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span style="color: #000;">{{ $cat->name }} ({{ $cat->events_count }})</span>
                            <span class="fw-bold" style="color: #FF6B35;">
                                {{ \App\Helpers\CurrencyHelper::formatFCFA($cat->total_revenue) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card card-orange">
    <div class="card-header card-header-orange">
        <h6 class="mb-0"><i class="fas fa-history me-2"></i>Commandes récentes</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-orange">
                    <tr>
                        <th>#</th>
                        <th>Client</th>
                        <th>Événement</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentOrders as $order)
                        <tr>
                            <td><strong>{{ $order->order_number }}</strong></td>
                            <td>{{ $order->user->name }}</td>
                            <td>{{ Str::limit($order->event->title, 30) }}</td>
                            <td class="fw-bold" style="color: #FF6B35;">
                                {{ \App\Helpers\CurrencyHelper::formatFCFA($order->total_amount) }}
                            </td>
                            <td>
                                @if($order->payment_status == 'paid')
                                    <span class="badge bg-success">Payé</span>
                                @elseif($order->payment_status == 'pending')
                                    <span class="badge bg-warning">En attente</span>
                                @else
                                    <span class="badge bg-danger">{{ ucfirst($order->payment_status) }}</span>
                                @endif
                            </td>
                            <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <a href="#" class="btn btn-sm btn-black">
                                    <i class="fas fa-eye me-1"></i>Voir
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($chartData['daily_revenues']->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d/m'))),
            datasets: [{
                label: 'Revenus (FCFA)',
                data: @json($chartData['daily_revenues']->pluck('revenue')),
                borderColor: '#FF6B35',
                backgroundColor: 'rgba(255,107,53,0.1)',
                tension: 0.4,
                fill: true,
                borderWidth: 3,
                pointBackgroundColor: '#FF6B35',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 6
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true,
                    labels: {
                        color: '#333',
                        font: {
                            weight: 'bold'
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('fr-FR').format(value) + ' FCFA';
                        },
                        color: '#666'
                    },
                    grid: {
                        color: 'rgba(255,107,53,0.1)'
                    }
                },
                x: {
                    ticks: {
                        color: '#666'
                    },
                    grid: {
                        color: 'rgba(255,107,53,0.1)'
                    }
                }
            }
        }
    });
</script>
@endpush