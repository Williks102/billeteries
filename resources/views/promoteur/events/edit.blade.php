{{-- resources/views/promoteur/events/edit.blade.php --}}
@extends('layouts.promoteur')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@push('styles')
<style>
    .form-card {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-left: 4px solid #FF6B35;
        margin-bottom: 2rem;
    }
    
    .form-control:focus {
        border-color: #FF6B35;
        box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
    }
    
    .btn-orange {
        background: linear-gradient(135deg, #FF6B35, #E55A2B);
        border: none;
        color: white;
    }
    
    .btn-orange:hover {
        background: linear-gradient(135deg, #E55A2B, #D4491F);
        color: white;
    }
    
    .image-preview {
        max-width: 200px;
        border-radius: 8px;
        margin-top: 1rem;
    }
    
    .current-image {
        position: relative;
        display: inline-block;
    }
    
    .remove-image {
        position: absolute;
        top: -10px;
        right: -10px;
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2" style="color: #FF6B35;">
                        <i class="fas fa-edit me-2"></i>
                        Modifier l'événement
                    </h1>
                    <p class="text-muted">{{ $event->title }}</p>
                </div>
                <div>
                    <a href="{{ route('promoteur.events.show', $event) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </a>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('promoteur.events.update', $event) }}" enctype="multipart/form-data">
        @csrf
        @method('PATCH')
        
        <div class="row">
            <!-- Formulaire principal -->
            <div class="col-lg-8">
                <div class="form-card">
                    <h5 class="fw-bold mb-4">
                        <i class="fas fa-info-circle me-2" style="color: #FF6B35;"></i>
                        Informations générales
                    </h5>
                    
                    <div class="row">
                        <!-- Titre -->
                        <div class="col-md-8 mb-3">
                            <label for="title" class="form-label">Titre de l'événement *</label>
                            <input type="text" 
                                   class="form-control @error('title') is-invalid @enderror" 
                                   id="title" 
                                   name="title" 
                                   value="{{ old('title', $event->title) }}" 
                                   required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Catégorie -->
                        <div class="col-md-4 mb-3">
                            <label for="category_id" class="form-label">Catégorie *</label>
                            <select class="form-select @error('category_id') is-invalid @enderror" 
                                    id="category_id" 
                                    name="category_id" 
                                    required>
                                <option value="">Choisir une catégorie</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" 
                                            {{ old('category_id', $event->category_id) == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="col-12 mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="4" 
                                      required>{{ old('description', $event->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Lieu -->
                        <div class="col-md-6 mb-3">
                            <label for="venue" class="form-label">Lieu *</label>
                            <input type="text" 
                                   class="form-control @error('venue') is-invalid @enderror" 
                                   id="venue" 
                                   name="venue" 
                                   value="{{ old('venue', $event->venue) }}" 
                                   required>
                            @error('venue')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Adresse -->
                        <div class="col-md-6 mb-3">
                            <label for="address" class="form-label">Adresse complète *</label>
                            <input type="text" 
                                   class="form-control @error('address') is-invalid @enderror" 
                                   id="address" 
                                   name="address" 
                                   value="{{ old('address', $event->address) }}" 
                                   required>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Date -->
                        <div class="col-md-4 mb-3">
                            <label for="event_date" class="form-label">Date de l'événement *</label>
                            <input type="date" 
                                   class="form-control @error('event_date') is-invalid @enderror" 
                                   id="event_date" 
                                   name="event_date" 
                                   value="{{ old('event_date', $event->event_date ? $event->event_date->format('Y-m-d') : '') }}" 
                                   required>
                            @error('event_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Heure de début -->
                        <div class="col-md-4 mb-3">
                            <label for="event_time" class="form-label">Heure de début *</label>
                            <input type="time" 
                                   class="form-control @error('event_time') is-invalid @enderror" 
                                   id="event_time" 
                                   name="event_time" 
                                   value="{{ old('event_time', $event->event_time ? $event->event_time->format('H:i') : '') }}" 
                                   required>
                            @error('event_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Heure de fin -->
                        <div class="col-md-4 mb-3">
                            <label for="end_time" class="form-label">Heure de fin (optionnelle)</label>
                            <input type="time" 
                                   class="form-control @error('end_time') is-invalid @enderror" 
                                   id="end_time" 
                                   name="end_time" 
                                   value="{{ old('end_time', $event->end_time ? $event->end_time->format('H:i') : '') }}">
                            @error('end_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Image -->
                <div class="form-card">
                    <h5 class="fw-bold mb-4">
                        <i class="fas fa-image me-2" style="color: #FF6B35;"></i>
                        Image de l'événement
                    </h5>
                    
                    @if($event->image)
                        <div class="current-image mb-3">
                            <img src="{{ Storage::url($event->image) }}" 
                                 alt="Image actuelle" 
                                 class="image-preview">
                            <div class="mt-2">
                                <small class="text-muted">Image actuelle</small>
                            </div>
                        </div>
                    @endif
                    
                    <div class="mb-3">
                        <label for="image" class="form-label">Changer l'image (optionnel)</label>
                        <input type="file" 
                               class="form-control @error('image') is-invalid @enderror" 
                               id="image" 
                               name="image" 
                               accept="image/*">
                        @error('image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            Formats acceptés : JPEG, PNG, JPG, GIF. Taille max : 2MB.
                        </div>
                    </div>

                    <!-- Aperçu de la nouvelle image -->
                    <div id="imagePreview" style="display: none;">
                        <img id="preview" src="" class="image-preview">
                        <div class="mt-2">
                            <small class="text-muted">Nouvelle image</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Actions -->
                <div class="form-card">
                    <h6 class="fw-bold mb-3">Actions</h6>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-orange">
                            <i class="fas fa-save me-2"></i>Sauvegarder les modifications
                        </button>
                        
                        <a href="{{ route('promoteur.events.show', $event) }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Annuler
                        </a>
                    </div>
                </div>

                <!-- Statut actuel -->
                <div class="form-card">
                    <h6 class="fw-bold mb-3">Statut actuel</h6>
                    
                    <div class="alert alert-{{ $event->status === 'published' ? 'success' : 'warning' }}">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-{{ $event->status === 'published' ? 'check-circle' : 'clock' }} me-2"></i>
                            <div>
                                <strong>{{ ucfirst($event->status) }}</strong>
                                <br>
                                <small>
                                    @if($event->status === 'published')
                                        Votre événement est visible par le public
                                    @else
                                        Votre événement est en brouillon
                                    @endif
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    @if($event->status === 'draft')
                        <div class="d-grid">
                            <form method="POST" action="{{ route('promoteur.events.publish', $event) }}" 
                                  onsubmit="return confirm('Publier cet événement ?')">
                                @csrf
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-upload me-2"></i>Publier l'événement
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="d-grid">
                            <form method="POST" action="{{ route('promoteur.events.unpublish', $event) }}" 
                                  onsubmit="return confirm('Dépublier cet événement ?')">
                                @csrf
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-download me-2"></i>Dépublier
                                </button>
                            </form>
                        </div>
                    @endif
                </div>

                <!-- Statistiques -->
                <div class="form-card">
                    <h6 class="fw-bold mb-3">Statistiques</h6>
                    
                    <div class="row text-center">
                        <div class="col-6 mb-2">
                            <div class="fw-bold text-primary">{{ $event->ticketTypes->count() }}</div>
                            <small class="text-muted">Types de billets</small>
                        </div>
                        <div class="col-6 mb-2">
                            <div class="fw-bold text-success">{{ $event->getTicketsSoldCount() }}</div>
                            <small class="text-muted">Billets vendus</small>
                        </div>
                        <div class="col-12">
                            <div class="fw-bold" style="color: #FF6B35;">{{ number_format($event->totalRevenue()) }} F</div>
                            <small class="text-muted">Revenus</small>
                        </div>
                    </div>
                </div>

                <!-- Liens rapides -->
                <div class="form-card">
                    <h6 class="fw-bold mb-3">Liens rapides</h6>
                    
                    <div class="d-grid gap-2">
                        <a href="{{ route('promoteur.events.tickets.index', $event) }}" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-ticket-alt me-2"></i>Gérer les billets
                        </a>
                        
                        @if($event->status === 'published')
                            <a href="{{ route('events.show', $event) }}" class="btn btn-outline-primary btn-sm" target="_blank">
                                <i class="fas fa-eye me-2"></i>Voir sur le site
                            </a>
                        @endif
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
    // Aperçu de l'image avant upload
    const imageInput = document.getElementById('image');
    const preview = document.getElementById('preview');
    const imagePreview = document.getElementById('imagePreview');
    
    imageInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
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
});
</script>
@endpush