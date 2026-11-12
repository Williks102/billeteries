@extends('layouts.admin')

@section('title', 'Événement : '.$event->title)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.events.index') }}">Événements</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $event->title }}</li>
@endsection

@section('content')
<div class="admin-page">
    <x-admin.page-header :title="$event->title" subtitle="Détail de l'événement et chiffres (toutes dates)" :back="route('admin.events.index')" backLabel="Événements" />

    <div class="card p-3 mb-4">
        <h2 class="h5 mb-3">Informations générales</h2>
        <ul class="list-group list-group-flush">
            <li class="list-group-item"><strong>Promoteur :</strong> {{ $event->promoteur?->name ?? '-' }}</li>
            <li class="list-group-item"><strong>Date :</strong> {{ $event->event_date?->format('d/m/Y') ?? '-' }}</li>
            <li class="list-group-item"><strong>Lieu :</strong> {{ $event->venue ?? '-' }}</li>
            <li class="list-group-item"><strong>Statut :</strong> {{ ucfirst($event->status) }}</li>
        </ul>
    </div>

    <x-stat-grid cols="3" label="Chiffres de l'événement">
        <x-stat-card label="Ventes (billets, hors frais)" unit="FCFA" :value="$stats['gmv']" hint="Commandes payées" />
        <x-stat-card label="Net promoteur à verser" unit="FCFA" :value="$stats['pending_payout']" hint="Commissions en attente" />
        <x-stat-card label="Net promoteur versé" unit="FCFA" :value="$stats['paid_out']" />
        <x-stat-card label="Billets vendus" :value="$stats['sold']" hint="Payés ou utilisés" />
        <x-stat-card label="Taux de remplissage" unit="%" :value="$stats['fill_rate']" hint="Vendus / capacité" />
        <x-stat-card label="Places disponibles" :value="$stats['available']" hint="Les réservations non payées restent disponibles" />
        <x-stat-card label="Taux d'utilisation" unit="%" :value="$stats['usage_rate']" hint="Scannés / vendus" />
        <x-stat-card label="Commandes payées" :value="$stats['orders_paid']" />
        <x-stat-card label="En attente de paiement" :value="$stats['orders_awaiting_payment']" />
    </x-stat-grid>

    <h2 class="h5 mb-3">Commandes récentes</h2>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 table-cards">
                <caption class="visually-hidden">Dix dernières commandes de l'événement</caption>
                <thead>
                    <tr>
                        <th scope="col">Client</th>
                        <th scope="col">Statut</th>
                        <th scope="col" class="text-end">Total</th>
                        <th scope="col" class="text-end">Billets</th>
                        <th scope="col">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentOrders as $order)
                        <tr>
                            <td data-label="Client">{{ $order->user?->name ?? 'N/A' }}</td>
                            <td data-label="Statut"><x-order-status-badge :order="$order" /></td>
                            <td data-label="Total" class="text-end text-nowrap">{{ \App\Helpers\CurrencyHelper::formatFCFA($order->total_amount) }}</td>
                            <td data-label="Billets" class="text-end">{{ $order->tickets_quantity ?? 0 }}</td>
                            <td data-label="Date">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted text-center py-3">Aucune commande pour cet événement.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
