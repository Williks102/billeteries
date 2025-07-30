{{-- resources/views/partials/acheteur-sidebar.blade.php --}}
{{-- VERSION CORRIGÉE selon l'architecture existante --}}
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
    
    {{-- Résumé rapide CORRIGÉ --}}
    <div class="mt-4 pt-3" style="border-top: 1px solid #e9ecef;">
        <div class="text-center">
            <small class="text-muted d-block mb-2">Résumé</small>
            
            {{-- CORRECTION : Billets actifs via la relation User->tickets() --}}
            @php
                try {
                    // Utiliser la relation définie dans le modèle User
                    $activeTickets = auth()->user()->tickets()
                        ->where('status', 'active')
                        ->whereHas('ticketType.event', function($q) {
                            $q->where('event_date', '>=', now());
                        })
                        ->count();
                } catch (\Exception $e) {
                    // Fallback en cas d'erreur
                    $activeTickets = 0;
                }
            @endphp
            @if($activeTickets > 0)
                <a href="{{ route('acheteur.tickets') }}" 
                   class="nav-link p-2 mb-2" style="background: rgba(40, 167, 69, 0.1); border-radius: 6px;">
                    <i class="fas fa-ticket-alt me-2"></i>
                    <span class="badge bg-success">{{ $activeTickets }}</span>
                    <small class="d-block">Billets valides</small>
                </a>
            @endif
            
            {{-- CORRECTION : Commandes récentes via la relation User->orders() --}}
            @php
                try {
                    $recentOrders = auth()->user()->orders()
                        ->where('payment_status', 'paid')
                        ->whereDate('created_at', '>=', now()->subDays(7))
                        ->count();
                } catch (\Exception $e) {
                    // Fallback en cas d'erreur
                    $recentOrders = 0;
                }
            @endphp
            @if($recentOrders > 0)
                <a href="{{ route('acheteur.orders') }}" 
                   class="nav-link p-2 mb-2" style="background: rgba(23, 162, 184, 0.1); border-radius: 6px;">
                    <i class="fas fa-shopping-bag me-2"></i>
                    <span class="badge bg-info">{{ $recentOrders }}</span>
                    <small class="d-block">Commandes récentes</small>
                </a>
            @endif
            
            {{-- CORRECTION : Favoris avec vérification de l'existence du modèle --}}
            @php
                try {
                    $favoritesCount = 0;
                    // Vérifier si le modèle Favorite existe
                    if (class_exists('\App\Models\Favorite')) {
                        $favoritesCount = \App\Models\Favorite::where('user_id', auth()->id())->count();
                    }
                } catch (\Exception $e) {
                    // Le modèle n'existe pas encore ou erreur
                    $favoritesCount = 0;
                }
            @endphp
            @if($favoritesCount > 0)
                <a href="{{ route('acheteur.favorites') }}" 
                   class="nav-link p-2 mb-2" style="background: rgba(220, 53, 69, 0.1); border-radius: 6px;">
                    <i class="fas fa-heart me-2"></i>
                    <span class="badge bg-danger">{{ $favoritesCount }}</span>
                    <small class="d-block">Mes favoris</small>
                </a>
            @endif
        </div>
    </div>
    
    {{-- Actions rapides --}}
    <div class="mt-4">
        <small class="text-muted d-block mb-2">Actions rapides</small>
        <div class="d-grid gap-2">
            <a href="{{ route('events.all') }}" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-plus me-2"></i>Découvrir des événements
            </a>
        </div>
    </div>
</div>