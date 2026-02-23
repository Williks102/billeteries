@extends('layouts.app')

@section('title', $page->seo_title)
@section('meta_description', $page->seo_description)

@section('content')
<div class="container my-5">
    @switch($page->template)
        @case('full-width')
            <!-- Template pleine largeur -->
            <div class="row">
                <div class="col-12">
                    <article class="page-content">
                        <header class="page-header text-center mb-5">
                            <h1 class="display-4 fw-bold text-orange">{{ $page->title }}</h1>
                            @if($page->excerpt)
                                <p class="lead text-muted">{{ $page->excerpt }}</p>
                            @endif
                        </header>
                        
                        <div class="page-body">
                            {!! $page->content !!}
                        </div>
                    </article>
                </div>
            </div>
            @break
            
        @case('landing')
            <!-- Template page d'atterrissage -->
            <div class="landing-page">
                <div class="hero-section text-center py-5 mb-5" style="background: linear-gradient(135deg, rgba(255,107,53,0.1), rgba(26,35,126,0.1)); border-radius: 20px;">
                    <h1 class="display-3 fw-bold text-orange mb-4">{{ $page->title }}</h1>
                    @if($page->excerpt)
                        <p class="lead fs-4 text-muted mb-4">{{ $page->excerpt }}</p>
                    @endif
                    <a href="#content" class="btn btn-orange btn-lg">
                        <i class="fas fa-arrow-down me-2"></i>Découvrir
                    </a>
                </div>
                
                <div id="content" class="row">
                    <div class="col-lg-10 mx-auto">
                        <div class="page-content">
                            {!! $page->content !!}
                        </div>
                    </div>
                </div>
            </div>
            @break
            
        @case('faq')
            <!-- Template FAQ -->
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <header class="page-header text-center mb-5">
                        <h1 class="display-4 fw-bold text-orange">{{ $page->title }}</h1>
                        @if($page->excerpt)
                            <p class="lead text-muted">{{ $page->excerpt }}</p>
                        @endif
                    </header>
                    
                    <!-- Barre de recherche FAQ -->
                    <div class="search-faq mb-4">
                        <div class="input-group">
                            <input type="text" class="form-control" id="faqSearch" 
                                   placeholder="Rechercher dans les questions...">
                            <button class="btn btn-orange" type="button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="page-content">
                        {!! $page->content !!}
                    </div>
                </div>
            </div>
            @break
            
        @case('contact')
            <!-- Template contact -->
            <div class="row">
                <div class="col-lg-8">
                    <header class="page-header mb-4">
                        <h1 class="display-5 fw-bold text-orange">{{ $page->title }}</h1>
                        @if($page->excerpt)
                            <p class="lead text-muted">{{ $page->excerpt }}</p>
                        @endif
                    </header>
                    
                    <div class="page-content">
                        {!! $page->content !!}
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- Informations de contact -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h5 class="card-title"><i class="fas fa-info-circle text-orange me-2"></i>Informations</h5>
                            
                            <div class="contact-item mb-3">
                                <h6><i class="fas fa-envelope text-orange me-2"></i>Email</h6>
                                <p class="mb-0">contact@clicbillet.ci</p>
                                <small class="text-muted">Réponse sous 24h</small>
                            </div>
                            
                            <div class="contact-item mb-3">
                                <h6><i class="fas fa-phone text-orange me-2"></i>Téléphone</h6>
                                <p class="mb-0">+225 XX XX XX XX</p>
                                <small class="text-muted">Lun-Ven 8h-18h</small>
                            </div>
                            
                            <div class="contact-item mb-3">
                                <h6><i class="fas fa-map-marker-alt text-orange me-2"></i>Adresse</h6>
                                <p class="mb-0">Abidjan, Cocody<br>Côte d'Ivoire</p>
                            </div>
                            
                            <div class="contact-item">
                                <h6><i class="fas fa-clock text-orange me-2"></i>Horaires</h6>
                                <p class="mb-0">Lundi - Vendredi : 8h - 18h<br>
                                Samedi : 9h - 15h<br>
                                Dimanche : Fermé</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @break
            
        @case('pricing')
            <!-- Template tarification -->
            <div class="row">
                <div class="col-12">
                    <header class="page-header text-center mb-5">
                        <h1 class="display-4 fw-bold text-orange">{{ $page->title }}</h1>
                        @if($page->excerpt)
                            <p class="lead text-muted">{{ $page->excerpt }}</p>
                        @endif
                    </header>
                    
                    <div class="page-content">
                        {!! $page->content !!}
                    </div>
                    
                    <!-- CTA Section -->
                    <div class="text-center mt-5">
                        <div class="bg-orange text-white p-5 rounded">
                            <h3 class="mb-4">Prêt à commencer ?</h3>
                            <p class="mb-4">Rejoignez des centaines d'organisateurs qui nous font confiance</p>
                            <a href="{{ route('register') }}" class="btn btn-light btn-lg me-3">
                                <i class="fas fa-user-plus me-2"></i>Créer un compte
                            </a>
                            <a href="{{ route('pages.contact') }}" class="btn btn-outline-light btn-lg">
                                <i class="fas fa-phone me-2"></i>Nous contacter
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @break
            
        @default
            <!-- Template par défaut -->
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <article class="page-content">
                        <header class="page-header mb-4">
                            <h1 class="display-5 fw-bold text-orange">{{ $page->title }}</h1>
                            @if($page->excerpt)
                                <p class="lead text-muted">{{ $page->excerpt }}</p>
                            @endif
                        </header>
                        
                        <div class="page-body">
                            {!! $page->content !!}
                        </div>
                        
                        <footer class="page-footer mt-5 pt-4 border-top">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted">
                                        Dernière mise à jour : {{ $page->updated_at->format('d/m/Y') }}
                                    </small>
                                </div>
                                <div>
                                    <a href="{{ route('pages.contact') }}" class="btn btn-outline-orange btn-sm">
                                        <i class="fas fa-question-circle me-2"></i>Une question ?
                                    </a>
                                </div>
                            </div>
                        </footer>
                    </article>
                </div>
            </div>
    @endswitch
