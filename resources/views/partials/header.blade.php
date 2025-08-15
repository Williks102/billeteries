
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

<!-- NAVBAR MOBILE (nouvelle) - Visible uniquement sur mobile -->
<nav class="mobile-only-navbar d-block d-md-none">
    <div class="navbar-container">
        <!-- Menu burger (mobile uniquement) -->
        <button class="navbar-toggle" id="navbarToggle">
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
            <button class="action-btn search-btn" id="searchToggle">
                <i class="fas fa-search"></i>
            </button>
            
            <!-- Bouton profil/compte -->
            <div class="profile-dropdown">
                <button class="action-btn profile-btn" id="profileToggle">
                    <i class="fas fa-user"></i>
                </button>
                
                <!-- Menu dropdown profil -->
                <div class="dropdown-menu" id="profileMenu">
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
                            <a href="{{ route('admin.users') }}" class="dropdown-item">
                                <i class="fas fa-users"></i>
                                <span>Utilisateurs</span>
                            </a>
                            <a href="{{ route('admin.events') }}" class="dropdown-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Événements</span>
                            </a>
                        @elseif(auth()->user()->isPromoteur())
                            <a href="{{ route('promoteur.dashboard') }}" class="dropdown-item">
                                <i class="fas fa-tachometer-alt"></i>
                                <span>Dashboard</span>
                            </a>
                            <a href="{{ route('promoteur.events.index') }}" class="dropdown-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Mes événements</span>
                            </a>
                            <a href="{{ route('promoteur.sales') }}" class="dropdown-item">
                                <i class="fas fa-chart-line"></i>
                                <span>Ventes</span>
                            </a>
                            <a href="{{ route('promoteur.scanner') }}" class="dropdown-item">
                                <i class="fas fa-qrcode"></i>
                                <span>Scanner QR</span>
                            </a>
                        @else
                            <a href="{{ route('acheteur.dashboard') }}" class="dropdown-item">
                                <i class="fas fa-tachometer-alt"></i>
                                <span>Mes billets</span>
                            </a>
                            <a href="{{ route('acheteur.tickets') }}" class="dropdown-item">
                                <i class="fas fa-ticket-alt"></i>
                                <span>Historique</span>
                            </a>
                            <a href="{{ route('cart.show') }}" class="dropdown-item">
                                <i class="fas fa-shopping-cart"></i>
                                <span>Mon panier</span>
                            </a>
                        @endif
                        
                        <div class="dropdown-divider"></div>
                        
                        <a href="{{ 
                            auth()->user()->isAdmin() ? route('admin.profile') : 
                            (auth()->user()->isPromoteur() ? route('promoteur.profile') : route('acheteur.profile')) 
                        }}" class="dropdown-item">
                            <i class="fas fa-user-cog"></i>
                            <span>Mon profil</span>
                        </a>
                        
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
                        <a href="{{ route('register') }}" class="dropdown-item">
                            <i class="fas fa-user-plus"></i>
                            <span>Inscription</span>
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
    
    <!-- Menu mobile overlay -->
    <div class="mobile-overlay" id="mobileOverlay">
        <div class="mobile-menu">
            <div class="mobile-header">
                <span class="mobile-title">Menu</span>
                <button class="mobile-close" id="mobileClose">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="mobile-content">
                <a href="{{ route('home') }}" class="mobile-link {{ request()->routeIs('home') ? 'active' : '' }}">
                    <i class="fas fa-home"></i>
                    <span>Accueil</span>
                </a>
                <a href="{{ route('events.all') }}" class="mobile-link {{ request()->routeIs('events.*') ? 'active' : '' }}">
                    <i class="fas fa-calendar"></i>
                    <span>Événements</span>
                </a>
                <a href="#" class="mobile-link">
                    <i class="fas fa-tags"></i>
                    <span>Catégories</span>
                </a>
                
                @auth
                    <div class="mobile-divider"></div>
                    
                    <!-- Liens spécifiques au rôle -->
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="mobile-link">
                            <i class="fas fa-shield-alt"></i>
                            <span>Administration</span>
                        </a>
                    @elseif(auth()->user()->isPromoteur())
                        <a href="{{ route('promoteur.dashboard') }}" class="mobile-link">
                            <i class="fas fa-chart-line"></i>
                            <span>Espace promoteur</span>
                        </a>
                    @else
                        <a href="{{ route('cart.show') }}" class="mobile-link">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Mon panier</span>
                        </a>
                    @endif
                    
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
                    <a href="{{ route('register') }}" class="mobile-link">
                        <i class="fas fa-user-plus"></i>
                        <span>Inscription</span>
                    </a>
                @endauth
            </div>
        </div>
    </div>
    
    <!-- Barre de recherche mobile -->
    <div class="search-overlay" id="searchOverlay">
        <div class="search-container">
            <div class="search-header">
                <button class="search-back" id="searchBack">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <form action="{{ route('search') }}" method="GET" class="search-form-mobile">
                    <div class="search-input-wrapper">
                        <input type="text" name="q" class="search-input" placeholder="Rechercher un événement..." id="mobileSearchInput">
                        <button class="search-clear" type="button" id="searchClear">
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
document.addEventListener('DOMContentLoaded', function() {
    // Elements de la navbar mobile
    const navbarToggle = document.getElementById('navbarToggle');
    const mobileOverlay = document.getElementById('mobileOverlay');
    const mobileClose = document.getElementById('mobileClose');
    const profileToggle = document.getElementById('profileToggle');
    const profileMenu = document.getElementById('profileMenu');
    const searchToggle = document.getElementById('searchToggle');
    const searchOverlay = document.getElementById('searchOverlay');
    const searchBack = document.getElementById('searchBack');
    const searchInput = document.getElementById('mobileSearchInput');
    const searchClear = document.getElementById('searchClear');
    
    // Debug pour vérifier les éléments
    console.log('Navbar elements found:', {
        navbarToggle: !!navbarToggle,
        mobileOverlay: !!mobileOverlay,
        profileToggle: !!profileToggle,
        searchToggle: !!searchToggle
    });
    
    // Mobile menu toggle
    if (navbarToggle && mobileOverlay) {
        navbarToggle.addEventListener('click', function() {
            console.log('Menu burger clicked');
            this.classList.toggle('active');
            mobileOverlay.classList.toggle('show');
            document.body.style.overflow = mobileOverlay.classList.contains('show') ? 'hidden' : '';
        });
    }
    
    // Close mobile menu
    if (mobileClose && mobileOverlay && navbarToggle) {
        mobileClose.addEventListener('click', function() {
            console.log('Close button clicked');
            navbarToggle.classList.remove('active');
            mobileOverlay.classList.remove('show');
            document.body.style.overflow = '';
        });
    }
    
    // Close mobile menu on overlay click
    if (mobileOverlay && navbarToggle) {
        mobileOverlay.addEventListener('click', function(e) {
            if (e.target === this) {
                console.log('Overlay clicked');
                navbarToggle.classList.remove('active');
                this.classList.remove('show');
                document.body.style.overflow = '';
            }
        });
    }
    
    // Profile dropdown toggle
    if (profileToggle && profileMenu) {
        profileToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            console.log('Profile button clicked');
            profileMenu.classList.toggle('show');
        });
    }
    
    // Close profile dropdown when clicking outside
    document.addEventListener('click', function() {
        if (profileMenu) {
            profileMenu.classList.remove('show');
        }
    });
    
    // Prevent dropdown close when clicking inside
    if (profileMenu) {
        profileMenu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
    
    // Search overlay toggle
    if (searchToggle && searchOverlay) {
        searchToggle.addEventListener('click', function() {
            console.log('Search button clicked');
            searchOverlay.classList.add('show');
            document.body.style.overflow = 'hidden';
            setTimeout(() => {
                if (searchInput) searchInput.focus();
            }, 300);
        });
    }
    
    // Close search overlay
    if (searchBack && searchOverlay && searchClear) {
        searchBack.addEventListener('click', function() {
            console.log('Search back clicked');
            searchOverlay.classList.remove('show');
            document.body.style.overflow = '';
            if (searchInput) searchInput.value = '';
            searchClear.classList.remove('show');
        });
    }
    
    // Search input handling
    if (searchInput && searchClear) {
        searchInput.addEventListener('input', function() {
            if (this.value.length > 0) {
                searchClear.classList.add('show');
            } else {
                searchClear.classList.remove('show');
            }
        });
    }
    
    // Clear search
    if (searchClear && searchInput) {
        searchClear.addEventListener('click', function() {
            searchInput.value = '';
            this.classList.remove('show');
            searchInput.focus();
        });
    }
    
    // Close overlays on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            // Close mobile menu
            if (mobileOverlay?.classList.contains('show')) {
                navbarToggle?.classList.remove('active');
                mobileOverlay.classList.remove('show');
                document.body.style.overflow = '';
            }
            
            // Close search overlay
            if (searchOverlay?.classList.contains('show')) {
                searchOverlay.classList.remove('show');
                document.body.style.overflow = '';
                if (searchInput) searchInput.value = '';
                searchClear?.classList.remove('show');
            }
            
            // Close profile dropdown
            profileMenu?.classList.remove('show');
        }
    });
    
    // Restore scroll on window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            document.body.style.overflow = '';
            mobileOverlay?.classList.remove('show');
            searchOverlay?.classList.remove('show');
            navbarToggle?.classList.remove('active');
        }
    });
});
</script>
@endpush
