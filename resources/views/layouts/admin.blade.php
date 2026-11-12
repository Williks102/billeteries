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
    
    <link rel="stylesheet" href="{{ asset('css/charte.css') }}">

    <style>
        /* Barre du haut, menu latéral, tiroir mobile, gouttières et jetons : public/css/charte.css (section « Barres de navigation »).
           Ici seulement ce qui est propre à l'admin. */
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

        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* ========================= SIDEBAR (propre à l'admin) ========================= */
        .admin-sidebar {
            transition: width var(--motion) ease-in-out, transform var(--motion) ease-in-out;
        }

        .admin-sidebar.mobile-hidden {
            transform: translateX(-100%);
        }

        .sidebar-menu > li {
            border-bottom: 1px solid #2c3338;
        }

        /* Sous-menus */
        .sidebar-submenu {
            list-style: none;
            padding: 0;
            margin: 0;
            background: #1a1e23;
            max-height: 0;
            overflow: hidden;
            transition: max-height var(--motion) ease;
        }

        .sidebar-submenu.open {
            max-height: 400px;
        }

        .menu-chevron {
            transition: transform var(--motion) ease;
        }

        .submenu-parent.open .menu-chevron {
            transform: rotate(180deg);
        }

        .sidebar-submenu .submenu-separator {
            margin-top: var(--space-2);
            border-top: 1px solid #3c434a;
            padding-top: var(--space-2);
        }

        /* Décalage de la barre et du contenu selon l'état du menu (largeurs = jetons de la charte) */
        .admin-sidebar.mini ~ .admin-topbar { left: var(--sidebar-mini-width); }
        .admin-sidebar.mobile-hidden ~ .admin-topbar { left: 0; }

        .admin-main {
            flex: 1 1 auto;
            min-width: 0;
            margin-left: var(--sidebar-width);
            min-height: calc(100vh - var(--topbar-height));
            transition: margin-left var(--motion) ease-in-out;
        }

        .admin-sidebar.mini ~ .admin-main { margin-left: var(--sidebar-mini-width); }

        @media (max-width: 1024px) {
            .admin-main { margin-left: var(--sidebar-mini-width); }
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
                width: var(--sidebar-width);
            }

            .admin-sidebar.mobile-open {
                transform: translateX(0);
                box-shadow: 0 0 20px rgba(0,0,0,0.5);
            }

            .admin-main { margin-left: 0; }

        }

        /* ========================= CARTES ========================= */
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
    </style>
    
    @stack('styles')
</head>
<body>
    @include('partials.demo-banner')
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <nav class="admin-sidebar" id="adminSidebar" aria-label="Menu d'administration">
            <!-- Sidebar Header -->
            <div class="sidebar-header">
                <a href="{{ route('admin.dashboard') }}" class="sidebar-brand">
                    <i class="fas fa-shield-alt"></i>
                    <span class="sidebar-brand-text">ClicBillet CI</span>
                </a>
                <button type="button" class="sidebar-toggle" data-action="toggle-sidebar" aria-label="Réduire ou déployer le menu">
                    <i class="fas fa-chevron-left" aria-hidden="true"></i>
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
                    <a href="#" data-action="toggle-submenu" data-menu="users" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
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
                    <a href="#" data-action="toggle-submenu" data-menu="events" class="{{ request()->routeIs('admin.events.*') || request()->routeIs('admin.intervention-dashboard') ? 'active' : '' }}">
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
                        <li class="submenu-separator">
                            <a href="{{ route('admin.categories.index') }}">Catégories</a>
                        </li>
                    </ul>
                </li>

                <!-- Commandes -->
                <li class="submenu-parent {{ request()->routeIs('admin.orders.*') ? 'open' : '' }}">
                    <a href="#" data-action="toggle-submenu" data-menu="orders" class="{{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
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
                    <a href="#" data-action="toggle-submenu" data-menu="tickets" class="{{ request()->routeIs('admin.tickets.*') ? 'active' : '' }}">
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

                <!-- Paramètres -->
                <li class="submenu-parent {{ request()->routeIs('admin.settings.*') ? 'open' : '' }}">
                    <a href="#" data-action="toggle-submenu" data-menu="settings" class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
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
                    <a href="{{ route('home') }}">
                        <i class="fas fa-globe"></i>
                        <span class="sidebar-menu-text">Voir le site</span>
                        <span class="sidebar-tooltip">Voir le site</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Topbar -->
        <header class="admin-topbar">
            <div class="topbar-left">
                <button type="button" class="mobile-toggle" data-action="toggle-mobile-sidebar" aria-label="Ouvrir le menu" aria-controls="adminSidebar" aria-expanded="false">
                    <i class="fas fa-bars" aria-hidden="true"></i>
                </button>
                <nav class="topbar-breadcrumb" aria-label="Fil d'Ariane">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.dashboard') }}" class="topbar-home" aria-label="Accueil de l'administration">
                                <i class="fas fa-home" aria-hidden="true"></i>
                            </a>
                        </li>
                        @if(View::hasSection('breadcrumb'))
                            @yield('breadcrumb')
                        @endif
                    </ol>
                </nav>
            </div>

            <div class="topbar-user">
                <!-- Notifications -->
                <button type="button" class="topbar-icon-btn topbar-notifications" data-action="toggle-notifications" aria-label="Notifications, 3 non lues">
                    <i class="fas fa-bell" aria-hidden="true"></i>
                    <span class="notification-badge" aria-hidden="true">3</span>
                </button>

                <!-- User Info -->
                <div class="user-info">
                    <div class="user-details">
                        <div class="user-name">{{ auth()->user()->name }}</div>
                        <div class="user-role">Administrateur</div>
                    </div>
                </div>

                <!-- User Avatar + Dropdown -->
                <div class="dropdown">
                    <button type="button" class="topbar-avatar-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu du compte de {{ auth()->user()->name }}">
                        <span class="user-avatar" aria-hidden="true">{{ substr(auth()->user()->name, 0, 1) }}</span>
                    </button>
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
        <div class="mobile-overlay" id="mobileOverlay" data-action="close-mobile-sidebar" aria-hidden="true"></div>

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
    
    <script src="{{ asset('js/layout-admin.js') }}" defer></script>

    
    @stack('scripts')
</body>
</html>