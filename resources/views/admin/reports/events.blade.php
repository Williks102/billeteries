@extends('layouts.admin')

@section('title', 'Rapport des événements')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">Rapports</a></li>
    <li class="breadcrumb-item active">Événements</li>
@endsection

@section('content')
@php $fcfa = fn ($v) => \App\Helpers\CurrencyHelper::formatFCFA($v); @endphp
<div class="admin-page">
    <x-admin.page-header title="Rapport des événements" subtitle="Événements créés sur la période" :back="route('admin.reports.index')" />

    <x-admin.period-filter :action="route('admin.reports.events')" :period="$period" />

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><x-admin.stat-card label="Événements" :value="$stats['total_events']" /></div>
        <div class="col-6 col-lg-3"><x-admin.stat-card label="Publiés" :value="$stats['published_events']" /></div>
        <div class="col-6 col-lg-3"><x-admin.stat-card label="En attente" :value="$stats['pending_events']" /></div>
        <div class="col-6 col-lg-3"><x-admin.stat-card label="Brouillons" :value="$stats['draft_events']" /></div>
        <div class="col-6"><x-admin.stat-card label="Ventes (commandes payées)" :value="$fcfa($stats['total_revenue'])" /></div>
        <div class="col-6"><x-admin.stat-card label="Moyenne par événement" :value="$fcfa($stats['avg_revenue_per_event'])" /></div>
    </div>

    @if($topCategories->isNotEmpty())
        <h2 class="h5 mb-3">Catégories les plus actives</h2>
        <div class="card mb-4">
            <div class="table-responsive">
                <table class="table mb-0">
                    <caption class="visually-hidden">Catégories les plus actives</caption>
                    <thead><tr><th scope="col">Catégorie</th><th scope="col" class="text-end">Événements</th><th scope="col" class="text-end">Ventes</th></tr></thead>
                    <tbody>
                        @foreach($topCategories as $row)
                            <tr>
                                <td>{{ $row['name'] }}</td>
                                <td class="text-end">{{ $row['count'] }}</td>
                                <td class="text-end text-nowrap">{{ $fcfa($row['revenue']) }}</td>
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
                <table class="table table-hover mb-0">
                    <caption class="visually-hidden">Événements de la période</caption>
                    <thead>
                        <tr>
                            <th scope="col">Événement</th>
                            <th scope="col" class="d-none d-md-table-cell">Promoteur</th>
                            <th scope="col" class="d-none d-md-table-cell">Catégorie</th>
                            <th scope="col">Statut</th>
                            <th scope="col" class="text-end">Ventes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($eventsData as $event)
                            <tr>
                                <td><a href="{{ route('admin.events.show', $event) }}">{{ $event->title }}</a></td>
                                <td class="d-none d-md-table-cell">{{ $event->promoteur?->name ?? '-' }}</td>
                                <td class="d-none d-md-table-cell">{{ $event->category?->name ?? '-' }}</td>
                                <td><span class="badge bg-secondary">{{ $event->status }}</span></td>
                                <td class="text-end text-nowrap">{{ $fcfa($event->orders->where('payment_status', 'paid')->sum('total_amount')) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
