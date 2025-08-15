@extends('layouts.app')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('title', 'Accueil - ClicBillet CI')
@section('body-class', 'home-page')

@section('content')
<!-- Search Bar Section -->
<section class="search-section">
    <div class="container">
        <div class="search-container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-10">
                    <div class="search-card">
                        <div class="search-header text-center mb-4">
                            <h1 class="search-title">
                                Trouvez votre <span class="text-orange">événement</span> idéal
                            </h1>
                            <p class="search-subtitle">
                                Concerts, théâtre, sports, conférences... Plus de {{ $events->count() ?? 0 }} événements vous attendent
                            </p>
                        </div>
                        
                        <!-- Barre de recherche principale -->
                        <form action="{{ route('events.all') }}" method="GET" class="search-form">
                            <div class="search-input-group">
                                <div class="search-input-wrapper">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" 
                                           name="q" 
                                           class="search-input" 
                                           placeholder="Rechercher un événement, artiste, lieu..."
                                           value="{{ request('q') }}"
                                           autocomplete="off">
                                </div>
                                <button type="submit" class="search-btn">
                                    <i class="fas fa-search me-2"></i>Rechercher
                                </button>
                            </div>
                        </form>
                        
                        <!-- Suggestions de recherche -->
                        <div class="search-suggestions">
                            <span class="suggestions-label">Recherches populaires :</span>
                            <div class="suggestions-tags">
                                <button class="suggestion-tag" data-search="concert">Concert</button>
                                <button class="suggestion-tag" data-search="abidjan">Abidjan</button>
                                <button class="suggestion-tag" data-search="weekend">Ce weekend</button>
                                <button class="suggestion-tag" data-search="gratuit">Gratuit</button>
                                <button class="suggestion-tag" data-search="théâtre">Théâtre</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section Catégories -->
