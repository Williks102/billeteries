<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Administration') - {{ config('app.name') }}</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-orange: #FF6B35;
            --primary-dark: #E55A2B;
            --black-primary: #2c3e50;
            --black-secondary: #34495e;
            --gray-light: #f8f9fa;
            --gray-medium: #6c757d;
        }

        body {
            background-color: var(--gray-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* === NAVBAR MODERNE === */
        .admin-navbar {
            background: linear-gradient(135deg, var(--black-primary), var(--black-secondary));
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            backdrop-filter: blur(10px);
            border-bottom: 3px solid var(--primary-orange);
        }

        .admin-navbar .navbar-brand {
            color: white !important;
            font-weight: 700;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-navbar .navbar-brand i {
            color: var(--primary-orange);
            font-size: 1.6rem;
        }

        .admin-navbar .navbar-brand:hover {
            color: var(--primary-orange) !important;
            transform: scale(1.02);
            transition: all 0.3s ease;
        }

        /* === NAVIGATION MENU === */
        .navbar-nav .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            padding: 12px 20px !important;
            border-radius: 8px;
            margin: 0 5px;
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
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s;
        }

        .navbar-nav .nav-link:hover::before {
            left: 100%;
        }

        .navbar-nav .nav-link:hover {
            color: white !important;
            background: rgba(255, 107, 53, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
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
            background: rgba(255, 107, 53, 0.1);
            border: 2px solid var(--primary-orange);
            border-radius: 25px;
            padding: 8px 16px !important;
        }

        .user-dropdown .dropdown-toggle:hover {
            background: var(--primary-orange);
            transform: none;
        }

        /* === CONTENU PRINCIPAL === */
        .admin-content {
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

            .admin-content {
                padding: 15px;
            }
        }

        /* === NOTIFICATION BADGE === */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
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

        .position-relative {
            position: relative;
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
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- Navbar Admin -->
    <nav class="navbar navbar-expand-lg admin-navbar sticky-top">
        <div class="container-fluid">
            <!-- Brand -->
            <a class="navbar-brand" href="{{ route('admin.dashboard') }}">
                <i class="fas fa-shield-alt"></i>
                <span>Administration - ClicBillet CI</span>
            </a>

            <!-- Toggle button -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
                <i class="fas fa-bars text-white"></i>
            </button>

            <!-- Navigation -->
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                            <i class="fas fa-tachometer-alt"></i>Dashboard
                        </a>
                    </li>
                    
                    <!-- Dropdown Utilisateurs -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-users"></i>Utilisateurs
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('admin.users.index') }}">
                                <i class="fas fa-list"></i>Tous les utilisateurs
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.users.create') }}">
                                <i class="fas fa-user-plus"></i>Nouvel utilisateur
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('admin.users.index', ['role' => 'promoteur']) }}">
                                <i class="fas fa-user-tie"></i>Promoteurs
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.users.index', ['role' => 'acheteur']) }}">
                                <i class="fas fa-user-friends"></i>Acheteurs
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.users.index', ['role' => 'admin']) }}">
                                <i class="fas fa-user-shield"></i>Administrateurs
                            </a></li>
                        </ul>
                    </li>

                    <!-- Dropdown Événements -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.events.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-calendar-alt"></i>Événements
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('admin.events.index') }}">
                                <i class="fas fa-list"></i>Tous les événements
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.events.index', ['status' => 'pending']) }}">
                                <i class="fas fa-clock"></i>En attente d'approbation
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.events.index', ['status' => 'published']) }}">
                                <i class="fas fa-check-circle"></i>Événements publiés
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('admin.categories.index') }}">
                                <i class="fas fa-tags"></i>Catégories
                            </a></li>
                        </ul>
                    </li>

                    <!-- Dropdown Billets -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle position-relative {{ request()->routeIs('admin.tickets.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-ticket-alt"></i>Billets
                            @if(isset($pendingTickets) && $pendingTickets > 0)
                                <span class="notification-badge">{{ $pendingTickets }}</span>
                            @endif
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('admin.tickets.index') }}">
                                <i class="fas fa-eye"></i>Tous les billets
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.tickets.index', ['status' => 'valid']) }}">
                                <i class="fas fa-check-circle"></i>Billets valides
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.tickets.index', ['status' => 'used']) }}">
                                <i class="fas fa-stamp"></i>Billets utilisés
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.tickets.index', ['status' => 'cancelled']) }}">
                                <i class="fas fa-times-circle"></i>Billets annulés
                            </a></li>
                        </ul>
                    </li>

                    <!-- Dropdown Commandes -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-shopping-cart"></i>Commandes
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('admin.orders.index') }}">
                                <i class="fas fa-list"></i>Toutes les commandes
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.orders.index', ['status' => 'pending']) }}">
                                <i class="fas fa-clock"></i>En attente
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.orders.index', ['status' => 'paid']) }}">
                                <i class="fas fa-check"></i>Payées
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.orders.index', ['status' => 'cancelled']) }}">
                                <i class="fas fa-ban"></i>Annulées
                            </a></li>
                        </ul>
                    </li>

                    <!-- Dropdown Finances -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.finances.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-chart-line"></i>Finances
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('admin.finances.commissions') }}">
                                <i class="fas fa-percentage"></i>Commissions
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.finances.revenues') }}">
                                <i class="fas fa-money-bill-wave"></i>Revenus
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.finances.analytics') }}">
                                <i class="fas fa-chart-bar"></i>Analyses
                            </a></li>
                        </ul>
                    </li>

                    <!-- Dropdown Contenu -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.pages.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-file-alt"></i>Contenu
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('admin.pages.index') }}">
                                <i class="fas fa-file-alt"></i>Pages CMS
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.pages.create') }}">
                                <i class="fas fa-plus"></i>Nouvelle page
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('admin.categories.index') }}">
                                <i class="fas fa-folder"></i>Catégories
                            </a></li>
                        </ul>
                    </li>

                    <!-- Dropdown Rapports -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-chart-pie"></i>Rapports
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('admin.reports.index') }}">
                                <i class="fas fa-chart-line"></i>Vue d'ensemble
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.reports.sales') }}">
                                <i class="fas fa-shopping-cart"></i>Ventes
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.reports.users') }}">
                                <i class="fas fa-users"></i>Utilisateurs
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.reports.events') }}">
                                <i class="fas fa-calendar"></i>Événements
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('admin.reports.export', 'all') }}">
                                <i class="fas fa-download"></i>Exporter données
                            </a></li>
                        </ul>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.index') }}">
                            <i class="fas fa-cog"></i>Paramètres
                        </a>
                    </li>
                </ul>

                <!-- User menu -->
                <ul class="navbar-nav">
                    <li class="nav-item dropdown user-dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-shield"></i>{{ Auth::user()->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('admin.profile') }}">
                                <i class="fas fa-user"></i>Mon profil
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.settings.index') }}">
                                <i class="fas fa-cog"></i>Paramètres
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('home') }}" target="_blank">
                                <i class="fas fa-external-link-alt"></i>Voir le site
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
    <div class="container-fluid admin-content">
        <!-- Breadcrumb -->
        @if(!request()->routeIs('admin.dashboard'))
        <nav class="modern-breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('admin.dashboard') }}" style="color: var(--primary-orange);">
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

        <!-- Contenu de la page -->
        @yield('content')
    </div>

    <!-- Form de déconnexion -->
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
    </form>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Scripts spécifiques à l'admin -->
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

        // Mise à jour des badges de notification (exemple)
        function updateNotificationBadges() {
            // Ici vous pouvez faire un appel AJAX pour récupérer les nouvelles notifications
            // fetch('/admin/notifications/count')
            //     .then(response => response.json())
            //     .then(data => {
            //         // Mettre à jour les badges
            //     });
        }

        // Actualiser les notifications toutes les 5 minutes
        setInterval(updateNotificationBadges, 300000);
    </script>
    
    @stack('scripts')
</body>
</html>