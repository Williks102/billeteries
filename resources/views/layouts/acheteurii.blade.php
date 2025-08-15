<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - ClicBillet CI</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Styles personnalisés -->
    <style>
        :root {
            --primary-orange: #FF6B35;
            --secondary-orange: #FF8C42;
            --dark-gray: #2C3E50;
            --light-gray: #F8F9FA;
            --border-color: #E9ECEF;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-gray);
            padding-top: 80px;
        }
        
        /* === NAVBAR === */
        .acheteur-navbar {
            background: white !important;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            height: 80px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
        }
        
        .navbar-brand {
            color: var(--primary-orange) !important;
            font-weight: 700;
            font-size: 1.4rem;
        }
        
        .nav-link {
            color: var(--dark-gray) !important;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            background-color: rgba(255, 107, 53, 0.1);
            color: var(--primary-orange) !important;
        }
        
        .nav-link.active {
            background-color: var(--primary-orange);
            color: white !important;
        }
        
        /* === SIDEBAR === */
        .acheteur-sidebar {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
            padding: 0;
            position: sticky;
            top: 100px;
            max-height: calc(100vh - 120px);
            overflow-y: auto;
        }
        
        /* === CONTENT === */
        .acheteur-content {
            padding: 20px;
        }
        
        /* === BREADCRUMB === */
        .acheteur-breadcrumb {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .breadcrumb-item + .breadcrumb-item::before {
            content: "›";
            color: var(--primary-orange);
            font-weight: bold;
        }
        
        .breadcrumb .breadcrumb-item.active {
            color: var(--dark-gray);
            font-weight: 500;
        }
        
        /* === RESPONSIVE === */
        @media (max-width: 768px) {
            body { 
                padding-top: 60px; 
            }
            
            .acheteur-navbar { 
                height: 60px; 
            }
            
            .acheteur-sidebar {
                position: relative !important;
                top: auto;
                max-height: none;
                margin-bottom: 20px;
            }
            
            .acheteur-content {
                padding: 10px;
            }
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg acheteur-navbar">
        <div class="container-fluid px-4">
            <!-- Brand -->
            <a class="navbar-brand" href="{{ route('home') }}">
                <i class="fas fa-ticket-alt me-2"></i>ClicBillet CI
            </a>
            
            <!-- Mobile toggle -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Navigation -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('home') }}">
                            <i class="fas fa-home me-1"></i>Accueil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('events.all') }}">
                            <i class="fas fa-calendar-alt me-1"></i>Événements
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <!-- Panier -->
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="{{ route('cart.show') }}">
                            <i class="fas fa-shopping-cart"></i>
                            <span id="cart-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.7rem;">
                                0
                            </span>
                        </a>
                    </li>
                    
                    <!-- Profil utilisateur - CORRECTION ICI -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            @auth
                                {{ auth()->user()->name ?? 'Utilisateur' }}
                            @else
                                Connexion
                            @endauth
                        </a>
                        <ul class="dropdown-menu">
                            @auth
                                <li>
                                    <a class="dropdown-item" href="{{ route('acheteur.profile') }}">
                                        <i class="fas fa-user me-2"></i>Mon profil
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                                    </a>
                                </li>
                            @else
                                <li>
                                    <a class="dropdown-item" href="{{ route('login') }}">
                                        <i class="fas fa-sign-in-alt me-2"></i>Se connecter
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('register') }}">
                                        <i class="fas fa-user-plus me-2"></i>S'inscrire
                                    </a>
                                </li>
                            @endauth
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Contenu principal -->
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2">
                @include('partials.acheteur-sidebar')
            </div>
            
            <!-- Contenu -->
            <div class="col-md-9 col-lg-10 acheteur-content">
                <!-- Breadcrumb (optionnel) -->
                @hasSection('breadcrumb')
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb acheteur-breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('acheteur.dashboard') }}">Dashboard</a>
                        </li>
                        @yield('breadcrumb')
                    </ol>
                </nav>
                @endif
                
                <!-- Messages flash -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                
                <!-- Contenu de la page -->
                @yield('content')
            </div>
        </div>
    </div>
    
    <!-- Formulaire de déconnexion -->
    @auth
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
    </form>
    @endauth
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script pour le compteur panier -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Fonction pour mettre à jour le compteur du panier
            function updateCartCount() {
                fetch('{{ route("cart.data") }}')
                    .then(response => response.json())
                    .then(data => {
                        const cartCount = document.getElementById('cart-count');
                        const sidebarCartCount = document.getElementById('sidebar-cart-count');
                        
                        if (cartCount) {
                            cartCount.textContent = data.count || 0;
                            cartCount.style.display = data.count > 0 ? 'inline' : 'none';
                        }
                        
                        if (sidebarCartCount) {
                            sidebarCartCount.textContent = data.count || 0;
                            sidebarCartCount.style.display = data.count > 0 ? 'inline' : 'none';
                        }
                    })
                    .catch(error => console.log('Erreur lors de la mise à jour du panier:', error));
            }
            
            // Mettre à jour le compteur au chargement
            updateCartCount();
            
            // Mettre à jour le compteur toutes les 30 secondes
            setInterval(updateCartCount, 30000);
        });
    </script>
    
    @stack('scripts')
</body>
</html>