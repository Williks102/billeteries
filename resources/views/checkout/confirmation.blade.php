{{-- resources/views/checkout/confirmation.blade.php --}}
@extends('layouts.app')

@section('title', 'Confirmation de commande')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/parcours-achat.css') }}">
@endpush

@section('content')
<div class="container py-4 py-md-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            {{-- Le message flash de succès est déjà affiché par le layout : un seul message --}}
            <x-checkout-steps :current="3" />

            @if($order->payment_status === 'paid' && !empty($isFreeOrder))
                @include('checkout.partials.free-confirmed', ['showTickets' => true])
                <div class="d-grid mb-4">
                    <a href="{{ route('acheteur.orders.show', $order) }}" class="btn btn-success btn-lg" style="min-height: 48px;">
                        <i class="fas fa-ticket-alt me-2" aria-hidden="true"></i>Voir mes billets
                    </a>
                </div>
            @elseif($order->payment_status === 'paid')
                <div class="text-center mb-4">
                    <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                    <h1 class="text-success">Paiement confirmé</h1>
                    <p class="lead">Vos billets et leurs QR codes sont disponibles dans votre espace.</p>
                    @if(!empty($demoSimulatedPayment))
                        <p class="badge bg-dark fs-6 text-wrap">MODE DÉMO : paiement simulé</p>
                    @endif
                </div>
                <div class="d-grid mb-4">
                    <a href="{{ route('acheteur.orders.show', $order) }}" class="btn btn-success btn-lg" style="min-height: 48px;">
                        <i class="fas fa-qrcode me-2" aria-hidden="true"></i>Voir mes billets
                    </a>
                </div>
            @elseif(in_array($order->payment_status, ['pending', 'processing']))
                <div class="text-center mb-4">
                    <i class="fas fa-receipt text-primary" style="font-size: 3rem;"></i>
                    <h1>Commande enregistrée</h1>
                </div>
                @include('checkout.partials.manual-payment')
            @else
                <div class="alert alert-danger">
                    Cette commande n'est plus active (statut : {{ $order->payment_status_label }}).
                </div>
            @endif

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">{{ $order->event->title ?? 'Événement' }}</h5></div>
                <div class="card-body">
                    @foreach($order->orderItems as $item)
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>{{ $item->ticketType->name }} × {{ $item->quantity }}</span>
                            <span>{{ \App\Helpers\CurrencyHelper::formatPrice($item->total_price) }}</span>
                        </div>
                    @endforeach
                    <div class="d-flex justify-content-between py-3 fw-bold fs-5">
                        <span>Total</span>
                        <span class="text-primary">{{ !empty($isFreeOrder) ? 'Gratuit' : \App\Helpers\CurrencyHelper::formatPrice($order->total_amount) }}</span>
                    </div>
                </div>
            </div>

            <div class="d-grid d-sm-block text-sm-center">
                <a href="{{ route('acheteur.orders.index') }}" class="btn btn-primary" style="min-height: 44px;">
                    <i class="fas fa-list me-2"></i>Mes commandes
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
