<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Détails commande #{{ $order->order_number }} - Billetterie CI</title>
    
    <!-- Bootstrap CSS -->
 <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- NOUVEAU : Thème Orange & Noir -->
<link href="{{ asset('css/theme.css') }}" rel="stylesheet">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        .order-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
        }
        
        .ticket-card {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            background: white;
        }
        
        .ticket-card:hover {
            border-color: #667eea;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.1);
        }
        
        .qr-code-placeholder {
            width: 100px;
            height: 100px;
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #6c757d;
        }
        
        .status-badge {
            font-size: 0.9rem;
            padding: 8px 16px;
            border-radius: 20px;
        }
        
        .btn-primary-custom {
            background: #FF6B35;
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
        }
        
        .btn-primary-custom:hover {
            background: #E55A2B;
        }
        
        .navbar-custom {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
        }
        
        .sidebar {
            background: #f8f9fa;
            min-height: calc(100vh - 56px);
            padding: 20px;
        }
        
        .sidebar .nav-link {
            color: #6c757d;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 5px;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover {
            background: #667eea;
            color: white;
        }
        
        .sidebar .nav-link.active {
            background: #667eea;
            color: white;
        }
        
        .order-summary {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            position: sticky;
            top: 20px;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="{{ route('home') }}">
                <i class="fas fa-ticket-alt me-2"></i>
                Billetterie CI
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i>{{ Auth::user()->name }}
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('acheteur.profile') }}"><i class="fas fa-user me-2"></i>Mon profil</a></li>
                        <li><a class="dropdown-item" href="{{ route('home') }}"><i class="fas fa-home me-2"></i>Retour à l'accueil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="{{ route('logout') }}"
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                            </a>
                        </li>
                    </ul>
                </div>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <nav class="nav flex-column">
                    <a class="nav-link" href="{{ route('acheteur.dashboard') }}">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a class="nav-link active" href="{{ route('acheteur.tickets') }}">
                        <i class="fas fa-ticket-alt me-2"></i>Mes billets
                    </a>
                    <a class="nav-link" href="{{ route('acheteur.profile') }}">
                        <i class="fas fa-user me-2"></i>Mon profil
                    </a>
                    <a class="nav-link" href="{{ route('home') }}">
                        <i class="fas fa-search me-2"></i>Chercher des événements
                    </a>
                </nav>
            </div>

            <!-- Contenu principal -->
            <div class="col-md-9 col-lg-10 p-0">
                <!-- Header -->
                <section class="order-header">
                    <div class="container-fluid">
                        <div class="row align-items-center">
                            <div class="col-lg-8">
                                <nav aria-label="breadcrumb" class="mb-3">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item">
                                            <a href="{{ route('acheteur.dashboard') }}" class="text-white-50">Dashboard</a>
                                        </li>
                                        <li class="breadcrumb-item">
                                            <a href="{{ route('acheteur.tickets') }}" class="text-white-50">Mes billets</a>
                                        </li>
                                        <li class="breadcrumb-item active text-white">Commande #{{ $order->order_number }}</li>
                                    </ol>
                                </nav>
                                
                                <h1 class="mb-2">{{ $order->event->title }}</h1>
                                <p class="lead mb-0">
                                    <i class="fas fa-calendar me-2"></i>{{ $order->event->formatted_event_date }} à {{ $order->event->formatted_event_time }}
                                </p>
                            </div>
                            <div class="col-lg-4 text-lg-end">
                                @if($order->payment_status == 'paid')
                                    <span class="status-badge bg-success text-white">
                                        <i class="fas fa-check-circle me-1"></i>Confirmé
                                    </span>
                                @elseif($order->payment_status == 'pending')
                                    <span class="status-badge bg-warning text-dark">
                                        <i class="fas fa-clock me-1"></i>En attente
                                    </span>
                                @else
                                    <span class="status-badge bg-danger text-white">
                                        <i class="fas fa-times-circle me-1"></i>{{ ucfirst($order->payment_status) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Contenu -->
                <div class="container-fluid mt-4">
                    <div class="row">
                        <!-- Mes billets -->
                        <div class="col-lg-8">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4><i class="fas fa-tickets me-2"></i>Mes billets ({{ $order->tickets->count() }})</h4>
                                
                                @if($order->canDownloadTickets())
                                    <a href="{{ route('acheteur.order.download', $order) }}" class="btn btn-success">
                                        <i class="fas fa-download me-2"></i>Télécharger PDF
                                    </a>
                                @endif
                            </div>

                            @if($order->tickets->count() > 0)
                                @foreach($order->tickets as $ticket)
                                    <div class="ticket-card">
                                        <div class="row align-items-center">
                                            <div class="col-md-2 text-center">
                                                <!-- QR Code placeholder -->
                                                <div class="qr-code-placeholder">
                                                    <i class="fas fa-qrcode"></i>
                                                </div>
                                                <small class="text-muted d-block mt-2">
                                                    {{ $ticket->ticket_code }}
                                                </small>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <h6 class="fw-bold mb-1">{{ $ticket->ticketType->name }}</h6>
                                                <p class="text-muted mb-1">
                                                    <i class="fas fa-ticket-alt me-1"></i>
                                                    {{ $ticket->ticketType->description }}
                                                </p>
                                                
                                                @if($ticket->seat_number)
                                                    <p class="text-muted mb-1">
                                                        <i class="fas fa-couch me-1"></i>
                                                        Siège : {{ $ticket->seat_number }}
                                                    </p>
                                                @endif
                                                
                                                <p class="text-muted mb-0">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    {{ $order->event->venue }}
                                                </p>
                                            </div>
                                            
                                            <div class="col-md-2 text-center">
                                                @if($ticket->status == 'sold')
                                                    <span class="badge bg-success">Valide</span>
                                                @elseif($ticket->status == 'used')
                                                    <span class="badge bg-info">Utilisé</span>
                                                @elseif($ticket->status == 'cancelled')
                                                    <span class="badge bg-danger">Annulé</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ ucfirst($ticket->status) }}</span>
                                                @endif
                                            </div>
                                            
                                            <div class="col-md-2 text-end">
                                                <strong>{{ number_format($ticket->ticketType->price, 0, ',', ' ') }} FCFA</strong>
                                                
                                                @if($ticket->status == 'sold' && $order->event->isUpcoming)
                                                    <div class="mt-2">
                                                        <button class="btn btn-sm btn-outline-primary" 
                                                                onclick="showQRCode('{{ $ticket->ticket_code }}')">
                                                            <i class="fas fa-qrcode"></i>
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Aucun billet trouvé pour cette commande.</p>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Résumé de commande -->
                        <div class="col-lg-4">
                            <div class="order-summary">
                                <h5 class="mb-4"><i class="fas fa-receipt me-2"></i>Détails de la commande</h5>
                                
                                <div class="mb-3">
                                    <small class="text-muted">Numéro de commande</small>
                                    <p class="fw-bold">#{{ $order->order_number }}</p>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">Date de commande</small>
                                    <p class="fw-bold">{{ $order->created_at->format('d/m/Y H:i') }}</p>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">Email de facturation</small>
                                    <p class="fw-bold">{{ $order->billing_email }}</p>
                                </div>
                                
                                @if($order->billing_phone)
                                    <div class="mb-3">
                                        <small class="text-muted">Téléphone</small>
                                        <p class="fw-bold">{{ $order->billing_phone }}</p>
                                    </div>
                                @endif
                                
                                <hr>
                                
                                <h6 class="mb-3">Détail des billets</h6>
                                @foreach($order->orderItems as $item)
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>{{ $item->quantity }}× {{ $item->ticketType->name }}</span>
                                        <span>{{ number_format($item->total_price, 0, ',', ' ') }} FCFA</span>
                                    </div>
                                @endforeach
                                
                                <hr>
                                
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Sous-total :</span>
                                    <span>{{ $order->formatted_total }}</span>
                                </div>
                                
                                <div class="d-flex justify-content-between mb-4">
                                    <strong>Total payé :</strong>
                                    <strong class="text-primary">{{ $order->formatted_total }}</strong>
                                </div>
                                
                                @if($order->payment_method)
                                    <div class="mb-3">
                                        <small class="text-muted">Mode de paiement</small>
                                        <p class="fw-bold">{{ ucfirst($order->payment_method) }}</p>
                                    </div>
                                @endif
                                
                                @if($order->payment_reference)
                                    <div class="mb-4">
                                        <small class="text-muted">Référence de paiement</small>
                                        <p class="fw-bold small">{{ $order->payment_reference }}</p>
                                    </div>
                                @endif
                                
                                <div class="d-grid gap-2">
                                    @if($order->canDownloadTickets())
                                        <a href="{{ route('acheteur.order.download', $order) }}" 
                                           class="btn btn-primary-custom">
                                            <i class="fas fa-download me-2"></i>Télécharger mes billets
                                        </a>
                                    @endif
                                    
                                    <a href="{{ route('acheteur.tickets') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Retour à mes billets
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Informations événement -->
                            <div class="order-summary mt-4">
                                <h5 class="mb-4"><i class="fas fa-calendar-alt me-2"></i>Événement</h5>
                                
                                <div class="mb-3">
                                    <h6 class="fw-bold">{{ $order->event->title }}</h6>
                                    <p class="text-muted mb-1">
                                        <i class="fas fa-tag me-1"></i>{{ $order->event->category->name }}
                                    </p>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">Date et heure</small>
                                    <p class="fw-bold">
                                        {{ $order->event->formatted_event_date }}<br>
                                        {{ $order->event->formatted_event_time }}
                                        @if($order->event->end_time)
                                            - {{ $order->event->end_time->format('H:i') }}
                                        @endif
                                    </p>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">Lieu</small>
                                    <p class="fw-bold">
                                        {{ $order->event->venue }}<br>
                                        <small class="text-muted">{{ $order->event->address }}</small>
                                    </p>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">Organisateur</small>
                                    <p class="fw-bold">{{ $order->event->promoteur->name }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal QR Code -->
    <div class="modal fade" id="qrModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">QR Code du billet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center" id="qrModalContent">
                    <!-- Contenu chargé dynamiquement -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function showQRCode(ticketCode) {
            document.getElementById('qrModalContent').innerHTML = `
                <div class="text-center">
                    <div class="qr-code-placeholder mx-auto mb-3" style="width: 200px; height: 200px; font-size: 4rem;">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <h6 class="fw-bold">Billet : ${ticketCode}</h6>
                    <p class="text-muted">Présentez ce QR code à l'entrée de l'événement</p>
                    <div class="alert alert-info small">
                        <i class="fas fa-info-circle me-1"></i>
                        Le QR code sera généré lors de l'implémentation finale
                    </div>
                </div>
            `;
            
            new bootstrap.Modal(document.getElementById('qrModal')).show();
        }
    </script>
</body>
</html>