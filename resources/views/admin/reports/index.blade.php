@extends('layouts.admin')

@section('title', 'Rapports')

@section('breadcrumb')
    <li class="breadcrumb-item active">Rapports</li>
@endsection

@section('content')
@php $fcfa = fn ($v) => \App\Helpers\CurrencyHelper::formatFCFA($v); @endphp
<div class="admin-page">
    <x-admin.page-header title="Rapports" subtitle="Chiffres globaux et accès aux rapports détaillés" />

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-4"><x-admin.stat-card label="Utilisateurs" :value="$stats['total_users']" /></div>
        <div class="col-6 col-lg-4"><x-admin.stat-card label="Événements" :value="$stats['total_events']" /></div>
        <div class="col-6 col-lg-4"><x-admin.stat-card label="Commandes" :value="$stats['total_orders']" /></div>
        <div class="col-6 col-lg-4"><x-admin.stat-card label="Ventes (commandes payées)" :value="$fcfa($stats['total_revenue'])" /></div>
        <div class="col-6 col-lg-4"><x-admin.stat-card label="Commissions plateforme" :value="$fcfa($stats['total_commissions'])" /></div>
        <div class="col-6 col-lg-4"><x-admin.stat-card label="Commissions à encaisser" :value="$fcfa($stats['pending_commissions'])" /></div>
    </div>

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
