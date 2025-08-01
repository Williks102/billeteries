@extends('layouts.app')

@section('title', 'À propos de ClicBillet CI')

@section('content')
<div class="container my-5">
    <!-- Hero Section -->
    <div class="row mb-5">
        <div class="col-12 text-center">
            <h1 class="display-4 fw-bold text-orange mb-4">À propos de ClicBillet CI</h1>
            <p class="lead">La plateforme de billetterie #1 en Côte d'Ivoire</p>
        </div>
    </div>

    <!-- Notre histoire -->
    <div class="row mb-5 align-items-center">
        <div class="col-lg-6 mb-4">
            <h2 class="mb-4">Notre histoire</h2>
            <p class="lead">ClicBillet CI est née d'une vision simple : démocratiser l'accès aux événements culturels, sportifs et professionnels en Côte d'Ivoire.</p>
            
            <p>Fondée en 2024, notre plateforme répond aux besoins croissants des organisateurs d'événements et du public ivoirien en matière de billetterie numérique. Nous croyons que chaque événement mérite d'être accessible, sécurisé et mémorable.</p>
            
            <p>Aujourd'hui, nous sommes fiers d'accompagner des centaines d'organisateurs et de servir des milliers de participants à travers tout le pays.</p>
        </div>
        <div class="col-lg-6 text-center">
            <div class="bg-orange p-5 rounded text-white">
                <i class="fas fa-rocket fa-4x mb-4"></i>
                <h3>Innovation</h3>
                <p>Une technologie moderne au service de l'événementiel ivoirien</p>
            </div>
        </div>
    </div>

    <!-- Nos valeurs -->
    <div class="row mb-5">
        <div class="col-12">
            <h2 class="text-center mb-5">Nos valeurs</h2>
        </div>
        
        <div class="col-md-4 mb-4 text-center">
            <div class="p-4 h-100 border rounded">
                <i class="fas fa-handshake fa-3x text-orange mb-3"></i>
                <h5>Confiance</h5>
                <p>Nous bâtissons des relations durables basées sur la transparence et la fiabilité.</p>
            </div>
        </div>
        
        <div class="col-md-4 mb-4 text-center">
            <div class="p-4 h-100 border rounded">
                <i class="fas fa-lightbulb fa-3x text-orange mb-3"></i>
                <h5>Innovation</h5>
                <p>Nous investissons constamment dans les nouvelles technologies pour améliorer l'expérience utilisateur.</p>
            </div>
        </div>
        
        <div class="col-md-4 mb-4 text-center">
            <div class="p-4 h-100 border rounded">
                <i class="fas fa-users fa-3x text-orange mb-3"></i>
                <h5>Communauté</h5>
                <p>Nous soutenons l'écosystème événementiel ivoirien et favorisons les connexions locales.</p>
            </div>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row mb-5 bg-light py-5 rounded">
        <div class="col-12">
            <h2 class="text-center mb-5">ClicBillet CI en chiffres</h2>
        </div>
        
        <div class="col-md-3 text-center mb-4">
            <div class="p-3">
                <h2 class="text-orange fw-bold">{{ number_format($stats['total_events']) }}+</h2>
                <p class="mb-0">Événements publiés</p>
            </div>
        </div>
        
        <div class="col-md-3 text-center mb-4">
            <div class="p-3">
                <h2 class="text-orange fw-bold">{{ number_format($stats['total_users']) }}+</h2>
                <p class="mb-0">Utilisateurs actifs</p>
            </div>
        </div>
        
        <div class="col-md-3 text-center mb-4">
            <div class="p-3">
                <h2 class="text-orange fw-bold">{{ number_format($stats['total_promoters']) }}+</h2>
                <p class="mb-0">Organisateurs partenaires</p>
            </div>
        </div>
        
        <div class="col-md-3 text-center mb-4">
            <div class="p-3">
                <h2 class="text-orange fw-bold">{{ $stats['categories_count'] }}</h2>
                <p class="mb-0">Catégories d'événements</p>
            </div>
        </div>
    </div>

    <!-- Notre mission -->
    <div class="row mb-5">
        <div class="col-lg-8 mx-auto text-center">
            <h2 class="mb-4">Notre mission</h2>
            <p class="lead">Connecter les organisateurs d'événements et le public ivoirien grâce à une plateforme simple, sécurisée et accessible à tous.</p>
            
            <div class="row mt-5">
                <div class="col-md-6 mb-4">
                    <div class="d-flex">
                        <i class="fas fa-check-circle text-orange me-3 mt-1"></i>
                        <div class="text-start">
                            <h6>Pour les organisateurs</h6>
                            <p class="small">Outils professionnels pour gérer et promouvoir vos événements efficacement</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="d-flex">
                        <i class="fas fa-check-circle text-orange me-3 mt-1"></i>
                        <div class="text-start">
                            <h6>Pour le public</h6>
                            <p class="small">Découverte simple d'événements et achat de billets en quelques clics</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="d-flex">
                        <i class="fas fa-check-circle text-orange me-3 mt-1"></i>
                        <div class="text-start">
                            <h6>Sécurité maximale</h6>
                            <p class="small">Transactions protégées et données personnelles sécurisées</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="d-flex">
                        <i class="fas fa-check-circle text-orange me-3 mt-1"></i>
                        <div class="text-start">
                            <h6>Support local</h6>
                            <p class="small">Équipe basée en Côte d'Ivoire, proche de nos utilisateurs</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Équipe -->
    <div class="row mb-5">
        <div class="col-12">
            <h2 class="text-center mb-5">Notre équipe</h2>
            <div class="row justify-content-center">
                <div class="col-md-4 text-center mb-4">
                    <div class="p-4">
                        <div class="bg-orange text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px;">
                            <i class="fas fa-user fa-3x"></i>
                        </div>
                        <h5>Direction Générale</h5>
                        <p class="text-muted">Vision stratégique et développement</p>
                    </div>
                </div>
                
                <div class="col-md-4 text-center mb-4">
                    <div class="p-4">
                        <div class="bg-orange text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px;">
                            <i class="fas fa-code fa-3x"></i>
                        </div>
                        <h5>Équipe Technique</h5>
                        <p class="text-muted">Développement et maintenance plateforme</p>
                    </div>
                </div>
                
                <div class="col-md-4 text-center mb-4">
                    <div class="p-4">
                        <div class="bg-orange text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px;">
                            <i class="fas fa-headset fa-3x"></i>
                        </div>
                        <h5>Support Client</h5>
                        <p class="text-muted">Assistance et accompagnement utilisateurs</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA -->
    <div class="row">
        <div class="col-12">
            <div class="bg-orange text-white p-5 rounded text-center">
                <h3 class="mb-4">Rejoignez l'aventure ClicBillet CI</h3>
                <p class="mb-4">Que vous soyez organisateur ou passionné d'événements, nous avons une place pour vous</p>
                <a href="{{ route('register') }}" class="btn btn-light btn-lg me-3">
                    <i class="fas fa-user-plus me-2"></i>Créer un compte
                </a>
                <a href="{{ route('pages.contact') }}" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-envelope me-2"></i>Nous contacter
                </a>
            </div>
        </div>
    </div>
</div>
@endsection