<!-- Header avec recherche desktop et navigation mobile optimisée -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <!-- VERSION MOBILE uniquement (d-lg-none) -->
        <div class="d-lg-none w-100 d-flex align-items-center justify-content-between">
            <!-- Hamburger -->
            <button class="navbar-toggler border-0 p-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Logo centré -->
            <a class="navbar-brand fw-bold d-flex align-items-center mx-auto" href="{{ route('home') }}">
                <i class="fas fa-ticket-alt me-2 text-orange"></i>
                <span class="brand-text">ClicBillet</span>
            </a>
            
            <!-- Actions droite : Search + Compte -->
            <div class="d-flex align-items-center gap-2">
                <!-- Bouton recherche mobile -->
                <button class="btn btn-link p-2 text-muted" type="button" data-bs-toggle="modal" data-bs-target="#mobileSearchModal">
                    <i class="fas fa-search"></i>
                </button>
                
                <!-- Menu compte mobile -->
                @auth
                    <div class="dropdown">
                        <button class="btn btn-link p-2 text-muted" type="button" data-bs-toggle="dropdown">
                            <div class="user-avatar">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li class="px-3 py-2 border-bottom">
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar me-2">{{ substr(auth()->user()->name, 0, 1) }}</div>
                                    <div>
                                        <div class="user-name">{{ auth()->user()->name }}</div>
                                        <div class="user-role">{{ ucfirst(auth()->user()->user_type) }}</div>
                                    </div>
                                </div>
                            </li>
                            @if(auth()->user()->isPromoteur())
                                <li><a class="dropdown-item" href="{{ route('promoteur.dashboard') }}"><i class="fas fa-tachometer-alt me-2"></i>Tableau de bord</a></li>
                            @elseif(auth()->user()->isAcheteur())
                                <li><a class="dropdown-item" href="{{ route('acheteur.dashboard') }}"><i class="fas fa-tachometer-alt me-2"></i>Tableau de bord</a></li>
                            @elseif(auth()->user()->isAdmin())
                                <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}"><i class="fas fa-tachometer-alt me-2"></i>Tableau de bord</a></li>
                            @endif
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="d-inline w-100">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger w-100 text-start">
                                        <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="btn btn-link p-2 text-muted">
                        <i class="fas fa-user"></i>
                    </a>
                @endauth
            </div>
        </div>
        
        <!-- VERSION DESKTOP (d-none d-lg-flex) -->
        <div class="d-none d-lg-flex w-100 align-items-center">
            <!-- Logo -->
            <a class="navbar-brand fw-bold d-flex align-items-center me-4" href="{{ route('home') }}">
                <i class="fas fa-ticket-alt me-2 text-orange"></i>
                <span class="brand-text">ClicBillet</span>
                <small class="text-muted ms-2">CI</small>
            </a>
            
            <!-- Menu navigation -->
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">
                        Accueil
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('events.all') ? 'active' : '' }}" href="{{ route('events.all') }}">
                        Événements
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('pages.contact') ? 'active' : '' }}" href="{{ route('pages.contact') }}">
                        Contact
                    </a>
                </li>
            </ul>
            
            <!-- BARRE DE RECHERCHE DESKTOP -->
            <div class="search-container me-4">
                <form action="{{ route('search') }}" method="GET" class="d-flex">
                    <div class="input-group search-input-group">
                        <input type="text" 
                               class="form-control search-input" 
                               name="q" 
                               placeholder="Rechercher un événement..."
                               value="{{ request('q') }}"
                               autocomplete="off">
                        <button class="btn btn-search" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Menu utilisateur desktop -->
            <ul class="navbar-nav">
                <!-- Panier -->
                <li class="nav-item">
                    <a class="nav-link cart-link position-relative" href="{{ route('cart.show') }}">
                        <i class="fas fa-shopping-cart"></i>
                        @if(session('cart') && count(session('cart')) > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-orange cart-badge">
                                {{ array_sum(array_column(session('cart'), 'quantity')) }}
                            </span>
                        @endif
                    </a>
                </li>
                
                @auth
                    <!-- Menu utilisateur connecté -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <div class="user-avatar me-2">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                            <div class="text-start d-none d-xl-block">
                                <div class="user-name">{{ auth()->user()->name }}</div>
                                <div class="user-role">{{ ucfirst(auth()->user()->user_type) }}</div>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            @if(auth()->user()->isPromoteur())
                                <li><a class="dropdown-item" href="{{ route('promoteur.dashboard') }}"><i class="fas fa-tachometer-alt me-2"></i>Tableau de bord</a></li>
                            @elseif(auth()->user()->isAcheteur())
                                <li><a class="dropdown-item" href="{{ route('acheteur.dashboard') }}"><i class="fas fa-tachometer-alt me-2"></i>Tableau de bord</a></li>
                            @elseif(auth()->user()->isAdmin())
                                <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}"><i class="fas fa-tachometer-alt me-2"></i>Tableau de bord</a></li>
                            @endif
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @else
                    <!-- Boutons connexion/inscription -->
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">Connexion</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('register') }}">
                            <span class="btn btn-orange btn-sm">Créer un compte</span>
                        </a>
                    </li>
                @endauth
            </ul>
        </div>
        
        <!-- Menu collapse pour mobile -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav d-block d-md-none">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">
                        <i class="fas fa-home me-2"></i>Accueil
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('events.all') ? 'active' : '' }}" href="{{ route('events.all') }}">
                        <i class="fas fa-calendar me-2"></i>Événements
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('pages.contact') ? 'active' : '' }}" href="{{ route('pages.contact') }}">
                        <i class="fas fa-envelope me-2"></i>Contact
                    </a>
                </li>
                
                <!-- Panier dans le menu mobile -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('cart.show') ? 'active' : '' }}" href="{{ route('cart.show') }}">
                        <i class="fas fa-shopping-cart me-2"></i>Panier
                        @if(session('cart') && count(session('cart')) > 0)
                            <span class="badge bg-orange ms-1">
                                {{ array_sum(array_column(session('cart'), 'quantity')) }}
                            </span>
                        @endif
                    </a>
                </li>
                
                @auth
                    <li class="nav-item border-top mt-3 pt-3">
                        @if(auth()->user()->isPromoteur())
                            <a class="nav-link" href="{{ route('promoteur.dashboard') }}">
                                <i class="fas fa-tachometer-alt me-2"></i>Tableau de bord
                            </a>
                        @elseif(auth()->user()->isAcheteur())
                            <a class="nav-link" href="{{ route('acheteur.dashboard') }}">
                                <i class="fas fa-tachometer-alt me-2"></i>Tableau de bord
                            </a>
                        @elseif(auth()->user()->isAdmin())
                            <a class="nav-link" href="{{ route('admin.dashboard') }}">
                                <i class="fas fa-tachometer-alt me-2"></i>Tableau de bord
                            </a>
                        @endif
                    </li>
                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="nav-link border-0 bg-transparent text-danger">
                                <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                            </button>
                        </form>
                    </li>
                @else
                    <li class="nav-item border-top mt-3 pt-3">
                        <a class="nav-link" href="{{ route('login') }}">
                            <i class="fas fa-sign-in-alt me-2"></i>Connexion
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('register') }}">
                            <i class="fas fa-user-plus me-2"></i>Créer un compte
                        </a>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>

