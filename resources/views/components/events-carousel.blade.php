{{-- resources/views/components/events-carousel.blade.php --}}
{{-- Composant carrousel d'événements optimisé pour ClicBillet CI --}}

@props([
    'events' => collect(),
    'title' => 'Événements à la une',
    'subtitle' => 'Découvrez les événements les plus populaires du moment',
    'showFilters' => true,
    'showNavigation' => true,
    'showIndicators' => true,
    'autoplay' => true,
    'categories' => null
])

<section id="events" class="events-section">
    <div class="container">
        <!-- En-tête -->
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h2 class="section-title">{{ $title }}</h2>
                <p class="section-subtitle">{{ $subtitle }}</p>
            </div>
        </div>

        <!-- Filtres de catégorie -->
        @if($showFilters && $categories && $categories->count() > 0)
            <div class="category-filters">
                <button class="btn btn-outline-orange active" data-category="all">
                    <i class="fas fa-grid-3x3 me-2"></i>Tous
                </button>
                @foreach($categories as $category)
                    <button class="btn btn-outline-orange" data-category="{{ $category->slug }}">
                        <i class="{{ $category->icon ?? 'fas fa-tag' }} me-2"></i>{{ $category->name }}
                    </button>
                @endforeach
            </div>
        @endif

        <!-- Carrousel -->
        @if($events->count() > 0)
            <div class="whippet-carousel-container">
                <div class="whippet-carousel" id="eventsCarousel" 
                     data-autoplay="{{ $autoplay ? 'true' : 'false' }}">
                    @foreach($events as $event)
                        <div class="whippet-slide">
                            <div class="card border-0 h-100 event-item" 
                                 data-category="{{ $event->category->slug ?? 'general' }}">
                                
                                <!-- Badge catégorie -->
                                @if($event->category)
                                    <div class="category-badge">{{ $event->category->name }}</div>
                                @endif
                                
                                <!-- Image d'événement -->
                                @if($event->image && Storage::exists($event->image))
                                    <img src="{{ Storage::url($event->image) }}" 
                                         class="event-image" 
                                         alt="{{ $event->title }}"
                                         loading="lazy">
                                @else
                                    <div class="event-image-placeholder">
                                        <i class="{{ $event->category->icon ?? 'fas fa-calendar-alt' }}"></i>
                                    </div>
                                @endif
                                
                                <div class="card-body">
                                    <!-- Titre -->
                                    <h5 class="card-title">{{ $event->title }}</h5>
                                    
                                    <!-- Description -->
                                    @if($event->description)
                                        <p class="card-text">{{ Str::limit($event->description, 120) }}</p>
                                    @endif
                                    
                                    <!-- Détails -->
                                    <div class="event-details">
                                        <div class="detail-item">
                                            <i class="fas fa-calendar-alt"></i>
                                            <span>{{ $event->event_date->format('l j F Y') }}</span>
                                        </div>
                                        
                                        @if($event->event_time)
                                            <div class="detail-item">
                                                <i class="fas fa-clock"></i>
                                                <span>{{ $event->event_time->format('H\hi') }}</span>
                                            </div>
                                        @endif
                                        
                                        @if($event->venue)
                                            <div class="detail-item">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <span>{{ $event->venue }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <!-- Prix -->
                                    @if($event->ticketTypes && $event->ticketTypes->count() > 0)
                                        @php
                                            $minPrice = $event->ticketTypes->min('price');
                                        @endphp
                                        <div class="event-price">
                                            À partir de {{ number_format($minPrice, 0, ',', ' ') }} FCFA
                                        </div>
                                    @endif
                                    
                                    <!-- Bouton d'action -->
                                    <a href="{{ route('events.show', $event->slug) }}" 
                                       class="btn btn-event">
                                        @if($event->ticketTypes && $event->ticketTypes->count() > 0)
                                            Réserver maintenant
                                        @else
                                            Voir les détails
                                        @endif
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Navigation -->
            @if($showNavigation)
                <div class="whippet-nav-container">
                    <button class="whippet-nav" id="prevBtn" aria-label="Événement précédent">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="whippet-nav" id="nextBtn" aria-label="Événement suivant">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            @endif

            <!-- Indicateurs -->
            @if($showIndicators)
                <div class="carousel-indicators" id="carouselIndicators">
                    <!-- Générés dynamiquement par JavaScript -->
                </div>
            @endif
        @else
            <!-- État vide -->
            <div class="empty-carousel">
                <i class="fas fa-calendar-times"></i>
                <h5>Aucun événement disponible</h5>
                <p>Il n'y a actuellement aucun événement à afficher. Revenez bientôt !</p>
                @auth
                    @if(auth()->user()->role === 'promoteur')
                        <a href="{{ route('promoteur.events.create') }}" class="btn btn-outline-orange">
                            <i class="fas fa-plus me-2"></i>Créer un événement
                        </a>
                    @endif
                @endauth
            </div>
        @endif
    </div>
</section>

@push('scripts')
<script src="{{ asset('js/components-events-carousel.js') }}" defer></script>
@endpush