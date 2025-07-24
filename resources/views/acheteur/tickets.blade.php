<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mes billets - Billetterie CI</title>
    
    <!-- Bootstrap CSS -->
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- NOUVEAU : Thème Orange & Noir -->
<link href="{{ asset('css/theme.css') }}" rel="stylesheet">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
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
            <div class="col-md-9 col-lg-10 p-4">
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-ticket-alt me-2"></i>Mes billets</h4>
        
        <!-- Filtres -->
        <form method="GET" action="{{ route('acheteur.tickets') }}" class="d-flex gap-2">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">Tous les billets</option>
                <option value="upcoming" {{ request('status') == 'upcoming' ? 'selected' : '' }}>À venir</option>
                <option value="past" {{ request('status') == 'past' ? 'selected' : '' }}>Passés</option>
            </select>
        </form>
    </div>

    @if($orders->count() > 0)
        @foreach($orders as $order)
            <div class="card mb-4">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="mb-0 fw-bold">{{ $order->event->title }}</h6>
                            <small class="text-muted">
                                Commande #{{ $order->order_number }} • 
                                {{ $order->created_at->format('d/m/Y H:i') }}
                            </small>
                        </div>
                        <div class="col-md-2 text-center">
                            @if($order->payment_status == 'paid')
                                <span class="badge bg-success">Confirmé</span>
                            @else
                                <span class="badge bg-warning">{{ ucfirst($order->payment_status) }}</span>
                            @endif
                        </div>
                        <div class="col-md-2 text-end">
                            <strong>{{ $order->formatted_total }}</strong>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1">
                                <i class="fas fa-calendar me-2"></i>
                                <strong>{{ $order->event->formatted_event_date }}</strong> à 
                                <strong>{{ $order->event->formatted_event_time }}</strong>
                            </p>
                            <p class="mb-1">
                                <i class="fas fa-map-marker-alt me-2"></i>
                                {{ $order->event->venue }}
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-tag me-2"></i>
                                {{ $order->event->category->name }}
                            </p>
                        </div>
                        
                        <div class="col-md-6">
                            <h6>Billets :</h6>
                            @foreach($order->orderItems as $item)
                                <div class="d-flex justify-content-between mb-1">
                                    <span>{{ $item->quantity }}× {{ $item->ticketType->name }}</span>
                                    <span>{{ number_format($item->total_price, 0, ',', ' ') }} FCFA</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    @if($order->payment_status == 'paid')
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <a href="{{ route('acheteur.order.detail', $order) }}" 
                                       class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>Voir les détails
                                    </a>
                                    
                                    @if($order->canDownloadTickets())
                                        <a href="{{ route('acheteur.order.download', $order) }}" 
                                           class="btn btn-success btn-sm">
                                            <i class="fas fa-download me-1"></i>Télécharger PDF
                                        </a>
                                    @endif
                                    
                                    @if($order->event->isUpcoming)
                                        <button class="btn btn-info btn-sm" onclick="showQRCodes({{ $order->id }})">
                                            <i class="fas fa-qrcode me-1"></i>QR Codes
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
        
        <!-- Pagination -->
        <div class="d-flex justify-content-center">
            {{ $orders->appends(request()->query())->links() }}
        </div>
    @else
        <div class="text-center py-5">
            <i class="fas fa-ticket-alt fa-5x text-muted mb-4"></i>
            <h5 class="text-muted">Aucun billet trouvé</h5>
            <p class="text-muted">Vous n'avez pas encore acheté de billets.</p>
            <a href="{{ route('home') }}" class="btn btn-primary">
                <i class="fas fa-search me-2"></i>Découvrir les événements
            </a>
        </div>
    @endif
</div>

<!-- Modal QR Codes -->
<div class="modal fade" id="qrModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mes QR Codes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="qrCodesContent">
                <!-- Contenu chargé dynamiquement -->
            </div>
        </div>
    </div>
</div>

<script>
            </div>
        </div>
    </div>

    <!-- Modal QR Codes -->
    <div class="modal fade" id="qrModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Mes QR Codes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="qrCodesContent">
                    <!-- Contenu chargé dynamiquement -->
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function showQRCodes(orderId) {
        // Afficher le modal avec les QR codes
        // Pour l'instant, juste un message
        document.getElementById('qrCodesContent').innerHTML = `
            <div class="text-center">
                <i class="fas fa-qrcode fa-3x mb-3"></i>
                <p>Les QR codes seront disponibles prochainement !</p>
                <p class="small text-muted">Vous pourrez scanner vos billets directement à l'entrée de l'événement.</p>
            </div>
        `;
        
        new bootstrap.Modal(document.getElementById('qrModal')).show();
    }
    </script>
</body>
</html>