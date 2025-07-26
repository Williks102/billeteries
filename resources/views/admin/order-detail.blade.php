@extends('layouts.app')

@section('title', 'Détail Commande #{{ $order->order_number ?? $order->id }} - Admin')
@section('body-class', 'admin-page')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar Admin -->
        <div class="col-md-3 col-lg-2">
            <div class="admin-sidebar bg-dark text-white rounded p-3 sticky-top">
                <h5 class="mb-4">
                    <i class="fas fa-shield-alt text-orange me-2"></i>
                    Administration
                </h5>
                
                <nav class="nav flex-column">
                    <a class="nav-link text-light" href="{{ route('admin.dashboard') }}">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a class="nav-link text-light" href="{{ route('admin.users') }}">
                        <i class="fas fa-users me-2"></i>Utilisateurs
                    </a>
                    <a class="nav-link text-light" href="{{ route('admin.events') }}">
                        <i class="fas fa-calendar me-2"></i>Événements
                    </a>
                    <a class="nav-link active text-white" href="{{ route('admin.orders') }}">
                        <i class="fas fa-shopping-cart me-2"></i>Commandes
                    </a>
                    <a class="nav-link text-light" href="{{ route('admin.commissions') }}">
                        <i class="fas fa-coins me-2"></i>Commissions
                    </a>
                    <a class="nav-link text-light" href="{{ route('admin.reports') }}">
                        <i class="fas fa-chart-line me-2"></i>Rapports
                    </a>
                    
                    <hr class="my-3" style="border-color: #444;">
                    
                    <a class="nav-link text-light" href="{{ route('home') }}">
                        <i class="fas fa-eye me-2"></i>Voir le site
                    </a>
                </nav>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="col-md-9 col-lg-10">
            <!-- Breadcrumb et actions -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('admin.orders') }}" class="text-decoration-none">Commandes</a>
                            </li>
                            <li class="breadcrumb-item active">Commande #{{ $order->order_number ?? $order->id }}</li>
                        </ol>
                    </nav>
                    <h2 class="mb-1">Détail de la commande</h2>
                    <p class="text-muted mb-0">Informations complètes sur la commande #{{ $order->order_number ?? $order->id }}</p>
                </div>
                <div class="d-flex gap-2">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog me-2"></i>Actions
                        </button>
                        <ul class="dropdown-menu">
                            @if($order->payment_status == 'pending')
                                <li>
                                    <button class="dropdown-item" onclick="updateOrderStatus({{ $order->id }}, 'paid')">
                                        <i class="fas fa-check text-success me-2"></i>Marquer comme payé
                                    </button>
                                </li>
                                <li>
                                    <button class="dropdown-item" onclick="updateOrderStatus({{ $order->id }}, 'failed')">
                                        <i class="fas fa-times text-danger me-2"></i>Marquer comme échoué
                                    </button>
                                </li>
                            @endif
                            @if($order->payment_status == 'paid')
                                <li>
                                    <button class="dropdown-item" onclick="updateOrderStatus({{ $order->id }}, 'refunded')">
                                        <i class="fas fa-undo text-warning me-2"></i>Rembourser
                                    </button>
                                </li>
                            @endif
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="mailto:{{ $order->user->email ?? $order->billing_email }}">
                                    <i class="fas fa-envelope me-2"></i>Contacter client
                                </a>
                            </li>
                            <li>
                                <button class="dropdown-item" onclick="downloadOrderPDF({{ $order->id }})">
                                    <i class="fas fa-download me-2"></i>Télécharger PDF
                                </button>
                            </li>
                        </ul>
                    </div>
                    <a href="{{ route('admin.orders') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </a>
                </div>
            </div>

            <!-- Informations générales -->
            <div class="row mb-4">
                <!-- Statut de la commande -->
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon 
                                @switch($order->payment_status)
                                    @case('paid') bg-success @break
                                    @case('pending') bg-warning @break
                                    @case('failed') bg-danger @break
                                    @case('refunded') bg-info @break
                                    @default bg-secondary
                                @endswitch">
                                <i class="fas fa-
                                @switch($order->payment_status)
                                    @case('paid') check-circle @break
                                    @case('pending') clock @break
                                    @case('failed') times-circle @break
                                    @case('refunded') undo @break
                                    @default question-circle
                                @endswitch"></i>
                            </div>
                            <div class="stat-info ms-3">
                                <h4 class="mb-0">{{ ucfirst($order->payment_status) }}</h4>
                                <p class="text-muted mb-0">Statut paiement</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Montant total -->
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-primary">
                                <i class="fas fa-euro-sign"></i>
                            </div>
                            <div class="stat-info ms-3">
                                <h4 class="mb-0">{{ number_format($order->total_amount, 0, ',', ' ') }}</h4>
                                <p class="text-muted mb-0">FCFA</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Nombre de billets -->
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-success">
                                <i class="fas fa-ticket-alt"></i>
                            </div>
                            <div class="stat-info ms-3">
                                <h4 class="mb-0">{{ $order->tickets->count() }}</h4>
                                <p class="text-muted mb-0">Billets</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Date de commande -->
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-info">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="stat-info ms-3">
                                <h4 class="mb-0">{{ $order->created_at->format('d/m/Y') }}</h4>
                                <p class="text-muted mb-0">{{ $order->created_at->format('H:i') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Informations client -->
                <div class="col-lg-6 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-user text-orange me-2"></i>
                                Informations client
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="client-info">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="client-avatar">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="mb-0">{{ $order->user->name ?? 'Client supprimé' }}</h6>
                                        <small class="text-muted">Client</small>
                                    </div>
                                </div>
                                
                                <div class="info-row">
                                    <span class="info-label">Email :</span>
                                    <span class="info-value">{{ $order->billing_email ?? $order->user->email ?? 'N/A' }}</span>
                                </div>
                                
                                <div class="info-row">
                                    <span class="info-label">Téléphone :</span>
                                    <span class="info-value">{{ $order->billing_phone ?? 'Non renseigné' }}</span>
                                </div>
                                
                                <div class="info-row">
                                    <span class="info-label">Méthode de paiement :</span>
                                    <span class="info-value">{{ $order->payment_method ?? 'N/A' }}</span>
                                </div>
                                
                                @if($order->user)
                                <div class="info-row">
                                    <span class="info-label">Membre depuis :</span>
                                    <span class="info-value">{{ $order->user->created_at->format('d/m/Y') }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informations événement -->
                <div class="col-lg-6 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-calendar text-orange me-2"></i>
                                Événement
                            </h5>
                        </div>
                        <div class="card-body">
                            @if($order->event)
                                <div class="event-info">
                                    <div class="d-flex align-items-start mb-3">
                                        @if($order->event->image)
                                            <img src="{{ asset('storage/' . $order->event->image) }}" 
                                                 class="event-image me-3" alt="{{ $order->event->title }}">
                                        @else
                                            <div class="event-image-placeholder me-3">
                                                <i class="fas fa-calendar-alt"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <h6 class="mb-1">{{ $order->event->title }}</h6>
                                            <small class="text-muted">{{ $order->event->category->name ?? '' }}</small>
                                        </div>
                                    </div>
                                    
                                    <div class="info-row">
                                        <span class="info-label">Promoteur :</span>
                                        <span class="info-value">{{ $order->event->promoteur->name ?? 'N/A' }}</span>
                                    </div>
                                    
                                    <div class="info-row">
                                        <span class="info-label">Date :</span>
                                        <span class="info-value">
                                            {{ $order->event->event_date ? $order->event->event_date->format('d/m/Y') : 'N/A' }}
                                            @if($order->event->event_time)
                                                à {{ $order->event->event_time->format('H:i') }}
                                            @endif
                                        </span>
                                    </div>
                                    
                                    <div class="info-row">
                                        <span class="info-label">Lieu :</span>
                                        <span class="info-value">{{ $order->event->venue ?? 'Non renseigné' }}</span>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <a href="{{ route('events.show', $order->event) }}" 
                                           class="btn btn-outline-primary btn-sm" target="_blank">
                                            <i class="fas fa-eye me-1"></i>Voir l'événement
                                        </a>
                                    </div>
                                </div>
                            @else
                                <div class="text-center py-3">
                                    <i class="fas fa-exclamation-triangle text-warning fa-2x mb-2"></i>
                                    <p class="text-muted mb-0">Événement supprimé</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Détail des billets -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-ticket-alt text-orange me-2"></i>
                            Billets achetés ({{ $order->tickets->count() }})
                        </h5>
                        @if($order->canDownloadTickets())
                            <button class="btn btn-primary btn-sm" onclick="downloadAllTickets({{ $order->id }})">
                                <i class="fas fa-download me-1"></i>Télécharger tous
                            </button>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($order->tickets->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Code billet</th>
                                        <th>Type</th>
                                        <th>Prix unitaire</th>
                                        <th>Statut</th>
                                        <th>QR Code</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->tickets as $ticket)
                                        <tr>
                                            <td>
                                                <code class="ticket-code">{{ $ticket->ticket_code }}</code>
                                            </td>
                                            <td>
                                                <span class="fw-medium">{{ $ticket->ticketType->name ?? 'N/A' }}</span>
                                            </td>
                                            <td>
                                                <span class="text-orange fw-bold">
                                                    {{ number_format($ticket->ticketType->price ?? 0, 0, ',', ' ') }} FCFA
                                                </span>
                                            </td>
                                            <td>
                                                @switch($ticket->status)
                                                    @case('active')
                                                        <span class="badge bg-success">Actif</span>
                                                        @break
                                                    @case('used')
                                                        <span class="badge bg-info">Utilisé</span>
                                                        @break
                                                    @case('cancelled')
                                                        <span class="badge bg-danger">Annulé</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary">{{ ucfirst($ticket->status) }}</span>
                                                @endswitch
                                            </td>
                                            <td>
                                                @if($ticket->qr_code)
                                                    <img src="{{ asset('storage/' . $ticket->qr_code) }}" 
                                                         class="qr-code-mini" alt="QR Code" 
                                                         onclick="showQRCode('{{ asset('storage/' . $ticket->qr_code) }}')">
                                                @else
                                                    <span class="text-muted">Non généré</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" 
                                                            onclick="viewTicketDetail({{ $ticket->id }})" 
                                                            title="Voir détail">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    @if($ticket->status == 'active')
                                                        <button class="btn btn-outline-warning" 
                                                                onclick="markTicketUsed({{ $ticket->id }})" 
                                                                title="Marquer comme utilisé">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="2"><strong>Total</strong></td>
                                        <td><strong class="text-orange">{{ number_format($order->total_amount, 0, ',', ' ') }} FCFA</strong></td>
                                        <td colspan="3"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Aucun billet trouvé</h5>
                            <p class="text-muted">Cette commande ne contient aucun billet</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal QR Code -->
<div class="modal fade" id="qrCodeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">QR Code du billet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="qrCodeImage" src="" class="img-fluid" style="max-width: 300px;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
/* Variables CSS */
:root {
    --primary-orange: #FF6B35;
    --secondary-orange: #ff8c61;
    --dark-blue: #1a237e;
    --light-gray: #f8f9fa;
}

.admin-page {
    background-color: #f8f9fa;
}

/* Sidebar admin */
.admin-sidebar {
    background: linear-gradient(135deg, #2c3e50, #34495e) !important;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.admin-sidebar .nav-link {
    color: rgba(255,255,255,0.8) !important;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 5px;
    transition: all 0.3s ease;
}

.admin-sidebar .nav-link:hover,
.admin-sidebar .nav-link.active {
    background: var(--primary-orange) !important;
    color: white !important;
}

.text-orange {
    color: var(--primary-orange) !important;
}

/* Breadcrumb */
.breadcrumb-item a {
    color: var(--primary-orange);
    text-decoration: none;
}

.breadcrumb-item a:hover {
    color: var(--dark-blue);
}

/* Cards et statistiques */
.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    border: none;
    height: 100%;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: white;
}

.stat-info h4 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dark-blue);
}

/* Client et événement info */
.client-avatar {
    width: 50px;
    height: 50px;
    background: rgba(255, 107, 53, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-orange);
}

.event-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
}

