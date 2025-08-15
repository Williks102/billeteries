
<!-- Header unifié pour ClicBillet CI -->
<!-- NAVBAR DESKTOP (existante) - Masquée sur mobile -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top d-none d-md-block">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand fw-bold d-flex align-items-center" href="{{ route('home') }}">
            <i class="fas fa-ticket-alt me-2 text-orange"></i>
            <span class="brand-text">ClicBillet</span>
            <small class="text-muted ms-2 d-none d-md-inline">CI</small>
        </a>
        
        <!-- Bouton mobile toggle -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Navigation principale -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Menu principal (gauche) -->
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">
                        <i class="fas fa-home me-1 d-lg-none"></i>Accueil
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('events.all') ? 'active' : '' }}" href="{{ route('events.all') }}">
                        <i class="fas fa-calendar me-1 d-lg-none"></i>Événements
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="#">
                        <i class="fas fa-tags me-1 d-lg-none"></i>Catégories
                    </a>
                </li>
            </ul>
            
            <!-- Actions utilisateur (droite) -->
            <ul class="navbar-nav">
                @auth
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            {{ Auth::user()->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            @if(auth()->user()->isAdmin())
                                <!-- Menu Admin -->
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.dashboard') }}">
                                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard Admin
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.users') }}">
                                        <i class="fas fa-users me-2"></i>Utilisateurs
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.events') }}">
                                        <i class="fas fa-calendar-alt me-2"></i>Événements
                                    </a>
                                </li>
                                
                            @elseif(auth()->user()->isPromoteur())
                                <!-- Menu Promoteur -->
                                <li>
                                    <a class="dropdown-item" href="{{ route('promoteur.dashboard') }}">
                                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('promoteur.events.index') }}">
                                        <i class="fas fa-calendar-alt me-2"></i>Mes événements
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('promoteur.sales') }}">
                                        <i class="fas fa-chart-line me-2"></i>Ventes
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('promoteur.scanner') }}">
                                        <i class="fas fa-qrcode me-2"></i>Scanner QR
                                    </a>
                                </li>
                                
                            @else
                                <!-- Menu Acheteur -->
                                <li>
                                    <a class="dropdown-item" href="{{ route('acheteur.dashboard') }}">
                                        <i class="fas fa-tachometer-alt me-2"></i>Mes billets
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('acheteur.tickets') }}">
                                        <i class="fas fa-ticket-alt me-2"></i>Historique
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('cart.show') }}">
                                        <i class="fas fa-shopping-cart me-2"></i>Mon panier
                                    </a>
                                </li>
                            @endif
                            
                            <!-- Options communes -->
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="{{ 
                                    auth()->user()->isAdmin() ? route('admin.profile') : 
                                    (auth()->user()->isPromoteur() ? route('promoteur.profile') : route('acheteur.profile')) 
                                }}">
                                    <i class="fas fa-user-cog me-2"></i>
                                    {{ auth()->user()->isAdmin() ? 'Paramètres admin' : 'Mon profil' }}
                                </a>
                            </li>
                            
                            @if(!auth()->user()->isAdmin())
                            <li>
                                <a class="dropdown-item" href="{{ route('home') }}">
                                    <i class="fas fa-home me-2"></i>Retour à l'accueil
                                </a>
                            </li>
                            @else
                            <li>
                                <a class="dropdown-item" href="{{ route('home') }}">
                                    <i class="fas fa-eye me-2"></i>Voir le site public
                                </a>
                            </li>
                            @endif
                            
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="{{ route('logout') }}"
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Formulaire de déconnexion -->
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                @else
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">
                            <i class="fas fa-sign-in-alt me-1 d-lg-none"></i>Connexion
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('register') }}">
                            <i class="fas fa-user-plus me-1 d-lg-none"></i>S'inscrire
                        </a>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>

