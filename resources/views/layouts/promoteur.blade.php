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
    
    <link rel="stylesheet" href="{{ asset('css/charte.css') }}">

    <style>
        /* Barre du haut, menu latéral, tiroir mobile, gouttières et jetons : public/css/charte.css (section « Barres de navigation »).
           Ici seulement ce qui est propre à l'espace promoteur. */
        :root {
            --primary-orange: var(--brand);
            --primary-dark: var(--brand-hover);
            --black-primary: #1d2327;
            --black-secondary: #2c3338;
            --gray-light: #f0f0f1;
            --gray-medium: #8c8f94;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: var(--font-sans);
            background: var(--gray-light);
            color: #3c434a;
        }

        .promoteur-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* ========================= SIDEBAR (propre au promoteur) ========================= */
        .promoteur-sidebar {
            transition: width var(--motion) ease-in-out, transform var(--motion) ease-in-out;
        }

        .sidebar-menu {
            padding: var(--space-2) 0;
        }

        .menu-chevron {
            transition: transform var(--motion) ease-in-out;
        }

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
            transition: max-height var(--motion) ease-in-out;
        }

        .sidebar-submenu.open {
            max-height: 500px;
        }

        /* ========================= CONTENU ========================= */
        .promoteur-main {
            margin-left: var(--sidebar-width);
            flex: 1;
            min-width: 0; /* un enfant flex ne doit pas dépasser la fenêtre (sinon défilement horizontal à 390 px) */
            max-width: 100%;
            min-height: 100vh;
            transition: margin-left var(--motion) ease-in-out;
        }

        .promoteur-content {
            min-height: calc(100vh - var(--topbar-height));
        }

        /* Menu réduit : le contenu et la barre suivent la largeur réelle du menu (jeton de la charte) */
        .promoteur-sidebar.mini ~ .promoteur-main { margin-left: var(--sidebar-mini-width); }
        .promoteur-sidebar.mini ~ .promoteur-main .promoteur-topbar { left: var(--sidebar-mini-width); }

        @media (max-width: 1024px) {
            .promoteur-main { margin-left: var(--sidebar-mini-width); }
        }

        @media (max-width: 768px) {
            .promoteur-sidebar {
                transform: translateX(-100%);
            }

            .promoteur-sidebar.mobile-visible {
                transform: translateX(0);
                box-shadow: 0 0 20px rgba(0,0,0,0.5);
            }

            .promoteur-main,
            .promoteur-sidebar.mini ~ .promoteur-main {
                margin-left: 0;
            }

            .promoteur-sidebar.mini ~ .promoteur-main .promoteur-topbar {
                left: 0;
            }

            /* Le tiroir est plein : sous-menus ouverts visibles */
            .sidebar-submenu {
                display: block;
            }

            .topbar-breadcrumb {
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

        .alert {
            border-radius: 6px;
            border-width: 1px;
            margin-bottom: 20px;
        }

        .topbar-user-menu { gap: var(--space-2); padding: 0 var(--space-2); min-width: auto; width: auto; }
    </style>
    
    @stack('styles')
</head>
<body class="has-bottom-nav">
    @include('partials.demo-banner')
    <div class="promoteur-wrapper">
        <!-- Mobile Overlay -->
        <div class="mobile-overlay" id="mobileOverlay" data-on-click="mobile-sidebar-close" aria-hidden="true"></div>
        
        <!-- Sidebar -->
        <nav class="promoteur-sidebar" id="promoteurSidebar" aria-label="Menu promoteur">
            <!-- Sidebar Header -->
            <div class="sidebar-header">
                <a href="{{ route('promoteur.dashboard') }}" class="sidebar-brand">
                    <i class="fas fa-ticket-alt"></i>
                    <span class="sidebar-brand-text">Espace Promoteur</span>
                </a>
                <button type="button" class="sidebar-toggle d-none d-lg-block" data-on-click="sidebar-toggle" aria-label="Réduire ou déployer le menu">
                    <i class="fas fa-chevron-left" aria-hidden="true"></i>
                </button>
            </div>

            <!-- Sidebar Menu -->
            <ul class="sidebar-menu">
                <!-- Dashboard -->
                <li>
                    <a href="{{ route('promoteur.dashboard') }}" class="{{ request()->routeIs('promoteur.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-tachometer-alt"></i>
                        <span class="sidebar-menu-text">Tableau de bord</span>
                        <span class="sidebar-tooltip">Tableau de bord</span>
                    </a>
                </li>

                <!-- Mes Événements -->
                <li class="submenu-parent {{ request()->routeIs('promoteur.events.*') ? 'open' : '' }}">
                    <a href="#" data-on-click="submenu-toggle" data-menu="events" class="{{ request()->routeIs('promoteur.events.*') ? 'active' : '' }}">
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
                    <a href="#" data-on-click="submenu-toggle" data-menu="tickets" class="{{ request()->routeIs('promoteur.scanner*') || request()->routeIs('promoteur.tickets.*') ? 'active' : '' }}">
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
                    <a href="#" data-on-click="submenu-toggle" data-menu="sales" class="{{ request()->routeIs('promoteur.sales*') ? 'active' : '' }}">
                        <i class="fas fa-chart-line"></i>
                        <span class="sidebar-menu-text">Ventes</span>
                        <i class="fas fa-chevron-down menu-chevron"></i>
                        <span class="sidebar-tooltip">Ventes</span>
                    </a>
                    <ul class="sidebar-submenu {{ request()->routeIs('promoteur.sales*') ? 'open' : '' }}" id="submenu-sales">
                        <li><a href="{{ route('promoteur.sales.index') }}" class="{{ request()->routeIs('promoteur.sales.index') ? 'active' : '' }}">Toutes mes ventes</a></li>
                        <li><a href="{{ route('promoteur.sales.index', ['period' => 'today']) }}">Aujourd'hui</a></li>
                        <li><a href="{{ route('promoteur.sales.index', ['period' => '7d']) }}">7 derniers jours</a></li>
                        <li><a href="{{ route('promoteur.sales.index', ['period' => 'this_month']) }}">Ce mois</a></li>
                    </ul>
                </li>

                <!-- Rapports -->
                <li class="submenu-parent {{ request()->routeIs('promoteur.reports*') ? 'open' : '' }}">
                    <a href="#" data-on-click="submenu-toggle" data-menu="reports" class="{{ request()->routeIs('promoteur.reports*') ? 'active' : '' }}">
                        <i class="fas fa-chart-pie"></i>
                        <span class="sidebar-menu-text">Rapports</span>
                        <i class="fas fa-chevron-down menu-chevron"></i>
                        <span class="sidebar-tooltip">Rapports</span>
                    </a>
                    <ul class="sidebar-submenu {{ request()->routeIs('promoteur.reports*') ? 'open' : '' }}" id="submenu-reports">
                        <li><a href="{{ route('promoteur.reports.index') }}" class="{{ request()->routeIs('promoteur.reports.index') ? 'active' : '' }}">Vue d'ensemble</a></li>
                        <li><a href="{{ route('promoteur.reports.index', ['type' => 'events']) }}">Par événement</a></li>
                        <li><a href="{{ route('promoteur.reports.index', ['type' => 'financial']) }}">Financier</a></li>
                    </ul>
                </li>

                <!-- Profil -->
                <li>
                    <a href="{{ route('promoteur.profile.show') }}" class="{{ request()->routeIs('promoteur.profile*') ? 'active' : '' }}">
                        <i class="fas fa-user-circle"></i>
                        <span class="sidebar-menu-text">Mon Profil</span>
                        <span class="sidebar-tooltip">Mon Profil</span>
                    </a>
                </li>

                <!-- Déconnexion -->
                <li class="mt-auto">
                    <a href="{{ route('logout') }}" data-on-click="submit-form-prevent" data-form="logout-form">
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
                    <button type="button" class="mobile-toggle" data-on-click="mobile-sidebar-toggle" aria-label="Ouvrir le menu" aria-controls="promoteurSidebar" aria-expanded="false">
                        <i class="fas fa-bars" aria-hidden="true"></i>
                    </button>

                    <nav class="topbar-breadcrumb" aria-label="Fil d'Ariane">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('promoteur.dashboard') }}" class="topbar-home" aria-label="Accueil de l'espace promoteur">
                                    <i class="fas fa-home" aria-hidden="true"></i>
                                </a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">@hasSection('breadcrumb')@yield('breadcrumb')@else{{ \Illuminate\Support\Str::limit(trim($__env->yieldContent('title')) ?: 'Tableau de bord', 40) }}@endif</li>
                        </ol>
                    </nav>
                </div>

                <div class="topbar-right">
                    <!-- Menu du compte : Bootstrap dropdown (comme l'admin), fermeture au clic extérieur et à Échap -->
                    <div class="dropdown">
                        <button type="button" class="topbar-avatar-btn topbar-user-menu" data-bs-toggle="dropdown" aria-expanded="false" aria-haspopup="true" aria-label="Menu du compte de {{ auth()->user()->name }}">
                            <span class="user-avatar" aria-hidden="true">{{ substr(auth()->user()->name, 0, 1) }}</span>
                            <span class="user-info">
                                <span class="user-details">
                                    <span class="user-name">{{ auth()->user()->name }}</span>
                                    <span class="user-role">Promoteur</span>
                                </span>
                            </span>
                            <i class="fas fa-chevron-down" aria-hidden="true"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('promoteur.profile.show') }}">
                                <i class="fas fa-user me-2" aria-hidden="true"></i>Mon profil
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('home') }}">
                                <i class="fas fa-globe me-2" aria-hidden="true"></i>Voir le site
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="fas fa-sign-out-alt me-2" aria-hidden="true"></i>Déconnexion
                                    </button>
                                </form>
                            </li>
                        </ul>
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

    @php
        $bottomNavItems = [
        ['label' => 'Accueil', 'icon' => 'fa-tachometer-alt', 'route' => 'promoteur.dashboard', 'active' => 'promoteur.dashboard'],
        ['label' => 'Événements', 'icon' => 'fa-calendar-alt', 'route' => 'promoteur.events.index', 'active' => 'promoteur.events.*'],
        ['label' => 'Ventes', 'icon' => 'fa-chart-line', 'route' => 'promoteur.sales.index', 'active' => 'promoteur.sales*'],
        ['label' => 'Profil', 'icon' => 'fa-user-circle', 'route' => 'promoteur.profile.show', 'active' => 'promoteur.profile*'],
    ];
    @endphp
    <x-bottom-nav :items="$bottomNavItems" />

    <!-- Logout Form -->
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
    </form>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="{{ asset('js/csp-actions.js') }}" defer></script>
    <script src="{{ asset('js/layouts-promoteur.js') }}" defer></script>
    
    @stack('scripts')
</body>
</html>