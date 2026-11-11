@extends('layouts.promoteur')

@section('title', 'Rapports')

@section('content')
@php
    $periods = ['today' => "Aujourd'hui", 'week' => 'Semaine', 'month' => 'Mois', 'year' => 'Année'];
    $types = ['overview' => "Vue d'ensemble", 'events' => 'Par événement', 'financial' => 'Financier'];
@endphp
<div class="container-fluid px-0">
    <h1 class="h3 fw-bold mb-3">Rapports</h1>

    @isset($error)
        <div class="alert alert-danger" role="alert">{{ $error }}</div>
    @endisset

    <form method="GET" action="{{ route('promoteur.reports.index') }}" class="row g-2 mb-4">
        <div class="col-12 col-sm-5">
            <label for="type" class="form-label">Rapport</label>
            <select id="type" name="type" class="form-select" style="min-height:44px;font-size:16px">
                @foreach($types as $value => $label)
                    <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-sm-5">
            <label for="period" class="form-label">Période</label>
            <select id="period" name="period" class="form-select" style="min-height:44px;font-size:16px">
                @foreach($periods as $value => $label)
                    <option value="{{ $value }}" @selected($period === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-sm-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100" style="min-height:44px">Afficher</button>
        </div>
    </form>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><x-promoteur.stat-card label="Ventes (commandes payées)" :value="\App\Helpers\CurrencyHelper::formatFCFA($stats['total_revenue'])" /></div>
        <div class="col-6 col-lg-3"><x-promoteur.stat-card label="Commandes payées" :value="number_format($stats['total_orders'], 0, ',', ' ')" /></div>
        <div class="col-6 col-lg-3"><x-promoteur.stat-card label="Billets vendus" :value="number_format($stats['tickets_sold'], 0, ',', ' ')" /></div>
        <div class="col-6 col-lg-3"><x-promoteur.stat-card label="Commission plateforme" :value="\App\Helpers\CurrencyHelper::formatFCFA($stats['total_commissions'])" /></div>
        <div class="col-6 col-lg-3"><x-promoteur.stat-card label="Événements" :value="$stats['total_events']" :hint="$stats['published_events'] . ' publiés'" /></div>
    </div>

    @if($type === 'events')
        <h2 class="h5 fw-bold">Par événement</h2>
        @forelse($data['top_events'] ?? [] as $row)
            <div class="card mb-2"><div class="card-body d-flex justify-content-between gap-2">
                <div class="min-w-0" style="min-width:0">
                    <div class="fw-semibold text-break">{{ $row->title }}</div>
                    <div class="text-muted small">{{ $row->tickets_sold }} billet(s) · {{ $row->orders_count }} commande(s)</div>
                </div>
                <div class="fw-bold text-nowrap">{{ \App\Helpers\CurrencyHelper::formatFCFA($row->total_revenue) }}</div>
            </div></div>
        @empty
            <div class="alert alert-info">Aucun événement.</div>
        @endforelse
    @elseif($type === 'financial')
        <h2 class="h5 fw-bold">Évolution mensuelle</h2>
        @forelse($data['monthly_data'] ?? [] as $row)
            <div class="card mb-2"><div class="card-body d-flex justify-content-between">
                <div>{{ $row->month }} <span class="text-muted small">· {{ $row->orders_count }} commande(s)</span></div>
                <div class="fw-bold">{{ \App\Helpers\CurrencyHelper::formatFCFA($row->revenue) }}</div>
            </div></div>
        @empty
            <div class="alert alert-info">Aucune vente payée sur cette période.</div>
        @endforelse
    @else
        <h2 class="h5 fw-bold">Ventes par jour</h2>
        @forelse($data['daily_sales'] ?? [] as $row)
            <div class="card mb-2"><div class="card-body d-flex justify-content-between">
                <div>{{ \Illuminate\Support\Carbon::parse($row->date)->format('d/m/Y') }} <span class="text-muted small">· {{ $row->orders_count }} commande(s)</span></div>
                <div class="fw-bold">{{ \App\Helpers\CurrencyHelper::formatFCFA($row->revenue) }}</div>
            </div></div>
        @empty
            <div class="alert alert-info">Aucune vente payée sur cette période.</div>
        @endforelse

        <h2 class="h5 fw-bold mt-4">Dernières commandes payées</h2>
        @forelse($data['recent_orders'] ?? [] as $order)
            <div class="card mb-2"><div class="card-body d-flex justify-content-between gap-2">
                <div class="min-w-0" style="min-width:0">
                    <div class="fw-semibold text-break">{{ $order->order_number }}</div>
                    <div class="text-muted small text-break">{{ $order->event_title }}</div>
                </div>
                <div class="fw-bold text-nowrap">{{ \App\Helpers\CurrencyHelper::formatFCFA($order->total_amount) }}</div>
            </div></div>
        @empty
            <div class="alert alert-info">Aucune commande payée.</div>
        @endforelse
    @endif
</div>
@endsection
