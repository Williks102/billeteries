{{-- resources/views/admin/tickets/show.blade.php --}}
@extends('layouts.admin')

@section('title', 'Détail du billet #' . $ticket->ticket_code . ' - ClicBillet CI')

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.tickets') }}">Billets</a>
    </li>
    <li class="breadcrumb-item active">{{ $ticket->ticket_code }}</li>
@endsection

@section('content')
    <!-- Header avec actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Billet #{{ $ticket->ticket_code }}</h2>
            <p class="text-muted mb-0">Détails complets du billet et historique des actions</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.tickets.pdf', $ticket) }}" class="btn btn-outline-orange">
                <i class="fas fa-file-pdf me-2"></i>Télécharger PDF
            </a>
            @if($ticket->status === 'sold')
                <button type="button" class="btn btn-warning" onclick="markAsUsed()">
                    <i class="fas fa-check me-2"></i>Marquer utilisé
                </button>
            @endif
            @if($ticket->status === 'cancelled')
                <button type="button" class="btn btn-success" onclick="reactivateTicket()">
                    <i class="fas fa-undo me-2"></i>Réactiver
                </button>
            @elseif($ticket->status !== 'used')
                <button type="button" class="btn btn-danger" onclick="cancelTicket()">
                    <i class="fas fa-ban me-2"></i>Annuler
                </button>
            @endif
        </div>
    </div>

    <div class="row">
        <!-- Informations principales du billet -->
        <div class="col-lg-8">
            <!-- Statut et informations générales -->
            <div class="card mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-ticket-alt text-orange me-2"></i>
                        Informations du billet
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-group mb-3">
                                <label class="form-label fw-semibold">Code du billet</label>
                                <div class="d-flex align-items-center">
                                    <code class="ticket-code me-3">{{ $ticket->ticket_code }}</code>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard('{{ $ticket->ticket_code }}')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="info-group mb-3">
                                <label class="form-label fw-semibold">Statut</label>
                                <div>
                                    <span class="badge fs-6 ticket-status {{ 
                                        $ticket->status == 'available' ? 'bg-secondary' :
                                        ($ticket->status == 'sold' ? 'bg-success' :
                                        ($ticket->status == 'used' ? 'bg-primary' : 'bg-danger'))
                                    }}">
                                        @switch($ticket->status)
                                            @case('available')
                                                <i class="fas fa-circle me-1"></i>Disponible
                                                @break
                                            @case('sold')
                                                <i class="fas fa-check-circle me-1"></i>Vendu
                                                @break
                                            @case('used')
                                                <i class="fas fa-star me-1"></i>Utilisé
                                                @break
                                            @case('cancelled')
                                                <i class="fas fa-ban me-1"></i>Annulé
                                                @break
                                        @endswitch
                                    </span>
                                </div>
                            </div>

                            @if($ticket->seat_number)
                                <div class="info-group mb-3">
                                    <label class="form-label fw-semibold">Numéro de siège</label>
                                    <p class="mb-0 fw-bold text-primary">{{ $ticket->seat_number }}</p>
                                </div>
                            @endif
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-group mb-3">
                                <label class="form-label fw-semibold">Type de billet</label>
                                <p class="mb-0">{{ $ticket->ticketType->name ?? 'N/A' }}</p>
                            </div>
                            
                            <div class="info-group mb-3">
                                <label class="form-label fw-semibold">Prix</label>
                                <p class="mb-0 fw-bold text-success">{{ number_format($ticket->ticketType->price ?? 0) }} F CFA</p>
                            </div>
                            
                            <div class="info-group mb-3">
                                <label class="form-label fw-semibold">Date de création</label>
                                <p class="mb-0">{{ $ticket->created_at->format('d/m/Y à H:i') }}</p>
                            </div>
                        </div>
                    </div>

                    @if($ticket->used_at)
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Billet utilisé</strong> le {{ $ticket->used_at->format('d/m/Y à H:i') }}
                            @if($ticket->used_by_admin_id)
                                par {{ \App\Models\User::find($ticket->used_by_admin_id)->name ?? 'Admin' }}
                            @endif
                        </div>
                    @endif

                    @if($ticket->cancelled_at)
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Billet annulé</strong> le {{ $ticket->cancelled_at->format('d/m/Y à H:i') }}
                            @if($ticket->cancellation_reason)
                                <br><small>Raison : {{ $ticket->cancellation_reason }}</small>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Informations sur l'événement -->
            @if($ticket->ticketType && $ticket->ticketType->event)
                <div class="card mb-4">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-alt text-orange me-2"></i>
                            Événement associé
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            @if($ticket->ticketType->event->image)
                                <div class="col-md-3">
                                    <img src="{{ asset('storage/' . $ticket->ticketType->event->image) }}" 
                                         alt="{{ $ticket->ticketType->event->title }}"
                                         class="img-fluid rounded">
                                </div>
                            @endif
                            <div class="{{ $ticket->ticketType->event->image ? 'col-md-9' : 'col-12' }}">
                                <h4 class="fw-bold mb-2">
                                    <a href="{{ route('admin.events.show', $ticket->ticketType->event) }}" 
                                       class="text-decoration-none">
                                        {{ $ticket->ticketType->event->title }}
                                    </a>
                                </h4>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <p class="mb-1">
                                            <i class="fas fa-calendar me-2 text-muted"></i>
                                            <strong>Date :</strong> 
                                            {{ $ticket->ticketType->event->date?->format('d/m/Y à H:i') ?? 'Date non définie' }}
                                        </p>
                                        <p class="mb-1">
                                            <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                                            <strong>Lieu :</strong> {{ $ticket->ticketType->event->location ?? 'Lieu non défini' }}
                                        </p>
                                    </div>
                                    <div class="col-sm-6">
                                        <p class="mb-1">
                                            <i class="fas fa-user me-2 text-muted"></i>
                                            <strong>Promoteur :</strong> 
                                            {{ $ticket->ticketType->event->promoteur->name ?? 'N/A' }}
                                        </p>
                                        <p class="mb-1">
                                            <i class="fas fa-tag me-2 text-muted"></i>
                                            <strong>Catégorie :</strong> 
                                            {{ $ticket->ticketType->event->category->name ?? 'N/A' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Informations de commande -->
            @if($order)
                <div class="card mb-4">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0">
                            <i class="fas fa-shopping-cart text-orange me-2"></i>
                            Commande associée
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-group mb-3">
                                    <label class="form-label fw-semibold">Numéro de commande</label>
                                    <p class="mb-0">
                                        <a href="{{ route('admin.orders.show', $order) }}" class="text-decoration-none">
                                            #{{ $order->order_number }}
                                        </a>
                                    </p>
                                </div>
                                
                                <div class="info-group mb-3">
                                    <label class="form-label fw-semibold">Acheteur</label>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar-sm me-2">
                                            {{ substr($order->user->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="fw-semibold">{{ $order->user->name }}</div>
                                            <small class="text-muted">{{ $order->user->email }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="info-group mb-3">
                                    <label class="form-label fw-semibold">Date de commande</label>
                                    <p class="mb-0">{{ $order->created_at->format('d/m/Y à H:i') }}</p>
                                </div>
                                
                                <div class="info-group mb-3">
                                    <label class="form-label fw-semibold">Montant total</label>
                                    <p class="mb-0 fw-bold text-success">{{ number_format($order->total_amount) }} F CFA</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Historique des actions -->
            @if(isset($ticketHistory) && $ticketHistory->count() > 0)
                <div class="card">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0">
                            <i class="fas fa-history text-orange me-2"></i>
                            Historique des actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            @foreach($ticketHistory->sortByDesc('date') as $event)
                                <div class="timeline-item">
                                    <div class="timeline-marker timeline-marker-{{ 
                                        $event['action'] == 'created' ? 'info' :
                                        ($event['action'] == 'sold' ? 'success' :
                                        ($event['action'] == 'used' ? 'primary' : 'warning'))
                                    }}">
                                        <i class="fas fa-{{ 
                                            $event['action'] == 'created' ? 'plus' :
                                            ($event['action'] == 'sold' ? 'shopping-cart' :
                                            ($event['action'] == 'used' ? 'check' : 'ban'))
                                        }}"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6 class="timeline-title">{{ $event['description'] }}</h6>
                                        <p class="timeline-subtitle">
                                            {{ $event['date']->format('d/m/Y à H:i') }}
                                            @if(isset($event['admin']) && $event['admin'])
                                                - par {{ $event['admin']->name }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar droite -->
        <div class="col-lg-4">
            <!-- Code QR -->
            <div class="card mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-qrcode text-orange me-2"></i>
                        Code QR
                    </h5>
                </div>
                <div class="card-body text-center">
                    <div id="qrcode" class="mb-3"></div>
                    <small class="text-muted">
                        Scannez ce code QR pour vérifier le billet
                    </small>
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="card mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-tools text-orange me-2"></i>
                        Actions rapides
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('tickets.verify', $ticket->ticket_code) }}" 
                           class="btn btn-outline-primary" target="_blank">
                            <i class="fas fa-external-link-alt me-2"></i>
                            Voir page publique
                        </a>
                        
                        @if($order)
                            <a href="{{ route('admin.orders.show', $order) }}" 
                               class="btn btn-outline-info">
                                <i class="fas fa-shopping-cart me-2"></i>
                                Voir la commande
                            </a>
                        @endif
                        
                        @if($ticket->ticketType && $ticket->ticketType->event)
                            <a href="{{ route('admin.events.show', $ticket->ticketType->event) }}" 
                               class="btn btn-outline-warning">
                                <i class="fas fa-calendar-alt me-2"></i>
                                Voir l'événement
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Statistiques de l'événement -->
            @if(isset($eventStats))
                <div class="card">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-pie text-orange me-2"></i>
                            Stats de l'événement
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <h4 class="text-primary mb-1">{{ $eventStats['total_tickets'] ?? 0 }}</h4>
                                <small class="text-muted">Total billets</small>
                            </div>
                            <div class="col-6 mb-3">
                                <h4 class="text-success mb-1">{{ $eventStats['sold_tickets'] ?? 0 }}</h4>
                                <small class="text-muted">Vendus</small>
                            </div>
                            <div class="col-6">
                                <h4 class="text-warning mb-1">{{ $eventStats['used_tickets'] ?? 0 }}</h4>
                                <small class="text-muted">Utilisés</small>
                            </div>
                            <div class="col-6">
                                <h4 class="text-secondary mb-1">{{ $eventStats['available_tickets'] ?? 0 }}</h4>
                                <small class="text-muted">Disponibles</small>
                            </div>
                        </div>
                        
                        @if(isset($eventStats['total_tickets']) && $eventStats['total_tickets'] > 0)
                            <div class="progress mt-3" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: {{ ($eventStats['sold_tickets'] / $eventStats['total_tickets']) * 100 }}%"></div>
                                <div class="progress-bar bg-warning" style="width: {{ ($eventStats['used_tickets'] / $eventStats['total_tickets']) * 100 }}%"></div>
                            </div>
                            <small class="text-muted">
                                {{ number_format(($eventStats['sold_tickets'] / $eventStats['total_tickets']) * 100, 1) }}% vendus
                            </small>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Billets similaires -->
            @if(isset($relatedTickets) && $relatedTickets->count() > 0)
                <div class="card mt-4">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0">
                            <i class="fas fa-ticket-alt text-orange me-2"></i>
                            Autres billets de l'événement
                        </h5>
                    </div>
                    <div class="card-body">
                        @foreach($relatedTickets as $relatedTicket)
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <div>
                                    <small class="fw-semibold">{{ $relatedTicket->ticket_code }}</small>
                                    <br>
                                    <small class="text-muted">{{ $relatedTicket->ticketType->name ?? 'N/A' }}</small>
                                </div>
                                <div>
                                    <span class="badge badge-sm {{ 
                                        $relatedTicket->status == 'available' ? 'bg-secondary' :
                                        ($relatedTicket->status == 'sold' ? 'bg-success' :
                                        ($relatedTicket->status == 'used' ? 'bg-primary' : 'bg-danger'))
                                    }}">
                                        {{ ucfirst($relatedTicket->status) }}
                                    </span>
                                    <a href="{{ route('admin.tickets.show', $relatedTicket) }}" 
                                       class="btn btn-sm btn-outline-primary ms-2">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('styles')
