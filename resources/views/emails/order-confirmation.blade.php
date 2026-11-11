
@extends('emails.layout')

@section('title', 'Confirmation de commande')

@section('header-title', 'Commande confirmée !')
@section('header-subtitle', 'Merci pour votre commande #' . $order->order_number)

@section('content')
    <h2>Bonjour {{ $customer->name }}, Code unique : {{ $user->customer_code }}</h2>

    <p>Nous avons bien reçu votre commande pour l'événement <strong>{{ $event->title }}</strong>.</p>
    
    @if($order->payment_status === 'paid')
        <div class="highlight">
            <p><strong>🎉 Votre paiement a été confirmé !</strong><br>
            Vos billets sont disponibles sur votre espace.</p>
        </div>
    @else
        <div class="highlight">
            <p><strong>⏳ Paiement en attente</strong><br>
            Nous vous enverrons vos billets dès confirmation du paiement.</p>
        </div>
    @endif

    <div class="order-summary">
        <h3>Détails de votre commande</h3>
        
        <div class="order-item">
            <strong>Événement :</strong> {{ $event->title }}<br>
            <strong>Date :</strong> {{ $event->formatted_event_date ?? 'Date TBD' }}<br>
            <strong>Lieu :</strong> {{ $event->venue ?? 'Lieu TBD' }}
        </div>
        
        @foreach($order->orderItems as $item)
            <div class="order-item">
                <strong>{{ $item->ticketType->name ?? 'Billet' }}</strong><br>
                Quantité : {{ $item->quantity }} × {{ number_format($item->unit_price) }} FCFA<br>
                <strong>Sous-total : {{ number_format($item->total_price) }} FCFA</strong>
            </div>
        @endforeach
        
        <div class="total-row">
            Total de la commande : {{ $total }}
        </div>
    </div>

    @if($order->payment_status === 'paid')
        <p><strong>Prochaines étapes :</strong></p>
        <ul>
            <li>📱 Téléchargez l'app mobile ou gardez vos billets PDF</li>
            <li>🎫 Présentez vos billets à l'entrée (QR code)</li>
            <li>🎉 Profitez de votre événement !</li>
        </ul>
        
        <a href="{{ route('acheteur.orders.show', $order) }}" class="btn">
            Voir ma commande en ligne
        </a>
    @endif

    <p>Si vous avez des questions, n'hésitez pas à nous contacter.</p>
    
    <p>Cordialement,<br>L'équipe ClicBillet CI</p>
@endsection