<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'ClicBillet CI - Billetterie en ligne')</title>
    
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
            --dark-blue: #1a237e;
            --light-gray: #f8f9fa;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
        }
        
        /* Styles pour le contenu principal */
        .main-content {
            min-height: calc(100vh - 200px);
        }
        
        /* Styles pour les pages d'administration */
        .admin-layout {
            background-color: #f8f9fa;
        }
        
        .admin-layout .main-content {
            padding: 2rem 0;
        }
        
        /* Styles pour les alertes */
        .alert {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
        }
        
        .alert-warning {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            color: #856404;
        }
        
        .alert-info {
            background: linear-gradient(135deg, #d1ecf1, #bee5eb);
            color: #0c5460;
        }
        
        /* Utilitaires */
        .text-orange {
            color: var(--primary-orange) !important;
        }
        
        .bg-orange {
            background-color: var(--primary-orange) !important;
        }
        
        .btn-orange {
            background: linear-gradient(135deg, var(--primary-orange), var(--secondary-orange));
            border: none;
            color: white;
            border-radius: 25px;
            padding: 10px 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-orange:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
            color: white;
        }
        
        /* Footer */
        .footer {
            background: #962e08ff;
            color: #fcfcfcff;
            padding: 3rem 0 1rem;
            margin-top: auto;
        }
        
        .footer a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .footer a:hover {
            color: white;
        }
        
        /* Animations */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem 0;
            }
        }
    </style>
    
    @stack('styles')
</head>
<body class="@yield('body-class')">
    <!-- Header unifié -->
    @include('partials.header')
    
    <!-- Messages flash -->
    @if(session('success') || session('error') || session('warning') || session('info'))
        <div class="container mt-3">
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
            
            @if(session('info'))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    {{ session('info') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
        </div>
    @endif
    
    <!-- Contenu principal -->
    <main class="main-content fade-in">
        @yield('content')
    </main>
    
    <!-- Footer -->
    @unless(request()->routeIs('login') || request()->routeIs('register'))
        <!-- Footer mis à jour avec les vrais liens -->
<footer class="footer mt-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-ticket-alt me-2"></i>ClicBillet CI
                </h5>
                <p>La plateforme de billetterie #1 en Côte d'Ivoire.
                Découvrez et réservez vos événements favoris en quelques clics.</p>
                <div class="social-links">
                    <a href="#" class="me-3"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="me-3"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="me-3"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="me-3"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="fw-bold mb-3">Événements</h6>
                <ul class="list-unstyled">
                    <li><a href="{{ route('events.concerts') }}">Concerts</a></li>
                    <li><a href="{{ route('events.theatre') }}">Théâtre</a></li>
                    <li><a href="{{ route('events.sports') }}">Sports</a></li>
                    <li><a href="{{ route('events.conferences') }}">Conférences</a></li>
                    <li><a href="{{ route('events.festivals') }}">Festivals</a></li>
                </ul>
            </div>
            
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="fw-bold mb-3">Aide</h6>
                <ul class="list-unstyled">
                    <li><a href="{{ route('pages.how-it-works') }}">Comment ça marche</a></li>
                    <li><a href="{{ route('pages.support') }}">Support</a></li>
                    <li><a href="{{ route('pages.faq') }}">FAQ</a></li>
                    <li><a href="{{ route('pages.contact') }}">Contact</a></li>
                </ul>
            </div>
            
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="fw-bold mb-3">Organisateurs</h6>
                <ul class="list-unstyled">
                    <li><a href="{{ route('register') }}">Devenir promoteur</a></li>
                    <li><a href="#">Guide promoteur</a></li>
                    <a href="#">Guide</a>  <!-- CMS -->
                    <li><a href="{{ route('pages.pricing') }}">Tarifs</a></li>
                    <li><a href="{{ route('pages.about') }}">À propos</a></li>
                </ul>
            </div>
            
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="fw-bold mb-3">Légal</h6>
                <ul class="list-unstyled">
                    <li><a href="{{ route('pages.terms') }}">Conditions d'utilisation</a></li>
                    <li><a href="{{ route('pages.privacy') }}">Politique de confidentialité</a></li>
                    <li><a href="{{ route('pages.legal') }}">Mentions légales</a></li>
                </ul>
            </div>
        </div>
        
        <hr class="my-4" style="border-color: rgba(255,255,255,0.2);">
        
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="mb-0">&copy; {{ date('Y') }} ClicBillet CI. Tous droits réservés.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-0">
                    <i class="fas fa-heart text-danger"></i> 
                    Fait avec passion en Côte d'Ivoire
                </p>
            </div>
        </div>
    </div>
</footer>
    @endunless
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script global pour les notifications -->
    <script>
        // Auto-hide alerts après 5 secondes
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert.auto-hide');
    alerts.forEach(function(alert) {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
        
        // Fonction globale pour afficher des notifications
        window.showNotification = function(message, type = 'info') {
            const alertContainer = document.createElement('div');
            alertContainer.className = 'container mt-3';
            alertContainer.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            document.body.insertBefore(alertContainer, document.querySelector('main'));
            
            // Auto-hide après 5 secondes
            setTimeout(() => {
                const alert = alertContainer.querySelector('.alert');
                if (alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 5000);
        };
    </script>
    
    @stack('scripts')
</body>
</html>