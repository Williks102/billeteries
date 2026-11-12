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
        <div class="col-12 col-md-auto mt-2 mt-md-0">
            <div class="d-flex flex-wrap gap-2" role="group" aria-label="Accès rapides">
                <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-primary">
                    <i class="fas fa-chart-line me-1"></i> Rapports
                </a>
                <a href="{{ route('admin.analytics') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-chart-bar me-1"></i> Analytics
                </a>
                <a href="{{ route('admin.revenues') }}" class="btn btn-outline-info">
                    <i class="fas fa-coins me-1"></i> Revenus
                </a>
            </div>
        </div>
    </div>

    <x-period-filter :action="route('admin.dashboard')" :period="$period" />

    <h2 class="h5 mt-3">Plateforme</h2>
    <x-stat-grid cols="3" label="Chiffres de la plateforme">
        <x-stat-card label="Utilisateurs" :value="$platformUsers['total']" :hint="$platformUsers['verified'].' vérifiés'" />
        <x-stat-card label="Événements" :value="$platformEvents['total']" :hint="$platformEvents['pending'].' en attente de validation'" />
        <x-stat-card label="Nouveaux utilisateurs" :value="$platformUsers['new']" :hint="'Inscrits : '.$period->label" />
    </x-stat-grid>

    <h2 class="h5">Ventes : {{ $period->label }}</h2>
    <x-stat-grid label="Ventes de la période">
        <x-stat-card label="Ventes (billets, hors frais)" unit="FCFA" :value="$summary['gmv']" hint="Commandes payées" />
        <x-stat-card label="Encaissé" unit="FCFA" :value="$summary['collected']" hint="Total payé par les clients" />
        <x-stat-card label="Commission plateforme" unit="FCFA" :value="$summary['platform_revenue']" hint="Revenu de la plateforme" />
        <x-stat-card label="Net promoteurs à verser (total, toutes dates)" unit="FCFA" :value="$summary['pending_payout']" hint="Solde dû, indépendant de la période" />
        <x-stat-card label="Net promoteurs versé" unit="FCFA" :value="$summary['paid_out']" hint="Versé sur la période" />
        <x-stat-card label="Commandes payées" :value="$summary['orders_paid']" />
        <x-stat-card label="En attente de paiement" :value="$summary['orders_awaiting_payment']" hint="Commandes créées sur la période" />
        <x-stat-card label="Remboursées" :value="$summary['refunds']['count']" :hint="'Montant remboursé : '.\App\Helpers\CurrencyHelper::formatFCFA($summary['refunds']['amount'])" />
        <x-stat-card label="Billets vendus" :value="$summary['tickets_sold']" hint="Payés ou utilisés" />
        <x-stat-card label="Taux d'utilisation" unit="%" :value="$summary['usage_rate']" hint="Scannés / vendus" />
        <x-stat-card label="Taux de paiement" unit="%" :value="$summary['payment_rate']" hint="Payées / créées" />
        <x-stat-card label="Panier moyen" unit="FCFA" :value="$summary['average_basket']" hint="Ventes / commandes payées" />
    </x-stat-grid>

    <div class="row g-4">
        
        <!-- Évolution des revenus -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pb-0">
                    <div class="d-flex align-items-center justify-content-between">
                        <h2 class="h5 card-title mb-0">Ventes des 6 derniers mois</h2>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="100" role="img" aria-label="Ventes (billets, hors frais) et commandes payées par mois sur 6 mois"></canvas>
                </div>
            </div>
        </div>

        <!-- Promoteurs actifs -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h2 class="h5 card-title mb-0">Top promoteurs : net sur la période</h2>
                </div>
                <div class="card-body">
                    @forelse($topPromoters as $promoter)
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;" aria-hidden="true">
                                <span class="fw-bold text-primary">{{ mb_substr($promoter['name'], 0, 1) }}</span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3 min-w-0">
                            <div class="fw-semibold text-break">{{ $promoter['name'] }}</div>
                            <div class="text-muted small">{{ $promoter['orders'] }} commande(s) payée(s)</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-success">{{ \App\Helpers\CurrencyHelper::formatFCFA($promoter['net']) }}</div>
                            <div class="text-muted small">net</div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-users fa-2x mb-2 opacity-50" aria-hidden="true"></i>
                        <p class="mb-0">Aucune vente de promoteur sur la période</p>
                    </div>
                    @endforelse
                </div>
                @if(! empty($topPromoters))
                <div class="card-footer bg-transparent border-0 pt-0">
                    <a href="{{ route('admin.users.index', ['role' => 'promoteur']) }}" class="btn btn-sm btn-outline-primary w-100">
                        Voir tous les promoteurs
                    </a>
                </div>
                @endif
            </div>
        </div>

        <!-- Événements en attente -->
        @if($pendingEvents->isNotEmpty())
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pb-0">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">⏰ Événements en Attente</h5>
                        <span class="badge bg-warning text-dark">{{ $platformEvents['pending'] }}</span>
                    </div>
                </div>
                <div class="card-body">
                    @foreach($pendingEvents as $event)
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{{ $event->title }}</div>
                            <div class="text-muted small">
                                Par {{ $event->promoteur?->name ?? '-' }} • {{ $event->created_at->diffForHumans() }}
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
                    <h2 class="h5 card-title mb-0">Commandes récentes (tous statuts)</h2>
                </div>
                <div class="card-body">
                    @forelse($recentOrders as $order)
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            @if($order->payment_status === 'paid')
                                <i class="fas fa-check-circle text-success"></i>
                            @elseif(in_array($order->payment_status, ['pending', 'processing'], true))
                                <i class="fas fa-clock text-warning"></i>
                            @elseif($order->payment_status === 'refunded')
                                <i class="fas fa-rotate-left text-secondary"></i>
                            @else
                                <i class="fas fa-times-circle text-danger"></i>
                            @endif
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="fw-semibold">{{ $order->order_number }}</div>
                            <div class="text-muted small">
                                {{ $order->user?->name ?? '-' }} • {{ $order->event?->title ?? '-' }}
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold">{{ \App\Helpers\CurrencyHelper::formatFCFA($order->total_amount) }}</div>
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
                @if($recentOrders->isNotEmpty())
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
            label: 'Ventes (FCFA, hors frais)',
            data: {!! json_encode($monthlyStats->pluck('revenue')) !!},
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Commandes payées',
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