<section class="categories-section py-3" style="background: #f8f9fa;">
    <div class="container">
        <!-- Filtres par catégorie - Une seule ligne compacte -->
        <div class="row">
            <div class="col-12">
                <div class="category-filters text-center">
                    <button class="btn btn-outline-orange active" data-category="all">
                        <i class="fas fa-globe me-2"></i>Tous
                    </button>
                    @if(isset($categories))
                        @foreach($categories as $category)
                            <button class="btn btn-outline-orange" data-category="{{ $category->slug }}">
                                <i class="{{ $category->icon }} me-2"></i>{{ $category->name }}
                            </button>
                        @endforeach
                    @else
                        <button class="btn btn-outline-orange" data-category="concert">
                            <i class="fas fa-music me-2"></i>Concerts
                        </button>
                        <button class="btn btn-outline-orange" data-category="theatre">
                            <i class="fas fa-theater-masks me-2"></i>Théâtre
                        </button>
                        <button class="btn btn-outline-orange" data-category="sport">
                            <i class="fas fa-futbol me-2"></i>Sports
                        </button>
                        <button class="btn btn-outline-orange" data-category="conference">
                            <i class="fas fa-microphone me-2"></i>Conférences
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section Événements avec Carrousel Whippet -->
<section id="events" class="events-section py-4">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h2 class="section-title">Événements à la une</h2>
                <p class="section-subtitle">Découvrez les événements les plus populaires du moment</p>
            </div>
        </div>

        <!-- Carrousel Whippet avec défilement infini -->
        <div class="whippet-carousel-container">
            <div class="whippet-carousel" id="eventsCarousel">
                @if(isset($events) && $events->count() > 0)
                    @foreach($events as $event)
                        <div class="whippet-slide original-slide">
                            <div class="card border-0 shadow-sm h-100 event-item" data-category="{{ $event->category->slug ?? 'general' }}">
                                @if($event->image)
                                    <img src="{{ Storage::url($event->image) }}" 
                                         class="card-img-top event-image" 
                                         alt="Affiche de l'événement {{ $event->title }} organisé à {{ $event->venue }} le {{ $event->event_date->format('d/m/Y') }}">
                                @else
                                    <div class="card-img-top event-image-placeholder d-flex align-items-center justify-content-center">
                                        <i class="{{ $event->category->icon ?? 'fas fa-calendar' }} fa-3x text-muted"></i>
                                    </div>
                                @endif
                                
                                <div class="card-body d-flex flex-column">
                                    <div class="event-category mb-2">
                                        <span class="badge bg-orange">{{ $event->category->name }}</span>
                                    </div>
                                    
                                    <h5 class="card-title mb-3">{{ $event->title }}</h5>
                                    
                                    <div class="event-details mb-3">
                                        <div class="detail-item mb-2">
                                            <i class="fas fa-calendar text-orange me-2"></i>
                                            <span>{{ $event->event_date->format('d/m/Y') }}</span>
                                        </div>
                                        @if($event->event_time)
                                            <div class="detail-item mb-2">
                                                <i class="fas fa-clock text-orange me-2"></i>
                                                <span>{{ $event->event_time->format('H:i') }}</span>
                                            </div>
                                        @endif
                                        <div class="detail-item mb-2">
                                            <i class="fas fa-map-marker-alt text-orange me-2"></i>
                                            <span>{{ $event->venue }}</span>
                                        </div>
                                    </div>

                                    @if($event->ticketTypes->count() > 0)
                                        <div class="event-price mb-3">
                                            <span class="price-label">À partir de</span>
                                            <span class="price-value">{{ number_format($event->ticketTypes->min('price')) }} F</span>
                                        </div>
                                    @endif
                                    
                                    <div class="mt-auto">
                                        <a href="{{ route('events.show', $event) }}" class="btn btn-orange w-100">
                                            <i class="fas fa-ticket-alt me-2"></i>Réserver
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <!-- Événements par défaut si aucun n'est trouvé -->
                    @for ($i = 1; $i <= 6; $i++)
                        <div class="whippet-slide original-slide">
                            <div class="card border-0 shadow-sm h-100 event-item">
                                <div class="card-img-top event-image-placeholder d-flex align-items-center justify-content-center">
                                    <i class="fas fa-calendar fa-3x text-muted"></i>
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <div class="event-category mb-2">
                                        <span class="badge bg-orange">Concert</span>
                                    </div>
                                    <h5 class="card-title mb-3">Événement à venir {{ $i }}</h5>
                                    <div class="event-details mb-3">
                                        <div class="detail-item mb-2">
                                            <i class="fas fa-calendar text-orange me-2"></i>
                                            <span>Bientôt disponible</span>
                                        </div>
                                        <div class="detail-item mb-2">
                                            <i class="fas fa-map-marker-alt text-orange me-2"></i>
                                            <span>Abidjan</span>
                                        </div>
                                    </div>
                                    <div class="mt-auto">
                                        <button class="btn btn-outline-orange w-100" disabled>
                                            <i class="fas fa-clock me-2"></i>Bientôt
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endfor
                @endif
            </div>
            
            <!-- Navigation du carrousel -->
            <div class="whippet-nav-container">
                <button class="whippet-nav whippet-prev" id="prevBtn">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="whippet-nav whippet-next" id="nextBtn">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>

        <!-- Lien vers tous les événements -->
        <div class="text-center mt-5">
            <a href="{{ route('events.all') }}" class="btn btn-outline-orange btn-lg">
                <i class="fas fa-search me-2"></i>Voir tous les événements
            </a>
        </div>
    </div>
</section>

<!-- Section Devenir Promoteur -->
<section class="promoter-section py-5" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="promoter-content">
                    <h2 class="mb-3">Organisateurs, rejoignez-nous !</h2>
                    <p class="lead mb-4">
                        Vous organisez des événements ? Utilisez notre plateforme pour vendre vos billets facilement.
                        Interface simple, paiements sécurisés, statistiques détaillées.
                    </p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="text-end">
                    <a href="{{ route('register') }}" class="btn btn-orange btn-lg">
                        <i class="fas fa-bullhorn me-2"></i>Devenir promoteur
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@push('styles')
<style>
.home-page {
    background: #ffffff;
}

/* Section Search Bar */
.search-section {
    background: linear-gradient(135deg, #FF6B35 0%, #E55A2B 100%);
    padding: 60px 0 40px;
    position: relative;
    overflow: hidden;
}

.search-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="rgba(255,255,255,0.1)"><polygon points="0,100 1000,0 1000,100"/></svg>');
    background-size: cover;
}

.search-container {
    position: relative;
    z-index: 2;
}

