{{-- resources/views/layouts/acheteur.blade.php - Layout optimisé mobile --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
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
            --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, sans-serif;
            background: var(--gray-light);
            color: #3c434a;
            overflow-x: hidden;
        }

        /* ========================= LAYOUT STRUCTURE ========================= */
        .acheteur-wrapper {
            display: flex;
            min-height: 100vh;
            position: relative;
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
            transition: var(--transition-smooth);
            z-index: 1000;
            overflow-x: hidden;
            overflow-y: auto;
        }

        .acheteur-sidebar.mini {
            width: var(--sidebar-mini-width);
        }

        /* Sidebar Header */
        .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 20px;
            background: var(--black-secondary);
            border-bottom: 1px solid #3c3f44;
            min-height: var(--topbar-height);
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #c3c4c7;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 600;
            transition: var(--transition-smooth);
        }

        .sidebar-brand:hover {
            color: white;
        }

        .sidebar-brand i {
            font-size: 1.4rem;
            color: var(--primary-orange);
        }

        .sidebar-brand-text {
            transition: var(--transition-smooth);
        }

        .acheteur-sidebar.mini .sidebar-brand-text {
            opacity: 0;
            width: 0;
            overflow: hidden;
        }

        .sidebar-toggle {
            background: none;
            border: none;
            color: #c3c4c7;
            padding: 8px;
            cursor: pointer;
            border-radius: 4px;
            transition: var(--transition-smooth);
        }

        .sidebar-toggle:hover {
            background: rgba(255, 107, 53, 0.1);
            color: var(--primary-orange);
        }

        /* Sidebar Menu */
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu > li {
            position: relative;
        }

        .sidebar-menu > li > a {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            color: #c3c4c7;
            text-decoration: none;
            transition: var(--transition-smooth);
            border-left: 3px solid transparent;
            position: relative;
        }

        .sidebar-menu > li > a:hover {
            background: rgba(255, 107, 53, 0.08);
            color: white;
            border-left-color: var(--primary-orange);
        }

        .sidebar-menu > li > a.active {
            background: rgba(255, 107, 53, 0.15);
            color: white;
            border-left-color: var(--primary-orange);
        }

        .sidebar-menu > li > a i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        .sidebar-menu-text {
            flex: 1;
            transition: var(--transition-smooth);
            white-space: nowrap;
        }

        .acheteur-sidebar.mini .sidebar-menu-text {
            opacity: 0;
            width: 0;
            overflow: hidden;
        }

        /* Submenu */
        .sidebar-submenu {
            list-style: none;
            padding: 0;
            margin: 0;
            max-height: 0;
            overflow: hidden;
            transition: var(--transition-smooth);
            background: var(--black-secondary);
        }

        .submenu-parent.open .sidebar-submenu {
            max-height: 400px;
        }

        .sidebar-submenu a {
            display: block;
            padding: 12px 20px 12px 60px;
            color: #a7aaad;
            text-decoration: none;
            transition: var(--transition-smooth);
            font-size: 0.95rem;
        }

        .sidebar-submenu a:hover,
        .sidebar-submenu a.active {
            background: rgba(255, 107, 53, 0.08);
            color: white;
        }

        .menu-chevron {
            transition: var(--transition-smooth);
            margin-left: auto;
            font-size: 0.9rem;
        }

        .submenu-parent.open .menu-chevron {
            transform: rotate(180deg);
        }

        .menu-badge {
            background: var(--primary-orange);
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: 600;
            margin-left: auto;
        }

        /* ========================= TOPBAR ========================= */
        .acheteur-topbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: var(--topbar-height);
            background: white;
            border-bottom: 1px solid #dcdcde;
            z-index: 999;
            transition: var(--transition-smooth);
            display: flex;
            align-items: center;
            padding: 0 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .acheteur-sidebar.mini + .acheteur-topbar {
            left: var(--sidebar-mini-width);
        }

        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            padding: 8px;
            color: var(--gray-medium);
            cursor: pointer;
            border-radius: 4px;
            margin-right: 15px;
            transition: var(--transition-smooth);
        }

        .mobile-toggle:hover {
            background: rgba(37, 99, 235, 0.1);
            color: var(--acheteur-primary);
        }

        .topbar-breadcrumb {
            flex: 1;
        }

        .breadcrumb {
            background: none;
            padding: 0;
            margin: 0;
            font-size: 0.9rem;
        }

        .breadcrumb-item + .breadcrumb-item::before {
            color: var(--gray-medium);
        }

        .breadcrumb-item.active {
            color: var(--acheteur-primary);
            font-weight: 500;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--acheteur-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            color: #3c434a;
            font-size: 0.9rem;
            line-height: 1.2;
        }

        .user-role {
            font-size: 0.75rem;
            color: var(--gray-medium);
            text-transform: uppercase;
        }

        /* ========================= MAIN CONTENT ========================= */
        .acheteur-main {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: var(--transition-smooth);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .acheteur-sidebar.mini + .acheteur-main {
            margin-left: var(--sidebar-mini-width);
        }

        .acheteur-content {
            flex: 1;
            padding: calc(var(--topbar-height) + 30px) 30px 30px;
            background: var(--gray-light);
        }

        /* ========================= MOBILE RESPONSIVE ========================= */
        @media (max-width: 768px) {
            .acheteur-sidebar {
                transform: translateX(-100%);
                width: var(--sidebar-width);
                box-shadow: 0 0 20px rgba(0,0,0,0.3);
            }
            
            .acheteur-sidebar.mobile-visible {
                transform: translateX(0);
            }

            .acheteur-main {
                margin-left: 0;
                width: 100%;
            }

            .acheteur-topbar {
                left: 0;
                width: 100%;
            }

            .mobile-toggle {
                display: block !important;
            }

            .topbar-breadcrumb {
                display: none;
            }

            .user-info {
                display: none;
            }

            .acheteur-content {
                padding: calc(var(--topbar-height) + 20px) 15px 20px;
            }
        }

        @media (max-width: 576px) {
            .acheteur-content {
                padding: calc(var(--topbar-height) + 15px) 10px 15px;
            }
        }

        /* ========================= CARDS ========================= */
        .dashboard-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 24px;
            border-left: 4px solid var(--acheteur-primary);
            margin-bottom: 24px;
            transition: var(--transition-smooth);
        }

        .dashboard-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-top: 4px solid var(--acheteur-primary);
            transition: var(--transition-smooth);
            height: 100%;
        }

        .stat-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        .stat-icon.primary {
            background: rgba(37, 99, 235, 0.1);
            color: var(--acheteur-primary);
        }

        .stat-icon.success {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }

        .stat-icon.warning {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }

        .stat-icon.info {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .stat-info h4 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: #1f2937;
        }

        .stat-info p {
            font-size: 0.9rem;
            margin: 0;
            color: var(--gray-medium);
        }

        /* ========================= MOBILE CARD OPTIMIZATION ========================= */
        @media (max-width: 768px) {
            .stat-card {
                padding: 16px;
                margin-bottom: 16px;
            }
            
            .stat-card .d-flex {
                flex-direction: row;
                text-align: left;
                gap: 12px;
            }
            
            .stat-icon {
                width: 40px;
                height: 40px;
                font-size: 1.1rem;
                flex-shrink: 0;
            }
            
            .stat-info h4 {
                font-size: 1.4rem;
                margin-bottom: 4px;
            }
            
            .stat-info p {
                font-size: 0.8rem;
                line-height: 1.2;
            }
            
            .dashboard-card {
                padding: 16px;
                margin-bottom: 16px;
                border-radius: 8px;
            }
        }

        @media (max-width: 576px) {
            .stat-card {
                padding: 12px;
            }
            
            .stat-card .d-flex {
                flex-direction: column;
                text-align: center;
                gap: 8px;
            }
            
            .stat-icon {
                margin: 0 auto;
            }
            
            .stat-info h4 {
                font-size: 1.2rem;
            }
        }

        /* ========================= BUTTONS ========================= */
        .btn-acheteur {
            background: var(--acheteur-primary);
            border: 1px solid var(--acheteur-primary);
            color: white;
            font-weight: 500;
            border-radius: 8px;
            padding: 10px 20px;
            transition: var(--transition-smooth);
            min-height: 44px; /* Touch-friendly */
        }

        .btn-acheteur:hover {
            background: var(--acheteur-secondary);
            border-color: var(--acheteur-secondary);
            color: white;
            transform: translateY(-1px);
        }

        .btn-outline-acheteur {
            border: 1px solid var(--acheteur-primary);
            color: var(--acheteur-primary);
            background: transparent;
            border-radius: 8px;
            min-height: 44px;
        }

        .btn-outline-acheteur:hover {
            background: var(--acheteur-primary);
            color: white;
        }

        /* ========================= MOBILE BUTTONS ========================= */
        @media (max-width: 768px) {
            .btn-acheteur,
            .btn-outline-acheteur {
                width: 100%;
                margin-bottom: 10px;
                padding: 12px 20px;
                font-size: 0.95rem;
            }
            
            .d-flex.justify-content-between {
                flex-direction: column;
                gap: 15px;
            }
            
            .d-flex.justify-content-between > div:last-child {
                text-align: center;
            }
        }

        /* ========================= MOBILE OVERLAY ========================= */
        .mobile-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition-smooth);
        }

        .mobile-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* ========================= ALERTS ========================= */
        .alert {
            border-radius: 8px;
            border-width: 1px;
            margin-bottom: 20px;
            padding: 16px 20px;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border-color: #22c55e;
            color: #15803d;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border-color: #ef4444;
            color: #dc2626;
        }

        /* ========================= TABLES MOBILE ========================= */
        @media (max-width: 768px) {
            .table-responsive {
                border: none;
                border-radius: 8px;
                overflow: hidden;
            }
            
            .table {
                font-size: 0.85rem;
                margin: 0;
            }
            
            .table th,
            .table td {
                padding: 8px 12px;
                vertical-align: middle;
            }
            
            .table thead {
                display: none;
            }
            
            .table tbody tr {
                display: block;
                margin-bottom: 16px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                padding: 16px;
            }
            
            .table tbody td {
                display: block;
                padding: 4px 0;
                border: none;
                position: relative;
                padding-left: 40%;
            }
            
            .table tbody td::before {
                content: attr(data-label);
                position: absolute;
                left: 0;
                width: 35%;
                font-weight: 600;
                color: var(--gray-medium);
                font-size: 0.8rem;
            }
        }

        /* ========================= UTILITY CLASSES ========================= */
        .text-orange {
            color: var(--primary-orange) !important;
        }

        .bg-orange {
            background-color: var(--primary-orange) !important;
        }

        .border-orange {
            border-color: var(--primary-orange) !important;
        }

        /* ========================= TOOLTIP SIDEBAR ========================= */
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

        @media (max-width: 768px) {
            .sidebar-tooltip {
                display: none;
            }
        }

        /* ========================= RESPONSIVE TEXT ========================= */
        @media (max-width: 768px) {
            h1 {
                font-size: 1.5rem;
            }
            
            h2 {
                font-size: 1.3rem;
            }
            
            h3 {
                font-size: 1.1rem;
            }
            
            .lead {
                font-size: 1rem;
            }
        }

        /* ========================= LOADING STATES ========================= */
        .loading-skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% {
                background-position: 200% 0;
            }
            100% {
                background-position: -200% 0;
            }
        }
        
    </style>
    
    @stack('styles')
