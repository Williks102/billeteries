@extends('layouts.promoteur')

@section('title', 'Rapports')

@section('content')
@php
    $types = ['overview' => "Vue d'ensemble", 'events' => 'Par événement', 'financial' => 'Financier'];
@endphp
<div class="container-fluid px-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <h1 class="h3 fw-bold mb-0">Rapports : {{ $types[$type] }}</h1>
        <nav class="d-flex flex-wrap gap-2" aria-label="Type de rapport">
            @foreach($types as $value => $label)
                <a href="{{ route('promoteur.reports.index', ['type' => $value] + request()->only(['period', 'start_date', 'end_date'])) }}"
                   class="btn {{ $type === $value ? 'btn-dark' : 'btn-outline-dark' }}" style="min-height:44px" @if($type === $value) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
    </div>

    <x-period-filter :action="route('promoteur.reports.index')" :period="$period" :keep="['type' => $type]" />

    <x-stat-grid label="Chiffres de la période">
        <x-stat-card label="Ventes (billets, hors frais)" unit="FCFA" :value="$stats['gmv']" :delta="$stats['growth']['rate']"
            :hint="$stats['growth']['rate'] !== null ? 'Comparé à la période équivalente ('.$stats['growth']['previous_label'].')' : 'Commandes payées ; évolution non calculable'" />
        <x-stat-card label="Commandes payées" :value="$stats['orders_paid']" />
        <x-stat-card label="Panier moyen" unit="FCFA" :value="$stats['average_basket']" hint="Ventes / commandes payées" />
        <x-stat-card label="Billets vendus" :value="$stats['tickets_sold']" hint="Payés ou utilisés" />
        <x-stat-card label="Commission plateforme" unit="FCFA" :value="$stats['platform_revenue']" hint="Prélevée sur la période" />
        <x-stat-card label="Revenu net (après commission)" unit="FCFA" :value="$stats['promoter_net']" />
        <x-stat-card label="Net à recevoir (total, toutes dates)" unit="FCFA" :value="$stats['pending_payout']" hint="Solde dû, indépendant de la période" />
        <x-stat-card label="Versé" unit="FCFA" :value="$stats['paid_out']" hint="Versé aux promoteurs pour des ventes de la période" />
        <x-stat-card label="Événements" :value="$stats['events']['total']" :hint="$stats['events']['published'].' publiés'" />
    </x-stat-grid>

    @if($type === 'events')
        <h2 class="h5 fw-bold">Par événement : {{ $period->label }}</h2>
        @if(count($data['events']) > 0)
            <div class="table-responsive">
                <table class="table table-cards align-middle">
                    <thead><tr><th>Événement</th><th>Ventes</th><th>Commandes payées</th><th>Billets vendus</th><th>Remplissage (ventes de la période)</th></tr></thead>
                    <tbody>
                    @foreach($data['events'] as $row)
                        <tr>
                            <td data-label="Événement" class="text-break">{{ $row['title'] }}</td>
                            <td data-label="Ventes" class="fw-semibold text-nowrap">{{ \App\Helpers\CurrencyHelper::formatFCFA($row['gmv']) }}</td>
                            <td data-label="Commandes payées">{{ $row['orders_paid'] }}</td>
                            <td data-label="Billets vendus">{{ $row['sold'] }}</td>
                            <td data-label="Remplissage (ventes de la période)">{{ \App\Helpers\CurrencyHelper::formatPercent($row['fill_rate']) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <p class="small text-muted">Le taux de remplissage compare les billets vendus sur la période à la capacité de l'événement.</p>
        @else
            <div class="alert alert-info">Aucun événement.</div>
        @endif
    @elseif($type === 'financial')
        <h2 class="h5 fw-bold">Évolution mensuelle : {{ $period->label }}</h2>
        @if(count($data['months']) > 0)
            <div class="table-responsive">
                <table class="table table-cards align-middle">
                    <thead><tr><th>Mois</th><th>Commandes payées</th><th>Ventes</th><th>Commission</th><th>Revenu net</th></tr></thead>
                    <tbody>
                    @foreach($data['months'] as $row)
                        <tr>
                            <td data-label="Mois">{{ $row['date'] }}</td>
                            <td data-label="Commandes payées">{{ $row['orders'] }}</td>
                            <td data-label="Ventes" class="fw-semibold text-nowrap">{{ \App\Helpers\CurrencyHelper::formatFCFA($row['gmv']) }}</td>
                            <td data-label="Commission" class="text-nowrap">{{ \App\Helpers\CurrencyHelper::formatFCFA($row['commission']) }}</td>
                            <td data-label="Revenu net" class="text-nowrap">{{ \App\Helpers\CurrencyHelper::formatFCFA($row['net']) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-info">Aucune vente payée sur cette période.</div>
        @endif
    @else
        <h2 class="h5 fw-bold">Ventes par {{ $data['series']['unit'] === 'day' ? 'jour' : 'mois' }}</h2>
        @if(count($data['series']['rows']) > 0)
            <div class="table-responsive">
                <table class="table table-cards align-middle">
                    <thead><tr><th>{{ $data['series']['unit'] === 'day' ? 'Jour' : 'Mois' }}</th><th>Commandes payées</th><th>Ventes</th></tr></thead>
                    <tbody>
                    @foreach($data['series']['rows'] as $row)
                        <tr>
                            <td data-label="Date">{{ $row['date'] }}</td>
                            <td data-label="Commandes payées">{{ $row['orders'] }}</td>
                            <td data-label="Ventes" class="fw-semibold text-nowrap">{{ \App\Helpers\CurrencyHelper::formatFCFA($row['gmv']) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-info">Aucune vente payée sur cette période.</div>
        @endif

        <h2 class="h5 fw-bold mt-4">Dernières commandes payées : {{ $period->label }}</h2>
        @if($data['recent_orders']->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-cards align-middle">
                    <thead><tr><th>Commande</th><th>Événement</th><th>Montant</th></tr></thead>
                    <tbody>
                    @foreach($data['recent_orders'] as $order)
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
            <div class="alert alert-info">Aucune commande payée sur cette période.</div>
        @endif
    @endif
</div>
@endsection
