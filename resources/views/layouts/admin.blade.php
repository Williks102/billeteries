{{-- resources/views/layouts/admin.blade.php - WordPress-style Sidebar --}}
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
            --black-primary: #1d2327;
            --black-secondary: #2c3338;
            --gray-light: #f0f0f1;
            --gray-medium: #8c8f94;
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
            color: #3c434a;
        }

        /* ========================= LAYOUT STRUCTURE ========================= */
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* ========================= SIDEBAR ========================= */
        .admin-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--black-primary);
            color: #c3c4c7;
            transition: all 0.2s ease-in-out;
            z-index: 1000;
            overflow-x: hidden;
            overflow-y: auto;
        }

        .admin-sidebar.mini {
            width: var(--sidebar-mini-width);
        }

        .admin-sidebar.mobile-hidden {
            transform: translateX(-100%);
        }

        /* Sidebar Header */
        .sidebar-header {
            padding: 0;
            height: var(--topbar-height);
            background: var(--black-secondary);
            display: flex;
            align-items: center;
            border-bottom: 1px solid #3c434a;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            padding: 0 20px;
            text-decoration: none;
            color: #f0f0f1;
            font-weight: 600;
            font-size: 1.1rem;
            white-space: nowrap;
            overflow: hidden;
        }

        .sidebar-brand i {
            color: var(--primary-orange);
            font-size: 1.4rem;
            margin-right: 12px;
            flex-shrink: 0;
        }

        .sidebar-brand-text {
            transition: opacity 0.2s ease;
        }

        .admin-sidebar.mini .sidebar-brand-text {
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
            color: #8c8f94;
            cursor: pointer;
            padding: 5px;
            border-radius: 3px;
            transition: color 0.2s;
        }

        .sidebar-toggle:hover {
            color: var(--primary-orange);
        }

        .admin-sidebar.mini .sidebar-toggle {
            display: none;
        }

        /* Sidebar Menu */
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu > li {
            border-bottom: 1px solid #2c3338;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #c3c4c7;
            text-decoration: none;
            transition: all 0.2s ease;
            position: relative;
        }

        .sidebar-menu a:hover {
            background: #2c3338;
            color: #72aee6;
        }

        .sidebar-menu a.active {
            background: var(--primary-orange);
            color: white;
        }

        .sidebar-menu a.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--primary-dark);
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

        .admin-sidebar.mini .sidebar-menu-text {
            opacity: 0;
            width: 0;
        }

        /* Submenu */
        .sidebar-submenu {
            list-style: none;
            padding: 0;
            margin: 0;
            background: #1a1e23;
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
            background: #2c3338;
            border-left-color: var(--primary-orange);
        }

        .sidebar-submenu a.active {
            background: #2c3338;
            border-left-color: var(--primary-orange);
            color: var(--primary-orange);
        }

        /* Chevron pour submenu */
        .menu-chevron {
            margin-left: auto;
            transition: transform 0.3s ease;
            color: #8c8f94;
            font-size: 0.8rem;
        }

        .submenu-parent.open .menu-chevron {
            transform: rotate(180deg);
            color: var(--primary-orange);
        }

        /* Meilleure visibilité du chevron */
        .sidebar-menu a:hover .menu-chevron {
            color: #72aee6;
        }

        /* Mini sidebar hover expansion */
        .admin-sidebar.mini:hover {
            width: var(--sidebar-width);
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
        }

        .admin-sidebar.mini:hover .sidebar-brand-text,
        .admin-sidebar.mini:hover .sidebar-menu-text {
            opacity: 1;
            width: auto;
        }

        /* ========================= TOPBAR ========================= */
        .admin-topbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: var(--topbar-height);
            background: white;
            border-bottom: 1px solid #dcdcde;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 999;
            transition: left 0.2s ease-in-out;
        }

        .admin-sidebar.mini ~ .admin-topbar {
            left: var(--sidebar-mini-width);
        }

        .admin-sidebar.mobile-hidden ~ .admin-topbar {
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

        /* Home button */
        .home-button {
            background: var(--gray-dark);
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
            background: var(--primary-dark);
            color: white;
            transform: translateY(-1px);
        }
        .topbar-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--gray-medium);
            font-size: 0.9rem;
        }

        .topbar-breadcrumb a {
            color: var(--primary-orange);
            text-decoration: none;
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
            background: #d63638;
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
            background: var(--primary-orange);
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
        .admin-main {
            margin-left: var(--sidebar-width);
            margin-top: var(--topbar-height);
            padding: 20px;
            min-height: calc(100vh - var(--topbar-height));
            transition: margin-left 0.2s ease-in-out;
            width: calc(100% - var(--sidebar-width));
        }

        .admin-sidebar.mini ~ .admin-main {
            margin-left: var(--sidebar-mini-width);
            width: calc(100% - var(--sidebar-mini-width));
        }

        .admin-sidebar.mobile-hidden ~ .admin-main {
            margin-left: 0;
            width: 100%;
        }

        /* ========================= RESPONSIVE ========================= */
        @media (max-width: 1024px) {
            .admin-sidebar {
                width: var(--sidebar-mini-width);
            }
            
            .admin-topbar {
                left: var(--sidebar-mini-width);
            }
            
            .admin-main {
                margin-left: var(--sidebar-mini-width);
                width: calc(100% - var(--sidebar-mini-width));
            }
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
                width: var(--sidebar-width); /* Sidebar complète sur mobile quand ouverte */
            }
            
            .admin-sidebar.mobile-open {
                transform: translateX(0);
                box-shadow: 0 0 20px rgba(0,0,0,0.5);
            }
            
            /* CORRECTION MOBILE : Forcer l'affichage complet */
            .admin-sidebar.mobile-open .sidebar-brand-text,
            .admin-sidebar.mobile-open .sidebar-menu-text {
                opacity: 1 !important;
                width: auto !important;
            }
            
            /* CORRECTION MOBILE : Submenus visibles */
            .admin-sidebar.mobile-open .sidebar-submenu.open {
                max-height: 400px;
                display: block;
            }
            
            /* CORRECTION MOBILE : Chevrons plus visibles */
            .admin-sidebar .menu-chevron {
                color: #c3c4c7 !important;
                font-size: 1rem !important;
                margin-left: auto;
            }
            
            .admin-sidebar .submenu-parent.open .menu-chevron {
                color: var(--primary-orange) !important;
            }
            
            .admin-topbar {
                left: 0;
            }
            
            .admin-main {
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
            
            /* Pas de hover effects sur mobile */
            .admin-sidebar:hover {
                width: var(--sidebar-width);
            }
            
            /* Désactiver les tooltips sur mobile */
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
            border: 1px solid #dcdcde;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid #dcdcde;
            border-left: 4px solid var(--primary-orange);
        }

        /* Mobile overlay */
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

        /* Tooltip pour mini sidebar */
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

        .admin-sidebar.mini .sidebar-menu a:hover .sidebar-tooltip {
            opacity: 1;
        }
        
    </style>
    
    @stack('styles')
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <nav class="admin-sidebar" id="adminSidebar">
            <!-- Sidebar Header -->
            <div class="sidebar-header">
                <a href="{{ route('admin.dashboard') }}" class="sidebar-brand">
                    <i class="fas fa-shield-alt"></i>
                    <span class="sidebar-brand-text">ClicBillet CI</span>
                </a>
                <button class="sidebar-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-chevron-left"></i>
                </button>
            </div>

            <!-- Sidebar Menu -->
            <ul class="sidebar-menu">
                <!-- Dashboard -->
                <li>
                    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-tachometer-alt"></i>
                        <span class="sidebar-menu-text">Dashboard</span>
                        <span class="sidebar-tooltip">Dashboard</span>
                    </a>
                </li>

                <!-- Utilisateurs -->
                <li class="submenu-parent {{ request()->routeIs('admin.users.*') ? 'open' : '' }}">
                    <a href="#" onclick="toggleSubmenu('users', this)" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <i class="fas fa-users"></i>
                        <span class="sidebar-menu-text">Utilisateurs</span>
                        <i class="fas fa-chevron-down menu-chevron"></i>
                        <span class="sidebar-tooltip">Utilisateurs</span>
                    </a>
                    <ul class="sidebar-submenu {{ request()->routeIs('admin.users.*') ? 'open' : '' }}" id="submenu-users">
                        <li><a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.index') ? 'active' : '' }}">Tous les utilisateurs</a></li>
                        <li><a href="{{ route('admin.users.create') }}" class="{{ request()->routeIs('admin.users.create') ? 'active' : '' }}">Ajouter un utilisateur</a></li>
                        <li><a href="{{ route('admin.users.index', ['role' => 'admin']) }}">Administrateurs</a></li>
                        <li><a href="{{ route('admin.users.index', ['role' => 'promoteur']) }}">Promoteurs</a></li>
                        <li><a href="{{ route('admin.users.index', ['role' => 'acheteur']) }}">Acheteurs</a></li>
                    </ul>
                </li>

                <!-- Événements -->
                <!-- Événements -->
                <li class="submenu-parent {{ request()->routeIs('admin.events.*') || request()->routeIs('admin.intervention-dashboard') ? 'open' : '' }}">
                    <a href="#" onclick="toggleSubmenu('events', this)" class="{{ request()->routeIs('admin.events.*') || request()->routeIs('admin.intervention-dashboard') ? 'active' : '' }}">
                        <i class="fas fa-calendar-check"></i>
                        <span class="sidebar-menu-text">Événements</span>
                        <i class="fas fa-chevron-down menu-chevron"></i>
                        <span class="sidebar-tooltip">Événements</span>
                    </a>
                    <ul class="sidebar-submenu {{ request()->routeIs('admin.events.*') || request()->routeIs('admin.intervention-dashboard') ? 'open' : '' }}" id="submenu-events">
                        <li><a href="{{ route('admin.events.index') }}" class="{{ request()->routeIs('admin.events.index') ? 'active' : '' }}">Tous les événements</a></li>
                        <li><a href="{{ route('admin.events.create') }}" class="{{ request()->routeIs('admin.events.create') ? 'active' : '' }}">Créer événement</a></li>
                        <li><a href="{{ route('admin.events.index', ['status' => 'pending']) }}">En attente</a></li>
                        <li><a href="{{ route('admin.events.index', ['status' => 'published']) }}">Publiés</a></li>
                        <li><a href="{{ route('admin.events.index', ['status' => 'cancelled']) }}">Annulés</a></li>

                        
                        <!-- Séparateur -->
                        <li style="margin-top: 8px; border-top: 1px solid #3c434a; padding-top: 8px;">
                            <a href="{{ route('admin.categories.index') }}">Catégories</a>
                        </li>
                    </ul>
                </li>

                <!-- Commandes -->
                <li class="submenu-parent {{ request()->routeIs('admin.orders.*') ? 'open' : '' }}">
                    <a href="#" onclick="toggleSubmenu('orders', this)" class="{{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                        <i class="fas fa-shopping-bag"></i>
                        <span class="sidebar-menu-text">Commandes</span>
                        <i class="fas fa-chevron-down menu-chevron"></i>
                        <span class="sidebar-tooltip">Commandes</span>
                    </a>
                    <ul class="sidebar-submenu {{ request()->routeIs('admin.orders.*') ? 'open' : '' }}" id="submenu-orders">
                        <li><a href="{{ route('admin.orders.index') }}" class="{{ request()->routeIs('admin.orders.index') ? 'active' : '' }}">Toutes les commandes</a></li>
                        <li><a href="{{ route('admin.orders.index', ['status' => 'pending']) }}">En attente</a></li>
                        <li><a href="{{ route('admin.orders.index', ['status' => 'paid']) }}">Payées</a></li>
                        <li><a href="{{ route('admin.orders.index', ['status' => 'failed']) }}">Échouées</a></li>
                        <li><a href="{{ route('admin.orders.index', ['status' => 'refunded']) }}">Remboursées</a></li>
                    </ul>
                </li>

                <!-- Tickets -->
                <li class="submenu-parent {{ request()->routeIs('admin.tickets.*') ? 'open' : '' }}">
                    <a href="#" onclick="toggleSubmenu('tickets', this)" class="{{ request()->routeIs('admin.tickets.*') ? 'active' : '' }}">
                        <i class="fas fa-ticket-alt"></i>
                        <span class="sidebar-menu-text">Tickets</span>
                        <i class="fas fa-chevron-down menu-chevron"></i>
                        <span class="sidebar-tooltip">Tickets</span>
                    </a>
                    <ul class="sidebar-submenu {{ request()->routeIs('admin.tickets.*') ? 'open' : '' }}" id="submenu-tickets">
                        <li><a href="{{ route('admin.tickets.index') }}" class="{{ request()->routeIs('admin.tickets.index') ? 'active' : '' }}">Tous les tickets</a></li>
                        <li><a href="{{ route('admin.tickets.index', ['status' => 'sold']) }}">Vendus</a></li>
                        <li><a href="{{ route('admin.tickets.index', ['status' => 'used']) }}">Utilisés</a></li>
                        <li><a href="{{ route('admin.tickets.index', ['status' => 'cancelled']) }}">Annulés</a></li>
                    </ul>
                </li>

                <!-- Finances -->
                <li>
                    <a href="{{ route('admin.commissions') }}" class="{{ request()->routeIs('admin.commissions') ? 'active' : '' }}">
                        <i class="fas fa-chart-line"></i>
                        <span class="sidebar-menu-text">Finances</span>
                        <span class="sidebar-tooltip">Finances</span>
                    </a>
                </li>

                <!-- Analytics -->
                <li>
                    <a href="{{ route('admin.analytics') }}" class="{{ request()->routeIs('admin.analytics') ? 'active' : '' }}">
                        <i class="fas fa-chart-bar"></i>
                        <span class="sidebar-menu-text">Analytics</span>
                        <span class="sidebar-tooltip">Analytics</span>
                    </a>
                </li>

                <!-- Paramètres -->
                <li class="submenu-parent {{ request()->routeIs('admin.settings.*') ? 'open' : '' }}">
                    <a href="#" onclick="toggleSubmenu('settings', this)" class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                        <i class="fas fa-cog"></i>
                        <span class="sidebar-menu-text">Paramètres</span>
                        <i class="fas fa-chevron-down menu-chevron"></i>
                        <span class="sidebar-tooltip">Paramètres</span>
                    </a>
                    <ul class="sidebar-submenu {{ request()->routeIs('admin.settings.*') ? 'open' : '' }}" id="submenu-settings">
                        <li><a href="{{ route('admin.profile') }}">Mon profil</a></li>
                        <li><a href="#">Emails</a></li>
                        <li><a href="#">Configuration</a></li>
                        <li><a href="#">Sauvegardes</a></li>
                    </ul>
                </li>
                <li>
                    <a href="{{ route('home') }}" class="home-button gap-2">
                    <i class="fas fa-globe"></i>
                    <span>Voir le site</span>
                </a>
                </li>
            </ul>
        </nav>

        <!-- Topbar -->
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="mobile-toggle" onclick="toggleMobileSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <nav class="topbar-breadcrumb">
                    <a href="{{ route('admin.dashboard') }}">
                        <i class="fas fa-home"></i>
                    </a>
                    @if(View::hasSection('breadcrumb'))
                        @yield('breadcrumb')
                    @endif
                </nav>
                
            </div>

            <div class="topbar-user">
                <!-- Notifications -->
                <div class="topbar-notifications" onclick="toggleNotifications()">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </div>

                <!-- User Info -->
                <div class="user-info">
                    <div class="user-name">{{ auth()->user()->name }}</div>
                    <div class="user-role">Administrateur</div>
                </div>

                <!-- User Avatar + Dropdown -->
                <div class="dropdown">
                    <div class="user-avatar" data-bs-toggle="dropdown" style="cursor: pointer;">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('admin.profile') }}">
                            <i class="fas fa-user me-2"></i>Mon profil
                        </a></li>
                        <li><a class="dropdown-item" href="#">
                            <i class="fas fa-cog me-2"></i>Paramètres
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
        <main class="admin-main">
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
        let sidebarState = localStorage.getItem('sidebar-state') || 'open';
        
        // Initialize sidebar state
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('adminSidebar');
            
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
            const sidebar = document.getElementById('adminSidebar');
            
            if (sidebar.classList.contains('mini')) {
                sidebar.classList.remove('mini');
                sidebarState = 'open';
            } else {
                sidebar.classList.add('mini');
                sidebarState = 'mini';
            }
            
            localStorage.setItem('sidebar-state', sidebarState);
        }

        // Toggle mobile sidebar
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            const overlay = document.getElementById('mobileOverlay');
            
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
        }

        // Close mobile sidebar
        function closeMobileSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            const overlay = document.getElementById('mobileOverlay');
            
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
        }

        // Toggle submenu
        function toggleSubmenu(menuId, element) {
            const submenu = document.getElementById('submenu-' + menuId);
            const parent = element.closest('li');
            const isOpen = submenu.classList.contains('open');
            
            // Sur mobile, ne pas fermer les autres submenus pour une meilleure UX
            const isMobile = window.innerWidth <= 768;
            
            if (!isMobile) {
                // Desktop : fermer les autres submenus
                document.querySelectorAll('.sidebar-submenu').forEach(menu => {
                    if (menu !== submenu) {
                        menu.classList.remove('open');
                        menu.closest('li').classList.remove('open');
                    }
                });
            }
            
            // Toggle current submenu
            if (isOpen) {
                submenu.classList.remove('open');
                parent.classList.remove('open');
            } else {
                submenu.classList.add('open');
                parent.classList.add('open');
            }
            
            // Empêcher la navigation
            return false;
        }

        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('adminSidebar');
            
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
            
            // Close mobile overlay
            document.getElementById('mobileOverlay').classList.remove('active');
        });

        // Notifications placeholder
        function toggleNotifications() {
            // Implement notifications dropdown
            console.log('Toggle notifications');
        }
    </script>
    
    @stack('scripts')
</body>
</html>