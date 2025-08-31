@extends('layouts.app')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('title', 'Accueil - ClicBillet CI')
@section('body-class', 'home-page')

@section('content')
<!-- Hero Section -->
<section class="search-section d-none d-md-block">
    <div class="container">
        <div class="row align-items-center min-vh-75">
            <div class="col-lg-6">
                <div class="hero-content">
                    <h1 class="hero-title mb-4">
                        Découvrez les meilleurs 
                        <span class="text-orange">événements</span> 
                        en Côte d'Ivoire
                    </h1>
                    <p class="hero-subtitle mb-4">
                        Concerts, théâtre, sports, conférences... Réservez vos billets en quelques clics 
                        sur la plateforme de billetterie #1 du pays.
                    </p>
                    <div class="hero-buttons">
                        <a href="#events" class="btn btn-orange btn-lg me-3">
                            <i class="fas fa-calendar me-2"></i>Voir les événements
                        </a>
                        @guest
                            <a href="{{ route('register') }}" class="btn btn-outline-primary btn-lg">
                                <i class="fas fa-user-plus me-2"></i>S'inscrire
                            </a>
                        @endguest
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-image text-center">
                    <i class="fas fa-ticket-alt hero-icon"></i>
                    <div class="floating-elements">
                        <div class="floating-card card-1">
                            <i class="fas fa-music"></i>
                            <span>Concerts</span>
                        </div>
                        <div class="floating-card card-2">
                            <i class="fas fa-theater-masks"></i>
                            <span>Théâtre</span>
                        </div>
                        <div class="floating-card card-3">
                            <i class="fas fa-futbol"></i>
                            <span>Sports</span>
                        </div>
                        <div class="floating-card card-4">
                            <i class="fas fa-microphone"></i>
                            <span>Conférences</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section Événements -->
<section id="events" class="events-section py-5">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h2 class="section-title">Événements à la une</h2>
                <p class="section-subtitle">Découvrez les événements les plus populaires du moment</p>
            </div>
        </div>

        <!-- Filtres par catégorie -->
        <div class="row mb-4">
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

        <!-- Liste des événements -->
        <div class="row" id="eventsContainer">
            @if(isset($events) && $events->count() > 0)
                @foreach($events as $event)
                    <div class="col-lg-4 col-md-6 mb-4 event-card" data-category="{{ $event->category->slug }}">
                        <div class="card border-0 shadow-sm h-100 event-item">
                            @if($event->image)
                                <img src="{{ Storage::url($event->image) }}" class="card-img-top event-image" alt="Affiche de l événement {{ $event->title }} organisé à {{ $event->venue }} le {{ $event->event_date->format('d/m/Y') }}. Ambiance festive, public attendu nombreux.">
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
                                        <span class="text-muted">À partir de</span>
                                        <span class="h5 text-orange mb-0">
                                            {{ number_format($event->ticketTypes->min('price'), 0, ',', ' ') }} FCFA
                                        </span>
                                    </div>
                                @endif
                                
                                <div class="mt-auto">
                                    <a href="{{ route('events.show', $event) }}" class="btn btn-orange w-100">
                                        <i class="fas fa-eye me-2"></i>Voir les détails
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <!-- Événements par défaut si pas de données -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-img-top bg-orange d-flex align-items-center justify-content-center" style="height: 200px;">
                            <i class="fas fa-music fa-3x text-white"></i>
                        </div>
                        <div class="card-body">
                            <span class="badge bg-orange mb-2">Concert</span>
                            <h5 class="card-title">Magic System Live</h5>
                            <p class="card-text text-muted">
                                <i class="fas fa-calendar me-2"></i>15 Décembre 2024<br>
                                <i class="fas fa-map-marker-alt me-2"></i>Palais de la Culture
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h6 text-orange mb-0">À partir de 5 000 FCFA</span>
                                <a href="#" class="btn btn-orange btn-sm">Voir détails</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-img-top bg-primary d-flex align-items-center justify-content-center" style="height: 200px;">
                            <i class="fas fa-futbol fa-3x text-white"></i>
                        </div>
                        <div class="card-body">
                            <span class="badge bg-primary mb-2">Sport</span>
                            <h5 class="card-title">ASEC vs Africa Sports</h5>
                            <p class="card-text text-muted">
                                <i class="fas fa-calendar me-2"></i>20 Décembre 2024<br>
                                <i class="fas fa-map-marker-alt me-2"></i>Stade Champroux
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h6 text-orange mb-0">À partir de 2 000 FCFA</span>
                                <a href="#" class="btn btn-orange btn-sm">Voir détails</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-img-top bg-success d-flex align-items-center justify-content-center" style="height: 200px;">
                            <i class="fas fa-theater-masks fa-3x text-white"></i>
                        </div>
                        <div class="card-body">
                            <span class="badge bg-success mb-2">Théâtre</span>
                            <h5 class="card-title">L'Avare de Molière</h5>
                            <p class="card-text text-muted">
                                <i class="fas fa-calendar me-2"></i>25 Décembre 2024<br>
                                <i class="fas fa-map-marker-alt me-2"></i>Théâtre National
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h6 text-orange mb-0">À partir de 3 000 FCFA</span>
                                <a href="#" class="btn btn-orange btn-sm">Voir détails</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Bouton voir plus -->
        <div class="row mt-4">
            <div class="col-12 text-center">
                <a href="{{ route('events.all') }}" class="btn btn-outline-orange btn-lg">
                    <i class="fas fa-plus me-2"></i>Voir tous les événements
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Section CTA pour les promoteurs -->
<section class="cta-section py-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="cta-card text-center">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <h3 class="mb-3">Vous organisez des événements ?</h3>
                            <p class="mb-0">
                                Rejoignez ClicBillet et vendez vos billets en ligne facilement. 
                                Interface simple, paiements sécurisés, statistiques détaillées.
                            </p>
                        </div>
                        <div class="col-lg-4">
                            <a href="{{ route('register') }}" class="btn btn-orange btn-lg">
                                <i class="fas fa-bullhorn me-2"></i>Devenir promoteur
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@push('styles')
<style>
.home-page {
    background: #ffffff;
}

