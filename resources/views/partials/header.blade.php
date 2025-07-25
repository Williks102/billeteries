<!-- Header unifié pour ClicBillet CI -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
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
                    <a class="nav-link" href="{{ route('home') }}#events">
                        <i class="fas fa-calendar me-1 d-lg-none"></i>Événements
                    </a>
                </li>
            </ul>
            
            <!-- Navigation droite -->
            <ul class="navbar-nav align-items-center">
                
                <!-- Panier (visible seulement pour les acheteurs non connectés ou connectés) -->
                @if(!auth()->check() || (auth()->check() && auth()->user()->isAcheteur()))
                <li class="nav-item me-3">
                    <a class="nav-link position-relative cart-link" href="{{ route('cart.show') }}" id="cartLink">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-badge badge bg-orange position-absolute top-0 start-100 translate-middle" 
                              id="cartBadge" style="display: none;">0</span>
                        <span class="d-lg-none ms-2">Panier</span>
                    </a>
                </li>
                @endif
                
                @guest
                    <!-- Utilisateur non connecté -->
                    <li class="nav-item me-2">
                        <a class="nav-link" href="{{ route('login') }}">
                            <i class="fas fa-sign-in-alt me-1"></i>
                            <span class="d-none d-lg-inline">Connexion</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-orange btn-sm" href="{{ route('register') }}">
                            <i class="fas fa-user-plus me-1"></i>S'inscrire
                        </a>
                    </li>
                @else
                    <!-- Utilisateur connecté -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" 
                           role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            
                            <!-- Avatar et infos utilisateur -->
                            <div class="user-avatar me-2">
                                @if(auth()->user()->isAdmin())
                                    <i class="fas fa-shield-alt text-danger"></i>
                                @elseif(auth()->user()->isPromoteur())
                                    <i class="fas fa-bullhorn text-warning"></i>
                                @else
                                    <i class="fas fa-user text-primary"></i>
                                @endif
                            </div>
                            
                            <div class="d-flex flex-column d-none d-lg-block">
                                <span class="user-name">{{ auth()->user()->name }}</span>
                                <small class="user-role text-muted">
                                    @if(auth()->user()->isAdmin())
                                        Administrateur
                                    @elseif(auth()->user()->isPromoteur())
                                        Promoteur
                                    @else
                                        Acheteur
                                    @endif
                                </small>
                            </div>
                            
                            <span class="d-lg-none ms-2">{{ auth()->user()->name }}</span>
                        </a>
                        
                        <!-- Menu déroulant adaptatif selon le rôle -->
                        <ul class="dropdown-menu dropdown-menu-end">
                            <!-- En-tête du menu -->
                            <li class="dropdown-header d-lg-none">
                                <strong>{{ auth()->user()->name }}</strong><br>
                                <small class="text-muted">
                                    @if(auth()->user()->isAdmin())
                                        <i class="fas fa-shield-alt text-danger me-1"></i>Administrateur
                                    @elseif(auth()->user()->isPromoteur())
                                        <i class="fas fa-bullhorn text-warning me-1"></i>Promoteur
                                    @else
                                        <i class="fas fa-user text-primary me-1"></i>Acheteur
                                    @endif
                                </small>
                            </li>
                            <li><hr class="dropdown-divider d-lg-none"></li>
                            
                            @if(auth()->user()->isAdmin())
                                <!-- Menu Admin -->
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.dashboard') }}">
                                        <i class="fas fa-tachometer-alt me-2"></i>Tableau de bord
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.users') }}">
                                        <i class="fas fa-users me-2"></i>Utilisateurs
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.events') }}">
                                        <i class="fas fa-calendar me-2"></i>Événements
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.orders') }}">
                                        <i class="fas fa-shopping-cart me-2"></i>Commandes
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.commissions') }}">
                                        <i class="fas fa-coins me-2"></i>Commissions
                                    </a>
                                </li>
                                
                            @elseif(auth()->user()->isPromoteur())
                                <!-- Menu Promoteur -->
                                <li>
                                    <a class="dropdown-item" href="{{ route('promoteur.dashboard') }}">
                                        <i class="fas fa-tachometer-alt me-2"></i>Tableau de bord
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('promoteur.events.index') }}">
                                        <i class="fas fa-calendar me-2"></i>Mes événements
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('promoteur.events.create') }}">
                                        <i class="fas fa-plus me-2"></i>Créer un événement
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
                                    auth()->user()->isAdmin() ? route('admin.dashboard') : 
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
                @endguest
            </ul>
        </div>
    </div>