.search-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 40px 30px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.search-title {
    font-size: 2.2rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 1rem;
}

.search-subtitle {
    font-size: 1.1rem;
    color: #6c757d;
    margin-bottom: 0;
}

.search-form {
    margin: 30px 0;
}

.search-input-group {
    display: flex;
    gap: 15px;
    align-items: stretch;
}

.search-input-wrapper {
    flex: 1;
    position: relative;
}

.search-icon {
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    font-size: 1.1rem;
    z-index: 3;
}

.search-input {
    width: 100%;
    padding: 18px 20px 18px 50px;
    border: 2px solid #e9ecef;
    border-radius: 15px;
    font-size: 1.1rem;
    background: white;
    transition: all 0.3s ease;
    outline: none;
}

.search-input:focus {
    border-color: var(--primary-orange);
    box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
}

.search-btn {
    background: var(--primary-orange);
    border: 2px solid var(--primary-orange);
    color: white;
    padding: 18px 30px;
    border-radius: 15px;
    font-weight: 600;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    white-space: nowrap;
    cursor: pointer;
}

.search-btn:hover {
    background: #e55a2b;
    border-color: #e55a2b;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
}

.search-suggestions {
    text-align: center;
    margin-top: 25px;
}

.suggestions-label {
    font-size: 0.9rem;
    color: #6c757d;
    margin-right: 10px;
}

.suggestions-tags {
    display: inline-flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: center;
    margin-top: 10px;
}

.suggestion-tag {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    color: #495057;
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.suggestion-tag:hover {
    background: var(--primary-orange);
    color: white;
    border-color: var(--primary-orange);
    transform: translateY(-1px);
}

.section-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 1rem;
}

.section-subtitle {
    font-size: 1.1rem;
    color: #6c757d;
    max-width: 600px;
    margin: 0 auto;
}

/* Filtres catégories - Compacts */
.category-filters {
    margin: 0;
    padding: 0 15px;
}

.btn-outline-orange {
    border: 2px solid var(--primary-orange);
    color: var(--primary-orange);
    border-radius: 20px;
    padding: 6px 15px;
    margin: 3px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.btn-outline-orange:hover,
.btn-outline-orange.active {
    background: var(--primary-orange);
    color: white;
    border-color: var(--primary-orange);
}

/* Carrousel Whippet avec défilement infini */
.whippet-carousel-container {
    position: relative;
    overflow: hidden;
    margin: 1rem 0;
}

.whippet-carousel {
    display: flex;
    transition: transform 0.6s ease;
    gap: 20px;
    will-change: transform;
}

.whippet-slide {
    min-width: calc(33.333% - 14px); /* 3 cartes visibles sur desktop */
    flex-shrink: 0;
}

.whippet-nav-container {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-top: 30px;
}

.whippet-nav {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    border: 2px solid var(--primary-orange);
    background: white;
    color: var(--primary-orange);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 1.2rem;
}

.whippet-nav:hover {
    background: var(--primary-orange);
    color: white;
    transform: scale(1.1);
}

.whippet-nav:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.whippet-nav:disabled:hover {
    background: white;
    color: var(--primary-orange);
    transform: none;
}

/* Cards événements */
.event-item {
    border-radius: 15px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    height: 100%;
}

.event-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.event-image {
    height: 220px;
    object-fit: cover;
    width: 100%;
}

.event-image-placeholder {
    height: 220px;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    display: flex;
    align-items: center;
    justify-content: center;
}

.event-category .badge {
    font-size: 0.75rem;
    padding: 5px 10px;
    border-radius: 15px;
}

.bg-orange {
    background: var(--primary-orange) !important;
}

.text-orange {
    color: var(--primary-orange) !important;
}

.btn-orange {
    background: var(--primary-orange);
    border-color: var(--primary-orange);
    color: white;
    border-radius: 20px; /* Boutons très arrondis comme Facebook */
    padding: 10px 25px;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    border: none;
}

.btn-orange:hover {
    background: #e55a2b;
    border-color: #e55a2b;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
}

.event-details {
    font-size: 0.9rem;
    color: #6c757d;
}

.detail-item {
    display: flex;
    align-items: center;
}

.price-label {
    font-size: 0.8rem;
    color: #6c757d;
    display: block;
}

.price-value {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--primary-orange);
}

