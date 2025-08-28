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
           href="{{ route('promoteur.scanner.index') }}">
            <i class="fas fa-qrcode me-2"></i>Scanner Billets
        </a>
        
        <a class="nav-link {{ request()->routeIs('promoteur.sales*') ? 'active' : '' }}" 
           href="{{ route('promoteur.sales') }}">
            <i class="fas fa-chart-line me-2"></i>Ventes & Revenus
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
                $activeEvents = \App\Models\Event::where('promoter_id', auth()->id())
                                                ->where('status', 'published')
                                                ->where('event_date', '>=', now())
                                                ->count();
            @endphp
            @if($activeEvents > 0)
                <a href="{{ route('promoteur.events.index') }}" 
                   class="btn btn-sm btn-outline-success mb-2 w-100">
                    {{ $activeEvents }} événement(s) actif(s)
                </a>
            @endif
            
            {{-- Ventes du mois --}}
            @php
                $monthlySales = auth()->user()->getSalesStats(
                    now()->startOfMonth(), 
                    now()->endOfMonth()
                );
            @endphp
            @if($monthlySales['total_revenue'] > 0)
                <a href="{{ route('promoteur.sales') }}" 
                   class="btn btn-sm btn-outline-primary w-100">
                    {{ number_format($monthlySales['total_revenue']) }} F ce mois
                </a>
            @endif
        </div>
    </div>
</div>