</div>

<!-- Styles pour le contenu des pages -->
<style>
.page-content {
    font-size: 1.1rem;
    line-height: 1.7;
}

.page-content h1, 
.page-content h2, 
.page-content h3, 
.page-content h4, 
.page-content h5, 
.page-content h6 {
    color: var(--primary-orange);
    margin-top: 2rem;
    margin-bottom: 1rem;
}

.page-content h1 {
    border-bottom: 3px solid var(--primary-orange);
    padding-bottom: 0.5rem;
}

.page-content h2 {
    border-bottom: 1px solid rgba(255, 107, 53, 0.3);
    padding-bottom: 0.3rem;
}

.page-content p {
    margin-bottom: 1.5rem;
}

.page-content ul, 
.page-content ol {
    margin-bottom: 1.5rem;
    padding-left: 2rem;
}

.page-content li {
    margin-bottom: 0.5rem;
}

.page-content blockquote {
    border-left: 4px solid var(--primary-orange);
    background: rgba(255, 107, 53, 0.05);
    padding: 1.5rem;
    margin: 2rem 0;
    border-radius: 0 8px 8px 0;
    font-style: italic;
}

.page-content img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    margin: 1.5rem 0;
}

.page-content pre {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    border-left: 4px solid var(--primary-orange);
    overflow-x: auto;
    margin: 1.5rem 0;
}

.page-content code {
    background: #f8f9fa;
    padding: 0.3rem 0.5rem;
    border-radius: 4px;
    font-size: 0.9em;
    color: var(--primary-orange);
}

.page-content table {
    width: 100%;
    margin: 2rem 0;
    border-collapse: collapse;
}

.page-content table th,
.page-content table td {
    padding: 0.75rem;
    border: 1px solid #dee2e6;
    text-align: left;
}

.page-content table th {
    background: var(--primary-orange);
    color: white;
    font-weight: 600;
}

.page-content table tbody tr:nth-child(even) {
    background: rgba(255, 107, 53, 0.05);
}

.page-content a {
    color: var(--primary-orange);
    text-decoration: none;
    border-bottom: 1px solid transparent;
    transition: all 0.3s ease;
}

.page-content a:hover {
    color: var(--primary-dark);
    border-bottom-color: var(--primary-orange);
}

/* Styles spécifiques aux templates */
.hero-section {
    animation: fadeInUp 1s ease-out;
}

.contact-item {
    transition: all 0.3s ease;
}

.contact-item:hover {
    transform: translateX(5px);
}

/* Animation smooth scroll pour les liens ancres */
html {
    scroll-behavior: smooth;
}

/* Responsive */
@media (max-width: 768px) {
    .page-content {
        font-size: 1rem;
    }
    
    .display-3 {
        font-size: 2.5rem;
    }
    
    .display-4 {
        font-size: 2rem;
    }
    
    .display-5 {
        font-size: 1.75rem;
    }
    
    .hero-section {
        padding: 3rem 1rem !important;
    }
    
    .page-content ul,
    .page-content ol {
        padding-left: 1.5rem;
    }
}

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
</style>

@if($page->template === 'faq')
<script src="{{ asset('js/admin-pages-show.js') }}" defer></script>
@endif

@if($page->template === 'landing')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Smooth scroll pour le bouton "Découvrir"
    const discoverBtn = document.querySelector('a[href="#content"]');
    if (discoverBtn) {
        discoverBtn.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('content').scrollIntoView({
                behavior: 'smooth'
            });
        });
    }
});
</script>
@endif
@endsection