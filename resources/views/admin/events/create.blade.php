@extends('admin.admin')

@section('title', 'Créer un événement')

@section('content')
<div class="container-fluid px-4">
    <!-- En-tête de la page -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Créer un événement</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.events.index') }}">Événements</a></li>
                    <li class="breadcrumb-item active">Créer</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.events.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Retour
            </a>
        </div>
    </div>

    <!-- Formulaire de création -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-plus me-2"></i>Informations de l'événement
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.events.store') }}" method="POST" enctype="multipart/form-data" id="eventForm">
                        @csrf
                        
                        <!-- Titre et catégorie -->
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="title" class="form-label">Titre de l'événement <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('title') is-invalid @enderror" 
                                       id="title" 
                                       name="title" 
                                       value="{{ old('title') }}" 
                                       placeholder="Ex: Concert de musique traditionnelle">
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="category_id" class="form-label">Catégorie <span class="text-danger">*</span></label>
                                <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id">
                                    <option value="">Sélectionner une catégorie</option>
                                    @foreach($categories ?? [] as $category)
                                        <option value="{{ $category->id }}" 
                                                {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Promoteur -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="promoter_id" class="form-label">Promoteur <span class="text-danger">*</span></label>
                                <select class="form-select @error('promoter_id') is-invalid @enderror" id="promoter_id" name="promoter_id">
                                    <option value="">Sélectionner un promoteur</option>
                                    @foreach($promoteurs ?? [] as $promoteur)
                                        <option value="{{ $promoteur->id }}" 
                                                {{ old('promoter_id') == $promoteur->id ? 'selected' : '' }}>
                                            {{ $promoteur->name }} ({{ $promoteur->email }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('promoter_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Statut initial</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="draft" {{ old('status', 'draft') == 'draft' ? 'selected' : '' }}>Brouillon</option>
                                    <option value="published" {{ old('status') == 'published' ? 'selected' : '' }}>Publié</option>
                                    <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Annulé</option>
                                </select>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="4" 
                                      placeholder="Décrivez votre événement en détail...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Lieu et adresse -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="venue" class="form-label">Lieu <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('venue') is-invalid @enderror" 
                                       id="venue" 
                                       name="venue" 
                                       value="{{ old('venue') }}" 
                                       placeholder="Ex: Palais de la Culture">
                                @error('venue')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="address" class="form-label">Adresse complète <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('address') is-invalid @enderror" 
                                       id="address" 
                                       name="address" 
                                       value="{{ old('address') }}" 
                                       placeholder="Ex: Treichville, Abidjan">
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Date et heure -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="event_date" class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" 
                                       class="form-control @error('event_date') is-invalid @enderror" 
                                       id="event_date" 
                                       name="event_date" 
                                       value="{{ old('event_date') }}" 
                                       min="{{ date('Y-m-d') }}">
                                @error('event_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="event_time" class="form-label">Heure de début <span class="text-danger">*</span></label>
                                <input type="time" 
                                       class="form-control @error('event_time') is-invalid @enderror" 
                                       id="event_time" 
                                       name="event_time" 
                                       value="{{ old('event_time') }}">
                                @error('event_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="end_time" class="form-label">Heure de fin</label>
                                <input type="time" 
                                       class="form-control @error('end_time') is-invalid @enderror" 
                                       id="end_time" 
                                       name="end_time" 
                                       value="{{ old('end_time') }}">
                                @error('end_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Optionnel</div>
                            </div>
                        </div>

                        <!-- Image -->
                        <div class="mb-4">
                            <label for="image" class="form-label">Image de l'événement</label>
                            <input type="file" 
                                   class="form-control @error('image') is-invalid @enderror" 
                                   id="image" 
                                   name="image" 
                                   accept="image/*">
                            @error('image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Formats acceptés: JPG, PNG, GIF. Taille max: 2MB</div>
                            
                            <!-- Prévisualisation -->
                            <div id="imagePreview" class="mt-3" style="display: none;">
                                <img id="previewImg" src="" alt="Aperçu" class="img-thumbnail" style="max-width: 200px;">
                            </div>
                        </div>

                        <!-- Boutons d'action -->
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
                                <i class="fas fa-times me-2"></i>Annuler
                            </button>
                            <div>
                                <button type="submit" name="action" value="draft" class="btn btn-outline-primary me-2">
                                    <i class="fas fa-save me-2"></i>Enregistrer en brouillon
                                </button>
                                <button type="submit" name="action" value="publish" class="btn btn-success">
                                    <i class="fas fa-check me-2"></i>Créer et publier
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar avec conseils -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-lightbulb me-2"></i>Conseils pour créer un événement
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-3">
                        <h6><i class="fas fa-info-circle me-2"></i>Titre accrocheur</h6>
                        <p class="mb-0 small">Utilisez un titre clair et attractif qui décrit bien votre événement.</p>
                    </div>
                    
                    <div class="alert alert-warning mb-3">
                        <h6><i class="fas fa-clock me-2"></i>Date et heure</h6>
                        <p class="mb-0 small">Vérifiez bien les dates et heures. Elles ne pourront plus être modifiées après publication.</p>
                    </div>
                    
                    <div class="alert alert-success mb-0">
                        <h6><i class="fas fa-image me-2"></i>Image attrayante</h6>
                        <p class="mb-0 small">Une belle image augmente le taux de clics. Utilisez une image de haute qualité.</p>
                    </div>
                </div>
            </div>

            <!-- Prochaines étapes -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-list-ol me-2"></i>Prochaines étapes
                    </h6>
                </div>
                <div class="card-body">
                    <div class="step-item d-flex align-items-center mb-2">
                        <span class="badge bg-primary rounded-pill me-3">1</span>
                        <span class="small">Créer l'événement</span>
                    </div>
                    <div class="step-item d-flex align-items-center mb-2">
                        <span class="badge bg-secondary rounded-pill me-3">2</span>
                        <span class="small">Configurer les types de billets</span>
                    </div>
                    <div class="step-item d-flex align-items-center mb-2">
                        <span class="badge bg-secondary rounded-pill me-3">3</span>
                        <span class="small">Publier l'événement</span>
                    </div>
                    <div class="step-item d-flex align-items-center">
                        <span class="badge bg-secondary rounded-pill me-3">4</span>
                        <span class="small">Promouvoir votre événement</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prévisualisation de l'image
    const imageInput = document.getElementById('image');
    const imagePreview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');

    imageInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                imagePreview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            imagePreview.style.display = 'none';
        }
    });

    // Validation des heures
    const eventTime = document.getElementById('event_time');
    const endTime = document.getElementById('end_time');

    function validateTimes() {
        if (eventTime.value && endTime.value) {
            if (endTime.value <= eventTime.value) {
                endTime.setCustomValidity('L\'heure de fin doit être après l\'heure de début');
            } else {
                endTime.setCustomValidity('');
            }
        }
    }

    eventTime.addEventListener('change', validateTimes);
    endTime.addEventListener('change', validateTimes);

    // Validation du formulaire
    const form = document.getElementById('eventForm');
    form.addEventListener('submit', function(e) {
        const action = e.submitter.value;
        
        if (action === 'publish') {
            if (!confirm('Êtes-vous sûr de vouloir publier cet événement immédiatement ?')) {
                e.preventDefault();
                return false;
            }
        }

        // Validation côté client
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        form.classList.add('was-validated');
    });

    // Auto-suggestion pour l'adresse (si API disponible)
    const addressInput = document.getElementById('address');
    // Ici vous pourriez intégrer une API de géolocalisation
});
</script>
@endpush

@push('styles')
<style>
.step-item {
    opacity: 0.7;
}
.step-item:first-child {
    opacity: 1;
    font-weight: 500;
}
.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}
.form-label {
    font-weight: 500;
    margin-bottom: 0.5rem;
}
.alert h6 {
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}
</style>
@endpush
@endsection