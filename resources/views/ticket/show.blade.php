@extends('layouts.app')

@section('title', 'Billet ' . $ticket->ticket_code . ' - ClicBillet CI')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Ticket Card -->
            <div class="ticket-container">
                <!-- Status Badge -->
                <div class="ticket-status">
                    @if($ticket->status === 'sold')
                        <span class="status-badge status-valid">
                            <i class="fas fa-check-circle me-1"></i>Valide
                        </span>
                    @elseif($ticket->status === 'used')
                        <span class="status-badge status-used">
                            <i class="fas fa-check-double me-1"></i>Utilisé
                        </span>
                    @else
                        <span class="status-badge status-{{ $ticket->status }}">
                            {{ ucfirst($ticket->status) }}
                        </span>
                    @endif
                </div>
                
                <!-- Header -->
                <div class="ticket-header">
                    <h1 class="ticket-brand">🎫 CLICBILLET</h1>
                    <h2 class="event-title">{{ $ticket->ticketType->event->title }}</h2>
                    <p class="ticket-type">{{ $ticket->ticketType->name }}</p>
                </div>
                
                <!-- Body -->
                <div class="ticket-body">
                    <!-- QR Code Section -->
                    <div class="qr-section">
                        <h5>Code QR - Présentez à l'entrée</h5>
                        
                        <div class="qr-code-container">
                            @if($ticket->qr_code_url)
                                <img src="{{ $ticket->qr_code_url }}" alt="QR Code" class="qr-image">
                            @else
                                <div class="qr-svg-container">
                                    {!! $ticket->getQrCodeSvg() !!}
                                </div>
                            @endif
                        </div>
                        
                        <p class="ticket-code">
                            <strong>Code :</strong> 
                            <code>{{ $ticket->ticket_code }}</code>
                        </p>
                    </div>
                    
                    <!-- Perforations -->
                    <div class="ticket-perforations"></div>
                    
                    <!-- Ticket Details -->
                    <div class="ticket-details">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="detail-group">
                                    <h6><i class="fas fa-user me-2"></i>Titulaire</h6>
                                    <p>{{ $ticket->holder_name ?: 'Non spécifié' }}</p>
                                    @if($ticket->holder_email)
                                        <small class="text-muted">{{ $ticket->holder_email }}</small>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="detail-group">
                                    <h6><i class="fas fa-calendar me-2"></i>Date</h6>
                                    <p>{{ $ticket->ticketType->event->event_date->format('d/m/Y') }}</p>
                                    @if($ticket->ticketType->event->event_time)
                                        <small class="text-muted">{{ $ticket->ticketType->event->event_time->format('H:i') }}</small>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="detail-group">
                                    <h6><i class="fas fa-map-marker-alt me-2"></i>Lieu</h6>
                                    <p>{{ $ticket->ticketType->event->venue }}</p>
                                    @if($ticket->ticketType->event->address)
                                        <small class="text-muted">{{ $ticket->ticketType->event->address }}</small>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="detail-group">
                                    <h6><i class="fas fa-ticket-alt me-2"></i>Type & Prix</h6>
                                    <p>{{ $ticket->ticketType->name }}</p>
                                    <small class="text-muted">{{ number_format($ticket->ticketType->price, 0, ',', ' ') }} FCFA</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="ticket-footer">
                    <div class="instructions">
                        <h6>📱 Instructions importantes</h6>
                        <ul>
                            <li>Présentez ce QR code à l'entrée (sur téléphone ou imprimé)</li>
                            <li>Arrivez 30 minutes avant le début de l'événement</li>
                            <li>Gardez une pièce d'identité avec vous</li>
                            <li>Ce billet est nominatif et non transférable</li>
                        </ul>
                    </div>
                    
                    @if($ticket->status === 'used')
                        <div class="usage-info">
                            <small class="text-muted">
                                <i class="fas fa-check me-1"></i>
                                Billet utilisé le {{ $ticket->used_at->format('d/m/Y à H:i') }}
                            </small>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Actions -->
            <div class="ticket-actions text-center mt-4">
                <a href="{{ route('acheteur.tickets') }}" class="btn btn-outline-primary me-2">
                    <i class="fas fa-arrow-left me-2"></i>Retour à mes billets
                </a>
                
                @if($ticket->status === 'sold')
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="fas fa-print me-2"></i>Imprimer
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
/* Ticket Styles */
.ticket-container {
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    overflow: hidden;
    position: relative;
    border: 2px dashed #e9ecef;
}

.ticket-status {
    position: absolute;
    top: 20px;
    right: 20px;
    z-index: 10;
}

.status-badge {
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
}

.status-valid {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
}

.status-used {
    background: linear-gradient(135deg, #6c757d, #495057);
    color: white;
}

.ticket-header {
    background: linear-gradient(135deg, #FF6B35, #ff8c61);
    color: white;
    padding: 2rem;
    text-align: center;
}

.ticket-brand {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    font-weight: 700;
}

.event-title {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.ticket-type {
    font-size: 1.1rem;
    opacity: 0.9;
    margin: 0;
}

.ticket-body {
    padding: 2rem;
}

.qr-section {
    text-align: center;
    margin-bottom: 2rem;
}

.qr-section h5 {
    color: #495057;
    margin-bottom: 1.5rem;
    font-weight: 600;
}

.qr-code-container {
    display: flex;
    justify-content: center;
    margin-bottom: 1rem;
}

.qr-image {
    max-width: 200px;
    height: auto;
}

.qr-svg-container {
    display: inline-block;
}

.ticket-code {
    margin-top: 1rem;
}

.ticket-code code {
    background: #f8f9fa;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 1.1rem;
    color: #FF6B35;
    font-weight: 600;
}

.ticket-perforations {
    height: 20px;
    background-image: repeating-linear-gradient(
        90deg,
        transparent,
        transparent 10px,
        #e9ecef 10px,
        #e9ecef 20px
    );
    margin: 1.5rem 0;
}

.detail-group {
    margin-bottom: 1.5rem;
}

.detail-group h6 {
    color: #495057;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.detail-group p {
    margin-bottom: 0.25rem;
    font-weight: 500;
}

.ticket-footer {
    background: #f8f9fa;
    padding: 1.5rem 2rem;
    border-top: 1px solid #e9ecef;
}

.instructions h6 {
    color: #495057;
    margin-bottom: 1rem;
}

.instructions ul {
    margin: 0;
    padding-left: 1.2rem;
}

.instructions li {
    margin-bottom: 0.5rem;
    color: #6c757d;
}

.usage-info {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e9ecef;
    text-align: center;
}

/* Actions */
.ticket-actions {
    margin-bottom: 2rem;
}

/* Print Styles */
@media print {
    .ticket-actions,
    nav,
    footer {
        display: none !important;
    }
    
    .ticket-container {
        box-shadow: none;
        border: 2px solid #000;
    }
    
    .ticket-header {
        background: #FF6B35 !important;
        -webkit-print-color-adjust: exact;
    }
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .ticket-container {
        margin: 1rem;
        border-radius: 15px;
    }
    
    .ticket-header {
        padding: 1.5rem;
    }
    
    .ticket-body {
        padding: 1.5rem;
    }
    
    .event-title {
        font-size: 1.5rem;
    }
    
    .ticket-brand {
        font-size: 1.3rem;
    }
}
</style>
@endpush
@endsection