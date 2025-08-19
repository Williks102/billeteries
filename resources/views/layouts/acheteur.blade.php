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
</html>