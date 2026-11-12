@extends('layouts.promoteur')
@section('title', 'Tableau de bord')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 fw-bold mb-1" style="color: var(--brand);">Tableau de bord</h1>
            <p class="text-muted mb-0">Bonjour {{ auth()->user()->name }}, voici l'activité de vos événements.</p>
        </div>
        <a href="{{ route('promoteur.events.create') }}" class="btn btn-dark btn-lg" style="min-height:44px">
            <i class="fas fa-plus me-2" aria-hidden="true"></i>Créer un événement
        </a>
    </div>

    <x-period-filter :action="route('promoteur.dashboard')" :period="$period" />

    <h2 class="h5 mt-3">Ventes : {{ $period->label }}</h2>
    <x-stat-grid label="Ventes de la période">
        <x-stat-card label="Ventes (billets, hors frais)" unit="FCFA" :value="$summary['gmv']" :delta="$summary['growth']['rate']"
            :hint="$summary['growth']['rate'] !== null ? 'Par rapport à la période précédente de même durée ('.$summary['growth']['previous_label'].')' : 'Commandes payées ; évolution non calculable'" />
        <x-stat-card label="Revenu net (après commission)" unit="FCFA" :value="$summary['promoter_net']" hint="Ce qui vous revient sur la période" />
        <x-stat-card label="Net à recevoir (total, toutes dates)" unit="FCFA" :value="$summary['pending_payout']" hint="Solde dû, indépendant de la période" />
        <x-stat-card label="Versé" unit="FCFA" :value="$summary['paid_out']" hint="Versé sur la période" />
        <x-stat-card label="Commission plateforme" unit="FCFA" :value="$summary['platform_revenue']" hint="Prélevée sur la période" />
        <x-stat-card label="Commandes payées" :value="$summary['orders_paid']" />
        <x-stat-card label="Panier moyen" unit="FCFA" :value="$summary['average_basket']" hint="Ventes / commandes payées" />
        <x-stat-card label="Billets vendus" :value="$summary['tickets_sold']" hint="Payés ou utilisés" />
        <x-stat-card label="Taux d'utilisation" unit="%" :value="$summary['usage_rate']" hint="Scannés / vendus" />
    </x-stat-grid>

    <h2 class="h5">Mes événements (état actuel)</h2>
    <x-stat-grid cols="3" label="Mes événements">
        <x-stat-card label="Événements créés" :value="$summary['events']['total']" :hint="$summary['events']['created'].' créés sur la période'" />
        <x-stat-card label="Événements publiés" :value="$summary['events']['published']" />
        <x-stat-card label="Réservés" :value="$summary['tickets_reserved']" hint="Commandes en attente de paiement, pas encore vendus" />
        <x-stat-card label="Taux de remplissage" unit="%" :value="$summary['fill_rate']" hint="Vendus / capacité, toutes dates" />
        <x-stat-card label="Places disponibles" :value="$summary['tickets_available']" hint="Capacité moins billets vendus" />
    </x-stat-grid>

    <div class="row g-4 mt-1">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent"><h2 class="h5 mb-0">Mes événements récents</h2></div>
                <div class="card-body">
                    @forelse($recentEvents as $event)
                        @php $fig = $eventStats[$event->id] ?? null; @endphp
                        <div class="d-flex flex-wrap justify-content-between gap-2 border-bottom py-3">
                            <div class="min-w-0" style="min-width:0">
                                <div class="fw-semibold text-break">{{ $event->title }}</div>
                                <div class="small text-muted">
                                    {{ $event->event_date ? $event->event_date->format('d/m/Y') : 'Date non définie' }}
                                    @if($event->venue) · {{ $event->venue }} @endif
                                </div>
                                <div class="small">
                                    {{ $fig['sold'] ?? 0 }} / {{ $fig['capacity'] ?? 0 }} billets vendus
                                    ({{ \App\Helpers\CurrencyHelper::formatPercent($fig['fill_rate'] ?? null) }})
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-{{ $event->status === 'published' ? 'success' : 'secondary' }}">{{ $event->status === 'published' ? 'Publié' : ucfirst($event->status) }}</span>
                                <a href="{{ route('promoteur.events.show', $event) }}" class="btn btn-outline-dark btn-sm d-inline-flex align-items-center justify-content-center" style="min-width:44px;min-height:44px" aria-label="Voir {{ $event->title }}"><i class="fas fa-eye" aria-hidden="true"></i></a>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-2">Aucun événement créé.</p>
                        <a href="{{ route('promoteur.events.create') }}" class="btn btn-dark" style="min-height:44px">Créer un événement</a>
                    @endforelse
                </div>
                @if($recentEvents->isNotEmpty())
                    <div class="card-footer bg-transparent">
                        <a href="{{ route('promoteur.events.index') }}" class="btn btn-outline-dark" style="min-height:44px">Voir tous mes événements</a>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent"><h2 class="h5 mb-0">Dernières commandes payées : {{ $period->label }}</h2></div>
                <div class="card-body p-0">
                    @if($recentOrders->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table mb-0 table-cards">
                                <thead><tr><th>Commande</th><th>Événement</th><th>Montant</th></tr></thead>
                                <tbody>
                                @foreach($recentOrders as $order)
                                    <tr>
                                        <td data-label="Commande">{{ $order->order_number }}</td>
                                        <td data-label="Événement" class="text-break">{{ $order->event?->title }}</td>
                                        <td data-label="Montant" class="fw-semibold text-nowrap">{{ \App\Helpers\CurrencyHelper::formatFCFA($order->total_amount) }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted p-3 mb-0">Aucune commande payée sur cette période.</p>
                    @endif
                </div>
                <div class="card-footer bg-transparent">
                    <a href="{{ route('promoteur.sales.index', request()->only(['period', 'start_date', 'end_date'])) }}" class="btn btn-outline-dark" style="min-height:44px">Voir les ventes</a>
                    <a href="{{ route('promoteur.reports.index') }}" class="btn btn-outline-dark" style="min-height:44px">Rapports</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