<!-- NAVBAR MOBILE avec ID uniques et état initial correct -->
<nav class="mobile-only-navbar d-block d-md-none">
    <div class="navbar-container">
        <!-- Menu burger avec ID unique -->
        <button class="navbar-toggle" id="mobileNavToggle" aria-label="Menu">
            <span class="burger-line"></span>
            <span class="burger-line"></span>
            <span class="burger-line"></span>
        </button>
        
        <!-- Logo -->
        <div class="navbar-brand">
            <a href="{{ route('home') }}" class="brand-link">
                <span class="brand-text">CLICBILLET</span>
                <span class="brand-suffix">.ci</span>
            </a>
        </div>
        
        <!-- Actions à droite -->
        <div class="navbar-actions">
            <!-- Bouton recherche -->
            <button class="action-btn search-btn" id="mobileSearchToggle" aria-label="Recherche">
                <i class="fas fa-search"></i>
            </button>
            
            <!-- Bouton profil/compte -->
            <div class="profile-dropdown">
                <button class="action-btn profile-btn" id="mobileProfileToggle" aria-label="Profil">
                    <i class="fas fa-user"></i>
                </button>
                
                <!-- Menu dropdown profil - ÉTAT INITIAL: FERMÉ -->
                <div class="dropdown-menu" id="mobileProfileMenu" style="opacity: 0; visibility: hidden;">
                    @auth
                        <div class="dropdown-header">
                            <div class="user-info">
                                <div class="user-avatar">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                                <div class="user-details">
                                    <span class="user-name">{{ auth()->user()->name }}</span>
                                    <span class="user-email">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="dropdown-item">
                                <i class="fas fa-tachometer-alt"></i>
                                <span>Dashboard Admin</span>
                            </a>
                        @elseif(auth()->user()->isPromoteur())
                            <a href="{{ route('promoteur.dashboard') }}" class="dropdown-item">
                                <i class="fas fa-tachometer-alt"></i>
                                <span>Dashboard</span>
                            </a>
                        @else
                            <a href="{{ route('acheteur.dashboard') }}" class="dropdown-item">
                                <i class="fas fa-tachometer-alt"></i>
                                <span>Mes billets</span>
                            </a>
                        @endif
                        
                        <div class="dropdown-divider"></div>
                        
                        <form action="{{ route('logout') }}" method="POST" class="dropdown-form">
                            @csrf
                            <button type="submit" class="dropdown-item logout-btn">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Déconnexion</span>
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="dropdown-item">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Connexion</span>
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
    
    <!-- Menu mobile overlay - ÉTAT INITIAL: FERMÉ -->
    <div class="mobile-overlay" id="mobileNavOverlay" style="opacity: 0; visibility: hidden;">
        <div class="mobile-menu">
            <div class="mobile-header">
                <span class="mobile-title">Menu</span>
                <button class="mobile-close" id="mobileNavClose" aria-label="Fermer">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="mobile-content">
                <a href="{{ route('home') }}" class="mobile-link">
                    <i class="fas fa-home"></i>
                    <span>Accueil</span>
                </a>
                <a href="{{ route('events.all') }}" class="mobile-link">
                    <i class="fas fa-calendar"></i>
                    <span>Événements</span>
                </a>
                
                @auth
                    <div class="mobile-divider"></div>
                    <div class="mobile-user">
                        <div class="mobile-user-info">
                            <div class="mobile-avatar">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                            <div class="mobile-user-details">
                                <span class="mobile-user-name">{{ auth()->user()->name }}</span>
                                <span class="mobile-user-role">{{ ucfirst(auth()->user()->role) }}</span>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="mobile-divider"></div>
                    <a href="{{ route('login') }}" class="mobile-link">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Connexion</span>
                    </a>
                @endauth
            </div>
        </div>
    </div>
    
    <!-- Barre de recherche mobile - ÉTAT INITIAL: FERMÉ -->
    <div class="search-overlay" id="mobileSearchOverlay" style="opacity: 0; visibility: hidden;">
        <div class="search-container">
            <div class="search-header">
                <button class="search-back" id="mobileSearchBack" aria-label="Retour">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <form action="{{ route('search') }}" method="GET" class="search-form-mobile">
                    <div class="search-input-wrapper">
                        <input type="text" name="q" class="search-input" placeholder="Rechercher un événement..." id="mobileSearchInput">
                        <button class="search-clear" type="button" id="mobileSearchClear" style="opacity: 0;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</nav>


