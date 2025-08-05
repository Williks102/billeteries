{{-- resources/views/admin/events/edit.blade.php --}}
@extends('layouts.admin')

@section('title', 'Modifier l\'événement - ' . $event->title)

@push('styles')
<style>
    .form-card {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        border-left: 4px solid var(--primary-orange);
        margin-bottom: 2rem;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: var(--primary-orange);
        box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
    }
    
    .image-preview {
        max-width: 250px;
        border-radius: 12px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        margin-top: 1rem;
    }
    
    .current-image-container {
        position: relative;
        display: inline-block;
    }
    
    .remove-image-btn {
        position: absolute;
        top: -10px;
        right: -10px;
        background: var(--danger);
        color: white;
        border: none;
        border-radius: 50%;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        transition: all 0.3s ease;
    }
    
    .remove-image-btn:hover {
        background: #c82333;
        transform: scale(1.1);
    }
    
    .status-badge {
        font-size: 0.85rem;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
    }
    
    .info-box {
        background: linear-gradient(135deg, rgba(255,107,53,0.1), rgba(255,107,53,0.05));
        border: 1px solid rgba(255,107,53,0.2);
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stat-item {
        text-align: center;
        padding: 1rem;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    
    .stat-item h4 {
        color: var(--primary-orange);
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .form-section-title {
        color: var(--black-primary);
        font-weight: 700;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--primary-orange);
        display: flex;
        align-items: center;
        gap: 10px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- En-tête avec breadcrumb -->
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('admin.dashboard') }}">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('admin.events') }}">Événements</a>
                    </li>
                    <li class="breadcrumb-item active">{{ $event->title }}</li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h1 class="h2 fw-bold text-dark mb-2">
                        <i class="fas fa-edit text-orange me-2"></i>
                        Modifier l'événement
                    </h1>
                    <p class="text-muted mb-0">{{ $event->title }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.events.show', $event) }}" class="btn btn-outline-orange">
                        <i class="fas fa-eye me-2"></i>Voir les détails
                    </a>
                    <a href="{{ route('admin.events') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Retour à la liste
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques rapides -->
    <div class="info-box">
        <div class="row">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-item">
                    <h4>{{ $event->getTicketsSoldCount() ?? 0 }}</h4>
                    <p class="text-muted mb-0">Billets vendus</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-item">
                    <h4>{{ number_format($event->totalRevenue() ?? 0, 0, ',', ' ') }} FCFA</h4>
                    <p class="text-muted mb-0">Revenus générés</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-item">
                    <h4>{{ $event->getOrdersCount() ?? 0 }}</h4>
                    <p class="text-muted mb-0">Commandes</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-item">
                    <h4>
                        <span class="status-badge 
                            @if($event->status === 'published') bg-success
                            @elseif($event->status === 'draft') bg-warning text-dark
                            @else bg-danger
                            @endif">
                            {{ ucfirst($event->status) }}
                        </span>
                    </h4>
                    <p class="text-muted mb-0">Statut actuel</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulaire d'édition -->
    <form method="POST" action="{{ route('admin.events.update', $event) }}" enctype="multipart/form-data" id="eventEditForm">
        @csrf
        @method('PATCH')
        
        <div class="row">
            <!-- Colonne principale - Formulaire -->
            <div class="col-lg-8">
                
                <!-- Informations générales -->
                <div class="form-card">
                    <h5 class="form-section-title">
                        <i class="fas fa-info-circle text-orange"></i>
                        Informations générales
                    </h5>
                    
                    <div class="row">
                        <!-- Titre -->
                        <div class="col-md-8 mb-3">
                            <label for="title" class="form-label fw-semibold">
                                Titre de l'événement <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('title') is-invalid @enderror" 
                                   id="title" 
                                   name="title" 
                                   value="{{ old('title', $event->title) }}" 
                                   placeholder="Titre accrocheur de votre événement" 
                                   required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Catégorie -->
                        <div class="col-md-4 mb-3">
                            <label for="category_id" class="form-label fw-semibold">
                                Catégorie <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('category_id') is-invalid @enderror" 
                                    id="category_id" 
                                    name="category_id" 
                                    required>
                                <option value="">Choisir une catégorie</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" 
                                            @selected(old('category_id', $event->category_id) == $category->id)>
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
                        <label for="description" class="form-label fw-semibold">
                            Description <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" 
                                  name="description" 
                                  rows="4" 
                                  placeholder="Description détaillée de l'événement..." 
                                  required>{{ old('description', $event->description) }}</textarea>
                        <div class="form-text">Décrivez votre événement de manière attrayante pour attirer les participants.</div>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Lieu et adresse -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="venue" class="form-label fw-semibold">
                                Lieu <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('venue') is-invalid @enderror" 
                                   id="venue" 
                                   name="venue" 
                                   value="{{ old('venue', $event->venue) }}" 
                                   placeholder="Ex: Palais de la Culture d'Abidjan" 
                                   required>
                            @error('venue')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="address" class="form-label fw-semibold">
                                Adresse complète <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('address') is-invalid @enderror" 
                                   id="address" 
                                   name="address" 
                                   value="{{ old('address', $event->address) }}" 
                                   placeholder="Adresse précise avec quartier et ville" 
                                   required>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Dates et heures -->
                <div class="form-card">
                    <h5 class="form-section-title">
                        <i class="fas fa-calendar-alt text-orange"></i>
                        Dates et heures
                    </h5>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="event_date" class="form-label fw-semibold">
                                Date de l'événement <span class="text-danger">*</span>
                            </label>
                            <input type="date" 
                                   class="form-control @error('event_date') is-invalid @enderror" 
                                   id="event_date" 
                                   name="event_date" 
                                   value="{{ old('event_date', $event->event_date?->format('Y-m-d')) }}" 
                                   min="{{ date('Y-m-d') }}" 
                                   required>
                            @error('event_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="event_time" class="form-label fw-semibold">
                                Heure de début <span class="text-danger">*</span>
                            </label>
                            <input type="time" 
                                   class="form-control @error('event_time') is-invalid @enderror" 
                                   id="event_time" 
                                   name="event_time" 
                                   value="{{ old('event_time', $event->event_time?->format('H:i')) }}" 
                                   required>
                            @error('event_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="end_time" class="form-label fw-semibold">
                                Heure de fin
                            </label>
                            <input type="time" 
                                   class="form-control @error('end_time') is-invalid @enderror" 
                                   id="end_time" 
                                   name="end_time" 
                                   value="{{ old('end_time', $event->end_time?->format('H:i')) }}">
                            <div class="form-text">Optionnel - Heure estimée de fin</div>
                            @error('end_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Image -->
                <div class="form-card">
                    <h5 class="form-section-title">
                        <i class="fas fa-image text-orange"></i>
                        Image de l'événement
                    </h5>
                    
                    <!-- Image actuelle -->
                    @if($event->image)
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Image actuelle</label>
                            <div class="current-image-container">
                                <img src="{{ Storage::url($event->image) }}" 
                                     alt="Image actuelle" 
                                     class="image-preview d-block">
                                <button type="button" 
                                        class="remove-image-btn" 
                                        onclick="removeCurrentImage()"
                                        title="Supprimer l'image actuelle">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <input type="hidden" name="remove_image" id="remove_image" value="0">
                        </div>
                    @endif
                    
                    <!-- Nouvelle image -->
                    <div class="mb-3">
                        <label for="image" class="form-label fw-semibold">
                            {{ $event->image ? 'Changer l\'image' : 'Ajouter une image' }}
                        </label>
                        <input type="file" 
                               class="form-control @error('image') is-invalid @enderror" 
                               id="image" 
                               name="image" 
                               accept="image/*"
                               onchange="previewNewImage(this)">
                        <div class="form-text">
                            Formats acceptés: JPG, PNG, GIF. Taille maximale: 2MB. 
                            Dimensions recommandées: 800x600px.
                        </div>
                        @error('image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        
                        <!-- Prévisualisation nouvelle image -->
                        <div id="new-image-preview" style="display: none;">
                            <img id="new-image" src="#" alt="Nouvelle image" class="image-preview mt-2">
                        </div>
                    </div>
                </div>

                <!-- Termes et conditions -->
                <div class="form-card">
                    <h5 class="form-section-title">
                        <i class="fas fa-file-contract text-orange"></i>
                        Termes et conditions
                    </h5>
                    <div class="mb-3">
                        <label for="terms_conditions" class="form-label fw-semibold">
                            Conditions particulières
                        </label>
                        <textarea class="form-control @error('terms_conditions') is-invalid @enderror" 
                                  id="terms_conditions" 
                                  name="terms_conditions" 
                                  rows="3" 
                                  placeholder="Conditions spécifiques à cet événement (optionnel)">{{ old('terms_conditions', $event->terms_conditions) }}</textarea>
                        <div class="form-text">
                            Conditions spéciales, restrictions d'âge, code vestimentaire, etc.
                        </div>
                        @error('terms_conditions')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Colonne latérale - Actions et infos -->
            <div class="col-lg-4">
                
                <!-- Actions rapides -->
                <div class="form-card">
                    <h5 class="form-section-title">
                        <i class="fas fa-cogs text-orange"></i>
                        Actions
                    </h5>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-orange btn-lg">
                            <i class="fas fa-save me-2"></i>Enregistrer les modifications
                        </button>
                        
                        <a href="{{ route('admin.events.show', $event) }}" class="btn btn-outline-primary">
                            <i class="fas fa-eye me-2"></i>Voir les détails
                        </a>
                        
                        @if($event->status === 'published')
                            <a href="{{ route('events.show', $event) }}" 
                               class="btn btn-outline-success" 
                               target="_blank">
                                <i class="fas fa-external-link-alt me-2"></i>Voir sur le site
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Gestion du statut -->
                <div class="form-card">
                    <h5 class="form-section-title">
                        <i class="fas fa-toggle-on text-orange"></i>
                        Statut et publication
                    </h5>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label fw-semibold">
                            Statut de publication <span class="text-danger">*</span>
                        </label>
                        <select class="form-select @error('status') is-invalid @enderror" 
                                id="status" 
                                name="status" 
                                required>
                            <option value="draft" @selected(old('status', $event->status) === 'draft')>
                                📝 Brouillon
                            </option>
                            <option value="published" @selected(old('status', $event->status) === 'published')>
                                ✅ Publié
                            </option>
                            <option value="cancelled" @selected(old('status', $event->status) === 'cancelled')>
                                ❌ Annulé
                            </option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Changement de promoteur (admin seulement) -->
                    <div class="mb-3">
                        <label for="promoter_id" class="form-label fw-semibold">
                            Promoteur responsable <span class="text-danger">*</span>
                        </label>
                        <select class="form-select @error('promoter_id') is-invalid @enderror" 
                                id="promoter_id" 
                                name="promoter_id" 
                                required>
                            <option value="">Sélectionner un promoteur</option>
                            @foreach($promoteurs as $promoteur)
                                <option value="{{ $promoteur->id }}" 
                                        @selected(old('promoter_id', $event->promoter_id) == $promoteur->id)>
                                    {{ $promoteur->name }} ({{ $promoteur->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('promoter_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Informations système -->
                <div class="form-card">
                    <h5 class="form-section-title">
                        <i class="fas fa-info text-orange"></i>
                        Informations système
                    </h5>
                    
                    <div class="row text-center g-3">
                        <div class="col-6">
                            <div class="border rounded p-2">
                                <strong class="d-block text-muted small">Créé le</strong>
                                <span class="small">{{ $event->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-2">
                                <strong class="d-block text-muted small">Modifié le</strong>
                                <span class="small">{{ $event->updated_at->format('d/m/Y H:i') }}</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="border rounded p-2">
                                <strong class="d-block text-muted small">ID de l'événement</strong>
                                <code class="small">#{{ $event->id }}</code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validation du formulaire
    const form = document.getElementById('eventEditForm');
    const eventDate = document.getElementById('event_date');
    const eventTime = document.getElementById('event_time');
    const endTime = document.getElementById('end_time');

    // Validation des heures
    function validateTimes() {
        if (eventTime.value && endTime.value) {
            if (eventTime.value >= endTime.value) {
                endTime.setCustomValidity('L\'heure de fin doit être après l\'heure de début');
            } else {
                endTime.setCustomValidity('');
            }
        }
    }

    eventTime.addEventListener('change', validateTimes);
    endTime.addEventListener('change', validateTimes);

    // Confirmation avant soumission pour les modifications importantes
    form.addEventListener('submit', function(e) {
        const currentStatus = '{{ $event->status }}';
        const newStatus = document.getElementById('status').value;
        
        if (currentStatus === 'published' && newStatus !== 'published') {
            if (!confirm('Attention ! Vous êtes sur le point de dépublier cet événement. Les utilisateurs ne pourront plus le voir sur le site. Continuer ?')) {
                e.preventDefault();
                return false;
            }
        }
        
        if (currentStatus !== 'published' && newStatus === 'published') {
            if (!confirm('Vous allez publier cet événement. Il sera visible par tous les utilisateurs. Assurez-vous que toutes les informations sont correctes. Continuer ?')) {
                e.preventDefault();
                return false;
            }
        }
    });
});

// Fonction pour supprimer l'image actuelle
function removeCurrentImage() {
    if (confirm('Êtes-vous sûr de vouloir supprimer l\'image actuelle ?')) {
        document.getElementById('remove_image').value = '1';
        document.querySelector('.current-image-container').style.display = 'none';
    }
}

// Fonction pour prévisualiser la nouvelle image
function previewNewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            document.getElementById('new-image').src = e.target.result;
            document.getElementById('new-image-preview').style.display = 'block';
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Auto-sauvegarde en brouillon (optionnel)
let autoSaveTimeout;
function autoSave() {
    clearTimeout(autoSaveTimeout);
    autoSaveTimeout = setTimeout(function() {
        // Implémentation de la sauvegarde automatique si nécessaire
        console.log('Auto-sauvegarde...');
    }, 30000); // 30 secondes
}

// Déclencher l'auto-sauvegarde sur les changements
document.querySelectorAll('input, textarea, select').forEach(function(element) {
    element.addEventListener('change', autoSave);
});
</script>
@endpush