@extends('layouts.admin')

@section('title', 'Rapport financier')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">Rapports</a></li>
    <li class="breadcrumb-item active">Financier</li>
@endsection

@section('content')
<div class="admin-page">
    <x-admin.page-header title="Rapport financier" subtitle="Ventes, commission plateforme et part des promoteurs" :back="route('admin.reports.index')" />

    <x-period-filter :action="route('admin.reports.financial')" :period="$period" />

    <x-stat-grid cols="3" label="Chiffres financiers de la période">
        <x-stat-card label="Ventes (billets, hors frais)" unit="FCFA" :value="$summary['gmv']" hint="Commandes payées" />
        <x-stat-card label="Encaissé" unit="FCFA" :value="$summary['collected']" hint="Total payé par les clients" />
        <x-stat-card label="Commission plateforme" unit="FCFA" :value="$summary['platform_revenue']" hint="Revenu de la plateforme" />
        <x-stat-card label="Net promoteurs à verser" unit="FCFA" :value="$summary['pending_payout']" hint="Commissions en attente" />
        <x-stat-card label="Net promoteurs versé" unit="FCFA" :value="$summary['paid_out']" hint="Commissions marquées payées" />
        <x-stat-card label="Commissions suspendues" unit="FCFA" :value="$summary['held']['commission']" hint="Commandes remboursées : hors revenus" />
    </x-stat-grid>

    <p class="text-muted small">Seule la commission plateforme est prélevée sur le prix des billets. Les commandes en attente, échouées ou remboursées ne comptent dans aucune vente.</p>
</div>
@endsection
