{{-- resources/views/acheteur/tickets.blade.php --}}
{{-- VERSION ADAPTÉE : Votre vue existante avec le nouveau layout --}}
@extends('layouts.acheteur')

@section('title', 'Mes billets - ClicBillet CI')

@section('breadcrumb')
    <li class="breadcrumb-item active">Mes billets</li>
@endsection

@section('content')
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">
                <i class="fas fa-ticket-alt text-primary me-2"></i>
                Mes billets
            </h2>
            <p class="text-muted mb-0">Gérez et scannez vos billets facilement</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-acheteur" onclick="refreshQRCodes()">
                <i class="fas fa-sync me-2"></i>Actualiser QR
            </button>
            <a href="{{ route('home') }}" class="btn btn-acheteur">
                <i class="fas fa-plus me-2"></i>Nouveaux événements
            </a>
        </div>
    </div>

    <!-- Filtres -->
    <div class="content-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('acheteur.tickets') }}" class="row g-3 align-items-center">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Statut</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">Tous les billets</option>
                        <option value="upcoming" {{ request('status') == 'upcoming' ? 'selected' : '' }}>À venir</option>
                        <option value="past" {{ request('status') == 'past' ? 'selected' : '' }}>Passés</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Recherche</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Nom de l'événement..." 
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-acheteur">
                        <i class="fas fa-search me-2"></i>Filtrer
                    </button>
                    @if(request()->hasAny(['status', 'search']))
                        <a href="{{ route('acheteur.tickets') }}" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-times me-1"></i>Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des billets -->
    @if($orders && $orders->count() > 0)
        @foreach($orders as $order)
            <div class="order-card mb-4">
                <!-- Header de la commande -->
                <div class="order-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">{{ $order->event->title ?? 'Événement supprimé' }}</h5>
                            <div class="order-meta">
                                <span class="me-3">
                                    <i class="fas fa-calendar me-1"></i>
                                    {{ $order->event && $order->event->event_date ? $order->event->event_date->format('d/m/Y') : 'Date TBD' }}
                                </span>
                                <span class="me-3">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    {{ $order->event->venue ?? 'Lieu TBD' }}
                                </span>
                                <span>
                                    <i class="fas fa-receipt me-1"></i>
                                    Commande #{{ $order->order_number ?? $order->id }}
                                </span>
                            </div>
                        </div>
                        <div class="text-end">
                            @php
                                $isUpcoming = $order->event && $order->event->event_date >= now()->toDateString();
                            @endphp
                            <span class="badge {{ $isUpcoming ? 'bg-success' : 'bg-secondary' }} fs-6">
                                {{ $isUpcoming ? 'À venir' : 'Passé' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Grid des billets -->
                <div class="tickets-grid">
                    @forelse($order->tickets as $ticket)
                        <div class="ticket-card">
                            <!-- QR Code Section -->
                            <div class="qr-section">
                                <div class="qr-container mx-auto">
                                    @php
                                        try {
                                            $qrService = app(\App\Services\QRCodeService::class);
                                            $qrBase64 = $qrService->getOrGenerateTicketQR($ticket, 'base64');
                                        } catch (\Exception $e) {
                                            $qrBase64 = null;
                                        }
                                    @endphp
                                    
                                    @if($qrBase64)
                                        <img src="{{ $qrBase64 }}" 
                                             alt="QR Code" 
                                             class="qr-code"
                                             style="width: 120px; height: 120px; cursor: pointer;"
                                             onclick="showLargeQR('{{ $qrBase64 }}', '{{ $ticket->ticket_code }}')">
                                    @else
                                        <div class="qr-placeholder" 
                                             style="width: 120px; height: 120px; background: #f8f9fa; border: 2px dashed #dee2e6; display: flex; align-items: center; justify-content: center; border-radius: 8px;">
                                            <div class="text-center text-muted">
                                                <i class="fas fa-qrcode fa-2x mb-2"></i>
                                                <div class="small">QR non disponible</div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Informations du billet -->
                            <div class="ticket-info">
                                <h6 class="fw-bold mb-2">{{ $ticket->ticketType->name ?? 'Billet Standard' }}</h6>
                                
                                <div class="info-row">
                                    <span class="info-label">🎟️ Code :</span>
                                    <span class="info-value fw-semibold">{{ $ticket->ticket_code }}</span>
                                </div>
                                
                                @if($ticket->seat_number)
                                <div class="info-row">
                                    <span class="info-label">💺 Siège :</span>
                                    <span class="info-value">{{ $ticket->seat_number }}</span>
                                </div>
                                @endif
                                
                                <div class="info-row">
                                    <span class="info-label">📅 Status :</span>
                                    <span class="status-badge status-{{ $ticket->status }}">
                                        @switch($ticket->status)
                                            @case('active')
                                                <i class="fas fa-check me-1"></i>Actif
                                                @break
                                            @case('used')
                                                <i class="fas fa-check-double me-1"></i>Utilisé
                                                @break
                                            @case('cancelled')
                                                <i class="fas fa-times me-1"></i>Annulé
                                                @break
                                            @default
                                                {{ ucfirst($ticket->status) }}
                                        @endswitch
                                    </span>
                                </div>
                                
                                @if($ticket->ticketType && $ticket->ticketType->price)
                                <div class="info-row">
                                    <span class="info-label">💰 Prix :</span>
                                    <span class="info-value fw-bold text-success">{{ number_format($ticket->ticketType->price) }} FCFA</span>
                                </div>
                                @endif
                            </div>

                            <!-- Actions du billet -->
                            <div class="ticket-actions">
                                @if($qrBase64)
                                <button type="button" class="btn btn-qr btn-sm" 
                                        onclick="showLargeQR('{{ $qrBase64 }}', '{{ $ticket->ticket_code }}')">
                                    <i class="fas fa-expand me-1"></i>Agrandir QR
                                </button>
                                @endif
                                
                                <a href="{{ route('acheteur.ticket.show', $ticket) }}" 
                                   class="btn btn-verify btn-sm">
                                    <i class="fas fa-eye me-1"></i>Détails
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="text-center py-4">
                                <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Aucun billet trouvé pour cette commande</p>
                            </div>
                        </div>
                    @endforelse
                </div>

                <!-- Actions de la commande -->
                <div class="order-actions">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                Acheté le {{ $order->created_at->format('d/m/Y à H:i') }}
                            </small>
                        </div>
                        <div class="col-md-6 text-end">
                            @if($order->canDownloadTickets())
                                <a href="{{ route('acheteur.order.download', $order) }}" 
                                   class="btn btn-download btn-sm me-2">
                                    <i class="fas fa-file-pdf me-1"></i>PDF
                                </a>
                            @endif
                            
                            <a href="{{ route('acheteur.order.detail', $order) }}" 
                               class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-info-circle me-1"></i>Détails
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-4">
            {{ $orders->appends(request()->query())->links() }}
        </div>
    @else
        <!-- État vide -->
        <div class="content-card">
            <div class="card-body text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-ticket-alt fa-5x text-muted"></i>
                </div>
                <h4 class="text-muted">Aucun billet trouvé</h4>
                <p class="text-muted mb-4">
                    @if(request()->hasAny(['status', 'search']))
                        Aucun billet ne correspond à vos critères de recherche.
                    @else
                        Vous n'avez pas encore acheté de billets pour des événements.
                    @endif
                </p>
                <div class="d-flex justify-content-center gap-3">
                    @if(request()->hasAny(['status', 'search']))
                        <a href="{{ route('acheteur.tickets') }}" class="btn btn-outline-acheteur">
                            <i class="fas fa-times me-2"></i>Effacer les filtres
                        </a>
                    @endif
                    <a href="{{ route('home') }}" class="btn btn-acheteur">
                        <i class="fas fa-search me-2"></i>Découvrir des événements
                    </a>
                </div>
            </div>
        </div>
    @endif
@endsection

<!-- Modal QR Code -->
<div class="modal fade qr-modal" id="qrModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-qrcode me-2"></i>
                    QR Code - <span id="modalTicketCode"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalQRImage" src="" alt="QR Code" class="large-qr mb-3">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Présentez ce code QR à l'entrée de l'événement
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    :root {
        --primary-orange: #FF6B35;
        --secondary-orange: #ff8c61;
        --success: #28a745;
        --info: #17a2b8;
        --warning: #ffc107;
        --danger: #dc3545;
    }
    
    /* Order Cards */
    .order-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
    }
    
    .order-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.12);
    }
    
    .order-header {
        background: linear-gradient(135deg, var(--primary-orange), var(--secondary-orange));
        color: white;
        padding: 20px 25px;
        position: relative;
        overflow: hidden;
    }
    
    .order-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 200%;
        background: rgba(255,255,255,0.1);
        transform: rotate(45deg);
    }
    
    .order-header h5 {
        margin: 0;
        font-weight: 600;
        position: relative;
        z-index: 2;
    }
    
    .order-meta {
        position: relative;
        z-index: 2;
        opacity: 0.9;
        margin-top: 5px;
    }
    
    /* Tickets Grid */
    .tickets-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        padding: 25px;
    }
    
    .ticket-card {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 20px;
        transition: all 0.3s ease;
        border: 2px solid transparent;
        position: relative;
        overflow: hidden;
    }
    
    .ticket-card:hover {
        border-color: var(--primary-orange);
        background: white;
        transform: scale(1.02);
    }
    
    .ticket-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-orange), var(--secondary-orange));
    }
    
    /* QR Code optimisé */
    .qr-section {
        text-align: center;
        margin-bottom: 20px;
    }
    
    .qr-container {
        position: relative;
        width: 120px;
        height: 120px;
        margin: 0 auto;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .qr-code {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    
    .qr-code:hover {
        transform: scale(1.05);
    }
    
    .qr-placeholder {
        border: 2px dashed #dee2e6 !important;
        background: #f8f9fa !important;
    }
    
    /* Ticket Info */
    .ticket-info {
        margin-bottom: 20px;
    }
    
    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
        font-size: 0.9rem;
    }
    
    .info-label {
        color: #6c757d;
        font-weight: 500;
    }
    
    .info-value {
        color: #495057;
    }
    
    /* Status Badges */
    .status-badge {
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .status-active {
        background: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }
    
    .status-used {
        background: rgba(23, 162, 184, 0.1);
        color: #17a2b8;
    }
    
    .status-cancelled {
        background: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }
    
    /* Ticket Actions */
    .ticket-actions {
        display: flex;
        gap: 8px;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .btn-sm {
        border-radius: 25px;
        padding: 8px 16px;
        font-weight: 500;
        font-size: 13px;
        border: none;
        transition: all 0.3s ease;
    }
    
    .btn-qr {
        background: var(--primary-orange);
        color: white;
    }
    
    .btn-qr:hover {
        background: var(--secondary-orange);
        color: white;
        transform: translateY(-2px);
    }
    
    .btn-download {
        background: #28a745;
        color: white;
    }
    
    .btn-download:hover {
        background: #218838;
        color: white;
        transform: translateY(-2px);
    }
    
    .btn-verify {
        background: #17a2b8;
        color: white;
    }
    
    .btn-verify:hover {
        background: #138496;
        color: white;
        transform: translateY(-2px);
    }
    
    /* Order Actions */
    .order-actions {
        padding: 15px 25px;
        background: #f8f9fa;
        border-top: 1px solid #e9ecef;
    }
    
    /* Modal QR */
    .qr-modal .modal-content {
        border-radius: 20px;
        border: none;
    }
    
    .qr-modal .modal-header {
        background: linear-gradient(135deg, var(--primary-orange), var(--secondary-orange));
        color: white;
        border-radius: 20px 20px 0 0;
    }
    
    .large-qr {
        max-width: 300px;
        width: 100%;
        height: auto;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .tickets-grid {
            grid-template-columns: 1fr;
            padding: 15px;
        }
        
        .ticket-actions {
            justify-content: center;
        }
        
        .order-header {
            padding: 15px 20px;
        }
        
        .order-meta span {
            display: block;
            margin-bottom: 5px;
        }
    }
    
    /* Animations */
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .order-card {
        animation: slideIn 0.6s ease-out;
    }
</style>
@endpush

@push('scripts')
<script>
    function showLargeQR(qrBase64, ticketCode) {
        document.getElementById('modalQRImage').src = qrBase64;
        document.getElementById('modalTicketCode').textContent = ticketCode;
        
        const modal = new bootstrap.Modal(document.getElementById('qrModal'));
        modal.show();
    }
    
    function refreshQRCodes() {
        window.location.reload();
    }
</script>
@endpush