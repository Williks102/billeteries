{{-- resources/views/pages/pricing.blade.php --}}
@extends('layouts.app')

@section('title', 'Tarifs - ClicBillet CI')

@push('styles')
<style>
    :root {
        --primary-orange: #FF6B35;
        --primary-dark: #E55A2B;
        --success-color: #28a745;
    }

    .hero-section {
        background: linear-gradient(135deg, #060242ff, #100a5eff);
        color: white;
        padding: 4rem 0;
        text-align: center;
        margin-bottom: 3rem;
    }

    .hero-section h1 {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .hero-section p {
        font-size: 1.3rem;
        opacity: 0.9;
    }

    .commission-highlight {
        background: white;
        color: var(--primary-orange);
        padding: 3rem;
        border-radius: 25px;
        box-shadow: 0 8px 40px rgba(0,0,0,0.15);
        margin: -2rem auto 3rem auto;
        max-width: 600px;
        text-align: center;
    }

    .commission-rate {
        font-size: 5rem;
        font-weight: 900;
        color: var(--primary-orange);
        line-height: 1;
        margin-bottom: 0.5rem;
    }

    .commission-subtitle {
        font-size: 1.5rem;
        font-weight: 600;
        color: #1a1a1a;
        margin-bottom: 1rem;
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        margin: 3rem 0;
    }

    .feature-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        border-left: 1px;
        transition: all 0.3s ease;
    }

    .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(255, 107, 53, 0.2);
    }

    .feature-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, var(--primary-orange), var(--primary-dark));
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .calculator-section {
        background: #f8f9fa;
        padding: 3rem 0;
        margin: 3rem 0;
    }

    .calculator-card {
        background: white;
        border-radius: 20px;
        padding: 3rem;
        box-shadow: 0 8px 30px rgba(0,0,0,0.1);
        max-width: 600px;
        margin: 0 auto;
    }

    .calculator-input {
        position: relative;
        margin-bottom: 2rem;
    }

    .calculator-input input {
        border: 3px solid #e9ecef;
        border-radius: 15px;
        padding: 1rem 1.5rem;
        font-size: 1.3rem;
        font-weight: 600;
        text-align: center;
        transition: all 0.3s ease;
    }

    .calculator-input input:focus {
        border-color: var(--primary-orange);
        box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
        outline: none;
    }

    .calculator-results {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-top: 2rem;
    }

    .result-box {
        text-align: center;
        padding: 2rem;
        border-radius: 15px;
        border: 2px solid #e9ecef;
    }

    .result-participants {
        background: rgba(23, 162, 184, 0.1);
        border-color: #17a2b8;
    }

    .result-earnings {
        background: rgba(40, 167, 69, 0.1);
        border-color: var(--success-color);
    }

    .result-amount {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .result-participants .result-amount {
        color: #17a2b8;
    }

    .result-earnings .result-amount {
        color: var(--success-color);
    }

    .workflow-steps {
        background: white;
        padding: 3rem;
        border-radius: 25px;
        margin: 3rem 0;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }

    .step {
        display: flex;
        align-items: center;
        margin-bottom: 3rem;
        padding: 2rem;
        border-radius: 15px;
        background: #f8f9fa;
        transition: all 0.3s ease;
    }

    .step:hover {
        background: rgba(255, 107, 53, 0.05);
        transform: translateX(10px);
    }

    .step-number {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, var(--primary-orange), var(--primary-dark));
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: 700;
        margin-right: 2rem;
        flex-shrink: 0;
        box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
    }

    .step-content h4 {
        color: var(--primary-orange);
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .cta-section {
        background: linear-gradient(135deg, #060242ff, #100a5eff);
        color: white;
        padding: 4rem;
        border-radius: 25px;
        text-align: center;
        margin: 3rem 0;
    }

    .cta-section h3 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
    }

    .btn {
        border-radius: 12px;
        padding: 1rem 2rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-light {
        background: white;
        color: var(--primary-orange);
        border: none;
    }

    .btn-light:hover {
        background: #f8f9fa;
        color: var(--primary-dark);
        transform: translateY(-2px);
    }

    .btn-outline-light:hover {
        background: rgba(255,255,255,0.1);
        color: white;
        transform: translateY(-2px);
    }

    @media (max-width: 768px) {
        .hero-section h1 {
            font-size: 2.5rem;
        }
        
        .commission-rate {
            font-size: 4rem;
        }
        
        .calculator-results {
            grid-template-columns: 1fr;
        }
        
        .step {
            flex-direction: column;
            text-align: center;
        }
        
        .step-number {
            margin-right: 0;
            margin-bottom: 1.5rem;
        }
        
        .cta-section {
            padding: 3rem 2rem;
        }
        
        .cta-section h3 {
            font-size: 2rem;
        }
    }
</style>
@endpush

@section('content')
<!-- Hero Section -->
<div class="hero-section">
    <div class="container">
        <h1><i class="fas fa-tags me-3"></i>TARIFS</h1>
        <p>Pour les organisateurs d'événements</p>
        <p><strong>Publier un événement sur ClicBillet CI est GRATUIT.</strong><br>
        Les frais ne s'appliquent qu'aux tickets payés par les participants.</p>
    </div>
</div>

<div class="container">
    <!-- Commission principale -->
    <div class="commission-highlight">
        <div class="commission-rate">8<small style="font-size: 0.6em;">%</small></div>
        <div class="commission-subtitle">Et puis c'est tout !</div>
        <p class="mb-0">Vendez les tickets de votre événement sur <strong>ClicBillet CI</strong></p>
    </div>

    <!-- Description des avantages -->
    <div class="text-center mb-5">
        <p class="lead">Avec notre plateforme, les frais ne sont que de <strong>8%</strong> du prix du ticket. Profitez d'une place de marché 24/7 et de milliers de participants potentiels. Vous ne trouverez pas meilleur service pour vendre vos tickets en Côte d'Ivoire.</p>
    </div>

    <!-- Calculateur -->
    <div class="calculator-section">
        <div class="container">
            <h2 class="text-center mb-4">Comprendre nos frais très simplement</h2>
            <p class="text-center text-muted mb-4">Posez le stylo et le papier et découvrez vos frais avec notre calculateur en ligne. Entrez simplement le prix d'un ticket et laissez-nous faire le reste !</p>
            
            <div class="calculator-card">
                <h3 class="text-center mb-4">
                    <i class="fas fa-calculator me-2" style="color: var(--primary-orange);"></i>
                    Calculez vos revenus ClicBillet CI
                </h3>
                
                <div class="calculator-input">
                    <label class="form-label text-center d-block mb-3">
                        <strong>Prix d'un ticket</strong>
                    </label>
                    <input type="number" id="ticketPrice" class="form-control" 
                           placeholder="Entrez un montant *" min="0" step="500" value="5000">
                    <small class="text-muted d-block text-center mt-2">En FCFA</small>
                </div>

                <div class="calculator-results">
                    <div class="result-box result-participants">
                        <div class="result-amount" id="participantPrice">5 000 F</div>
                        <div>Les participants payeront</div>
                        <small class="text-muted">Prix ticket + frais techniques</small>
                    </div>
                    <div class="result-box result-earnings">
                        <div class="result-amount" id="organizerEarnings">4 100 F</div>
                        <div>Vous recevrez</div>
                        <small class="text-muted">Vos revenus nets</small>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Commission ClicBillet : 8% + frais techniques 500 FCFA/ticket
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Avantages -->
    <h2 class="text-center mb-5">Pourquoi organiser votre événement sur ClicBillet CI ?</h2>
    
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-store"></i>
            </div>
            <h4 class="mb-3">Place de marché</h4>
            <p>En plus d'une disponibilité 24h/24 et 7j/7, nous étendons les moyens de paiement pour vos participants grâce à notre réseau étendu. Vous ne trouverez pas un si grand marché pour vendre vos tickets en Côte d'Ivoire.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-cut"></i>
            </div>
            <h4 class="mb-3">Suppression des coûts annexes</h4>
            <p>Infographie, impression, collecte des recettes, contrôle des tickets... Oubliez ces tâches fastidieuses et concentrez-vous sur le cœur de votre événement. Notre plateforme est une solution clé en main.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-cogs"></i>
            </div>
            <h4 class="mb-3">Simplicité et flexibilité</h4>
            <p>ClicBillet CI est facile à utiliser avec uniquement des fonctionnalités utiles. Plusieurs types de tickets possibles, prix modifiables à tout moment, outils de gestion avancés et bien plus.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-headset"></i>
            </div>
            <h4 class="mb-3">Accompagnement</h4>
            <p>De la vente des tickets à la fin de l'événement, nous sommes à vos côtés. Notre Service Client est disponible et prêt à répondre à vos questions. La satisfaction de nos clients est notre priorité.</p>
        </div>
    </div>

    <!-- Workflow -->
    <div class="workflow-steps">
        <h2 class="text-center mb-5">
            <i class="fas fa-route me-3" style="color: var(--primary-orange);"></i>
            Comment ça marche en 4 étapes
        </h2>

        <div class="step">
            <div class="step-number">1</div>
            <div class="step-content">
                <h4>Renseignez les informations</h4>
                <p>Renseignez les informations de votre événement : titre, description, images, date, lieu, sponsors, types de tickets, infoline...etc. Vous avez la possibilité d'indiquer si l'événement est diffusé en ligne, ou si vous souhaitez ne recevoir que vos propres invités.</p>
            </div>
        </div>

        <div class="step">
            <div class="step-number">2</div>
            <div class="step-content">
                <h4>Publiez votre événement</h4>
                <p>Une fois les informations sur l'événement saisies, publiez-le pour le rendre disponible à l'achat. Vos participants où qu'ils soient, à n'importe quel moment peuvent acheter des places. De votre côté, vous pouvez regarder en direct dans votre dashboard la liste de vos futurs convives et la recette générée.</p>
            </div>
        </div>

        <div class="step">
            <div class="step-number">3</div>
            <div class="step-content">
                <h4>Organisez l'événement</h4>
                <p>Le jour J, utilisez notre système de validation pour vérifier et valider les tickets QR code des participants. La validation se fait en quelques secondes et est beaucoup plus efficace qu'une vérification classique de tickets papier. Votre événement peut commencer sereinement.</p>
            </div>
        </div>

        <div class="step">
            <div class="step-number">4</div>
            <div class="step-content">
                <h4>Recevez vos revenus</h4>
                <p>Vous vous concentrez sur les préparatifs de votre événement, nous nous occupons de la vente des tickets. <strong>Vos revenus sont disponibles à la demande</strong> directement depuis votre dashboard. Plus besoin d'attendre - récupérez vos gains quand vous le souhaitez !</p>
            </div>
        </div>
    </div>

    <!-- Moyens de paiement -->
    <div class="row mb-5">
        <div class="col-12">
            <h2 class="text-center mb-5">Moyens de paiement acceptés</h2>
            
            <div class="row justify-content-center">
                <div class="col-md-4 mb-4">
                    <div class="feature-card text-center">
                        <div class="feature-icon mx-auto">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h5>Mobile Money</h5>
                        <p class="mb-0">Orange Money, MTN Money, Moov Money</p>
                        <small class="text-muted">Paiement instantané</small>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="feature-card text-center">
                        <div class="feature-icon mx-auto">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <h5>Carte bancaire</h5>
                        <p class="mb-0">Visa, Mastercard</p>
                        <small class="text-muted">Paiement sécurisé SSL</small>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="feature-card text-center">
                        <div class="feature-icon mx-auto">
                            <i class="fas fa-gift"></i>
                        </div>
                        <h5>Code coupon</h5>
                        <p class="mb-0">Bons d'achat et promotions</p>
                        <small class="text-muted">Pour vos invités VIP</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ Tarifs -->
    <div class="row mb-5">
        <div class="col-lg-8 mx-auto">
            <h2 class="text-center mb-5">Questions fréquentes</h2>
            
            <div class="accordion" id="pricingFAQ">
                <div class="accordion-item mb-3">
                    <h3 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                            <strong>Comment récupérer mes revenus ?</strong>
                        </button>
                    </h3>
                    <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#pricingFAQ">
                        <div class="accordion-body">
                            <p>Vos revenus sont <strong>disponibles à la demande</strong> depuis votre dashboard promoteur. Plus besoin d'attendre des dates fixes - vous pouvez retirer vos gains quand vous le souhaitez !</p>
                            <p><small class="text-muted">Méthodes de retrait : Mobile Money, virement bancaire</small></p>
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
                            <p><strong>Non, transparence totale !</strong> Seuls 8% de commission + 500 FCFA de frais techniques par billet sont déduits. Aucun frais caché, aucune surprise.</p>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item mb-3">
                    <h3 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                            <strong>Que se passe-t-il si personne n'achète de billets ?</strong>
                        </button>
                    </h3>
                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#pricingFAQ">
                        <div class="accordion-body">
                            <p><strong>Aucun frais !</strong> Si aucun billet n'est vendu, vous ne payez rien. Nos frais ne s'appliquent que sur les ventes effectives.</p>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item mb-3">
                    <h3 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                            <strong>Puis-je modifier mes prix après publication ?</strong>
                        </button>
                    </h3>
                    <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#pricingFAQ">
                        <div class="accordion-body">
                            <p><strong>Oui, totalement !</strong> Vous pouvez ajuster vos prix à tout moment selon l'évolution de vos ventes. La flexibilité est l'un de nos points forts.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Final -->
    <div class="cta-section">
        <h3>Prêt à créer votre premier événement ?</h3>
        <p class="lead mb-4">Rejoignez des centaines d'organisateurs qui nous font confiance en Côte d'Ivoire</p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="{{ route('register') }}" class="btn btn-light btn-lg">
                <i class="fas fa-user-plus me-2"></i>Créer un compte gratuit
            </a>
            <a href="{{ route('pages.contact') }}" class="btn btn-outline-light btn-lg">
                <i class="fas fa-phone me-2"></i>Parler à un conseiller
            </a>
        </div>
        <p class="mt-3 mb-0">
            <small><i class="fas fa-shield-alt me-2"></i>Inscription gratuite, sans engagement, support inclus</small>
        </p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const priceInput = document.getElementById('ticketPrice');
    const participantPriceEl = document.getElementById('participantPrice');
    const organizerEarningsEl = document.getElementById('organizerEarnings');
    
    // Configuration ClicBillet CI
    const COMMISSION_RATE = 8; // 8%
    const TECHNICAL_FEE = 500; // 500 FCFA par billet
    
    function updateCalculations() {
        const ticketPrice = parseInt(priceInput.value) || 0;
        
        if (ticketPrice <= 0) {
            participantPriceEl.textContent = '0 F';
            organizerEarningsEl.textContent = '0 F';
            return;
        }
        
        // Ce que paient les participants (prix + frais techniques)
        const participantPrice = ticketPrice + TECHNICAL_FEE;
        
        // Commission ClicBillet (sur le prix du ticket seulement)
        const commission = Math.round((ticketPrice * COMMISSION_RATE) / 100);
        
        // Ce que reçoit l'organisateur
        const organizerEarnings = ticketPrice - commission;
        
        // Mise à jour de l'affichage
        participantPriceEl.textContent = formatCurrency(participantPrice);
        organizerEarningsEl.textContent = formatCurrency(organizerEarnings);
    }
    
    function formatCurrency(amount) {
        return new Intl.NumberFormat('fr-FR').format(amount) + ' F';
    }
    
    // Écouteur d'événements
    priceInput.addEventListener('input', updateCalculations);
    
    // Calcul initial
    updateCalculations();
    
    // Suggestions de prix au focus
    priceInput.addEventListener('focus', function() {
        if (!this.value) {
            // Suggestions de prix populaires
            const suggestions = [2500, 5000, 7500, 10000, 15000];
            const randomPrice = suggestions[Math.floor(Math.random() * suggestions.length)];
            this.placeholder = `Ex: ${randomPrice} FCFA`;
        }
    });
});
</script>
@endsection