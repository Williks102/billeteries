<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Administration') - ClicBillet CI</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    @stack('styles')
    
    <style>
        :root {
            --admin-primary: #FF6B35;

            --admin-secondary: #011127ff;
            --admin-success: #059669;
            --admin-warning: #d97706;
            --admin-danger: #dc2626;
            --admin-info: #0891b2;
        }
        
        .navbar-admin {
            background: linear-gradient(135deg, var(--admin-secondary) 0%, #011127ff 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-admin .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: white !important;
        }
        
        .navbar-admin .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            padding: 0.75rem 1rem !important;
            border-radius: 0.375rem;
            margin: 0 0.25rem;
            transition: all 0.2s ease;
        }
        
        .navbar-admin .nav-link:hover {
            background-color: rgba(240, 125, 59, 1);
            color: white !important;
            transform: translateY(-1px);
        }
        
        .navbar-admin .nav-link.active {
            background-color: #FF6B35
            color: white !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            border-radius: 0.5rem;
            padding: 0.5rem 0;
        }
        
        .dropdown-item {
            padding: 0.75rem 1.5rem;
            transition: all 0.2s ease;
            border-radius: 0;
        }
        
        .dropdown-item:hover {
            background-color: var(--admin-primary);
            color: white;
            transform: translateX(5px);
        }
        
        .dropdown-item i {
            width: 20px;
            margin-right: 10px;
        }
        
        .content-wrapper {
            min-height: calc(100vh - 76px);
            background-color: #f8fafc;
        }
        
        .badge-notification {
            position: absolute;
            top: 0.25rem;
            right: 0.25rem;
            background-color: var(--admin-warning);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.65rem;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .navbar-text {
            color: rgba(255,255,255,0.8) !important;
            font-size: 0.9rem;
        }
        
        .btn-admin-logout {
            background-color: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            transition: all 0.2s ease;
        }
        
        .btn-admin-logout:hover {
            background-color: rgba(255,255,255,0.2);
            color: white;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <!-- Navigation Admin -->
    <nav class="navbar navbar-expand-lg navbar-admin sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('admin.dashboard') }}">
                <i class="fas fa-tachometer-alt me-2"></i>
                ClicBillet CI
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdmin">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarAdmin">
                <ul class="navbar-nav me-auto">
                    
                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>

                    <!-- Dropdown Utilisateurs -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle position-relative {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">
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
                                <i class="fas fa-user"></i>Acheteurs
                            </a></li>
                        </ul>
                    </li>

                    <!-- Dropdown Événements -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle position-relative {{ request()->routeIs('admin.events.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-calendar-alt"></i>Événements
                            @if(\App\Models\Event::where('status', 'pending')->count() > 0)
                            <span class="badge-notification">{{ \App\Models\Event::where('status', 'pending')->count() }}</span>
                            @endif
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('admin.events.index') }}">
                                <i class="fas fa-list"></i>Tous les événements
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.events.index', ['status' => 'pending']) }}">
                                <i class="fas fa-clock"></i>En attente d'approbation
                                @if(\App\Models\Event::where('status', 'pending')->count() > 0)
                                <span class="badge bg-warning ms-2">{{ \App\Models\Event::where('status', 'pending')->count() }}</span>
                                @endif
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.events.index', ['status' => 'published']) }}">
                                <i class="fas fa-check-circle"></i>Événements publiés
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.events.create') }}">
                                <i class="fas fa-plus"></i>Nouvel événement
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('admin.categories.index') }}">
                                <i class="fas fa-tags"></i>Catégories
                            </a></li>
                        </ul>
                    </li>

                    <!-- Dropdown Billets -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle position-relative {{ request()->routeIs('admin.tickets.*', 'admin.orders.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-ticket-alt"></i>Billets
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('admin.tickets.index') }}">
                                <i class="fas fa-ticket-alt"></i>Tous les billets
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.orders.index') }}">
                                <i class="fas fa-shopping-cart"></i>Commandes
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('admin.tickets.index', ['status' => 'used']) }}">
                                <i class="fas fa-check"></i>Billets utilisés
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.orders.index', ['payment_status' => 'pending']) }}">
                                <i class="fas fa-clock"></i>Commandes en attente
                            </a></li>
                        </ul>
                    </li>

                    <!-- Dropdown Finances -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.commissions', 'admin.revenues', 'admin.finances.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-coins"></i>Finances
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('admin.commissions') }}">
                                <i class="fas fa-percentage"></i>Commissions
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.revenues') }}">
                                <i class="fas fa-chart-line"></i>Revenus
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('admin.commissions', ['status' => 'pending']) }}">
                                <i class="fas fa-clock"></i>Commissions en attente
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.commissions', ['status' => 'paid']) }}">
                                <i class="fas fa-check-circle"></i>Commissions payées
                            </a></li>
                        </ul>
                    </li>

                    <!-- Dropdown Contenu -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.pages.*', 'admin.categories.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">
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

                    <!-- Dropdown Rapports (CORRIGÉ) -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.reports.*', 'admin.analytics') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-chart-pie"></i>Rapports
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('admin.reports.index') }}">
                                <i class="fas fa-chart-line"></i>Vue d'ensemble
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.reports.sales') }}">
                                <i class="fas fa-shopping-cart"></i>Ventes
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.reports.financial') }}">
                                <i class="fas fa-coins"></i>Financier
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.reports.users') }}">
                                <i class="fas fa-users"></i>Utilisateurs
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.reports.events') }}">
                                <i class="fas fa-calendar"></i>Événements
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.analytics') }}">
                                <i class="fas fa-chart-bar"></i>Analytics
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('admin.users.export') }}">
                                <i class="fas fa-download"></i>Export Utilisateurs
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.events.export') }}">
                                <i class="fas fa-download"></i>Export Événements
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.orders.export') }}">
                                <i class="fas fa-download"></i>Export Commandes
                            </a></li>
                        </ul>
                    </li>

                    <!-- Paramètres -->
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.index') }}">
                            <i class="fas fa-cog"></i> Paramètres
                        </a>
                    </li>
                </ul>

                <!-- Menu utilisateur -->
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            {{ auth()->user()->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('admin.profile') }}">
                                <i class="fas fa-user"></i>Mon profil
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.settings.index') }}">
                                <i class="fas fa-cog"></i>Paramètres
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="{{ route('logout') }}" 
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="fas fa-sign-out-alt"></i>Déconnexion
                                </a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenu principal -->
    <div class="content-wrapper">
        <!-- Messages flash -->
        @if(session('success'))
        <div class="container-fluid pt-3">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="container-fluid pt-3">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        @endif

        @if(session('warning'))
        <div class="container-fluid pt-3">
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        @endif

        @if(session('info'))
        <div class="container-fluid pt-3">
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                {{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        @endif

        <!-- Breadcrumb (optionnel) -->
        @if(isset($breadcrumbs) && count($breadcrumbs) > 0)
        <div class="container-fluid py-2 border-bottom bg-white">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('admin.dashboard') }}">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    @foreach($breadcrumbs as $breadcrumb)
                        @if($loop->last)
                        <li class="breadcrumb-item active">{{ $breadcrumb['title'] }}</li>
                        @else
                        <li class="breadcrumb-item">
                            <a href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['title'] }}</a>
                        </li>
                        @endif
                    @endforeach
                </ol>
            </nav>
        </div>
        @endif

        <!-- Contenu de la page -->
        @yield('content')
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Scripts personnalisés -->
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Confirmation pour les actions de suppression
        document.addEventListener('click', function(e) {
            if (e.target.closest('a[href*="delete"]') || e.target.closest('button[type="submit"]')?.closest('form[method="POST"]')?.querySelector('input[name="_method"][value="DELETE"]')) {
                if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ?')) {
                    e.preventDefault();
                    return false;
                }
            }
        });

        // Animation smooth pour les liens anchor
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Mise à jour automatique des badges de notification
        setInterval(function() {
            // Ici vous pouvez ajouter du code pour mettre à jour les notifications
            // en temps réel via AJAX si nécessaire
        }, 60000); // Toutes les minutes
    </script>

    @stack('scripts')
</body>
</html>