.event-image-placeholder {
    width: 60px;
    height: 60px;
    background: #f8f9fa;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f8f9fa;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 600;
    color: #6c757d;
    min-width: 120px;
}

.info-value {
    color: #333;
    text-align: right;
}

/* Billets */
.ticket-code {
    background: rgba(255, 107, 53, 0.1);
    color: var(--primary-orange);
    padding: 4px 8px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-weight: bold;
}

.qr-code-mini {
    width: 40px;
    height: 40px;
    cursor: pointer;
    border-radius: 4px;
    transition: transform 0.2s ease;
}

.qr-code-mini:hover {
    transform: scale(1.1);
}

/* Cards génériques */
.card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.card-header {
    border-radius: 12px 12px 0 0 !important;
    border-bottom: 1px solid #e9ecef;
}

/* Tableaux */
.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
    font-size: 0.9rem;
}

.table td {
    vertical-align: middle;
    font-size: 0.9rem;
}

/* Badges */
.badge {
    font-size: 0.75rem;
    padding: 0.4em 0.8em;
}

/* Responsive */
@media (max-width: 768px) {
    .admin-sidebar {
        margin-bottom: 2rem;
    }
    
    .stat-card {
        margin-bottom: 1rem;
    }
    
    .table-responsive {
        font-size: 0.8rem;
    }
    
    .info-row {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .info-value {
        text-align: left;
        margin-top: 4px;
    }
}
</style>
@endpush

@push('scripts')
<script>
function updateOrderStatus(orderId, status) {
    const statusLabels = {
        'paid': 'payée',
        'failed': 'échouée',
        'refunded': 'remboursée'
    };
    
    const label = statusLabels[status] || status;
    
    if (confirm(`Êtes-vous sûr de vouloir marquer cette commande comme ${label} ?`)) {
        fetch(`/admin/orders/${orderId}/status`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ status: status })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(`Commande marquée comme ${label}`, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Erreur lors de la mise à jour', 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showNotification('Erreur lors de la mise à jour', 'error');
        });
    }
}