</nav>

<!-- Styles CSS pour le header -->
<style>
:root {
    --primary-orange: #FF6B35;
    --secondary-orange: #ff8c61;
    --dark-blue: #1a237e;
}

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

.user-avatar {
    width: 35px;
    height: 35px;
    background: rgba(255, 107, 53, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.user-name {
    font-size: 0.9rem;
    font-weight: 600;
    line-height: 1.2;
}

.user-role {
    font-size: 0.75rem;
    line-height: 1;
}

.dropdown-menu {
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border-radius: 12px;
    padding: 0.5rem 0;
    min-width: 250px;
}

.dropdown-item {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    margin: 0 0.5rem;
    transition: all 0.2s ease;
}

.dropdown-item:hover {
    background: rgba(255, 107, 53, 0.1);
    color: var(--primary-orange);
}

.dropdown-divider {
    margin: 0.5rem 1rem;
}

.dropdown-header {
    padding: 0.75rem 1.5rem;
    font-size: 0.9rem;
}

.navbar-toggler {
    border: none !important;
    box-shadow: none !important;
}

.navbar-toggler:focus {
    box-shadow: none;
}

/* Responsive */
@media (max-width: 991px) {
    .navbar-nav {
        padding-top: 1rem;
    }
    
    .nav-link {
        padding: 0.75rem 1rem;
        margin: 2px 0;
    }
    
    .dropdown-menu {
        min-width: auto;
        width: 100%;
        margin-top: 0.5rem;
    }
}

/* Animation pour le badge du panier */
@keyframes bounce {
    0%, 20%, 53%, 80%, 100% {
        transform: translate(-50%, -50%) scale(1);
    }
    40%, 43% {
        transform: translate(-50%, -50%) scale(1.1);
    }
    70% {
        transform: translate(-50%, -50%) scale(1.05);
    }
    90% {
        transform: translate(-50%, -50%) scale(1.02);
    }
}

.cart-badge.animate {
    animation: bounce 0.6s ease-in-out;
}
</style>

<!-- JavaScript pour la gestion du panier -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mettre à jour le badge du panier
    updateCartBadge();
    
    // Écouter les changements du panier (si vous utilisez des événements personnalisés)
    document.addEventListener('cartUpdated', updateCartBadge);
});

function updateCartBadge() {
    // Récupérer les données du panier via AJAX
    fetch('{{ route("cart.data") }}')
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById('cartBadge');
            const count = data.totalItems || 0;
            
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'flex';
                badge.classList.add('animate');
                
                // Retirer l'animation après qu'elle soit terminée
                setTimeout(() => {
                    badge.classList.remove('animate');
                }, 600);
            } else {
                badge.style.display = 'none';
            }
        })
        .catch(error => {
            console.log('Erreur lors de la récupération du panier:', error);
        });
}

// Fonction utilitaire pour ajouter un item au panier (à appeler depuis vos pages)
function addToCart(ticketTypeId, quantity = 1) {
    fetch('{{ route("cart.add") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            ticket_type_id: ticketTypeId,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartBadge();
            // Optionnel: afficher une notification
            showNotification('Billet ajouté au panier !', 'success');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur lors de l\'ajout au panier', 'error');
    });
}

// Fonction pour afficher des notifications (optionnelle)
function showNotification(message, type = 'info') {
    // Vous pouvez implémenter votre système de notifications ici
    // Par exemple avec Toast Bootstrap ou une autre librairie
    console.log(`${type.toUpperCase()}: ${message}`);
}
</script>