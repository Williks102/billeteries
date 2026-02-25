@extends('layouts.app')

@php
use Illuminate\Support\Facades\Storage;
@endphp
@section('title', 'Tous les événements')

@section('content')
<div class="events-page">
    <!-- En-tête avec statistiques -->
    <div class="hero-section bg-gradient-primary text-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold mb-3">Tous les événements</h1>
                    <p class="lead mb-4">Découvrez tous les événements disponibles sur notre plateforme</p>
                    
                    <!-- Statistiques rapides -->
                    <div class="row">
                        <div class="col-md-3 col-6 mb-3">
                            <div class="stat-item">
                                <h3 class="h4 mb-1">{{ $stats['total_events'] }}</h3>
                                <small class="text-white-50">Événements à venir</small>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="stat-item">
                                <h3 class="h4 mb-1">{{ $stats['total_categories'] }}</h3>
                                <small class="text-white-50">Catégories</small>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="stat-item">
                                <h3 class="h4 mb-1">{{ $stats['today_events'] }}</h3>
                                <small class="text-white-50">Aujourd'hui</small>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="stat-item">
                                <h3 class="h4 mb-1">{{ $stats['this_week_events'] }}</h3>
                                <small class="text-white-50">Cette semaine</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <!-- Image ou illustration optionnelle -->
                    <div class="text-center">
                        <i class="fas fa-calendar-alt fa-6x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="filters-section bg-light py-4">
        <div class="container">
            <form method="GET" action="{{ route('events.all') }}" class="row g-3">
                <!-- Recherche -->
                <div class="col-lg-4 col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Rechercher un événement..." 
                               value="{{ request('search') }}">
                    </div>
                </div>

                <!-- Filtre par catégorie -->
                <div class="col-lg-2 col-md-6">
                    <select name="category" class="form-select">
                        <option value="all">Toutes les catégories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->slug }}" 
                                    {{ request('category') == $category->slug ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtre par date -->
                <div class="col-lg-2 col-md-6">
                    <select name="date_filter" class="form-select">
                        <option value="">Toutes les dates</option>
                        <option value="today" {{ request('date_filter') == 'today' ? 'selected' : '' }}>
                            Aujourd'hui
                        </option>
                        <option value="this_week" {{ request('date_filter') == 'this_week' ? 'selected' : '' }}>
                            Cette semaine
                        </option>
                        <option value="this_month" {{ request('date_filter') == 'this_month' ? 'selected' : '' }}>
                            Ce mois
                        </option>
                        <option value="next_month" {{ request('date_filter') == 'next_month' ? 'selected' : '' }}>
                            Mois prochain
                        </option>
                    </select>
                </div>

                <!-- Tri -->
                <div class="col-lg-2 col-md-6">
                    <select name="sort" class="form-select">
                        <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>
                            Plus récents
                        </option>
                        <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>
                            Plus anciens
                        </option>
                        <option value="date_asc" {{ request('sort') == 'date_asc' ? 'selected' : '' }}>
                            Date croissante
                        </option>
                        <option value="date_desc" {{ request('sort') == 'date_desc' ? 'selected' : '' }}>
                            Date décroissante
                        </option>
                        <option value="title" {{ request('sort') == 'title' ? 'selected' : '' }}>
                            Alphabétique
                        </option>
                    </select>
                </div>

                <!-- Boutons -->
                <div class="col-lg-2">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="fas fa-filter me-1"></i>Filtrer
                        </button>
                        <a href="{{ route('events.all') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Résultats -->
    <div class="results-section py-5">
        <div class="container">
            <!-- En-tête des résultats -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="h4 mb-1">
                        {{ $events->total() }} événement{{ $events->total() > 1 ? 's' : '' }} trouvé{{ $events->total() > 1 ? 's' : '' }}
                    </h2>
                    @if(request()->hasAny(['search', 'category', 'date_filter']))
                        <p class="text-muted mb-0">
                            Résultats filtrés
                            @if(request('search'))
                                pour "{{ request('search') }}"
                            @endif
                            @if(request('category') && request('category') != 'all')
                                @php $selectedCategory = $categories->firstWhere('slug', request('category')) @endphp
                                dans {{ $selectedCategory->name ?? 'cette catégorie' }}
                            @endif
                        </p>
                    @endif
                </div>
                
                <!-- Vue liste/grille -->
                <div class="view-toggle">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-secondary active" id="grid-view">
                            <i class="fas fa-th"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="list-view">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>
            </div>

            @if($events->count() > 0)
                <!-- Grille d'événements -->
                <div id="events-grid" class="row">
                    @foreach($events as $event)
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                            <div class="event-card h-100">
                                <div class="position-relative">
                                    @if($event->image)
                                        <img src="{{ Storage::url($event->image) }}" 
                                             class="card-img-top event-image" 
                                             alt="{{ $event->title }}">
                                    @else
                                        <div class="event-image-placeholder">
                                            <i class="fas fa-calendar-alt fa-3x text-muted"></i>
                                        </div>
                                    @endif
                                    
                                    <!-- Badge catégorie -->
                                    <div class="position-absolute top-0 start-0 m-2">
                                        <span class="badge bg-primary">{{ $event->category->name }}</span>
                                    </div>

                                    <!-- Badge "Nouveau" pour les événements récents -->
                                    @if($event->created_at->diffInDays() <= 7)
                                        <div class="position-absolute top-0 end-0 m-2">
                                            <span class="badge bg-success">Nouveau</span>
                                        </div>
                                    @endif
                                </div>

                                <div class="card-body p-3">
                                    <h5 class="card-title mb-2">
                                        <a href="{{ route('events.show', $event) }}" class="text-decoration-none">
                                            {{ Str::limit($event->title, 50) }}
                                        </a>
                                    </h5>
                                    
                                    <div class="event-meta mb-3">
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="fas fa-calendar-alt me-2 text-muted"></i>
                                            <small class="text-muted">
                                                {{ $event->event_date->format('d/m/Y') }}
                                                @if($event->event_time)
                                                    à {{ $event->event_time->format('H:i') }}
                                                @endif
                                            </small>
                                        </div>
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                                            <small class="text-muted">{{ Str::limit($event->venue, 30) }}</small>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-user me-2 text-muted"></i>
                                            <small class="text-muted">{{ $event->promoteur->name }}</small>
                                        </div>
                                    </div>

                                    <!-- Prix -->
                                    @if($event->ticketTypes->count() > 0)
                                        <div class="price-info mb-3">
                                            @php
                                                $minPrice = $event->ticketTypes->min('price');
                                                $maxPrice = $event->ticketTypes->max('price');
                                            @endphp
                                            @if($minPrice == $maxPrice)
                                                <span class="h6 text-primary mb-0">{{ number_format($minPrice, 0, ',', ' ') }} FCFA</span>
                                            @else
                                                <span class="h6 text-primary mb-0">
                                                    {{ number_format($minPrice, 0, ',', ' ') }} - {{ number_format($maxPrice, 0, ',', ' ') }} FCFA
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <div class="card-footer bg-transparent p-3 pt-0">
                                    <a href="{{ route('events.show', $event) }}" class="btn btn-primary w-100">
                                        <i class="fas fa-eye me-2"></i>Voir les détails
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Vue liste (masquée par défaut) -->
                <div id="events-list" class="d-none">
                    @foreach($events as $event)
                        <div class="event-list-item mb-3">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    @if($event->image)
                                        <img src="{{ Storage::url($event->image) }}" 
                                             class="img-fluid rounded event-thumbnail" 
                                             alt="{{ $event->title }}">
                                    @else
                                        <div class="event-thumbnail-placeholder">
                                            <i class="fas fa-calendar-alt fa-2x text-muted"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-8">
                                    <h5 class="mb-2">
                                        <a href="{{ route('events.show', $event) }}" class="text-decoration-none">
                                            {{ $event->title }}
                                        </a>
                                        @if($event->created_at->diffInDays() <= 7)
                                            <span class="badge bg-success ms-2">Nouveau</span>
                                        @endif
                                    </h5>
                                    <p class="text-muted mb-2">{{ Str::limit($event->description, 120) }}</p>
                                    <div class="event-meta">
                                        <span class="me-3">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            {{ $event->event_date->format('d/m/Y') }}
                                        </span>
                                        <span class="me-3">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            {{ $event->venue }}
                                        </span>
                                        <span class="badge bg-secondary">{{ $event->category->name }}</span>
                                    </div>
                                </div>
                                <div class="col-md-2 text-end">
                                    @if($event->ticketTypes->count() > 0)
                                        @php
                                            $minPrice = $event->ticketTypes->min('price');
                                        @endphp
                                        <div class="h6 text-primary mb-2">
                                            À partir de {{ number_format($minPrice, 0, ',', ' ') }} FCFA
                                        </div>
                                    @endif
                                    <a href="{{ route('events.show', $event) }}" class="btn btn-primary btn-sm">
                                        Voir les détails
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-5">
                    {{ $events->links() }}
                </div>
            @else
                <!-- Aucun résultat -->
                <div class="text-center py-5">
                    <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                    <h3 class="text-muted">Aucun événement trouvé</h3>
                    <p class="text-muted mb-4">
                        @if(request()->hasAny(['search', 'category', 'date_filter']))
                            Essayez de modifier vos critères de recherche ou 
                            <a href="{{ route('events.all') }}">voir tous les événements</a>
                        @else
                            Il n'y a actuellement aucun événement disponible.
                        @endif
                    </p>
                    @if(request()->hasAny(['search', 'category', 'date_filter']))
                        <a href="{{ route('events.all') }}" class="btn btn-primary">
                            <i class="fas fa-refresh me-2"></i>Voir tous les événements
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

@push('styles')
<style>
.events-page {
    background-color: #f8f9fa;
}

.hero-section {
    background: linear-gradient(135deg, var(--primary-orange), #e67e22) !important;
}

.stat-item h3 {
    font-size: 1.75rem;
    font-weight: 700;
}

.filters-section {
    border-bottom: 1px solid #dee2e6;
}

.event-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    border: none;
    transition: all 0.3s ease;
    overflow: hidden;
}

.event-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}



.event-meta small {
    font-size: 0.875rem;
}

.event-list-item {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: all 0.3s ease;
}

.event-list-item:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}



.view-toggle .btn {
    border-color: #dee2e6;
}

.view-toggle .btn.active {
    background-color: var(--primary-orange);
    border-color: var(--primary-orange);
    color: white;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, var(--primary-orange), #e67e22) !important;
}

@media (max-width: 768px) {
    .event-image {
        height: 150px;
    }
    
    .event-list-item .row {
        text-align: center;
    }
    
    .event-list-item .col-md-2,
    .event-list-item .col-md-8,
    .event-list-item .col-md-2 {
        margin-bottom: 1rem;
    }
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/events-all.js') }}" defer></script>
@endpush
@endsection