<style>
    .ticket-code {
        background: #f8f9fa;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 1.1rem;
        color: #FF6B35;
        font-weight: 600;
        border: 1px solid #e9ecef;
    }
    
    .info-group label {
        color: #495057;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    
    .ticket-status {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
        font-weight: 600;
    }
    
    .user-avatar-sm {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg, #FF6B35, #fd7e14);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 0.8rem;
        flex-shrink: 0;
    }
    
    .timeline {
        position: relative;
        padding-left: 30px;
    }
    
    .timeline:before {
        content: '';
        position: absolute;
        left: 15px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e9ecef;
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 20px;
    }
    
    .timeline-marker {
        position: absolute;
        left: -23px;
        top: 0;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        color: white;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .timeline-marker-info { background: #17a2b8; }
    .timeline-marker-success { background: #28a745; }
    .timeline-marker-primary { background: #007bff; }
    .timeline-marker-warning { background: #ffc107; color: #212529 !important; }
    
    .timeline-content {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        border-left: 3px solid #FF6B35;
    }
    
    .timeline-title {
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 5px;
        color: #2d3748;
    }
    
    .timeline-subtitle {
        font-size: 0.8rem;
        color: #6c757d;
        margin-bottom: 0;
    }
    
    .card {
        border: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        border-radius: 12px;
    }
    
    .card-header {
        border-radius: 12px 12px 0 0 !important;
        padding: 1rem 1.25rem;
    }
    
    .progress {
        border-radius: 10px;
        overflow: hidden;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    
    .badge-sm {
        font-size: 0.7rem;
        padding: 0.25em 0.5em;
    }
    
    #qrcode {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 150px;
    }
    
    /* Mobile responsive */
    @media (max-width: 768px) {
        .timeline {
            padding-left: 20px;
        }
        
        .timeline-marker {
            left: -18px;
            width: 12px;
            height: 12px;
            font-size: 0.6rem;
        }
        
        .timeline:before {
            left: 10px;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Générer le QR Code
        const ticketCode = '{{ $ticket->ticket_code }}';
        const verifyUrl = '{{ route("tickets.verify", $ticket->ticket_code) }}';
        
        QRCode.toCanvas(document.getElementById('qrcode'), verifyUrl, {
            width: 150,
            height: 150,
            colorDark: '#000000',
            colorLight: '#ffffff',
            margin: 2
        }, function (error) {
            if (error) {
                console.error('Erreur génération QR Code:', error);
                document.getElementById('qrcode').innerHTML = '<p class="text-muted">Erreur lors de la génération du QR Code</p>';
            }
        });
    });
    
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            // Afficher une notification de succès
            const button = event.target.closest('button');
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check text-success"></i>';
            
            setTimeout(() => {
                button.innerHTML = originalHTML;
            }, 2000);
        });
    }
    
    function markAsUsed() {
        if (confirm('Êtes-vous sûr de vouloir marquer ce billet comme utilisé ?')) {
            fetch('{{ route("admin.tickets.markUsed", $ticket) }}', {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur s\'est produite');
            });
        }
    }
    
    function cancelTicket() {
        const reason = prompt('Raison de l\'annulation (optionnel):');
        if (reason !== null) { // L'utilisateur n'a pas annulé
            fetch('{{ route("admin.tickets.cancel", $ticket) }}', {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ reason: reason })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur s\'est produite');
            });
        }
    }
    
    function reactivateTicket() {
        if (confirm('Êtes-vous sûr de vouloir réactiver ce billet ?')) {
            fetch('{{ route("admin.tickets.reactivate", $ticket) }}', {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur s\'est produite');
            });
        }
    }
</script>
@endpush