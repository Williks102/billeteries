<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mes billets - Billetterie CI</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-orange: #FF6B35;
            --secondary-orange: #ff8c61;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar-custom {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        
        .sidebar {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            padding: 25px;
            position: sticky;
            top: 20px;
        }
        
        .sidebar .nav-link {
            color: #6c757d;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .sidebar .nav-link:hover {
            background: var(--primary-orange);
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar .nav-link.active {
            background: var(--primary-orange);
            color: white;
            box-shadow: 0 3px 10px rgba(255, 107, 53, 0.3);
        }
        
        /* Cards des commandes */
        .order-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: none;
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
            margin: 0 auto 15px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .qr-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .qr-image:hover {
            transform: scale(1.1);
        }
        
        .qr-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #e9ecef, #f8f9fa);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-orange);
            font-size: 20px;
            border: 2px dashed var(--primary-orange);
            border-radius: 15px;
        }
        
        .ticket-info {
            text-align: left;
        }
        
        .ticket-code {
            background: var(--primary-orange);
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-weight: bold;
            font-size: 14px;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        .ticket-type {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .ticket-price {
            color: var(--primary-orange);
            font-weight: 700;
            font-size: 18px;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-valid { background: #d4edda; color: #155724; }
        .status-used { background: #d1ecf1; color: #0c5460; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        /* Actions */
        .ticket-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .btn-custom {
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
        
        .btn-verify {
            background: #17a2b8;
            color: white;
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
            
            .sidebar {
                margin-bottom: 20px;
            }
            
            .ticket-actions {
                justify-content: center;
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
        
        /* Filtres */
        .filters-section {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light navbar-custom">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="{{ route('home') }}">
                <i class="fas fa-ticket-alt me-2"></i>
                Billetterie CI
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="{{ route('home') }}">
                    <i class="fas fa-home me-1"></i>Accueil
                </a>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i>{{ Auth::user()->name }}
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('acheteur.profile') }}">Mon profil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="dropdown-item">Déconnexion</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2">
                <div class="sidebar">
                    <h6 class="text-muted text-uppercase mb-3 fw-bold">
                        <i class="fas fa-user-circle me-2"></i>Mon Espace
                    </h6>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="{{ route('acheteur.dashboard') }}">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link active" href="{{ route('acheteur.tickets') }}">
                            <i class="fas fa-ticket-alt me-2"></i>Mes billets
                        </a>
                        <a class="nav-link" href="{{ route('acheteur.profile') }}">
                            <i class="fas fa-user-cog me-2"></i>Mon profil
                        </a>
                        <a class="nav-link" href="{{ route('home') }}">
                            <i class="fas fa-search me-2"></i>Nouveaux événements
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Contenu principal -->
            <div class="col-md-9 col-lg-10">
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
                        <button class="btn btn-outline-primary" onclick="refreshQRCodes()">
                            <i class="fas fa-sync me-2"></i>Actualiser QR
                        </button>
                        <a href="{{ route('home') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Nouveaux événements
                        </a>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="filters-section">
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
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>Filtrer
                                </button>
                                <a href="{{ route('acheteur.tickets') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Liste des commandes -->
                @if($orders->count() > 0)
                    @foreach($orders as $order)
                        <div class="order-card">
                            <!-- En-tête de la commande -->
                            <div class="order-header">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5>
                                            <i class="fas fa-calendar-event me-2"></i>
                                            {{ $order->event->title }}
                                        </h5>
                                        <div class="order-meta">
                                            <i class="fas fa-receipt me-2"></i>
                                            Commande #{{ $order->order_number }} 
                                            <span class="ms-3">
                                                <i class="fas fa-clock me-1"></i>
                                                {{ $order->created_at->format('d/m/Y à H:i') }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <div class="h4 mb-1">{{ number_format($order->total_amount) }} FCFA</div>
                                        <span class="badge bg-light text-dark">
                                            {{ $order->tickets->count() }} billet(s)
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Grille des billets -->
                            <div class="tickets-grid">
                                @foreach($order->tickets as $ticket)
                                    <div class="ticket-card">
                                        <!-- Section QR Code optimisée -->
                                        <div class="qr-section">
                                            @php
                                                try {
                                                    $qrService = app(\App\Services\QRCodeService::class);
                                                    $qrBase64 = $qrService->generateTicketQRBase64($ticket, 200);
                                                    $hasQR = $qrBase64 && strlen($qrBase64) > 100;
                                                } catch (\Exception $e) {
                                                    $qrBase64 = null;
                                                    $hasQR = false;
                                                    \Log::error("QR Error for {$ticket->ticket_code}: " . $e->getMessage());
                                                }
                                            @endphp
                                            
                                            <div class="qr-container">
                                                @if($hasQR)
                                                    <img src="{{ $qrBase64 }}" 
                                                         alt="QR Code" 
                                                         class="qr-image"
                                                         onclick="showLargeQR('{{ $qrBase64 }}', '{{ $ticket->ticket_code }}', '{{ $order->event->title }}')">
                                                @else
                                                    <div class="qr-placeholder">
                                                        <i class="fas fa-qrcode"></i>
                                                    </div>
                                                @endif
                                            </div>
                                            
                                            @if($hasQR)
                                                <small class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>Scannable
                                                </small>
                                            @else
                                                <small class="badge bg-warning">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>Code manuel
                                                </small>
                                            @endif
                                        </div>

                                        <!-- Informations du billet -->
                                        <div class="ticket-info">
                                            <div class="ticket-code">{{ $ticket->ticket_code }}</div>
                                            
                                            <div class="ticket-type">{{ $ticket->ticketType->name ?? 'Standard' }}</div>
                                            
                                            @if($ticket->seat_number)
                                                <div class="text-muted mb-2">
                                                    <i class="fas fa-chair me-1"></i>{{ $ticket->seat_number }}
                                                </div>
                                            @endif
                                            
                                            <div class="ticket-price">
                                                {{ number_format($ticket->ticketType->price ?? 0) }} FCFA
                                            </div>
                                            
                                            <!-- Statut -->
                                            <div class="mt-2">
                                                @if($ticket->status == 'sold')
                                                    <span class="status-badge status-valid">
                                                        <i class="fas fa-check-circle me-1"></i>Valide
                                                    </span>
                                                @elseif($ticket->status == 'used')
                                                    <span class="status-badge status-used">
                                                        <i class="fas fa-check me-1"></i>Utilisé
                                                    </span>
                                                @else
                                                    <span class="status-badge status-cancelled">
                                                        <i class="fas fa-times me-1"></i>{{ ucfirst($ticket->status) }}
                                                    </span>
                                                @endif
                                            </div>
                                            
                                            <!-- Actions -->
                                            <div class="ticket-actions">
                                                @if($hasQR)
                                                    <button class="btn btn-custom btn-qr" 
                                                            onclick="showLargeQR('{{ $qrBase64 }}', '{{ $ticket->ticket_code }}', '{{ $order->event->title }}')">
                                                        <i class="fas fa-expand me-1"></i>Agrandir QR
                                                    </button>
                                                @endif
                                                
                                                <a href="{{ url("/verify-ticket/{$ticket->ticket_code}") }}" 
                                                   target="_blank" class="btn btn-custom btn-verify">
                                                    <i class="fas fa-external-link-alt me-1"></i>Vérifier
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Actions de la commande -->
                            <div class="border-top p-3">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <div class="text-muted">
                                            <i class="fas fa-map-marker-alt me-2"></i>
                                            {{ $order->event->venue }}
                                            <span class="ms-3">
                                                <i class="fas fa-calendar me-1"></i>
                                                {{ $order->event->event_date ? $order->event->event_date->format('d/m/Y') : 'Date TBD' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        @if($order->canDownloadTickets())
                                            <a href="{{ route('acheteur.order.download', $order) }}" 
                                               class="btn btn-custom btn-download me-2">
                                                <i class="fas fa-file-pdf me-1"></i>PDF
                                            </a>
                                        @endif
                                        
                                        <a href="{{ route('acheteur.order.detail', $order) }}" 
                                           class="btn btn-outline-primary">
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
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-ticket-alt fa-5x text-muted"></i>
                        </div>
                        <h4 class="text-muted">Aucun billet trouvé</h4>
                        <p class="text-muted mb-4">Vous n'avez pas encore acheté de billets pour des événements.</p>
                        <a href="{{ route('home') }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-search me-2"></i>Découvrir les événements
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal QR Code Large -->
    <div class="modal fade qr-modal" id="largeQRModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-qrcode me-2"></i>
                        QR Code - <span id="qrTicketCode"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <img id="largeQRImage" src="" alt="QR Code" class="large-qr mb-3">
                    
                    <h6 id="qrEventTitle" class="text-muted mb-3"></h6>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-mobile-alt me-2"></i>
                        <strong>Instructions :</strong> Positionnez votre téléphone à 10-15cm du QR code pour le scanner
                    </div>
                    
                    <div class="row text-center mt-3">
                        <div class="col-4">
                            <i class="fas fa-camera fa-2x text-primary mb-2"></i>
                            <div class="small">Ouvrez l'appareil photo</div>
                        </div>
                        <div class="col-4">
                            <i class="fas fa-crosshairs fa-2x text-warning mb-2"></i>
                            <div class="small">Visez le QR code</div>
                        </div>
                        <div class="col-4">
                            <i class="fas fa-check fa-2x text-success mb-2"></i>
                            <div class="small">Validez l'entrée</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Fermer
                    </button>
                    <button type="button" class="btn btn-primary" onclick="downloadQR()">
                        <i class="fas fa-download me-1"></i>Télécharger
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let currentQRData = null;
        
        function showLargeQR(qrBase64, ticketCode, eventTitle) {
            document.getElementById('largeQRImage').src = qrBase64;
            document.getElementById('qrTicketCode').textContent = ticketCode;
            document.getElementById('qrEventTitle').textContent = eventTitle;
            currentQRData = { qrBase64, ticketCode, eventTitle };
            
            new bootstrap.Modal(document.getElementById('largeQRModal')).show();
        }
        
        function downloadQR() {
            if (!currentQRData) return;
            
            const link = document.createElement('a');
            link.href = currentQRData.qrBase64;
            link.download = `QR-${currentQRData.ticketCode}.png`;
            link.click();
        }
        
        function refreshQRCodes() {
            location.reload();
        }
        
        // Animation d'entrée
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.order-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>