.hero-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    padding: 80px 0;
}

.hero-title {
    font-size: 3rem;
    font-weight: 700;
    line-height: 1.2;
}

.hero-subtitle {
    font-size: 1.2rem;
    color: #6c757d;
    line-height: 1.6;
}

.hero-icon {
    font-size: 12rem;
    color: var(--primary-orange);
    opacity: 0.1;
    position: relative;
    z-index: 1;
}

.floating-elements {
    position: relative;
}

.floating-card {
    position: absolute;
    background: white;
    border-radius: 12px;
    padding: 1rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 80px;
    animation: float 3s ease-in-out infinite;
}

.floating-card i {
    font-size: 1.5rem;
    color: var(--primary-orange);
    margin-bottom: 0.5rem;
}

.floating-card span {
    font-size: 0.8rem;
    font-weight: 600;
    color: #495057;
}

.card-1 {
    top: 10%;
    left: 10%;
    animation-delay: 0s;
}

.card-2 {
    top: 20%;
    right: 15%;
    animation-delay: 1s;
}

.card-3 {
    bottom: 30%;
    left: 15%;
    animation-delay: 2s;
}

.card-4 {
    bottom: 10%;
    right: 10%;
    animation-delay: 0.5s;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

.stats-section {
    background: #f8f9fa !important;
}

.stat-item {
    padding: 2rem 1rem;
}

.stat-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--primary-orange), var(--secondary-orange));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
}

.stat-icon i {
    font-size: 2rem;
    color: white;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--primary-orange);
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #6c757d;
    margin-bottom: 0;
}

.section-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--dark-blue);
    margin-bottom: 1rem;
}

.section-subtitle {
    font-size: 1.1rem;
    color: #6c757d;
    margin-bottom: 0;
}

.category-filters {
    margin-bottom: 2rem;
}

.btn-outline-orange {
    border: 2px solid var(--primary-orange);
    color: var(--primary-orange);
    margin: 0 0.5rem 0.5rem 0;
    border-radius: 25px;
    padding: 0.5rem 1.5rem;
    transition: all 0.3s ease;
}

.btn-outline-orange:hover,
.btn-outline-orange.active {
    background: var(--primary-orange);
    color: white;
    transform: translateY(-2px);
}

.event-item {
    transition: all 0.3s ease;
    height: 100%;
}

.event-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.15) !important;
}

.event-image {
    height: 200px;
    object-fit: cover;
}

.event-image-placeholder {
    height: 200px;
    background: #f8f9fa;
}

.event-details .detail-item {
    font-size: 0.9rem;
    color: #6c757d;
}

.cta-section {
    background: linear-gradient(135deg, #fff3cd, #fff3cd);
    color: #081d30ff;
}

.cta-card {
    background: rgba(255,255,255,0.1);
    border-radius: 20px;
    padding: 3rem 2rem;
    backdrop-filter: blur(10px);
}

.min-vh-75 {
    min-height: 75vh;
}

@media (max-width: 768px) {
    .hero-title {
        font-size: 2rem;
    }
    
    .floating-card {
        display: none;
    }
    
    .hero-icon {
        font-size: 6rem;
    }
    
    .section-title {
        font-size: 2rem;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filtres par catégorie
    const filterButtons = document.querySelectorAll('[data-category]');
    const eventCards = document.querySelectorAll('.event-card');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const category = this.getAttribute('data-category');
            
            // Mettre à jour les boutons actifs
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Filtrer les événements
            eventCards.forEach(card => {
                if (category === 'all' || card.getAttribute('data-category') === category) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
    
    // Animation scroll pour les statistiques
    const observerOptions = {
        threshold: 0.5,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animation = 'fadeInUp 0.6s ease forwards';
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('.stat-item').forEach(item => {
        observer.observe(item);
    });
});
</script>
@endpush
@endsection