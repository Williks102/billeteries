{{-- resources/views/layouts/acheteur.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Mon Espace - ClicBillet CI')</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Styles personnalisés -->
    <style>
        :root {
            --primary-orange: #FF6B35;
            --secondary-orange: #ff8c61;
            --primary-dark: #e55a2b;
            --dark-blue: #1a237e;
            --black-primary: #2c3e50;
            --light-gray: #f8f9fa;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-gray);
            padding-top: 70px;
        }
        
        /* === HEADER ACHETEUR === */
        .acheteur-navbar {
            position: fixed !important;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1050;
            background: linear-gradient(135deg, var(--dark-blue), var(--black-primary)) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: 70px;
        }
        
        .acheteur-navbar .navbar-brand {
            color: white !important;
            font-weight: 700;
            font-size: 1.3rem;
        }
        
        .acheteur-navbar .nav-link {
            color: rgba(255,255,255,0.9) !important;
            transition: all 0.3s ease;
        }
        
        .acheteur-navbar .nav-link:hover {
            color: var(--primary-orange) !important;
        }

        .mobile-header {
    background: linear-gradient(135deg, #00796b, #26a69a);
}
        
        /* === SIDEBAR ACHETEUR === */
        .acheteur-sidebar {
            position: sticky !important;
            top: 90px;
            max-height: calc(100vh - 110px);
            overflow-y: auto;
            background: white !important;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .acheteur-sidebar h5 {
            color: var(--black-primary);
            font-weight: 600;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 15px;
        }
        
        .acheteur-sidebar .nav-link {
            color: #6c757d !important;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: all 0.3s ease;
        }
        
        .acheteur-sidebar .nav-link:hover {
            background: rgba(26, 35, 126, 0.1) !important;
            color: var(--dark-blue) !important;
            transform: translateX(5px);
        }
        
        .acheteur-sidebar .nav-link.active {
            background: linear-gradient(45deg, var(--dark-blue), var(--black-primary)) !important;
            color: white !important;
            box-shadow: 0 2px 8px rgba(26, 35, 126, 0.3);
        }
        
        .acheteur-sidebar .nav-link i {
            width: 20px;
            text-align: center;
        }
        
        /* === CONTENU === */
        .acheteur-content {
            min-height: calc(100vh - 100px);
            padding: 20px;
        }
        
        /* === COMPOSANTS === */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .btn-acheteur {
            background: linear-gradient(45deg, var(--dark-blue), var(--black-primary));
            border: none;
            color: white;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-acheteur:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 35, 126, 0.4);
            color: white;
        }
        
        .btn-outline-acheteur {
            border: 2px solid var(--dark-blue);
            color: var(--dark-blue);
            background: transparent;
            border-radius: 8px;
            padding: 8px 18px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-outline-acheteur:hover {
            background: var(--dark-blue);
            color: white;
            transform: translateY(-2px);
        }
        
        /* === STAT CARDS === */
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid var(--primary-orange);
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
        }
        
        .stat-icon.primary {
            background: linear-gradient(135deg, var(--primary-orange), #ff8c42);
        }
        
        .stat-icon.success {
            background: linear-gradient(135deg, #28a745, #34d058);
        }
        
        .stat-icon.info {
            background: linear-gradient(135deg, var(--dark-blue), #0066cc);
        }
        
        .content-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e9ecef;
            overflow: hidden;
        }
        
        /* === BREADCRUMB === */
        .acheteur-breadcrumb {
            background: rgba(255, 107, 53, 0.1) !important;
            border-radius: 8px;
            padding: 12px 20px;
            margin-bottom: 20px;
        }
        
        .acheteur-breadcrumb .breadcrumb-item a {
            color: var(--primary-orange);
            text-decoration: none;
        }
        
        .acheteur-breadcrumb .breadcrumb-item.active {
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
        <button class="mobile-hamburger-btn" onclick="toggleSidebarAcheteur()">
    <i class="fas fa-ellipsis-v"></i> <!-- Icône différente -->
 </button>

    <nav class="navbar navbar-expand-lg acheteur-navbar" id="acheteurSidebar">
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
                    
                    <!-- Profil utilisateur -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            {{ Auth::user()->name }}
                        </a>
                        <ul class="dropdown-menu">
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
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
    </form>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
function toggleSidebar() {
    const sidebar = document.getElementById('acheteurSidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    sidebar.classList.toggle('mobile-open');
    overlay.classList.toggle('active');
    
    if (sidebar.classList.contains('mobile-open')) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = 'auto';
    }
}

function closeSidebar() {
    const sidebar = document.getElementById('acheteurSidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    sidebar.classList.remove('mobile-open');
    overlay.classList.remove('active');
    document.body.style.overflow = 'auto';
}

// Fermer avec la touche Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSidebar();
    }
});

// Fermer automatiquement lors du redimensionnement vers desktop
window.addEventListener('resize', function() {
    if (window.innerWidth > 768) {
        closeSidebar();
    }
});

// Gérer les clics sur les liens de la sidebar mobile
document.querySelectorAll('.acheteur-sidebar .nav-link').forEach(link => {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 768) {
            setTimeout(closeSidebar, 100);
        }
    });
});
</script>

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