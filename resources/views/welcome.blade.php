@extends('layouts.app')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('title', 'Accueil - ClicBillet CI')
@section('body-class', 'home-page')

@section('content')
<!-- Hero Section avec Recherche -->
<section class="hero-section">
    <div class="hero-overlay"></div>
    <div class="container">
        <div class="row align-items-center min-vh-100">
            <div class="col-lg-6">
                <div class="hero-content">
                    <h1 class="hero-title">
                        <span class="text-white">Découvrez les</span>
                        <span class="text-gradient">meilleurs événements</span>
                        <span class="text-white">en Côte d'Ivoire</span>
                    </h1>
                    <p class="hero-subtitle">
                        Concerts, festivals, spectacles, conférences... 
                        Trouvez et réservez vos billets en quelques clics !
                    </p>
                    
                    <!-- Barre de recherche principale -->
                    <div class="hero-search">
                        <form action="/search" method="GET" class="search-form">
                            <div class="search-container">
                                <div class="search-input-wrapper">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" 
                                           name="q" 
                                           class="search-input" 
                                           placeholder="Rechercher un événement, artiste, lieu..."
                                           autocomplete="off">
                                </div>
                                <button type="submit" class="search-btn">
                                    <i class="fas fa-arrow-right me-2"></i>
                                    Rechercher
                                </button>
                            </div>
                        </form>
                        
                        <!-- Suggestions rapides -->
                        <div class="search-suggestions">
                            <span class="suggestions-label">Recherches populaires :</span>
                            <div class="suggestions-tags">
                                <a href="/search?q=concert" class="suggestion-tag">Concert</a>
                                <a href="/search?q=festival" class="suggestion-tag">Festival</a>
                                <a href="/search?q=spectacle" class="suggestion-tag">Spectacle</a>
                                <a href="/search?q=théâtre" class="suggestion-tag">Théâtre</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stats Hero -->
            <div class="col-lg-6">
                <div class="hero-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stat-content">
                            <h3>1,250+</h3>
                            <p>Événements organisés</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <h3>50,000+</h3>
                            <p>Utilisateurs actifs</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <div class="stat-content">
                            <h3>180,000+</h3>
                            <p>Billets vendus</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Catégories Populaires -->
<section class="categories-section">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h2 class="section-title">Explorez par catégorie</h2>
                <p class="section-subtitle">Trouvez exactement ce qui vous passionne</p>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-music"></i>
                    </div>
                    <h5>Concerts</h5>
                    <p>125 événements</p>
                    <a href="/search?category=concert" class="stretched-link"></a>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-mask"></i>
                    </div>
                    <h5>Spectacles</h5>
                    <p>87 événements</p>
                    <a href="/search?category=spectacle" class="stretched-link"></a>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-microphone"></i>
                    </div>
                    <h5>Conférences</h5>
                    <p>42 événements</p>
                    <a href="/search?category=conference" class="stretched-link"></a>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-glass-cheers"></i>
                    </div>
                    <h5>Festivals</h5>
                    <p>38 événements</p>
                    <a href="/search?category=festival" class="stretched-link"></a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Événements à la Une -->
