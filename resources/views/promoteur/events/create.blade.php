@extends('layouts.promoteur')

@push('styles')
<style>
    .step-indicator {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-left: 4px solid #FF6B35;
    }
    
    .step {
        display: flex;
        align-items: center;
        position: relative;
        justify-content: center;
    }
    
    .step-number {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: white;
        z-index: 2;
        font-size: 1.2rem;
    }
    
    .step-number.active {
        background: linear-gradient(135deg, #FF6B35, #E55A2B);
        box-shadow: 0 4px 8px rgba(255, 107, 53, 0.3);
    }
    
    .step-number.completed {
        background: linear-gradient(135deg, #28a745, #20c997);
    }
    
    .step-number.pending {
        background: #6c757d;
    }
    
    .step-line {
        height: 3px;
        flex: 1;
        margin: 0 1rem;
        background: #dee2e6;
        border-radius: 2px;
    }
    
    .step-line.completed {
        background: linear-gradient(135deg, #28a745, #20c997);
    }
    
    .card-custom {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-left: 4px solid #FF6B35;
        margin-bottom: 2rem;
        transition: transform 0.2s ease;
    }
    
    .card-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.15);
    }
    
    .btn-orange {
        background: linear-gradient(135deg, #FF6B35, #E55A2B);
        border: none;
        color: white;
        font-weight: 600;
        padding: 0.75rem 2rem;
        border-radius: 10px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 8px rgba(255, 107, 53, 0.3);
    }
    
    .btn-orange:hover {
        background: linear-gradient(135deg, #E55A2B, #D4491F);
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(255, 107, 53, 0.4);
        color: white;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #FF6B35;
        box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
    }
    
    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
    }
    
    .text-orange {
        color: #FF6B35 !important;
    }
    
    .border-orange {
        border-color: #FF6B35 !important;
    }
    
    .icon-section {
        color: #FF6B35;
        font-size: 1.5rem;
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
                        <a href="{{ route('promoteur.dashboard') }}" class="text-decoration-none">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('promoteur.events.index') }}" class="text-decoration-none">Événements</a>
                    </li>
                    <li class="breadcrumb-item active">Créer un événement</li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1 text-orange">
                        <i class="fas fa-calendar-plus me-2"></i>
                        Créer un nouvel événement
                    </h1>
                    <p class="text-muted">Remplissez les informations de base de votre événement</p>
                </div>
                <a href="{{ route('promoteur.dashboard') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Retour au dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Indicateur d'étapes -->
    <div class="step-indicator">
        <div class="step">
            <div class="step-number active">1</div>
            <span class="ms-3 fw-bold text-orange">Informations de base</span>
            <div class="step-line"></div>
            
            <div class="step-number pending">2</div>
            <span class="ms-3 text-muted fw-medium">Types de billets</span>
            <div class="step-line"></div>
            
            <div class="step-number pending">3</div>
            <span class="ms-3 text-muted fw-medium">Publication</span>
        </div>
    </div>

    <!-- Messages d'erreur -->
    @if ($errors->any())
        <div class="alert alert-danger border-0 rounded-3 shadow-sm">
            <div class="d-flex align-items-center mb-2">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Il y a des erreurs dans votre formulaire :</strong>
            </div>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Formulaire principal -->
    <form action="{{ route('promoteur.events.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <!-- Informations générales -->
        <div class="card-custom">
            <div class="card-body p-4">
                <h5 class="card-title mb-4 d-flex align-items-center">
                    <i class="fas fa-info-circle icon-section me-3"></i>
                    <span class="text-orange fw-bold">Informations générales</span>
                </h5>

                <div class="row g-4">
                    <!-- Titre -->
                    <div class="col-12">
                        <label class="form-label">Titre de l'événement <span class="text-danger">*</span></label>
                        <input type="text" name="title" required value="{{ old('title') }}" 
                               class="form-control form-control-lg"
                               placeholder="Ex: Concert de musique traditionnelle ivoirienne">
                    </div>

                    <!-- Catégorie et Image -->
                    <div class="col-md-6">
                        <label class="form-label">Catégorie <span class="text-danger">*</span></label>
                        <select name="category_id" required class="form-select form-select-lg">
                            <option value="">🎯 Sélectionner une catégorie</option>
                            @if(isset($categories))
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Image de couverture</label>
                        <input type="file" name="image" accept="image/*" class="form-control">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            JPG, PNG (max 2MB) - Recommandé: 1200x600px
                        </small>
                    </div>

                    <!-- Description -->
                    <div class="col-12">
                        <label class="form-label">Description de l'événement <span class="text-danger">*</span></label>
                        <textarea name="description" rows="4" required class="form-control"
                                  placeholder="Décrivez votre événement en détail : programme, artistes, ambiance...">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lieu et date -->
        <div class="card-custom">
            <div class="card-body p-4">
                <h5 class="card-title mb-4 d-flex align-items-center">
                    <i class="fas fa-map-marker-alt icon-section me-3"></i>
                    <span class="text-orange fw-bold">Lieu et planning</span>
                </h5>

                <div class="row g-4">
                    <!-- Nom du lieu et Date -->
                    <div class="col-md-6">
                        <label class="form-label">Nom du lieu <span class="text-danger">*</span></label>
                        <input type="text" name="venue" required value="{{ old('venue') }}" 
                               class="form-control"
                               placeholder="Ex: Palais de la Culture, Stade Félix Houphouët-Boigny">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Date de l'événement <span class="text-danger">*</span></label>
                        <input type="date" name="event_date" required value="{{ old('event_date') }}"
                               min="{{ date('Y-m-d', strtotime('+1 day')) }}" class="form-control">
                    </div>

                    <!-- Adresse complète -->
                    <div class="col-12">
                        <label class="form-label">Adresse complète <span class="text-danger">*</span></label>
                        <input type="text" name="address" required value="{{ old('address') }}" 
                               class="form-control"
                               placeholder="Adresse complète avec commune et ville (Ex: Cocody, Abidjan)">
                    </div>

                    <!-- Heures -->
                    <div class="col-md-6">
                        <label class="form-label">Heure de début <span class="text-danger">*</span></label>
                        <input type="time" name="event_time" required value="{{ old('event_time') }}" 
                               class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Heure de fin (optionnel)</label>
                        <input type="time" name="end_time" value="{{ old('end_time') }}" 
                               class="form-control">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Laissez vide si la durée est indéterminée
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conditions particulières -->
        <div class="card-custom">
            <div class="card-body p-4">
                <h5 class="card-title mb-4 d-flex align-items-center">
                    <i class="fas fa-file-contract icon-section me-3"></i>
                    <span class="text-orange fw-bold">Conditions particulières</span>
                </h5>

                <div class="row">
                    <div class="col-12">
                        <label class="form-label">Conditions et informations importantes</label>
                        <textarea name="terms_conditions" rows="4" class="form-control"
                                  placeholder="Ex: Âge minimum requis, objets interdits, code vestimentaire, conditions d'annulation, informations de sécurité...">{{ old('terms_conditions') }}</textarea>
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Ces informations seront affichées sur la page publique de l'événement
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="card-custom">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center text-muted">
                            <i class="fas fa-lightbulb me-2 text-warning"></i>
                            <div>
                                <strong>Prochaine étape :</strong> Après création, vous configurerez les types de billets et leurs prix
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 text-end">
                        <div class="d-flex justify-content-end gap-3">
                            <a href="{{ route('promoteur.dashboard') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Annuler
                            </a>
                            
                            <button type="submit" class="btn btn-orange btn-lg">
                                <i class="fas fa-arrow-right me-2"></i>
                                Créer et configurer les billets
                            </button>
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
        // Validation des heures
        const startTimeInput = document.querySelector('input[name="event_time"]');
        const endTimeInput = document.querySelector('input[name="end_time"]');
        
        endTimeInput.addEventListener('change', function(e) {
            const startTime = startTimeInput.value;
            const endTime = e.target.value;
            
            if (startTime && endTime && endTime <= startTime) {
                alert('⚠️ L\'heure de fin doit être après l\'heure de début');
                e.target.value = '';
                e.target.focus();
            }
        });

        // Validation de la date
        const dateInput = document.querySelector('input[name="event_date"]');
        dateInput.addEventListener('change', function(e) {
            const selectedDate = new Date(e.target.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (selectedDate <= today) {
                alert('⚠️ La date de l\'événement doit être dans le futur');
                e.target.value = '';
                e.target.focus();
            }
        });

        // Animation du formulaire
        const cards = document.querySelectorAll('.card-custom');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });

        // Amélioration de l'UX du formulaire
        const inputs = document.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.closest('.card-custom').style.borderLeftColor = '#FF6B35';
                this.closest('.card-custom').style.borderLeftWidth = '6px';
            });
            
            input.addEventListener('blur', function() {
                this.closest('.card-custom').style.borderLeftColor = '#FF6B35';
                this.closest('.card-custom').style.borderLeftWidth = '4px';
            });
        });

        // Compteur de caractères pour la description
        const descriptionTextarea = document.querySelector('textarea[name="description"]');
        if (descriptionTextarea) {
            const counter = document.createElement('small');
            counter.className = 'text-muted float-end';
            descriptionTextarea.parentNode.appendChild(counter);
            
            descriptionTextarea.addEventListener('input', function() {
                const length = this.value.length;
                counter.textContent = `${length} caractères`;
                
                if (length < 50) {
                    counter.className = 'text-warning float-end';
                    counter.textContent = `${length} caractères (minimum recommandé: 50)`;
                } else {
                    counter.className = 'text-muted float-end';
                }
            });
        }
    });
</script>
@endpush