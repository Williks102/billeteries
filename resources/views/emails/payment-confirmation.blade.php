@extends('emails.layout')

@section('title', 'Vos billets sont prêts !')

@section('header-title', '🎫 Vos billets sont prêts !')
@section('header-subtitle', ($order->isFree() ? 'Commande gratuite confirmée' : 'Paiement confirmé') . ' pour la commande #' . $order->order_number)

@section('content')
    <h2>Félicitations {{ $order->user->name }} !</h2>
    
    @if($order->isFree())
    <p>Votre commande gratuite est confirmée : aucun paiement n'est nécessaire. Vos billets pour <strong>{{ $order->event->title }}</strong> sont maintenant disponibles !</p>
    @else
    <p>Votre paiement a été confirmé avec succès. Vos billets pour <strong>{{ $order->event->title }}</strong> sont maintenant disponibles !</p>
    @endif
    
    <div class="highlight">
        @if($order->isFree())
        <p><strong>✅ Commande gratuite confirmée</strong><br>
        @else
        <p><strong>✅ Paiement confirmé : {{ \App\Helpers\CurrencyHelper::formatFCFA($order->total_amount) }}</strong><br>
        @endif
        📎 Vos billets sont disponibles sur votre espace</p>
    </div>

    <div class="order-summary">
        <h3>Informations importantes</h3>
        
        <div class="order-item">
            <strong>📅 Date de l'événement :</strong> {{ $order->event->formatted_event_date ?? 'Date TBD' }}<br>
            <strong>🕐 Heure :</strong> {{ $order->event->formatted_event_time ?? 'Heure TBD' }}<br>
            <strong>📍 Lieu :</strong> {{ $order->event->venue ?? 'Lieu TBD' }}
        </div>
        
        <div class="order-item">
            <strong>🎫 Nombre de billets :</strong> {{ $order->tickets->count() }}<br>
            <strong>🎟️ Codes billets :</strong>
            @foreach($order->tickets as $ticket)
                <code>{{ $ticket->ticket_code }}</code>@if(!$loop->last), @endif
            @endforeach
        </div>
    </div>

    <p><strong>Instructions importantes :</strong></p>
    <ul>
        <li>📱 <strong>Imprimez vos billets</strong> ou gardez-les sur votre téléphone</li>
        <li>🔍 <strong>QR Code obligatoire</strong> : Chaque billet a un QR code unique à scanner à l'entrée</li>
        <li>⏰ <strong>Arrivez à l'heure</strong> : Les portes ouvrent généralement 1h avant l'événement</li>
        <li>🆔 <strong>Pièce d'identité</strong> : Apportez une pièce d'identité valide</li>
        <li>❌ <strong>Un seul scan</strong> : Chaque billet ne peut être scanné qu'une seule fois</li>
    </ul>

    <a href="{{ route('acheteur.orders.show', $order) }}" class="btn">
        Gérer mes billets en ligne
    </a>

    <p>Nous vous souhaitons un excellent événement !</p>
    
    <p>L'équipe ClicBillet CI</p>
@endsection