<section class="featured-events-section">
    <div class="container">
        <div class="row align-items-center mb-5">
            <div class="col-md-8">
                <h2 class="section-title">Événements à la une</h2>
                <p class="section-subtitle">Les événements les plus populaires du moment</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="/events" class="btn btn-outline-orange">
                    Voir tous les événements
                    <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
        </div>
        
        <div class="row g-4">
            <!-- Event Card 1 -->
            <div class="col-md-6 col-lg-4">
                <div class="event-card">
                    <div class="event-image">
                        <img src="https://images.unsplash.com/photo-1501281668745-f7f57925c3b4?w=400&h=300&fit=crop" alt="Concert">
                        <div class="event-price">15,000 FCFA</div>
                        <div class="event-category">Concert</div>
                    </div>
                    <div class="event-content">
                        <div class="event-date">
                            <i class="fas fa-calendar me-2"></i>
                            <span>Samedi 15 Septembre 2025</span>
                        </div>
                        <h5 class="event-title">Festival de Musique Ivoirienne</h5>
                        <div class="event-location">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            <span>Palais de la Culture, Abidjan</span>
                        </div>
                        <div class="event-meta">
                            <span class="tickets-remaining">
                                <i class="fas fa-ticket-alt me-1"></i>
                                246 billets restants
                            </span>
                        </div>
                        <a href="/events/festival-musique-ivoirienne" class="btn btn-orange w-100 mt-3">
                            Réserver maintenant
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Event Card 2 -->
            <div class="col-md-6 col-lg-4">
                <div class="event-card">
                    <div class="event-image">
                        <img src="https://images.unsplash.com/photo-1540039155733-5bb30b53aa14?w=400&h=300&fit=crop" alt="Théâtre">
                        <div class="event-price">8,000 FCFA</div>
                        <div class="event-category">Théâtre</div>
                    </div>
                    <div class="event-content">
                        <div class="event-date">
                            <i class="fas fa-calendar me-2"></i>
                            <span>Vendredi 20 Septembre 2025</span>
                        </div>
                        <h5 class="event-title">Pièce Théâtrale : L'Héritage</h5>
                        <div class="event-location">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            <span>Théâtre National, Abidjan</span>
                        </div>
                        <div class="event-meta">
                            <span class="tickets-remaining">
                                <i class="fas fa-ticket-alt me-1"></i>
                                89 billets restants
                            </span>
                        </div>
                        <a href="/events/piece-theatre-heritage" class="btn btn-orange w-100 mt-3">
                            Réserver maintenant
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Event Card 3 -->
            <div class="col-md-6 col-lg-4">
                <div class="event-card">
                    <div class="event-image">
                        <img src="https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=400&h=300&fit=crop" alt="Conférence">
                        <div class="event-price">12,000 FCFA</div>
                        <div class="event-category">Conférence</div>
                    </div>
                    <div class="event-content">
                        <div class="event-date">
                            <i class="fas fa-calendar me-2"></i>
                            <span>Dimanche 25 Septembre 2025</span>
                        </div>
                        <h5 class="event-title">Tech Summit CI 2025</h5>
                        <div class="event-location">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            <span>Hôtel Ivoire, Abidjan</span>
                        </div>
                        <div class="event-meta">
                            <span class="tickets-remaining">
                                <i class="fas fa-ticket-alt me-1"></i>
                                156 billets restants
                            </span>
                        </div>
                        <a href="/events/tech-summit-ci-2025" class="btn btn-orange w-100 mt-3">
                            Réserver maintenant
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Pourquoi Choisir ClicBillet -->
<section class="why-choose-section">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h2 class="section-title">Pourquoi choisir ClicBillet CI ?</h2>
                <p class="section-subtitle">La billetterie de confiance en Côte d'Ivoire</p>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h5>100% Sécurisé</h5>
                    <p>Paiements protégés et données chiffrées pour votre tranquillité d'esprit</p>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h5>Simple & Rapide</h5>
                    <p>Réservez vos billets en moins de 2 minutes sur mobile ou desktop</p>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <h5>Billets Digitaux</h5>
                    <p>QR codes sécurisés, pas de queue à l'entrée, juste votre téléphone</p>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h5>Support Local</h5>
                    <p>Équipe ivoirienne disponible 7j/7 pour vous accompagner</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section Organisateurs -->
