{{-- =============================================== --}}
{{-- resources/views/emails/promoteur-new-sale.blade.php --}}
{{-- Email pour notifier le promoteur d'une nouvelle vente --}}
{{-- =============================================== --}}
@extends('emails.layout')

@section('title', 'Nouvelle vente !')

@section('header-title', '💰 Nouvelle vente !')
@section('header-subtitle', 'Félicitations, vous avez une nouvelle vente')

@section('content')
    <h2>Bonjour {{ $order->event->promoteur->name }},</h2>
    
    <p>Excellente nouvelle ! Vous venez de réaliser une nouvelle vente pour votre événement <strong>{{ $order->event->title }}</strong>.</p>

    <div class="order-summary">
        <h3>Détails de la vente</h3>
        
        <div class="order-item">
            <strong>Client :</strong> {{ $order->user->name }} ({{ $order->user->email }})<br>
            <strong>Commande :</strong> #{{ $order->order_number }}<br>
            <strong>Date :</strong> {{ $order->created_at->format('d/m/Y à H:i') }}
        </div>
        
        @foreach($order->orderItems as $item)
            <div class="order-item">
                <strong>{{ $item->ticketType->name ?? 'Billet' }}</strong><br>
                {{ $item->quantity }} billets × {{ \App\Helpers\CurrencyHelper::formatFCFA($item->unit_price) }}<br>
                <strong>Sous-total : {{ \App\Helpers\CurrencyHelper::formatFCFA($item->total_price) }}</strong>
            </div>
        @endforeach
        
        <div class="total-row">
            Total de la vente : {{ \App\Helpers\CurrencyHelper::formatFCFA($order->total_amount) }}
        </div>
        
        @if($order->commission)
            <div class="order-item">
                <strong>Votre gain net :</strong> {{ \App\Helpers\CurrencyHelper::formatFCFA($order->commission->net_amount) }}<br>
                <small>Après déduction de la commission plateforme ({{ $order->commission->commission_rate }} % du prix des billets)</small>
            </div>
        @endif
    </div>

    <a href="{{ route('promoteur.events.show', $order->event) }}" class="btn">
        Voir les détails sur votre dashboard
    </a>

    <p>Continuez sur cette lancée ! 🚀</p>
    
    <p>L'équipe ClicBillet CI</p>
@endsection