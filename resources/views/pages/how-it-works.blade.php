@extends('layouts.app')

@section('title', 'Comment ça marche - ClicBillet CI')

@section('content')
<div class="container my-5">
    <!-- Hero Section -->
    <div class="row mb-5">
        <div class="col-12 text-center">
            <h1 class="display-4 fw-bold text-orange mb-4">Comment ça marche ?</h1>
            <p class="lead">ClicBillet CI simplifie l'achat et la vente de billets d'événements en Côte d'Ivoire</p>
        </div>
    </div>

    <!-- Pour les acheteurs -->
    <div class="row mb-5">
        <div class="col-12">
            <h2 class="text-center mb-5"><i class="fas fa-users text-orange me-3"></i>Pour les acheteurs</h2>
        </div>
        
        <div class="col-md-3 text-center mb-4">
            <div class="p-4 border rounded">
                <div class="bg-orange text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <i class="fas fa-search fa-2x"></i>
                </div>
                <h5>1. Découvrez</h5>
                <p>Parcourez les événements disponibles par catégorie ou utilisez la recherche</p>
            </div>
        </div>
        
        <div class="col-md-3 text-center mb-4">
            <div class="p-4 border rounded">
                <div class="bg-orange text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <i class="fas fa-ticket-alt fa-2x"></i>
                </div>
                <h5>2. Sélectionnez</h5>
                <p>Choisissez vos billets et ajoutez-les à votre panier en quelques clics</p>
            </div>
        </div>
        
        <div class="col-md-3 text-center mb-4">
            <div class="p-4 border rounded">
                <div class="bg-orange text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <i class="fas fa-credit-card fa-2x"></i>
                </div>
                <h5>3. Payez</h5>
                <p>Réglez en ligne par carte bancaire ou Mobile Money en toute sécurité</p>
            </div>
        </div>
        
        <div class="col-md-3 text-center mb-4">
            <div class="p-4 border rounded">
                <div class="bg-orange text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <i class="fas fa-mobile-alt fa-2x"></i>
                </div>
                <h5>4. Profitez</h5>
                <p>Recevez vos billets par email et présentez-les à l'entrée de l'événement</p>
            </div>
        </div>
    </div>

    <!-- Pour les organisateurs -->
    <div class="row mb-5 bg-light py-5 rounded">
        <div class="col-12">
            <h2 class="text-center mb-5"><i class="fas fa-megaphone text-orange me-3"></i>Pour les organisateurs</h2>
        </div>
        
        <div class="col-md-4 text-center mb-4">
            <div class="p-4">
                <div class="bg-orange text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <i class="fas fa-user-plus fa-2x"></i>
                </div>
                <h5>1. Inscrivez-vous</h5>
                <p>Créez votre compte promoteur et soumettez vos documents pour validation</p>
            </div>
        </div>
        
        <div class="col-md-4 text-center mb-4">
            <div class="p-4">
                <div class="bg-orange text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <i class="fas fa-calendar-plus fa-2x"></i>
                </div>
                <h5>2. Créez</h5>
                <p>Créez vos événements, configurez les types de billets et définissez vos prix</p>
            </div>
        </div>
        
        <div class="col-md-4 text-center mb-4">
            <div class="p-4">
                <div class="bg-orange text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <i class="fas fa-chart-line fa-2x"></i>
                </div>
                <h5>3. Vendez</h5>
                <p>Publiez votre événement et suivez vos ventes en temps réel depuis votre dashboard</p>
            </div>
        </div>
    </div>

    <!-- Avantages -->
    <div class="row mb-5">
        <div class="col-12">
            <h2 class="text-center mb-5"><i class="fas fa-star text-orange me-3"></i>Pourquoi choisir ClicBillet CI ?</h2>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="d-flex">
                <div class="me-3">
                    <i class="fas fa-shield-alt fa-2x text-orange"></i>
                </div>
                <div>
                    <h5>Sécurité garantie</h5>
                    <p>Toutes les transactions sont sécurisées et protégées. Vos données personnelles restent confidentielles.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="d-flex">
                <div class="me-3">
                    <i class="fas fa-clock fa-2x text-orange"></i>
                </div>
                <div>
                    <h5>Billets instantanés</h5>
                    <p>Recevez vos billets immédiatement par email après le paiement. Plus besoin d'attendre !</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="d-flex">
                <div class="me-3">
                    <i class="fas fa-headset fa-2x text-orange"></i>
                </div>
                <div>
                    <h5>Support 24/7</h5>
                    <p>Notre équipe est disponible pour vous aider avant, pendant et après votre achat.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="d-flex">
                <div class="me-3">
                    <i class="fas fa-mobile-alt fa-2x text-orange"></i>
                </div>
                <div>
                    <h5>100% mobile</h5>
                    <p>Achetez, gérez et utilisez vos billets directement depuis votre téléphone.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA -->
    <div class="row">
        <div class="col-12 text-center">
            <h3 class="mb-4">Prêt à commencer ?</h3>
            <a href="{{ route('home') }}" class="btn btn-orange btn-lg me-3">
                <i class="fas fa-search me-2"></i>Découvrir les événements
            </a>
            <a href="{{ route('register') }}" class="btn btn-outline-orange btn-lg">
                <i class="fas fa-user-plus me-2"></i>Devenir organisateur
            </a>
        </div>
    </div>
</div>
@endsection