<section class="organizers-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="organizer-content">
                    <h2 class="section-title">Vous organisez des événements ?</h2>
                    <p class="section-subtitle">
                        Rejoignez les centaines d'organisateurs qui font confiance à ClicBillet CI
                    </p>
                    
                    <div class="benefits-list">
                        <div class="benefit-item">
                            <div class="benefit-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div>
                                <h6>Augmentez vos ventes</h6>
                                <p>Vendez plus de billets grâce à notre plateforme optimisée</p>
                            </div>
                        </div>
                        
                        <div class="benefit-item">
                            <div class="benefit-icon">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div>
                                <h6>Gestion simplifiée</h6>
                                <p>Dashboard complet pour gérer vos événements et ventes</p>
                            </div>
                        </div>
                        
                        <div class="benefit-item">
                            <div class="benefit-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div>
                                <h6>Commissions transparentes</h6>
                                <p>Tarifs clairs et paiements rapides après vos événements</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="cta-buttons">
                        <a href="/register" class="btn btn-orange btn-lg me-3">
                            <i class="fas fa-rocket me-2"></i>
                            Devenir organisateur
                        </a>
                        <a href="/pages/contact" class="btn btn-outline-orange btn-lg">
                            <i class="fas fa-phone me-2"></i>
                            Nous appeler
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="organizer-visual">
                    <div class="dashboard-preview">
                        <div class="dashboard-header">
                            <div class="dashboard-stats">
                                <div class="mini-stat">
                                    <span class="stat-number">24</span>
                                    <span class="stat-label">Événements</span>
                                </div>
                                <div class="mini-stat">
                                    <span class="stat-number">2.4M</span>
                                    <span class="stat-label">FCFA vendus</span>
                                </div>
                                <div class="mini-stat">
                                    <span class="stat-number">1,856</span>
                                    <span class="stat-label">Billets</span>
                                </div>
                            </div>
                        </div>
                        <div class="dashboard-chart">
                            <canvas id="previewChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Témoignages -->
<section class="testimonials-section">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h2 class="section-title">Ce que disent nos utilisateurs</h2>
                <p class="section-subtitle">Plus de 10,000 avis positifs ⭐⭐⭐⭐⭐</p>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <div class="stars">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p>"Interface super simple, j'ai acheté mes billets en 2 minutes. Les QR codes fonctionnent parfaitement !"</p>
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">AM</div>
                        <div>
                            <strong>Aya M.</strong>
                            <span>Cliente depuis 2023</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <div class="stars">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p>"Excellent pour promouvoir mes concerts. Dashboard très complet et support client au top !"</p>
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">KD</div>
                        <div>
                            <strong>Koffi D.</strong>
                            <span>Organisateur d'événements</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <div class="stars">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p>"Plateforme fiable, paiements sécurisés. Je recommande à tous mes amis !"</p>
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">FN</div>
                        <div>
                            <strong>Fatou N.</strong>
                            <span>Utilisatrice régulière</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Final -->
<section class="final-cta-section">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="cta-content">
                    <h2>Prêt à découvrir votre prochain événement ?</h2>
                    <p>Rejoignez des milliers d'Ivoiriens qui font confiance à ClicBillet CI</p>
                    <div class="cta-buttons">
                        <a href="/events" class="btn btn-orange btn-lg me-3">
                            <i class="fas fa-calendar-alt me-2"></i>
                            Voir les événements
                        </a>
                        <a href="/register" class="btn btn-outline-white btn-lg">
                            <i class="fas fa-user-plus me-2"></i>
                            Créer un compte
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
:root {
    --primary-orange: #FF6B35;
    --secondary-orange: #ff8c61;
    --dark-blue: #1a237e;
    --black-primary: #2c3e50;
}

/* Hero Section */
.hero-section {
    background: linear-gradient(135deg, var(--dark-blue) 0%, var(--black-primary) 50%, var(--primary-orange) 100%);
    position: relative;
    overflow: hidden;
    padding: 100px 0;
}

.hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.3);
    z-index: 1;
}

.hero-content {
    position: relative;
    z-index: 2;
}

.hero-title {
    font-size: 3.5rem;
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: 2rem;
}

