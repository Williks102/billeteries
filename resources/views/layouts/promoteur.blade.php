{{-- resources/views/layouts/promoteur.blade.php - VÉRIFICATION --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - Espace Promoteur</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Styles personnalisés -->
    <style>
        :root {
            --primary-color: #FF6B35;
            --secondary-color: #E55A2B;
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .navbar-brand {
            color: var(--primary-color) !important;
            font-weight: bold;
        }
        
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .sidebar {
            min-height: calc(100vh - 56px);
            background: white;
            box-shadow: 2px 0 4px rgba(0,0,0,0.1);
        }
        
        .nav-link {
            color: #6c757d;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin: 0.25rem 0;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover,
        .nav-link.active {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white !important;
        }
        
        .main-content {
            padding: 2rem;
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('home') }}">
                <i class="fas fa-ticket-alt me-2"></i>
                {{ config('app.name', 'Billetterie') }}
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i>
                        {{ Auth::user()->name }}
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('promoteur.profile') }}">
                            <i class="fas fa-user me-2"></i>Profil
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="{{ route('logout') }}"
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="p-3">
                    <h6 class="text-muted text-uppercase mb-3">Menu Principal</h6>
                    <nav class="nav flex-column">
                        <a class="nav-link {{ request()->routeIs('promoteur.dashboard') ? 'active' : '' }}" 
                           href="{{ route('promoteur.dashboard') }}">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link {{ request()->routeIs('promoteur.events.*') ? 'active' : '' }}" 
                           href="{{ route('promoteur.events.index') }}">
                            <i class="fas fa-calendar me-2"></i>Mes Événements
                        </a>
                        <a class="nav-link {{ request()->routeIs('promoteur.sales') ? 'active' : '' }}" 
                           href="{{ route('promoteur.sales') }}">
                            <i class="fas fa-chart-line me-2"></i>Ventes
                        </a>
                        <a class="nav-link {{ request()->routeIs('promoteur.commissions') ? 'active' : '' }}" 
                           href="{{ route('promoteur.commissions') }}">
                            <i class="fas fa-coins me-2"></i>Commissions
                        </a>
                        <a class="nav-link {{ request()->routeIs('promoteur.reports') ? 'active' : '' }}" 
                           href="{{ route('promoteur.reports') }}">
                            <i class="fas fa-chart-bar me-2"></i>Rapports
                        </a>
                        <a class="nav-link {{ request()->routeIs('promoteur.scanner') ? 'active' : '' }}" 
                           href="{{ route('promoteur.scanner') }}">
                            <i class="fas fa-qrcode me-2"></i>Scanner
                        </a>
                    </nav>
                    
                    <hr>
                    
                    <h6 class="text-muted text-uppercase mb-3">Actions Rapides</h6>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="{{ route('promoteur.events.create') }}">
                            <i class="fas fa-plus me-2"></i>Nouvel Événement
                        </a>
                        <a class="nav-link" href="{{ route('home') }}">
                            <i class="fas fa-eye me-2"></i>Voir le Site
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    
                    @yield('content')
                </div>
            </div>
        </div>
    </div>
    
    <!-- Logout Form -->
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
    </form>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    @stack('scripts')
</body>
</html>