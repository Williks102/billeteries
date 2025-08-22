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
            --promoteur-primary: #f59e0b;
            --promoteur-secondary: #d97706;
            --black-primary: #1f2937;
            --black-secondary: #374151;
            --gray-light: #f9fafb;
            --gray-medium: #6b7280;
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
            color: #374151;
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
            color: #d1d5db;
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
            border-bottom: 1px solid #4b5563;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            padding: 0 20px;
            text-decoration: none;
            color: #f3f4f6;
            font-weight: 600;
            font-size: 1.1rem;
            white-space: nowrap;
            overflow: hidden;
        }

        .sidebar-brand i {
            color: var(--promoteur-primary);
            font-size: 1.4rem;
            margin-right: 12px;
            flex-shrink: 0;
        }

        .sidebar-brand-text {
            transition: opacity 0.2s ease;
        }

        .promoteur-sidebar.mini .sidebar-brand-text {
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
            color: var(--gray-medium);
            cursor: pointer;
            padding: 5px;
            border-radius: 3px;
            transition: color 0.2s;
        }

        .sidebar-toggle:hover {
            color: var(--promoteur-primary);
        }

        .promoteur-sidebar.mini .sidebar-toggle {
            display: none;
        }

        /* Sidebar Menu */
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu > li {
            border-bottom: 1px solid #374151;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #d1d5db;
            text-decoration: none;
            transition: all 0.2s ease;
            position: relative;
        }

        .sidebar-menu a:hover {
            background: var(--black-secondary);
            color: #fbbf24;
        }

        .sidebar-menu a.active {
            background: var(--promoteur-primary);
            color: white;
        }

        .sidebar-menu a.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--promoteur-secondary);
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

        .promoteur-sidebar.mini .sidebar-menu-text {
            opacity: 0;
            width: 0;
        }

        /* Submenu */
        .sidebar-submenu {
            list-style: none;
            padding: 0;
            margin: 0;
            background: #111827;
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
            background: var(--black-secondary);
            border-left-color: var(--promoteur-primary);
        }

        .sidebar-submenu a.active {
            background: var(--black-secondary);
            border-left-color: var(--promoteur-primary);
            color: var(--promoteur-primary);
        }

        /* Chevron pour submenu */
        .menu-chevron {
            margin-left: auto;
            transition: transform 0.3s ease;
            color: var(--gray-medium);
            font-size: 0.8rem;
        }

        .submenu-parent.open .menu-chevron {
            transform: rotate(180deg);
            color: var(--promoteur-primary);
        }

        .sidebar-menu a:hover .menu-chevron {
            color: #fbbf24;
        }

        /* Mini sidebar hover expansion */
        .promoteur-sidebar.mini:hover {
            width: var(--sidebar-width);
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
        }

        .promoteur-sidebar.mini:hover .sidebar-brand-text,
        .promoteur-sidebar.mini:hover .sidebar-menu-text {
            opacity: 1;
            width: auto;
        }

        /* ========================= TOPBAR ========================= */
        .promoteur-topbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: var(--topbar-height);
            background: white;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 999;
            transition: left 0.2s ease-in-out;
        }

        .promoteur-sidebar.mini ~ .promoteur-topbar {
            left: var(--sidebar-mini-width);
        }

        .promoteur-sidebar.mobile-hidden ~ .promoteur-topbar {
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

        /* Breadcrumb */
        .topbar-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--gray-medium);
            font-size: 0.9rem;
        }

        .topbar-breadcrumb a {
            color: var(--promoteur-primary);
            text-decoration: none;
        }

        /* Home button */
        .home-button {
            background: var(--promoteur-primary);
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
            background: var(--promoteur-secondary);
            color: white;
            transform: translateY(-1px);
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
            background: #ef4444;
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

        .revenue-display {
            background: linear-gradient(135deg, #fef3c7, #fbbf24);
            color: #92400e;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            border: 1px solid #f59e0b;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--promoteur-primary);
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
        .promoteur-main {
            margin-left: var(--sidebar-width);
            margin-top: var(--topbar-height);
            padding: 20px;
            min-height: calc(100vh - var(--topbar-height));
            transition: margin-left 0.2s ease-in-out;
            width: calc(100% - var(--sidebar-width));
        }

        .promoteur-sidebar.mini ~ .promoteur-main {
            margin-left: var(--sidebar-mini-width);
            width: calc(100% - var(--sidebar-mini-width));
        }

        .promoteur-sidebar.mobile-hidden ~ .promoteur-main {
            margin-left: 0;
            width: 100%;
        }

        /* ========================= RESPONSIVE ========================= */
        @media (max-width: 1024px) {
            .promoteur-sidebar {
                width: var(--sidebar-mini-width);
            }
            
            .promoteur-topbar {
                left: var(--sidebar-mini-width);
            }
            
            .promoteur-main {
                margin-left: var(--sidebar-mini-width);
                width: calc(100% - var(--sidebar-mini-width));
            }
        }

        @media (max-width: 768px) {
            .promoteur-sidebar {
                transform: translateX(-100%);
                width: var(--sidebar-width);
            }
            
            .promoteur-sidebar.mobile-open {
                transform: translateX(0);
                box-shadow: 0 0 20px rgba(0,0,0,0.5);
            }
            
            .promoteur-sidebar.mobile-open .sidebar-brand-text,
            .promoteur-sidebar.mobile-open .sidebar-menu-text {
                opacity: 1 !important;
                width: auto !important;
            }
            
            .promoteur-sidebar.mobile-open .sidebar-submenu.open {
                max-height: 400px;
                display: block;
            }
            
            .promoteur-sidebar .menu-chevron {
                color: #d1d5db !important;
                font-size: 1rem !important;
                margin-left: auto;
            }
            
            .promoteur-sidebar .submenu-parent.open .menu-chevron {
                color: var(--promoteur-primary) !important;
            }
            
            .promoteur-topbar {
                left: 0;
            }
            
            .promoteur-main {
                margin-left: 0;
                width: 100%;
                padding: 15px;
            }
            
            .mobile-toggle {
                display: block;
            }
            
            .user-info, .revenue-display {
                display: none;
            }
            
            .promoteur-sidebar:hover {
                width: var(--sidebar-width);
            }
            
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
            border: 1px solid #e5e7eb;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid #e5e7eb;
            border-left: 4px solid var(--promoteur-primary);
        }

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
                    <i class="fas fa-calendar-alt"></i>
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
                <li class="submenu-parent {{ request()->routeIs('promoteur.events*') ? 'open' : '' }}">
                    <a href="#" onclick="toggleSubmenu('events', this)" class="{{ request()->routeIs('promoteur.events*') ? 'active' : '' }}">
                        <i class="fas fa-calendar-check"></i>
                        <span class="sidebar-menu-text">Mes Événements</span>
                        <i class="fas fa-chevron-down menu-chevron"></i>
                        <span class="sidebar-tooltip">Mes Événements</span>
                    </a>
                    <ul class="sidebar-submenu {{ request()->routeIs('promoteur.events*') ? 'open' : '' }}" id="submenu-events">
                        <li><a href="{{ route('promoteur.events.index') }}" class="{{ request()->routeIs('promoteur.events.index') ? 'active' : '' }}">Tous mes événements</a></li>
                        <li><a href="{{ route('promoteur.events.create') }}" class="{{ request()->routeIs('promoteur.events.create') ? 'active' : '' }}">Créer un événement</a></li>
                        <li><a href="{{ route('promoteur.events.draft') }}">Brouillons</a></li>
                        <li><a href="{{ route('promoteur.events.published') }}">Publiés</a></li>
                        <li><a href="{{ route('promoteur.events.archived') }}">Archivés</a></li>
                    </ul>
                </li>

                <!-- Gestion des Tickets -->
                <li class="submenu-parent {{ request()->routeIs('promoteur.tickets*') ? 'open' : '' }}">
                    <a href="#" onclick="toggleSubmenu('tickets', this)" class="{{ request()->routeIs('promoteur.tickets*') ? 'active' : '' }}">
                        <i class="fas fa-ticket-alt"></i>
                        <span class="sidebar-menu-text">Gestion des Tickets</span>
                        <i class="fas fa-chevron-down menu-chevron"></i>
                        <span class="sidebar-tooltip">Gestion des Tickets</span>
                    </a>
                    <ul class="sidebar-submenu {{ request()->routeIs('promoteur.tickets*') ? 'open' : '' }}" id="submenu-tickets">
                        <li><a href="{{ route('promoteur.tickets.index') }}" class="{{ request()->routeIs('promoteur.tickets.index') ? 'active' : '' }}">Tous les tickets</a></li>
                        <li><a href="{{ route('promoteur.tickets.sold') }}">Tickets vendus</a></li>
                        <li><a href="{{ route('promoteur.tickets.scanned') }}">Tickets scannés</a></li>
                        <li><a href="{{ route('promoteur.tickets.scanner') }}">Scanner les tickets</a></li>
                    </ul>
                </li>

                <!-- Ventes & Commandes -->
                <li class="submenu-parent {{ request()->routeIs('promoteur.sales*') ? 'open' : '' }}">
                    <a href="#" onclick="toggleSubmenu('sales', this)" class="{{ request()->routeIs('promoteur.sales*') ? 'active' : '' }}">
                        <i class="fas fa-chart-line"></i>
                        <span class="sidebar-menu-text">Ventes & Commandes</span>
                        <i class="fas fa-chevron-down menu-chevron"></i>
                        <span class="sidebar-tooltip">Ventes & Commandes</span>
                    </a>
                    <ul class="sidebar-submenu {{ request()->routeIs('promoteur.sales*') ? 'open' : '' }}" id="submenu-sales">
                        <li><a href="{{ route('promoteur.sales.overview') }}" class="{{ request()->routeIs('promoteur.sales.overview') ? 'active' : '' }}">Vue d'ensemble</a></li>
                        <li><a href="{{ route('promoteur.sales.orders') }}">Commandes</a></li>
                        <li><a href="{{ route('promoteur.sales.analytics') }}">Analytics détaillées</a></li>
                        <li><a href="{{ route('promoteur.sales.exports') }}">Exports & Rapports</a></li>
                    </ul>
                </li>

                <!-- Revenus & Commissions -->
                <li class="submenu-parent {{ request()->routeIs('promoteur.revenues*') ? 'open' : '' }}">
                    <a href="#" onclick="toggleSubmenu('revenues', this)" class="{{ request()->routeIs('promoteur.revenues*') ? 'active' : '' }}">
                        <i class="fas fa-money-bill-wave"></i>
                        <span class="sidebar-menu-text">Revenus & Commissions</span>
                        <i class="fas fa-chevron-down menu-chevron"></i>
                        <span class="sidebar-tooltip">Revenus & Commissions</span>
                    </a>
                    <ul class="sidebar-submenu {{ request()->routeIs('promoteur.revenues*') ? 'open' : '' }}" id="submenu-revenues">
                        <li><a href="{{ route('promoteur.revenues.dashboard') }}" class="{{ request()->routeIs('promoteur.revenues.dashboard') ? 'active' : '' }}">Dashboard revenus</a></li>
                        <li><a href="{{ route('promoteur.revenues.commissions') }}">Mes commissions</a></li>
                        <li><a href="{{ route('promoteur.revenues.payouts') }}">Demandes de paiement</a></li>
                        <li><a href="{{ route('promoteur.revenues.history') }}">Historique</a></li>
                    </ul>
                </li>

                <!-- Marketing & Promotion -->
                <li class="submenu-parent {{ request()->routeIs('promoteur.marketing*') ? 'open' : '' }}">
                    <a href="#" onclick="toggleSubmenu('marketing', this)" class="{{ request()->routeIs('promoteur.marketing*') ? 'active' : '' }}">
                        <i class="fas fa-bullhorn"></i>
                        <span class="sidebar-menu-text">Marketing & Promotion</span>
                        <i class="fas fa-chevron-down menu-chevron"></i>
                        <span class="sidebar-tooltip">Marketing & Promotion</span>
                    </a>
                    <ul class="sidebar-submenu {{ request()->routeIs('promoteur.marketing*') ? 'open' : '' }}" id="submenu-marketing">
                        <li><a href="{{ route('promoteur.marketing.campaigns') }}" class="{{ request()->routeIs('promoteur.marketing.campaigns') ? 'active' : '' }}">Campagnes</a></li>
                        <li><a href="{{ route('promoteur.marketing.codes') }}">Codes promo</a></li>
                        <li><a href="{{ route('promoteur.marketing.emails') }}">Email marketing</a></li>
                        <li><a href="{{ route('promoteur.marketing.social') }}">Réseaux sociaux</a></li>
                    </ul>
                </li>

                <!-- Analytics -->
                <li>
                    <a href="{{ route('promoteur.analytics') }}" class="{{ request()->routeIs('promoteur.analytics') ? 'active' : '' }}">
                        <i class="fas fa-chart-bar"></i>
                        <span class="sidebar-menu-text">Analytics</span>
                        <span class="sidebar-tooltip">Analytics</span>
                    </a>
                </li>

                <!-- Mon Profil -->
                <li class="submenu-parent {{ request()->routeIs('promoteur.profile*') ? 'open' : '' }}">
                    <a href="#" onclick="toggleSubmenu('profile', this)" class="{{ request()->routeIs('promoteur.profile*') ? 'active' : '' }}">
                        <i class="fas fa-user-cog"></i>
                        <span class="sidebar-menu-text">Mon Profil</span>
                        <i class="fas fa-chevron-down menu-chevron"></i>
                        <span class="sidebar-tooltip">Mon Profil</span>
                    </a>
                    <ul class="sidebar-submenu {{ request()->routeIs('promoteur.profile*') ? 'open' : '' }}" id="submenu-profile">
                        <li><a href="{{ route('promoteur.profile.edit') }}" class="{{ request()->routeIs('promoteur.profile.edit') ? 'active' : '' }}">Informations personnelles</a></li>
                        <li><a href="{{ route('promoteur.profile.company') }}">Informations société</a></li>
                        <li><a href="{{ route('promoteur.profile.banking') }}">Coordonnées bancaires</a></li>
                        <li><a href="{{ route('promoteur.profile.security') }}">Sécurité</a></li>
                        <li><a href="{{ route('promoteur.profile.notifications') }}">Notifications</a></li>
                    </ul>
                </li>

                <!-- Support -->
                <li>
                    <a href="{{ route('promoteur.support') }}" class="{{ request()->routeIs('promoteur.support') ? 'active' : '' }}">
                        <i class="fas fa-life-ring"></i>
                        <span class="sidebar-menu-text">Support</span>
                        <span class="sidebar-tooltip">Support</span>
                    </a>
                </li>
            </ul>
        </nav>

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
                <a href="{{ route('home') }}" class="home-button">
                    <i class="fas fa-globe"></i>
                    <span>Voir le site</span>
                </a>
            </div>

            <div class="topbar-user">
                <!-- Revenus -->
                <div class="revenue-display">
                    <i class="fas fa-coins me-1"></i>
                    {{ number_format(auth()->user()->totalRevenue() ?? 0) }} FCFA
                </div>

                <!-- Notifications -->
                <div class="topbar-notifications" onclick="toggleNotifications()">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">5</span>
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
                        <li><a class="dropdown-item" href="{{ route('promoteur.profile.edit') }}">
                            <i class="fas fa-user me-2"></i>Mon profil
                        </a></li>
                        <li><a class="dropdown-item" href="{{ route('promoteur.events.create') }}">
                            <i class="fas fa-plus me-2"></i>Nouvel événement
                        </a></li>
                        <li><a class="dropdown-item" href="{{ route('promoteur.revenues.dashboard') }}">
                            <i class="fas fa-money-bill me-2"></i>Mes revenus
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
        <main class="promoteur-main">
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
        let sidebarState = localStorage.getItem('promoteur-sidebar-state') || 'open';
        
        // Initialize sidebar state
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('promoteurSidebar');
            
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
            const sidebar = document.getElementById('promoteurSidebar');
            
            if (sidebar.classList.contains('mini')) {
                sidebar.classList.remove('mini');
                sidebarState = 'open';
            } else {
                sidebar.classList.add('mini');
                sidebarState = 'mini';
            }
            
            localStorage.setItem('promoteur-sidebar-state', sidebarState);
        }

        // Toggle mobile sidebar
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('promoteurSidebar');
            const overlay = document.getElementById('mobileOverlay');
            
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
        }

        // Close mobile sidebar
        function closeMobileSidebar() {
            const sidebar = document.getElementById('promoteurSidebar');
            const overlay = document.getElementById('mobileOverlay');
            
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
        }

        // Toggle submenu
        function toggleSubmenu(menuId, element) {
            const submenu = document.getElementById('submenu-' + menuId);
            const parent = element.closest('li');
            const isOpen = submenu.classList.contains('open');
            
            const isMobile = window.innerWidth <= 768;
            
            if (!isMobile) {
                document.querySelectorAll('.sidebar-submenu').forEach(menu => {
                    if (menu !== submenu) {
                        menu.classList.remove('open');
                        menu.closest('li').classList.remove('open');
                    }
                });
            }
            
            if (isOpen) {
                submenu.classList.remove('open');
                parent.classList.remove('open');
            } else {
                submenu.classList.add('open');
                parent.classList.add('open');
            }
            
            return false;
        }

        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('promoteurSidebar');
            
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
            
            document.getElementById('mobileOverlay').classList.remove('active');
        });

        // Notifications placeholder
        function toggleNotifications() {
            console.log('Toggle notifications');
        }
    </script>
    
    @stack('scripts')
</body>
</html>