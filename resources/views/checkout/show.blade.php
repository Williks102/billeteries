<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Finaliser ma commande - Billetterie CI</title>
    
    <!-- Bootstrap CSS -->
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- NOUVEAU : Thème Orange & Noir -->
<link href="{{ asset('css/theme.css') }}" rel="stylesheet">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        .checkout-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .step {
            display: flex;
            align-items: center;
            color: #6c757d;
        }
        
        .step.active {
            color: #667eea;
            font-weight: bold;
        }
        
        .step.completed {
            color: #28a745;
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-size: 0.9rem;
        }
        
        .step.active .step-number {
            background: #667eea;
            color: white;
        }
        
        .step.completed .step-number {
            background: #28a745;
            color: white;
        }
        
        .step-separator {
            width: 50px;
            height: 2px;
            background: #e9ecef;
            margin: 0 20px;
        }
        
        .order-summary {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            position: sticky;
            top: 20px;
        }
        
        .order-item {
            border-bottom: 1px solid #e9ecef;
            padding: 15px 0;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .btn-primary-custom {
            background: #FF6B35;
            border: none;
            border-radius: 25px;
            padding: 15px 40px;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .btn-primary-custom:hover {
            background: #E55A2B;
        }
        
        .navbar-custom {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light navbar-custom">
        <div class="container">
            <a class="navbar-brand fw-bold" href="{{ route('home') }}">
                <i class="fas fa-ticket-alt me-2"></i>
                Billetterie CI
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="{{ route('cart.show') }}">
                    <i class="fas fa-arrow-left me-1"></i> Retour au panier
                </a>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <section class="checkout-header">
        <div class="container">
            <div class="text-center">
                <h1 class="mb-3"><i class="fas fa-credit-card me-2"></i>Finaliser ma commande</h1>
                <p class="lead">Vérifiez vos informations et validez votre achat</p>
            </div>
        </div>
    </section>

    <!-- Indicateur d'étapes -->
    <div class="container mt-4">
        <div class="step-indicator">
            <div class="step completed">
                <div class="step-number"><i class="fas fa-check"></i></div>
                <span>Sélection</span>
            </div>
            <div class="step-separator"></div>
            <div class="step completed">
                <div class="step-number"><i class="fas fa-check"></i></div>
                <span>Panier</span>
            </div>
            <div class="step-separator"></div>
            <div class="step active">
                <div class="step-number">3</div>
                <span>Validation</span>
            </div>
            <div class="step-separator"></div>
            <div class="step">
                <div class="step-number">4</div>
                <span>Confirmation</span>
            </div>
        </div>
    </div>

    <!-- Contenu principal -->
    <div class="container mt-4 mb-5">
        @if(session('error'))
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            </div>
        @endif

        <form action="{{ route('checkout.process') }}" method="POST">
            @csrf
            <div class="row">
                <!-- Informations de facturation -->
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-user me-2"></i>Informations de facturation</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nom complet</label>
                                    <input type="text" class="form-control" value="{{ Auth::user()->name }}" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email de facturation *</label>
                                    <input type="email" class="form-control @error('billing_email') is-invalid @enderror" 
                                           name="billing_email" value="{{ old('billing_email', Auth::user()->email) }}" required>
                                    @error('billing_email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Téléphone de facturation *</label>
                                    <input type="tel" class="form-control @error('billing_phone') is-invalid @enderror" 
                                           name="billing_phone" value="{{ old('billing_phone', Auth::user()->phone) }}" required>
                                    @error('billing_phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Méthode de paiement (pour l'instant désactivée) -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Mode de validation</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Mode de test :</strong> Votre commande sera validée automatiquement pour les tests. 
                                Le système de paiement sera ajouté prochainement.
                            </div>
                            
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="manual" value="manual" checked>
                                <label class="form-check-label" for="manual">
                                    <strong>Validation manuelle</strong><br>
                                    <small class="text-muted">Vos billets seront générés immédiatement</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Conditions générales -->
                    <div class="card">
                        <div class="card-body">
                            <div class="form-check mb-3">
                                <input class="form-check-input @error('terms_accepted') is-invalid @enderror" 
                                       type="checkbox" id="terms_accepted" name="terms_accepted" required>
                                <label class="form-check-label" for="terms_accepted">
                                    J'accepte les <a href="#" target="_blank">conditions générales de vente</a> 
                                    et la <a href="#" target="_blank">politique de confidentialité</a> *
                                </label>
                                @error('terms_accepted')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter">
                                <label class="form-check-label" for="newsletter">
                                    Je souhaite recevoir les nouveautés et offres spéciales par email
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Résumé de commande -->
                <div class="col-lg-4">
                    <div class="order-summary">
                        <h5 class="mb-4"><i class="fas fa-receipt me-2"></i>Résumé de la commande</h5>
                        
                        @foreach($cart as $item)
                            <div class="order-item">
                                <h6 class="fw-bold mb-1">{{ $item['event_title'] }}</h6>
                                <p class="text-muted mb-1 small">{{ $item['ticket_name'] }}</p>
                                <p class="text-muted mb-1 small">
                                    <i class="fas fa-calendar me-1"></i>{{ $item['event_date'] }}
                                </p>
                                <p class="text-muted mb-2 small">
                                    <i class="fas fa-map-marker-alt me-1"></i>{{ $item['event_venue'] }}
                                </p>
                                <div class="d-flex justify-content-between">
                                    <span>{{ $item['quantity'] }} × {{ number_format($item['unit_price'], 0, ',', ' ') }} FCFA</span>
                                    <strong>{{ number_format($item['total_price'], 0, ',', ' ') }} FCFA</strong>
                                </div>
                            </div>
                        @endforeach
                        
                        <div class="border-top pt-3 mt-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Sous-total :</span>
                                <span>{{ number_format($cartTotal, 0, ',', ' ') }} FCFA</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Frais de service :</span>
                                <span>{{ number_format($serviceFee, 0, ',', ' ') }} FCFA</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-4">
                                <strong>Total à payer :</strong>
                                <strong class="text-primary">{{ number_format($finalTotal, 0, ',', ' ') }} FCFA</strong>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary-custom w-100">
                            <i class="fas fa-check me-2"></i>
                            Valider ma commande
                        </button>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i>
                                Commande sécurisée
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>