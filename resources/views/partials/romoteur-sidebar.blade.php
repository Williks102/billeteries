{{-- resources/views/partials/promoteur-sidebar.blade.php --}}
<div class="promoteur-sidebar p-3">
    <h5 class="mb-4">
        <i class="fas fa-ticket-alt text-primary me-2"></i>
        Espace Promoteur
    </h5>
    
    <nav class="nav flex-column">
        <a class="nav-link {{ request()->routeIs('promoteur.dashboard') ? 'active' : '' }}" 
           href="{{ route('promoteur.dashboard') }}">
            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
        </a>
        
        <a class="nav-link {{ request()->routeIs('promoteur.events*') ? 'active' : '' }}" 
           href="{{ route('promoteur.events.index') }}">
            <i class="fas fa-calendar-alt me-2"></i>Mes Événements
        </a>
        
        <a class="nav-link {{ request()->routeIs('promoteur.scanner*') ? 'active' : '' }}" 
           href="{{ route('promoteur.scanner') }}">
            <i class="fas fa-qrcode me-2"></i>Scanner Billets
        </a>
        
        <a class="nav-link {{ request()->routeIs('promoteur.sales*') ? 'active' : '' }}" 
           href="{{ route('promoteur.sales') }}">
            <i class="fas fa-chart-bar me-2"></i>Ventes
        </a>
        
        <a class="nav-link {{ request()->routeIs('promoteur.commissions*') ? 'active' : '' }}" 
           href="{{ route('promoteur.commissions') }}">
            <i class="fas fa-coins me-2"></i>Commissions
        </a>
        
        <a class="nav-link {{ request()->routeIs('promoteur.reports*') ? 'active' : '' }}" 
           href="{{ route('promoteur.reports') }}">
            <i class="fas fa-file-chart me-2"></i>Rapports
        </a>
        
        <hr class="my-3">
        
        <a class="nav-link" href="{{ route('home') }}" target="_blank">
            <i class="fas fa-external-link-alt me-2"></i>Voir le site
        </a>
        
        <a class="nav-link" href="{{ route('promoteur.profile') }}">
            <i class="fas fa-user-cog me-2"></i>Mon profil
        </a>
    </nav>
    
    {{-- Indicateurs rapides --}}
    <div class="mt-4 pt-3" style="border-top: 1px solid #e9ecef;">
        <div class="text-center">
            <small class="text-muted d-block mb-2">Aperçu rapide</small>
            
            {{-- Événements actifs --}}
            @php
                $activeEvents = \App\Models\Event::where('promoteur_id', auth()->id())
                                                ->where('status', 'published')
                                                ->where('event_date', '>=', now())
                                                ->count();
            @endphp
            @if($activeEvents > 0)
                <a href="{{ route('promoteur.events.index') }}" 
                   class="nav-link p-2 mb-2" style="background: rgba(40, 167, 69, 0.1); border-radius: 6px;">
                    <i class="fas fa-calendar-check me-2"></i>
                    <span class="badge bg-success">{{ $activeEvents }}</span>
                    <small class="d-block">Événements actifs</small>
                </a>
            @endif
            
            {{-- Ventes du jour --}}
            @php
                $todaySales = \App\Models\Order::whereHas('event', function($q) {
                    $q->where('promoteur_id', auth()->id());
                })->whereDate('created_at', today())
                  ->where('payment_status', 'paid')
                  ->sum('total_amount');
            @endphp
            @if($todaySales > 0)
                <a href="{{ route('promoteur.sales') }}" 
                   class="nav-link p-2 mb-2" style="background: rgba(255, 107, 53, 0.1); border-radius: 6px;">
                    <i class="fas fa-money-bill-wave me-2"></i>
                    <div class="fw-bold">{{ number_format($todaySales) }} FCFA</div>
                    <small class="d-block">Ventes aujourd'hui</small>
                </a>
            @endif
            
            {{-- Commissions en attente --}}
            @php
                $pendingCommissions = \App\Models\Commission::where('promoter_id', auth()->id())
                                                          ->where('status', 'pending')
                                                          ->count();
            @endphp
            @if($pendingCommissions > 0)
                <a href="{{ route('promoteur.commissions') }}" 
                   class="nav-link p-2 mb-2" style="background: rgba(255, 193, 7, 0.1); border-radius: 6px;">
                    <i class="fas fa-clock me-2"></i>
                    <span class="badge bg-warning text-dark">{{ $pendingCommissions }}</span>
                    <small class="d-block">Commissions en attente</small>
                </a>
            @endif
        </div>
    </div>
    
    {{-- Raccourcis rapides --}}
    <div class="mt-4">
        <small class="text-muted d-block mb-2">Actions rapides</small>
        <div class="d-grid gap-2">
            <a href="{{ route('promoteur.events.create') }}" class="btn btn-sm btn-promoteur">
                <i class="fas fa-plus me-2"></i>Nouvel événement
            </a>
        </div>
    </div>
</div>