<!-- Modal recherche mobile -->
<div class="modal fade" id="mobileSearchModal" tabindex="-1">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Rechercher un événement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('search') }}" method="GET">
                    <div class="input-group mb-3">
                        <input type="text" 
                               class="form-control form-control-lg" 
                               name="q" 
                               placeholder="Tapez le nom d'un événement..."
                               autocomplete="off"
                               autofocus>
                        <button class="btn btn-orange" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
                
                <!-- Suggestions de recherche -->
                <div class="mt-4">
                    <h6 class="text-muted mb-3">Recherches populaires</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('search', ['q' => 'concert']) }}" class="btn btn-outline-secondary btn-sm">Concert</a>
                        <a href="{{ route('search', ['q' => 'festival']) }}" class="btn btn-outline-secondary btn-sm">Festival</a>
                        <a href="{{ route('search', ['q' => 'spectacle']) }}" class="btn btn-outline-secondary btn-sm">Spectacle</a>
                        <a href="{{ route('search', ['q' => 'théâtre']) }}" class="btn btn-outline-secondary btn-sm">Théâtre</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Styles CSS -->
<style>
:root {
    --primary-orange: #FF6B35;
    --secondary-orange: #ff8c61;
    --dark-blue: #1a237e;
}

/* Styles généraux */
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
    padding: 8px 20px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-orange:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
    color: white;
}

