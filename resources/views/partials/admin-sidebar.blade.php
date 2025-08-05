{{-- resources/views/partials/admin-sidebar.blade.php --}}
<div class="admin-sidebar p-3">
    <h5 class="mb-4">
        <i class="fas fa-shield-alt text-orange me-2"></i>
        Administration
    </h5>
    
    <nav class="nav flex-column">
        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" 
           href="{{ route('admin.dashboard') }}">
            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
        </a>
        
        <a class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}" 
           href="{{ route('admin.users') }}">
            <i class="fas fa-users me-2"></i>Utilisateurs
        </a>
        
        <a class="nav-link {{ request()->routeIs('admin.events*') ? 'active' : '' }}" 
   href="{{ route('admin.events') }}">
   <i class="fas fa-calendar me-2"></i>Événements
</a>
        
        <a class="nav-link {{ request()->routeIs('admin.orders*') ? 'active' : '' }}" 
           href="{{ route('admin.orders') }}">
            <i class="fas fa-shopping-cart me-2"></i>Commandes
        </a>
        
        <a class="nav-link {{ request()->routeIs('admin.commissions*') ? 'active' : '' }}" 
           href="{{ route('admin.commissions') }}">
            <i class="fas fa-coins me-2"></i>Commissions
        </a>
        
        <a class="nav-link {{ request()->routeIs('admin.reports*') ? 'active' : '' }}" 
           href="{{ route('admin.reports') }}">
            <i class="fas fa-chart-line me-2"></i>Rapports
        </a>
        
        <a class="nav-link {{ request()->routeIs('admin.settings*') ? 'active' : '' }}" 
           href="{{ route('admin.settings') }}">
            <i class="fas fa-cog me-2"></i>Paramètres
        </a>

        <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.pages*') ? 'active' : '' }}" href="{{ route('admin.pages.index') }}">
        <i class="fas fa-file-alt"></i> Pages
        </a>
        </li>   
        
        <hr class="my-3">
        
        <a class="nav-link" href="{{ route('home') }}" target="_blank">
            <i class="fas fa-external-link-alt me-2"></i>Voir le site
        </a>
        
        <a class="nav-link" href="{{ route('admin.profile') }}">
            <i class="fas fa-user-cog me-2"></i>Mon profil
        </a>
    </nav>
    
    {{-- Indicateurs rapides --}}
    <div class="mt-4 pt-3" style="border-top: 1px solid rgba(255,255,255,0.1);">
        <div class="text-center">
            <small class="text-white-50 d-block mb-2">Indicateurs rapides</small>
            
            {{-- Commissions en attente --}}
            @php
                $pendingCommissions = \App\Models\Commission::where('status', 'pending')->count();
            @endphp
            @if($pendingCommissions > 0)
                <a href="{{ route('admin.commissions', ['status' => 'pending']) }}" 
                   class="nav-link p-2 mb-2" style="background: rgba(220, 53, 69, 0.2); border-radius: 6px;">
                    <i class="fas fa-clock me-2"></i>
                    <span class="badge bg-danger">{{ $pendingCommissions }}</span>
                    <small class="d-block">Commissions en attente</small>
                </a>
            @endif
            
            {{-- Commandes du jour --}}
            @php
                $todayOrders = \App\Models\Order::whereDate('created_at', today())
                                               ->where('payment_status', 'paid')
                                               ->count();
            @endphp
            @if($todayOrders > 0)
                <a href="{{ route('admin.orders') }}" 
                   class="nav-link p-2 mb-2" style="background: rgba(40, 167, 69, 0.2); border-radius: 6px;">
                    <i class="fas fa-shopping-cart me-2"></i>
                    <span class="badge bg-success">{{ $todayOrders }}</span>
                    <small class="d-block">Commandes aujourd'hui</small>
                </a>
            @endif
            
            {{-- Nouveaux utilisateurs --}}
            @php
                $newUsers = \App\Models\User::whereDate('created_at', today())->count();
            @endphp
            @if($newUsers > 0)
                <a href="{{ route('admin.users') }}" 
                   class="nav-link p-2 mb-2" style="background: rgba(23, 162, 184, 0.2); border-radius: 6px;">
                    <i class="fas fa-user-plus me-2"></i>
                    <span class="badge bg-info">{{ $newUsers }}</span>
                    <small class="d-block">Nouveaux utilisateurs</small>
                </a>
            @endif
        </div>
    </div>
    
    {{-- Version et infos système --}}
    <div class="mt-4 text-center">
        <small class="text-white-50">
            <i class="fas fa-code me-1"></i>ClicBillet CI v2.0<br>
            <i class="fas fa-calendar me-1"></i>{{ now()->format('d/m/Y') }}
        </small>
    </div>
</div>