{{-- resources/views/acheteur/ticket-detail.blade.php : un billet (QR visible UNIQUEMENT si le paiement est confirmé) --}}
@extends('layouts.acheteur')

@section('title', 'Billet ' . $ticket->ticket_code)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('acheteur.tickets.index') }}">Mes billets</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $ticket->ticket_code }}</li>
@endsection

@section('content')
@php
    $event = $ticket->ticketType->event ?? null;
    $statusLabels = ['sold' => 'Valide', 'used' => 'Utilisé', 'reserved' => 'Réservé', 'cancelled' => 'Annulé', 'refunded' => 'Remboursé'];
@endphp
<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card mb-4">
            <div class="card-body">
                <h1 class="h4 mb-3">{{ $event->title ?? 'Événement' }}</h1>
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><i class="fas fa-calendar me-2 text-muted" aria-hidden="true"></i><strong>Date :</strong>
                        {{ $event?->event_date?->format('d/m/Y') ?? 'À confirmer' }}</li>
                    <li class="mb-2"><i class="fas fa-clock me-2 text-muted" aria-hidden="true"></i><strong>Heure :</strong>
                        {{ $event?->event_time?->format('H:i') ?? 'À confirmer' }}</li>
                    <li class="mb-2"><i class="fas fa-map-marker-alt me-2 text-muted" aria-hidden="true"></i><strong>Lieu :</strong>
                        {{ $event->venue ?? 'À confirmer' }}</li>
                    <li class="mb-2"><i class="fas fa-ticket-alt me-2 text-muted" aria-hidden="true"></i><strong>Type :</strong>
                        {{ $ticket->ticketType->name ?? 'Billet' }}</li>
                    <li class="mb-2"><i class="fas fa-hashtag me-2 text-muted" aria-hidden="true"></i><strong>Code :</strong>
                        <code>{{ $ticket->ticket_code }}</code></li>
                    <li><strong>Statut :</strong>
                        <span class="badge {{ $isIssued ? 'bg-success' : 'bg-warning text-dark' }}">
                            {{ $isIssued ? ($statusLabels[$ticket->status] ?? 'Valide') : 'En attente de paiement' }}
                        </span></li>
                </ul>
            </div>
        </div>

        @if($isIssued && $qrDataUri)
            <div class="card mb-4 text-center">
                <div class="card-body">
                    <h2 class="h5 mb-3">Présentez ce QR code à l'entrée</h2>
                    <div class="d-inline-block bg-white p-3 border rounded">
                        <img src="{{ $qrDataUri }}" alt="QR code du billet {{ $ticket->ticket_code }}"
                             width="260" height="260" style="width: min(260px, 70vw); height: auto; display: block;">
                    </div>
                    <p class="text-muted small mt-3 mb-3">Augmentez la luminosité de l'écran pour faciliter le scan.</p>
                    {{-- tickets.download (PDF d'un billet) renvoie une erreur : la vue tickets.pdf n'existe pas.
                         On propose le PDF de la commande (acheteur.orders.download) tant que ce n'est pas corrigé. --}}
                    @if($order)
                        <a href="{{ route('acheteur.orders.download', $order) }}" class="btn btn-primary btn-lg w-100" style="min-height: 48px;">
                            <i class="fas fa-download me-2" aria-hidden="true"></i>Télécharger mes billets (PDF)
                        </a>
                    @endif
                </div>
            </div>
        @else
            <div class="alert alert-warning" role="status">
                <h2 class="h5"><i class="fas fa-hourglass-half me-2" aria-hidden="true"></i>En attente de confirmation du paiement</h2>
                <p class="mb-3">Le QR code de ce billet apparaîtra dès que votre paiement sera confirmé.</p>
                @if($order)
                    <a href="{{ route('acheteur.orders.show', $order) }}" class="btn btn-warning w-100" style="min-height: 48px;">
                        Voir ma commande et les instructions de paiement
                    </a>
                @endif
            </div>
        @endif

        <a href="{{ route('acheteur.tickets.index') }}" class="btn btn-outline-secondary w-100" style="min-height: 44px;">
            <i class="fas fa-arrow-left me-2" aria-hidden="true"></i>Mes billets
        </a>
    </div>
</div>
@endsection
