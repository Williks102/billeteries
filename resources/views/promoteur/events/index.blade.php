
@extends('layouts.promoteur')

@push('styles')
<style>
    .event-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
        overflow: hidden;
    }
    
    .event-card:hover {
        transform: translateY(-5px);
    }
    
    .event-image {
        height: 200px;
        object-fit: cover;
        background: linear-gradient(135deg, #FF6B35, #E55A2B);
    }
    
    .event-stats {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        margin: 1rem 0;
    }
    
    .stat-item {
        text-align: center;
        border-right: 1px solid #dee2e6;
    }
    
    .stat-item:last-child {
        border-right: none;
    }
    
    .filter-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-left: 4px solid #FF6B35;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2" style="color: #FF6B35;">
                        <i class="fas fa-calendar me-2"></i>
                        Mes Événements
                    </h1>
                    <p class="text-muted">Gérez tous vos événements</p>
                </div>
                <div>
                    <a href="{{ route('promoteur.events.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Nouvel Événement
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Statistiques rapides -->
    @if(isset($events) && $events->count() > 0)
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center" style="border-left: 4px solid #FF6B35;">
                    <div class="card-body">
                        <h3 class="fw-bold" style="color: #FF6B35;">{{ $events->total() ?? $events->count() }}</h3>
                        <p class="text-muted mb-0">Total Événements</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center" style="border-left: 4px solid #28a745;">
                    <div class="card-body">
                        <h3 class="fw-bold text-success">{{ $events->where('status', 'published')->count() }}</h3>
                        <p class="text-muted mb-0">Publiés</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center" style="border-left: 4px solid #ffc107;">
                    <div class="card-body">
                        <h3 class="fw-bold text-warning">{{ $events->where('status', 'draft')->count() }}</h3>
                        <p class="text-muted mb-0">Brouillons</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center" style="border-left: 4px solid #17a2b8;">
                    <div class="card-body">
                        <h3 class="fw-bold text-info">{{ $events->where('event_date', '>=', now())->count() }}</h3>
                        <p class="text-muted mb-0">À venir</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Filtres -->
    <div class="card filter-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('promoteur.events.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Brouillon</option>
                        <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Publié</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Catégorie</label>
                    <select name="category" class="form-select">
                        <option value="">Toutes les catégories</option>
                        @if(isset($categories))
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Recherche</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Nom de l'événement..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-2"></i>Filtrer
                        </button>
                        @if(request()->hasAny(['status', 'category', 'search']))
                            <a href="{{ route('promoteur.events.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des événements -->
    <div class="row">
        @if(isset($events) && $events->count() > 0)
            @foreach($events as $event)
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card event-card h-100">
                        @if($event->image)
                            <img src="{{ Storage::url($event->image) }}" class="card-img-top event-image">
                        @else
                            <div class="event-image d-flex align-items-center justify-content-center text-white">
                                <i class="fas fa-calendar-alt fa-3x"></i>
                            </div>
                        @endif
                        
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0">{{ Str::limit($event->title, 30) }}</h5>
                                <span class="badge bg-{{ $event->status === 'published' ? 'success' : 'warning' }}">
                                    {{ ucfirst($event->status) }}
                                </span>
                            </div>
                            
                            <p class="card-text text-muted small mb-3">
                                <i class="fas fa-calendar me-1"></i>
                                {{ $event->event_date ? $event->event_date->format('d/m/Y') : 'Date non définie' }}
                                @if($event->event_time)
                                    à {{ $event->event_time->format('H:i') }}
                                @endif
                                <br>
                                <i class="fas fa-map-marker-alt me-1"></i>
                                {{ Str::limit($event->venue, 25) }}
                                <br>
                                <i class="fas fa-tag me-1"></i>
                                {{ $event->category->name ?? 'Sans catégorie' }}
                            </p>
                            
                            <!-- Statistiques de l'événement -->
                            <div class="event-stats">
                                <div class="row">
                                    <div class="col-4 stat-item">
                                        <div class="fw-bold text-primary">{{ $event->ticketTypes->count() }}</div>
                                        <small class="text-muted">Billets</small>
                                    </div>
                                    <div class="col-4 stat-item">
                                        <div class="fw-bold text-success">{{ $event->getTicketsSoldCount() }}</div>
                                        <small class="text-muted">Vendus</small>
                                    </div>
                                    <div class="col-4 stat-item">
                                        <div class="fw-bold" style="color: #FF6B35;">{{ number_format($event->totalRevenue()) }}</div>
                                        <small class="text-muted">FCFA</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-footer bg-transparent">
                            <div class="d-grid gap-2">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('promoteur.events.show', $event) }}" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye"></i> Détails
                                    </a>
                                    <a href="{{ route('promoteur.events.edit', $event) }}" 
                                       class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-edit"></i> Modifier
                                    </a>
                                    <a href="{{ route('promoteur.events.tickets.index', $event) }}" 
                                       class="btn btn-outline-info btn-sm">
                                        <i class="fas fa-ticket-alt"></i> Billets
                                    </a>
                                </div>
                                
                                @if($event->status === 'draft')
                                    <form method="POST" action="{{ route('promoteur.events.publish', $event) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm w-100"
                                                onclick="return confirm('Publier cet événement ?')">
                                            <i class="fas fa-upload me-1"></i> Publier
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('promoteur.events.unpublish', $event) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-warning btn-sm w-100"
                                                onclick="return confirm('Dépublier cet événement ?')">
                                            <i class="fas fa-download me-1"></i> Dépublier
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
            
            <!-- Pagination -->
            @if(method_exists($events, 'links'))
                <div class="col-12">
                    <div class="d-flex justify-content-center">
                        {{ $events->appends(request()->query())->links() }}
                    </div>
                </div>
            @endif
        @else
            <div class="col-12">
                <div class="card text-center py-5">
                    <div class="card-body">
                        <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">Aucun événement trouvé</h5>
                        <p class="text-muted">
                            @if(request()->hasAny(['status', 'category', 'search']))
                                Aucun événement ne correspond aux filtres sélectionnés.
                                <br>
                                <a href="{{ route('promoteur.events.index') }}" class="btn btn-outline-secondary mt-2">
                                    <i class="fas fa-times me-2"></i>Effacer les filtres
                                </a>
                            @else
                                Vous n'avez pas encore créé d'événement.
                            @endif
                        </p>
                        <a href="{{ route('promoteur.events.create') }}" class="btn btn-primary mt-2">
                            <i class="fas fa-plus me-2"></i>Créer mon premier événement
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animation des cartes
    const cards = document.querySelectorAll('.event-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
</script>
@endpush