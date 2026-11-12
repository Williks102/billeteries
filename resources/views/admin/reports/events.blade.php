@extends('layouts.admin')

@section('title', 'Rapport des événements')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">Rapports</a></li>
    <li class="breadcrumb-item active">Événements</li>
@endsection

@section('content')
@php $fcfa = fn ($v) => \App\Helpers\CurrencyHelper::formatFCFA($v); @endphp
<div class="admin-page">
    <x-admin.page-header title="Rapport des événements" subtitle="Événements créés sur la période et ventes de la période" :back="route('admin.reports.index')" />

    <x-period-filter :action="route('admin.reports.events')" :period="$period" />

    <x-stat-grid label="Événements créés sur la période">
        <x-stat-card label="Événements créés" :value="$stats['total']" :hint="$period->label" />
        <x-stat-card label="Publiés" :value="$stats['published']" />
        <x-stat-card label="En attente" :value="$stats['pending']" />
        <x-stat-card label="Brouillons" :value="$stats['draft']" />
        <x-stat-card label="Ventes de la période (billets, hors frais)" unit="FCFA" :value="$summary['gmv']" hint="Tous événements" />
        <x-stat-card label="Commandes payées" :value="$summary['orders_paid']" hint="Tous événements" />
    </x-stat-grid>

    @if(! empty($topCategories))
        <h2 class="h5 mb-3">Catégories les plus actives</h2>
        <div class="card mb-4">
            <div class="table-responsive">
                <table class="table mb-0 table-cards">
                    <caption class="visually-hidden">Catégories les plus actives</caption>
                    <thead><tr><th scope="col">Catégorie</th><th scope="col" class="text-end">Événements créés</th><th scope="col" class="text-end">Ventes de la période</th></tr></thead>
                    <tbody>
                        @foreach($topCategories as $row)
                            <tr>
                                <td data-label="Catégorie">{{ $row['name'] }}</td>
                                <td data-label="Événements créés" class="text-end">{{ $row['events'] }}</td>
                                <td data-label="Ventes de la période" class="text-end text-nowrap">{{ $fcfa($row['gmv']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <h2 class="h5 mb-3">Détail</h2>
    @if($eventsData->isEmpty())
        <div class="alert alert-info">Aucun événement créé sur cette période.</div>
    @else
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0 table-cards">
                    <caption class="visually-hidden">Événements créés sur la période</caption>
                    <thead>
                        <tr>
                            <th scope="col">Événement</th>
                            <th scope="col">Promoteur</th>
                            <th scope="col">Catégorie</th>
                            <th scope="col">Statut</th>
                            <th scope="col" class="text-end">Ventes de la période</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($eventsData as $event)
                            <tr>
                                <td data-label="Événement"><a href="{{ route('admin.events.show', $event) }}">{{ $event->title }}</a></td>
                                <td data-label="Promoteur">{{ $event->promoteur?->name ?? '-' }}</td>
                                <td data-label="Catégorie">{{ $event->category?->name ?? '-' }}</td>
                                <td data-label="Statut"><span class="badge bg-secondary">{{ $event->status }}</span></td>
                                <td data-label="Ventes de la période" class="text-end text-nowrap">{{ $fcfa($eventSales[$event->id]['gmv'] ?? 0) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $eventsData->links() }}</div>
    @endif
</div>
@endsection
