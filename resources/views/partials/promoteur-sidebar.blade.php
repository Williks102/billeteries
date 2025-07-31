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
