@extends('layouts.admin')

@section('title', 'Revenus')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Revenus</li>
@endsection

@section('content')
@php $fcfa = fn ($v) => \App\Helpers\CurrencyHelper::formatFCFA($v); @endphp
<div class="admin-page">
    <x-admin.page-header title="Revenus" subtitle="Ventes et commission plateforme" />

    <x-period-filter :action="route('admin.revenues')" :period="$period" />

    <x-stat-grid label="Revenus de la période">
        <x-stat-card label="Ventes (billets, hors frais)" unit="FCFA" :value="$summary['gmv']" hint="Commandes payées" />
        <x-stat-card label="Encaissé" unit="FCFA" :value="$summary['collected']" hint="Total payé par les clients" />
        <x-stat-card label="Commission plateforme" unit="FCFA" :value="$summary['platform_revenue']" hint="Revenu de la plateforme" />
        <x-stat-card label="Net promoteurs (à verser + versé)" unit="FCFA" :value="$summary['promoter_net']" />
        <x-stat-card label="Net promoteurs à verser (total, toutes dates)" unit="FCFA" :value="$summary['pending_payout']" hint="Solde dû, indépendant de la période" />
        <x-stat-card label="Commandes payées" :value="$summary['orders_paid']" />
    </x-stat-grid>

    <h2 class="h5 mb-3">12 derniers mois (commandes payées, indépendant de la période choisie)</h2>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 table-cards">
                <caption class="visually-hidden">Évolution mensuelle des ventes et commissions</caption>
                <thead>
                    <tr>
                        <th scope="col">Mois</th>
                        <th scope="col" class="text-end">Ventes (hors frais)</th>
                        <th scope="col" class="text-end">Commission</th>
                        <th scope="col" class="text-end">Net promoteurs</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($monthlyRevenue as $row)
                        <tr>
                            <td data-label="Mois">{{ $row['month'] }}</td>
                            <td class="text-end text-nowrap" data-label="Ventes">{{ $fcfa($row['revenue']) }}</td>
                            <td class="text-end text-nowrap" data-label="Commission">{{ $fcfa($row['commissions']) }}</td>
                            <td class="text-end text-nowrap" data-label="Net promoteurs">{{ $fcfa($row['net_revenue']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
