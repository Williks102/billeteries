{{-- resources/views/admin/events/create-hybrid.blade.php --}}
@extends('layouts.admin')

@section('title', 'Créer un événement (Mode Hybride)')

@section('content')
<div class="container-fluid px-4">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas fa-magic me-2 text-warning"></i>
                Créer un événement (Mode Hybride)
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.events.index') }}">Événements</a></li>
                    <li class="breadcrumb-item active">Création Hybride</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.events.create') }}" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-left me-2"></i>Mode Standard
            </a>
            <a href="{{ route('admin.events.index') }}" class="btn btn-secondary">
                <i class="fas fa-times me-2"></i>Annuler
            </a>
        </div>
    </div>

    <!-- Formulaire hybride -->
    <form action="{{ route('admin.events.store-with-mode') }}" method="POST" enctype="multipart/form-data" id="hybridEventForm">
        @csrf
        
        <div class="row">
            <!-- Informations de base -->
            <div class="col-lg-8">
                <!-- Carte informations événement -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>Informations de l'Événement
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Titre -->
                            <div class="col-md-8 mb-3">
                                <label for="title" class="form-label">Titre de l'événement <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('title') is-invalid @enderror" 
                                       id="title" 
                                       name="title" 
                                       value="{{ old('title') }}" 
                                       placeholder="Ex: Concert de musique traditionnelle"
                                       required>
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Catégorie -->
                            <div class="col-md-4 mb-3">
                                <label for="category_id" class="form-label">Catégorie <span class="text-danger">*</span></label>
                                <select class="form-select @error('category_id') is-invalid @enderror" 
                                        id="category_id" 
                                        name="category_id" 
                                        required>
                                    <option value="">Sélectionner une catégorie</option>
                                    @foreach($categories ?? [] as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="4" 
                                      placeholder="Décrivez votre événement en détail..." 
                                      required>{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <!-- Promoteur -->
                            <div class="col-md-6 mb-3">
                                <label for="promoter_id" class="form-label">Promoteur <span class="text-danger">*</span></label>
                                <select class="form-select @error('promoter_id') is-invalid @enderror" 
                                        id="promoter_id" 
                                        name="promoter_id" 
                                        required>
                                    <option value="">Sélectionner un promoteur</option>
                                    @foreach($promoteurs ?? [] as $promoteur)
                                        <option value="{{ $promoteur->id }}" {{ old('promoter_id') == $promoteur->id ? 'selected' : '' }}>
                                            {{ $promoteur->name }} ({{ $promoteur->email }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('promoter_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Statut -->
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Statut initial <span class="text-danger">*</span></label>
                                <select class="form-select @error('status') is-invalid @enderror" 
                                        id="status" 
                                        name="status" 
                                        required>
                                    <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>Brouillon</option>
                                    <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>En attente de validation</option>
                                    <option value="published" {{ old('status') === 'published' ? 'selected' : '' }}>Publié (si billets prêts)</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Date -->
                            <div class="col-md-4 mb-3">
                                <label for="event_date" class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" 
                                       class="form-control @error('event_date') is-invalid @enderror" 
                                       id="event_date" 
                                       name="event_date" 
                                       value="{{ old('event_date') }}" 
                                       min="{{ date('Y-m-d', strtotime('+1 day')) }}" 
                                       required>
                                @error('event_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Heure -->
                            <div class="col-md-4 mb-3">
                                <label for="event_time" class="form-label">Heure <span class="text-danger">*</span></label>
                                <input type="time" 
                                       class="form-control @error('event_time') is-invalid @enderror" 
                                       id="event_time" 
                                       name="event_time" 
                                       value="{{ old('event_time', '20:00') }}" 
                                       required>
                                @error('event_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Capacité -->
                            <div class="col-md-4 mb-3">
                                <label for="max_capacity" class="form-label">Capacité max</label>
                                <input type="number" 
                                       class="form-control @error('max_capacity') is-invalid @enderror" 
                                       id="max_capacity" 
                                       name="max_capacity" 
                                       value="{{ old('max_capacity') }}" 
                                       placeholder="Optionnel" 
                                       min="1">
                                @error('max_capacity')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Lieu -->
                            <div class="col-md-6 mb-3">
                                <label for="venue" class="form-label">Lieu <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('venue') is-invalid @enderror" 
                                       id="venue" 
                                       name="venue" 
                                       value="{{ old('venue') }}" 
                                       placeholder="Ex: Palais de la Culture" 
                                       required>
                                @error('venue')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Adresse -->
                            <div class="col-md-6 mb-3">
                                <label for="address" class="form-label">Adresse complète</label>
                                <input type="text" 
                                       class="form-control @error('address') is-invalid @enderror" 
                                       id="address" 
                                       name="address" 
                                       value="{{ old('address') }}" 
                                       placeholder="Adresse détaillée">
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Image -->
                        <div class="mb-3">
                            <label for="image" class="form-label">Image de l'événement</label>
                            <input type="file" 
                                   class="form-control @error('image') is-invalid @enderror" 
                                   id="image" 
                                   name="image" 
                                   accept="image/*">
                            <small class="text-muted">Formats acceptés : JPG, PNG, GIF (max 2MB)</small>
                            @error('image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Carte Billets (Optionnelle) -->
                <div class="card mb-4" id="ticketsCard" style="display: none;">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-ticket-alt me-2"></i>Configuration des Billets
                        </h5>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="addTicketType">
                            <i class="fas fa-plus me-1"></i>Ajouter un type
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="ticketTypesContainer">
                            <!-- Les types de billets seront ajoutés ici dynamiquement -->
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Info :</strong> Si vous configurez les billets maintenant, l'événement sera immédiatement 
                            opérationnel. Sinon, le promoteur devra les configurer dans son espace.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuration de gestion -->
            <div class="col-lg-4">
                <!-- Mode de gestion -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-cogs me-2 text-warning"></i>Mode de Gestion
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Qui va gérer cet événement ? <span class="text-danger">*</span></label>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="management_mode" value="promoter" 
                                       id="mode_promoter" {{ old('management_mode', 'promoter') === 'promoter' ? 'checked' : '' }}>
                                <label class="form-check-label" for="mode_promoter">
                                    <strong>Mode Promoteur</strong><br>
                                    <small class="text-muted">Le promoteur gère tout (normal)</small>
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="management_mode" value="admin" 
                                       id="mode_admin" {{ old('management_mode') === 'admin' ? 'checked' : '' }}>
                                <label class="form-check-label" for="mode_admin">
                                    <strong>Mode Admin</strong><br>
                                    <small class="text-muted">Vous gérez tout (dépannage)</small>
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="management_mode" value="collaborative" 
                                       id="mode_collaborative" {{ old('management_mode') === 'collaborative' ? 'checked' : '' }}>
                                <label class="form-check-label" for="mode_collaborative">
                                    <strong>Mode Collaboratif</strong><br>
                                    <small class="text-muted">Gestion partagée</small>
                                </label>
                            </div>
                        </div>

                        <!-- Permissions pour mode collaboratif -->
                        <div id="collaborativePermissions" style="display: none;">
                            <label class="form-label">Vos permissions :</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="admin_permissions[]" value="create_tickets" id="perm_create">
                                <label class="form-check-label" for="perm_create">Créer des billets</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="admin_permissions[]" value="edit_tickets" id="perm_edit">
                                <label class="form-check-label" for="perm_edit">Modifier les billets</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="admin_permissions[]" value="set_prices" id="perm_prices">
                                <label class="form-check-label" for="perm_prices">Définir les prix</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="admin_permissions[]" value="publish_event" id="perm_publish">
                                <label class="form-check-label" for="perm_publish">Publier l'événement</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="admin_permissions[]" value="manage_sales" id="perm_sales">
                                <label class="form-check-label" for="perm_sales">Gérer les ventes</label>
                            </div>
                        </div>

                        <!-- Raison -->
                        <div class="mt-3" id="reasonField" style="display: none;">
                            <label class="form-label">Raison du mode spécial</label>
                            <textarea name="management_reason" class="form-control" rows="3" 
                                      placeholder="Pourquoi utilisez-vous ce mode de gestion ?">{{ old('management_reason') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Configuration rapide des billets -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-bolt me-2 text-primary"></i>Configuration Rapide
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="configure_tickets_now" 
                                   id="configure_tickets_now" value="1" {{ old('configure_tickets_now') ? 'checked' : '' }}>
                            <label class="form-check-label" for="configure_tickets_now">
                                <strong>Configurer les billets maintenant</strong><br>
                                <small class="text-muted">Créer les types de billets directement</small>
                            </label>
                        </div>

                        <div class="alert alert-warning">
                            <i class="fas fa-lightbulb me-2"></i>
                            <strong>Conseil :</strong> Activez cette option si :
                            <ul class="mb-0 mt-2">
                                <li>Le promoteur a des difficultés techniques</li>
                                <li>L'événement est urgent</li>
                                <li>Vous voulez aider le promoteur</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted">
                                <i class="fas fa-info-circle me-2"></i>
                                L'événement sera créé selon le mode de gestion sélectionné
                            </div>
                            
                            <div>
                                <a href="{{ route('admin.events.index') }}" class="btn btn-secondary me-2">
                                    <i class="fas fa-times me-2"></i>Annuler
                                </a>
                                
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-magic me-2"></i>Créer l'Événement
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modeRadios = document.querySelectorAll('input[name="management_mode"]');
    const collaborativePerms = document.getElementById('collaborativePermissions');
    const reasonField = document.getElementById('reasonField');
    const configureTicketsCheckbox = document.getElementById('configure_tickets_now');
    const ticketsCard = document.getElementById('ticketsCard');
    const ticketTypesContainer = document.getElementById('ticketTypesContainer');
    const addTicketTypeBtn = document.getElementById('addTicketType');
    
    let ticketTypeIndex = 0;

    // Gestion des modes de gestion
    modeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const mode = this.value;
            
            if (mode === 'collaborative') {
                collaborativePerms.style.display = 'block';
                reasonField.style.display = 'block';
            } else if (mode === 'admin') {
                collaborativePerms.style.display = 'none';
                reasonField.style.display = 'block';
            } else {
                collaborativePerms.style.display = 'none';
                reasonField.style.display = 'none';
            }
        });
    });

    // Gestion de l'affichage des billets
    configureTicketsCheckbox.addEventListener('change', function() {
        if (this.checked) {
            ticketsCard.style.display = 'block';
            addTicketType(); // Ajouter un premier type par défaut
        } else {
            ticketsCard.style.display = 'none';
            ticketTypesContainer.innerHTML = '';
            ticketTypeIndex = 0;
        }
    });

    // Ajouter un type de billet
    addTicketTypeBtn.addEventListener('click', addTicketType);

    function addTicketType() {
        const index = ticketTypeIndex++;
        const template = `
            <div class="ticket-type-form border rounded p-3 mb-3" data-index="${index}">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">
                        <i class="fas fa-ticket-alt me-2"></i>Type de billet #${index + 1}
                    </h6>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-ticket-type">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" name="ticket_types[${index}][name]" class="form-control" 
                               placeholder="Ex: Standard, VIP..." required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Prix (FCFA) <span class="text-danger">*</span></label>
                        <input type="number" name="ticket_types[${index}][price]" class="form-control" 
                               placeholder="5000" min="0" step="100" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Quantité <span class="text-danger">*</span></label>
                        <input type="number" name="ticket_types[${index}][quantity_available]" class="form-control" 
                               placeholder="100" min="1" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Max par commande <span class="text-danger">*</span></label>
                        <input type="number" name="ticket_types[${index}][max_per_order]" class="form-control" 
                               value="5" min="1" max="20" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Début de vente <span class="text-danger">*</span></label>
                        <input type="date" name="ticket_types[${index}][sale_start_date]" class="form-control" 
                               value="${new Date().toISOString().split('T')[0]}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Fin de vente <span class="text-danger">*</span></label>
                        <input type="date" name="ticket_types[${index}][sale_end_date]" class="form-control" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="ticket_types[${index}][description]" class="form-control" rows="2" 
                              placeholder="Description optionnelle du type de billet"></textarea>
                </div>
            </div>
        `;

        ticketTypesContainer.insertAdjacentHTML('beforeend', template);

        // Ajouter l'événement de suppression
        const removeBtn = ticketTypesContainer.lastElementChild.querySelector('.remove-ticket-type');
        removeBtn.addEventListener('click', function() {
            this.closest('.ticket-type-form').remove();
        });

        // Synchroniser la date de fin avec la date de l'événement
        const eventDateInput = document.getElementById('event_date');
        const endDateInput = ticketTypesContainer.lastElementChild.querySelector('input[name*="[sale_end_date]"]');
        
        if (eventDateInput.value) {
            // Mettre la fin de vente la veille de l'événement
            const eventDate = new Date(eventDateInput.value);
            eventDate.setDate(eventDate.getDate() - 1);
            endDateInput.value = eventDate.toISOString().split('T')[0];
        }
    }

    // Déclencher les événements au chargement si nécessaire
    const checkedMode = document.querySelector('input[name="management_mode"]:checked');
    if (checkedMode) {
        checkedMode.dispatchEvent(new Event('change'));
    }

    if (configureTicketsCheckbox.checked) {
        configureTicketsCheckbox.dispatchEvent(new Event('change'));
    }

    // Synchroniser les dates automatiquement
    document.getElementById('event_date').addEventListener('change', function() {
        const endDateInputs = document.querySelectorAll('input[name*="[sale_end_date]"]');
        const eventDate = new Date(this.value);
        eventDate.setDate(eventDate.getDate() - 1);
        
        endDateInputs.forEach(input => {
            input.value = eventDate.toISOString().split('T')[0];
        });
    });
});
</script>

<style>
.ticket-type-form {
    background-color: #f8f9fa;
    transition: all 0.3s ease;
}

.ticket-type-form:hover {
    background-color: #e9ecef;
}

.form-check-label strong {
    color: #495057;
}

.alert-warning ul {
    padding-left: 1.2rem;
}
</style>
@endsection