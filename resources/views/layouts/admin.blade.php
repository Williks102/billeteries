{{-- resources/views/layouts/admin.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Administration - ClicBillet CI')</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Styles personnalisés -->
    <style>
        :root {
            --primary-orange: #FF6B35;
            --secondary-orange: #ff8c61;
            --accent-orange: #ff9f7a;
            --primary-dark: #e55a2b;
            --dark-blue: #1a237e;
            --black-primary: #2c3e50;
            --black-secondary: #34495e;
            --black-light: #7f8c8d;
            --light-gray: #f8f9fa;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            background-color: var(--light-gray);
            padding-top: 70px; /* Compenser le header fixe */
        }
        
        /* === HEADER FIXE === */
        .admin-navbar {
            position: fixed !important;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1050;
            background: linear-gradient(135deg, var(--black-primary), var(--black-secondary)) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: 70px;
        }
        
        .admin-navbar .navbar-brand {
            color: white !important;
            font-weight: 700;
            font-size: 1.3rem;
        }
        
        .admin-navbar .navbar-brand:hover {
            color: var(--primary-orange) !important;
        }
        
        .admin-navbar .text-orange {
            color: var(--primary-orange) !important;
        }
        
        .admin-navbar .nav-link {
            color: rgba(255,255,255,0.9) !important;
            transition: all 0.3s ease;
        }
        
        .admin-navbar .nav-link:hover {
            color: var(--primary-orange) !important;
        }
        
        .admin-navbar .dropdown-menu {
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            border-radius: 10px;
        }
        
        /* === SIDEBAR === */
        .admin-sidebar {
            position: sticky !important;
            top: 90px; /* Sous le header fixe */
            max-height: calc(100vh - 110px);
            overflow-y: auto;
            background: linear-gradient(135deg, var(--black-primary), var(--black-secondary)) !important;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .admin-sidebar h5 {
            color: white;
            font-weight: 600;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 15px;
        }
        
        .admin-sidebar .nav-link {
            color: rgba(255,255,255,0.8) !important;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
        }
        
        .admin-sidebar .nav-link:hover {
            background: rgba(255, 107, 53, 0.2) !important;
            color: white !important;
            transform: translateX(5px);
        }
        
        .admin-sidebar .nav-link.active {
            background: var(--primary-orange) !important;
            color: white !important;
            box-shadow: 0 2px 8px rgba(255, 107, 53, 0.3);
        }
        
        .admin-sidebar .nav-link i {
            width: 20px;
            text-align: center;
        }
        
        .admin-sidebar hr {
            border-color: rgba(255,255,255,0.2) !important;
            margin: 15px 0;
        }
        
        /* === CONTENU PRINCIPAL === */
        .admin-content {
            min-height: calc(100vh - 100px);
            padding: 20px;
        }
        
        /* === CARTES ET COMPOSANTS === */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }
        
        .card-header {
            border-radius: 12px 12px 0 0 !important;
            border-bottom: 1px solid #e9ecef;
            background: white;
            font-weight: 600;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: none;
            height: 100%;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
        }
        
        .stat-icon.primary { 
            background: linear-gradient(45deg, var(--primary-orange), var(--primary-dark));
        }
        
        .stat-icon.success { 
            background: linear-gradient(45deg, var(--success), #20c997);
        }
        
        .stat-icon.warning { 
            background: linear-gradient(45deg, var(--warning), #fd7e14);
        }
        
        .stat-icon.info { 
            background: linear-gradient(45deg, var(--info), #6f42c1);
        }
        
        .stat-info h4 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--black-primary);
            margin-bottom: 5px;
        }
        
        /* === BOUTONS === */
        .btn-orange {
            background: linear-gradient(45deg, var(--primary-orange), var(--primary-dark));
            border: none;
            color: white;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-orange:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.4);
            color: white;
        }
        
        .btn-outline-orange {
            border: 2px solid var(--primary-orange);
            color: var(--primary-orange);
            background: transparent;
            border-radius: 8px;
            padding: 8px 18px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-outline-orange:hover {
            background: var(--primary-orange);
            color: white;
            transform: translateY(-2px);
        }
        
        /* === TABLEAUX === */
        .table th {
            border-top: none;
            font-weight: 600;
            color: var(--black-primary);
            background: var(--light-gray);
        }
        
        .table td {
            vertical-align: middle;
        }
        
        /* === BADGES === */
        .badge {
            font-size: 0.75rem;
            padding: 0.4em 0.8em;
            border-radius: 6px;
        }
        
        .badge.bg-success {
            background: var(--success) !important;
        }
        
        .badge.bg-warning {
            background: var(--warning) !important;
            color: var(--black-primary) !important;
        }
        
        .badge.bg-danger {
            background: var(--danger) !important;
        }
        
        /* === BREADCRUMB === */
        .breadcrumb {
            background: transparent;
            padding: 0;
            margin-bottom: 20px;
        }
        
        .breadcrumb-item a {
            color: var(--primary-orange);
            text-decoration: none;
        }
        
        .breadcrumb-item a:hover {
            color: var(--primary-dark);
        }
        
        .breadcrumb-item.active {
            color: var(--black-primary);
        }
        
        /* === ALERTES === */
        .alert {
            border-radius: 12px;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        /* === RESPONSIVE === */
        @media (max-width: 768px) {
            body {
                padding-top: 60px;
            }
            
            .admin-navbar {
                height: 60px;
            }
            
            .admin-sidebar {
                position: relative !important;
                top: auto;
                max-height: none;
                margin-bottom: 20px;
            }
            
            .admin-content {
                padding: 15px;
            }
            
            .stat-card {
                margin-bottom: 1rem;
            }
        }
        
        /* === SCROLLBAR PERSONNALISÉE === */
        .admin-sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .admin-sidebar::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
            border-radius: 3px;
        }
        
        .admin-sidebar::-webkit-scrollbar-thumb {
            background: var(--primary-orange);
            border-radius: 3px;
        }
        
        .admin-sidebar::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- Header Admin -->
    <nav class="navbar navbar-expand-lg admin-navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('admin.dashboard') }}">
                <i class="fas fa-shield-alt text-orange me-2"></i>
                Administration - ClicBillet CI
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-shield me-2"></i>{{ Auth::user()->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="{{ route('home') }}">
                                    <i class="fas fa-eye me-2"></i>Voir le site
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('admin.profile') }}">
                                    <i class="fas fa-user-cog me-2"></i>Mon profil
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="{{ route('logout') }}"
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Formulaire de déconnexion caché -->
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
    </form>

    <!-- Contenu principal avec sidebar -->
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Admin -->
            <div class="col-md-3 col-lg-2">
                @include('partials.admin-sidebar')
            </div>

            <!-- Zone de contenu principal -->
            <div class="col-md-9 col-lg-10">
                <div class="admin-content">
                    <!-- Alertes globales -->
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ session('warning') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Breadcrumb automatique -->
                    @if(!View::hasSection('no-breadcrumb'))
                        <nav aria-label="breadcrumb" class="mb-4">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="{{ route('admin.dashboard') }}">
                                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                                    </a>
                                </li>
                                @yield('breadcrumb')
                            </ol>
                        </nav>
                    @endif

                    <!-- Contenu de la page -->
                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Scripts personnalisés -->
    <script>
        // Auto-hide des alertes après 5 secondes
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });

        // Confirmation de suppression globale
        function confirmDelete(message = 'Êtes-vous sûr de vouloir supprimer cet élément ?') {
            return confirm(message);
        }

        // Fonction utilitaire pour les requêtes AJAX avec CSRF
        function ajaxRequest(url, method = 'GET', data = {}) {
            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            };
            
            if (method !== 'GET') {
                options.body = JSON.stringify(data);
            }
            
            return fetch(url, options);
        }
    </script>
    
    @stack('scripts')
</body>
</html>