{{-- ================================================================== --}}
{{-- resources/views/partials/acheteur-sidebar.blade.php --}}
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
    
    {{-- Résumé rapide --}}
    <div class="mt-4 pt-3" style="border-top: 1px solid #e9ecef;">
        <div class="text-center">
            <small class="text-muted d-block mb-2">Résumé</small>
            
            {{-- Billets actifs --}}
            @php
                $activeTickets = \App\Models\Ticket::where('user_id', auth()->id())
                                                 ->where('status', 'active')
                                                 ->whereHas('ticketType.event', function($q) {
                                                     $q->where('event_date', '>=', now());
                                                 })
                                                 ->count();
            @endphp
            @if($activeTickets > 0)
                <a href="{{ route('acheteur.tickets') }}" 
                   class="nav-link p-2 mb-2" style="background: rgba(40, 167, 69, 0.1); border-radius: 6px;">
                    <i class="fas fa-ticket-alt me-2"></i>
                    <span class="badge bg-success">{{ $activeTickets }}</span>
                    <small class="d-block">Billets valides</small>
                </a>
            @endif
            
            {{-- Commandes récentes --}}
            @php
                $recentOrders = \App\Models\Order::where('user_id', auth()->id())
                                                ->where('payment_status', 'paid')
                                                ->whereDate('created_at', '>=', now()->subDays(7))
                                                ->count();
            @endphp
            @if($recentOrders > 0)
                <a href="{{ route('acheteur.orders') }}" 
                   class="nav-link p-2 mb-2" style="background: rgba(23, 162, 184, 0.1); border-radius: 6px;">
                    <i class="fas fa-shopping-bag me-2"></i>
                    <span class="badge bg-info">{{ $recentOrders }}</span>
                    <small class="d-block">Commandes récentes</small>
                </a>
            @endif
            
            {{-- Favoris --}}
            @php
                $favoritesCount = \App\Models\Favorite::where('user_id', auth()->id())->count();
            @endphp
            @if($favoritesCount > 0)
                <a href="{{ route('acheteur.favorites') }}" 
                   class="nav-link p-2 mb-2" style="background: rgba(220, 53, 69, 0.1); border-radius: 6px;">
                    <i class="fas fa-heart me-2"></i>
                    <span class="badge bg-danger">{{ $favoritesCount }}</span>
                    <small class="d-block">Favoris</small>
                </a>
            @endif
        </div>
    </div>
    
    {{-- Actions rapides --}}
    <div class="mt-4">
        <small class="text-muted d-block mb-2">Actions rapides</small>
        <div class="d-grid gap-2">
            <a href="{{ route('events.all') }}" class="btn btn-sm btn-acheteur">
                <i class="fas fa-search me-2"></i>Chercher événements
            </a>
        </div>
    </div>
    
    {{-- Prochains événements --}}
    @php
        $upcomingEvents = \App\Models\Ticket::where('user_id', auth()->id())
            ->where('status', 'active')
            ->with(['ticketType.event'])
            ->whereHas('ticketType.event', function($q) {
                $q->where('event_date', '>=', now())
                  ->where('event_date', '<=', now()->addDays(7));
            })
            ->limit(3)
            ->get();
    @endphp
    
    @if($upcomingEvents->count() > 0)
        <div class="mt-4">
            <small class="text-muted d-block mb-2">Prochains événements</small>
            @foreach($upcomingEvents as $ticket)
                <div class="card mb-2" style="font-size: 0.85rem;">
                    <div class="card-body p-2">
                        <div class="fw-bold text-truncate">{{ $ticket->ticketType->event->title }}</div>
                        <small class="text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            {{ $ticket->ticketType->event->event_date->format('d/m à H:i') }}
                        </small>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- ================================================================== --}}
{{-- resources/views/components/layout-switcher.blade.php --}}
{{-- Composant pour basculer automatiquement entre les layouts --}}

@php
    $user = auth()->user();
    $currentLayout = 'layouts.app'; // Layout par défaut

    if ($user) {
        $currentLayout = match($user->role) {
            'admin' => 'layouts.admin',
            'promoteur' => 'layouts.promoteur',
            'acheteur' => 'layouts.acheteur',
            default => 'layouts.app'
        };
    }
    
    // Override si spécifié explicitement
    if (isset($layout)) {
        $currentLayout = $layout;
    }
@endphp

@extends($currentLayout)

{{ $slot }}

{{-- ================================================================== --}}
{{-- resources/views/components/auto-sidebar.blade.php --}}
{{-- Composant pour afficher automatiquement la bonne sidebar --}}

@php
    $user = auth()->user();
@endphp

@if($user && $user->isAdmin())
    @include('partials.admin-sidebar')
@elseif($user && $user->isPromoteur())
    @include('partials.promoteur-sidebar')
@elseif($user && $user->isAcheteur())
    @include('partials.acheteur-sidebar')
@endif

{{-- ================================================================== --}}
{{-- Exemple d'utilisation dans une vue --}}
{{-- resources/views/admin/example-usage.blade.php --}}

{{-- 
Méthode 1: Utilisation directe du layout admin
@extends('layouts.admin')

Méthode 2: Utilisation du composant auto-layout
<x-layout-switcher>
    @section('content')
        <!-- Contenu ici -->
    @endsection
</x-layout-switcher>

Méthode 3: Utilisation de la directive Blade personnalisée
@autoExtends

Méthode 4: Layout automatique via middleware (pas besoin de spécifier)
Les routes avec middleware 'layout:admin' utilisent automatiquement layouts.admin
--}}