/* Logo */
.navbar-brand {
    color: var(--dark-blue) !important;
    transition: all 0.3s ease;
}

.navbar-brand:hover {
    color: var(--primary-orange) !important;
}

.brand-text {
    font-size: 1.5rem;
    font-weight: 700;
}

/* Navigation */
.nav-link {
    color: #6c757d !important;
    font-weight: 500;
    transition: all 0.3s ease;
    border-radius: 8px;
    margin: 0 2px;
}

.nav-link:hover,
.nav-link.active {
    color: var(--primary-orange) !important;
    background: rgba(255, 107, 53, 0.1);
}

/* Barre de recherche desktop */
.search-container {
    min-width: 350px;
}

.search-input-group {
    background: #f8f9fa;
    border-radius: 25px;
    overflow: hidden;
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.search-input-group:focus-within {
    border-color: var(--primary-orange);
    box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
}

.search-input {
    border: none;
    background: transparent;
    padding: 12px 20px;
    font-size: 0.95rem;
}

.search-input:focus {
    box-shadow: none;
    background: transparent;
}

.btn-search {
    background: var(--primary-orange);
    border: none;
    color: white;
    padding: 12px 20px;
    transition: all 0.3s ease;
}

.btn-search:hover {
    background: var(--secondary-orange);
    color: white;
}

/* Avatar utilisateur */
.user-avatar {
    width: 35px;
    height: 35px;
    background: rgba(255, 107, 53, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-orange);
    font-weight: 600;
    font-size: 0.9rem;
}

.user-name {
    font-size: 0.9rem;
    font-weight: 600;
    line-height: 1.2;
    color: #333;
}

.user-role {
    font-size: 0.75rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Badge panier */
.cart-link {
    position: relative;
}

.cart-badge {
    font-size: 0.7rem;
    min-width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Mobile styles */
@media (max-width: 991.98px) {
    .navbar-toggler {
        border: none;
        padding: 8px;
    }
    
    .navbar-toggler:focus {
        box-shadow: none;
    }
    
    /* Centrer le logo sur mobile */
    .navbar-brand {
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
    }
    
    /* Ajustement des boutons d'action mobile */
    .btn-link {
        border: none;
        background: none;
        color: #6c757d;
    }
    
    .btn-link:hover {
        color: var(--primary-orange);
    }
}

/* Modal recherche mobile */
.modal-fullscreen .modal-body {
    padding: 2rem 1rem;
}

.modal-fullscreen .form-control-lg {
    padding: 1rem 1.5rem;
    font-size: 1.1rem;
}

/* Dropdown menu */
.dropdown-menu {
    border: none;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    border-radius: 12px;
    overflow: hidden;
}

.dropdown-item {
    padding: 12px 20px;
    transition: all 0.2s ease;
}

.dropdown-item:hover {
    background: rgba(255, 107, 53, 0.1);
    color: var(--primary-orange);
}

.dropdown-item i {
    width: 20px;
    text-align: center;
}
</style>