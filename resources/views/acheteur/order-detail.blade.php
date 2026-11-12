{{-- resources/views/acheteur/order-detail.blade.php --}}
@extends('layouts.acheteur')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('title', 'Détail Commande #' . ($order->order_number ?? $order->id) . ' - ClicBillet CI')

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('acheteur.orders.index') }}">Mes Commandes</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Commande #{{ $order->order_number ?? $order->id }}</li>
@endsection

@section('content')
    <!-- Header avec actions -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1">
                <i class="fas fa-shopping-bag text-primary me-2"></i>
                Commande #{{ $order->order_number ?? $order->id }}
            </h2>
            <p class="text-muted mb-0">
                Passée le {{ $order->created_at->format('d/m/Y à H:i') }}
                <!-- Statut de paiement -->
                <x-order-status-badge :order="$order" class="ms-2" />
            </p>
        </div>
        <div class="d-grid d-md-flex gap-2">
            @if($order->payment_status === 'paid')
                <a href="{{ route('acheteur.orders.download', $order) }}" class="btn btn-acheteur" style="min-height: 44px;">
                    <i class="fas fa-download me-2"></i>Télécharger mes billets
                </a>
            @endif
            <a href="{{ route('acheteur.orders.index') }}" class="btn btn-outline-acheteur">
                <i class="fas fa-arrow-left me-2"></i>Retour
            </a>
        </div>
    </div>

    @if($awaitingPayment)
        <x-payment-instructions :info="$paymentInfo" :deadline="$paymentDeadline" :reference="$order->order_number" :amount="$order->total_amount" />
    @elseif($order->payment_status === 'paid' && !empty($demoSimulatedPayment))
        <div class="alert alert-dark" role="status">MODE DÉMO : paiement simulé</div>
    @endif

    <!-- Informations de l'événement -->
    <div class="card mb-4 border-primary">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-calendar-alt me-2"></i>Événement
            </h5>
        </div>
        <div class="card-body">
            <div class="row align-items-center">
                @if($order->event && $order->event->image)
                    <div class="col-md-3">
                        <img src="{{ Storage::url( $order->event->image) }}" 
                             alt="{{ $order->event->title }}" 
                             class="img-fluid rounded">
                    </div>
                @endif
                <div class="col-md-{{ $order->event && $order->event->image ? '9' : '12' }}">
                    <h4 class="text-primary mb-2">{{ $order->event->title ?? 'Événement supprimé' }}</h4>
                    
                    @if($order->event)
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-2">
                                    <i class="fas fa-calendar text-muted me-2"></i>
                                    <strong>Date :</strong> {{ $order->event->formatted_event_date ?? 'Date TBD' }}
                                </p>
                                <p class="mb-2">
                                    <i class="fas fa-clock text-muted me-2"></i>
                                    <strong>Heure :</strong> {{ $order->event->formatted_event_time ?? 'Heure TBD' }}
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2">
                                    <i class="fas fa-map-marker-alt text-muted me-2"></i>
                                    <strong>Lieu :</strong> {{ $order->event->venue ?? 'Lieu TBD' }}
                                </p>
                                @if($order->event->address)
                                    <p class="mb-2">
                                        <i class="fas fa-map text-muted me-2"></i>
                                        <strong>Adresse :</strong> {{ $order->event->address }}
                                    </p>
                                @endif
                            </div>
                        </div>
                        
                        @if($order->event->category)
                            <span class="badge bg-info">{{ $order->event->category->name }}</span>
                        @endif
                        
                        @if($order->event->description)
                            <div class="mt-3">
                                <p class="text-muted">{{ Str::limit($order->event->description, 200) }}</p>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Détails de la commande -->
        <div class="col-lg-8">
            
            <!-- Items de la commande -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-ticket-alt me-2"></i>Billets commandés
                    </h5>
                </div>
                <div class="card-body">
                    @if($order->orderItems && $order->orderItems->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Type de billet</th>
                                        <th class="text-center">Quantité</th>
                                        <th class="text-end">Prix unitaire</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->orderItems as $item)
                                        <tr>
                                            <td>
                                                <strong>{{ $item->ticketType->name ?? 'Type supprimé' }}</strong>
                                                @if($item->ticketType && $item->ticketType->description)
                                                    <br><small class="text-muted">{{ $item->ticketType->description }}</small>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary">{{ $item->quantity }}</span>
                                            </td>
                                            <td class="text-end">{{ \App\Helpers\CurrencyHelper::formatFCFA($item->unit_price) }}</td>
                                            <td class="text-end fw-bold">{{ \App\Helpers\CurrencyHelper::formatFCFA($item->total_price) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="3" class="text-end">Total commande :</th>
                                        <th class="text-end text-primary fs-5">{{ \App\Helpers\CurrencyHelper::formatFCFA($order->total_amount) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-exclamation-triangle text-warning fs-1 mb-3"></i>
                            <p class="text-muted">Aucun billet trouvé pour cette commande.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Billets générés (si payé) -->
            @if($order->payment_status === 'paid' && $order->tickets && $order->tickets->count() > 0)
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-check-circle me-2"></i>Mes billets ({{ $order->tickets->count() }})
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach($order->tickets as $ticket)
                                <div class="col-12 col-md-6">
                                    <div class="card border-success">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="mb-0">{{ $ticket->ticketType->name ?? 'Billet Standard' }}</h6>
                                                <span class="badge {{ $ticket->status === 'sold' ? 'bg-success' : 'bg-warning text-dark' }}">
                                                    {{ ['sold' => 'Valide', 'used' => 'Utilisé'][$ticket->status] ?? ucfirst($ticket->status) }}
                                                </span>
                                            </div>
                                            
                                            <p class="text-muted mb-2">
                                                <i class="fas fa-hashtag me-1"></i>
                                                <code>{{ $ticket->ticket_code }}</code>
                                            </p>
                                            
                                            @if($ticket->seat_number)
                                                <p class="text-muted mb-2">
                                                    <i class="fas fa-chair me-1"></i>
                                                    Siège: {{ $ticket->seat_number }}
                                                </p>
                                            @endif

                                            @php($qrDataUri = $ticket->qrDataUri())
                                            @if($qrDataUri)
                                                <div class="text-center mb-3">
                                                    <div class="d-inline-block bg-white p-2 border rounded">
                                                        <img src="{{ $qrDataUri }}" alt="QR code du billet {{ $ticket->ticket_code }}"
                                                             width="220" height="220" style="width: min(220px, 70vw); height: auto; display: block;">
                                                    </div>
                                                </div>
                                            @endif
                                            <a href="{{ route('acheteur.tickets.show', $ticket) }}"
                                               class="btn btn-outline-primary w-100" style="min-height: 44px;">
                                                <i class="fas fa-eye me-1" aria-hidden="true"></i>Voir le billet
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

        </div>

        <!-- Sidebar avec informations -->
        <div class="col-lg-4">
            
            <!-- Résumé de la commande -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Résumé de la commande
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td class="fw-bold">Numéro :</td>
                            <td>#{{ $order->order_number ?? $order->id }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Date :</td>
                            <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Statut :</td>
                            <td>
                                <span class="badge {{ $order->status == 'completed' ? 'bg-success' : ($order->status == 'pending' ? 'bg-warning' : 'bg-danger') }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Paiement :</td>
                            <td>
                                <x-order-status-badge :order="$order" />
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Méthode :</td>
                            <td>{{ $order->isFree() ? 'Gratuit (aucun paiement)' : ($order->payment_method ? $paymentInfo['label'] : 'Non définie') }}</td>
                        </tr>
                        <tr class="table-active">
                            <td class="fw-bold">Total :</td>
                            <td class="fw-bold text-primary">{{ \App\Helpers\CurrencyHelper::formatFCFA($order->total_amount) }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-bolt me-2"></i>Actions rapides
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($order->payment_status === 'paid')
                            <a href="{{ route('acheteur.orders.download', $order) }}" 
                               class="btn btn-success">
                                <i class="fas fa-download me-2"></i>Télécharger PDF
                            </a>
                        @endif
                        
                        <a href="{{ route('acheteur.orders.index') }}" 
                           class="btn btn-outline-primary">
                            <i class="fas fa-list me-2"></i>Toutes mes commandes
                        </a>
                        
                        @if($order->event)
                            <a href="{{ route('events.show', $order->event) }}" 
                               class="btn btn-outline-info">
                                <i class="fas fa-info-circle me-2"></i>Voir l'événement
                            </a>
                        @endif
                        
                        <a href="{{ route('home') }}" 
                           class="btn btn-outline-secondary">
                            <i class="fas fa-home me-2"></i>Découvrir d'autres événements
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
