@extends('layouts.app')

@section('title', 'Finaliser votre commande')

@push('styles')
<style>
:root {
    --primary-orange: #FF6B35;
    --primary-dark: #E55A2B;
    --success-green: #28a745;
    --warning-yellow: #ffc107;
    --border-color: #e9ecef;
    --light-gray: #f8f9fa;
    --shadow-light: 0 2px 10px rgba(0,0,0,0.1);
    --shadow-medium: 0 4px 20px rgba(0,0,0,0.15);
    --transition-smooth: all 0.3s ease;
}

/* Container principal */
.checkout-container {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    min-height: calc(100vh - 80px);
    padding: 2rem 0;
}

.checkout-card {
    background: white;
    border-radius: 20px;
    box-shadow: var(--shadow-light);
    overflow: hidden;
    margin-bottom: 2rem;
    border: 1px solid var(--border-color);
}

/* Header avec progression */
.checkout-header {
    background: linear-gradient(135deg, var(--primary-orange), var(--primary-dark));
    color: white;
    padding: 2rem;
    text-align: center;
}

.checkout-header h1 {
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.checkout-header p {
    margin-bottom: 1rem;
    opacity: 0.9;
}

.progress-steps {
    display: flex;
    justify-content: center;
    margin-top: 1rem;
    gap: 2rem;
}

.step {
    display: flex;
    align-items: center;
    color: rgba(255,255,255,0.7);
    font-size: 0.9rem;
}

.step.active {
    color: white;
    font-weight: 600;
}

.step-number {
    background: rgba(255,255,255,0.2);
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.5rem;
    font-weight: bold;
}

.step.active .step-number {
    background: white;
    color: var(--primary-orange);
}

/* Section principale */
.checkout-content {
    padding: 2rem;
}

/* Choix du mode de commande */
.purchase-mode {
    margin-bottom: 2rem;
    text-align: center;
}

.purchase-mode h4 {
    color: #333;
    margin-bottom: 1.5rem;
    font-weight: 600;
}

.mode-options {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 1rem;
}

.mode-option {
    flex: 1;
    max-width: 300px;
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 15px;
    padding: 1.5rem;
    cursor: pointer;
    transition: var(--transition-smooth);
    text-align: center;
    position: relative;
}

.mode-option.selected {
    border-color: var(--primary-orange);
    background: rgba(255, 107, 53, 0.05);
    transform: translateY(-2px);
    box-shadow: var(--shadow-medium);
}

.mode-option:hover {
    border-color: var(--primary-orange);
    transform: translateY(-2px);
    box-shadow: var(--shadow-medium);
}

.mode-icon {
    font-size: 2.5rem;
    margin-bottom: 0.75rem;
    color: var(--primary-orange);
}

.mode-title {
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #333;
    font-size: 1.1rem;
}

.mode-description {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 0.75rem;
}

.mode-benefits {
    font-size: 0.8rem;
    color: var(--success-green);
    font-weight: 500;
}

/* Formulaire */
.form-section {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-section h5 {
    color: #333;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
}

.form-floating {
    margin-bottom: 1rem;
}

.form-control {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 0.75rem 1rem;
    transition: var(--transition-smooth);
    font-size: 0.95rem;
}

.form-control:focus {
    border-color: var(--primary-orange);
    box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
}

.form-control.is-valid {
    border-color: var(--success-green);
    background-color: rgba(40, 167, 69, 0.05);
}

.form-control.is-invalid {
    border-color: #dc3545;
    background-color: rgba(220, 53, 69, 0.05);
}

.form-text {
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

/* Section création de compte */
.account-section {
    border: 2px dashed #dee2e6;
    border-radius: 15px;
    padding: 1.5rem;
    margin-top: 1rem;
    transition: var(--transition-smooth);
}

.account-section.active {
    border-color: var(--primary-orange);
    border-style: solid;
    background: rgba(255, 107, 53, 0.02);
}

.account-toggle {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
    cursor: pointer;
}

.account-toggle input[type="checkbox"] {
    width: 20px;
    height: 20px;
    accent-color: var(--primary-orange);
}

.account-toggle label {
    cursor: pointer;
    font-weight: 500;
    margin: 0;
}

.account-benefits {
    background: #e8f5e8;
    border: 1px solid var(--success-green);
    border-radius: 10px;
    padding: 1rem;
    margin-top: 1rem;
}

.benefit-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.benefit-item:last-child {
    margin-bottom: 0;
}

.benefit-item i {
    color: var(--success-green);
    width: 16px;
}

/* Résumé de commande */
.order-summary {
    background: linear-gradient(135deg, #fff3e0, #ffe0b2);
    border-radius: 15px;
    padding: 1.5rem;
    border: 1px solid #ffcc02;
    position: sticky;
    top: 2rem;
}

.summary-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    color: #333;
}

.summary-header h5 {
    margin: 0;
    font-weight: 600;
}

.cart-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(0,0,0,0.1);
}

.cart-item:last-child {
    border-bottom: none;
}

.item-details {
    flex: 1;
}

.item-name {
    font-weight: 600;
    color: #333;
    font-size: 0.95rem;
    margin-bottom: 0.25rem;
}

.item-info {
    font-size: 0.8rem;
    color: #666;
}

.item-price {
    font-weight: 600;
    color: var(--primary-orange);
    white-space: nowrap;
}

.summary-total {
    border-top: 2px solid rgba(0,0,0,0.1);
    padding-top: 1rem;
    margin-top: 1rem;
}

.total-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.total-final {
    font-size: 1.2rem;
    font-weight: bold;
    color: var(--primary-orange);
    border-top: 1px solid rgba(0,0,0,0.1);
    padding-top: 0.75rem;
    margin-top: 0.75rem;
}

/* Bouton de commande */
.btn-order {
    background: linear-gradient(135deg, var(--primary-orange), var(--primary-dark));
    border: none;
    color: white;
    font-weight: bold;
    font-size: 1.1rem;
    padding: 1rem 2rem;
    border-radius: 15px;
    width: 100%;
    transition: var(--transition-smooth);
    box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
}

.btn-order:hover:not(:disabled) {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(255, 107, 53, 0.4);
    color: white;
}

.btn-order:disabled {
    background: #ccc;
    transform: none;
    box-shadow: none;
    cursor: not-allowed;
}

/* CGV */
.terms-section {
    background: white;
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
}

.form-check {
    margin-bottom: 1rem;
}

.form-check-input {
    width: 1.2rem;
    height: 1.2rem;
    margin-top: 0.1rem;
}

.form-check-input:checked {
    background-color: var(--primary-orange);
    border-color: var(--primary-orange);
}

.form-check-label {
    margin-left: 0.5rem;
    font-size: 0.95rem;
    line-height: 1.4;
}

/* Sécurité et confiance */
.security-badges {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e9ecef;
}

.security-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8rem;
    color: var(--success-green);
    background: rgba(40, 167, 69, 0.1);
    padding: 0.5rem 1rem;
    border-radius: 20px;
    border: 1px solid rgba(40, 167, 69, 0.3);
}

/* Responsive */
@media (max-width: 768px) {
    .checkout-container {
        padding: 1rem;
    }
    
    .checkout-header {
        padding: 1.5rem 1rem;
    }
    
    .progress-steps {
        flex-direction: column;
        gap: 0.5rem;
        margin-top: 1rem;
    }
    
    .mode-options {
        flex-direction: column;
    }
    
    .checkout-content {
        padding: 1.5rem;
    }
    
    .order-summary {
        position: static;
        margin-top: 2rem;
    }
    
    .security-badges {
        flex-direction: column;
        align-items: center;
    }
}

/* États de chargement */
.loading {
    opacity: 0.6;
    pointer-events: none;
}

.btn-order.loading {
    position: relative;
}

.btn-order.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 20px;
    height: 20px;
    border: 2px solid transparent;
    border-top: 2px solid white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: translate(-50%, -50%) rotate(0deg); }
    100% { transform: translate(-50%, -50%) rotate(360deg); }
}
</style>
@endpush

