@extends('layouts.admin')

@section('title', 'Rapport des ventes')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">Rapports</a></li>
    <li class="breadcrumb-item active">Ventes</li>
@endsection

@section('content')
@php $fcfa = fn ($v) => \App\Helpers\CurrencyHelper::formatFCFA($v); @endphp
<div class="admin-page">
    <x-admin.page-header title="Rapport des ventes" subtitle="Commandes payées sur la période" :back="route('admin.reports.index')" />

    <x-admin.period-filter :action="route('admin.reports.sales')" :period="$period" />

    <div class="row g-3 mb-4">
        <div class="col-6"><x-admin.stat-card label="Commandes payées" :value="$salesData->count()" /></div>
        <div class="col-6"><x-admin.stat-card label="Ventes" :value="$fcfa($salesData->sum('total_amount'))" /></div>
    </div>

    @if($salesData->isEmpty())
        <div class="alert alert-info">Aucune commande payée sur cette période.</div>
    @else
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <caption class="visually-hidden">Commandes payées de la période</caption>
                    <thead>
                        <tr>
                            <th scope="col">Commande</th>
                            <th scope="col" class="d-none d-md-table-cell">Événement</th>
                            <th scope="col" class="d-none d-md-table-cell">Client</th>
                            <th scope="col">Date</th>
                            <th scope="col" class="text-end">Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($salesData as $order)
                            <tr>
                                <td><a href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a></td>
                                <td class="d-none d-md-table-cell">{{ $order->event?->title ?? '-' }}</td>
                                <td class="d-none d-md-table-cell">{{ $order->user?->name ?? '-' }}</td>
                                <td>{{ $order->created_at->format('d/m/Y') }}</td>
                                <td class="text-end text-nowrap">{{ $fcfa($order->total_amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
