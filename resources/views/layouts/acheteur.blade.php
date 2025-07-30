{{-- resources/views/layouts/acheteur.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Mon Espace - ClicBillet CI')</title>
    
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
            --primary-dark: #e55a2b;
            --dark-blue: #1a237e;
            --black-primary: #2c3e50;
            --light-gray: #f8f9fa;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-gray);
            padding-top: 70px;
        }
        
        /* === HEADER ACHETEUR === */
        .acheteur-navbar {
            position: fixed !important;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1050;
            background: linear-gradient(135deg, var(--dark-blue), var(--black-primary)) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: 70px;
        }
        
        .acheteur-navbar .navbar-brand {
            color: white !important;
            font-weight: 700;
            font-size: 1.3rem;
        }
        
        .acheteur-navbar .nav-link {
            color: rgba(255,255,255,0.9) !important;
            transition: all 0.3s ease;
        }
        
        .acheteur-navbar .nav-link:hover {
            color: var(--primary-orange) !important;
        }
        
        /* === SIDEBAR ACHETEUR === */
        .acheteur-sidebar {
            position: sticky !important;
            top: 90px;
            max-height: calc(100vh - 110px);
            overflow-y: auto;
            background: white !important;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .acheteur-sidebar h5 {
            color: var(--black-primary);
            font-weight: 600;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 15px;
        }
        
        .acheteur-sidebar .nav-link {
            color: #6c757d !important;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: all 0.3s ease;
        }
        
        .acheteur-sidebar .nav-link:hover {
            background: rgba(26, 35, 126, 0.1) !important;
            color: var(--dark-blue) !important;
            transform: translateX(5px);
        }
        
        .acheteur-sidebar .nav-link.active {
            background: linear-gradient(45deg, var(--dark-blue), var(--black-primary)) !important;
            color: white !important;
            box-shadow: 0 2px 8px rgba(26, 35, 126, 0.3);
        }
        
        .acheteur-sidebar .nav-link i {
            width: 20px;
            text-align: center;
        }
        
        /* === CONTENU === */
        .acheteur-content {
            min-height: calc(100vh - 100px);
            padding: 20px;
        }
        
        /* === COMPOSANTS === */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .btn-acheteur {
            background: linear-gradient(45deg, var(--dark-blue), var(--black-primary));
            border: none;
            color: white;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-acheteur:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 35, 126, 0.4);
            color: white;
        }
        
        /* === RESPONSIVE === */
        @media (max-width: 768px) {
            body { padding-top: 60px; }
            .acheteur-navbar { height: 60px; }
            .acheteur-sidebar {
                position: relative !important;
                top: auto;
                max-height: none;
                margin-bottom: 20px;
            }
        }
    </style>
    
    @stack('styles')
    </head>