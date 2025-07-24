<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mon Dashboard - Billetterie CI</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- NOUVEAU : Thème Orange & Noir -->
<link href="{{ asset('css/theme.css') }}" rel="stylesheet">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            border: none;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 1.5rem;
            color: white;
        }
        
        .stat-icon.primary { background: linear-gradient(45deg, #667eea, #764ba2); }
        .stat-icon.success { background: linear-gradient(45deg, #56ab2f, #a8e6cf); }
        .stat-icon.warning { background: linear-gradient(45deg, #f093fb, #f5576c); }
        .stat-icon.info { background: linear-gradient(45deg, #4facfe, #00f2fe); }
        
        .recent-order-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .recent-order-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .btn-primary-custom {
            background: #FF6B35;
            border: none;
            border-radius: 25px;
            padding: 10px 25px;
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

    {{-- Juste après la navbar --}}
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center py-3">
        <h1>Dashboard {{ ucfirst(auth()->user()->role) }}</h1>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-danger">
                <i class="fas fa-sign-out-alt"></i> Se déconnecter
            </button>
        </form>
    </div>
</div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <nav class="nav flex-column">
                    <a class="nav-link active" href="{{ route('acheteur.dashboard') }}">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a class="nav-link" href="{{ route('acheteur.tickets') }}">
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
                <section class="dashboard-header">
                    <div class="container-fluid">
                        <div class="row align-items-center">
                            <div class="col-lg-8">
                                <h1 class="mb-2">👋 Bonjour {{ Auth::user()->name }} !</h1>
                                <p class="lead mb-0">Bienvenue sur votre espace personnel</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Messages de succès -->
                @if(session('success'))
                    <div class="container-fluid mt-4">
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    </div>
                @endif

                <!-- Statistiques -->
                <div class="container-fluid mt-4">
                    <div class="row">
                        <div class="col-lg-3 col-md-6 mb-4">
                            <div class="stat-card">
                                <div class="stat-icon primary">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <h3 class="fw-bold mb-1">{{ $stats['total_orders'] }}</h3>
                                <p class="text-muted mb-0">Commandes</p>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-4">
                            <div class="stat-card">
                                <div class="stat-icon success">
                                    <i class="fas fa-ticket-alt"></i>
                                </div>
                                <h3 class="fw-bold mb-1">{{ $stats['total_tickets'] }}</h3>
                                <p class="text-muted mb-0">Billets achetés</p>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-4">
                            <div class="stat-card">
                                <div class="stat-icon warning">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <h3 class="fw-bold mb-1">{{ $stats['upcoming_events'] }}</h3>
                                <p class="text-muted mb-0">Événements à venir</p>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-4">
                            <div class="stat-card">
                                <div class="stat-icon info">
                                    <i class="fas fa-history"></i>
                                </div>
                                <h3 class="fw-bold mb-1">{{ $stats['past_events'] }}</h3>
                                <p class="text-muted mb-0">Événements passés</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Événements à venir -->
                @if($upcomingEvents->count() > 0)
                    <div class="container-fluid mt-4">
                        <div class="row">
                            <div class="col-12">
                                <h4 class="mb-4"><i class="fas fa-calendar-alt me-2"></i>Mes prochains événements</h4>
                                <div class="row">
                                    @foreach($upcomingEvents as $order)
                                        <div class="col-lg-4 mb-4">
                                            <div class="card h-100">
                                                <div class="card-body">
                                                    <span class="badge bg-primary mb-2">{{ $order->event->category->name }}</span>
                                                    <h6 class="card-title fw-bold">{{ $order->event->title }}</h6>
                                                    <p class="card-text small text-muted">
                                                        <i class="fas fa-calendar me-1"></i>{{ $order->event->formatted_event_date }}<br>
                                                        <i class="fas fa-clock me-1"></i>{{ $order->event->formatted_event_time }}<br>
                                                        <i class="fas fa-map-marker-alt me-1"></i>{{ $order->event->venue }}
                                                    </p>
                                                    <p class="card-text">
                                                        <strong>{{ $order->tickets->count() }} billet(s)</strong>
                                                    </p>
                                                </div>
                                                <div class="card-footer bg-transparent">
                                                    <a href="{{ route('acheteur.order.detail', $order) }}" class="btn btn-outline-primary btn-sm w-100">
                                                        Voir mes billets
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Commandes récentes -->
                <div class="container-fluid mt-4 mb-5">
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4><i class="fas fa-history me-2"></i>Commandes récentes</h4>
                                <a href="{{ route('acheteur.tickets') }}" class="btn btn-outline-primary">
                                    Voir tout
                                </a>
                            </div>
                            
                            @if($recentOrders->count() > 0)
                                @foreach($recentOrders as $order)
                                    <div class="recent-order-card">
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <h6 class="fw-bold mb-1">{{ $order->event->title }}</h6>
                                                <p class="text-muted mb-1 small">
                                                    Commande #{{ $order->order_number }} • 
                                                    {{ $order->created_at->format('d/m/Y H:i') }}
                                                </p>
                                                <p class="text-muted mb-0 small">
                                                    <i class="fas fa-calendar me-1"></i>{{ $order->event->formatted_event_date }} • 
                                                    {{ $order->orderItems->sum('quantity') }} billet(s)
                                                </p>
                                            </div>
                                            <div class="col-md-2 text-center">
                                                @if($order->payment_status == 'paid')
                                                    <span class="badge bg-success">Payé</span>
                                                @elseif($order->payment_status == 'pending')
                                                    <span class="badge bg-warning">En attente</span>
                                                @else
                                                    <span class="badge bg-danger">{{ ucfirst($order->payment_status) }}</span>
                                                @endif
                                            </div>
                                            <div class="col-md-2 text-end">
                                                <strong>{{ $order->formatted_total }}</strong><br>
                                                <a href="{{ route('acheteur.order.detail', $order) }}" class="btn btn-sm btn-outline-primary">
                                                    Détails
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">Aucune commande pour le moment</h5>
                                    <p class="text-muted">Découvrez nos événements et réservez vos billets !</p>
                                    <a href="{{ route('home') }}" class="btn btn-primary-custom">
                                        <i class="fas fa-search me-2"></i>Chercher des événements
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>