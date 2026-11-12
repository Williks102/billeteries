@extends('layouts.admin')

@section('title', 'Rapport des ventes')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">Rapports</a></li>
    <li class="breadcrumb-item active" aria-current="page">Ventes</li>
@endsection

@section('content')
<div class="admin-page">
    <x-admin.page-header title="Rapport des ventes" subtitle="Commandes payées sur la période" :back="route('admin.reports.index')" />

    <x-period-filter :action="route('admin.reports.sales')" :period="$period" />

    <x-stat-grid label="Ventes de la période">
        <x-stat-card label="Commandes payées" :value="$summary['orders_paid']" />
        <x-stat-card label="Ventes (billets, hors frais)" unit="FCFA" :value="$summary['gmv']" />
        <x-stat-card label="Encaissé" unit="FCFA" :value="$summary['collected']" />
        <x-stat-card label="Panier moyen" unit="FCFA" :value="$summary['average_basket']" hint="Ventes / commandes payées" />
    </x-stat-grid>

    @if($salesData->isEmpty())
        <div class="alert alert-info">Aucune commande payée sur cette période.</div>
    @else
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0 table-cards">
                    <caption class="visually-hidden">Commandes payées de la période</caption>
                    <thead>
                        <tr>
                            <th scope="col">Commande</th>
                            <th scope="col">Événement</th>
                            <th scope="col">Client</th>
                            <th scope="col">Date de vente</th>
                            <th scope="col" class="text-end">Encaissé</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($salesData as $order)
                            <tr>
                                <td data-label="Commande"><a href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a></td>
                                <td data-label="Événement">{{ $order->event?->title ?? '-' }}</td>
                                <td data-label="Client">{{ $order->user?->name ?? '-' }}</td>
                                <td data-label="Date de vente">{{ ($order->paid_at ?? $order->created_at)->format('d/m/Y') }}</td>
                                <td data-label="Encaissé" class="text-end text-nowrap">{{ \App\Helpers\CurrencyHelper::formatFCFA($order->total_amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $salesData->links() }}</div>
    @endif
</div>
@endsection
