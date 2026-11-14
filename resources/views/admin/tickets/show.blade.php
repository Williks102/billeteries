@extends('layouts.admin')

@section('title', 'Billet ' . $ticket->ticket_code)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.tickets.index') }}">Billets</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $ticket->ticket_code }}</li>
@endsection

@section('content')
@php
    $buyer = $order?->user;
    $buyerEmail = $buyer?->email ?? $order?->billing_email;
@endphp
<div class="admin-page">
    <x-admin.page-header :title="'Billet ' . $ticket->ticket_code" :back="route('admin.tickets.index')" backLabel="Tous les billets">
        @if($can['edit'])
            <a href="{{ route('admin.tickets.edit', $ticket) }}" class="btn btn-outline-primary"><i class="fas fa-edit" aria-hidden="true"></i> Modifier le statut</a>
        @endif
    </x-admin.page-header>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card mb-4">
                <div class="card-header"><h2 class="h5 mb-0">Détails du billet</h2></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">Statut</dt><dd class="col-7"><x-ticket-status-badge :status="$ticket->status" /></dd>
                        <dt class="col-5">Code</dt><dd class="col-7 font-monospace">{{ $ticket->ticket_code }}</dd>
                        <dt class="col-5">Événement</dt>
                        <dd class="col-7">
                            @if($event)
                                <a href="{{ route('admin.events.show', $event) }}">{{ $event->title }}</a>
                            @else
                                <span class="text-muted">Événement supprimé</span>
                            @endif
                        </dd>
                        <dt class="col-5">Type de billet</dt><dd class="col-7">{{ $ticket->ticketType?->name ?? '—' }}</dd>
                        <dt class="col-5">Prix</dt><dd class="col-7">{{ \App\Helpers\CurrencyHelper::formatFCFA($ticket->ticketType?->price ?? 0) }}</dd>
                        <dt class="col-5">Promoteur</dt><dd class="col-7">{{ $event?->promoteur?->name ?? '—' }}</dd>
                        @if($ticket->seat_number)
                            <dt class="col-5">Place</dt><dd class="col-7">{{ $ticket->seat_number }}</dd>
                        @endif
                        <dt class="col-5">Scanné le</dt><dd class="col-7">{{ $ticket->used_at ? $ticket->used_at->format('d/m/Y H:i') : '—' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h2 class="h5 mb-0">Commande et acheteur</h2></div>
                <div class="card-body">
                    @if($order)
                        <dl class="row mb-0">
                            <dt class="col-5">Commande</dt><dd class="col-7"><a href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number ?? $order->id }}</a></dd>
                            <dt class="col-5">Paiement</dt><dd class="col-7"><x-order-status-badge :order="$order" /></dd>
                            <dt class="col-5">Acheteur</dt><dd class="col-7">{{ $buyer?->name ?? 'Invité' }}</dd>
                            <dt class="col-5">E-mail</dt><dd class="col-7 text-break">{{ $buyerEmail ?? '—' }}</dd>
                            <dt class="col-5">Téléphone</dt><dd class="col-7">{{ $buyer?->phone ?? $order->billing_phone ?? '—' }}</dd>
                            <dt class="col-5">Date de vente</dt><dd class="col-7">{{ $order->payment_status === 'paid' ? ($order->paid_at ?? $order->created_at)->format('d/m/Y H:i') : '—' }}</dd>
                        </dl>
                    @else
                        <p class="text-muted mb-0">Ce billet n'est rattaché à aucune commande.</p>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="h5 mb-0">Historique</h2></div>
                <ul class="list-group list-group-flush">
                    @foreach($history as $entry)
                        <li class="list-group-item d-flex flex-wrap justify-content-between gap-2">
                            <span><i class="fas {{ $entry['icon'] }} me-2" aria-hidden="true"></i>{{ $entry['message'] }}</span>
                            <span class="text-muted">{{ $entry['date']?->format('d/m/Y H:i') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card mb-4">
                <div class="card-header"><h2 class="h5 mb-0">QR code</h2></div>
                <div class="card-body text-center">
                    @if($qrUri)
                        <img src="{{ $qrUri }}" alt="QR code du billet {{ $ticket->ticket_code }}" width="220" height="220" class="img-fluid">
                    @elseif($issued)
                        <p class="text-muted mb-0">QR indisponible pour le moment : utilisez « Régénérer le QR ».</p>
                    @else
                        <p class="text-muted mb-0"><i class="fas fa-lock me-1" aria-hidden="true"></i>Billet non émis : le QR n'existe qu'après confirmation du paiement de la commande.</p>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="h5 mb-0">Actions</h2></div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($can['mark_used'])
                            <form method="POST" action="{{ route('admin.tickets.mark-used', $ticket) }}" data-confirm="Marquer ce billet comme utilisé ?">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-success w-100"><i class="fas fa-check" aria-hidden="true"></i> Marquer comme utilisé</button>
                            </form>
                        @endif
                        @if($can['regenerate_qr'])
                            <form method="POST" action="{{ route('admin.tickets.bulk-action') }}">
                                @csrf
                                <input type="hidden" name="action" value="regenerate_qr">
                                <input type="hidden" name="tickets[]" value="{{ $ticket->id }}">
                                <button type="submit" class="btn btn-outline-secondary w-100"><i class="fas fa-sync" aria-hidden="true"></i> Régénérer le QR</button>
                            </form>
                            <a href="{{ route('admin.tickets.download', $ticket) }}" class="btn btn-outline-secondary"><i class="fas fa-download" aria-hidden="true"></i> Télécharger le QR</a>
                        @endif
                        @if($can['reactivate'])
                            <form method="POST" action="{{ route('admin.tickets.reactivate', $ticket) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-outline-success w-100"><i class="fas fa-undo" aria-hidden="true"></i> Réactiver le billet</button>
                            </form>
                        @endif
                        @if($can['cancel'])
                            <form method="POST" action="{{ route('admin.tickets.cancel', $ticket) }}" data-confirm="Annuler ce billet ?">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-outline-danger w-100"><i class="fas fa-ban" aria-hidden="true"></i> Annuler le billet</button>
                            </form>
                        @endif
                        @if($order)
                            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-outline-secondary"><i class="fas fa-shopping-cart" aria-hidden="true"></i> Voir la commande</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
