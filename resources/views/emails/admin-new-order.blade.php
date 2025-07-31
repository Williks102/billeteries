{{-- =============================================== --}}
{{-- resources/views/emails/admin-new-order.blade.php --}}
{{-- Email pour notifier les admins d'une nouvelle commande --}}
{{-- =============================================== --}}
@extends('emails.layout')

@section('title', '[ADMIN] Nouvelle commande')

@section('header-title', '🔔 Nouvelle commande')
@section('header-subtitle', 'Activité sur la plateforme ClicBillet CI')

@section('content')
    <h2>Nouvelle commande reçue</h2>
    
    <p>Une nouvelle commande vient d'être passée sur la plateforme.</p>

    <div class="order-summary">
        <h3>Détails administrateur</h3>
        
        <div class="order-item">
            <strong>Commande :</strong> #{{ $order->order_number }}<br>
            <strong>Client :</strong> {{ $order->user->name }} ({{ $order->user->email }})<br>
            <strong>Date :</strong> {{ $order->created_at->format('d/m/Y à H:i') }}<br>
            <strong>Statut paiement :</strong> 
            @if($order->payment_status === 'paid')
                <span style="color: #28a745;">✅ PAYÉ</span>
            @else
                <span style="color: #ffc107;">⏳ EN ATTENTE</span>
            @endif
        </div>
        
        <div class="order-item">
            <strong>Événement :</strong> {{ $order->event->title }}<br>
            <strong>Promoteur :</strong> {{ $order->event->promoteur->name }}<br>
            <strong>Date événement :</strong> {{ $order->event->formatted_event_date ?? 'Date TBD' }}
        </div>
        
        <div class="total-row">
            Montant total : {{ number_format($order->total_amount) }} FCFA
        </div>
        
        @if($order->commission)
            <div class="order-item">
                <strong>Commission plateforme :</strong> {{ number_format($order->commission->commission_amount) }} FCFA
            </div>
        @endif
    </div>

    <a href="{{ route('admin.orders.show', $order) }}" class="btn">
        Voir dans l'interface admin
    </a>

    <p><small>Email automatique du système ClicBillet CI</small></p>
@endsection