function downloadOrderPDF(orderId) {
    window.open(`/admin/orders/${orderId}/pdf`, '_blank');
}

function downloadAllTickets(orderId) {
    window.open(`/admin/orders/${orderId}/tickets/download`, '_blank');
}

function showQRCode(imageUrl) {
    document.getElementById('qrCodeImage').src = imageUrl;
    const modal = new bootstrap.Modal(document.getElementById('qrCodeModal'));
    modal.show();
}

function viewTicketDetail(ticketId) {
    window.open(`/admin/tickets/${ticketId}`, '_blank');
}

function markTicketUsed(ticketId) {
    if (confirm('Êtes-vous sûr de vouloir marquer ce billet comme utilisé ?')) {
        fetch(`/admin/tickets/${ticketId}/mark-used`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Billet marqué comme utilisé', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Erreur lors de la mise à jour', 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showNotification('Erreur lors de la mise à jour', 'error');
        });
    }
}

// Fonction de notification
function showNotification(message, type) {
    console.log(`${type}: ${message}`);
    
    if (type === 'success') {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.parentNode.removeChild(alertDiv);
            }
        }, 3000);
    } else if (type === 'error') {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed';
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            <i class="fas fa-exclamation-circle me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.parentNode.removeChild(alertDiv);
            }
        }, 5000);
    }
}
</script>
@endpush
@endsection