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
            color: #c3c4c7;
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
            color: var(--acheteur-primary);
            font-size: 1.5rem;
            margin-right: 10px;
            flex-shrink: 0;
        }

        .sidebar-brand-text {
            transition: all 0.2s ease-in-out;
        }

        .acheteur-sidebar.mini .sidebar-brand-text {
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
            color: var(--acheteur-primary);
        }

        .acheteur-sidebar.mini .sidebar-toggle i {
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
            background: var(--acheteur-primary);
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

        .acheteur-sidebar.mini .sidebar-menu-text {
            opacity: 0;
            transform: translateX(-20px);
        }

        .menu-chevron {
            font-size: 0.8rem;
            transition: transform 0.2s ease-in-out;
            margin-left: auto;
        }

        .acheteur-sidebar.mini .menu-chevron {
            opacity: 0;
        }

        /* Badge notifications */
        .menu-badge {
            background: var(--primary-orange);
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: auto;
            min-width: 18px;
            text-align: center;
            font-weight: bold;
        }

        .acheteur-sidebar.mini .menu-badge {
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
            border-left-color: var(--acheteur-primary);
        }

        .sidebar-submenu li a.active {
            color: var(--acheteur-primary);
            background: rgba(37, 99, 235, 0.1);
            border-left-color: var(--acheteur-primary);
        }

        .acheteur-sidebar.mini .sidebar-submenu {
            display: none;
        }

        /* ========================= TOPBAR ========================= */
        .acheteur-topbar {
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

        .acheteur-sidebar.mini + .acheteur-main .acheteur-topbar {
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
            color: var(--acheteur-primary);
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
            background: var(--primary-orange);
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
            background: var(--acheteur-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* ========================= MAIN CONTENT ========================= */
        .acheteur-main {
            margin-left: var(--sidebar-width);
            flex: 1;
            min-height: 100vh;
            transition: margin-left 0.2s ease-in-out;
        }

        .acheteur-sidebar.mini + .acheteur-main {
            margin-left: var(--sidebar-mini-width);
        }

        .acheteur-content {
            margin-top: var(--topbar-height);
            padding: 30px;
        }

        /* ========================= RESPONSIVE ========================= */
        @media (max-width: 768px) {
            .acheteur-sidebar {
                transform: translateX(-100%);
            }

            .acheteur-sidebar.mobile-visible {
                transform: translateX(0);
            }

            .acheteur-main {
                margin-left: 0;
            }

            .acheteur-topbar {
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

            .acheteur-content {
                padding: 15px;
            }
        }

        /* ========================= CARDS ========================= */
        .dashboard-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 24px;
            border-left: 4px solid var(--acheteur-primary);
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-top: 3px solid var(--acheteur-primary);
        }

        /* ========================= BUTTONS ========================= */
        .btn-acheteur {
            background: var(--acheteur-primary);
            border: 1px solid var(--acheteur-primary);
            color: white;
            font-weight: 500;
        }

        .btn-acheteur:hover {
            background: var(--acheteur-secondary);
            border-color: var(--acheteur-secondary);
            color: white;
        }

        .btn-outline-acheteur {
            border: 1px solid var(--acheteur-primary);
            color: var(--acheteur-primary);
            background: transparent;
        }

        .btn-outline-acheteur:hover {
            background: var(--acheteur-primary);
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
                        <li><a href="{{ route('acheteur.tickets') }}" class="{{ request()->routeIs('acheteur.tickets') && !request()->get('status') ? 'active' : '' }}">Tous mes billets</a></li>
                        <li><a href="{{ route('acheteur.tickets', ['status' => 'upcoming']) }}">Événements à venir</a></li>
                        <li><a href="{{ route('acheteur.tickets', ['status' => 'past']) }}">Événements passés</a></li>
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
                        <li><a href="{{ route('acheteur.orders') }}" class="{{ request()->routeIs('acheteur.orders') && !request()->get('status') ? 'active' : '' }}">Toutes mes commandes</a></li>
                        <li><a href="{{ route('acheteur.orders', ['status' => 'paid']) }}">Commandes payées</a></li>
                        <li><a href="{{ route('acheteur.orders', ['status' => 'pending']) }}">En attente</a></li>
                    </ul>
                </li>

                <!-- Panier -->
                <li>
                    <a href="{{ route('cart.show') }}" class="{{ request()->routeIs('cart.*') ? 'active' : '' }}">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="sidebar-menu-text">Panier</span>
                        <span id="sidebar-cart-count" class="menu-badge" style="display: none;">0</span>
                        <span class="sidebar-tooltip">Panier</span>
                    </a>
                </li>

                <!-- Favoris -->
                <li>
                    <a href="{{ route('acheteur.favorites') }}" class="{{ request()->routeIs('acheteur.favorites*') ? 'active' : '' }}">
                        <i class="fas fa-heart"></i>
                        <span class="sidebar-menu-text">Favoris</span>
                        @if(isset($favoritesCount) && $favoritesCount > 0)
                            <span class="menu-badge">{{ $favoritesCount }}</span>
                        @endif
                        <span class="sidebar-tooltip">Favoris</span>
                    </a>
                </li>

                <!-- Séparateur visuel -->
                <li style="border-top: 1px solid #3c3f44; margin: 10px 0; padding-top: 10px;">
                    <a href="{{ route('home') }}" target="_blank">
                        <i class="fas fa-compass"></i>
                        <span class="sidebar-menu-text">Découvrir</span>
                        <span class="sidebar-tooltip">Découvrir</span>
                    </a>
                </li>

                <!-- Tous les événements -->
                <li>
                    <a href="{{ route('events.all') }}" target="_blank">
                        <i class="fas fa-calendar-alt"></i>
                        <span class="sidebar-menu-text">Événements</span>
                        <span class="sidebar-tooltip">Événements</span>
                    </a>
                </li>

                <!-- Profil & Paramètres -->
                <li class="submenu-parent {{ request()->routeIs('acheteur.profile*') ? 'open' : '' }}">
                    <a href="#" onclick="toggleSubmenu('settings', this)" class="{{ request()->routeIs('acheteur.profile*') ? 'active' : '' }}">
                        <i class="fas fa-cog"></i>
                        <span class="sidebar-menu-text">Paramètres</span>
                        <i class="fas fa-chevron-down menu-chevron"></i>
                        <span class="sidebar-tooltip">Paramètres</span>
                    </a>
                    <ul class="sidebar-submenu {{ request()->routeIs('acheteur.profile*') ? 'open' : '' }}" id="submenu-settings">
                        <li><a href="{{ route('acheteur.profile') }}" class="{{ request()->routeIs('acheteur.profile') ? 'active' : '' }}">Mon profil</a></li>
                        <li><a href="{{ route('acheteur.profile') }}#settings">Paramètres</a></li>
                        <li><a href="{{ route('pages.support') }}" target="_blank">Aide & Support</a></li>
                        <li><a href="{{ route('pages.how-it-works') }}" target="_blank">Comment ça marche</a></li>
                    </ul>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="acheteur-main">
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
                </div>

                <div class="topbar-user">
                    <!-- Panier dans topbar -->
                    <div class="topbar-notifications">
                        <a href="{{ route('cart.show') }}" style="color: inherit; text-decoration: none;">
                            <i class="fas fa-shopping-cart"></i>
                            <span id="topbar-cart-count" class="notification-badge" style="display: none;">0</span>
                        </a>
                    </div>

                    <!-- Notifications -->
                    <div class="topbar-notifications" onclick="toggleNotifications()">
                        <i class="fas fa-bell"></i>
                        @if(isset($notificationsCount) && $notificationsCount > 0)
                            <span class="notification-badge">{{ $notificationsCount }}</span>
                        @endif
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
                            <li><a class="dropdown-item" href="{{ route('acheteur.profile') }}">
                                <i class="fas fa-user me-2"></i>Mon profil
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('acheteur.profile') }}#settings">
                                <i class="fas fa-cog me-2"></i>Paramètres
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('home') }}" target="_blank">
                                <i class="fas fa-compass me-2"></i>Découvrir
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('pages.support') }}" target="_blank">
                                <i class="fas fa-question-circle me-2"></i>Aide & Support
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('pages.how-it-works') }}" target="_blank">
                                <i class="fas fa-info-circle me-2"></i>Comment ça marche
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
            <div class="acheteur-content">
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
            const sidebar = document.getElementById('acheteurSidebar');
            sidebar.classList.toggle('mini');
            
            // Sauvegarder l'état
            localStorage.setItem('acheteurSidebarMini', sidebar.classList.contains('mini'));
        }

        // Toggle Mobile Sidebar
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('acheteurSidebar');
            const overlay = document.getElementById('mobileOverlay');
            
            sidebar.classList.toggle('mobile-visible');
            overlay.classList.toggle('active');
        }

        // Close Mobile Sidebar
        function closeMobileSidebar() {
            const sidebar = document.getElementById('acheteurSidebar');
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

        // Mise à jour du compteur du panier
        function updateCartCount() {
            fetch('/cart/data')
                .then(response => response.json())
                .then(data => {
                    const sidebarCartBadge = document.getElementById('sidebar-cart-count');
                    const topbarCartBadge = document.getElementById('topbar-cart-count');
                    
                    if (data.total_items > 0) {
                        // Sidebar badge
                        if (sidebarCartBadge) {
                            sidebarCartBadge.textContent = data.total_items;
                            sidebarCartBadge.style.display = 'inline-block';
                        }
                        // Topbar badge
                        if (topbarCartBadge) {
                            topbarCartBadge.textContent = data.total_items;
                            topbarCartBadge.style.display = 'flex';
                        }
                    } else {
                        if (sidebarCartBadge) sidebarCartBadge.style.display = 'none';
                        if (topbarCartBadge) topbarCartBadge.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.log('Erreur lors de la mise à jour du panier:', error);
                });
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

        // Helper pour les confirmations
        function confirmAction(message, callback) {
            if (confirm(message)) {
                callback();
            }
        }

        // Initialisation au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            // Restaurer l'état de la sidebar
            const isMini = localStorage.getItem('acheteurSidebarMini') === 'true';
            if (isMini) {
                document.getElementById('acheteurSidebar').classList.add('mini');
            }

            // Mise à jour initiale du panier
            updateCartCount();

            // Initialiser les tooltips Bootstrap (si utilisés)
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Gestion responsive
            function handleResize() {
                const sidebar = document.getElementById('acheteurSidebar');
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

        // Mise à jour du panier toutes les 30 secondes
        setInterval(updateCartCount, 30000);

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
        window.AcheteurLayout = {
            toggleSidebar,
            toggleMobileSidebar,
            closeMobileSidebar,
            toggleSubmenu,
            showToast,
            confirmAction,
            makeRequest,
            updateCartCount,
            toggleFavorite: window.toggleFavorite
        };
    </script>
    
    @stack('scripts')
</body>
</html>