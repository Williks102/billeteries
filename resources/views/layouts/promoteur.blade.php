{{-- resources/views/layouts/promoteur.blade.php - VERSION CORRIGÉE MOBILE --}}
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
            transition: transform 0.3s ease-in-out;
            z-index: 1050;
            overflow-x: hidden;
            overflow-y: auto;
            transform: translateX(0);
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
            color: #ffffff;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            flex: 1;
            transition: padding 0.2s ease-in-out;
        }

        .sidebar-brand i {
            color: var(--primary-orange);
            margin-right: 10px;
            font-size: 1.3rem;
        }

        .sidebar-brand-text {
            transition: opacity 0.2s ease-in-out;
        }

        .sidebar-toggle {
            background: none;
            border: none;
            color: #c3c4c7;
            padding: 10px;
            margin-right: 10px;
            cursor: pointer;
            transition: transform 0.2s ease-in-out;
        }

        .sidebar-toggle:hover {
            color: white;
            transform: scale(1.1);
        }

        /* Menu Items */
        .sidebar-menu {
            list-style: none;
            padding: 10px 0;
            margin: 0;
        }

        .sidebar-menu > li {
            margin: 0;
        }

        .sidebar-menu > li > a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: #c3c4c7;
            text-decoration: none;
            transition: all 0.2s ease-in-out;
            border-left: 3px solid transparent;
            position: relative;
        }

        .sidebar-menu > li > a:hover {
            color: #ffffff;
            background: rgba(255,255,255,0.05);
            border-left-color: var(--primary-orange);
        }

        .sidebar-menu > li > a.active {
            color: var(--primary-orange);
            background: rgba(255, 107, 53, 0.1);
            border-left-color: var(--primary-orange);
        }

        .sidebar-menu > li > a i {
            width: 20px;
            text-align: center;
            margin-right: 12px;
            font-size: 1.1rem;
        }

        .sidebar-menu-text {
            transition: opacity 0.2s ease-in-out;
        }

        .menu-chevron {
            font-size: 0.8rem;
            transition: transform 0.2s ease-in-out;
            margin-left: auto;
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
            transition: left 0.3s ease-in-out;
            z-index: 1040;
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
            color: var(--black-primary);
            cursor: pointer;
            padding: 8px;
        }

        .mobile-toggle:hover {
            color: var(--primary-orange);
        }

        .topbar-breadcrumb {
            display: flex;
            align-items: center;
            color: var(--gray-medium);
            font-size: 0.9rem;
        }

        .topbar-breadcrumb i {
            margin: 0 8px;
            font-size: 0.8rem;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .notifications {
            position: relative;
            cursor: pointer;
            padding: 8px;
            color: var(--gray-medium);
            transition: color 0.2s;
        }

        .notifications:hover {
            color: var(--primary-orange);
        }

        .notification-count {
            position: absolute;
            top: 0;
            right: 0;
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

        .user-menu {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
            transition: background 0.2s;
        }

        .user-menu:hover {
            background: var(--gray-light);
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
            line-height: 1.2;
        }

        .user-role {
            font-size: 0.8rem;
            color: var(--gray-medium);
            line-height: 1.2;
        }

        /* ========================= MAIN CONTENT ========================= */
        .promoteur-main {
            margin-left: var(--sidebar-width);
            flex: 1;
            min-height: 100vh;
            transition: margin-left 0.3s ease-in-out;
        }

        .promoteur-content {
            margin-top: var(--topbar-height);
            padding: 30px;
            min-height: calc(100vh - var(--topbar-height));
        }

        /* ========================= MOBILE OVERLAY ========================= */
        .mobile-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1040;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }

        .mobile-overlay.active {
            display: block;
            opacity: 1;
        }

        /* ========================= RESPONSIVE DESIGN ========================= */
        
        /* Tablettes */
        @media (max-width: 1024px) {
            .promoteur-sidebar {
                width: var(--sidebar-mini-width);
            }
            
            .sidebar-brand-text,
            .sidebar-menu-text,
            .menu-chevron {
                opacity: 0;
            }
            
            .sidebar-submenu {
                display: none;
            }
            
            .promoteur-topbar {
                left: var(--sidebar-mini-width);
            }
            
            .promoteur-main {
                margin-left: var(--sidebar-mini-width);
            }
        }

        /* Mobile - Corrections importantes */
        @media (max-width: 768px) {
            /* Sidebar cachée par défaut sur mobile */
            .promoteur-sidebar {
                transform: translateX(-100%);
                width: var(--sidebar-width);
            }
            
            /* Sidebar visible quand active */
            .promoteur-sidebar.mobile-visible {
                transform: translateX(0);
            }
            
            /* Topbar occupe toute la largeur */
            .promoteur-topbar {
                left: 0;
            }
            
            /* Main content sans marge */
            .promoteur-main {
                margin-left: 0;
            }
            
            /* Bouton mobile toggle visible */
            .mobile-toggle {
                display: block;
            }
            
            /* Masquer breadcrumb sur mobile */
            .topbar-breadcrumb {
                display: none;
            }
            
            /* Masquer info utilisateur sur très petits écrans */
            .user-info {
                display: none;
            }
            
            /* Padding réduit sur mobile */
            .promoteur-content {
                padding: 15px;
            }
            
            .promoteur-topbar {
                padding: 0 15px;
            }
            
            /* Menu full width sur mobile */
            .sidebar-brand-text,
            .sidebar-menu-text,
            .menu-chevron {
                opacity: 1;
            }
            
            .sidebar-submenu {
                display: block;
            }
        }

        /* Très petits écrans */
        @media (max-width: 480px) {
            .promoteur-content {
                padding: 10px;
            }
            
            .promoteur-topbar {
                padding: 0 10px;
            }
            
            .topbar-right .notifications {
                display: none;
            }
        }

        /* ========================= UTILITAIRES ========================= */
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

        /* Cards */
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

        /* Alerts */
        .alert {
            border-radius: 6px;
            border-width: 1px;
            margin-bottom: 20px;
        }

        /* Tooltip pour mini sidebar */
        .sidebar-tooltip {
            position: absolute;
            left: calc(100% + 10px);
            top: 50%;
            transform: translateY(-50%);
            background: var(--black-secondary);
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 0.85rem;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s;
            z-index: 1060;
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

        @media (min-width: 1025px) {
            .promoteur-sidebar.mini .sidebar-menu > li > a:hover .sidebar-tooltip {
                opacity: 1;
            }
        }
        
    </style>
    
    @stack('styles')
</head>
<body>
    <div class="promoteur-wrapper">
        <!-- Mobile Overlay -->
        <div class="mobile-overlay" id="mobileOverlay" onclick="closeMobileSidebar()"></div>
        
        <!-- Sidebar -->
        <nav class="promoteur-sidebar" id="promoteurSidebar">
            <!-- Sidebar Header -->
            <div class="sidebar-header">
                <a href="{{ route('promoteur.dashboard') }}" class="sidebar-brand">
                    <i class="fas fa-ticket-alt"></i>
                    <span class="sidebar-brand-text">Espace Promoteur</span>
                </a>
                <button class="sidebar-toggle d-none d-lg-block" onclick="toggleSidebar()">
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
                        <li><a href="{{ route('promoteur.events.index', ['status' => 'published']) }}">Publiés</a></li>
                        <li><a href="{{ route('promoteur.events.index', ['status' => 'draft']) }}">Brouillons</a></li>
                    </ul>
                </li>

                <!-- Billets & Scanner -->
                <li class="submenu-parent {{ request()->routeIs('promoteur.scanner*') || request()->routeIs('promoteur.tickets.*') ? 'open' : '' }}">
                    <a href="#" onclick="toggleSubmenu('tickets', this)" class="{{ request()->routeIs('promoteur.scanner*') || request()->routeIs('promoteur.tickets.*') ? 'active' : '' }}">
                        <i class="fas fa-qrcode"></i>
                        <span class="sidebar-menu-text">Billets</span>
                        <i class="fas fa-chevron-down menu-chevron"></i>
                        <span class="sidebar-tooltip">Billets</span>
                    </a>
                    <ul class="sidebar-submenu {{ request()->routeIs('promoteur.scanner*') || request()->routeIs('promoteur.tickets.*') ? 'open' : '' }}" id="submenu-tickets">
                        <li><a href="{{ route('promoteur.scanner.index') }}" class="{{ request()->routeIs('promoteur.scanner.index') ? 'active' : '' }}">Scanner QR</a></li>
                        <li><a href="{{ route('promoteur.scanner.stats') }}" class="{{ request()->routeIs('promoteur.scanner.stats') ? 'active' : '' }}">Statistiques scanner</a></li>
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
                        <li><a href="{{ route('promoteur.sales') }}" class="{{ request()->routeIs('promoteur.sales') ? 'active' : '' }}">Toutes mes ventes</a></li>
                        <li><a href="{{ route('promoteur.sales', ['period' => 'today']) }}">Aujourd'hui</a></li>
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
                        <li><a href="{{ route('promoteur.reports') }}" class="{{ request()->routeIs('promoteur.reports') ? 'active' : '' }}">Vue d'ensemble</a></li>
                        <li><a href="{{ route('promoteur.reports', ['type' => 'events']) }}">Par événement</a></li>
                        <li><a href="{{ route('promoteur.reports', ['type' => 'financial']) }}">Financier</a></li>
                    </ul>
                </li>

                <!-- Profil -->
                <li>
                    <a href="{{ route('promoteur.profile') }}" class="{{ request()->routeIs('promoteur.profile*') ? 'active' : '' }}">
                        <i class="fas fa-user-circle"></i>
                        <span class="sidebar-menu-text">Mon Profil</span>
                        <span class="sidebar-tooltip">Mon Profil</span>
                    </a>
                </li>

                <!-- Déconnexion -->
                <li class="mt-auto">
                    <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="sidebar-menu-text">Déconnexion</span>
                        <span class="sidebar-tooltip">Déconnexion</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content Area -->
        <div class="promoteur-main">
            <!-- Top Bar -->
            <header class="promoteur-topbar">
                <div class="topbar-left">
                    <button class="mobile-toggle" onclick="toggleMobileSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <nav class="topbar-breadcrumb">
                        <i class="fas fa-home"></i>
                        <i class="fas fa-chevron-right"></i>
                        @yield('breadcrumb', 'Dashboard')
                    </nav>
                </div>

                <div class="topbar-right">
                    <div class="notifications" onclick="showNotifications()">
                        <i class="fas fa-bell"></i>
                        <span class="notification-count">3</span>
                    </div>

                    <div class="user-menu" onclick="toggleUserMenu()">
                        <div class="user-avatar">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <div class="user-info">
                            <div class="user-name">{{ auth()->user()->name }}</div>
                            <div class="user-role">Promoteur</div>
                        </div>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="promoteur-content">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        {{ session('warning') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <!-- Logout Form -->
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
    </form>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // ========================= SIDEBAR FUNCTIONALITY =========================
        
        // Variables globales
        let sidebarState = 'full'; // full, mini, mobile-hidden
        
        // Toggle sidebar desktop (mini/full)
        function toggleSidebar() {
            const sidebar = document.getElementById('promoteurSidebar');
            
            if (window.innerWidth > 1024) {
                if (sidebarState === 'full') {
                    sidebar.classList.add('mini');
                    sidebarState = 'mini';
                    localStorage.setItem('sidebarState', 'mini');
                } else {
                    sidebar.classList.remove('mini');
                    sidebarState = 'full';
                    localStorage.setItem('sidebarState', 'full');
                }
            }
        }
        
        // Toggle sidebar mobile (show/hide)
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('promoteurSidebar');
            const overlay = document.getElementById('mobileOverlay');
            
            if (window.innerWidth <= 768) {
                if (sidebar.classList.contains('mobile-visible')) {
                    closeMobileSidebar();
                } else {
                    openMobileSidebar();
                }
            }
        }
        
        // Ouvrir sidebar mobile
        function openMobileSidebar() {
            const sidebar = document.getElementById('promoteurSidebar');
            const overlay = document.getElementById('mobileOverlay');
            
            sidebar.classList.add('mobile-visible');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        // Fermer sidebar mobile
        function closeMobileSidebar() {
            const sidebar = document.getElementById('promoteurSidebar');
            const overlay = document.getElementById('mobileOverlay');
            
            sidebar.classList.remove('mobile-visible');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
        
        // Toggle submenu
        function toggleSubmenu(menuId, element) {
            const submenu = document.getElementById('submenu-' + menuId);
            const parent = element.closest('.submenu-parent');
            
            if (window.innerWidth > 1024 && sidebarState === 'mini') {
                return; // Pas de submenu en mode mini
            }
            
            if (submenu) {
                const isOpen = submenu.classList.contains('open');
                
                // Fermer tous les autres submenus
                document.querySelectorAll('.sidebar-submenu.open').forEach(sub => {
                    if (sub !== submenu) {
                        sub.classList.remove('open');
                        sub.closest('.submenu-parent').classList.remove('open');
                    }
                });
                
                // Toggle le submenu actuel
                if (isOpen) {
                    submenu.classList.remove('open');
                    parent.classList.remove('open');
                } else {
                    submenu.classList.add('open');
                    parent.classList.add('open');
                }
            }
        }
        
        // Gestion responsive au redimensionnement
        function handleResize() {
            const sidebar = document.getElementById('promoteurSidebar');
            const overlay = document.getElementById('mobileOverlay');
            
            if (window.innerWidth <= 768) {
                // Mode mobile
                sidebar.classList.remove('mini');
                closeMobileSidebar();
                sidebarState = 'mobile-hidden';
            } else if (window.innerWidth <= 1024) {
                // Mode tablette
                closeMobileSidebar();
                sidebar.classList.add('mini');
                sidebarState = 'mini';
            } else {
                // Mode desktop
                closeMobileSidebar();
                const savedState = localStorage.getItem('sidebarState');
                if (savedState === 'mini') {
                    sidebar.classList.add('mini');
                    sidebarState = 'mini';
                } else {
                    sidebar.classList.remove('mini');
                    sidebarState = 'full';
                }
            }
        }
        
        // Fermer mobile sidebar si clic en dehors
        function handleClickOutside(event) {
            const sidebar = document.getElementById('promoteurSidebar');
            const mobileToggle = document.querySelector('.mobile-toggle');
            
            if (window.innerWidth <= 768 && 
                sidebar.classList.contains('mobile-visible') &&
                !sidebar.contains(event.target) &&
                !mobileToggle.contains(event.target)) {
                closeMobileSidebar();
            }
        }
        
        // Gestion ESC key pour fermer mobile sidebar
        function handleKeyPress(event) {
            if (event.key === 'Escape' && window.innerWidth <= 768) {
                const sidebar = document.getElementById('promoteurSidebar');
                if (sidebar.classList.contains('mobile-visible')) {
                    closeMobileSidebar();
                }
            }
        }
        
        // ========================= USER MENU =========================
        function toggleUserMenu() {
            // TODO: Implémenter dropdown utilisateur
            console.log('Toggle user menu');
        }
        
        function showNotifications() {
            // TODO: Implémenter panneau notifications
            console.log('Show notifications');
        }
        
        // ========================= INITIALIZATION =========================
        document.addEventListener('DOMContentLoaded', function() {
            // Restaurer l'état de la sidebar depuis localStorage
            const savedState = localStorage.getItem('sidebarState');
            if (savedState && window.innerWidth > 1024) {
                const sidebar = document.getElementById('promoteurSidebar');
                if (savedState === 'mini') {
                    sidebar.classList.add('mini');
                    sidebarState = 'mini';
                }
            }
            
            // Event listeners
            window.addEventListener('resize', handleResize);
            document.addEventListener('click', handleClickOutside);
            document.addEventListener('keydown', handleKeyPress);
            
            // Appel initial pour définir l'état correct
            handleResize();
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
                alerts.forEach(function(alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
        
        // ========================= UTILITY FUNCTIONS =========================
        
        // Smooth scroll to top
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
        
        // Format numbers with spaces
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
        }
        
        // Show loading state
        function showLoading(element) {
            const originalText = element.innerHTML;
            element.dataset.originalText = originalText;
            element.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Chargement...';
            element.disabled = true;
        }
        
        // Hide loading state
        function hideLoading(element) {
            const originalText = element.dataset.originalText;
            if (originalText) {
                element.innerHTML = originalText;
                delete element.dataset.originalText;
            }
            element.disabled = false;
        }
        
        // Toast notifications
        function showToast(message, type = 'success', duration = 3000) {
            // Create toast element
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 1060; min-width: 300px;';
            toast.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(toast);
            
            // Auto remove after duration
            setTimeout(() => {
                if (toast.parentNode) {
                    const bsAlert = new bootstrap.Alert(toast);
                    bsAlert.close();
                }
            }, duration);
        }
        
        // Confirm dialog
        function confirmAction(message, callback) {
            if (confirm(message)) {
                callback();
            }
        }
        
        // AJAX helper
        function makeRequest(url, method = 'GET', data = null) {
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                }
            };
            
            if (data) {
                options.body = JSON.stringify(data);
            }
            
            return fetch(url, options)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .catch(error => {
                    console.error('Request failed:', error);
                    showToast('Une erreur est survenue', 'danger');
                    throw error;
                });
        }
        
    </script>
    
    @stack('scripts')
</body>
</html>