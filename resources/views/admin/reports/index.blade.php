@extends('layouts.admin')

@section('title', 'Rapports')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Rapports</li>
@endsection

@section('content')
<div class="admin-page">
    <x-admin.page-header title="Rapports" subtitle="Chiffres globaux et accès aux rapports détaillés" />

    <x-period-filter :action="route('admin.reports.index')" :period="$period" />

    <x-stat-grid cols="3" label="Chiffres globaux">
        <x-stat-card label="Utilisateurs" :value="$platformUsers['total']" />
        <x-stat-card label="Événements" :value="$platformEvents['total']" />
        <x-stat-card label="Commandes payées" :value="$summary['orders_paid']" :hint="$period->label" />
        <x-stat-card label="Ventes (billets, hors frais)" unit="FCFA" :value="$summary['gmv']" :hint="$period->label" />
        <x-stat-card label="Commission plateforme" unit="FCFA" :value="$summary['platform_revenue']" :hint="$period->label" />
        <x-stat-card label="Net promoteurs à verser (total, toutes dates)" unit="FCFA" :value="$summary['pending_payout']" hint="Solde dû, indépendant de la période" />
    </x-stat-grid>

    <div class="list-group">
        <a class="list-group-item list-group-item-action py-3" href="{{ route('admin.reports.sales') }}">Rapport des ventes</a>
        <a class="list-group-item list-group-item-action py-3" href="{{ route('admin.reports.financial') }}">Rapport financier</a>
        <a class="list-group-item list-group-item-action py-3" href="{{ route('admin.reports.events') }}">Rapport des événements</a>
        <a class="list-group-item list-group-item-action py-3" href="{{ route('admin.reports.users') }}">Rapport des utilisateurs</a>
        <a class="list-group-item list-group-item-action py-3" href="{{ route('admin.revenues') }}">Revenus mensuels</a>
        <a class="list-group-item list-group-item-action py-3" href="{{ route('admin.commissions') }}">Commissions</a>
    </div>
</div>
@endsection
