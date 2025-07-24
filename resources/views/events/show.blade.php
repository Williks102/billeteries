<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $event->title }} - Billetterie CI</title>
    
    <!-- Bootstrap CSS -->
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- NOUVEAU : Thème Orange & Noir -->
<link href="{{ asset('css/theme.css') }}" rel="stylesheet">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        .event-hero {
            background: linear-gradient(135deg, #0d0e14ff 0%, #0a090aff 100%);
            color: white;
            padding: 100px 0 60px;
        }
        
        .ticket-card {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            transition: all 0.3s ease;
        }
        
        .ticket-card:hover {
            border-color: #667eea;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.1);
        }
        
        .ticket-card.popular {
            border-color: #FF6B35;
            position: relative;
        }
        
        .popular-badge {
            position: absolute;
            top: -10px;
            left: 20px;
            background: #FF6B35;
            color: white;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
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
        
        .info-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #ee5c07ff;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        
        .similar-event-card {
            transition: transform 0.3s ease;
        }
        
        .similar-event-card:hover {
            transform: translateY(-5px);
        }
        
        .navbar-custom {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
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
                <a class="nav-link position-relative me-3" href="{{ route('cart.show') }}">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-badge badge bg-danger position-absolute top-0 start-100 translate-middle" 
                          style="display: none; font-size: 0.7rem;">0</span>
                </a>
                <a class="nav-link" href="{{ route('home') }}">
                    <i class="fas fa-arrow-left me-1"></i> Retour aux événements
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="event-hero">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Breadcrumb -->
                    <nav aria-label="breadcrumb" class="mb-4">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('home') }}" class="text-white-50">Accueil</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('categories.show', $event->category) }}" class="text-white-50">
                                    {{ $event->category->name }}
                                </a>
                            </li>
                            <li class="breadcrumb-item active text-white">{{ $event->title }}</li>
                        </ol>
                    </nav>
                    
                    <!-- Catégorie -->
                    <span class="badge bg-light text-dark mb-3 fs-6">
                        <i class="{{ $event->category->icon }} me-1"></i>
                        {{ $event->category->name }}
                    </span>
                    
                    <!-- Titre -->
                    <h1 class="display-4 fw-bold mb-4">{{ $event->title }}</h1>
                    
                    <!-- Infos principales -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <div class="info-icon">
                                    <i class="fas fa-calendar"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Date & Heure</h6>
                                    <p class="mb-0">{{ $event->formatted_event_date }} à {{ $event->formatted_event_time }}</p>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center mb-3">
                                <div class="info-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Lieu</h6>
                                    <p class="mb-0">{{ $event->venue }}</p>
                                    <small class="text-white-50">{{ $event->address }}</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <div class="info-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Organisateur</h6>
                                    <p class="mb-0">{{ $event->promoteur->name }}</p>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center mb-3">
                                <div class="info-icon">
                                    <i class="fas fa-ticket-alt"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Billets disponibles</h6>
                                    <p class="mb-0">{{ $event->availableTicketsCount() }} / {{ $event->totalTicketsAvailable() }}</p>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-success" style="width: {{ 100 - $event->getProgressPercentage() }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Prix à partir de -->
                    <div class="alert alert-light d-inline-block">
                        <i class="fas fa-tag me-2"></i>
                        <strong>À partir de {{ number_format($event->getLowestPrice(), 0, ',', ' ') }} FCFA</strong>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Description et Types de billets -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <!-- Description -->
                <div class="col-lg-8">
                    <div class="mb-5">
                        <h3 class="mb-4"><i class="fas fa-info-circle me-2"></i>À propos de cet événement</h3>
                        <div class="bg-light p-4 rounded">
                            <p class="lead">{{ $event->description }}</p>
                            
                            @if($event->terms_conditions)
                                <hr>
                                <h6><i class="fas fa-file-contract me-2"></i>Conditions particulières</h6>
                                <p class="small text-muted">{{ $event->terms_conditions }}</p>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Types de billets -->
                    <div class="mb-5">
                        <h3 class="mb-4"><i class="fas fa-tickets me-2"></i>Choisir vos billets</h3>
                        
                        @if($event->ticketTypes->count() > 0 && $event->isOnSale())
                            <div class="row">
                                @foreach($event->ticketTypes as $index => $ticketType)
                                    <div class="col-lg-6 mb-4">
                                        <div class="ticket-card p-4 h-100 {{ $index == 1 ? 'popular' : '' }}">
                                            @if($index == 1)
                                                <div class="popular-badge">Populaire</div>
                                            @endif
                                            
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div>
                                                    <h5 class="fw-bold mb-1">{{ $ticketType->name }}</h5>
                                                    <p class="text-muted small mb-0">{{ $ticketType->description }}</p>
                                                </div>
                                                <div class="text-end">
                                                    <h4 class="fw-bold text-primary mb-0">
                                                        {{ number_format($ticketType->price, 0, ',', ' ') }} FCFA
                                                    </h4>
                                                </div>
                                            </div>
                                            
                                            <!-- Infos stock -->
                                            <div class="mb-3">
                                                @if($ticketType->remainingTickets() > 0)
                                                    <small class="text-success">
                                                        <i class="fas fa-check-circle me-1"></i>
                                                        {{ $ticketType->remainingTickets() }} places disponibles
                                                    </small>
                                                @else
                                                    <small class="text-danger">
                                                        <i class="fas fa-times-circle me-1"></i>
                                                        Complet
                                                    </small>
                                                @endif
                                                
                                                @if($ticketType->max_per_order < 10)
                                                    <small class="text-muted d-block">
                                                        Limité à {{ $ticketType->max_per_order }} billet(s) par commande
                                                    </small>
                                                @endif
                                            </div>
                                            
                                            <!-- Bouton réservation -->
                                            @if($ticketType->isAvailable())
                                                <button class="btn btn-primary-custom w-100" 
                                                        onclick="selectTicket({{ $ticketType->id }}, '{{ $ticketType->name }}', {{ $ticketType->price }}, {{ $ticketType->max_per_order }})">
                                                    <i class="fas fa-cart-plus me-2"></i>
                                                    Sélectionner
                                                </button>
                                            @else
                                                <button class="btn btn-secondary w-100" disabled>
                                                    @if($ticketType->isSoldOut())
                                                        Complet
                                                    @elseif(!$ticketType->isSaleStarted())
                                                        Vente pas encore ouverte
                                                    @elseif($ticketType->isSaleEnded())
                                                        Vente terminée
                                                    @else
                                                        Non disponible
                                                    @endif
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @elseif(!$event->isOnSale())
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Les billets ne sont pas encore en vente pour cet événement.
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Aucun type de billet n'est actuellement disponible.
                            </div>
                        @endif
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Partage -->
                    <div class="card mb-4">
                        <div class="card-body text-center">
                            <h6 class="card-title"><i class="fas fa-share-alt me-2"></i>Partager</h6>
                            <div class="d-flex justify-content-center gap-2">
                                <a href="#" class="btn btn-primary btn-sm">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="#" class="btn btn-info btn-sm">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="#" class="btn btn-success btn-sm">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contact organisateur -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h6 class="card-title"><i class="fas fa-user-tie me-2"></i>Organisateur</h6>
                            <p class="fw-bold mb-1">{{ $event->promoteur->name }}</p>
                            <p class="text-muted small mb-2">{{ $event->promoteur->phone }}</p>
                            <button class="btn btn-outline-primary btn-sm w-100">
                                <i class="fas fa-envelope me-1"></i>
                                Contacter
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Événements similaires -->
    @if($similarEvents->count() > 0)
        <section class="py-5 bg-light">
            <div class="container">
                <h3 class="text-center mb-5">Événements similaires</h3>
                <div class="row">
                    @foreach($similarEvents as $similar)
                        <div class="col-lg-4 mb-4">
                            <div class="card similar-event-card h-100">
                                <div class="card-body">
                                    <h6 class="card-title">{{ $similar->title }}</h6>
                                    <p class="card-text small text-muted">
                                        <i class="fas fa-calendar me-1"></i>{{ $similar->formatted_event_date }}
                                        <br>
                                        <i class="fas fa-map-marker-alt me-1"></i>{{ $similar->venue }}
                                    </p>
                                    <p class="fw-bold text-primary">
                                        À partir de {{ number_format($similar->getLowestPrice(), 0, ',', ' ') }} FCFA
                                    </p>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <a href="{{ route('events.show', $similar) }}" class="btn btn-outline-primary btn-sm w-100">
                                        Voir détails
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- Modal sélection de billets -->
    <div class="modal fade" id="ticketModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sélectionner vos billets</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong id="selectedTicketName"></strong></p>
                    <p>Prix unitaire : <span id="selectedTicketPrice"></span> FCFA</p>
                    
                    <div class="mb-3">
                        <label class="form-label">Nombre de billets</label>
                        <select id="ticketQuantity" class="form-select">
                            <!-- Options ajoutées dynamiquement -->
                        </select>
                    </div>
                    
                    <div class="alert alert-info">
                        <strong>Total : <span id="totalPrice">0</span> FCFA</strong>
                    </div>
                    
                    @guest
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            Vous devez être connecté pour acheter des billets.
                        </div>
                    @endguest
                </div>
                <div class="modal-footer">
                    @auth
                        <button type="button" class="btn btn-primary-custom" onclick="addToCart()">
                            <i class="fas fa-shopping-cart me-1"></i>
                            Ajouter au panier
                        </button>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary-custom">
                            <i class="fas fa-sign-in-alt me-1"></i>
                            Se connecter pour acheter
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let selectedTicketId = null;
        let selectedTicketPrice = 0;
        
        // Charger le nombre d'articles dans le panier au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            fetch('/cart/data')
                .then(response => response.json())
                .then(data => {
                    updateCartBadge(data.cart_count);
                })
                .catch(error => console.error('Erreur lors du chargement du panier:', error));
        });
        
        function selectTicket(ticketId, ticketName, price, maxQuantity) {
            selectedTicketId = ticketId;
            document.getElementById('selectedTicketName').textContent = ticketName;
            document.getElementById('selectedTicketPrice').textContent = price.toLocaleString();
            selectedTicketPrice = price;
            
            // Remplir les options de quantité
            const quantitySelect = document.getElementById('ticketQuantity');
            quantitySelect.innerHTML = '';
            
            for (let i = 1; i <= maxQuantity; i++) {
                const option = document.createElement('option');
                option.value = i;
                option.textContent = i + (i === 1 ? ' billet' : ' billets');
                quantitySelect.appendChild(option);
            }
            
            updateTotal();
            
            // Ouvrir le modal
            new bootstrap.Modal(document.getElementById('ticketModal')).show();
        }
        
        function updateTotal() {
            const quantity = parseInt(document.getElementById('ticketQuantity').value) || 1;
            const total = selectedTicketPrice * quantity;
            document.getElementById('totalPrice').textContent = total.toLocaleString();
        }
        
        function addToCart() {
            if (!selectedTicketId) {
                alert('Veuillez sélectionner un type de billet');
                return;
            }
            
            const quantity = parseInt(document.getElementById('ticketQuantity').value);
            
            // Afficher un indicateur de chargement
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Ajout en cours...';
            button.disabled = true;
            
            fetch('{{ route("cart.add") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    ticket_type_id: selectedTicketId,
                    quantity: quantity
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Succès - fermer le modal et afficher un message
                    bootstrap.Modal.getInstance(document.getElementById('ticketModal')).hide();
                    
                    // Afficher une notification de succès
                    showNotification('Billets ajoutés au panier avec succès !', 'success');
                    
                    // Mettre à jour le compteur du panier
                    updateCartBadge(data.cart_count);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Une erreur est survenue. Veuillez réessayer.', 'error');
            })
            .finally(() => {
                // Restaurer le bouton
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }
        
        function showNotification(message, type) {
            // Créer une notification Bootstrap
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 350px;';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            // Supprimer automatiquement après 5 secondes
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }
        
        function updateCartBadge(count) {
            // Mettre à jour le badge du panier dans la navigation
            const cartBadge = document.querySelector('.cart-badge');
            if (cartBadge) {
                cartBadge.textContent = count;
                cartBadge.style.display = count > 0 ? 'inline' : 'none';
            }
        }
        
        // Mettre à jour le total quand la quantité change
        document.getElementById('ticketQuantity').addEventListener('change', updateTotal);
    </script>
</body>
</html>