@section('content')
<div class="checkout-container">
    <div class="container">
        <!-- Header avec progression -->
        <div class="checkout-card">
            <div class="checkout-header">
                <h1><i class="fas fa-shopping-cart me-2"></i>Finaliser votre commande</h1>
                <p>Rapide, sécurisé et sans engagement</p>
                
                <div class="progress-steps">
                    <div class="step">
                        <div class="step-number">✓</div>
                        Panier
                    </div>
                    <div class="step active">
                        <div class="step-number">2</div>
                        Informations
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        Confirmation
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Choix du mode -->
                <div class="checkout-card">
                    <div class="checkout-content">
                        <div class="purchase-mode">
                            <h4>Comment souhaitez-vous procéder ?</h4>
                            
                            <div class="mode-options">
                                <div class="mode-option selected" data-mode="guest" id="guestMode">
                                    <div class="mode-icon">⚡</div>
                                    <div class="mode-title">Commande express</div>
                                    <div class="mode-description">Rapide et simple, sans créer de compte</div>
                                    <div class="mode-benefits">✓ En 2 minutes seulement</div>
                                </div>
                                
                                <div class="mode-option" data-mode="account" id="accountMode">
                                    <div class="mode-icon">👤</div>
                                    <div class="mode-title">Avec création de compte</div>
                                    <div class="mode-description">Gérez vos commandes et billets facilement</div>
                                    <div class="mode-benefits">✓ Historique • ✓ Réimpression • ✓ Support</div>
                                </div>
                            </div>
                        </div>

                        <!-- Formulaire -->
                        <form id="checkoutForm" method="POST" action="{{ route('checkout.guest.process') }}">
                            @csrf
                            
                            <!-- Informations personnelles -->
                            <div class="form-section">
                                <h5><i class="fas fa-user me-2"></i>Vos informations</h5>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control @error('first_name') is-invalid @enderror" 
                                                   id="first_name" name="first_name" 
                                                   placeholder="Prénom" value="{{ old('first_name') }}" required>
                                            <label for="first_name">Prénom *</label>
                                            @error('first_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control @error('last_name') is-invalid @enderror" 
                                                   id="last_name" name="last_name" 
                                                   placeholder="Nom" value="{{ old('last_name') }}" required>
                                            <label for="last_name">Nom *</label>
                                            @error('last_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-floating">
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                           id="email" name="email" 
                                           placeholder="Email" value="{{ old('email') }}" required>
                                    <label for="email">Adresse email *</label>
                                    <div class="form-text">Vos billets seront envoyés à cette adresse</div>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="form-floating">
                                    <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                                           id="phone" name="phone" 
                                           placeholder="Téléphone" value="{{ old('phone') }}" required>
                                    <label for="phone">Numéro de téléphone *</label>
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Section création de compte (conditionnelle) -->
                            <div class="account-section" id="accountSection" style="display: none;">
                                <div class="account-toggle">
                                    <input type="checkbox" id="create_account" name="create_account" value="1" {{ old('create_account') ? 'checked' : '' }}>
                                    <label for="create_account">
                                        <strong>Créer un compte pour gérer mes billets</strong>
                                    </label>
                                </div>

                                <div id="passwordFields" style="display: none;">
                                    <div class="form-floating">
                                        <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                               id="password" name="password" placeholder="Mot de passe">
                                        <label for="password">Mot de passe (min. 6 caractères)</label>
                                        @error('password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="form-floating">
                                        <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" 
                                               id="password_confirmation" name="password_confirmation" 
                                               placeholder="Confirmer le mot de passe">
                                        <label for="password_confirmation">Confirmer le mot de passe</label>
                                    </div>

                                    <div class="account-benefits">
                                        <div class="benefit-item">
                                            <i class="fas fa-check-circle"></i>
                                            Accès à l'historique de toutes vos commandes
                                        </div>
                                        <div class="benefit-item">
                                            <i class="fas fa-check-circle"></i>
                                            Réimpression de vos billets à tout moment
                                        </div>
                                        <div class="benefit-item">
                                            <i class="fas fa-check-circle"></i>
                                            Support client prioritaire
                                        </div>
                                        <div class="benefit-item">
                                            <i class="fas fa-check-circle"></i>
                                            Notifications pour vos événements favoris
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- CGV et bouton -->
                            <div class="terms-section">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input @error('terms_accepted') is-invalid @enderror" 
                                           id="terms_accepted" name="terms_accepted" required {{ old('terms_accepted') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="terms_accepted">
                                        J'accepte les <a href="{{ route('pages.terms') }}" target="_blank" class="text-decoration-none fw-bold">conditions générales de vente</a> 
                                        et la <a href="{{ route('pages.privacy') }}" target="_blank" class="text-decoration-none fw-bold">politique de confidentialité</a> *
                                    </label>
                                    @error('terms_accepted')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <button type="submit" class="btn-order mt-3" id="submitBtn">
                                    <i class="fas fa-lock me-2"></i>
                                    <span class="btn-text">Confirmer ma commande</span>
                                </button>

                                <div class="security-badges">
                                    <div class="security-badge">
                                        <i class="fas fa-shield-alt"></i>
                                        Paiement sécurisé
                                    </div>
                                    <div class="security-badge">
                                        <i class="fas fa-clock"></i>
                                        Billets instantanés
                                    </div>
                                    <div class="security-badge">
                                        <i class="fas fa-headset"></i>
                                        Support 24/7
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Résumé de commande -->
            <div class="col-lg-4">
                <div class="order-summary">
                    <div class="summary-header">
                        <i class="fas fa-receipt"></i>
                        <h5>Résumé de votre commande</h5>
                    </div>

                    @foreach($cart as $item)
                        <div class="cart-item">
                            <div class="item-details">
                                <div class="item-name">{{ $item['ticket_name'] }}</div>
                                <div class="item-info">
                                    {{ $item['event_title'] }} • Qté: {{ $item['quantity'] }}
                                </div>
                                <div class="item-info">
                                    📅 {{ $item['event_date'] ?? 'Date à confirmer' }}
                                </div>
                            </div>
                            <div class="item-price">
                                {{ number_format($item['total_price']) }} FCFA
                            </div>
                        </div>
                    @endforeach

                    <div class="summary-total">
                        <div class="total-row">
                            <span>Sous-total</span>
                            <span>{{ number_format($cartTotal) }} FCFA</span>
                        </div>
                        <div class="total-row">
                            <span>Frais de service</span>
                            <span>{{ number_format($serviceFee) }} FCFA</span>
                        </div>
                        <div class="total-row total-final">
                            <span><strong>Total à payer</strong></span>
                            <span><strong>{{ number_format($finalTotal) }} FCFA</strong></span>
                        </div>
                    </div>

                    <!-- Retour au panier -->
                    <div class="text-center mt-3">
                        <a href="{{ route('cart.show') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-2"></i>Modifier mon panier
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Gestion des modes de commande
    const guestMode = document.getElementById('guestMode');
    const accountMode = document.getElementById('accountMode');
    const accountSection = document.getElementById('accountSection');
    const createAccountCheckbox = document.getElementById('create_account');
    const passwordFields = document.getElementById('passwordFields');
    const submitBtn = document.getElementById('submitBtn');
    
    // Mode invité (par défaut)
    guestMode.addEventListener('click', function() {
        selectMode('guest');
    });
    
    // Mode avec compte
    accountMode.addEventListener('click', function() {
        selectMode('account');
    });
    
    function selectMode(mode) {
        // Reset des sélections
        document.querySelectorAll('.mode-option').forEach(option => {
            option.classList.remove('selected');
        });
        
        if (mode === 'guest') {
            guestMode.classList.add('selected');
            accountSection.style.display = 'none';
            createAccountCheckbox.checked = false;
            passwordFields.style.display = 'none';
            updateButtonText('guest');
        } else {
            accountMode.classList.add('selected');
            accountSection.style.display = 'block';
            accountSection.classList.add('active');
            createAccountCheckbox.checked = true;
            passwordFields.style.display = 'block';
            updateButtonText('account');
        }
    }
    
    // Gestion de la case à cocher création de compte
    createAccountCheckbox.addEventListener('change', function() {
        if (this.checked) {
            passwordFields.style.display = 'block';
            accountSection.classList.add('active');
            // Rendre les champs mot de passe obligatoires
            document.getElementById('password').required = true;
            document.getElementById('password_confirmation').required = true;
            updateButtonText('account');
        } else {
            passwordFields.style.display = 'none';
            accountSection.classList.remove('active');
            // Rendre les champs mot de passe optionnels
            document.getElementById('password').required = false;
            document.getElementById('password_confirmation').required = false;
            updateButtonText('guest');
        }
    });
    
    // Mettre à jour le texte du bouton
    function updateButtonText(mode) {
        const btnText = submitBtn.querySelector('.btn-text');
        if (mode === 'account') {
            btnText.textContent = 'Créer mon compte et commander';
        } else {
            btnText.textContent = 'Confirmer ma commande';
        }
    }
    
    // Validation en temps réel de l'email
    const emailInput = document.getElementById('email');
    let emailTimeout;
    
    /*
    emailInput.addEventListener('input', function() {
        clearTimeout(emailTimeout);
        const email = this.value;
        
        if (email && email.includes('@')) {
            emailTimeout = setTimeout(() => {
                checkEmailAvailability(email);
            }, 1000); // Attendre 1s après que l'utilisateur ait arrêté de taper
        }
    });
    */
    
    async function checkEmailAvailability(email) {
    try {
        const response = await fetch('/api/check-email', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ email: email })
        });
        
        // Vérifier si la réponse est OK
        if (!response.ok) {
            console.error('Erreur API check-email:', response.status, response.statusText);
            return; // Sortir silencieusement en cas d'erreur serveur
        }
        
        const data = await response.json();
        const emailInput = document.getElementById('email');
        
        // Vérifier si la réponse contient les données attendues
        if (!data.hasOwnProperty('available') || !data.hasOwnProperty('exists')) {
            console.error('Réponse API invalide:', data);
            return;
        }
        
        if (!data.available && createAccountCheckbox.checked) {
            emailInput.classList.add('is-invalid');
            emailInput.classList.remove('is-valid');
            
            // Afficher message d'erreur
            let feedback = emailInput.parentNode.querySelector('.invalid-feedback');
            if (!feedback) {
                feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                emailInput.parentNode.appendChild(feedback);
            }
            feedback.textContent = 'Un compte existe déjà avec cet email. Décochez "Créer un compte" ou connectez-vous.';
            
            // Proposer de décocher la création de compte (optionnel)
            setTimeout(() => {
                if (confirm('Un compte existe déjà avec cet email. Voulez-vous continuer sans créer de nouveau compte ?')) {
                    createAccountCheckbox.checked = false;
                    createAccountCheckbox.dispatchEvent(new Event('change'));
                    emailInput.classList.remove('is-invalid');
                    emailInput.classList.add('is-valid');
                    if (feedback) feedback.remove();
                }
            }, 1000);
            
        } else {
            emailInput.classList.remove('is-invalid');
            emailInput.classList.add('is-valid');
            
            // Supprimer message d'erreur s'il existe
            const feedback = emailInput.parentNode.querySelector('.invalid-feedback');
            if (feedback) feedback.remove();
        }
        
    } catch (error) {
        console.error('Erreur vérification email:', error);
        // En cas d'erreur, ne pas bloquer l'utilisateur, juste logger
        const emailInput = document.getElementById('email');
        emailInput.classList.remove('is-invalid');
        
        // Supprimer les messages d'erreur existants
        const feedback = emailInput.parentNode.querySelector('.invalid-feedback');
        if (feedback) feedback.remove();
    }
}
    
    // Gestion de la soumission du formulaire
    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        const btn = document.getElementById('submitBtn');
        
        // Validation des champs obligatoires
        const requiredFields = ['first_name', 'last_name', 'email', 'phone'];
        let isValid = true;
        
        requiredFields.forEach(fieldName => {
            const field = document.getElementById(fieldName);
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
            }
        });
        
        // Validation mot de passe si création de compte
        if (createAccountCheckbox.checked) {
            const password = document.getElementById('password');
            const passwordConfirm = document.getElementById('password_confirmation');
            
            if (!password.value || password.value.length < 6) {
                password.classList.add('is-invalid');
                isValid = false;
            }
            
            if (password.value !== passwordConfirm.value) {
                passwordConfirm.classList.add('is-invalid');
                isValid = false;
            }
        }
        
        // Validation CGV
        const termsAccepted = document.getElementById('terms_accepted');
        if (!termsAccepted.checked) {
            termsAccepted.classList.add('is-invalid');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            scrollToFirstError();
            return;
        }
        
        // Animation de chargement
        btn.disabled = true;
        btn.classList.add('loading');
        btn.querySelector('.btn-text').textContent = 'Traitement en cours...';
    });
    
    function scrollToFirstError() {
        const firstError = document.querySelector('.is-invalid');
        if (firstError) {
            firstError.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
            firstError.focus();
        }
    }
    
    // Validation temps réel des mots de passe
    const passwordInput = document.getElementById('password');
    const passwordConfirmInput = document.getElementById('password_confirmation');
    
    if (passwordInput && passwordConfirmInput) {
        function validatePasswords() {
            const password = passwordInput.value;
            const confirm = passwordConfirmInput.value;
            
            // Validation longueur
            if (password.length > 0 && password.length < 6) {
                passwordInput.classList.add('is-invalid');
                passwordInput.classList.remove('is-valid');
            } else if (password.length >= 6) {
                passwordInput.classList.remove('is-invalid');
                passwordInput.classList.add('is-valid');
            }
            
            // Validation concordance
            if (confirm.length > 0) {
                if (password === confirm && password.length >= 6) {
                    passwordConfirmInput.classList.remove('is-invalid');
                    passwordConfirmInput.classList.add('is-valid');
                } else {
                    passwordConfirmInput.classList.add('is-invalid');
                    passwordConfirmInput.classList.remove('is-valid');
                }
            }
        }
        
        passwordInput.addEventListener('input', validatePasswords);
        passwordConfirmInput.addEventListener('input', validatePasswords);
    }
    
    // Auto-focus sur le premier champ
    document.getElementById('first_name').focus();
});
</script>
@endpush