@extends('layouts.app')

@section('title', 'Réservation directe - ' . $event->title)

@section('content')
<div class="container py-4" style="max-width:720px;">
    <h1 class="h4 mb-1">Récapitulatif de votre réservation</h1>
    <p class="text-muted">{{ $event->title }}@if($event->venue) - {{ $event->venue }}@endif</p>

    @if(session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif

    <div class="alert alert-warning" role="status">
        Vos billets sont réservés pendant 60 minutes. Au-delà, la réservation est libérée.
    </div>

    <ul class="list-group mb-3">
        @foreach($selectedTickets as $item)
            <li class="list-group-item d-flex justify-content-between align-items-start gap-2">
                <span>{{ $item['ticket_name'] }} <span class="text-muted">x {{ $item['quantity'] }}</span></span>
                <strong class="text-nowrap">{{ \App\Helpers\CurrencyHelper::formatFCFA($item['total_price']) }}</strong>
            </li>
        @endforeach
        <li class="list-group-item d-flex justify-content-between bg-light">
            <strong>Total</strong>
            <strong class="text-nowrap">{{ \App\Helpers\CurrencyHelper::formatFCFA($cartTotal) }}</strong>
        </li>
    </ul>

    <a href="{{ route('checkout.show') }}" class="btn btn-primary w-100 mb-2" style="min-height:48px;display:flex;align-items:center;justify-content:center;">Continuer vers le paiement</a>
    <a href="{{ route('events.show', $event) }}" class="btn btn-link w-100" style="min-height:44px;">Modifier ma sélection</a>
</div>
@endsection
