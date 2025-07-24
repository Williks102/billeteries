<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Mon Panier - Billetterie CI</title>
    
    <!-- Bootstrap CSS -->
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- NOUVEAU : Thème Orange & Noir -->
<link href="{{ asset('css/theme.css') }}" rel="stylesheet">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        .cart-item {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            transition: box-shadow 0.3s ease;
        }
        
        .cart-item:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .btn-primary-custom {
            background: #FF6B35;
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
        }
        
        .btn-primary-custom:hover {
            background: #E55A2B;
        }
        
        .navbar-custom {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
        }
        
        .cart-summary {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
            position: sticky;
            top: 20px;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top navbar-custom">
        <div class="container">
            <a class="navbar-brand fw-bold" href="{{ route('home') }}">
                <i class="fas fa-ticket-alt me-2"></i>
                Billetterie CI
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="{{ route('home') }}">
                    <i class="fas fa-arrow-left me-1"></i> Continuer les achats
                </a>
            </div>
        </div>
    </nav>

    <!-- Contenu principal -->
    <div class="container mt-5 pt-5">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4"><i class="fas fa-shopping-cart me-2"></i>Mon Panier</h1>
            </div>
        </div>

        @if(count($cart) > 0)
            <div class="row">
                <!-- Articles du panier -->
                <div class="col-lg-8">
                    @foreach($cart as $cartKey => $item)
                        <div class="cart-item" data-cart-key="{{ $cartKey }}">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="fw-bold">{{ $item['event_title'] }}</h5>
                                    <p class="text-muted mb-1">
                                        <i class="fas fa-ticket-alt me-1"></i>
                                        <strong>{{ $item['ticket_name'] }}</strong>
                                    </p>
                                    <p class="text-muted mb-1">
                                        <i class="fas fa-calendar me-1"></i>
                                        {{ $item['event_date'] }}
                                    </p>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        {{ $item['event_venue'] }}
                                    </p>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <!-- Quantité -->
                                        <div class="input-group" style="width: 120px;">
                                            <button class="btn btn-outline-secondary btn-sm" type="button" 
                                                    onclick="updateQuantity('{{ $cartKey }}', {{ $item['quantity'] - 1 }})">-</button>
                                            <input type="number" class="form-control form-control-sm text-center" 
                                                   value="{{ $item['quantity'] }}" 
                                                   min="1" max="{{ $item['max_per_order'] }}"
                                                   onchange="updateQuantity('{{ $cartKey }}', this.value)">
                                            <button class="btn btn-outline-secondary btn-sm" type="button"
                                                    onclick="updateQuantity('{{ $cartKey }}', {{ $item['quantity'] + 1 }})">+</button>
                                        </div>
                                        
                                        <!-- Prix -->
                                        <div class="text-end">
                                            <p class="mb-0 fw-bold">{{ number_format($item['total_price'], 0, ',', ' ') }} FCFA</p>
                                            <small class="text-muted">{{ number_format($item['unit_price'], 0, ',', ' ') }} FCFA/billet</small>
                                        </div>
                                        
                                        <!-- Supprimer -->
                                        <button class="btn btn-outline-danger btn-sm ms-2" 
                                                onclick="removeFromCart('{{ $cartKey }}')"
                                                title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <!-- Résumé du panier -->
                <div class="col-lg-4">
                    <div class="cart-summary">
                        <h4 class="mb-4">Résumé de la commande</h4>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Nombre de billets :</span>
                            <span id="cart-count">{{ $cartCount }}</span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Sous-total :</span>
                            <span id="cart-subtotal">{{ number_format($cartTotal, 0, ',', ' ') }} FCFA</span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Frais de service :</span>
                            <span>500 FCFA</span>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-4">
                            <strong>Total :</strong>
                            <strong id="cart-total">{{ number_format($cartTotal + 500, 0, ',', ' ') }} FCFA</strong>
                        </div>
                        
                        @auth
                            <a href="{{ route('checkout.show') }}" class="btn btn-primary-custom w-100 mb-3">
                                <i class="fas fa-credit-card me-2"></i>
                                Procéder au paiement
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-primary-custom w-100 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Se connecter pour payer
                            </a>
                        @endauth
                        
                        <button class="btn btn-outline-danger w-100" onclick="clearCart()">
                            <i class="fas fa-trash me-2"></i>
                            Vider le panier
                        </button>
                    </div>
                </div>
            </div>
        @else
            <!-- Panier vide -->
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-5x text-muted mb-4"></i>
                <h3 class="text-muted">Votre panier est vide</h3>
                <p class="text-muted mb-4">Découvrez nos événements et ajoutez des billets à votre panier.</p>
                <a href="{{ route('home') }}" class="btn btn-primary-custom">
                    <i class="fas fa-ticket-alt me-2"></i>
                    Voir les événements
                </a>
            </div>
        @endif
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Configuration CSRF pour les requêtes AJAX
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        function updateQuantity(cartKey, quantity) {
            if (quantity < 1) return;
            
            fetch('{{ route("cart.update") }}', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    cart_key: cartKey,
                    quantity: parseInt(quantity)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Recharger la page pour voir les changements
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue');
            });
        }

        function removeFromCart(cartKey) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer ce billet du panier ?')) {
                return;
            }
            
            fetch('{{ route("cart.remove") }}', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    cart_key: cartKey
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue');
            });
        }

        function clearCart() {
            if (!confirm('Êtes-vous sûr de vouloir vider votre panier ?')) {
                return;
            }
            
            fetch('{{ route("cart.clear") }}', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue');
            });
        }
    </script>
</body>
</html>