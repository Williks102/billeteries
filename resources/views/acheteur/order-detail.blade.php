@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Détail de la commande #{{ $order->order_number }}</h4>
                </div>
                <div class="card-body">
                    <!-- Informations de la commande -->
                    <div class="order-info mb-4">
                        <h5>{{ $order->event->title }}</h5>
                        <p>Date: {{ $order->event->formatted_event_date }}</p>
                        <p>Lieu: {{ $order->event->venue }}</p>
                    </div>

                    <!-- Liste des billets -->
                    <div class="tickets-section">
                        <h5>Vos billets</h5>
                        <div class="row" id="ticketsContainer">
                            @foreach($order->tickets as $ticket)
                            <div class="col-md-6 mb-4">
                                <div class="ticket-card">
                                    <div class="ticket-header">
                                        <h6>{{ $ticket->ticketType->name ?? 'Billet Standard' }}</h6>
                                        <span class="badge bg-success">{{ ucfirst($ticket->status) }}</span>
                                    </div>
                                    <div class="ticket-body">
                                        <div class="qr-code-container text-center mb-3" data-ticket-id="{{ $ticket->id }}">
                                            <div class="qr-loading">
                                                <div class="spinner-border" role="status">
                                                    <span class="visually-hidden">Chargement...</span>
                                                </div>
                                            </div>
                                            <div class="qr-code-wrapper" style="display: none;">
                                                <img class="qr-code-img" src="" alt="QR Code" style="width: 200px; height: 200px; cursor: pointer;">
                                            </div>
                                            <div class="qr-error" style="display: none;">
                                                <div class="alert alert-warning">
                                                    <i class="fas fa-exclamation-triangle"></i> QR Code non disponible
                                                    <div class="mt-2">Code: <strong>{{ $ticket->ticket_code }}</strong></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="ticket-info">
                                            <p><strong>Code:</strong> {{ $ticket->ticket_code }}</p>
                                            @if($ticket->seat_number)
                                            <p><strong>Siège:</strong> {{ $ticket->seat_number }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="actions mt-4">
                        @if($order->canDownloadTickets())
                        <a href="{{ route('acheteur.order.download', $order) }}" class="btn btn-success">
                            <i class="fas fa-download"></i> Télécharger PDF
                        </a>
                        @endif
                        <a href="{{ route('acheteur.tickets') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal QR Code Fullscreen -->
<div class="modal fade" id="qrFullscreenModal" tabindex="-1">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content bg-dark">
            <div class="modal-header border-0">
                <h5 class="modal-title text-white">QR Code</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body d-flex align-items-center justify-content-center">
                <img id="fullscreenQrImage" src="" alt="QR Code" style="max-width: 80vmin; max-height: 80vmin;">
            </div>
        </div>
    </div>
</div>

<style>
.ticket-card {
    border: 1px solid #dee2e6;
    border-radius: 10px;
    padding: 20px;
    transition: all 0.3s ease;
}

.ticket-card:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.qr-code-container {
    min-height: 220px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.qr-code-img {
    border: 2px solid #dee2e6;
    border-radius: 10px;
    padding: 10px;
    background: white;
}

.qr-code-img:hover {
    box-shadow: 0 0 20px rgba(255, 107, 53, 0.3);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Charger les QR codes via AJAX
    loadQRCodes();
    
    // Gestionnaire de clic sur les QR codes
    document.querySelectorAll('.qr-code-img').forEach(img => {
        img.addEventListener('click', function() {
            showFullscreenQR(this.src);
        });
    });
});

function loadQRCodes() {
    const orderId = {{ $order->id }};
    
    fetch(`/acheteur/orders/${orderId}/qr-codes`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.tickets) {
                data.tickets.forEach(ticket => {
                    const container = document.querySelector(`[data-ticket-id="${ticket.id}"]`);
                    if (container && ticket.qr_code) {
                        // Masquer le loader
                        container.querySelector('.qr-loading').style.display = 'none';
                        
                        // Afficher le QR code
                        const wrapper = container.querySelector('.qr-code-wrapper');
                        const img = wrapper.querySelector('.qr-code-img');
                        img.src = ticket.qr_code;
                        wrapper.style.display = 'block';
                        
                        // Ajouter le gestionnaire de clic
                        img.addEventListener('click', function() {
                            showFullscreenQR(ticket.qr_code);
                        });
                    } else if (container) {
                        // Afficher l'erreur
                        container.querySelector('.qr-loading').style.display = 'none';
                        container.querySelector('.qr-error').style.display = 'block';
                    }
                });
            }
        })
        .catch(error => {
            console.error('Erreur chargement QR codes:', error);
            // Afficher les erreurs pour tous les billets
            document.querySelectorAll('.qr-code-container').forEach(container => {
                container.querySelector('.qr-loading').style.display = 'none';
                container.querySelector('.qr-error').style.display = 'block';
            });
        });
}

function showFullscreenQR(qrSrc) {
    document.getElementById('fullscreenQrImage').src = qrSrc;
    const modal = new bootstrap.Modal(document.getElementById('qrFullscreenModal'));
    modal.show();
}
</script>
@endsection