.promoter-section {
    border-top: 1px solid #e9ecef;
}

.promoter-content h2 {
    color: #2c3e50;
    font-weight: 700;
}

/* Responsive */
@media (max-width: 768px) {
    .search-title {
        font-size: 1.8rem;
    }
    
    .search-subtitle {
        font-size: 1rem;
    }
    
    .search-card {
        padding: 25px 20px;
        margin: 0 15px;
    }
    
    .search-input-group {
        flex-direction: column;
        gap: 10px;
    }
    
    .search-btn {
        padding: 15px 25px;
    }
    
    .suggestions-tags {
        justify-content: center;
    }
    
    .whippet-slide {
        min-width: 100%; /* 1 carte sur mobile */
    }
    
    .section-title {
        font-size: 1.8rem;
    }
    
    .category-filters {
        text-align: center;
    }
    
    .btn-outline-orange {
        margin: 3px;
        padding: 6px 15px;
        font-size: 0.9rem;
    }
}

@media (max-width: 992px) and (min-width: 769px) {
    .whippet-slide {
        min-width: calc(50% - 10px); /* 2 cartes sur tablette */
    }
}

/* Variables CSS */
:root {
    --primary-orange: #FF6B35;
    --primary-dark: #E55A2B;
    --black-primary: #2c3e50;
    --success: #28a745;
    --warning: #ffc107;
    --info: #17a2b8;
}

