<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Mon Espace') - {{ config('app.name') }}</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-orange: #FF6B35;
            --primary-dark: #E55A2B;
            --secondary-orange: #FFA726;
            --accent-orange: #FFB74D;
            --dark-blue: #1a237e;
            --dark-blue-light: #3949ab;
            --black-primary: #2c3e50;
            --black-secondary: #34495e;
            --gray-light: #f8f9fa;
            --gray-medium: #6c757d;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
        }

        body {
            background-color: var(--gray-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* === NAVBAR ACHETEUR === */
        .acheteur-navbar {
            background: linear-gradient(135deg, var(--dark-blue), var(--dark-blue-light));
            box-shadow: 0 4px 20px rgba(26, 35, 126, 0.3);
            backdrop-filter: blur(10px);
        }

        .acheteur-navbar .navbar-brand {
            color: white !important;
            font-weight: 700;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .acheteur-navbar .navbar-brand i {
            color: var(--primary-orange);
            font-size: 1.6rem;
        }

        .acheteur-navbar .navbar-brand:hover {
            color: var(--primary-orange) !important;
            transform: scale(1.02);
            transition: all 0.3s ease;
        }

        /* === NAVIGATION MENU === */
        .navbar-nav .nav-link {
            color: rgba(255,255,255,0.95) !important;
            font-weight: 500;
            padding: 12px 18px !important;
            border-radius: 8px;
            margin: 0 3px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .navbar-nav .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,107,53,0.2), transparent);
            transition: left 0.5s;
        }

        .navbar-nav .nav-link:hover::before {
            left: 100%;
        }

        .navbar-nav .nav-link:hover {
            color: white !important;
            background: rgba(255, 107, 53, 0.15);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .navbar-nav .nav-link.active {
            background: var(--primary-orange) !important;
            color: white !important;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.4);
        }

        .navbar-nav .nav-link i {
            margin-right: 8px;
            width: 18px;
            text-align: center;
        }

        /* === DROPDOWN MENU === */
        .dropdown-menu {
            background: white;
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            padding: 10px 0;
            margin-top: 8px;
            min-width: 220px;
        }

        .dropdown-item {
            color: var(--black-primary);
            padding: 12px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
            border-radius: 8px;
            margin: 2px 8px;
        }

        .dropdown-item:hover {
            background: linear-gradient(135deg, var(--primary-orange), var(--primary-dark));
            color: white;
            transform: translateX(5px);
        }

        .dropdown-item i {
            color: var(--primary-orange);
            width: 20px;
            text-align: center;
            margin-right: 10px;
        }

        .dropdown-item:hover i {
            color: white;
        }

        .dropdown-divider {
            border-color: rgba(255, 107, 53, 0.2);
            margin: 8px 0;
        }

        /* === USER DROPDOWN === */
        .user-dropdown .dropdown-toggle {
            background: rgba(255, 107, 53, 0.1);
            border: 2px solid rgba(255, 107, 53, 0.3);
            border-radius: 25px;
            padding: 8px 16px !important;
        }

        .user-dropdown .dropdown-toggle:hover {
            background: rgba(255, 107, 53, 0.2);
            border-color: var(--primary-orange);
            transform: none;
        }

        /* === CONTENU PRINCIPAL === */
        .acheteur-content {
            padding: 30px;
            margin-top: 20px;
            min-height: calc(100vh - 120px);
        }

        .modern-breadcrumb {
            background: white;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .breadcrumb-item + .breadcrumb-item::before {
            content: "›";
            color: var(--primary-orange);
            font-weight: bold;
        }

        /* === RESPONSIVE === */
        @media (max-width: 991.98px) {
            .navbar-nav {
                background: rgba(255,255,255,0.95);
                border-radius: 12px;
                padding: 15px;
                margin-top: 15px;
                backdrop-filter: blur(10px);
            }

            .navbar-nav .nav-link {
                color: var(--black-primary) !important;
                margin: 2px 0;
            }

            .navbar-nav .nav-link:hover {
                color: white !important;
                background: var(--primary-orange);
            }

            .acheteur-content {
                padding: 15px;
            }
        }

        /* === NOTIFICATION BADGE === */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .cart-badge {
            background: var(--primary-orange);
        }

        .favorite-badge {
            background: var(--danger);
        }

        /* === CARDS ET COMPONENTS === */
        .dashboard-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.08);
            padding: 30px;
            border-left: 4px solid var(--primary-orange);
            transition: all 0.3s ease;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 3px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border-top: 4px solid var(--primary-orange);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 107, 53, 0.15);
        }

        /* === BOUTONS ACHETEUR === */
        .btn-acheteur {
            background: linear-gradient(135deg, var(--primary-orange), var(--primary-dark));
            border: none;
            color: white;
            font-weight: 600;
            border-radius: 8px;
            padding: 12px 24px;
            transition: all 0.3s ease;
        }

        .btn-acheteur:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-orange));
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.4);
        }

        .btn-outline-acheteur {
            border: 2px solid var(--primary-orange);
            color: var(--primary-orange);
            background: transparent;
            font-weight: 600;
            border-radius: 8px;
            padding: 10px 22px;
            transition: all 0.3s ease;
        }

        .btn-outline-acheteur:hover {
            background: var(--primary-orange);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
        }

        /* === STYLE POUR LES ICÔNES DU PANIER === */
        .cart-count-sidebar {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--primary-orange);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- Navbar Acheteur -->
    <nav class="navbar navbar-expand-lg acheteur-navbar sticky-top">
        <div class="container-fluid">
            <!-- Brand -->
            <a class="navbar-brand" href="{{ route('acheteur.dashboard') }}">
                <i class="fas fa-user-circle"></i>
                <span>Mon Espace</span>
            </a>

            <!-- Toggle button -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#acheteurNavbar">
                <i class="fas fa-bars text-white"></i>
            </button>

            <!-- Navigation -->
            <div class="collapse navbar-collapse" id="acheteurNavbar">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('acheteur.dashboard') ? 'active' : '' }}" href="{{ route('acheteur.dashboard') }}">
                            <i class="fas fa-tachometer-alt"></i>Dashboard
                        </a>
                    </li>
                    
                    <!-- Dropdown Mes Billets -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('acheteur.tickets*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-ticket-alt"></i>Mes Billets
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('acheteur.tickets') }}">
                                <i class="fas fa-list"></i>Tous mes billets
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('acheteur.tickets', ['status' => 'upcoming']) }}">
                                <i class="fas fa-clock"></i>Événements à venir
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('acheteur.tickets', ['status' => 'past']) }}">
                                <i class="fas fa-history"></i>Événements passés
                            </a></li>
                        </ul>
                    </li>

                    <!-- Dropdown Mes Commandes -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('acheteur.orders*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-shopping-bag"></i>Mes Commandes
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('acheteur.orders') }}">
                                <i class="fas fa-list"></i>Toutes mes commandes
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('acheteur.orders', ['status' => 'paid']) }}">
                                <i class="fas fa-check-circle"></i>Commandes payées
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('acheteur.orders', ['status' => 'pending']) }}">
                                <i class="fas fa-clock"></i>En attente
                            </a></li>
                        </ul>
                    </li>

                    <!-- Favoris -->
                    <li class="nav-item">
                        <a class="nav-link position-relative {{ request()->routeIs('acheteur.favorites*') ? 'active' : '' }}" href="{{ route('acheteur.favorites') }}">
                            <i class="fas fa-heart"></i>Favoris
                            @if(isset($favoritesCount) && $favoritesCount > 0)
                                <span class="notification-badge favorite-badge">{{ $favoritesCount }}</span>
                            @endif
                        </a>
                    </li>

                    <!-- Séparateur -->
                    <li class="nav-item">
                        <hr class="nav-divider d-lg-none my-2">
                    </li>

                    <!-- Découvrir -->
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('home') }}">
                            <i class="fas fa-compass"></i>Découvrir
                        </a>
                    </li>

                    <!-- Tous les événements -->
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('events.all') }}">
                            <i class="fas fa-calendar-alt"></i>Événements
                        </a>
                    </li>

                    <!-- Panier -->
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="{{ route('cart.show') }}">
                            <i class="fas fa-shopping-cart"></i>Panier
                            <span id="navbar-cart-count" class="notification-badge cart-badge" style="display: none;">0</span>
                        </a>
                    </li>
                </ul>

                <!-- User menu -->
                <ul class="navbar-nav">
                    <li class="nav-item dropdown user-dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i>{{ Auth::user()->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('acheteur.profile') }}">
                                <i class="fas fa-user"></i>Mon profil
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('acheteur.profile') }}#settings">
                                <i class="fas fa-cog"></i>Paramètres
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('pages.support') }}" target="_blank">
                                <i class="fas fa-question-circle"></i>Aide & Support
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('pages.how-it-works') }}" target="_blank">
                                <i class="fas fa-info-circle"></i>Comment ça marche
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="{{ route('logout') }}"
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="fas fa-sign-out-alt"></i>Déconnexion
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenu Principal -->
    <div class="container-fluid acheteur-content">
        <!-- Breadcrumb -->
        @if(!request()->routeIs('acheteur.dashboard'))
        <nav class="modern-breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('acheteur.dashboard') }}" style="color: var(--primary-orange);">
                        <i class="fas fa-home me-1"></i>Mon Espace
                    </a>
                </li>
                @yield('breadcrumb')
            </ol>
        </nav>
        @endif

        <!-- Messages Flash -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>{{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i>{{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Contenu de la page -->
        @yield('content')
    </div>

    <!-- Form de déconnexion -->
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
    </form>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Scripts spécifiques à l'acheteur -->
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Mise à jour du compteur du panier
        function updateCartCount() {
            fetch('/cart/data')
                .then(response => response.json())
                .then(data => {
                    const cartBadge = document.getElementById('navbar-cart-count');
                    if (data.total_items > 0) {
                        cartBadge.textContent = data.total_items;
                        cartBadge.style.display = 'flex';
                    } else {
                        cartBadge.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.log('Erreur lors de la mise à jour du panier:', error);
                });
        }

        // Mise à jour du panier au chargement de la page
        document.addEventListener('DOMContentLoaded', updateCartCount);

        // Mise à jour du panier toutes les 30 secondes
        setInterval(updateCartCount, 30000);

        // Confirmation pour les actions destructives
        document.addEventListener('click', function(e) {
            if (e.target.matches('[data-confirm]')) {
                if (!confirm(e.target.getAttribute('data-confirm'))) {
                    e.preventDefault();
                }
            }
        });

        // Helper pour afficher des notifications toast
        function showToast(type, message) {
            const toastHtml = `
                <div class="toast align-items-center text-white bg-${type} border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle me-2"></i>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            // Créer le container de toasts s'il n'existe pas
            let toastContainer = document.querySelector('.toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
                toastContainer.style.zIndex = '1055';
                document.body.appendChild(toastContainer);
            }
            
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            
            // Activer le toast
            const toastElement = toastContainer.lastElementChild;
            const toast = new bootstrap.Toast(toastElement);
            toast.show();
            
            // Supprimer automatiquement après fermeture
            toastElement.addEventListener('hidden.bs.toast', function() {
                this.remove();
            });
        }

        // Fonction pour ajouter aux favoris (utilisée dans d'autres vues)
        window.toggleFavorite = function(eventId, button) {
            const isFavorited = button.classList.contains('favorited');
            const url = isFavorited ? `/acheteur/favorites/${eventId}` : `/acheteur/favorites/${eventId}`;
            const method = isFavorited ? 'DELETE' : 'POST';

            fetch(url, {
                method: method,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (isFavorited) {
                        button.classList.remove('favorited');
                        button.innerHTML = '<i class="far fa-heart"></i>';
                        showToast('info', 'Retiré des favoris');
                    } else {
                        button.classList.add('favorited');
                        button.innerHTML = '<i class="fas fa-heart text-danger"></i>';
                        showToast('success', 'Ajouté aux favoris');
                    }
                } else {
                    showToast('danger', data.message || 'Erreur lors de la mise à jour');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showToast('danger', 'Une erreur est survenue');
            });
        };

        // Initialisation des tooltips Bootstrap
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
    
    @stack('scripts')
</body>
{{-- resources/views/layouts/acheteur.blade.php - WordPress-style Sidebar --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Mon Espace') - {{ config('app.name') }}</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-orange: #FF6B35;
            --primary-dark: #E55A2B;
            --acheteur-primary: #2563eb;
            --acheteur-secondary: #1d4ed8;
            --black-primary: #1e293b;
            --black-secondary: #334155;
            --gray-light: #f8fafc;
            --gray-medium: #64748b;
            --sidebar-width: 280px;
            --sidebar-mini-width: 60px;
            --topbar-height: 60px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, sans-serif;
            background: var(--gray-light);
            color: #334155;
        }

        /* ========================= LAYOUT STRUCTURE ========================= */
        .acheteur-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* ========================= SIDEBAR ========================= */
        .acheteur-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--black-primary);
            color: #cbd5e1;
            transition: all 0.2s ease-in-out;
            z-index: 1000;
            overflow-x: hidden;
            overflow-y: auto;
        }

        .acheteur-sidebar.mini {
            width: var(--sidebar-mini-width);
        }

        .acheteur-sidebar.mobile-hidden {
            transform: translateX(-100%);
        }

        /* Sidebar Header */
        .sidebar-header {
            padding: 0;
            height: var(--topbar-height);
            background: var(--black-secondary);
            display: flex;
            align-items: center;
            border-bottom: 1px solid #475569;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            padding: 0 20px;
            text-decoration: none;
            color: #f1f5f9;
            font-weight: 600;
            font-size: 1.1rem;
            white-space: nowrap;
            overflow: hidden;
        }

        .sidebar-brand i {
            color: var(--acheteur-primary);
            font-size: 1.4rem;
            margin-right: 12px;
            flex-shrink: 0;
        }

        .sidebar-brand-text {
            transition: opacity 0.2s ease;
        }

        .acheteur-sidebar.mini .sidebar-brand-text {
            opacity: 0;
            width: 0;
        }

        /* Toggle Button */
        .sidebar-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-medium);
            cursor: pointer;
            padding: 5px;
            border-radius: 3px;
            transition: color 0.2s;
        }

        .sidebar-toggle:hover {
            color: var(--acheteur-primary);
        }

        .acheteur-sidebar.mini .sidebar-toggle {
            display: none;
        }

        /* Sidebar Menu */
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu > li {
            border-bottom: 1px solid #334155;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.2s ease;
            position: relative;
        }

        .sidebar-menu a:hover {
            background: var(--black-secondary);
            color: #60a5fa;
        }

        .sidebar-menu a.active {
            background: var(--acheteur-primary);
            color: white;
        }

        .sidebar-menu a.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--acheteur-secondary);
        }

        .sidebar-menu i {
            width: 20px;
            text-align: center;
            margin-right: 12px;
            flex-shrink: 0;
            font-size: 1.1rem;
        }

        .sidebar-menu-text {
            white-space: nowrap;
            overflow: hidden;
            transition: opacity 0.2s ease;
        }

        .acheteur-sidebar.mini .sidebar-menu-text {
            opacity: 0;
            width: 0;
        }

        /* Submenu */
        .sidebar-submenu {
            list-style: none;
            padding: 0;
            margin: 0;
            background: #0f172a;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .sidebar-submenu.open {
            max-height: 300px;
        }

        .sidebar-submenu a {
            padding: 10px 20px 10px 52px;
            font-size: 0.9rem;
            border-left: 3px solid transparent;
        }

        .sidebar-submenu a:hover {
            background: var(--black-secondary);
            border-left-color: var(--acheteur-primary);
        }

        .sidebar-submenu a.active {
            background: var(--black-secondary);
            border-left-color: var(--acheteur-primary);
            color: var(--acheteur-primary);
        }

        /* Chevron pour submenu */
        .menu-chevron {
            margin-left: auto;
            transition: transform 0.3s ease;
            color: var(--gray-medium);
            font-size: 0.8rem;
        }

        .submenu-parent.open .menu-chevron {
            transform: rotate(180deg);
            color: var(--acheteur-primary);
        }

        .sidebar-menu a:hover .menu-chevron {
            color: #60a5fa;
        }

        /* Mini sidebar hover expansion */
        .acheteur-sidebar.mini:hover {
            width: var(--sidebar-width);
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
        }

        .acheteur-sidebar.mini:hover .sidebar-brand-text,
        .acheteur-sidebar.mini:hover .sidebar-menu-text {
            opacity: 1;
            width: auto;
        }

        /* ========================= TOPBAR ========================= */
        .acheteur-topbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: var(--topbar-height);
            background: white;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 999;
            transition: left 0.2s ease-in-out;
        }

        .acheteur-sidebar.mini ~ .acheteur-topbar {
            left: var(--sidebar-mini-width);
        }

        .acheteur-sidebar.mobile-hidden ~ .acheteur-topbar {
            left: 0;
        }

        /* Mobile toggle button */
        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.2rem;
            color: var(--black-primary);
            cursor: pointer;
            padding: 8px;
            border-radius: 4px;
        }

        .mobile-toggle:hover {
            background: var(--gray-light);
        }

        /* Breadcrumb */
        .topbar-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--gray-medium);
            font-size: 0.9rem;
        }

        .topbar-breadcrumb a {
            color: var(--acheteur-primary);
            text-decoration: none;
        }

        /* Home button */
        .home-button {
            background: var(--acheteur-primary);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .home-button:hover {
            background: var(--acheteur-secondary);
            color: white;
            transform: translateY(-1px);
        }

        /* User menu */
        .topbar-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .topbar-notifications {
            position: relative;
            cursor: pointer;
            color: var(--gray-medium);
            font-size: 1.1rem;
        }

        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .cart-icon {
            position: relative;
            cursor: pointer;
            color: var(--gray-medium);
            font-size: 1.1rem;
            transition: color 0.2s;
        }

        .cart-icon:hover {
            color: var(--acheteur-primary);
        }

        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--primary-orange);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--acheteur-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .user-name {
            font-weight: 500;
            font-size: 0.9rem;
            color: var(--black-primary);
        }

        .user-role {
            font-size: 0.8rem;
            color: var(--gray-medium);
        }

        /* ========================= MAIN CONTENT ========================= */
        .acheteur-main {
            margin-left: var(--sidebar-width);
            margin-top: var(--topbar-height);
            padding: 20px;
            min-height: calc(100vh - var(--topbar-height));
            transition: margin-left 0.2s ease-in-out;
            width: calc(100% - var(--sidebar-width));
        }

        .acheteur-sidebar.mini ~ .acheteur-main {
            margin-left: var(--sidebar-mini-width);
            width: calc(100% - var(--sidebar-mini-width));
        }

        .acheteur-sidebar.mobile-hidden ~ .acheteur-main {
            margin-left: 0;
            width: 100%;
        }

        /* ========================= RESPONSIVE ========================= */
        @media (max-width: 1024px) {
            .acheteur-sidebar {
                width: var(--sidebar-mini-width);
            }
            
            .acheteur-topbar {
                left: var(--sidebar-mini-width);
            }
            
            .acheteur-main {
                margin-left: var(--sidebar-mini-width);
                width: calc(100% - var(--sidebar-mini-width));
            }
        }

        @media (max-width: 768px) {
            .acheteur-sidebar {
                transform: translateX(-100%);
                width: var(--sidebar-width);
            }
            
            .acheteur-sidebar.mobile-open {
                transform: translateX(0);
                box-shadow: 0 0 20px rgba(0,0,0,0.5);
            }
            
            .acheteur-sidebar.mobile-open .sidebar-brand-text,
            .acheteur-sidebar.mobile-open .sidebar-menu-text {
                opacity: 1 !important;
                width: auto !important;
            }
            
            .acheteur-sidebar.mobile-open .sidebar-submenu.open {
                max-height: 400px;
                display: block;
            }
            
            .acheteur-sidebar .menu-chevron {
                color: #cbd5e1 !important;
                font-size: 1rem !important;
                margin-left: auto;
            }
            
            .acheteur-sidebar .submenu-parent.open .menu-chevron {
                color: var(--acheteur-primary) !important;
            }
            
            .acheteur-topbar {
                left: 0;
            }
            
            .acheteur-main {
                margin-left: 0;
                width: 100%;
                padding: 15px;
            }
            
            .mobile-toggle {
                display: block;
            }
            
            .user-info {
                display: none;
            }
            
            .acheteur-sidebar:hover {
                width: var(--sidebar-width);
            }
            
            .sidebar-tooltip {
                display: none;
            }
        }

        /* ========================= CARDS AND COMPONENTS ========================= */
        .dashboard-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
            border-left: 4px solid var(--acheteur-primary);
        }

        .mobile-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            display: none;
        }

        @media (max-width: 768px) {
            .mobile-overlay.active {
                display: block;
            }
        }

        .sidebar-tooltip {
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            background: var(--black-secondary);
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 0.85rem;
            white-space: nowrap;
            margin-left: 10px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s;
            z-index: 1001;
        }

        .sidebar-tooltip::before {
            content: '';
            position: absolute;
            right: 100%;
            top: 50%;
            transform: translateY(-50%);
            border: 5px solid transparent;
            border-right-color: var(--black-secondary);
        }

        .acheteur-sidebar.mini .sidebar-menu a:hover .sidebar-tooltip {
            opacity: 1;
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <div class="acheteur-wrapper">
        <!-- Sidebar -->
        <nav class="acheteur-sidebar" id="acheteurSidebar">
            <!-- Sidebar Header -->
            <div class="sidebar-header">
                <a href="{{ route('acheteur.dashboard') }}" class="sidebar-brand">
                    <i class="fas fa-user-circle"></i>
                    <span class="sidebar-brand-text">Mon Espace</span>
                </a>
                <button class="sidebar-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-chevron-left"></i>
                </button>
            </div>

            <!-- Sidebar Menu -->
            <ul class="sidebar-menu">
                <!-- Dashboard -->
                <li>
                    <a href="{{ route('acheteur.dashboard') }}" class="{{ request()->routeIs('acheteur.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-tachometer-alt"></i>
                        <span class="sidebar-menu-text">Dashboard</span>
                        <span class="sidebar-tooltip">Dashboard</span>
                    </a>
                </li>

                <!-- Mes Billets -->
                <li class="submenu-parent {{ request()->routeIs('acheteur.tickets*') ? 'open' : '' }}">
                    <a href="#" onclick="toggleSubmenu('tickets', this)" class="{{ request()->routeIs('acheteur.tickets*') ? 'active' : '' }}">
                        <i class="fas fa-ticket-alt"></i>
                        <span class="sidebar-menu-text">Mes Billets</span>
                        <i class="fas fa-chevron-down menu-chevron"></i>
                        <span class="sidebar-tooltip">Mes Billets</span>
                    </a>
                    <ul class="sidebar-submenu {{ request()->routeIs('acheteur.tickets*') ? 'open' : '' }}" id="submenu-tickets">
                        <li><a href="{{ route('acheteur.tickets.index') }}" class="{{ request()->routeIs('acheteur.tickets.index') ? 'active' : '' }}">Tous mes billets</a></li>
                        <li><a href="{{ route('acheteur.tickets.upcoming') }}">Événements à venir</a></li>
                        <li><a href="{{ route('acheteur.tickets.past') }}">Historique</a></li>
                        <li><a href="{{ route('acheteur.tickets.favorites') }}">Favoris</a></li>
                    </ul>
                </li>

                <!-- Mes Commandes -->
                <li class="submenu-parent {{ request()->routeIs('acheteur.orders*') ? 'open' : '' }}">
                    <a href="#" onclick="toggleSubmenu('orders', this)" class="{{ request()->routeIs('acheteur.orders*') ? 'active' : '' }}">
                        <i class="fas fa-shopping-bag"></i>
                        <span class="sidebar-menu-text">Mes Commandes</span>
                        <i class="fas fa-chevron-down menu-chevron"></i>
                        <span class="sidebar-tooltip">Mes Commandes</span>
                    </a>
                    <ul class="sidebar-submenu {{ request()->routeIs('acheteur.orders*') ? 'open' : '' }}" id="submenu-orders">
                        <li><a href="{{ route('acheteur.orders.index') }}" class="{{ request()->routeIs('acheteur.orders.index') ? 'active' : '' }}">Toutes mes commandes</a></li>
                        <li><a href="{{ route('acheteur.orders.pending') }}">En cours</a></li>
                        <li><a href="{{ route('acheteur.orders.completed') }}">Terminées</a></li>
                        <li><a href="{{ route('acheteur.orders.invoices') }}">Factures</a></li>
                    </ul>
                </li>

                <!-- Événements -->
                <li class="submenu-parent {{ request()->routeIs('acheteur.events*') ? 'open' : '' }}">
                    <a href="#" onclick="toggleSubmenu('events', this)" class="{{ request()->routeIs('acheteur.events*') ? 'active' : '' }}">
                        <i class="fas fa-calendar-alt"></i>
                        <span class="sidebar-menu-text">Événements</span>
                        <i class="fas fa-chevron-down menu-chevron"></i>
                        <span class="sidebar-tooltip">Événements</span>
                    </a>
                    <ul class="sidebar-submenu {{ request()->routeIs('acheteur.events*') ? 'open' : '' }}" id="submenu-events">
                        <li><a href="{{ route('acheteur.events.browse') }}" class="{{ request()->routeIs('acheteur.events.browse') ? 'active' : '' }}">Parcourir les événements</a></li>
                        <li><a href="{{ route('acheteur.events.recommendations') }}">Recommandations</a></li>
                        <li><a href="{{ route('acheteur.events.categories') }}">Par catégorie</a></li>
                        <li><a href="{{ route('acheteur.events.calendar') }}">Calendrier</a></li>
                    </ul>
                </li>

                <!-- Mon Panier -->
                <li>
                    <a href="{{ route('cart.show') }}" class="{{ request()->routeIs('cart.*') ? 'active' : '' }}">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="sidebar-menu-text">Mon Panier</span>
                        <span class="sidebar-tooltip">Mon Panier</span>
                        @if(session('cart_count', 0) > 0)
                            <span class="cart-badge">{{ session('cart_count') }}</span>
                        @endif
                    </a>
                </li>

                <!-- Mes Favoris -->
                <li>
                    <a href="{{ route('acheteur.favorites') }}" class="{{ request()->routeIs('acheteur.favorites') ? 'active' : '' }}">
                        <i class="fas fa-heart"></i>
                        <span class="sidebar-menu-text">Mes Favoris</span>
                        <span class="sidebar-tooltip">Mes Favoris</span>
                    </a>
                </li>

                <!-- Mon Profil -->
                <li class="submenu-parent {{ request()->routeIs('acheteur.profile*') ? 'open' : '' }}">
                    <a href="#" onclick="toggleSubmenu('profile', this)" class="{{ request()->routeIs('acheteur.profile*') ? 'active' : '' }}">
                        <i class="fas fa-user-cog"></i>
                        <span class="sidebar-menu-text">Mon Profil</span>
                        <i class="fas fa-chevron-down menu-chevron"></i>
                        <span class="sidebar-tooltip">Mon Profil</span>
                    </a>
                    <ul class="sidebar-submenu {{ request()->routeIs('acheteur.profile*') ? 'open' : '' }}" id="submenu-profile">
                        <li><a href="{{ route('acheteur.profile.edit') }}" class="{{ request()->routeIs('acheteur.profile.edit') ? 'active' : '' }}">Informations personnelles</a></li>
                        <li><a href="{{ route('acheteur.profile.security') }}">Sécurité</a></li>
                        <li><a href="{{ route('acheteur.profile.notifications') }}">Notifications</a></li>
                        <li><a href="{{ route('acheteur.profile.preferences') }}">Préférences</a></li>
                    </ul>
                </li>

                <!-- Support -->
                <li>
                    <a href="{{ route('acheteur.support') }}" class="{{ request()->routeIs('acheteur.support') ? 'active' : '' }}">
                        <i class="fas fa-life-ring"></i>
                        <span class="sidebar-menu-text">Support</span>
                        <span class="sidebar-tooltip">Support</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Topbar -->
        <header class="acheteur-topbar">
            <div class="topbar-left">
                <button class="mobile-toggle" onclick="toggleMobileSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <nav class="topbar-breadcrumb">
                    <a href="{{ route('acheteur.dashboard') }}">
                        <i class="fas fa-home"></i>
                    </a>
                    @if(View::hasSection('breadcrumb'))
                        @yield('breadcrumb')
                    @endif
                </nav>
                <a href="{{ route('home') }}" class="home-button">
                    <i class="fas fa-globe"></i>
                    <span>Voir le site</span>
                </a>
            </div>

            <div class="topbar-user">
                <!-- Panier -->
                <a href="{{ route('cart.show') }}" class="cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                    @if(session('cart_count', 0) > 0)
                        <span class="cart-badge">{{ session('cart_count') }}</span>
                    @endif
                </a>

                <!-- Notifications -->
                <div class="topbar-notifications" onclick="toggleNotifications()">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">2</span>
                </div>

                <!-- User Info -->
                <div class="user-info">
                    <div class="user-name">{{ auth()->user()->name }}</div>
                    <div class="user-role">Acheteur</div>
                </div>

                <!-- User Avatar + Dropdown -->
                <div class="dropdown">
                    <div class="user-avatar" data-bs-toggle="dropdown" style="cursor: pointer;">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('acheteur.profile.edit') }}">
                            <i class="fas fa-user me-2"></i>Mon profil
                        </a></li>
                        <li><a class="dropdown-item" href="{{ route('acheteur.orders.index') }}">
                            <i class="fas fa-shopping-bag me-2"></i>Mes commandes
                        </a></li>
                        <li><a class="dropdown-item" href="{{ route('acheteur.tickets.index') }}">
                            <i class="fas fa-ticket-alt me-2"></i>Mes billets
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Mobile Overlay -->
        <div class="mobile-overlay" id="mobileOverlay" onclick="closeMobileSidebar()"></div>

        <!-- Main Content -->
        <main class="acheteur-main">
            <!-- Flash Messages -->
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>{{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <!-- Page Content -->
            @yield('content')
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Sidebar state management
        let sidebarState = localStorage.getItem('acheteur-sidebar-state') || 'open';
        
        // Initialize sidebar state
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('acheteurSidebar');
            
            if (window.innerWidth <= 1024 && window.innerWidth > 768) {
                sidebar.classList.add('mini');
                sidebarState = 'mini';
            } else if (window.innerWidth <= 768) {
                sidebar.classList.add('mobile-hidden');
                sidebarState = 'mobile-hidden';
            } else if (sidebarState === 'mini') {
                sidebar.classList.add('mini');
            }
        });

        // Toggle sidebar (desktop)
        function toggleSidebar() {
            const sidebar = document.getElementById('acheteurSidebar');
            
            if (sidebar.classList.contains('mini')) {
                sidebar.classList.remove('mini');
                sidebarState = 'open';
            } else {
                sidebar.classList.add('mini');
                sidebarState = 'mini';
            }
            
            localStorage.setItem('acheteur-sidebar-state', sidebarState);
        }

        // Toggle mobile sidebar
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('acheteurSidebar');
            const overlay = document.getElementById('mobileOverlay');
            
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
        }

        // Close mobile sidebar
        function closeMobileSidebar() {
            const sidebar = document.getElementById('acheteurSidebar');
            const overlay = document.getElementById('mobileOverlay');
            
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
        }

        // Toggle submenu
        function toggleSubmenu(menuId, element) {
            const submenu = document.getElementById('submenu-' + menuId);
            const parent = element.closest('li');
            const isOpen = submenu.classList.contains('open');
            
            const isMobile = window.innerWidth <= 768;
            
            if (!isMobile) {
                document.querySelectorAll('.sidebar-submenu').forEach(menu => {
                    if (menu !== submenu) {
                        menu.classList.remove('open');
                        menu.closest('li').classList.remove('open');
                    }
                });
            }
            
            if (isOpen) {
                submenu.classList.remove('open');
                parent.classList.remove('open');
            } else {
                submenu.classList.add('open');
                parent.classList.add('open');
            }
            
            return false;
        }

        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('acheteurSidebar');
            
            if (window.innerWidth <= 768) {
                sidebar.classList.add('mobile-hidden');
                sidebar.classList.remove('mini', 'mobile-open');
            } else if (window.innerWidth <= 1024) {
                sidebar.classList.add('mini');
                sidebar.classList.remove('mobile-hidden', 'mobile-open');
            } else {
                sidebar.classList.remove('mobile-hidden', 'mobile-open');
                if (sidebarState === 'open') {
                    sidebar.classList.remove('mini');
                }
            }
            
            document.getElementById('mobileOverlay').classList.remove('active');
        });

        // Notifications placeholder
        function toggleNotifications() {
            console.log('Toggle notifications');
        }
    </script>
    
    @stack('scripts')
</body>
</html>