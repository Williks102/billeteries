@extends('layouts.promoteur')

@push('styles')
<style>
    .step-indicator {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .step {
        display: flex;
        align-items: center;
        position: relative;
    }

    .step-number {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: white;
        z-index: 2;
    }

    .step-number.completed {
        background: 
#28a745;
    }

    .step-number.active {
        background: 
#FF6B35;
    }

    .step-number.pending {
        background: 
#6c757d;
    }

    .step-line {
        height: 2px;
        flex: 1;
        margin: 0 1rem;
    }

    .step-line.completed {
        background: 
#28a745;
    }

    .step-line.pending {
        background: 
#dee2e6;
    }

    .card-custom {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-left: 4px solid 
#FF6B35;
        margin-bottom: 2rem;
    }

    .preset-card {
        border: 2px solid 
#e9ecef;
        border-radius: 10px;
        padding: 1.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
    }

    .preset-card:hover {
        border-color: 
#FF6B35;
        box-shadow: 0 4px 8px rgba(255, 107, 53, 0.2);
        transform: translateY(-2px);
    }

    .preset-card.selected {
        border-color: 
#28a745;
        background-color: 
#f8f9fa;
    }

    .ticket-form {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-left: 4px solid 
#FF6B35;
        padding: 2rem;
        margin-bottom: 1.5rem;
        position: relative;
    }

    .btn-orange {
        background: 
#FF6B35;
        border-color: 
#FF6B35;
        color: white;
    }

    .btn-orange:hover {
        background: 
#E55A2B;
        border-color: 
#E55A2B;
        color: white;
    }

    .revenue-card {
        background: linear-gradient(135deg, 
#f8f9fa, 
#e9ecef);
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Configurer les types de billets</h1>
            <p class="text-muted">Définissez les différents types de billets pour "{{ $event->title }}"</p>
        </div>
        <a href="{{ route('promoteur.events.show', $event) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Retour à l'événement
        </a>
    </div>

    <!-- Indicateur d'étapes -->
    <div class="step-indicator">
        <div class="step">
            <div class="step-number completed">
                <i class="fas fa-check"></i>
            </div>
            <span class="ms-2 fw-medium text-success">Informations de base</span>
            <div class="step-line completed"></div>

            <div class="step-number active">2</div>
            <span class="ms-2 fw-medium" style="color: 
#FF6B35;">Types de billets</span>
            <div class="step-line pending"></div>

            <div class="step-number pending">3</div>
            <span class="ms-2 text-muted">Publication</span>
        </div>
    </div>

    <!-- Récapitulatif de l'événement -->
    <div class="card-custom">
        <div class="card-body">
            <h5 class="card-title mb-3">
                <i class="fas fa-info-circle me-2" style="color: 
#FF6B35;"></i>
                Récapitulatif de l'événement
            </h5>
            <div class="row">
                <div class="col-md-4">
                    <strong>Événement:</strong> {{ $event->title }}
                </div>
                <div class="col-md-4">
                    <strong>Date:</strong> {{ $event->event_date ? $event->event_date->format('d/m/Y') : 'Non définie' }}
                </div>
                <div class="col-md-4">
                    <strong>Lieu:</strong> {{ $event->venue }}
                </div>
            </div>
        </div>
    </div>

    <!-- Modèles prédéfinis -->
    <div class="card-custom">
        <div class="card-body">
            <h5 class="card-title mb-3">
                <i class="fas fa-magic me-2" style="color: 
#FF6B35;"></i>
                Modèles prédéfinis
            </h5>
            <p class="text-muted mb-4">Cliquez sur un modèle pour l'ajouter rapidement</p>

            <div class="row">
                <!-- Standard -->
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="preset-card" data-preset='{"name":"Standard","description":"Billet d\'accès standard","price":"5000","quantity":"100","max_per_order":"5"}'>
                        <div class="text-primary mb-2">
                            <i class="fas fa-user fa-2x"></i>
                        </div>
                        <h6 class="fw-bold">Standard</h6>
                        <p class="small text-muted">Accès général</p>
                        <div class="fw-bold text-primary">5,000 FCFA</div>
                    </div>
                </div>

                <!-- VIP -->
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="preset-card" data-preset='{"name":"VIP","description":"Accès privilégié avec avantages","price":"10000","quantity":"50","max_per_order":"3"}'>
                        <div class="text-warning mb-2">
                            <i class="fas fa-crown fa-2x"></i>
                        </div>
                        <h6 class="fw-bold">VIP</h6>
                        <p class="small text-muted">Accès privilégié</p>
                        <div class="fw-bold text-warning">10,000 FCFA</div>
                    </div>
                </div>

                <!-- Early Bird -->
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="preset-card" data-preset='{"name":"Early Bird","description":"Tarif préférentiel (vente anticipée)","price":"3500","quantity":"75","max_per_order":"5"}'>
                        <div class="text-success mb-2">
                            <i class="fas fa-bolt fa-2x"></i>
                        </div>
                        <h6 class="fw-bold">Early Bird</h6>
                        <p class="small text-muted">Vente anticipée</p>
                        <div class="fw-bold text-success">3,500 FCFA</div>
                    </div>
                </div>

                <!-- Groupe -->
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="preset-card" data-preset='{"name":"Groupe","description":"Tarif de groupe (minimum 5 personnes)","price":"4000","quantity":"200","max_per_order":"10"}'>
                        <div class="text-info mb-2">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                        <h6 class="fw-bold">Groupe</h6>
                        <p class="small text-muted">Tarif de groupe</p>
                        <div class="fw-bold text-info">4,000 FCFA</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulaire principal -->
    <form action="{{ route('promoteur.events.tickets.store', $event) }}" method="POST">
        @csrf

        <!-- Affichage des erreurs -->
        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Il y a des erreurs dans votre formulaire :</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Conteneur des types de billets -->
        <div id="ticket-types-container">
            <!-- Les types seront ajoutés ici par JavaScript -->
        </div>

        <!-- Bouton pour ajouter un type -->
        <div class="text-center mb-4">
            <button type="button" id="add-ticket-type" class="btn btn-outline-secondary btn-lg">
                <i class="fas fa-plus me-2"></i>
                Ajouter un type de billet
            </button>
        </div>

        <!-- Actions -->
        <div class="card-custom">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        <i class="fas fa-info-circle me-2"></i>
                        Vous devez créer au moins un type de billet
                    </div>

                    <div>
                        <a href="{{ route('promoteur.events.show', $event) }}" class="btn btn-secondary me-2">
                            Terminer plus tard
                        </a>

                        <button type="submit" class="btn btn-orange btn-lg">
                            Finaliser l'événement
                            <i class="fas fa-check ms-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    let ticketTypeIndex = 0;

    // Template pour un type de billet
    function createTicketTypeForm(data = {}) {
        const index = ticketTypeIndex++;
        const today = new Date().toISOString().split('T')[0];
        const eventDate = '{{ $event->event_date ? $event->event_date->format("Y-m-d") : "" }}';

        return `
            <div class="ticket-form" data-index="${index}">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="fas fa-ticket-alt me-2" style="color: 
#FF6B35;"></i>
                        Type de billet #${index + 1}
                    </h5>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-ticket-type">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>

                <div class="row">
                    <!-- Nom -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nom du billet <span class="text-danger">*</span></label>
                        <input type="text" name="ticket_types[${index}][name]" required 
                               value="${data.name || ''}" class="form-control"
                               placeholder="Ex: Standard, VIP, Early Bird...">
                    </div>

                    <!-- Prix -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Prix (FCFA) <span class="text-danger">*</span></label>
                        <input type="number" name="ticket_types[${index}][price]" required min="0" step="100"
                               value="${data.price || ''}" class="form-control price-input"
                               placeholder="Ex: 5000">
                    </div>

                    <!-- Description -->
                    <div class="col-12 mb-3">
                        <label class="form-label">Description</label>
                        <input type="text" name="ticket_types[${index}][description]"
                               value="${data.description || ''}" class="form-control"
                               placeholder="Description courte du type de billet">
                    </div>

                    <!-- Quantité et Maximum -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Quantité disponible <span class="text-danger">*</span></label>
                        <input type="number" name="ticket_types[${index}][quantity_available]" required min="1"
                               value="${data.quantity || '100'}" class="form-control quantity-input"
                               placeholder="Nombre de billets disponibles">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Maximum par commande <span class="text-danger">*</span></label>
                        <input type="number" name="ticket_types[${index}][max_per_order]" required min="1" max="20"
                               value="${data.max_per_order || '5'}" class="form-control"
                               placeholder="Maximum par commande">
                    </div>

                    <!-- Dates -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Début de vente <span class="text-danger">*</span></label>
                        <input type="date" name="ticket_types[${index}][sale_start_date]" required
                               value="${today}" min="${today}" class="form-control start-date-input">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Fin de vente <span class="text-danger">*</span></label>
                        <input type="date" name="ticket_types[${index}][sale_end_date]" required
                               value="${eventDate}" max="${eventDate}" class="form-control end-date-input">
                    </div>
                </div>

                <!-- Résumé des revenus -->
                <div class="revenue-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Revenus potentiels:</span>
                        <span class="fw-bold text-success revenue-estimate">0 FCFA</span>
                    </div>
                </div>
            </div>
        `;
    }

    // Ajouter un type de billet
    document.getElementById('add-ticket-type').addEventListener('click', function() {
        const container = document.getElementById('ticket-types-container');
        const formHtml = createTicketTypeForm();
        container.insertAdjacentHTML('beforeend', formHtml);

        // Attacher les event listeners pour ce nouveau formulaire
        attachEventListeners(container.lastElementChild);

        // Animation d'apparition
        const newForm = container.lastElementChild;
        newForm.style.opacity = '0';
        newForm.style.transform = 'translateY(20px)';
        setTimeout(() => {
            newForm.style.transition = 'all 0.3s ease';
            newForm.style.opacity = '1';
            newForm.style.transform = 'translateY(0)';
        }, 10);
    });

    // Utiliser un modèle prédéfini
    document.querySelectorAll('.preset-card').forEach(card => {
        card.addEventListener('click', function() {
            const data = JSON.parse(this.dataset.preset);
            const container = document.getElementById('ticket-types-container');
            const formHtml = createTicketTypeForm(data);
            container.insertAdjacentHTML('beforeend', formHtml);

            // Attacher les event listeners et calculer le résumé
            const newForm = container.lastElementChild;
            attachEventListeners(newForm);
            calculateRevenue(newForm);

            // Animation et effet visuel
            newForm.style.opacity = '0';
            newForm.style.transform = 'translateY(20px)';
            setTimeout(() => {
                newForm.style.transition = 'all 0.3s ease';
                newForm.style.opacity = '1';
                newForm.style.transform = 'translateY(0)';
            }, 10);

            // Effet sur la carte
            this.classList.add('selected');
            setTimeout(() => {
                this.classList.remove('selected');
            }, 1500);
        });
    });

    // Attacher les event listeners à un formulaire
    function attachEventListeners(form) {
        // Bouton supprimer
        const removeBtn = form.querySelector('.remove-ticket-type');
        removeBtn.addEventListener('click', function() {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce type de billet ?')) {
                form.style.transition = 'all 0.3s ease';
                form.style.opacity = '0';
                form.style.transform = 'translateY(-20px)';
                setTimeout(() => form.remove(), 300);
            }
        });

        // Calcul des revenus en temps réel
        const priceInput = form.querySelector('.price-input');
        const quantityInput = form.querySelector('.quantity-input');

        [priceInput, quantityInput].forEach(input => {
            input.addEventListener('input', () => calculateRevenue(form));
        });

        // Validation des dates
        const startDateInput = form.querySelector('.start-date-input');
        const endDateInput = form.querySelector('.end-date-input');

        startDateInput.addEventListener('change', function() {
            endDateInput.setAttribute('min', this.value);
            if (endDateInput.value && endDateInput.value < this.value) {
                endDateInput.value = this.value;
            }
        });
    }

    // Calculer les revenus potentiels
    function calculateRevenue(form) {
        const price = parseFloat(form.querySelector('.price-input').value) || 0;
        const quantity = parseInt(form.querySelector('.quantity-input').value) || 0;
        const revenue = price * quantity;

        const revenueElement = form.querySelector('.revenue-estimate');
        revenueElement.textContent = new Intl.NumberFormat('fr-FR').format(revenue) + ' FCFA';

        // Changer la couleur selon le montant
        if (revenue > 500000) {
            revenueElement.className = 'fw-bold text-success';
        } else if (revenue > 100000) {
            revenueElement.className = 'fw-bold text-warning';
        } else {
            revenueElement.className = 'fw-bold text-muted';
        }
    }

    // Ajouter automatiquement le premier type
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('add-ticket-type').click();
    });

    // Validation du formulaire
    document.querySelector('form').addEventListener('submit', function(e) {
        const ticketTypes = document.querySelectorAll('.ticket-form');

        if (ticketTypes.length === 0) {
            e.preventDefault();
            alert('Vous devez créer au moins un type de billet');
            return;
        }

        // Vérifier que les dates sont cohérentes
        let hasError = false;
        ticketTypes.forEach(form => {
            const startDate = form.querySelector('.start-date-input').value;
            const endDate = form.querySelector('.end-date-input').value;

            if (new Date(endDate) <= new Date(startDate)) {
                hasError = true;
                alert('La date de fin de vente doit être après la date de début');
            }
        });

        if (hasError) {
            e.preventDefault();
        }
    });
