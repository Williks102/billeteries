@extends('layouts.admin')

@section('title', 'Rapport financier')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">Rapports</a></li>
    <li class="breadcrumb-item active">Financier</li>
@endsection

@section('content')
@php $fcfa = fn ($v) => \App\Helpers\CurrencyHelper::formatFCFA($v); @endphp
<div class="admin-page">
    <x-admin.page-header title="Rapport financier" subtitle="Ventes, commission plateforme et part des promoteurs" :back="route('admin.reports.index')" />

    <x-admin.period-filter :action="route('admin.reports.financial')" :period="$period" />

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4"><x-admin.stat-card label="Ventes (commandes payées)" :value="$fcfa($financial['total_revenue'])" /></div>
        <div class="col-6 col-md-4"><x-admin.stat-card label="Commission plateforme" :value="$fcfa($financial['total_commissions'])" /></div>
        <div class="col-6 col-md-4"><x-admin.stat-card label="Reste dû aux promoteurs" :value="$fcfa($financial['net_revenue'])" hint="Ventes moins commission" /></div>
        <div class="col-6 col-md-6"><x-admin.stat-card label="Commissions encaissées" :value="$fcfa($financial['paid_commissions'])" /></div>
        <div class="col-6 col-md-6"><x-admin.stat-card label="Commissions en attente" :value="$fcfa($financial['pending_commissions'])" /></div>
    </div>

    <p class="text-muted small">Seule la commission plateforme est prélevée sur le prix des billets.</p>
</div>
@endsection
