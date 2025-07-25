{{-- resources/views/tickets/show.blade.php - Affichage d'un billet --}}
@extends('layouts.app')

@push('styles')
<style>
    .ticket-container {
        max-width: 600px;
        margin: 2rem auto;
        background: white;
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        overflow: hidden;
        position: relative;
    }
    
    .ticket-header {
        background: linear-gradient(135deg, #FF6B35, #E55A2B);
        color: white;
        padding: 2rem;
        text-align: center;
    }
    
    .ticket-body {
        padding: 2rem;
    }
    
    .qr-code-section {
        text-align: center;
        padding: 2rem;
        background: #f8f9fa;
        border-radius: 10px;
        margin: 1rem 0;
    }
    
    .ticket-info {
        border-left: 4px solid #FF6B35;
        padding: 1rem;
        background: #f8f9fa;
        margin: 1rem 0;
        border-radius: 0 8px 8px 0;
    }
    
    .status-badge {
        position: absolute;
        top: 20px;
        right: 20px;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: bold;
        text-transform: uppercase;
        font-size: 0.8rem;
    }
    
    .status-sold {
        background: #28a745;
        color: white;
    }
    
    .status-used {
        background: #6c757d;
        color: white;
    }
    
    .ticket-perforations {
        position: relative;
        margin: 1rem 0;
    }
    
    .ticket-perforations::before {
        content: '';
        position: absolute;
        top: 50%;
        left: -10px;
        right: -10px;
        height: 2px;
        background: repeating-linear-gradient(
            to right,
            #ddd 0px,
            #ddd 10px,
            transparent 10px,
            transparent 20px
        );
    }
    
    @media print {
        body * {
            visibility: hidden;
        }
        .ticket-container, .ticket-container * {
            visibility: visible;
        }
        .ticket-container {
            position: absolute;
            left: 0;
            top: 0;
            box-shadow: none;
        }
        .no-print {
            display: none !important;
        }
    }
</style>
@endpush

@section('content')
<div class="container">
    <div class="ticket-container">
        <!-- Statut Badge -->
        <div class="status-badge status-{{ $ticket->status }}">
            @if($ticket->status === 'sold')
                <i class="fas fa-check-circle me-1"></i>Valide
            @else
                <i class="fas fa-times-circle me-1"></i>Utilisé
            @endif
        </div>
        
        <!-- Header -->
        <div class="ticket-header">
            <h1 class="h3 mb-2">🎫 CLICBILLET</h1>
            <h2 class="h4 mb-0">{{ $ticket->ticketType->event->title }}</h2>
            <p class="mb-0 opacity-75">{{ $ticket->ticketType->name }}</p>
        </div>
        
        <!-- Body -->
        <div class="ticket-body">
            <!-- QR Code -->
            <div class="qr-code-section">
                <h5 class="mb-3">Code QR - Présentez à l'entrée</h5>
                
                @if($ticket->qr_code_url)
                    <img src="{{ $ticket->qr_code_url }}" alt="QR Code" style="max-width: 200px;">
                @else
                    <div style="width: 200px; height: 200px; margin: 0 auto; display: flex; align-items: center; justify-content: center; border: 2px dashed #ddd; border-radius: 8px;">
                        {!! $ticket->getQrCodeSvg() !!}
                    </div>
                @endif
                
                <p class="mt-3 mb-0">
                    <strong>Code billet :</strong> 
                    <code class="bg-light px-2 py-1 rounded">{{ $ticket->ticket_code }}</code>
                </p>
            </div>
            
            <!-- Perforations -->
            <div class="ticket-perforations"></div>
            
            <!-- Informations du billet -->
            <div class="row">
                <div class="col-md-6">
                    <div class="ticket-info">
                        <h6><i class="fas fa-user me-2"></i>Titulaire</h6>
                        <p class="mb-0">{{ $ticket->holder_name }}</p>
                        @if($ticket->holder_email)
                            <small class="text-muted">{{ $ticket->holder_email }}</small>
                        @endif
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="ticket-info">
                        <h6><i class="fas fa-calendar me-2"></i>Date et Heure</h6>
                        <p class="mb-0">{{ $ticket->ticketType->event->event_date->format('d/m/Y') }}</p>
                        @if($ticket->ticketType->event->event_time)
                            <small class="text-muted">{{ $ticket->ticketType->event->event_time->format('H:i') }}</small>
                        @endif
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="ticket-info">
                        <h6><i class="fas fa-map-marker-alt me-2"></i>Lieu</h6>
                        <p class="mb-0">{{ $ticket->ticketType->event->venue }}</p>
                        <small class="text-muted">{{ $ticket->ticketType->event->address }}</small>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="ticket-info">
                        <h6><i class="fas fa-tag me-2"></i>Prix</h6>
                        <p class="mb-0">{{ number_format($ticket->orderItem->unit_price) }} FCFA</p>
                        <small class="text-muted">{{ $ticket->ticketType->name }}</small>
                    </div>
                </div>
            </div>
            
            @if($ticket->used_at)
                <div class="alert alert-warning mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Billet utilisé</strong> le {{ $ticket->used_at->format('d/m/Y à H:i') }}
                </div>
            @endif
            
            <!-- Actions -->
            <div class="text-center mt-4 no-print">
                <button onclick="window.print()" class="btn btn-primary me-2">
                    <i class="fas fa-print me-2"></i>Imprimer
                </button>
                
                <a href="{{ route('tickets.download', $ticket) }}" class="btn btn-outline-primary me-2">
                    <i class="fas fa-download me-2"></i>Télécharger PDF
                </a>
                
                @if(auth()->check() && auth()->user()->isAcheteur())
                    <a href="{{ route('acheteur.tickets') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Mes billets
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

{{-- resources/views/tickets/verify.blade.php - Page de vérification publique --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h4><i class="fas fa-qrcode me-2"></i>Vérification de billet</h4>
                </div>
                
                <div class="card-body text-center">
                    @if($ticket && $ticket->isValid())
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle fa-3x mb-3"></i>
                            <h5>✅ Billet valide</h5>
                            <p>Ce billet est authentique et valide pour l'entrée.</p>
                        </div>
                        
                        <div class="ticket-details bg-light p-3 rounded">
                            <h6>Détails du billet :</h6>
                            <ul class="list-unstyled">
                                <li><strong>Événement :</strong> {{ $ticket->ticketType->event->title }}</li>
                                <li><strong>Date :</strong> {{ $ticket->ticketType->event->event_date->format('d/m/Y') }}</li>
                                <li><strong>Lieu :</strong> {{ $ticket->ticketType->event->venue }}</li>
                                <li><strong>Type :</strong> {{ $ticket->ticketType->name }}</li>
                                <li><strong>Titulaire :</strong> {{ $ticket->holder_name }}</li>
                            </ul>
                        </div>
                    @elseif($ticket && $ticket->status === 'used')
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                            <h5>⚠️ Billet déjà utilisé</h5>
                            <p>Ce billet a déjà été scanné le {{ $ticket->used_at->format('d/m/Y à H:i') }}</p>
                        </div>
                    @else
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle fa-3x mb-3"></i>
                            <h5>❌ Billet invalide</h5>
                            <p>Ce billet n'existe pas ou n'est pas valide.</p>
                        </div>
                    @endif
                    
                    <a href="{{ route('home') }}" class="btn btn-primary mt-3">
                        <i class="fas fa-home me-2"></i>Retour à l'accueil
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- resources/views/emails/ticket.blade.php - Email avec billet --}}
@extends('layouts.email')

@section('content')
<div style="text-align: center; padding: 2rem; background: #f8f9fa;">
    <h1 style="color: #FF6B35;">🎫 Votre billet ClicBillet</h1>
    <p>Merci pour votre achat ! Voici votre billet électronique.</p>
</div>

<div style="padding: 2rem;">
    <h2>{{ $ticket->ticketType->event->title }}</h2>
    
    <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin: 1rem 0;">
        <strong>Informations importantes :</strong>
        <ul>
            <li>📅 Date : {{ $ticket->ticketType->event->event_date->format('d/m/Y') }}</li>
            <li>🕐 Heure : {{ $ticket->ticketType->event->event_time->format('H:i') }}</li>
            <li>📍 Lieu : {{ $ticket->ticketType->event->venue }}</li>
            <li>🎫 Type : {{ $ticket->ticketType->name }}</li>
        </ul>
    </div>
    
    <div style="text-align: center; margin: 2rem 0;">
        <h3>Code QR - Présentez à l'entrée</h3>
        {!! $ticket->getQrCodeSvg() !!}
        <p><strong>Code billet :</strong> {{ $ticket->ticket_code }}</p>
    </div>
    
    <div style="background: #fff3cd; padding: 1rem; border-radius: 8px; border-left: 4px solid #ffc107;">
        <strong>⚠️ Instructions importantes :</strong>
        <ul>
            <li>Présentez ce QR code à l'entrée de l'événement</li>
            <li>Vous pouvez l'afficher sur votre téléphone ou l'imprimer</li>
            <li>Arrivez 30 minutes avant le début de l'événement</li>
            <li>En cas de problème, contactez l'organisateur</li>
        </ul>
    </div>
</div>

<div style="text-align: center; padding: 1rem; background: #f8f9fa;">
    <a href="{{ route('tickets.show', $ticket) }}" 
       style="background: #FF6B35; color: white; padding: 1rem 2rem; text-decoration: none; border-radius: 25px;">
        Voir mon billet en ligne
    </a>
</div>
@endsection