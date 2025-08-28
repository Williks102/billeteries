{{-- resources/views/layouts/promoteur.blade.php - WordPress-style Sidebar --}}
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
        .promoteur-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* ========================= SIDEBAR ========================= */
        .promoteur-sidebar {
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

        .promoteur-sidebar.mini {
            width: var(--sidebar-mini-width);
        }

        .promoteur-sidebar.mobile-hidden {
            transform: translateX(-100%);
        }

        /* Sidebar Header */
        .sidebar-header {
            padding: 0;
            height: var(--topbar-height);
            background: var(--black-secondary);
            display: flex;
            align-items: center;
            border-bottom: 1px solid #3c3f44;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            padding: 0 20px;
            text-decoration: none;
            color: #f6f7f7;
            font-weight: 600;
            font-size: 1.1rem;
            white-space: nowrap;
            overflow: hidden;
        }

        .sidebar-brand i {
            color: var(--primary-orange);
            font-size: 1.5rem;
            margin-right: 10px;
            flex-shrink: 0;
        }

        .sidebar-brand-text {
            transition: all 0.2s ease-in-out;
        }

        .promoteur-sidebar.mini .sidebar-brand-text {
            opacity: 0;
            transform: translateX(-20px);
        }

        .sidebar-toggle {
            background: none;
            border: none;
            color: #c3c4c7;
            font-size: 1rem;
            padding: 8px;
            margin-left: auto;
            margin-right: 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
        }

        .sidebar-toggle:hover {
            background: rgba(255,255,255,0.1);
            color: var(--primary-orange);
        }

        .promoteur-sidebar.mini .sidebar-toggle i {
            transform: rotate(180deg);
        }

        /* Sidebar Menu */
        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin: 0;
        }

        .sidebar-menu > li > a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #c3c4c7;
            text-decoration: none;
            position: relative;
            transition: all 0.2s ease-in-out;
            border: none;
            width: 100%;
            background: none;
        }

        .sidebar-menu > li > a:hover {
            background: #2c3338;
            color: #ffffff;
        }

        .sidebar-menu > li > a.active {
            background: var(--primary-orange);
            color: #ffffff;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.2);
        }

        .sidebar-menu > li > a i {
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
            margin-right: 12px;
            flex-shrink: 0;
        }

        .sidebar-menu-text {
            flex: 1;
            white-space: nowrap;
            transition: all 0.2s ease-in-out;
        }

        .promoteur-sidebar.mini .sidebar-menu-text {
            opacity: 0;
            transform: translateX(-20px);
        }

        .menu-chevron {
            font-size: 0.8rem;
            transition: transform 0.2s ease-in-out;
            margin-left: auto;
        }

        .promoteur-sidebar.mini .menu-chevron {
            opacity: 0;
        }

        /* Submenu */
        .submenu-parent.open > a .menu-chevron {
            transform: rotate(180deg);
        }

        .sidebar-submenu {
            list-style: none;
            padding: 0;
            margin: 0;
            background: #23282d;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-in-out;
        }

        .sidebar-submenu.open {
            max-height: 500px;
        }

        .sidebar-submenu li a {
            display: block;
            padding: 10px 20px 10px 52px;
            color: #a7aaad;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s ease-in-out;
            border-left: 3px solid transparent;
        }

        .sidebar-submenu li a:hover {
            color: #ffffff;
            background: rgba(255,255,255,0.05);
            border-left-color: var(--primary-orange);
        }

        .sidebar-submenu li a.active {
            color: var(--primary-orange);
            background: rgba(255, 107, 53, 0.1);
            border-left-color: var(--primary-orange);
        }

        .promoteur-sidebar.mini .sidebar-submenu {
            display: none;
        }

        /* ========================= TOPBAR ========================= */
        .promoteur-topbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: var(--topbar-height);
            background: #ffffff;
            border-bottom: 1px solid #dcdcde;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            transition: left 0.2s ease-in-out;
            z-index: 999;
        }

        .promoteur-sidebar.mini + .promoteur-main .promoteur-topbar {
            left: var(--sidebar-mini-width);
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.2rem;
            color: #3c434a;
            padding: 8px;
            border-radius: 4px;
            cursor: pointer;
        }

        .mobile-toggle:hover {
            background: #f6f7f7;
        }

        .topbar-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #646970;
            font-size: 0.9rem;
        }

        .topbar-breadcrumb a {
            color: var(--primary-orange);
            text-decoration: none;
        }

        .topbar-breadcrumb a:hover {
            text-decoration: underline;
        }

        .topbar-breadcrumb i {
            font-size: 0.8rem;
        }

        /* Topbar Right */
        .topbar-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .topbar-notifications {
            position: relative;
            cursor: pointer;
            padding: 8px;
            border-radius: 4px;
            transition: background 0.2s;
        }

        .topbar-notifications:hover {
            background: #f6f7f7;
        }

        .topbar-notifications i {
            font-size: 1.1rem;
            color: #646970;
        }

        .notification-badge {
            position: absolute;
            top: 2px;
            right: 2px;
            background: #d63384;
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
            line-height: 1.2;
        }

        .user-info {
            text-align: right;
        }

        .user-name {
            font-weight: 600;
            color: #1d2327;
            font-size: 0.9rem;
        }

        .user-role {
            font-size: 0.8rem;
            color: #646970;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--primary-orange);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* ========================= MAIN CONTENT ========================= */
        .promoteur-main {
            margin-left: var(--sidebar-width);
            flex: 1;
            min-height: 100vh;
            transition: margin-left 0.2s ease-in-out;
        }

        .promoteur-sidebar.mini + .promoteur-main {
            margin-left: var(--sidebar-mini-width);
        }

        .promoteur-content {
            margin-top: var(--topbar-height);
            padding: 30px;
        }

        /* ========================= RESPONSIVE ========================= */
        @media (max-width: 768px) {
            .promoteur-sidebar {
                transform: translateX(-100%);
            }

            .promoteur-sidebar.mobile-visible {
                transform: translateX(0);
            }

            .promoteur-main {
                margin-left: 0;
            }

            .promoteur-topbar {
                left: 0;
            }

            .mobile-toggle {
                display: block;
            }

            .topbar-breadcrumb {
                display: none;
            }

            .user-info {
                display: none;
            }

            .promoteur-content {
                padding: 15px;
            }
        }

        /* ========================= CARDS ========================= */
        .dashboard-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 24px;
            border-left: 4px solid var(--primary-orange);
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-top: 3px solid var(--primary-orange);
        }

        /* ========================= BUTTONS ========================= */
        .btn-orange {
            background: var(--primary-orange);
            border: 1px solid var(--primary-orange);
            color: white;
            font-weight: 500;
        }

        .btn-orange:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            color: white;
        }

        .btn-outline-orange {
            border: 1px solid var(--primary-orange);
            color: var(--primary-orange);
            background: transparent;
        }

        .btn-outline-orange:hover {
            background: var(--primary-orange);
            color: white;
        }

        /* ========================= ALERTS ========================= */
        .alert {
            border-radius: 6px;
            border-width: 1px;
            margin-bottom: 20px;
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

        .promoteur-sidebar.mini .sidebar-menu a:hover .sidebar-tooltip {
            opacity: 1;
        }
        
    </style>
    
    @stack('styles')
</head>
<body>
    <div class="promoteur-wrapper">
        <!-- Sidebar -->
        <nav class="promoteur-sidebar" id="promoteurSidebar">
            <!-- Sidebar Header -->
            <div class="sidebar-header">
                <a href="{{ route('promoteur.dashboard') }}" class="sidebar-brand">
                    <i class="fas fa-ticket-alt"></i>
                    <span class="sidebar-brand-text">Espace Promoteur</span>
                </a>
                <button class="sidebar-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-chevron-left"></i>
                </button>
            </div>

            <!-- Sidebar Menu -->
            <ul class="sidebar-menu">
                <!-- Dashboard -->
                <li>
                    <a href="{{ route('promoteur.dashboard') }}" class="{{ request()->routeIs('promoteur.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-tachometer-alt"></i>
                        <span class="sidebar-menu-text">Dashboard</span>
                        <span class="sidebar-tooltip">Dashboard</span>
                    </a>
                </li>

                <!-- Mes Événements -->
                <li class="submenu-parent {{ request()->routeIs('promoteur.events.*') ? 'open' : '' }}">
                    <a href="#" onclick="toggleSubmenu('events', this)" class="{{ request()->routeIs('promoteur.events.*') ? 'active' : '' }}">
                        <i class="fas fa-calendar-alt"></i>
                        <span class="sidebar-menu-text">Mes Événements</span>
                        <i class="fas fa-chevron-down menu-chevron"></i>
                        <span class="sidebar-tooltip">Mes Événements</span>
                    </a>
                    <ul class="sidebar-submenu {{ request()->routeIs('promoteur.events.*') ? 'open' : '' }}" id="submenu-events">
                        <li><a href="{{ route('promoteur.events.index') }}" class="{{ request()->routeIs('promoteur.events.index') ? 'active' : '' }}">Tous mes événements</a></li>
                        <li><a href="{{ route('promoteur.events.create') }}" class="{{ request()->routeIs('promoteur.events.create') ? 'active' : '' }}">Créer un événement</a></li>
                        <li><a href="{{ route('promoteur.events.index', ['status' => 'draft']) }}">Brouillons</a></li>
                        <li><a href="{{ route('promoteur.events.index', ['status' => 'pending']) }}">En attente</a></li>
                        <li><a href="{{ route('promoteur.events.index', ['status' => 'published']) }}">Publiés</a></li>
                    </ul>
                </li>

                <!-- Billets -->
                <li class="submenu-parent {{ request()->routeIs('promoteur.scanner.*') || request()->routeIs('promoteur.tickets.*') ? 'open' : '' }}">
                    <a href="#" onclick="toggleSubmenu('tickets', this)" class="{{ request()->routeIs('promoteur.scanner.*') || request()->routeIs('promoteur.tickets.*') ? 'active' : '' }}">
                        <i class="fas fa-ticket-alt"></i>
                        <span class="sidebar-menu-text">Billets</span>
                        <i class="fas fa-chevron-down menu-chevron"></i>
                        <span class="sidebar-tooltip">Billets</span>
                    </a>
                    <ul class="sidebar-submenu {{ request()->routeIs('promoteur.scanner.*') || request()->routeIs('promoteur.tickets.*') ? 'open' : '' }}" id="submenu-tickets">
                        <li><a href="{{ route('promoteur.scanner.index') }}" class="{{ request()->routeIs('promoteur.scanner.index') ? 'active' : '' }}">Scanner QR</a></li>
                        <li><a href="{{ route('promoteur.scanner.stats') }}" class="{{ request()->routeIs('promoteur.scanner.stats') ? 'active' : '' }}">Statistiques scanner</a></li>
                        <li><a href="{{ route('promoteur.scanner.recent') }}" class="{{ request()->routeIs('promoteur.scanner.recent') ? 'active' : '' }}">Scans récents</a></li>
                        <li><a href="{{ route('promoteur.scanner.search') }}" class="{{ request()->routeIs('promoteur.scanner.search') ? 'active' : '' }}">Rechercher un billet</a></li>
                    </ul>
                </li>

                <!-- Ventes -->
                <li class="submenu-parent {{ request()->routeIs('promoteur.sales*') ? 'open' : '' }}">
                    <a href="#" onclick="toggleSubmenu('sales', this)" class="{{ request()->routeIs('promoteur.sales*') ? 'active' : '' }}">
                        <i class="fas fa-chart-line"></i>
                        <span class="sidebar-menu-text">Ventes</span>
                        <i class="fas fa-chevron-down menu-chevron"></i>
                        <span class="sidebar-tooltip">Ventes</span>
                    </a>
                    <ul class="sidebar-submenu {{ request()->routeIs('promoteur.sales*') ? 'open' : '' }}" id="submenu-sales">
                        <li><a href="{{ route('promoteur.sales') }}" class="{{ request()->routeIs('promoteur.sales') && !request()->get('period') ? 'active' : '' }}">Toutes mes ventes</a></li>
                        <li><a href="{{ route('promoteur.sales', ['period' => 'today']) }}">Ventes du jour</a></li>
                        <li><a href="{{ route('promoteur.sales', ['period' => 'week']) }}">Cette semaine</a></li>
                        <li><a href="{{ route('promoteur.sales', ['period' => 'month']) }}">Ce mois</a></li>
                    </ul>
                </li>

                <!-- Rapports -->
                <li class="submenu-parent {{ request()->routeIs('promoteur.reports*') ? 'open' : '' }}">
                    <a href="#" onclick="toggleSubmenu('reports', this)" class="{{ request()->routeIs('promoteur.reports*') ? 'active' : '' }}">
                        <i class="fas fa-chart-pie"></i>
                        <span class="sidebar-menu-text">Rapports</span>
                        <i class="fas fa-chevron-down menu-chevron"></i>
                        <span class="sidebar-tooltip">Rapports</span>
                    </a>
                    <ul class="sidebar-submenu {{ request()->routeIs('promoteur.reports*') ? 'open' : '' }}" id="submenu-reports">
                        <li><a href="{{ route('promoteur.reports') }}" class="{{ request()->routeIs('promoteur.reports') && !request()->get('type') ? 'active' : '' }}">Vue d'ensemble</a></li>
                        <li><a href="{{ route('promoteur.reports', ['type' => 'events']) }}">Par événement</a></li>
                        <li><a href="{{ route('promoteur.reports', ['type' => 'financial']) }}">Financier</a></li>
                        <li><a href="{{ route('promoteur.reports.export') }}" class="{{ request()->routeIs('promoteur.reports.export') ? 'active' : '' }}">Exporter données</a></li>
                    </ul>
                </li>

                <!-- Profil & Paramètres -->
                <li class="submenu-parent {{ request()->routeIs('promoteur.profile*') || request()->routeIs('promoteur.settings*') ? 'open' : '' }}">
                    <a href="#" onclick="toggleSubmenu('settings', this)" class="{{ request()->routeIs('promoteur.profile*') || request()->routeIs('promoteur.settings*') ? 'active' : '' }}">
                        <i class="fas fa-cog"></i>
                        <span class="sidebar-menu-text">Paramètres</span>
                        <i class="fas fa-chevron-down menu-chevron"></i>
                        <span class="sidebar-tooltip">Paramètres</span>
                    </a>
                    <ul class="sidebar-submenu {{ request()->routeIs('promoteur.profile*') || request()->routeIs('promoteur.settings*') ? 'open' : '' }}" id="submenu-settings">
                        <li><a href="{{ route('promoteur.profile') }}" class="{{ request()->routeIs('promoteur.profile') ? 'active' : '' }}">Mon profil</a></li>
                        <li><a href="{{ route('promoteur.profile') }}#settings">Paramètres</a></li>
                        <li><a href="{{ route('pages.promoter-guide') }}" target="_blank">Guide promoteur</a></li>
                    </ul>
                </li>

                <li>
                    <a href="{{ route('home') }}" target="_blank" class="home-button gap-2">
                        <i class="fas fa-globe"></i>
                        <span class="sidebar-menu-text">Voir le site</span>
                        <span class="sidebar-tooltip">Voir le site</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="promoteur-main">
            <!-- Topbar -->
            <header class="promoteur-topbar">
                <div class="topbar-left">
                    <button class="mobile-toggle" onclick="toggleMobileSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <nav class="topbar-breadcrumb">
                        <a href="{{ route('promoteur.dashboard') }}">
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
                        @if(isset($pendingScans) && $pendingScans > 0)
                            <span class="notification-badge">{{ $pendingScans }}</span>
                        @endif
                    </div>

                    <!-- User Info -->
                    <div class="user-info">
                        <div class="user-name">{{ auth()->user()->name }}</div>
                        <div class="user-role">Promoteur</div>
                    </div>

                    <!-- User Avatar + Dropdown -->
                    <div class="dropdown">
                        <div class="user-avatar" data-bs-toggle="dropdown" style="cursor: pointer;">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('promoteur.profile') }}">
                                <i class="fas fa-user me-2"></i>Mon profil
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('promoteur.profile') }}#settings">
                                <i class="fas fa-cog me-2"></i>Paramètres
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('home') }}" target="_blank">
                                <i class="fas fa-external-link-alt me-2"></i>Voir le site
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('pages.promoter-guide') }}" target="_blank">
                                <i class="fas fa-question-circle me-2"></i>Guide promoteur
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
            </header>

            <!-- Page Content -->
            <div class="promoteur-content">
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

                <!-- Content -->
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Mobile Overlay -->
    <div class="mobile-overlay" id="mobileOverlay" onclick="closeMobileSidebar()"></div>

    <!-- Form de déconnexion -->
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
    </form>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle Sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('promoteurSidebar');
            sidebar.classList.toggle('mini');
            
            // Sauvegarder l'état
            localStorage.setItem('promoteurSidebarMini', sidebar.classList.contains('mini'));
        }

        // Toggle Mobile Sidebar
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('promoteurSidebar');
            const overlay = document.getElementById('mobileOverlay');
            
            sidebar.classList.toggle('mobile-visible');
            overlay.classList.toggle('active');
        }

        // Close Mobile Sidebar
        function closeMobileSidebar() {
            const sidebar = document.getElementById('promoteurSidebar');
            const overlay = document.getElementById('mobileOverlay');
            
            sidebar.classList.remove('mobile-visible');
            overlay.classList.remove('active');
        }

        // Toggle Submenu
        function toggleSubmenu(menuId, element) {
            event.preventDefault();
            
            const submenu = document.getElementById('submenu-' + menuId);
            const parent = element.parentElement;
            
            // Toggle current submenu
            parent.classList.toggle('open');
            submenu.classList.toggle('open');
            
            // Close other submenus
            const otherSubmenus = document.querySelectorAll('.submenu-parent');
            otherSubmenus.forEach(item => {
                if (item !== parent && item.classList.contains('open')) {
                    item.classList.remove('open');
                    const otherSubmenu = item.querySelector('.sidebar-submenu');
                    if (otherSubmenu) {
                        otherSubmenu.classList.remove('open');
                    }
                }
            });
        }

        // Toggle Notifications (placeholder)
        function toggleNotifications() {
            // Implémentation des notifications à ajouter
            console.log('Toggle notifications');
        }

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
            fetch('/promoteur/notifications/count')
                .then(response => response.json())
                .then(data => {
                    const scanBadge = document.querySelector('.notification-badge');
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

        // Initialisation au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            // Restaurer l'état de la sidebar
            const isMini = localStorage.getItem('promoteurSidebarMini') === 'true';
            if (isMini) {
                document.getElementById('promoteurSidebar').classList.add('mini');
            }

            // Initialiser les tooltips Bootstrap (si utilisés)
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Gestion responsive
            function handleResize() {
                const sidebar = document.getElementById('promoteurSidebar');
                const overlay = document.getElementById('mobileOverlay');
                
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('mobile-visible');
                    overlay.classList.remove('active');
                }
            }

            window.addEventListener('resize', handleResize);
            handleResize(); // Appeler une fois au chargement

            // Fermer sidebar mobile avec Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeMobileSidebar();
                }
            });
        });

        // Gestion des erreurs AJAX globales
        window.addEventListener('unhandledrejection', function(event) {
            console.error('Erreur non gérée:', event.reason);
            showToast('danger', 'Une erreur inattendue est survenue');
        });

        // Fonction utilitaire pour les requêtes AJAX
        function makeRequest(url, options = {}) {
            const defaultOptions = {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            };

            return fetch(url, { ...defaultOptions, ...options })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .catch(error => {
                    console.error('Erreur AJAX:', error);
                    showToast('danger', 'Erreur de communication avec le serveur');
                    throw error;
                });
        }

        // Export des fonctions utilitaires pour utilisation dans d'autres scripts
        window.PromoteurLayout = {
            toggleSidebar,
            toggleMobileSidebar,
            closeMobileSidebar,
            toggleSubmenu,
            showToast,
            confirmAction,
            makeRequest,
            updateNotificationBadges
        };
    </script>
    
    @stack('scripts')
</body>
</html>