.text-gradient {
    background: linear-gradient(45deg, var(--primary-orange), var(--secondary-orange));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.hero-subtitle {
    font-size: 1.3rem;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 3rem;
    line-height: 1.6;
}

/* Barre de recherche Hero */
.hero-search {
    margin-bottom: 2rem;
}

.search-container {
    display: flex;
    gap: 15px;
    max-width: 600px;
    margin: 0 auto;
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
    color: #999;
    z-index: 3;
}

.search-input {
    width: 100%;
    padding: 18px 20px 18px 55px;
    border: none;
    border-radius: 15px;
    font-size: 1.1rem;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.search-btn {
    background: var(--primary-orange);
    border: none;
    color: white;
    padding: 18px 30px;
    border-radius: 15px;
    font-weight: 600;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.search-btn:hover {
    background: #e55a2b;
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(255, 107, 53, 0.4);
    color: white;
}

/* Suggestions */
.search-suggestions {
    text-align: center;
    margin-top: 25px;
}

.suggestions-label {
    color: rgba(255, 255, 255, 0.8);
    margin-right: 15px;
}

.suggestion-tag {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 0.9rem;
    text-decoration: none;
    margin: 5px;
    display: inline-block;
    transition: all 0.3s ease;
}

.suggestion-tag:hover {
    background: var(--primary-orange);
    color: white;
    border-color: var(--primary-orange);
    transform: translateY(-2px);
}

/* Hero Stats */
.hero-stats {
    display: flex;
    flex-direction: column;
    gap: 20px;
    position: relative;
    z-index: 2;
}

.stat-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 25px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    gap: 20px;
    transition: all 0.3s ease;
}

.stat-card:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateX(10px);
}

.stat-icon {
    width: 60px;
    height: 60px;
    background: var(--primary-orange);
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.stat-content h3 {
    color: white;
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
}

.stat-content p {
    color: rgba(255, 255, 255, 0.8);
    margin: 0;
    font-size: 1rem;
}

/* Categories Section */
.categories-section {
    padding: 100px 0;
    background: #f8f9fa;
}

.section-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--black-primary);
    margin-bottom: 1rem;
}

.section-subtitle {
    font-size: 1.2rem;
    color: #6c757d;
    max-width: 600px;
    margin: 0 auto;
}

.category-card:hover .category-icon {
    transform: scale(1.1);
}

.category-card h5 {
    color: var(--black-primary);
    font-weight: 600;
    margin-bottom: 10px;
}

.category-card p {
    color: #6c757d;
    margin: 0;
}

/* Featured Events Section */
.featured-events-section {
    padding: 100px 0;
    background: white;
}

.btn-outline-orange {
    border: 2px solid var(--primary-orange);
    color: var(--primary-orange);
    border-radius: 25px;
    padding: 12px 25px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-outline-orange:hover {
    background: var(--primary-orange);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
}

.event-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    transition: all 0.4s ease;
    position: relative;
}

.event-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
}

.event-image {
    position: relative;
    height: 220px;
    overflow: hidden;
}

.event-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: all 0.4s ease;
}

.event-card:hover .event-image img {
    transform: scale(1.05);
}

.event-price {
    position: absolute;
    top: 15px;
    right: 15px;
    background: var(--primary-orange);
    color: white;
    padding: 8px 15px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
}

.event-category {
    position: absolute;
    top: 15px;
    left: 15px;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 6px 12px;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
}

.event-content {
    padding: 25px;
}

.event-date {
    color: var(--primary-orange);
    font-weight: 600;
    margin-bottom: 10px;
    font-size: 0.9rem;
}

.event-title {
    color: var(--black-primary);
    font-weight: 700;
    margin-bottom: 15px;
    line-height: 1.3;
}

.event-location {
    color: #6c757d;
    margin-bottom: 15px;
    font-size: 0.9rem;
}

.event-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.tickets-remaining {
    color: #28a745;
    font-size: 0.85rem;
    font-weight: 500;
}

