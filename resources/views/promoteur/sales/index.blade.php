@extends('layouts.promoteur')

@section('title', 'Ventes')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <div>
            <h1 class="h3 fw-bold mb-1" style="color: var(--brand);">Ventes</h1>
            <p class="text-muted mb-0">Suivez les performances de vos événements.</p>
        </div>
        <a href="{{ route('promoteur.reports.export', ['type' => 'sales']) }}" class="btn btn-dark" style="min-height:44px">
            <i class="fas fa-download me-2" aria-hidden="true"></i>Exporter
        </a>
    </div>

    <x-period-filter :action="route('promoteur.sales.index')" :period="$period" />

    <x-stat-grid label="Ventes de la période">
        <x-stat-card label="Ventes (billets, hors frais)" unit="FCFA" :value="$summary['gmv']" :delta="$summary['growth']['rate']"
            :hint="$summary['growth']['rate'] !== null ? 'Par rapport à la période précédente de même durée' : 'Commandes payées ; évolution non calculable'" />
        <x-stat-card label="Billets vendus" :value="$summary['tickets_sold']" hint="Payés ou utilisés" />
        <x-stat-card label="Commandes payées" :value="$summary['orders_paid']" />
        <x-stat-card label="Panier moyen" unit="FCFA" :value="$summary['average_basket']" hint="Ventes / commandes payées" />
        <x-stat-card label="Revenu net (après commission)" unit="FCFA" :value="$summary['promoter_net']" />
        <x-stat-card label="Net à recevoir (total, toutes dates)" unit="FCFA" :value="$summary['pending_payout']" hint="Solde dû, indépendant de la période" />
        <x-stat-card label="Versé" unit="FCFA" :value="$summary['paid_out']" hint="Versé sur la période" />
        <x-stat-card label="Réservés" :value="$summary['tickets_reserved']" hint="En attente de paiement, pas vendus" />
    </x-stat-grid>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent"><h2 class="h5 mb-0">Évolution des ventes : {{ $period->label }}</h2></div>
                <div class="card-body">
                    @if(count($dailySales['rows']) > 0)
                        <div style="position: relative; height: 300px;">
                            <canvas id="dailySalesChart" role="img" aria-label="Ventes par {{ $dailySales['unit'] === 'day' ? 'jour' : 'mois' }}"></canvas>
                        </div>
                    @else
                        <p class="text-muted mb-0">Aucune vente payée sur cette période.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent"><h2 class="h5 mb-0">Top événements</h2></div>
                <div class="card-body">
                    @forelse($events->take(5) as $row)
                        <div class="d-flex justify-content-between gap-2 mb-2 p-2 bg-light rounded">
                            <div class="min-w-0" style="min-width:0">
                                <div class="fw-semibold text-break">{{ $row['title'] }}</div>
                                <small class="text-muted">{{ $row['sold'] }} billets vendus</small>
                            </div>
                            <strong class="text-nowrap">{{ \App\Helpers\CurrencyHelper::formatFCFA($row['gmv']) }}</strong>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Aucune vente pour cette période.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <h2 class="h5 mt-4">Performance par événement : {{ $period->label }}</h2>
    @if($events->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-cards align-middle">
                <thead><tr><th>Événement</th><th>Ventes</th><th>Billets vendus</th><th>Commandes payées</th><th>Taux de remplissage (total)</th></tr></thead>
                <tbody>
                @foreach($events as $row)
                    <tr>
                        <td data-label="Événement" class="text-break">{{ $row['title'] }}</td>
                        <td data-label="Ventes" class="fw-semibold text-nowrap">{{ \App\Helpers\CurrencyHelper::formatFCFA($row['gmv']) }}</td>
                        <td data-label="Billets vendus">{{ $row['sold'] }}</td>
                        <td data-label="Commandes payées">{{ $row['orders_paid'] }}</td>
                        <td data-label="Taux de remplissage (total)">{{ \App\Helpers\CurrencyHelper::formatPercent($fillRates[$row['id']] ?? null) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-muted">Aucune donnée de vente sur cette période.</p>
    @endif

    <h2 class="h5 mt-4">Commandes payées : {{ $period->label }}</h2>
    @if($orders->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-cards align-middle">
                <thead><tr><th>Commande</th><th>Client</th><th>Événement</th><th>Billets</th><th>Montant</th><th>Date</th></tr></thead>
                <tbody>
                @foreach($orders as $order)
                    <tr>
                        <td data-label="Commande">{{ $order->order_number }}</td>
                        <td data-label="Client" class="text-break">{{ $order->user?->name ?? $order->billing_email }}</td>
                        <td data-label="Événement" class="text-break">{{ $order->event?->title }}</td>
                        <td data-label="Billets">{{ $order->tickets_quantity }}</td>
                        <td data-label="Montant" class="fw-semibold text-nowrap">{{ \App\Helpers\CurrencyHelper::formatFCFA($order->total_amount) }}</td>
                        <td data-label="Date">{{ ($order->paid_at ?? $order->created_at)->format('d/m/Y H:i') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $orders->links() }}
    @else
        <p class="text-muted">Aucune commande payée sur cette période.</p>
    @endif
</div>
@endsection

@if(count($dailySales['rows']) > 0)
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var canvas = document.getElementById('dailySalesChart');
    if (!canvas || typeof Chart === 'undefined') { return; }
    var rows = @json($dailySales['rows']);
    new Chart(canvas.getContext('2d'), {
        type: 'line',
        data: {
            labels: rows.map(function (r) { return r.date; }),
            datasets: [{ label: 'Ventes (FCFA)', data: rows.map(function (r) { return r.gmv; }),
                borderColor: '#C2410C', backgroundColor: 'rgba(194, 65, 12, 0.1)', tension: 0.4, fill: true }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { callback: function (v) { return new Intl.NumberFormat('fr-FR').format(v) + ' F'; } } } } }
    });
});
</script>
@endpush
@endif
