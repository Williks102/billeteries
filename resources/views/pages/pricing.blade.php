@extends('layouts.app')

@section('title', 'Tarifs - ClicBillet CI')

@section('content')
<div class="container my-5">
    <!-- Hero Section -->
    <div class="row mb-5">
        <div class="col-12 text-center">
            <h1 class="display-4 fw-bold text-orange mb-4">Nos tarifs</h1>
            <p class="lead">Des commissions transparentes et compétitives pour tous types d'événements</p>
        </div>
    </div>

    <!-- Plans tarifaires -->
    <div class="row mb-5">
        @foreach($pricing as $plan)
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow h-100 {{ $loop->index == 1 ? 'border-orange' : '' }}">
                @if($loop->index == 1)
                <div class="ribbon bg-orange text-white text-center py-2">
                    <small>POPULAIRE</small>
                </div>
                @endif
                
                <div class="card-body p-4 text-center">
                    <h3 class="card-title">{{ $plan['name'] }}</h3>
                    <div class="display-4 fw-bold text-orange mb-4">{{ $plan['commission'] }}</div>
                    <p class="text-muted mb-4">de commission sur les ventes</p>
                    
                    <ul class="list-unstyled text-start">
                        @foreach($plan['features'] as $feature)
                        <li class="mb-2">
                            <i class="fas fa-check text-orange me-2"></i>{{ $feature }}
                        </li>
                        @endforeach
                    </ul>
                    
                    <a href="{{ route('register') }}" class="btn {{ $loop->index == 1 ? 'btn-orange' : 'btn-outline-orange' }} btn-lg w-100 mt-4">
                        Commencer maintenant
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Comparaison détaillée -->
    <div class="row mb-5">
        <div class="col-12">
            <h2 class="text-center mb-5">Comparaison détaillée</h2>
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="bg-orange text-white">
                        <tr>
                            <th>Fonctionnalités</th>
                            <th class="text-center">Gratuit</th>
                            <th class="text-center">Standard</th>
                            <th class="text-center">Premium</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Commission</strong></td>
                            <td class="text-center">0%</td>
                            <td class="text-center">5%</td>
                            <td class="text-center">3%</td>
                        </tr>
                        <tr>
                            <td>Création d'événements illimitée</td>
                            <td class="text-center"><i class="fas fa-check text-success"></i></td>
                            <td class="text-center"><i class="fas fa-check text-success"></i></td>
                            <td class="text-center"><i class="fas fa-check text-success"></i></td>
                        </tr>
                        <tr>
                            <td>Types de billets multiples</td>
                            <td class="text-center"><i class="fas fa-check text-success"></i></td>
                            <td class="text-center"><i class="fas fa-check text-success"></i></td>
                            <td class="text-center"><i class="fas fa-check text-success"></i></td>
                        </tr>
                        <tr>
                            <td>Statistiques avancées</td>
                            <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                            <td class="text-center"><i class="fas fa-check text-success"></i></td>
                            <td class="text-center"><i class="fas fa-check text-success"></i></td>
                        </tr>
                        <tr>
                            <td>Support prioritaire</td>
                            <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                            <td class="text-center"><i class="fas fa-check text-success"></i></td>
                            <td class="text-center"><i class="fas fa-check text-success"></i></td>
                        </tr>
                        <tr>
                            <td>Manager dédié</td>
                            <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                            <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                            <td class="text-center"><i class="fas fa-check text-success"></i></td>
                        </tr>
                        <tr>
                            <td>Marketing personnalisé</td>
                            <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                            <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                            <td class="text-center"><i class="fas fa-check text-success"></i></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Frais supplémentaires -->
    <div class="row mb-5">
        <div class="col-12">
            <h2 class="text-center mb-5">Frais de transaction</h2>
            
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card border-0 bg-light h-100">
                        <div class="card-body p-4">
                            <h5><i class="fas fa-credit-card text-orange me-2"></i>Carte bancaire</h5>
                            <p class="mb-0">2.9% + 500 FCFA par transaction</p>
                            <small class="text-muted">Frais prélevés automatiquement</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card border-0 bg-light h-100">
                        <div class="card-body p-4">
                            <h5><i class="fas fa-mobile-alt text-orange me-2"></i>Mobile Money</h5>
                            <p class="mb-0">1.5% + 200 FCFA par transaction</p>
                            <small class="text-muted">Frais avantageux pour le mobile</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ Tarifs -->
    <div class="row mb-5">
        <div class="col-12">
            <h2 class="text-center mb-5">Questions fréquentes sur les tarifs</h2>
            
            <div class="accordion" id="pricingFAQ">
                <div class="accordion-item mb-3">
                    <h3 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                            <strong>Quand sont prélevées les commissions ?</strong>
                        </button>
                    </h3>
                    <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#pricingFAQ">
                        <div class="accordion-body">
                            Les commissions sont automatiquement déduites du montant de chaque vente. Vous recevez le montant net sur votre compte promoteur.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item mb-3">
                    <h3 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                            <strong>Y a-t-il des frais cachés ?</strong>
                        </button>
                    </h3>
                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#pricingFAQ">
                        <div class="accordion-body">
                            Non, nous pratiquons une politique de transparence totale. Seules les commissions affichées et les frais de transaction s'appliquent.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item mb-3">
                    <h3 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                            <strong>Comment changer de plan ?</strong>
                        </button>
                    </h3>
                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#pricingFAQ">
                        <div class="accordion-body">
                            Les plans évoluent automatiquement selon votre volume de ventes. Contactez-nous pour des besoins spécifiques ou des négociations de volume.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item mb-3">
                    <h3 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                            <strong>Puis-je négocier les tarifs ?</strong>
                        </button>
                    </h3>
                    <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#pricingFAQ">
                        <div class="accordion-body">
                            Pour les gros volumes (>1000 billets/mois) ou les partenariats long terme, nous proposons des tarifs préférentiels. Contactez notre équipe commerciale.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA -->
    <div class="row">
        <div class="col-12">
            <div class="bg-orange text-white p-5 rounded text-center">
                <h3 class="mb-4">Prêt à créer votre premier événement ?</h3>
                <p class="mb-4">Rejoignez des centaines d'organisateurs qui nous font confiance</p>
                <a href="{{ route('register') }}" class="btn btn-light btn-lg me-3">
                    <i class="fas fa-user-plus me-2"></i>Créer un compte gratuit
                </a>
                <a href="{{ route('pages.contact') }}" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-phone me-2"></i>Parler à un conseiller
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.ribbon {
    position: absolute;
    top: -5px;
    left: -5px;
    right: -5px;
    z-index: 1;
}
.border-orange {
    border: 2px solid var(--primary-orange) !important;
}
</style>
@endsection