</head>
<body>
    <div class="acheteur-wrapper">
        <!-- Mobile Overlay -->
        <div class="mobile-overlay" id="mobileOverlay" onclick="closeMobileSidebar()"></div>
        
        <!-- Sidebar -->
        <nav class="acheteur-sidebar" id="acheteurSidebar">
            <!-- Sidebar Header -->
            <div class="sidebar-header">
                <a href="{{ route('acheteur.dashboard') }}" class="sidebar-brand">
                    <i class="fas fa-user-circle"></i>
                    <span class="sidebar-brand-text">Mon Espace</span>
                </a>
                <button class="sidebar-toggle d-none d-md-block" onclick="toggleSidebar()">
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
                    <ul class="sidebar-submenu" id="submenu-tickets">
                        <li><a href="{{ route('acheteur.tickets') }}" class="{{ request()->routeIs('acheteur.tickets') && !request('status') ? 'active' : '' }}">Tous mes billets</a></li>
                        <li><a href="{{ route('acheteur.tickets', ['status' => 'active']) }}">Billets actifs</a></li>
                        <li><a href="{{ route('acheteur.tickets', ['status' => 'used']) }}">Billets utilisés</a></li>
                        <li><a href="{{ route('acheteur.tickets', ['status' => 'expired']) }}">Billets expirés</a></li>
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
                    <ul class="sidebar-submenu" id="submenu-orders">
                        <li><a href="{{ route('acheteur.orders') }}" class="{{ request()->routeIs('acheteur.orders') && !request('status') ? 'active' : '' }}">Toutes mes commandes</a></li>
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
                    <ul class="sidebar-submenu" id="submenu-settings">
                        <li><a href="{{ route('acheteur.profile') }}">Mon Profil</a></li>
                        <li><a href="#" onclick="showPasswordModal()">Changer mot de passe</a></li>
                        <li><a href="#" onclick="showNotificationSettings()">Notifications</a></li>
                    </ul>
                </li>

                <!-- Déconnexion -->
                <li style="border-top: 1px solid #3c3f44; margin-top: 20px; padding-top: 10px;">
                    <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" style="color: #e74c3c;">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="sidebar-menu-text">Déconnexion</span>
                        <span class="sidebar-tooltip">Déconnexion</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="acheteur-main">
            <!-- Top Bar -->
            <header class="acheteur-topbar">
                <button class="mobile-toggle" onclick="toggleMobileSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="topbar-breadcrumb">
                    @hasSection('breadcrumb')
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('acheteur.dashboard') }}">Dashboard</a>
                            </li>
                            @yield('breadcrumb')
                        </ol>
                    </nav>
                    @endif
                </div>
                
                <div class="user-info">
                    <div class="user-details">
                        <span class="user-name">{{ Auth::user()->name }}</span>
                        <span class="user-role">Acheteur</span>
                    </div>
                    <div class="user-avatar">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <main class="acheteur-content">
                <!-- Messages flash -->
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

                <!-- Page Content -->
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Logout Form -->
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
    </form>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // ========================= SIDEBAR FUNCTIONS =========================
        function toggleSidebar() {
            const sidebar = document.getElementById('acheteurSidebar');
            sidebar.classList.toggle('mini');
            
            // Sauvegarder l'état
            localStorage.setItem('acheteurSidebarMini', sidebar.classList.contains('mini'));
        }

        function toggleMobileSidebar() {
            const sidebar = document.getElementById('acheteurSidebar');
            const overlay = document.getElementById('mobileOverlay');
            const body = document.body;
            
            sidebar.classList.toggle('mobile-visible');
            overlay.classList.toggle('active');
            
            if (sidebar.classList.contains('mobile-visible')) {
                body.style.overflow = 'hidden';
            } else {
                body.style.overflow = '';
            }
        }

        function closeMobileSidebar() {
            const sidebar = document.getElementById('acheteurSidebar');
            const overlay = document.getElementById('mobileOverlay');
            const body = document.body;
            
            sidebar.classList.remove('mobile-visible');
            overlay.classList.remove('active');
            body.style.overflow = '';
        }

        function toggleSubmenu(menuId, element) {
            event.preventDefault();
            
            const submenu = document.getElementById('submenu-' + menuId);
            const parent = element.closest('.submenu-parent');
            
            // Fermer tous les autres sous-menus
            document.querySelectorAll('.submenu-parent').forEach(item => {
                if (item !== parent) {
                    item.classList.remove('open');
                }
            });
            
            // Toggle le sous-menu actuel
            parent.classList.toggle('open');
        }

        // ========================= CART FUNCTIONS =========================
        function updateCartCount() {
            fetch('{{ route("cart.data") }}')
                .then(response => response.json())
                .then(data => {
                    const cartCountElements = document.querySelectorAll('#sidebar-cart-count');
                    cartCountElements.forEach(element => {
                        if (data.count > 0) {
                            element.textContent = data.count;
                            element.style.display = 'inline-block';
                        } else {
                            element.style.display = 'none';
                        }
                    });
                })
                .catch(error => {
                    console.log('Erreur lors de la mise à jour du panier:', error);
                });
        }

        // ========================= TOUCH GESTURES =========================
        let touchStartX = 0;
        let touchEndX = 0;
        let touchStartY = 0;
        let touchEndY = 0;

        document.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
            touchStartY = e.changedTouches[0].screenY;
        }, { passive: true });

        document.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            touchEndY = e.changedTouches[0].screenY;
            handleSwipeGesture();
        }, { passive: true });

        function handleSwipeGesture() {
            const sidebar = document.getElementById('acheteurSidebar');
            const diffX = touchEndX - touchStartX;
            const diffY = touchEndY - touchStartY;
            
            // S'assurer que c'est un swipe horizontal
            if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
                if (window.innerWidth <= 768) {
                    if (diffX < -50 && sidebar.classList.contains('mobile-visible')) {
                        // Swipe vers la gauche = fermer
                        closeMobileSidebar();
                    } else if (diffX > 50 && !sidebar.classList.contains('mobile-visible') && touchStartX < 20) {
                        // Swipe vers la droite depuis le bord = ouvrir
                        toggleMobileSidebar();
                    }
                }
            }
        }

        // ========================= RESPONSIVE HANDLER =========================
        function handleResize() {
            const sidebar = document.getElementById('acheteurSidebar');
            const overlay = document.getElementById('mobileOverlay');
            const body = document.body;
            
            if (window.innerWidth > 768) {
                sidebar.classList.remove('mobile-visible');
                overlay.classList.remove('active');
                body.style.overflow = '';
            }
        }

        // ========================= PASSWORD MODAL =========================
        function showPasswordModal() {
            // Créer et afficher un modal pour changer le mot de passe
            const modalHtml = `
                <div class="modal fade" id="passwordModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Changer le mot de passe</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="passwordForm">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Mot de passe actuel</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">Nouveau mot de passe</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="button" class="btn btn-acheteur" onclick="submitPasswordChange()">Modifier</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modal = new bootstrap.Modal(document.getElementById('passwordModal'));
            modal.show();
            
            // Nettoyer le modal après fermeture
            document.getElementById('passwordModal').addEventListener('hidden.bs.modal', function() {
                this.remove();
            });
        }

        function submitPasswordChange() {
            const form = document.getElementById('passwordForm');
            const formData = new FormData(form);
            
            // Ici, vous pouvez ajouter la logique AJAX pour soumettre le changement de mot de passe
            // fetch('/acheteur/profile/password', { method: 'POST', body: formData })
            console.log('Changement de mot de passe à implémenter');
        }

        function showNotificationSettings() {
            // Modal pour les paramètres de notification
            alert('Paramètres de notification à implémenter');
        }

        // ========================= INITIALIZATION =========================
        document.addEventListener('DOMContentLoaded', function() {
            // Restaurer l'état de la sidebar
            const isMini = localStorage.getItem('acheteurSidebarMini') === 'true';
            if (isMini && window.innerWidth > 768) {
                document.getElementById('acheteurSidebar').classList.add('mini');
            }

            // Mise à jour initiale du panier
            updateCartCount();

            // Initialiser les tooltips Bootstrap
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Event listeners
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
        window.addEventListener('error', function(e) {
            console.error('Erreur JavaScript:', e.error);
        });

        // Prévenir les clics accidentels pendant les animations
        let isAnimating = false;
        document.addEventListener('click', function(e) {
            if (isAnimating) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    </script>
    
    @stack('scripts')
</body>
</html>