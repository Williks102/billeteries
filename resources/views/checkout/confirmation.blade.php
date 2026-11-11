{{-- resources/views/checkout/confirmation.blade.php --}}
@extends('layouts.app')

@section('title', 'Confirmation de commande')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($order->payment_status === 'paid')
                <div class="text-center mb-4">
                    <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                    <h1 class="text-success">Paiement confirmé</h1>
                    <p class="lead">Vos billets sont disponibles dans votre espace.</p>
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
                            <span>{{ number_format($item->total_price) }} FCFA</span>
                        </div>
                    @endforeach
                    <div class="d-flex justify-content-between py-3 fw-bold fs-5">
                        <span>Total (frais de service inclus)</span>
                        <span class="text-primary">{{ number_format($order->total_amount) }} FCFA</span>
                    </div>
                </div>
            </div>

            <div class="text-center">
                <a href="{{ route('acheteur.orders.index') }}" class="btn btn-primary">
                    <i class="fas fa-list me-2"></i>Mes commandes
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