/* Animations pour le filtrage */
.event-item {
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.event-item.hidden {
    opacity: 0;
    transform: translateY(20px);
    pointer-events: none;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== BARRE DE RECHERCHE =====
    const searchInput = document.querySelector('.search-input');
    const searchForm = document.querySelector('.search-form');
    const suggestionTags = document.querySelectorAll('.suggestion-tag');
    
    // Suggestions de recherche
    suggestionTags.forEach(tag => {
        tag.addEventListener('click', function() {
            const searchTerm = this.dataset.search;
            searchInput.value = searchTerm;
            searchForm.submit();
        });
    });
    
    // Auto-completion (optionnel - peut être étendu)
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        // Ici vous pouvez ajouter de l'auto-completion en temps réel
        // Par exemple, filtrer les événements visibles
    });
    
    // ===== CARROUSEL WHIPPET AVEC DÉFILEMENT INFINI ET TOUCH =====
    const carousel = document.getElementById('eventsCarousel');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const originalSlides = document.querySelectorAll('.original-slide');
    
    if (carousel && originalSlides.length > 0) {
        let currentIndex = 0;
        let slidesToShow = getSlidesToShow();
        let isTransitioning = false;
        const slideWidth = 100 / slidesToShow;
        
        // Configuration du défilement infini
        const INFINITE_SCROLL = true;
        const AUTO_PLAY = true;
        const AUTO_PLAY_SPEED = 4000;
        const TRANSITION_SPEED = 600;
        
        // Variables pour le touch/swipe
        let isDragging = false;
        let startX = 0;
        let currentX = 0;
        let startTime = 0;
        let startTransform = 0;
        const SWIPE_THRESHOLD = 50; // Distance minimum pour déclencher un swipe
        const SWIPE_VELOCITY_THRESHOLD = 0.3; // Vitesse minimum pour un swipe rapide
        
        function getSlidesToShow() {
            if (window.innerWidth <= 768) return 1;
            if (window.innerWidth <= 992) return 2;
            return 3;
        }
        
        // Créer les slides dupliquées pour l'effet infini
        function createInfiniteSlides() {
            if (!INFINITE_SCROLL) return;
            
            // Supprimer les slides clonées existantes
            const clonedSlides = carousel.querySelectorAll('.cloned-slide');
            clonedSlides.forEach(slide => slide.remove());
            
            // Cloner les slides originales au début et à la fin
            const slidesToClone = Math.max(slidesToShow, 2);
            
            // Cloner à la fin
            for (let i = 0; i < slidesToClone; i++) {
                const slideToClone = originalSlides[i % originalSlides.length];
                const clonedSlide = slideToClone.cloneNode(true);
                clonedSlide.classList.add('cloned-slide');
                clonedSlide.classList.remove('original-slide');
                carousel.appendChild(clonedSlide);
            }
            
            // Cloner au début
            for (let i = slidesToClone - 1; i >= 0; i--) {
                const slideToClone = originalSlides[(originalSlides.length - 1 - i) % originalSlides.length];
                const clonedSlide = slideToClone.cloneNode(true);
                clonedSlide.classList.add('cloned-slide');
                clonedSlide.classList.remove('original-slide');
                carousel.insertBefore(clonedSlide, carousel.firstChild);
            }
            
            // Repositionner le carrousel
            currentIndex = slidesToClone;
            updateCarouselPosition(false);
        }
        
        function updateCarouselPosition(animate = true, customTransform = null) {
            const translateX = customTransform !== null ? customTransform : -(currentIndex * slideWidth);
            
            if (animate && !isTransitioning) {
                carousel.classList.remove('no-transition');
                carousel.style.transition = `transform ${TRANSITION_SPEED}ms ease`;
                isTransitioning = true;
                setTimeout(() => {
                    isTransitioning = false;
                }, TRANSITION_SPEED);
            } else {
                carousel.classList.add('no-transition');
                carousel.style.transition = 'none';
            }
            
            carousel.style.transform = `translateX(${translateX}%)`;
            
            // Mise à jour des boutons
            if (!INFINITE_SCROLL) {
                const maxIndex = originalSlides.length - slidesToShow;
                prevBtn.disabled = currentIndex === 0;
                nextBtn.disabled = currentIndex >= maxIndex;
            } else {
                prevBtn.disabled = false;
                nextBtn.disabled = false;
            }
        }
        
        function nextSlide() {
            if (isTransitioning || isDragging) return;
            
            currentIndex++;
            updateCarouselPosition(true);
            
            if (INFINITE_SCROLL) {
                const totalSlides = carousel.children.length;
                const originalSlidesCount = originalSlides.length;
                const slidesToClone = Math.max(slidesToShow, 2);
                
                if (currentIndex >= originalSlidesCount + slidesToClone) {
                    setTimeout(() => {
                        currentIndex = slidesToClone;
                        updateCarouselPosition(false);
                    }, TRANSITION_SPEED);
                }
            }
        }
        
        function prevSlide() {
            if (isTransitioning || isDragging) return;
            
            currentIndex--;
            updateCarouselPosition(true);
            
            if (INFINITE_SCROLL) {
                const slidesToClone = Math.max(slidesToShow, 2);
                
                if (currentIndex < 0) {
                    setTimeout(() => {
                        currentIndex = originalSlides.length + slidesToClone - 1;
                        updateCarouselPosition(false);
                    }, TRANSITION_SPEED);
                }
            }
        }
        
        // ===== GESTION DU TOUCH ET SWIPE =====
        function getEventX(e) {
            return e.type.includes('mouse') ? e.clientX : e.touches[0].clientX;
        }
        
        function handleStart(e) {
            if (isTransitioning) return;
            
            isDragging = true;
            startX = getEventX(e);
            currentX = startX;
            startTime = Date.now();
            
            // Calculer la position de départ
            const currentTransform = carousel.style.transform;
            const match = currentTransform.match(/translateX\(([^)]+)\)/);
            startTransform = match ? parseFloat(match[1]) : -(currentIndex * slideWidth);
            
            stopAutoPlay();
            carousel.style.cursor = 'grabbing';
            
            // Empêcher la sélection de texte
            e.preventDefault();
        }
        
        function handleMove(e) {
            if (!isDragging) return;
            
            e.preventDefault();
            currentX = getEventX(e);
            const deltaX = currentX - startX;
            const deltaPercent = (deltaX / carousel.offsetWidth) * 100;
            
            // Appliquer la transformation en temps réel
            const newTransform = startTransform + deltaPercent;
            updateCarouselPosition(false, newTransform);
        }
        
        function handleEnd(e) {
            if (!isDragging) return;
            
            isDragging = false;
            carousel.style.cursor = 'grab';
            
            const deltaX = currentX - startX;
            const deltaTime = Date.now() - startTime;
            const velocity = Math.abs(deltaX) / deltaTime;
            
            // Déterminer la direction du swipe
            const swipeDistance = Math.abs(deltaX);
            const isQuickSwipe = velocity > SWIPE_VELOCITY_THRESHOLD;
            const isLongSwipe = swipeDistance > SWIPE_THRESHOLD;
            
            if (isQuickSwipe || isLongSwipe) {
                if (deltaX > 0) {
                    // Swipe vers la droite (slide précédent)
                    prevSlide();
                } else {
                    // Swipe vers la gauche (slide suivant)
                    nextSlide();
                }
            } else {
                // Retour à la position originale
                updateCarouselPosition(true);
            }
            
            startAutoPlay();
        }
        
        // Event listeners pour mouse
        carousel.addEventListener('mousedown', handleStart);
        document.addEventListener('mousemove', handleMove);
        document.addEventListener('mouseup', handleEnd);
        
        // Event listeners pour touch
        carousel.addEventListener('touchstart', handleStart, { passive: false });
        carousel.addEventListener('touchmove', handleMove, { passive: false });
        carousel.addEventListener('touchend', handleEnd);
        
        // Empêcher le drag des images
        carousel.addEventListener('dragstart', (e) => {
            e.preventDefault();
        });
        
        // Event listeners pour les boutons
        nextBtn.addEventListener('click', nextSlide);
        prevBtn.addEventListener('click', prevSlide);
        
        // Auto-play
        let autoPlayInterval;
        
        function startAutoPlay() {
            if (AUTO_PLAY && !isDragging) {
                autoPlayInterval = setInterval(nextSlide, AUTO_PLAY_SPEED);
            }
        }
        
        function stopAutoPlay() {
            clearInterval(autoPlayInterval);
        }
        
        // Pause auto-play au survol (sauf sur mobile)
        if (!('ontouchstart' in window)) {
            carousel.addEventListener('mouseenter', stopAutoPlay);
            carousel.addEventListener('mouseleave', startAutoPlay);
        }
        
        // Responsive
        window.addEventListener('resize', () => {
            const newSlidesToShow = getSlidesToShow();
            if (newSlidesToShow !== slidesToShow) {
                slidesToShow = newSlidesToShow;
                if (INFINITE_SCROLL) {
                    createInfiniteSlides();
                } else {
                    currentIndex = 0;
                    updateCarouselPosition(false);
                }
            }
        });
        
        // Initialisation
        if (INFINITE_SCROLL) {
            createInfiniteSlides();
        } else {
            updateCarouselPosition(false);
        }
        
        startAutoPlay();
    }
    
    // ===== FILTRES PAR CATÉGORIE =====
    const categoryButtons = document.querySelectorAll('[data-category]');
    const eventCards = document.querySelectorAll('.event-item');
    
    categoryButtons.forEach(button => {
        button.addEventListener('click', function() {
            const category = this.dataset.category;
            
            // Mise à jour des boutons actifs
            categoryButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Filtrage des événements dans le carrousel
            eventCards.forEach(card => {
                const cardCategory = card.dataset.category;
                if (category === 'all' || cardCategory === category) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
            
            // Réinitialiser le carrousel après filtrage
            if (carousel) {
                currentIndex = 0;
                updateCarousel();
            }
        });
    });
    
    // ===== RECHERCHE EN TEMPS RÉEL (OPTIONNEL) =====
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.toLowerCase().trim();
        
        if (query.length > 2) {
            searchTimeout = setTimeout(() => {
                // Filtrer les événements visibles selon la recherche
                eventCards.forEach(card => {
                    const title = card.querySelector('.card-title')?.textContent.toLowerCase() || '';
                    const venue = card.querySelector('.detail-item:last-child span')?.textContent.toLowerCase() || '';
                    const category = card.querySelector('.badge')?.textContent.toLowerCase() || '';
                    
                    if (title.includes(query) || venue.includes(query) || category.includes(query)) {
                        card.classList.remove('hidden');
                    } else {
                        card.classList.add('hidden');
                    }
                });
                
                // Réinitialiser le carrousel
                if (carousel) {
                    currentIndex = 0;
                    updateCarousel();
                }
            }, 300);
        } else if (query.length === 0) {
            // Réafficher tous les événements
            eventCards.forEach(card => {
                card.classList.remove('hidden');
            });
            if (carousel) {
                currentIndex = 0;
                updateCarousel();
            }
        }
    });
});
</script>
@endpush