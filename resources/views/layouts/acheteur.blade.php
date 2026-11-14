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

    <link rel="stylesheet" href="{{ asset('css/charte.css') }}">

    <style>
        :root {
            --primary-orange: var(--brand);
            --primary-dark: var(--brand-hover);
            --acheteur-primary: #2563eb;
            --acheteur-secondary: #1d4ed8;
            --black-primary: #1d2327;
            --black-secondary: #2c3338;
            --gray-light: #f0f0f1;
            --gray-medium: #8c8f94;
            --transition-smooth: all var(--motion) ease;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: var(--font-sans);
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
            transition: width var(--motion) ease-in-out, transform var(--motion) ease-in-out;
        }

        .sidebar-menu > li {
            position: relative;
        }

        /* Sidebar Menu */
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

        /* ========================= MAIN CONTENT ========================= */
        .acheteur-main {
            flex: 1;
            min-width: 0;
            margin-left: var(--sidebar-width);
            transition: margin-left var(--motion) ease-in-out;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .acheteur-sidebar.mini ~ .acheteur-main { margin-left: var(--sidebar-mini-width); }
        .acheteur-sidebar.mini ~ .acheteur-main .acheteur-topbar { left: var(--sidebar-mini-width); }

        .acheteur-content {
            flex: 1;
            background: var(--gray-light);
        }

        /* ========================= MOBILE RESPONSIVE ========================= */
        @media (max-width: 768px) {
            .acheteur-main,
            .acheteur-sidebar.mini ~ .acheteur-main {
                margin-left: 0;
                width: 100%;
            }

            .acheteur-sidebar {
                transform: translateX(-100%);
                box-shadow: 0 0 20px rgba(0,0,0,0.3);
            }

            .acheteur-sidebar.mobile-visible {
                transform: translateX(0);
            }

            .acheteur-sidebar.mini ~ .acheteur-main .acheteur-topbar {
                left: 0;
            }

            .topbar-breadcrumb {
                display: none;
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
<body class="has-bottom-nav">
    @include('partials.demo-banner')
    <div class="acheteur-wrapper">
        <!-- Mobile Overlay -->
        <div class="mobile-overlay" id="mobileOverlay" data-on-click="mobile-sidebar-close" aria-hidden="true"></div>

        <!-- Sidebar -->
        <nav class="acheteur-sidebar" id="acheteurSidebar" aria-label="Menu acheteur">
            <!-- Sidebar Header -->
            <div class="sidebar-header">
                <a href="{{ route('acheteur.dashboard') }}" class="sidebar-brand">
                    <i class="fas fa-user-circle"></i>
                    <span class="sidebar-brand-text">Mon Espace</span>
                </a>
                <button type="button" class="sidebar-toggle d-none d-md-block" data-on-click="sidebar-toggle" aria-label="Réduire ou déployer le menu">
                    <i class="fas fa-chevron-left" aria-hidden="true"></i>
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
                    <a href="#" data-on-click="submenu-toggle" data-menu="tickets" class="{{ request()->routeIs('acheteur.tickets*') ? 'active' : '' }}">
                        <i class="fas fa-ticket-alt"></i>
                        <span class="sidebar-menu-text">Mes Billets</span>
                        <i class="fas fa-chevron-down menu-chevron"></i>
                        <span class="sidebar-tooltip">Mes Billets</span>
                    </a>
                    <ul class="sidebar-submenu" id="submenu-tickets">
                        <li><a href="{{ route('acheteur.tickets.index') }}" class="{{ request()->routeIs('acheteur.tickets.index') && !request('status') ? 'active' : '' }}">Tous mes billets</a></li>
                        <li><a href="{{ route('acheteur.tickets.index', ['status' => 'active']) }}">Billets actifs</a></li>
                        <li><a href="{{ route('acheteur.tickets.index', ['status' => 'used']) }}">Billets utilisés</a></li>
                        <li><a href="{{ route('acheteur.tickets.index', ['status' => 'expired']) }}">Billets expirés</a></li>
                    </ul>
                </li>

                <!-- Mes Commandes -->
                <li class="submenu-parent {{ request()->routeIs('acheteur.orders*') ? 'open' : '' }}">
                    <a href="#" data-on-click="submenu-toggle" data-menu="orders" class="{{ request()->routeIs('acheteur.orders*') ? 'active' : '' }}">
                        <i class="fas fa-shopping-bag"></i>
                        <span class="sidebar-menu-text">Mes Commandes</span>
                        <i class="fas fa-chevron-down menu-chevron"></i>
                        <span class="sidebar-tooltip">Mes Commandes</span>
                    </a>
                    <ul class="sidebar-submenu" id="submenu-orders">
                        <li><a href="{{ route('acheteur.orders.index') }}" class="{{ request()->routeIs('acheteur.orders.index') && !request('status') ? 'active' : '' }}">Toutes mes commandes</a></li>
                        <li><a href="{{ route('acheteur.orders.index', ['status' => 'paid']) }}">Commandes payées</a></li>
                        <li><a href="{{ route('acheteur.orders.index', ['status' => 'pending']) }}">En attente</a></li>
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
                    <a href="#" data-on-click="submenu-toggle" data-menu="settings" class="{{ request()->routeIs('acheteur.profile*') ? 'active' : '' }}">
                        <i class="fas fa-cog"></i>
                        <span class="sidebar-menu-text">Paramètres</span>
                        <i class="fas fa-chevron-down menu-chevron"></i>
                        <span class="sidebar-tooltip">Paramètres</span>
                    </a>
                    <ul class="sidebar-submenu" id="submenu-settings">
                        <li><a href="{{ route('acheteur.profile.show') }}">Mon Profil</a></li>
                        <li><a href="#" data-on-click="password-modal-show">Changer mot de passe</a></li>
                        <li><a href="#" data-on-click="notification-settings-show">Notifications</a></li>
                    </ul>
                </li>

                <!-- Déconnexion -->
                <li style="border-top: 1px solid #3c3f44; margin-top: 20px; padding-top: 10px;">
                    <a href="#" data-on-click="submit-form-prevent" data-form="logout-form" style="color: #e74c3c;">
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
                <div class="topbar-left">
                    <button type="button" class="mobile-toggle" data-on-click="mobile-sidebar-toggle" aria-label="Ouvrir le menu" aria-controls="acheteurSidebar" aria-expanded="false">
                        <i class="fas fa-bars" aria-hidden="true"></i>
                    </button>

                    @hasSection('breadcrumb')
                    <nav class="topbar-breadcrumb" aria-label="Fil d'Ariane">
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
                    <div class="user-avatar" aria-hidden="true">
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

    @php
        $bottomNavItems = [
        ['label' => 'Accueil', 'icon' => 'fa-home', 'route' => 'acheteur.dashboard', 'active' => 'acheteur.dashboard'],
        ['label' => 'Billets', 'icon' => 'fa-ticket-alt', 'route' => 'acheteur.tickets.index', 'active' => 'acheteur.tickets*'],
        ['label' => 'Commandes', 'icon' => 'fa-shopping-bag', 'route' => 'acheteur.orders.index', 'active' => 'acheteur.orders*'],
        ['label' => 'Profil', 'icon' => 'fa-user-circle', 'route' => 'acheteur.profile.show', 'active' => 'acheteur.profile*'],
    ];
    @endphp
    <x-bottom-nav :items="$bottomNavItems" />

    <!-- Logout Form -->
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
    </form>

    <script src="{{ asset('js/csp-actions.js') }}" defer></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script @cspNonce>
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
            syncMobileToggle(sidebar.classList.contains('mobile-visible'));
        }

        function syncMobileToggle(open) {
            const btn = document.querySelector('.mobile-toggle');
            if (!btn) return;
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            btn.setAttribute('aria-label', open ? 'Fermer le menu' : 'Ouvrir le menu');
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeMobileSidebar();
        });

        function closeMobileSidebar() {
            const sidebar = document.getElementById('acheteurSidebar');
            const overlay = document.getElementById('mobileOverlay');
            const body = document.body;

            sidebar.classList.remove('mobile-visible');
            overlay.classList.remove('active');
            body.style.overflow = '';
            syncMobileToggle(false);
        }

        function toggleSubmenu(menuId, element, evt) {
            (evt || window.event).preventDefault();

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
                                <button type="button" class="btn btn-acheteur" data-on-click="password-change-submit">Modifier</button>
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

        // ========================= ACTIONS (data-on-click, voir public/js/csp-actions.js) =========================
        document.addEventListener('DOMContentLoaded', function() {
            CspActions.register({
                'sidebar-toggle': function () { toggleSidebar(); },
                'mobile-sidebar-toggle': function () { toggleMobileSidebar(); },
                'mobile-sidebar-close': function () { closeMobileSidebar(); },
                'submenu-toggle': function (el, e) { toggleSubmenu(el.dataset.menu, el, e); },
                'password-modal-show': function () { showPasswordModal(); },
                'password-change-submit': function () { submitPasswordChange(); },
                'notification-settings-show': function () { showNotificationSettings(); }
            });
        });

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