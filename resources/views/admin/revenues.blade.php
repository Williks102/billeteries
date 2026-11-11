@extends('layouts.admin')

@section('title', 'Revenus')

@section('breadcrumb')
    <li class="breadcrumb-item active">Revenus</li>
@endsection

@section('content')
@php $fcfa = fn ($v) => \App\Helpers\CurrencyHelper::formatFCFA($v); @endphp
<div class="admin-page">
    <x-admin.page-header title="Revenus" subtitle="Ventes et commission plateforme" />

    <x-admin.period-filter :action="route('admin.revenues')" :period="$period" />

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-lg-3"><x-admin.stat-card label="Ventes (commandes payées)" :value="$fcfa($revenue)" /></div>
        <div class="col-6 col-lg-3"><x-admin.stat-card label="Commission plateforme" :value="$fcfa($commissions)" /></div>
        <div class="col-6 col-lg-3"><x-admin.stat-card label="Part des promoteurs" :value="$fcfa($netRevenue)" hint="Ventes moins commission" /></div>
        <div class="col-12 col-md-6 col-lg-3"><x-admin.stat-card label="Commandes créées" :value="$orders" hint="Tous statuts" /></div>
    </div>

    <h2 class="h5 mb-3">12 derniers mois</h2>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <caption class="visually-hidden">Évolution mensuelle des ventes et commissions</caption>
                <thead>
                    <tr>
                        <th scope="col">Mois</th>
                        <th scope="col" class="text-end">Ventes</th>
                        <th scope="col" class="text-end">Commission</th>
                        <th scope="col" class="text-end d-none d-md-table-cell">Part promoteurs</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($monthlyRevenue as $row)
                        <tr>
                            <td>{{ $row['month'] }}</td>
                            <td class="text-end text-nowrap">{{ $fcfa($row['revenue']) }}</td>
                            <td class="text-end text-nowrap">{{ $fcfa($row['commissions']) }}</td>
                            <td class="text-end text-nowrap d-none d-md-table-cell">{{ $fcfa($row['net_revenue']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