</script>
@endpush@extends('layouts.app')

@section('content')
<div class="min-h-screen py-8 bg-gray-50">
    <div class="max-w-6xl mx-auto px-4">
        <!-- En-tête -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        <i class="fas fa-ticket-alt text-orange-500 mr-3"></i>
                        Configurer les types de billets
                    </h1>
                    <p class="text-gray-600 mt-2">Définissez les différents types de billets et leurs prix pour "{{ $event->title }}"</p>
                </div>
                <a href="{{ route('promoteur.events.show', $event) }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition">
                    <i class="fas fa-arrow-left mr-2"></i>Retour à l'événement
                </a>
            </div>
        </div>

        <!-- Étapes de création -->
        <div class="mb-8">
            <div class="flex items-center justify-center">
                <div class="flex items-center space-x-4">
                    <!-- Étape 1 : Terminée -->
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-green-500 text-white rounded-full flex items-center justify-center">
                            <i class="fas fa-check"></i>
                        </div>
                        <span class="ml-2 text-green-600 font-medium">Informations de base</span>
                    </div>

                    <div class="w-16 h-1 bg-green-500"></div>

                    <!-- Étape 2 : Actuelle -->
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-orange-500 text-white rounded-full flex items-center justify-center font-bold">
                            2
                        </div>
                        <span class="ml-2 text-orange-600 font-medium">Types de billets</span>
                    </div>

                    <div class="w-16 h-1 bg-gray-300"></div>

                    <!-- Étape 3 : Publication -->
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center font-bold">
                            3
                        </div>
                        <span class="ml-2 text-gray-500">Publication</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informations de l'événement -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <h2 class="text-lg font-bold text-gray-900 mb-4">Récapitulatif de l'événement</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="text-gray-600">Événement:</span>
                    <span class="font-medium ml-2">{{ $event->title }}</span>
                </div>
                <div>
                    <span class="text-gray-600">Date:</span>
                    <span class="font-medium ml-2">{{ $event->formatted_event_date }}</span>
                </div>
                <div>
                    <span class="text-gray-600">Lieu:</span>
                    <span class="font-medium ml-2">{{ $event->venue }}</span>
                </div>
            </div>
        </div>

        <!-- Types prédéfinis -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-900 mb-4">
                <i class="fas fa-magic text-purple-500 mr-2"></i>
                Modèles prédéfinis
            </h2>
            <p class="text-gray-600 mb-6">Cliquez sur un modèle pour l'ajouter rapidement</p>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Standard -->
                <div class="border border-gray-200 rounded-lg p-4 cursor-pointer hover:border-orange-500 transition preset-card" 
                     data-preset='{"name":"Standard","description":"Billet d\'accès standard","price":"5000","quantity":"100","max_per_order":"5"}'>
                    <div class="text-center">
                        <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-user"></i>
                        </div>
                        <h3 class="font-bold text-gray-900">Standard</h3>
                        <p class="text-sm text-gray-600 mt-1">Accès général</p>
                        <p class="text-lg font-bold text-blue-600 mt-2">5,000 FCFA</p>
                    </div>
                </div>

                <!-- VIP -->
                <div class="border border-gray-200 rounded-lg p-4 cursor-pointer hover:border-orange-500 transition preset-card"
                     data-preset='{"name":"VIP","description":"Accès privilégié avec avantages","price":"10000","quantity":"50","max_per_order":"3"}'>
                    <div class="text-center">
                        <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-crown"></i>
                        </div>
                        <h3 class="font-bold text-gray-900">VIP</h3>
                        <p class="text-sm text-gray-600 mt-1">Accès privilégié</p>
                        <p class="text-lg font-bold text-yellow-600 mt-2">10,000 FCFA</p>
                    </div>
                </div>

                <!-- Early Bird -->
                <div class="border border-gray-200 rounded-lg p-4 cursor-pointer hover:border-orange-500 transition preset-card"
                     data-preset='{"name":"Early Bird","description":"Tarif préférentiel (vente anticipée)","price":"3500","quantity":"75","max_per_order":"5"}'>
                    <div class="text-center">
                        <div class="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <h3 class="font-bold text-gray-900">Early Bird</h3>
                        <p class="text-sm text-gray-600 mt-1">Vente anticipée</p>
                        <p class="text-lg font-bold text-green-600 mt-2">3,500 FCFA</p>
                    </div>
                </div>

                <!-- Groupe -->
                <div class="border border-gray-200 rounded-lg p-4 cursor-pointer hover:border-orange-500 transition preset-card"
                     data-preset='{"name":"Groupe","description":"Tarif de groupe (minimum 5 personnes)","price":"4000","quantity":"200","max_per_order":"10"}'>
                    <div class="text-center">
                        <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="font-bold text-gray-900">Groupe</h3>
                        <p class="text-sm text-gray-600 mt-1">Tarif de groupe</p>
                        <p class="text-lg font-bold text-purple-600 mt-2">4,000 FCFA</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulaire principal -->
        <form action="{{ route('promoteur.events.tickets.store', $event) }}" method="POST" class="space-y-6">
            @csrf

            <!-- Affichage des erreurs -->
            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <strong>Il y a des erreurs dans votre formulaire :</strong>
                    <ul class="mt-2 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Conteneur des types de billets -->
            <div id="ticket-types-container" class="space-y-6">
                <!-- Les types seront ajoutés ici par JavaScript -->
            </div>

            <!-- Bouton pour ajouter un type -->
            <div class="text-center">
                <button type="button" id="add-ticket-type" 
                        class="bg-gray-100 text-gray-700 px-6 py-3 rounded-lg border-2 border-dashed border-gray-300 hover:border-orange-500 hover:bg-orange-50 hover:text-orange-600 transition">
                    <i class="fas fa-plus mr-2"></i>
                    Ajouter un type de billet
                </button>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center text-sm text-gray-600">
                    <i class="fas fa-info-circle mr-2"></i>
                    Vous devez créer au moins un type de billet
                </div>

                <div class="flex items-center space-x-4">
                    <a href="{{ route('promoteur.events.show', $event) }}"
                       class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                        Terminer plus tard
                    </a>

                    <button type="submit" 
                            class="bg-orange-500 text-white px-8 py-3 rounded-lg hover:bg-orange-600 transition flex items-center font-medium">
                        Finaliser l'événement
                        <i class="fas fa-check ml-2"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    let ticketTypeIndex = 0;

    // Template pour un type de billet
    function createTicketTypeForm(data = {}) {
        const index = ticketTypeIndex++;
        const today = new Date().toISOString().split('T')[0];
        const eventDate = '{{ $event->event_date }}';

        return `
            <div class="bg-white rounded-xl shadow-lg p-6 ticket-type-form" data-index="${index}">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold text-gray-900">
                        <i class="fas fa-ticket-alt text-orange-500 mr-2"></i>
                        Type de billet #${index + 1}
                    </h3>
                    <button type="button" class="text-red-500 hover:text-red-700 remove-ticket-type">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Nom -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Nom du billet <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="ticket_types[${index}][name]" required 
                               value="${data.name || ''}"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                               placeholder="Ex: Standard, VIP, Early Bird...">
                    </div>

                    <!-- Prix -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Prix (FCFA) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="ticket_types[${index}][price]" required min="0" step="100"
                               value="${data.price || ''}"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                               placeholder="Ex: 5000">
                    </div>

                    <!-- Description -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Description
                        </label>
                        <input type="text" name="ticket_types[${index}][description]"
                               value="${data.description || ''}"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                               placeholder="Description courte du type de billet">
                    </div>

                    <!-- Quantité disponible -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Quantité disponible <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="ticket_types[${index}][quantity_available]" required min="1"
                               value="${data.quantity || '100'}"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                               placeholder="Nombre de billets disponibles">
                    </div>

                    <!-- Maximum par commande -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Maximum par commande <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="ticket_types[${index}][max_per_order]" required min="1" max="20"
                               value="${data.max_per_order || '5'}"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                               placeholder="Maximum de billets par commande">
                    </div>

                    <!-- Date de début de vente -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Début de vente <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="ticket_types[${index}][sale_start_date]" required
                               value="${today}"
                               min="${today}"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    </div>

                    <!-- Date de fin de vente -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Fin de vente <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="ticket_types[${index}][sale_end_date]" required
                               value="${eventDate}"
                               max="${eventDate}"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    </div>
                </div>

                <!-- Résumé du type -->
                <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Revenus potentiels:</span>
                        <span class="font-bold text-green-600 revenue-estimate">0 FCFA</span>
                    </div>
                </div>
            </div>
        `;
    }

    // Ajouter un type de billet
    document.getElementById('add-ticket-type').addEventListener('click', function() {
        const container = document.getElementById('ticket-types-container');
        const formHtml = createTicketTypeForm();
        container.insertAdjacentHTML('beforeend', formHtml);

        // Attacher les event listeners pour ce nouveau formulaire
        attachEventListeners(container.lastElementChild);
    });

    // Utiliser un modèle prédéfini
    document.querySelectorAll('.preset-card').forEach(card => {
        card.addEventListener('click', function() {
            const data = JSON.parse(this.dataset.preset);
            const container = document.getElementById('ticket-types-container');
            const formHtml = createTicketTypeForm(data);
            container.insertAdjacentHTML('beforeend', formHtml);

            // Attacher les event listeners et calculer le résumé
            const newForm = container.lastElementChild;
            attachEventListeners(newForm);
            calculateRevenue(newForm);

            // Effet visuel
            this.classList.add('border-green-500', 'bg-green-50');
            setTimeout(() => {
                this.classList.remove('border-green-500', 'bg-green-50');
            }, 1000);
        });
    });

    // Attacher les event listeners à un formulaire
    function attachEventListeners(form) {
        // Bouton supprimer
        const removeBtn = form.querySelector('.remove-ticket-type');
        removeBtn.addEventListener('click', function() {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce type de billet ?')) {
                form.remove();
            }
        });

        // Calcul des revenus en temps réel
        const priceInput = form.querySelector('input[name="[price]"]');
        const quantityInput = form.querySelector('input[name="[quantity_available]"]');

        [priceInput, quantityInput].forEach(input => {
            input.addEventListener('input', () => calculateRevenue(form));
        });

        // Validation des dates
        const startDateInput = form.querySelector('input[name="[sale_start_date]"]');
        const endDateInput = form.querySelector('input[name="[sale_end_date]"]');

        startDateInput.addEventListener('change', function() {
            endDateInput.setAttribute('min', this.value);
            if (endDateInput.value && endDateInput.value < this.value) {
                endDateInput.value = this.value;
            }
        });
    }

    // Calculer les revenus potentiels
    function calculateRevenue(form) {
        const price = parseFloat(form.querySelector('input[name="[price]"]').value) || 0;
        const quantity = parseInt(form.querySelector('input[name="[quantity_available]"]').value) || 0;
        const revenue = price * quantity;

        const revenueElement = form.querySelector('.revenue-estimate');
        revenueElement.textContent = new Intl.NumberFormat('fr-FR').format(revenue) + ' FCFA';
    }

    // Ajouter automatiquement le premier type
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('add-ticket-type').click();
    });

    // Validation du formulaire
    document.querySelector('form').addEventListener('submit', function(e) {
        const ticketTypes = document.querySelectorAll('.ticket-type-form');

        if (ticketTypes.length === 0) {
            e.preventDefault();
            alert('Vous devez créer au moins un type de billet');
            return;
        }

        // Vérifier que les dates de fin sont après les dates de début
        let hasError = false;
        ticketTypes.forEach(form => {
            const startDate = form.querySelector('input[name="[sale_start_date]"]').value;
            const endDate = form.querySelector('input[name="[sale_end_date]"]').value;

            if (new Date(endDate) <= new Date(startDate)) {
                hasError = true;
                alert('La date de fin de vente doit être après la date de début');
            }
        });

        if (hasError) {
            e.preventDefault();
        }
    });

    // Animation d'ajout de formulaire
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1 && node.classList.contains('ticket-type-form')) {
                    node.style.opacity = '0';
                    node.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        node.style.transition = 'all 0.3s ease';
                        node.style.opacity = '1';
                        node.style.transform = 'translateY(0)';
                    }, 10);
                }
            });
        });
    });

    observer.observe(document.getElementById('ticket-types-container'), {
        childList: true
    });
</script>
@endsection