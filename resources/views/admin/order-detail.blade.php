{{-- resources/views/admin/order-detail.blade.php --}}
@extends('layouts.admin')

@section('content')
<h1 class="mb-4">Commande #{{ $order->order_number }}</h1>
<div class="card p-4">
    <p><strong>Client :</strong> {{ $order->user->name }}</p>
    <p><strong>Email :</strong> {{ $order->billing_email }}</p>
    <p><strong>Téléphone :</strong> {{ $order->billing_phone }}</p>
    <p><strong>Événement :</strong> {{ $order->event->title }}</p>
    <p><strong>Total :</strong> {{ Currency::formatFCFA($order->total_amount) }}</p>
    <p><strong>Statut :</strong> {{ ucfirst($order->payment_status) }}</p>

    <hr>
    <h5>Billets</h5>
    <ul>
        @foreach($order->tickets as $ticket)
            <li>{{ $ticket->ticket_code }} ({{ ucfirst($ticket->status) }})</li>
        @endforeach
    </ul>
</div>
@endsection