.btn-orange {
    background: linear-gradient(135deg, var(--primary-orange), var(--secondary-orange));
    border: none;
    color: white;
    border-radius: 25px;
    padding: 12px 25px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-orange:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 107, 53, 0.4);
    color: white;
}

/* Why Choose Section */
.why-choose-section {
    padding: 100px 0;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.feature-card {
    background: white;
    padding: 40px 30px;
    border-radius: 20px;
    text-align: center;
    transition: all 0.4s ease;
    height: 100%;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(255, 107, 53, 0.15);
}

.feature-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--primary-orange), var(--secondary-orange));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 25px;
    color: white;
    font-size: 2rem;
    transition: all 0.3s ease;
}

.feature-card:hover .feature-icon {
    transform: scale(1.1) rotate(5deg);
}

.feature-card h5 {
    color: var(--black-primary);
    font-weight: 700;
    margin-bottom: 15px;
}

.feature-card p {
    color: #6c757d;
    line-height: 1.6;
    margin: 0;
}

/* Organizers Section */
.organizers-section {
    padding: 100px 0;
    background: white;
}

.organizer-content {
    padding-right: 30px;
}

.benefits-list {
    margin: 40px 0;
}

.benefit-item {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 30px;
}

.benefit-icon {
    width: 50px;
    height: 50px;
    background: rgba(255, 107, 53, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-orange);
    font-size: 1.2rem;
    flex-shrink: 0;
}

.benefit-item h6 {
    color: var(--black-primary);
    font-weight: 700;
    margin-bottom: 8px;
}

.benefit-item p {
    color: #6c757d;
    margin: 0;
    line-height: 1.5;
}

.cta-buttons {
    margin-top: 40px;
}

.btn-lg {
    padding: 15px 30px;
    font-size: 1.1rem;
}

/* Dashboard Preview */
.organizer-visual {
    position: relative;
}

.dashboard-preview {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transform: perspective(1000px) rotateY(-5deg) rotateX(5deg);
    transition: all 0.4s ease;
}

.dashboard-preview:hover {
    transform: perspective(1000px) rotateY(0deg) rotateX(0deg);
}

.dashboard-header {
    background: linear-gradient(135deg, var(--primary-orange), var(--secondary-orange));
    padding: 25px;
    color: white;
}

.dashboard-stats {
    display: flex;
    justify-content: space-between;
}

.mini-stat {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 1.8rem;
    font-weight: 700;
}

.stat-label {
    font-size: 0.8rem;
    opacity: 0.9;
}

.dashboard-chart {
    padding: 30px;
    background: #f8f9fa;
}

/* Testimonials Section */
.testimonials-section {
    padding: 100px 0;
    background: linear-gradient(135deg, var(--primary-orange) 0%, var(--secondary-orange) 100%);
}

.testimonials-section .section-title,
.testimonials-section .section-subtitle {
    color: white;
}

.testimonial-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 30px;
    border-radius: 20px;
    transition: all 0.4s ease;
    height: 100%;
}

.testimonial-card:hover {
    transform: translateY(-5px);
    background: white;
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
}

.stars {
    color: #ffc107;
    margin-bottom: 20px;
    font-size: 1.1rem;
}

.testimonial-content p {
    font-style: italic;
    line-height: 1.6;
    margin-bottom: 25px;
    color: #333;
}

.testimonial-author {
    display: flex;
    align-items: center;
    gap: 15px;
}

.author-avatar {
    width: 50px;
    height: 50px;
    background: var(--primary-orange);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1.1rem;
}

.testimonial-author strong {
    color: var(--black-primary);
    font-weight: 700;
}

.testimonial-author span {
    color: #6c757d;
    font-size: 0.9rem;
    display: block;
}

/* Final CTA Section */
.final-cta-section {
    padding: 100px 0;
    background: linear-gradient(135deg, var(--dark-blue) 0%, var(--black-primary) 100%);
    text-align: center;
}