@push('scripts')
<script>
// Attendre que TOUT soit chargé
window.addEventListener('load', function() {
    // Délai pour s'assurer que Bootstrap est initialisé
    setTimeout(function() {
        console.log('🚀 Initialisation navbar mobile...');
        
        // Elements avec les nouveaux ID
        const navToggle = document.getElementById('mobileNavToggle');
        const navOverlay = document.getElementById('mobileNavOverlay');
        const navClose = document.getElementById('mobileNavClose');
        const profileToggle = document.getElementById('mobileProfileToggle');
        const profileMenu = document.getElementById('mobileProfileMenu');
        const searchToggle = document.getElementById('mobileSearchToggle');
        const searchOverlay = document.getElementById('mobileSearchOverlay');
        const searchBack = document.getElementById('mobileSearchBack');
        const searchInput = document.getElementById('mobileSearchInput');
        const searchClear = document.getElementById('mobileSearchClear');
        
        // Debug - vérifier les éléments
        console.log('📱 Éléments trouvés:', {
            navToggle: !!navToggle,
            navOverlay: !!navOverlay,
            profileToggle: !!profileToggle,
            searchToggle: !!searchToggle
        });
        
        // FORCER la fermeture initiale
        if (navOverlay) {
            navOverlay.classList.remove('show');
            navOverlay.style.opacity = '0';
            navOverlay.style.visibility = 'hidden';
        }
        if (profileMenu) {
            profileMenu.classList.remove('show');
            profileMenu.style.opacity = '0';
            profileMenu.style.visibility = 'hidden';
        }
        if (searchOverlay) {
            searchOverlay.classList.remove('show');
            searchOverlay.style.opacity = '0';
            searchOverlay.style.visibility = 'hidden';
        }
        if (navToggle) {
            navToggle.classList.remove('active');
        }
        
        // Mobile menu toggle
        if (navToggle && navOverlay) {
            navToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('🍔 Menu burger cliqué');
                
                this.classList.toggle('active');
                
                if (this.classList.contains('active')) {
                    navOverlay.classList.add('show');
                    navOverlay.style.opacity = '1';
                    navOverlay.style.visibility = 'visible';
                    document.body.style.overflow = 'hidden';
                } else {
                    navOverlay.classList.remove('show');
                    navOverlay.style.opacity = '0';
                    navOverlay.style.visibility = 'hidden';
                    document.body.style.overflow = '';
                }
            });
        }
        
        // Close mobile menu
        if (navClose && navOverlay && navToggle) {
            navClose.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('❌ Fermeture menu');
                
                navToggle.classList.remove('active');
                navOverlay.classList.remove('show');
                navOverlay.style.opacity = '0';
                navOverlay.style.visibility = 'hidden';
                document.body.style.overflow = '';
            });
        }
        
        // Close menu on overlay click
        if (navOverlay && navToggle) {
            navOverlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    console.log('🎯 Overlay cliqué');
                    navToggle.classList.remove('active');
                    this.classList.remove('show');
                    this.style.opacity = '0';
                    this.style.visibility = 'hidden';
                    document.body.style.overflow = '';
                }
            });
        }
        
        // Profile dropdown
        if (profileToggle && profileMenu) {
            profileToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('👤 Profil cliqué');
                
                const isShowing = profileMenu.classList.contains('show');
                
                if (isShowing) {
                    profileMenu.classList.remove('show');
                    profileMenu.style.opacity = '0';
                    profileMenu.style.visibility = 'hidden';
                } else {
                    profileMenu.classList.add('show');
                    profileMenu.style.opacity = '1';
                    profileMenu.style.visibility = 'visible';
                }
            });
        }
        
        // Close profile on outside click
        document.addEventListener('click', function() {
            if (profileMenu) {
                profileMenu.classList.remove('show');
                profileMenu.style.opacity = '0';
                profileMenu.style.visibility = 'hidden';
            }
        });
        
        // Search overlay
        if (searchToggle && searchOverlay) {
            searchToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('🔍 Recherche ouverte');
                
                searchOverlay.classList.add('show');
                searchOverlay.style.opacity = '1';
                searchOverlay.style.visibility = 'visible';
                document.body.style.overflow = 'hidden';
                
                setTimeout(() => {
                    if (searchInput) searchInput.focus();
                }, 300);
            });
        }
        
        // Close search
        if (searchBack && searchOverlay) {
            searchBack.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('🔙 Recherche fermée');
                
                searchOverlay.classList.remove('show');
                searchOverlay.style.opacity = '0';
                searchOverlay.style.visibility = 'hidden';
                document.body.style.overflow = '';
                
                if (searchInput) searchInput.value = '';
                if (searchClear) {
                    searchClear.style.opacity = '0';
                }
            });
        }
        
        // Search input
        if (searchInput && searchClear) {
            searchInput.addEventListener('input', function() {
                if (this.value.length > 0) {
                    searchClear.style.opacity = '1';
                } else {
                    searchClear.style.opacity = '0';
                }
            });
            
            searchClear.addEventListener('click', function() {
                searchInput.value = '';
                this.style.opacity = '0';
                searchInput.focus();
            });
        }
        
        // Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (navOverlay?.classList.contains('show')) {
                    navToggle?.classList.remove('active');
                    navOverlay.classList.remove('show');
                    navOverlay.style.opacity = '0';
                    navOverlay.style.visibility = 'hidden';
                    document.body.style.overflow = '';
                }
                
                if (searchOverlay?.classList.contains('show')) {
                    searchOverlay.classList.remove('show');
                    searchOverlay.style.opacity = '0';
                    searchOverlay.style.visibility = 'hidden';
                    document.body.style.overflow = '';
                }
                
                if (profileMenu) {
                    profileMenu.classList.remove('show');
                    profileMenu.style.opacity = '0';
                    profileMenu.style.visibility = 'hidden';
                }
            }
        });
        
        console.log('✅ Navbar mobile initialisée');
        
    }, 500); // Délai de 500ms pour éviter les conflits
});
</script>
@endpush
