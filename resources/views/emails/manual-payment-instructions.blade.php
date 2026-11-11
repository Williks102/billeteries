@extends('emails.layout')

@section('title', 'Instructions de paiement')

@section('header-title', 'Commande enregistrée')
@section('header-subtitle', 'Commande #' . $order->order_number)

@section('content')
    <p>Nous avons bien enregistré votre commande pour l'événement <strong>{{ $event->title }}</strong>.</p>

    <div class="highlight">
        <p><strong>Montant à régler : {{ $total }}</strong><br>
        Mode de paiement : {{ $info['label'] }}</p>
    </div>

    <p>{{ $info['instructions'] }}</p>

    <ul>
        @foreach($info['details'] as $label => $value)
            <li><strong>{{ $label }} :</strong> {{ $value }}</li>
        @endforeach
        <li><strong>Référence à indiquer :</strong> {{ $order->order_number }}</li>
    </ul>

    @if($order->expires_at)
        <p><strong>Date limite de paiement : {{ $order->expires_at->format('d/m/Y à H:i') }}.</strong>
        Passé ce délai, votre réservation est libérée.</p>
    @endif

    <p>Vos billets vous seront envoyés dès que l'administrateur aura confirmé la réception de votre paiement.</p>
@endsection