.cta-content h2 {
    color: white;
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 20px;
}

.cta-content p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.3rem;
    margin-bottom: 40px;
}

.btn-outline-white {
    border: 2px solid white;
    color: white;
    border-radius: 25px;
    padding: 15px 30px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-outline-white:hover {
    background: white;
    color: var(--black-primary);
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(255, 255, 255, 0.3);
}

/* Responsive Design */
@media (max-width: 991.98px) {
    .hero-title {
        font-size: 2.5rem;
    }
    
    .search-container {
        flex-direction: column;
    }
    
    .search-btn {
        width: 100%;
    }
    
    .hero-stats {
        margin-top: 50px;
    }
    
    .organizer-content {
        padding-right: 0;
        margin-bottom: 50px;
    }
    
    .dashboard-preview {
        transform: none;
    }
    
    .cta-content h2 {
        font-size: 2.2rem;
    }
}

@media (max-width: 767.98px) {
    .hero-title {
        font-size: 2rem;
    }
    
    .hero-subtitle {
        font-size: 1.1rem;
    }
    
    .section-title {
        font-size: 2rem;
    }
    
    .stat-card {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .benefit-item {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .cta-buttons {
        flex-direction: column;
        gap: 15px;
    }
    
    .btn-lg {
        width: 100%;
    }
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.hero-content {
    animation: slideInLeft 1s ease-out;
}

.hero-stats {
    animation: slideInRight 1s ease-out 0.3s both;
}

.section-title {
    animation: fadeInUp 0.8s ease-out;
}

.category-card {
    animation: fadeInUp 0.8s ease-out;
}

.event-card {
    animation: fadeInUp 0.8s ease-out;
}

.feature-card {
    animation: fadeInUp 0.8s ease-out;
}

.testimonial-card {
    animation: fadeInUp 0.8s ease-out;
}

/* Loading animation pour les stats */
.stat-content h3 {
    animation: countUp 2s ease-out;
}

@keyframes countUp {
    from {
        transform: scale(0.5);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

<script>
// Graphique de prévisualisation du dashboard
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('previewChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun'],
                datasets: [{
                    label: 'Ventes',
                    data: [65, 89, 120, 89, 156, 189],
                    borderColor: '#FF6B35',
                    backgroundColor: 'rgba(255, 107, 53, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        display: false
                    },
                    y: {
                        display: false
                    }
                },
                elements: {
                    point: {
                        radius: 4,
                        hoverRadius: 6
                    }
                }
            }
        });
    }

    // Animation des compteurs
    function animateCounter(element, start, end, duration) {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const current = Math.floor(progress * (end - start) + start);
            element.innerText = current.toLocaleString() + '+';
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }

    // Animer les stats au scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const statNumbers = entry.target.querySelectorAll('.stat-content h3');
                statNumbers.forEach((stat, index) => {
                    const endValue = [1250, 50000, 180000][index];
                    setTimeout(() => {
                        animateCounter(stat, 0, endValue, 2000);
                    }, index * 200);
                });
                observer.unobserve(entry.target);
            }
        });
    });

    const heroStats = document.querySelector('.hero-stats');
    if (heroStats) {
        observer.observe(heroStats);
    }

    // Auto-focus sur la barre de recherche au survol
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.addEventListener('focus', function() {
            this.parentElement.parentElement.style.transform = 'scale(1.02)';
        });
        
        searchInput.addEventListener('blur', function() {
            this.parentElement.parentElement.style.transform = 'scale(1)';
        });
    }

    // Suggestions rapides
    document.querySelectorAll('.suggestion-tag').forEach(tag => {
        tag.addEventListener('click', function(e) {
            e.preventDefault();
            const searchTerm = this.textContent;
            searchInput.value = searchTerm;
            searchInput.form.submit();
        });
    });
});
</script>

</body>
