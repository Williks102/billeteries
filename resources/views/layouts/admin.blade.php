<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Billetterie CI</title>

    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @stack('styles')

    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            background: black;
            min-height: 100vh;
            border-right: 1px solid #dee2e6;
            padding: 1rem;
            display: flex;
            flex-direction: column;
        }
        .sidebar .nav-link {
            color: #f8f9fa;
            margin-bottom: 10px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: #FF6B35;
            color: white !important;
        }
        .admin-content {
            padding: 2rem;
        }
        .sidebar h4 {
            color: white !important;
        }
        .text-orange {
            color: #FF6B35 !important;
        }
        .logout-section {
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid #333;
        }
        .logout-btn {
            background-color: #dc3545;
            border: none;
            color: white;
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .logout-btn:hover {
            background-color: #c82333;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div>
                    <h4 class="fw-bold mb-4">
                        <i class="fas fa-ticket-alt text-orange me-2"></i>Billetterie CI
                    </h4>
                    <nav class="nav flex-column">
                        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.users') ? 'active' : '' }}" href="{{ route('admin.users') }}">
                            <i class="fas fa-users me-2"></i> Utilisateurs
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.events') ? 'active' : '' }}" href="{{ route('admin.events') }}">
                            <i class="fas fa-calendar-alt me-2"></i> Événements
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.orders') ? 'active' : '' }}" href="{{ route('admin.orders') }}">
                            <i class="fas fa-shopping-cart me-2"></i> Commandes
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.reports') ? 'active' : '' }}" href="{{ route('admin.reports') }}">
                            <i class="fas fa-chart-line me-2"></i> Rapports
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}" href="{{ route('admin.settings') }}">
                            <i class="fas fa-cogs me-2"></i> Paramètres
                        </a>
                        <hr style="border-color: #333;">
                        <a class="nav-link" href="{{ route('home') }}">
                            <i class="fas fa-home me-2"></i> Voir le site
                        </a>
                    </nav>
                </div>
                
                <!-- Section déconnexion -->
                <div class="logout-section">
                    <form action="{{ route('logout') }}" method="POST" class="mb-0">
                        @csrf
                        <button type="submit" class="logout-btn">
                            <i class="fas fa-sign-out-alt me-2"></i>
                            Se déconnecter
                        </button>
                    </form>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 admin-content">
                @yield('content')
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>