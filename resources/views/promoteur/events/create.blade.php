@extends('layouts.promoteur')

@push('styles')
<style>
    .step-indicator {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-left: 4px solid var(--brand);
    }
    
    .step {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem 0;
        align-items: center;
        position: relative;
        justify-content: center;
    }
    @media (max-width: 575.98px) {
        .step-indicator { padding: 1rem; }
        .step-line { display: none; }
        .step { justify-content: flex-start; flex-direction: column; align-items: flex-start; }
        .step .ms-3 { margin-inline-start: .5rem !important; margin-inline-end: 0; }
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
        background: linear-gradient(135deg, var(--brand), var(--brand-hover));
        box-shadow: 0 4px 8px rgba(var(--brand-rgb), 0.3);
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
        border-left: 4px solid var(--brand);
        margin-bottom: 2rem;
        transition: transform 0.2s ease;
    }

    @media (prefers-reduced-motion: reduce) { .card-custom, .card-custom:hover { transition: none; transform: none; } }
    
    .card-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.15);
    }
    
    .btn-orange {
        background: linear-gradient(135deg, var(--brand), var(--brand-hover));
        border: none;
        color: white;
        font-weight: 600;
        padding: 0.75rem 2rem;
        border-radius: 10px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 8px rgba(var(--brand-rgb), 0.3);
    }
    
    .btn-orange:hover {
        background: linear-gradient(135deg, var(--brand-hover), #D4491F);
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(var(--brand-rgb), 0.4);
        color: white;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: var(--brand);
        box-shadow: 0 0 0 0.2rem rgba(var(--brand-rgb), 0.25);
    }
    
    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
    }
    
    .text-orange {
        color: var(--brand) !important;
    }
    
    .border-orange {
        border-color: var(--brand) !important;
    }
    
    .icon-section {
        color: var(--brand);
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
            
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
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
                        <label for="title" class="form-label">Titre de l'événement <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">(obligatoire)</span></label>
                        <input type="text" id="title" name="title" required value="{{ old('title') }}"
                               class="form-control form-control-lg @error('title') is-invalid @enderror"
                               autocomplete="off" aria-describedby="title-help @error('title') title-error @enderror" @error('title') aria-invalid="true" @enderror>
                        <div id="title-help" class="form-text">Ex : Concert de musique traditionnelle ivoirienne</div>
                        <x-field-error name="title" />
                    </div>

                    <!-- Catégorie et Image -->
                    <div class="col-md-6">
                        <label for="category_id" class="form-label">Catégorie <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">(obligatoire)</span></label>
                        <select id="category_id" name="category_id" required class="form-select form-select-lg @error('category_id') is-invalid @enderror"
                                @error('category_id') aria-invalid="true" aria-describedby="category_id-error" @enderror>
                            <option value="">Sélectionner une catégorie</option>
                            @if(isset($categories))
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <x-field-error name="category_id" />
                    </div>

                    <div class="col-md-6">
                        <label for="image" class="form-label">Image de couverture</label>
                        <input type="file" id="image" name="image" accept="image/*" class="form-control @error('image') is-invalid @enderror"
                               aria-describedby="image-help @error('image') image-error @enderror" @error('image') aria-invalid="true" @enderror>
                        <div id="image-help" class="form-text">
                            <i class="fas fa-info-circle me-1" aria-hidden="true"></i>
                            JPG, PNG (max 2 Mo) - Recommandé : 1200x600 px
                        </div>
                        <x-field-error name="image" />
                    </div>

                    <!-- Description -->
                    <div class="col-12">
                        <label for="description" class="form-label">Description de l'événement <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">(obligatoire)</span></label>
                        <textarea id="description" name="description" rows="4" required class="form-control @error('description') is-invalid @enderror"
                                  aria-describedby="description-help @error('description') description-error @enderror" @error('description') aria-invalid="true" @enderror>{{ old('description') }}</textarea>
                        <div id="description-help" class="form-text">Programme, artistes, ambiance...</div>
                        <x-field-error name="description" />
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
                        <label for="venue" class="form-label">Nom du lieu <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">(obligatoire)</span></label>
                        <input type="text" id="venue" name="venue" required value="{{ old('venue') }}"
                               class="form-control @error('venue') is-invalid @enderror" autocomplete="off"
                               aria-describedby="venue-help @error('venue') venue-error @enderror" @error('venue') aria-invalid="true" @enderror>
                        <div id="venue-help" class="form-text">Ex : Palais de la Culture</div>
                        <x-field-error name="venue" />
                    </div>

                    <div class="col-md-6">
                        <label for="event_date" class="form-label">Date de l'événement <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">(obligatoire)</span></label>
                        <input type="date" id="event_date" name="event_date" required value="{{ old('event_date') }}"
                               min="{{ date('Y-m-d', strtotime('+1 day')) }}" class="form-control @error('event_date') is-invalid @enderror"
                               @error('event_date') aria-invalid="true" aria-describedby="event_date-error" @enderror>
                        <x-field-error name="event_date" />
                    </div>

                    <!-- Adresse complète -->
                    <div class="col-12">
                        <label for="address" class="form-label">Adresse complète <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">(obligatoire)</span></label>
                        <input type="text" id="address" name="address" required value="{{ old('address') }}"
                               class="form-control @error('address') is-invalid @enderror" autocomplete="street-address"
                               aria-describedby="address-help @error('address') address-error @enderror" @error('address') aria-invalid="true" @enderror>
                        <div id="address-help" class="form-text">Avec commune et ville (Ex : Cocody, Abidjan)</div>
                        <x-field-error name="address" />
                    </div>

                    <!-- Heures -->
                    <div class="col-md-6">
                        <label for="event_time" class="form-label">Heure de début <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">(obligatoire)</span></label>
                        <input type="time" id="event_time" name="event_time" required value="{{ old('event_time') }}"
                               class="form-control @error('event_time') is-invalid @enderror"
                               @error('event_time') aria-invalid="true" aria-describedby="event_time-error" @enderror>
                        <x-field-error name="event_time" />
                    </div>

                    <div class="col-md-6">
                        <label for="end_time" class="form-label">Heure de fin (optionnel)</label>
                        <input type="time" id="end_time" name="end_time" value="{{ old('end_time') }}"
                               class="form-control @error('end_time') is-invalid @enderror"
                               aria-describedby="end_time-help @error('end_time') end_time-error @enderror" @error('end_time') aria-invalid="true" @enderror>
                        <div id="end_time-help" class="form-text">
                            <i class="fas fa-info-circle me-1" aria-hidden="true"></i>
                            Laissez vide si la durée est indéterminée
                        </div>
                        <x-field-error name="end_time" />
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
                        <label for="terms_conditions" class="form-label">Conditions et informations importantes</label>
                        <textarea id="terms_conditions" name="terms_conditions" rows="4" class="form-control @error('terms_conditions') is-invalid @enderror"
                                  aria-describedby="terms_conditions-help @error('terms_conditions') terms_conditions-error @enderror" @error('terms_conditions') aria-invalid="true" @enderror>{{ old('terms_conditions') }}</textarea>
                        <div id="terms_conditions-help" class="form-text">
                            <i class="fas fa-info-circle me-1" aria-hidden="true"></i>
                            Âge minimum, objets interdits, code vestimentaire, annulation... Affiché sur la page publique de l'événement.
                        </div>
                        <x-field-error name="terms_conditions" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="card-custom">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-lg-5">
                        <div class="d-flex align-items-center text-muted">
                            <i class="fas fa-lightbulb me-2 text-warning"></i>
                            <div>
                                <strong>Prochaine étape :</strong> Après création, vous configurerez les types de billets et leurs prix
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-7">
                        <div class="action-bar mt-3 mt-lg-0">
                            <button type="submit" class="btn btn-orange btn-lg">
                                <i class="fas fa-arrow-right me-2"></i>
                                Créer et configurer les billets
                            </button>
                            <a href="{{ route('promoteur.dashboard') }}" class="btn btn-secondary action-bar__cancel">
                                <i class="fas fa-times me-2"></i>Annuler
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/promoteur-events-create.js') }}" defer></script>
@endpush