{{-- resources/views/checkout/guest-confirmation.blade.php --}}

@extends('layouts.app')

@section('title', 'Commande confirmée')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/parcours-achat.css') }}">
@endpush

@section('content')
<div class="container py-4 py-md-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            {{-- Le message flash de succès est déjà affiché par le layout : un seul message --}}
            <x-checkout-steps :current="3" />

            @if($order->payment_status === 'paid')
            <!-- Header de confirmation -->
            <div class="text-center mb-4">
                <div class="success-icon mb-3">
                    <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                </div>
                <h1 class="text-success">Paiement confirmé !</h1>
                <p class="lead">Vos billets vous ont été envoyés par email</p>
                @if(!empty($demoSimulatedPayment))
                    <p class="badge bg-dark fs-6 text-wrap">MODE DÉMO : paiement simulé</p>
                @endif
            </div>
            @elseif(in_array($order->payment_status, ['pending', 'processing']))
            <div class="text-center mb-4">
                <i class="fas fa-receipt text-primary" style="font-size: 3rem;"></i>
                <h1>Commande enregistrée</h1>
            </div>
            @include('checkout.partials.manual-payment')
            @else
            <div class="alert alert-danger">Cette commande n'est plus active.</div>
            @endif

            <!-- Détails de la commande -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-receipt me-2"></i>
                        Commande #{{ $order->order_number }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Événement</h6>
                            <p>{{ $order->event->title }}</p>
                            
                            <h6>Date</h6>
                            <p>{{ $order->event->formatted_event_date ?? 'À confirmer' }}</p>
                            
                            <h6>Lieu</h6>
                            <p>{{ $order->event->venue }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Email</h6>
                            <p>{{ $order->billing_email }}</p>
                            
                            <h6>Téléphone</h6>
                            <p>{{ $order->billing_phone }}</p>
                        </div>
                    </div>

                    <!-- Billets -->
                    <h6 class="mt-4">Vos billets</h6>
                    @foreach($order->orderItems as $item)
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>{{ $item->ticketType->name }} × {{ $item->quantity }}</span>
                            <span class="fw-bold">{{ \App\Helpers\CurrencyHelper::formatPrice($item->total_price) }}</span>
                        </div>
                    @endforeach
                    
                    <div class="d-flex justify-content-between py-3 fw-bold fs-5 border-top">
                        <span>Total</span>
                        <span class="text-primary">{{ \App\Helpers\CurrencyHelper::formatPrice($order->total_amount) }}</span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-envelope fa-2x text-primary mb-3"></i>
                            <h6>Vérifiez votre email</h6>
                            <p class="text-muted small">
                                @if($order->payment_status === 'paid')
                                    Vos billets ont été envoyés à {{ $order->billing_email }}
                                @else
                                    Les instructions de paiement ont été envoyées à {{ $order->billing_email }}. Vos billets suivront après confirmation du paiement.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                
            </div>

            <!-- Navigation -->
            <div class="d-grid gap-2 d-sm-block text-sm-center mt-4">
                <a href="{{ route('home') }}" class="btn btn-outline-primary" style="min-height: 44px;">
                    <i class="fas fa-home me-2"></i>Retour à l'accueil
                </a>
                <a href="{{ route('events.all') }}" class="btn btn-primary ms-sm-2" style="min-height: 44px;">
                    <i class="fas fa-calendar-alt me-2"></i>Voir d'autres événements
                </a>
            </div>
        </div>
    </div>
</div>

@endsection