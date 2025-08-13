{{-- resources/views/partials/acheteur-sidebar.blade.php --}}
{{-- VERSION COMPLÈTEMENT CORRIGÉE avec protection auth --}}
<div class="acheteur-sidebar p-3">
    <h5 class="mb-4">
        <i class="fas fa-user-circle text-primary me-2"></i>
        Mon Espace
    </h5>
    
    <nav class="nav flex-column">
        <a class="nav-link {{ request()->routeIs('acheteur.dashboard') ? 'active' : '' }}" 
           href="{{ route('acheteur.dashboard') }}">
            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
        </a>
        
        <a class="nav-link {{ request()->routeIs('acheteur.tickets*') ? 'active' : '' }}" 
           href="{{ route('acheteur.tickets') }}">
            <i class="fas fa-ticket-alt me-2"></i>Mes Billets
        </a>
        
        <a class="nav-link {{ request()->routeIs('acheteur.orders*') ? 'active' : '' }}" 
           href="{{ route('acheteur.orders') }}">
            <i class="fas fa-shopping-bag me-2"></i>Mes Commandes
        </a>
        
        <a class="nav-link {{ request()->routeIs('acheteur.favorites*') ? 'active' : '' }}" 
           href="{{ route('acheteur.favorites') }}">
            <i class="fas fa-heart me-2"></i>Mes Favoris
        </a>
        
        <hr class="my-3">
        
        <a class="nav-link" href="{{ route('home') }}">
            <i class="fas fa-home me-2"></i>Découvrir
        </a>
        
        <a class="nav-link" href="{{ route('events.all') }}">
            <i class="fas fa-calendar-alt me-2"></i>Tous les événements
        </a>
        
        <a class="nav-link" href="{{ route('cart.show') }}">
            <i class="fas fa-shopping-cart me-2"></i>
            Mon Panier
            <span id="sidebar-cart-count" class="badge bg-danger ms-auto">0</span>
        </a>
        
        <hr class="my-3">
        
        <a class="nav-link" href="{{ route('acheteur.profile') }}">
            <i class="fas fa-user-cog me-2"></i>Mon profil
        </a>
    </nav>
    
    {{-- Résumé rapide AVEC PROTECTION AUTH --}}
    @auth
    <div class="mt-4 pt-3" style="border-top: 1px solid #e9ecef;">
        <div class="text-center">
            <small class="text-muted d-block mb-2">Résumé</small>
            
            {{-- CORRECTION : Protection complète avec try/catch --}}
            @php
                $activeTickets = 0;
                $recentOrders = 0;
                
                try {
                    if (auth()->check() && auth()->user()) {
                        // Compter les billets actifs via les commandes payées
                        $activeTickets = auth()->user()->orders()
                            ->where('payment_status', 'paid')
                            ->whereHas('event', function($q) {
                                $q->where('event_date', '>=', now());
                            })
                            ->withCount('tickets')
                            ->get()
                            ->sum('tickets_count');
                        
                        // Compter les commandes récentes
                        $recentOrders = auth()->user()->orders()
                            ->where('payment_status', 'paid')
                            ->whereDate('created_at', '>=', now()->subDays(7))
                            ->count();
                    }
                } catch (\Exception $e) {
                    // En cas d'erreur, on garde les valeurs par défaut (0)
                    \Log::warning('Erreur dans acheteur-sidebar: ' . $e->getMessage());
                }
            @endphp
            
            {{-- Affichage des billets actifs --}}
            @if($activeTickets > 0)
                <a href="{{ route('acheteur.tickets') }}" 
                   class="nav-link p-2 mb-2" style="background: rgba(40, 167, 69, 0.1); border-radius: 6px;">
                    <i class="fas fa-ticket-alt me-2"></i>
                    <span class="badge bg-success">{{ $activeTickets }}</span>
                    <small class="d-block">Billets valides</small>
                </a>
            @endif
            
            {{-- Affichage des commandes récentes --}}
            @if($recentOrders > 0)
                <a href="{{ route('acheteur.orders') }}" 
                   class="nav-link p-2 mb-2" style="background: rgba(23, 162, 184, 0.1); border-radius: 6px;">
                    <i class="fas fa-shopping-bag me-2"></i>
                    <span class="badge bg-info">{{ $recentOrders }}</span>
                    <small class="d-block">Cette semaine</small>
                </a>
            @endif
            
            {{-- Si aucune donnée --}}
            @if($activeTickets == 0 && $recentOrders == 0)
                <div class="text-center py-3">
                    <i class="fas fa-tickets-alt text-muted mb-2" style="font-size: 2rem;"></i>
                    <small class="text-muted d-block">Pas encore de billets</small>
                    <a href="{{ route('events.all') }}" class="btn btn-outline-primary btn-sm mt-2">
                        <i class="fas fa-search me-1"></i>Découvrir
                    </a>
                </div>
            @endif
        </div>
    </div>
    @else
    {{-- Message pour utilisateurs non connectés --}}
    <div class="mt-4 pt-3" style="border-top: 1px solid #e9ecef;">
        <div class="text-center py-3">
            <i class="fas fa-user-lock text-muted mb-2" style="font-size: 2rem;"></i>
            <small class="text-muted d-block mb-3">Connectez-vous pour voir vos billets</small>
            <a href="{{ route('login') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-sign-in-alt me-1"></i>Se connecter
            </a>
        </div>
    </div>
    @endauth
</div>

{{-- CSS pour améliorer l'apparence --}}
<style>
.acheteur-sidebar {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 20px rgba(0,0,0,0.08);
    position: sticky;
    top: 100px;
    max-height: calc(100vh - 120px);
    overflow-y: auto;
}

.acheteur-sidebar .nav-link {
    color: #6c757d;
    font-weight: 500;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    margin-bottom: 0.25rem;
    transition: all 0.3s ease;
    text-decoration: none;
}

.acheteur-sidebar .nav-link:hover {
    background-color: rgba(255, 107, 53, 0.1);
    color: #FF6B35;
    transform: translateX(5px);
}

.acheteur-sidebar .nav-link.active {
    background-color: #FF6B35;
    color: white !important;
    font-weight: 600;
}

.acheteur-sidebar .nav-link.active i {
    color: white;
}

.badge {
    font-size: 0.7rem;
}

@media (max-width: 768px) {
    .acheteur-sidebar {
        position: relative !important;
        top: auto;
        max-height: none;
        margin-bottom: 20px;
    }
}
</style>