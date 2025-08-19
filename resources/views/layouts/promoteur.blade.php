<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Espace Promoteur') - {{ config('app.name') }}</title>
    
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
            --black-primary: #2c3e50;
            --black-secondary: #34495e;
            --gray-light: #f8f9fa;
            --gray-medium: #6c757d;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
        }

        body {
            background-color: var(--gray-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* === NAVBAR PROMOTEUR === */
        .promoteur-navbar {
            background: linear-gradient(135deg, var(--primary-orange), var(--primary-dark));
            box-shadow: 0 4px 20px rgba(255, 107, 53, 0.3);
            backdrop-filter: blur(10px);
        }

        .promoteur-navbar .navbar-brand {
            color: white !important;
            font-weight: 700;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .promoteur-navbar .navbar-brand i {
            color: white;
            font-size: 1.6rem;
        }

        .promoteur-navbar .navbar-brand:hover {
            color: var(--accent-orange) !important;
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
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .navbar-nav .nav-link:hover::before {
            left: 100%;
        }

        .navbar-nav .nav-link:hover {
            color: white !important;
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .navbar-nav .nav-link.active {
            background: rgba(255, 255, 255, 0.25) !important;
            color: white !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
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
            min-width: 250px;
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
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 25px;
            padding: 8px 16px !important;
        }

        .user-dropdown .dropdown-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: white;
            transform: none;
        }

        /* === CONTENU PRINCIPAL === */
        .promoteur-content {
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

            .promoteur-content {
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

        .success-badge {
            background: var(--success);
        }

        .warning-badge {
            background: var(--warning);
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

        /* === BOUTONS PERSONNALISÉS === */
        .btn-orange {
            background: linear-gradient(135deg, var(--primary-orange), var(--primary-dark));
            border: none;
            color: white;
            font-weight: 600;
            border-radius: 8px;
            padding: 12px 24px;
            transition: all 0.3s ease;
        }

        .btn-orange:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-orange));
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.4);
        }

        .btn-outline-orange {
            border: 2px solid var(--primary-orange);
            color: var(--primary-orange);
            background: transparent;
            font-weight: 600;
            border-radius: 8px;
            padding: 10px 22px;
            transition: all 0.3s ease;
        }

        .btn-outline-orange:hover {
            background: var(--primary-orange);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- Navbar Promoteur -->
    <nav class="navbar navbar-expand-lg promoteur-navbar sticky-top">
        <div class="container-fluid">
            <!-- Brand -->
            <a class="navbar-brand" href="{{ route('promoteur.dashboard') }}">
                <i class="fas fa-ticket-alt"></i>
                <span>Espace Promoteur</span>
            </a>

            <!-- Toggle button -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#promoteurNavbar">
                <i class="fas fa-bars text-white"></i>
            </button>

            <!-- Navigation -->
            <div class="collapse navbar-collapse" id="promoteurNavbar">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('promoteur.dashboard') ? 'active' : '' }}" href="{{ route('promoteur.dashboard') }}">
                            <i class="fas fa-tachometer-alt"></i>Dashboard
                        </a>
                    </li>
                    
                    <!-- Dropdown Événements -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('promoteur.events.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-calendar-alt"></i>Mes Événements
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('promoteur.events.index') }}">
                                <i class="fas fa-list"></i>Tous mes événements
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('promoteur.events.create') }}">
                                <i class="fas fa-plus-circle"></i>Créer un événement
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('promoteur.events.index', ['status' => 'draft']) }}">
                                <i class="fas fa-edit"></i>Brouillons
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('promoteur.events.index', ['status' => 'pending']) }}">
                                <i class="fas fa-clock"></i>En attente
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('promoteur.events.index', ['status' => 'published']) }}">
                                <i class="fas fa-check-circle"></i>Publiés
                            </a></li>
                        </ul>
                    </li>

                    <!-- Dropdown Billets -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle position-relative" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-ticket-alt"></i>Billets
                            @if(isset($pendingScans) && $pendingScans > 0)
                                <span class="notification-badge warning-badge">{{ $pendingScans }}</span>
                            @endif
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('promoteur.scanner.index') }}">
                                <i class="fas fa-qrcode"></i>Scanner QR
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('promoteur.scanner.stats') }}">
                                <i class="fas fa-chart-bar"></i>Statistiques scanner
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('promoteur.scanner.recent') }}">
                                <i class="fas fa-history"></i>Scans récents
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('promoteur.scanner.search') }}">
                                <i class="fas fa-search"></i>Rechercher un billet
                            </a></li>
                        </ul>
                    </li>

                    <!-- Dropdown Ventes -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('promoteur.sales') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-chart-line"></i>Ventes
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('promoteur.sales') }}">
                                <i class="fas fa-shopping-cart"></i>Mes ventes
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('promoteur.sales', ['period' => 'today']) }}">
                                <i class="fas fa-calendar-day"></i>Ventes du jour
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('promoteur.sales', ['period' => 'week']) }}">
                                <i class="fas fa-calendar-week"></i>Cette semaine
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('promoteur.sales', ['period' => 'month']) }}">
                                <i class="fas fa-calendar"></i>Ce mois
                            </a></li>
                        </ul>
                    </li>

                    <!-- Dropdown Rapports -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('promoteur.reports*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-chart-pie"></i>Rapports
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('promoteur.reports') }}">
                                <i class="fas fa-chart-line"></i>Vue d'ensemble
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('promoteur.reports', ['type' => 'events']) }}">
                                <i class="fas fa-calendar"></i>Par événement
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('promoteur.reports', ['type' => 'financial']) }}">
                                <i class="fas fa-money-bill-wave"></i>Financier
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('promoteur.reports.export') }}">
                                <i class="fas fa-download"></i>Exporter données
                            </a></li>
                        </ul>
                    </li>
                </ul>

                <!-- User menu -->
                <ul class="navbar-nav">
                    <li class="nav-item dropdown user-dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-tie"></i>{{ Auth::user()->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('promoteur.profile') }}">
                                <i class="fas fa-user"></i>Mon profil
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('promoteur.profile') }}#settings">
                                <i class="fas fa-cog"></i>Paramètres
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('home') }}" target="_blank">
                                <i class="fas fa-external-link-alt"></i>Voir le site
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('pages.promoter-guide') }}" target="_blank">
                                <i class="fas fa-question-circle"></i>Guide promoteur
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
    <div class="container-fluid promoteur-content">
        <!-- Breadcrumb -->
        @if(!request()->routeIs('promoteur.dashboard'))
        <nav class="modern-breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('promoteur.dashboard') }}" style="color: var(--primary-orange);">
                        <i class="fas fa-home me-1"></i>Dashboard
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
    
    <!-- Scripts spécifiques au promoteur -->
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Confirmation pour les actions destructives
        document.addEventListener('click', function(e) {
            if (e.target.matches('[data-confirm]')) {
                if (!confirm(e.target.getAttribute('data-confirm'))) {
                    e.preventDefault();
                }
            }
        });

        // Mise à jour automatique des badges de notification
        function updateNotificationBadges() {
            // Appel AJAX pour récupérer les nouvelles notifications
            fetch('/promoteur/notifications/count')
                .then(response => response.json())
                .then(data => {
                    // Mettre à jour le badge des scans en attente
                    const scanBadge = document.querySelector('.notification-badge.warning-badge');
                    if (scanBadge && data.pending_scans !== undefined) {
                        if (data.pending_scans > 0) {
                            scanBadge.textContent = data.pending_scans;
                            scanBadge.style.display = 'flex';
                        } else {
                            scanBadge.style.display = 'none';
                        }
                    }
                })
                .catch(error => {
                    console.log('Erreur lors de la mise à jour des notifications:', error);
                });
        }

        // Actualiser les notifications toutes les 2 minutes
        setInterval(updateNotificationBadges, 120000);

        // Helper pour les confirmations
        function confirmAction(message, callback) {
            if (confirm(message)) {
                callback();
            }
        }

        // Helper pour afficher des toasts
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

        // Gestion des erreurs AJAX globales
        window.addEventListener('unhandledrejection', function(event) {
            console.error('Erreur non gérée:', event.reason);
            showToast('danger', 'Une erreur inattendue est survenue');
        });

        // Initialisation des tooltips Bootstrap (si utilisés)
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