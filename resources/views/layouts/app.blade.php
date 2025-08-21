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
        footer {
    background: linear-gradient(135deg, #2c3e50 0%, #1a252f 100%) !important;
}

/* Liens du footer */
.footer-link {
    transition: all 0.3s ease;
    padding: 3px 0;
    display: flex;
    align-items: center;
    border-radius: 4px;
    padding-left: 8px;
    margin-left: -8px;
}

.footer-link:hover {
    color: var(--primary-orange) !important;
    background: rgba(255, 107, 53, 0.1);
    padding-left: 12px;
    transform: translateX(4px);
}

.footer-link i {
    transition: all 0.3s ease;
    width: 18px;
    text-align: center;
}

.footer-link:hover i {
    color: var(--primary-orange) !important;
    transform: scale(1.1);
}

/* Réseaux sociaux */
.social-links a {
    transition: all 0.3s ease;
    padding: 8px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
}

.hover-orange:hover {
    color: var(--primary-orange) !important;
    background: rgba(255, 107, 53, 0.1);
    transform: translateY(-2px);
}

/* Badges */
.bg-orange-light {
    background: var(--orange-light) !important;
    color: var(--primary-orange) !important;
    font-size: 0.7rem;
}

/* Responsive */
@media (max-width: 768px) {
    footer .col-lg-2 {
        margin-bottom: 2rem !important;
    }
    
    footer h6 {
        border-bottom: 2px solid var(--primary-orange);
        padding-bottom: 0.5rem;
        margin-bottom: 1rem !important;
    }
    
    .social-links {
        text-align: center;
        margin-top: 1rem;
    }
}

/* Animation au scroll */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

footer .col-lg-2:nth-child(2) { animation: slideInUp 0.6s ease 0.1s both; }
footer .col-lg-2:nth-child(3) { animation: slideInUp 0.6s ease 0.2s both; }
footer .col-lg-2:nth-child(4) { animation: slideInUp 0.6s ease 0.3s both; }
footer .col-lg-2:nth-child(5) { animation: slideInUp 0.6s ease 0.4s both; }

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
    
    {{-- Footer complet pour ClicBillet CI --}}
{{-- À placer dans resources/views/layouts/app.blade.php --}}

<footer class="bg-dark text-white py-5 mt-5">
    <div class="container">
        <div class="row">
            {{-- Logo et description --}}
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="mb-4">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-ticket-alt me-2" style="color: #FF6B35;"></i>
                        ClicBillet CI
                    </h5>
                    <p class="mb-3 text-light-emphasis">
                        La plateforme de référence pour découvrir et réserver vos événements favoris en Côte d'Ivoire. 
                        Concerts, théâtre, sports, conférences... Tout en quelques clics !
                    </p>
                    
                    {{-- Réseaux sociaux --}}
                    <div class="social-links">
                        <h6 class="fw-semibold mb-3">Suivez-nous</h6>
                        <div class="d-flex gap-3">
                            <a href="#" class="text-white-50 hover-orange fs-4" title="Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="text-white-50 hover-orange fs-4" title="Twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" class="text-white-50 hover-orange fs-4" title="Instagram">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#" class="text-white-50 hover-orange fs-4" title="LinkedIn">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                            <a href="#" class="text-white-50 hover-orange fs-4" title="YouTube">
                                <i class="fab fa-youtube"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Événements par catégorie (dynamique) --}}
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="fw-bold mb-3">
                    <i class="fas fa-calendar me-2" style="color: #FF6B35;"></i>
                    Événements
                </h6>
                <ul class="list-unstyled">
                    @php
                        $footerCategories = \App\Models\EventCategory::withActiveEvents()
                            ->orderBy('name')
                            ->limit(6)
                            ->get();
                    @endphp
                    
                    {{-- Lien "Tous les événements" --}}
                    <li class="mb-2">
                        <a href="{{ route('events.all') }}" 
                           class="text-light text-decoration-none footer-link d-flex align-items-center">
                            <i class="fas fa-globe me-2"></i>Tous les événements
                        </a>
                    </li>
                    
                    {{-- Catégories dynamiques --}}
                    @forelse($footerCategories as $category)
                        <li class="mb-2">
                            <a href="{{ route('categories.show', $category->slug) }}" 
                               class="text-light text-decoration-none footer-link d-flex align-items-center">
                                <i class="{{ $category->icon ?? 'fas fa-calendar-alt' }} me-2"></i>
                                {{ $category->name }}
                                @if($category->activeEventsCount() > 0)
                                    <span class="badge bg-orange-light ms-auto">{{ $category->activeEventsCount() }}</span>
                                @endif
                            </a>
                        </li>
                    @empty
                        {{-- Fallback si aucune catégorie --}}
                        <li class="mb-2">
                            <a href="{{ route('events.all') }}" class="text-light text-decoration-none footer-link">
                                <i class="fas fa-search me-2"></i>Découvrir les événements
                            </a>
                        </li>
                    @endforelse
                </ul>
            </div>
            
            {{-- Pages d'aide (dynamique) --}}
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="fw-bold mb-3">
                    <i class="fas fa-question-circle me-2" style="color: #FF6B35;"></i>
                    Aide & Support
                </h6>
                <ul class="list-unstyled">
                    @php
                        $helpPages = \App\Models\Page::where('is_active', true)
                            ->where(function($query) {
                                $query->whereIn('slug', ['how-it-works', 'support', 'faq', 'contact', 'guide'])
                                      ->orWhere('title', 'like', '%aide%')
                                      ->orWhere('title', 'like', '%support%')
                                      ->orWhere('title', 'like', '%faq%');
                            })
                            ->orderBy('menu_order')
                            ->limit(5)
                            ->get();
                    @endphp
                    
                    @forelse($helpPages as $page)
                        <li class="mb-2">
                            <a href="{{ route('pages.cms', $page->slug) }}" 
                               class="text-light text-decoration-none footer-link">
                                <i class="fas fa-chevron-right me-2 small"></i>{{ $page->title }}
                            </a>
                        </li>
                    @empty
                        {{-- Fallback --}}
                        <li class="mb-2">
                            <a href="{{ route('pages.cms', 'faq') }}" class="text-light text-decoration-none footer-link">
                                <i class="fas fa-question me-2"></i>FAQ
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="{{ route('pages.cms', 'contact') }}" class="text-light text-decoration-none footer-link">
                                <i class="fas fa-envelope me-2"></i>Contact
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="{{ route('pages.cms', 'support') }}" class="text-light text-decoration-none footer-link">
                                <i class="fas fa-headset me-2"></i>Support
                            </a>
                        </li>
                    @endforelse
                </ul>
            </div>
            
            {{-- Organisateurs & Promoteurs --}}
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="fw-bold mb-3">
                    <i class="fas fa-users me-2" style="color: #FF6B35;"></i>
                    Organisateurs
                </h6>
                <ul class="list-unstyled">
                    @php
                        $promoterPages = \App\Models\Page::where('is_active', true)
                            ->where(function($query) {
                                $query->whereIn('slug', ['become-promoter', 'promoter-guide', 'pricing', 'about'])
                                      ->orWhere('title', 'like', '%promoteur%')
                                      ->orWhere('title', 'like', '%organisateur%')
                                      ->orWhere('title', 'like', '%tarif%');
                            })
                            ->orderBy('menu_order')
                            ->limit(4)
                            ->get();
                    @endphp
                    
                    {{-- Lien inscription promoteur --}}
                    <li class="mb-2">
                        <a href="{{ route('register') }}" 
                           class="text-light text-decoration-none footer-link">
                            <i class="fas fa-user-plus me-2"></i>Devenir promoteur
                        </a>
                    </li>
                    
                    {{-- Pages dynamiques --}}
                    @forelse($promoterPages as $page)
                        <li class="mb-2">
                            <a href="{{ route('pages.cms', $page->slug) }}" 
                               class="text-light text-decoration-none footer-link">
                                <i class="fas fa-chevron-right me-2 small"></i>{{ $page->title }}
                            </a>
                        </li>
                    @empty
                        {{-- Fallback --}}
                        <li class="mb-2">
                            <a href="{{ route('pages.cms', 'about') }}" class="text-light text-decoration-none footer-link">
                                <i class="fas fa-info-circle me-2"></i>À propos
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="{{ route('pages.cms', 'pricing') }}" class="text-light text-decoration-none footer-link">
                                <i class="fas fa-tag me-2"></i>Tarifs
                            </a>
                        </li>
                    @endforelse
                </ul>
            </div>
            
            {{-- Pages légales (dynamique) --}}
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="fw-bold mb-3">
                    <i class="fas fa-balance-scale me-2" style="color: #FF6B35;"></i>
                    Légal
                </h6>
                <ul class="list-unstyled">
                    @php
                        $legalPages = \App\Models\Page::where('is_active', true)
                            ->whereIn('slug', ['terms', 'privacy', 'legal', 'mentions-legales'])
                            ->orderBy('menu_order')
                            ->get();
                    @endphp
                    
                    @forelse($legalPages as $page)
                        <li class="mb-2">
                            <a href="{{ route('pages.cms', $page->slug) }}" 
                               class="text-light text-decoration-none footer-link">
                                <i class="fas fa-file-alt me-2"></i>{{ $page->title }}
                            </a>
                        </li>
                    @empty
                        {{-- Fallback --}}
                        <li class="mb-2">
                            <a href="{{ route('pages.cms', 'terms') }}" class="text-light text-decoration-none footer-link">
                                <i class="fas fa-file-contract me-2"></i>Conditions d'utilisation
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="{{ route('pages.cms', 'privacy') }}" class="text-light text-decoration-none footer-link">
                                <i class="fas fa-shield-alt me-2"></i>Politique de confidentialité
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="{{ route('pages.cms', 'legal') }}" class="text-light text-decoration-none footer-link">
                                <i class="fas fa-gavel me-2"></i>Mentions légales
                            </a>
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
        
        {{-- Séparateur --}}
        <hr class="my-4" style="border-color: rgba(255,255,255,0.2);">
        
        {{-- Copyright et informations finales --}}
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="mb-0 text-light-emphasis">
                    &copy; {{ date('Y') }} ClicBillet CI. Tous droits réservés.
                    <br class="d-md-none">
                    <small class="text-white-50">
                        Plateforme de billetterie en ligne - Côte d'Ivoire
                    </small>
                </p>
            </div>
            
            <div class="col-md-6 text-md-end">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-end gap-3">
                    {{-- Informations de contact rapide --}}
                    <div class="text-white-50 small">
                        <i class="fas fa-envelope me-1"></i>
                        <a href="mailto:contact@clicbillet.ci" class="text-white-50 text-decoration-none">
                            contact@clicbillet.ci
                        </a>
                    </div>
                    
                    {{-- Indicateur de version ou statut --}}
                    <div class="text-white-50 small">
                        <i class="fas fa-circle text-success me-1" style="font-size: 0.5rem;"></i>
                        Service en ligne
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>


    
    
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